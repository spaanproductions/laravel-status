<?php

namespace SpaanProductions\LaravelStatus\Subscribers;

use Throwable;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Cache;
use SpaanProductions\LaravelStatus\Jobs\SyncStatusJob;

/**
 * Keeps the dashboard up to date between deploys: the health check route (/up, Laravel 11+) starts a sync in the
 * background once every `health-sync.hours`. Every other health check only costs one cache call.
 */
class HealthCheckSubscriber
{
	public const EVENT = 'Illuminate\Foundation\Events\DiagnosingHealth';

	public const CACHE_KEY = 'laravel-status:health-sync';

	public function handle($event): void
	{
		if ( ! config('laravel-status.health-sync.enabled', true) || is_null(config('laravel-status.token'))) {
			return;
		}

		try {
			// add() only stores the key when it isn't there yet, atomically: one health check per interval wins.
			$due = Cache::add(self::CACHE_KEY, time(), now()->addHours(max(1, (int) config('laravel-status.health-sync.hours', 6))));

			if ( ! $due) {
				return;
			}

			// Without a queue worker (the sync queue) the job would run inside the health check: run it after the
			// response instead, so the health check stays fast.
			if (config('queue.default') === 'sync') {
				SyncStatusJob::dispatchAfterResponse();
			} else {
				SyncStatusJob::dispatch();
			}
		} catch (Throwable $exception) {
			// The health check reports the site as down when a listener throws: a failing sync (e.g. the cache is
			// unreachable) must never do that.
			if (config('laravel-status.log-exceptions')) {
				report($exception);
			}
		}
	}

	public function subscribe(Dispatcher $events): void
	{
		// The event (and the /up route) exist from Laravel 11.
		if (class_exists(self::EVENT)) {
			$events->listen(self::EVENT, [self::class, 'handle']);
		}
	}
}

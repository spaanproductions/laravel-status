<?php

namespace SpaanProductions\LaravelStatus\Jobs;

use RuntimeException;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * The same sync as `laravel-status:sync` on a deploy, started by HealthCheckSubscriber.
 */
class SyncStatusJob implements ShouldQueue
{
	use Queueable;
	use Dispatchable;
	use InteractsWithQueue;

	public $tries = 1;

	public $timeout = 120;

	public function __construct()
	{
		$this->onQueue(config('laravel-status.queues.sync'));
	}

	public function handle(): void
	{
		// Through Artisan, so the console kernel is booted and the schedule (routes/console.php or the console
		// Kernel) is loaded, also when this runs after a web request: without it the scheduled tasks would be sent
		// empty, and the dashboard would mark them all inactive.
		$exitCode = Artisan::call('laravel-status:sync');

		if ($exitCode !== 0 && config('laravel-status.log-exceptions')) {
			report(new RuntimeException('laravel-status:sync failed: ' . trim(Artisan::output())));
		}
	}

	/** Tags for Laravel Telescope */
	public function tags(): array
	{
		return [
			'job:SyncStatusJob',
		];
	}
}

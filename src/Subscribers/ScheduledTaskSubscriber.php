<?php

namespace SpaanProductions\LaravelStatus\Subscribers;

use Illuminate\Events\Dispatcher;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use SpaanProductions\LaravelStatus\Jobs\ScheduledTaskEventLogJob;
use SpaanProductions\LaravelStatus\Helpers\Payloads\ScheduledTaskPayload;

class ScheduledTaskSubscriber
{
	public function handle($event): void
	{
		if ( ! config('laravel-status.scheduler-logging-enabled')) {
			return;
		}

		$data = ScheduledTaskPayload::fromEvent($event);
		dispatch(new ScheduledTaskEventLogJob($data->toArray()));
	}

	public function subscribe(Dispatcher $events): void
	{
		$events->listen([
			ScheduledTaskStarting::class,
			ScheduledTaskFinished::class,
			ScheduledTaskSkipped::class,
		], static::class . '@handle');

		if (class_exists(ScheduledTaskFailed::class)) {
			$events->listen(ScheduledTaskFailed::class, static::class . '@handle');
		}
	}
}

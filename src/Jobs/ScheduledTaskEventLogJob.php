<?php

namespace SpaanProductions\LaravelStatus\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\HttpClientException;

class ScheduledTaskEventLogJob implements ShouldQueue
{
	use Queueable;
	use Dispatchable;
	use SerializesModels;
	use InteractsWithQueue;

	public $tries = 1;

	public function __construct(
		public array $payload,
	) {
		//
	}

	public function handle(): void
	{
		if ( ! config('laravel-status.scheduler-logging-enabled')) {
			return;
		}

		if (is_null(config('laravel-status.token'))) {
			return;
		}

		if (empty(data_get($this->payload, 'task.command'))) {
			return;
		}

		try {
			Http::timeout(10)
				->retry(2, 10)
				->baseUrl(config('laravel-status.base-url'))
				->withHeaders([
					'Accept' => 'application/json',
				])
				->withToken(config('laravel-status.token'))
				->post('/api/task-event', $this->payload);
		} catch (HttpClientException $exception) {
			if (config('laravel-status.log-exceptions')) {
				// To the error tracker of the project (Flare, Bugsnag, the log): report() uses its exception handler.
				report($exception);
			}
		}
	}

	/** Tags for Laravel Telescope */
	public function tags(): array
	{
		return [
			'job:ScheduledTaskEventLogJob',
			'command:' . data_get($this->payload, 'task.command', 'unknown'),
		];
	}
}

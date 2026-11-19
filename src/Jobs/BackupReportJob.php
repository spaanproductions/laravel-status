<?php

namespace SpaanProductions\LaravelStatus\Jobs;

use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use SpaanProductions\LaravelStatus\Metrics\Host;
use SpaanProductions\LaravelStatus\Metrics\Environment;
use SpaanProductions\LaravelStatus\Backups\BackupInventory;

/**
 * Sends the backup overview to status.spaanproductions.nl after spatie/laravel-backup made (or failed to make) a backup.
 */
class BackupReportJob implements ShouldQueue
{
	use Queueable;
	use Dispatchable;
	use SerializesModels;
	use InteractsWithQueue;

	public $tries = 1;

	/**
	 * @param array{type: string, disk: string|null, name: string|null, error: string|null} $event
	 */
	public function __construct(
		public array $event,
	) {
		$this->onQueue(config('laravel-status.queues.backups'));
	}

	public function handle(BackupInventory $inventory): void
	{
		if ( ! config('laravel-status.backup-reporting-enabled', true)) {
			return;
		}

		if (is_null(config('laravel-status.token'))) {
			return;
		}

		$payload = [
			'host' => (new Host())->value(),
			'environment' => (new Environment())->value(),
			'backups' => $inventory->destinations(),
			'event' => $this->event,
		];

		try {
			Http::timeout(30)
				->retry(2, 1000)
				->baseUrl(config('laravel-status.base-url'))
				->withHeaders([
					'Accept' => 'application/json',
				])
				->withToken(config('laravel-status.token'))
				->post('/api/backups', $payload);
		} catch (Throwable $exception) {
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
			'job:BackupReportJob',
			'backup:' . ($this->event['type'] ?? 'unknown'),
		];
	}
}

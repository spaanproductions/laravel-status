<?php

namespace SpaanProductions\LaravelStatus\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use SpaanProductions\LaravelStatus\ShareableMetrics\Metric;
use SpaanProductions\LaravelStatus\ShareableMetrics\Metrics;

class LaravelStatusCommand extends Command
{
	public $signature = 'laravel-status:sync {--dry-run}';
	public $description = 'Send the stats of this application to the status dashboard (laravel-status.base-url).';

	public function handle(): int
	{
		$this->output->title('Sending the stats to ' . config('laravel-status.base-url') . '.');

		try {
			$data = collect([
				Metrics\Host::class,
				Metrics\Environment::class,
				Metrics\Name::class,
				Metrics\InstalledVersion::class,
				Metrics\Url::class,
				// Metrics\GitInfo::class,
				Metrics\InstalledPackages::class,
				Metrics\InstalledNpmPackages::class,
				Metrics\Dependencies::class,
				Metrics\NpmDependencies::class,
				Metrics\PhpVersion::class,
				Metrics\LaravelVersion::class,
				Metrics\ServerInfo::class,
				Metrics\ScheduledTasks::class,
				Metrics\LaravelAbout::class,
				Metrics\Backups::class,
			])->map(function (string $metricClass) {
				return new $metricClass();
			})->map(function (Metric $metric) {
				return $metric->toArray();
			})->mapWithKeys(function ($item) {
				return [
					key($item) => $item[key($item)],
				];
			});
		} catch (\Throwable $exception) {
			$this->output->error('Collecting data failed!');

			return self::FAILURE;
		}

		if ($this->option('dry-run')) {
			dump($data->toArray());

			return self::SUCCESS;
		}

		try {
			$response = Http::baseUrl(config('laravel-status.base-url'))
				->withHeaders([
					'Accept' => 'application/json',
				])
				->withToken(config('laravel-status.token'))
				->post('/api/stats', $data->toArray());
		} catch (\Throwable $exception) {
			$this->output->error('Posting data to API failed!');

			return self::FAILURE;
		}

		if ( ! $response->ok()) {
			$this->error('Something went wrong..');
			$this->error($response->json('message'));

			return self::FAILURE;
		}

		$this->info(sprintf('The current state is "%s", latest Laravel version is %s.', $response->json('status'), $response->json('desired_laravel_version')));

		$this->output->success('All done');

		return self::SUCCESS;
	}
}

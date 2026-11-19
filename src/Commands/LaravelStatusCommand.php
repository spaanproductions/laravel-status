<?php

namespace SpaanProductions\LaravelStatus\Commands;

use Throwable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use SpaanProductions\LaravelStatus\StatsCollector;

class LaravelStatusCommand extends Command
{
	public $signature = 'laravel-status:sync {--dry-run}';
	public $description = 'Send the stats of this application to the status dashboard (laravel-status.base-url).';

	public function handle(): int
	{
		$this->output->title('Sending the stats to ' . config('laravel-status.base-url') . '.');

		$collector = app(StatsCollector::class);

		try {
			$data = collect($collector->collect());
		} catch (Throwable $exception) {
			$this->output->error('Collecting the stats failed: ' . $exception->getMessage());

			if (config('laravel-status.log-exceptions')) {
				report($exception);
			}

			return self::FAILURE;
		}

		foreach ($collector->failures() as $name => $reason) {
			$this->warn(sprintf('Left out "%s": %s', $name, $reason));
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

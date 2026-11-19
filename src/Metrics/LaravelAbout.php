<?php

namespace SpaanProductions\LaravelStatus\Metrics;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Console\AboutCommand;
use Symfony\Component\Console\Output\BufferedOutput;

class LaravelAbout extends Metric
{
	public function name(): string
	{
		return 'about';
	}

	public function value(): mixed
	{
		return $this->getInformation()->toArray();
	}

	public function getInformation(): Collection
	{
		// Not cached: a sync right after a deploy must send the new state. Its own buffer, because this runs inside
		// laravel-status:sync, whose output Artisan::output() would otherwise share.
		$output = new BufferedOutput();
		Artisan::call(AboutCommand::class, ['--json' => true], $output);

		$information = new Collection(json_decode($output->fetch(), true, 512, JSON_THROW_ON_ERROR));

		return $this->formatInformation($information);
	}

	public function formatInformation(Collection $information): Collection
	{
		return $information
			->map(function (array $items, string $category) {
				return (new Collection($items))->transform(function (mixed $value, string $key) {
					return match (true) {
						is_bool($value), is_array($value) => $value,
						is_int($value) => number_format($value),
						is_string($value) => blank($value) ? '-' : $value,
						default => (string) $value,
					};
				});
			});
	}
}

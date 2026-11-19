<?php

namespace SpaanProductions\LaravelStatus;

use Throwable;
use RuntimeException;
use SpaanProductions\LaravelStatus\Metrics\Metric;

/**
 * Collects the payload of laravel-status:sync, one key per metric.
 *
 * A failing optional metric is left out (the dashboard keeps what it has for that key) and the rest is still sent; a
 * failing required metric makes the collecting fail, since the dashboard can't store a project without it.
 */
class StatsCollector
{
	/** @var list<class-string<Metric>> */
	public const METRICS = [
		Metrics\Host::class,
		Metrics\Environment::class,
		Metrics\Name::class,
		Metrics\InstalledVersion::class,
		Metrics\Url::class,
		Metrics\GitInfo::class,
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
	];

	/** @var array<string, string> The optional metrics left out by the last collect(), with the reason. */
	protected array $failures = [];

	/**
	 * @return array<string, mixed>
	 *
	 * @throws RuntimeException when a required metric fails
	 */
	public function collect(): array
	{
		$this->failures = [];
		$payload = [];

		foreach ($this->metrics() as $metric) {
			try {
				$payload[$metric->name()] = $metric->value();
			} catch (Throwable $exception) {
				if ($metric->required()) {
					throw new RuntimeException(sprintf('The required "%s" metric failed: %s', $metric->name(), $exception->getMessage()), 0, $exception);
				}

				$this->failures[$metric->name()] = $exception->getMessage();

				if (config('laravel-status.log-exceptions')) {
					report($exception);
				}
			}
		}

		return $payload;
	}

	/** @return array<string, string> */
	public function failures(): array
	{
		return $this->failures;
	}

	/** @return list<Metric> */
	protected function metrics(): array
	{
		return array_map(function (string $class) {
			return app($class);
		}, static::METRICS);
	}
}

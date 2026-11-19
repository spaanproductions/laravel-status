<?php

namespace SpaanProductions\LaravelStatus\Metrics;

use SpaanProductions\LaravelStatus\Heartbeats\Heartbeat;

/**
 * When the scheduler (schedule:run) last ran, so the dashboard can tell whether the cron runs at all. Null when it
 * never ran since the package was installed.
 */
class Scheduler extends Metric
{
	public function __construct(protected Heartbeat $heartbeat)
	{
	}

	public function name(): string
	{
		return 'scheduler';
	}

	public function value(): mixed
	{
		return [
			'last_run_at' => $this->heartbeat->lastAsString(Heartbeat::SCHEDULER),
		];
	}
}

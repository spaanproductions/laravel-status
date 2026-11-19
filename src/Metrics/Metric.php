<?php

namespace SpaanProductions\LaravelStatus\Metrics;

/**
 * One key of the payload of laravel-status:sync. StatsCollector holds the list of metrics.
 */
abstract class Metric
{
	/** The key in the payload. */
	abstract public function name(): string;

	/** The value; null when there is nothing to send. */
	abstract public function value(): mixed;

	/**
	 * A required metric that fails makes the whole sync fail: the dashboard can't store a project without it. An
	 * optional one is left out of the payload instead, so the dashboard keeps what it has.
	 */
	public function required(): bool
	{
		return false;
	}

	public function toArray(): array
	{
		return [
			$this->name() => $this->value(),
		];
	}
}

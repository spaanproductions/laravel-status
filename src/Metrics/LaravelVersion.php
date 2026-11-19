<?php

namespace SpaanProductions\LaravelStatus\Metrics;

class LaravelVersion extends Metric
{
	public function name(): string
	{
		return 'laravel-version';
	}

	public function required(): bool
	{
		return true;
	}

	public function value(): mixed
	{
		return app()->version();
	}
}

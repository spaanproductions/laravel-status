<?php

namespace SpaanProductions\LaravelStatus\Metrics;

class Environment extends Metric
{
	public function name(): string
	{
		return 'environment';
	}

	public function required(): bool
	{
		return true;
	}

	public function value(): mixed
	{
		return config('app.env');
	}
}

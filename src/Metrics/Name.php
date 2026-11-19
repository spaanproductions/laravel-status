<?php

namespace SpaanProductions\LaravelStatus\Metrics;

class Name extends Metric
{
	public function name(): string
	{
		return 'name';
	}

	public function required(): bool
	{
		return true;
	}

	public function value(): mixed
	{
		return config('app.name');
	}
}

<?php

namespace SpaanProductions\LaravelStatus\Metrics;

class Url extends Metric
{
	public function name(): string
	{
		return 'url';
	}

	public function required(): bool
	{
		return true;
	}

	public function value(): mixed
	{
		return url('/');
	}
}

<?php

namespace SpaanProductions\LaravelStatus\Metrics;

class PhpVersion extends Metric
{
	public function name(): string
	{
		return 'php-version';
	}

	public function required(): bool
	{
		return true;
	}

	public function value(): mixed
	{
		return phpversion();
	}
}

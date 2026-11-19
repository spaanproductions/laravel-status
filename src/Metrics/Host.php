<?php

namespace SpaanProductions\LaravelStatus\Metrics;

class Host extends Metric
{
	public function name(): string
	{
		return 'host';
	}

	public function required(): bool
	{
		return true;
	}

	public function value(): mixed
	{
		return parse_url(config('app.url'), PHP_URL_HOST);
	}
}

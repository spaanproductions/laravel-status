<?php

namespace SpaanProductions\LaravelStatus\ShareableMetrics\Metrics;

use SpaanProductions\LaravelStatus\ShareableMetrics\Metric;
use SpaanProductions\LaravelStatus\ShareableMetrics\CollectableMetric;

class Host extends Metric implements CollectableMetric
{
	public function name(): string
	{
		return 'host';
	}

	public function value()
	{
		return parse_url(config('app.url'), PHP_URL_HOST);
	}
}

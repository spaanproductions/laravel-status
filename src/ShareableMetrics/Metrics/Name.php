<?php

namespace SpaanProductions\LaravelStatus\ShareableMetrics\Metrics;

use SpaanProductions\LaravelStatus\ShareableMetrics\Metric;
use SpaanProductions\LaravelStatus\ShareableMetrics\CollectableMetric;

class Name extends Metric implements CollectableMetric
{
	public function name(): string
	{
		return 'name';
	}

	public function value()
	{
		return config('app.name');
	}
}

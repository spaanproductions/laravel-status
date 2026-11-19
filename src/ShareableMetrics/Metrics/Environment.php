<?php

namespace SpaanProductions\LaravelStatus\ShareableMetrics\Metrics;

use SpaanProductions\LaravelStatus\ShareableMetrics\Metric;
use SpaanProductions\LaravelStatus\ShareableMetrics\CollectableMetric;

class Environment extends Metric implements CollectableMetric
{
	public function name(): string
	{
		return 'environment';
	}

	public function value()
	{
		return config('app.env');
	}
}

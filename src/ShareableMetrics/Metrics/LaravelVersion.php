<?php

namespace SpaanProductions\LaravelStatus\ShareableMetrics\Metrics;

use SpaanProductions\LaravelStatus\ShareableMetrics\Metric;
use SpaanProductions\LaravelStatus\ShareableMetrics\CollectableMetric;

class LaravelVersion extends Metric implements CollectableMetric
{
	public function name(): string
	{
		return 'laravel-version';
	}

	public function value()
	{
		return app()->version();
	}
}

<?php

namespace SpaanProductions\LaravelStatus\ShareableMetrics\Metrics;

use SpaanProductions\LaravelStatus\ShareableMetrics\Metric;
use SpaanProductions\LaravelStatus\ShareableMetrics\CollectableMetric;

class Url extends Metric implements CollectableMetric
{
	public function name(): string
	{
		return 'url';
	}

	public function value()
	{
		return url('/');
	}
}

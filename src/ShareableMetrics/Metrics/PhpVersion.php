<?php

namespace SpaanProductions\LaravelStatus\ShareableMetrics\Metrics;

use SpaanProductions\LaravelStatus\ShareableMetrics\Metric;
use SpaanProductions\LaravelStatus\ShareableMetrics\CollectableMetric;

class PhpVersion extends Metric implements CollectableMetric
{
	public function name(): string
	{
		return 'php-version';
	}

	public function value()
	{
		return phpversion();
	}
}

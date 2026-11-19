<?php

namespace SpaanProductions\LaravelStatus\ShareableMetrics\Metrics;

use Composer\InstalledVersions;
use SpaanProductions\LaravelStatus\ShareableMetrics\Metric;
use SpaanProductions\LaravelStatus\ShareableMetrics\CollectableMetric;

class InstalledVersion extends Metric implements CollectableMetric
{
	public function name(): string
	{
		return 'version';
	}

	public function value()
	{
		return InstalledVersions::getVersion('spaanproductions/laravel-status');
	}
}

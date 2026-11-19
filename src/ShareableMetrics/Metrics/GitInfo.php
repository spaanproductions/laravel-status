<?php

namespace SpaanProductions\LaravelStatus\ShareableMetrics\Metrics;

use SpaanProductions\LaravelStatus\ShareableMetrics\Metric;
use SpaanProductions\LaravelStatus\ShareableMetrics\CollectableMetric;
use SpaanProductions\LaravelStatus\Helpers\SourceControl\GitInfoCollector;

class GitInfo extends Metric implements CollectableMetric
{
	public function name(): string
	{
		return 'git-info';
	}

	public function value()
	{
		return resolve(GitInfoCollector::class)->collect()->toArray();
	}
}

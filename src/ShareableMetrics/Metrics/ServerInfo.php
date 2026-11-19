<?php

namespace SpaanProductions\LaravelStatus\ShareableMetrics\Metrics;

use SpaanProductions\LaravelStatus\ShareableMetrics\Metric;
use SpaanProductions\LaravelStatus\ShareableMetrics\CollectableMetric;

class ServerInfo extends Metric implements CollectableMetric
{
	public function name(): string
	{
		return 'server-info';
	}

	public function value()
	{
		return [
			'os' => php_uname('s'),
			'hostname' => php_uname('n'),
			'release' => php_uname('r'),
			'version' => php_uname('v'),
			'machine' => php_uname('m'),
		];
	}
}

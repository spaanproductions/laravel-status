<?php

namespace SpaanProductions\LaravelStatus\Metrics;

use Composer\InstalledVersions;

class InstalledVersion extends Metric
{
	public function name(): string
	{
		return 'version';
	}

	public function value(): mixed
	{
		return InstalledVersions::getVersion('spaanproductions/laravel-status');
	}
}

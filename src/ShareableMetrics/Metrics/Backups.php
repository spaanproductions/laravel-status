<?php

namespace SpaanProductions\LaravelStatus\ShareableMetrics\Metrics;

use SpaanProductions\LaravelStatus\Backups\BackupInventory;
use SpaanProductions\LaravelStatus\ShareableMetrics\Metric;
use SpaanProductions\LaravelStatus\ShareableMetrics\CollectableMetric;

/**
 * The spatie/laravel-backup destinations and their backups, or null when that package is not used.
 */
class Backups extends Metric implements CollectableMetric
{
	public function name(): string
	{
		return 'backups';
	}

	public function value()
	{
		if ( ! config('laravel-status.backup-reporting-enabled', true)) {
			return null;
		}

		$inventory = app(BackupInventory::class);

		return $inventory->available() ? $inventory->destinations() : null;
	}
}

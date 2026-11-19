<?php

namespace SpaanProductions\LaravelStatus\Metrics;

use SpaanProductions\LaravelStatus\Backups\BackupInventory;

/**
 * The spatie/laravel-backup destinations and their backups, or null when that package is not used.
 */
class Backups extends Metric
{
	public function name(): string
	{
		return 'backups';
	}

	public function value(): mixed
	{
		if ( ! config('laravel-status.backup-reporting-enabled', true)) {
			return null;
		}

		$inventory = app(BackupInventory::class);

		return $inventory->available() ? $inventory->destinations() : null;
	}
}

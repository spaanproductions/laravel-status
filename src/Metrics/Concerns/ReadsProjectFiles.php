<?php

namespace SpaanProductions\LaravelStatus\Metrics\Concerns;

use Throwable;
use Illuminate\Support\Facades\File;

trait ReadsProjectFiles
{
	/**
	 * A JSON file of the project (relative to base_path()), or null when it is missing or not valid JSON.
	 */
	protected function readJson(string $file): ?array
	{
		try {
			$contents = json_decode(File::get(base_path($file)), true);
		} catch (Throwable $exception) {
			// A project without the file (no package.json, no vendor/composer) simply has nothing to send.
			return null;
		}

		return is_array($contents) ? $contents : null;
	}
}

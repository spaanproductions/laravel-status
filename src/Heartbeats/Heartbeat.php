<?php

namespace SpaanProductions\LaravelStatus\Heartbeats;

/**
 * The last time the scheduler ran and the last time a queue worker processed a job, as the modification time of a file
 * in storage/framework/laravel-status. A file, not the cache: deploy scripts often clear the cache, and the sync right
 * after a deploy must still know when the scheduler last ran. On a server with several machines each one has its own.
 */
class Heartbeat
{
	public const SCHEDULER = 'scheduler';

	public const QUEUE = 'queue';

	public function beat(string $name): void
	{
		$directory = dirname($this->path($name));

		if ( ! is_dir($directory)) {
			mkdir($directory, 0755, true);
		}

		touch($this->path($name));
	}

	/** The unix time of the last beat, or null when there was none (yet). */
	public function last(string $name): ?int
	{
		$path = $this->path($name);

		clearstatcache(true, $path);

		return is_file($path) ? filemtime($path) : null;
	}

	/** The last beat as ISO 8601 with the offset of the app timezone, or null. */
	public function lastAsString(string $name): ?string
	{
		$time = $this->last($name);

		return $time === null ? null : date(DATE_ATOM, $time);
	}

	protected function path(string $name): string
	{
		return storage_path('framework/laravel-status/' . $name);
	}
}

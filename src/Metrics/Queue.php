<?php

namespace SpaanProductions\LaravelStatus\Metrics;

use Throwable;
use Illuminate\Support\Facades\Queue as QueueManager;
use SpaanProductions\LaravelStatus\Heartbeats\Heartbeat;

/**
 * The health of the queue: the default connection, how many jobs wait on its default queue, how many failed jobs
 * there are and when a worker last processed a job. Each part is null when it can't be read, so the rest still goes.
 */
class Queue extends Metric
{
	public function __construct(protected Heartbeat $heartbeat)
	{
	}

	public function name(): string
	{
		return 'queue';
	}

	public function value(): mixed
	{
		return [
			'connection' => config('queue.default'),
			'size' => $this->attempt(function () {
				return QueueManager::connection()->size();
			}),
			'failed_jobs' => $this->attempt(function () {
				$failer = app('queue.failer');

				// Laravel 10.x and older don't count every failed job store; then the count is unknown.
				return method_exists($failer, 'count') ? $failer->count() : null;
			}),
			'last_processed_at' => $this->heartbeat->lastAsString(Heartbeat::QUEUE),
		];
	}

	/**
	 * @param callable(): ?int $read
	 */
	protected function attempt(callable $read): ?int
	{
		try {
			$value = $read();

			return $value === null ? null : (int) $value;
		} catch (Throwable $exception) {
			// E.g. Redis unreachable or no failed_jobs table: unknown, and the other parts are still sent.
			return null;
		}
	}
}

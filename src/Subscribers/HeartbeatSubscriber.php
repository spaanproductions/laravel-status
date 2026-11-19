<?php

namespace SpaanProductions\LaravelStatus\Subscribers;

use Throwable;
use Illuminate\Events\Dispatcher;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Console\Events\CommandStarting;
use SpaanProductions\LaravelStatus\Heartbeats\Heartbeat;

/**
 * Records when the scheduler last ran and when a queue worker last processed a job, so the dashboard can tell whether
 * they run at all. No HTTP: the sync sends the times along.
 */
class HeartbeatSubscriber
{
	/** At most one queue heartbeat per worker per this many seconds: a worker can process many jobs a second. */
	public const QUEUE_INTERVAL = 60;

	/** @var int|null The time of the last queue heartbeat of this process. */
	protected static ?int $lastQueueBeat = null;

	public function __construct(protected Heartbeat $heartbeat)
	{
	}

	public function handleCommandStarting(CommandStarting $event): void
	{
		// schedule:run starts every minute, also when no task is due (schedule:work starts it too).
		if ($event->command === 'schedule:run') {
			$this->beat(Heartbeat::SCHEDULER);
		}
	}

	public function handleJobProcessed(JobProcessed $event): void
	{
		if (static::$lastQueueBeat !== null && time() - static::$lastQueueBeat < static::QUEUE_INTERVAL) {
			return;
		}

		static::$lastQueueBeat = time();

		$this->beat(Heartbeat::QUEUE);
	}

	/** Forgets the throttle of this process (tests). */
	public static function reset(): void
	{
		static::$lastQueueBeat = null;
	}

	protected function beat(string $name): void
	{
		try {
			$this->heartbeat->beat($name);
		} catch (Throwable $exception) {
			// A heartbeat that can't be written (e.g. a read-only storage directory) must never break the scheduler or
			// a job; the dashboard then simply sees no heartbeat.
			if (config('laravel-status.log-exceptions')) {
				report($exception);
			}
		}
	}

	public function subscribe(Dispatcher $events): void
	{
		$events->listen(CommandStarting::class, [self::class, 'handleCommandStarting']);
		$events->listen(JobProcessed::class, [self::class, 'handleJobProcessed']);
	}
}

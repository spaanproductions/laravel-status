<?php

namespace SpaanProductions\LaravelStatus\Tests\Heartbeats;

use Illuminate\Support\Facades\File;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Console\Events\CommandStarting;
use Symfony\Component\Console\Input\ArrayInput;
use SpaanProductions\LaravelStatus\Metrics\Queue;
use SpaanProductions\LaravelStatus\Tests\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use SpaanProductions\LaravelStatus\Metrics\Scheduler;
use SpaanProductions\LaravelStatus\Heartbeats\Heartbeat;
use SpaanProductions\LaravelStatus\Subscribers\HeartbeatSubscriber;

class HeartbeatTest extends TestCase
{
	private string $storage;

	public function setUp(): void
	{
		parent::setUp();

		$this->storage = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'laravel-status-storage-' . uniqid();
		File::ensureDirectoryExists($this->storage);
		$this->app->useStoragePath($this->storage);
		HeartbeatSubscriber::reset();
	}

	public function tearDown(): void
	{
		File::deleteDirectory($this->storage);
		HeartbeatSubscriber::reset();

		parent::tearDown();
	}

	private function commandStarting(string $command): void
	{
		event(new CommandStarting($command, new ArrayInput([]), new BufferedOutput()));
	}

	private function jobProcessed(): void
	{
		event(new JobProcessed('redis', \Mockery::mock(\Illuminate\Contracts\Queue\Job::class)));
	}

	public function test_without_heartbeats_the_times_are_unknown()
	{
		$this->assertSame(['last_run_at' => null], app(Scheduler::class)->value());
		$this->assertNull(app(Queue::class)->value()['last_processed_at']);
	}

	public function test_schedule_run_records_the_scheduler_heartbeat()
	{
		$this->commandStarting('schedule:run');

		$lastRun = app(Scheduler::class)->value()['last_run_at'];

		$this->assertNotNull($lastRun);
		$this->assertEqualsWithDelta(time(), strtotime($lastRun), 5);
	}

	public function test_other_commands_are_no_scheduler_heartbeat()
	{
		$this->commandStarting('migrate');

		$this->assertNull(app(Scheduler::class)->value()['last_run_at']);
	}

	public function test_a_processed_job_records_the_queue_heartbeat()
	{
		$this->jobProcessed();

		$this->assertNotNull(app(Queue::class)->value()['last_processed_at']);
	}

	public function test_the_queue_heartbeat_is_written_at_most_once_a_minute_per_worker()
	{
		$this->jobProcessed();
		$path = $this->storage . '/framework/laravel-status/queue';
		touch($path, time() - 30);

		$this->jobProcessed();

		clearstatcache(true, $path);
		$this->assertSame(time() - 30, filemtime($path), 'A second job within a minute does not write again.');
	}

	public function test_a_heartbeat_that_cannot_be_written_never_breaks_the_scheduler()
	{
		$this->app->instance(Heartbeat::class, new class() extends Heartbeat {
			public function beat(string $name): void
			{
				throw new \RuntimeException('Read-only storage');
			}
		});

		$this->commandStarting('schedule:run');

		$this->assertNull(app(Scheduler::class)->value()['last_run_at']);
	}

	public function test_the_queue_metric_sends_the_connection_size_and_failed_jobs()
	{
		config()->set('queue.default', 'sync');

		$value = app(Queue::class)->value();

		$this->assertSame('sync', $value['connection']);
		$this->assertSame(0, $value['size']);
		$this->assertArrayHasKey('failed_jobs', $value);
	}

	public function test_an_unreadable_failed_jobs_store_is_unknown_and_the_rest_is_still_sent()
	{
		$this->app->instance('queue.failer', new class() {
			public function count(): int
			{
				throw new \RuntimeException('No failed_jobs table');
			}
		});

		$value = app(Queue::class)->value();

		$this->assertNull($value['failed_jobs']);
		$this->assertSame(config('queue.default'), $value['connection']);
	}
}

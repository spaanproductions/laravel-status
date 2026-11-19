<?php

namespace SpaanProductions\LaravelStatus\Tests\Subscribers;

use RuntimeException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use SpaanProductions\LaravelStatus\Tests\TestCase;
use SpaanProductions\LaravelStatus\Jobs\SyncStatusJob;
use SpaanProductions\LaravelStatus\Subscribers\HealthCheckSubscriber;

class HealthCheckSubscriberTest extends TestCase
{
	public function setUp(): void
	{
		parent::setUp();

		if ( ! class_exists(HealthCheckSubscriber::EVENT)) {
			$this->markTestSkipped('The health check event exists from Laravel 11.');
		}

		config()->set('laravel-status.token', 'secret-token');
		config()->set('queue.default', 'redis');
		// The parallel test runner defaults to the database store, without a cache table.
		config()->set('cache.default', 'array');
		Bus::fake();
	}

	private function checkHealth(): void
	{
		$event = HealthCheckSubscriber::EVENT;

		event(new $event());
	}

	public function test_the_subscriber_listens_to_the_health_check()
	{
		$this->assertArrayHasKey(HealthCheckSubscriber::EVENT, $this->app->make('events')->getRawListeners());
	}

	public function test_a_health_check_starts_a_sync_once_per_interval()
	{
		$this->checkHealth();
		$this->checkHealth();

		Bus::assertDispatchedTimes(SyncStatusJob::class, 1);

		$this->travel(6 * 60 + 1)->minutes();
		$this->checkHealth();

		Bus::assertDispatchedTimes(SyncStatusJob::class, 2);
	}

	public function test_the_interval_can_be_changed()
	{
		config()->set('laravel-status.health-sync.hours', 2);

		$this->checkHealth();
		$this->travel(2 * 60 + 1)->minutes();
		$this->checkHealth();

		Bus::assertDispatchedTimes(SyncStatusJob::class, 2);
	}

	public function test_nothing_is_started_when_turned_off()
	{
		config()->set('laravel-status.health-sync.enabled', false);

		$this->checkHealth();

		Bus::assertNotDispatched(SyncStatusJob::class);
	}

	public function test_nothing_is_started_without_a_token()
	{
		config()->set('laravel-status.token', null);

		$this->checkHealth();

		Bus::assertNotDispatched(SyncStatusJob::class);
	}

	public function test_without_a_queue_worker_the_sync_runs_after_the_response()
	{
		config()->set('queue.default', 'sync');

		$this->checkHealth();

		Bus::assertDispatchedAfterResponse(SyncStatusJob::class);
	}

	public function test_a_failure_never_breaks_the_health_check()
	{
		Cache::shouldReceive('add')->andThrow(new RuntimeException('Cache unreachable'));

		$this->checkHealth();

		Bus::assertNotDispatched(SyncStatusJob::class);
	}
}

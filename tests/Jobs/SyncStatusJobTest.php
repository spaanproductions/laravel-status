<?php

namespace SpaanProductions\LaravelStatus\Tests\Jobs;

use Illuminate\Support\Facades\Http;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Debug\ExceptionHandler;
use SpaanProductions\LaravelStatus\Tests\TestCase;
use SpaanProductions\LaravelStatus\Jobs\SyncStatusJob;

class SyncStatusJobTest extends TestCase
{
	public function setUp(): void
	{
		parent::setUp();

		config()->set('laravel-status.base-url', 'https://status.test');
		config()->set('laravel-status.token', 'secret-token');
	}

	public function test_it_sends_the_same_stats_as_the_sync_command_including_the_scheduled_tasks()
	{
		Http::fake(['https://status.test/api/stats' => Http::response(['status' => 'ok', 'desired_laravel_version' => '13.0.0'])]);
		app(Schedule::class)->command('inspire')->hourly();

		(new SyncStatusJob())->handle();

		Http::assertSent(function ($request) {
			return $request->url() === 'https://status.test/api/stats'
				&& in_array('inspire', array_column($request->data()['scheduled-tasks'], 'command'), true);
		});
	}

	public function test_a_failed_sync_is_reported_when_log_exceptions_is_on()
	{
		Http::fake(['*' => Http::response(['message' => 'Unauthenticated.'], 401)]);
		config()->set('laravel-status.log-exceptions', true);
		$this->mock(ExceptionHandler::class)->shouldReceive('report')->once();

		(new SyncStatusJob())->handle();
	}

	public function test_a_failed_sync_is_not_reported_by_default()
	{
		Http::fake(['*' => Http::response(['message' => 'Unauthenticated.'], 401)]);
		$this->mock(ExceptionHandler::class)->shouldNotReceive('report');

		(new SyncStatusJob())->handle();
	}
}

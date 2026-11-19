<?php

namespace SpaanProductions\LaravelStatus\Tests;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Events\ScheduledTaskStarting;

class LaravelStatusServiceProviderTest extends TestCase
{
	public function test_sync_command_is_registered()
	{
		$this->assertArrayHasKey('laravel-status:sync', Artisan::all());
	}

	public function test_scheduled_task_subscriber_is_wired_to_the_event_dispatcher()
	{
		$this->assertTrue(Event::hasListeners(ScheduledTaskStarting::class));
	}

	public function test_default_config_values_are_loaded()
	{
		$this->assertSame('https://status.spaanproductions.nl', config('laravel-status.base-url'));
		$this->assertNull(config('laravel-status.token'));
		$this->assertFalse(config('laravel-status.scheduler-logging-enabled'), 'Scheduler logging is opt-in.');
		$this->assertFalse(config('laravel-status.log-exceptions'));
	}

	public function test_the_config_can_be_published_with_both_tags()
	{
		$this->assertContains('laravel-status-config', \Illuminate\Support\ServiceProvider::publishableGroups());
		$this->assertContains('status-config', \Illuminate\Support\ServiceProvider::publishableGroups());
	}
}

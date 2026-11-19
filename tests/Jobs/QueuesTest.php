<?php

namespace SpaanProductions\LaravelStatus\Tests\Jobs;

use SpaanProductions\LaravelStatus\Tests\TestCase;
use SpaanProductions\LaravelStatus\Jobs\SyncStatusJob;
use SpaanProductions\LaravelStatus\Jobs\BackupReportJob;
use SpaanProductions\LaravelStatus\Jobs\ScheduledTaskEventLogJob;

class QueuesTest extends TestCase
{
	public function test_the_jobs_use_the_default_queue_by_default()
	{
		$this->assertNull((new ScheduledTaskEventLogJob([]))->queue);
		$this->assertNull((new BackupReportJob([]))->queue);
		$this->assertNull((new SyncStatusJob())->queue);
	}

	public function test_every_job_has_its_own_configurable_queue()
	{
		config()->set('laravel-status.queues.scheduler', 'status-scheduler');
		config()->set('laravel-status.queues.backups', 'status-backups');
		config()->set('laravel-status.queues.sync', 'status-sync');

		$this->assertSame('status-scheduler', (new ScheduledTaskEventLogJob([]))->queue);
		$this->assertSame('status-backups', (new BackupReportJob([]))->queue);
		$this->assertSame('status-sync', (new SyncStatusJob())->queue);
	}
}

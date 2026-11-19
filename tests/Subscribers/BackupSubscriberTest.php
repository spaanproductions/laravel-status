<?php

namespace SpaanProductions\LaravelStatus\Tests\Subscribers;

use Exception;
use Illuminate\Support\Facades\Bus;
use SpaanProductions\LaravelStatus\Tests\TestCase;
use SpaanProductions\LaravelStatus\Jobs\BackupReportJob;
use SpaanProductions\LaravelStatus\Subscribers\BackupSubscriber;
use SpaanProductions\LaravelStatus\Tests\Fakes\FakeBackupEvents;
use SpaanProductions\LaravelStatus\Tests\Fakes\FakeBackupDestination;

class BackupSubscriberTest extends TestCase
{
	private function assertReported(array $event): void
	{
		Bus::assertDispatched(BackupReportJob::class, function (BackupReportJob $job) use ($event) {
			return $job->event === $event;
		});
	}

	public function test_a_successful_backup_is_reported_with_laravel_backup_v10()
	{
		Bus::fake();

		(new BackupSubscriber())->handle(FakeBackupEvents::succeededV10('s3', 'Kledingwinkel'));

		$this->assertReported(['type' => 'succeeded', 'disk' => 's3', 'name' => 'Kledingwinkel', 'error' => null]);
	}

	public function test_a_failed_backup_is_reported_with_its_error_with_laravel_backup_v10()
	{
		Bus::fake();

		(new BackupSubscriber())->handle(FakeBackupEvents::failedV10(new Exception('mysqldump: Access denied')));

		$this->assertReported(['type' => 'failed', 'disk' => null, 'name' => null, 'error' => 'mysqldump: Access denied']);
	}

	public function test_backups_are_reported_with_laravel_backup_v9()
	{
		Bus::fake();

		$destination = FakeBackupDestination::create('s3', 'Kledingwinkel');

		(new BackupSubscriber())->handle(FakeBackupEvents::succeededV9($destination));
		(new BackupSubscriber())->handle(FakeBackupEvents::failedV9(new Exception('S3 timeout'), $destination));
		(new BackupSubscriber())->handle(FakeBackupEvents::failedV9(new Exception('Dump failed')));

		$this->assertReported(['type' => 'succeeded', 'disk' => 's3', 'name' => 'Kledingwinkel', 'error' => null]);
		$this->assertReported(['type' => 'failed', 'disk' => 's3', 'name' => 'Kledingwinkel', 'error' => 'S3 timeout']);
		$this->assertReported(['type' => 'failed', 'disk' => null, 'name' => null, 'error' => 'Dump failed']);
	}

	public function test_nothing_is_reported_when_turned_off()
	{
		config()->set('laravel-status.backup-reporting-enabled', false);
		Bus::fake();

		(new BackupSubscriber())->handle(FakeBackupEvents::succeededV10('s3', 'Kledingwinkel'));

		Bus::assertNotDispatched(BackupReportJob::class);
	}

	public function test_without_laravel_backup_no_listeners_are_added()
	{
		if (class_exists(BackupSubscriber::SUCCEEDED)) {
			$this->markTestSkipped('spatie/laravel-backup is installed.');
		}

		// Not hasListeners(): that also counts wildcard listeners, such as the one spatie/laravel-ray adds.
		$this->assertArrayNotHasKey(BackupSubscriber::SUCCEEDED, $this->app->make('events')->getRawListeners());
	}
}

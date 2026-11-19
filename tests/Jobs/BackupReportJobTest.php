<?php

namespace SpaanProductions\LaravelStatus\Tests\Jobs;

use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Debug\ExceptionHandler;
use SpaanProductions\LaravelStatus\Tests\TestCase;
use SpaanProductions\LaravelStatus\Jobs\BackupReportJob;
use SpaanProductions\LaravelStatus\Backups\BackupInventory;

class BackupReportJobTest extends TestCase
{
	private array $event = ['type' => 'failed', 'disk' => 's3', 'name' => 'Clothing4U', 'error' => 'S3 timeout'];

	public function setUp(): void
	{
		parent::setUp();

		config()->set('app.url', 'https://c4u.nu');
		config()->set('app.env', 'production');
		config()->set('laravel-status.base-url', 'https://status.test');
		config()->set('laravel-status.token', 'secret-token');
	}

	private function inventory(array $destinations): BackupInventory
	{
		return new class($destinations) extends BackupInventory {
			public function __construct(private array $fakeDestinations)
			{
			}

			public function destinations(): array
			{
				return $this->fakeDestinations;
			}
		};
	}

	public function test_it_posts_the_backups_and_the_event()
	{
		Http::fake(['https://status.test/api/backups' => Http::response(null, 204)]);

		$destinations = [['disk' => 's3', 'name' => 'Clothing4U', 'reachable' => true, 'used_storage' => 10.0, 'error' => null, 'backups' => []]];

		(new BackupReportJob($this->event))->handle($this->inventory($destinations));

		Http::assertSent(function ($request) use ($destinations) {
			return $request->url() === 'https://status.test/api/backups'
				&& $request->hasHeader('Authorization', 'Bearer secret-token')
				&& $request['host'] === 'c4u.nu'
				&& $request['environment'] === 'production'
				&& $request['backups'] === $destinations
				&& $request['event'] === $this->event;
		});
	}

	public function test_it_does_nothing_without_a_token()
	{
		Http::fake();
		config()->set('laravel-status.token', null);

		(new BackupReportJob($this->event))->handle($this->inventory([]));

		Http::assertNothingSent();
	}

	public function test_it_does_nothing_when_turned_off()
	{
		Http::fake();
		config()->set('laravel-status.backup-reporting-enabled', false);

		(new BackupReportJob($this->event))->handle($this->inventory([]));

		Http::assertNothingSent();
	}

	public function test_a_failing_request_does_not_break_the_backup()
	{
		Http::fake(['*' => Http::response('Down', 503)]);

		(new BackupReportJob($this->event))->handle($this->inventory([]));

		$this->addToAssertionCount(1);
	}

	public function test_a_failing_request_is_reported_when_log_exceptions_is_on()
	{
		Http::fake(['*' => Http::response('Down', 503)]);
		config()->set('laravel-status.log-exceptions', true);
		$this->mock(ExceptionHandler::class)->shouldReceive('report')->once();

		(new BackupReportJob($this->event))->handle($this->inventory([]));
	}

	public function test_a_failing_request_is_not_reported_by_default()
	{
		Http::fake(['*' => Http::response('Down', 503)]);
		$this->mock(ExceptionHandler::class)->shouldNotReceive('report');

		(new BackupReportJob($this->event))->handle($this->inventory([]));
	}
}

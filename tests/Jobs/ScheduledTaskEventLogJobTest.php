<?php

namespace SpaanProductions\LaravelStatus\Tests\Jobs;

use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Debug\ExceptionHandler;
use SpaanProductions\LaravelStatus\Tests\TestCase;
use SpaanProductions\LaravelStatus\Jobs\ScheduledTaskEventLogJob;

class ScheduledTaskEventLogJobTest extends TestCase
{
	private function validPayload(): array
	{
		return [
			'type' => 'ScheduledTaskStarting',
			'task' => [
				'command' => 'inspire',
			],
		];
	}

	public function test_does_not_send_http_when_scheduler_logging_disabled()
	{
		Http::fake();
		config()->set('laravel-status.scheduler-logging-enabled', false);
		config()->set('laravel-status.token', 'tkn');

		(new ScheduledTaskEventLogJob($this->validPayload()))->handle();

		Http::assertNothingSent();
	}

	public function test_does_not_send_http_when_token_is_null()
	{
		Http::fake();
		config()->set('laravel-status.scheduler-logging-enabled', true);
		config()->set('laravel-status.token', null);

		(new ScheduledTaskEventLogJob($this->validPayload()))->handle();

		Http::assertNothingSent();
	}

	public function test_does_not_send_http_when_task_command_is_empty()
	{
		Http::fake();
		config()->set('laravel-status.scheduler-logging-enabled', true);
		config()->set('laravel-status.token', 'tkn');

		(new ScheduledTaskEventLogJob([
			'type' => 'ScheduledTaskStarting',
			'task' => ['command' => ''],
		]))->handle();

		Http::assertNothingSent();
	}

	public function test_posts_payload_to_task_event_endpoint_with_bearer_token()
	{
		if ( ! class_exists(\GuzzleHttp\Psr7\Response::class)) {
			$this->markTestSkipped('guzzlehttp/guzzle is required to fake HTTP responses.');
		}

		Http::fake([
			'https://status.test/api/task-event' => Http::response([], 200),
		]);

		config()->set('laravel-status.scheduler-logging-enabled', true);
		config()->set('laravel-status.token', 'secret-token');
		config()->set('laravel-status.base-url', 'https://status.test');

		$payload = $this->validPayload();

		(new ScheduledTaskEventLogJob($payload))->handle();

		Http::assertSent(function ($request) use ($payload) {
			return $request->url() === 'https://status.test/api/task-event'
				&& $request->method() === 'POST'
				&& $request->hasHeader('Authorization', 'Bearer secret-token')
				&& $request->hasHeader('Accept', 'application/json')
				&& $request->data() === $payload;
		});
	}

	public function test_tags_include_job_marker_and_command_name()
	{
		$tags = (new ScheduledTaskEventLogJob($this->validPayload()))->tags();

		$this->assertSame(['job:ScheduledTaskEventLogJob', 'command:inspire'], $tags);
	}

	public function test_tags_fall_back_to_unknown_when_command_is_missing()
	{
		$tags = (new ScheduledTaskEventLogJob([]))->tags();

		$this->assertSame(['job:ScheduledTaskEventLogJob', 'command:unknown'], $tags);
	}

	public function test_a_failing_request_is_reported_when_log_exceptions_is_on()
	{
		Http::fake(['*' => Http::response('Down', 503)]);
		config()->set('laravel-status.scheduler-logging-enabled', true);
		config()->set('laravel-status.token', 'tkn');
		config()->set('laravel-status.log-exceptions', true);
		$this->mock(ExceptionHandler::class)->shouldReceive('report')->once();

		(new ScheduledTaskEventLogJob($this->validPayload()))->handle();
	}

	public function test_a_failing_request_is_not_reported_by_default()
	{
		Http::fake(['*' => Http::response('Down', 503)]);
		config()->set('laravel-status.scheduler-logging-enabled', true);
		config()->set('laravel-status.token', 'tkn');
		$this->mock(ExceptionHandler::class)->shouldNotReceive('report');

		(new ScheduledTaskEventLogJob($this->validPayload()))->handle();
	}

	public function test_does_not_send_http_by_default()
	{
		Http::fake();
		config()->set('laravel-status.token', 'tkn');

		(new ScheduledTaskEventLogJob($this->validPayload()))->handle();

		Http::assertNothingSent();
	}
}

<?php

namespace SpaanProductions\LaravelStatus\Tests\Commands;

use Illuminate\Support\Facades\Http;
use Symfony\Component\VarDumper\VarDumper;
use Illuminate\Http\Client\ConnectionException;
use SpaanProductions\LaravelStatus\StatsCollector;
use SpaanProductions\LaravelStatus\Tests\TestCase;

class LaravelStatusCommandTest extends TestCase
{
	public function setUp(): void
	{
		parent::setUp();

		config()->set('laravel-status.base-url', 'https://status.test');
		config()->set('laravel-status.token', 'secret-token');
	}

	public function test_it_posts_the_full_metric_payload_to_the_stats_endpoint()
	{
		if ( ! class_exists(\GuzzleHttp\Psr7\Response::class)) {
			$this->markTestSkipped('guzzlehttp/guzzle is required to fake HTTP responses.');
		}

		Http::fake([
			'https://status.test/api/stats' => Http::response([
				'status' => 'ok',
				'desired_laravel_version' => '11.0.0',
			], 200),
		]);

		// Without the mocked console output: that mock also swallows the output of the nested `about` command.
		$this->assertSame(0, $this->withoutMockingConsoleOutput()->artisan('laravel-status:sync'));

		Http::assertSent(function ($request) {
			if ($request->url() !== 'https://status.test/api/stats') {
				return false;
			}

			if ( ! $request->hasHeader('Authorization', 'Bearer secret-token')) {
				return false;
			}

			$body = $request->data();

			$expectedKeys = [
				'host', 'environment', 'name', 'version', 'url', 'packages',
				'php-version', 'laravel-version', 'server-info',
				'scheduled-tasks', 'about', 'backups', 'git-info',
			];

			foreach ($expectedKeys as $key) {
				if ( ! array_key_exists($key, $body)) {
					return false;
				}
			}

			return true;
		});
	}

	public function test_dry_run_returns_success_without_issuing_http()
	{
		Http::fake();

		$originalHandler = VarDumper::setHandler(function () {
			// suppress dump() output for this test only
		});

		try {
			$this->artisan('laravel-status:sync', ['--dry-run' => true])->assertExitCode(0);
		} finally {
			VarDumper::setHandler($originalHandler);
		}

		Http::assertNothingSent();
	}

	public function test_it_fails_on_non_2xx_response()
	{
		if ( ! class_exists(\GuzzleHttp\Psr7\Response::class)) {
			$this->markTestSkipped('guzzlehttp/guzzle is required to fake HTTP responses.');
		}

		Http::fake([
			'https://status.test/api/stats' => Http::response(['message' => 'nope'], 500),
		]);

		$this->artisan('laravel-status:sync')->assertExitCode(1);
	}

	public function test_it_fails_on_http_exception()
	{
		Http::fake(function () {
			throw new ConnectionException('boom');
		});

		$this->artisan('laravel-status:sync')->assertExitCode(1);
	}

	public function test_the_collector_has_every_metric_the_dashboard_knows()
	{
		$names = array_map(function (string $class) {
			return app($class)->name();
		}, StatsCollector::METRICS);

		$this->assertEqualsCanonicalizing([
			'host', 'environment', 'name', 'version', 'url', 'git-info', 'packages', 'npm-packages', 'dependencies',
			'npm-dependencies', 'php-version', 'laravel-version', 'server-info', 'scheduled-tasks', 'about', 'backups',
			'queue', 'scheduler',
		], $names);
	}
}

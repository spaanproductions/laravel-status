<?php

namespace SpaanProductions\LaravelStatus\Tests;

use RuntimeException;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Debug\ExceptionHandler;
use SpaanProductions\LaravelStatus\Metrics\Host;
use SpaanProductions\LaravelStatus\Metrics\Metric;
use SpaanProductions\LaravelStatus\StatsCollector;
use SpaanProductions\LaravelStatus\Metrics\GitInfo;

class StatsCollectorTest extends TestCase
{
	public function setUp(): void
	{
		parent::setUp();

		config()->set('laravel-status.base-url', 'https://status.test');
		config()->set('laravel-status.token', 'secret-token');
	}

	/** Replaces the metric with one that throws, keeping its name and whether it is required. */
	private function failing(string $metric): void
	{
		$original = new $metric();

		$this->app->instance($metric, new class($original->name(), $original->required()) extends Metric {
			public function __construct(private string $key, private bool $isRequired)
			{
			}

			public function name(): string
			{
				return $this->key;
			}

			public function required(): bool
			{
				return $this->isRequired;
			}

			public function value(): mixed
			{
				throw new RuntimeException('Broken collector');
			}
		});
	}

	public function test_a_failing_optional_metric_is_left_out_and_the_rest_is_collected()
	{
		$this->failing(GitInfo::class);
		$collector = app(StatsCollector::class);

		$payload = $collector->collect();

		$this->assertArrayNotHasKey('git-info', $payload, 'The dashboard keeps what it has for a key that is not sent.');
		$this->assertArrayHasKey('packages', $payload);
		$this->assertSame(['git-info' => 'Broken collector'], $collector->failures());
	}

	public function test_a_failing_optional_metric_is_reported_when_log_exceptions_is_on()
	{
		$this->failing(GitInfo::class);
		config()->set('laravel-status.log-exceptions', true);
		$this->mock(ExceptionHandler::class)->shouldReceive('report')->once();

		app(StatsCollector::class)->collect();
	}

	public function test_a_failing_required_metric_fails_the_collecting()
	{
		$this->failing(Host::class);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The required "host" metric failed: Broken collector');

		app(StatsCollector::class)->collect();
	}

	public function test_the_sync_still_sends_without_a_failing_optional_metric()
	{
		Http::fake(['https://status.test/api/stats' => Http::response(['status' => 'ok', 'desired_laravel_version' => '13.0.0'])]);
		$this->failing(GitInfo::class);

		$this->artisan('laravel-status:sync')
			->expectsOutputToContain('Left out "git-info": Broken collector')
			->assertExitCode(0);

		Http::assertSent(function ($request) {
			return ! array_key_exists('git-info', $request->data());
		});
	}

	public function test_the_sync_fails_without_a_required_metric()
	{
		Http::fake();
		$this->failing(Host::class);

		$this->artisan('laravel-status:sync')->assertExitCode(1);

		Http::assertNothingSent();
	}
}

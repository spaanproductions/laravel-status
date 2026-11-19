<?php

namespace SpaanProductions\LaravelStatus\Tests;

use Composer\InstalledVersions;
use Illuminate\Console\Scheduling\Schedule;
use SpaanProductions\LaravelStatus\LaravelStatus;

class LaravelStatusTest extends TestCase
{
	public function test_version_returns_the_installed_composer_version()
	{
		$expected = InstalledVersions::getVersion('spaanproductions/laravel-status');

		$this->assertSame($expected, (new LaravelStatus())->version());
	}

	public function test_sanitised_command_strips_quotes_php_binary_and_artisan_token()
	{
		$stats = new LaravelStatus();

		$command = sprintf("'%s' 'artisan' inspire", PHP_BINARY);

		$this->assertSame('inspire', $stats->sanitisedCommand($command));
		$this->assertSame('', $stats->sanitisedCommand(null));
		$this->assertSame('', $stats->sanitisedCommand(''));
		$this->assertSame('schedule:run', $stats->sanitisedCommand('"artisan" schedule:run'));
	}

	public function test_fingerprint_task_returns_stable_laravel_status_prefixed_sha1()
	{
		$event = app(Schedule::class)->command('inspire')->daily();

		$first = (new LaravelStatus())->fingerprintTask($event);
		$second = (new LaravelStatus())->fingerprintTask($event);

		$this->assertSame($first, $second);
		$this->assertStringStartsWith('laravel-status:', $first);
		$this->assertSame(40, strlen(substr($first, strlen('laravel-status:'))));
	}

	public function test_fingerprint_callback_event_includes_string_command_in_hash()
	{
		$schedule = app(Schedule::class);
		$eventA = $schedule->call('Foo@bar')->everyMinute();
		$eventB = $schedule->call('Foo@baz')->everyMinute();

		$stats = new LaravelStatus();

		$this->assertNotSame(
			$stats->fingerprintTask($eventA),
			$stats->fingerprintTask($eventB)
		);
	}

	public function test_fingerprint_callback_event_sets_extra_with_file_and_line_for_closures()
	{
		$event = app(Schedule::class)->call(function () {
			return 'noop';
		})->everyMinute();

		$fingerprint = (new LaravelStatus())->fingerprintTask($event);

		$this->assertStringStartsWith('laravel-status:', $fingerprint);
		$this->assertIsArray((array) $event->extra);
		$this->assertArrayHasKey('file', (array) $event->extra);
		$this->assertArrayHasKey('line', (array) $event->extra);
		$this->assertMatchesRegularExpression('/^\d+ to \d+$/', $event->extra['line']);
	}

	public function test_fingerprint_callback_event_uses_class_name_for_invokable_object()
	{
		$schedule = app(Schedule::class);
		$invokable = new InvokableCallback();
		$event = $schedule->call($invokable)->everyMinute();

		$fingerprint = (new LaravelStatus())->fingerprintTask($event);

		$expected = sprintf(
			'laravel-status:%s',
			sha1(str_replace('..', '.', $event->expression . '.' . get_class($invokable) . '.' . $event->description))
		);

		$this->assertSame($expected, $fingerprint);
	}

	public function test_fingerprint_task_for_non_callback_event_uses_expression_command_and_description()
	{
		$event = app(Schedule::class)->command('inspire')->daily()->description('Inspire me');

		$expected = sprintf(
			'laravel-status:%s',
			sha1(trim($event->expression . '.' . $event->command . '.' . $event->description, '.'))
		);

		$this->assertSame($expected, (new LaravelStatus())->fingerprintTask($event));
	}
}

class InvokableCallback
{
	public function __invoke()
	{
		return 'invoked';
	}
}

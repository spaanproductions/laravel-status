<?php

namespace SpaanProductions\LaravelStatus\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SpaanProductions\LaravelStatus\LaravelStatusServiceProvider;

class TestCase extends Orchestra
{
	public function setUp(): void
	{
		parent::setUp();

		// Static state of the fake laravel-backup destination must not leak from one test into the next.
		Fakes\FakeBackupDestination::$disks = [];
	}

	protected function getPackageProviders($app)
	{
		return [
			LaravelStatusServiceProvider::class,
		];
	}

	public function getEnvironmentSetUp($app)
	{
		config()->set('database.default', 'testing');
	}
}

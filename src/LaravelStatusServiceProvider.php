<?php

namespace SpaanProductions\LaravelStatus;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use SpaanProductions\LaravelStatus\Subscribers\BackupSubscriber;
use SpaanProductions\LaravelStatus\Commands\LaravelStatusCommand;
use SpaanProductions\LaravelStatus\Subscribers\HeartbeatSubscriber;
use SpaanProductions\LaravelStatus\Subscribers\HealthCheckSubscriber;
use SpaanProductions\LaravelStatus\Subscribers\ScheduledTaskSubscriber;

class LaravelStatusServiceProvider extends PackageServiceProvider
{
	public function configurePackage(Package $package): void
	{
		/*
		 * This class is a Package Service Provider
		 * More info: https://github.com/spatie/laravel-package-tools
		 */

		$package
			->name('laravel-status')
			->hasConfigFile('laravel-status')
			->hasCommands([
				LaravelStatusCommand::class,
			]);
	}

	public function packageBooted(): void
	{
		// package-tools names the tag after the short name ("status-config"); this is the tag the docs use.
		$this->publishes([
			__DIR__ . '/../config/laravel-status.php' => config_path('laravel-status.php'),
		], 'laravel-status-config');
	}

	public function bootingPackage(): void
	{
		// The health check is a web request: listen outside the console as well.
		$this->app->make('events')->subscribe(HealthCheckSubscriber::class);

		if ($this->app->runningInConsole()) {
			$this->app->make('events')->subscribe(ScheduledTaskSubscriber::class);

			// spatie/laravel-backup runs its backups from the console (backup:run).
			$this->app->make('events')->subscribe(BackupSubscriber::class);

			// The scheduler and the queue workers run from the console.
			$this->app->make('events')->subscribe(HeartbeatSubscriber::class);
		}
	}
}

<?php

namespace SpaanProductions\LaravelStatus\Tests\ShareableMetrics;

use Composer\InstalledVersions;
use SpaanProductions\LaravelStatus\Tests\TestCase;
use SpaanProductions\LaravelStatus\ShareableMetrics\Metrics\Url;
use SpaanProductions\LaravelStatus\ShareableMetrics\Metrics\Host;
use SpaanProductions\LaravelStatus\ShareableMetrics\Metrics\Name;
use SpaanProductions\LaravelStatus\ShareableMetrics\Metrics\PhpVersion;
use SpaanProductions\LaravelStatus\ShareableMetrics\Metrics\ServerInfo;
use SpaanProductions\LaravelStatus\ShareableMetrics\Metrics\Environment;
use SpaanProductions\LaravelStatus\ShareableMetrics\Metrics\LaravelVersion;
use SpaanProductions\LaravelStatus\ShareableMetrics\Metrics\InstalledVersion;

class SimpleMetricsTest extends TestCase
{
	public function test_host_metric_parses_host_from_configured_app_url()
	{
		config()->set('app.url', 'https://example.com/some/path');

		$metric = new Host();

		$this->assertSame('host', $metric->name());
		$this->assertSame('example.com', $metric->value());
	}

	public function test_environment_metric_returns_configured_environment()
	{
		config()->set('app.env', 'staging');

		$metric = new Environment();

		$this->assertSame('environment', $metric->name());
		$this->assertSame('staging', $metric->value());
	}

	public function test_name_metric_returns_configured_app_name()
	{
		config()->set('app.name', 'Acme');

		$metric = new Name();

		$this->assertSame('name', $metric->name());
		$this->assertSame('Acme', $metric->value());
	}

	public function test_url_metric_returns_root_url()
	{
		$metric = new Url();

		$this->assertSame('url', $metric->name());
		$this->assertSame(url('/'), $metric->value());
	}

	public function test_php_version_metric_matches_phpversion()
	{
		$metric = new PhpVersion();

		$this->assertSame('php-version', $metric->name());
		$this->assertSame(phpversion(), $metric->value());
	}

	public function test_laravel_version_metric_matches_application_version()
	{
		$metric = new LaravelVersion();

		$this->assertSame('laravel-version', $metric->name());
		$this->assertSame(app()->version(), $metric->value());
	}

	public function test_server_info_metric_returns_php_uname_breakdown()
	{
		$metric = new ServerInfo();
		$value = $metric->value();

		$this->assertSame('server-info', $metric->name());
		$this->assertIsArray($value);
		$this->assertSame(
			['os', 'hostname', 'release', 'version', 'machine'],
			array_keys($value)
		);
		$this->assertSame(php_uname('s'), $value['os']);
		$this->assertSame(php_uname('n'), $value['hostname']);
		$this->assertSame(php_uname('m'), $value['machine']);
	}

	public function test_installed_version_metric_returns_composer_installed_version()
	{
		$metric = new InstalledVersion();

		$this->assertSame('version', $metric->name());
		$this->assertSame(InstalledVersions::getVersion('spaanproductions/laravel-status'), $metric->value());
	}

	public function test_metric_to_array_wraps_name_and_value()
	{
		config()->set('app.url', 'https://example.com');

		$this->assertSame(['host' => 'example.com'], (new Host())->toArray());
	}
}

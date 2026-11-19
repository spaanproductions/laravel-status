<?php

namespace SpaanProductions\LaravelStatus\Metrics;

use RuntimeException;
use ReflectionExtension;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Composer\InstalledVersions;
use SpaanProductions\LaravelStatus\Metrics\Concerns\ReadsProjectFiles;

class InstalledPackages extends Metric
{
	use ReadsProjectFiles;

	public function name(): string
	{
		return 'packages';
	}

	public function required(): bool
	{
		return true;
	}

	public function value(): mixed
	{
		$composerJson = $this->readJson('composer.json');

		if ($composerJson === null) {
			throw new RuntimeException('composer.json could not be read.');
		}

		$packages = collect(Arr::get($composerJson, 'require', []))
			->map(function ($constraint, $package) {
				return [
					'package' => $package,
					'constraint' => $constraint,
					'is_dev' => false,
					'version' => $this->getVersion($package),
				];
			});

		$devPackages = collect(Arr::get($composerJson, 'require-dev', []))
			->map(function ($constraint, $package) {
				return [
					'package' => $package,
					'constraint' => $constraint,
					'is_dev' => true,
					'version' => $this->getVersion($package),
				];
			});


		return $packages->merge($devPackages)->toArray();
	}

	protected function getVersion($package)
	{
		if ($package === 'php') {
			return phpversion();
		}

		if (Str::startsWith($package, 'ext-')) {
			try {
				return (new ReflectionExtension(str_replace('ext-', '', $package)))
					->getVersion();
			} catch (\Exception $exception) {
				//
			}
		}

		return InstalledVersions::isInstalled($package) ? InstalledVersions::getVersion($package) : null;
	}
}

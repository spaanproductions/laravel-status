<?php

namespace SpaanProductions\LaravelStatus\Metrics;

use Illuminate\Support\Arr;
use SpaanProductions\LaravelStatus\Metrics\Concerns\ReadsProjectFiles;

/**
 * The NPM packages in package.json (dependencies and devDependencies), with the version that package-lock.json pins.
 * Null when the project has no readable package.json, so the dashboard keeps what it already has.
 */
class InstalledNpmPackages extends Metric
{
	use ReadsProjectFiles;

	public function name(): string
	{
		return 'npm-packages';
	}

	public function value(): mixed
	{
		$packageJson = $this->readJson('package.json');

		if ( ! is_array($packageJson)) {
			return null;
		}

		$lock = $this->readJson('package-lock.json') ?: [];

		$packages = collect(Arr::get($packageJson, 'dependencies', []))
			->map(function ($constraint, $package) use ($lock) {
				return $this->entry($package, $constraint, false, $lock);
			});

		$devPackages = collect(Arr::get($packageJson, 'devDependencies', []))
			->map(function ($constraint, $package) use ($lock) {
				return $this->entry($package, $constraint, true, $lock);
			});

		return $packages->merge($devPackages)->values()->toArray();
	}

	protected function entry(string $package, $constraint, bool $isDev, array $lock): array
	{
		return [
			'package' => $package,
			'constraint' => $constraint,
			'is_dev' => $isDev,
			'version' => $this->getVersion($package, $lock),
		];
	}

	/**
	 * Lockfile v2 and v3 list every package under "packages", lockfile v1 under "dependencies".
	 */
	protected function getVersion(string $package, array $lock): ?string
	{
		return Arr::get($lock, 'packages.node_modules/' . $package . '.version')
			?? Arr::get($lock, 'dependencies.' . $package . '.version');
	}
}

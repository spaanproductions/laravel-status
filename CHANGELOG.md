# Changelog

All notable changes to `spaanproductions/laravel-status` will be documented in this file. Up to 2.4.0 the package was
`mtvtd/laravel-stats`.

## 3.0.1 - 2026-11-19

- Scheduler logging is now opt-in: set `LARAVEL_STATUS_SCHEDULER_LOGGING_ENABLED=true` to send the scheduled task
  events. The scheduled tasks themselves are still sent with every `laravel-status:sync`.
- CI: `spatie/ray` and `spatie/laravel-ray` (via orchestra/workbench) have a minimum version in `require-dev`, so the
  `prefer-lowest` runs no longer install versions with PHP 8.4 deprecations.

## 3.0.0 - 2026-11-19

The package is now `spaanproductions/laravel-status` and reports to status.spaanproductions.nl. Upgrading from
`mtvtd/laravel-stats`:

- `composer remove mtvtd/laravel-stats && composer require spaanproductions/laravel-status`.
- The command is `php artisan laravel-status:sync` (was `mtvtd:laravel-stats`); update the deploy script.
- The env keys are `LARAVEL_STATUS_*` (were `LARAVEL_STATS_*`), the config file is `config/laravel-status.php`
  (was `config/laravel-stats.php`), the namespace is `SpaanProductions\LaravelStatus` (was `Mtvtd\LaravelStats`).
- With `LARAVEL_STATUS_LOG_EXCEPTIONS=true`, failed requests go to `report()` (the project's error tracker) instead of
  Bugsnag or the log.
- Removed the unused `ManageLaravelTeam` metric.

## 2.4.0 - 2026-11-06

- Also report the NPM packages in `package-lock.json` that are not in `package.json` (the ones that come along with them), as `npm-dependencies`, with the `package.json` packages that pull them in, so status.mtvtd.nl can find vulnerabilities in those as well. Needs a v2 or v3 lockfile (npm 7 and newer).

## 2.3.0 - 2026-11-05

- Report the NPM packages from `package.json` (with the versions pinned in `package-lock.json`) as `npm-packages`, so status.mtvtd.nl can show them.

## 2.2.0 - 2026-11-05

- Also report the installed packages that are not in `composer.json` (the ones that come along with them), with the `composer.json` packages that pull them in, so status.mtvtd.nl can find vulnerabilities in those as well.

## 2.1.1 - 2026-11-04

- Fix the CI: the tests no longer run in separate processes and the fake backup destination is reset between tests.

## 2.1.0 - 2026-11-04

- Report spatie/laravel-backup backups (v8–v10) to status.mtvtd.nl: with `mtvtd:laravel-stats`, and after every backup run (also failed ones, with the error). Turn off with `LARAVEL_STATS_BACKUP_REPORTING_ENABLED=false`.

## 1.0.0

- Initial release as `mtvtd/laravel-stats`.

# Laravel Status

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spaanproductions/laravel-status.svg?style=flat-square)](https://packagist.org/packages/spaanproductions/laravel-status)
[![Tests](https://github.com/spaanproductions/laravel-status/actions/workflows/run-tests.yml/badge.svg)](https://github.com/spaanproductions/laravel-status/actions/workflows/run-tests.yml)
[![Check & fix styling](https://github.com/spaanproductions/laravel-status/actions/workflows/php-cs-fixer.yml/badge.svg)](https://github.com/spaanproductions/laravel-status/actions/workflows/php-cs-fixer.yml)

Sends the state of a Laravel application to the [Spaan Productions](https://spaanproductions.nl) status dashboard
(status.spaanproductions.nl): the Laravel and PHP versions, the Composer and NPM packages (checked there against the
security advisories), the scheduled tasks and every run of them, and the spatie/laravel-backup backups.

Supports PHP 8.0+ and Laravel 9 to 13.

## Installation

```bash
composer require spaanproductions/laravel-status
```

Create a token for the project on the Projects page of the dashboard ("Nieuw project token") and add it to `.env`:

```dotenv
LARAVEL_STATUS_TOKEN=[project-token]
```

Add the sync command to the deploy script, after the migrations:

```bash
php artisan laravel-status:sync
```

The project shows up on the dashboard after the first sync. `php artisan laravel-status:sync --dry-run` shows what
would be sent, without sending it.

The scheduled task events go through the queue, so the project needs a running queue worker.

## Configuration

Everything works without publishing the config. To change it:

```bash
php artisan vendor:publish --tag="laravel-status-config"
```

| Env | Default | |
|---|---|---|
| `LARAVEL_STATUS_TOKEN` | – | The project token; without it nothing is sent |
| `LARAVEL_STATUS_BASE_URL` | `https://status.spaanproductions.nl` | Only for testing against another dashboard |
| `LARAVEL_STATUS_SCHEDULER_LOGGING_ENABLED` | `true` | Send every start, finish, skip and failure of a scheduled task |
| `LARAVEL_STATUS_BACKUP_REPORTING_ENABLED` | `true` | Send the spatie/laravel-backup backups |
| `LARAVEL_STATUS_LOG_EXCEPTIONS` | `false` | Report failed requests to the project's error tracker (`report()`) |

## Packages

`laravel-status:sync` sends the packages in `composer.json` with their installed version, and every other installed
package that comes along with them (read from `vendor/composer/installed.json`), with the `composer.json` packages that
pull it in. The dashboard checks all of them against the Packagist security advisories, the same source as
`composer audit`.

The same goes for NPM: the packages in `package.json` with the version from `package-lock.json`, and every other
package in the lockfile with the `package.json` packages that pull it in (lockfile v2 or v3, npm 7 and newer). The
dashboard checks those against the npm registry advisories, the same source as `npm audit`.

## Backups

When the project uses [spatie/laravel-backup](https://github.com/spatie/laravel-backup) (v8, v9 or v10), the package
reports its backups, whatever disk they are on (S3, local, ...):

- `laravel-status:sync` sends every backup destination with its newest backups (date and size).
- After every `backup:run`, the result is sent right away, including the error when the backup failed. This goes
  through the queue, like the scheduler logging.

Nothing changes for projects without laravel-backup.

## Upgrading from mtvtd/laravel-stats

See the 3.0.0 entry in the [CHANGELOG](CHANGELOG.md): a new package name, command, config file and env keys.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Spaan Productions](https://github.com/spaanproductions)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

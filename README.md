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

Between deploys the package keeps the dashboard up to date through Laravel's health check route (`/up`, Laravel 11+):
the first health check after every 6 hours starts the same sync in the background (a queued job; with the `sync` queue
it runs after the response). Every other health check only costs one cache call, and a failing sync never makes the
health check fail. Point the uptime monitor of the dashboard at `/up` to use this.

To also send every run of the scheduled tasks (start, finish, skip, failure), turn on the scheduler logging. It goes
through the queue, so the project needs a running queue worker:

```dotenv
LARAVEL_STATUS_SCHEDULER_LOGGING_ENABLED=true
```

## Configuration

Everything works without publishing the config. To change it:

```bash
php artisan vendor:publish --tag="laravel-status-config"
```

| Env | Default | |
|---|---|---|
| `LARAVEL_STATUS_TOKEN` | – | The project token; without it nothing is sent |
| `LARAVEL_STATUS_BASE_URL` | `https://status.spaanproductions.nl` | Only for testing against another dashboard |
| `LARAVEL_STATUS_SCHEDULER_LOGGING_ENABLED` | `false` | Send every start, finish, skip and failure of a scheduled task (needs a queue worker) |
| `LARAVEL_STATUS_BACKUP_REPORTING_ENABLED` | `true` | Send the spatie/laravel-backup backups |
| `LARAVEL_STATUS_HEALTH_SYNC_ENABLED` | `true` | Sync from the health check (`/up`) between deploys |
| `LARAVEL_STATUS_HEALTH_SYNC_HOURS` | `6` | At most one health check sync per this many hours |
| `LARAVEL_STATUS_SCHEDULER_QUEUE` | default queue | Queue of the scheduled task events |
| `LARAVEL_STATUS_BACKUPS_QUEUE` | default queue | Queue of the backup reports |
| `LARAVEL_STATUS_SYNC_QUEUE` | default queue | Queue of the health check sync |
| `LARAVEL_STATUS_LOG_EXCEPTIONS` | `false` | Report failed requests to the project's error tracker (`report()`) |

## Packages

`laravel-status:sync` sends the packages in `composer.json` with their installed version, and every other installed
package that comes along with them (read from `vendor/composer/installed.json`), with the `composer.json` packages that
pull it in. The dashboard checks all of them against the Packagist security advisories, the same source as
`composer audit`.

The same goes for NPM: the packages in `package.json` with the version from `package-lock.json`, and every other
package in the lockfile with the `package.json` packages that pull it in (lockfile v2 or v3, npm 7 and newer). The
dashboard checks those against the npm registry advisories, the same source as `npm audit`.

## Git

When the project is a git checkout, the sync sends the branch, the deployed commit (hash, subject and date) and the
remotes. No author names or e-mail addresses are sent, and credentials in remote URLs are removed.

## Queue and scheduler

The sync also tells whether the queue and the scheduler work:

- `queue`: the default connection, the number of jobs waiting on its default queue, the number of failed jobs, and
  when a queue worker last processed a job.
- `scheduler`: when `schedule:run` last started (every minute when the cron runs, also without due tasks).

The two times are kept as files in `storage/framework/laravel-status` (not in the cache, which deploy scripts often
clear). A worker writes its heartbeat at most once a minute. With several servers, each server has its own; the sync
sends those of the server it runs on.

## When a collector fails

Every part of the sync is collected separately. When one fails (no git, an unreadable lockfile, ...), it is left out
and the rest is still sent; the dashboard keeps what it had for that part. The command prints what it left out, and
with `LARAVEL_STATUS_LOG_EXCEPTIONS=true` the error goes to the project's error tracker. Only the basics the dashboard
can't do without (host, environment, name, URL, Composer packages, PHP and Laravel version, server info) make the sync
fail.

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

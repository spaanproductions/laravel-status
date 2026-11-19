---
name: laravel-status
description: "Connects this Laravel project to the Spaan Productions status dashboard (status.spaanproductions.nl) with the spaanproductions/laravel-status package. Use when setting up, configuring or troubleshooting what the project sends to the dashboard: the `laravel-status:sync` command, LARAVEL_STATUS_* env keys, the deploy script, scheduled task logging, backups (spatie/laravel-backup), the /up health check sync, the queue and scheduler heartbeats, or a project that shows outdated, missing or wrong data on the dashboard."
license: MIT
metadata:
  author: spaanproductions
---
# Laravel Status

`spaanproductions/laravel-status` sends the state of this application to the Spaan Productions status dashboard:
Laravel and PHP versions, Composer and NPM packages (checked there against security advisories), scheduled tasks and
their runs, spatie/laravel-backup backups, git info, and the health of the queue and the scheduler.

## Setup

1. `composer require spaanproductions/laravel-status`
2. Create a token on the dashboard (Projects > "Nieuw project token") and add it to `.env`:

   ```dotenv
   LARAVEL_STATUS_TOKEN=[project-token]
   ```

3. Add `php artisan laravel-status:sync` to the deploy script, after the migrations.
4. Point the project's uptime monitor on the dashboard at `https://[project]/up` (Laravel 11+): the first health check
   after every 6 hours starts the same sync in the background, so the dashboard stays current between deploys.
5. Optional, needs a running queue worker: `LARAVEL_STATUS_SCHEDULER_LOGGING_ENABLED=true` sends every start, finish,
   skip and failure of a scheduled task.

The project appears on the dashboard after the first sync. The config doesn't need publishing; to change it:
`php artisan vendor:publish --tag="laravel-status-config"` (creates `config/laravel-status.php`).

## Configuration

| Env | Default | |
|---|---|---|
| `LARAVEL_STATUS_TOKEN` | – | Without it nothing is sent |
| `LARAVEL_STATUS_BASE_URL` | `https://status.spaanproductions.nl` | Only to test against another dashboard |
| `LARAVEL_STATUS_SCHEDULER_LOGGING_ENABLED` | `false` | Send every scheduled task event (queued) |
| `LARAVEL_STATUS_BACKUP_REPORTING_ENABLED` | `true` | Send the spatie/laravel-backup backups |
| `LARAVEL_STATUS_HEALTH_SYNC_ENABLED` | `true` | Sync from the `/up` health check |
| `LARAVEL_STATUS_HEALTH_SYNC_HOURS` | `6` | At most one health check sync per this many hours |
| `LARAVEL_STATUS_SCHEDULER_QUEUE` / `_BACKUPS_QUEUE` / `_SYNC_QUEUE` | default queue | Queue of each job |
| `LARAVEL_STATUS_LOG_EXCEPTIONS` | `false` | Report failed requests and left-out parts to the error tracker |

Production caches the config: run `php artisan config:cache` after changing these.

## How it works

- `laravel-status:sync` collects every part separately and posts them to `/api/stats`. A part that fails (no git, an
  unreadable lockfile, ...) is left out and the dashboard keeps its last value; the command prints
  `Left out "<part>": <reason>`. Only the basics (host, environment, name, URL, Composer packages, PHP and Laravel
  version, server info) make the sync fail.
- The project is identified by the host of `APP_URL` plus `APP_ENV`. Changing either makes it a new project on the
  dashboard.
- Scheduled task events, backup reports and the health check sync are queued jobs.
- The queue and scheduler heartbeats are files in `storage/framework/laravel-status` (not the cache, which deploy
  scripts clear). The scheduler beat is written when `schedule:run` starts; the queue beat at most once a minute per
  worker when a job is processed.
- No personal data: git info has no author names or e-mail addresses, and credentials in remote URLs are removed.

## Troubleshooting

Start with `php artisan laravel-status:sync --dry-run`: it shows the payload without sending it, plus any left-out
parts. Then:

| Symptom | Check |
|---|---|
| `Unauthenticated` / 401 | `LARAVEL_STATUS_TOKEN`, and `php artisan config:cache` after setting it |
| Project missing or duplicated on the dashboard | `APP_URL` and `APP_ENV`: the dashboard keys projects on host + environment |
| Data only updates on deploy | Is the uptime monitor on `/up`? Laravel 11+ only; `LARAVEL_STATUS_HEALTH_SYNC_ENABLED`; a queue worker for the sync job |
| No scheduled task runs on the dashboard | `LARAVEL_STATUS_SCHEDULER_LOGGING_ENABLED=true` and a running queue worker |
| Dashboard says the scheduler doesn't run | The cron for `schedule:run`; `storage/framework/laravel-status/scheduler` must be writable |
| Dashboard says jobs wait without a worker | Start or restart the queue worker / Horizon (`horizon:terminate` after a deploy) |
| No backups on the dashboard | spatie/laravel-backup installed and `LARAVEL_STATUS_BACKUP_REPORTING_ENABLED` not false |
| Git info missing | The deploy has no `.git` directory (zero-downtime releases) or `git` is not available to PHP |

## Don't

- Don't call the dashboard API yourself; use `laravel-status:sync`.
- Don't put the token in code or in a committed config file; it belongs in `.env`.
- Don't wrap `/up` in authentication: the health check sync and the uptime monitor need it public.

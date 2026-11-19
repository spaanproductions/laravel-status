# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this package is

`spaanproductions/laravel-status` (the successor of `mtvtd/laravel-stats`, from 3.0) is a Laravel package that ships application telemetry (Laravel/PHP versions, installed packages, scheduled tasks, host info, etc.) to a remote dashboard at `status.spaanproductions.nl`. It also subscribes to scheduler events at runtime to forward task execution logs to the same backend.

The two integration points end-users hit:
- Run `php artisan laravel-status:sync` (typically from a deploy script) to push a snapshot of metrics.
- The package auto-listens to `ScheduledTask*` events in the console kernel and dispatches `ScheduledTaskEventLogJob` per event.

## Common commands

- Run tests: `composer test` (uses `testbench package:test --parallel --no-coverage`)
- Run a single test file: `vendor/bin/phpunit tests/ExampleTest.php`
- Run a single test method: `vendor/bin/phpunit --filter true_is_true`
- Coverage HTML report: `composer test-coverage` (output → `coverage/`)
- Lint/format: `composer lint` (dry run) and `composer fix`, PHP CS Fixer from `require-dev` with `.php-cs-fixer.dist.php`. CI (`.github/workflows/php-cs-fixer.yml`) runs `composer fix` with the same version and commits the fixes back ("Fix styling"), so pull after a push.

## Test matrix and compatibility

`composer.json` and CI target a wide range: PHP 8.0+ (CI runs 8.3/8.4/8.5), Laravel 9–13, both `prefer-lowest` and `prefer-stable`. Any code change must work across this matrix — avoid syntax/APIs that only exist in newer PHP or Laravel versions. The `class_exists(ScheduledTaskFailed::class)` guard in `ScheduledTaskSubscriber` is an example of how cross-version compatibility is handled.

## Architecture

### Service provider wiring (`LaravelStatusServiceProvider`)
Built on `spatie/laravel-package-tools`. Registers the config file and the `laravel-status:sync` command; binds `HealthCheckSubscriber` always (the health check is a web request) and, only when running in console, `ScheduledTaskSubscriber` and `BackupSubscriber`. No migrations, views or models.

### Health check sync
`HealthCheckSubscriber` listens to `DiagnosingHealth` (the `/up` route, Laravel 11+; not registered on older versions). `Cache::add()` lets one health check per `health-sync.hours` dispatch `SyncStatusJob` (after the response on the `sync` queue), which runs `laravel-status:sync` through `Artisan::call()`, so the console kernel and the schedule are loaded (otherwise `scheduled-tasks` would be empty and the dashboard would mark every task inactive). The listener catches everything: a throwing listener makes `/up` answer 500.

Every job takes its queue from `laravel-status.queues.*` in its constructor.

### Metric collection (`src/Metrics/`, `src/StatsCollector.php`)
Each key of the `/api/stats` payload is a class extending the abstract `Metrics\Metric` (`name(): string`, `value(): mixed`, `required(): bool`). `StatsCollector::METRICS` is the list; `StatsCollector::collect()` builds the payload, resolving each metric from the container (so tests can swap one). A metric that throws:
- **optional** (the default): left out of the payload, listed in `failures()` (the command prints "Left out ..."), reported with `log-exceptions`. The dashboard keeps what it has for a key that isn't sent.
- **required** (`required(): true`: host, environment, name, url, packages, php-version, laravel-version, server-info — what the dashboard validates as required): the collecting fails and nothing is sent.

Adding a metric: a class in `src/Metrics/`, added to `StatsCollector::METRICS`, and the key handled in the status app. Metrics that read project files use the `Metrics\Concerns\ReadsProjectFiles` trait (`readJson()`, null when missing). `--dry-run` dumps the payload instead of posting.

`LaravelAbout` runs `about --json` with its own `BufferedOutput` and no cache. In tests, `$this->artisan()` mocks all console output, including nested commands: run the sync with `withoutMockingConsoleOutput()` when the `about` key matters.

### Scheduler logging pipeline
1. `ScheduledTaskSubscriber` listens for `ScheduledTaskStarting/Finished/Skipped/Failed` events.
2. `ScheduledTaskPayload::fromEvent()` is a factory that dispatches to a per-event subclass (`ScheduledTaskStartingPayload`, etc.) under `src/Helpers/Payloads/`. Each subclass shapes the payload for that event type.
3. The payload is dispatched onto the queue inside `ScheduledTaskEventLogJob`, which POSTs to `/api/task-event` with `tries=1`, a 10s HTTP timeout and 2 retries.
4. The job short-circuits silently if `scheduler-logging-enabled` is false, the token is null, or the task command is empty. If `log-exceptions` is true, errors go to `report()`, so to whatever error tracker the project uses (Flare, Bugsnag, the log). `BackupReportJob` does the same.

### Task fingerprinting (`LaravelStatus::fingerprintTask`)
Produces a stable `laravel-status:<sha1>` identifier per scheduled task so the backend can correlate `Starting`/`Finished` events for the same run. `CallbackEvent`s use reflection to read the protected `callback` property — be careful when touching this code, since the closure-handling branch also mutates `$event->extra` as a side effect (used downstream to surface the file/line of inline closures).

### Heartbeats (`Heartbeats\Heartbeat`, `Subscribers\HeartbeatSubscriber`)
`CommandStarting` for `schedule:run` and `JobProcessed` (at most once a minute per worker process) touch a file in `storage/framework/laravel-status/`; the `Scheduler` and `Queue` metrics send its modification time. Files rather than the cache, because deploy scripts clear the cache right before the sync. Writing a heartbeat never throws. The `Queue` metric reads each part (`size`, `failed_jobs`) separately and sends null for a part it can't read.

### Git info (`Metrics\GitInfo`)
Runs `git` with Symfony Process (no shell, 10 s timeout) in `base_path()`: branch (null when detached), the HEAD commit (id, subject, unix date) and the remotes. No author/committer names or e-mail addresses (personal data the dashboard doesn't use), and credentials are stripped from remote URLs. Null outside a git checkout.

## Config

`config/laravel-status.php` exposes four env vars:
- `LARAVEL_STATUS_BASE_URL` (default `https://status.spaanproductions.nl`)
- `LARAVEL_STATUS_TOKEN` — bearer token; if null, the scheduler job silently no-ops.
- `LARAVEL_STATUS_SCHEDULER_LOGGING_ENABLED` (default `false`, opt-in since it needs a queue worker) — gates both the subscriber and the job.
- `LARAVEL_STATUS_BACKUP_REPORTING_ENABLED` (default `true`) — reports spatie/laravel-backup backups.
- `LARAVEL_STATUS_LOG_EXCEPTIONS` (default `false`) — controls whether HTTP failures in the jobs are reported.

Publish with `php artisan vendor:publish --tag="laravel-status-config"`. package-tools also registers `status-config` (it names the tag after the short name); the service provider adds `laravel-status-config` in `packageBooted()`.

The API on the other side is the Spaan Productions Status app (`POST /api/stats`, `/api/task-event`, `/api/backups`). Change the payload only together with that app.

## Dev dependencies

`spatie/ray` and `spatie/laravel-ray` are not used by the package: orchestra/testbench pulls them in through
orchestra/workbench. They have a minimum version in `require-dev` only so `prefer-lowest` in CI doesn't install old
versions that trigger PHP 8.4 and 8.5 deprecation warnings (ray 1.43.0 is the first without the 8.5 "null as an array offset" one). Projects that install the package are not affected.

## Code style

- Indentation: **tabs**, not spaces (`.editorconfig`).
- PHP CS Fixer rules (`.php-cs-fixer.dist.php`) enforce PSR-2, short array syntax, ordered imports by length, `not_operator_with_space` (so `! $foo` not `!$foo`), blank line before `return`/`throw`/`try`, snake_case test method names, and class method separation.
- Tests use PHPUnit `/** @test */` annotation style, snake_case method names (e.g. `true_is_true`), and extend the package `TestCase` which boots `LaravelStatusServiceProvider` via Orchestra Testbench.

## Things to know before editing

- The metric list in `StatsCollector::METRICS` is hand-maintained — it's not auto-discovered from `src/Metrics/`. New metric classes won't ship unless added there.
- `ScheduledTaskFailed` is only listened to when the class exists, because older Laravel versions don't have it.
- `ScheduledTaskPayload::fromEvent` falls through to a generic `self($event)` for unknown event types, but only the four known events ever subscribe — this is a safety net, not a public extension point.
- `getCommand()` in `ScheduledTasks` strips everything up to and including the artisan binary token, so it relies on `Application::artisanBinary()` matching what the scheduler stored — don't normalize commands elsewhere without checking this.

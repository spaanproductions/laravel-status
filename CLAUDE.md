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
- Lint/format: project uses `php-cs-fixer` with `.php_cs.dist.php`. `php-cs-fixer` is not in `require-dev` — install/run it via a globally available binary (`php-cs-fixer fix`). CI enforces this via `.github/workflows/php-cs-fixer.yml`.
- Static analysis: psalm config exists (`psalm.xml.dist`, errorLevel 4) but psalm is not in `require-dev`; install separately if you want to run it.

## Test matrix and compatibility

`composer.json` and CI target a wide range: PHP 8.0+ (CI runs 8.3/8.4/8.5), Laravel 9–13, both `prefer-lowest` and `prefer-stable`. Any code change must work across this matrix — avoid syntax/APIs that only exist in newer PHP or Laravel versions. The `class_exists(ScheduledTaskFailed::class)` guard in `ScheduledTaskSubscriber` is an example of how cross-version compatibility is handled.

## Architecture

### Service provider wiring (`LaravelStatusServiceProvider`)
Built on `spatie/laravel-package-tools`. Registers config file, the `laravel-status:sync` command, and — only when running in console — binds `ScheduledTaskSubscriber` to the event dispatcher. Migrations and views exist as scaffolding stubs but are currently disabled.

### Metric collection (`src/ShareableMetrics/`)
Each piece of telemetry is a class extending the abstract `Metric` and implementing `CollectableMetric` (`name(): string`, `value(): mixed`). `Metric::toArray()` returns `[name => value]`. `LaravelStatusCommand::handle()` instantiates a hardcoded list of metric classes, calls `toArray()` on each, and merges them into one payload that is POSTed to `/api/stats`. Adding a new metric means: create a class under `ShareableMetrics/Metrics/`, then add it to the `collect([...])` list in `LaravelStatusCommand`. `--dry-run` dumps the payload instead of posting.

### Scheduler logging pipeline
1. `ScheduledTaskSubscriber` listens for `ScheduledTaskStarting/Finished/Skipped/Failed` events.
2. `ScheduledTaskPayload::fromEvent()` is a factory that dispatches to a per-event subclass (`ScheduledTaskStartingPayload`, etc.) under `src/Helpers/Payloads/`. Each subclass shapes the payload for that event type.
3. The payload is dispatched onto the queue inside `ScheduledTaskEventLogJob`, which POSTs to `/api/task-event` with `tries=1`, a 10s HTTP timeout and 2 retries.
4. The job short-circuits silently if `scheduler-logging-enabled` is false, the token is null, or the task command is empty. If `log-exceptions` is true, errors go to `report()`, so to whatever error tracker the project uses (Flare, Bugsnag, the log). `BackupReportJob` does the same.

### Task fingerprinting (`LaravelStatus::fingerprintTask`)
Produces a stable `laravel-status:<sha1>` identifier per scheduled task so the backend can correlate `Starting`/`Finished` events for the same run. `CallbackEvent`s use reflection to read the protected `callback` property — be careful when touching this code, since the closure-handling branch also mutates `$event->extra` as a side effect (used downstream to surface the file/line of inline closures).

### Git info helpers (`src/Helpers/SourceControl/`)
`GitInfoCollector` shells out via `SystemCommandExecutor` (which wraps `exec()`) to gather branch/HEAD/remote info. The `GitInfo` metric that consumes this is currently commented out of the command's collection list — git info is collected at the helper level but not shipped.

## Config

`config/laravel-status.php` exposes four env vars:
- `LARAVEL_STATUS_BASE_URL` (default `https://status.spaanproductions.nl`)
- `LARAVEL_STATUS_TOKEN` — bearer token; if null, the scheduler job silently no-ops.
- `LARAVEL_STATUS_SCHEDULER_LOGGING_ENABLED` (default `true`) — gates both the subscriber and the job.
- `LARAVEL_STATUS_BACKUP_REPORTING_ENABLED` (default `true`) — reports spatie/laravel-backup backups.
- `LARAVEL_STATUS_LOG_EXCEPTIONS` (default `false`) — controls whether HTTP failures in the jobs are reported.

Publish with `php artisan vendor:publish --tag="laravel-status-config"`. package-tools also registers `status-config` (it names the tag after the short name); the service provider adds `laravel-status-config` in `packageBooted()`.

The API on the other side is the Spaan Productions Status app (`POST /api/stats`, `/api/task-event`, `/api/backups`). Change the payload only together with that app.

## Code style

- Indentation: **tabs**, not spaces (`.editorconfig`).
- PHP CS Fixer rules (`.php_cs.dist.php`) enforce PSR-2, short array syntax, ordered imports by length, `not_operator_with_space` (so `! $foo` not `!$foo`), blank line before `return`/`throw`/`try`, snake_case test method names, and class method separation.
- Tests use PHPUnit `/** @test */` annotation style, snake_case method names (e.g. `true_is_true`), and extend the package `TestCase` which boots `LaravelStatusServiceProvider` via Orchestra Testbench.

## Things to know before editing

- The metric collection list in `LaravelStatusCommand::handle()` is hand-maintained — it's not auto-discovered from the `Metrics/` directory. New metric classes won't ship unless explicitly added there.
- `ScheduledTaskFailed` is only listened to when the class exists, because older Laravel versions don't have it.
- `ScheduledTaskPayload::fromEvent` falls through to a generic `self($event)` for unknown event types, but only the four known events ever subscribe — this is a safety net, not a public extension point.
- `getCommand()` in `ScheduledTasks` strips everything up to and including the artisan binary token, so it relies on `Application::artisanBinary()` matching what the scheduler stored — don't normalize commands elsewhere without checking this.

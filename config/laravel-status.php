<?php

return [
	'base-url' => env('LARAVEL_STATUS_BASE_URL', 'https://status.spaanproductions.nl'),

    'token' => env('LARAVEL_STATUS_TOKEN'),

	/*
	 * Send every start, finish, skip and failure of a scheduled task. Opt-in: it needs a running queue worker.
	 */
	'scheduler-logging-enabled' => env('LARAVEL_STATUS_SCHEDULER_LOGGING_ENABLED', false),

	/*
	 * When the project uses spatie/laravel-backup: send its backups with the stats,
	 * and report every backup run (successful or failed) right away.
	 */
	'backup-reporting-enabled' => env('LARAVEL_STATUS_BACKUP_REPORTING_ENABLED', true),

	/*
	 * Keep the dashboard up to date between deploys: the health check route (/up, Laravel 11+) starts the same sync
	 * as laravel-status:sync, in the background, at most once every given number of hours.
	 */
	'health-sync' => [
		'enabled' => env('LARAVEL_STATUS_HEALTH_SYNC_ENABLED', true),
		'hours' => env('LARAVEL_STATUS_HEALTH_SYNC_HOURS', 6),
	],

	'backups' => [
		// The newest backups to send per destination.
		'max' => 200,
	],

	/*
	 * The queue of each job; null is the default queue of the connection.
	 */
	'queues' => [
		// Every scheduled task event (ScheduledTaskEventLogJob).
		'scheduler' => env('LARAVEL_STATUS_SCHEDULER_QUEUE'),
		// Every backup run (BackupReportJob).
		'backups' => env('LARAVEL_STATUS_BACKUPS_QUEUE'),
		// The sync started by the health check (SyncStatusJob).
		'sync' => env('LARAVEL_STATUS_SYNC_QUEUE'),
	],

	'log-exceptions' =>  env('LARAVEL_STATUS_LOG_EXCEPTIONS', false),
];

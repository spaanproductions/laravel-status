<?php

return [
	'base-url' => env('LARAVEL_STATUS_BASE_URL', 'https://status.spaanproductions.nl'),

    'token' => env('LARAVEL_STATUS_TOKEN'),

	'scheduler-logging-enabled' => env('LARAVEL_STATUS_SCHEDULER_LOGGING_ENABLED', true),

	/*
	 * When the project uses spatie/laravel-backup: send its backups with the stats,
	 * and report every backup run (successful or failed) right away.
	 */
	'backup-reporting-enabled' => env('LARAVEL_STATUS_BACKUP_REPORTING_ENABLED', true),

	'backups' => [
		// The newest backups to send per destination.
		'max' => 200,
	],

	'log-exceptions' =>  env('LARAVEL_STATUS_LOG_EXCEPTIONS', false),
];

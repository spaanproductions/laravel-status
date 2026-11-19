<?php

namespace SpaanProductions\LaravelStatus\Helpers\Payloads;

use ReflectionClass;
use Illuminate\Console\Scheduling\Event;
use SpaanProductions\LaravelStatus\Facades\LaravelStatus;
use SpaanProductions\LaravelStatus\Helpers\Tasks\TaskIdentifier;

class TaskPayload
{
	/** @var Event */
	private $event;

	public function __construct(Event $event)
	{
		$this->event = $event;
	}

	public function toArray(): array
	{
		return [
			'timezone' => $this->event->timezone,
			'type' => (new TaskIdentifier)($this->event),
			'expression' => $this->event->expression,
			'command' => LaravelStatus::sanitisedCommand($this->event->command),
			'maintenance' => $this->event->evenInMaintenanceMode,
			'without_overlapping' => $this->event->withoutOverlapping,
			'on_one_server' => $this->event->onOneServer,
			'run_in_background' => $this->event->runInBackground,
			'description' => $this->event->description,
			'mutex' => LaravelStatus::fingerprintTask($this->event),
			'filtered' => $this->isFiltered(),
			/* @phpstan-ignore-next-line */
			'extra' => $this->event->extra ?? null,
		];
	}

	private function isFiltered(): bool
	{
		return with(new ReflectionClass($this->event), function (ReflectionClass $class) {
			return ! empty(array_merge(
				tap($class->getProperty('filters'))->setAccessible(true)->getValue($this->event),
				tap($class->getProperty('rejects'))->setAccessible(true)->getValue($this->event)
			));
		});
	}
}

<?php

namespace SpaanProductions\LaravelStatus;

use Illuminate\Support\Facades\Facade;

/**
 * @see \SpaanProductions\LaravelStatus\LaravelStatus
 */
class LaravelStatusFacade extends Facade
{
	protected static function getFacadeAccessor()
	{
		return 'manage-laravel-status';
	}
}

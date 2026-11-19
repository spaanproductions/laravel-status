<?php

namespace SpaanProductions\LaravelStatus\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \SpaanProductions\LaravelStatus\LaravelStatus
 */
class LaravelStatus extends Facade
{
	protected static function getFacadeAccessor()
	{
		return \SpaanProductions\LaravelStatus\LaravelStatus::class;
	}
}

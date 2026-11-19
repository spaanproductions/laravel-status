<?php

namespace SpaanProductions\LaravelStatus\Metrics;

class ServerInfo extends Metric
{
	public function name(): string
	{
		return 'server-info';
	}

	public function required(): bool
	{
		return true;
	}

	public function value(): mixed
	{
		return [
			'os' => php_uname('s'),
			'hostname' => php_uname('n'),
			'release' => php_uname('r'),
			'version' => php_uname('v'),
			'machine' => php_uname('m'),
		];
	}
}

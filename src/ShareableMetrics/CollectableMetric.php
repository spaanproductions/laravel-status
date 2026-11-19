<?php

namespace SpaanProductions\LaravelStatus\ShareableMetrics;

interface CollectableMetric
{
	public function name(): string;

	public function value();
}

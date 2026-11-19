<?php

namespace SpaanProductions\LaravelStatus\Tests\Metrics;

use Illuminate\Support\Collection;
use SpaanProductions\LaravelStatus\Tests\TestCase;
use SpaanProductions\LaravelStatus\Metrics\LaravelAbout;

class LaravelAboutTest extends TestCase
{
	public function test_metric_name_is_about()
	{
		$this->assertSame('about', (new LaravelAbout())->name());
	}

	public function test_value_is_the_output_of_the_about_command()
	{
		$value = (new LaravelAbout())->value();

		$this->assertArrayHasKey('environment', $value);
		$this->assertSame(app()->version(), $value['environment']['laravel_version']);
	}

	public function test_format_information_normalises_ints_blanks_and_passes_through_bool_and_array()
	{
		$input = new Collection([
			'Section' => [
				'count' => 1234,
				'blank_string' => '',
				'live_string' => 'value',
				'flag' => true,
				'nested' => ['a', 'b'],
				'other' => 3.14,
			],
		]);

		$out = (new LaravelAbout())->formatInformation($input)->toArray();

		$this->assertSame(number_format(1234), $out['Section']['count']);
		$this->assertSame('-', $out['Section']['blank_string']);
		$this->assertSame('value', $out['Section']['live_string']);
		$this->assertTrue($out['Section']['flag']);
		$this->assertSame(['a', 'b'], $out['Section']['nested']);
		$this->assertSame('3.14', $out['Section']['other']);
	}
}

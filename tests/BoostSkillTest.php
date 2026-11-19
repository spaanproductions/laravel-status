<?php

namespace SpaanProductions\LaravelStatus\Tests;

use Symfony\Component\Yaml\Yaml;

/**
 * Laravel Boost installs the skills in resources/boost/skills of a direct dependency, but silently skips one without
 * a name and description in its frontmatter.
 */
class BoostSkillTest extends TestCase
{
	public function test_the_skill_has_the_frontmatter_boost_needs()
	{
		$content = file_get_contents(__DIR__ . '/../resources/boost/skills/laravel-status/SKILL.md');

		$this->assertSame(1, preg_match('/^---\R(.*?)\R---\R/s', $content, $matches), 'The skill starts with a frontmatter block.');

		$frontmatter = Yaml::parse($matches[1]);

		$this->assertSame('laravel-status', $frontmatter['name']);
		$this->assertNotEmpty($frontmatter['description']);
	}
}

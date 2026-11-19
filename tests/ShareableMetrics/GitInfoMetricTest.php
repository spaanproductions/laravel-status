<?php

namespace SpaanProductions\LaravelStatus\Tests\ShareableMetrics;

use SpaanProductions\LaravelStatus\Tests\TestCase;
use SpaanProductions\LaravelStatus\Helpers\SourceControl\GitInfo;
use SpaanProductions\LaravelStatus\Helpers\SourceControl\CommitInfo;
use SpaanProductions\LaravelStatus\Helpers\SourceControl\RemoteInfo;
use SpaanProductions\LaravelStatus\Helpers\SourceControl\GitInfoCollector;
use SpaanProductions\LaravelStatus\ShareableMetrics\Metrics\GitInfo as GitInfoMetric;

class GitInfoMetricTest extends TestCase
{
	public function test_value_returns_collector_payload_as_array()
	{
		$commit = (new CommitInfo())
			->setId('h')->setAuthorName('an')->setAuthorEmail('ae')
			->setCommitterName('cn')->setCommitterEmail('ce')
			->setMessage('msg')->setDate(42);

		$remote = (new RemoteInfo())->setName('origin')->setUrl('git@example.com:foo/bar.git');

		$gitInfo = new GitInfo('main', $commit, [$remote]);

		$this->app->instance(GitInfoCollector::class, new class($gitInfo) extends GitInfoCollector {
			private $payload;

			public function __construct(GitInfo $payload)
			{
				parent::__construct();
				$this->payload = $payload;
			}

			public function collect(): GitInfo
			{
				return $this->payload;
			}
		});

		$this->assertSame('git-info', (new GitInfoMetric())->name());
		$this->assertSame($gitInfo->toArray(), (new GitInfoMetric())->value());
	}
}

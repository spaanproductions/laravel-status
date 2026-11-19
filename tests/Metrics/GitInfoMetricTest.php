<?php

namespace SpaanProductions\LaravelStatus\Tests\Metrics;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use SpaanProductions\LaravelStatus\Tests\TestCase;
use SpaanProductions\LaravelStatus\Metrics\GitInfo;

class GitInfoMetricTest extends TestCase
{
	private string $directory;

	private string $originalBasePath;

	public function setUp(): void
	{
		parent::setUp();

		$this->originalBasePath = $this->app->basePath();
		$this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'laravel-status-git-' . uniqid();
		File::ensureDirectoryExists($this->directory);
		$this->app->setBasePath($this->directory);
	}

	public function tearDown(): void
	{
		$this->app->setBasePath($this->originalBasePath);
		File::deleteDirectory($this->directory);

		parent::tearDown();
	}

	private function git(array $arguments): void
	{
		$process = new Process(array_merge(['git', '-c', 'user.name=Jane Doe', '-c', 'user.email=jane@example.com'], $arguments), $this->directory);
		$process->mustRun();
	}

	private function repository(): void
	{
		$this->git(['init', '--initial-branch=main']);
		File::put($this->directory . '/README.md', 'Hello');
		$this->git(['add', 'README.md']);
		$this->git(['commit', '-m', 'First commit']);
		$this->git(['remote', 'add', 'origin', 'https://deploy:secret-token@github.com/acme/app.git']);
		$this->git(['remote', 'add', 'mirror', 'git@github.com:acme/app.git']);
	}

	public function test_it_sends_the_branch_the_commit_and_the_remotes()
	{
		$this->repository();

		$value = (new GitInfo())->value();

		$this->assertSame('main', $value['branch']);
		$this->assertSame('First commit', $value['head']['message']);
		$this->assertSame(40, strlen($value['head']['id']));
		$this->assertIsInt($value['head']['date']);
		$this->assertEqualsCanonicalizing([
			['name' => 'origin', 'url' => 'https://github.com/acme/app.git'],
			['name' => 'mirror', 'url' => 'git@github.com:acme/app.git'],
		], $value['remotes']);
	}

	public function test_no_credentials_and_no_personal_data_are_sent()
	{
		$this->repository();

		$json = json_encode((new GitInfo())->value());

		$this->assertStringNotContainsString('secret-token', $json, 'Credentials in a remote URL are removed.');
		$this->assertStringNotContainsString('Jane', $json, 'The author name is not sent.');
		$this->assertStringNotContainsString('jane@example.com', $json, 'The author e-mail address is not sent.');
	}

	public function test_a_detached_checkout_has_no_branch()
	{
		$this->repository();
		$this->git(['checkout', '--detach']);

		$this->assertNull((new GitInfo())->value()['branch']);
	}

	public function test_it_is_null_outside_a_git_repository()
	{
		// An empty directory inside the temp dir: make sure no parent directory is a repository by using its own ceiling.
		putenv('GIT_CEILING_DIRECTORIES=' . dirname($this->directory));

		try {
			$this->assertNull((new GitInfo())->value());
		} finally {
			putenv('GIT_CEILING_DIRECTORIES');
		}
	}
}

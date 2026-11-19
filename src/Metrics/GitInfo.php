<?php

namespace SpaanProductions\LaravelStatus\Metrics;

use Symfony\Component\Process\Process;

/**
 * The deployed git state: the branch, the commit and the remotes. Null when the project is not a git checkout (e.g. a
 * zero-downtime release without .git) or git can't run.
 *
 * Personal data stays out: no author or committer names and e-mail addresses. Credentials in remote URLs
 * (https://user:token@host/...) are removed.
 */
class GitInfo extends Metric
{
	public function name(): string
	{
		return 'git-info';
	}

	public function value(): mixed
	{
		// %H the hash, %at the commit date (unix time), %s the subject.
		$head = $this->git(['log', '-1', '--format=%H%n%at%n%s']);

		if ($head === null) {
			return null;
		}

		[$hash, $date, $subject] = array_pad(explode("\n", $head, 3), 3, '');

		$branch = $this->git(['rev-parse', '--abbrev-ref', 'HEAD']);

		return [
			// "HEAD" when the checkout is detached, as after a deploy of a tag or commit.
			'branch' => $branch === 'HEAD' ? null : $branch,
			'head' => [
				'id' => $hash,
				'message' => $subject,
				'date' => (int) $date,
			],
			'remotes' => $this->remotes(),
		];
	}

	/** @return list<array{name: string, url: string}> */
	protected function remotes(): array
	{
		$output = $this->git(['remote', '-v']);

		if ($output === null || $output === '') {
			return [];
		}

		$remotes = [];

		// "origin\tgit@github.com:acme/app.git (fetch)": one line per remote for fetch and one for push.
		foreach (explode("\n", $output) as $line) {
			if ( ! preg_match('/^(\S+)\s+(\S+)/', $line, $match)) {
				continue;
			}

			$remotes[$match[1]] = [
				'name' => $match[1],
				'url' => $this->withoutCredentials($match[2]),
			];
		}

		return array_values($remotes);
	}

	protected function withoutCredentials(string $url): string
	{
		// Only URLs with a scheme carry credentials; scp-like "git@host:path" names just the ssh user.
		return preg_replace('#^([a-z][a-z0-9+.-]*://)[^/@]*@#i', '$1', $url);
	}

	/**
	 * Runs git in the project directory, without a shell. Null when git isn't there or fails.
	 *
	 * @param list<string> $arguments
	 */
	protected function git(array $arguments): ?string
	{
		$process = new Process(array_merge(['git'], $arguments), base_path(), null, null, 10);
		$process->run();

		return $process->isSuccessful() ? trim($process->getOutput()) : null;
	}
}

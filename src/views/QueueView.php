<?php

namespace App\views;

require_once __DIR__ . '/../redis_connector.php';
require_once __DIR__ . '/ViewInterface.php';

class QueueView implements ViewInterface
{
	private $redis;
	private $mode = 'detailed';
	private string $env;
	private int $scrollOffset = 0;
	private int $linesPerPage = 20;

	public function __construct(string $env)
	{
		$this->env = $env;
		$this->redis = getRedisClient($env);
	}

	public function scroll(int $direction): void
	{
		$this->scrollOffset += $direction;
		if ($this->scrollOffset < 0) {
			$this->scrollOffset = 0;
		}
		$this->render();
	}

	public function toggleMode(): void
	{
		$this->mode = $this->mode === 'summary' ? 'detailed' : 'summary';
		$this->scrollOffset = 0;
	}

	public function getEnv(): string
	{
		return $this->env;
	}

	public function render(): void
	{
		clearScreen();

		$allKeys = $this->redis->keys('kolejki:*');
		$queues = array_filter($allKeys, function ($key) {
			return preg_match('/^kolejki:\d+$/', $key);
		});

		if (empty($queues)) {
			echo date("Y-m-d H:i:s") . " Brak kolejek.\n";
			return;
		}

		$lines = [];
		foreach ($queues as $queue) {
			$value = $this->redis->get($queue);
			$data = json_decode($value, true);

			if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
				$lines[] = " - $queue";
				continue;
			}

			if ($this->mode === 'summary') {
				$lines[] = " - $queue";
			} else {
				$lines[] = " - $queue:";
				foreach ($data as $key => $val) {
					$lines[] = "     $key: $val";
				}
			}
		}

		$totalLines = count($lines);
		$start = $this->scrollOffset;
		$visibleLines = array_slice($lines, $start, $this->linesPerPage);

		echo "Znaleziono kolejki:\n";
		foreach ($visibleLines as $line) {
			echo $line . "\n";
		}

		// Pokazujemy info na dole np. "20/45"
		$end = min($start + $this->linesPerPage, $totalLines);
		echo "\n📄 Linie $start-$end z $totalLines\n";
	}
}

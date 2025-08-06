<?php

namespace App\views;

require_once __DIR__ . '/../redis_connector.php';
require_once __DIR__ . '/ViewInterface.php';

class WagonView implements ViewInterface
{
	private string $env;
	private $redis;
	private int $coasterId;
	private bool $detailed = false;
	private int $scrollOffset = 0;
	private int $linesPerPage = 20;

	public function __construct(string $env, int $coasterId)
	{
		$this->env = $env;
		$this->coasterId = $coasterId;
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
		$this->detailed = !$this->detailed;
		$this->scrollOffset = 0;
	}

	public function getEnv(): string
	{
		return $this->env;
	}

	public function render(): void
	{
		clearScreen();

		$key = "kolejki:{$this->coasterId}:wagony";
		$data = $this->redis->hGetAll($key);

		if (empty($data)) {
			echo "Brak wagonów dla kolejki {$this->coasterId}.\n";
			return;
		}

		$lines = [];
		foreach ($data as $id => $json) {
			if ($this->detailed) {
				$wagon = json_decode($json, true);
				if (json_last_error() !== JSON_ERROR_NONE) {
					$lines[] = " - ID: $id (Błąd odczytu danych)";
					continue;
				}
				$lines[] = " - ID: $id";
				foreach ($wagon as $key => $val) {
					$lines[] = "     $key: $val";
				}
			} else {
				$lines[] = " - ID: $id";
			}
		}

		$totalLines = count($lines);
		$start = $this->scrollOffset;
		$visibleLines = array_slice($lines, $start, $this->linesPerPage);

		echo "Wagony kolejki {$this->coasterId}:\n";
		foreach ($visibleLines as $line) {
			echo $line . "\n";
		}

		$end = min($start + $this->linesPerPage, $totalLines);
		echo "\n📄 Linie $start-$end z $totalLines\n";
	}
}

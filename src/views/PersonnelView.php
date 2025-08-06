<?php

namespace App\views;

require_once __DIR__ . '/../redis_connector.php';
require_once __DIR__ . '/../engine/ComputeEngineRunner.php';
require_once __DIR__ . '/ViewInterface.php';

use App\Engine\ComputeEngineRunner;
use ConfigLoader;

class PersonnelView implements ViewInterface
{
	private string $env;
	private $redis;
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

	public function toggleMode(): void {}
	public function getEnv(): string { return $this->env; }

	public function render(): void
	{
		clearScreen();

		$allKeys = $this->redis->keys('kolejki:*');
		$queues = array_filter($allKeys, fn($key) => preg_match('/^kolejki:\d+$/', $key));

		if (empty($queues)) {
			echo "Brak kolejek.\n";
			return;
		}

		$runner = new ComputeEngineRunner($this->env);
		$lines = [];

		foreach ($queues as $queueKey) {
			$id = (int) explode(':', $queueKey)[1];
			$filepath = ConfigLoader::get('personnel_dir') . "/$id.json";

			if (!file_exists($filepath)) {
				$runner->generatePersonnelOutput($id);
			}

			if (file_exists($filepath)) {
				$data = json_decode(file_get_contents($filepath), true);
				if (isset($data['lines']) && is_array($data['lines'])) {
					$lines = array_merge($lines, $data['lines']);
				}
			} else {
				$lines[] = "⚠️ Kolejka $id: brak danych.";
			}
		}

		$total = count($lines);
		$visible = array_slice($lines, $this->scrollOffset, $this->linesPerPage);

		echo "👨‍🔧 Bilans personelu w kolejkach:\n";
		foreach ($visible as $line) echo $line . "\n";

		$end = min($this->scrollOffset + $this->linesPerPage, $total);
		echo "\n📄 Linie {$this->scrollOffset}-$end z $total\n";
	}
}

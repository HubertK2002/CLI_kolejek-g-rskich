<?php

namespace App\views;

use App\ViewManager;
use ConfigLoader;

class RedisLogView implements ViewInterface
{
	private string $env;
	private string $logPath;
	private int $scrollOffset = 0;
	private int $linesPerPage = 20;
	private string $mode = 'auto';

	public function __construct(string $env)
	{
		$this->env = $env;
		$this->logPath = ConfigLoader::get('log_file');
	}

	public function getEnv(): string
	{
		return $this->env;
	}

	public function toggleMode(): void
	{
		$this->mode = $this->mode === 'auto' ? 'manual' : 'auto';
	}

	public function scroll(int $direction): void
	{
		$this->scrollOffset += $direction;
		if ($this->scrollOffset < 0) {
			$this->scrollOffset = 0;
		}
		$this->render();
	}

	public function render(): void
	{
		clearScreen();

		if (!file_exists($this->logPath)) {
			echo "Brak pliku logu Redis: $this->logPath\n";
			return;
		}

		$lines = file($this->logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$totalLines = count($lines);

		if ($this->mode === 'auto') {
			$this->scrollOffset = max(0, $totalLines - $this->linesPerPage);
		}

		$start = $this->scrollOffset;
		$visibleLines = array_slice($lines, $start, $this->linesPerPage);

		echo "📜 Zawartość logu Redis:\n";
		foreach ($visibleLines as $line) {
			echo $line . "\n";
		}

		$end = min($start + $this->linesPerPage, $totalLines);
		echo "\n📄 Linie $start-$end z $totalLines  |  Tryb: " . ($this->mode === 'auto' ? '🟢 auto' : '🛑 manual') . "\n";
	}

	public function forceRefresh(): void
	{
		$this->render();
	}

}

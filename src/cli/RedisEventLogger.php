<?php

namespace App\CLI;

use ConfigLoader;

class RedisEventLogger
{
	protected string $logFile;

	public function __construct()
	{
		$this->logFile = ConfigLoader::get('log_file');
		$logDir = dirname($this->logFile);

		if (!is_dir($logDir)) {
			mkdir($logDir, 0777, true);
		}
	}

	public function log(string $message): void
	{
		$entry = '[' . date('Y-m-d H:i:s') . '] ' . $message;
		file_put_contents($this->logFile, $entry . PHP_EOL, FILE_APPEND);
	}
}

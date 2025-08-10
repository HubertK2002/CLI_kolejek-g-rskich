<?php

class ConfigLoader
{
	private const CONFIG_PATH = '../../config';
	private static ?self $instance = null;
	private array $config;

	private function __construct(string $env)
	{
		$raw = parse_ini_file(self::CONFIG_PATH, true);

		if (!isset($raw[$env], $raw['paths'])) {
			throw new RuntimeException("Invalid environment or missing [paths] section");
		}

		$dataRoot = rtrim($raw[$env]['data_root'], '/');

		$this->config = array_merge($raw[$env], [
			'log_file'         => $dataRoot . '/' . $raw['paths']['log_file'],
			'notification_dir' => $dataRoot . '/' . $raw['paths']['notification_dir'],
			'clients_dir'      => $dataRoot . '/' . $raw['paths']['clients_dir'],
			'personnel_dir'    => $dataRoot . '/' . $raw['paths']['personnel_dir'],
		]);
	}

	public static function init(string $env): void
	{
		if (self::$instance === null) {
			self::$instance = new self($env);
		}
	}

	public static function get(string $key): mixed
	{
		if (!self::$instance) {
			throw new RuntimeException("ConfigLoader not initialized");
		}
		if (!array_key_exists($key, self::$instance->config)) {
			throw new InvalidArgumentException("Missing config key: $key");
		}
		return self::$instance->config[$key];
	}

	public static function all(): array
	{
		if (!self::$instance) {
			throw new RuntimeException("ConfigLoader not initialized");
		}
		return self::$instance->config;
	}
}

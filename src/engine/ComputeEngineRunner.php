<?php

namespace App\Engine;

require_once __DIR__ . '/ClientComputeEngine.php';
require_once __DIR__ . '/PersonnelComputeEngine.php';

use App\Engine\ClientComputeEngine;
use App\Engine\PersonnelComputeEngine;
use ConfigLoader;

class ComputeEngineRunner
{
	private string $env;
	private \Redis $redis;

	public function __construct(string $env)
	{
		$this->env = $env;
		$this->redis = getRedisClient($env);
	}

	public function runForQueue(int $id): void
	{
		$this->ensureOutputDirectories();
		$this->generateClientOutput($id);
		$this->generatePersonnelOutput($id);
	}

	public function generateClientOutput(int $id): void
	{
		$this->ensureOutputDirectories();
		$clientOutputRaw = (new ClientComputeEngine($this->redis))->compute($id);

		$lines = [];

		if (isset($clientOutputRaw['error'])) {
			$lines[] = "⚠️ Kolejka $id: Błąd – {$clientOutputRaw['error']}";
		} else {
			$lines[] = "📊 Kolejka $id:";
			$lines[] = "   - Klientów nieobsłużonych: {$clientOutputRaw['missing_clients']}";
			$lines[] = "   - Miejsc brakuje: {$clientOutputRaw['missing_seats']}";

			$wagonInfo = "   - Wagonów: {$clientOutputRaw['current_wagons']} / potrzeba: {$clientOutputRaw['needed_wagons']}";
			if ($clientOutputRaw['missing_wagons'] > 0) {
				$wagonInfo .= " (❗ brakuje: {$clientOutputRaw['missing_wagons']})";
			} elseif ($clientOutputRaw['excess_wagons'] > 0) {
				$wagonInfo .= " (ℹ️ nadmiar: {$clientOutputRaw['excess_wagons']})";
			}
			$lines[] = $wagonInfo;

			$lines[] = "   - Kursów na wagon: {$clientOutputRaw['trips_per_wagon']}";

			$staffInfo = "   - Personel: {$clientOutputRaw['current_staff']} / potrzeba: {$clientOutputRaw['needed_staff']}";
			if ($clientOutputRaw['missing_staff'] > 0) {
				$staffInfo .= " (❗ brakuje: {$clientOutputRaw['missing_staff']})";
			} else {
				$staffInfo .= " (✅ wystarczająco)";
			}
			$lines[] = $staffInfo;
		}

		$hasIssues =
			($clientOutputRaw['missing_clients'] ?? 0) > 0 ||
			($clientOutputRaw['missing_wagons'] ?? 0) > 0 ||
			($clientOutputRaw['missing_staff'] ?? 0) > 0 ||
			($clientOutputRaw['excess_wagons'] ?? 0) > 0;

		$output = [
			'has_issues' => $hasIssues,
			'lines' => $lines,
			'last_updated' => date('c')
		];

		file_put_contents(ConfigLoader::get('clients_dir') . "/{$id}.json", json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
	}

	public function generatePersonnelOutput(int $id): void
	{
		$this->ensureOutputDirectories();
		$personnelOutputRaw = (new PersonnelComputeEngine($this->redis))->compute($id);
		$hasIssues = isset($personnelOutputRaw['diff']) && $personnelOutputRaw['diff'] < 0;

		$line = match (true) {
			$personnelOutputRaw['diff'] > 0 => "✅ Kolejka $id: Nadmiar personelu: +{$personnelOutputRaw['diff']} (posiada: {$personnelOutputRaw['current_staff']}, potrzeba: {$personnelOutputRaw['needed_staff']})",
			$personnelOutputRaw['diff'] < 0 => "❌ Kolejka $id: Brakujący personel: {$personnelOutputRaw['diff']} (posiada: {$personnelOutputRaw['current_staff']}, potrzeba: {$personnelOutputRaw['needed_staff']})",
			default => "☑️ Kolejka $id: Personel idealnie dopasowany ({$personnelOutputRaw['current_staff']})"
		};

		$output = [
			'has_issues' => $hasIssues,
			'lines' => [$line],
			'last_updated' => date('c')
		];

		file_put_contents(ConfigLoader::get('personnel_dir') . "/{$id}.json", json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
	}

	private function ensureOutputDirectories(): void
	{
		$this->ensureDir(ConfigLoader::get('clients_dir'));
		$this->ensureDir(ConfigLoader::get('personnel_dir'));
	}

	private function ensureDir(string $path): void
	{
		if (!is_dir($path)) {
			if (!mkdir($path, 0777, true) && !is_dir($path)) {
				throw new RuntimeException("❌ Nie udało się utworzyć katalogu: $path");
			}
		}
	}
}

<?php

namespace App\Notifications;

require_once __DIR__ . '/../engine/ComputeEngineRunner.php';

use App\Engine\ComputeEngineRunner;
use ConfigLoader;

class NotificationManager
{
	private string $env;

	public function __construct(string $env)
	{
		$this->env = $env;
	}

	public function updateNotificationForQueue(int $id): void
	{
		// 🔁 Zawsze generuj dane na nowo
		$runner = new ComputeEngineRunner($this->env);
		$runner->runForQueue($id);

		// 🔍 Ścieżki do plików obliczeń
		$clientFile = ConfigLoader::get('clients_dir') . "/$id.json";
		$personnelFile = ConfigLoader::get('personnel_dir') . "/$id.json";
		$notifFile = ConfigLoader::get('notification_dir') . "/$id.json";

		$client = file_exists($clientFile) ? json_decode(file_get_contents($clientFile), true) : null;
		$personnel = file_exists($personnelFile) ? json_decode(file_get_contents($personnelFile), true) : null;

		// 📌 Priorytet: najpierw klienci, potem personel
		$use = null;
		if ($client && ($client['has_issues'] ?? false)) {
			$use = $client['lines'] ?? [];
		} elseif ($personnel && ($personnel['has_issues'] ?? false)) {
			$use = $personnel['lines'] ?? [];
		}

		// ❌ Brak problemów – usuń powiadomienie
		if ($use === null || empty($use)) {
			if (file_exists($notifFile)) {
				unlink($notifFile);
			}
			return;
		}

		// 📄 Nie twórz ponownie jeśli linie się nie zmieniły
		$existing = file_exists($notifFile) ? json_decode(file_get_contents($notifFile), true) : null;
		if ($existing && $existing['lines'] === $use) {
			return;
		}

		// 📝 Zapisz/aktualizuj powiadomienie
		$notification = [
			'status' => 'utworzona',
			'detected_at' => $existing['detected_at'] ?? date('c'),
			'lines' => $use
		];

		@mkdir(ConfigLoader::get('notification_dir'), 0777, true);
		file_put_contents($notifFile, json_encode($notification, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
	}
}

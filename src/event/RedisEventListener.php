<?php

namespace App\Event;

use Clue\React\Redis\Factory;
use React\EventLoop\LoopInterface;
use App\CLI\RedisEventLogger;
use App\Notifications\NotificationManager;
use ConfigLoader;

class RedisEventListener
{
	protected LoopInterface $loop;
	protected RedisEventLogger $logger;

	public function __construct(LoopInterface $loop)
	{
		$this->loop = $loop;
		$this->logger = new RedisEventLogger();
	}

	public function listen(): void
	{
		$factory = new Factory($this->loop);

		$env = \App\ViewManager::getInstance()->get()->getEnv();
		$host = ConfigLoader::get('host', $env);
		$port = ConfigLoader::get('redis_port', $env);

		$factory->createClient("$host:$port")->then(function (\Clue\React\Redis\Client $redis) {
			
			$redis->subscribe('coaster.added');
			$redis->subscribe('coaster.updated');
			$redis->subscribe('wagon.added');
			$redis->subscribe('wagon.deleted');

			$redis->on('message', function ($channel, $message) {
				$this->logger->log("[$channel] $message");

				$id = $this->extractQueueId($message);

				if ($id !== null) {
					$env = \App\ViewManager::getInstance()->get()->getEnv();
					$notifier = new NotificationManager($env);
					$notifier->updateNotificationForQueue($id);
				}

				$view = \App\ViewManager::getInstance()->get();
				if ($view instanceof \App\views\RedisLogView) {
					$view->forceRefresh();
				}
			});
		}, function (\Throwable $e) {
			$this->logger->log("❌ Nie udało się połączyć z Redisem: " . $e->getMessage());
		});
	}

	private function extractQueueId(string $message): ?int
	{
		$data = json_decode($message, true);
		if (json_last_error() === JSON_ERROR_NONE && isset($data['queue_id'])) {
			return (int) $data['queue_id'];
		}
		return null;
	}
}

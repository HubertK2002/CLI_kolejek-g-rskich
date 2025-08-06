<?php

namespace App\Engine;

use Redis;

class PersonnelComputeEngine
{
	private Redis $redis;

	public function __construct(Redis $redis)
	{
		$this->redis = $redis;
	}

	public function compute(int $queueId): array
	{
		$key = "kolejki:$queueId";
		$data = json_decode($this->redis->get($key), true);
		if (!$data || !isset($data['liczba_personelu'])) {
			return ['error' => 'Missing queue data'];
		}

		$wagonsKey = "kolejki:$queueId:wagony";
		$wagonCount = $this->redis->hLen($wagonsKey);

		$needed = 1 + 2 * $wagonCount;
		$current = (int)$data['liczba_personelu'];
		$diff = $current - $needed;

		return [
			'current_staff' => $current,
			'needed_staff' => $needed,
			'diff' => $diff,
			'status' => $diff === 0 ? 'perfect' : ($diff > 0 ? 'excess' : 'shortage'),
		];
	}
}

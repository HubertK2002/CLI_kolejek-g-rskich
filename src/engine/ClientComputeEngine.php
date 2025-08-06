<?php

namespace App\Engine;

use Redis;

class ClientComputeEngine
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

		if (!$data || !isset($data['liczba_klientow'], $data['dl_trasy'], $data['predkosc_wagonu'], $data['godzina_od'], $data['godzina_do'], $data['liczba_personelu'])) {
			return ['error' => 'Missing queue data'];
		}

		$wagons = $this->redis->hGetAll("kolejki:$queueId:wagony");
		$wagonCount = count($wagons);

		$workMinutes = (strtotime($data['godzina_do']) - strtotime($data['godzina_od'])) / 60;
		$tripTime = ($data['dl_trasy'] / $data['predkosc_wagonu']) + 5;
		$tripsPerWagon = floor($workMinutes / $tripTime);

		$totalSeats = 0;
		foreach ($wagons as $json) {
			$w = json_decode($json, true);
			if ($w && isset($w['ilosc_miejsc'])) {
				$totalSeats += (int)$w['ilosc_miejsc'];
			}
		}
		$averageSeats = $wagonCount > 0 ? floor($totalSeats / $wagonCount) : 20;

		$canTransport = $averageSeats * $tripsPerWagon * $wagonCount;
		$missingClients = max(0, $data['liczba_klientow'] - $canTransport);
		$missingSeats = $tripsPerWagon > 0 ? ceil($missingClients / $tripsPerWagon) : $missingClients;

		$clientsPerWagon = $averageSeats * $tripsPerWagon;
		$neededWagons = $clientsPerWagon > 0 ? ceil($data['liczba_klientow'] / $clientsPerWagon) : 0;
		$missingWagons = max(0, $neededWagons - $wagonCount);
		$excessWagons = max(0, $wagonCount - $neededWagons);

		$neededStaff = 1 + 2 * $neededWagons;
		$missingStaff = max(0, $neededStaff - $data['liczba_personelu']);

		return [
			'missing_clients' => $missingClients,
			'missing_seats' => $missingSeats,
			'needed_wagons' => $neededWagons,
			'current_wagons' => $wagonCount,
			'missing_wagons' => $missingWagons,
			'excess_wagons' => $excessWagons,
			'needed_staff' => $neededStaff,
			'current_staff' => $data['liczba_personelu'],
			'missing_staff' => $missingStaff,
			'trips_per_wagon' => $tripsPerWagon,
		];
	}
}

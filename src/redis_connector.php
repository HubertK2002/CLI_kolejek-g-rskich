<?php

use ConfigLoader;

function getRedisClient(string $env): Redis
{
	$host = ConfigLoader::get('host', $env);
	$port = ConfigLoader::get('redis_port', $env);

	$redis = new Redis();
	$redis->connect($host, $port);

	return $redis;
}

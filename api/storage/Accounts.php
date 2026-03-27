<?php

namespace Api\Storage;

class Accounts
{
	private static array $accounts;

	public function __construct()
	{
		self::$accounts = [
			"100" => ["balance" => 10],
			"200" => ["balance" => 20]
		];
	}

	public function all(): array
	{
		return self::$accounts;
	}

	public function get(string $id)
	{
		return array_key_exists($id, self::$accounts) ? self::$accounts[$id] : null;
	}

	public function set(string $id, int $balance): int
	{
		self::$accounts[$id] = intval($balance);
		return self::$accounts[$id];
	}

	public function deleteAll(): void
	{
		self::$accounts = [];
	}
}

<?php

namespace Api\Storage;

class Accounts
{
	private $accounts;

	public function __construct()
	{
		$this->accounts = [
			"100" => ["balance" => 10],
			"200" => ["balance" => 20]
		];
	}

	public function all(): array
	{
		return $this->accounts;
	}

	public function get(string $id)
	{
		return array_key_exists($id, $this->accounts) ? $this->accounts[$id] : null;
	}

	public function set(string $id, int $balance): int
	{
		$this->accounts[$id] = intval($balance);
		return $this->accounts[$id];
	}

	public function deleteAll(): void
	{
		$this->accounts = [];
	}
}

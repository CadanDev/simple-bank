<?php

namespace Api\Models;

/**
 * Class Accounts
 * File-based storage for accounts and balances.
 * Persists state across requests regardless of client cookies.
 */
class Accounts
{
	private const STORAGE_DIR = __DIR__ . '/../../data';
	private const STORAGE_FILE = self::STORAGE_DIR . '/accounts.json';
	private array $accounts;

	public function __construct()
	{
		// Ensure storage directory exists
		if (!is_dir(self::STORAGE_DIR)) {
			@mkdir(self::STORAGE_DIR, 0777, true);
		}

		// Load existing data if present
		$this->accounts = [];
		if (file_exists(self::STORAGE_FILE)) {
			$raw = @file_get_contents(self::STORAGE_FILE);
			$data = json_decode($raw, true);
			if (is_array($data)) {
				$this->accounts = $data;
			}
		}
	}

	private function persist(): void
	{
		$tmp = self::STORAGE_FILE . '.tmp';
		$fp = @fopen($tmp, 'wb');
		if ($fp === false) {
			return;
		}
		// Acquire exclusive lock while writing
		if (flock($fp, LOCK_EX)) {
			fwrite($fp, json_encode($this->accounts));
			fflush($fp);
			flock($fp, LOCK_UN);
		}
		fclose($fp);
		@rename($tmp, self::STORAGE_FILE);
	}

	public function all(): array
	{
		return $this->accounts;
	}

	public function get(string $id): ?int
	{
		return array_key_exists($id, $this->accounts) ? intval($this->accounts[$id]) : null;
	}

	public function set(string $id, int $balance): int
	{
		$this->accounts[$id] = intval($balance);
		$this->persist();
		return $this->accounts[$id];
	}

	public function resetAll(): void
	{
		$this->accounts = [];
		$this->persist();
	}

	public function transfer(string $originId, string $destinationId, int $amount): bool
	{
		$originBalance = $this->get($originId);
		$destinationBalance = $this->get($destinationId) ?? 0;
		try {
			if ($originBalance === null || $originBalance < $amount) {
				return false;
			}

			$this->set($originId, $originBalance - $amount);
			$this->set($destinationId, $destinationBalance + $amount);
			return true;
		} catch (\Throwable $e) {
			// Rollback in case of any error
			$this->set($originId, $originBalance);
			$this->set($destinationId, $destinationBalance);
			return false;
		}
	}
}
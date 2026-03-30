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
	/**
	 * Perform a transfer between two accounts atomically.
	 * @param string $originId The ID of the origin account
	 * @param string $destinationId The ID of the destination account
	 * @param int $amount The amount to transfer
	 * @return bool True if the transfer was successful, false otherwise
	 */
	public function transfer(string $originId, string $destinationId, int $amount): bool
	{
		$lockPath = self::STORAGE_FILE . '.lock';
		$lockFp = @fopen($lockPath, 'cb');
		if ($lockFp === false) {
			return false;
		}

		if (!flock($lockFp, LOCK_EX)) {
			fclose($lockFp);
			return false;
		}

		try {
			$accounts = [];
			if (file_exists(self::STORAGE_FILE)) {
				$raw = @file_get_contents(self::STORAGE_FILE);
				$data = json_decode($raw, true);
				if (is_array($data)) {
					$accounts = $data;
				}
			}

			$originBalance = array_key_exists($originId, $accounts) ? intval($accounts[$originId]) : null;
			$destinationBalance = array_key_exists($destinationId, $accounts) ? intval($accounts[$destinationId]) : 0;

			if ($originBalance === null || $originBalance < $amount) {
				return false;
			}

			$accounts[$originId] = $originBalance - $amount;
			$accounts[$destinationId] = $destinationBalance + $amount;

			$payload = json_encode($accounts);
			if ($payload === false) {
				return false;
			}

			$tmp = self::STORAGE_FILE . '.tmp';
			if (@file_put_contents($tmp, $payload) === false) {
				return false;
			}

			if (!@rename($tmp, self::STORAGE_FILE)) {
				@unlink($tmp);
				return false;
			}

			$this->accounts = $accounts;
			return true;
		} finally {
			flock($lockFp, LOCK_UN);
			fclose($lockFp);
		}
	}
}
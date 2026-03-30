<?php

namespace Api\Models;

/**
 * Class Accounts
 * Session-based storage for accounts and balances.
 * Uses PHP session to persist data across HTTP requests.
 */
class Accounts
{
	private const SESSION_KEY = 'accounts';
	private array $accounts;

	public function __construct()
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			throw new \RuntimeException('Session must be started before using Accounts model');
		}

		$this->accounts = $_SESSION[self::SESSION_KEY] ?? [];
	}

	private function persist(): void
	{
		$_SESSION[self::SESSION_KEY] = $this->accounts;
	}

	/**
	 * Returns all accounts and their balances
	 * @return array
	 */
	public function all(): array
	{
		return $this->accounts;
	}

	/**
	 * Returns the balance of the account with the given id, or null if it doesn't exist
	 * @param string $id
	 * @return int|null
	 */
	public function get(string $id): ?int
	{
		return array_key_exists($id, $this->accounts) ? intval($this->accounts[$id]) : null;
	}

	/**
	 * Sets the balance of the account with the given id, creating it if it doesn't exist
	 * @param string $id
	 * @param int $balance
	 * @return int the new balance of the account
	 */
	public function set(string $id, int $balance): int
	{
		$this->accounts[$id] = intval($balance);
		$this->persist();
		return $this->accounts[$id];
	}

	/**
	 * Deletes all accounts and resets to the initial state
	 * @return void
	 */
	public function resetAll(): void
	{
		$this->accounts = [];
		$this->persist();
	}
}

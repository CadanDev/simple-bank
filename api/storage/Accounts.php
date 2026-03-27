<?php

namespace Api\Storage;

/**
 * Class Accounts
 * A simple in-memory storage for accounts and their balances.
 * This class is not thread-safe and is intended for demonstration purposes only.
 */
class Accounts
{
	private const START_ACCOUNTS = [
		'100' => 100,
		'200' => 100,
	];
	private static array $accounts;

	public function __construct()
	{
		self::$accounts = self::START_ACCOUNTS;
	}
	/**
	 * Returns all accounts and their balances
	 * @return array
	 */
	public function all(): array
	{
		return self::$accounts;
	}
	/**
	 * Returns the balance of the account with the given id, or null if it doesn't exist
	 * @param string $id
	 * @return int|null
	 */
	public function get(string $id): ?int
	{
		return array_key_exists($id, self::$accounts) ? intval(self::$accounts[$id]) : null;
	}
	/**
	 * Sets the balance of the account with the given id, creating it if it doesn't exist
	 * @param string $id
	 * @param int $balance
	 * @return int the new balance of the account
	 */
	public function set(string $id, int $balance): int
	{
		self::$accounts[$id] = intval($balance);
		return self::$accounts[$id];
	}
	/**
	 * Deletes all accounts and resets to the initial state
	 * @return void
	 */
	public function deleteAll(): void
	{
		self::$accounts = self::START_ACCOUNTS;
	}
}

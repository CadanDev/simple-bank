<?php

namespace Api\Controllers;
use Api\Models\Accounts;
use App\Logger\Logger;

class EventController
{
	private function payloadValidation($payload): string|bool
	{
		if (!is_array($payload) || !isset($payload['type'])) {
			return 'invalid payload: type is required';
		}
		$type = $payload['type'];
		switch ($type) {
			case 'deposit':
				if (!isset($payload['destination'])) {
					return 'invalid payload: destination is required for deposit';
				}
				if (!isset($payload['amount'])) {
					return 'invalid payload: amount is required for deposit';
				}
				return true;
			case 'withdraw':
				if (!isset($payload['origin'])) {
					return 'invalid payload: origin is required for withdraw';
				}
				if (!isset($payload['amount'])) {
					return 'invalid payload: amount is required for withdraw';
				}
				return true;
			case 'transfer':
				if (!isset($payload['origin'])) {
					return 'invalid payload: origin is required for transfer';
				}
				if (!isset($payload['destination'])) {
					return 'invalid payload: destination is required for transfer';
				}
				if (!isset($payload['amount'])) {
					return 'invalid payload: amount is required for transfer';
				}
				return true;
		}
		return 'invalid payload: unknown event type';
	}
	/**
	 * Check if the account has sufficient balance for withdraw or transfer
	 * @param mixed $storage The storage instance to interact with account data
	 * @param mixed $accountId The ID of the account to check
	 * @param mixed $amount The amount to check against the account balance
	 * @return bool True if the account has sufficient balance, false otherwise
	 */
	private function balanceCheck($storage, $accountId, $amount): bool
	{
		$current = $storage->get($accountId);
		return $current !== null && intval($current) >= $amount;
	}
	/**
	 * Handle events: deposit, withdraw, transfer
	 * Expected payload:
	 * {
	 *   "type": "deposit" | "withdraw" | "transfer",
	 *   "origin": "account_id", // for withdraw and transfer
	 *   "destination": "account_id", // for deposit and transfer
	 *   "amount": 100
	 * }
	 */
	public function index()
	{
		$logger = new Logger();
		try {
			$storage = new Accounts();
			// This validate if the payload is a valid JSON and contains the required 'type' field
			$raw = file_get_contents('php://input');
			$payload = json_decode($raw, true);
			$validationResult = $this->payloadValidation($payload);
			if ($validationResult !== true) {
				$logger->error("EventController: invalid payload - {$raw}");
				http_response_code(400);
				return ['error' => $validationResult];
			}

			$type = $payload['type'];

			/*
			 * For deposit:
			 * - If the destination account does not exist, it should be created with the deposited amount as balance.
			 * - If the destination account already exists, the deposited amount should be added to the existing balance.
			 *
			 * For withdraw:
			 * - If the origin account does not exist, return 404.
			 * - If the origin account exists but has insufficient balance, return 400 with an error message "insufficient balance".
			 * - If the origin account exists and has sufficient balance, subtract the withdrawn amount from the existing balance.
			 *
			 * For transfer:
			 * - If the origin account does not exist, return 404.
			 * - If the origin account exists but has insufficient balance, return 400 with an error message "insufficient balance".
			 * - If the origin account exists and has sufficient balance, subtract the transferred amount from the origin account and add it to the destination account (creating it if it does not exist).
			 */
			switch ($type) {
				case 'deposit':
					$dest = (string) ($payload['destination'] ?? '');
					$amount = intval($payload['amount'] ?? 0);
					$current = $storage->get($dest);

					if ($current === null) {
						$logger->info("Deposit: destination {$dest} not found, creating account");
						$current = 0;
					}
					if($amount < 0) {
						$logger->warning("Deposit: negative amount - destination={$dest} amount={$amount}");
						http_response_code(400);
						return ['error' => 'amount must be non-negative'];
					}
					$new = $storage->set($dest, $current + $amount);
					$logger->info("Deposit: destination={$dest} amount={$amount} new_balance={$new}");
					http_response_code(201);
					return ['destination' => ['id' => $dest, 'balance' => intval($new)]];
				case 'withdraw':
					$origin = (string) ($payload['origin'] ?? '');
					$amount = intval($payload['amount'] ?? 0);
					$current = $storage->get($origin);

					if ($current === null) {
						$logger->warning("Withdraw: origin {$origin} not found");
						http_response_code(404);
						return 0;
					}
					
					if (!$this->balanceCheck($storage, $origin, $amount)) {
						$logger->warning("Withdraw: insufficient balance - origin={$origin} balance={$current} amount={$amount}");
						http_response_code(400);
						return ['error' => 'insufficient balance'];
					}
					$new = $storage->set($origin, intval($current) - $amount);
					$logger->info("Withdraw: origin={$origin} amount={$amount} new_balance={$new}");
					http_response_code(201);
					return ['origin' => ['id' => $origin, 'balance' => intval($new)]];
				case 'transfer':
					$origin = (string) ($payload['origin'] ?? '');
					$dest = (string) ($payload['destination'] ?? '');
					$amount = intval($payload['amount'] ?? 0);
					$currentOrigin = $storage->get($origin);

					if ($currentOrigin === null) {
						$logger->warning("Transfer: origin {$origin} not found");
						http_response_code(404);
						return 0;
					}
					if (!$this->balanceCheck($storage, $origin, $amount)) {
						$logger->warning("Transfer: insufficient balance - origin={$origin} balance={$currentOrigin} amount={$amount}");
						http_response_code(400);
						return ['error' => 'insufficient balance'];
					}
					$transferred = $storage->transfer($origin, $dest, $amount);
					if (!$transferred) {
						$logger->error("Transfer: failed to persist - origin={$origin} dest={$dest} amount={$amount}");
						http_response_code(500);
						return ['error' => 'internal error'];
					}
					$newOrigin = $storage->get($origin) ?? 0;
					$newDest = $storage->get($dest) ?? 0;
					$logger->info("Transfer: origin={$origin} dest={$dest} amount={$amount} origin_new={$newOrigin} dest_new={$newDest}");
					http_response_code(201);
					return ['origin' => ['id' => $origin, 'balance' => intval($newOrigin)], 'destination' => ['id' => $dest, 'balance' => intval($newDest)]];
				default:
					$logger->warning("EventController: unknown event type {$type}");
					http_response_code(400);
					return ['error' => 'unknown event type'];
			}
		} catch (\Throwable $e) {
			$logger->error("EventController exception: {$e->getMessage()}");
			http_response_code(500);
			return ['error' => 'internal error'];
		}
	}
}
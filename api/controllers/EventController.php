<?php

namespace Api\Controllers;
use Api\Models\Accounts;
use App\Logger\Logger;

class EventController
{
	public function index()
	{
		$logger = new Logger();
		try {
			$raw = file_get_contents('php://input');
			$payload = json_decode($raw, true);
			$storage = new Accounts();

			if (!is_array($payload) || !isset($payload['type'])) {
				$logger->error("EventController: invalid payload - {$raw}");
				http_response_code(400);
				return ['error' => 'invalid payload'];
			}

			$type = $payload['type'];

			switch ($type) {
				case 'deposit':
					$dest = (string) ($payload['destination'] ?? '');
					$amount = intval($payload['amount'] ?? 0);
					$current = $storage->get($dest);
					if ($current === null) {
						$logger->info("Deposit: destination {$dest} not found, creating account");
						$current = 0;
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
					$newOrigin = $storage->set($origin, intval($currentOrigin) - $amount);
					$currentDest = $storage->get($dest) ?? 0;
					$newDest = $storage->set($dest, intval($currentDest) + $amount);
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
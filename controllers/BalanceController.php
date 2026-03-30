<?php

namespace Api\Controllers;
use Api\Models\Accounts;
use App\Logger\Logger;

class BalanceController
{
	public function index()
	{
		$logger = new Logger();
		try {
			$accountId = $_GET['account_id'] ?? null;
			if ($accountId === null) {
				$logger->warning('BalanceController: account_id required');
				http_response_code(400);
				return ['error' => 'account_id required'];
			}

			$storage = new Accounts();
			$balance = $storage->get($accountId);

			if ($balance === null) {
				$logger->warning("BalanceController: account {$accountId} not found");
				http_response_code(404);
				return 0;
			}

			http_response_code(200);
			$logger->info("BalanceController: returned balance for account {$accountId}");
			return intval($balance);
		} catch (\Throwable $e) {
			$logger->error("BalanceController exception: {$e->getMessage()}");
			http_response_code(500);
			return ['error' => 'internal error'];
		}
	}
}
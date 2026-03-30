<?php

namespace Api\Controllers;

use Api\Models\Accounts;
use App\Logger\Logger;

class ResetController
{
	public function index()
	{
		$logger = new Logger();
		try {
			$storage = new Accounts();
			$logger->info('ResetController: deleting all accounts');
			$storage->resetAll();
			$logger->info('ResetController: resetAll completed');
			http_response_code(200);
			return true;
		} catch (\Throwable $e) {
			$logger->error("ResetController exception: {$e->getMessage()}");
			http_response_code(500);
			return ["INTERNAL ERROR"];
		}
	}
}

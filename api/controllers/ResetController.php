<?php

namespace Api\Controllers;

use Api\Storage\Accounts;
use App\Logger\Logger;

class ResetController
{
	public function index()
	{
		$logger = new Logger();
		try {
			$storage = new Accounts();
			$logger->info('ResetController: deleting all accounts');
			$storage->deleteAll();
			$logger->info('ResetController: deleteAll completed');
			http_response_code(200);
			return ['status' => 'ok'];
		} catch (\Throwable $e) {
			$logger->error('ResetController exception: ' . $e->getMessage());
			http_response_code(500);
			return ['error' => 'internal error'];
		}
	}
}

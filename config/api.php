<?php

use Api\Controllers\BalanceController;
use Api\Controllers\EventController;
use Api\Controllers\ResetController;

return [
	'/api/teste' => 'Hello World',
	'/api/balance' => BalanceController::class,
	'/api/event' => EventController::class,
	'/api/reset' => ResetController::class,
];
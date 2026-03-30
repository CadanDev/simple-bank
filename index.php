<?php
use Api\Controllers\ApiController;

$config = require_once __DIR__ . '/config/api.php';

/**
 * Initialize error reporting for development.
 * In production, these settings should be adjusted to log errors instead of displaying them.
 */
if($config['environment'] === 'dev') {
	ini_set('display_errors', '1');
	ini_set('display_startup_errors', '1');
	error_reporting(E_ALL);
} else {
	ini_set('display_errors', '0');
	error_reporting(0);
}

/**
 * Register a shutdown function to catch fatal errors and return a JSON response.
 * This ensures that even if a fatal error occurs, the client receives a structured error message.
 */
register_shutdown_function(function () {
	$err = error_get_last();
	if ($err !== null) {
		http_response_code(500);
		header('Content-Type: application/json');
		echo json_encode([
			'error' => 'Fatal error',
			'message' => $err['message'] ?? null,
			'file' => $err['file'] ?? null,
			'line' => $err['line'] ?? null,
		]);
	}
});

require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/config/session.php';

$controller = new ApiController();
$controller->handleRequest();
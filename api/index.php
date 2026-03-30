<?php
use Api\Controllers\ApiController;

// Show all errors during development to diagnose 500 responses
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Shutdown handler to catch fatal errors and return a JSON payload
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


/**
 * Simple autoloader for Api and App namespaces
 */
spl_autoload_register(function ($class) {
	$baseDir = __DIR__ . '/';
	if (strpos($class, 'Api\\') === 0) {
		$rel = str_replace('\\', '/', substr($class, strlen('Api\\')));
		$file = $baseDir . $rel . '.php';
		if (file_exists($file)) {
			require_once $file;
		}
	}
	if (strpos($class, 'App\\') === 0) {
		$rel = str_replace('\\', '/', substr($class, strlen('App\\')));
		$file = $baseDir . 'app/' . $rel . '.php';
		if (file_exists($file)) {
			require_once $file;
		} else {
			// Fallback: try app/<basename>.php (e.g. App\\Logger\\Logger -> app/Logger.php)
			$basename = basename($rel);
			$fallback = $baseDir . 'app/' . $basename . '.php';
			if (file_exists($fallback)) {
				require_once $fallback;
			}
		}
	}
});

require_once __DIR__ . '/config/session.php';

$controller = new ApiController();
$controller->handleRequest();
echo json_encode(['status' => 'success', 'message' => 'Request processed']);
?>

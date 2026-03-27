<?php

use Api\Controllers\ApiController;

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
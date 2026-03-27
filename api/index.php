<?php
require_once __DIR__ . '/logger/Logger.php';
$routes = require_once __DIR__ . '/routes/api.php';
$logger = new \App\Logger\Logger();

// Autoload simples para namespaces Api\ and App\ (carrega arquivos relativos a /api)
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
		$file = $baseDir . $rel . '.php';
		if (file_exists($file)) {
			require_once $file;
		}
	}
});

try {
	$requestUri = $_SERVER['REQUEST_URI'];
	$path = parse_url($requestUri, PHP_URL_PATH);
	$route = $path;
	
	if (array_key_exists($route, $routes)) {
		$controllerClass = $routes[$route];
		if (is_string($controllerClass) && !class_exists($controllerClass)) {
			header('Content-Type: application/json');
			echo json_encode(['message' => $controllerClass]);
		} else {
			$controller = new $controllerClass();
			$response = $controller->index();
			header('Content-Type: application/json');
			echo json_encode($response);
		}
	} else {
		$logger->warning("Route not found: " . $route);
		http_response_code(404);
		echo json_encode(['error' => 'Route not found']);
	}
} catch (Exception $e) {
	$logger->error("Error processing request: " . $e->getMessage());
	http_response_code(500);
	echo json_encode(['error' => 'Internal Server Error']);
}
<?php

namespace Api\Controllers;

use App\Logger\Logger;

class ApiController
{
	protected Logger $logger;
	protected array $routes;

	public function __construct()
	{
		$this->logger = new Logger();
		$this->routes = require_once __DIR__ . '/../config/api.php';
	}

	public function handleRequest(): void
	{
		try {
			$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
			$path = parse_url($requestUri, PHP_URL_PATH);
			// Remove base folder (if app is hosted in a subdirectory) so routes
			// defined like '/api/teste' match regardless of the public path.
			$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
			$baseDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
			if ($baseDir !== '' && strpos($path, $baseDir) === 0) {
				$path = substr($path, strlen($baseDir));
				if ($path === '') {
					$path = '/';
				}
			}
			$route = $path;

			// Allow routes to be matched with or without the '/api' prefix.
			// Try the requested path first, then the alternative (add/remove '/api').
			$candidates = [$route];
			if (strpos($route, '/api') === 0) {
				$stripped = substr($route, 4);
				if ($stripped === '') {
					$stripped = '/';
				}
				$candidates[] = $stripped;
			} else {
				$candidates[] = '/api' . ($route === '/' ? '' : $route);
			}

			$matched = null;
			foreach ($candidates as $r) {
				if (array_key_exists($r, $this->routes)) {
					$matched = $r;
					break;
				}
			}

			if ($matched !== null) {
				$controllerClass = $this->routes[$matched];
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
				$this->logger->warning("Route not found: $route");
				http_response_code(404);
				header('Content-Type: application/json');
				echo json_encode(['error' => 'Route not found']);
			}
		} catch (\Throwable $e) {
			$this->logger->error("Error processing request: {$e->getMessage()}");
			http_response_code(500);
			header('Content-Type: application/json');
			echo json_encode(['error' => 'Internal Server Error']);
		}
	}
}
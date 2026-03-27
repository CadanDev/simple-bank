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
			$route = $path;

			if (array_key_exists($route, $this->routes)) {
				$controllerClass = $this->routes[$route];
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
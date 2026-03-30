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
		$routes = require __DIR__ . '/../config/api.php';
		if (!is_array($routes)) {
			$this->logger->warning('ApiController: config/api.php did not return an array, using empty routes');
			$this->routes = [];
		} else {
			$this->routes = $routes;
		}
	}
	/**
	 * Handle incoming HTTP requests, route them to the appropriate controller, and return the response.
	 * This method processes the request URI, matches it against defined routes, and invokes the corresponding controller's index method.
	 * It also handles errors and returns appropriate HTTP status codes and responses.
	 */
	public function handleRequest(): void
	{
		try {
			$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
			$path = parse_url($requestUri, PHP_URL_PATH);
			$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
			$baseDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
			if ($baseDir !== '' && strpos($path, $baseDir) === 0) {
				$path = substr($path, strlen($baseDir));
				if ($path === '') {
					$path = '/';
				}
			}
			$route = $path;

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
					if (is_array($response) || is_object($response)) {
						header('Content-Type: application/json');
						echo json_encode($response);
					} else {
						header('Content-Type: text/plain; charset=utf-8');
						echo (string) $response;
					}
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
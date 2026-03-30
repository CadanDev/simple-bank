<?php
/**
 * Simple autoloader for Api and App namespaces
 * This allows us to automatically include class files based on their namespace and class name.
 * It checks for classes in the Api namespace and loads them from the corresponding directory structure.
 * For classes in the App namespace, it first tries to load from the app/ directory, and if not found, it falls back to loading from the app/ directory using just the class name.
 */
spl_autoload_register(function ($class) {
	$baseDir = __DIR__ . '/';
	if (strpos($class, 'Api\\') === 0) {
			$rel = str_replace('\\', '/', substr($class, strlen('Api\\')));
			$parts = explode('/', $rel);
			if (count($parts) > 0) {
				$parts[0] = strtolower($parts[0]);
			}
			$relFixed = implode('/', $parts);
			$file = $baseDir . $relFixed . '.php';
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
			$basename = basename($rel);
			$fallback = $baseDir . 'app/' . $basename . '.php';
			if (file_exists($fallback)) {
				require_once $fallback;
			}
		}
	}
});
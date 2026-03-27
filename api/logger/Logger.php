<?php

namespace App\Logger;

use DateTime;
class Logger
{
	private string $logsDir;
	private string $logFile;

	public function __construct()
	{
		$this->logsDir = __DIR__ . '/../../logs';
		$this->ensureLogsDirectory();
		$this->logFile = $this->logsDir . '/' . date('Y-m-d') . '.log';
	}

	private function ensureLogsDirectory(): void
	{
		if (!is_dir($this->logsDir)) {
			mkdir($this->logsDir, 0755, true);
		}
	}

	public function info(string $message): void
	{
		$this->write('INFO', $message);
	}

	public function error(string $message): void
	{
		$this->write('ERROR', $message);
	}

	public function warning(string $message): void
	{
		$this->write('WARNING', $message);
	}

	public function debug(string $message): void
	{
		$this->write('DEBUG', $message);
	}

	private function write(string $level, string $message): void
	{
		$timestamp = (new DateTime())->format('Y-m-d H:i:s');
		$logLine = "[{$timestamp}] {$level}: {$message}" . PHP_EOL;
		file_put_contents($this->logFile, $logLine, FILE_APPEND);
	}
}
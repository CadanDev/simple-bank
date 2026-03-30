<?php

namespace App\Logger;

use DateTime;
class Logger
{
	private string $logLevelEnv;
	private string $logsDir;
	private string $logFile;

	public function __construct()
	{
		$this->logsDir = __DIR__ . '/../logs';
		$this->ensureLogsDirectory();
		$this->logFile = $this->logsDir . '/' . date('Y-m-d') . '.log';
		$this->logLevelEnv = 'prod';// prod/dev
	}
	/**
	 * Function that ensures the logs directory exists, if not it creates it.
	 * @return void
	 */
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
	/**
	 * Function that write the called log information in log file.
	 * @param string $level Log level (INFO, ERROR, WARNING, DEBUG)
	 * @param string $message Log message to be written in log file
	 * @return void
	 */
	private function write(string $level, string $message): void
	{
		if ($this->logLevelEnv === 'prod' && !in_array($level, ['ERROR', 'DEBUG'])) {
			return;
		}

		$timestamp = (new DateTime())->format('Y-m-d H:i:s');
		$logLine = "[{$timestamp}] {$level}: {$message}" . PHP_EOL;
		file_put_contents($this->logFile, $logLine, FILE_APPEND);
	}
}
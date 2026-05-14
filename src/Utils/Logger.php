<?php

declare(strict_types=1);

namespace Wizdam\Utils;

/**
 * Logger Class
 * Logging utility untuk error, info, dan debug messages
 * 
 * @version 2.0.0 (PSR-4/PSR-12 Compliant)
 * @author Wizdam Team
 * @license MIT
 */
class Logger
{
    private const LEVEL_ERROR = 'ERROR';
    private const LEVEL_INFO = 'INFO';
    private const LEVEL_WARNING = 'WARNING';
    private const LEVEL_DEBUG = 'DEBUG';

    private string $logFile;
    private bool $enabled;
    private string $minLevel;

    private array $levelPriority = [
        self::LEVEL_DEBUG => 1,
        self::LEVEL_INFO => 2,
        self::LEVEL_WARNING => 3,
        self::LEVEL_ERROR => 4,
    ];

    /**
     * Constructor
     */
    public function __construct(
        string $logFile = '',
        bool $enabled = true,
        string $minLevel = self::LEVEL_INFO
    ) {
        $this->logFile = $logFile ?: PROJECT_ROOT . '/logs/app.log';
        $this->enabled = $enabled;
        $this->minLevel = $minLevel;

        // Create logs directory if it doesn't exist
        if ($this->enabled) {
            $logDir = dirname($this->logFile);
            if (!file_exists($logDir)) {
                mkdir($logDir, 0755, true);
            }
        }
    }

    /**
     * Log error message
     */
    public function error(string $message): void
    {
        $this->log(self::LEVEL_ERROR, $message);
    }

    /**
     * Log info message
     */
    public function info(string $message): void
    {
        $this->log(self::LEVEL_INFO, $message);
    }

    /**
     * Log warning message
     */
    public function warning(string $message): void
    {
        $this->log(self::LEVEL_WARNING, $message);
    }

    /**
     * Log debug message
     */
    public function debug(string $message): void
    {
        $this->log(self::LEVEL_DEBUG, $message);
    }

    /**
     * Log message with level
     */
    private function log(string $level, string $message): void
    {
        if (!$this->enabled) {
            return;
        }

        // Check if level meets minimum priority
        if (($this->levelPriority[$level] ?? 0) < ($this->levelPriority[$this->minLevel] ?? 0)) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $logEntry = sprintf("[%s] %s: %s%s", $timestamp, $level, $message, PHP_EOL);

        error_log($logEntry, 3, $this->logFile);
    }

    /**
     * Log exception
     */
    public function exception(\Throwable $exception): void
    {
        $message = sprintf(
            "%s in %s:%d\nStack trace:\n%s",
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        $this->error($message);
    }

    /**
     * Clear log file
     */
    public function clear(): bool
    {
        if (file_exists($this->logFile)) {
            return unlink($this->logFile);
        }
        return false;
    }

    /**
     * Get log file path
     */
    public function getLogFile(): string
    {
        return $this->logFile;
    }

    /**
     * Read last N lines from log
     */
    public function tail(int $lines = 50): array
    {
        if (!file_exists($this->logFile)) {
            return [];
        }

        $file = new \SplFileObject($this->logFile);
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();

        $startLine = max(0, $totalLines - $lines);
        $result = [];

        $file->seek($startLine);
        while (!$file->eof()) {
            $line = $file->current();
            if ($line !== null && $line !== '') {
                $result[] = rtrim($line, "\n\r");
            }
            $file->next();
        }

        return $result;
    }

    /**
     * Search log for pattern
     */
    public function search(string $pattern, int $limit = 100): array
    {
        if (!file_exists($this->logFile)) {
            return [];
        }

        $results = [];
        $file = new \SplFileObject($this->logFile);

        while (!$file->eof()) {
            $line = $file->current();
            if ($line !== null && preg_match('/' . preg_quote($pattern, '/') . '/i', $line)) {
                $results[] = rtrim($line, "\n\r");
                if (count($results) >= $limit) {
                    break;
                }
            }
            $file->next();
        }

        return $results;
    }

    /**
     * Get log statistics
     */
    public function getStats(): array
    {
        if (!file_exists($this->logFile)) {
            return [
                'total_lines' => 0,
                'file_size' => 0,
                'file_size_formatted' => '0 B',
                'last_modified' => null,
                'error_count' => 0,
                'warning_count' => 0,
                'info_count' => 0,
                'debug_count' => 0,
            ];
        }

        $file = new \SplFileObject($this->logFile);
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();

        $content = file_get_contents($this->logFile);
        if ($content === false) {
            $content = '';
        }

        return [
            'total_lines' => $totalLines,
            'file_size' => filesize($this->logFile) ?: 0,
            'file_size_formatted' => Validator::formatFileSize(filesize($this->logFile) ?: 0),
            'last_modified' => date('Y-m-d H:i:s', filemtime($this->logFile)),
            'error_count' => substr_count($content, 'ERROR:'),
            'warning_count' => substr_count($content, 'WARNING:'),
            'info_count' => substr_count($content, 'INFO:'),
            'debug_count' => substr_count($content, 'DEBUG:'),
        ];
    }
}

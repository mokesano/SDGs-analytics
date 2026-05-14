<?php

declare(strict_types=1);

namespace Wizdam\Utils;

/**
 * Cache Manager Class
 * Mengelola caching data dengan file-based storage
 * 
 * @version 2.0.0 (PSR-4/PSR-12 Compliant)
 * @author Wizdam Team
 * @license MIT
 */
class CacheManager
{
    private string $cacheDir;
    private bool $enabled;
    private int $ttl;
    private bool $compressionEnabled;

    /**
     * Constructor
     */
    public function __construct(
        string $cacheDir = '',
        bool $enabled = true,
        int $ttl = 3600,
        bool $compressionEnabled = true
    ) {
        // Use provided cacheDir or default to project root /cache
        if ($cacheDir === '') {
            // Fallback: use relative path from current file or default
            $projectRoot = dirname(__DIR__, 2); // Go up from src/Utils to project root
            $cacheDir = $projectRoot . '/cache';
        }
        
        $this->cacheDir = $cacheDir;
        $this->enabled = $enabled;
        $this->ttl = $ttl;
        $this->compressionEnabled = $compressionEnabled;

        // Ensure cache directory exists
        if ($this->enabled && !file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Generate cache filename
     */
    public function getCacheFilename(string $type, string $identifier): string
    {
        $safeIdentifier = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $identifier);
        return $this->cacheDir . '/' . $type . '_' . $safeIdentifier . '.json';
    }

    /**
     * Read from cache
     */
    public function read(string $cacheFile): mixed
    {
        if (!$this->enabled || !file_exists($cacheFile)) {
            return false;
        }

        if (time() - filemtime($cacheFile) > $this->ttl) {
            unlink($cacheFile);
            return false;
        }

        $content = file_get_contents($cacheFile);
        if ($content === false) {
            return false;
        }

        // Try to decompress if needed
        if ($this->compressionEnabled) {
            $decompressed = @gzuncompress($content);
            if ($decompressed !== false) {
                $content = $decompressed;
            }
        }

        return json_decode($content, true);
    }

    /**
     * Write to cache
     */
    public function write(string $cacheFile, mixed $data): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

        // Compress if enabled
        if ($this->compressionEnabled) {
            $jsonData = gzcompress($jsonData, 6);
        }

        // Ensure cache directory exists
        $cacheDir = dirname($cacheFile);
        if (!file_exists($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        return file_put_contents($cacheFile, $jsonData, LOCK_EX) !== false;
    }

    /**
     * Clear cache for specific type or all
     */
    public function clear(?string $type = null): bool
    {
        if (!is_dir($this->cacheDir)) {
            return true;
        }

        $pattern = $type ? $this->cacheDir . '/' . $type . '_*.json' : $this->cacheDir . '/*.json';
        $files = glob($pattern);

        if ($files === false) {
            return true;
        }

        foreach ($files as $file) {
            unlink($file);
        }

        return true;
    }

    /**
     * Check if cache exists and is valid
     */
    public function has(string $cacheFile): bool
    {
        if (!$this->enabled || !file_exists($cacheFile)) {
            return false;
        }

        return time() - filemtime($cacheFile) <= $this->ttl;
    }

    /**
     * Get cache file age in seconds
     */
    public function getAge(string $cacheFile): int
    {
        if (!file_exists($cacheFile)) {
            return -1;
        }

        return time() - filemtime($cacheFile);
    }

    /**
     * Delete specific cache file
     */
    public function delete(string $cacheFile): bool
    {
        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }
        return false;
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        if (!is_dir($this->cacheDir)) {
            return [
                'total_files' => 0,
                'total_size' => 0,
                'oldest_file' => null,
                'newest_file' => null,
            ];
        }

        $files = glob($this->cacheDir . '/*.json');
        if ($files === false) {
            $files = [];
        }

        $totalSize = 0;
        $oldestTime = null;
        $newestTime = null;
        $oldestFile = null;
        $newestFile = null;

        foreach ($files as $file) {
            $size = filesize($file);
            if ($size !== false) {
                $totalSize += $size;
            }

            $mtime = filemtime($file);
            if ($mtime !== false) {
                if ($oldestTime === null || $mtime < $oldestTime) {
                    $oldestTime = $mtime;
                    $oldestFile = $file;
                }
                if ($newestTime === null || $mtime > $newestTime) {
                    $newestTime = $mtime;
                    $newestFile = $file;
                }
            }
        }

        return [
            'total_files' => count($files),
            'total_size' => $totalSize,
            'total_size_formatted' => Validator::formatFileSize($totalSize),
            'oldest_file' => $oldestFile,
            'newest_file' => $newestFile,
            'oldest_time' => $oldestTime ? date('Y-m-d H:i:s', $oldestTime) : null,
            'newest_time' => $newestTime ? date('Y-m-d H:i:s', $newestTime) : null,
        ];
    }
}

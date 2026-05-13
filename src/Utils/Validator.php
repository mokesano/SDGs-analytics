<?php

declare(strict_types=1);

namespace Wizdam\Utils;

use Exception;

/**
 * Validation Utility Class
 * Fungsi validasi input untuk ORCID, DOI, dan data lainnya
 * 
 * @version 2.0.0 (PSR-4/PSR-12 Compliant)
 * @author Wizdam Team
 * @license MIT
 */
class Validator
{
    /**
     * Validate ORCID ID format
     */
    public static function validateOrcid(string $orcid): bool
    {
        // Remove URL prefix if present
        $cleanOrcid = str_replace(['https://orcid.org/', 'http://orcid.org/'], '', $orcid);

        // ORCID format: 0000-0000-0000-000X (where X is checksum)
        if (!preg_match('/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/', $cleanOrcid)) {
            return false;
        }

        // Validate checksum
        return self::validateOrcidChecksum($cleanOrcid);
    }

    /**
     * Validate ORCID checksum
     */
    public static function validateOrcidChecksum(string $orcid): bool
    {
        $digits = str_replace('-', '', substr($orcid, 0, -1));
        $checkDigit = strtoupper(substr($orcid, -1));

        $total = 0;
        for ($i = 0; $i < strlen($digits); $i++) {
            $total = ($total + intval($digits[$i])) * 2;
        }

        $remainder = $total % 11;
        $result = (12 - $remainder) % 11;
        $expectedCheckDigit = ($result == 10) ? 'X' : strval($result);

        return $checkDigit === $expectedCheckDigit;
    }

    /**
     * Validate DOI format
     */
    public static function validateDoi(string $doi): bool
    {
        // Remove URL prefix if present
        $cleanDoi = preg_replace('/^https?:\/\/(dx\.)?doi\.org\//', '', $doi);

        // Basic DOI format validation
        return (bool) preg_match('/^10\.\d{4,}\/[^\s]+$/', $cleanDoi);
    }

    /**
     * Clean and normalize input
     */
    public static function cleanInput(string $input): string
    {
        return trim(strip_tags($input));
    }

    /**
     * Sanitize output for display
     */
    public static function sanitizeOutput(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate email format
     */
    public static function validateEmail(string $email): bool
    {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Validate URL format
     */
    public static function validateUrl(string $url): bool
    {
        return (bool) filter_var($url, FILTER_VALIDATE_URL);
    }

    /**
     * Validate integer range
     */
    public static function validateIntRange(int $value, int $min, int $max): bool
    {
        return $value >= $min && $value <= $max;
    }

    /**
     * Validate required field
     */
    public static function validateRequired(?string $value): bool
    {
        return $value !== null && trim($value) !== '';
    }

    /**
     * Validate array has minimum items
     */
    public static function validateMinArrayItems(array $array, int $min): bool
    {
        return count($array) >= $min;
    }

    /**
     * Get user IP address
     */
    public static function getUserIpAddress(): string
    {
        $ipHeaders = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];

        foreach ($ipHeaders as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Handle comma-separated IPs (X-Forwarded-For)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Generate unique request ID
     */
    public static function generateRequestId(): string
    {
        return uniqid('req_', true);
    }

    /**
     * Format file size
     */
    public static function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Calculate execution time
     */
    public static function calculateExecutionTime(float $startTime): float
    {
        return round(microtime(true) - $startTime, 3);
    }
}

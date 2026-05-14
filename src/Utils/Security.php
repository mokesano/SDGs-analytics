<?php

declare(strict_types=1);

namespace Wizdam\Utils;

/**
 * Security Utility Class
 * Fungsi keamanan untuk CSRF, session, dan rate limiting
 * 
 * @version 2.0.0 (PSR-4/PSR-12 Compliant)
 * @author Wizdam Team
 * @license MIT
 */
class Security
{
    private const CSRF_TOKEN_KEY = 'csrf_token';

    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken(): string
    {
        if (!isset($_SESSION[self::CSRF_TOKEN_KEY])) {
            $_SESSION[self::CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::CSRF_TOKEN_KEY];
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken(string $token): bool
    {
        return isset($_SESSION[self::CSRF_TOKEN_KEY]) && hash_equals($_SESSION[self::CSRF_TOKEN_KEY], $token);
    }

    /**
     * Regenerate CSRF token
     */
    public static function regenerateCsrfToken(): string
    {
        $_SESSION[self::CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
        return $_SESSION[self::CSRF_TOKEN_KEY];
    }

    /**
     * Rate limiting check
     */
    public static function checkRateLimit(
        CacheManager $cache,
        string $identifier,
        int $limit = 60,
        int $window = 3600
    ): bool {
        $cacheFile = $cache->getCacheFilename('rate_limit', $identifier);
        $rateData = $cache->read($cacheFile);

        $currentTime = time();

        if ($rateData === false) {
            $rateData = [
                'count' => 1,
                'window_start' => $currentTime,
            ];
        } else {
            // Reset window if expired
            if ($currentTime - $rateData['window_start'] > $window) {
                $rateData = [
                    'count' => 1,
                    'window_start' => $currentTime,
                ];
            } else {
                $rateData['count']++;
            }
        }

        $cache->write($cacheFile, $rateData);

        return $rateData['count'] <= $limit;
    }

    /**
     * Get remaining requests for rate limit
     */
    public static function getRemainingRequests(
        CacheManager $cache,
        string $identifier,
        int $limit = 60,
        int $window = 3600
    ): int {
        $cacheFile = $cache->getCacheFilename('rate_limit', $identifier);
        $rateData = $cache->read($cacheFile);

        if ($rateData === false) {
            return $limit;
        }

        $currentTime = time();
        if ($currentTime - $rateData['window_start'] > $window) {
            return $limit;
        }

        return max(0, $limit - $rateData['count']);
    }

    /**
     * Sanitize array input recursively
     */
    public static function sanitizeArray(array $input): array
    {
        $result = [];
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $result[$key] = self::sanitizeArray($value);
            } elseif (is_string($value)) {
                $result[$key] = Validator::sanitizeOutput($value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Validate session is active
     */
    public static function validateSession(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Secure session configuration
     */
    public static function configureSecureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session cookies
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');

            session_start();
        }
    }

    /**
     * Check if request is from localhost
     */
    public static function isLocalhost(): bool
    {
        $ip = Validator::getUserIpAddress();
        return in_array($ip, ['127.0.0.1', '::1', 'localhost'], true);
    }

    /**
     * Validate allowed origin for CORS
     */
    public static function validateOrigin(string $origin, array $allowedOrigins): bool
    {
        if (empty($allowedOrigins)) {
            return false;
        }

        foreach ($allowedOrigins as $allowed) {
            if ($allowed === '*') {
                return true;
            }
            if ($allowed === $origin) {
                return true;
            }
            // Support wildcard subdomains
            if (strpos($allowed, '*') !== false) {
                $pattern = '/^' . str_replace('.', '\\.', str_replace('*', '.*', $allowed)) . '$/';
                if (preg_match($pattern, $origin)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Send CORS headers
     */
    public static function sendCorsHeaders(string $origin, array $allowedOrigins): void
    {
        if (self::validateOrigin($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: {$origin}");
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');
        }
    }

    /**
     * Handle preflight OPTIONS request
     */
    public static function handlePreflightRequest(string $origin, array $allowedOrigins): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            self::sendCorsHeaders($origin, $allowedOrigins);
            http_response_code(204);
            exit;
        }
    }
}

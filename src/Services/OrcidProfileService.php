<?php

declare(strict_types=1);

namespace Wizdam\Services;

use Exception;

/**
 * ORCID Profile Service
 * 
 * Wrapper class untuk ORCID_Profile_API.php yang menyediakan interface OOP
 * untuk pengambilan data profil peneliti dari ORCID tanpa mengubah file API asli.
 * 
 * @version 1.0.0
 * @author Wizdam Team
 * @license MIT
 */
class OrcidProfileService
{
    private string $apiFilePath;
    private string $cacheDir;
    private int $cacheTtl;
    private int $abstractCacheTtl;
    private int $apiTimeout;

    /**
     * Constructor
     */
    public function __construct(
        string $apiFilePath = '',
        string $cacheDir = '',
        int $cacheTtl = 86400,
        int $abstractCacheTtl = 604800,
        int $apiTimeout = 5
    ) {
        $this->apiFilePath = $apiFilePath ?: PROJECT_ROOT . '/api/ORCID_Profile_API.php';
        
        if (!file_exists($this->apiFilePath)) {
            throw new Exception('ORCID Profile API file not found: ' . $this->apiFilePath);
        }

        $this->cacheDir = $cacheDir ?: PROJECT_ROOT . '/api/cache';
        $this->cacheTtl = $cacheTtl;
        $this->abstractCacheTtl = $abstractCacheTtl;
        $this->apiTimeout = $apiTimeout;

        // Ensure cache directory exists
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Validate ORCID format
     * 
     * @param string $orcid ORCID to validate
     * @return bool True if valid
     */
    public function isValidOrcid(string $orcid): bool
    {
        $cleanOrcid = str_replace(['https://orcid.org/', 'http://orcid.org/'], '', $orcid);
        
        if (!preg_match('/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/', $cleanOrcid)) {
            return false;
        }

        return $this->validateOrcidChecksum($cleanOrcid);
    }

    /**
     * Validate ORCID checksum
     */
    private function validateOrcidChecksum(string $orcid): bool
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
     * Clean ORCID input (remove URL prefixes)
     */
    public function cleanOrcid(string $orcid): string
    {
        return str_replace(['https://orcid.org/', 'http://orcid.org/'], '', trim($orcid));
    }

    /**
     * Get cache key for ORCID
     */
    private function getCacheKey(string $orcid): string
    {
        return 'orcid_' . substr(md5($orcid), 0, 8) . '_' . $orcid;
    }

    /**
     * Read from cache
     */
    private function readFromCache(string $key): mixed
    {
        $cacheFile = $this->cacheDir . '/' . $key . '.json.gz';
        
        if (!file_exists($cacheFile)) {
            return false;
        }

        $content = @gzdecode(file_get_contents($cacheFile));
        if ($content === false) {
            return false;
        }

        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        // Check TTL
        $ttl = isset($data['has_abstract']) && $data['has_abstract'] 
            ? $this->abstractCacheTtl 
            : $this->cacheTtl;

        if (time() - filemtime($cacheFile) > $ttl) {
            unlink($cacheFile);
            return false;
        }

        return $data;
    }

    /**
     * Write to cache
     */
    private function writeToCache(string $key, array $data): bool
    {
        $cacheFile = $this->cacheDir . '/' . $key . '.json.gz';
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
        $compressed = gzencode($jsonData, 6);
        
        if ($compressed === false) {
            return false;
        }

        return file_put_contents($cacheFile, $compressed, LOCK_EX) !== false;
    }

    /**
     * Fetch researcher profile from ORCID API
     * 
     * @param string $orcid Researcher ORCID
     * @param bool $forceRefresh Force refresh cache
     * @return array|null Profile data or null on failure
     */
    public function getProfile(string $orcid, bool $forceRefresh = false): ?array
    {
        $cleanOrcid = $this->cleanOrcid($orcid);

        if (!$this->isValidOrcid($cleanOrcid)) {
            throw new Exception('Invalid ORCID format: ' . $orcid);
        }

        // Check cache first
        $cacheKey = $this->getCacheKey($cleanOrcid);
        if (!$forceRefresh) {
            $cached = $this->readFromCache($cacheKey);
            if ($cached !== false && isset($cached['status']) && $cached['status'] === 'success') {
                return $cached;
            }
        }

        // Fetch from API using cURL
        $profileUrl = 'https://pub.orcid.org/v3.0/' . $cleanOrcid;
        $data = $this->makeRequest($profileUrl);

        if ($data === null) {
            return null;
        }

        // Extract profile information
        $profile = $this->extractProfileData($data);
        $profile['status'] = 'success';
        $profile['orcid'] = $cleanOrcid;

        // Cache the result
        $this->writeToCache($cacheKey, $profile);

        return $profile;
    }

    /**
     * Make HTTP request to ORCID API
     */
    private function makeRequest(string $url): ?array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => $this->apiTimeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => 'Wizdam-SDG-Analyzer/1.0',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || !empty($error)) {
            error_log('ORCID API cURL Error: ' . $error);
            return null;
        }

        if ($httpCode >= 400) {
            error_log('ORCID API HTTP Error: ' . $httpCode);
            return null;
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('ORCID API JSON Decode Error: ' . json_last_error_msg());
            return null;
        }

        return $decoded;
    }

    /**
     * Extract profile data from ORCID response
     */
    private function extractProfileData(array $data): array
    {
        $person = $data['person'] ?? [];
        $name = $person['name'] ?? [];

        return [
            'given_names' => $name['given-names']['value'] ?? '',
            'family_name' => $name['family-name']['value'] ?? '',
            'full_name' => trim(($name['given-names']['value'] ?? '') . ' ' . ($name['family-name']['value'] ?? '')),
            'credit_name' => $name['credit-name']['value'] ?? '',
            'biography' => $person['biography']['content'] ?? '',
            'country' => $person['addresses']['address'][0]['country']['name'] ?? '',
            'keywords' => $this->extractKeywords($person),
            'external_urls' => $this->extractExternalUrls($person),
        ];
    }

    /**
     * Extract keywords from profile
     */
    private function extractKeywords(array $person): array
    {
        $keywords = [];
        if (isset($person['keywords']['keyword'])) {
            foreach ($person['keywords']['keyword'] as $kw) {
                $keywords[] = $kw['content'] ?? '';
            }
        }
        return $keywords;
    }

    /**
     * Extract external URLs from profile
     */
    private function extractExternalUrls(array $person): array
    {
        $urls = [];
        if (isset($person['researcher-urls']['researcher-url'])) {
            foreach ($person['researcher-urls']['researcher-url'] as $url) {
                $urls[] = $url['url']['value'] ?? '';
            }
        }
        return $urls;
    }

    /**
     * Clear cache for specific ORCID
     */
    public function clearCache(string $orcid): bool
    {
        $cleanOrcid = $this->cleanOrcid($orcid);
        $cacheKey = $this->getCacheKey($cleanOrcid);
        $cacheFile = $this->cacheDir . '/' . $cacheKey . '.json.gz';

        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }

        return false;
    }

    /**
     * Get cache directory
     */
    public function getCacheDir(): string
    {
        return $this->cacheDir;
    }

    /**
     * Set cache TTL
     */
    public function setCacheTtl(int $ttl): void
    {
        $this->cacheTtl = $ttl;
    }
}

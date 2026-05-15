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
    private ?array $lastFetchedData = null;

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
        // Use provided path or default to api/ORCID_Profile_API.php
        if ($apiFilePath === '') {
            $projectRoot = dirname(__DIR__, 2); // Go up from src/Services to project root
            $apiFilePath = $projectRoot . '/api/ORCID_Profile_API.php';
        }
        
        $this->apiFilePath = $apiFilePath;
        
        if (!file_exists($this->apiFilePath)) {
            throw new Exception('ORCID Profile API file not found: ' . $this->apiFilePath);
        }

        // Use provided cacheDir or default to api/cache
        if ($cacheDir === '') {
            $projectRoot = dirname(__DIR__, 2);
            $cacheDir = $projectRoot . '/api/cache';
        }
        
        // Fallback to project cache directory
        if (!is_dir($cacheDir)) {
            $cacheDir = dirname(__DIR__, 2) . '/cache';
        }
        
        $this->cacheDir = $cacheDir;
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
     * Read profile from cache
     */
    private function readProfileFromCache(string $orcid): ?array
    {
        $cacheKey = $this->getCacheKey($orcid);
        $cacheFile = $this->cacheDir . '/' . $cacheKey . '.json.gz';
        
        if (!file_exists($cacheFile)) {
            return null;
        }

        $content = @gzdecode(file_get_contents($cacheFile));
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        // Check TTL
        $ttl = isset($data['has_abstract']) && $data['has_abstract'] 
            ? $this->abstractCacheTtl 
            : $this->cacheTtl;

        if (time() - filemtime($cacheFile) > $ttl) {
            @unlink($cacheFile);
            return null;
        }

        return $data;
    }

    /**
     * Read works from cache
     */
    private function readWorksFromCache(string $orcid, int $limit = 100): ?array
    {
        $cacheKey = 'orcid_works_' . substr(md5($orcid), 0, 8) . '_' . $orcid . '_' . $limit;
        $cacheFile = $this->cacheDir . '/' . $cacheKey . '.json.gz';
        
        if (!file_exists($cacheFile)) {
            // Try without limit suffix
            $cacheKey = 'orcid_works_' . substr(md5($orcid), 0, 8) . '_' . $orcid;
            $cacheFile = $this->cacheDir . '/' . $cacheKey . '.json.gz';
            
            if (!file_exists($cacheFile)) {
                // Try plain filename
                $cacheFile = $this->cacheDir . '/orcid_works_' . $orcid . '.json';
                if (!file_exists($cacheFile)) {
                    return null;
                }
            }
        }

        $content = @file_get_contents($cacheFile);
        if ($content === false) {
            return null;
        }

        // Try gzdecode first
        $decoded = @gzdecode($content);
        if ($decoded !== false) {
            $content = $decoded;
        }

        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data;
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
        if (!$forceRefresh) {
            $cached = $this->readProfileFromCache($cleanOrcid);
            if ($cached !== null) {
                // Extract profile data from cached response
                return $this->extractProfileFromCachedData($cached);
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

        $this->lastFetchedData = ['profile' => $profile, 'raw' => $data];

        return $profile;
    }

    /**
     * Extract profile from cached API data
     */
    private function extractProfileFromCachedData(array $cachedData): array
    {
        $basicInfo = $cachedData['basic_info'] ?? [];
        $activities = $cachedData['activities'] ?? [];
        
        // Support dua format nama: nested (name['given']) dan flat (given_names)
        $givenNames = '';
        $familyName = '';
        $fullName = '';
        
        if (isset($basicInfo['name'])) {
            // Format nested dari ORCID_Profile_API.php
            $givenNames = $basicInfo['name']['given'] ?? '';
            $familyName = $basicInfo['name']['family'] ?? '';
            $fullName = trim($givenNames . ' ' . $familyName);
        } else {
            // Format flat
            $givenNames = $basicInfo['given_names'] ?? '';
            $familyName = $basicInfo['family_name'] ?? '';
            $fullName = $basicInfo['full_name'] ?? trim($givenNames . ' ' . $familyName);
        }
        
        // Ekstrak external_ids dari berbagai format
        $externalIds = [];
        if (isset($basicInfo['external_ids'])) {
            // Format dari ORCID_Profile_API.php
            $externalIds = $basicInfo['external_ids'];
        } elseif (isset($basicInfo['external_urls'])) {
            // Format lama
            $externalIds = $basicInfo['external_urls'];
        }
        
        return [
            'name' => $fullName,
            'given_names' => $givenNames,
            'family_name' => $familyName,
            'keywords' => $basicInfo['keywords'] ?? [],
            'urls' => $externalIds,
            'biography' => $basicInfo['biography'] ?? '',
            'country' => $basicInfo['country'] ?? '',
            'status' => 'success',
            'orcid' => $cachedData['orcid'] ?? '',
            // Simpan juga raw data untuk ResearcherIdentityService
            'external_ids' => $externalIds,
        ];
    }

    /**
     * Get works/publications for an ORCID
     * 
     * @param string $orcid Researcher ORCID
     * @param int $limit Maximum number of works to return
     * @return array List of works
     */
    public function getWorks(string $orcid, int $limit = 100): array
    {
        $cleanOrcid = $this->cleanOrcid($orcid);

        if (!$this->isValidOrcid($cleanOrcid)) {
            throw new Exception('Invalid ORCID format: ' . $orcid);
        }

        // Check cache first
        $cachedWorks = $this->readWorksFromCache($cleanOrcid, $limit);
        if ($cachedWorks !== null && is_array($cachedWorks)) {
            return $this->extractWorksFromCachedData($cachedWorks, $limit);
        }

        // If no cached works, try to fetch from profile
        $profileData = $this->readProfileFromCache($cleanOrcid);
        if ($profileData !== null && isset($profileData['activities']['works'])) {
            return $this->extractWorksFromCachedData($profileData, $limit);
        }

        // No data available
        return [];
    }

    /**
     * Extract works from cached data
     */
    private function extractWorksFromCachedData(array $cachedData, int $limit = 100): array
    {
        $works = [];
        
        // Check if it's a full profile cache
        if (isset($cachedData['activities']['works'])) {
            $worksData = $cachedData['activities']['works'];
            if (is_array($worksData)) {
                foreach ($worksData as $work) {
                    if (count($works) >= $limit) {
                        break;
                    }
                    $works[] = $this->extractWorkItem($work);
                }
            }
        }
        
        // Check if it's a works-only cache
        if (isset($cachedData['works']) && is_array($cachedData['works'])) {
            $worksData = $cachedData['works'];
            foreach ($worksData as $work) {
                if (count($works) >= $limit) {
                    break;
                }
                $works[] = $this->extractWorkItem($work);
            }
        }

        return $works;
    }

    /**
     * Extract individual work item
     */
    private function extractWorkItem(array $workData): array
    {
        return [
            'title' => $workData['title'] ?? $workData['publication-title'] ?? 'Unknown Title',
            'year' => $workData['year'] ?? $workData['publication-date']['year'] ?? date('Y'),
            'doi' => $workData['doi'] ?? $workData['external-ids']['doi'] ?? '',
            'abstract' => $workData['abstract'] ?? '',
            'journal' => $workData['journal-title'] ?? '',
            'type' => $workData['type'] ?? 'work'
        ];
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

        // Ekstrak external IDs (termasuk Scopus Author ID)
        $externalIds = [];
        if (!empty($person['external-identifiers']['external-identifier'])) {
            foreach ($person['external-identifiers']['external-identifier'] as $extId) {
                $type = $extId['external-id-type'] ?? '';
                $value = $extId['external-id-value'] ?? '';
                $url = $extId['external-id-url']['value'] ?? '';
                
                if ($type && $value) {
                    $externalIds[] = [
                        'type' => $type,
                        'value' => $value,
                        'url' => $url,
                    ];
                }
            }
        }

        return [
            'given_names' => $name['given-names']['value'] ?? '',
            'family_name' => $name['family-name']['value'] ?? '',
            'full_name' => trim(($name['given-names']['value'] ?? '') . ' ' . ($name['family-name']['value'] ?? '')),
            'name' => trim(($name['given-names']['value'] ?? '') . ' ' . ($name['family-name']['value'] ?? '')),
            'credit_name' => $name['credit-name']['value'] ?? '',
            'biography' => $person['biography']['content'] ?? '',
            'country' => $person['addresses']['address'][0]['country']['name'] ?? '',
            'keywords' => $this->extractKeywords($person),
            'urls' => $this->extractExternalUrls($person),
            'external_urls' => $this->extractExternalUrls($person),
            'external_ids' => $externalIds,  // Tambahkan external_ids
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

    /**
     * Get last fetched raw data
     */
    public function getLastFetchedData(): ?array
    {
        return $this->lastFetchedData;
    }
}

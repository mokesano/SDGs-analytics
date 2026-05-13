<?php

declare(strict_types=1);

namespace Wizdam\Services;

use Exception;
use Wizdam\Utils\CacheManager;
use Wizdam\Utils\Validator;

/**
 * ORCID Service
 * 
 * Service untuk mengambil dan memproses data dari ORCID API
 * dengan caching dan error handling yang proper.
 * 
 * @version 1.0.0
 * @author Wizdam Team
 * @license MIT
 */
class OrcidService
{
    private string $apiBaseUrl;
    private CacheManager $cacheManager;
    private int $timeout;
    private string $userAgent;

    /**
     * Constructor
     */
    public function __construct(
        string $apiBaseUrl = 'https://pub.orcid.org/v3.0',
        ?CacheManager $cacheManager = null,
        int $timeout = 30,
        string $userAgent = 'Wizdam-SDG-Analyzer/1.0'
    ) {
        $this->apiBaseUrl = $apiBaseUrl;
        $this->cacheManager = $cacheManager ?? new CacheManager();
        $this->timeout = $timeout;
        $this->userAgent = $userAgent;
    }

    /**
     * Validate ORCID format
     */
    public function isValidOrcid(string $orcid): bool
    {
        return Validator::validateOrcid($orcid);
    }

    /**
     * Clean ORCID input (remove URL prefixes)
     */
    public function cleanOrcid(string $orcid): string
    {
        return str_replace(['https://orcid.org/', 'http://orcid.org/'], '', trim($orcid));
    }

    /**
     * Fetch researcher profile from ORCID
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
        $cacheFile = $this->cacheManager->getCacheFilename('orcid_profile', $cleanOrcid);
        if (!$forceRefresh && $this->cacheManager->has($cacheFile)) {
            $cached = $this->cacheManager->read($cacheFile);
            if ($cached !== false) {
                return $cached;
            }
        }

        // Fetch from API - get both person and works
        $profileUrl = $this->apiBaseUrl . '/' . $cleanOrcid;
        $data = $this->makeRequest($profileUrl);

        if ($data === null) {
            return null;
        }

        // Extract relevant profile information
        $profile = $this->extractProfileData($data);
        
        // Also fetch works separately
        $works = $this->getWorks($cleanOrcid, 100, $forceRefresh);
        $profile['works'] = $works ?? [];
        $profile['works_count'] = is_array($works) ? count($works) : 0;

        // Cache the result
        $this->cacheManager->write($cacheFile, $profile);

        return $profile;
    }

    /**
     * Fetch researcher works from ORCID
     * 
     * @param string $orcid Researcher ORCID
     * @param int $limit Maximum number of works to fetch
     * @param bool $forceRefresh Force refresh cache
     * @return array|null Works data or null on failure
     */
    public function getWorks(string $orcid, int $limit = 50, bool $forceRefresh = false): ?array
    {
        $cleanOrcid = $this->cleanOrcid($orcid);

        if (!$this->isValidOrcid($cleanOrcid)) {
            throw new Exception('Invalid ORCID format: ' . $orcid);
        }

        // Check cache first
        $cacheFile = $this->cacheManager->getCacheFilename('orcid_works', $cleanOrcid . '_' . $limit);
        if (!$forceRefresh && $this->cacheManager->has($cacheFile)) {
            $cached = $this->cacheManager->read($cacheFile);
            if ($cached !== false) {
                return $cached;
            }
        }

        // Fetch from API
        $worksUrl = $this->apiBaseUrl . '/' . $cleanOrcid . '/works';
        $params = [
            'limit' => $limit,
            'sort' => 'created-date:desc'
        ];

        $data = $this->makeRequest($worksUrl, $params);

        if ($data === null) {
            return null;
        }

        // Extract works information
        $works = $this->extractWorksData($data);

        // Cache the result
        $this->cacheManager->write($cacheFile, $works);

        return $works;
    }

    /**
     * Get complete researcher data (profile + works)
     * 
     * @param string $orcid Researcher ORCID
     * @param int $workLimit Maximum number of works to fetch
     * @param bool $forceRefresh Force refresh cache
     * @return array Complete researcher data
     */
    public function getResearcherData(string $orcid, int $workLimit = 50, bool $forceRefresh = false): array
    {
        $cleanOrcid = $this->cleanOrcid($orcid);

        $profile = $this->getProfile($cleanOrcid, $forceRefresh);
        $works = $this->getWorks($cleanOrcid, $workLimit, $forceRefresh);

        return [
            'orcid' => $cleanOrcid,
            'profile' => $profile,
            'works' => $works,
            'fetched_at' => date('Y-m-d H:i:s'),
            'work_count' => is_array($works) ? count($works) : 0,
        ];
    }

    /**
     * Make HTTP request to ORCID API
     */
    private function makeRequest(string $url, array $params = []): ?array
    {
        $fullUrl = $url;
        if (!empty($params)) {
            $fullUrl .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => $this->userAgent,
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
     * Extract relevant profile data from ORCID response
     */
    private function extractProfileData(array $data): array
    {
        $person = $data['person'] ?? [];
        $name = $person['name'] ?? [];

        return [
            'orcid' => $data['orcid-identifier']['path'] ?? '',
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
     * Extract works data from ORCID response
     */
    private function extractWorksData(array $data): array
    {
        $works = [];
        
        if (!isset($data['group'])) {
            return $works;
        }

        foreach ($data['group'] as $group) {
            $workSummary = $group['work-summary'][0] ?? null;
            if (!$workSummary) {
                continue;
            }

            $title = $workSummary['title']['title']['value'] ?? '';
            $putCode = $workSummary['put-code'] ?? '';
            
            // Try to get DOI
            $doi = '';
            if (isset($workSummary['external-ids']['external-id'])) {
                foreach ($workSummary['external-ids']['external-id'] as $extId) {
                    if (($extId['external-id-type'] ?? '') === 'doi') {
                        $doi = $extId['external-id-value'] ?? '';
                        break;
                    }
                }
            }

            // Get publication year
            $pubDate = $workSummary['publication-date'] ?? [];
            $year = $pubDate['year']['value'] ?? '';

            // Get journal title
            $journalTitle = '';
            if (isset($workSummary['journal-title'])) {
                $journalTitle = $workSummary['journal-title']['title'] ?? '';
            }

            $works[] = [
                'put_code' => $putCode,
                'title' => $title,
                'doi' => $doi,
                'year' => $year,
                'journal' => $journalTitle,
                'url' => $workSummary['url']['value'] ?? '',
            ];
        }

        return $works;
    }

    /**
     * Get work details by put-code
     * 
     * @param string $orcid Researcher ORCID
     * @param string $putCode Work put-code
     * @return array|null Work details or null on failure
     */
    public function getWorkDetails(string $orcid, string $putCode): ?array
    {
        $cleanOrcid = $this->cleanOrcid($orcid);

        if (!$this->isValidOrcid($cleanOrcid)) {
            throw new Exception('Invalid ORCID format: ' . $orcid);
        }

        // Check cache first
        $cacheFile = $this->cacheManager->getCacheFilename('orcid_work_detail', $cleanOrcid . '_' . $putCode);
        if ($this->cacheManager->has($cacheFile)) {
            $cached = $this->cacheManager->read($cacheFile);
            if ($cached !== false) {
                return $cached;
            }
        }

        // Fetch from API
        $workUrl = $this->apiBaseUrl . '/' . $cleanOrcid . '/work/' . $putCode;
        $data = $this->makeRequest($workUrl);

        if ($data === null) {
            return null;
        }

        // Extract detailed work information
        $details = $this->extractWorkDetails($data);

        // Cache the result
        $this->cacheManager->write($cacheFile, $details);

        return $details;
    }

    /**
     * Extract detailed work information
     */
    private function extractWorkDetails(array $data): array
    {
        $title = $data['title']['title']['value'] ?? '';
        $abstract = '';
        
        if (isset($data['short-description'])) {
            $abstract = $data['short-description'];
        }

        // Get authors/contributors
        $contributors = [];
        if (isset($data['contributors']['contributor'])) {
            foreach ($data['contributors']['contributor'] as $contrib) {
                $contributors[] = [
                    'name' => $contrib['contributor-name']['credited-name']['value'] ?? '',
                    'role' => $contrib['contributor-role']['role'] ?? '',
                ];
            }
        }

        return [
            'put_code' => $data['put-code'] ?? '',
            'title' => $title,
            'abstract' => $abstract,
            'contributors' => $contributors,
            'doi' => $this->extractDoiFromWork($data),
            'year' => $data['publication-date']['year']['value'] ?? '',
            'journal' => $data['journal-title']['title'] ?? '',
            'type' => $data['type'] ?? '',
            'url' => $data['url']['value'] ?? '',
        ];
    }

    /**
     * Extract DOI from work data
     */
    private function extractDoiFromWork(array $data): string
    {
        if (isset($data['external-ids']['external-id'])) {
            foreach ($data['external-ids']['external-id'] as $extId) {
                if (($extId['external-id-type'] ?? '') === 'doi') {
                    return $extId['external-id-value'] ?? '';
                }
            }
        }
        return '';
    }

    /**
     * Clear ORCID cache for specific researcher
     */
    public function clearCache(string $orcid): bool
    {
        $cleanOrcid = $this->cleanOrcid($orcid);
        
        // Clear all cache files related to this ORCID
        $patterns = [
            'orcid_profile_' . $cleanOrcid,
            'orcid_works_' . $cleanOrcid,
            'orcid_work_detail_' . $cleanOrcid,
        ];

        foreach ($patterns as $pattern) {
            $files = glob($this->cacheManager->getCacheFilename($pattern, '*'));
            if ($files !== false) {
                foreach ($files as $file) {
                    unlink($file);
                }
            }
        }

        return true;
    }
}

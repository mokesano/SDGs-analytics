<?php

declare(strict_types=1);

namespace Wizdam\Services;

use Exception;

/**
 * Scopus Researcher Service
 * 
 * Wrapper class untuk api/scopus.php dan api/researcher.php yang menyediakan interface OOP
 * untuk pencarian peneliti dan data sitasi dari Scopus tanpa mengubah file API asli.
 * 
 * @version 1.0.0
 * @author Wizdam Team
 * @license MIT
 */
class ScopusResearcherService
{
    private string $projectRoot;
    private string $apiKey;
    private string $cacheDir;
    private int $cacheTtl;

    /**
     * Constructor
     * 
     * @param string $projectRoot Root directory proyek
     * @param string $apiKey Scopus API key
     * @param int $cacheTTL Cache time-to-live dalam detik (default: 7 hari)
     */
    public function __construct(
        string $projectRoot = '',
        string $apiKey = '',
        int $cacheTTL = 604800
    ) {
        $this->projectRoot = $projectRoot ?: dirname(__DIR__, 2);
        $this->apiKey = $apiKey ?: '2b2a63a2cd69bd0cfd7acc07addc140f';
        $this->cacheTtl = $cacheTTL;
        $this->cacheDir = $this->projectRoot . '/api/cache';
        
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Search researcher by ORCID
     * 
     * @param string $orcid ORCID identifier
     * @param bool $forceRefresh Force refresh cache
     * @return array Researcher data or error
     * @throws Exception If researcher not found or API error
     */
    public function searchByOrcid(string $orcid, bool $forceRefresh = false): array
    {
        // Validate ORCID format
        if (!$this->isValidOrcid($orcid)) {
            throw new Exception('Invalid ORCID format. Expected: XXXX-XXXX-XXXX-XXXX');
        }

        // Check cache
        $cacheKey = 'scopus_researcher_' . preg_replace('/[^0-9X]/', '', $orcid);
        $cachedData = $this->getFromCache($cacheKey);
        
        if ($cachedData !== null && !$forceRefresh) {
            return $cachedData;
        }

        // Call API via include (legacy method wrapped)
        $result = $this->callResearcherApi($orcid);
        
        if (empty($result['status']) || $result['status'] !== 'success') {
            throw new Exception($result['message'] ?? 'Failed to fetch researcher data from Scopus');
        }

        // Cache result
        $this->saveToCache($cacheKey, $result);

        return $result;
    }

    /**
     * Get researcher publications with citation data
     * 
     * @param string $orcid ORCID identifier
     * @param int $limit Maximum number of publications
     * @param bool $forceRefresh Force refresh cache
     * @return array Publications with citation metrics
     */
    public function getPublications(string $orcid, int $limit = 20, bool $forceRefresh = false): array
    {
        $researcherData = $this->searchByOrcid($orcid, $forceRefresh);
        
        if (empty($researcherData['works'])) {
            return [];
        }

        $publications = array_slice($researcherData['works'], 0, $limit);
        
        // Enhance with citation data if available
        foreach ($publications as &$pub) {
            if (!empty($pub['doi'])) {
                $citationData = $this->getCitationCount($pub['doi']);
                $pub['citation_count'] = $citationData['count'] ?? 0;
                $pub['cited_by'] = $citationData['documents'] ?? [];
            }
        }

        return $publications;
    }

    /**
     * Get h-index and other metrics for researcher
     * 
     * @param string $orcid ORCID identifier
     * @param bool $forceRefresh Force refresh cache
     * @return array Metrics including h-index, i10-index, total citations
     */
    public function getMetrics(string $orcid, bool $forceRefresh = false): array
    {
        $researcherData = $this->searchByOrcid($orcid, $forceRefresh);
        
        $metrics = [
            'h_index' => $researcherData['h_index'] ?? null,
            'i10_index' => $researcherData['i10_index'] ?? null,
            'total_citations' => $researcherData['total_citations'] ?? null,
            'total_works' => $researcherData['total_works'] ?? count($researcherData['works'] ?? []),
            'orcid' => $orcid,
            'name' => $researcherData['name'] ?? null,
        ];

        // Calculate h-index if not provided
        if ($metrics['h_index'] === null && !empty($researcherData['works'])) {
            $metrics['h_index'] = $this->calculateHIndex($researcherData['works']);
        }

        return $metrics;
    }

    /**
     * Search journal by ISSN (delegate to ScopusJournalService)
     * 
     * @param string $issn ISSN in format XXXX-XXXX
     * @param bool $forceRefresh Force refresh cache
     * @return array Journal data
     */
    public function searchJournalByIssn(string $issn, bool $forceRefresh = false): array
    {
        $journalService = new ScopusJournalService($this->projectRoot . '/api/SCOPUS_Journal-Checker_API.php', $this->apiKey);
        return $journalService->searchByIssn($issn);
    }

    /**
     * Get citation count for a specific DOI
     * 
     * @param string $doi Document DOI
     * @return array Citation count and citing documents
     */
    public function getCitationCount(string $doi): array
    {
        $cacheKey = 'scopus_citation_' . md5($doi);
        $cachedData = $this->getFromCache($cacheKey);
        
        if ($cachedData !== null) {
            return $cachedData;
        }

        // Simulate citation lookup (in real implementation, call Scopus Citation API)
        $result = [
            'count' => 0,
            'documents' => [],
            'doi' => $doi,
        ];

        $this->saveToCache($cacheKey, $result);
        
        return $result;
    }

    /**
     * Validate ORCID format
     * 
     * @param string $orcid ORCID to validate
     * @return bool True if valid
     */
    public function isValidOrcid(string $orcid): bool
    {
        $clean = preg_replace('/[^0-9X]/', '', strtoupper($orcid));
        
        if (strlen($clean) !== 16) {
            return false;
        }

        // ORCID checksum validation (ISO 7064 MOD 11-2)
        $total = 0;
        for ($i = 0; $i < 15; $i++) {
            $digit = (int)$clean[$i];
            $total = ($total + $digit) * 2;
        }
        
        $remainder = $total % 11;
        $checkDigit = (12 - $remainder) % 11;
        
        $lastChar = $clean[15];
        $expectedCheck = ($checkDigit === 10) ? 'X' : (string)$checkDigit;

        return $lastChar === $expectedCheck;
    }

    /**
     * Format ORCID to standard XXXX-XXXX-XXXX-XXXX format
     * 
     * @param string $orcid Raw ORCID
     * @return string Formatted ORCID
     */
    public function formatOrcid(string $orcid): string
    {
        $clean = preg_replace('/[^0-9X]/', '', strtoupper($orcid));
        
        if (strlen($clean) !== 16) {
            return $orcid;
        }

        return substr($clean, 0, 4) . '-' . 
               substr($clean, 4, 4) . '-' . 
               substr($clean, 8, 4) . '-' . 
               substr($clean, 12, 4);
    }

    /**
     * Calculate h-index from publications
     * 
     * @param array $works Array of works with citation_count
     * @return int h-index value
     */
    private function calculateHIndex(array $works): int
    {
        $citations = [];
        
        foreach ($works as $work) {
            $count = $work['citation_count'] ?? 0;
            $citations[] = $count;
        }

        rsort($citations);
        
        $hIndex = 0;
        foreach ($citations as $i => $count) {
            if ($count >= $i + 1) {
                $hIndex = $i + 1;
            } else {
                break;
            }
        }

        return $hIndex;
    }

    /**
     * Call legacy researcher API
     * 
     * @param string $orcid ORCID identifier
     * @return array API response
     */
    private function callResearcherApi(string $orcid): array
    {
        // Simulate API call by including the file and capturing output
        // In production, this would be a proper HTTP request or direct method call
        
        ob_start();
        
        // Set up environment for API
        $_GET['orcid'] = $orcid;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        $apiFile = $this->projectRoot . '/api/researcher.php';
        
        if (file_exists($apiFile)) {
            // Capture and decode JSON output
            include $apiFile;
            $output = ob_get_clean();
            return json_decode($output, true) ?? ['status' => 'error', 'message' => 'Invalid JSON response'];
        }

        ob_end_clean();
        
        return ['status' => 'error', 'message' => 'API file not found'];
    }

    /**
     * Get data from cache
     * 
     * @param string $key Cache key
     * @return array|null Cached data or null if expired/not found
     */
    private function getFromCache(string $key): ?array
    {
        $cacheFile = $this->cacheDir . '/' . $key . '.json.gz';
        
        if (!file_exists($cacheFile)) {
            return null;
        }

        // Check TTL
        if (time() - filemtime($cacheFile) > $this->cacheTtl) {
            unlink($cacheFile);
            return null;
        }

        $content = file_get_contents($cacheFile);
        $decoded = gzdecode($content);
        
        if ($decoded === false) {
            return null;
        }

        return json_decode($decoded, true);
    }

    /**
     * Save data to cache
     * 
     * @param string $key Cache key
     * @param array $data Data to cache
     * @return bool Success status
     */
    private function saveToCache(string $key, array $data): bool
    {
        $cacheFile = $this->cacheDir . '/' . $key . '.json.gz';
        
        $encoded = gzencode(json_encode($data), 6);
        
        if ($encoded === false) {
            return false;
        }

        return file_put_contents($cacheFile, $encoded, LOCK_EX) !== false;
    }

    /**
     * Clear all cache
     * 
     * @return int Number of files deleted
     */
    public function clearCache(): int
    {
        $count = 0;
        
        if (!is_dir($this->cacheDir)) {
            return 0;
        }

        $files = glob($this->cacheDir . '/scopus_*.json.gz');
        
        foreach ($files as $file) {
            if (unlink($file)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get API key
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Set API key
     */
    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }
}

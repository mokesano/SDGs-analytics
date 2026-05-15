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
     * @param string|null $apiKey Scopus API key (optional, will use default if not provided)
     * @param string $projectRoot Root directory proyek
     * @param int $cacheTTL Cache time-to-live dalam detik (default: 7 hari)
     */
    public function __construct(
        ?string $apiKey = null,
        string $projectRoot = '',
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
     * Get researcher publications with citation data (by ORCID)
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

        // Enrich with Scopus citation count only when not already set accurately
        foreach ($publications as &$pub) {
            if (!empty($pub['doi']) && empty($pub['citation_count'])) {
                $citationData = $this->getCitationCount($pub['doi']);
                if ($citationData['count'] > 0) {
                    $pub['citation_count'] = $citationData['count'];
                    $pub['cited_by']       = $citationData['documents'] ?? [];
                }
            }
        }
        unset($pub);

        return $publications;
    }

    /**
     * Get publications by Scopus Author ID via Scopus Search API
     *
     * @param string $scopusId Scopus Author ID (10-11 digits)
     * @param int $limit Maximum publications to return
     * @param bool $forceRefresh Force cache bypass
     * @return array Publications with title, doi, year, journal, citation_count
     */
    public function getPublicationsByScopusId(string $scopusId, int $limit = 25, bool $forceRefresh = false): array
    {
        if (!preg_match('/^\d{10,11}$/', $scopusId)) {
            return [];
        }

        $cacheKey = 'scopus_pubs_' . $scopusId . '_' . $limit;
        $cached   = $this->getFromCache($cacheKey);
        if ($cached !== null && !$forceRefresh) {
            return $cached;
        }

        $url = 'https://api.elsevier.com/content/search/scopus?' . http_build_query([
            'query'  => 'AU-ID(' . $scopusId . ')',
            'count'  => min($limit, 200),
            'start'  => 0,
            'field'  => 'dc:title,prism:doi,prism:coverDate,prism:publicationName,citedby-count,dc:description,prism:volume,prism:issueIdentifier',
            'sort'   => '-coverDate',
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'X-ELS-APIKey: ' . $this->apiKey,
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr || $httpCode !== 200) {
            error_log("[ScopusResearcherService] Search API error for AU-ID($scopusId): HTTP $httpCode $curlErr");
            return [];
        }

        $data    = json_decode($response, true);
        $entries = $data['search-results']['entry'] ?? [];
        if (!is_array($entries)) {
            return [];
        }

        $publications = [];
        foreach ($entries as $entry) {
            $coverDate = $entry['prism:coverDate'] ?? '';
            $year      = $coverDate ? (int)substr($coverDate, 0, 4) : null;

            $publications[] = [
                'title'          => $entry['dc:title']                ?? 'Unknown Title',
                'doi'            => $entry['prism:doi']               ?? null,
                'year'           => $year,
                'journal'        => $entry['prism:publicationName']   ?? null,
                'citation_count' => (int)($entry['citedby-count']     ?? 0),
                'abstract'       => $entry['dc:description']          ?? null,
                'volume'         => $entry['prism:volume']            ?? null,
                'issue'          => $entry['prism:issueIdentifier']   ?? null,
                'source'         => 'Scopus',
            ];
        }

        $this->saveToCache($cacheKey, $publications);
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
     * Get complete researcher profile by Scopus Author ID
     * This is a wrapper method compatible with ResearcherAggregatorService
     * 
     * @param string $scopusId Scopus Author ID
     * @param bool $forceRefresh Force refresh cache
     * @return array Complete profile with metrics and publications
     */
    public function getResearcherProfile(string $scopusId, bool $forceRefresh = false): array
    {
        // Validate Scopus ID format (10-11 digits)
        if (!preg_match('/^\d{10,11}$/', $scopusId)) {
            return ['error' => 'Invalid Scopus Author ID format'];
        }

        // Check cache
        $cacheKey = 'scopus_author_' . $scopusId;
        $cachedData = $this->getFromCache($cacheKey);
        
        if ($cachedData !== null && !$forceRefresh) {
            return $cachedData;
        }

        // Call Scopus API directly using author ID
        $result = $this->callScopusAuthorApi($scopusId);
        
        // Handle API errors gracefully - return partial data or fallback
        if (empty($result) || isset($result['error'])) {
            // If API fails, return basic info without metrics
            error_log("Scopus API Error for ID $scopusId: " . ($result['error'] ?? 'Unknown error'));
            
            // Return structure with minimal data to prevent breaking the flow
            return [
                'author_id' => $scopusId,
                'name' => 'Unknown (API Error)',
                'metrics' => [
                    'h_index' => null,
                    'cited_by_count' => null,
                    'document_count' => null,
                ],
                'affiliation' => null,
                'subject_areas' => [],
                'publications' => [],
                'api_error' => $result['error'] ?? 'Failed to fetch data from Scopus API',
            ];
        }

        // Build profile structure
        $profile = [
            'author_id' => $scopusId,
            'name' => $result['preferred_name'] ?? 'Unknown',
            'metrics' => [
                'h_index' => $result['h_index'] ?? 0,
                'cited_by_count' => $result['cited_by_count'] ?? 0,
                'document_count' => $result['document_count'] ?? 0,
            ],
            'affiliation' => $result['affiliation'] ?? null,
            'subject_areas' => $result['subject_areas'] ?? [],
            'publications' => $result['documents'] ?? [],
        ];

        // Cache result
        $this->saveToCache($cacheKey, $profile);

        return $profile;
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
     * Get citation count for a specific DOI via Scopus Abstract/Citation API
     *
     * @param string $doi Document DOI
     * @return array ['count' => int, 'documents' => [], 'doi' => string]
     */
    public function getCitationCount(string $doi): array
    {
        $cacheKey   = 'scopus_citation_' . md5($doi);
        $cachedData = $this->getFromCache($cacheKey);
        if ($cachedData !== null) {
            return $cachedData;
        }

        $cleanDoi = ltrim($doi, '/');
        $url      = 'https://api.elsevier.com/content/abstract/doi/' . rawurlencode($cleanDoi)
                  . '?field=citedby-count';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'X-ELS-APIKey: ' . $this->apiKey,
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        $count = 0;
        if (!$curlErr && $httpCode === 200) {
            $data  = json_decode($response, true);
            $count = (int)(
                $data['abstracts-retrieval-response']['coredata']['citedby-count']
                ?? $data['abstracts-retrieval-response']['item']['bibrecord']['head']['citation-info']['citationnumber']['$']
                ?? 0
            );
        } else {
            error_log("[ScopusResearcherService] Citation API error for DOI $doi: HTTP $httpCode $curlErr");
        }

        $result = ['count' => $count, 'documents' => [], 'doi' => $doi];
        // Only cache successful hits to avoid persisting API failures
        if (!$curlErr && $httpCode === 200) {
            $this->saveToCache($cacheKey, $result);
        }

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
     * Call legacy researcher API via output-buffered include
     *
     * @param string $orcid ORCID identifier (already validated)
     * @return array API response
     */
    private function callResearcherApi(string $orcid): array
    {
        $apiFile = $this->projectRoot . '/api/researcher.php';

        if (!file_exists($apiFile)) {
            return ['status' => 'error', 'message' => 'API file not found'];
        }

        $origGet    = $_GET;
        $origMethod = $_SERVER['REQUEST_METHOD'];

        $_GET['orcid']              = $orcid;
        $_SERVER['REQUEST_METHOD']  = 'GET';

        ob_start();
        try {
            include $apiFile;
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            $_GET                       = $origGet;
            $_SERVER['REQUEST_METHOD']  = $origMethod;
            error_log('[ScopusResearcherService] callResearcherApi include error: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'API include error: ' . $e->getMessage()];
        } finally {
            $_GET                       = $origGet;
            $_SERVER['REQUEST_METHOD']  = $origMethod;
        }

        $decoded = json_decode($output, true);
        return $decoded ?? ['status' => 'error', 'message' => 'Invalid JSON from researcher API'];
    }

    /**
     * Call Scopus Author API directly using Author ID
     * 
     * @param string $scopusId Scopus Author ID
     * @return array Author profile data
     */
    private function callScopusAuthorApi(string $scopusId): array
    {
        $url = 'https://api.elsevier.com/content/author/author_id/' . $scopusId;
        $headers = [
            'Accept: application/json',
            'X-ELS-APIKey: ' . $this->apiKey,
            'X-ELS-ResourceVersion: FULL', // Required for full author data
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => 'CURL error: ' . $error];
        }

        if ($httpCode !== 200) {
            $errorMsg = "HTTP error: $httpCode";
            // Try to parse error message from response
            $data = json_decode($response, true);
            if ($data && isset($data['service-error']['status']['statusText'])) {
                $errorMsg .= " - " . $data['service-error']['status']['statusText'];
            }
            return ['error' => $errorMsg, 'http_code' => $httpCode, 'response' => $response];
        }

        $data = json_decode($response, true);
        
        if (!$data || !isset($data['author-retrieval-response'])) {
            return ['error' => 'Invalid response format from Scopus API'];
        }

        $authorData = $data['author-retrieval-response'];
        
        // Handle array response (sometimes API returns array with single item)
        if (is_array($authorData) && isset($authorData[0])) {
            $authorData = $authorData[0];
        }

        // Extract relevant information
        $coredata = $authorData['coredata'] ?? [];
        $preferredName = $authorData['preferred-name'] ?? $coredata['preferred-name'] ?? [];
        
        // Scopus Author API nests current affiliation under affiliation-current.affiliation-name
        // OR affiliation-current.affiliation[0].affiliation-name (multiple affiliations)
        $affiliationBlock = $authorData['affiliation-current'] ?? [];
        $affiliationName  = $affiliationBlock['affiliation-name']
            ?? $affiliationBlock['affiliation']['affiliation-name']
            ?? (is_array($affiliationBlock['affiliation'] ?? null)
                ? ($affiliationBlock['affiliation'][0]['affiliation-name'] ?? null)
                : null);

        $profile = [
            'preferred_name' => trim(($preferredName['given-name'] ?? '') . ' ' . ($preferredName['surname'] ?? $coredata['surname'] ?? '')),
            'h_index'        => (int)($authorData['h-index'] ?? $coredata['h-index'] ?? 0),
            'cited_by_count' => (int)($coredata['citation-count'] ?? $coredata['citedby-count'] ?? 0),
            'document_count' => (int)($coredata['document-count'] ?? 0),
            'affiliation'    => $affiliationName,
            'subject_areas'  => [],
            'documents'      => [],
        ];

        // Extract subject areas
        if (!empty($authorData['subject-areas']['subject-area'])) {
            $areas = $authorData['subject-areas']['subject-area'];
            if (!is_array($areas) || !isset($areas[0])) {
                $areas = [$areas];
            }
            foreach ($areas as $area) {
                $profile['subject_areas'][] = $area['$'] ?? $area;
            }
        }

        return $profile;
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

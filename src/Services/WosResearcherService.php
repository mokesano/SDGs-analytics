<?php

declare(strict_types=1);

namespace Wizdam\Services;

/**
 * Web of Science Researcher Service
 * 
 * Mengambil data peneliti dari Web of Science API menggunakan ResearcherID,
 * termasuk metrik (h-index, sitasi), daftar publikasi, dan informasi lainnya.
 * 
 * @package Wizdam\Services
 * @author Wizdam Team
 * @version 1.0.0
 */
class WosResearcherService
{
    private const WOS_API_BASE_URL = 'https://wos-api.clarivate.com/api/wos';
    private const RESEARCHERID_BASE_URL = 'https://www.researcherid.com/rid';
    
    private ?string $apiKey;
    private string $cacheDir;
    private int $cacheTtl;
    private \Wizdam\Utils\CacheManager $cacheManager;

    /**
     * Constructor
     * 
     * @param string|null $apiKey Web of Science API Key (opsional)
     * @param string $cacheDir Direktori cache
     * @param int $cacheTtl TTL cache dalam detik (default: 7 hari)
     */
    public function __construct(
        ?string $apiKey = null,
        string $cacheDir = __DIR__ . '/../../api/cache',
        int $cacheTtl = 604800
    ) {
        $this->apiKey = $apiKey ?? getenv('WOS_API_KEY') ?: null;
        $this->cacheDir = $cacheDir;
        $this->cacheTtl = $cacheTtl;
        $this->cacheManager = new \Wizdam\Utils\CacheManager($cacheDir);
        
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Dapatkan profil peneliti dari ResearcherID
     * Metode ini melakukan scraping dari researcherid.com jika API key tidak tersedia
     * 
     * @param string $researcherId ResearcherID (format: A-1234-2008)
     * @return array|null Data profil peneliti atau null jika gagal
     */
    public function getResearcherProfile(string $researcherId): ?array
    {
        // Validasi format ResearcherID
        if (!$this->isValidResearcherId($researcherId)) {
            error_log("Invalid ResearcherID format: {$researcherId}");
            return null;
        }

        $cacheKey = "wos_researcher_{$researcherId}";
        $cachedData = $this->cacheManager->get($cacheKey);
        
        if ($cachedData !== null) {
            return $cachedData;
        }

        // Jika API key tersedia, gunakan API resmi
        if ($this->apiKey) {
            $profileData = $this->fetchFromApi($researcherId);
        } else {
            // Fallback: scraping dari researcherid.com
            $profileData = $this->fetchFromResearcherIdCom($researcherId);
        }
        
        if ($profileData) {
            $this->cacheManager->set($cacheKey, $profileData, $this->cacheTtl);
        }
        
        return $profileData;
    }

    /**
     * Dapatkan daftar publikasi peneliti dari Web of Science
     * 
     * @param string $researcherId ResearcherID
     * @param int $limit Jumlah maksimum publikasi (default: 25)
     * @return array Daftar publikasi
     */
    public function getPublications(string $researcherId, int $limit = 25): array
    {
        if (!$this->isValidResearcherId($researcherId)) {
            return [];
        }

        $cacheKey = "wos_pubs_{$researcherId}_{$limit}";
        $cachedData = $this->cacheManager->get($cacheKey);
        
        if ($cachedData !== null) {
            return $cachedData;
        }

        $publications = [];
        
        if ($this->apiKey) {
            $publications = $this->fetchPublicationsFromApi($researcherId, $limit);
        } else {
            $publications = $this->fetchPublicationsFromResearcherIdCom($researcherId, $limit);
        }
        
        $this->cacheManager->set($cacheKey, $publications, $this->cacheTtl);
        
        return $publications;
    }

    /**
     * Fetch data dari Web of Science API resmi
     * 
     * @param string $researcherId ResearcherID
     * @return array|null Data profil
     */
    private function fetchFromApi(string $researcherId): ?array
    {
        $url = self::WOS_API_BASE_URL . '/researchers/' . urlencode($researcherId);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'X-ApiKey: ' . $this->apiKey,
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false) {
            error_log("WOS API Error: HTTP {$httpCode}");
            return null;
        }

        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $this->parseApiProfile($data);
    }

    /**
     * Fetch data dengan scraping dari researcherid.com
     * 
     * @param string $researcherId ResearcherID
     * @return array|null Data profil
     */
    private function fetchFromResearcherIdCom(string $researcherId): ?array
    {
        $url = self::RESEARCHERID_BASE_URL . '/' . urlencode($researcherId);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);

        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $html === false) {
            error_log("ResearcherID.com Error: HTTP {$httpCode}");
            return null;
        }

        return $this->parseResearcherIdComHtml($html, $researcherId);
    }

    /**
     * Parse HTML dari researcherid.com
     * 
     * @param string $html HTML content
     * @param string $researcherId ResearcherID
     * @return array Data profil yang diparse
     */
    private function parseResearcherIdComHtml(string $html, string $researcherId): array
    {
        $profile = [
            'researcher_id' => $researcherId,
            'name' => null,
            'affiliation' => null,
            'country' => null,
            'metrics' => [
                'total_publications' => 0,
                'total_citations' => 0,
                'h_index' => 0,
                'i10_index' => 0,
            ],
            'fields' => [],
            'urls' => [
                'researcherid_url' => self::RESEARCHERID_BASE_URL . '/' . $researcherId,
            ],
        ];

        // Ekstrak nama (menggunakan regex sederhana)
        if (preg_match('/<title[^>]*>([^<]+)<\/title>/', $html, $matches)) {
            $title = trim($matches[1]);
            if (strpos($title, '|') !== false) {
                $profile['name'] = trim(explode('|', $title)[0]);
            }
        }

        // Ekstrak afiliasi
        if (preg_match('/affiliation["\']?\s*[:\s]\s*["\']?([^<",]+)/i', $html, $matches)) {
            $profile['affiliation'] = trim($matches[1]);
        }

        // Ekstrak metrik
        if (preg_match('/Publications["\']?\s*[:\s]\s*(\d+)/i', $html, $matches)) {
            $profile['metrics']['total_publications'] = (int) $matches[1];
        }

        if (preg_match('/Citations["\']?\s*[:\s]\s*(\d+)/i', $html, $matches)) {
            $profile['metrics']['total_citations'] = (int) $matches[1];
        }

        if (preg_match('/H-index["\']?\s*[:\s]\s*(\d+)/i', $html, $matches)) {
            $profile['metrics']['h_index'] = (int) $matches[1];
        }

        // Ekstrak fields of study
        if (preg_match_all('/Fields of Study[^>]*>\s*([^<]+)/i', $html, $matches)) {
            $profile['fields'] = array_map('trim', $matches[1]);
        }

        return $profile;
    }

    /**
     * Parse response dari WOS API
     * 
     * @param array $data Response dari API
     * @return array Data profil yang diparse
     */
    private function parseApiProfile(array $data): array
    {
        $researcher = $data['researcher'] ?? [];

        return [
            'researcher_id' => $researcher['researcherID'] ?? null,
            'orcid' => $researcher['orcid'] ?? null,
            'name' => [
                'full_name' => $researcher['fullName'] ?? null,
                'given_name' => $researcher['givenName'] ?? null,
                'family_name' => $researcher['familyName'] ?? null,
            ],
            'affiliation' => $researcher['affiliation'] ?? null,
            'country' => $researcher['country'] ?? null,
            'metrics' => [
                'total_publications' => (int) ($researcher['publicationCount'] ?? 0),
                'total_citations' => (int) ($researcher['citationCount'] ?? 0),
                'h_index' => (int) ($researcher['hIndex'] ?? 0),
                'i10_index' => (int) ($researcher['i10Index'] ?? 0),
            ],
            'fields' => $researcher['researchAreas'] ?? [],
            'urls' => [
                'researcherid_url' => self::RESEARCHERID_BASE_URL . '/' . ($researcher['researcherID'] ?? ''),
                'wos_url' => $researcher['webOfScienceUrl'] ?? null,
            ],
            'raw_data' => $researcher,
        ];
    }

    /**
     * Fetch publikasi dari WOS API
     * 
     * @param string $researcherId ResearcherID
     * @param int $limit Jumlah publikasi
     * @return array Daftar publikasi
     */
    private function fetchPublicationsFromApi(string $researcherId, int $limit): array
    {
        $url = self::WOS_API_BASE_URL . '/researchers/' . urlencode($researcherId) . '/publications';
        $params = ['limit' => $limit];
        
        $fullUrl = $url . '?' . http_build_query($params);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'X-ApiKey: ' . $this->apiKey,
            ],
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            return [];
        }

        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return $this->parseApiPublications($data);
    }

    /**
     * Fetch publikasi dari researcherid.com
     * 
     * @param string $researcherId ResearcherID
     * @param int $limit Jumlah publikasi
     * @return array Daftar publikasi
     */
    private function fetchPublicationsFromResearcherIdCom(string $researcherId, int $limit): array
    {
        $url = self::RESEARCHERID_BASE_URL . '/' . urlencode($researcherId) . '/publications';
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);

        $html = curl_exec($ch);
        curl_close($ch);

        if ($html === false) {
            return [];
        }

        return $this->parsePublicationsFromHtml($html, $limit);
    }

    /**
     * Parse publikasi dari HTML
     * 
     * @param string $html HTML content
     * @param int $limit Jumlah publikasi
     * @return array Daftar publikasi
     */
    private function parsePublicationsFromHtml(string $html, int $limit): array
    {
        $publications = [];
        
        // Pattern sederhana untuk ekstraksi publikasi
        preg_match_all('/<div class="publication"[^>]*>(.*?)<\/div>/s', $html, $pubBlocks);
        
        foreach (array_slice($pubBlocks[1], 0, $limit) as $block) {
            $pub = [
                'title' => null,
                'year' => null,
                'journal' => null,
                'doi' => null,
                'citations' => 0,
            ];

            if (preg_match('/<h3[^>]*>([^<]+)<\/h3>/', $block, $matches)) {
                $pub['title'] = trim(strip_tags($matches[1]));
            }

            if (preg_match('/(\d{4})/', $block, $matches)) {
                $pub['year'] = $matches[1];
            }

            if (preg_match('/DOI:\s*([^\s<]+)/i', $block, $matches)) {
                $pub['doi'] = $matches[1];
            }

            if (preg_match('/Cited by (\d+)/i', $block, $matches)) {
                $pub['citations'] = (int) $matches[1];
            }

            $publications[] = $pub;
        }

        return $publications;
    }

    /**
     * Parse publikasi dari API response
     * 
     * @param array $data Response dari API
     * @return array Daftar publikasi
     */
    private function parseApiPublications(array $data): array
    {
        $publications = [];
        $items = $data['publications'] ?? [];

        foreach ($items as $item) {
            $publications[] = [
                'title' => $item['title'] ?? null,
                'year' => $item['publicationYear'] ?? null,
                'journal' => $item['sourceTitle'] ?? null,
                'doi' => $item['doi'] ?? null,
                'issn' => $item['issn'] ?? null,
                'volume' => $item['volume'] ?? null,
                'issue' => $item['issue'] ?? null,
                'pages' => $item['pages'] ?? null,
                'citations' => (int) ($item['citationCount'] ?? 0),
                'authors' => $item['authors'] ?? [],
                'type' => $item['documentType'] ?? null,
                'fields' => $item['researchAreas'] ?? [],
            ];
        }

        return $publications;
    }

    /**
     * Validasi format ResearcherID
     * Format: 1-2 huruf kapital + dash + 4 digit + dash + 4 digit
     * Contoh: A-1234-2008, AB-5678-2010
     * 
     * @param string $researcherId ResearcherID
     * @return bool True jika valid
     */
    public function isValidResearcherId(string $researcherId): bool
    {
        return preg_match('/^[A-Z]{1,2}-\d{4}-\d{4}$/', $researcherId) === 1;
    }

    /**
     * Cari peneliti berdasarkan nama di Web of Science
     * 
     * @param string $query Nama peneliti
     * @param string $affiliation Afiliasi (opsional)
     * @param int $limit Jumlah hasil
     * @return array Daftar peneliti yang ditemukan
     */
    public function searchResearchers(string $query, ?string $affiliation = null, int $limit = 10): array
    {
        // Implementasi pencarian - bisa ditambahkan sesuai kebutuhan
        // Saat ini hanya placeholder karena memerlukan API key
        return [];
    }

    /**
     * Set API key
     * 
     * @param string $apiKey Web of Science API Key
     */
    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Clear cache untuk peneliti tertentu
     * 
     * @param string $researcherId ResearcherID
     */
    public function clearCache(string $researcherId): void
    {
        $pattern = "*wos*{$researcherId}*";
        $this->cacheManager->clearByPattern($pattern);
    }
}

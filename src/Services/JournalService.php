<?php

declare(strict_types=1);

namespace Wizdam\Services;

use Exception;
use Wizdam\Utils\CacheManager;
use Wizdam\Utils\Validator;

/**
 * Journal Service
 * 
 * Wrapper untuk journal.php dan SCOPUS_Journal-Checker_API.php
 * yang menyediakan interface OOP untuk operasi jurnal.
 *
 * @version 1.0.0
 * @author Wizdam Team
 * @license MIT
 */
class JournalService
{
    private ScopusJournalService $scopusService;
    private CacheManager $cacheManager;
    private string $cacheDir;
    private int $cacheTtl;

    /**
     * Constructor
     */
    public function __construct(
        string $scopusApiFilePath = '',
        string $cacheDir = '',
        int $cacheTtl = 604800, // 7 days
        string $apiKey = ''
    ) {
        $this->scopusService = new ScopusJournalService($scopusApiFilePath, $apiKey);
        
        // Use provided cacheDir or default to project root /cache
        if ($cacheDir === '') {
            $projectRoot = dirname(__DIR__, 2); // Go up from src/Services to project root
            $cacheDir = $projectRoot . '/cache';
        }
        
        $this->cacheDir = $cacheDir;
        $this->cacheTtl = $cacheTtl;
        
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
        
        $this->cacheManager = new CacheManager($this->cacheDir, true, $this->cacheTtl);
    }

    /**
     * Get journal by ISSN with caching
     *
     * @param string $issn
     * @param bool $forceRefresh
     * @return array{status: string, issn?: string, title?: string, publisher?: string, ...}
     * @throws Exception
     */
    public function getByIssn(string $issn, bool $forceRefresh = false): array
    {
        // Validate and format ISSN
        if (!Validator::validateIssn($issn)) {
            return [
                'status' => 'error',
                'message' => 'Format ISSN tidak valid. Gunakan format: XXXX-XXXX (8 digit)'
            ];
        }

        $formattedIssn = Validator::formatIssn($issn);
        $cleanIssn = str_replace('-', '', strtoupper($issn));
        $cacheKey = 'journal_' . $cleanIssn;

        // Try to get from cache
        if (!$forceRefresh) {
            $cached = $this->cacheManager->get($cacheKey);
            if ($cached !== null && is_array($cached)) {
                return $cached;
            }
        }

        // Fetch from Scopus API via wrapper
        $result = $this->scopusService->searchByIssn($formattedIssn);

        if (empty($result['success'])) {
            return [
                'status' => 'error',
                'message' => $result['error'] ?? 'Jurnal tidak ditemukan di Scopus.'
            ];
        }

        // Map subjects to SDGs
        $subjects = $result['subject_areas'] ?? [];
        $sdgCodes = $this->mapSubjectsToSdgs($subjects);

        // Build response
        $responseData = [
            'status' => 'success',
            'issn' => $formattedIssn,
            'eissn' => $result['eissn'] ?? null,
            'title' => $result['name'] ?? null,
            'publisher' => $result['publisher'] ?? null,
            'scopus_id' => $result['scopus_id'] ?? null,
            'sjr' => $result['sjr'] ?? null,
            'h_index' => $result['h_index'] ?? null,
            'citescore' => $result['citescore'] ?? null,
            'snip' => $result['snip'] ?? null,
            'quartile' => $result['quartile'] ?? null,
            'open_access' => $result['open_access'] ?? false,
            'country' => $result['country'] ?? null,
            'discontinued' => $result['discontinued'] ?? false,
            'subject_areas' => $subjects,
            'sdg_codes' => $sdgCodes,
            'last_fetched' => date('Y-m-d H:i:s'),
            'source' => 'Scopus'
        ];

        // Save to cache
        $this->cacheManager->set($cacheKey, $responseData);

        return $responseData;
    }

    /**
     * Map subject areas to SDG codes
     * Based on sdg_subject_mapping.php logic
     *
     * @param array $subjects
     * @return array
     */
    private function mapSubjectsToSdgs(array $subjects): array
    {
        // Load subject mapping
        $projectRoot = dirname(__DIR__, 2); // Go up from src/Services to project root
        $mappingFile = $projectRoot . '/includes/sdg_subject_mapping.php';
        $subjectMapping = [];
        
        if (file_exists($mappingFile)) {
            $subjectMapping = include $mappingFile;
        }

        // Default mapping if file not found
        if (empty($subjectMapping)) {
            $subjectMapping = $this->getDefaultSubjectMapping();
        }

        $sdgCodes = [];
        $subjectNames = array_column($subjects, 'name');

        foreach ($subjectNames as $subject) {
            $subjectLower = strtolower($subject);
            
            foreach ($subjectMapping as $sdgCode => $keywords) {
                foreach ($keywords as $keyword) {
                    if (strpos($subjectLower, strtolower($keyword)) !== false) {
                        if (!in_array($sdgCode, $sdgCodes)) {
                            $sdgCodes[] = $sdgCode;
                        }
                    }
                }
            }
        }

        sort($sdgCodes);
        return $sdgCodes;
    }

    /**
     * Get default subject to SDG mapping
     *
     * @return array
     */
    private function getDefaultSubjectMapping(): array
    {
        return [
            'SDG1' => ['poverty', 'social welfare', 'economic development'],
            'SDG2' => ['agriculture', 'food', 'nutrition', 'farming'],
            'SDG3' => ['health', 'medicine', 'disease', 'public health', 'clinical'],
            'SDG4' => ['education', 'learning', 'teaching', 'pedagogy'],
            'SDG5' => ['gender', 'women', 'equality', 'empowerment'],
            'SDG6' => ['water', 'sanitation', 'hygiene', 'wastewater'],
            'SDG7' => ['energy', 'renewable', 'power', 'electricity'],
            'SDG8' => ['economic', 'employment', 'labor', 'work', 'business'],
            'SDG9' => ['technology', 'innovation', 'infrastructure', 'industry'],
            'SDG10' => ['inequality', 'social inclusion', 'migration'],
            'SDG11' => ['urban', 'city', 'sustainable', 'housing', 'transport'],
            'SDG12' => ['consumption', 'production', 'waste', 'recycling'],
            'SDG13' => ['climate', 'carbon', 'emission', 'environmental'],
            'SDG14' => ['marine', 'ocean', 'fisheries', 'aquatic'],
            'SDG15' => ['biodiversity', 'ecosystem', 'forest', 'wildlife', 'conservation'],
            'SDG16' => ['governance', 'justice', 'peace', 'institution', 'law'],
            'SDG17' => ['partnership', 'collaboration', 'development', 'international']
        ];
    }

    /**
     * Search journals by keyword
     *
     * @param string $keyword
     * @param int $limit
     * @return array
     * @throws Exception
     */
    public function searchByKeyword(string $keyword, int $limit = 20): array
    {
        // This would typically call an external search API
        // For now, return empty result or implement based on available APIs
        return [
            'status' => 'success',
            'results' => [],
            'total' => 0,
            'keyword' => $keyword
        ];
    }

    /**
     * Get journal statistics
     *
     * @return array
     * @throws Exception
     */
    public function getStatistics(): array
    {
        // Count cached journals
        $cacheFiles = glob($this->cacheDir . '/journal_*.json.gz');
        $count = count($cacheFiles);

        return [
            'status' => 'success',
            'cached_journals' => $count,
            'cache_directory' => $this->cacheDir,
            'cache_ttl_seconds' => $this->cacheTtl
        ];
    }

    /**
     * Clear journal cache
     *
     * @param string|null $issn Specific ISSN to clear, or null for all
     * @return bool
     */
    public function clearCache(?string $issn = null): bool
    {
        if ($issn !== null) {
            $cleanIssn = str_replace('-', '', strtoupper($issn));
            $cacheKey = 'journal_' . $cleanIssn;
            return $this->cacheManager->delete($cacheKey);
        }

        // Clear all journal caches
        $pattern = $this->cacheDir . '/journal_*.json.gz';
        $files = glob($pattern);
        
        foreach ($files as $file) {
            @unlink($file);
        }

        return true;
    }
}

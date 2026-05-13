<?php

declare(strict_types=1);

namespace Wizdam\Services;

use Exception;

/**
 * SDG Classification Service
 * 
 * Wrapper class untuk SDG_Classification_API.php yang menyediakan interface OOP
 * untuk klasifikasi SDG tanpa mengubah file API asli.
 * 
 * @version 1.0.0
 * @author Wizdam Team
 * @license MIT
 */
class SdgClassificationService
{
    private string $apiFilePath;
    private array $config;
    private ?array $sdgDefinitions = null;
    private ?array $sdgKeywords = null;

    /**
     * Constructor
     */
    public function __construct(string $apiFilePath = '')
    {
        $this->apiFilePath = $apiFilePath ?: PROJECT_ROOT . '/api/SDG_Classification_API.php';
        
        if (!file_exists($this->apiFilePath)) {
            throw new Exception('SDG Classification API file not found: ' . $this->apiFilePath);
        }

        $this->config = [
            'MIN_SCORE_THRESHOLD'       => 0.20,
            'CONFIDENCE_THRESHOLD'      => 0.30,
            'HIGH_CONFIDENCE_THRESHOLD' => 0.60,
            'MAX_SDGS_PER_WORK'         => 7,
            'KEYWORD_WEIGHT'            => 0.30,
            'SIMILARITY_WEIGHT'         => 0.30,
            'SUBSTANTIVE_WEIGHT'        => 0.20,
            'CAUSAL_WEIGHT'             => 0.20,
            'ACTIVE_CONTRIBUTOR_THRESHOLD'   => 0.50,
            'RELEVANT_CONTRIBUTOR_THRESHOLD' => 0.35,
            'DISCUSSANT_THRESHOLD'           => 0.25,
            'CACHE_TTL'                 => 604800,
        ];
    }

    /**
     * Load SDG definitions dari file API
     */
    public function loadSdgDefinitions(): array
    {
        if ($this->sdgDefinitions !== null) {
            return $this->sdgDefinitions;
        }

        // Extract definitions from API file without executing it
        $apiContent = file_get_contents($this->apiFilePath);
        
        // Parse SDG_DEFINITIONS using regex
        if (preg_match('/\$SDG_DEFINITIONS\s*=\s*(\[[\s\S]*?\]);/', $apiContent, $matches)) {
            // Evaluate safely to get the array
            eval('$this->sdgDefinitions = ' . $matches[1] . ';');
        } else {
            // Fallback to standard definitions
            $this->sdgDefinitions = $this->getDefaultSdgDefinitions();
        }

        return $this->sdgDefinitions;
    }

    /**
     * Load SDG keywords dari file API
     */
    public function loadSdgKeywords(): array
    {
        if ($this->sdgKeywords !== null) {
            return $this->sdgKeywords;
        }

        $apiContent = file_get_contents($this->apiFilePath);
        
        // Parse SDG_KEYWORDS using regex
        if (preg_match('/\$SDG_KEYWORDS\s*=\s*(\[[\s\S]*?\]);/', $apiContent, $matches)) {
            eval('$this->sdgKeywords = ' . $matches[1] . ';');
        } else {
            $this->sdgKeywords = [];
        }

        return $this->sdgKeywords;
    }

    /**
     * Get default SDG definitions as fallback
     */
    private function getDefaultSdgDefinitions(): array
    {
        return [
            'SDG1'  => ['title' => 'No Poverty', 'description' => 'End poverty in all its forms everywhere'],
            'SDG2'  => ['title' => 'Zero Hunger', 'description' => 'End hunger, achieve food security and improved nutrition and promote sustainable agriculture'],
            'SDG3'  => ['title' => 'Good Health and Well-being', 'description' => 'Ensure healthy lives and promote well-being for all at all ages'],
            'SDG4'  => ['title' => 'Quality Education', 'description' => 'Ensure inclusive and equitable quality education and promote lifelong learning opportunities for all'],
            'SDG5'  => ['title' => 'Gender Equality', 'description' => 'Achieve gender equality and empower all women and girls'],
            'SDG6'  => ['title' => 'Clean Water and Sanitation', 'description' => 'Ensure availability and sustainable management of water and sanitation for all'],
            'SDG7'  => ['title' => 'Affordable and Clean Energy', 'description' => 'Ensure access to affordable, reliable, sustainable and modern energy for all'],
            'SDG8'  => ['title' => 'Decent Work and Economic Growth', 'description' => 'Promote sustained, inclusive and sustainable economic growth, full and productive employment and decent work for all'],
            'SDG9'  => ['title' => 'Industry, Innovation and Infrastructure', 'description' => 'Build resilient infrastructure, promote inclusive and sustainable industrialization and foster innovation'],
            'SDG10' => ['title' => 'Reduced Inequalities', 'description' => 'Reduce inequality within and among countries'],
            'SDG11' => ['title' => 'Sustainable Cities and Communities', 'description' => 'Make cities and human settlements inclusive, safe, resilient and sustainable'],
            'SDG12' => ['title' => 'Responsible Consumption and Production', 'description' => 'Ensure sustainable consumption and production patterns'],
            'SDG13' => ['title' => 'Climate Action', 'description' => 'Take urgent action to combat climate change and its impacts'],
            'SDG14' => ['title' => 'Life Below Water', 'description' => 'Conserve and sustainably use the oceans, seas and marine resources for sustainable development'],
            'SDG15' => ['title' => 'Life on Land', 'description' => 'Protect, restore and promote sustainable use of terrestrial ecosystems'],
            'SDG16' => ['title' => 'Peace, Justice and Strong Institutions', 'description' => 'Promote peaceful and inclusive societies for sustainable development'],
            'SDG17' => ['title' => 'Partnerships for the Goals', 'description' => 'Strengthen the means of implementation and revitalize the global partnership for sustainable development'],
        ];
    }

    /**
     * Analyze text content for SDG classification
     * 
     * @param string $title Paper title
     * @param string $abstract Paper abstract
     * @param array $keywords Optional keywords array
     * @return array SDG classification results
     */
    public function analyzeText(string $title, string $abstract, array $keywords = []): array
    {
        $this->loadSdgKeywords();
        $this->loadSdgDefinitions();

        $combinedText = strtolower($title . ' ' . $abstract);
        $results = [];

        foreach ($this->sdgKeywords as $sdgCode => $keywordList) {
            $matchCount = 0;
            $matchedKeywords = [];

            foreach ($keywordList as $keyword) {
                if (stripos($combinedText, strtolower($keyword)) !== false) {
                    $matchCount++;
                    $matchedKeywords[] = $keyword;
                }
            }

            if ($matchCount > 0) {
                $score = min(1.0, $matchCount / count($keywordList));
                $results[$sdgCode] = [
                    'score' => $score,
                    'match_count' => $matchCount,
                    'matched_keywords' => array_slice(array_unique($matchedKeywords), 0, 10),
                    'confidence' => $this->calculateConfidence($score, $matchCount),
                ];
            }
        }

        // Sort by score descending
        arsort($results);

        return [
            'title' => $title,
            'abstract_length' => strlen($abstract),
            'keyword_count' => count($keywords),
            'sdg_matches' => $results,
            'top_sdgs' => array_slice($results, 0, $this->config['MAX_SDGS_PER_WORK'], true),
        ];
    }

    /**
     * Calculate confidence score based on match metrics
     */
    private function calculateConfidence(float $score, int $matchCount): float
    {
        $baseConfidence = $score * $this->config['KEYWORD_WEIGHT'];
        $volumeBonus = min(0.3, $matchCount * 0.02);
        
        return min(1.0, $baseConfidence + $volumeBonus);
    }

    /**
     * Classify ORCID works batch
     * 
     * @param string $orcid Researcher ORCID
     * @param array $works Array of works to classify
     * @return array Classification results
     */
    public function classifyWorks(string $orcid, array $works): array
    {
        $results = [];
        $aggregateScores = [];

        foreach ($works as $index => $work) {
            $title = $work['title'] ?? '';
            $abstract = $work['abstract'] ?? '';
            $keywords = $work['keywords'] ?? [];

            $analysis = $this->analyzeText($title, $abstract, $keywords);
            
            $results[] = [
                'work_index' => $index,
                'title' => $title,
                'analysis' => $analysis,
            ];

            // Aggregate scores for researcher profile
            foreach ($analysis['sdg_matches'] as $sdgCode => $sdgData) {
                if (!isset($aggregateScores[$sdgCode])) {
                    $aggregateScores[$sdgCode] = [
                        'total_score' => 0,
                        'work_count' => 0,
                        'total_matches' => 0,
                    ];
                }
                $aggregateScores[$sdgCode]['total_score'] += $sdgData['score'];
                $aggregateScores[$sdgCode]['work_count']++;
                $aggregateScores[$sdgCode]['total_matches'] += $sdgData['match_count'];
            }
        }

        // Calculate average scores per SDG
        $researcherProfile = [];
        foreach ($aggregateScores as $sdgCode => $data) {
            $researcherProfile[$sdgCode] = [
                'average_score' => $data['total_score'] / $data['work_count'],
                'work_count' => $data['work_count'],
                'total_matches' => $data['total_matches'],
                'confidence' => $this->calculateConfidence(
                    $data['total_score'] / $data['work_count'],
                    (int)($data['total_matches'] / $data['work_count'])
                ),
            ];
        }

        arsort($researcherProfile);

        return [
            'orcid' => $orcid,
            'works_analyzed' => count($works),
            'work_results' => $results,
            'researcher_profile' => $researcherProfile,
            'top_sdgs' => array_slice($researcherProfile, 0, 5, true),
        ];
    }

    /**
     * Get SDG info by code
     */
    public function getSdgInfo(string $sdgCode): ?array
    {
        $definitions = $this->loadSdgDefinitions();
        return $definitions[$sdgCode] ?? null;
    }

    /**
     * Get all SDG definitions
     */
    public function getAllSdgDefinitions(): array
    {
        return $this->loadSdgDefinitions();
    }

    /**
     * Get configuration value
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Set configuration value
     */
    public function setConfig(string $key, mixed $value): void
    {
        $this->config[$key] = $value;
    }
}

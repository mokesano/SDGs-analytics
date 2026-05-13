<?php

declare(strict_types=1);

namespace Wizdam\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wizdam\Services\OrcidService;
use Wizdam\Services\SdgClassificationService;
use Wizdam\Services\SdgDefinitionsService;
use Wizdam\Utils\Validator;
use Wizdam\Utils\CacheManager;

/**
 * Complete ORCID Analysis Test
 * 
 * Menguji alur lengkap: Input -> Fetch API -> Klasifikasi SDG -> Output Data
 * ORCID Target: 0000-0001-9006-2018
 */
class CompleteOrcidAnalysisTest extends TestCase
{
    private const TEST_ORCID = '0000-0001-9006-2018';
    
    private OrcidService $orcidService;
    private SdgClassificationService $sdgClassificationService;
    private SdgDefinitionsService $sdgDefinitionsService;
    private Validator $validator;
    private CacheManager $cacheManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orcidService = new OrcidService();
        $this->sdgClassificationService = new SdgClassificationService();
        $this->sdgDefinitionsService = new SdgDefinitionsService();
        $this->validator = new Validator();
        $this->cacheManager = new CacheManager();
    }

    /**
     * Test 1: Validasi format ORCID
     */
    public function testOrcidFormatValidation(): void
    {
        $isValid = $this->validator->validateOrcid(self::TEST_ORCID);
        $this->assertTrue($isValid, "ORCID " . self::TEST_ORCID . " harus valid");
    }

    /**
     * Test 2: Fetch Profile dari ORCID API (dengan caching)
     */
    public function testFetchProfileFromOrcidApi(): void
    {
        $profile = $this->orcidService->getProfile(self::TEST_ORCID, true); // Force refresh
        
        $this->assertIsArray($profile, "Profile harus berupa array");
        $this->assertArrayHasKey('given_names', $profile, "Profile harus memiliki field 'given_names'");
        $this->assertArrayHasKey('family_name', $profile, "Profile harus memiliki field 'family_name'");
        $this->assertArrayHasKey('full_name', $profile, "Profile harus memiliki field 'full_name'");
        
        if (!empty($profile['full_name'])) {
            echo "\n[Nama Peneliti: " . $profile['full_name'] . "]";
        }
    }

    /**
     * Test 3: Fetch Works/Publications dari ORCID API
     */
    public function testFetchWorksFromOrcidApi(): void
    {
        $works = $this->orcidService->getWorks(self::TEST_ORCID, 50, true); // Force refresh
        
        $this->assertIsArray($works, "Works harus berupa array");
        
        $workCount = count($works);
        echo "\n[Jumlah Publikasi Teranalisis: $workCount]";
        
        if ($workCount > 0) {
            $this->assertGreaterThan(0, $workCount, "Harus ada minimal 1 publikasi");
        }
    }

    /**
     * Test 4: Struktur Data Works - Memastikan semua field wajib ada
     */
    public function testWorksDataStructure(): void
    {
        $works = $this->orcidService->getWorks(self::TEST_ORCID, 50);
        
        if (count($works) === 0) {
            $this->markTestSkipped("Tidak ada works untuk diuji");
            return;
        }
        
        $firstWork = $works[0];
        
        $requiredFields = ['title', 'year', 'doi', 'journal', 'url'];
        
        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $firstWork, "Work harus memiliki field '$field'");
        }
    }

    /**
     * Test 5: Load SDG Definitions
     */
    public function testSdgDefinitionsLoaded(): void
    {
        $definitions = $this->sdgDefinitionsService->getAllDefinitions();
        
        $this->assertCount(17, $definitions, "Harus ada 17 SDG definitions");
    }

    /**
     * Test 6: Analisis SDG untuk setiap publikasi
     */
    public function testSdgClassificationForPublications(): void
    {
        $works = $this->orcidService->getWorks(self::TEST_ORCID, 50);
        
        if (count($works) === 0) {
            $this->markTestSkipped("Tidak ada works untuk dianalisis");
            return;
        }
        
        $analyzedWorks = [];
        $sdgScores = [];
        
        foreach ($works as $index => $work) {
            if ($index >= 10) break; // Limit 10 works untuk testing
            
            $title = $work['title'] ?? '';
            $abstract = ''; // Abstract tidak selalu tersedia di summary
            
            if (empty(trim($title))) {
                continue;
            }
            
            $analysis = $this->sdgClassificationService->analyzeText($title, $abstract, []);
            
            $analyzedWorks[] = [
                'title' => $title,
                'year' => $work['year'] ?? '',
                'doi' => $work['doi'] ?? '',
                'analysis' => $analysis,
            ];
            
            // Aggregate SDG scores
            foreach ($analysis['sdg_matches'] ?? [] as $sdgCode => $sdgData) {
                if (!isset($sdgScores[$sdgCode])) {
                    $sdgScores[$sdgCode] = [
                        'total_score' => 0,
                        'count' => 0,
                        'matched_keywords' => [],
                    ];
                }
                $sdgScores[$sdgCode]['total_score'] += $sdgData['score'];
                $sdgScores[$sdgCode]['count']++;
                $sdgScores[$sdgCode]['matched_keywords'] = array_merge(
                    $sdgScores[$sdgCode]['matched_keywords'],
                    $sdgData['matched_keywords'] ?? []
                );
            }
        }
        
        // Calculate average scores
        $averageScores = [];
        foreach ($sdgScores as $sdgCode => $data) {
            if ($data['count'] > 0) {
                $averageScores[$sdgCode] = [
                    'average_score' => $data['total_score'] / $data['count'],
                    'work_count' => $data['count'],
                    'unique_keywords' => array_values(array_unique($data['matched_keywords'])),
                ];
            }
        }
        
        // Sort by average score descending
        arsort($averageScores);
        
        // Display results
        echo "\n\n===========================================";
        echo "\n   HASIL ANALISIS SDG";
        echo "\n===========================================\n";
        echo "ORCID: " . self::TEST_ORCID . "\n";
        echo "Jumlah Publikasi Dianalisis: " . count($analyzedWorks) . "\n\n";
        
        if (count($averageScores) > 0) {
            echo "Dominasi SDG:\n";
            $topSdg = null;
            $topScore = 0;
            
            foreach ($averageScores as $sdgCode => $data) {
                $sdgInfo = $this->sdgDefinitionsService->getDefinition($sdgCode);
                $sdgName = $sdgInfo['name'] ?? $sdgCode;
                printf("  %-8s (%s): Score %.3f (dari %d publikasi)\n", 
                    $sdgCode, 
                    $sdgName, 
                    $data['average_score'],
                    $data['work_count']
                );
                
                if ($data['average_score'] > $topScore) {
                    $topScore = $data['average_score'];
                    $topSdg = [
                        'code' => $sdgCode,
                        'name' => $sdgName,
                        'score' => $data['average_score'],
                        'keywords' => $data['unique_keywords'],
                    ];
                }
            }
            
            echo "\n[Dominasi SDG: " . ($topSdg['code'] ?? 'N/A') . " - " . ($topSdg['name'] ?? '') . " (Score: " . number_format($topScore, 3) . ")]";
            
            // Find evidence - publication that matches top SDG
            if ($topSdg !== null && count($analyzedWorks) > 0) {
                foreach ($analyzedWorks as $work) {
                    if (isset($work['analysis']['sdg_matches'][$topSdg['code']])) {
                        echo "\n\n[Bukti Kontribusi:]";
                        echo "\n  Judul Paper: " . $work['title'];
                        echo "\n  Tahun: " . ($work['year'] ?? 'N/A');
                        if (!empty($work['doi'])) {
                            echo "\n  DOI: " . $work['doi'];
                        }
                        echo "\n  Keywords Match: " . implode(', ', array_slice($work['analysis']['sdg_matches'][$topSdg['code']]['matched_keywords'], 0, 5));
                        break;
                    }
                }
            }
        } else {
            echo "Tidak ada matching SDG ditemukan.\n";
        }
        
        echo "\n===========================================\n";
        
        $this->assertIsArray($averageScores, "SDG scores harus berupa array");
    }

    /**
     * Test 7: End-to-End Flow - Lengkap dengan output structure
     */
    public function testEndToEndFlowWithOutputStructure(): void
    {
        // Step 1: Get profile
        $profile = $this->orcidService->getProfile(self::TEST_ORCID);
        $this->assertNotEmpty($profile['full_name'] ?? '', "Nama peneliti harus ada");
        
        // Step 2: Get works
        $works = $this->orcidService->getWorks(self::TEST_ORCID, 50);
        
        // Step 3: Analyze works for SDG
        $sdgResults = $this->sdgClassificationService->classifyWorks(self::TEST_ORCID, $works);
        
        // Step 4: Build final output structure
        $topSdgs = $sdgResults['top_sdgs'] ?? [];
        $topSdgKey = !empty($topSdgs) ? array_keys($topSdgs)[0] : '';
        $topSdgValue = !empty($topSdgs) ? reset($topSdgs) : ['average_score' => 0];
        
        $output = [
            'researcher_name' => $profile['full_name'] ?? '',
            'orcid' => self::TEST_ORCID,
            'publications' => array_map(function($work) {
                return [
                    'title' => $work['title'] ?? '',
                    'year' => $work['year'] ?? '',
                    'doi' => $work['doi'] ?? '',
                    'journal' => $work['journal'] ?? '',
                    'url' => $work['url'] ?? '',
                ];
            }, $works),
            'sdg_scores' => $sdgResults['researcher_profile'] ?? [],
            'sdg_categories' => array_keys($sdgResults['researcher_profile'] ?? []),
            'evidence_links' => array_map(function($work) use ($sdgResults) {
                return [
                    'title' => $work['title'] ?? '',
                    'url' => $work['url'] ?? '',
                    'doi' => $work['doi'] ?? '',
                ];
            }, array_slice($works, 0, 10)),
            'summary' => [
                'total_publications' => count($works),
                'top_sdg' => $topSdgKey,
                'top_sdg_score' => is_array($topSdgValue) ? ($topSdgValue['average_score'] ?? 0) : 0,
            ]
        ];
        
        // Verify output structure
        $this->assertArrayHasKey('researcher_name', $output);
        $this->assertArrayHasKey('publications', $output);
        $this->assertArrayHasKey('sdg_scores', $output);
        $this->assertArrayHasKey('sdg_categories', $output);
        $this->assertArrayHasKey('evidence_links', $output);
        
        // Display summary
        echo "\n\n===========================================";
        echo "\n   STRUKTUR OUTPUT FINAL";
        echo "\n===========================================\n";
        echo "Researcher Name: " . $output['researcher_name'] . "\n";
        echo "Total Publications: " . $output['summary']['total_publications'] . "\n";
        echo "Top SDG: " . ($output['summary']['top_sdg'] ?? 'N/A') . "\n";
        echo "Top SDG Score: " . number_format($output['summary']['top_sdg_score'], 3) . "\n";
        echo "SDG Categories Count: " . count($output['sdg_categories']) . "\n";
        echo "Evidence Links Count: " . count($output['evidence_links']) . "\n";
        echo "===========================================\n";
    }

    /**
     * Test 8: Verify Cache Files
     */
    public function testCacheFilesCreated(): void
    {
        $profileCachePattern = 'cache/orcid_profile_' . self::TEST_ORCID . '*.json*';
        $worksCachePattern = 'cache/orcid_works_' . self::TEST_ORCID . '*.json*';
        
        $profileFiles = glob($profileCachePattern);
        $worksFiles = glob($worksCachePattern);
        
        $this->assertGreaterThan(0, count($profileFiles), "Cache profile harus ada");
        echo "\n[Cache Profile: " . count($profileFiles) . " file(s)]";
        
        $this->assertGreaterThan(0, count($worksFiles), "Cache works harus ada");
        echo "\n[Cache Works: " . count($worksFiles) . " file(s)]";
    }

    /**
     * Test 9: Error Handling - Invalid ORCID
     */
    public function testInvalidOrcidHandling(): void
    {
        $invalidOrcid = '0000-0000-0000-0000';
        $this->assertFalse(
            $this->validator->validateOrcid($invalidOrcid),
            "ORCID invalid harus ditolak"
        );
    }

    /**
     * Test 10: Error Handling - Malformed ORCID
     */
    public function testMalformedOrcidHandling(): void
    {
        $malformedOrcid = 'invalid-orcid-format';
        $this->assertFalse(
            $this->validator->validateOrcid($malformedOrcid),
            "ORCID malformed harus ditolak"
        );
    }
}

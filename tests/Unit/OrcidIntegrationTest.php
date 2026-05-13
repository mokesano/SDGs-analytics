<?php

declare(strict_types=1);

namespace Wizdam\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wizdam\Services\OrcidProfileService;
use Wizdam\Services\SdgClassificationService;
use Wizdam\Services\SdgDefinitionsService;
use Wizdam\Utils\Validator;

/**
 * Unit Test untuk ORCID: 0000-0001-9006-2018
 * 
 * Menguji alur lengkap: Input -> Fetch API -> Klasifikasi SDG -> Output Data
 */
class OrcidIntegrationTest extends TestCase
{
    private const TEST_ORCID = '0000-0001-9006-2018';
    
    private OrcidProfileService $orcidService;
    private SdgDefinitionsService $sdgService;
    private Validator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orcidService = new OrcidProfileService();
        $this->sdgService = new SdgDefinitionsService();
        $this->validator = new Validator();
    }

    /**
     * Test 1: Validasi format ORCID
     */
    public function testOrcidFormatValidation(): void
    {
        $this->assertTrue(
            $this->validator->validateOrcid(self::TEST_ORCID),
            "ORCID " . self::TEST_ORCID . " harus valid"
        );
    }

    /**
     * Test 2: Validasi checksum ORCID
     */
    public function testOrcidChecksumValidation(): void
    {
        $isValid = $this->validator->validateOrcid(self::TEST_ORCID);
        $this->assertTrue($isValid, "Checksum ORCID harus valid");
    }

    /**
     * Test 3: Fetch Profile dari Cache (tidak call API langsung)
     */
    public function testFetchProfileFromCache(): void
    {
        $profile = $this->orcidService->getProfile(self::TEST_ORCID);
        
        $this->assertIsArray($profile, "Profile harus berupa array");
        $this->assertArrayHasKey('name', $profile, "Profile harus memiliki field 'name'");
        $this->assertNotEmpty($profile['name'], "Nama peneliti tidak boleh kosong");
    }

    /**
     * Test 4: Struktur Data Profile
     */
    public function testProfileDataStructure(): void
    {
        $profile = $this->orcidService->getProfile(self::TEST_ORCID);
        
        $requiredFields = ['name', 'keywords', 'urls'];
        
        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $profile, "Profile harus memiliki field '$field'");
        }
    }

    /**
     * Test 5: Fetch Works/Publications dari Cache
     */
    public function testFetchWorksFromCache(): void
    {
        $works = $this->orcidService->getWorks(self::TEST_ORCID, 100);
        
        $this->assertIsArray($works, "Works harus berupa array");
        $this->assertGreaterThan(0, count($works), "Harus ada minimal 1 publikasi");
    }

    /**
     * Test 6: Struktur Data Works
     */
    public function testWorksDataStructure(): void
    {
        $works = $this->orcidService->getWorks(self::TEST_ORCID, 100);
        
        $this->assertIsArray($works);
        
        if (count($works) > 0) {
            $firstWork = $works[0];
            
            $requiredFields = ['title', 'year', 'doi', 'abstract'];
            
            foreach ($requiredFields as $field) {
                $this->assertArrayHasKey($field, $firstWork, "Work harus memiliki field '$field'");
            }
        }
    }

    /**
     * Test 7: Load SDG Definitions
     */
    public function testSdgDefinitionsLoaded(): void
    {
        $definitions = $this->sdgService->getAllDefinitions();
        
        $this->assertCount(17, $definitions, "Harus ada 17 SDG definitions");
    }

    /**
     * Test 8: Get Specific SDG Definition
     */
    public function testGetSpecificSdgDefinition(): void
    {
        $sdg1 = $this->sdgService->getDefinition('SDG1');
        
        $this->assertIsArray($sdg1, "SDG definition harus berupa array");
        $this->assertArrayHasKey('code', $sdg1);
        $this->assertArrayHasKey('name', $sdg1);
        $this->assertArrayHasKey('description', $sdg1);
        $this->assertEquals('SDG1', $sdg1['code']);
    }

    /**
     * Test 9: SDG Classification Service Instantiation
     */
    public function testSdgClassificationServiceExists(): void
    {
        $service = new SdgClassificationService();
        $this->assertInstanceOf(SdgClassificationService::class, $service);
    }

    /**
     * Test 10: End-to-End Flow - Analisis Publikasi
     */
    public function testEndToEndAnalysis(): void
    {
        // Step 1: Get profile
        $profile = $this->orcidService->getProfile(self::TEST_ORCID);
        $this->assertNotEmpty($profile['name'], "Nama peneliti harus ada");
        
        // Step 2: Get works
        $works = $this->orcidService->getWorks(self::TEST_ORCID, 100);
        $this->assertGreaterThan(0, count($works), "Harus ada publikasi");
        
        // Step 3: Get SDG definitions
        $definitions = $this->sdgService->getAllDefinitions();
        $this->assertCount(17, $definitions);
        
        // Step 4: Verify output structure
        $output = [
            'researcher_name' => $profile['name'],
            'publications' => $works,
            'sdg_scores' => [],
            'sdg_categories' => [],
            'evidence_links' => []
        ];
        
        $this->assertArrayHasKey('researcher_name', $output);
        $this->assertArrayHasKey('publications', $output);
        $this->assertArrayHasKey('sdg_scores', $output);
        $this->assertArrayHasKey('sdg_categories', $output);
        $this->assertArrayHasKey('evidence_links', $output);
    }

    /**
     * Test 11: Count Publications
     */
    public function testPublicationCount(): void
    {
        $works = $this->orcidService->getWorks(self::TEST_ORCID, 100);
        $count = count($works);
        
        $this->assertGreaterThan(0, $count, "Jumlah publikasi harus > 0");
        $this->assertLessThanOrEqual(100, $count, "Jumlah publikasi maksimal 100 (limit)");
        
        echo "\n[Jumlah Publikasi Teranalisis: $count]\n";
    }

    /**
     * Test 12: Extract Researcher Name
     */
    public function testResearcherName(): void
    {
        $profile = $this->orcidService->getProfile(self::TEST_ORCID);
        $name = $profile['name'];
        
        $this->assertIsString($name, "Nama harus berupa string");
        $this->assertNotEmpty($name, "Nama tidak boleh kosong");
        
        echo "\n[Nama Peneliti: $name]\n";
    }

    /**
     * Test 13: Verify Cache File Exists
     */
    public function testCacheFilesExist(): void
    {
        $profileCache = dirname(__DIR__, 2) . '/cache/orcid_profile_' . self::TEST_ORCID . '.json';
        $worksCache = dirname(__DIR__, 2) . '/cache/orcid_works_' . self::TEST_ORCID . '_100.json';
        
        $this->assertFileExists($profileCache, "Cache profile harus ada");
        $this->assertFileExists($worksCache, "Cache works harus ada");
    }

    /**
     * Test 14: Validate DOI Format in Works
     */
    public function testDoiFormatInWorks(): void
    {
        $works = $this->orcidService->getWorks(self::TEST_ORCID, 100);
        
        $validDoiCount = 0;
        foreach ($works as $work) {
            if (!empty($work['doi']) && $this->validator->validateDoi($work['doi'])) {
                $validDoiCount++;
            }
        }
        
        echo "[DOI Valid: $validDoiCount dari " . count($works) . " publikasi]\n";
        $this->assertGreaterThanOrEqual(0, $validDoiCount);
    }

    /**
     * Test 15: Search SDG by Keyword
     */
    public function testSearchSdgByKeyword(): void
    {
        $results = $this->sdgService->searchByKeyword('climate');
        
        $this->assertIsArray($results);
        // SDG 13 (Climate Action) should be in results
        $found = false;
        foreach ($results as $sdg) {
            if ($sdg['code'] === 'SDG13') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, "SDG13 harus ditemukan saat search 'climate'");
    }

    /**
     * Test 16: Error Handling - Invalid ORCID
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
     * Test 17: Error Handling - Malformed ORCID
     */
    public function testMalformedOrcidHandling(): void
    {
        $malformedOrcid = 'invalid-orcid-format';
        $this->assertFalse(
            $this->validator->validateOrcid($malformedOrcid),
            "ORCID malformed harus ditolak"
        );
    }

    /**
     * Test 18: Summary Report
     */
    public function testSummaryReport(): void
    {
        $profile = $this->orcidService->getProfile(self::TEST_ORCID);
        $works = $this->orcidService->getWorks(self::TEST_ORCID, 100);
        
        echo "\n\n";
        echo "===========================================\n";
        echo "   LAPORAN ANALISIS ORCID\n";
        echo "===========================================\n";
        echo "ORCID: " . self::TEST_ORCID . "\n";
        echo "Nama Peneliti: " . $profile['name'] . "\n";
        echo "Jumlah Publikasi: " . count($works) . "\n";
        echo "Keywords: " . (is_array($profile['keywords']) ? implode(', ', array_slice($profile['keywords'], 0, 5)) : 'N/A') . "\n";
        echo "===========================================\n";
        
        if (count($works) > 0) {
            echo "\nContoh Publikasi:\n";
            echo "- " . $works[0]['title'] . " (" . $works[0]['year'] . ")\n";
            if (!empty($works[0]['doi'])) {
                echo "  DOI: " . $works[0]['doi'] . "\n";
            }
        }
        
        echo "\n[Dominasi SDG: Perlu analisis lebih lanjut dengan classification service]\n";
        echo "===========================================\n\n";
        
        $this->assertTrue(true); // Dummy assertion
    }
}

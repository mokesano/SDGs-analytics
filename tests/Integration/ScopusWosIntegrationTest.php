<?php

declare(strict_types=1);

namespace Wizdam\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Wizdam\Services\ResearcherAggregatorService;
use Wizdam\Services\ResearcherIdentityService;
use Wizdam\Services\OrcidProfileService;
use Wizdam\Services\ScopusResearcherService;
use Wizdam\Services\WosResearcherService;
use Wizdam\Utils\CacheManager;

/**
 * Integration Test: End-to-End Scopus & WoS Data Extraction
 * 
 * Menguji alur lengkap:
 * 1. Ekstraksi Scopus ID & ResearcherID dari ORCID
 * 2. Fetch data peneliti dari Scopus (jika ID ditemukan)
 * 3. Fetch data peneliti dari WoS (jika ID ditemukan)
 * 4. Agregasi data lengkap
 */
class ScopusWosIntegrationTest extends TestCase
{
    private const TEST_ORCID = '0000-0001-9006-2018';
    private ResearcherAggregatorService $aggregator;
    private OrcidProfileService $orcidService;
    private ResearcherIdentityService $identityService;
    private CacheManager $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new CacheManager();
        $this->orcidService = new OrcidProfileService();
        $this->identityService = new ResearcherIdentityService();
        
        // Cek apakah API Key tersedia di environment atau .env
        $scopusKey = getenv('SCOPUS_API_KEY') ?: null;
        $wosKey = getenv('WOS_API_KEY') ?: null;
        
        $this->aggregator = new ResearcherAggregatorService(
            scopusApiKey: $scopusKey,
            wosApiKey: $wosKey
        );
        
        // Clear cache untuk test ini agar mendapatkan data fresh
        $this->cache->delete("orcid_profile_" . self::TEST_ORCID);
        $this->cache->delete("orcid_works_" . self::TEST_ORCID);
        $this->cache->delete("scopus_researcher_");
        $this->cache->delete("wos_researcher_");
    }

    /**
     * Test 1: Ekstraksi Identifier dari ORCID
     */
    public function testExtractIdentifiersFromOrcid(): void
    {
        echo "\n=== TEST 1: Ekstraksi Identifier dari ORCID ===\n";
        
        $profileData = $this->orcidService->getProfile(self::TEST_ORCID);
        
        $this->assertIsArray($profileData, "Profile data harus berupa array");
        $this->assertArrayHasKey('name', $profileData, "Profile harus memiliki nama");
        
        $identifiers = $this->identityService->extractAllIdentifiers($profileData);
        
        echo "Nama Peneliti: " . ($profileData['name']['full'] ?? 'N/A') . "\n";
        echo "Scopus Author ID: " . ($identifiers['scopus_author_id']['value'] ?? 'TIDAK DITEMUKAN') . "\n";
        echo "ResearcherID: " . ($identifiers['researcher_id']['value'] ?? 'TIDAK DITEMUKAN') . "\n";
        echo "Publons ID: " . ($identifiers['publons_id']['value'] ?? 'TIDAK DITEMUKAN') . "\n";
        echo "Google Scholar ID: " . ($identifiers['google_scholar_id']['value'] ?? 'TIDAK DITEMUKAN') . "\n";
        
        // Kita tidak assert harus ada ID, karena tergantung data ORCID
        // Tapi kita pastikan strukturnya benar
        $this->assertArrayHasKey('scopus_author_id', $identifiers);
        $this->assertArrayHasKey('researcher_id', $identifiers);
        $this->assertArrayHasKey('publons_id', $identifiers);
        $this->assertArrayHasKey('google_scholar_id', $identifiers);
        
        if (!empty($identifiers['scopus_author_id']['value'])) {
            echo "✅ Scopus ID ditemukan: " . $identifiers['scopus_author_id']['value'] . "\n";
            $this->assertTrue(
                $this->identityService->isValidScopusAuthorId($identifiers['scopus_author_id']['value']),
                "Format Scopus ID harus valid"
            );
        } else {
            echo "⚠️ Scopus ID tidak ditemukan di profil ORCID\n";
        }
        
        if (!empty($identifiers['researcher_id']['value'])) {
            echo "✅ ResearcherID ditemukan: " . $identifiers['researcher_id']['value'] . "\n";
            $this->assertTrue(
                $this->identityService->isValidResearcherId($identifiers['researcher_id']['value']),
                "Format ResearcherID harus valid"
            );
        } else {
            echo "⚠️ ResearcherID tidak ditemukan di profil ORCID\n";
        }
    }

    /**
     * Test 2: Fetch Data dari Scopus (Jika Scopus ID ditemukan)
     */
    public function testFetchScopusDataIfIdExists(): void
    {
        echo "\n=== TEST 2: Fetch Data dari Scopus ===\n";
        
        $profileData = $this->orcidService->getProfile(self::TEST_ORCID);
        $identifiers = $this->identityService->extractAllIdentifiers($profileData);
        $scopusId = !empty($identifiers['scopus_author_id']['value']) ? $identifiers['scopus_author_id']['value'] : null;
        
        if (!$scopusId) {
            echo "⚠️ SKIP: Scopus ID tidak ditemukan, tidak bisa fetch data Scopus\n";
            $this->markTestSkipped('Scopus ID tidak ditemukan di profil ORCID');
            return;
        }
        
        echo "Scopus ID yang akan dicek: {$scopusId}\n";
        
        $scopusService = new ScopusResearcherService('', getenv('SCOPUS_API_KEY') ?: '');
        $scopusProfile = $scopusService->getResearcherProfile($scopusId);
        
        $this->assertIsArray($scopusProfile, "Scopus profile harus berupa array");
        
        echo "Nama di Scopus: " . ($scopusProfile['name'] ?? 'N/A') . "\n";
        echo "Affiliation: " . ($scopusProfile['affiliation'] ?? 'N/A') . "\n";
        echo "Document Count: " . ($scopusProfile['document_count'] ?? 0) . "\n";
        echo "Cited By Count: " . ($scopusProfile['cited_by_count'] ?? 0) . "\n";
        echo "h-index: " . ($scopusProfile['h_index'] ?? 'N/A') . "\n";
        
        // Assert jika API key ada, data harus lebih lengkap
        if (getenv('SCOPUS_API_KEY')) {
            $this->assertGreaterThan(0, 
                $scopusProfile['document_count'] ?? 0, 
                "Peneliti harus memiliki publikasi di Scopus"
            );
        }
        
        // Cek publications
        if (!empty($scopusProfile['publications'])) {
            echo "\n📚 Jumlah Publikasi dari Scopus: " . count($scopusProfile['publications']) . "\n";
            echo "Contoh Publikasi Pertama:\n";
            $firstPub = $scopusProfile['publications'][0];
            echo "  - Judul: " . ($firstPub['title'] ?? 'N/A') . "\n";
            echo "  - Tahun: " . ($firstPub['year'] ?? 'N/A') . "\n";
            echo "  - Jurnal: " . ($firstPub['journal'] ?? 'N/A') . "\n";
            echo "  - DOI: " . ($firstPub['doi'] ?? 'N/A') . "\n";
            echo "  - Sitasi: " . ($firstPub['citations'] ?? 0) . "\n";
            
            $this->assertArrayHasKey('title', $firstPub);
            $this->assertArrayHasKey('year', $firstPub);
        } else {
            echo "⚠️ Tidak ada publikasi yang dikembalikan (mungkin perlu API Key)\n";
        }
    }

    /**
     * Test 3: Fetch Data dari Web of Science (Jika ResearcherID ditemukan)
     */
    public function testFetchWosDataIfIdExists(): void
    {
        echo "\n=== TEST 3: Fetch Data dari Web of Science ===\n";
        
        $profileData = $this->orcidService->getProfile(self::TEST_ORCID);
        $identifiers = $this->identityService->extractAllIdentifiers($profileData);
        $researcherId = !empty($identifiers['researcher_id']['value']) ? $identifiers['researcher_id']['value'] : null;
        
        if (!$researcherId) {
            echo "⚠️ SKIP: ResearcherID tidak ditemukan, tidak bisa fetch data WoS\n";
            $this->markTestSkipped('ResearcherID tidak ditemukan di profil ORCID');
            return;
        }
        
        echo "ResearcherID yang akan dicek: {$researcherId}\n";
        
        $wosService = new WosResearcherService(getenv('WOS_API_KEY') ?: null);
        $wosProfile = $wosService->getResearcherProfile($researcherId);
        
        $this->assertIsArray($wosProfile, "WoS profile harus berupa array");
        
        echo "Nama di WoS: " . ($wosProfile['name'] ?? 'N/A') . "\n";
        echo "Total Publikasi: " . ($wosProfile['total_publications'] ?? 0) . "\n";
        echo "Total Sitasi: " . ($wosProfile['total_citations'] ?? 0) . "\n";
        echo "h-index: " . ($wosProfile['h_index'] ?? 'N/A') . "\n";
        
        if (!empty($wosProfile['publications'])) {
            echo "\n📚 Jumlah Publikasi dari WoS: " . count($wosProfile['publications']) . "\n";
            echo "Contoh Publikasi Pertama:\n";
            $firstPub = $wosProfile['publications'][0];
            echo "  - Judul: " . ($firstPub['title'] ?? 'N/A') . "\n";
            echo "  - Tahun: " . ($firstPub['year'] ?? 'N/A') . "\n";
            echo "  - Jurnal: " . ($firstPub['journal'] ?? 'N/A') . "\n";
            
            $this->assertArrayHasKey('title', $firstPub);
        }
    }

    /**
     * Test 4: End-to-End Aggregation
     */
    public function testEndToEndAggregatedProfile(): void
    {
        echo "\n=== TEST 4: End-to-End Aggregated Profile ===\n";
        
        $completeProfile = $this->aggregator->getCompleteProfile(self::TEST_ORCID);
        
        $this->assertIsArray($completeProfile);
        $this->assertArrayHasKey('basic_info', $completeProfile);
        $this->assertArrayHasKey('identifiers', $completeProfile);
        $this->assertArrayHasKey('metrics', $completeProfile);
        $this->assertArrayHasKey('publications', $completeProfile);
        $this->assertArrayHasKey('sources', $completeProfile);
        
        echo "\n👤 INFORMASI PENELITI:\n";
        echo "  Nama Lengkap: " . ($completeProfile['basic_info']['full_name'] ?? 'N/A') . "\n";
        echo "  ORCID: " . ($completeProfile['basic_info']['orcid'] ?? 'N/A') . "\n";
        
        echo "\n🔑 IDENTIFIER:\n";
        echo "  Scopus ID: " . ($completeProfile['identifiers']['scopus_author_id'] ?? 'TIDAK ADA') . "\n";
        echo "  ResearcherID: " . ($completeProfile['identifiers']['researcher_id'] ?? 'TIDAK ADA') . "\n";
        
        echo "\n📊 METRIK GABUNGAN:\n";
        echo "  Total Publikasi (semua sumber): " . ($completeProfile['metrics']['total_publications'] ?? 0) . "\n";
        echo "  Total Sitasi (semua sumber): " . ($completeProfile['metrics']['total_citations'] ?? 0) . "\n";
        echo "  h-index (tertinggi): " . ($completeProfile['metrics']['h_index'] ?? 'N/A') . "\n";
        
        echo "\n📁 SUMBER DATA:\n";
        foreach ($completeProfile['sources'] as $source => $status) {
            $icon = $status ? '✅' : '❌';
            echo "  {$icon} {$source}: " . ($status ? 'BERHASIL' : 'GAGAL/TIDAK ADA ID') . "\n";
        }
        
        echo "\n📚 PUBLIKASI (Sample 5 pertama):\n";
        $publications = $completeProfile['publications'] ?? [];
        $sampleCount = min(5, count($publications));
        
        for ($i = 0; $i < $sampleCount; $i++) {
            $pub = $publications[$i];
            echo "\n  [" . ($i + 1) . "] " . ($pub['title'] ?? 'No Title') . "\n";
            echo "      Tahun: " . ($pub['year'] ?? 'N/A') . "\n";
            echo "      Jurnal: " . ($pub['journal'] ?? 'N/A') . "\n";
            echo "      DOI: " . ($pub['doi'] ?? 'N/A') . "\n";
            echo "      Sumber: " . ($pub['source'] ?? 'N/A') . "\n";
            echo "      Sitasi: " . ($pub['citations'] ?? 0) . "\n";
        }
        
        echo "\n📈 TOTAL PUBLIKASI DIAGREGASI: " . count($publications) . "\n";
        
        // Validasi struktur
        $this->assertGreaterThan(0, count($publications), "Harus ada minimal 1 publikasi");
        
        // Cek bahwa ada data dari minimal satu sumber
        $hasData = false;
        foreach ($completeProfile['sources'] as $status) {
            if ($status) {
                $hasData = true;
                break;
            }
        }
        $this->assertTrue($hasData, "Minimal satu sumber data harus berhasil");
    }

    /**
     * Test 5: Validasi Kekayaan Data
     */
    public function testDataRichnessValidation(): void
    {
        echo "\n=== TEST 5: Validasi Kekayaan Data ===\n";
        
        $completeProfile = $this->aggregator->getCompleteProfile(self::TEST_ORCID);
        
        $score = 0;
        $maxScore = 100;
        
        // Skor untuk nama lengkap
        if (!empty($completeProfile['basic_info']['full_name'])) {
            $score += 10;
            echo "✅ Nama lengkap tersedia (+10)\n";
        }
        
        // Skor untuk identifiers
        if (!empty($completeProfile['identifiers']['scopus_author_id'])) {
            $score += 15;
            echo "✅ Scopus ID tersedia (+15)\n";
        }
        if (!empty($completeProfile['identifiers']['researcher_id'])) {
            $score += 15;
            echo "✅ ResearcherID tersedia (+15)\n";
        }
        
        // Skor untuk metrik
        if (($completeProfile['metrics']['h_index'] ?? 0) > 0) {
            $score += 20;
            echo "✅ h-index tersedia: " . $completeProfile['metrics']['h_index'] . " (+20)\n";
        }
        if (($completeProfile['metrics']['total_citations'] ?? 0) > 0) {
            $score += 20;
            echo "✅ Total sitasi tersedia: " . $completeProfile['metrics']['total_citations'] . " (+20)\n";
        }
        
        // Skor untuk jumlah publikasi
        $pubCount = count($completeProfile['publications'] ?? []);
        if ($pubCount > 0) {
            $pubScore = min(20, $pubCount * 2); // Max 20 poin
            $score += $pubScore;
            echo "✅ {$pubCount} publikasi ditemukan (+{$pubScore})\n";
        }
        
        echo "\n🎯 SKOR KEKAYAAN DATA: {$score}/{$maxScore}\n";
        
        if ($score >= 70) {
            echo "🌟 DATA SANGAT KAYA!\n";
        } elseif ($score >= 40) {
            echo "👍 DATA CUKUP KAYA\n";
        } else {
            echo "⚠️ DATA MASIH TERBATAS (mungkin perlu API Keys)\n";
        }
        
        $this->assertGreaterThanOrEqual(30, $score, "Skor kekayaan data minimal 30");
    }
}

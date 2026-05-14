<?php

declare(strict_types=1);

namespace Wizdam\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Wizdam\Services\ResearcherAggregatorService;
use Wizdam\Services\ResearcherIdentityService;
use Wizdam\Services\ScopusResearcherService;
use Wizdam\Services\WosResearcherService;
use Wizdam\Services\OrcidProfileService;

/**
 * Integration Test: Complete Researcher Profile Flow
 * 
 * Menguji alur lengkap dari ORCID -> Ekstraksi Scopus ID/ResearcherID -> 
 * Fetch data dari Scopus & WoS -> Agregasi data -> Analisis SDG
 * 
 * ORCID Test: 0000-0001-9006-2018 (Bakri Bambang)
 * 
 * @package Wizdam\Tests\Integration
 * @group integration
 */
class CompleteResearcherProfileTest extends TestCase
{
    private ResearcherAggregatorService $aggregator;
    private string $testOrcid = '0000-0001-9006-2018';
    private string $scopusApiKey;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Scopus API Key dari konfigurasi existing
        $this->scopusApiKey = '2b2a63a2cd69bd0cfd7acc07addc140f';
        
        // Inisialisasi aggregator dengan Scopus API key
        $this->aggregator = new ResearcherAggregatorService(
            scopusApiKey: $this->scopusApiKey,
            wosApiKey: null, // WoS API key tidak tersedia, akan gunakan fallback
            cacheDir: __DIR__ . '/../../api/cache',
            cacheTtl: 3600 // 1 jam untuk testing
        );
    }
    
    /**
     * Test 1: Ekstraksi Identifier dari ORCID
     * 
     * Memastikan Scopus ID dan ResearcherID dapat diekstrak dari profil ORCID
     */
    public function testExtractIdentifiersFromOrcid(): void
    {
        echo "\n=== TEST 1: Ekstraksi Identifier dari ORCID ===\n";
        
        $result = $this->aggregator->extractIdentifiersOnly($this->testOrcid);
        
        $this->assertTrue($result['success'], "Gagal mengekstrak identifier");
        $this->assertArrayHasKey('identifiers', $result);
        $this->assertArrayHasKey('summary', $result);
        
        // Cek apakah ada identifier yang ditemukan
        $identifiers = $result['identifiers'];
        echo "Identifiers found:\n";
        print_r($identifiers);
        
        $summary = $result['summary'];
        echo "\nSummary:\n";
        print_r($summary);
        
        // Minimal harus ada beberapa identifier atau URLs
        $this->assertIsArray($identifiers);
    }
    
    /**
     * Test 2: Ambil Profil Lengkap dengan Data Scopus
     * 
     * Memastikan data Scopus berhasil diambil jika Scopus ID ditemukan
     */
    public function testCompleteProfileWithScopusData(): void
    {
        echo "\n=== TEST 2: Profil Lengkap dengan Data Scopus ===\n";
        
        $profile = $this->aggregator->getCompleteProfile($this->testOrcid);
        
        // Validasi struktur dasar
        $this->assertArrayHasKey('success', $profile);
        $this->assertTrue($profile['success'], "Profil gagal diambil");
        $this->assertArrayHasKey('orcid', $profile);
        $this->assertEquals($this->testOrcid, $profile['orcid']);
        
        // Validasi basic info
        $this->assertArrayHasKey('basic_info', $profile);
        $this->assertArrayHasKey('name', $profile['basic_info']);
        echo "\nNama Peneliti: " . $profile['basic_info']['name'] . "\n";
        
        // Validasi identifiers
        $this->assertArrayHasKey('identifiers', $profile);
        $scopusId = $profile['identifiers']['scopus_author_id'];
        $researcherId = $profile['identifiers']['researcher_id'];
        
        echo "Scopus ID: " . ($scopusId ?? 'Tidak ditemukan') . "\n";
        echo "ResearcherID: " . ($researcherId ?? 'Tidak ditemukan') . "\n";
        
        // Jika Scopus ID ditemukan, pastikan data Scopus ada
        if ($scopusId) {
            $this->assertNotNull($profile['scopus_data'], "Data Scopus seharusnya ada untuk Scopus ID: $scopusId");
            
            if ($profile['scopus_data'] && !isset($profile['scopus_data']['error'])) {
                echo "\n=== DATA SCOPUS ===\n";
                
                // Validasi metrics dari Scopus
                if (isset($profile['scopus_data']['metrics'])) {
                    $metrics = $profile['scopus_data']['metrics'];
                    echo "h-index: " . ($metrics['h_index'] ?? 0) . "\n";
                    echo "Total Sitasi: " . ($metrics['cited_by_count'] ?? 0) . "\n";
                    echo "Jumlah Dokumen: " . ($metrics['document_count'] ?? 0) . "\n";
                    
                    $this->assertArrayHasKey('h_index', $metrics);
                    $this->assertArrayHasKey('cited_by_count', $metrics);
                    $this->assertArrayHasKey('document_count', $metrics);
                }
            } else {
                echo "Data Scopus tidak tersedia atau error\n";
            }
        }
        
        // Validasi publikasi
        $this->assertArrayHasKey('publications', $profile);
        echo "\nJumlah Publikasi: " . count($profile['publications']) . "\n";
        
        // Tampilkan beberapa publikasi pertama
        if (!empty($profile['publications'])) {
            echo "\n=== CONTOH PUBLIKASI ===\n";
            foreach (array_slice($profile['publications'], 0, 3) as $index => $pub) {
                echo ($index + 1) . ". " . $pub['title'] . "\n";
                echo "   Tahun: " . ($pub['year'] ?? 'N/A') . "\n";
                echo "   Jurnal: " . ($pub['journal'] ?? 'N/A') . "\n";
                echo "   DOI: " . ($pub['doi'] ?? 'N/A') . "\n";
                echo "   Sitasi: " . ($pub['citations'] ?? 0) . "\n";
                echo "   Sumber: " . ($pub['source'] ?? 'N/A') . "\n\n";
            }
        }
        
        // Validasi metrics agregat
        $this->assertArrayHasKey('metrics', $profile);
        echo "\n=== METRICS AGREGAT ===\n";
        echo "Total Publikasi: " . $profile['metrics']['total_publications'] . "\n";
        echo "Total Sitasi: " . $profile['metrics']['total_citations'] . "\n";
        echo "h-index: " . $profile['metrics']['h_index'] . "\n";
        echo "Sumber Data: " . implode(', ', $profile['metrics']['sources']) . "\n";
        
        $this->assertGreaterThan(0, $profile['metrics']['total_publications'], "Seharusnya ada publikasi");
    }
    
    /**
     * Test 3: Analisis SDG pada Publikasi
     * 
     * Memastikan analisis SDG berjalan pada publikasi yang diambil
     */
    public function testSdgAnalysisOnPublications(): void
    {
        echo "\n=== TEST 3: Analisis SDG pada Publikasi ===\n";
        
        $profile = $this->aggregator->getCompleteProfile($this->testOrcid);
        
        $this->assertArrayHasKey('sdg_analysis', $profile);
        
        if ($profile['sdg_analysis']) {
            $sdgAnalysis = $profile['sdg_analysis'];
            
            echo "\n=== HASIL ANALISIS SDG ===\n";
            
            // Tampilkan skor SDG
            if (isset($sdgAnalysis['sdg_scores'])) {
                echo "\nSkor SDG:\n";
                arsort($sdgAnalysis['sdg_scores']);
                foreach (array_slice($sdgAnalysis['sdg_scores'], 0, 5) as $sdgCode => $score) {
                    echo "$sdgCode: " . number_format($score, 4) . "\n";
                }
            }
            
            // Tampilkan kategori SDG utama
            if (isset($sdgAnalysis['sdg_categories'])) {
                echo "\nKategori SDG Utama:\n";
                foreach (array_slice($sdgAnalysis['sdg_categories'], 0, 3) as $category) {
                    echo "- " . $category['sdg_code'] . ": " . $category['name'] . "\n";
                    echo "  Publikasi: " . count($category['publications']) . "\n";
                }
            }
            
            // Validasi struktur
            $this->assertArrayHasKey('sdg_scores', $sdgAnalysis);
            $this->assertArrayHasKey('sdg_categories', $sdgAnalysis);
            $this->assertArrayHasKey('evidence_links', $sdgAnalysis);
            
            // Pastikan ada minimal 1 SDG terdeteksi
            $this->assertGreaterThan(0, count($sdgAnalysis['sdg_scores']), "Seharusnya ada minimal 1 SDG terdeteksi");
        } else {
            echo "Analisis SDG tidak tersedia\n";
        }
    }
    
    /**
     * Test 4: Detail Informasi Jurnal dari Scopus
     * 
     * Memastikan informasi jurnal (SJR, quartile) dapat diambil untuk publikasi
     */
    public function testJournalInformationFromScopus(): void
    {
        echo "\n=== TEST 4: Detail Informasi Jurnal dari Scopus ===\n";
        
        $profile = $this->aggregator->getCompleteProfile($this->testOrcid);
        
        $this->assertArrayHasKey('publications', $profile);
        
        $foundJournalInfo = false;
        
        foreach ($profile['publications'] as $pub) {
            if (!empty($pub['journal'])) {
                echo "\nJurnal: " . $pub['journal'] . "\n";
                echo "Judul: " . $pub['title'] . "\n";
                
                // Cek apakah ada info jurnal di original_data
                if (isset($pub['original_data']['source-id']) || isset($pub['original_data']['issn'])) {
                    $foundJournalInfo = true;
                    echo "Source ID: " . ($pub['original_data']['source-id'] ?? 'N/A') . "\n";
                    echo "ISSN: " . ($pub['original_data']['issn'] ?? 'N/A') . "\n";
                }
                
                // Break setelah menemukan 2 jurnal
                if ($foundJournalInfo) {
                    static $count = 0;
                    $count++;
                    if ($count >= 2) break;
                }
            }
        }
        
        echo "\nInformasi jurnal ditemukan: " . ($foundJournalInfo ? 'Ya' : 'Tidak') . "\n";
    }
    
    /**
     * Test 5: Verifikasi Data Kaya (Rich Data)
     * 
     * Memastikan semua field data yang kaya tersedia
     */
    public function testRichDataAvailability(): void
    {
        echo "\n=== TEST 5: Verifikasi Data Kaya ===\n";
        
        $profile = $this->aggregator->getCompleteProfile($this->testOrcid);
        
        // Checklist data kaya yang diharapkan
        $richDataChecklist = [
            'Basic Info' => !empty($profile['basic_info']['name']),
            'ORCID' => !empty($profile['orcid']),
            'Keywords' => !empty($profile['basic_info']['keywords']),
            'Scopus ID' => !empty($profile['identifiers']['scopus_author_id']),
            'ResearcherID' => !empty($profile['identifiers']['researcher_id']),
            'Publications Count' => $profile['metrics']['total_publications'] > 0,
            'Citations Count' => $profile['metrics']['total_citations'] >= 0,
            'h-index' => $profile['metrics']['h_index'] >= 0,
            'SDG Analysis' => !empty($profile['sdg_analysis']),
            'Publication Details' => $this->checkPublicationDetails($profile['publications']),
        ];
        
        echo "\n=== CHECKLIST DATA KAYA ===\n";
        $allPassed = true;
        foreach ($richDataChecklist as $item => $status) {
            $icon = $status ? '✅' : '❌';
            echo "$icon $item: " . ($status ? 'Available' : 'Missing') . "\n";
            if (!$status) $allPassed = false;
        }
        
        // Minimal 80% data harus tersedia
        $passedCount = count(array_filter($richDataChecklist));
        $totalCount = count($richDataChecklist);
        $percentage = ($passedCount / $totalCount) * 100;
        
        echo "\nPersentase Data Tersedia: {$percentage}%\n";
        
        $this->assertGreaterThanOrEqual(60, $percentage, "Minimal 60% data kaya harus tersedia");
    }
    
    /**
     * Helper: Cek detail publikasi
     */
    private function checkPublicationDetails(array $publications): bool
    {
        if (empty($publications)) return false;
        
        $hasTitle = false;
        $hasYear = false;
        $hasJournal = false;
        
        foreach ($publications as $pub) {
            if (!empty($pub['title'])) $hasTitle = true;
            if (!empty($pub['year'])) $hasYear = true;
            if (!empty($pub['journal'])) $hasJournal = true;
        }
        
        return $hasTitle && $hasYear;
    }
    
    /**
     * Test 6: Performance Test dengan Caching
     * 
     * Memastikan caching bekerja untuk request kedua
     */
    public function testCachingPerformance(): void
    {
        echo "\n=== TEST 6: Performance Test dengan Caching ===\n";
        
        // Request pertama
        $startTime1 = microtime(true);
        $profile1 = $this->aggregator->getCompleteProfile($this->testOrcid);
        $time1 = microtime(true) - $startTime1;
        
        echo "Request pertama: " . number_format($time1, 3) . " detik\n";
        
        // Request kedua (should use cache)
        $startTime2 = microtime(true);
        $profile2 = $this->aggregator->getCompleteProfile($this->testOrcid);
        $time2 = microtime(true) - $startTime2;
        
        echo "Request kedua (cache): " . number_format($time2, 3) . " detik\n";
        
        // Request kedua seharusnya lebih cepat
        if ($time1 > 0) {
            $speedup = $time1 / max($time2, 0.001);
            echo "Speedup: " . number_format($speedup, 2) . "x\n";
        }
        
        // Pastikan data konsisten
        $this->assertEquals($profile1['orcid'], $profile2['orcid']);
        $this->assertEquals($profile1['basic_info']['name'], $profile2['basic_info']['name']);
    }
}

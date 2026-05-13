<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Wizdam\Services\ResearcherAggregatorService;
use Wizdam\Services\OrcidProfileService;
use Wizdam\Services\ResearcherIdentityService;
use Wizdam\Services\ScopusResearcherService;
use Wizdam\Utils\CacheManager;

echo "=== PENGUJIAN LIVE FETCH (NO CACHE) ===\n";
echo "ORCID: 0000-0002-8223-2387\n\n";

// Hapus cache jika ada
$cache = new CacheManager();
$cache->delete('orcid_profile_0000-0002-8223-2387');
$cache->delete('orcid_works_0000-0002-8223-2387');
echo "[1/5] Cache dibersihkan...\n";

// Step 1: Fetch ORCID Profile Live
echo "[2/5] Fetching ORCID Profile dari API Live...\n";
$orcidService = new OrcidProfileService();
$profileData = $orcidService->getProfile('0000-0002-8223-2387', false); // force refresh

if (!$profileData || isset($profileData['error'])) {
    echo "ERROR: Gagal fetch profile ORCID\n";
    var_dump($profileData);
    exit(1);
}

echo "   ✓ Profile berhasil diambil\n";
echo "   Nama: " . ($profileData['given_name'] . ' ' . $profileData['family_name']) . "\n";

// Step 2: Ekstrak Identifiers
echo "[3/5] Mengekstrak Scopus ID & ResearcherID...\n";
$identityService = new ResearcherIdentityService();
$identifiers = $identityService->extractAllIdentifiers($profileData);

echo "   Scopus Author ID: " . ($identifiers['scopus_author_id'] ?? 'TIDAK DITEMUKAN') . "\n";
echo "   ResearcherID: " . ($identifiers['researcher_id'] ?? 'TIDAK DITEMUKAN') . "\n";
echo "   Publons: " . ($identifiers['publons_id'] ?? 'TIDAK DITEMUKAN') . "\n";
echo "   Google Scholar: " . ($identifiers['google_scholar_id'] ?? 'TIDAK DITEMUKAN') . "\n";

// Step 3: Fetch Scopus Data jika ID ditemukan
$scopusData = null;
if (!empty($identifiers['scopus_author_id'])) {
    echo "[4/5] Fetching data Scopus untuk ID: " . $identifiers['scopus_author_id'] . "...\n";
    $scopusService = new ScopusResearcherService();
    $scopusData = $scopusService->getResearcherProfile($identifiers['scopus_author_id']);
    
    if ($scopusData && !isset($scopusData['error'])) {
        echo "   ✓ Data Scopus berhasil diambil\n";
        echo "   h-index: " . ($scopusData['h_index'] ?? 'N/A') . "\n";
        echo "   Total Sitasi: " . ($scopusData['total_citations'] ?? 'N/A') . "\n";
        echo "   Jumlah Dokumen: " . ($scopusData['document_count'] ?? 'N/A') . "\n";
    } else {
        echo "   ⚠ Data Scopus tidak tersedia (mungkin perlu API Key atau akses terbatas)\n";
        if (isset($scopusData['error'])) {
            echo "   Error: " . $scopusData['error'] . "\n";
        }
    }
} else {
    echo "[4/5] Skipping Scopus fetch (tidak ada Scopus ID)\n";
}

// Step 4: Agregasi Lengkap
echo "[5/5] Menggabungkan semua data...\n";
$aggregator = new ResearcherAggregatorService();
$completeProfile = $aggregator->getCompleteProfile('0000-0002-8223-2387', true); // force refresh

echo "\n=== HASIL LENGKAP ===\n";
echo "Nama Peneliti: " . $completeProfile['basic_info']['full_name'] . "\n";
echo "ORCID: " . $completeProfile['basic_info']['orcid'] . "\n";
echo "Scopus ID: " . ($completeProfile['identifiers']['scopus_author_id'] ?? 'N/A') . "\n";
echo "ResearcherID: " . ($completeProfile['identifiers']['researcher_id'] ?? 'N/A') . "\n";
echo "\nMETRIK:\n";
echo "  Total Publikasi (ORCID): " . $completeProfile['metrics']['total_publications'] . "\n";
echo "  h-index (Scopus): " . ($completeProfile['metrics']['h_index'] ?? 'N/A') . "\n";
echo "  Total Sitasi (Scopus): " . ($completeProfile['metrics']['total_citations'] ?? 'N/A') . "\n";
echo "\nPUBLIKASI TERATAS (5 dari " . count($completeProfile['publications']['all']) . "):\n";

foreach (array_slice($completeProfile['publications']['all'], 0, 5) as $i => $pub) {
    echo "  " . ($i+1) . ". " . $pub['title'] . " (" . $pub['year'] . ")\n";
    if (!empty($pub['doi'])) {
        echo "     DOI: " . $pub['doi'] . "\n";
    }
    if (!empty($pub['journal'])) {
        echo "     Journal: " . $pub['journal'] . "\n";
    }
}

echo "\n=== SELESAI ===\n";

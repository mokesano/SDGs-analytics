<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Wizdam\Services\OrcidProfileService;
use Wizdam\Services\ResearcherIdentityService;
use Wizdam\Services\ScopusResearcherService;

$orcid = '0000-0002-4198-4662';

echo "=== LIVE FETCH TEST FOR ORCID: $orcid ===\n\n";

try {
    // 1. Fetch dari ORCID API (Live) - Force refresh
    echo "[1] Mengambil data dari ORCID Public API (Live)...\n";
    $orcidService = new OrcidProfileService();
    $profileRaw = $orcidService->getProfile($orcid, true); // true = force refresh

    if (!$profileRaw || isset($profileRaw['error'])) {
        echo "ERROR: Gagal mengambil data ORCID.\n";
        if (isset($profileRaw['error'])) {
            print_r($profileRaw['error']);
        }
        exit(1);
    }

    echo "✅ Berhasil mengambil data ORCID.\n";
    echo "   Nama Depan: " . ($profileRaw['given_names'] ?? 'N/A') . "\n";
    echo "   Nama Belakang: " . ($profileRaw['family_name'] ?? 'N/A') . "\n";
    echo "   Nama Lengkap: " . ($profileRaw['name'] ?? 'N/A') . "\n";
    echo "   Jumlah Works di ORCID: " . (isset($profileRaw['works']) ? count($profileRaw['works']) : 0) . "\n";

    // 2. Ekstraksi Identifier
    echo "\n[2] Mengekstrak Scopus ID & ResearcherID...\n";
    $identityService = new ResearcherIdentityService();
    $identifiers = $identityService->extractAllIdentifiers($profileRaw);

    $scopusId = $identityService->getScopusAuthorId($profileRaw);
    $researcherId = $identityService->getResearcherId($profileRaw);

    echo "   Scopus Author ID: " . ($scopusId ?? 'TIDAK DITEMUKAN') . "\n";
    echo "   ResearcherID (WoS): " . ($researcherId ?? 'TIDAK DITEMUKAN') . "\n";

    // 3. Fetch dari Scopus (Jika ID ada)
    if ($scopusId) {
        echo "\n[3] Mengambil data bibliometrik dari Scopus API (ID: $scopusId)...\n";
        
        $scopusApiKey = getenv('SCOPUS_API_KEY');
        $scopusService = new ScopusResearcherService($scopusApiKey ?: null);
        
        // Cek apakah API Key tersedia
        if (!$scopusService->getApiKey()) {
            echo "   ⚠️ PERINGATAN: SCOPUS_API_KEY tidak ditemukan di environment.\n";
            echo "   Tidak dapat mengambil data detail Scopus (h-index, sitasi per artikel).\n";
            echo "   Hanya data ORCID yang dapat ditampilkan.\n";
        } else {
            try {
                $scopusProfile = $scopusService->getResearcherProfile($scopusId);
                if ($scopusProfile && !isset($scopusProfile['api_error'])) {
                    echo "   ✅ Berhasil mengambil data Scopus.\n";
                    echo "   Nama di Scopus: " . ($scopusProfile['name'] ?? 'N/A') . "\n";
                    echo "   h-index: " . ($scopusProfile['metrics']['h_index'] ?? 'N/A') . "\n";
                    echo "   Total Sitasi: " . ($scopusProfile['metrics']['cited_by_count'] ?? 'N/A') . "\n";
                    echo "   Total Dokumen: " . ($scopusProfile['metrics']['document_count'] ?? 'N/A') . "\n";
                    
                    if (!empty($scopusProfile['publications'])) {
                        echo "\n   --- 5 Publikasi Teratas (dari Scopus) ---\n";
                        foreach (array_slice($scopusProfile['publications'], 0, 5) as $i => $pub) {
                            $cite = $pub['cited_by_count'] ?? 0;
                            $title = substr($pub['title'], 0, 60) . (strlen($pub['title']) > 60 ? '...' : '');
                            echo "   " . ($i+1) . ". [Sitasi: $cite] $title\n";
                        }
                    }
                } elseif (isset($scopusProfile['api_error'])) {
                    echo "   ⚠️ PERINGATAN: API Scopus mengembalikan error.\n";
                    echo "   Error: " . $scopusProfile['api_error'] . "\n";
                    echo "   Data peneliti hanya tersedia dari ORCID (tanpa metrik Scopus).\n";
                    echo "   Untuk mendapatkan data lengkap, pastikan API Key Scopus valid.\n";
                } else {
                    echo "   ❌ Gagal mengambil data Scopus: " . ($scopusProfile['message'] ?? 'Unknown error') . "\n";
                }
            } catch (Exception $e) {
                echo "   ❌ Exception saat fetch Scopus: " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "\n[3] Melewati langkah Scopus karena ID tidak ditemukan di profil ORCID.\n";
    }

    echo "\n=== SELESAI ===\n";

} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

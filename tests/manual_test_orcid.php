<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Wizdam\Services\OrcidService;
use Wizdam\Services\SdgClassificationService;

echo "=== UNIT TEST: ORCID 0000-0001-9006-2018 ===\n\n";

// Test 1: OrcidService
echo "[Test 1] Mengambil data dari ORCID API...\n";
$orcidService = new OrcidService();
$profile = $orcidService->getProfile('0000-0001-9006-2018');

if ($profile === null || isset($profile['error'])) {
    echo "❌ FAIL: Gagal mengambil data ORCID\n";
    if (isset($profile['error'])) {
        echo "   Error: " . $profile['error'] . "\n";
    }
    exit(1);
} else {
    echo "✅ PASS: Data ORCID berhasil diambil\n";
    echo "   Nama: " . ($profile['name'] ?? 'N/A') . "\n";
    echo "   Jumlah Works: " . ($profile['works_count'] ?? 0) . "\n";
}

echo "\n";

// Test 2: SDG Classification
echo "[Test 2] Klasifikasi SDG...\n";
if (isset($profile['works']) && count($profile['works']) > 0) {
    $sdgService = new SdgClassificationService();
    
    // Ambil 5 works pertama untuk testing
    $testWorks = array_slice($profile['works'], 0, 5);
    $results = [];
    
    foreach ($testWorks as $work) {
        $title = $work['title'] ?? '';
        
        if ($title !== '') {
            $classification = $sdgService->analyzeText($title, '', []);
            $topSdgs = array_keys($classification['top_sdgs'] ?? []);
            $totalScore = 0;
            foreach ($classification['top_sdgs'] ?? [] as $sdgData) {
                $totalScore += $sdgData['score'] ?? 0;
            }
            
            $results[] = [
                'title' => substr($title, 0, 60),
                'sdgs' => $topSdgs,
                'score' => round($totalScore, 3)
            ];
        }
    }
    
    if (count($results) > 0) {
        echo "✅ PASS: Klasifikasi SDG berjalan\n";
        foreach ($results as $i => $result) {
            echo "   Work " . ($i+1) . ": " . $result['title'] . "...\n";
            echo "      SDGs: " . implode(', ', $result['sdgs']) . " (Score: " . $result['score'] . ")\n";
        }
    } else {
        echo "⚠️ WARNING: Tidak ada work yang bisa diklasifikasi\n";
    }
} else {
    echo "⚠️ WARNING: Tidak ada works untuk diklasifikasi\n";
}

echo "\n=== SELESAI ===\n";

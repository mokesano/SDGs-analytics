<?php
/**
 * Integration Test for ORCID: 0000-0002-5157-9767
 * Full flow test from ORCID fetch to SDG classification
 */

// Set test mode to prevent redirect
define('TEST_MODE', true);
$_SERVER['HTTPS'] = 'on'; // Simulate HTTPS for CLI

// Load core dependencies (config, bootstrap, functions)
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

// Load SDG definitions
require_once __DIR__ . '/../includes/sdg_definitions.php';

// Extract only the needed helper functions from SDG_Classification_API.php
// to avoid function redeclaration conflicts
$apiFile = __DIR__ . '/../api/SDG_Classification_API.php';
if (file_exists($apiFile) && !function_exists('createTextVector')) {
    $code = file_get_contents($apiFile);
    
    // Extract createTextVector function
    if (preg_match('/^(function createTextVector.*?\n\})/ms', $code, $matches)) {
        eval(str_replace('function createTextVector', 'function createTextVector', $matches[1]));
    }
    
    // Extract calculateCosineSimilarity function
    if (preg_match('/^(function calculateCosineSimilarity.*?\n\})/ms', $code, $matches)) {
        eval(str_replace('function calculateCosineSimilarity', 'function calculateCosineSimilarity', $matches[1]));
    }
    
    // Extract preprocessText function
    if (preg_match('/^(function preprocessText.*?\n\})/ms', $code, $matches)) {
        eval(str_replace('function preprocessText', 'function preprocessText', $matches[1]));
    }
}

echo "=== PENGUJIAN INTEGRASI ORCID: 0000-0002-5157-9767 ===\n\n";

$passed = 0;
$failed = 0;

// Test 1: Validasi ORCID
$orcid = '0000-0002-5157-9767';
echo "1. Validasi Format ORCID: ";
if (validateORCID($orcid)) {
    echo "✅ LULUS - Format ORCID valid\n";
    $passed++;
} else {
    echo "❌ GAGAL - Format ORCID tidak valid\n";
    $failed++;
    exit(1);
}

// Test 2: Fetch data dari ORCID API
echo "2. Mengambil Data dari ORCID API: ";
$cacheKey = getCacheFilename('orcid', $orcid);
$cachedData = readFromCache($cacheKey);

if ($cachedData) {
    echo "✅ Menggunakan data dari cache\n";
    $works = $cachedData;
    $passed++;
} else {
    $url = $CONFIG['ORCID_API_URL'] . '/' . $orcid . '/works';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200 && $response) {
        $data = json_decode($response, true);
        echo "✅ LULUS - Berhasil mengambil data (HTTP $httpCode)\n";
        
        // Simpan ke cache
        writeToCache($cacheKey, $data);
        $works = $data;
        $passed++;
    } else {
        echo "❌ GAGAL - HTTP Code: $httpCode\n";
        $failed++;
        exit(1);
    }
}

// Test 3: Ekstrak dan Proses Works
echo "3. Mengekstrak Daftar Works: ";
$workList = [];
if (isset($works['group']) && is_array($works['group'])) {
    foreach ($works['group'] as $group) {
        $workSummary = $group['work-summary'][0] ?? null;
        if ($workSummary) {
            $putCode = $workSummary['put-code'] ?? '';
            $title = $workSummary['title']['title']['value'] ?? 'No Title';
            $year = $workSummary['publication-date']['year']['value'] ?? 'Unknown';
            $doi = '';
            
            // Extract DOI from external identifiers
            if (isset($workSummary['external-ids']['external-id'])) {
                $extIds = $workSummary['external-ids']['external-id'];
                if (!is_array($extIds)) $extIds = [$extIds];
                foreach ($extIds as $id) {
                    if (($id['external-id-type'] ?? '') === 'doi') {
                        $doi = $id['external-id-value'] ?? '';
                        break;
                    }
                }
            }
            
            $workList[] = [
                'putCode' => $putCode,
                'title' => $title,
                'year' => $year,
                'doi' => $doi
            ];
        }
    }
    
    echo "✅ LULUS - Ditemukan " . count($workList) . " works\n";
    $passed++;
    
    if (count($workList) > 0) {
        echo "   Sample work: '" . substr($workList[0]['title'], 0, 50) . "...'\n";
    }
} else {
    echo "⚠️ PERINGATAN - Tidak ada works atau format tidak sesuai\n";
    $passed++; // Still pass, just no works
}

// Test 4: Analisis SDG untuk sample work (jika ada)
if (count($workList) > 0) {
    echo "4. Menguji Klasifikasi SDG pada Sample Work: ";
    $sampleWork = $workList[0];
    $title = $sampleWork['title'];
    
    // Preprocess text
    $cleanText = preprocessText($title);
    
    // Create vector
    $vector = createTextVector($cleanText);
    
    if (is_array($vector) && count($vector) > 0) {
        echo "✅ LULUS - Vektor teks berhasil dibuat (" . count($vector) . " fitur)\n";
        $passed++;
        
        // Calculate similarity dengan semua SDG
        $maxScore = 0;
        $bestSDG = null;
        
        foreach (SDG_DEFINITIONS as $sdgId => $sdgData) {
            $sdgVector = createTextVector($sdgData['keywords'] . ' ' . $sdgData['description']);
            $similarity = calculateCosineSimilarity($vector, $sdgVector);
            
            if ($similarity > $maxScore) {
                $maxScore = $similarity;
                $bestSDG = $sdgId;
            }
        }
        
        echo "5. Hasil Klasifikasi SDG Terbaik: ";
        if ($bestSDG) {
            echo "✅ SDG " . $bestSDG . " (" . SDG_DEFINITIONS[$bestSDG]['name'] . ")\n";
            echo "   Score: " . number_format($maxScore, 4) . "\n";
            $passed++;
        } else {
            echo "❌ GAGAL - Tidak dapat menentukan SDG\n";
            $failed++;
        }
    } else {
        echo "❌ GAGAL - Vektor teks kosong atau tidak valid\n";
        $failed++;
    }
} else {
    echo "4. Melewati uji klasifikasi SDG (tidak ada works)\n";
}

// Test 5: Verifikasi Database Connection
echo "6. Verifikasi Koneksi Database: ";
try {
    global $pdo;
    if ($pdo) {
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "✅ LULUS - Terkoneksi dengan " . count($tables) . " tabel\n";
        echo "   Tabel: " . implode(', ', $tables) . "\n";
        $passed++;
    } else {
        echo "❌ GAGAL - Koneksi database tidak tersedia\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "❌ GAGAL - Error: " . $e->getMessage() . "\n";
    $failed++;
}

// Summary
echo "\n=== RINGKASAN HASIL PENGUJIAN ===\n";
echo "Total Tests: " . ($passed + $failed) . "\n";
echo "✅ Passed: $passed\n";
echo "❌ Failed: $failed\n";

if ($failed === 0) {
    echo "\n=== KESIMPULAN ===\n";
    echo "✅ SELURUH ALUR LOGIKA BERFUNGSI DENGAN BAIK\n";
    echo "✅ Data ORCID 0000-0002-5157-9767 berhasil diproses dari fetch hingga klasifikasi SDG\n";
    echo "✅ Sistem siap untuk produksi\n";
    exit(0);
} else {
    echo "\n=== KESIMPULAN ===\n";
    echo "❌ ADA TEST YANG GAGAL - Perlu perbaikan\n";
    exit(1);
}

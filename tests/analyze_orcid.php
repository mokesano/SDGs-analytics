<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Wizdam\Services\OrcidService;
use Wizdam\Services\SdgClassificationService;
use Wizdam\Services\SdgDefinitionsService;

$orcidService = new OrcidService();
$sdgClassificationService = new SdgClassificationService();
$sdgDefinitionsService = new SdgDefinitionsService();

$orcid = '0000-0001-9006-2018';

echo "=== DATA PROFIL ORCID ===" . PHP_EOL;
$profile = $orcidService->getProfile($orcid);
echo "Nama Lengkap: " . ($profile['full_name'] ?? 'N/A') . PHP_EOL;
echo "Given Names: " . ($profile['given_names'] ?? 'N/A') . PHP_EOL;
echo "Family Name: " . ($profile['family_name'] ?? 'N/A') . PHP_EOL;
echo "Keywords: " . (is_array($profile['keywords']) && count($profile['keywords']) > 0 ? implode(', ', $profile['keywords']) : 'N/A') . PHP_EOL;
echo PHP_EOL;

$works = $orcidService->getWorks($orcid, 50);
echo "=== PUBLIKASI ===" . PHP_EOL;
echo "Jumlah Publikasi: " . count($works) . PHP_EOL;

if (count($works) > 0) {
    echo PHP_EOL . "Contoh 3 Publikasi Pertama:" . PHP_EOL;
    foreach (array_slice($works, 0, 3) as $i => $work) {
        echo ($i+1) . '. ' . ($work['title'] ?? 'N/A') . PHP_EOL;
        echo '   Tahun: ' . ($work['year'] ?? 'N/A') . PHP_EOL;
        echo '   DOI: ' . ($work['doi'] ?? 'N/A') . PHP_EOL;
        echo '   Journal: ' . ($work['journal'] ?? 'N/A') . PHP_EOL;
        echo PHP_EOL;
    }
}

echo PHP_EOL . "=== ANALISIS SDG ===" . PHP_EOL;
echo "Jumlah Publikasi Dianalisis: " . min(10, count($works)) . PHP_EOL . PHP_EOL;

// Analisis top 10 publikasi
$sdgScores = [];

foreach ($works as $index => $work) {
    if ($index >= 10) break;
    
    $title = $work['title'] ?? '';
    if (empty(trim($title))) continue;
    
    $analysis = $sdgClassificationService->analyzeText($title, '', []);
    
    foreach ($analysis['sdg_matches'] ?? [] as $sdgCode => $sdgData) {
        if (!isset($sdgScores[$sdgCode])) {
            $sdgScores[$sdgCode] = ['total_score' => 0, 'count' => 0, 'keywords' => []];
        }
        $sdgScores[$sdgCode]['total_score'] += $sdgData['score'];
        $sdgScores[$sdgCode]['count']++;
        $sdgScores[$sdgCode]['keywords'] = array_merge($sdgScores[$sdgCode]['keywords'], $sdgData['matched_keywords'] ?? []);
    }
}

// Hitung rata-rata
$averageScores = [];
foreach ($sdgScores as $sdgCode => $data) {
    if ($data['count'] > 0) {
        $averageScores[$sdgCode] = [
            'average_score' => $data['total_score'] / $data['count'],
            'work_count' => $data['count'],
            'keywords' => array_values(array_unique($data['keywords']))
        ];
    }
}
arsort($averageScores);

echo "DOMINASI SDG:" . PHP_EOL;
$topSdg = null;
$topScore = 0;

foreach ($averageScores as $sdgCode => $data) {
    $sdgInfo = $sdgDefinitionsService->getDefinition($sdgCode);
    $sdgName = $sdgInfo['name'] ?? $sdgCode;
    printf('  %-8s (%s): Score %.3f (dari %d publikasi)' . PHP_EOL, 
        $sdgCode, $sdgName, $data['average_score'], $data['work_count']);
    
    if ($data['average_score'] > $topScore) {
        $topScore = $data['average_score'];
        $topSdg = ['code' => $sdgCode, 'name' => $sdgName, 'score' => $data['average_score'], 'keywords' => $data['keywords']];
    }
}

if ($topSdg !== null) {
    echo PHP_EOL . "[Dominasi SDG: {$topSdg['code']} - {$topSdg['name']} (Score: " . number_format($topScore, 3) . ")]" . PHP_EOL;
    
    // Cari bukti kontribusi
    foreach ($works as $work) {
        $title = $work['title'] ?? '';
        if (empty(trim($title))) continue;
        
        $analysis = $sdgClassificationService->analyzeText($title, '', []);
        if (isset($analysis['sdg_matches'][$topSdg['code']])) {
            echo PHP_EOL . "[BUKTI KONTRIBUSI]" . PHP_EOL;
            echo "Judul Paper: " . $title . PHP_EOL;
            echo "Tahun: " . ($work['year'] ?? 'N/A') . PHP_EOL;
            echo "DOI: " . ($work['doi'] ?? 'N/A') . PHP_EOL;
            echo "Keywords Match: " . implode(', ', array_slice($analysis['sdg_matches'][$topSdg['code']]['matched_keywords'], 0, 5)) . PHP_EOL;
            break;
        }
    }
}

echo PHP_EOL . "=== RINGKASAN DATA NYATA ===" . PHP_EOL;
echo "- Nama Peneliti: " . ($profile['full_name'] ?? 'N/A') . PHP_EOL;
echo "- Jumlah Publikasi Teranalisis: " . count($works) . PHP_EOL;
echo "- Dominasi SDG: " . ($topSdg['code'] ?? 'N/A') . " - " . ($topSdg['name'] ?? 'N/A') . " (Score: " . ($topSdg !== null ? number_format($topScore, 3) : 'N/A') . ")" . PHP_EOL;
if ($topSdg !== null) {
    echo "- Bukti Kontribusi: Lihat paper di atas yang match dengan SDG tersebut" . PHP_EOL;
}

<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$cacheFile = '/workspace/cache/orcid_profile_0000-0001-9006-2018.json';
if (file_exists($cacheFile)) {
    $content = file_get_contents($cacheFile);
    $data = json_decode(gzuncompress($content) ?: $content, true);
    
    echo "=== DATA LENGKAP ORCID 0000-0001-9006-2018 ===\n\n";
    echo "Nama: " . ($data['full_name'] ?: 'N/A') . "\n";
    echo "Jumlah Works: " . ($data['works_count'] ?? 0) . "\n\n";
    
    if (count($data['works'] ?? []) > 0) {
        echo "5 WORKS PERTAMA:\n";
        foreach (array_slice($data['works'], 0, 5) as $i => $work) {
            echo ($i+1) . ". " . ($work['title'] ?? 'No title') . "\n";
            echo "   Tahun: " . ($work['year'] ?? 'N/A') . "\n";
            echo "   DOI: " . ($work['doi'] ?: 'N/A') . "\n\n";
        }
        
        // Analisis SDG untuk semua works
        echo "=== ANALISIS SDG ===\n";
        $sdgService = new \Wizdam\Services\SdgClassificationService();
        $allWorksAnalysis = $sdgService->classifyWorks('0000-0001-9006-2018', $data['works']);
        
        echo "Total Works Dianalisis: " . $allWorksAnalysis['works_analyzed'] . "\n\n";
        
        if (count($allWorksAnalysis['top_sdgs'] ?? []) > 0) {
            echo "TOP SDGs:\n";
            foreach ($allWorksAnalysis['top_sdgs'] as $sdgCode => $sdgData) {
                $sdgInfo = $sdgService->getSdgInfo($sdgCode);
                echo "- {$sdgCode}: {$sdgInfo['title']}\n";
                echo "  Score: " . round($sdgData['average_score'], 3) . "\n";
                echo "  Works: {$sdgData['work_count']}\n\n";
            }
        }
        
        // Cari work dengan score tertinggi
        echo "=== WORK DENGAN KONTRIBUSI TERTINGGI ===\n";
        $bestWork = null;
        $bestScore = 0;
        
        foreach ($allWorksAnalysis['work_results'] as $result) {
            $totalScore = 0;
            foreach ($result['analysis']['sdg_matches'] ?? [] as $sdgData) {
                $totalScore += $sdgData['score'];
            }
            
            if ($totalScore > $bestScore) {
                $bestScore = $totalScore;
                $bestWork = $result;
            }
        }
        
        if ($bestWork !== null) {
            echo "Judul: " . $bestWork['title'] . "\n";
            echo "Total Score: " . round($bestScore, 3) . "\n";
            echo "SDG Match:\n";
            foreach ($bestWork['analysis']['top_sdgs'] as $sdgCode => $sdgData) {
                echo "  - {$sdgCode}: " . round($sdgData['score'], 3) . "\n";
            }
        }
    }
} else {
    echo 'Cache file not found. Run test first.';
}

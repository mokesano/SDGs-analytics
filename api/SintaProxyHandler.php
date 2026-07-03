<?php
declare(strict_types=1);

/**
 * @file SintaProxyHandler.php
 * @brief Script untuk mengakses Sinta Impact dengan caching (Versi Final)
 */

// -----------------------------------------------------------------
// BAGIAN MONITORING (UP/DOWN)
// -----------------------------------------------------------------
if (empty($_GET)) {
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'up', 
        'message' => 'Endpoint is operational', 
        'version' => 'v1.0.0'
        ]);
    exit;
}

// Batas kode tanpa batasan akses, Lanjutan kode handler yang sudah ada
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Debug mode setting
define('DEBUG_MODE', false); // Set ke true jika diperlukan, false untuk production

// Cache settings
define('CACHE_ENABLED', true);
define('CACHE_DIRECTORY', __DIR__ . '/cache');
define('CACHE_DURATION', 7 * 24 * 60 * 60); // 7 days in seconds
define('DEBUG_DIRECTORY', __DIR__ . '/debug');

// Create required directories
if (CACHE_ENABLED && !file_exists(CACHE_DIRECTORY)) {
    mkdir(CACHE_DIRECTORY, 0755, true);
}

if (DEBUG_MODE && !file_exists(DEBUG_DIRECTORY)) {
    mkdir(DEBUG_DIRECTORY, 0755, true);
}

// Clean old debug files if in debug mode
if (DEBUG_MODE) {
    cleanOldDebugFiles();
}

// Get and validate ISSN
$issn = isset($_GET['issn']) ? trim($_GET['issn']) : '';
$normalizedIssn = preg_replace('/[^0-9X]/', '', strtoupper($issn));
$issnWithDash = substr($normalizedIssn, 0, 4) . '-' . substr($normalizedIssn, 4, 4);

// Validate ISSN format
if (empty($normalizedIssn) || !preg_match('/^\d{7}[\dX]$/', $normalizedIssn)) {
    outputJsonResponse([
        'success' => false,
        'error' => 'ISSN tidak valid. Format yang benar: 1234-5678 atau 12345678'
    ]);
    exit;
}

// Check cache first
if (CACHE_ENABLED) {
    $cacheFile = CACHE_DIRECTORY . '/issn_' . md5($normalizedIssn) . '.json';
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < CACHE_DURATION)) {
        // Load from cache but format impact correctly
        $cachedData = json_decode(file_get_contents($cacheFile), true);
        if (isset($cachedData['impact'])) {
            $cachedData['impact'] = number_format((float)$cachedData['impact'], 3, '.', '');
        }
        echo json_encode($cachedData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

// Attempt to get journal info
try {
    // Increase execution time limit
    set_time_limit(120);
    
    // Try without dash first, then with dash if needed
    $result = findJournalInfo($normalizedIssn);
    if (!$result['success']) {
        $result = findJournalInfo($issnWithDash);
    }
    
    // Save to cache if successful
    if ($result['success'] && CACHE_ENABLED) {
        $jsonResult = json_encode($result);
        file_put_contents(CACHE_DIRECTORY . '/issn_' . md5($normalizedIssn) . '.json', $jsonResult);
    }
    
    outputJsonResponse($result);
} catch (Exception $e) {
    outputJsonResponse([
        'success' => false,
        'issn' => $issnWithDash,
        'error' => $e->getMessage()
    ]);
}

/**
 * Find journal info by ISSN
 */
function findJournalInfo($issn) {
    // Step 1: Search for the journal using the ISSN
    $searchUrl = "https://sinta.kemdikbud.go.id/journals?q=" . urlencode($issn);
    $searchHtml = fetchWithRetry($searchUrl);
    
    // Save search results for debugging
    if (DEBUG_MODE) {
        saveDebugFile('search_' . $issn . '.html', $searchHtml);
    }
    
    // Get journal name from search results first (this is often more reliable)
    $journalNameFromSearch = '';
    if (preg_match('/<div[^>]*class="(?:affil-name|journal-list-name)[^"]*"[^>]*>(?:<a[^>]*>)?([^<]+)(?:<\/a>)?/is', $searchHtml, $nameMatches)) {
        $journalNameFromSearch = trim(preg_replace('/<i[^>]*>.*?<\/i>/i', '', $nameMatches[1]));
    }
    
    // Get grade from search results (more reliable sometimes)
    $gradeFromSearch = null;
    if (preg_match('/Accredited<\/span><\/a><\/span>.*?<i class="el el-certificate"><\/i>\s*S(\d+)\s*<span/is', $searchHtml, $gradeMatches) || 
        preg_match('/class="[^"]*num-stat accredited[^"]*"><a[^>]*><i[^>]*><\/i>\s*S(\d+)\s*<span/is', $searchHtml, $gradeMatches)) {
        $gradeFromSearch = $gradeMatches[1];
    }
    
    // Step 2: Extract journal ID from search results
    if (!preg_match('/<a[^>]*href=["\'](?:https:\/\/sinta\.kemdikbud\.go\.id)?\/journals\/profile\/(\d+)["\'][^>]*>/i', $searchHtml, $matches)) {
        return [
            'success' => false,
            'issn' => formatIssn($issn),
            'error' => 'Jurnal tidak ditemukan di SINTA'
        ];
    }
    
    $journalId = $matches[1];
    
    // Step 3: Fetch the journal profile page
    $profileUrl = "https://sinta.kemdikbud.go.id/journals/profile/$journalId";
    $profileHtml = fetchWithRetry($profileUrl);
    
    // Save profile page for debugging
    if (DEBUG_MODE) {
        saveDebugFile('profile_' . $journalId . '.html', $profileHtml);
    }
    
    // Step 4: Extract data from the profile page
    $data = [
        'success' => true,
        'issn' => formatIssn($issn),
        'sinta_id' => $journalId,
        'sinta_url' => $profileUrl
    ];
    
    // Enhanced title extraction with multiple patterns
    $journalTitle = '';
    
    // Try all these patterns to find the journal title
    $titlePatterns = [
        // Common patterns in the profile page
        '/<div[^>]*class=["\']profile-name["\'][^>]*>([^<]+)<\/div>/is',
        '/<div[^>]*class=["\']affil-name["\'][^>]*>(?:<a[^>]*>)?([^<]+)(?:<\/a>)?/is',
        '/<h1[^>]*>([^<]+)<\/h1>/is',
        '/<h2[^>]*>([^<]+)<\/h2>/is',
        
        // Look for title in metadata
        '/<meta\s+name=["\']citation_title["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/is',
        '/<meta\s+property=["\']og:title["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/is',
        
        // Sometimes the title is in a breadcrumb
        '/<li[^>]*class=["\']active["\'][^>]*>([^<]+)<\/li>/is',
        
        // Sometimes it's in the title tag
        '/<title>([^<|]+)(?:\s*\|[^<]*)?<\/title>/is'
    ];
    
    // Try each pattern until we find a title
    foreach ($titlePatterns as $pattern) {
        if (preg_match($pattern, $profileHtml, $titleMatches)) {
            $potentialTitle = trim($titleMatches[1]);
            // Make sure it's not just "SINTA" or similar
            if ($potentialTitle && 
                $potentialTitle != "SINTA" && 
                $potentialTitle != "SINTA - Science and Technology Index" &&
                strlen($potentialTitle) > 5) {
                    
                $journalTitle = $potentialTitle;
                break;
            }
        }
    }
    
    // If no title found in profile, use the one from search results
    if (empty($journalTitle) && !empty($journalNameFromSearch)) {
        $journalTitle = $journalNameFromSearch;
    }
    
    // Last resort - check if the journal title might be in a specific section
    if (empty($journalTitle)) {
        // Sometimes the title is on a specific div structure
        if (preg_match('/<div[^>]*class=["\']col-md["\'][^>]*>.*?<div[^>]*>([^<]+)<\/div>.*?<div[^>]*class=["\']affil-abbrev["\'][^>]*/is', $profileHtml, $matches)) {
            $journalTitle = trim($matches[1]);
        }
    }
    
    // Set the title (with fallback if nothing found)
    $data['title'] = !empty($journalTitle) ? $journalTitle : "Journal #$journalId";
    
    // Extract impact factor - FORMATTED WITH 3 DECIMAL PLACES
    $impact = 0.000;
    if (preg_match('/<div[^>]*class=["\'](?:stat-num|pr-num)["\'][^>]*>([\d\.,]+)<\/div>\s*<div[^>]*class=["\'](?:stat-text|pr-txt)["\'][^>]*>\s*Impact\s*<\/div>/is', $profileHtml, $impactMatches)) {
        $impactValue = str_replace(',', '.', $impactMatches[1]);
        $impact = (float)$impactValue;
    }
    
    // Format the impact factor to always have 3 decimal places
    $data['impact'] = number_format($impact, 3, '.', '');
    
    // Enhanced Sinta grade extraction with multiple patterns
    $grade = null;
    
    // Use grade from search results if found
    if ($gradeFromSearch !== null) {
        $grade = $gradeFromSearch;
    } else {
        // Try multiple patterns to extract grade from profile page
        $gradePatterns = [
            // From class attributes
            '/class="[^"]*num-stat accredited[^"]*"><a[^>]*><i[^>]*><\/i>\s*S(\d+)\s*<span/is',
            '/S(\d+)\s*<span[^>]*>Accredited<\/span>/is',
            
            // From specific sections about accreditation 
            '/Accreditation.*?S(?:INTA)?\s*(\d+)/is',
            '/Current\s+Accreditation.*?S(?:inta)?\s*(\d+)/i',
            
            // From div structure
            '/<div[^>]*>S(\d+)<\/div>/i',
            
            // Look at spans near "accredited" text
            '/<span[^>]*>S(\d+)<\/span>.*?Accredited/is',
            '/Accredited.*?<span[^>]*>S(\d+)<\/span>/is',
            
            // In list items
            '/<li[^>]*>.*?(?:Accreditation|Sinta).*?S?(\d+).*?<\/li>/is'
        ];
        
        foreach ($gradePatterns as $pattern) {
            if (preg_match($pattern, $profileHtml, $gradeMatches)) {
                $grade = $gradeMatches[1];
                break;
            }
        }
        
        // Last attempt - direct text search
        if ($grade === null) {
            $sinta_grades = ['1', '2', '3', '4', '5', '6'];
            foreach ($sinta_grades as $potential_grade) {
                if (strpos($profileHtml, "Sinta $potential_grade") !== false || 
                    strpos($profileHtml, "SINTA $potential_grade") !== false || 
                    strpos($profileHtml, "S$potential_grade Accredited") !== false) {
                    $grade = $potential_grade;
                    break;
                }
            }
        }
    }
    
    // Add grade to result if found
    if ($grade !== null) {
        $data['grade'] = $grade;
    }
    
    // Extract ISSN details
    if (preg_match('/E-ISSN\s*:\s*([^\s|<]+)/is', $profileHtml, $eissnMatches)) {
        $data['e_issn'] = trim(preg_replace('/[^0-9X-]/', '', $eissnMatches[1]));
    }
    
    if (preg_match('/P-ISSN\s*:\s*([^\s|<]+)/is', $profileHtml, $pissnMatches)) {
        $data['p_issn'] = trim(preg_replace('/[^0-9X-]/', '', $pissnMatches[1]));
    }
    
    // Extract publisher
    if (preg_match('/<i class="el el-address-book[^>]*><\/i>\s*([^<]+)<\/a>/is', $profileHtml, $publisherMatches)) {
        $data['publisher'] = trim($publisherMatches[1]);
    }
    
    return $data;
}

/**
 * Format ISSN with dash
 */
function formatIssn($issn) {
    $issn = preg_replace('/[^0-9X]/', '', strtoupper($issn));
    if (strlen($issn) == 8) {
        return substr($issn, 0, 4) . '-' . substr($issn, 4, 4);
    }
    return $issn;
}

/**
 * Save debug file
 */
function saveDebugFile($filename, $content) {
    if (!DEBUG_MODE) return;
    
    $filepath = DEBUG_DIRECTORY . '/' . $filename;
    file_put_contents($filepath, $content);
}

/**
 * Clean old debug files (keep only files from the last 24 hours)
 */
function cleanOldDebugFiles($maxAge = 86400) { // 24 hours in seconds
    if (!DEBUG_MODE || !is_dir(DEBUG_DIRECTORY)) return;
    
    $now = time();
    $files = glob(DEBUG_DIRECTORY . '/*.html');
    
    foreach ($files as $file) {
        $fileAge = $now - filemtime($file);
        if ($fileAge > $maxAge) {
            @unlink($file);
        }
    }
}

/**
 * Fetch URL with retry
 */
function fetchWithRetry($url, $maxAttempts = 3, $timeout = 30) {
    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9,id;q=0.8',
                'Cache-Control: no-cache'
            ]
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response && $statusCode >= 200 && $statusCode < 300) {
            return $response;
        }
        
        // Wait before retrying (longer with each attempt)
        if ($attempt < $maxAttempts) {
            sleep($attempt * 2);
        }
    }
    
    throw new Exception("Gagal mengakses $url setelah $maxAttempts percobaan. Status: $statusCode, Error: $error");
}

/**
 * Output JSON response
 */
function outputJsonResponse($data) {
    // Ensure impact is formatted with 3 decimal places if it exists
    if (isset($data['impact'])) {
        $data['impact'] = number_format((float)$data['impact'], 3, '.', '');
    }
    
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>
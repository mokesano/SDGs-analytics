<?php
declare(strict_types=1);

/**
 * cURL Sinta Score dan Sinta Grade di Sinta Kemdikti Saintek Indonesia
 * @file SintaScoreNEW.php

 * @brief Script Sinta Impact dengan Smart Detection (Efficient Version)
 * Menggunakan prinsip 1 file cache, hash-based detection, weekly expiry
 * 
 * @brief Script untuk mengakses Sinta Impact dengan caching (Versi Final)
 * @author Rochmady and Wizdam Team
 * Last update 2025-06-12
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

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Debug mode setting
define('DEBUG_MODE', false);

// Cache settings - EFFICIENT APPROACH
define('CACHE_ENABLED', true);
define('CACHE_DIRECTORY', __DIR__ . '/cache');
define('WEEKLY_CACHE_DURATION', 604800); // 7 days in seconds
define('DEBUG_DIRECTORY', __DIR__ . '/debug');

// Smart detection thresholds
define('HIGH_ACCESS_CACHE_DURATION', 259200); // 3 days for frequently accessed
define('MEDIUM_ACCESS_CACHE_DURATION', 432000); // 5 days for medium access
define('FORCE_UPDATE_THRESHOLD', 2592000); // 30 days maximum

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

// Handle update endpoint
if (isset($_GET['action']) && $_GET['action'] === 'update') {
    handleUpdateRequest();
    exit;
}

// Handle status endpoint
if (isset($_GET['action']) && $_GET['action'] === 'status') {
    handleStatusRequest();
    exit;
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

// Cache file with compression (following most_popular.php pattern)
$cacheFile = CACHE_DIRECTORY . '/sinta_' . md5($normalizedIssn) . '.json.gz';
$forceUpdate = isset($_GET['force_update']) && $_GET['force_update'] === 'true';

// Check cache with smart detection (EFFICIENT METHOD)
if (CACHE_ENABLED && !$forceUpdate && file_exists($cacheFile)) {
    $cachedData = loadFromCache($cacheFile);
    
    if ($cachedData !== false && isCacheValid($cacheFile, $cachedData, $normalizedIssn)) {
        // Update access count for smart caching
        updateAccessCount($normalizedIssn, $cachedData);
        
        // Add cache info
        $cachedData['cache_info'] = [
            'from_cache' => true,
            'cache_age_hours' => round((time() - filemtime($cacheFile)) / 3600, 1),
            'last_updated' => $cachedData['meta']['last_update'],
            'access_count' => getAccessCount($cachedData),
            'expires_at' => date('Y-m-d H:i:s', filemtime($cacheFile) + getCacheDuration($cachedData))
        ];
        
        // Save updated access count back to cache
        saveToCache($cacheFile, $cachedData);
        
        outputJsonResponse($cachedData);
        exit;
    }
}

// Fetch fresh data if cache invalid or not exists
try {
    set_time_limit(120);
    
    // Try without dash first, then with dash if needed
    $result = findJournalInfo($normalizedIssn);
    if (!$result['success']) {
        $result = findJournalInfo($issnWithDash);
    }
    
    if ($result['success'] && CACHE_ENABLED) {
        // Prepare data with smart detection hash
        $currentTime = time();
        $fullData = [
            'success' => true,
            'issn' => $result['issn'],
            'title' => $result['title'],
            'impact' => $result['impact'],
            'grade' => isset($result['grade']) ? $result['grade'] : null,
            'sinta_id' => $result['sinta_id'],
            'sinta_url' => $result['sinta_url'],
            'e_issn' => isset($result['e_issn']) ? $result['e_issn'] : null,
            'p_issn' => isset($result['p_issn']) ? $result['p_issn'] : null,
            'publisher' => isset($result['publisher']) ? $result['publisher'] : null,
            
            // Smart detection metadata
            'data_hash' => generateDataHash($result),
            'generated_at' => $currentTime,
            'access_count' => 1,
            'access_history' => [date('Y-W') => 1], // Weekly access tracking
            
            'meta' => [
                'last_update' => date('Y-m-d H:i:s'),
                'cache_version' => '2.1-smart-efficient',
                'update_method' => 'smart_detection'
            ]
        ];
        
        // Check for changes if cache existed
        if (file_exists($cacheFile)) {
            $oldData = loadFromCache($cacheFile);
            if ($oldData !== false) {
                $hasChanges = detectDataChanges($oldData, $fullData);
                if ($hasChanges) {
                    error_log("Sinta smart detection: Data changed for ISSN $normalizedIssn");
                }
                
                // Preserve access history
                if (isset($oldData['access_history'])) {
                    $fullData['access_history'] = mergeAccessHistory($oldData['access_history']);
                }
                if (isset($oldData['access_count'])) {
                    $fullData['access_count'] = $oldData['access_count'] + 1;
                }
            }
        }
        
        // Save to cache
        saveToCache($cacheFile, $fullData);
        
        // Add cache info for response
        $fullData['cache_info'] = [
            'from_cache' => false,
            'updated_now' => true,
            'access_count' => $fullData['access_count'],
            'expires_at' => date('Y-m-d H:i:s', $currentTime + getCacheDuration($fullData))
        ];
        
        outputJsonResponse($fullData);
    } else {
        outputJsonResponse($result);
    }
    
} catch (Exception $e) {
    outputJsonResponse([
        'success' => false,
        'issn' => $issnWithDash,
        'error' => $e->getMessage()
    ]);
}

/**
 * Generate hash for data comparison (following most_popular.php pattern)
 */
function generateDataHash($data) {
    $hashData = [
        'impact' => isset($data['impact']) ? $data['impact'] : '0.000',
        'grade' => isset($data['grade']) ? $data['grade'] : null,
        'title' => isset($data['title']) ? $data['title'] : ''
    ];
    return md5(serialize($hashData));
}

/**
 * Check if cache is valid using smart detection
 */
function isCacheValid($cacheFile, $cachedData, $issn) {
    if (!$cachedData || !isset($cachedData['data_hash'])) {
        return false;
    }
    
    // Check age-based expiry with smart duration
    $cacheAge = time() - filemtime($cacheFile);
    $cacheDuration = getCacheDuration($cachedData);
    
    if ($cacheAge >= $cacheDuration) {
        error_log("Sinta cache expired for ISSN $issn (age: " . round($cacheAge/3600, 1) . " hours)");
        return false;
    }
    
    return true;
}

/**
 * Get dynamic cache duration based on access pattern
 */
function getCacheDuration($data) {
    $accessCount = getAccessCount($data);
    
    if ($accessCount >= 10) {
        return HIGH_ACCESS_CACHE_DURATION; // 3 days
    } elseif ($accessCount >= 5) {
        return MEDIUM_ACCESS_CACHE_DURATION; // 5 days
    }
    
    return WEEKLY_CACHE_DURATION; // 7 days
}

/**
 * Get access count from cache data
 */
function getAccessCount($data) {
    if (!isset($data['access_count'])) {
        return 1;
    }
    return (int)$data['access_count'];
}

/**
 * Update access count efficiently
 */
function updateAccessCount($issn, &$data) {
    $currentWeek = date('Y-W');
    
    if (!isset($data['access_history'])) {
        $data['access_history'] = [];
    }
    
    if (!isset($data['access_history'][$currentWeek])) {
        $data['access_history'][$currentWeek] = 0;
    }
    
    $data['access_history'][$currentWeek]++;
    
    // Calculate total access count from recent weeks (last 4 weeks)
    $totalAccess = 0;
    $currentWeekNum = (int)date('W');
    $currentYear = (int)date('Y');
    
    foreach ($data['access_history'] as $week => $count) {
        list($year, $weekNum) = explode('-', $week);
        $weekDiff = ($currentYear - (int)$year) * 52 + ($currentWeekNum - (int)$weekNum);
        
        if ($weekDiff <= 4) { // Keep last 4 weeks
            $totalAccess += $count;
        }
    }
    
    $data['access_count'] = $totalAccess;
    
    // Clean old weeks
    $data['access_history'] = array_filter($data['access_history'], function($week) use ($currentYear, $currentWeekNum) {
        list($year, $weekNum) = explode('-', $week);
        $weekDiff = ($currentYear - (int)$year) * 52 + ($currentWeekNum - (int)$weekNum);
        return $weekDiff <= 4;
    }, ARRAY_FILTER_USE_KEY);
}

/**
 * Merge access history from old data
 */
function mergeAccessHistory($oldHistory) {
    $currentWeek = date('Y-W');
    $newHistory = $oldHistory;
    
    if (!isset($newHistory[$currentWeek])) {
        $newHistory[$currentWeek] = 0;
    }
    
    $newHistory[$currentWeek]++;
    
    return $newHistory;
}

/**
 * Detect changes using hash comparison
 */
function detectDataChanges($oldData, $newData) {
    if (!isset($oldData['data_hash'])) {
        return true;
    }
    
    $oldHash = $oldData['data_hash'];
    $newHash = generateDataHash($newData);
    
    return $oldHash !== $newHash;
}

/**
 * Load data from compressed cache (following most_popular.php pattern)
 */
function loadFromCache($cacheFile) {
    if (!file_exists($cacheFile)) {
        return false;
    }
    
    $compressedContent = file_get_contents($cacheFile);
    if ($compressedContent === false) {
        return false;
    }
    
    $content = gzuncompress($compressedContent);
    if ($content === false) {
        return false;
    }
    
    $data = json_decode($content, true);
    return $data !== null ? $data : false;
}

/**
 * Save data to compressed cache
 */
function saveToCache($cacheFile, $data) {
    $dir = dirname($cacheFile);
    
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    try {
        $content = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        if ($content === false) {
            error_log("Sinta JSON encode failed: " . json_last_error_msg());
            return false;
        }
        
        $compressedContent = gzcompress($content, 9);
        
        if ($compressedContent === false) {
            error_log("Sinta GZIP compression failed");
            return false;
        }
        
        $result = file_put_contents($cacheFile, $compressedContent);
        
        if ($result === false) {
            error_log("Failed to write Sinta cache file: " . $cacheFile);
            return false;
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Exception while saving Sinta cache: " . $e->getMessage());
        return false;
    }
}

/**
 * Handle update request endpoint
 */
function handleUpdateRequest() {
    $issn = isset($_GET['issn']) ? trim($_GET['issn']) : '';
    
    if (empty($issn)) {
        // Run batch update for all cached files
        runBatchUpdate();
        outputJsonResponse([
            'success' => true,
            'message' => 'Batch update completed',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        return;
    }
    
    // Update specific ISSN
    $normalizedIssn = preg_replace('/[^0-9X]/', '', strtoupper($issn));
    
    if (!preg_match('/^\d{7}[\dX]$/', $normalizedIssn)) {
        outputJsonResponse([
            'success' => false,
            'error' => 'ISSN tidak valid'
        ]);
        return;
    }
    
    try {
        $result = findJournalInfo($normalizedIssn);
        
        if ($result['success'] && CACHE_ENABLED) {
            $cacheFile = CACHE_DIRECTORY . '/sinta_' . md5($normalizedIssn) . '.json.gz';
            
            $fullData = [
                'success' => true,
                'issn' => $result['issn'],
                'title' => $result['title'],
                'impact' => $result['impact'],
                'grade' => isset($result['grade']) ? $result['grade'] : null,
                'sinta_id' => $result['sinta_id'],
                'sinta_url' => $result['sinta_url'],
                'data_hash' => generateDataHash($result),
                'generated_at' => time(),
                'access_count' => 1,
                'meta' => [
                    'last_update' => date('Y-m-d H:i:s'),
                    'cache_version' => '2.1-smart-efficient',
                    'update_method' => 'manual_update'
                ]
            ];
            
            saveToCache($cacheFile, $fullData);
        }
        
        outputJsonResponse([
            'success' => true,
            'message' => "Update completed for ISSN: $issn",
            'data' => $result,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        outputJsonResponse([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Run batch update for all cached files
 */
function runBatchUpdate() {
    if (!CACHE_ENABLED || !is_dir(CACHE_DIRECTORY)) {
        return;
    }
    
    $cacheFiles = glob(CACHE_DIRECTORY . '/sinta_*.json.gz');
    $updateCount = 0;
    $changeCount = 0;
    
    foreach ($cacheFiles as $cacheFile) {
        try {
            $cachedData = loadFromCache($cacheFile);
            if (!$cachedData || !isset($cachedData['issn'])) {
                continue;
            }
            
            $issn = $cachedData['issn'];
            $normalizedIssn = preg_replace('/[^0-9X-]/', '', strtoupper($issn));
            
            // Get fresh data
            $freshData = findJournalInfo($normalizedIssn);
            
            if ($freshData['success']) {
                // Check for changes
                $hasChanges = detectDataChanges($cachedData, $freshData);
                
                if ($hasChanges) {
                    $changeCount++;
                }
                
                // Update cache with preserved access data
                $fullData = [
                    'success' => true,
                    'issn' => $freshData['issn'],
                    'title' => $freshData['title'],
                    'impact' => $freshData['impact'],
                    'grade' => isset($freshData['grade']) ? $freshData['grade'] : null,
                    'sinta_id' => $freshData['sinta_id'],
                    'sinta_url' => $freshData['sinta_url'],
                    'data_hash' => generateDataHash($freshData),
                    'generated_at' => time(),
                    'access_count' => isset($cachedData['access_count']) ? $cachedData['access_count'] : 1,
                    'access_history' => isset($cachedData['access_history']) ? $cachedData['access_history'] : [],
                    'meta' => [
                        'last_update' => date('Y-m-d H:i:s'),
                        'cache_version' => '2.1-smart-efficient',
                        'update_method' => 'batch_update'
                    ]
                ];
                
                saveToCache($cacheFile, $fullData);
                $updateCount++;
            }
            
            // Small delay
            usleep(500000); // 0.5 seconds
            
        } catch (Exception $e) {
            error_log("Batch update error for $cacheFile: " . $e->getMessage());
        }
    }
    
    error_log("Sinta batch update completed: $updateCount updated, $changeCount changed");
}

/**
 * Handle status request endpoint
 */
function handleStatusRequest() {
    $status = [
        'cache_enabled' => CACHE_ENABLED,
        'weekly_cache_duration_hours' => WEEKLY_CACHE_DURATION / 3600,
        'high_access_duration_hours' => HIGH_ACCESS_CACHE_DURATION / 3600,
        'medium_access_duration_hours' => MEDIUM_ACCESS_CACHE_DURATION / 3600,
        'cache_version' => '2.1-smart-efficient'
    ];
    
    // Get cache statistics
    if (CACHE_ENABLED && is_dir(CACHE_DIRECTORY)) {
        $cacheFiles = glob(CACHE_DIRECTORY . '/sinta_*.json.gz');
        $status['cached_journals'] = count($cacheFiles);
        
        $totalSize = 0;
        $oldestCache = null;
        $newestCache = null;
        $highAccessCount = 0;
        $mediumAccessCount = 0;
        
        foreach ($cacheFiles as $file) {
            $mtime = filemtime($file);
            $size = filesize($file);
            $totalSize += $size;
            
            if ($oldestCache === null || $mtime < $oldestCache) {
                $oldestCache = $mtime;
            }
            if ($newestCache === null || $mtime > $newestCache) {
                $newestCache = $mtime;
            }
            
            // Check access patterns
            $data = loadFromCache($file);
            if ($data && isset($data['access_count'])) {
                $accessCount = getAccessCount($data);
                if ($accessCount >= 10) {
                    $highAccessCount++;
                } elseif ($accessCount >= 5) {
                    $mediumAccessCount++;
                }
            }
        }
        
        $status['cache_total_size_kb'] = round($totalSize / 1024, 2);
        $status['high_access_journals'] = $highAccessCount;
        $status['medium_access_journals'] = $mediumAccessCount;
        $status['low_access_journals'] = count($cacheFiles) - $highAccessCount - $mediumAccessCount;
        
        if ($oldestCache) {
            $status['oldest_cache'] = date('Y-m-d H:i:s', $oldestCache);
            $status['oldest_cache_age_hours'] = round((time() - $oldestCache) / 3600, 1);
        }
        
        if ($newestCache) {
            $status['newest_cache'] = date('Y-m-d H:i:s', $newestCache);
            $status['newest_cache_age_hours'] = round((time() - $newestCache) / 3600, 1);
        }
    }
    
    outputJsonResponse($status);
}

/**
 * Find journal info by ISSN
 */
function findJournalInfo($issn) {
    // Step 1: Search for the journal using the ISSN
    $searchUrl = "https://sinta.kemdiktisaintek.go.id/journals?q=" . urlencode($issn);
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
    if (!preg_match('/<a[^>]*href=["\'](?:https:\/\/sinta\.kemdiktisaintek\.go\.id)?\/journals\/profile\/(\d+)["\'][^>]*>/i', $searchHtml, $matches)) {
        return [
            'success' => false,
            'issn' => formatIssn($issn),
            'error' => 'Jurnal tidak ditemukan di SINTA'
        ];
    }
    
    $journalId = $matches[1];
    
    // Step 3: Fetch the journal profile page
    $profileUrl = "https://sinta.kemdiktisaintek.go.id/journals/profile/$journalId";
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
        '/<div[^>]*class=["\']profile-name["\'][^>]*>([^<]+)<\/div>/is',
        '/<div[^>]*class=["\']affil-name["\'][^>]*>(?:<a[^>]*>)?([^<]+)(?:<\/a>)?/is',
        '/<h1[^>]*>([^<]+)<\/h1>/is',
        '/<h2[^>]*>([^<]+)<\/h2>/is',
        '/<meta\s+name=["\']citation_title["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/is',
        '/<meta\s+property=["\']og:title["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/is',
        '/<li[^>]*class=["\']active["\'][^>]*>([^<]+)<\/li>/is',
        '/<title>([^<|]+)(?:\s*\|[^<]*)?<\/title>/is'
    ];
    
    // Try each pattern until we find a title
    foreach ($titlePatterns as $pattern) {
        if (preg_match($pattern, $profileHtml, $titleMatches)) {
            $potentialTitle = trim($titleMatches[1]);
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
            '/class="[^"]*num-stat accredited[^"]*"><a[^>]*><i[^>]*><\/i>\s*S(\d+)\s*<span/is',
            '/S(\d+)\s*<span[^>]*>Accredited<\/span>/is',
            '/Accreditation.*?S(?:INTA)?\s*(\d+)/is',
            '/Current\s+Accreditation.*?S(?:inta)?\s*(\d+)/i',
            '/<div[^>]*>S(\d+)<\/div>/i',
            '/<span[^>]*>S(\d+)<\/span>.*?Accredited/is',
            '/Accredited.*?<span[^>]*>S(\d+)<\/span>/is',
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
function cleanOldDebugFiles($maxAge = 86400) {
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
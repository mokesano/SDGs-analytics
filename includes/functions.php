<?php
/**
 * SDG Frontend - Main Functions
 * Kumpulan fungsi utama untuk SDG Classification Analysis
 * 
 * @version 1.0.0 (PHP 7.4+ Compatible)
 * @author Rochmady and Wizdam Team
 * @license MIT
 */

// ==============================================
// VALIDASI INPUT
// ==============================================

/**
 * Validate ORCID ID format
 */
function validateOrcid($orcid) {
    // Remove URL prefix if present
    $clean_orcid = str_replace(['https://orcid.org/', 'http://orcid.org/'], '', $orcid);
    
    // ORCID format: 0000-0000-0000-000X (where X is checksum)
    if (!preg_match('/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/', $clean_orcid)) {
        return false;
    }
    
    // Validate checksum
    return validateOrcidChecksum($clean_orcid);
}

/**
 * Validate ORCID checksum
 */
function validateOrcidChecksum($orcid) {
    $digits = str_replace('-', '', substr($orcid, 0, -1));
    $checkDigit = strtoupper(substr($orcid, -1));
    
    $total = 0;
    for ($i = 0; $i < strlen($digits); $i++) {
        $total = ($total + intval($digits[$i])) * 2;
    }
    
    $remainder = $total % 11;
    $result = (12 - $remainder) % 11;
    $expectedCheckDigit = ($result == 10) ? 'X' : strval($result);
    
    return $checkDigit === $expectedCheckDigit;
}

/**
 * Validate DOI format
 */
function validateDoi($doi) {
    // Remove URL prefix if present
    $clean_doi = preg_replace('/^https?:\/\/(dx\.)?doi\.org\//', '', $doi);
    
    // Basic DOI format validation
    return preg_match('/^10\.\d{4,}\/[^\s]+$/', $clean_doi);
}

/**
 * Clean and normalize input
 */
function cleanInput($input) {
    return trim(strip_tags($input));
}

// ==============================================
// CACHE MANAGEMENT
// ==============================================

/**
 * Generate cache filename
 */
function getCacheFilename($type, $identifier) {
    $cache_dir = getConfig('CACHE_DIR', __DIR__ . '/../cache');
    $safe_identifier = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $identifier);
    return $cache_dir . '/' . $type . '_' . $safe_identifier . '.json';
}

/**
 * Read from cache
 */
function readFromCache($cache_file) {
    if (!getConfig('ENABLE_CACHE', true) || !file_exists($cache_file)) {
        return false;
    }
    
    $cache_ttl = getConfig('CACHE_TTL', 3600);
    if (time() - filemtime($cache_file) > $cache_ttl) {
        unlink($cache_file);
        return false;
    }
    
    $content = file_get_contents($cache_file);
    if ($content === false) {
        return false;
    }
    
    // Try to decompress if needed
    if (getConfig('ENABLE_COMPRESSION', true)) {
        $decompressed = @gzuncompress($content);
        if ($decompressed !== false) {
            $content = $decompressed;
        }
    }
    
    return json_decode($content, true);
}

/**
 * Write to cache
 */
function writeToCache($cache_file, $data) {
    if (!getConfig('ENABLE_CACHE', true)) {
        return false;
    }
    
    $json_data = json_encode($data, JSON_UNESCAPED_UNICODE);
    
    // Compress if enabled
    if (getConfig('ENABLE_COMPRESSION', true)) {
        $json_data = gzcompress($json_data, 6);
    }
    
    // Ensure cache directory exists
    $cache_dir = dirname($cache_file);
    if (!file_exists($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }
    
    return file_put_contents($cache_file, $json_data, LOCK_EX) !== false;
}

/**
 * Clear cache for specific type or all
 */
function clearCache($type = null) {
    $cache_dir = getConfig('CACHE_DIR', __DIR__ . '/../cache');
    
    if (!is_dir($cache_dir)) {
        return true;
    }
    
    $pattern = $type ? $cache_dir . '/' . $type . '_*.json' : $cache_dir . '/*.json';
    $files = glob($pattern);
    
    foreach ($files as $file) {
        unlink($file);
    }
    
    return true;
}

// ==============================================
// API REQUESTS
// ==============================================

/**
 * Make API request with proper error handling
 */
function makeApiRequest($url, $data = null, $headers = []) {
    $ch = curl_init();
    
    // Basic cURL options
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_TIMEOUT => getConfig('TIMEOUT_EXECUTE', 120),
        CURLOPT_CONNECTTIMEOUT => getConfig('TIMEOUT_CONNECT', 10),
        CURLOPT_USERAGENT => 'SDG-Analysis-Platform/5.1.8',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    
    // POST data if provided
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $headers[] = 'Content-Type: application/json';
    }
    
    // Set headers
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($response === false || !empty($error)) {
        throw new Exception('cURL Error: ' . $error);
    }
    
    if ($http_code >= 400) {
        throw new Exception('HTTP Error: ' . $http_code);
    }
    
    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON Decode Error: ' . json_last_error_msg());
    }
    
    return $decoded;
}

/**
 * Fetch ORCID data
 */
function fetchOrcidData($orcid) {
    $clean_orcid = str_replace(['https://orcid.org/', 'http://orcid.org/'], '', $orcid);
    
    // Check cache first
    $cache_file = getCacheFilename('orcid', $clean_orcid);
    $cached_result = readFromCache($cache_file);
    
    if ($cached_result !== false) {
        return $cached_result;
    }
    
    try {
        // Fetch ORCID profile
        $profile_url = getConfig('ORCID_API_URL', 'https://pub.orcid.org/v3.0') . '/' . $clean_orcid;
        $profile_data = makeApiRequest($profile_url, null, ['Accept: application/json']);
        
        // Fetch works
        $works_url = $profile_url . '/works';
        $works_data = makeApiRequest($works_url, null, ['Accept: application/json']);
        
        $result = [
            'profile' => $profile_data,
            'works' => $works_data,
            'fetched_at' => time()
        ];
        
        // Cache the result
        writeToCache($cache_file, $result);
        
        return $result;
        
    } catch (Exception $e) {
        logError('ORCID fetch error for ' . $clean_orcid . ': ' . $e->getMessage());
        throw new Exception('Failed to fetch ORCID data: ' . $e->getMessage());
    }
}

/**
 * Fetch DOI data
 */
function fetchDoiData($doi) {
    $clean_doi = preg_replace('/^https?:\/\/(dx\.)?doi\.org\//', '', $doi);
    
    // Check cache first
    $cache_file = getCacheFilename('doi', $clean_doi);
    $cached_result = readFromCache($cache_file);
    
    if ($cached_result !== false) {
        return $cached_result;
    }
    
    try {
        // Try Crossref first
        $crossref_url = getConfig('CROSSREF_API_URL', 'https://api.crossref.org/works') . '/' . $clean_doi;
        
        try {
            $crossref_data = makeApiRequest($crossref_url, null, ['Accept: application/json']);
            
            $result = [
                'source' => 'crossref',
                'data' => $crossref_data,
                'fetched_at' => time()
            ];
            
            // Cache the result
            writeToCache($cache_file, $result);
            
            return $result;
            
        } catch (Exception $e) {
            // Try OpenAlex as fallback
            $openalex_url = getConfig('OPENALEX_API_URL', 'https://api.openalex.org/works') . '/doi:' . $clean_doi;
            
            $openalex_data = makeApiRequest($openalex_url, null, ['Accept: application/json']);
            
            $result = [
                'source' => 'openalex',
                'data' => $openalex_data,
                'fetched_at' => time()
            ];
            
            // Cache the result
            writeToCache($cache_file, $result);
            
            return $result;
        }
        
    } catch (Exception $e) {
        logError('DOI fetch error for ' . $clean_doi . ': ' . $e->getMessage());
        throw new Exception('Failed to fetch DOI data: ' . $e->getMessage());
    }
}

// ==============================================
// SDG ANALYSIS
// ==============================================

/**
 * Process ORCID analysis
 */
function processOrcidAnalysis($orcid) {
    $clean_orcid = str_replace(['https://orcid.org/', 'http://orcid.org/'], '', $orcid);
    
    // Check cache first
    $cache_file = getCacheFilename('analysis_orcid', $clean_orcid);
    $cached_result = readFromCache($cache_file);
    
    if ($cached_result !== false) {
        return $cached_result;
    }
    
    try {
        // Call SDG analysis API
        $api_url = getApiBaseUrl();
        $post_data = json_encode([
            'action' => 'analyze_orcid',
            'orcid' => $clean_orcid,
            'include_details' => true
        ]);
        
        $api_result = makeApiRequest($api_url, $post_data);
        
        if (isset($api_result['error'])) {
            throw new Exception('API Error: ' . $api_result['error']);
        }
        
        // Add timestamp and cache
        $api_result['analyzed_at'] = time();
        $api_result['cache_ttl'] = getConfig('CACHE_TTL', 3600);
        
        writeToCache($cache_file, $api_result);
        
        return $api_result;
        
    } catch (Exception $e) {
        logError('ORCID analysis error for ' . $clean_orcid . ': ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Process DOI analysis
 */
function processDoiAnalysis($doi) {
    $clean_doi = preg_replace('/^https?:\/\/(dx\.)?doi\.org\//', '', $doi);
    
    // Check cache first
    $cache_file = getCacheFilename('analysis_doi', $clean_doi);
    $cached_result = readFromCache($cache_file);
    
    if ($cached_result !== false) {
        return $cached_result;
    }
    
    try {
        // Call SDG analysis API
        $api_url = getApiBaseUrl();
        $post_data = json_encode([
            'action' => 'analyze_doi',
            'doi' => $clean_doi,
            'include_details' => true
        ]);
        
        $api_result = makeApiRequest($api_url, $post_data);
        
        if (isset($api_result['error'])) {
            throw new Exception('API Error: ' . $api_result['error']);
        }
        
        // Add timestamp and cache
        $api_result['analyzed_at'] = time();
        $api_result['cache_ttl'] = getConfig('CACHE_TTL', 3600);
        
        writeToCache($cache_file, $api_result);
        
        return $api_result;
        
    } catch (Exception $e) {
        logError('DOI analysis error for ' . $clean_doi . ': ' . $e->getMessage());
        throw $e;
    }
}

// ==============================================
// HTML RENDERING FUNCTIONS
// ==============================================

/**
 * Render safe HTML content
 */
function renderSafeHtml($content) {
    if (empty($content)) {
        return '';
    }
    
    $decoded = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $fixed = fixUnclosedHtmlTags($decoded);
    $allowedTags = '<strong><b><em><i><u><sup><sub><br><p>';
    $safe = strip_tags($fixed, $allowedTags);
    $safe = cleanupHtmlStructure($safe);
    
    return $safe;
}

/**
 * Fix unclosed HTML tags
 */
function fixUnclosedHtmlTags($content) {
    $pairedTags = ['strong', 'b', 'em', 'i', 'u', 'sup', 'sub', 'p'];
    $fixed = $content;
    
    foreach ($pairedTags as $tag) {
        $fixed = balanceHtmlTags($fixed, $tag);
    }
    
    return $fixed;
}

/**
 * Balance HTML tags
 */
function balanceHtmlTags($content, $tag) {
    preg_match_all('/<' . preg_quote($tag, '/') . '(?:\s[^>]*)?>/', $content, $openMatches);
    preg_match_all('/<\/' . preg_quote($tag, '/') . '>/', $content, $closeMatches);
    
    $openTags = $openMatches[0];
    $closeTags = $closeMatches[0];
    
    if (count($openTags) > count($closeTags)) {
        $missingClose = count($openTags) - count($closeTags);
        for ($i = 0; $i < $missingClose; $i++) {
            $content .= '</' . $tag . '>';
        }
    } elseif (count($closeTags) > count($openTags)) {
        $excessClose = count($closeTags) - count($openTags);
        for ($i = 0; $i < $excessClose; $i++) {
            $content = preg_replace('/<\/' . preg_quote($tag, '/') . '>/', '', $content, 1);
        }
    }
    
    return $content;
}

/**
 * Clean up HTML structure
 */
function cleanupHtmlStructure($content) {
    $content = preg_replace('/<p[^>]*>[\s]*<\/p>/i', '', $content);
    $content = preg_replace('/<p([^>]*)>/i', '<p$1>', $content);
    $content = preg_replace('/<\/p>/i', '</p> ', $content);
    $content = preg_replace('/\s+/', ' ', $content);
    $content = trim($content);
    
    return $content;
}

/**
 * Render detailed analysis
 */
function renderDetailedAnalysis($data, $sdgDefinitions, $index) {
    $output = '<div class="detailed-analysis" id="analysis-' . $index . '">';
    
    // Check data structure
    $analysisData = isset($data['detailed_analysis']) ? $data['detailed_analysis'] : 
                   (isset($data['sdg_analysis']) ? $data['sdg_analysis'] : []);
    
    if (!empty($analysisData)) {
        foreach ($analysisData as $sdg => $analysis) {
            $sdg_info = getSdgInfo($sdg);
            if (!$sdg_info) continue;
            
            $output .= '<div class="analysis-section">';
            $output .= '<h5 style="color: ' . $sdg_info['color'] . '">' . 
                      htmlspecialchars($sdg . ': ' . $sdg_info['title']) . '</h5>';
            
            if (isset($analysis['explanation'])) {
                $output .= '<div class="explanation">' . 
                          renderSafeHtml($analysis['explanation']) . '</div>';
            }
            
            if (isset($analysis['evidence']) && !empty($analysis['evidence'])) {
                $output .= '<div class="evidence"><strong>Evidence:</strong><ul>';
                foreach ($analysis['evidence'] as $evidence) {
                    $output .= '<li>' . htmlspecialchars($evidence) . '</li>';
                }
                $output .= '</ul></div>';
            }
            
            $output .= '</div>';
        }
    } else {
        $output .= '<p class="none-SDG">No detailed SDG analysis available for this work.</p>';
    }
    
    $output .= '</div>';
    return $output;
}

/**
 * Format confidence score
 */
function formatScore($score) {
    if (is_numeric($score)) {
        return number_format($score * 100, 1) . '%';
    }
    return 'N/A';
}

/**
 * Format publication year
 */
function formatYear($year_data) {
    if (is_array($year_data) && isset($year_data['date-parts'][0][0])) {
        return $year_data['date-parts'][0][0];
    } elseif (is_string($year_data)) {
        return $year_data;
    } elseif (is_numeric($year_data)) {
        return $year_data;
    }
    return 'Unknown';
}

// ==============================================
// UTILITY FUNCTIONS
// ==============================================

/**
 * Log error messages
 */
function logError($message) {
    global $LOG_CONFIG;
    
    if (!isset($LOG_CONFIG['enabled']) || !$LOG_CONFIG['enabled']) {
        return;
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] ERROR: $message" . PHP_EOL;
    
    $log_file = $LOG_CONFIG['file'] ?? __DIR__ . '/../logs/app.log';
    
    // Create logs directory if it doesn't exist
    $log_dir = dirname($log_file);
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    error_log($log_entry, 3, $log_file);
}

/**
 * Log info messages
 */
function logInfo($message) {
    global $LOG_CONFIG;
    
    if (!isset($LOG_CONFIG['enabled']) || !$LOG_CONFIG['enabled']) {
        return;
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] INFO: $message" . PHP_EOL;
    
    $log_file = $LOG_CONFIG['file'] ?? __DIR__ . '/../logs/app.log';
    error_log($log_entry, 3, $log_file);
}

/**
 * Generate CSRF token
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize output for display
 */
function sanitizeOutput($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Get user IP address
 */
function getUserIpAddress() {
    $ip_headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ip_headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            // Handle comma-separated IPs (X-Forwarded-For)
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Rate limiting check
 */
function checkRateLimit($identifier, $limit = 60, $window = 3600) {
    $cache_file = getCacheFilename('rate_limit', $identifier);
    $rate_data = readFromCache($cache_file);
    
    $current_time = time();
    
    if ($rate_data === false) {
        $rate_data = [
            'count' => 1,
            'window_start' => $current_time
        ];
    } else {
        // Reset window if expired
        if ($current_time - $rate_data['window_start'] > $window) {
            $rate_data = [
                'count' => 1,
                'window_start' => $current_time
            ];
        } else {
            $rate_data['count']++;
        }
    }
    
    writeToCache($cache_file, $rate_data);
    
    return $rate_data['count'] <= $limit;
}

/**
 * Generate unique request ID
 */
function generateRequestId() {
    return uniqid('req_', true);
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Calculate execution time
 */
function calculateExecutionTime($start_time) {
    return round(microtime(true) - $start_time, 3);
}

/**
 * Generate metadata for SEO
 */
function generatePageMetadata($page, $data = []) {
    $metadata = [
        'title' => SITE_NAME,
        'description' => 'AI-powered platform for analyzing research contributions to UN Sustainable Development Goals',
        'keywords' => 'SDG, sustainable development goals, research analysis, ORCID, DOI, AI analysis',
        'canonical' => SITE_URL . '/?page=' . $page,
        'og_type' => 'website',
        'og_image' => SITE_URL . '/assets/images/og-image.jpg'
    ];
    
    // Page-specific metadata
    switch ($page) {
        case 'home':
            $metadata['title'] = 'SDG Classification Analysis - AI-Powered Research Analysis';
            $metadata['description'] = 'Analyze research contributions to UN Sustainable Development Goals using AI. Support for ORCID profiles and DOI analysis.';
            break;
        case 'about':
            $metadata['title'] = 'About Us - ' . SITE_NAME;
            $metadata['description'] = 'Learn about our mission to advance sustainable development through AI-powered research analysis.';
            break;
        case 'documentation':
            $metadata['title'] = 'Documentation - ' . SITE_NAME;
            $metadata['description'] = 'Complete documentation and API reference for our SDG analysis platform.';
            break;
    }
    
    // Merge with provided data
    return array_merge($metadata, $data);
}

/**
 * Render pagination
 */
function renderPagination($current_page, $total_pages, $base_url, $params = []) {
    if ($total_pages <= 1) {
        return '';
    }
    
    $html = '<nav class="pagination" aria-label="Pagination">';
    $html .= '<ul class="pagination-list">';
    
    // Previous button
    if ($current_page > 1) {
        $prev_url = $base_url . '?' . http_build_query(array_merge($params, ['page' => $current_page - 1]));
        $html .= '<li><a href="' . $prev_url . '" class="pagination-prev" aria-label="Previous page">&laquo; Previous</a></li>';
    }
    
    // Page numbers
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $page_url = $base_url . '?' . http_build_query(array_merge($params, ['page' => $i]));
        $active_class = ($i == $current_page) ? ' active' : '';
        $html .= '<li><a href="' . $page_url . '" class="pagination-link' . $active_class . '">' . $i . '</a></li>';
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $next_url = $base_url . '?' . http_build_query(array_merge($params, ['page' => $current_page + 1]));
        $html .= '<li><a href="' . $next_url . '" class="pagination-next" aria-label="Next page">Next &raquo;</a></li>';
    }
    
    $html .= '</ul>';
    $html .= '</nav>';
    
    return $html;
}

// ==============================================
// EXPORT FUNCTIONS
// ==============================================

/**
 * Export data to CSV
 */
function exportToCsv($data, $filename = 'export.csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    if (!empty($data)) {
        // Write header
        if (is_array($data[0])) {
            fputcsv($output, array_keys($data[0]));
        }
        
        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit;
}

/**
 * Export data to JSON
 */
function exportToJson($data, $filename = 'export.json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// ==============================================
// ANALYSIS HELPER FUNCTIONS
// ==============================================

/**
 * Calculate SDG distribution from analysis results
 */
function calculateSdgDistribution($analysis_results) {
    $sdg_counts = [];
    $total_works = 0;
    
    if (isset($analysis_results['works'])) {
        foreach ($analysis_results['works'] as $work) {
            $total_works++;
            
            if (isset($work['sdg_scores'])) {
                foreach ($work['sdg_scores'] as $sdg => $score) {
                    if ($score > 0.2) { // Threshold for meaningful contribution
                        $sdg_counts[$sdg] = ($sdg_counts[$sdg] ?? 0) + 1;
                    }
                }
            }
        }
    }
    
    // Calculate percentages
    $distribution = [];
    foreach ($sdg_counts as $sdg => $count) {
        $distribution[$sdg] = [
            'count' => $count,
            'percentage' => $total_works > 0 ? round(($count / $total_works) * 100, 1) : 0
        ];
    }
    
    return $distribution;
}

/**
 * Get top contributing SDGs
 */
function getTopContributingSdgs($analysis_results, $limit = 5) {
    $sdg_totals = [];
    
    if (isset($analysis_results['works'])) {
        foreach ($analysis_results['works'] as $work) {
            if (isset($work['sdg_scores'])) {
                foreach ($work['sdg_scores'] as $sdg => $score) {
                    $sdg_totals[$sdg] = ($sdg_totals[$sdg] ?? 0) + $score;
                }
            }
        }
    }
    
    arsort($sdg_totals);
    return array_slice($sdg_totals, 0, $limit, true);
}

/**
 * Calculate confidence metrics
 */
function calculateConfidenceMetrics($analysis_results) {
    $scores = [];
    $total_works = 0;
    
    if (isset($analysis_results['works'])) {
        foreach ($analysis_results['works'] as $work) {
            $total_works++;
            
            if (isset($work['sdg_scores'])) {
                foreach ($work['sdg_scores'] as $score) {
                    if ($score > 0) {
                        $scores[] = $score;
                    }
                }
            }
        }
    }
    
    if (empty($scores)) {
        return [
            'average' => 0,
            'median' => 0,
            'min' => 0,
            'max' => 0,
            'std_dev' => 0
        ];
    }
    
    sort($scores);
    $count = count($scores);
    $sum = array_sum($scores);
    $average = $sum / $count;
    
    // Calculate median
    $median = $count % 2 == 0 
        ? ($scores[$count/2 - 1] + $scores[$count/2]) / 2
        : $scores[floor($count/2)];
    
    // Calculate standard deviation
    $variance = 0;
    foreach ($scores as $score) {
        $variance += pow($score - $average, 2);
    }
    $std_dev = sqrt($variance / $count);
    
    return [
        'average' => round($average, 3),
        'median' => round($median, 3),
        'min' => min($scores),
        'max' => max($scores),
        'std_dev' => round($std_dev, 3),
        'total_scores' => $count,
        'total_works' => $total_works
    ];
}

// ==============================================
// PERFORMANCE MONITORING
// ==============================================

/**
 * Start performance timer
 */
function startTimer($name = 'default') {
    global $performance_timers;
    $performance_timers[$name] = microtime(true);
}

/**
 * End performance timer
 */
function endTimer($name = 'default') {
    global $performance_timers;
    
    if (!isset($performance_timers[$name])) {
        return 0;
    }
    
    $elapsed = microtime(true) - $performance_timers[$name];
    unset($performance_timers[$name]);
    
    return round($elapsed, 4);
}

/**
 * Get memory usage
 */
function getMemoryUsage($peak = false) {
    $bytes = $peak ? memory_get_peak_usage(true) : memory_get_usage(true);
    return formatFileSize($bytes);
}

/**
 * Performance report
 */
function generatePerformanceReport() {
    return [
        'execution_time' => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4),
        'memory_usage' => getMemoryUsage(),
        'peak_memory' => getMemoryUsage(true),
        'included_files' => count(get_included_files()),
        'server_load' => function_exists('sys_getloadavg') ? sys_getloadavg()[0] : 'N/A'
    ];
}

// ==============================================
// SECURITY FUNCTIONS
// ==============================================

/**
 * Generate secure random string
 */
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Hash password securely
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_ARGON2ID);
}

/**
 * Verify password hash
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Sanitize filename
 */
function sanitizeFilename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $filename);
    $filename = preg_replace('/_{2,}/', '_', $filename);
    return trim($filename, '_');
}

/**
 * Check if request is HTTPS
 */
function isHttps() {
    return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
}

/**
 * Redirect to HTTPS
 */
function redirectToHttps() {
    if (!isHttps() && ENVIRONMENT === 'production') {
        $redirect_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header('Location: ' . $redirect_url, true, 301);
        exit;
    }
}

// ==============================================
// INITIALIZATION
// ==============================================

// Initialize performance monitoring
global $performance_timers;
$performance_timers = [];

// Start main timer
startTimer('main');

// Force HTTPS in production
if (ENVIRONMENT === 'production') {
    redirectToHttps();
}

// Set default timezone if not already set
if (!ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}

?>
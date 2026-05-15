<?php
/**
 * Scopus Journal Metrics Checker - Enhanced Version
 * 
 * Fitur: CiteScore, Quartile, SJR, SNIP, Subject Areas, Discontinued Status
 * Setup: Ganti YOUR_SCOPUS_API_KEY_HERE dengan API key Scopus Anda
 * Get API Key: https://dev.elsevier.com/
 * 
 * @author Rochmady and Wuzdam Team
 * @version 1.0.0 (PHP 7.4+ Compatible)
 * Created: 2025-06-14
 * Last update: 2025-06-16
 */

// =============================================================================
// KONFIGURASI
// =============================================================================
$SCOPUS_API_KEY = '2b2a63a2cd69bd0cfd7acc07addc140f';
$DEBUG_MODE = false; // Set ke false untuk production

// =============================================================================
// CLASS SCOPUS API
// =============================================================================
class ScopusAPI {
    private $apiKey;
    private $baseUrl = 'https://api.elsevier.com/content/serial/title';
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    /**
     * Get debug information for troubleshooting
     */
    private function getDebugInfo($entry) {
        $debug = array();
        
        // Check all possible locations for quartile data
        $locations = array(
            'citeScoreYearInfoList',
            'SJRList', 
            'subject-area',
            'ranking',
            'quartile'
        );
        
        foreach ($locations as $location) {
            if (isset($entry[$location])) {
                $debug[$location] = $entry[$location];
            }
        }
        
        // Look for any field containing 'quartile' or 'rank'
        foreach ($entry as $key => $value) {
            if (stripos($key, 'quartile') !== false || stripos($key, 'rank') !== false) {
                $debug['found_' . $key] = $value;
            }
        }
        
        return $debug;
    }

    /**
     * Cari jurnal berdasarkan ISSN
     */
    public function searchByISSN($issn) {
        $cleanISSN = $this->cleanISSN($issn);
        
        // Try multiple API endpoints for better quartile data
        $endpoints = array(
            $this->baseUrl . '/issn/' . $cleanISSN,
            $this->baseUrl . '/issn/' . $cleanISSN . '?view=ENHANCED',
            $this->baseUrl . '/issn/' . $cleanISSN . '?view=CITESCORE'
        );
        
        $bestResult = null;
        $headers = array(
            'Accept: application/json',
            'X-ELS-APIKey: ' . $this->apiKey,
            'User-Agent: JournalChecker/2.0'
        );
        
        foreach ($endpoints as $url) {
            $response = $this->makeHttpRequest($url, $headers);
            
            if ($response['success']) {
                $result = $this->parseJournalData($response['data'], $issn);
                if ($result['success']) {
                    if (!$bestResult || $result['quartile']) {
                        $bestResult = $result;
                        // If we found quartile, use this result
                        if ($result['quartile']) {
                            break;
                        }
                    }
                }
            }
        }
        
        return $bestResult ? $bestResult : array('success' => false, 'error' => 'Journal not found in any Scopus endpoint');
    }
    
    /**
     * Bersihkan format ISSN
     */
    private function cleanISSN($issn) {
        return str_replace('-', '', trim($issn));
    }
    
    /**
     * Format ISSN dengan tanda strip
     */
    private function formatISSN($issn) {
        $clean = $this->cleanISSN($issn);
        return substr($clean, 0, 4) . '-' . substr($clean, 4, 4);
    }
    
    /**
     * HTTP Request ke Scopus API
     */
    private function makeHttpRequest($url, $headers) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        // Handle errors
        if ($error) {
            return array('success' => false, 'error' => 'Connection Error: ' . $error);
        }
        
        if ($httpCode === 404) {
            return array('success' => false, 'error' => 'Journal not found in Scopus database');
        }
        
        if ($httpCode === 401) {
            return array('success' => false, 'error' => 'Invalid API Key. Check your Scopus API configuration.');
        }
        
        if ($httpCode !== 200) {
            return array('success' => false, 'error' => 'API Error (HTTP ' . $httpCode . ')');
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array('success' => false, 'error' => 'Invalid API response format');
        }
        
        return array('success' => true, 'data' => $data);
    }
    
    /**
     * Parse data jurnal dari API response
     */
    private function parseJournalData($apiData, $originalISSN) {
        global $DEBUG_MODE;
        
        if (!isset($apiData['serial-metadata-response']['entry']) || 
            empty($apiData['serial-metadata-response']['entry'])) {
            return array('success' => false, 'error' => 'Journal not found or not indexed in Scopus');
        }
        
        $entry = $apiData['serial-metadata-response']['entry'][0];
        
        // Basic journal info
        $journal = array(
            'success' => true,
            'issn' => $this->formatISSN($originalISSN),
            'title' => $this->getValue($entry, 'dc:title', 'N/A'),
            'publisher' => $this->getValue($entry, 'dc:publisher', 'N/A'),
            'scopus_id' => $this->getValue($entry, 'source-id'),
            'subject_areas' => $this->extractSubjectAreas($entry),
            'discontinued' => null,
            'discontinued_year' => null,
            'coverage_start' => null,
            'coverage_end' => null,
            'citescore' => null,
            'citescore_year' => null,
            'quartile' => null,
            'percentile' => null,
            'rank' => null,
            'quartile_year' => null,
            'quartile_source' => null,
            'sjr' => null,
            'snip' => null,
            'debug_info' => $DEBUG_MODE ? $this->getDebugInfo($entry) : null
        );
        
        // Extract metrics
        $this->extractCiteScore($entry, $journal);
        $this->extractQuartile($entry, $journal);
        $this->extractSJR($entry, $journal);
        $this->extractSNIP($entry, $journal);
        $this->extractDiscontinuedStatus($entry, $journal);
        
        // Extract additional features
$this->extractOpenAccessStatus($entry, $journal);
$this->extractPublicationType($entry, $journal);
$this->extractCountryInfo($entry, $journal);
$this->extractEnhancedSubjectAreas($entry, $journal);
$this->extractDetailedCoverage($entry, $journal);
        
        return $journal;
    }
    
    /**
     * Ambil nilai dari array dengan fallback
     */
    private function getValue($array, $key, $default = null) {
        return isset($array[$key]) ? $array[$key] : $default;
    }
    
    /**
     * Extract subject areas
     */
    private function extractSubjectAreas($entry) {
        $subjects = array();
        
        if (isset($entry['subject-area']) && is_array($entry['subject-area'])) {
            foreach ($entry['subject-area'] as $subject) {
                if (is_array($subject) && isset($subject['$'])) {
                    $subjects[] = $subject['$'];
                } elseif (is_string($subject)) {
                    $subjects[] = $subject;
                }
            }
        }
        
        return $subjects;
    }
    
    /**
     * Extract CiteScore data
     */
    private function extractCiteScore($entry, &$journal) {
        if (!isset($entry['citeScoreYearInfoList']['citeScoreCurrentMetric'])) {
            return;
        }
        
        $citeScoreInfo = $entry['citeScoreYearInfoList'];
        $journal['citescore'] = $citeScoreInfo['citeScoreCurrentMetric'];
        
        // Get year
        if (isset($citeScoreInfo['citeScoreCurrentMetricYear'])) {
            $journal['citescore_year'] = $citeScoreInfo['citeScoreCurrentMetricYear'];
        } else {
            $journal['citescore_year'] = date('Y') - 1;
        }
        
        // Get percentile if available
        if (isset($citeScoreInfo['citeScoreCurrentPercentile'])) {
            $journal['percentile'] = $citeScoreInfo['citeScoreCurrentPercentile'];
        }
    }
    
    /**
     * Extract Quartile data - with smart SJR validation
     */
    private function extractQuartile($entry, &$journal) {
        global $DEBUG_MODE;
        
        $quartileFound = false;
        $searchPaths = array();
        $sjrQuartile = null;
        $sjrYear = null;
        
        // STEP 1: Extract SJR quartile and year
        if (isset($entry['SJRList']['SJR']) && is_array($entry['SJRList']['SJR'])) {
            $latestSJR = end($entry['SJRList']['SJR']);
            if (isset($latestSJR['quartile'])) {
                $sjrQuartile = $latestSJR['quartile'];
                $sjrYear = isset($latestSJR['@year']) ? $latestSJR['@year'] : null;
                $searchPaths[] = "SJR Quartile found: $sjrQuartile (Year: $sjrYear)";
            }
        }
        
        // STEP 2: Check if SJR quartile is recent (within 2 years)
        $currentYear = date('Y');
        $sjrIsRecent = false;
        if ($sjrYear && ($currentYear - intval($sjrYear)) <= 2) {
            $sjrIsRecent = true;
        }
        
        // PRIORITY 1: Use SJR quartile if recent and reliable
        if (!$quartileFound && $sjrQuartile && $sjrIsRecent) {
            $journal['quartile'] = $sjrQuartile;
            $journal['quartile_year'] = $sjrYear;
            $quartileFound = true;
            $searchPaths[] = "PRIORITY 1: Using SJR quartile = $sjrQuartile (Recent data from $sjrYear)";
        }
        
        // PRIORITY 2: Extract from citeScoreYearInfo (might be more current)
        if (!$quartileFound && isset($entry['citeScoreYearInfoList']['citeScoreYearInfo'])) {
            $yearInfoList = $entry['citeScoreYearInfoList']['citeScoreYearInfo'];
            
            // Look for the most recent complete year
            foreach ($yearInfoList as $yearInfo) {
                if (isset($yearInfo['@status']) && $yearInfo['@status'] === 'Complete' &&
                    isset($yearInfo['citeScoreInformationList'][0]['citeScoreInfo'][0]['citeScoreSubjectRank'][0]['percentile'])) {
                    
                    $percentile = $yearInfo['citeScoreInformationList'][0]['citeScoreInfo'][0]['citeScoreSubjectRank'][0]['percentile'];
                    $rank = $yearInfo['citeScoreInformationList'][0]['citeScoreInfo'][0]['citeScoreSubjectRank'][0]['rank'];
                    $year = $yearInfo['@year'];
                    
                    // Convert percentile to quartile
                    if ($percentile >= 75) $quartile = 'Q1';
                    elseif ($percentile >= 50) $quartile = 'Q2';
                    elseif ($percentile >= 25) $quartile = 'Q3';
                    else $quartile = 'Q4';
                    
                    // Check if this CiteScore quartile is more recent than SJR
                    if (!$sjrYear || ($year && intval($year) > intval($sjrYear))) {
                        $journal['quartile'] = $quartile;
                        $journal['percentile'] = $percentile;
                        $journal['rank'] = $rank;
                        $journal['quartile_year'] = $year;
                        $quartileFound = true;
                        $searchPaths[] = "PRIORITY 2: Using CiteScore quartile = $quartile (More recent: $year vs SJR: $sjrYear)";
                        break;
                    }
                }
            }
        }
        
        // PRIORITY 3: Use SJR quartile even if older (fallback)
        if (!$quartileFound && $sjrQuartile) {
            $journal['quartile'] = $sjrQuartile;
            $journal['quartile_year'] = $sjrYear;
            $quartileFound = true;
            $searchPaths[] = "PRIORITY 3: Using SJR quartile = $sjrQuartile (Fallback, Year: $sjrYear)";
        }
        
        // PRIORITY 4: From CiteScore tracker (fallback)
        if (!$quartileFound && isset($entry['citeScoreYearInfoList']['citeScoreTracker']['quartile'])) {
            $journal['quartile'] = $entry['citeScoreYearInfoList']['citeScoreTracker']['quartile'];
            $quartileFound = true;
            $searchPaths[] = "PRIORITY 4: Using CiteScore tracker = " . $entry['citeScoreYearInfoList']['citeScoreTracker']['quartile'];
        }
        
        // PRIORITY 5: From CiteScore quartile rank (fallback)
        if (!$quartileFound && isset($entry['citeScoreYearInfoList']['citeScoreQuartileRank'])) {
            $journal['quartile'] = $entry['citeScoreYearInfoList']['citeScoreQuartileRank'];
            $quartileFound = true;
            $searchPaths[] = "PRIORITY 5: Using CiteScore rank = " . $entry['citeScoreYearInfoList']['citeScoreQuartileRank'];
        }
        
        // PRIORITY 6: From current percentile (last resort calculation)
        if (!$quartileFound && isset($journal['percentile']) && $journal['percentile']) {
            $percentile = floatval($journal['percentile']);
            if ($percentile >= 75) $quartile = 'Q1';
            elseif ($percentile >= 50) $quartile = 'Q2';
            elseif ($percentile >= 25) $quartile = 'Q3';
            else $quartile = 'Q4';
            
            $journal['quartile'] = $quartile;
            $quartileFound = true;
            $searchPaths[] = "PRIORITY 6: Calculated from percentile $percentile = $quartile";
        }
        
        // Add data source information
        if ($quartileFound) {
            if ($sjrQuartile && $journal['quartile'] === $sjrQuartile) {
                $journal['quartile_source'] = 'SJR';
            } else {
                $journal['quartile_source'] = 'CiteScore';
            }
        }
        
        // Debug logging
        if ($DEBUG_MODE) {
            error_log("=== DEBUG: Smart Quartile Extraction ===");
            error_log("SJR Quartile: " . ($sjrQuartile ? $sjrQuartile : "NULL") . " (Year: " . ($sjrYear ? $sjrYear : "NULL") . ")");
            error_log("SJR is recent: " . ($sjrIsRecent ? "YES" : "NO"));
            error_log("Final quartile: " . ($journal['quartile'] ? $journal['quartile'] : "NULL"));
            error_log("Final source: " . ($journal['quartile_source'] ? $journal['quartile_source'] : "NULL"));
            foreach ($searchPaths as $path) {
                error_log("Search: $path");
            }
        }
    }
    
    /**
     * Extract SJR data
     */
    private function extractSJR($entry, &$journal) {
        if (isset($entry['SJRList']['SJR']) && is_array($entry['SJRList']['SJR'])) {
            $latestSJR = end($entry['SJRList']['SJR']);
            if (isset($latestSJR['$'])) {
                $journal['sjr'] = floatval($latestSJR['$']);
            }
        }
    }
    
    /**
     * Extract SNIP data
     */
    private function extractSNIP($entry, &$journal) {
        if (isset($entry['SNIPList']['SNIP']) && is_array($entry['SNIPList']['SNIP'])) {
            $latestSNIP = end($entry['SNIPList']['SNIP']);
            if (isset($latestSNIP['$'])) {
                $journal['snip'] = floatval($latestSNIP['$']);
            }
        }
    }
    
    /**
     * Extract Discontinued Status and Coverage Information
     */
    private function extractDiscontinuedStatus($entry, &$journal) {
        global $DEBUG_MODE;
        
        // Check for discontinued status in multiple possible locations
        $discontinued = false;
        $discontinuedYear = null;
        $coverageStart = null;
        $coverageEnd = null;
        
        // Method 1: Check for discontinued flag
        if (isset($entry['discontinued']) && $entry['discontinued'] === 'true') {
            $discontinued = true;
        }
        
        // Method 2: Check for active status
        if (isset($entry['@status']) && strtolower($entry['@status']) === 'discontinued') {
            $discontinued = true;
        }
        
        // Method 3: Check coverage dates
        if (isset($entry['prism:coverageStartYear'])) {
            $coverageStart = $entry['prism:coverageStartYear'];
        }
        
        if (isset($entry['prism:coverageEndYear'])) {
            $coverageEnd = $entry['prism:coverageEndYear'];
            $currentYear = date('Y');
            
            // If coverage ended and it's more than 2 years ago, likely discontinued
            if ($coverageEnd && ($currentYear - intval($coverageEnd)) > 2) {
                $discontinued = true;
                $discontinuedYear = $coverageEnd;
            }
        }
        
        // Method 4: Check publication range
        if (isset($entry['prism:publicationName']) && 
            isset($entry['link']) && 
            is_array($entry['link'])) {
            
            foreach ($entry['link'] as $link) {
                if (isset($link['@href']) && strpos($link['@href'], 'discontinued') !== false) {
                    $discontinued = true;
                    break;
                }
            }
        }
        
        // Method 5: Check if no recent metrics (could indicate discontinued)
        $currentYear = date('Y');
        $hasRecentMetrics = false;
        
        // Check if CiteScore is recent
        if (isset($journal['citescore_year']) && 
            ($currentYear - intval($journal['citescore_year'])) <= 3) {
            $hasRecentMetrics = true;
        }
        
        // Check if SJR is recent
        if (isset($entry['SJRList']['SJR']) && is_array($entry['SJRList']['SJR'])) {
            $latestSJR = end($entry['SJRList']['SJR']);
            if (isset($latestSJR['@year']) && 
                ($currentYear - intval($latestSJR['@year'])) <= 3) {
                $hasRecentMetrics = true;
            }
        }
        
        // If no recent metrics and coverage ended, likely discontinued
        if (!$hasRecentMetrics && $coverageEnd && 
            ($currentYear - intval($coverageEnd)) > 1) {
            $discontinued = true;
            if (!$discontinuedYear) {
                $discontinuedYear = $coverageEnd;
            }
        }
        
        // Set the results
        $journal['discontinued'] = $discontinued;
        $journal['discontinued_year'] = $discontinuedYear;
        $journal['coverage_start'] = $coverageStart;
        $journal['coverage_end'] = $coverageEnd;
    }
    
    /**
     * Extract Open Access Status
     */
    private function extractOpenAccessStatus($entry, &$journal) {
        $openAccess = false;
        $oaType = null;
        
        // Check for open access indicator
        if (isset($entry['openaccess'])) {
            $openAccess = ($entry['openaccess'] === 'true' || $entry['openaccess'] === '1');
        }
        
        // Check for OA type
        if (isset($entry['openAccessType'])) {
            $oaType = $entry['openAccessType'];
            $openAccess = true;
        }
        
        // Check for hybrid OA
        if (isset($entry['oaAllowsAuthorPaid']) && $entry['oaAllowsAuthorPaid'] === 'true') {
            $openAccess = true;
            $oaType = $oaType ? $oaType : 'Hybrid';
        }
        
        $journal['open_access'] = $openAccess;
        $journal['open_access_type'] = $oaType;
    }
    
    /**
     * Extract Publication Type
     */
    private function extractPublicationType($entry, &$journal) {
        $pubType = 'Journal';  // Default
        
        if (isset($entry['prism:aggregationType'])) {
            $type = $entry['prism:aggregationType'];
            switch (strtolower($type)) {
                case 'journal':
                    $pubType = 'Academic Journal';
                    break;
                case 'book':
                    $pubType = 'Book Series';
                    break;
                case 'conference':
                    $pubType = 'Conference Proceedings';
                    break;
                case 'trade':
                    $pubType = 'Trade Publication';
                    break;
                default:
                    $pubType = ucfirst($type);
            }
        }
        
        $journal['publication_type'] = $pubType;
    }
    
    /**
     * Extract Country Information
     */
    private function extractCountryInfo($entry, &$journal) {
        $country = null;
        $countryCode = null;
        
        // Check publisher location
        if (isset($entry['publisher-country'])) {
            $country = $entry['publisher-country'];
        }
        
        // Check for country code
        if (isset($entry['prism:countryCode'])) {
            $countryCode = $entry['prism:countryCode'];
        }
        
        $journal['country'] = $country;
        $journal['country_code'] = $countryCode;
    }
    
    /**
     * Extract Enhanced Subject Areas with Quartiles
     */
    private function extractEnhancedSubjectAreas($entry, &$journal) {
        $subjects = array();
        $subjectDetails = array();
        
        if (isset($entry['subject-area']) && is_array($entry['subject-area'])) {
            foreach ($entry['subject-area'] as $subject) {
                $subjectInfo = array();
                
                if (is_array($subject)) {
                    // Extract subject name
                    if (isset($subject['$'])) {
                        $subjectInfo['name'] = $subject['$'];
                        $subjects[] = $subject['$'];
                    }
                    
                    // Extract subject code
                    if (isset($subject['@code'])) {
                        $subjectInfo['code'] = $subject['@code'];
                    }
                    
                    // Extract subject abbreviation
                    if (isset($subject['@abbrev'])) {
                        $subjectInfo['abbreviation'] = $subject['@abbrev'];
                    }
                    
                    // Get quartile for this subject area
                    $quartileInfo = $this->getSubjectQuartileDetails($entry, $subject);
                    if ($quartileInfo) {
                        $subjectInfo['quartile'] = $quartileInfo['quartile'];
                        $subjectInfo['percentile'] = $quartileInfo['percentile'];
                        $subjectInfo['rank'] = $quartileInfo['rank'];
                        $subjectInfo['total_journals'] = $quartileInfo['total_journals'];
                        $subjectInfo['year'] = $quartileInfo['year'];
                    }
                    
                    $subjectDetails[] = $subjectInfo;
                } elseif (is_string($subject)) {
                    $subjects[] = $subject;
                    $subjectDetails[] = array('name' => $subject);
                }
            }
        }
        
        $journal['subject_areas'] = $subjects;  // Keep original for backward compatibility
        $journal['subject_details'] = $subjectDetails;
    }
    
    /**
     * Get detailed quartile information for specific subject area
     */
    private function getSubjectQuartileDetails($entry, $subject) {
        if (!isset($entry['citeScoreYearInfoList']['citeScoreYearInfo'])) {
            return null;
        }
        
        $yearInfoList = $entry['citeScoreYearInfoList']['citeScoreYearInfo'];
        
        // Get the most recent complete year data
        foreach ($yearInfoList as $yearInfo) {
            if (isset($yearInfo['@status']) && $yearInfo['@status'] === 'Complete' &&
                isset($yearInfo['citeScoreInformationList'][0]['citeScoreInfo'])) {
                
                $citeScoreInfos = $yearInfo['citeScoreInformationList'][0]['citeScoreInfo'];
                
                foreach ($citeScoreInfos as $info) {
                    if (isset($info['citeScoreSubjectRank'])) {
                        foreach ($info['citeScoreSubjectRank'] as $rank) {
                            // Try to match subject by code or name
                            $isMatch = false;
                            
                            if (isset($rank['subjectCode']) && isset($subject['@code']) && 
                                $rank['subjectCode'] === $subject['@code']) {
                                $isMatch = true;
                            } elseif (isset($rank['subjectName']) && isset($subject['$']) &&
                                     strtolower($rank['subjectName']) === strtolower($subject['$'])) {
                                $isMatch = true;
                            }
                            
                            if ($isMatch && isset($rank['percentile'])) {
                                $percentile = floatval($rank['percentile']);
                                
                                // Calculate quartile from percentile
                                if ($percentile >= 75) $quartile = 'Q1';
                                elseif ($percentile >= 50) $quartile = 'Q2';
                                elseif ($percentile >= 25) $quartile = 'Q3';
                                else $quartile = 'Q4';
                                
                                return array(
                                    'quartile' => $quartile,
                                    'percentile' => $percentile,
                                    'rank' => isset($rank['rank']) ? $rank['rank'] : null,
                                    'total_journals' => isset($rank['totalJournals']) ? $rank['totalJournals'] : null,
                                    'year' => $yearInfo['@year']
                                );
                            }
                        }
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * Extract Detailed Coverage Information
     */
    private function extractDetailedCoverage($entry, &$journal) {
        $coverageDetails = array();
        
        // Overall coverage (already extracted)
        $overallCoverage = array(
            'start' => $journal['coverage_start'],
            'end' => $journal['coverage_end']
        );
        
        if ($journal['coverage_start'] && $journal['coverage_end']) {
            $overallCoverage['total_years'] = intval($journal['coverage_end']) - intval($journal['coverage_start']) + 1;
        } elseif ($journal['coverage_start'] && !$journal['coverage_end']) {
            $overallCoverage['total_years'] = date('Y') - intval($journal['coverage_start']) + 1;
            $overallCoverage['status'] = 'Active';
        }
        
        $coverageDetails['overall'] = $overallCoverage;
        
        // Coverage per subject area
        $subjectCoverage = array();
        if (isset($entry['citeScoreYearInfoList']['citeScoreYearInfo'])) {
            foreach ($entry['citeScoreYearInfoList']['citeScoreYearInfo'] as $yearInfo) {
                $year = $yearInfo['@year'];
                
                if (isset($yearInfo['citeScoreInformationList'][0]['citeScoreInfo'])) {
                    foreach ($yearInfo['citeScoreInformationList'][0]['citeScoreInfo'] as $info) {
                        if (isset($info['citeScoreSubjectRank'])) {
                            foreach ($info['citeScoreSubjectRank'] as $rank) {
                                if (isset($rank['subjectCode']) && isset($rank['subjectName'])) {
                                    $subjectCode = $rank['subjectCode'];
                                    $subjectName = $rank['subjectName'];
                                    
                                    if (!isset($subjectCoverage[$subjectCode])) {
                                        $subjectCoverage[$subjectCode] = array(
                                            'name' => $subjectName,
                                            'code' => $subjectCode,
                                            'years' => array()
                                        );
                                    }
                                    
                                    $subjectCoverage[$subjectCode]['years'][] = $year;
                                }
                            }
                        }
                    }
                }
            }
            
            // Process subject coverage data
            foreach ($subjectCoverage as &$coverage) {
                sort($coverage['years']);
                $coverage['first_year'] = reset($coverage['years']);
                $coverage['last_year'] = end($coverage['years']);
                $coverage['total_years'] = count($coverage['years']);
                $coverage['years_list'] = implode(', ', $coverage['years']);
            }
        }
        
        $coverageDetails['by_subject'] = $subjectCoverage;
        $journal['coverage_details'] = $coverageDetails;
    }
    
}

// =============================================================================
// UTILITY FUNCTIONS
// =============================================================================

/**
 * Validasi format ISSN
 */
function validateISSN($issn) {
    $clean = str_replace('-', '', trim($issn));
    return preg_match('/^\d{7}[\dxX]$/', $clean);
}

/**
 * Format quartile untuk tampilan
 */
function formatQuartile($quartile) {
    if (!$quartile) return null;
    
    $q = strtoupper(trim($quartile));
    
    // Convert number to Q format
    if (is_numeric($q)) {
        $q = 'Q' . $q;
    }
    
    return $q;
}

/**
 * Get quartile description
 */
function getQuartileDescription($quartile) {
    $descriptions = array(
        'Q1' => 'Top 25% best journals',
        'Q2' => 'Ranking 25-50%',
        'Q3' => 'Ranking 50-75%', 
        'Q4' => 'Ranking 75-100%'
    );
    
    $q = formatQuartile($quartile);
    return isset($descriptions[$q]) ? $descriptions[$q] : 'Unknown quartile';
}

/**
 * Get quartile color
 */
function getQuartileColor($quartile) {
    $colors = array(
        'Q1' => '#27ae60',  // Green
        'Q2' => '#3498db',  // Blue  
        'Q3' => '#f39c12',  // Orange
        'Q4' => '#e74c3c'   // Red
    );
    
    $q = formatQuartile($quartile);
    return isset($colors[$q]) ? $colors[$q] : '#9b59b6';
}

/**
 * Get discontinued status badge
 */
function getDiscontinuedBadge($discontinued, $discontinuedYear = null) {
    if (!$discontinued) {
        return '<span class="status-badge status-active">Active</span>';
    }
    
    $year = $discontinuedYear ? " ($discontinuedYear)" : '';
    return '<span class="status-badge status-discontinued">Discontinued' . $year . '</span>';
}

/**
 * Get coverage info
 */
function formatCoverageInfo($coverageStart, $coverageEnd) {
    if (!$coverageStart && !$coverageEnd) {
        return 'N/A';
    }
    
    $start = $coverageStart ? $coverageStart : '?';
    $end = $coverageEnd ? $coverageEnd : 'Present';
    
    return $start . ' - ' . $end;
}

/**
 * Get Open Access badge
 */
function getOpenAccessBadge($openAccess, $oaType = null) {
    if (!$openAccess) {
        return '<span class="oa-badge oa-closed">Subscription</span>';
    }
    
    $type = $oaType ? " ($oaType)" : '';
    return '<span class="oa-badge oa-open">Open Access' . $type . '</span>';
}

// =============================================================================
// FORM PROCESSING
// =============================================================================

$result = null;
$error = null;
$searchISSN = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['issn'])) {
    $searchISSN = trim($_POST['issn']);
    
    if (!validateISSN($searchISSN)) {
        $error = 'Format ISSN tidak valid. Gunakan format: 1234-5678 atau 12345678';
    } else {
        try {
            $scopusAPI = new ScopusAPI($SCOPUS_API_KEY);
            $result = $scopusAPI->searchByISSN($searchISSN);
            
            if (!$result['success']) {
                $error = $result['error'];
                $result = null;
            }
        } catch (Exception $e) {
            $error = 'System Error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scopus Journal Metrics Checker | Wizdam</title>
    <meta name="description" content="An API and visual platform to check Scopus-indexed journal metrics by ISSN. Provides real-time CiteScore, Quartile, SJR, and SNIP data for research and integration." />
    <meta name="owner" content="PT. Sangia Research Media and Publishing" />
    <meta name="design" content="Rochmady and Wizdam AI Team" />
    <meta name="generator" content="Wizdam AI v1.8.0" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --light-bg: #f8f9fa;
            --dark-bg: #2c3e50;
            --text-primary: #2c3e50;
            --text-secondary: #7f8c8d;
            --border-color: #e1e8ed;
            --shadow: 0 2px 10px rgba(0,0,0,0.1);
            --radius: 12px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            line-height: 1.6;
            scroll-behavior: smooth;
            background-image: url("https://sdgactioncampaign.org/wp-content/uploads/2024/07/bg-curves-full.svg");
            background-position: top right;
            background-repeat: repeat-x;
            background-size: cover;
        }
        
        /* =====================================================================
           NAVBAR STYLES
           ================================================================== */
        .navbar {
            background: white;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 0;
        }
        
        .navbar-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            height: 70px;
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.5em;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .navbar-brand i {
            font-size: 1.8em;
        }
        
        .navbar-nav {
            display: flex;
            list-style: none;
            gap: 30px;
            align-items: center;
        }
        
        .navbar-nav a {
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 500;
            transition: color 0.3s ease;
            padding: 10px 0;
            position: relative;
        }
        
        .navbar-nav a:hover {
            color: var(--primary-color);
        }
        
        .navbar-nav a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: width 0.3s ease;
        }
        
        .navbar-nav a:hover::after {
            width: 100%;
        }
        
        .navbar-login {
            background: var(--primary-color);
            color: white !important;
            padding: 10px 20px !important;
            border-radius: 27px;
            transition: all 0.3s ease;
        }
        
        .navbar-login:hover {
            background: #2980b9;
            transform: translateY(-2px);
            color: white !important;
        }
        
        .navbar-login::after {
            display: none;
        }
        
        .mobile-menu-toggle {
            display: none;
            flex-direction: column;
            cursor: pointer;
            padding: 10px;
        }
        
        .mobile-menu-toggle span {
            width: 25px;
            height: 3px;
            background: var(--text-primary);
            margin: 3px 0;
            transition: 0.3s;
        }
        
        /* =====================================================================
           MAIN CONTAINER
           ================================================================== */
        .container {
            max-width: 1000px;
            margin: 70px auto;
            /*background: white;*/
            border-radius: var(--radius);
            /*box-shadow: 0 0 50px rgba(0,0,0,0.15);*/
            overflow: hidden;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 70px 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5rem;
            font-weight: 400;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            position: relative;
        }
        
        .header p {
            font-size: 1.2em;
            opacity: 0.9;
        }
        
        /* Form Section */
        .form-section {
            padding: 20px;
            background: #fff;
            margin-top: 27px;
            margin-right: auto;
            margin-left: auto;
            margin-bottom: 27px;
            max-width: 700px;
            border-radius: var(--radius);
            box-shadow: 0 0 50px rgba(0,0,0,0.15);
        }
        
        .form-group {
            /*margin-bottom: 30px;*/
        }
        
        /* Main Label Styling */
        .form-main-label {
            display: block;
            margin-bottom: 12px;
            padding-bottom: 12px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.2em;
        }
        
        /* Floating Input Container */
        .floating-input {
            position: relative;
            margin-bottom: 10px;
        }
        
        .floating-input input {
            width: 100%;
            padding: 20px 25px;
            border: 2px solid #e1e8ed;
            border-radius: 12px;
            font-size: 1.1em;
            transition: all 0.3s ease;
            background: white;
            outline: none;
            font-family: inherit;
        }
        
        .floating-input input:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
            transform: translateY(-2px);
        }
        
        /* Floating Label Styling */
        .floating-label {
            position: absolute;
            left: 17px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.1em;
            color: #7f8c8d;
            pointer-events: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            padding: 0 8px;
            font-weight: 400;
            z-index: 1;
        }
        
        /* Floating Label Active State */
        .floating-input input:focus + .floating-label,
        .floating-input input:not(:placeholder-shown) + .floating-label,
        .floating-input input.has-content + .floating-label {
            top: 0px;
            transform: translateY(-50%);
            font-size: 0.85em;
            color: #3498db;
            font-weight: 600;
            background: white;
        }
        
        /* Floating label aktif saat input fokus */
        .floating-input input:focus + .floating-label {
            color: #3498db; /* warna biru saat fokus */
        }
        
        /* Floating label saat input punya isi tapi tidak fokus */
        .floating-input input:not(:focus):not(:placeholder-shown) + .floating-label,
        .floating-input input.has-content:not(:focus) + .floating-label {
            color: #aaa; /* warna default abu saat tidak fokus */
        }

        /* Error State for Floating Label */
        .floating-input.error input {
            border-color: #e74c3c;
            background-color: #fdf2f2;
        }
        
        .floating-input.error .floating-label {
            color: #e74c3c;
        }
        
        .floating-input.error input:focus + .floating-label,
        .floating-input.error input:not(:placeholder-shown) + .floating-label,
        .floating-input.error input.has-content + .floating-label {
            color: #e74c3c;
        }
        
        .input-row {
            display: flex;
            gap: 20px;
            align-items: stretch;
        }
        
        .input-row .floating-input {
            flex: 1;
            margin-bottom: 0;
        }
        
        .btn-search {
            padding: 18px 35px;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .btn-search:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
        }
        
        .btn-search:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .example-hint {
            margin-top: 15px;
            color: #7f8c8d;
            font-size: 1em;
            background: #ecf0f1;
            padding: 15px;
            border-radius: 0 8px 8px 0;
            border-left: 4px solid #3498db;
        }
        
        /* =====================================================================
           ENHANCED LOADING OVERLAY - MINIMALIST VERSION
           ================================================================== */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            z-index: 9999;
            animation: fadeInOverlay 0.3s ease;
        }
        
        .loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
        }
        
        .loading-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .spinner-container {
            position: relative;
            margin: 0 0 25px 0;
            width: 60px;
            height: 60px;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
            position: relative;
            box-shadow: 0 0 20px rgba(52, 152, 219, 0.3);
        }
        
        .spinner::before {
            content: '';
            position: absolute;
            top: -4px;
            left: -4px;
            right: -4px;
            bottom: -4px;
            border: 4px solid transparent;
            border-top: 4px solid #e74c3c;
            border-radius: 50%;
            animation: spin 2s linear infinite reverse;
        }
        
        .spinner::after {
            content: '';
            position: absolute;
            top: 8px;
            left: 8px;
            right: 8px;
            bottom: 8px;
            border: 2px solid transparent;
            border-top: 2px solid #f39c12;
            border-radius: 50%;
            animation: spin 1.5s linear infinite;
        }
        
        .loading-text {
            font-size: 1.27em;
            font-weight: 500;
            margin-bottom: 14px;
            color: var(--primary-color);
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .loading-status {
            font-size: 1em;
            color: var(--text-secondary);
            margin-bottom: 0;
            min-height: 24px;
            font-weight: 500;
        }
        
        /* Loading Animations */
        @keyframes fadeInOverlay {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Responsive Loading */
        @media (max-width: 768px) {
            .spinner {
                width: 50px;
                height: 50px;
                border-width: 3px;
            }
            
            .spinner::before {
                border-width: 3px;
                top: -3px;
                left: -3px;
                right: -3px;
                bottom: -3px;
            }
            
            .spinner::after {
                border-width: 2px;
                top: 7px;
                left: 7px;
                right: 7px;
                bottom: 7px;
            }
            
            .loading-text {
                font-size: 1.2em;
                margin-bottom: 12px;
            }
            
            .loading-status {
                font-size: 0.9em;
            }
        }
        
        /* Alert Messages */
        .alert {
            margin: 30px 50px;
            padding: 25px;
            border-radius: 12px;
            font-weight: 500;
        }
        
        .alert-error {
            background: #fdf2f2;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .alert-error ul {
            margin-top: 15px;
            margin-left: 20px;
        }
        
        /* Results Section */
        .results {
            padding: 17px;
            /*margin-top: 27px;*/
            /*background: #f8f9fa;*/
            /*border-radius: var(--radius);*/
            /*box-shadow: 0 20px 30px rgba(0,0,0,0.1);*/
        }
        
        .journal-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 30px;
            border-radius: 0 15px 15px 15px;
            margin-bottom: 20px;
            border-left: 6px solid #3498db;
        }
        
        .journal-identity {
            margin-bottom: 20px;
        }
        
        .journal-title {
            font-size: 1.8em;
            color: #2c3e50;
            font-weight: 600;
            line-height: 1.4;
        }
        
        .journal-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .meta-item {
            display: flex;
            flex-direction: column;
        }
        
        .meta-label {
            font-weight: 700;
            color: #34495e;
            margin-bottom: 8px;
            font-size: 0.95em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .meta-value {
            color: #2c3e50;
            font-size: 1.1em;
        }
        
        /* Status Badges */
        .status-badge {
            display: inline-block;
            /*padding: 8px 16px;*/
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 5px 0;
        }
        
        .status-active {
            /*background: linear-gradient(135deg, #27ae60 0%, #229954 100%);*/
            /*color: white;*/
            /*box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);*/
        }
        
        .status-discontinued {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }
        
        /* Coverage Info Styling */
        .coverage-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
            margin: 10px 0;
            font-size: 0.95em;
        }
        
        .coverage-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .coverage-value {
            color: #5a6c7d;
        }
        
        /* Metrics Grid */
        .metrics-container {
            margin-bottom: 30px;
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .metric-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 0 0 15px 15px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--card-color);
        }
        
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 35px rgba(0,0,0,0.1);
        }
        
        .metric-label {
            font-size: 1em;
            color: #7f8c8d;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 700;
        }
        
        .metric-value {
            font-size: 3em;
            font-weight: 700;
            color: var(--card-color);
            margin-bottom: 10px;
            line-height: 1;
        }
        
        .metric-description {
            font-size: 0.95em;
            color: #545e5f;
            line-height: 1.5;
        }
        
        /* Specific metric colors */
        .metric-card.citescore { --card-color: #27ae60; }
        .metric-card.quartile { --card-color: #9b59b6; }
        .metric-card.sjr { --card-color: #3498db; }
        .metric-card.snip { --card-color: #f39c12; }
        
        /* Subject Areas */
        .subject-section {
            background: #f8f9fa;
            background: #fff;
            border-radius: 15px;
            padding: 20px;
        }
        
        .subject-section h3 {
            color: #2c3e50;
            margin-bottom: 25px;
            font-size: 1.4em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .subjects-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
        }
        
        .subject-tag {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            padding: 12px 20px;
            border-radius: 30px;
            font-size: 1.17em;
            font-weight: 500;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .subject-tag:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        /* Open Access Badges */
        .oa-badge {
            display: inline-block;
            padding: 6px 12px;
            margin: 5px 0;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .oa-open {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(39, 174, 96, 0.3);
        }
        
        .oa-closed {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(149, 165, 166, 0.3);
        }
        
        /* Publication Type */
        .publication-type {
            background: #e8f4fd;
            color: #2980b9;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
            /*margin: 5px 0;*/
        }
        
        /* Country Info */
        .country-info {
            color: #2c3e50;
            font-weight: 500;
        }
        
        /* Enhanced Subject Areas */
        .subjects-enhanced {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin: 30px 0;
            margin-bottom: 0;
            /*box-shadow: 0 2px 15px rgba(0,0,0,0.1);*/
        }
        
        .subjects-enhanced h3 {
            color: #2c3e50;
            margin-bottom: 25px;
            font-size: 1.4em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .subject-detailed-list {
            display: grid;
            gap: 15px;
        }
        
        .subject-item-detailed {
            background: #f8f9fa;
            border: 1px solid #e1e8ed;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .subject-item-detailed:hover {
            background: #e3f2fd;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .subject-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 7px;
            border-bottom: 1px solid #3498db;
            padding-bottom: 7px;
        }
        
        .subject-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.1em;
        }
        
        .subject-quartile-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 1em;
            font-weight: 700;
            color: white;
        }
        
        .subject-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            font-size: 0.9em;
        }
        
        .subject-detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .subject-detail-label {
            color: #7f8c8d;
            font-weight: 600;
            font-size: .9em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        
        .subject-detail-value {
            color: #2c3e50;
            font-weight: 600;
            font-size: 18px;
        }
        
        /* Coverage Details */
        .coverage-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            margin-bottom: 0;
            /*box-shadow: 0 2px 15px rgba(0,0,0,0.1);*/
        }
        
        .coverage-section h3 {
            color: #2c3e50;
            margin-bottom: 25px;
            font-size: 1.4em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .coverage-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }
        
        .coverage-overall {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 0 12px 12px 0;
            border-left: 4px solid #27ae60;
        }
        
        .coverage-overall:hover {
            background: #e3f2fd;
            transform: translateY(-2px);
            box-shadow: 0 0 25px rgba(0,0,0,0.1);
        }
        
        .coverage-subjects {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #3498db;
        }
        
        .coverage-overall h4,
        .coverage-subjects h4 {
            margin-bottom: 15px;
            color: #2c3e50;
            font-size: 1.1em;
        }
        
        .coverage-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e1e8ed;
        }
        
        .coverage-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .coverage-subject-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .coverage-years {
            font-size: 0.9em;
            color: #5a6c7d;
        }
        
        .coverage-stat {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .coverage-stat-label {
            color: #7f8c8d;
            font-weight: 500;
        }
        
        .coverage-stat-value {
            color: #2c3e50;
            font-weight: 600;
        }
        
        /* Enhanced Info Grid */
        .enhanced-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin: 30px 0;
        }
        
        .info-card {
            background: white;
            border: 1px solid #e1e8ed;
            border-radius: 15px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 25px rgba(0,0,0,0.1);
        }
        
        .info-card h4 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.2em;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .info-item {
            /*margin-bottom: 15px;*/
        }
        
        .info-label {
            color: #7f8c8d;
            font-weight: 600;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        
        .info-value {
            color: #2c3e50;
            font-weight: 500;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .coverage-grid {
                grid-template-columns: 1fr;
            }
            
            .enhanced-info-grid {
                grid-template-columns: 1fr;
            }
            
            .subject-details {
                grid-template-columns: 1fr;
            }
            
            .subject-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
        
        /* =====================================================================
           CHATBOT STYLES
           ================================================================== */
        .chatbot {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }
        
        .chatbot-toggle {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 1.5em;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(52, 152, 219, 0.3);
            transition: all 0.3s ease;
            animation: pulse 2s infinite;
        }
        
        .chatbot-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 25px rgba(52, 152, 219, 0.4);
        }
        
        .chatbot-window {
            display: none;
            position: absolute;
            bottom: 80px;
            right: 0;
            width: 350px;
            height: 450px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            animation: slideUp 0.3s ease;
        }
        
        .chatbot-header {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            padding: 20px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chatbot-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.2em;
            cursor: pointer;
            padding: 5px;
        }
        
        .chatbot-messages {
            height: 320px;
            padding: 20px;
            overflow-y: auto;
            background: #f8f9fa;
        }
        
        .message {
            margin-bottom: 15px;
            padding: 12px 16px;
            border-radius: 15px;
            max-width: 80%;
            word-wrap: break-word;
        }
        
        .message.bot {
            background: white;
            color: var(--text-primary);
            margin-right: 20%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .message.user {
            background: var(--primary-color);
            color: white;
            margin-left: 20%;
            margin-right: 0;
        }
        
        .chatbot-input {
            padding: 15px;
            border-top: 1px solid #e1e8ed;
            display: flex;
            gap: 10px;
        }
        
        .chatbot-input input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #e1e8ed;
            border-radius: 20px;
            outline: none;
            font-size: 0.9em;
        }
        
        .chatbot-input button {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .chatbot-input button:hover {
            background: #2980b9;
            transform: scale(1.1);
        }
        
        /* ====================================================================
           BACK TO TOP BUTTON
           ================================================================== */
        .back-to-top {
            position: fixed;
            bottom: 110px;
            right: 35px;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2em;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 999;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        }
        
        .back-to-top.visible {
            opacity: 1;
            visibility: visible;
        }
        
        .back-to-top:hover {
            transform: translateY(-3px) scale(1.1);
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.4);
        }
        
        /* =====================================================================
           FOOTER STYLES
           ================================================================== */
        .footer {
            background: var(--dark-bg);
            color: white;
            padding: 50px 0 30px;
            margin-top: 20px;
            background-color: #34495e;
            background-image: url("//assets.sangia.org/img/SDGs_icon_SVG/border-sdga.svg");
            background-position: top center;
            background-repeat: no-repeat;
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            grid-template-columns: repeat(5, 1fr);
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .brand-wide {
            grid-column: span 2;
        }
        
        .footer-section h3 {
            margin-bottom: 20px;
            font-size: 1.2em;
            color: white;
        }
        
        .footer-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.5em;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .footer-brand i {
            font-size: 1.8em;
        }
        
        .brand-description {
            color: #bdc3c7; 
            line-height: 1.6;
            margin-bottom: 40px;
        }
        
        .footer-section ul {
            list-style: none;
        }
        
        .footer-section ul li {
            margin-bottom: 10px;
        }
        
        .footer-section ul li a {
            color: #bdc3c7;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer-section ul li a:hover {
            color: var(--primary-color);
        }
        
        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(52, 152, 219, 0.1);
            color: var(--primary-color);
            border-radius: 50%;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .social-links a:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .footer-copyright {
            color: #bdc3c7;
            font-size: 14px;
        }
        
        .copyright-title {
            
        }
        
        .footer-support {
            font-size: 13px;
            color: #bdc3c7;
        }
        
        .footer-bottom-links {
            display: flex;
            gap: 30px;
            list-style: none;
        }
        
        .footer-bottom-links a {
            color: #bdc3c7;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }
        
        /* =====================================================================
           ANIMATIONS
           ================================================================== */
        @keyframes pulse {
            0% {
                box-shadow: 0 4px 20px rgba(52, 152, 219, 0.3);
            }
            50% {
                box-shadow: 0 4px 20px rgba(52, 152, 219, 0.6);
            }
            100% {
                box-shadow: 0 4px 20px rgba(52, 152, 219, 0.3);
            }
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* =====================================================================
           RESPONSIVE DESIGN
           ================================================================== */
        @media (max-width: 768px) {
            .navbar-nav {
                display: none;
            }
            
            .mobile-menu-toggle {
                display: flex;
            }
            
            .container {
                margin: 10px;
                border-radius: 0;
            }
            
            .form-section, .results {
                padding: 30px 25px;
            }
            
            .input-row {
                flex-direction: column;
                gap: 15px;
            }
            
            .floating-input input {
                padding: 20px;
                font-size: 1em;
            }
            
            .floating-label {
                left: 20px;
                font-size: 1em;
            }
            
            .floating-input input:focus + .floating-label,
            .floating-input input:not(:placeholder-shown) + .floating-label,
            .floating-input input.has-content + .floating-label {
                font-size: 0.8em;
                top: 0px;
            }
            
            .metrics-grid {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .metric-value {
                font-size: 2.5em;
            }
            
            .footer-content {
                grid-template-columns: 1fr 1fr;
                gap: 30px;
            }
            
            .chatbot {
                bottom: 20px;
                right: 20px;
            }
            
            .chatbot-window {
                width: 300px;
                height: 400px;
            }
            
            .back-to-top {
                right: 25px;
                bottom: 100px;
            }
        }
        
        @media (max-width: 480px) {
            .navbar-container {
                padding: 0 16px;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .chatbot-window {
                width: calc(100vw - 40px);
                right: -280px;
            }
        }
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="#" class="navbar-brand">
                <i class="fas fa-chart-line"></i>
                WIZDAM AI-scola
            </a>
            
            <ul class="navbar-nav">
                <li><a href="#about">Search</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#team">Team</a></li>
                <li><a href="#help">Help</a></li>
                <li><a href="#login" class="navbar-login">Login</a></li>
            </ul>
            
            <div class="mobile-menu-toggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

<section id="sangia" class="body">
    
    <!-- Header -->
    <div class="header">
        <h1>Scopus Journal Metrics Checker</h1>
        <p>Dapatkan informasi CiteScore, Quartile, SJR, dan SNIP jurnal berdasarkan ISSN</p>
    </div>
        
    <!-- Form Section -->
    <div class="form-section">
        <form method="POST" id="journalForm">
            <div class="form-group">
                <div class="input-row">
                    <div class="floating-input">
                        <input 
                            type="text" 
                            id="issn" 
                            name="issn" 
                            value="<?php echo htmlspecialchars($searchISSN); ?>"
                            placeholder=" "
                            maxlength="9"
                            required
                        >
                        <div class="floating-label">Masukkan ISSN Jurnal</div>
                    </div>
                    <button type="submit" class="btn-search" id="searchBtn">Cek Jurnal</button>
                </div>
                <div class="example-hint">
                    💡 <strong>Tips: Untuk testing gunakan contoh ISSN berikut</strong><br>
                      2076-3417 (Applied Sciences) • 1999-4915 (Viruses) • 2071-1050 (Sustainability)
                </div>
            </div>
        </form>
            
        <div class="loading-overlay" id="loading">
            <div class="loading">
                <div class="loading-container">
                    <div class="spinner-container">
                        <div class="spinner"></div>
                    </div>
                    <div class="loading-text">Mencari Data Jurnal</div>
                    <div class="loading-status" id="loadingStatus">Memproses permintaan...</div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($error || ($result && $result['success'])): ?>    
    <div class="container">
        
        <!-- Error Messages -->
        <?php if ($error): ?>
            <div class="alert alert-error">
                <strong>❌ Error:</strong> <?php echo htmlspecialchars($error); ?>
                <br><br>
                <strong>Tips mengatasi error:</strong>
                <ul>
                    <li>Pastikan format ISSN benar (8 digit dengan/tanpa tanda strip)</li>
                    <li>Jurnal harus terindeks di Scopus database</li>
                    <li>Periksa koneksi internet Anda</li>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Results Section -->
        <?php if ($result && $result['success']): ?>
            <div class="results">
                <!-- Journal Header -->
                <div class="journal-header">
                    <div class="journal-identity">
                        <div class="journal-title"><?php echo htmlspecialchars($result['title']); ?></div>
                        <span class="meta-value">ISSN: <?php echo htmlspecialchars($result['issn']); ?></span>
                    </div>
                    <div class="journal-meta">
                        <div class="meta-item">
                            <span class="meta-label">Publisher</span>
                            <span class="meta-value"><?php echo htmlspecialchars($result['publisher']); ?></span>
                        </div>
                        <?php if ($result['scopus_id']): ?>
                        <div class="meta-item">
                            <span class="meta-label">Scopus ID</span>
                            <a class="link-action" href="https://www.scopus.com/sourceid/<?php echo htmlspecialchars($result['scopus_id']); ?>" target="_blank"><span class="meta-value"><?php echo htmlspecialchars($result['scopus_id']); ?></span></a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Enhanced Information Grid -->
                <?php if ($result && $result['success']): ?>
                <div class="enhanced-info-grid">
                    
                    <!-- Basic Information Card -->
                    <div class="info-card">
                        <h4>Publication Type</h4>
                        <div class="info-item">
                            <div class="info-value">
                                <span class="publication-type"><?php echo htmlspecialchars(isset($result['publication_type']) ? $result['publication_type'] : 'Academic Journal'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Basic Information Card -->
                    <div class="info-card">
                        <h4>Access Model</h4>
                        <div class="info-item">
                            <div class="info-value">
                                <?php echo getOpenAccessBadge(isset($result['open_access']) ? $result['open_access'] : false, isset($result['open_access_type']) ? $result['open_access_type'] : null); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Basic Information Card -->
                    <div class="info-card">
                        <h4>Journal Status</h4>                    
                        <div class="meta-item">
                            <span class="meta-value"><?php echo getDiscontinuedBadge($result['discontinued'], $result['discontinued_year']); ?></span>
                        </div>
                        <?php if ($result['coverage_start'] || $result['coverage_end']): ?>
                        <div class="meta-item">
                            <span class="meta-label">Coverage Period</span>
                            <span class="meta-value"><?php echo formatCoverageInfo($result['coverage_start'], $result['coverage_end']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Basic Information Card -->
                    <?php if (!empty($result['country'])): ?>
                    <div class="info-card">
                        <h4>Country</h4>
                        <div class="info-item">
                            <div class="info-value country-info">
                                <?php echo htmlspecialchars($result['country']); ?>
                                <?php if (!empty($result['country_code'])): ?>
                                    (<?php echo htmlspecialchars($result['country_code']); ?>)
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                </div>
                <?php endif; ?>
                
                <!-- Metrics Grid -->
                <div class="metrics-container">
                    <div class="metrics-grid">
                        <!-- CiteScore -->
                        <?php if ($result['citescore']): ?>
                        <div class="metric-card citescore">
                            <div class="metric-label">CiteScore</div>
                            <div class="metric-value"><?php echo number_format($result['citescore'], 2); ?></div>
                            <div class="metric-description">
                                Rata-rata sitasi per dokumen dalam 3 tahun terakhir
                                <?php if ($result['citescore_year']): ?>
                                    <br><strong>Tahun: <?php echo $result['citescore_year']; ?></strong>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Quartile -->
                        <?php if ($result['quartile']): ?>
                        <div class="metric-card quartile" style="--card-color: <?php echo getQuartileColor($result['quartile']); ?>">
                            <div class="metric-label">Quartile</div>
                            <div class="metric-value"><?php echo formatQuartile($result['quartile']); ?></div>
                            <div class="metric-title"><?php echo getQuartileDescription($result['quartile']); ?></div>
                            <div class="metric-description">
                                <?php if ($result['percentile']): ?>
                                <div class="percentile">
                                        <strong>Percentile: <?php echo number_format($result['percentile'], 1); ?>%</strong>
                                </div>
                                <?php endif; ?>
                                <div class="rank quartile_year">
                                    <?php if (isset($result['rank'])): ?>
                                        <span>Rank: <?php echo $result['rank']; ?></span>
                                    <?php endif; ?>
                                    <?php if (isset($result['quartile_year'])): ?>
                                        <span> • Year: <?php echo $result['quartile_year']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if (isset($result['quartile_source'])): ?>
                                <div class="quartile_source">
                                    <small>
                                        <span style="color: <?php echo $result['quartile_source'] === 'SJR' ? '#3498db' : '#9b59b6'; ?>; font-weight: 600;">Based on <?php echo $result['quartile_source']; ?> ranking
                                        <?php if ($result['quartile_source'] === 'CiteScore'): ?></span>
                                    </small>
                                     • 
                                    <small>
                                        <span style="color: #e67e22;">May differ from SJR website</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php else: ?>
                        <!-- Show debug info when quartile not found -->
                        <div class="metric-card" style="--card-color: #95a5a6; opacity: 0.7; border: 2px dashed #bdc3c7;">
                            <div class="metric-label">Quartile</div>
                            <div class="metric-value" style="font-size: 1.5em; color: #7f8c8d;">N/A</div>
                            <div class="metric-description">
                                Data quartile tidak ditemukan dalam response API
                                <?php if ($DEBUG_MODE && isset($result['debug_info'])): ?>
                                    <br><small style="color: #e74c3c;">
                                        <strong>Debug mode aktif</strong> - Cek error log untuk detail struktur API
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- SJR -->
                        <?php if ($result['sjr']): ?>
                        <div class="metric-card sjr">
                            <div class="metric-label">SJR</div>
                            <div class="metric-value"><?php echo number_format($result['sjr'], 3); ?></div>
                            <div class="metric-title">SCImago Journal Rank</div>
                            <div class="metric-description">
                                Mengukur pengaruh ilmiah berdasarkan jaringan sitasi
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- SNIP -->
                        <?php if ($result['snip']): ?>
                        <div class="metric-card snip">
                            <div class="metric-label">SNIP</div>
                            <div class="metric-value"><?php echo number_format($result['snip'], 3); ?></div>
                            <div class="metric-title">Source Normalized Impact per Paper</div>
                            <div class="metric-description">
                                Memperhitungkan karakteristik bidang ilmu
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- No Metrics Warning -->
                    <?php if (!$result['citescore'] && !$result['quartile'] && !$result['sjr'] && !$result['snip']): ?>
                        <div class="alert alert-error">
                            <strong>⚠️ Perhatian:</strong> Jurnal ditemukan di Scopus, tetapi data metrik belum tersedia atau belum diperbarui.
                            <br>Kemungkinan jurnal masih baru atau sedang dalam proses perhitungan metrik.
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Subject Areas -->
                <?php if (!empty($result['subject_areas'])): ?>
                <div class="subject-section">
                    <h3>Subject Areas</h3>
                    <div class="subjects-container">
                        <?php foreach ($result['subject_areas'] as $subject): ?>
                            <div class="subject-tag"><?php echo htmlspecialchars($subject); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Discontinued Warning -->
                <?php if ($result['discontinued']): ?>
                <div class="alert alert-error">
                    <strong>⚠️ Jurnal Discontinued:</strong> Jurnal ini sudah tidak lagi aktif di Scopus<?php echo $result['discontinued_year'] ? ' sejak tahun ' . $result['discontinued_year'] : ''; ?>.
                    <br>Artikel yang dipublikasikan di jurnal ini mungkin tidak akan mendapatkan metrik terbaru atau pengindeksan baru.
                    <?php if ($result['coverage_end']): ?>
                        <br><strong>Periode coverage terakhir:</strong> <?php echo formatCoverageInfo($result['coverage_start'], $result['coverage_end']); ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Enhanced Subject Areas with Quartiles -->
                <?php if (!empty($result['subject_details'])): ?>
                <div class="subjects-enhanced">
                    <h3>Subject Areas & Rankings</h3>
                    <div class="subject-detailed-list">
                        <?php foreach ($result['subject_details'] as $subject): ?>
                            <div class="subject-item-detailed">
                                <div class="subject-header">
                                    <div class="subject-name"><?php echo htmlspecialchars($subject['name']); ?></div>
                                    <?php if (isset($subject['quartile'])): ?>
                                        <div class="subject-quartile-badge" style="background-color: <?php echo getQuartileColor($subject['quartile']); ?>">
                                            <?php echo $subject['quartile']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="subject-details">
                                    <?php if (isset($subject['code'])): ?>
                                    <div class="subject-detail-item">
                                        <div class="subject-detail-label">Subject Code</div>
                                        <div class="subject-detail-value"><?php echo htmlspecialchars($subject['code']); ?></div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($subject['percentile'])): ?>
                                    <div class="subject-detail-item">
                                        <div class="subject-detail-label">Percentile</div>
                                        <div class="subject-detail-value"><?php echo number_format($subject['percentile'], 1); ?>%</div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($subject['rank'])): ?>
                                    <div class="subject-detail-item">
                                        <div class="subject-detail-label">Rank</div>
                                        <div class="subject-detail-value"><?php echo $subject['rank']; ?></div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($subject['total_journals'])): ?>
                                    <div class="subject-detail-item">
                                        <div class="subject-detail-label">Total Journals</div>
                                        <div class="subject-detail-value"><?php echo $subject['total_journals']; ?></div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($subject['year'])): ?>
                                    <div class="subject-detail-item">
                                        <div class="subject-detail-label">Year</div>
                                        <div class="subject-detail-value"><?php echo $subject['year']; ?></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Coverage Details -->
                <?php if (!empty($result['coverage_details'])): ?>
                <div class="coverage-section">
                    <h3>Coverage Information</h3>
                    <div class="coverage-grid">
                        
                        <!-- Overall Coverage -->
                        <div class="coverage-overall">
                            <h4>Overall Coverage</h4>
                            
                            <?php if ($result['coverage_details']['overall']['start']): ?>
                            <div class="coverage-stat">
                                <span class="coverage-stat-label">Start Year:</span>
                                <span class="coverage-stat-value"><?php echo $result['coverage_details']['overall']['start']; ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($result['coverage_details']['overall']['end']): ?>
                            <div class="coverage-stat">
                                <span class="coverage-stat-label">End Year:</span>
                                <span class="coverage-stat-value"><?php echo $result['coverage_details']['overall']['end']; ?></span>
                            </div>
                            <?php else: ?>
                            <div class="coverage-stat">
                                <span class="coverage-stat-label">Status:</span>
                                <span class="coverage-stat-value">Active</span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($result['coverage_details']['overall']['total_years'])): ?>
                            <div class="coverage-stat">
                                <span class="coverage-stat-label">Total Years:</span>
                                <span class="coverage-stat-value"><?php echo $result['coverage_details']['overall']['total_years']; ?> years</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Coverage by Subject -->
                        <?php if (!empty($result['coverage_details']['by_subject'])): ?>
                        <div class="coverage-subjects">
                            <h4>Coverage by Subject Area</h4>
                            
                            <?php foreach ($result['coverage_details']['by_subject'] as $coverage): ?>
                            <div class="coverage-item">
                                <div class="coverage-subject-name"><?php echo htmlspecialchars($coverage['name']); ?></div>
                                <div class="coverage-years">
                                    <?php echo $coverage['first_year']; ?> - <?php echo $coverage['last_year']; ?> 
                                    (<?php echo $coverage['total_years']; ?> years)
                                </div>
                                <div class="coverage-years">
                                    <small>Years: <?php echo $coverage['years_list']; ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
        <?php endif; ?>
        
    </div>
    <?php endif; ?>
    
    <!-- CHATBOT -->
    <div class="chatbot" id="chatbot">
        <button class="chatbot-toggle" id="chatbotToggle">
            <i class="fas fa-comments"></i>
        </button>
        
        <div class="chatbot-window" id="chatbotWindow">
            <div class="chatbot-header">
                <span>🤖 WIZDAM Assistant</span>
                <button class="chatbot-close" id="chatbotClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="chatbot-messages" id="chatbotMessages">
                <div class="message bot">
                    👋 Halo! Saya WIZDAM Assistant. Saya siap membantu Anda dengan:
                    <br><br>
                    • Cara menggunakan aplikasi
                    <br>
                    • Penjelasan metrik jurnal
                    <br>
                    • Tips mencari jurnal berkualitas
                    <br><br>
                    Ada yang bisa saya bantu?
                </div>
            </div>
            
            <div class="chatbot-input">
                <input type="text" id="chatbotInput" placeholder="Ketik pertanyaan Anda...">
                <button id="chatbotSend">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- BACK TO TOP BUTTON -->
    <button class="back-to-top" id="backToTop">
        <i class="fas fa-arrow-up"></i>
    </button>

</section>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-section brand-wide">
                    <div class="footer-brand">
                        <i class="fas fa-chart-line"></i>
                        WIZDAM AI-scola
                    </div>
                    <div class="brand-description">
                        <p>Platform visualisasi hasil analisis metrik jurnal ilmiah yang memberikan informasi terkini dan akurat tentang CiteScore, Quartile, SJR, dan SNIP dari database Scopus.
                        </p>
                    </div>
                    <div class="footer-support">Developed by Rochmady & Wizdam Team. | Contact: rochmady at sangia.org | Data powered by Scopus API</div>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Platform</h3>
                    <ul>
                        <li><a href="#">Journal Checker</a></li>
                        <li><a href="#">Bulk Analysis</a></li>
                        <li><a href="#">API Access</a></li>
                        <li><a href="#">Mobile App</a></li>
                        <li><a href="#">Analytics Dashboard</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Resources</h3>
                    <ul>
                        <li><a href="#">Documentation</a></li>
                        <li><a href="#">User Guide</a></li>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Video Tutorials</a></li>
                        <li><a href="#">Blog</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Company</h3>
                    <ul>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Our Team</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Contact</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="footer-copyright">
                    <div class="copyright-title">© 2025 Wizdam by PT. Sangia Research Media and Publishing. All rights reserved.</div>
                </div>
                <ul class="footer-bottom-links">
                    <li><a href="#">Terms of Service</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Cookie Policy</a></li>
                    <li><a href="#">Accessibility</a></li>
                </ul>
            </div>
        </div>
    </footer>
    
    <script>
        // =====================================================================
        // GLOBAL VARIABLES
        // =====================================================================
        let loadingTimer;
        let loadingStartTime;
        let currentStep = 0;
        
        // =====================================================================
        // LOADING SYSTEM WITH INTERACTIVE OVERLAY
        // =====================================================================
        function startLoading() {
            const loadingOverlay = document.getElementById('loading');
            const searchBtn = document.getElementById('searchBtn');
            const status = document.getElementById('loadingStatus');
            
            // Show loading overlay with smooth animation
            loadingOverlay.style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevent body scroll
            searchBtn.disabled = true;
            
            // Start timer (for internal tracking only)
            loadingStartTime = Date.now();
            currentStep = 0;
            
            // Update status every 2 seconds
            loadingTimer = setInterval(() => {
                const elapsed = Date.now() - loadingStartTime;
                updateLoadingStatus(elapsed);
            }, 2000);
        }
        
        function updateLoadingStatus(elapsed) {
            const status = document.getElementById('loadingStatus');
            
            // Update status based on elapsed time
            if (elapsed < 2000) {
                status.textContent = 'Menghubungi server Scopus...';
            } else if (elapsed < 4000) {
                status.textContent = 'Memproses permintaan API...';
            } else if (elapsed < 6000) {
                status.textContent = 'Menganalisis data jurnal...';
            } else if (elapsed < 8000) {
                status.textContent = 'Menghitung metrik dan quartile...';
            } else {
                status.textContent = 'Menyelesaikan proses...';
            }
        }
        
        function stopLoading() {
            if (loadingTimer) {
                clearInterval(loadingTimer);
            }
            
            const loadingOverlay = document.getElementById('loading');
            const searchBtn = document.getElementById('searchBtn');
            
            // Hide loading after brief delay with fade out
            setTimeout(() => {
                loadingOverlay.style.opacity = '0';
                
                setTimeout(() => {
                    loadingOverlay.style.display = 'none';
                    loadingOverlay.style.opacity = '1'; // Reset for next time
                    document.body.style.overflow = ''; // Restore body scroll
                    searchBtn.disabled = false;
                }, 300);
            }, 1000);
        }
        
        // =====================================================================
        // FORM HANDLING
        // =====================================================================
        document.getElementById('journalForm').addEventListener('submit', function(e) {
            startLoading();
            
            // For demonstration, we'll simulate the loading
            // In real implementation, this would be handled by PHP
            <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
            // If not a real form submission, simulate loading for demo
            setTimeout(() => {
                stopLoading();
            }, 8000);
            <?php else: ?>
            // Real form submission - stop loading after page loads
            window.addEventListener('load', () => {
                setTimeout(stopLoading, 500);
            });
            <?php endif; ?>
        });
        
        // =====================================================================
        // CHATBOT FUNCTIONALITY
        // =====================================================================
        const chatbot = {
            isOpen: false,
            
            responses: {
                greeting: [
                    "Halo! Ada yang bisa saya bantu?",
                    "Selamat datang di WIZDAM! Bagaimana saya bisa membantu Anda?",
                    "Hi! Saya siap membantu Anda dengan analisis jurnal."
                ],
                
                quartile: [
                    "Quartile adalah ranking jurnal berdasarkan CiteScore. Q1 = 25% teratas (terbaik), Q2 = 25-50%, Q3 = 50-75%, Q4 = 75-100% (terendah).",
                    "Q1 menunjukkan jurnal berkualitas tinggi, Q4 menunjukkan jurnal dengan impact yang lebih rendah."
                ],
                
                citescore: [
                    "CiteScore mengukur rata-rata sitasi per dokumen yang diterbitkan jurnal dalam 3 tahun terakhir. Semakin tinggi nilai CiteScore, semakin berpengaruh jurnal tersebut.",
                    "CiteScore dihitung dari database Scopus dan merupakan indikator penting kualitas jurnal."
                ],
                
                sjr: [
                    "SJR (SCImago Journal Rank) mengukur pengaruh ilmiah jurnal berdasarkan jaringan sitasi. SJR mempertimbangkan prestise jurnal yang mengutip.",
                    "SJR memberikan bobot lebih tinggi untuk sitasi dari jurnal bergengsi tinggi."
                ],
                
                snip: [
                    "SNIP (Source Normalized Impact per Paper) memperhitungkan karakteristik bidang ilmu. SNIP menormalkan perbedaan perilaku sitasi antar disiplin ilmu.",
                    "SNIP memungkinkan perbandingan yang adil antara jurnal dari bidang ilmu yang berbeda."
                ],
                
                discontinued: [
                    "Jurnal discontinued artinya jurnal tersebut sudah tidak aktif lagi di Scopus. Artikel yang dipublikasikan mungkin tidak mendapat metrik terbaru.",
                    "Status discontinued biasanya terjadi jika jurnal berhenti terbit atau tidak memenuhi standar Scopus."
                ],
                
                help: [
                    "Untuk menggunakan WIZDAM:\n1. Masukkan ISSN jurnal (8 digit)\n2. Klik 'Cari Jurnal'\n3. Tunggu analisis selesai\n4. Lihat hasil metrik jurnal",
                    "Anda bisa menggunakan format ISSN: 1234-5678 atau 12345678. Contoh ISSN untuk testing: 2076-3417, 1999-4915, 2071-1050."
                ],
                
                error: [
                    "Jika terjadi error, pastikan:\n• Format ISSN benar (8 digit)\n• Jurnal terindeks di Scopus\n• Koneksi internet stabil\n• API key valid",
                    "Error umum biasanya karena jurnal tidak terdaftar di Scopus atau format ISSN salah."
                ],
                
                default: [
                    "Maaf, saya tidak yakin dengan pertanyaan itu. Coba tanya tentang quartile, citescore, sjr, snip, discontinued, atau cara menggunakan aplikasi.",
                    "Saya bisa membantu menjelaskan metrik jurnal atau cara menggunakan WIZDAM. Ada pertanyaan spesifik?",
                    "Silakan tanya tentang: cara mencari jurnal, penjelasan metrik, status discontinued, atau troubleshooting error."
                ]
            },
            
            init() {
                const toggle = document.getElementById('chatbotToggle');
                const close = document.getElementById('chatbotClose');
                const send = document.getElementById('chatbotSend');
                const input = document.getElementById('chatbotInput');
                
                toggle.addEventListener('click', () => this.toggle());
                close.addEventListener('click', () => this.close());
                send.addEventListener('click', () => this.sendMessage());
                input.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') this.sendMessage();
                });
                
                // Auto-open after 3 seconds for first visit
                setTimeout(() => {
                    if (!this.isOpen && !localStorage.getItem('chatbotShown')) {
                        this.open();
                        localStorage.setItem('chatbotShown', 'true');
                    }
                }, 3000);
            },
            
            toggle() {
                this.isOpen ? this.close() : this.open();
            },
            
            open() {
                document.getElementById('chatbotWindow').style.display = 'block';
                this.isOpen = true;
            },
            
            close() {
                document.getElementById('chatbotWindow').style.display = 'none';
                this.isOpen = false;
            },
            
            sendMessage() {
                const input = document.getElementById('chatbotInput');
                const message = input.value.trim();
                
                if (!message) return;
                
                this.addMessage(message, 'user');
                input.value = '';
                
                // Simulate typing delay
                setTimeout(() => {
                    const response = this.generateResponse(message);
                    this.addMessage(response, 'bot');
                }, 500 + Math.random() * 1000);
            },
            
            addMessage(text, sender) {
                const messages = document.getElementById('chatbotMessages');
                const message = document.createElement('div');
                message.className = `message ${sender}`;
                message.innerHTML = text.replace(/\n/g, '<br>');
                messages.appendChild(message);
                messages.scrollTop = messages.scrollHeight;
            },
            
            generateResponse(message) {
                const msg = message.toLowerCase();
                
                if (msg.includes('halo') || msg.includes('hai') || msg.includes('hello')) {
                    return this.getRandomResponse('greeting');
                } else if (msg.includes('quartile') || msg.includes('q1') || msg.includes('q2')) {
                    return this.getRandomResponse('quartile');
                } else if (msg.includes('citescore') || msg.includes('cite score')) {
                    return this.getRandomResponse('citescore');
                } else if (msg.includes('sjr') || msg.includes('scimago')) {
                    return this.getRandomResponse('sjr');
                } else if (msg.includes('snip')) {
                    return this.getRandomResponse('snip');
                } else if (msg.includes('discontinued') || msg.includes('tidak aktif')) {
                    return this.getRandomResponse('discontinued');
                } else if (msg.includes('help') || msg.includes('bantuan') || msg.includes('cara')) {
                    return this.getRandomResponse('help');
                } else if (msg.includes('error') || msg.includes('masalah') || msg.includes('tidak bisa')) {
                    return this.getRandomResponse('error');
                } else {
                    return this.getRandomResponse('default');
                }
            },
            
            getRandomResponse(category) {
                const responses = this.responses[category];
                return responses[Math.floor(Math.random() * responses.length)];
            }
        };
        
        // =====================================================================
        // BACK TO TOP FUNCTIONALITY
        // =====================================================================
        const backToTop = {
            init() {
                const button = document.getElementById('backToTop');
                
                window.addEventListener('scroll', () => {
                    if (window.pageYOffset > 300) {
                        button.classList.add('visible');
                    } else {
                        button.classList.remove('visible');
                    }
                });
                
                button.addEventListener('click', () => {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });
            }
        };
        
        // =====================================================================
        // ISSN INPUT FORMATTING
        // =====================================================================
        document.getElementById('issn').addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^0-9xX]/g, '');
            
            if (value.length > 4) {
                value = value.substr(0, 4) + '-' + value.substr(4, 4);
            }
            
            if (value.length > 9) {
                value = value.substr(0, 9);
            }
            
            e.target.value = value;
            
            // Update has-content class for floating label
            if (value) {
                e.target.classList.add('has-content');
            } else {
                e.target.classList.remove('has-content');
            }
        });
        
        // Input validation with proper floating label handling
        document.getElementById('issn').addEventListener('blur', function(e) {
            const issn = e.target.value.replace('-', '');
            const isValid = /^\d{7}[\dxX]$/.test(issn);
            const container = e.target.closest('.floating-input');
            
            // Remove previous error state
            container.classList.remove('error');
            
            if (e.target.value && !isValid) {
                container.classList.add('error');
            }
            
            // Ensure floating label stays up if there's content
            if (e.target.value) {
                e.target.classList.add('has-content');
            } else {
                e.target.classList.remove('has-content');
            }
        });
        
        // =====================================================================
        // MOBILE MENU TOGGLE
        // =====================================================================
        document.querySelector('.mobile-menu-toggle').addEventListener('click', function() {
            const navMenu = document.querySelector('.navbar-nav');
            navMenu.style.display = navMenu.style.display === 'flex' ? 'none' : 'flex';
        });
        
        // =====================================================================
        // INITIALIZE ALL COMPONENTS
        // =====================================================================
        window.addEventListener('load', function() {
            // Initialize floating label state
            const issnInput = document.getElementById('issn');
            if (issnInput.value) {
                issnInput.classList.add('has-content');
            }
            
            // Focus on ISSN input
            issnInput.focus();
            
            // Initialize components
            chatbot.init();
            backToTop.init();
            
            // Add loading animation to existing results (if any)
            const results = document.querySelector('.results');
            if (results) {
                results.style.animation = 'fadeInUp 0.6s ease';
            }
            
            // Stop loading if page loaded with results
            <?php if ($result || $error): ?>
            setTimeout(stopLoading, 500);
            <?php endif; ?>
        });
        
        // =====================================================================
        // KEYBOARD SHORTCUTS
        // =====================================================================
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + K to focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.getElementById('issn').focus();
            }
            
            // Escape to close chatbot
            if (e.key === 'Escape' && chatbot.isOpen) {
                chatbot.close();
            }
        });
        
        // =====================================================================
        // SMOOTH SCROLLING FOR NAVBAR LINKS
        // =====================================================================
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
    
    <!-- Default Statcounter code for Wizdam AI  -->
    <script type="text/javascript">
        var sc_project=13147827; 
        var sc_invisible=1; 
        var sc_security="751ecd6a"; 
    </script>
    <script type="text/javascript"
    src="https://www.statcounter.com/counter/counter.js" async></script>
    <noscript>
        <div class="statcounter"><a title="Web Analytics" href="https://statcounter.com/" target="_blank"><img class="statcounter" src="https://c.statcounter.com/13147827/0/751ecd6a/1/" alt="Web Analytics" referrerPolicy="no-referrer-when-downgrade"></a>
        </div>
    </noscript>
    <!-- End of Statcounter Code -->
    
</body>
</html>
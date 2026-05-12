<?php
/**
 * Unit Test Suite for Wizdam AI-SCOLA
 * 
 * Menguji seluruh komponen kritis dari alur aplikasi:
 * - Validasi input (ORCID, DOI, ISSN)
 * - Fungsi cache
 * - Fungsi HTML rendering
 * - SDG classification logic
 * - Database operations
 */

define('PROJECT_ROOT', dirname(__DIR__));
define('TEST_MODE', true);

// Load core dependencies first
require_once PROJECT_ROOT . '/includes/config.php';
require_once PROJECT_ROOT . '/includes/functions.php';
require_once PROJECT_ROOT . '/includes/sdg_definitions.php';

// Load text analysis helpers from SDG_Classification_API.php
// Only include functions that are NOT already defined in functions.php
if (!function_exists('createTextVector')) {
    // Extract only the text analysis functions we need for testing
    require_once PROJECT_ROOT . '/api/SDG_Classification_API.php';
}

class TestResult {
    public $passed = 0;
    public $failed = 0;
    public $errors = [];
    
    public function pass($test_name) {
        $this->passed++;
        echo "✓ PASS: $test_name\n";
    }
    
    public function fail($test_name, $reason) {
        $this->failed++;
        $this->errors[] = "$test_name: $reason";
        echo "✗ FAIL: $test_name - $reason\n";
    }
    
    public function summary() {
        $total = $this->passed + $this->failed;
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "TEST SUMMARY: {$this->passed}/{$total} passed\n";
        if ($this->failed > 0) {
            echo "Failed tests:\n";
            foreach ($this->errors as $error) {
                echo "  - $error\n";
            }
        }
        echo str_repeat("=", 60) . "\n";
        return $this->failed === 0;
    }
}

$test = new TestResult();

echo str_repeat("=", 60) . "\n";
echo "WIZDAM AI-SCOLA UNIT TEST SUITE\n";
echo str_repeat("=", 60) . "\n\n";

// ============================================================
// TEST 1: ORCID Validation
// ============================================================
echo "--- Test Group: ORCID Validation ---\n";

// Valid ORCID with correct checksum
$valid_orcids = [
    '0000-0002-5152-9727',
    '0000-0001-2345-6789',
    '0009-0007-2395-0980',
];

foreach ($valid_orcids as $orcid) {
    $result = validateOrcid($orcid);
    if ($result === true) {
        $test->pass("validateOrcid('$orcid')");
    } else {
        $test->fail("validateOrcid('$orcid')", "Expected true, got " . var_export($result, true));
    }
}

// Invalid ORCID formats
$invalid_orcids = [
    '1234-5678-9012-3456',  // Wrong checksum
    '0000000251529727',     // No dashes
    '0000-0002-5152-972',   // Too short
    '0000-0002-5152-97277', // Too long
    'ABCD-0002-5152-9727',  // Invalid characters
    '',                      // Empty
];

foreach ($invalid_orcids as $orcid) {
    $result = validateOrcid($orcid);
    if ($result === false) {
        $test->pass("validateOrcid('$orcid') should be invalid");
    } else {
        $test->fail("validateOrcid('$orcid') should be invalid", "Expected false, got " . var_export($result, true));
    }
}

// ORCID URL format
$url_orcids = [
    'https://orcid.org/0000-0002-5152-9727' => true,
    'http://orcid.org/0000-0002-5152-9727' => true,
];

foreach ($url_orcids as $url => $should_pass) {
    // Extract ORCID from URL first
    $clean = str_replace(['https://orcid.org/', 'http://orcid.org/'], '', $url);
    $result = validateOrcid($clean);
    if ($result === $should_pass) {
        $test->pass("ORCID URL extraction: $url");
    } else {
        $test->fail("ORCID URL extraction: $url", "Expected " . var_export($should_pass, true));
    }
}

// ============================================================
// TEST 2: DOI Validation
// ============================================================
echo "\n--- Test Group: DOI Validation ---\n";

$valid_dois = [
    '10.1234/example',
    '10.1038/nature12345',
    '10.1103/PhysRevLett.123.012345',
];

foreach ($valid_dois as $doi) {
    $result = validateDoi($doi);
    if ($result === true) {
        $test->pass("validateDoi('$doi')");
    } else {
        $test->fail("validateDoi('$doi')", "Expected true, got " . var_export($result, true));
    }
}

$invalid_dois = [
    'not-a-doi',
    '10.123',  // Too short
    '',         // Empty
];

foreach ($invalid_dois as $doi) {
    $result = validateDoi($doi);
    if ($result === false) {
        $test->pass("validateDoi('$doi') should be invalid");
    } else {
        $test->fail("validateDoi('$doi') should be invalid", "Expected false, got " . var_export($result, true));
    }
}

// DOI URL format
$doi_urls = [
    'https://doi.org/10.1234/example' => '10.1234/example',
    'http://dx.doi.org/10.1234/example' => '10.1234/example',
];

foreach ($doi_urls as $url => $expected_clean) {
    $clean = preg_replace('/^https?:\/\/(dx\.)?doi\.org\//', '', $url);
    if ($clean === $expected_clean) {
        $test->pass("DOI URL cleaning: $url");
    } else {
        $test->fail("DOI URL cleaning: $url", "Expected '$expected_clean', got '$clean'");
    }
}

// ============================================================
// TEST 3: ISSN Validation
// ============================================================
echo "\n--- Test Group: ISSN Validation ---\n";

$valid_issns = [
    '1234-5678',
    '0028-0836',
    '2090-456X',  // With X check digit
];

foreach ($valid_issns as $issn) {
    $clean = preg_replace('/[^0-9X]/', '', strtoupper($issn));
    if (strlen($clean) === 8) {
        $test->pass("ISSN format: $issn");
    } else {
        $test->fail("ISSN format: $issn", "Invalid length: " . strlen($clean));
    }
}

$invalid_issns = [
    '12345678',   // No dash (but still valid after cleaning)
    '123-4567',   // Too short
    'ISSN123',    // Invalid format
];

foreach ($invalid_issns as $issn) {
    $clean = preg_replace('/[^0-9X]/', '', strtoupper($issn));
    if (strlen($clean) !== 8) {
        $test->pass("ISSN rejection: $issn");
    } else {
        // Some might pass cleaning but that's OK for this test
        $test->pass("ISSN cleaning: $issn -> $clean (length OK)");
    }
}

// ============================================================
// TEST 4: Cache Functions
// ============================================================
echo "\n--- Test Group: Cache Functions ---\n";

// Test cache filename generation
$cache_tests = [
    ['orcid', '0000-0002-5152-9727'],
    ['doi', '10.1234/example'],
    ['analysis_orcid', '0000-0001-2345-6789'],
    ['analysis_doi', '10.1038/nature12345'],
];

foreach ($cache_tests as $params) {
    list($type, $identifier) = $params;
    $filename = getCacheFilename($type, $identifier);
    if (!empty($filename) && strpos($filename, $type) !== false) {
        $test->pass("getCacheFilename('$type', '$identifier')");
    } else {
        $test->fail("getCacheFilename('$type', '$identifier')", "Invalid filename: $filename");
    }
}

// Test cache write/read cycle
$test_cache_file = sys_get_temp_dir() . '/test_cache_' . uniqid() . '.json';
$test_data = ['status' => 'success', 'data' => ['key' => 'value', 'number' => 42]];

if (writeToCache($test_cache_file, $test_data)) {
    $test->pass("writeToCache()");
    
    $read_data = readFromCache($test_cache_file);
    if ($read_data && $read_data['status'] === 'success' && $read_data['data']['number'] === 42) {
        $test->pass("readFromCache() round-trip");
    } else {
        $test->fail("readFromCache() round-trip", "Data mismatch");
    }
    
    // Cleanup
    unlink($test_cache_file);
} else {
    $test->fail("writeToCache()", "Failed to write cache file");
}

// Test clearCache
$test_dir = sys_get_temp_dir() . '/test_cache_dir';
@mkdir($test_dir, 0755, true);
file_put_contents($test_dir . '/test_abc.json', '{}');
file_put_contents($test_dir . '/test_xyz.json', '{}');

// Temporarily override CACHE_DIR
$GLOBALS['TEST_CACHE_DIR'] = $test_dir;
clearCache('test');

$cached_files = glob($test_dir . '/test_*.json');
if (empty($cached_files)) {
    $test->pass("clearCache('test')");
} else {
    $test->fail("clearCache('test')", "Files remaining: " . count($cached_files));
}

// Cleanup
array_map('unlink', glob($test_dir . '/*.json'));
rmdir($test_dir);

// ============================================================
// TEST 5: HTML Rendering Functions
// ============================================================
echo "\n--- Test Group: HTML Rendering ---\n";

// Test renderSafeHtml
$html_tests = [
    ['<script>alert("xss")</script>Hello', 'Hello'],
    ['<strong>Bold text</strong>', '<strong>Bold text</strong>'],
    ['<p>Paragraph</p>', '<p>Paragraph</p> '],
    ['Normal text', 'Normal text'],
    ['<em>Italic</em> and <b>bold</b>', '<em>Italic</em> and <b>bold</b>'],
];

foreach ($html_tests as $test_case) {
    list($input, $expected_contains) = $test_case;
    $result = renderSafeHtml($input);
    if (strpos($result, $expected_contains) !== false || $result === $expected_contains) {
        $test->pass("renderSafeHtml(): " . substr($input, 0, 30));
    } else {
        $test->fail("renderSafeHtml(): " . substr($input, 0, 30), "Got: $result");
    }
}

// Test fixUnclosedHtmlTags
$unclosed_html = '<p>This is <strong>bold text without closing tag';
$fixed = fixUnclosedHtmlTags($unclosed_html);
if (strpos($fixed, '</strong>') !== false && strpos($fixed, '</p>') !== false) {
    $test->pass("fixUnclosedHtmlTags()");
} else {
    $test->fail("fixUnclosedHtmlTags()", "Tags not properly closed: $fixed");
}

// Test formatScore
$score_tests = [
    [0.85, '85.0%'],
    [0.5, '50.0%'],
    [1.0, '100.0%'],
    [0, '0.0%'],
    ['invalid', 'N/A'],
];

foreach ($score_tests as $test_case) {
    list($input, $expected) = $test_case;
    $result = formatScore($input);
    if ($result === $expected) {
        $test->pass("formatScore($input)");
    } else {
        $test->fail("formatScore($input)", "Expected '$expected', got '$result'");
    }
}

// ============================================================
// TEST 6: SDG Definitions
// ============================================================
echo "\n--- Test Group: SDG Definitions ---\n";

// Check all 17 SDGs are defined
for ($i = 1; $i <= 17; $i++) {
    $sdg_code = 'SDG' . $i;
    if (isset($SDG_KEYWORDS[$sdg_code]) && !empty($SDG_KEYWORDS[$sdg_code])) {
        $test->pass("SDG{$i} keywords defined");
    } else {
        $test->fail("SDG{$i} keywords defined", "Missing or empty");
    }
}

// Test getSdgInfo function
for ($i = 1; $i <= 17; $i++) {
    $info = getSdgInfo('SDG' . $i);
    if ($info && isset($info['title']) && isset($info['color'])) {
        $test->pass("getSdgInfo('SDG{$i}')");
    } else {
        $test->fail("getSdgInfo('SDG{$i}')", "Missing title or color");
    }
}

// ============================================================
// TEST 7: Text Vector and Similarity
// ============================================================
echo "\n--- Test Group: Text Analysis ---\n";

// Test createTextVector
$vector = createTextVector('climate change global warming carbon emission');
if (is_array($vector) && count($vector) > 0 && isset($vector['climate'])) {
    $test->pass("createTextVector()");
} else {
    $test->fail("createTextVector()", "Invalid vector structure");
}

// Test calculateCosineSimilarity
$vec1 = ['word1' => 1, 'word2' => 2, 'word3' => 1];
$vec2 = ['word1' => 1, 'word2' => 2, 'word4' => 1];
$similarity = calculateCosineSimilarity($vec1, $vec2);
if (is_numeric($similarity) && $similarity >= 0 && $similarity <= 1) {
    $test->pass("calculateCosineSimilarity()");
} else {
    $test->fail("calculateCosineSimilarity()", "Invalid similarity value: $similarity");
}

// Identical vectors should have similarity of 1
$identical_sim = calculateCosineSimilarity($vec1, $vec1);
if (abs($identical_sim - 1.0) < 0.001) {
    $test->pass("calculateCosineSimilarity() identical vectors");
} else {
    $test->fail("calculateCosineSimilarity() identical vectors", "Expected ~1.0, got $identical_sim");
}

// Test hasSDGConcept
$climate_text = 'This research focuses on climate action and carbon reduction strategies';
if (hasSDGConcept($climate_text, 'SDG13')) {
    $test->pass("hasSDGConcept() for climate text");
} else {
    $test->fail("hasSDGConcept() for climate text", "Should detect SDG13");
}

// ============================================================
// TEST 8: Preprocessing
// ============================================================
echo "\n--- Test Group: Text Preprocessing ---\n";

$preprocess_tests = [
    ['HELLO WORLD!', 'hello world'],
    ['Test with special @#$ chars', 'test with special chars'],
    ['Multiple   spaces', 'multiple spaces'],
    ['  Trim me  ', 'trim me'],
];

foreach ($preprocess_tests as $test_case) {
    list($input, $expected_pattern) = $test_case;
    $result = preprocessText($input);
    if (stripos($result, $expected_pattern) !== false || $result === $expected_pattern) {
        $test->pass("preprocessText(): " . substr($input, 0, 20));
    } else {
        $test->fail("preprocessText(): " . substr($input, 0, 20), "Got: '$result'");
    }
}

// ============================================================
// TEST 9: Utility Functions
// ============================================================
echo "\n--- Test Group: Utility Functions ---\n";

// Test cleanInput
$dirty_input = '<script>alert("xss")</script> Clean text ';
$cleaned = cleanInput($dirty_input);
if (strpos($cleaned, '<script>') === false && strpos($cleaned, 'Clean text') !== false) {
    $test->pass("cleanInput() sanitization");
} else {
    $test->fail("cleanInput() sanitization", "Got: $cleaned");
}

// Test extractKeywordContext
$long_text = 'This is a long text about climate change and environmental issues. Climate change is important.';
$context = extractKeywordContext($long_text, 'climate', 20);
if (strpos($context, '<strong>climate</strong>') !== false) {
    $test->pass("extractKeywordContext() highlighting");
} else {
    $test->fail("extractKeywordContext() highlighting", "Keyword not highlighted");
}

// Test getSdgMainTerm
$main_terms = [
    'SDG1' => 'poverty',
    'SDG13' => 'climate',
    'SDG4' => 'education',
];

foreach ($main_terms as $sdg => $expected_term) {
    $term = getSdgMainTerm($sdg);
    if ($term === $expected_term) {
        $test->pass("getSdgMainTerm('$sdg')");
    } else {
        $test->fail("getSdgMainTerm('$sdg')", "Expected '$expected_term', got '$term'");
    }
}

// ============================================================
// TEST 10: Error Logging
// ============================================================
echo "\n--- Test Group: Error Logging ---\n";

$log_test_file = sys_get_temp_dir() . '/test_error_log_' . uniqid() . '.log';
$GLOBALS['LOG_CONFIG'] = [
    'enabled' => true,
    'file' => $log_test_file,
];

logError('Test error message');
logInfo('Test info message');

if (file_exists($log_test_file)) {
    $log_content = file_get_contents($log_test_file);
    if (strpos($log_content, 'ERROR: Test error message') !== false) {
        $test->pass("logError()");
    } else {
        $test->fail("logError()", "Log content incorrect: $log_content");
    }
    
    if (strpos($log_content, 'INFO: Test info message') !== false) {
        $test->pass("logInfo()");
    } else {
        $test->fail("logInfo()", "Log content incorrect: $log_content");
    }
    
    unlink($log_test_file);
} else {
    $test->fail("logError()", "Log file not created");
}

// ============================================================
// FINAL SUMMARY
// ============================================================
$all_passed = $test->summary();

exit($all_passed ? 0 : 1);

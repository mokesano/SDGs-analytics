<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/sdg_definitions.php';

echo "=== Wizdam AI-SCOLA Function Tests ===\n\n";

// Test 1: ORCID Validation
echo "1. ORCID Validation:\n";
$tests = [
    ['0000-0002-5152-9727', true],
    ['0000-0001-2345-6789', true],
    ['invalid', false],
    ['', false],
];
foreach ($tests as $t) {
    $result = validateOrcid($t[0]);
    $status = ($result === $t[1]) ? 'PASS' : 'FAIL';
    echo "   {$t[0]}: $status\n";
}

// Test 2: DOI Validation
echo "\n2. DOI Validation:\n";
$tests = [
    ['10.1234/example', true],
    ['10.1038/nature12345', true],
    ['invalid', false],
];
foreach ($tests as $t) {
    $result = validateDoi($t[0]);
    $status = ($result === $t[1]) ? 'PASS' : 'FAIL';
    echo "   {$t[0]}: $status\n";
}

// Test 3: Cache Functions
echo "\n3. Cache Functions:\n";
$file = getCacheFilename('orcid', '0000-0002-5152-9727');
echo "   getCacheFilename: " . (strpos($file, 'orcid') !== false ? 'PASS' : 'FAIL') . "\n";

// Test 4: Format Score
echo "\n4. Format Score:\n";
echo "   0.85 -> " . formatScore(0.85) . ": " . (formatScore(0.85) === '85.0%' ? 'PASS' : 'FAIL') . "\n";
echo "   invalid -> " . formatScore('invalid') . ": " . (formatScore('invalid') === 'N/A' ? 'PASS' : 'FAIL') . "\n";

// Test 5: SDG Definitions
echo "\n5. SDG Definitions:\n";
echo "   Count: " . count($SDG_KEYWORDS) . ": " . (count($SDG_KEYWORDS) === 17 ? 'PASS' : 'FAIL') . "\n";
$info = getSdgInfo('SDG1');
echo "   SDG1 title: " . (!empty($info['title']) ? 'PASS' : 'FAIL') . "\n";

// Test 6: Text Analysis
echo "\n6. Text Analysis:\n";
$vector = createTextVector('climate change warming');
echo "   createTextVector: " . (is_array($vector) && isset($vector['climate']) ? 'PASS' : 'FAIL') . "\n";

$vec1 = ['a'=>1, 'b'=>2];
$vec2 = ['a'=>1, 'b'=>2];
$sim = calculateCosineSimilarity($vec1, $vec2);
echo "   cosineSimilarity: " . (abs($sim - 1.0) < 0.001 ? 'PASS' : 'FAIL') . " (got $sim)\n";

// Test 7: HTML Rendering
echo "\n7. HTML Rendering:\n";
$clean = renderSafeHtml('<script>alert(1)</script>Hello');
echo "   XSS protection: " . (strpos($clean, '<script>') === false ? 'PASS' : 'FAIL') . "\n";

echo "\n=== All Tests Complete ===\n";

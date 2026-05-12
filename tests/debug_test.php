<?php
// Simple test runner to debug output
define('TEST_MODE', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

if (!function_exists('createTextVector')) {
    require_once __DIR__ . '/../api/SDG_Classification_API.php';
}

echo "Functions loaded successfully\n";
echo "validateORCID exists: " . (function_exists('validateORCID') ? 'YES' : 'NO') . "\n";
echo "createTextVector exists: " . (function_exists('createTextVector') ? 'YES' : 'NO') . "\n";

$orcid = '0000-0002-5157-9767';
echo "\nTesting ORCID: {$orcid}\n";
echo "Validation result: " . (validateORCID($orcid) ? 'VALID' : 'INVALID') . "\n";

echo "\nFetching from ORCID API...\n";
$url = "https://pub.orcid.org/v3.0/{$orcid}/works?limit=2";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
    CURLOPT_TIMEOUT => 30
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
if ($httpCode == 200 && $response) {
    $data = json_decode($response, true);
    $count = isset($data['group']) ? count($data['group']) : 0;
    echo "Works found: {$count}\n";
    
    if ($count > 0) {
        $title = $data['group'][0]['work-summary'][0]['title']['title']['value'] ?? 'No title';
        echo "First work title: " . substr($title, 0, 60) . "...\n";
        
        echo "\nTesting vectorization...\n";
        $vector = createTextVector($title);
        echo "Vector dimensions: " . count($vector) . "\n";
        echo "SUCCESS!\n";
    }
} else {
    echo "Failed to fetch data\n";
}

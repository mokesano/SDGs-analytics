<?php
/**
 * Debug Script: Chatbot CSS Loading Issue
 * Script untuk mengidentifikasi mengapa chatbot.css gagal load
 */

echo "=== CHATBOT CSS DEBUG ANALYSIS ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// Test 1: Check if chatbot.css file exists
echo "TEST 1: File Existence Check\n";
echo "Checking: assets/css/chatbot.css\n";

if (file_exists('assets/css/chatbot.css')) {
    echo "✅ FOUND: chatbot.css exists\n";
    echo "File size: " . filesize('assets/css/chatbot.css') . " bytes\n";
    echo "Last modified: " . date('Y-m-d H:i:s', filemtime('assets/css/chatbot.css')) . "\n";
    echo "Permissions: " . substr(sprintf('%o', fileperms('assets/css/chatbot.css')), -4) . "\n";
    echo "Is readable: " . (is_readable('assets/css/chatbot.css') ? 'YES' : 'NO') . "\n";
} else {
    echo "❌ NOT FOUND: chatbot.css does not exist\n";
}

echo "\n" . str_repeat("-", 50) . "\n\n";

// Test 2: Check file content
echo "TEST 2: File Content Analysis\n";

if (file_exists('assets/css/chatbot.css')) {
    $content = file_get_contents('assets/css/chatbot.css');
    
    if ($content !== false) {
        echo "✅ Content readable\n";
        echo "Content length: " . strlen($content) . " characters\n";
        
        // Check for BOM
        $bom = substr($content, 0, 3);
        if ($bom === "\xEF\xBB\xBF") {
            echo "⚠️  WARNING: BOM detected in file!\n";
        } else {
            echo "✅ No BOM detected\n";
        }
        
        // Check first few lines
        $lines = explode("\n", $content);
        echo "First 5 lines of file:\n";
        for ($i = 0; $i < min(5, count($lines)); $i++) {
            echo "Line " . ($i + 1) . ": " . trim($lines[$i]) . "\n";
        }
        
        // Check for common CSS patterns
        if (strpos($content, '#chatbotBtn') !== false) {
            echo "✅ Contains chatbot button styles\n";
        } else {
            echo "❌ Missing chatbot button styles\n";
        }
        
        if (strpos($content, '.chatbot-modal') !== false || strpos($content, '#chatbotModal') !== false) {
            echo "✅ Contains chatbot modal styles\n";
        } else {
            echo "❌ Missing chatbot modal styles\n";
        }
        
    } else {
        echo "❌ Could not read file content\n";
    }
} else {
    echo "❌ File does not exist, skipping content analysis\n";
}

echo "\n" . str_repeat("-", 50) . "\n\n";

// Test 3: Compare with working style.css
echo "TEST 3: Comparison with style.css\n";

if (file_exists('assets/css/style.css')) {
    echo "✅ style.css exists\n";
    echo "style.css size: " . filesize('assets/css/style.css') . " bytes\n";
    echo "style.css permissions: " . substr(sprintf('%o', fileperms('assets/css/style.css')), -4) . "\n";
} else {
    echo "❌ style.css does not exist\n";
}

echo "\n" . str_repeat("-", 50) . "\n\n";

// Test 4: Check directory structure
echo "TEST 4: Directory Structure Check\n";
echo "Checking: assets/css/ directory\n";

if (is_dir('assets/css/')) {
    echo "✅ assets/css/ directory exists\n";
    echo "Directory permissions: " . substr(sprintf('%o', fileperms('assets/css/')), -4) . "\n";
    
    echo "Files in assets/css/:\n";
    $files = scandir('assets/css/');
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $filepath = 'assets/css/' . $file;
            $size = is_file($filepath) ? filesize($filepath) : 'DIR';
            $readable = is_readable($filepath) ? 'R' : '-';
            echo "  - $file ($size bytes, $readable)\n";
        }
    }
} else {
    echo "❌ assets/css/ directory does not exist\n";
}

echo "\n" . str_repeat("-", 50) . "\n\n";

// Test 5: Check if it's a typo issue like before
echo "TEST 5: Potential Typo Check\n";

$potential_names = [
    'chatbot.css',
    'chatbots.css',
    'chat-bot.css',
    'chat_bot.css',
    'Chatbot.css',
    'CHATBOT.css'
];

echo "Checking for potential filename variations:\n";
foreach ($potential_names as $name) {
    $path = 'assets/css/' . $name;
    if (file_exists($path)) {
        echo "✅ FOUND: $name\n";
    } else {
        echo "❌ NOT FOUND: $name\n";
    }
}

echo "\n" . str_repeat("-", 50) . "\n\n";

// Test 6: HTTP Response Check (simulate browser request)
echo "TEST 6: HTTP Response Simulation\n";

$css_url = 'assets/css/chatbot.css';
$full_path = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($css_url, '/');

echo "Simulating browser request to: $css_url\n";
echo "Full server path: $full_path\n";

if (file_exists($full_path)) {
    echo "✅ File accessible via HTTP path\n";
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $full_path);
    finfo_close($finfo);
    
    echo "MIME type: $mime_type\n";
    
    if ($mime_type === 'text/css' || $mime_type === 'text/plain') {
        echo "✅ Correct MIME type for CSS\n";
    } else {
        echo "⚠️  Unexpected MIME type: $mime_type\n";
    }
    
} else {
    echo "❌ File not accessible via HTTP path\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "DIAGNOSTIC SUMMARY:\n";
echo "1. If file exists but has 0 rules loaded: Check file content and encoding\n";
echo "2. If file doesn't exist: Check filename spelling and directory structure\n";
echo "3. If file exists but wrong MIME type: Check server configuration\n";
echo "4. If permissions issue: Fix file/directory permissions\n";
echo str_repeat("=", 60) . "\n";
?>
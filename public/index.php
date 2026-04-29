<?php
/**
 * SDG Frontend - Main Application Router
 * Router utama yang menggabungkan semua komponen dengan benar
 * 
 * @version 5.1.8
 * @author Rochmady and Wizdam Team
 * @license MIT
 */

// Mulai session dan error handling
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include file konfigurasi dan fungsi utama
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/sdg_definitions.php';

// Ambil parameter halaman dari URL
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Daftar halaman yang diizinkan sesuai dengan struktur README.md
$allowed_pages = [
    'home', 'about', 'apps', 'teams', 'archived', 'help', 'contact',
    'documentation', 'analitics-dashboard', 'api-access', 'bulk-analysis',
    'integration-tools', 'tutorials', 'research-papers', 'api-reference',
    'community-forum', 'blog', 'careers', 'partners', 'press-kit',
    'privacy-policy'
];

// Validasi halaman
if (!in_array($page, $allowed_pages)) {
    $page = 'home';
}

// Set variabel khusus per halaman
$page_title = '';
$page_description = '';
$additional_css = [];
$additional_scripts = [];
$body_class = 'page-' . $page;

// Set page-specific metadata
switch($page) {
    case 'home':
        $page_title = 'SDGs Classification Analysis - AI-Powered Research Analysis';
        $page_description = 'Advanced AI platform for analyzing research contributions to UN Sustainable Development Goals. Analyze ORCID profiles and DOI articles.';
        break;
    case 'about':
        $page_title = 'About - SDGs Classification Analysis';
        $page_description = 'Learn about our AI-powered platform for SDG research analysis and our mission to advance sustainable development.';
        break;
    case 'documentation':
        $page_title = 'Documentation - SDGs Classification Analysis';
        $page_description = 'Complete documentation for using our SDG analysis platform, API reference, and integration guides.';
        break;
    case 'contact':
        $page_title = 'Contact Us - SDGs Classification Analysis';
        $page_description = 'Get in touch with our team for support, partnerships, or questions about our SDG analysis platform.';
        break;
    default:
        $page_title = ucfirst(str_replace('-', ' ', $page)) . ' - SDGs Classification Analysis';
        $page_description = 'SDGs Classification Analysis - AI-powered platform for research analysis.';
}

// Include komponen header
include 'components/header.php';

// Include navigation
include 'components/navigation.php';

// Include konten halaman yang diminta
$page_file = "pages/{$page}.php";
if (file_exists($page_file)) {
    include $page_file;
} else {
    // Fallback ke halaman home jika file tidak ditemukan
    include 'pages/home.php';
}

// Include footer dan chatbot
include 'components/footer.php';

// Include script utama di akhir untuk performance
?>

<!-- Back to Top Button -->
<div id="back-to-top" class="back-to-top" onclick="scrollToTop()">
    <i class="fas fa-arrow-up"></i>
</div>

<!-- Loading overlay global -->
<div id="global-loading" class="loading-overlay">
    <div class="spinner"></div>
    <div class="loading-text">Processing...</div>
    <div class="loading-subtext">Please wait while we analyze your request</div>
</div>

<!-- Scripts utama -->
<script src="assets/js/script.js"></script>
<script src="assets/js/chart.js"></script>

<!-- Service Worker untuk PWA -->
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/sw.js')
            .then(function(registration) {
                console.log('ServiceWorker registration successful');
            })
            .catch(function(err) {
                console.log('ServiceWorker registration failed: ', err);
            });
    });
}
</script>

</body>
</html>
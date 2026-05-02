<?php
/**
 * SDG Frontend — Front Controller & AJAX Proxy
 *
 * Dua peran dalam satu file:
 * 1. AJAX Proxy  — Jika POST dengan _sdg, teruskan ke API via direct include
 * 2. Page Router — Jika GET, sajikan halaman HTML sesuai ?page=
 *
 * @version 1.0.0 (PHP 7.4+ Compatible)
 * @author Rochmady and Wizdam Team
 * @license MIT
 */

// Root proyek = direktori parent dari public/
define('PROJECT_ROOT', dirname(__DIR__));

// ================================================================
// BAGIAN #0 — AJAX PROXY (harus paling atas, sebelum output apa pun)
// POST dengan _sdg → layani sebagai JSON, exit.
// ================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['_sdg'])) {

    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');

    $api_file = PROJECT_ROOT . '/api/SDG_Classification_API.php';
    if (!file_exists($api_file)) {
        http_response_code(503);
        echo json_encode(['status' => 'error', 'message' => 'File API tidak ditemukan.']);
        exit;
    }

    $action  = trim($_POST['_sdg']);
    $params  = [];
    $refresh = (!empty($_POST['refresh']) && $_POST['refresh'] === 'true');
    if ($refresh) $params['refresh'] = 'true';

    switch ($action) {
        case 'init':
            if (empty($_POST['orcid'])) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'orcid required']); exit; }
            $params['orcid']  = trim($_POST['orcid']);
            $params['action'] = 'init';
            break;
        case 'batch':
            if (empty($_POST['orcid'])) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'orcid required']); exit; }
            $params['orcid']  = trim($_POST['orcid']);
            $params['action'] = 'batch';
            $params['offset'] = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
            $params['limit']  = isset($_POST['limit'])  ? (int)$_POST['limit']  : 3;
            break;
        case 'summary':
            if (empty($_POST['orcid'])) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'orcid required']); exit; }
            $params['orcid']  = trim($_POST['orcid']);
            $params['action'] = 'summary';
            break;
        case 'doi':
            if (empty($_POST['doi'])) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'doi required']); exit; }
            $rawDoi = trim($_POST['doi']);
            $rawDoi = preg_replace('/^https?:\/\/(dx\.)?doi\.org\//i', '', $rawDoi);
            $params['doi'] = $rawDoi;
            break;
        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenal: ' . htmlspecialchars($action)]);
            exit;
    }

    // Spoof $_GET dan REQUEST_METHOD agar API berjalan normal
    $orig_get    = $_GET;
    $orig_method = $_SERVER['REQUEST_METHOD'];
    $_GET        = $params;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $prev_err = ini_get('display_errors');
    ini_set('display_errors', '0');

    ob_start();
    try {
        require $api_file;
    } catch (Throwable $t) {
        ob_end_clean();
        $_GET = $orig_get;
        $_SERVER['REQUEST_METHOD'] = $orig_method;
        ini_set('display_errors', $prev_err);
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Fatal: ' . $t->getMessage()]);
        exit;
    }
    $raw = ob_get_clean();
    ini_set('display_errors', $prev_err);
    $_GET = $orig_get;
    $_SERVER['REQUEST_METHOD'] = $orig_method;

    // Strip PHP warnings/notices, temukan awal JSON
    $jpos = false;
    for ($i = 0, $l = strlen($raw); $i < $l; $i++) {
        if ($raw[$i] === '{' || $raw[$i] === '[') { $jpos = $i; break; }
    }
    $json_str = ($jpos !== false) ? substr($raw, $jpos) : '';
    if (empty($json_str)) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'API tidak menghasilkan output.']);
        exit;
    }
    json_decode($json_str);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Output API bukan JSON: ' . json_last_error_msg()]);
        exit;
    }
    echo $json_str;
    exit;
}

// ================================================================
// BAGIAN #1 — PAGE ROUTER (GET requests)
// ================================================================
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', PROJECT_ROOT . '/logs/error.log');

// Sertakan file core dengan path absolut
require_once PROJECT_ROOT . '/includes/config.php';
require_once PROJECT_ROOT . '/includes/functions.php';
require_once PROJECT_ROOT . '/includes/sdg_definitions.php';

// Routing sederhana via ?page=
$page = isset($_GET['page']) ? trim($_GET['page']) : 'home';

$allowed_pages = [
    'home', 'about', 'apps', 'teams', 'archived', 'help', 'contact',
    'documentation', 'analitics-dashboard', 'api-access', 'bulk-analysis',
    'integration-tools', 'tutorials', 'research-papers', 'api-reference',
    'community-forum', 'blog', 'careers', 'partners', 'press-kit',
    'privacy-policy',
];
if (!in_array($page, $allowed_pages)) $page = 'home';

// Metadata halaman
$page_title       = '';
$page_description = '';
$additional_css   = [];
$body_class       = 'page-' . $page;

switch ($page) {
    case 'home':
        $page_title       = 'SDGs Classification Analysis - AI-Powered Research Analysis';
        $page_description = 'Advanced AI platform for analyzing research contributions to UN Sustainable Development Goals.';
        break;
    case 'about':
        $page_title       = 'About - SDGs Classification Analysis';
        $page_description = 'Learn about our AI-powered platform for SDG research analysis.';
        break;
    case 'documentation':
        $page_title       = 'Documentation - SDGs Classification Analysis';
        $page_description = 'Complete documentation for using our SDG analysis platform.';
        break;
    case 'contact':
        $page_title       = 'Contact Us - SDGs Classification Analysis';
        $page_description = 'Get in touch with our team.';
        break;
    default:
        $page_title       = ucfirst(str_replace('-', ' ', $page)) . ' - SDGs Classification Analysis';
        $page_description = 'SDGs Classification Analysis - AI-powered platform for research analysis.';
}

include PROJECT_ROOT . '/components/header.php';
include PROJECT_ROOT . '/components/navigation.php';

$page_file = PROJECT_ROOT . "/pages/{$page}.php";
if (file_exists($page_file)) {
    include $page_file;
} else {
    include PROJECT_ROOT . '/pages/home.php';
}

include PROJECT_ROOT . '/components/footer.php';
include PROJECT_ROOT . '/components/chatbot.php';
?>

<!-- Back to Top Button -->
<button id="back-to-top" class="back-to-top" onclick="window.scrollTo({top:0,behavior:'smooth'})">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- Scripts utama -->
<script src="../assets/js/script.js"></script>
<script src="../assets/js/charts.js"></script>
</body>
</html>

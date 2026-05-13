<?php
/**
 * routers.php — Application Router & AJAX Proxy Handler
 * 
 * Menangani semua logika routing dan AJAX proxy agar index.php tetap sederhana.
 * File ini mendelegasikan logika kompleks dari index.php.
 * 
 * @version 1.0.0
 * @author Rochmady and Wizdam Team
 * @license MIT
 */

// ================================================
// AJAX PROXY HANDLER
// ================================================

/**
 * Handle AJAX POST requests with _sdg parameter
 * Returns true if request was handled (and script exited), false otherwise
 */
function handleAjaxProxy() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['_sdg'])) {
        return false;
    }

    // Clear output buffers
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
            if (empty($_POST['orcid'])) { 
                http_response_code(400); 
                echo json_encode(['status'=>'error','message'=>'orcid required']); 
                exit; 
            }
            $params['orcid']  = trim($_POST['orcid']);
            $params['action'] = 'init';
            break;
            
        case 'batch':
            if (empty($_POST['orcid'])) { 
                http_response_code(400); 
                echo json_encode(['status'=>'error','message'=>'orcid required']); 
                exit; 
            }
            $params['orcid']  = trim($_POST['orcid']);
            $params['action'] = 'batch';
            $params['offset'] = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
            $params['limit']  = isset($_POST['limit'])  ? (int)$_POST['limit']  : 3;
            break;
            
        case 'summary':
            if (empty($_POST['orcid'])) { 
                http_response_code(400); 
                echo json_encode(['status'=>'error','message'=>'orcid required']); 
                exit; 
            }
            $params['orcid']  = trim($_POST['orcid']);
            $params['action'] = 'summary';
            break;
            
        case 'doi':
            if (empty($_POST['doi'])) { 
                http_response_code(400); 
                echo json_encode(['status'=>'error','message'=>'doi required']); 
                exit; 
            }
            $rawDoi = trim($_POST['doi']);
            $rawDoi = preg_replace('/^https?:\/\/(dx\.)?doi\.org\//i', '', $rawDoi);
            $params['doi'] = $rawDoi;
            break;
            
        case 'journal':
            if (empty($_POST['issn'])) { 
                http_response_code(400); 
                echo json_encode(['status'=>'error','message'=>'issn required']); 
                exit; 
            }
            $rawIssn = preg_replace('/[^0-9X]/', '', strtoupper(trim($_POST['issn'])));
            if (strlen($rawIssn) !== 8) { 
                http_response_code(400); 
                echo json_encode(['status'=>'error','message'=>'Format ISSN tidak valid']); 
                exit; 
            }
            $params['issn']   = $rawIssn;
            $params['action'] = 'journal';
            $api_file = PROJECT_ROOT . '/api/scopus.php';
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenal: ' . htmlspecialchars($action)]);
            exit;
    }

    // Spoof $_GET and REQUEST_METHOD for API compatibility
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

    // Strip PHP warnings/notices, find JSON start
    $jpos = false;
    for ($i = 0, $l = strlen($raw); $i < $l; $i++) {
        if ($raw[$i] === '{' || $raw[$i] === '[') { 
            $jpos = $i; 
            break; 
        }
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

/**
 * Handle Public API GET requests
 * Returns true if request was handled (and script exited), false otherwise
 */
function handlePublicApi() {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['api'])) {
        return false;
    }
    
    $api_action = trim($_GET['api']);
    
    if ($api_action === 'journal') {
        while (ob_get_level()) ob_end_clean();
        $api_file = PROJECT_ROOT . '/api/journal.php';
        if (file_exists($api_file)) {
            require $api_file;
        } else {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(503);
            echo json_encode(['status' => 'error', 'message' => 'API endpoint tidak ditemukan.']);
        }
        exit;
        
    } elseif ($api_action === 'researcher') {
        while (ob_get_level()) ob_end_clean();
        $api_file = PROJECT_ROOT . '/api/researcher.php';
        if (file_exists($api_file)) {
            require $api_file;
        } else {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(503);
            echo json_encode(['status' => 'error', 'message' => 'API endpoint tidak ditemukan.']);
        }
        exit;
    }
    
    // Unknown api= values fall through to normal page routing
    return false;
}

// ================================================
// PAGE ROUTER
// ================================================

/**
 * Get the current page from query parameters
 * @return string
 */
function getCurrentPage() {
    $page = isset($_GET['page']) ? trim($_GET['page']) : 'home';
    
    $allowed_pages = [
        'home', 'about', 'apps', 'teams', 'archived', 'help', 'contact',
        'documentation', 'analitics-dashboard', 'api-access', 'bulk-analysis',
        'integration-tools', 'tutorials', 'research-papers', 'api-reference',
        'community-forum', 'blog', 'careers', 'partners', 'press-kit',
        'privacy-policy',
        'login', 'register', 'forgot-password', 'leaderboard', 'orcid-profile',
        'journal-profile', 'journal-archive', 'sdg-researcher-list',
    ];
    
    if (!in_array($page, $allowed_pages)) {
        $page = 'home';
    }
    
    return $page;
}

/**
 * Get page metadata (title, description, CSS)
 * @param string $page
 * @return array
 */
function getPageMetadata($page) {
    $metadata = [
        'title' => '',
        'description' => '',
        'css' => [],
        'body_class' => 'page-' . $page
    ];
    
    switch ($page) {
        case 'home':
            $metadata['title'] = 'SDGs Classification Analysis - AI-Powered Research Analysis';
            $metadata['description'] = 'Advanced AI platform for analyzing research contributions to UN Sustainable Development Goals.';
            break;
            
        case 'about':
            $metadata['title'] = 'About - SDGs Classification Analysis';
            $metadata['description'] = 'Learn about our AI-powered platform for SDG research analysis.';
            break;
            
        case 'documentation':
            $metadata['title'] = 'Documentation - SDGs Classification Analysis';
            $metadata['description'] = 'Complete documentation for using our SDG analysis platform.';
            break;
            
        case 'contact':
            $metadata['title'] = 'Contact Us - SDGs Classification Analysis';
            $metadata['description'] = 'Get in touch with our team.';
            break;
            
        case 'login':
            $metadata['title'] = 'Login - SDGs Classification Analysis';
            break;
            
        case 'register':
            $metadata['title'] = 'Register - SDGs Classification Analysis';
            break;
            
        case 'forgot-password':
            $metadata['title'] = 'Reset Password - SDGs Classification Analysis';
            break;
            
        case 'leaderboard':
            $metadata['title'] = 'Leaderboard - SDGs Classification Analysis';
            $metadata['description'] = 'Top researchers contributing to Sustainable Development Goals.';
            break;
            
        case 'orcid-profile':
            $metadata['title'] = 'Researcher Profile - SDGs Classification Analysis';
            break;
            
        case 'journal-profile':
            $issn_param = isset($_GET['issn']) ? htmlspecialchars(trim($_GET['issn'])) : '';
            $metadata['title'] = ($issn_param ? $issn_param . ' — ' : '') . 'Journal Profile | SDGs Classification Analysis';
            $metadata['description'] = 'Scopus journal metrics, quartile, SJR, SDG mapping, and research overview.';
            break;
            
        case 'journal-archive':
            $metadata['title'] = 'Journal Archive — SDGs Classification Analysis';
            $metadata['description'] = 'Browse all Scopus journals checked on Wizdam AI.';
            break;
            
        case 'sdg-researcher-list':
            $metadata['title'] = 'Peneliti per SDG — SDGs Classification Analysis';
            $metadata['description'] = 'Browse researchers by SDG category and contribution type.';
            break;
            
        default:
            $metadata['title'] = ucfirst(str_replace('-', ' ', $page)) . ' - SDGs Classification Analysis';
            $metadata['description'] = 'SDGs Classification Analysis - AI-powered platform for research analysis.';
    }
    
    return $metadata;
}

/**
 * Get the page file path for inclusion
 * @param string $page
 * @return string
 */
function getPageFilePath($page) {
    $auth_pages = [
        'login' => 'auth/login', 
        'register' => 'auth/register', 
        'forgot-password' => 'auth/forgot'
    ];
    
    $page_slug = isset($auth_pages[$page]) ? $auth_pages[$page] : $page;
    $page_file = PROJECT_ROOT . "/pages/{$page_slug}.php";
    
    if (file_exists($page_file)) {
        return $page_file;
    }
    
    // Fallback to home.php
    return PROJECT_ROOT . '/pages/home.php';
}

/**
 * Initialize application and handle routing
 * This is the main entry point called from index.php
 */
function runApplication() {
    // Start session
    session_start();
    
    // Error handling configuration
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', PROJECT_ROOT . '/logs/error.log');
    
    // Load core files
    require_once PROJECT_ROOT . '/includes/config.php';
    require_once PROJECT_ROOT . '/includes/functions.php';
    require_once PROJECT_ROOT . '/includes/sdg_definitions.php';
    require_once PROJECT_ROOT . '/includes/bootstrap.php';
    
    // Handle AJAX proxy first (highest priority)
    if (handleAjaxProxy()) {
        return; // Script already exited
    }
    
    // Handle public API requests
    if (handlePublicApi()) {
        return; // Script already exited
    }
    
    // Page routing
    $page = getCurrentPage();
    $metadata = getPageMetadata($page);
    
    // Make metadata available globally for components (using old variable names for backward compatibility)
    $page_title       = $metadata['title'];
    $page_description = $metadata['description'];
    $body_class       = $metadata['body_class'];
    
    // Also set as globals for explicit access
    $GLOBALS['PAGE_TITLE'] = $metadata['title'];
    $GLOBALS['PAGE_DESCRIPTION'] = $metadata['description'];
    $GLOBALS['BODY_CLASS'] = $metadata['body_class'];
    $GLOBALS['CURRENT_PAGE'] = $page;
    
    // Include header and navigation
    include PROJECT_ROOT . '/components/header.php';
    include PROJECT_ROOT . '/components/navigation.php';
    
    // Include page content
    $page_file = getPageFilePath($page);
    include $page_file;
    
    // Include footer and chatbot (includes </body></html>)
    include PROJECT_ROOT . '/components/footer.php';
    include PROJECT_ROOT . '/components/chatbot.php';
}

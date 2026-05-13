<?php

namespace Wizdam\Core;

/**
 * Application Class - Main Application Container
 * 
 * Mengelola siklus hidup aplikasi, dependency injection, dan eksekusi.
 * Mengimplementasikan pola Singleton untuk instance global.
 * 
 * @package Wizdam\Core
 * @author Rochmady and Wizdam Team
 * @version 2.0.0
 */
class Application
{
    /**
     * Single instance of the application
     */
    private static ?Application $instance = null;

    /**
     * Application configuration
     */
    private array $config = [];

    /**
     * Registered services
     */
    private array $services = [];

    /**
     * Application boot status
     */
    private bool $booted = false;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
        $this->initialize();
    }

    /**
     * Prevent cloning of the singleton instance
     */
    private function __clone() {}

    /**
     * Prevent unserialization of the singleton instance
     */
    public function __wakeup()
    {
        throw new \RuntimeException('Cannot unserialize singleton');
    }

    /**
     * Get or create the singleton instance
     */
    public static function get(): Application
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the application
     */
    private function initialize(): void
    {
        // Define project root if not already defined
        if (!defined('PROJECT_ROOT')) {
            define('PROJECT_ROOT', dirname(__DIR__, 2));
        }

        // Load base configuration
        $this->loadConfiguration();

        // Setup error handling
        $this->setupErrorHandling();

        // Register core services
        $this->registerCoreServices();
    }

    /**
     * Load application configuration
     */
    private function loadConfiguration(): void
    {
        $configFile = PROJECT_ROOT . '/includes/config.php';
        
        if (file_exists($configFile)) {
            require_once $configFile;
            
            // Merge global CONFIG array into our config
            if (isset($GLOBALS['CONFIG']) && is_array($GLOBALS['CONFIG'])) {
                $this->config = $GLOBALS['CONFIG'];
            }
            
            // Add other config arrays
            $this->config['db'] = $GLOBALS['DB_CONFIG'] ?? [];
            $this->config['log'] = $GLOBALS['LOG_CONFIG'] ?? [];
            $this->config['email'] = $GLOBALS['EMAIL_CONFIG'] ?? [];
            $this->config['analytics'] = $GLOBALS['ANALYTICS_CONFIG'] ?? [];
        }

        // Add constants to config
        $this->config['constants'] = [
            'SITE_NAME' => defined('SITE_NAME') ? SITE_NAME : 'SDG Analysis',
            'SITE_URL' => defined('SITE_URL') ? SITE_URL : '',
            'VERSION' => defined('VERSION') ? VERSION : '1.0.0',
            'ENVIRONMENT' => defined('ENVIRONMENT') ? ENVIRONMENT : 'production',
            'DEBUG_MODE' => defined('DEBUG_MODE') ? DEBUG_MODE : false,
        ];
    }

    /**
     * Setup error handling and logging
     */
    private function setupErrorHandling(): void
    {
        $environment = $this->getConfig('constants.ENVIRONMENT', 'production');
        
        if ($environment === 'production') {
            error_reporting(0);
            ini_set('display_errors', 0);
            ini_set('log_errors', 1);
            ini_set('error_log', PROJECT_ROOT . '/logs/php_errors.log');
        } else {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        }

        // Set custom exception handler
        set_exception_handler(function($exception) {
            $this->handleException($exception);
        });

        // Set custom error handler
        set_error_handler(function($severity, $message, $file, $line) {
            if (error_reporting() & $severity) {
                throw new \ErrorException($message, 0, $severity, $file, $line);
            }
        });
    }

    /**
     * Register core services
     */
    private function registerCoreServices(): void
    {
        // Database service will be registered on demand
        $this->services['db.initialized'] = false;
        
        // Router service
        $this->services['router'] = null;
        
        // Cache service
        $this->services['cache'] = null;
    }

    /**
     * Boot the application - load all necessary components
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Load required files
        $this->loadRequiredFiles();

        // Initialize database connection
        $this->initializeDatabase();

        // Load SDG definitions
        $this->loadSdgDefinitions();

        $this->booted = true;
    }

    /**
     * Load required files for application
     */
    private function loadRequiredFiles(): void
    {
        $files = [
            PROJECT_ROOT . '/includes/functions.php',
            PROJECT_ROOT . '/includes/bootstrap.php',
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                require_once $file;
            }
        }
    }

    /**
     * Initialize database connection
     */
    private function initializeDatabase(): void
    {
        // Database is initialized by bootstrap.php
        // This method ensures it's available
        if (!$this->services['db.initialized']) {
            try {
                $db = getDb();
                $this->services['db.initialized'] = true;
                $this->services['db'] = $db;
            } catch (\RuntimeException $e) {
                // Database initialization failed
                $this->services['db.initialized'] = false;
            }
        }
    }

    /**
     * Load SDG definitions into global scope
     */
    private function loadSdgDefinitions(): void
    {
        $sdgFile = PROJECT_ROOT . '/includes/sdg_definitions.php';
        
        if (file_exists($sdgFile)) {
            require_once $sdgFile;
            
            // Make sure SDG_DEFINITIONS is available globally
            if (isset($GLOBALS['SDG_DEFINITIONS'])) {
                $this->services['sdg_definitions'] = $GLOBALS['SDG_DEFINITIONS'];
            }
        }
    }

    /**
     * Execute the application - handle routing and rendering
     */
    public function execute(): void
    {
        // Boot the application first
        $this->boot();

        // Handle AJAX proxy requests
        if ($this->handleAjaxProxy()) {
            return;
        }

        // Handle public API requests
        if ($this->handlePublicApi()) {
            return;
        }

        // Route the request and render page
        $this->routeRequest();
    }

    /**
     * Handle AJAX proxy requests
     */
    private function handleAjaxProxy(): bool
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['_sdg'])) {
            return false;
        }

        // Clear output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
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
        if ($refresh) {
            $params['refresh'] = 'true';
        }

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
        } catch (\Throwable $t) {
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
     * Handle public API GET requests
     */
    private function handlePublicApi(): bool
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['api'])) {
            return false;
        }
        
        $api_action = trim($_GET['api']);
        
        if ($api_action === 'journal') {
            while (ob_get_level()) {
                ob_end_clean();
            }
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
            while (ob_get_level()) {
                ob_end_clean();
            }
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
        
        return false;
    }

    /**
     * Route the request and render the appropriate page
     */
    private function routeRequest(): void
    {
        // Get current page
        $page = $this->getCurrentPage();
        
        // Get page metadata
        $metadata = $this->getPageMetadata($page);
        
        // Set global variables for backward compatibility
        $GLOBALS['PAGE_TITLE'] = $metadata['title'];
        $GLOBALS['PAGE_DESCRIPTION'] = $metadata['description'];
        $GLOBALS['BODY_CLASS'] = $metadata['body_class'];
        $GLOBALS['CURRENT_PAGE'] = $page;
        
        // Include header and navigation
        include PROJECT_ROOT . '/components/header.php';
        include PROJECT_ROOT . '/components/navigation.php';
        
        // Include page content
        $page_file = $this->getPageFilePath($page);
        include $page_file;
        
        // Include footer and chatbot
        include PROJECT_ROOT . '/components/footer.php';
        include PROJECT_ROOT . '/components/chatbot.php';
    }

    /**
     * Get the current page from query parameters
     */
    private function getCurrentPage(): string
    {
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
     * Get page metadata
     */
    private function getPageMetadata(string $page): array
    {
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
     */
    private function getPageFilePath(string $page): string
    {
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
     * Handle uncaught exceptions
     */
    private function handleException(\Throwable $exception): void
    {
        error_log('Uncaught exception: ' . $exception->getMessage());
        
        $environment = $this->getConfig('constants.ENVIRONMENT', 'production');
        
        if ($environment === 'development' || $this->getConfig('constants.DEBUG_MODE', false)) {
            echo '<pre>' . $exception . '</pre>';
        } else {
            http_response_code(500);
            $error_page = PROJECT_ROOT . '/pages/error.php';
            if (file_exists($error_page)) {
                include $error_page;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
            }
        }
    }

    /**
     * Get configuration value
     */
    public function getConfig(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }
        
        return $value;
    }

    /**
     * Set configuration value
     */
    public function setConfig(string $key, $value): void
    {
        $keys = explode('.', $key);
        $config = &$this->config;
        
        for ($i = 0, $count = count($keys); $i < $count; $i++) {
            if ($i === $count - 1) {
                $config[$keys[$i]] = $value;
            } else {
                if (!isset($config[$keys[$i]]) || !is_array($config[$keys[$i]])) {
                    $config[$keys[$i]] = [];
                }
                $config = &$config[$keys[$i]];
            }
        }
    }

    /**
     * Get a registered service
     * @throws \InvalidArgumentException if service not found
     */
    public function getService(string $name)
    {
        if (!isset($this->services[$name])) {
            throw new \InvalidArgumentException("Service '{$name}' is not registered");
        }
        return $this->services[$name];
    }

    /**
     * Register a service
     */
    public function registerService(string $name, $service): void
    {
        $this->services[$name] = $service;
    }

    /**
     * Check if application has a service registered
     */
    public function hasService(string $name): bool
    {
        return isset($this->services[$name]);
    }

    /**
     * Get all configuration as array
     */
    public function getAllConfig(): array
    {
        return $this->config;
    }

    /**
     * Check if application has been booted
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * Get application version
     */
    public function getVersion(): string
    {
        return $this->getConfig('constants.VERSION', '1.0.0');
    }
}

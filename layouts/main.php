<?php
/**
 * Main Layout Template
 * 
 * Standard layout for authenticated pages with navbar and sidebar
 * Uses localization system for all text content
 */

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__, 2));
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include locale system
require_once PROJECT_ROOT . '/includes/locale.php';

// Default values
$page_title = $page_title ?? __('common.loading');
$page_content = $page_content ?? '';
$sidebar_items = $sidebar_items ?? [];
?>
<!DOCTYPE html>
<html lang="<?php echo LocaleHelper::getInstance()->getCurrentLocale(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Wizdam AI-sikola</title>
    
    <!-- Favicon -->
    <link rel="icon" href="/assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet CSS for maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    
    <!-- Main Stylesheets -->
    <link rel="stylesheet" href="/assets/css/variables.css">
    <link rel="stylesheet" href="/assets/css/base.css">
    <link rel="stylesheet" href="/assets/css/components.css">
    <link rel="stylesheet" href="/assets/css/sdg-colors.css">
    <link rel="stylesheet" href="/assets/css/layout.css">
    <link rel="stylesheet" href="/assets/css/animations.css">
    
    <style>
        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }
        
        .dashboard-sidebar {
            width: 280px;
            background: var(--color-bg);
            border-right: 1px solid var(--color-border);
            padding: 1.5rem;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .dashboard-main {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            background: var(--color-bg-soft);
        }
        
        .sidebar-header {
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--color-border);
            margin-bottom: 1.5rem;
        }
        
        .sidebar-logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-nav li {
            margin-bottom: 0.5rem;
        }
        
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--color-text);
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.2s, color 0.2s;
        }
        
        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: var(--color-bg-muted);
            color: var(--color-primary);
        }
        
        .sidebar-nav i {
            width: 20px;
            text-align: center;
        }
        
        .user-profile {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--color-border);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--color-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        .user-info {
            flex: 1;
            overflow: hidden;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 0.95rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .user-email {
            font-size: 0.8rem;
            color: var(--color-text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .content-card {
            background: var(--color-bg);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--color-bg);
            padding: 1.5rem;
            border-radius: 12px;
            border-left: 4px solid var(--color-primary);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--color-text);
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: var(--color-text-muted);
            margin-top: 0.25rem;
        }
        
        @media (max-width: 1024px) {
            .dashboard-sidebar {
                transform: translateX(-100%);
                z-index: 1000;
                transition: transform 0.3s;
            }
            
            .dashboard-sidebar.open {
                transform: translateX(0);
            }
            
            .dashboard-main {
                margin-left: 0;
            }
        }
    </style>
</head>
<body class="dashboard-page">
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Wizdam AI</span>
                </div>
            </div>
            
            <nav>
                <ul class="sidebar-nav">
                    <li><a href="/dashboard" class="<?php echo ($page ?? '') === 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-home"></i> <?php echo __('nav.dashboard'); ?></a></li>
                    <li><a href="/submit-research"><i class="fas fa-upload"></i> <?php echo __('submit_research.page_title'); ?></a></li>
                    <li><a href="/leaderboard"><i class="fas fa-trophy"></i> <?php echo __('leaderboard.page_title'); ?></a></li>
                    <li><a href="/archived"><i class="fas fa-archive"></i> <?php echo __('nav.archived'); ?></a></li>
                    <li><a href="/analytics"><i class="fas fa-chart-bar"></i> <?php echo __('analytics.page_title'); ?></a></li>
                    <li><a href="/settings"><i class="fas fa-cog"></i> <?php echo __('nav.settings'); ?></a></li>
                </ul>
            </nav>
            
            <div class="user-profile">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? __('common.loading')); ?></div>
                    <div class="user-email"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></div>
                </div>
                <a href="/logout" title="<?php echo __('nav.logout'); ?>"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="dashboard-main">
            <div class="content-card">
                <?php echo $page_content; ?>
            </div>
        </main>
    </div>
    
    <script src="/assets/js/script.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    
    <!-- D3.js -->
    <script src="https://d3js.org/d3.v7.min.js"></script>
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <!-- Scroll Reveal Animation -->
    <script src="/assets/js/scroll-reveal.js"></script>
    
    <!-- Charts Configuration -->
    <script src="/assets/js/charts.js"></script>
</body>
</html>

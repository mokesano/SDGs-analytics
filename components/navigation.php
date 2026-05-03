<?php
/**
 * SDG Frontend - Navigation Component
 * Komponen navigasi responsif dengan semua fitur modern
 * 
 * @version 1.0.0
 * @author Rochmady and Wizdam Team
 * @license MIT
 */

// Get current page
$current_page = isset($page) ? $page : 'home';

// Navigation menu items
$navigation_items = [
    'home' => [
        'title' => 'Home',
        'icon' => 'fas fa-home',
        'url' => '?page=home',
        'description' => 'SDG Analysis Platform'
    ],
    'about' => [
        'title' => 'About',
        'icon' => 'fas fa-info-circle',
        'url' => '?page=about',
        'description' => 'About our platform'
    ],
    'apps' => [
        'title' => 'Apps',
        'icon' => 'fas fa-th-large',
        'url' => '?page=apps',
        'description' => 'SDG applications'
    ],
    'teams' => [
        'title' => 'Teams',
        'icon' => 'fas fa-users',
        'url' => '?page=teams',
        'description' => 'Our team'
    ],
    'help' => [
        'title' => 'Help',
        'icon' => 'fas fa-question-circle',
        'url' => '?page=help',
        'description' => 'Help and support'
    ],
    'contact' => [
        'title' => 'Contact',
        'icon' => 'fas fa-envelope',
        'url' => '?page=contact',
        'description' => 'Contact us'
    ],
    'archived' => [
        'title' => 'Archived',
        'icon' => 'fas fa-archive',
        'url' => '?page=archived',
        'description' => 'Riwayat pencarian'
    ],
    'leaderboard' => [
        'title' => 'Leaderboard',
        'icon' => 'fas fa-trophy',
        'url' => '?page=leaderboard',
        'description' => 'Ranking peneliti SDG'
    ],
];

// Additional menu items (dropdown)
$additional_items = [
    'documentation' => [
        'title' => 'Documentation',
        'icon' => 'fas fa-book',
        'url' => '?page=documentation',
        'description' => 'API documentation'
    ],
    'api-reference' => [
        'title' => 'API Reference',
        'icon' => 'fas fa-code',
        'url' => '?page=api-reference',
        'description' => 'API reference guide'
    ],
    'tutorials' => [
        'title' => 'Tutorials',
        'icon' => 'fas fa-graduation-cap',
        'url' => '?page=tutorials',
        'description' => 'Learning tutorials'
    ],
    'research-papers' => [
        'title' => 'Research',
        'icon' => 'fas fa-microscope',
        'url' => '?page=research-papers',
        'description' => 'Research papers'
    ],
    'community-forum' => [
        'title' => 'Community',
        'icon' => 'fas fa-comments',
        'url' => '?page=community-forum',
        'description' => 'Community forum'
    ],
    'blog' => [
        'title' => 'Blog',
        'icon' => 'fas fa-blog',
        'url' => '?page=blog',
        'description' => 'Latest updates'
    ]
];

// Tools and features
$tools_items = [
    'analitics-dashboard' => [
        'title' => 'Analytics',
        'icon' => 'fas fa-chart-bar',
        'url' => '?page=analitics-dashboard',
        'description' => 'Analytics dashboard'
    ],
    'api-access' => [
        'title' => 'API Access',
        'icon' => 'fas fa-key',
        'url' => '?page=api-access',
        'description' => 'API access management'
    ],
    'bulk-analysis' => [
        'title' => 'Bulk Analysis',
        'icon' => 'fas fa-layer-group',
        'url' => '?page=bulk-analysis',
        'description' => 'Bulk data analysis'
    ],
    'integration-tools' => [
        'title' => 'Integrations',
        'icon' => 'fas fa-plug',
        'url' => '?page=integration-tools',
        'description' => 'Integration tools'
    ]
];
?>

<nav class="navbar" role="navigation" aria-label="Main navigation">
    <div class="navbar-container">
        
        <!-- Brand/Logo -->
        <div class="navbar-brand">
            <a href="?page=home" class="brand-link" aria-label="SDG Analysis - Home">
                <div class="brand-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="brand-text">
                    <span class="brand-name">Wizdam AI-sikola</span>
                    <span class="brand-version">v<?php echo VERSION; ?></span>
                </div>
            </a>
        </div>
        
        <!-- Mobile Menu Toggle -->
        <button class="navbar-toggle" aria-label="Toggle navigation menu" onclick="toggleMobileMenu()">
            <span></span>
            <span></span>
            <span></span>
        </button>
        
        <!-- Main Navigation Menu -->
        <div class="navbar-menu" id="navbarMenu">
            
            <!-- Primary Navigation -->
            <div class="navbar-section navbar-main">
                <?php foreach ($navigation_items as $key => $item): ?>
                <a href="<?php echo $item['url']; ?>" 
                   class="navbar-item <?php echo $current_page === $key ? 'active' : ''; ?>"
                   title="<?php echo htmlspecialchars($item['description']); ?>"
                   <?php if ($key === 'home'): ?>aria-current="page"<?php endif; ?>>
                    <i class="<?php echo $item['icon']; ?>"></i>
                    <span class="navbar-text"><?php echo htmlspecialchars($item['title']); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            
            <!-- Resources Dropdown -->
            <div class="navbar-section navbar-dropdown">
                <div class="dropdown">
                    <button class="dropdown-trigger navbar-item" aria-haspopup="true" aria-expanded="false" onclick="toggleDropdown('resourcesDropdown')">
                        <i class="fas fa-book-open"></i>
                        <span class="navbar-text">Resources</span>
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </button>
                    <div class="dropdown-menu" id="resourcesDropdown" role="menu">
                        <div class="dropdown-content">
                            <?php foreach ($additional_items as $key => $item): ?>
                            <a href="<?php echo $item['url']; ?>" 
                               class="dropdown-item <?php echo $current_page === $key ? 'active' : ''; ?>"
                               role="menuitem">
                                <i class="<?php echo $item['icon']; ?>"></i>
                                <div class="dropdown-item-content">
                                    <span class="dropdown-item-title"><?php echo htmlspecialchars($item['title']); ?></span>
                                    <span class="dropdown-item-desc"><?php echo htmlspecialchars($item['description']); ?></span>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tools Dropdown -->
            <div class="navbar-section navbar-dropdown">
                <div class="dropdown">
                    <button class="dropdown-trigger navbar-item" aria-haspopup="true" aria-expanded="false" onclick="toggleDropdown('toolsDropdown')">
                        <i class="fas fa-tools"></i>
                        <span class="navbar-text">Tools</span>
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </button>
                    <div class="dropdown-menu" id="toolsDropdown" role="menu">
                        <div class="dropdown-content">
                            <?php foreach ($tools_items as $key => $item): ?>
                            <a href="<?php echo $item['url']; ?>" 
                               class="dropdown-item <?php echo $current_page === $key ? 'active' : ''; ?>"
                               role="menuitem">
                                <i class="<?php echo $item['icon']; ?>"></i>
                                <div class="dropdown-item-content">
                                    <span class="dropdown-item-title"><?php echo htmlspecialchars($item['title']); ?></span>
                                    <span class="dropdown-item-desc"><?php echo htmlspecialchars($item['description']); ?></span>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Search -->
            <div class="navbar-section navbar-search">
                <div class="search-container">
                    <input type="text" 
                           class="search-input" 
                           placeholder="Search documentation..." 
                           id="navbarSearch"
                           autocomplete="off"
                           onkeyup="performNavbarSearch(this.value)">
                    <i class="fas fa-search search-icon"></i>
                    <div class="search-results" id="searchResults" style="display: none;"></div>
                </div>
            </div>
            
            <!-- User Actions -->
            <div class="navbar-section navbar-actions">
                
                <!-- Theme Toggle -->
                <button class="navbar-item action-btn" 
                        onclick="toggleTheme()" 
                        title="Toggle dark/light theme"
                        aria-label="Toggle theme">
                    <i class="fas fa-moon" id="themeIcon"></i>
                </button>
                
                <!-- Language Selector -->
                <div class="dropdown">
                    <button class="dropdown-trigger navbar-item action-btn" 
                            aria-haspopup="true" 
                            aria-expanded="false" 
                            onclick="toggleDropdown('languageDropdown')"
                            title="Select language">
                        <i class="fas fa-globe"></i>
                        <span class="navbar-text">EN</span>
                    </button>
                    <div class="dropdown-menu" id="languageDropdown" role="menu">
                        <div class="dropdown-content">
                            <a href="#" class="dropdown-item active" onclick="setLanguage('en')" role="menuitem">
                                <span class="flag-icon">🇺🇸</span> English
                            </a>
                            <a href="#" class="dropdown-item" onclick="setLanguage('id')" role="menuitem">
                                <span class="flag-icon">🇮🇩</span> Bahasa Indonesia
                            </a>
                            <a href="#" class="dropdown-item" onclick="setLanguage('es')" role="menuitem">
                                <span class="flag-icon">🇪🇸</span> Español
                            </a>
                            <a href="#" class="dropdown-item" onclick="setLanguage('fr')" role="menuitem">
                                <span class="flag-icon">🇫🇷</span> Français
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Notifications -->
                <button class="navbar-item action-btn notification-btn" 
                        onclick="toggleNotifications()" 
                        title="Notifications"
                        aria-label="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge" id="notificationBadge" style="display: none;">3</span>
                </button>
                
                <!-- Auth Section -->
                <?php if (isset($_SESSION['user_id'])): ?>
                <div class="dropdown">
                    <button class="dropdown-trigger navbar-item action-btn" onclick="toggleDropdown('userDropdown')" title="Akun Saya" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-user-circle"></i>
                        <span class="navbar-text"><?php echo htmlspecialchars(isset($_SESSION['user_name']) ? mb_substr($_SESSION['user_name'], 0, 12) : 'User'); ?></span>
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </button>
                    <div class="dropdown-menu" id="userDropdown" role="menu">
                        <div class="dropdown-content">
                            <a href="?page=home" class="dropdown-item" role="menuitem">
                                <i class="fas fa-tachometer-alt"></i>
                                <div class="dropdown-item-content"><span class="dropdown-item-title">Dashboard</span><span class="dropdown-item-desc">Halaman utama</span></div>
                            </a>
                            <a href="?page=archived" class="dropdown-item" role="menuitem">
                                <i class="fas fa-history"></i>
                                <div class="dropdown-item-content"><span class="dropdown-item-title">Riwayat Analisis</span></div>
                            </a>
                            <hr style="margin:4px 0;border-color:#f0f0f0;">
                            <a href="#" class="dropdown-item" role="menuitem" onclick="logoutUser(); return false;">
                                <i class="fas fa-sign-out-alt" style="color:#e74c3c;"></i>
                                <div class="dropdown-item-content"><span class="dropdown-item-title" style="color:#e74c3c;">Keluar</span></div>
                            </a>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <a href="?page=login" class="navbar-item cta-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    <span class="navbar-text">Masuk</span>
                </a>
                <a href="?page=register" class="navbar-item" style="padding:6px 14px;background:transparent;border:1.5px solid var(--brand,#ff5627);border-radius:8px;color:var(--brand,#ff5627);font-size:.85rem;font-weight:600;display:inline-flex;align-items:center;gap:6px;text-decoration:none;margin-left:4px;transition:all .2s;">
                    <i class="fas fa-user-plus"></i>
                    <span class="navbar-text">Daftar</span>
                </a>
                <?php endif; ?>

            </div>
        </div>
    </div>
    
    <!-- Mobile Search (visible only on mobile) -->
    <div class="mobile-search" id="mobileSearch" style="display: none;">
        <div class="mobile-search-container">
            <input type="text" 
                   class="mobile-search-input" 
                   placeholder="Search..." 
                   autocomplete="off">
            <button class="mobile-search-close" onclick="closeMobileSearch()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    
    <!-- Progress Bar (for page loading) -->
    <div class="progress-bar" id="pageProgress">
        <div class="progress-fill"></div>
    </div>
</nav>

<!-- Navigation Scripts -->
<script>
// Logout user
function logoutUser() {
    const fd = new FormData();
    fd.append('action', 'logout');
    fetch('../api/auth.php', { method: 'POST', body: fd })
        .then(() => { window.location.href = '?page=home'; })
        .catch(() => { window.location.href = '?page=home'; });
}

// Mobile menu toggle
function toggleMobileMenu() {
    const menu = document.getElementById('navbarMenu');
    const toggle = document.querySelector('.navbar-toggle');
    
    menu.classList.toggle('active');
    toggle.classList.toggle('active');
    
    // Update ARIA attributes
    const isExpanded = menu.classList.contains('active');
    toggle.setAttribute('aria-expanded', isExpanded);
    
    // Prevent body scroll when menu is open
    document.body.style.overflow = isExpanded ? 'hidden' : '';
}

// Dropdown toggle
function toggleDropdown(dropdownId) {
    const dropdown = document.getElementById(dropdownId);
    const trigger = dropdown.previousElementSibling;
    const isActive = dropdown.classList.contains('active');
    
    // Close all other dropdowns
    document.querySelectorAll('.dropdown-menu.active').forEach(menu => {
        if (menu.id !== dropdownId) {
            menu.classList.remove('active');
            menu.previousElementSibling.setAttribute('aria-expanded', 'false');
            menu.previousElementSibling.classList.remove('active');
        }
    });
    
    // Toggle current dropdown
    dropdown.classList.toggle('active');
    trigger.setAttribute('aria-expanded', !isActive);
    trigger.classList.toggle('active');
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu.active').forEach(menu => {
            menu.classList.remove('active');
            menu.previousElementSibling.setAttribute('aria-expanded', 'false');
            menu.previousElementSibling.classList.remove('active');
        });
    }
});

// Theme toggle
function toggleTheme() {
    const body = document.body;
    const themeIcon = document.getElementById('themeIcon');
    const isDark = body.classList.toggle('dark-theme');
    
    // Update icon
    themeIcon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
    
    // Save preference
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    
    // Dispatch custom event for other components
    window.dispatchEvent(new CustomEvent('themeChanged', { detail: { isDark } }));
}

// Initialize theme from localStorage
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const shouldUseDark = savedTheme === 'dark' || (!savedTheme && prefersDark);
    
    if (shouldUseDark) {
        document.body.classList.add('dark-theme');
        document.getElementById('themeIcon').className = 'fas fa-sun';
    }
});

// Language selector
function setLanguage(lang) {
    // Store language preference
    localStorage.setItem('language', lang);
    
    // Update UI text (simplified example)
    const langText = document.querySelector('.navbar-actions .navbar-text');
    const langMap = {
        'en': 'EN',
        'id': 'ID', 
        'es': 'ES',
        'fr': 'FR'
    };
    
    if (langText) {
        langText.textContent = langMap[lang] || 'EN';
    }
    
    // Close dropdown
    document.getElementById('languageDropdown').classList.remove('active');
    
    // Dispatch language change event
    window.dispatchEvent(new CustomEvent('languageChanged', { detail: { language: lang } }));
    
    // In a real implementation, you would reload content in the selected language
    console.log('Language changed to:', lang);
}

// Search functionality
let searchTimeout;
function performNavbarSearch(query) {
    clearTimeout(searchTimeout);
    const resultsContainer = document.getElementById('searchResults');
    
    if (query.length < 2) {
        resultsContainer.style.display = 'none';
        return;
    }
    
    searchTimeout = setTimeout(() => {
        // Simulate search results
        const mockResults = [
            { title: 'API Documentation', url: '?page=api-reference', type: 'documentation' },
            { title: 'SDG Analysis Tutorial', url: '?page=tutorials', type: 'tutorial' },
            { title: 'ORCID Integration Guide', url: '?page=documentation', type: 'guide' },
            { title: 'Bulk Analysis Tools', url: '?page=bulk-analysis', type: 'tool' }
        ].filter(item => 
            item.title.toLowerCase().includes(query.toLowerCase())
        );
        
        if (mockResults.length > 0) {
            resultsContainer.innerHTML = mockResults.map(result => `
                <a href="${result.url}" class="search-result-item">
                    <div class="search-result-title">${result.title}</div>
                    <div class="search-result-type">${result.type}</div>
                </a>
            `).join('');
            resultsContainer.style.display = 'block';
        } else {
            resultsContainer.innerHTML = '<div class="search-no-results">No results found</div>';
            resultsContainer.style.display = 'block';
        }
    }, 300);
}

// Hide search results when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.search-container')) {
        document.getElementById('searchResults').style.display = 'none';
    }
});

// Notifications
function toggleNotifications() {
    // Simulate notification panel
    const hasNotifications = document.getElementById('notificationBadge').style.display !== 'none';
    
    if (hasNotifications) {
        // Show notifications modal or panel
        alert('Notifications panel would open here.\n\nRecent notifications:\n• Analysis completed for ORCID: 0000-0002-1825-0097\n• New API documentation available\n• System maintenance scheduled');
        
        // Hide notification badge
        document.getElementById('notificationBadge').style.display = 'none';
    } else {
        alert('No new notifications');
    }
}

// Mobile search
function openMobileSearch() {
    document.getElementById('mobileSearch').style.display = 'flex';
    document.querySelector('.mobile-search-input').focus();
}

function closeMobileSearch() {
    document.getElementById('mobileSearch').style.display = 'none';
}

// Keyboard navigation
document.addEventListener('keydown', function(event) {
    // Escape key closes dropdowns and mobile menu
    if (event.key === 'Escape') {
        // Close mobile menu
        const menu = document.getElementById('navbarMenu');
        if (menu.classList.contains('active')) {
            toggleMobileMenu();
        }
        
        // Close dropdowns
        document.querySelectorAll('.dropdown-menu.active').forEach(menu => {
            menu.classList.remove('active');
            menu.previousElementSibling.setAttribute('aria-expanded', 'false');
            menu.previousElementSibling.classList.remove('active');
        });
        
        // Close search results
        document.getElementById('searchResults').style.display = 'none';
    }
    
    // Ctrl/Cmd + K opens search
    if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
        event.preventDefault();
        document.getElementById('navbarSearch').focus();
    }
});

// Progress bar for page navigation
function showPageProgress() {
    const progressBar = document.getElementById('pageProgress');
    const progressFill = progressBar.querySelector('.progress-fill');
    
    progressBar.style.opacity = '1';
    progressFill.style.width = '0%';
    
    // Animate progress
    let progress = 0;
    const interval = setInterval(() => {
        progress += Math.random() * 30;
        if (progress > 90) progress = 90;
        
        progressFill.style.width = progress + '%';
        
        if (progress >= 90) {
            clearInterval(interval);
        }
    }, 100);
    
    return {
        complete: () => {
            progressFill.style.width = '100%';
            setTimeout(() => {
                progressBar.style.opacity = '0';
                setTimeout(() => {
                    progressFill.style.width = '0%';
                }, 500);
            }, 200);
        }
    };
}

// Intercept navigation links to show progress
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.navbar-item[href^="?"], .dropdown-item[href^="?"]');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            // Only show progress for same-origin navigation
            if (this.href && this.href.includes('?page=')) {
                const progress = showPageProgress();
                
                // Complete progress after a short delay (simulating page load)
                setTimeout(() => {
                    progress.complete();
                }, 1000 + Math.random() * 1000);
            }
        });
    });
});

// Smooth scroll for anchor links
document.addEventListener('click', function(event) {
    if (event.target.matches('a[href^="#"]')) {
        event.preventDefault();
        const target = document.querySelector(event.target.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    }
});

// Add active states and accessibility improvements
document.addEventListener('DOMContentLoaded', function() {
    // Add focus visible for keyboard navigation
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Tab') {
            document.body.classList.add('keyboard-navigation');
        }
    });
    
    document.addEventListener('mousedown', function() {
        document.body.classList.remove('keyboard-navigation');
    });
    
    // Update notification badge (simulated)
    const hasNotifications = Math.random() > 0.5; // Random for demo
    if (hasNotifications) {
        document.getElementById('notificationBadge').style.display = 'inline-block';
    }
});

// Responsive breakpoint detection
function updateNavbarForBreakpoint() {
    const isMobile = window.innerWidth <= 768;
    const navbar = document.querySelector('.navbar');
    
    if (isMobile) {
        navbar.classList.add('mobile');
    } else {
        navbar.classList.remove('mobile');
        // Close mobile menu if open
        const menu = document.getElementById('navbarMenu');
        if (menu.classList.contains('active')) {
            toggleMobileMenu();
        }
    }
}

// Listen for window resize
window.addEventListener('resize', updateNavbarForBreakpoint);
document.addEventListener('DOMContentLoaded', updateNavbarForBreakpoint);

// Add scroll effect to navbar
let lastScrollTop = 0;
window.addEventListener('scroll', function() {
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    const navbar = document.querySelector('.navbar');
    
    if (scrollTop > lastScrollTop && scrollTop > 100) {
        // Scrolling down
        navbar.classList.add('scrolled-down');
    } else {
        // Scrolling up
        navbar.classList.remove('scrolled-down');
    }
    
    // Add scrolled class for styling
    if (scrollTop > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
    
    lastScrollTop = scrollTop;
});

// Preload critical pages
const criticalPages = ['?page=about', '?page=documentation', '?page=help'];
criticalPages.forEach(url => {
    const link = document.createElement('link');
    link.rel = 'prefetch';
    link.href = url;
    document.head.appendChild(link);
});
</script>

<!-- Navigation Styles -->
<style>
/* Additional navbar styles that complement the main CSS */
.navbar {
    /*position: relative;*/
    transition: all 0.3s ease;
}

.navbar.scrolled {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
}

.navbar.scrolled-down {
    transform: translateY(-100%);
}

.progress-bar {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 2px;
    background: transparent;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--brand, #ff5627), var(--brand-hover, #e0481d));
    width: 0%;
    transition: width 0.3s ease;
}

.search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
}

.search-result-item {
    display: block;
    padding: 12px 16px;
    text-decoration: none;
    color: #333;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s ease;
}

.search-result-item:hover {
    background-color: #f8f9fa;
}

.search-result-title {
    font-weight: 500;
    margin-bottom: 4px;
}

.search-result-type {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
}

.search-no-results {
    padding: 16px;
    text-align: center;
    color: #666;
    font-style: italic;
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #e74c3c;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.mobile-search {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border-top: 1px solid #e0e0e0;
    padding: 16px;
    z-index: 999;
}

.mobile-search-container {
    display: flex;
    align-items: center;
    gap: 12px;
}

.mobile-search-input {
    flex: 1;
    padding: 12px 16px;
    border: 1px solid #ddd;
    border-radius: 25px;
    font-size: 16px;
    outline: none;
}

.mobile-search-close {
    background: none;
    border: none;
    font-size: 18px;
    color: #666;
    cursor: pointer;
    padding: 8px;
}

/* Dark theme styles */
.dark-theme .navbar {
    background: rgba(30, 30, 30, 0.95);
    color: white;
}

.dark-theme .navbar.scrolled {
    background: rgba(20, 20, 20, 0.95);
}

.dark-theme .search-results {
    background: #2a2a2a;
    border-color: #404040;
}

.dark-theme .search-result-item {
    color: #e0e0e0;
    border-color: #404040;
}

.dark-theme .search-result-item:hover {
    background-color: #333;
}

.dark-theme .mobile-search {
    background: #2a2a2a;
    border-color: #404040;
}

.dark-theme .mobile-search-input {
    background: #333;
    border-color: #404040;
    color: white;
}

/* Keyboard navigation */
.keyboard-navigation .navbar-item:focus,
.keyboard-navigation .dropdown-trigger:focus {
    outline: 2px solid var(--brand, #ff5627);
    outline-offset: 2px;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .navbar {
        border-bottom: 2px solid currentColor;
    }
    
    .navbar-item {
        border: 1px solid transparent;
    }
    
    .navbar-item:focus {
        border-color: currentColor;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .navbar,
    .dropdown-menu,
    .progress-fill,
    .navbar-item {
        transition: none;
    }
}
</style>
<?php
// components/header.php - Header component
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' | ' : ''; ?>SDGs Classification Analysis | Wizdam AI</title>
    
    <!-- Meta Tags -->
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : 'Advanced AI-powered platform for analyzing research contributions to Sustainable Development Goals. Empowering researchers and institutions with intelligent classification and insights.'; ?>" />
    <meta name="keywords" content="SDG, Sustainable Development Goals, Research Analysis, AI Classification, Academic Research, ORCID, DOI, Research Impact" />
    <meta name="author" content="Wizdam AI Team" />
    <meta name="owner" content="PT. Sangia Research Media and Publishing" />
    <meta name="design" content="Rochmady and Wizdam AI Team" />
    <meta name="generator" content="Wizdam AI v<?php echo VERSION; ?>" />
    <meta name="robots" content="index, follow" />
    <meta name="language" content="English" />
    <meta name="revisit-after" content="7 days" />
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo isset($page_title) ? $page_title : 'SDGs Classification Analysis'; ?>" />
    <meta property="og:description" content="<?php echo isset($page_description) ? $page_description : 'Advanced AI-powered platform for analyzing research contributions to Sustainable Development Goals.'; ?>" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?php echo SITE_URL . $_SERVER['REQUEST_URI']; ?>" />
    <meta property="og:image" content="<?php echo SITE_URL; ?>/assets/images/og-image.jpg" />
    <meta property="og:site_name" content="Wizdam AI - SDG Analysis" />
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="<?php echo isset($page_title) ? $page_title : 'SDGs Classification Analysis'; ?>" />
    <meta name="twitter:description" content="<?php echo isset($page_description) ? $page_description : 'Advanced AI-powered platform for analyzing research contributions to Sustainable Development Goals.'; ?>" />
    <meta name="twitter:image" content="<?php echo SITE_URL; ?>/assets/images/twitter-card.jpg" />
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>/assets/images/favicon.ico" />
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo SITE_URL; ?>/assets/images/favicon-32x32.png" />
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo SITE_URL; ?>/assets/images/favicon-16x16.png" />
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo SITE_URL; ?>/assets/images/apple-touch-icon.png" />
    <link rel="manifest" href="<?php echo SITE_URL; ?>/assets/images/site.webmanifest" />
    
    <!-- Preconnect to External Resources -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" />
    <link rel="preconnect" href="https://assets.sangia.org" />
    
    <!-- External CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@700;800&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet" />

    <!-- Local CSS -->
    <link href="assets/css/style.css" rel="stylesheet" />
    <link href="assets/css/chatbot.css" rel="stylesheet" />

    <!-- Design System CSS -->
    <link href="assets/css/variables.css" rel="stylesheet" />
    <link href="assets/css/base.css" rel="stylesheet" />
    <link href="assets/css/components.css" rel="stylesheet" />
    <link href="assets/css/sdg-colors.css" rel="stylesheet" />
    <link href="assets/css/layout.css" rel="stylesheet" />
    <link href="assets/css/animations.css" rel="stylesheet" />
    
    <!-- External JavaScript Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js" defer></script>
    
    <!-- Schema.org Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "SDGs Classification Analysis",
        "description": "Advanced AI-powered platform for analyzing research contributions to Sustainable Development Goals",
        "url": "<?php echo SITE_URL; ?>",
        "applicationCategory": "EducationApplication",
        "operatingSystem": "Web Browser",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "USD"
        },
        "creator": {
            "@type": "Organization",
            "name": "Wizdam AI",
            "url": "<?php echo SITE_URL; ?>"
        },
        "featureList": [
            "ORCID Analysis",
            "DOI Analysis", 
            "SDG Classification",
            "Research Impact Assessment",
            "AI-Powered Analysis"
        ]
    }
    </script>
    
    <!-- Performance and Analytics -->
    <?php if (defined('GOOGLE_ANALYTICS_ID') && GOOGLE_ANALYTICS_ID): ?>
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo GOOGLE_ANALYTICS_ID; ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo GOOGLE_ANALYTICS_ID; ?>');
    </script>
    <?php endif; ?>
    
    <!-- Critical CSS for Above-the-Fold Content -->
    <style>
        /* Critical CSS - Inline for faster loading */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", "Roboto", system-ui, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            color: #333;
            padding-top: 80px;
            line-height: 1.6;
        }
        
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0,0,0,0.1);
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 27px;
            color: white;
            padding: 70px 30px;
            background: linear-gradient(135deg, #ff5627 0%, #e0481d 100%);
            position: relative;
            overflow: hidden;
        }
        
        /* Loading states */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
    
    <!-- Preload Important Resources -->
    <link rel="preload" href="assets/css/style.css" as="style" />
    <link rel="preload" href="assets/css/navbar.css" as="style" />
    <link rel="preload" href="assets/js/script.js" as="script" />
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/webfonts/fa-solid-900.woff2" as="font" type="font/woff2" crossorigin />
    
    <!-- DNS Prefetch for External Resources -->
    <link rel="dns-prefetch" href="//pub.orcid.org" />
    <link rel="dns-prefetch" href="//api.crossref.org" />
    <link rel="dns-prefetch" href="//assets.sangia.org" />
    
    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff" />
    <meta http-equiv="X-Frame-Options" content="DENY" />
    <meta http-equiv="X-XSS-Protection" content="1; mode=block" />
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin" />
    
    <!-- Theme Color for Mobile Browsers -->
    <meta name="theme-color" content="#ff5627" />
    <meta name="msapplication-TileColor" content="#ff5627" />
    <meta name="msapplication-TileImage" content="<?php echo SITE_URL; ?>/assets/images/mstile-144x144.png" />
    
    <!-- Apple-specific Meta Tags -->
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="default" />
    <meta name="apple-mobile-web-app-title" content="SDG Analysis" />
    
    <!-- Additional Page-Specific Styles -->
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css_file): ?>
            <link href="<?php echo $css_file; ?>" rel="stylesheet" />
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Additional Page-Specific Meta Tags -->
    <?php if (isset($additional_meta)): ?>
        <?php echo $additional_meta; ?>
    <?php endif; ?>
    
    <!-- Custom Inline Styles -->
    <?php if (isset($inline_styles)): ?>
        <style><?php echo $inline_styles; ?></style>
    <?php endif; ?>
</head>
<body class="<?php echo isset($body_class) ? $body_class : ''; ?>">
    <!-- Skip to Content Link for Accessibility -->
    <a href="#main-content" class="skip-link sr-only sr-only-focusable">Skip to main content</a>
    
    <!-- Page Loading Indicator -->
    <div id="page-loader" class="page-loader">
        <div class="loader-spinner"></div>
        <div class="loader-text">Loading...</div>
    </div>
    
    <!-- Accessibility Styles -->
    <style>
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        .sr-only-focusable:focus {
            position: static;
            width: auto;
            height: auto;
            padding: 0.5rem 1rem;
            margin: 0;
            overflow: visible;
            clip: auto;
            white-space: normal;
            background: #007bff;
            color: white;
            text-decoration: none;
            z-index: 10000;
        }
        
        .page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 1;
            transition: opacity 0.3s ease;
        }
        
        .page-loader.hidden {
            opacity: 0;
            pointer-events: none;
        }
        
        .loader-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #ff5627;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }
        
        .loader-text {
            color: #ff5627;
            font-weight: 600;
            font-size: 14px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* High contrast mode support */
        @media (prefers-contrast: high) {
            .navbar {
                border-bottom: 2px solid #000;
            }
            
            .header {
                background: #000;
                color: #fff;
            }
        }
        
        /* Reduced motion support */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }
    </style>
    
    <!-- Initialize Page Loader -->
    <script>
        // Hide page loader when content is ready
        window.addEventListener('load', function() {
            const loader = document.getElementById('page-loader');
            if (loader) {
                setTimeout(() => {
                    loader.classList.add('hidden');
                    setTimeout(() => {
                        loader.style.display = 'none';
                    }, 300);
                }, 500);
            }
        });
        
        // Performance monitoring
        if ('performance' in window) {
            window.addEventListener('load', function() {
                setTimeout(function() {
                    const perf = performance.getEntriesByType('navigation')[0];
                    console.log('Page load time:', perf.loadEventEnd - perf.fetchStart, 'ms');
                }, 0);
            });
        }
        
        // Global error handling
        window.addEventListener('error', function(e) {
            console.error('Global error:', e.error);
            // You can send error reports to your analytics service here
        });
        
        // Unhandled promise rejection handling
        window.addEventListener('unhandledrejection', function(e) {
            console.error('Unhandled promise rejection:', e.reason);
        });
    </script>
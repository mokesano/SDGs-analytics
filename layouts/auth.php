<?php
/**
 * Auth Layout Template
 * 
 * Clean, minimal layout for authentication pages (login, register, forgot password)
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
?>
<!DOCTYPE html>
<html lang="<?php echo LocaleHelper::getInstance()->getCurrentLocale(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('auth.page_title'); ?> - Wizdam AI-sikola</title>
    <meta name="description" content="<?php echo __('auth.meta_description'); ?>">
    
    <!-- Favicon -->
    <link rel="icon" href="/assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Main Stylesheets -->
    <link rel="stylesheet" href="/assets/css/variables.css">
    <link rel="stylesheet" href="/assets/css/base.css">
    <link rel="stylesheet" href="/assets/css/components.css">
    <link rel="stylesheet" href="/assets/css/sdg-colors.css">
    <link rel="stylesheet" href="/assets/css/layout.css">
    <link rel="stylesheet" href="/assets/css/animations.css">
    
    <!-- Auth-specific styles -->
    <style>
        .auth-layout {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--color-bg-soft) 0%, var(--color-bg-muted) 100%);
            padding: 2rem;
        }
        
        .auth-container {
            width: 100%;
            max-width: 480px;
            background: var(--color-bg);
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .auth-header {
            padding: 2.5rem 2rem 1.5rem;
            text-align: center;
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-light) 100%);
            color: white;
        }
        
        .auth-logo {
            width: 64px;
            height: 64px;
            margin: 0 auto 1rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }
        
        .auth-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .auth-subtitle {
            font-size: 0.95rem;
            opacity: 0.9;
            line-height: 1.5;
        }
        
        .auth-body {
            padding: 2rem;
        }
        
        .auth-content {
            margin-bottom: 1.5rem;
        }
        
        .auth-form .form-group {
            margin-bottom: 1.25rem;
        }
        
        .auth-form label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--color-text);
        }
        
        .auth-form input[type="email"],
        .auth-form input[type="password"],
        .auth-form input[type="text"] {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--color-border);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .auth-form input:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
        }
        
        .auth-form .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        
        .auth-form .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }
        
        .auth-form .forgot-link {
            color: var(--color-primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .auth-form .forgot-link:hover {
            text-decoration: underline;
        }
        
        .auth-form .btn-submit {
            width: 100%;
            padding: 1rem;
            background: var(--color-primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
        }
        
        .auth-form .btn-submit:hover {
            background: var(--color-primary-light);
            transform: translateY(-1px);
        }
        
        .auth-form .btn-submit:active {
            transform: translateY(0);
        }
        
        .auth-footer {
            margin-top: 1.5rem;
            text-align: center;
            color: var(--color-text-muted);
            font-size: 0.9rem;
        }
        
        .auth-footer a {
            color: var(--color-primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }
        
        .alert-error {
            background: #FEF2F2;
            color: #DC2626;
            border: 1px solid #FECACA;
        }
        
        .alert-success {
            background: #F0FDF4;
            color: #16A34A;
            border: 1px solid #BBF7D0;
        }
        
        .benefits-list {
            list-style: none;
            padding: 0;
            margin: 1.5rem 0;
        }
        
        .benefits-list li {
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--color-border);
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }
        
        .benefits-list li:last-child {
            border-bottom: none;
        }
        
        .benefits-list i {
            color: var(--sdg4);
            margin-top: 0.25rem;
        }
        
        .benefit-title {
            font-weight: 600;
            color: var(--color-text);
            display: block;
            margin-bottom: 0.25rem;
        }
        
        .benefit-desc {
            font-size: 0.875rem;
            color: var(--color-text-muted);
        }
        
        @media (max-width: 640px) {
            .auth-layout {
                padding: 1rem;
            }
            
            .auth-header {
                padding: 2rem 1.5rem 1rem;
            }
            
            .auth-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body class="auth-page">
    <div class="auth-layout">
        <div class="auth-container">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h1 class="auth-title"><?php echo __('auth.brand_name'); ?></h1>
                <p class="auth-subtitle"><?php echo __('auth.tagline'); ?></p>
            </div>
            
            <div class="auth-body">
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <div class="auth-content">
                    <?php echo $content ?? ''; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="/assets/js/script.js"></script>
    <script src="/assets/js/scroll-reveal.js"></script>
</body>
</html>

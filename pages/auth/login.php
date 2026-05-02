<?php
/**
 * Login Page - Wizdam AI-sikola
 * 
 * Authentication page using delight-im/auth
 * Content is loaded from markdown with localization support
 */

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__, 2));
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include dependencies
require_once PROJECT_ROOT . '/includes/locale.php';
require_once PROJECT_ROOT . '/includes/markdown_parser.php';
require_once PROJECT_ROOT . '/vendor/autoload.php';

use Delight\Auth\Auth;
use Delight\Auth\AnonymousUserException;

// Initialize Auth (will be configured properly once database is set up)
$dbh = new PDO('sqlite:' . PROJECT_ROOT . '/database/wizdam.sqlite');
$auth = new Auth($dbh);

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    try {
        $auth->login($email, $password, function ($timeout) use ($remember) {
            return $remember ? $timeout : null;
        });
        
        // Login successful
        header('Location: /dashboard');
        exit;
        
    } catch (\Delight\Auth\InvalidEmailPasswordException $e) {
        $error_message = __('auth.error_invalid_credentials');
    } catch (\Delight\Auth\EmailNotVerifiedException $e) {
        $error_message = __('auth.error_email_not_verified');
    } catch (\Delight\Auth\TooManyRequestsException $e) {
        $error_message = __('auth.error_too_many_requests');
    } catch (\Exception $e) {
        $error_message = __('auth.error_generic');
    }
}

// Load markdown content
$content_file = PROJECT_ROOT . '/content/markdown/login_content.md';
$content = MarkdownParser::parse($content_file);

// Build login form HTML
$form_html = '
<form class="auth-form" method="POST" action="">
    <div class="form-group">
        <label for="email">' . __('login.email_label') . '</label>
        <input type="email" id="email" name="email" required placeholder="' . __('register.email_placeholder') . '" autocomplete="email">
    </div>
    
    <div class="form-group">
        <label for="password">' . __('login.password_label') . '</label>
        <input type="password" id="password" name="password" required placeholder="••••••••" autocomplete="current-password">
    </div>
    
    <div class="form-options">
        <label class="checkbox-label">
            <input type="checkbox" name="remember">
            ' . __('login.remember_me') . '
        </label>
        <a href="/forgot-password" class="forgot-link">' . __('login.forgot_password') . '</a>
    </div>
    
    <button type="submit" class="btn-submit">' . __('login.submit_button') . '</button>
</form>

<div class="auth-footer">
    <p>' . __('login.no_account') . ' <a href="/register">' . __('login.register_link') . '</a></p>
</div>
';

// Combine content and form
$full_content = $content . $form_html;

// Include auth layout
include PROJECT_ROOT . '/layouts/auth.php';

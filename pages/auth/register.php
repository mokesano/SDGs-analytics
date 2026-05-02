<?php
/**
 * Register Page - Wizdam AI-sikola
 * 
 * User registration page using delight-im/auth
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

// Initialize Auth
$dbh = new PDO('sqlite:' . PROJECT_ROOT . '/database/wizdam.sqlite');
$auth = new Auth($dbh);

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $orcid = trim($_POST['orcid'] ?? '');
    
    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $error_message = __('auth.error_missing_fields');
    } elseif ($password !== $confirm_password) {
        $error_message = __('auth.error_passwords_mismatch');
    } elseif (strlen($password) < 8) {
        $error_message = __('auth.error_password_too_short');
    } else {
        try {
            // Create user account
            $userId = $auth->createUser($email, $password, $name);
            
            // Store additional user data (ORCID) in session or custom table
            if (!empty($orcid)) {
                // Could be stored in a custom users_extended table
                $_SESSION['pending_orcid'] = $orcid;
            }
            
            $success_message = __('auth.registration_success');
            
            // Optionally auto-login
            // $auth->login($email, $password);
            // header('Location: /dashboard');
            // exit;
            
        } catch (\Delight\Auth\DuplicateEmailException $e) {
            $error_message = __('auth.error_email_exists');
        } catch (\Delight\Auth\InvalidEmailException $e) {
            $error_message = __('auth.error_invalid_email');
        } catch (\Delight\Auth\InvalidPasswordException $e) {
            $error_message = __('auth.error_weak_password');
        } catch (\Exception $e) {
            $error_message = __('auth.error_generic');
        }
    }
}

// Load markdown content
$content_file = PROJECT_ROOT . '/content/markdown/register_content.md';
$content = MarkdownParser::parse($content_file);

// Build registration form HTML
$form_html = '
<form class="auth-form" method="POST" action="">
    <div class="form-group">
        <label for="name">' . __('register.name_label') . ' <span class="required">*</span></label>
        <input type="text" id="name" name="name" required placeholder="' . __('register.name_placeholder') . '" autocomplete="name" value="' . htmlspecialchars($_POST['name'] ?? '') . '">
    </div>
    
    <div class="form-group">
        <label for="orcid">' . __('register.orcid_label') . '</label>
        <input type="text" id="orcid" name="orcid" placeholder="' . __('register.orcid_placeholder') . '" pattern="\d{4}-\d{4}-\d{4}-\d{3}[\dX]" title="Format: 0000-0000-0000-000X" value="' . htmlspecialchars($_POST['orcid'] ?? '') . '">
    </div>
    
    <div class="form-group">
        <label for="email">' . __('register.email_label') . ' <span class="required">*</span></label>
        <input type="email" id="email" name="email" required placeholder="' . __('register.email_placeholder') . '" autocomplete="email" value="' . htmlspecialchars($_POST['email'] ?? '') . '">
    </div>
    
    <div class="form-group">
        <label for="password">' . __('register.password_label') . ' <span class="required">*</span></label>
        <input type="password" id="password" name="password" required placeholder="••••••••" autocomplete="new-password" minlength="8">
        <small class="form-hint">' . __('register.password_requirements') . '</small>
    </div>
    
    <div class="form-group">
        <label for="confirm_password">' . __('register.confirm_password_label') . ' <span class="required">*</span></label>
        <input type="password" id="confirm_password" name="confirm_password" required placeholder="••••••••" autocomplete="new-password">
    </div>
    
    <div class="form-group">
        <label class="checkbox-label">
            <input type="checkbox" name="terms" required>
            ' . __('register.terms_checkbox') . ' <a href="/privacy-policy" target="_blank">' . __('register.terms_link') . '</a> ' . __('register.and') . ' <a href="/privacy-policy" target="_blank">' . __('register.privacy_link') . '</a>
        </label>
    </div>
    
    <button type="submit" class="btn-submit">' . __('register.submit_button') . '</button>
</form>

<div class="auth-footer">
    <p>' . __('register.have_account') . ' <a href="/login">' . __('register.login_link') . '</a></p>
</div>
';

// Combine content and form
$full_content = $content . $form_html;

// Include auth layout
include PROJECT_ROOT . '/layouts/auth.php';

<?php
/**
 * Forgot Password Page - Wizdam AI-sikola
 * 
 * Password reset request page using delight-im/auth
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
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    if (empty($email)) {
        $error_message = __('auth.error_email_required');
    } else {
        try {
            // Initiate password reset
            $auth->requestPasswordReset($email, function ($selector, $token) {
                // In production, send email with reset link
                // For now, just log it or show in success message
                error_log("Password reset requested for selector: " . $selector);
            });
            
            // Don't reveal whether email exists or not (security best practice)
            $success_message = __('forgot_password.check_email');
            
        } catch (\Delight\Auth\InvalidEmailException $e) {
            $error_message = __('auth.error_invalid_email');
        } catch (\Delight\Auth\UserAlreadyExistsException $e) {
            // This shouldn't happen in reset flow, but handle gracefully
            $error_message = __('auth.error_generic');
        } catch (\Delight\Auth\TooManyRequestsException $e) {
            $error_message = __('auth.error_too_many_requests');
        } catch (\Exception $e) {
            $error_message = __('auth.error_generic');
        }
    }
}

// Load markdown content
$content_file = PROJECT_ROOT . '/content/markdown/forgot_password_content.md';
$content = MarkdownParser::parse($content_file);

// Build forgot password form HTML
$form_html = '
<form class="auth-form" method="POST" action="">
    <div class="form-group">
        <label for="email">' . __('forgot_password.email_label') . '</label>
        <input type="email" id="email" name="email" required placeholder="' . __('register.email_placeholder') . '" autocomplete="email">
    </div>
    
    <button type="submit" class="btn-submit">' . __('forgot_password.submit_button') . '</button>
</form>

<div class="auth-footer">
    <p><a href="/login">← ' . __('forgot_password.back_to_login') . '</a></p>
</div>
';

// Combine content and form
$full_content = $content . $form_html;

// Include auth layout
include PROJECT_ROOT . '/layouts/auth.php';

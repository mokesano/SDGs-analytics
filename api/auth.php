<?php
/**
 * api/auth.php — Authentication API Endpoint
 * Handles register, login, logout actions via POST requests
 *
 * @version 1.0.0
 * @author Wizdam Team
 */

session_start();

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once PROJECT_ROOT . '/includes/auth.php';

// Always output JSON
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? '';

// ── Register ──────────────────────────────────────────────────────────────────
if ($action === 'register') {
    $token = $_POST['_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid security token. Please refresh the page.']);
        exit;
    }

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';

    $errors = [];
    if (strlen($name) < 2 || strlen($name) > 100) {
        $errors[] = 'Name must be between 2 and 100 characters.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => implode(' ', $errors)]);
        exit;
    }

    $result = authRegister($email, $password, $name);
    if (!$result['ok']) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $result['message']]);
        exit;
    }

    echo json_encode([
        'status'   => 'success',
        'message'  => 'Akun berhasil dibuat',
        'redirect' => '?page=home',
    ]);
    exit;
}

// ── Login ─────────────────────────────────────────────────────────────────────
if ($action === 'login') {
    $token = $_POST['_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid security token. Please refresh the page.']);
        exit;
    }

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Email and password are required.']);
        exit;
    }

    $result = authLogin($email, $password);
    if (!$result['ok']) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => $result['message']]);
        exit;
    }

    echo json_encode([
        'status'   => 'success',
        'message'  => 'Login berhasil',
        'redirect' => '?page=home',
    ]);
    exit;
}

// ── Logout ────────────────────────────────────────────────────────────────────
if ($action === 'logout') {
    authLogout();

    echo json_encode([
        'status'   => 'success',
        'redirect' => '?page=home',
    ]);
    exit;
}

// ── Forgot password ───────────────────────────────────────────────────────────
if ($action === 'forgot') {
    $token = $_POST['_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid security token. Please refresh the page.']);
        exit;
    }

    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address.']);
        exit;
    }

    // No real email sending — always return generic success message
    echo json_encode([
        'status'  => 'success',
        'message' => 'Jika email terdaftar, instruksi reset akan dikirim',
    ]);
    exit;
}

// Unknown action
http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
exit;

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

// Always output JSON
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? '';

// Initialize DB connection
try {
    $db = new PDO('sqlite:' . PROJECT_ROOT . '/database/wizdam.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// ── Register ──────────────────────────────────────────────────────────────────
if ($action === 'register') {
    // CSRF verification
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

    // Validation
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

    // Check email uniqueness
    try {
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Email address is already registered.']);
            exit;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'An error occurred. Please try again.']);
        exit;
    }

    // Hash password and insert user
    $password_hash = password_hash($password, PASSWORD_ARGON2ID);

    try {
        $stmt = $db->prepare('INSERT INTO users (email, password_hash, name, role) VALUES (?, ?, ?, \'user\')');
        $stmt->execute([$email, $password_hash, $name]);
        $user_id = $db->lastInsertId();
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to create account. Please try again.']);
        exit;
    }

    // Set session
    $_SESSION['user_id']    = $user_id;
    $_SESSION['user_name']  = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role']  = 'user';

    // Regenerate session ID for security
    session_regenerate_id(true);

    echo json_encode([
        'status'   => 'success',
        'message'  => 'Akun berhasil dibuat',
        'redirect' => '?page=home',
    ]);
    exit;
}

// ── Login ─────────────────────────────────────────────────────────────────────
if ($action === 'login') {
    // CSRF verification
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

    // Fetch user by email
    try {
        $stmt = $db->prepare('SELECT id, email, password_hash, name, role FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'An error occurred. Please try again.']);
        exit;
    }

    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Email atau password salah.']);
        exit;
    }

    // Set session variables
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];

    // Regenerate session ID for security
    session_regenerate_id(true);

    echo json_encode([
        'status'   => 'success',
        'message'  => 'Login berhasil',
        'redirect' => '?page=home',
    ]);
    exit;
}

// ── Logout ────────────────────────────────────────────────────────────────────
if ($action === 'logout') {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();

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

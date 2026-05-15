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

// Initialize DB connection
try {
    $db = new PDO('sqlite:' . PROJECT_ROOT . '/database/wizdam.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// ── Delight-im/Auth adapter ───────────────────────────────────────────────
// Activated only when the library is installed (composer install).
// P1: schema.sql already created all delight_* tables before this check runs,
//     so the adapter never encounters missing tables.
// P2: every Delight success path below also writes the four legacy $_SESSION
//     keys that the rest of the app reads directly.
$delight = null;
if (class_exists('Delight\Auth\Auth')) {
    try {
        // Use table prefix 'delight_' to avoid column conflicts with our
        // legacy users table (password_hash vs password, roles_mask, etc.)
        $delight = new \Delight\Auth\Auth(
            \Delight\Db\PdoDatabase::fromDsn(
                new \Delight\Db\PdoDsn('sqlite:' . PROJECT_ROOT . '/database/wizdam.db')
            ),
            null,       // IP address — auto-detected
            'delight_'  // table prefix
        );
    } catch (\Throwable $e) {
        // Library present but init failed; fall back to legacy auth silently
        $delight = null;
    }
}

/**
 * Sets the four legacy session keys that every page in the app reads.
 * Called after every successful Delight login or registration.
 */
function _setLegacySession(PDO $db, string $email, string $name, string $role, ?int $fallbackId): void
{
    // Look up the user in our own users table so we get the correct id/role
    $stmt = $db->prepare('SELECT id, name, role FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $_SESSION['user_id']    = $row ? (int)$row['id']   : $fallbackId;
    $_SESSION['user_name']  = $row ? $row['name']       : $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role']  = $row ? $row['role']       : $role;
}

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

    // ── Delight path ──────────────────────────────────────────────────────
    if ($delight !== null) {
        // Check our users table first so we can give a clear duplicate error
        // before Delight even tries (avoids a Delight exception leaking info)
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

        try {
            // Register in delight_users; auto-login immediately after
            $delight->register($email, $password, $name);
            $delight->login($email, $password);

            // Mirror into our legacy users table so the rest of the app can
            // query by id/role without touching delight_users at all
            $password_hash = password_hash($password, PASSWORD_ARGON2ID);
            $stmt = $db->prepare('INSERT OR IGNORE INTO users (email, password_hash, name, role) VALUES (?, ?, ?, \'user\')');
            $stmt->execute([$email, $password_hash, $name]);

            // P2: populate legacy session keys the app reads directly
            _setLegacySession($db, $email, $name, 'user', null);
            session_regenerate_id(true);

            echo json_encode(['status' => 'success', 'message' => 'Akun berhasil dibuat', 'redirect' => '?page=home']);
            exit;
        } catch (\Delight\Auth\InvalidEmailException $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address.']);
            exit;
        } catch (\Delight\Auth\InvalidPasswordException $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters.']);
            exit;
        } catch (\Delight\Auth\UserAlreadyExistsException $e) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Email address is already registered.']);
            exit;
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to create account. Please try again.']);
            exit;
        }
    }

    // ── Legacy path ───────────────────────────────────────────────────────
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

    $_SESSION['user_id']    = $user_id;
    $_SESSION['user_name']  = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role']  = 'user';
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

    // ── Delight path ──────────────────────────────────────────────────────
    if ($delight !== null) {
        try {
            $delight->login($email, $password);

            // P2: Delight manages its own session but the rest of the app
            // reads the four legacy keys below, so we must populate them.
            // Pull name/role from our users table (authoritative for role).
            _setLegacySession($db, $email, '', 'user', null);
            session_regenerate_id(true);

            echo json_encode(['status' => 'success', 'message' => 'Login berhasil', 'redirect' => '?page=home']);
            exit;
        } catch (\Delight\Auth\InvalidEmailException | \Delight\Auth\InvalidPasswordException | \Delight\Auth\EmailNotVerifiedException $e) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Email atau password salah.']);
            exit;
        } catch (\Delight\Auth\TooManyRequestsException $e) {
            http_response_code(429);
            echo json_encode(['status' => 'error', 'message' => 'Terlalu banyak percobaan login. Coba lagi nanti.']);
            exit;
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'An error occurred. Please try again.']);
            exit;
        }
    }

    // ── Legacy path ───────────────────────────────────────────────────────
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
        echo json_encode(['status' => 'error', 'message' => $result['message']]);
        exit;
    }

    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];
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
    // Let Delight invalidate its own session tokens first (remember-me cookies etc.)
    if ($delight !== null) {
        try { $delight->logOut(); } catch (\Throwable $e) { /* ignore */ }
    }

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

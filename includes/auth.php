<?php
/**
 * includes/auth.php — Auth wrapper
 * Uses delight-im/auth when available, falls back to session-based auth.
 */

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__));
}

$_auth_vendor = PROJECT_ROOT . '/vendor/autoload.php';
$_use_delight = false;

if (file_exists($_auth_vendor)) {
    require_once $_auth_vendor;
    if (class_exists('\Delight\Auth\Auth')) {
        $_use_delight = true;
    }
}
unset($_auth_vendor);

/**
 * Returns a shared \Delight\Auth\Auth instance (delight-im path).
 */
function _getDelightAuth(): \Delight\Auth\Auth {
    static $instance = null;
    if ($instance === null) {
        $pdo = function_exists('getDb') ? getDb() : new PDO('sqlite:' . PROJECT_ROOT . '/database/wizdam.db');
        $instance = new \Delight\Auth\Auth($pdo);
    }
    return $instance;
}

/**
 * Returns a shared PDO connection (fallback path).
 */
function _getFallbackDb(): PDO {
    if (function_exists('getDb')) {
        return getDb();
    }
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . PROJECT_ROOT . '/database/wizdam.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    return $pdo;
}

if ($GLOBALS['_use_delight'] ?? $_use_delight) {

    // ── delight-im implementations ────────────────────────────────────────────

    function authRegister(string $email, string $password, string $name): array {
        try {
            $auth = _getDelightAuth();
            $userId = $auth->register($email, $password, $name);
            return ['ok' => true];
        } catch (\Delight\Auth\InvalidEmailException $e) {
            return ['ok' => false, 'message' => 'Alamat email tidak valid.'];
        } catch (\Delight\Auth\InvalidPasswordException $e) {
            return ['ok' => false, 'message' => 'Password terlalu pendek (minimal 8 karakter).'];
        } catch (\Delight\Auth\UserAlreadyExistsException $e) {
            return ['ok' => false, 'message' => 'Email sudah terdaftar.'];
        } catch (\Delight\Auth\TooManyRequestsException $e) {
            return ['ok' => false, 'message' => 'Terlalu banyak percobaan. Silakan coba lagi nanti.'];
        } catch (\Exception $e) {
            return ['ok' => false, 'message' => 'Gagal membuat akun. Silakan coba lagi.'];
        }
    }

    function authLogin(string $email, string $password): array {
        try {
            $auth = _getDelightAuth();
            $auth->login($email, $password);
            return ['ok' => true];
        } catch (\Delight\Auth\InvalidEmailException $e) {
            return ['ok' => false, 'message' => 'Email atau password salah.'];
        } catch (\Delight\Auth\InvalidPasswordException $e) {
            return ['ok' => false, 'message' => 'Email atau password salah.'];
        } catch (\Delight\Auth\EmailNotVerifiedException $e) {
            return ['ok' => false, 'message' => 'Email belum diverifikasi.'];
        } catch (\Delight\Auth\UserNotExistsException $e) {
            return ['ok' => false, 'message' => 'Email atau password salah.'];
        } catch (\Delight\Auth\TooManyRequestsException $e) {
            return ['ok' => false, 'message' => 'Terlalu banyak percobaan. Silakan coba lagi nanti.'];
        } catch (\Exception $e) {
            return ['ok' => false, 'message' => 'Login gagal. Silakan coba lagi.'];
        }
    }

    function authLogout(): void {
        try {
            _getDelightAuth()->logOut();
        } catch (\Exception $e) {
            // ignore
        }
    }

    function authCurrentUser(): ?array {
        try {
            $auth = _getDelightAuth();
            if (!$auth->isLoggedIn()) {
                return null;
            }
            $roles = [];
            try {
                $roles = $auth->getRoles();
            } catch (\Exception $e) {}
            $role = !empty($roles) ? array_key_first($roles) : 'user';
            return [
                'id'    => $auth->getUserId(),
                'email' => $auth->getEmail(),
                'name'  => $auth->getUsername(),
                'role'  => $role,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    function authIsLoggedIn(): bool {
        try {
            return _getDelightAuth()->isLoggedIn();
        } catch (\Exception $e) {
            return false;
        }
    }

    function authHasRole(string $role): bool {
        try {
            $auth = _getDelightAuth();
            if (!$auth->isLoggedIn()) {
                return false;
            }
            $roles = $auth->getRoles();
            return in_array($role, array_values($roles), true) || array_key_exists($role, $roles);
        } catch (\Exception $e) {
            return false;
        }
    }

} else {

    // ── Session-based fallback implementations ────────────────────────────────

    function authRegister(string $email, string $password, string $name): array {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'Alamat email tidak valid.'];
        }
        if (strlen($password) < 8) {
            return ['ok' => false, 'message' => 'Password terlalu pendek (minimal 8 karakter).'];
        }
        if (strlen($name) < 2 || strlen($name) > 100) {
            return ['ok' => false, 'message' => 'Nama harus antara 2 dan 100 karakter.'];
        }
        try {
            $db = _getFallbackDb();
            $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                return ['ok' => false, 'message' => 'Email sudah terdaftar.'];
            }
            $hash = password_hash($password, PASSWORD_ARGON2ID);
            $stmt = $db->prepare('INSERT INTO users (email, password_hash, name, role) VALUES (?, ?, ?, \'user\')');
            $stmt->execute([$email, $hash, $name]);
            $userId = $db->lastInsertId();

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id']    = $userId;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name']  = $name;
            $_SESSION['user_role']  = 'user';
            session_regenerate_id(true);

            return ['ok' => true];
        } catch (\PDOException $e) {
            return ['ok' => false, 'message' => 'Gagal membuat akun. Silakan coba lagi.'];
        }
    }

    function authLogin(string $email, string $password): array {
        if (empty($email) || empty($password)) {
            return ['ok' => false, 'message' => 'Email dan password wajib diisi.'];
        }
        try {
            $db = _getFallbackDb();
            $stmt = $db->prepare('SELECT id, email, password_hash, name, role FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return ['ok' => false, 'message' => 'Terjadi kesalahan. Silakan coba lagi.'];
        }
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['ok' => false, 'message' => 'Email atau password salah.'];
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_role']  = $user['role'];
        session_regenerate_id(true);

        return ['ok' => true];
    }

    function authLogout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '',
                time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    function authCurrentUser(): ?array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        return [
            'id'    => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'] ?? '',
            'name'  => $_SESSION['user_name'] ?? '',
            'role'  => $_SESSION['user_role'] ?? 'user',
        ];
    }

    function authIsLoggedIn(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return !empty($_SESSION['user_id']);
    }

    function authHasRole(string $role): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
    }
}

<?php

declare(strict_types=1);

namespace Wizdam\Services;

use PDO;
use Exception;
use Wizdam\Utils\Security;
use Wizdam\Utils\Validator;

/**
 * Authentication Service
 * 
 * Wrapper untuk auth.php yang menyediakan interface OOP
 * untuk operasi autentikasi tanpa mengubah file API asli.
 *
 * @version 1.0.0
 * @author Wizdam Team
 * @license MIT
 */
class AuthService
{
    private PDO $db;
    private array $config;

    /**
     * Constructor
     */
    public function __construct(PDO $db, array $config = [])
    {
        $this->db = $db;
        $this->config = $config;
    }

    /**
     * Register new user
     *
     * @param string $name
     * @param string $email
     * @param string $password
     * @param string $passwordConfirm
     * @param string $csrfToken
     * @return array{status: string, message: string, user_id?: int}
     * @throws Exception
     */
    public function register(
        string $name,
        string $email,
        string $password,
        string $passwordConfirm,
        string $csrfToken
    ): array {
        // Verify CSRF token
        if (!Security::verifyCsrfToken($csrfToken)) {
            return [
                'status' => 'error',
                'message' => 'Invalid security token. Please refresh the page.'
            ];
        }

        // Validate input
        $name = trim($name);
        $email = trim($email);

        if (empty($name)) {
            return [
                'status' => 'error',
                'message' => 'Nama lengkap wajib diisi.'
            ];
        }

        if (!Validator::validateEmail($email)) {
            return [
                'status' => 'error',
                'message' => 'Format email tidak valid.'
            ];
        }

        if (strlen($password) < 8) {
            return [
                'status' => 'error',
                'message' => 'Password minimal 8 karakter.'
            ];
        }

        if ($password !== $passwordConfirm) {
            return [
                'status' => 'error',
                'message' => 'Konfirmasi password tidak cocok.'
            ];
        }

        // Check if email already exists
        $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return [
                'status' => 'error',
                'message' => 'Email sudah terdaftar.'
            ];
        }

        // Hash password and insert user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare(
            'INSERT INTO users (name, email, password_hash, created_at) VALUES (?, ?, ?, datetime("now"))'
        );
        $stmt->execute([$name, $email, $hashedPassword]);

        return [
            'status' => 'success',
            'message' => 'Registrasi berhasil. Silakan login.',
            'user_id' => (int) $this->db->lastInsertId()
        ];
    }

    /**
     * Login user
     *
     * @param string $email
     * @param string $password
     * @param string $csrfToken
     * @return array{status: string, message: string, user?: array, token?: string}
     * @throws Exception
     */
    public function login(string $email, string $password, string $csrfToken): array
    {
        // Verify CSRF token
        if (!Security::verifyCsrfToken($csrfToken)) {
            return [
                'status' => 'error',
                'message' => 'Invalid security token. Please refresh the page.'
            ];
        }

        $email = trim($email);

        if (!Validator::validateEmail($email)) {
            return [
                'status' => 'error',
                'message' => 'Format email tidak valid.'
            ];
        }

        // Find user by email
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return [
                'status' => 'error',
                'message' => 'Email atau password salah.'
            ];
        }

        // Generate session token
        $token = bin2hex(random_bytes(32));
        
        // Store token in database
        $stmt = $this->db->prepare(
            'INSERT INTO user_sessions (user_id, token, expires_at) VALUES (?, ?, datetime("now", "+7 days"))'
        );
        $stmt->execute([(int) $user['id'], $token]);

        return [
            'status' => 'success',
            'message' => 'Login berhasil.',
            'user' => [
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'email' => $user['email']
            ],
            'token' => $token
        ];
    }

    /**
     * Logout user
     *
     * @param string $token
     * @return array{status: string, message: string}
     * @throws Exception
     */
    public function logout(string $token): array
    {
        $stmt = $this->db->prepare('DELETE FROM user_sessions WHERE token = ?');
        $stmt->execute([$token]);

        return [
            'status' => 'success',
            'message' => 'Logout berhasil.'
        ];
    }

    /**
     * Get current user by token
     *
     * @param string $token
     * @return array|null
     * @throws Exception
     */
    public function getCurrentUser(string $token): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.* FROM users u 
             INNER JOIN user_sessions s ON u.id = s.user_id 
             WHERE s.token = ? AND s.expires_at > datetime("now")'
        );
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Request password reset
     *
     * @param string $email
     * @return array{status: string, message: string, reset_token?: string}
     * @throws Exception
     */
    public function requestPasswordReset(string $email): array
    {
        $email = trim($email);

        if (!Validator::validateEmail($email)) {
            return [
                'status' => 'error',
                'message' => 'Format email tidak valid.'
            ];
        }

        // Find user by email
        $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            // Don't reveal if email exists or not for security
            return [
                'status' => 'success',
                'message' => 'Jika email terdaftar, link reset password telah dikirim.'
            ];
        }

        // Generate reset token
        $resetToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store reset token
        $stmt = $this->db->prepare(
            'INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)'
        );
        $stmt->execute([(int) $user['id'], $resetToken, $expiresAt]);

        return [
            'status' => 'success',
            'message' => 'Jika email terdaftar, link reset password telah dikirim.',
            'reset_token' => $resetToken // In production, send via email instead
        ];
    }

    /**
     * Reset password with token
     *
     * @param string $token
     * @param string $newPassword
     * @param string $confirmPassword
     * @return array{status: string, message: string}
     * @throws Exception
     */
    public function resetPassword(string $token, string $newPassword, string $confirmPassword): array
    {
        if ($newPassword !== $confirmPassword) {
            return [
                'status' => 'error',
                'message' => 'Konfirmasi password tidak cocok.'
            ];
        }

        if (strlen($newPassword) < 8) {
            return [
                'status' => 'error',
                'message' => 'Password minimal 8 karakter.'
            ];
        }

        // Find valid reset token
        $stmt = $this->db->prepare(
            'SELECT user_id FROM password_resets 
             WHERE token = ? AND expires_at > datetime("now") AND used = 0'
        );
        $stmt->execute([$token]);
        $reset = $stmt->fetch();

        if (!$reset) {
            return [
                'status' => 'error',
                'message' => 'Token reset tidak valid atau sudah kadaluarsa.'
            ];
        }

        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare(
            'UPDATE users SET password_hash = ? WHERE id = ?'
        );
        $stmt->execute([$hashedPassword, (int) $reset['user_id']]);

        // Mark token as used
        $stmt = $this->db->prepare(
            'UPDATE password_resets SET used = 1 WHERE token = ?'
        );
        $stmt->execute([$token]);

        return [
            'status' => 'success',
            'message' => 'Password berhasil direset. Silakan login dengan password baru.'
        ];
    }
}

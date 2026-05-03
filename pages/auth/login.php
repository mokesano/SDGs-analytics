<?php
/**
 * pages/auth/login.php — Login Page
 * Halaman masuk akun pengguna
 */

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ?page=home');
    exit;
}

$page_title = 'Masuk ke Akun';
?>

<style>
/* ── Auth card ─────────────────────────────────────────────────────────────── */
.auth-wrapper {
    min-height: calc(100vh - 80px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 16px;
    background: linear-gradient(135deg, #0d1b2a 0%, #1a2e45 60%, #0d1b2a 100%);
}

.auth-card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    padding: 40px;
    width: 100%;
    max-width: 420px;
    animation: slideUp 0.4s ease;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(24px); }
    to   { opacity: 1; transform: translateY(0); }
}

.auth-logo {
    text-align: center;
    margin-bottom: 28px;
}

.auth-logo .logo-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #ff5627, #e0481d);
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 14px;
}

.auth-logo .logo-icon i {
    font-size: 26px;
    color: #fff;
}

.auth-logo h1 {
    font-size: 1.6rem;
    font-weight: 700;
    color: #0d1b2a;
    margin: 0 0 4px;
}

.auth-logo p {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0;
}

/* Form elements */
.form-group {
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}

.input-wrapper {
    position: relative;
}

.input-wrapper i.input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    font-size: 0.9rem;
    pointer-events: none;
}

.form-control {
    width: 100%;
    padding: 11px 14px 11px 40px;
    border: 1.5px solid #e5e7eb;
    border-radius: 8px;
    font-size: 0.9rem;
    color: #1f2937;
    background: #f9fafb;
    transition: border-color 0.2s, box-shadow 0.2s;
    outline: none;
    box-sizing: border-box;
}

.form-control:focus {
    border-color: #ff5627;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(255, 86, 39, 0.12);
}

.toggle-password {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #9ca3af;
    cursor: pointer;
    padding: 4px;
    font-size: 0.9rem;
    line-height: 1;
}

.toggle-password:hover {
    color: #ff5627;
}

/* Checkbox row */
.form-row-flex {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 22px;
    font-size: 0.85rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #4b5563;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    accent-color: #ff5627;
    width: 15px;
    height: 15px;
}

.link-forgot {
    color: #ff5627;
    text-decoration: none;
    font-weight: 500;
}

.link-forgot:hover {
    text-decoration: underline;
}

/* Submit button */
.btn-auth {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #ff5627, #e0481d);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.2s, transform 0.15s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-auth:hover:not(:disabled) {
    opacity: 0.9;
    transform: translateY(-1px);
}

.btn-auth:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

/* Error / success alerts */
.alert {
    padding: 11px 14px;
    border-radius: 8px;
    font-size: 0.85rem;
    margin-bottom: 18px;
    display: none;
}

.alert-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
}

.alert-success {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #16a34a;
}

.alert i {
    margin-right: 6px;
}

/* Divider */
.auth-divider {
    text-align: center;
    margin: 24px 0 0;
    font-size: 0.85rem;
    color: #6b7280;
}

.auth-divider a {
    color: #ff5627;
    font-weight: 600;
    text-decoration: none;
}

.auth-divider a:hover {
    text-decoration: underline;
}
</style>

<div class="auth-wrapper">
    <div class="auth-card">
        <!-- Logo / heading -->
        <div class="auth-logo">
            <div class="logo-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <h1>Masuk ke Akun</h1>
            <p>Platform SDGs Analysis &mdash; Wizdam AI</p>
        </div>

        <!-- Inline error alert -->
        <div class="alert alert-error" id="loginAlert">
            <i class="fas fa-exclamation-circle"></i>
            <span id="loginAlertMsg"></span>
        </div>

        <!-- Login form -->
        <form id="loginForm" novalidate>
            <input type="hidden" name="_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="login">

            <div class="form-group">
                <label for="email">Alamat Email</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email"
                           id="email"
                           name="email"
                           class="form-control"
                           placeholder="nama@contoh.com"
                           autocomplete="email"
                           required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password"
                           id="password"
                           name="password"
                           class="form-control"
                           placeholder="Masukkan password"
                           autocomplete="current-password"
                           required>
                    <button type="button" class="toggle-password" onclick="togglePwd('password', this)" aria-label="Tampilkan password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="form-row-flex">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember"> Ingat saya
                </label>
                <a href="?page=forgot-password" class="link-forgot">Lupa password?</a>
            </div>

            <button type="submit" class="btn-auth" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i>
                <span>Masuk</span>
            </button>
        </form>

        <p class="auth-divider">
            Belum punya akun? <a href="?page=register">Daftar sekarang</a>
        </p>
    </div>
</div>

<script>
// Toggle password visibility
function togglePwd(fieldId, btn) {
    const field = document.getElementById(fieldId);
    const icon  = btn.querySelector('i');
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        field.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// Login form submission
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const btn   = document.getElementById('loginBtn');
    const alert = document.getElementById('loginAlert');
    const msg   = document.getElementById('loginAlertMsg');

    // Reset alert
    alert.style.display = 'none';

    // Loading state
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Memproses...</span>';

    try {
        const formData = new FormData(this);

        const response = await fetch('../api/auth.php', {
            method: 'POST',
            body: formData,
        });

        const data = await response.json();

        if (data.status === 'success') {
            btn.innerHTML = '<i class="fas fa-check"></i><span>Berhasil!</span>';
            window.location.href = data.redirect || '?page=home';
        } else {
            alert.classList.remove('alert-success');
            alert.classList.add('alert-error');
            msg.textContent = data.message || 'Terjadi kesalahan. Silakan coba lagi.';
            alert.style.display = 'block';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sign-in-alt"></i><span>Masuk</span>';
        }
    } catch (err) {
        alert.classList.remove('alert-success');
        alert.classList.add('alert-error');
        msg.textContent = 'Gagal terhubung ke server. Periksa koneksi Anda.';
        alert.style.display = 'block';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-sign-in-alt"></i><span>Masuk</span>';
    }
});
</script>

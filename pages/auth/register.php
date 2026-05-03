<?php
/**
 * pages/auth/register.php — Register Page
 * Halaman pendaftaran akun pengguna baru
 */

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ?page=home');
    exit;
}

$page_title = 'Buat Akun';
?>

<style>
/* ── Auth card (same base as login.php) ──────────────────────────────────── */
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
    max-width: 440px;
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

.form-group {
    margin-bottom: 16px;
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

.toggle-password:hover { color: #ff5627; }

/* Password strength indicator */
.strength-bar-wrap {
    margin-top: 8px;
    height: 4px;
    background: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
}

.strength-bar {
    height: 100%;
    width: 0%;
    border-radius: 4px;
    transition: width 0.3s ease, background 0.3s ease;
}

.strength-label {
    font-size: 0.75rem;
    margin-top: 4px;
    color: #6b7280;
}

/* Terms checkbox */
.checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    color: #4b5563;
    font-size: 0.85rem;
    cursor: pointer;
    line-height: 1.4;
}

.checkbox-label input[type="checkbox"] {
    accent-color: #ff5627;
    width: 15px;
    height: 15px;
    flex-shrink: 0;
    margin-top: 2px;
}

.checkbox-label a {
    color: #ff5627;
    text-decoration: none;
}

.checkbox-label a:hover { text-decoration: underline; }

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
    margin-top: 20px;
}

.btn-auth:hover:not(:disabled) {
    opacity: 0.9;
    transform: translateY(-1px);
}

.btn-auth:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

/* Alert */
.alert {
    padding: 11px 14px;
    border-radius: 8px;
    font-size: 0.85rem;
    margin-bottom: 18px;
    display: none;
}

.alert-error   { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
.alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }
.alert i { margin-right: 6px; }

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

.auth-divider a:hover { text-decoration: underline; }
</style>

<div class="auth-wrapper">
    <div class="auth-card">
        <!-- Logo / heading -->
        <div class="auth-logo">
            <div class="logo-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h1>Buat Akun Baru</h1>
            <p>Bergabung dengan Platform SDGs Analysis &mdash; Wizdam AI</p>
        </div>

        <!-- Inline alerts -->
        <div class="alert alert-error" id="regAlert">
            <i class="fas fa-exclamation-circle"></i>
            <span id="regAlertMsg"></span>
        </div>

        <div class="alert alert-success" id="regSuccess">
            <i class="fas fa-check-circle"></i>
            <span id="regSuccessMsg"></span>
        </div>

        <!-- Register form -->
        <form id="registerForm" novalidate>
            <input type="hidden" name="_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="register">

            <div class="form-group">
                <label for="name">Nama Lengkap</label>
                <div class="input-wrapper">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text"
                           id="name"
                           name="name"
                           class="form-control"
                           placeholder="Nama lengkap Anda"
                           autocomplete="name"
                           required>
                </div>
            </div>

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
                           placeholder="Minimal 8 karakter"
                           autocomplete="new-password"
                           required
                           oninput="checkStrength(this.value)">
                    <button type="button" class="toggle-password" onclick="togglePwd('password', this)" aria-label="Tampilkan password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <!-- Strength indicator -->
                <div class="strength-bar-wrap">
                    <div class="strength-bar" id="strengthBar"></div>
                </div>
                <div class="strength-label" id="strengthLabel"></div>
            </div>

            <div class="form-group">
                <label for="password_confirm">Konfirmasi Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password"
                           id="password_confirm"
                           name="password_confirm"
                           class="form-control"
                           placeholder="Ulangi password"
                           autocomplete="new-password"
                           required>
                    <button type="button" class="toggle-password" onclick="togglePwd('password_confirm', this)" aria-label="Tampilkan konfirmasi password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="terms" required>
                    Saya menyetujui <a href="?page=privacy-policy" target="_blank">Kebijakan Privasi</a> dan syarat penggunaan platform ini.
                </label>
            </div>

            <button type="submit" class="btn-auth" id="registerBtn">
                <i class="fas fa-user-plus"></i>
                <span>Buat Akun</span>
            </button>
        </form>

        <p class="auth-divider">
            Sudah punya akun? <a href="?page=login">Masuk</a>
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

// Password strength checker
function checkStrength(password) {
    const bar   = document.getElementById('strengthBar');
    const label = document.getElementById('strengthLabel');

    if (!password) {
        bar.style.width = '0%';
        label.textContent = '';
        return;
    }

    let score = 0;
    if (password.length >= 8)  score++;
    if (password.length >= 12) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;

    const levels = [
        { pct: '20%', color: '#ef4444', text: 'Sangat lemah' },
        { pct: '40%', color: '#f97316', text: 'Lemah' },
        { pct: '60%', color: '#eab308', text: 'Sedang' },
        { pct: '80%', color: '#22c55e', text: 'Kuat' },
        { pct: '100%', color: '#16a34a', text: 'Sangat kuat' },
    ];

    const idx = Math.min(score, 4);
    bar.style.width      = levels[idx].pct;
    bar.style.background = levels[idx].color;
    label.textContent    = levels[idx].text;
    label.style.color    = levels[idx].color;
}

// Register form submission
document.getElementById('registerForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const btn        = document.getElementById('registerBtn');
    const alertEl    = document.getElementById('regAlert');
    const alertMsg   = document.getElementById('regAlertMsg');
    const successEl  = document.getElementById('regSuccess');
    const successMsg = document.getElementById('regSuccessMsg');

    alertEl.style.display   = 'none';
    successEl.style.display = 'none';

    // Client-side: check terms checkbox
    if (!this.querySelector('[name="terms"]').checked) {
        alertMsg.textContent = 'Anda harus menyetujui kebijakan privasi untuk mendaftar.';
        alertEl.style.display = 'block';
        return;
    }

    // Client-side: check password match
    const pwd     = document.getElementById('password').value;
    const confirm = document.getElementById('password_confirm').value;
    if (pwd !== confirm) {
        alertMsg.textContent = 'Password dan konfirmasi password tidak sama.';
        alertEl.style.display = 'block';
        return;
    }

    // Loading state
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Mendaftar...</span>';

    try {
        const formData = new FormData(this);

        const response = await fetch('../api/auth.php', {
            method: 'POST',
            body: formData,
        });

        const data = await response.json();

        if (data.status === 'success') {
            successMsg.textContent = data.message || 'Akun berhasil dibuat! Mengalihkan...';
            successEl.style.display = 'block';
            btn.innerHTML = '<i class="fas fa-check"></i><span>Berhasil!</span>';
            setTimeout(() => {
                window.location.href = data.redirect || '?page=home';
            }, 1200);
        } else {
            alertMsg.textContent = data.message || 'Terjadi kesalahan. Silakan coba lagi.';
            alertEl.style.display = 'block';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-user-plus"></i><span>Buat Akun</span>';
        }
    } catch (err) {
        alertMsg.textContent = 'Gagal terhubung ke server. Periksa koneksi Anda.';
        alertEl.style.display = 'block';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-user-plus"></i><span>Buat Akun</span>';
    }
});
</script>

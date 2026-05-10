<?php
/**
 * pages/auth/forgot.php — Forgot Password Page
 * Halaman permintaan reset password
 */

$page_title = 'Lupa Password';
?>

<style>
/* ── Auth card (same base as login/register) ─────────────────────────────── */
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
    margin: 0 0 8px;
}

.auth-logo p {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0;
    line-height: 1.5;
}

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

/* Alerts */
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

/* Back link */
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
                <i class="fas fa-key"></i>
            </div>
            <h1>Lupa Password?</h1>
            <p>Masukkan alamat email Anda dan kami akan mengirimkan instruksi untuk mereset password.</p>
        </div>

        <!-- Alerts -->
        <div class="alert alert-error" id="forgotAlert">
            <i class="fas fa-exclamation-circle"></i>
            <span id="forgotAlertMsg"></span>
        </div>

        <div class="alert alert-success" id="forgotSuccess">
            <i class="fas fa-check-circle"></i>
            <span id="forgotSuccessMsg"></span>
        </div>

        <!-- Forgot password form -->
        <form id="forgotForm" novalidate>
            <input type="hidden" name="_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="forgot">

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

            <button type="submit" class="btn-auth" id="forgotBtn">
                <i class="fas fa-paper-plane"></i>
                <span>Kirim Instruksi Reset</span>
            </button>
        </form>

        <p class="auth-divider">
            Ingat password? <a href="?page=login">Kembali ke halaman masuk</a>
        </p>
    </div>
</div>

<script>
document.getElementById('forgotForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const btn        = document.getElementById('forgotBtn');
    const alertEl    = document.getElementById('forgotAlert');
    const alertMsg   = document.getElementById('forgotAlertMsg');
    const successEl  = document.getElementById('forgotSuccess');
    const successMsg = document.getElementById('forgotSuccessMsg');

    alertEl.style.display   = 'none';
    successEl.style.display = 'none';

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
            successMsg.textContent = data.message || 'Jika email terdaftar, instruksi reset akan dikirim.';
            successEl.style.display = 'block';
            this.style.display = 'none'; // Hide form after success
            btn.innerHTML = '<i class="fas fa-paper-plane"></i><span>Kirim Instruksi Reset</span>';
        } else {
            alertMsg.textContent = data.message || 'Terjadi kesalahan. Silakan coba lagi.';
            alertEl.style.display = 'block';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i><span>Kirim Instruksi Reset</span>';
        }
    } catch (err) {
        alertMsg.textContent = 'Gagal terhubung ke server. Periksa koneksi Anda.';
        alertEl.style.display = 'block';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i><span>Kirim Instruksi Reset</span>';
    }
});
</script>

# Fase 2 - Core Features Setup

## ✅ Yang Sudah Diselesaikan

### 1. Library Auth System
- **Package**: `delight-im/auth` ^4.0 telah ditambahkan ke `composer.json`
- **Status**: Terinstall via Composer di `/vendor/delight-im/auth/`
- **Fungsi**: Registrasi, login, session management, password hashing, email verification

### 2. Struktur Direktori Baru
```
/workspace/
├── pages/auth/              # Halaman autentikasi
│   ├── login.php           # (akan dibuat)
│   ├── register.php        # (akan dibuat)
│   └── forgot-password.php # (akan dibuat)
├── content/markdown/       # Konten statis dalam Markdown
│   ├── login.md           # ✅ Selesai
│   ├── register.md        # ✅ Selesai
│   ├── forgot-password.md # ✅ Selesai
│   ├── dashboard.md       # ✅ Selesai
│   ├── archived.md        # ✅ Selesai
│   ├── leaderboard.md     # ✅ Selesai
│   ├── analytics.md       # ✅ Selesai
│   ├── submit-research.md # ✅ Selesai
│   ├── settings.md        # ✅ Selesai
│   ├── 404.md             # ✅ Selesai
│   ├── 500.md             # ✅ Selesai
│   ├── maintenance.md     # ✅ Selesai
│   └── welcome.md         # ✅ Selesai (halaman utama)
```

### 3. Konten Markdown yang Tersedia

| File | Halaman | Deskripsi |
|------|---------|-----------|
| `login.md` | `/login` | Form login dengan benefit registration |
| `register.md` | `/register` | Form registrasi dengan penjelasan fitur |
| `forgot-password.md` | `/forgot-password` | Instruksi reset password |
| `dashboard.md` | `/dashboard` | User dashboard overview |
| `archived.md` | `/archived` | Penjelasan halaman arsip |
| `leaderboard.md` | `/leaderboard` | Panduan leaderboard SDG |
| `analytics.md` | `/analytics` | Analytics dashboard description |
| `submit-research.md` | `/submit` | Panduan submit PDF/URL/DOI |
| `settings.md` | `/settings` | Profile settings overview |
| `welcome.md` | `/` | Landing page comprehensive |
| `404.md` | Error 404 | Custom error page |
| `500.md` | Error 500 | Custom error page |
| `maintenance.md` | Maintenance | Maintenance mode page |

## 📋 Langkah Selanjutnya

### A. Buat Helper Function untuk Markdown
File: `/includes/markdown-helper.php`
```php
<?php
function renderMarkdown($slug) {
    $file = PROJECT_ROOT . "/content/markdown/{$slug}.md";
    if (!file_exists($file)) return false;
    $content = file_get_contents($file);
    // Parse Markdown sederhana atau gunakan library Parsedown
    return $content; // Sementara return raw markdown
}
?>
```

### B. Update Router di index.php
Tambahkan routes baru:
```php
$allowed_pages = [
    // ... existing ...
    'login', 'register', 'forgot-password', 
    'dashboard', 'settings', 'submit',
    'leaderboard', 'analytics'
];
```

### C. Buat Page Handlers PHP
Contoh `/pages/auth/login.php`:
```php
<?php
$page_title = "Login - Wizdam AI";
require_once PROJECT_ROOT . '/includes/markdown-helper.php';
$content = renderMarkdown('login');
?>
<div class="auth-container">
    <!-- Form HTML -->
    <div class="markdown-content"><?= $content ?></div>
</div>
```

### D. Tambah CSS Khusus Auth
File: `/assets/css/auth.css`
```css
.auth-container {
    max-width: 500px;
    margin: 3rem auto;
    padding: 2rem;
    background: var(--color-bg-soft);
    border-radius: 8px;
}
```

### E. Implementasi Auth Logic dengan delight-im/auth
File: `/includes/auth-controller.php`
```php
<?php
use Delight\Auth\Auth;

require_once PROJECT_ROOT . '/vendor/autoload.php';

$db = new PDO('sqlite:' . PROJECT_ROOT . '/database/wizdam.sqlite');
$auth = new Auth($db);

// Register
$userId = $auth->register($_POST['email'], $_POST['password'], $_POST['name']);

// Login
$auth->login($_POST['email'], $_POST['password']);

// Check if logged in
$isLoggedIn = $auth->isLoggedIn();
?>
```

## 🎯 Checklist Fase 2

- [x] Install delight-im/auth library
- [x] Buat direktori pages/auth/
- [x] Buat direktori content/markdown/
- [x] Buat 13 file konten markdown
- [ ] Buat helper function markdown parser
- [ ] Update router public/index.php
- [ ] Buat halaman login.php dengan form
- [ ] Buat halaman register.php dengan form
- [ ] Buat halaman forgot-password.php
- [ ] Buat halaman dashboard.php (dynamic content)
- [ ] Buat halaman settings.php (dynamic content)
- [ ] Buat halaman submit.php (upload form)
- [ ] Buat halaman leaderboard.php (DB query)
- [ ] Buat halaman analytics.php (charts + DB)
- [ ] Tambah CSS auth.css
- [ ] Implementasi auth controller
- [ ] Test registrasi & login flow

## 📦 Dependencies

### Composer (PHP)
```json
{
    "require": {
        "delight-im/auth": "^4.0",
        "guzzlehttp/guzzle": "^7.8",
        "andreskrey/readability.php": "^2.1",
        "phpoffice/phpword": "^1.2"
    }
}
```

### NPM (JavaScript) - Sudah ada di package.json
- apexcharts - Visualisasi data
- chart.js - Charts alternatif  
- d3 - Network graphs
- react/react-dom - UI components (opsional)

## 🔐 Database Schema untuk Auth

Tabel `users` sudah ada di schema.sql:
```sql
CREATE TABLE IF NOT EXISTS users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    email         TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    name          TEXT,
    role          TEXT DEFAULT 'user',
    orcid         TEXT,
    verified_at   DATETIME,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

## 📝 Catatan Penting

1. **Pisahkan Concerns**:
   - Konten statis → Markdown di `/content/markdown/`
   - Layout/layout → Components di `/components/`
   - Logika dinamis → PHP di `/pages/` dan `/api/`
   - Styling → CSS modular di `/assets/css/`

2. **Gunakan delight-im/auth** untuk:
   - Password hashing (secure by default)
   - Session management
   - Email verification tokens
   - "Remember me" functionality
   - Rate limiting login attempts

3. **Markdown Parsing**:
   - Opsi 1: Library Parsedown (`composer require erusev/parsedown`)
   - Opsi 2: Regex sederhana untuk basic formatting
   - Opsi 3: Static HTML generation saat build

4. **Security**:
   - CSRF tokens pada semua form
   - HTTPS enforcement
   - Password strength validation
   - Email verification required

---
*Dokumen ini dibuat untuk tracking progress Fase 2 - Core Features*
*Last updated: {{DATE}}*

# FASE 2 — Core Features Progress

## Status: IN PROGRESS

### Yang Sudah Diselesaikan

#### 1. Sistem Lokalisasi (Locale System) ✅
- **File**: `/workspace/includes/locale.php`
- **Fitur**:
  - Class `LocaleHelper` dengan pattern Singleton
  - Deteksi locale otomatis dari session, cookie, atau browser
  - Parsing file `.po` (gettext style)
  - Support 2 locale: `en_US` dan `id_ID`
  - Helper functions: `__()`, `_t()` untuk translasi
  - Format tanggal dan angka sesuai locale
  
- **Files Locale**:
  - `/workspace/locale/en_US/LC_MESSAGES/messages.po` — English (US)
  - `/workspace/locale/id_ID/LC_MESSAGES/messages.po` — Bahasa Indonesia
  - 200+ translation keys untuk semua halaman

#### 2. Markdown Content Parser ✅
- **File**: `/workspace/includes/markdown_parser.php`
- **Fitur**:
  - Class `MarkdownParser` untuk parse markdown ke HTML
  - Support placeholder `{{key}}` yang diganti dengan translasi
  - Konversi: headers, bold, italic, links, lists, horizontal rules
  - Helper function: `render_markdown()`

#### 3. Layout Templates ✅
- **Auth Layout** (`/workspace/layouts/auth.php`):
  - Layout khusus untuk halaman login/register/forgot-password
  - Design clean dengan header gradient
  - Responsive untuk mobile
  - Integrated dengan locale system
  
- **Main Layout** (`/workspace/layouts/main.php`):
  - Layout untuk dashboard dan halaman authenticated
  - Sidebar navigation fixed
  - User profile display
  - Stats grid support

#### 4. Halaman Auth (Authentication) ✅
- **Login** (`/workspace/pages/auth/login.php`):
  - Form login dengan delight-im/auth
  - Konten dari markdown dengan localization
  - Remember me functionality
  - Error handling lengkap
  
- **Register** (`/workspace/pages/auth/register.php`):
  - Form registrasi lengkap (name, email, password, ORCID optional)
  - Password validation (min 8 chars)
  - Terms & conditions checkbox
  - Duplicate email handling
  
- **Forgot Password** (`/workspace/pages/auth/forgot-password.php`):
  - Request password reset
  - Security best practice (tidak reveal email exists)
  - Rate limiting support

#### 5. Content Markdown untuk Semua Halaman ✅
```
/workspace/content/markdown/
├── login_content.md          — Konten halaman login
├── register_content.md       — Konten halaman register
├── forgot_password_content.md — Konten forgot password
├── dashboard_content.md      — Konten dashboard
├── analytics.md              — Konten analytics page
├── archived.md               — Konten archive page
├── leaderboard.md            — Konten leaderboard
├── settings.md               — Konten settings
├── submit-research.md        — Konten submit research
└── ... (existing files)
```

### Struktur Direktori Baru

```
/workspace/
├── includes/
│   ├── locale.php           ← NEW: Localization system
│   └── markdown_parser.php  ← NEW: Markdown parser
├── layouts/                 ← NEW: Layout templates directory
│   ├── auth.php             ← Auth pages layout
│   └── main.php             ← Dashboard layout
├── pages/
│   └── auth/                ← NEW: Auth pages directory
│       ├── login.php
│       ├── register.php
│       └── forgot-password.php
├── content/
│   └── markdown/            ← Content files (static text)
│       ├── login_content.md
│       ├── register_content.md
│       └── ...
├── locale/                  ← NEW: Translation files
│   ├── en_US/
│   │   └── LC_MESSAGES/
│   │       └── messages.po
│   └── id_ID/
│       └── LC_MESSAGES/
│           └── messages.po
└── public/
    └── index.php            ← Updated: include locale.php
```

### Pemisahan KONTEN vs HALAMAN

| Tipe | Lokasi | Format | Fungsi |
|------|--------|--------|--------|
| **Konten Statis** | `/content/markdown/*.md` | Markdown + `{{keys}}` | Isi teks yang ditampilkan, mudah diedit, multi-bahasa |
| **Konten Dinamis** | PHP logic di pages/ | PHP + `__('keys')` | Data dari database, form handling, user-specific |
| **Layout/Halaman** | `/layouts/*.php` | PHP + HTML | Struktur HTML, CSS, navigasi, wrapper |
| **Translasi** | `/locale/*/LC_MESSAGES/messages.po` | gettext .po | Semua string UI dalam 2 bahasa |

### Library delight-im/auth

Sudah terinstall via composer.json:
```json
"require": {
    "delight-im/auth": "^4.0"
}
```

**Fitur yang tersedia**:
- Registrasi user dengan email verification
- Login/logout dengan session management
- Password hashing (Argon2id)
- Password reset flow
- Remember me tokens
- Rate limiting (too many requests)
- Email uniqueness validation

### Next Steps (Yang Masih Perlu Dikerjakan)

1. **Update Router** (`public/index.php`) untuk handle routes baru:
   - `/login` → `pages/auth/login.php`
   - `/register` → `pages/auth/register.php`
   - `/forgot-password` → `pages/auth/forgot-password.php`
   - `/dashboard` → `pages/dashboard.php` (create new)
   - `/logout` → logout handler

2. **Create Dashboard Page** (`pages/dashboard.php`):
   - Load stats from database
   - Display recent activity
   - Quick actions cards

3. **Create Logout Handler**:
   - Destroy session
   - Redirect to home

4. **Database Schema Update**:
   - Ensure `users` table matches delight-im/auth requirements
   - Add custom fields for ORCID, etc.

5. **Add Auth Middleware**:
   - Protect dashboard routes
   - Redirect unauthenticated users

6. **Complete Static Pages**:
   - Leaderboard page
   - Analytics page
   - Submit research page
   - Settings page

---

**Catatan Penting**: 
- Semua konten statis TIDAK hardcode di PHP, tapi di file `.md` terpisah
- Semua string UI menggunakan sistem locale (`__()`)
- Layout dipisahkan dari konten untuk reusability
- Dynamic content tetap menggunakan PHP + database queries

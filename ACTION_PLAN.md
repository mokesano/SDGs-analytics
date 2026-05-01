# Wizdam AI-sikola — Action Plan v1.0

> **SDGs Classification & Research Analytics Platform**  
> PT. Sangia Research Media and Publishing · wizdam.sangia.org  
> Dokumen ini dimasukkan ke root repositori sebagai `ACTION_PLAN.md`

---

## Daftar Isi

1. [Status Sistem Saat Ini](#1-status-sistem-saat-ini)
2. [Bug Kritis — Selesaikan Pertama](#2-bug-kritis--selesaikan-pertama)
3. [SQLite: Apakah Cukup?](#3-sqlite-apakah-cukup)
4. [Schema Database](#4-schema-database)
5. [Library Pendukung](#5-library-pendukung)
6. [Desain Visual — SDG Color System](#6-desain-visual--sdg-color-system)
7. [Roadmap 5 Phase](#7-roadmap-5-phase)
8. [Struktur Repositori](#8-struktur-repositori)
9. [GitHub Branch & Commit Strategy](#9-github-branch--commit-strategy)
10. [Prioritas Pull Request Pertama](#10-prioritas-pull-request-pertama)

---

## 1. Status Sistem Saat Ini

### ✅ Sudah Berjalan

| Komponen | Status |
|---|---|
| Sequential batch processing ORCID (anti-timeout) | ✅ Berjalan |
| PHP proxy POST anti-WAF + REQUEST_METHOD spoofing | ✅ Berjalan |
| Analisis SDG: keyword matching + cosine similarity + causal analysis | ✅ Berjalan |
| Cache gzip (TTL 7 hari) | ✅ Berjalan |
| Tampilan profil peneliti: SDG cards, charts, detail analisis | ✅ Berjalan |
| Struktur direktori modular: `public/` `api/` `components/` `pages/` | ✅ Berjalan |
| Error log diarahkan ke luar `public/` via `ini_set` di `index.php` | ✅ Berjalan |

### ❌ Belum Ada / Bermasalah

- Metadata artikel tidak lengkap (lihat Bug #1)
- Analisis DOI crash dengan SyntaxError (lihat Bug #2)
- Tidak ada database — data tidak persisten
- Tidak ada routing URL (`/orcid/{id}`, `/journal/{issn}`)
- Tidak ada sistem autentikasi pengguna
- Semua halaman navbar masih placeholder

---

## 2. Bug Kritis — Selesaikan Pertama

### 🔴 Bug #1 — Metadata Artikel Tidak Lengkap

**Gejala:** Karya peneliti hanya menampilkan judul dan DOI. Tidak ada penulis, jurnal, volume, issue, halaman, keywords, dan topik.

**Penyebab Root:**  
ORCID endpoint `/v3.0/{orcid}/works` hanya mengembalikan `work-summary` (judul + DOI + put-code). Data lengkap hanya tersedia via endpoint terpisah `/v3.0/{orcid}/work/{put-code}` yang belum diimplementasikan. Crossref API (`/works/{doi}`) juga bisa mengisi data bibliografi lengkap.

**Fix yang Diperlukan di `api/SDG_Classification_API.php`:**

```
handleOrcidInitRequest()
  → Saat mengumpulkan works_stubs, simpan juga put-code setiap karya

handleOrcidBatchRequest()
  → Untuk setiap karya dalam batch:
     1. Fetch /v3.0/{orcid}/work/{put-code} → ambil contributors, journal-title, 
        publication-date, volume, issue, start-page, end-page, external-ids
     2. Jika DOI tersedia, fetch Crossref /works/{doi} → ambil author[],
        container-title, issued, volume, issue, page, subject[], abstract
     3. Merge hasil keduanya: ORCID sebagai primary, Crossref sebagai enrichment
     4. Simpan semua field lengkap ke processed_works[]
```
**Branch:** `fix/orcid-work-metadata`

---

### 🔴 Bug #2 — SyntaxError JSON pada Analisis DOI

**Gejala:** `SyntaxError: JSON.parse: unexpected character at line 1 column 1` — hasil menampilkan HTML mentah.

**Penyebab Root:**  
Crossref API mengembalikan HTML error page dalam kondisi: DOI tidak valid, rate limit (HTTP 429), atau server error. Fungsi `fetchDoiData()` langsung `json_decode($response)` tanpa validasi `Content-Type` response terlebih dahulu.

**Fix yang Diperlukan di `api/SDG_Classification_API.php`:**

```
fetchDoiData($doi)
  → Setelah curl_exec():
     1. Cek $http_code: jika 429 → sleep(2) + retry max 3x dengan exponential backoff
     2. Cek $http_code: jika 404 → throw Exception('DOI tidak ditemukan', 404)
     3. Cek Content-Type header: jika bukan 'application/json' → throw Exception
     4. Cek karakter pertama response: jika bukan '{' atau '[' → throw Exception
     5. Baru json_decode()

fetchAbstractFromAlternativeSource($doi)
  → Tambahkan fallback ke OpenAlex: https://api.openalex.org/works/doi:{doi}
  → OpenAlex mengembalikan abstract_inverted_index yang perlu di-reconstruct
```
**Branch:** `fix/doi-json-parse-error`

---

## 3. SQLite: Apakah Cukup?

**Jawaban: Ya — lebih dari cukup untuk seluruh kebutuhan yang dideskripsikan.**

| Kebutuhan | SQLite | Catatan |
|---|---|---|
| Simpan profil ORCID + karya lengkap | ✅ | Tabel `researchers` + `works` |
| Klik badge SDG → tampilkan artikel | ✅ | JOIN query sederhana |
| Arsip ORCID & Journal profile | ✅ | Query `SELECT * WHERE last_fetched IS NOT NULL` |
| Daftar peneliti per SDG (SDG 1–17) | ✅ | `GROUP BY sdg_code ORDER BY count DESC` |
| Leaderboard Active Contributor / Diskutor | ✅ | `WHERE contributor_type = 'Active Contributor'` |
| Upload PDF → ekstrak teks → analisis | ✅ | Simpan `extracted_text` di tabel `submissions` |
| Registrasi & login pengguna | ✅ | Tabel `users` dengan hashed password |
| Full-text search abstrak | ✅ | SQLite FTS5 extension (built-in) |
| Concurrent writes banyak user | ⚠️ | WAL mode mengatasi ini; upgrade ke MySQL hanya jika > 500 concurrent writes/hari |

**Strategi migrasi ke MySQL di masa depan:** Semua query ditulis via PDO dengan placeholder `?` — ganti driver PDO dari `sqlite:` ke `mysql:` sudah cukup tanpa ubah query.

---

## 4. Schema Database

File: `database/schema.sql`

```sql
-- Peneliti
CREATE TABLE IF NOT EXISTS researchers (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    orcid         TEXT UNIQUE NOT NULL,
    name          TEXT,
    institutions  TEXT,          -- JSON array
    total_works   INTEGER DEFAULT 0,
    last_fetched  DATETIME,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Karya / Artikel
CREATE TABLE IF NOT EXISTS works (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    researcher_id INTEGER REFERENCES researchers(id) ON DELETE CASCADE,
    put_code      TEXT,
    title         TEXT,
    doi           TEXT,
    abstract      TEXT,
    authors       TEXT,          -- JSON array [{name, orcid}]
    journal       TEXT,
    volume        TEXT,
    issue         TEXT,
    pages         TEXT,
    year          INTEGER,
    keywords      TEXT,          -- JSON array
    work_type     TEXT,
    url           TEXT,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Relasi Karya ↔ SDG (tabel pivot utama)
CREATE TABLE IF NOT EXISTS work_sdgs (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    work_id          INTEGER REFERENCES works(id) ON DELETE CASCADE,
    sdg_code         TEXT NOT NULL,  -- 'SDG1' .. 'SDG17'
    confidence_score REAL,
    contributor_type TEXT,           -- 'Active Contributor' | 'Relevant Contributor' | 'Discutor' | 'Not Relevant'
    keyword_score    REAL,
    similarity_score REAL,
    causal_score     REAL,
    impact_score     REAL
);

-- Jurnal Scopus
CREATE TABLE IF NOT EXISTS journals (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    issn          TEXT UNIQUE,
    eissn         TEXT,
    title         TEXT,
    publisher     TEXT,
    scopus_id     TEXT,
    sjr           REAL,
    h_index       INTEGER,
    quartile      TEXT,            -- 'Q1' | 'Q2' | 'Q3' | 'Q4'
    open_access   INTEGER DEFAULT 0,
    country       TEXT,
    last_fetched  DATETIME,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Subject area jurnal (one-to-many)
CREATE TABLE IF NOT EXISTS journal_subjects (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    journal_id  INTEGER REFERENCES journals(id) ON DELETE CASCADE,
    subject     TEXT,
    asjc_code   TEXT
);

-- Pengguna
CREATE TABLE IF NOT EXISTS users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    email         TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    name          TEXT,
    role          TEXT DEFAULT 'user',  -- 'user' | 'admin'
    orcid         TEXT,
    verified_at   DATETIME,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Riwayat pencarian (untuk arsip & analytics)
CREATE TABLE IF NOT EXISTS search_history (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER REFERENCES users(id) ON DELETE SET NULL,
    input_type  TEXT,   -- 'orcid' | 'doi' | 'issn'
    input_value TEXT,
    searched_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Submission PDF / URL dari pengguna
CREATE TABLE IF NOT EXISTS submissions (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id        INTEGER REFERENCES users(id) ON DELETE CASCADE,
    type           TEXT,   -- 'pdf' | 'url' | 'doi'
    source         TEXT,   -- path file atau URL
    extracted_text TEXT,
    status         TEXT DEFAULT 'pending',  -- 'pending' | 'processing' | 'done' | 'error'
    work_id        INTEGER REFERENCES works(id) ON DELETE SET NULL,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Index untuk query yang sering dipakai
CREATE INDEX IF NOT EXISTS idx_work_sdgs_sdg_code     ON work_sdgs(sdg_code);
CREATE INDEX IF NOT EXISTS idx_work_sdgs_contributor  ON work_sdgs(contributor_type);
CREATE INDEX IF NOT EXISTS idx_works_researcher       ON works(researcher_id);
CREATE INDEX IF NOT EXISTS idx_works_year             ON works(year);
CREATE INDEX IF NOT EXISTS idx_researchers_orcid      ON researchers(orcid);

-- Full-text search untuk abstrak
CREATE VIRTUAL TABLE IF NOT EXISTS works_fts USING fts5(
    title, abstract, keywords,
    content='works', content_rowid='id'
);
```

### Contoh Query untuk Fitur Interaktif

```sql
-- Klik badge SDG-4 pada profil peneliti → tampilkan karya terkait
SELECT w.title, w.doi, w.journal, w.year, ws.confidence_score, ws.contributor_type
FROM works w
JOIN work_sdgs ws ON w.id = ws.work_id
WHERE ws.sdg_code = 'SDG4'
  AND w.researcher_id = ?
ORDER BY ws.confidence_score DESC;

-- Leaderboard Active Contributor SDG-13
SELECT r.name, r.orcid, COUNT(*) AS active_count
FROM researchers r
JOIN works w ON r.id = w.researcher_id
JOIN work_sdgs ws ON w.id = ws.work_id
WHERE ws.sdg_code = 'SDG13'
  AND ws.contributor_type = 'Active Contributor'
GROUP BY r.id
ORDER BY active_count DESC
LIMIT 20;

-- Daftar peneliti yang pernah dicari (halaman Archived)
SELECT r.*, COUNT(DISTINCT ws.sdg_code) AS sdg_count
FROM researchers r
LEFT JOIN works w ON r.id = w.researcher_id
LEFT JOIN work_sdgs ws ON w.id = ws.work_id
WHERE r.last_fetched IS NOT NULL
GROUP BY r.id
ORDER BY r.last_fetched DESC;
```

---

## 5. Library Pendukung

### Peningkatan Akurasi Analisis SDG

| Library / API | Fungsi | Integrasi |
|---|---|---|
| **Hugging Face Inference API** | Semantic similarity via SciBERT/SPECTER2 — jauh lebih akurat dari cosine similarity berbasis kata | HTTP request, tidak perlu install |
| **AURORA SDG Queries** | Dataset query resmi dari 13 universitas untuk mapping artikel → SDG, open source | Implementasi sebagai lapisan validasi ke-2 |
| **OpenAlex API** | Metadata artikel akademik gratis: penulis, konsep topik, citation count, abstrak | Fallback/enrichment setelah Crossref |
| **Crossref Metadata API** | Bibliografi lengkap dari DOI: penulis, jurnal, ISSN, keywords, funding | Sudah dipakai, perlu perbaikan error handling |

### Ekstraksi Teks

| Library | Fungsi | Cara Install |
|---|---|---|
| **pdftotext** (Poppler) | Ekstrak teks dari PDF upload pengguna | `apt install poppler-utils` → via `shell_exec()` |
| **Readability.php** | Ekstrak konten utama dari URL artikel (strip navigasi/iklan) | `composer require andreskrey/readability.php` |
| **Guzzle HTTP** | HTTP client dengan retry logic, timeout, cookie jar | `composer require guzzlehttp/guzzle` |

### Visualisasi Data

| Library | Digunakan Untuk | Catatan |
|---|---|---|
| **Chart.js** | Sudah ada — doughnut, bar chart | Tambah: radar chart, timeline |
| **ApexCharts** | Charts modern dengan animasi built-in — lebih cocok untuk tema cerah | Rekomendasi utama |
| **D3.js** | Network graph peneliti↔SDG, chord diagram relasi SDG | Halaman Analytics Dashboard |

---

## 6. Desain Visual — SDG Color System

### Filosofi

Tidak mengikuti warna referensi (merah-putih). Sebaliknya, menggunakan **sistem warna SDG resmi UN** sebagai aksen dinamis di atas background putih/abu-abu bersih. Hasilnya: tampilan **profesional, cerah, dan langsung terhubung secara visual dengan identitas SDG global**.

### Palet Utama

```css
/* Background & Neutral */
--color-bg:          #FFFFFF;   /* Halaman utama: putih bersih */
--color-bg-soft:     #F8FAFC;   /* Card background */
--color-bg-muted:    #F1F5F9;   /* Section alternating */
--color-text:        #1E293B;   /* Body text: slate-800 */
--color-text-muted:  #64748B;   /* Subtext: slate-500 */
--color-border:      #E2E8F0;   /* Border: slate-200 */

/* Primary brand (netral, tidak berkompetisi dengan SDG colors) */
--color-primary:     #1E40AF;   /* Biru tua untuk UI elemen */
--color-primary-light: #3B82F6; /* Hover states */
--color-accent:      #7C3AED;   /* Purple untuk highlight */

/* 17 Warna SDG Resmi UN — digunakan sebagai accent system */
--sdg1:  #E5243B;  /* No Poverty */
--sdg2:  #DDA63A;  /* Zero Hunger */
--sdg3:  #4C9F38;  /* Good Health */
--sdg4:  #C5192D;  /* Quality Education */
--sdg5:  #FF3A21;  /* Gender Equality */
--sdg6:  #26BDE2;  /* Clean Water */
--sdg7:  #FCC30B;  /* Clean Energy */
--sdg8:  #A21942;  /* Decent Work */
--sdg9:  #FD6925;  /* Industry & Innovation */
--sdg10: #DD1367;  /* Reduced Inequalities */
--sdg11: #FD9D24;  /* Sustainable Cities */
--sdg12: #BF8B2E;  /* Responsible Consumption */
--sdg13: #3F7E44;  /* Climate Action */
--sdg14: #0A97D9;  /* Life Below Water */
--sdg15: #56C02B;  /* Life on Land */
--sdg16: #00689D;  /* Peace & Justice */
--sdg17: #19486A;  /* Partnerships */
```

### Prinsip Desain

1. **Background putih bersih** — konten akademik mudah dibaca, tidak lelah di mata
2. **SDG badge berwarna akurat** — setiap badge menggunakan warna resmi SDG-nya, bukan warna generik
3. **Section header** — gradient tipis menggunakan warna SDG yang sedang ditampilkan (dinamis via CSS custom property)
4. **Card hover** — `box-shadow` dengan warna SDG terkait, bukan bayangan abu-abu generik
5. **Chart colors** — palette 17 warna SDG sebagai dataset colors
6. **Hero section** — background putih dengan geometric pattern halus (UN SDG wheel motif, opacity rendah)
7. **Typography** — Inter/Plus Jakarta Sans untuk heading, sistem font untuk body

### File CSS yang Perlu Dibuat

```
assets/css/
├── variables.css      ← Semua CSS custom properties di atas
├── base.css           ← Reset, typography, spacing system
├── components.css     ← Card, button, badge, form elements
├── sdg-colors.css     ← .sdg-badge-1 ... .sdg-badge-17, .sdg-glow-*
├── layout.css         ← Grid, navbar, footer, page sections
└── animations.css     ← Scroll reveal, shimmer, hover transitions
```

---

## 7. Roadmap 5 Phase

### Phase 1 — Bug Fix & Foundation (Minggu 1–2)

| Task | Estimasi | Branch | Deliverable |
|---|---|---|---|
| Fix metadata artikel (put-code + Crossref enrichment) | 3 hari | `fix/orcid-work-metadata` | Karya tampil dengan penulis, jurnal, keywords |
| Fix SyntaxError DOI + retry logic | 1 hari | `fix/doi-json-parse-error` | DOI analysis stabil, handle rate limit |
| Implementasi front controller router | 1 hari | `feat/router` | URL `/orcid/{id}` dan `/journal/{issn}` aktif |
| Setup SQLite + schema + PDO helper | 2 hari | `feat/sqlite-setup` | DB tersedia, tabel siap |
| Simpan hasil analisis ke DB setelah batch selesai | 1 hari | `feat/persist-results` | Data ORCID tersimpan otomatis |
| Pisahkan CSS/JS ke `assets/` (refactor) | 1 hari | `refactor/assets-structure` | File modular, bukan inline |

---

### Phase 2 — Core Features (Minggu 3–5)

| Task | Estimasi | Branch | Deliverable |
|---|---|---|---|
| Halaman `/orcid/{id}` dedicated | 2 hari | `feat/orcid-profile-page` | URL permanen per peneliti |
| Klik badge SDG → filter karya (via DB query) | 2 hari | `feat/sdg-badge-interaction` | Interaksi dinamis real-time |
| Halaman arsip `/archived` | 1 hari | `feat/archive-page` | Daftar semua peneliti yang pernah dicari |
| Sistem registrasi & login | 2 hari | `feat/auth` | User bisa simpan hasil, punya dashboard |
| Upload PDF → ekstrak teks → analisis SDG | 3 hari | `feat/pdf-upload` | Analisis lebih akurat dari full text |
| Fetch URL artikel → ekstrak konten → analisis | 2 hari | `feat/url-submission` | User submit link → analisis otomatis |
| Semua halaman navbar menjadi halaman nyata | 3 hari | `feat/pages-skeleton` | About, Teams, Help, dll tidak lagi placeholder |

---

### Phase 3 — Scopus Journal Integration (Minggu 6–8)

| Task | Estimasi | Branch | Deliverable |
|---|---|---|---|
| Scopus API wrapper (`api/scopus.php`) | 2 hari | `feat/scopus-api` | Fetch SJR, quartile, h-index, subjects |
| Halaman `/journal/{issn}` | 2 hari | `feat/journal-profile-page` | Profile jurnal dengan metadata Scopus lengkap |
| Halaman arsip jurnal | 1 hari | `feat/journal-archive` | Daftar semua jurnal yang pernah dicek |
| Mapping journal subjects → SDG | 2 hari | `feat/journal-sdg-mapping` | Jurnal dikategorikan ke SDG via subject area |
| API endpoint publik `/api/journal/{issn}` | 1 hari | `feat/public-api-journal` | JSON response untuk integrasi OJS/sistem lain |

---

### Phase 4 — Analytics & Leaderboard (Minggu 9–10)

| Task | Estimasi | Branch | Deliverable |
|---|---|---|---|
| Leaderboard peneliti: Active Contributor / Diskutor | 2 hari | `feat/leaderboard` | Ranking per contributor type |
| Daftar peneliti per SDG (SDG 1–17, bisa filter) | 2 hari | `feat/sdg-researcher-list` | Browse peneliti berdasarkan SDG |
| Analytics dashboard (chart tren, distribusi, heatmap) | 3 hari | `feat/analytics-dashboard` | Visualisasi agregat seluruh data |
| API publik `/api/researcher/{orcid}` | 1 hari | `feat/public-api-researcher` | Embed ke sistem eksternal |

---

### Phase 5 — Visual & Polish (Minggu 11–12)

| Task | Estimasi | Branch | Deliverable |
|---|---|---|---|
| SDG Color System — semua CSS variables | 1 hari | `style/sdg-color-system` | Palet konsisten di seluruh halaman |
| Redesign komponen: card, badge, button, form | 3 hari | `style/component-redesign` | Visual bersih, profesional, SDG-branded |
| Scroll reveal animations | 0.5 hari | `style/animations` | Elemen muncul smooth saat scroll |
| Halaman leaderboard & daftar SDG — visual khusus | 1 hari | `style/leaderboard-design` | Presentasi data yang menarik |
| Mobile responsiveness audit seluruh halaman | 1 hari | `style/mobile-audit` | Semua fitur baru responsive |
| SEO: meta tags, OG tags per halaman | 0.5 hari | `style/seo` | Setiap `/orcid/` dan `/journal/` punya meta unik |
| Performance: lazy load, cache headers, minify | 2 hari | `chore/performance` | PageSpeed > 85 |

---

## 8. Struktur Repositori

```
wizdam-ai-sikola/
│
├── public/                          # Document root (wizdam.sangia.org)
│   ├── index.php                    # Front controller + router
│   ├── .htaccess                    # RewriteRule semua → index.php
│   ├── favicon.ico
│   └── robots.txt
│
├── includes/                        # Core framework ringan
│   ├── config.php                   # DB path, API keys, base URL, konstanta
│   ├── bootstrap.php                # Inisialisasi DB, session, autoload, routing
│   ├── router.php                   # URL parser → delegasi ke handler
│   └── functions.php                # Helper: escape(), redirect(), flash(), auth()
│
├── api/                             # API handlers (semua via POST proxy)
│   ├── sdgs.php                     # SDG Classification API (sequential batch)
│   ├── scopus.php                   # Scopus Journal API
│   ├── orcid.php                    # ORCID API helper (put-code, work detail)
│   └── pdf.php                      # PDF text extraction endpoint
│
├── pages/                           # Page content handlers
│   ├── home.php                     # Halaman utama — search interface
│   ├── orcid-profile.php            # /orcid/{id} — profil peneliti
│   ├── journal-profile.php          # /journal/{issn} — profil jurnal
│   ├── archived.php                 # /archived — arsip semua pencarian
│   ├── leaderboard.php              # /leaderboard — ranking peneliti per SDG
│   ├── dashboard.php                # /dashboard — analytics (user login)
│   ├── auth/
│   │   ├── login.php
│   │   ├── register.php
│   │   └── forgot.php
│   ├── about.php
│   ├── apps.php
│   ├── teams.php
│   ├── help.php
│   ├── contact.php
│   ├── documentation.php
│   ├── api-reference.php
│   ├── blog.php
│   ├── careers.php
│   ├── partners.php
│   └── privacy-policy.php
│
├── components/                      # Reusable PHP partials
│   ├── navbar.php                   # Navigasi — include di semua halaman
│   ├── footer.php                   # Footer
│   ├── sdg-badge.php                # SDG badge component (warna otomatis)
│   ├── work-card.php                # Article card component
│   ├── researcher-card.php          # Researcher card untuk leaderboard
│   ├── journal-card.php             # Journal card component
│   └── chatbot.php                  # Chatbot modal
│
├── assets/
│   ├── css/
│   │   ├── variables.css            # CSS custom properties & SDG color system
│   │   ├── base.css                 # Reset, typography, spacing
│   │   ├── components.css           # Card, button, badge, form
│   │   ├── sdg-colors.css           # .sdg-badge-1 ... .sdg-badge-17
│   │   ├── layout.css               # Grid, navbar, footer, sections
│   │   └── animations.css           # Scroll reveal, hover, shimmer
│   ├── js/
│   │   ├── script.js                # Form, input validation, ORCID AJAX
│   │   ├── charts.js                # ApexCharts/Chart.js SDG color config
│   │   ├── scroll-reveal.js         # Intersection Observer animations
│   │   └── dashboard.js             # Analytics dashboard interactions
│   └── images/
│       ├── sdg-icons/               # 17 SVG icons SDG resmi UN
│       └── ui/                      # Logo, favicon sources
│
├── database/
│   ├── schema.sql                   # DDL lengkap (diversi-kontrol)
│   ├── migrations/                  # Migration files bertahap
│   │   ├── 001_initial.sql
│   │   └── 002_add_submissions.sql
│   └── wizdam.db                    # SQLite DB — masuk .gitignore
│
├── storage/                         # Runtime files — masuk .gitignore
│   ├── cache/                       # API cache .gz files
│   ├── uploads/                     # PDF uploads dari user
│   └── logs/
│       └── error_log
│
├── .gitignore
├── README.md
├── ACTION_PLAN.md                   # File ini
└── CHANGELOG.md
```

---

## 9. GitHub Branch & Commit Strategy

### Branch Model

```
main          ← Production (auto-deploy ke wizdam.sangia.org)
  └── develop ← Integration / staging
        ├── fix/orcid-work-metadata
        ├── fix/doi-json-parse-error
        ├── feat/router
        ├── feat/sqlite-setup
        ├── feat/orcid-profile-page
        ├── feat/sdg-badge-interaction
        ├── feat/archive-page
        ├── feat/auth
        ├── feat/pdf-upload
        ├── feat/scopus-api
        ├── feat/journal-profile-page
        ├── feat/leaderboard
        ├── feat/analytics-dashboard
        ├── style/sdg-color-system
        ├── style/component-redesign
        └── chore/performance
```

### Commit Convention

```
fix:      perbaikan bug
          fix: resolve JSON parse error on Crossref response
          fix: handle ORCID API rate limit with exponential backoff

feat:     fitur baru
          feat: add ORCID put-code metadata fetch for full work details
          feat: implement SQLite persistent storage for researcher profiles

refactor: restrukturisasi tanpa perubahan perilaku
          refactor: extract CSS to assets/css/ from inline styles

style:    perubahan visual/CSS murni
          style: implement SDG color system across badge components

docs:     dokumentasi
          docs: update ACTION_PLAN phase 2 progress

chore:    dependency, config, tooling
          chore: add .gitignore for storage/ and database/wizdam.db
```

---

## 10. Prioritas Pull Request Pertama

Urutan berdasarkan dampak dan dependensi:

### PR #1 — `fix/doi-json-parse-error` ⚡ (1 hari)

**Kenapa duluan:** Bug paling mudah, paling mengganggu, tidak ada dependensi lain.

Perubahan di `api/sdgs.php` → `fetchDoiData()`:
- Validasi `Content-Type` header sebelum `json_decode()`
- Handle HTTP 429 dengan retry + backoff
- Handle HTTP 404 dengan pesan error jelas
- Tambah OpenAlex sebagai fallback `fetchAbstractFromAlternativeSource()`

---

### PR #2 — `fix/orcid-work-metadata` 🔬 (3 hari)

**Kenapa kedua:** Dampak terbesar pada kualitas analisis. Metadata lengkap = analisis SDG lebih akurat.

Perubahan di `api/sdgs.php`:
- `handleOrcidInitRequest()` → simpan `put_code` di `works_stubs`
- `handleOrcidBatchRequest()` → fetch `/work/{put-code}` per karya dalam batch
- Merge data ORCID + Crossref → `processed_works[]` menjadi kaya metadata

---

### PR #3 — `feat/router` + `feat/sqlite-setup` 🏗️ (3 hari)

**Kenapa ketiga:** Fondasi semua fitur berikutnya. Semua PR Phase 2 bergantung pada ini.

File baru:
- `includes/router.php` — URL parsing dan delegasi
- `includes/config.php` — konstanta global
- `includes/bootstrap.php` — inisialisasi PDO SQLite
- `database/schema.sql` — DDL lengkap
- `public/index.php` — front controller final (ganti versi lama)

---

### PR #4 — `feat/persist-results` 💾 (1 hari)

Tambahkan penyimpanan otomatis ke SQLite setelah `handleOrcidSummaryRequest()` selesai. Data peneliti + karya + work_sdgs tersimpan untuk semua request berikutnya.

---

> **Catatan:** PR #1 dan #2 bisa dikerjakan paralel di branch terpisah. PR #3 dan #4 harus menunggu PR #2 merge karena mengubah struktur data yang sama.

---

*Dokumen ini diperbarui seiring kemajuan pengembangan. Setiap phase yang selesai ditandai ✅ dan direkam di `CHANGELOG.md`.*
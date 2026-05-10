# Changelog

All notable changes to the Wizdam AI-sikola project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Phase 1 — Bug Fix & Foundation ✅

**Completed:** December 2024

#### Fixed
- **Bug #1**: Metadata artikel tidak lengkap - Implemented put-code fetching from ORCID API and Crossref enrichment for complete bibliographic data (authors, journal, volume, issue, pages, keywords)
  - Modified `handleOrcidInitRequest()` to store put-code in works_stubs
  - Enhanced `handleOrcidBatchRequest()` to fetch full work details via `/work/{put-code}` endpoint
  - Added Crossref enrichment for contributors, journal-title, volume, issue, pages, and keywords
  
- **Bug #2**: SyntaxError JSON.parse pada analisis DOI - Added comprehensive validation in `fetchDoiData()`:
  - Content-Type header validation (must be application/json)
  - HTTP 429 retry with exponential backoff (max 3 attempts)
  - HTTP 404 handling with clear error message
  - First-character validation before json_decode()
  - OpenAlex fallback in `fetchAbstractFromAlternativeSource()`

- **Bug #3**: SQLite ON CONFLICT clause does not match UNIQUE constraint - Added UNIQUE constraints to `works.doi` and `works.put_code` columns in schema.sql to support upsert operations
  
- **Bug #4**: lastInsertId() returns stale ID after ON CONFLICT DO UPDATE - Modified `saveResearcher()` and `saveWork()` to explicitly SELECT the ID after upsert instead of relying on lastInsertId()

- **Bug #5**: showError() replaces #ajaxProgressSection innerHTML breaking subsequent progress updates - Refactored showError() to preserve DOM structure by updating element content instead of replacing innerHTML, ensuring showProgress()/setBar() continue to work after errors

#### Added
- **Front Controller Router** (`public/.htaccess`, `public/index.php`):
  - Clean URL support for `/orcid/{id}` → `index.php?page=orcid-profile&orcid={id}`
  - Clean URL support for `/journal/{issn}` → `index.php?page=journal-profile&issn={issn}`
  - RewriteBase configuration for proper routing

- **SQLite Database** (`includes/database.php`, `database/schema.sql`):
  - PDO database helper class with singleton pattern
  - WAL mode enabled for better concurrent access
  - Tables: researchers, works, work_sdgs, journals, journal_subjects, users, search_history, submissions
  - Helper methods: saveResearcher(), getResearcherByOrcid(), saveWork(), saveWorkSdg(), getWorksBySdg(), getArchivedResearchers()

- **Profile Pages**:
  - `pages/orcid-profile.php` - Researcher profile with SDG distribution and works list
  - `pages/journal-profile.php` - Journal profile with Scopus metrics and quartile badges

- **Configuration Updates** (`includes/config.php`):
  - SQLite database path constants (DB_PATH, DB_SCHEMA_FILE)
  - Updated $DB_CONFIG for SQLite driver

- **Data Persistence** (`api/SDG_Classification_API.php`):
  - New function `persistOrcidResultsToDatabase()` to save analysis results after batch completion
  - Automatic database storage when `action=summary` is called
  - Saves researchers, works, and work_sdgs relationships in a single transaction
  - Error logging for failed persistence operations

#### Changed
- Refactored CSS into modular files: variables.css, base.css, components.css, sdg-colors.css, layout.css, animations.css (already existed)
- Refactored JavaScript into modular files: script.js, charts.js, scroll-reveal.js (already existed)

#### Task Completion Status

| Task | Status | Deliverable |
|------|--------|-------------|
| Fix metadata artikel (put-code + Crossref enrichment) | ✅ Done | Karya tampil dengan penulis, jurnal, keywords |
| Fix SyntaxError DOI + retry logic | ✅ Done | DOI analysis stabil, handle rate limit |
| Implementasi front controller router | ✅ Done | URL `/orcid/{id}` dan `/journal/{issn}` aktif |
| Setup SQLite + schema + PDO helper | ✅ Done | DB tersedia, tabel siap |
| Simpan hasil analisis ke DB setelah batch selesai | ✅ Done | Data ORCID tersimpan otomatis via `persistOrcidResultsToDatabase()` |
| Pisahkan CSS/JS ke `assets/` (refactor) | ✅ Done | File modular di assets/css/ dan assets/js/ |

---

## [5.2.0] - 2024

### Added
- Sequential batch processing for ORCID profiles (anti-timeout)
- PHP proxy POST with anti-WAF and REQUEST_METHOD spoofing
- SDG analysis with keyword matching + cosine similarity + causal analysis
- Gzip cache with 7-day TTL
- Researcher profile view with SDG cards, charts, and detailed analysis
- Modular directory structure: public/, api/, components/, pages/
- Error logging redirected outside public/ via ini_set

### Fixed
- Timeout issues on large ORCID profiles by implementing batch processing
- WAF blocking by using POST proxy with method spoofing

---

*For more details, see ACTION_PLAN.md*
# Changelog — Wizdam AI SDGs Classification Analysis Platform

Format mengikuti [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).  
Semua perubahan signifikan dicatat di sini.

---

## [Unreleased] — Phase 3 (Aktif)

### ✅ Ditambahkan — Scopus Journal Integration
- **feat/journal-sdg-mapping**: `includes/sdg_subject_mapping.php` — konstanta `SUBJECT_SDG_MAP` + fungsi `mapSubjectsToSdgs()` memetakan subject area Scopus ASJC ke 17 kode SDG UN
- **feat/scopus-journal-api**: `api/scopus.php` — proxy handler Scopus ISSN lookup dengan validasi ISSN, gzip cache 7 hari (`cache/journal_{ISSN}.json.gz`), persistence ke SQLite (`journals` + `journal_subjects`), mapping subject→SDG
- **feat/journal-profile**: `pages/journal-profile.php` — halaman profil jurnal Scopus lengkap: metrik CiteScore/SJR/SNIP/Quartile, SDG chips, subject area tags, 3 state (no ISSN / not found / full profile)
- **feat/journal-archive**: `pages/journal-archive.php` — arsip jurnal dengan sticky filter bar, quartile pill filter, real-time search, card grid, pagination, ISSN lookup inline
- **feat/issn-routing**: `public/index.php` — case `journal` di POST proxy switch → `api/scopus.php`; halaman `journal-profile` + `journal-archive` ditambahkan ke `$allowed_pages` + routing metadata
- **feat/issn-home**: `pages/home.php` — deteksi ISSN di `detectType()` (format `XXXX-XXXX`), redirect otomatis ke `?page=journal-profile&issn=`, update placeholder + hint text
- **feat/journal-archive-nav**: `components/navigation.php` — Journal Archive ditambahkan ke Tools dropdown
- **fix/archived-page**: `pages/archived.php` — diganti dari data statis menjadi query SQLite real dengan search, pagination, link ke orcid-profile

---

## [Unreleased] — Phase 2 (Aktif)

### ✅ Ditambahkan
- **feat/auth**: Sistem autentikasi lengkap — login, register, forgot-password (`pages/auth/`, `api/auth.php`) dengan PHP session backend, CSRF protection, Argon2ID password hashing
- **feat/orcid-profile-page**: Halaman profil peneliti dedicated (`?page=orcid-profile&orcid=`) dengan data dari SQLite DB — charts, SDG distribution, works list
- **feat/leaderboard**: Halaman leaderboard peneliti (`?page=leaderboard`) filterable per SDG dan tipe kontributor, data real dari SQLite
- **feat/archive-page**: Halaman arsip pencarian (`?page=archived`) dengan data real SQLite dan pagination
- **feat/analytics-dashboard**: Dashboard analytics real dari SQLite — SDG distribution bar, contributor doughnut, yearly trend line (Chart.js)
- **feat/pdf-upload**: Endpoint upload PDF (`api/pdf.php`) — ekstraksi teks via pdftotext atau fallback PDF parser
- **feat/components**: Komponen PHP yang dapat digunakan ulang: `sdg-badge.php`, `work-card.php`, `researcher-card.php`, `journal-card.php`
- **feat/pages-skeleton**: Semua halaman navbar diisi konten nyata: about, apps, teams, help, contact, documentation, api-reference, blog, careers, partners, privacy-policy, tutorials, community-forum, press-kit, research-papers
- **assets/js/dashboard.js**: Chart.js helpers untuk SDG bar chart, contributor doughnut, yearly trend line, animated counters
- **desain-UI-UX/**: 13 gambar mockup UI/UX halaman lengkap sebagai referensi implementasi

---

## [5.3.0] — 2025-05-03 — Phase 1 ✅ Selesai

### ✅ Diperbaiki — Bug Kritis
- **fix/doi-json-parse-error**: Validasi `detectType()` + `validateDoi()` JavaScript yang ketat — URL non-DOI tidak lagi dikirim ke API dan menyebabkan `SyntaxError: JSON.parse`
- **fix/orcid-regex**: ORCID validasi PHP + JavaScript diperluas dari `^0000-` ke `^\d{4}-`, mendukung semua format valid termasuk `0009-`, `0001-`, dll. + strip URL ORCID otomatis
- **fix/orcid-metadata**: Metadata karya lengkap via ORCID `/work/{put-code}` endpoint + CrossRef enrichment (judul, penulis, jurnal, volume, issue, halaman, keywords, tahun)
- **fix/abstract-truncated**: Abstract tidak lagi terpotong — sistem expand/collapse dengan `toggleAbstract()`, preview 320 karakter + "Show more"

### ✅ Ditambahkan — Foundation
- **feat/sqlite-setup**: `includes/bootstrap.php` — inisialisasi PDO SQLite dengan WAL mode, foreign keys, otomatis buat schema dari `database/schema.sql`
- **feat/persist-results**: Auto-save hasil analisis ORCID ke SQLite setelah batch selesai — `persistOrcidResultsToDb()` menyimpan researchers + works + work_sdgs
- **feat/orcid-profile-api**: Integrasi `ORCID_Profile_API.php` — bio, email, keywords, affiliations dari endpoint `/person` + `/employments`
- **feat/abstract-multi-source**: `fetchAbstractMultiSource()` — CrossRef → OpenAlex (inverted-index reconstruction) → Semantic Scholar fallback chain

### ✅ Ditambahkan — Visual
- **style/hero-redesign**: Hero section dark navy dengan canvas particle network animation, WIZDAM wordmark, floating SDG tile mockup (sesuai cover image)
- **style/sdg-color-system**: 6 file CSS modular (`variables.css`, `base.css`, `components.css`, `sdg-colors.css`, `layout.css`, `animations.css`) dengan 17 warna SDG resmi UN
- **style/og-images**: OG + Twitter meta image diperbarui ke cover images Wizdam

---

## [5.2.0] — 2025-04-28

### Ditambahkan
- Struktur halaman modular dengan front controller (`public/index.php`)
- Sequential batch processing untuk analisis ORCID (anti-timeout)
- Cache gzip (TTL 7 hari)
- Design system CSS files awal
- PHP proxy POST anti-WAF + REQUEST_METHOD spoofing

---

## [5.1.8] — 2025-04-15

### Ditambahkan
- Analisis SDG: keyword matching + cosine similarity + causal analysis
- Tampilan profil peneliti: SDG cards, charts, detail analisis
- Struktur direktori modular: `public/` `api/` `components/` `pages/`

---

*Dikelola oleh Wizdam AI Team — PT. Sangia Research Media and Publishing*

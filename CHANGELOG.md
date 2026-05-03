# Changelog — Wizdam AI SDGs Classification Analysis Platform

Format mengikuti [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).  
Semua perubahan signifikan dicatat di sini.

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

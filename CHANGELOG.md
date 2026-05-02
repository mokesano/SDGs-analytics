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

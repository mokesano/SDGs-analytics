-- ============================================================
-- schema.sql — Wizdam AI-sikola Database Schema
-- SQLite (compatible with PDO + future MySQL migration)
-- ============================================================

-- ── Peneliti ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS researchers (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    orcid         TEXT UNIQUE NOT NULL,
    name          TEXT,
    institutions  TEXT,          -- JSON array ["Universitas A", "Universitas B"]
    total_works   INTEGER DEFAULT 0,
    last_fetched  DATETIME,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ── Karya / Artikel ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS works (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    researcher_id INTEGER REFERENCES researchers(id) ON DELETE CASCADE,
    put_code      TEXT UNIQUE,  -- UNIQUE constraint untuk ON CONFLICT (fallback jika DOI kosong)
    title         TEXT,
    doi           TEXT UNIQUE,  -- UNIQUE constraint untuk ON CONFLICT
    abstract      TEXT,
    authors       TEXT,          -- JSON array [{"name": "...", "orcid": "..."}]
    journal       TEXT,
    volume        TEXT,
    issue         TEXT,
    pages         TEXT,
    year          INTEGER,
    keywords      TEXT,          -- JSON array ["keyword1", "keyword2"]
    work_type     TEXT,
    url           TEXT,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ── Relasi Karya ↔ SDG (tabel pivot utama) ────────────────────────────────
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

-- ── Jurnal Scopus ─────────────────────────────────────────────────────────
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

-- ── Subject area jurnal (one-to-many) ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS journal_subjects (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    journal_id  INTEGER REFERENCES journals(id) ON DELETE CASCADE,
    subject     TEXT,
    asjc_code   TEXT
);

-- ── Pengguna ──────────────────────────────────────────────────────────────
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

-- ── Riwayat pencarian (untuk arsip & analytics) ───────────────────────────
CREATE TABLE IF NOT EXISTS search_history (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER REFERENCES users(id) ON DELETE SET NULL,
    input_type  TEXT,   -- 'orcid' | 'doi' | 'issn'
    input_value TEXT,
    searched_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ── Submission PDF / URL dari pengguna ────────────────────────────────────
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

-- ── API Keys ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS api_keys (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER REFERENCES users(id) ON DELETE CASCADE,
    key_hash   TEXT UNIQUE NOT NULL,
    name       TEXT,            -- e.g. "My OJS Plugin"
    last_used  DATETIME,
    expires_at DATETIME,
    is_active  INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- Index untuk query yang sering dipakai
-- ============================================================
CREATE INDEX IF NOT EXISTS idx_work_sdgs_sdg_code     ON work_sdgs(sdg_code);
CREATE INDEX IF NOT EXISTS idx_work_sdgs_contributor  ON work_sdgs(contributor_type);
CREATE INDEX IF NOT EXISTS idx_works_researcher       ON works(researcher_id);
CREATE INDEX IF NOT EXISTS idx_works_year             ON works(year);
CREATE INDEX IF NOT EXISTS idx_works_doi              ON works(doi);
CREATE INDEX IF NOT EXISTS idx_researchers_orcid      ON researchers(orcid);
CREATE INDEX IF NOT EXISTS idx_search_history_type    ON search_history(input_type, searched_at);
CREATE INDEX IF NOT EXISTS idx_journals_issn          ON journals(issn);

-- ── Full-text search untuk abstrak (FTS5) ─────────────────────────────────
CREATE VIRTUAL TABLE IF NOT EXISTS works_fts USING fts5(
    title, abstract, keywords,
    content='works', content_rowid='id'
);

-- ============================================================
-- Triggers untuk keep FTS in sync
-- ============================================================
CREATE TRIGGER IF NOT EXISTS works_fts_insert AFTER INSERT ON works BEGIN
    INSERT INTO works_fts(rowid, title, abstract, keywords)
    VALUES (new.id, new.title, new.abstract, new.keywords);
END;

CREATE TRIGGER IF NOT EXISTS works_fts_delete AFTER DELETE ON works BEGIN
    INSERT INTO works_fts(works_fts, rowid, title, abstract, keywords)
    VALUES ('delete', old.id, old.title, old.abstract, old.keywords);
END;

CREATE TRIGGER IF NOT EXISTS works_fts_update AFTER UPDATE ON works BEGIN
    INSERT INTO works_fts(works_fts, rowid, title, abstract, keywords)
    VALUES ('delete', old.id, old.title, old.abstract, old.keywords);
    INSERT INTO works_fts(rowid, title, abstract, keywords)
    VALUES (new.id, new.title, new.abstract, new.keywords);
END;

-- ============================================================
-- Contoh Query untuk Fitur Interaktif
-- ============================================================

-- Klik badge SDG-4 pada profil peneliti → tampilkan karya terkait
-- SELECT w.title, w.doi, w.journal, w.year, ws.confidence_score, ws.contributor_type
-- FROM works w
-- JOIN work_sdgs ws ON w.id = ws.work_id
-- WHERE ws.sdg_code = 'SDG4'
--   AND w.researcher_id = ?
-- ORDER BY ws.confidence_score DESC;

-- Leaderboard Active Contributor SDG-13
-- SELECT r.name, r.orcid, COUNT(*) AS active_count
-- FROM researchers r
-- JOIN works w ON r.id = w.researcher_id
-- JOIN work_sdgs ws ON w.id = ws.work_id
-- WHERE ws.sdg_code = 'SDG13'
--   AND ws.contributor_type = 'Active Contributor'
-- GROUP BY r.id
-- ORDER BY active_count DESC
-- LIMIT 20;

-- Daftar peneliti yang pernah dicari (halaman Archived)
-- SELECT r.*, COUNT(DISTINCT ws.sdg_code) AS sdg_count
-- FROM researchers r
-- LEFT JOIN works w ON r.id = w.researcher_id
-- LEFT JOIN work_sdgs ws ON w.id = ws.work_id
-- WHERE r.last_fetched IS NOT NULL
-- GROUP BY r.id
-- ORDER BY r.last_fetched DESC;

-- Full-text search abstrak
-- SELECT w.title, w.abstract, w.doi
-- FROM works_fts
-- JOIN works w ON works_fts.rowid = w.id
-- WHERE works_fts MATCH 'climate adaptation resilience'
-- ORDER BY rank;

-- SDG distribution heatmap per year
-- SELECT w.year, ws.sdg_code, COUNT(*) AS count
-- FROM works w
-- JOIN work_sdgs ws ON w.id = ws.work_id
-- WHERE ws.contributor_type IN ('Active Contributor', 'Relevant Contributor')
-- GROUP BY w.year, ws.sdg_code
-- ORDER BY w.year, ws.sdg_code;

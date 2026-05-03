<?php
/**
 * api/scopus.php — Scopus Journal Proxy Handler
 * Included by public/index.php POST proxy when _sdg=journal.
 * $_GET['issn'] is set by the proxy before include.
 */

require_once dirname(__DIR__) . '/api/SCOPUS_Journal-Checker_API.php';
require_once dirname(__DIR__) . '/includes/sdg_subject_mapping.php';

// ── Validate ISSN ──────────────────────────────────────────────────────────
$raw_issn = trim($_GET['issn'] ?? '');
$issn_clean = preg_replace('/[^0-9X]/', '', strtoupper($raw_issn));

if (strlen($issn_clean) !== 8) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Format ISSN tidak valid. Gunakan format: XXXX-XXXX (8 digit)']);
    exit;
}

$formatted_issn = substr($issn_clean, 0, 4) . '-' . substr($issn_clean, 4, 4);

// ── Cache check (7 days) ───────────────────────────────────────────────────
$cache_dir  = dirname(__DIR__) . '/cache';
if (!is_dir($cache_dir)) mkdir($cache_dir, 0755, true);
$cache_file = $cache_dir . '/journal_' . $issn_clean . '.json.gz';
$force      = !empty($_GET['refresh']) && $_GET['refresh'] === 'true';

if (!$force && file_exists($cache_file) && (time() - filemtime($cache_file)) < 604800) {
    $cached = @json_decode((string)gzdecode(file_get_contents($cache_file)), true);
    if (!empty($cached['status']) && $cached['status'] === 'success') {
        echo json_encode($cached);
        exit;
    }
}

// ── Call Scopus API ────────────────────────────────────────────────────────
global $SCOPUS_API_KEY;
$api    = new ScopusAPI($SCOPUS_API_KEY);
$result = $api->searchByISSN($formatted_issn);

if (empty($result['success'])) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => $result['error'] ?? 'Jurnal tidak ditemukan di Scopus.']);
    exit;
}

// ── Subject → SDG mapping ──────────────────────────────────────────────────
$subjects   = $result['subject_areas'] ?? [];
$sdg_codes  = mapSubjectsToSdgs($subjects);

$result['sdg_codes'] = $sdg_codes;
$result['status']    = 'success';
$result['action']    = 'journal';

// ── Persist to SQLite ──────────────────────────────────────────────────────
_scopusPersist($result);

// ── Write cache ────────────────────────────────────────────────────────────
file_put_contents($cache_file, gzencode(json_encode($result), 6), LOCK_EX);

echo json_encode($result);
exit;

// ── Persistence helper ─────────────────────────────────────────────────────
function _scopusPersist(array $j): void {
    try {
        if (function_exists('getDb')) {
            $db = getDb();
        } else {
            $db_path = (defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__)) . '/database/wizdam.db';
            $db_dir  = dirname($db_path);
            if (!is_dir($db_dir)) mkdir($db_dir, 0755, true);
            $db = new PDO('sqlite:' . $db_path);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->exec('PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON;');
            $schema = (defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__)) . '/database/schema.sql';
            if (file_exists($schema)) $db->exec((string)file_get_contents($schema));
        }

        $stmt = $db->prepare("
            INSERT INTO journals (issn, eissn, title, publisher, scopus_id, sjr, quartile, open_access, country, last_fetched)
            VALUES (:issn,:eissn,:title,:publisher,:scopus_id,:sjr,:quartile,:oa,:country,datetime('now'))
            ON CONFLICT(issn) DO UPDATE SET
              title=excluded.title, publisher=excluded.publisher, scopus_id=excluded.scopus_id,
              sjr=excluded.sjr, quartile=excluded.quartile,
              open_access=excluded.open_access, country=excluded.country,
              last_fetched=excluded.last_fetched
        ");
        $stmt->execute([
            ':issn'      => $j['issn'],
            ':eissn'     => $j['eissn'] ?? null,
            ':title'     => $j['title'] ?? null,
            ':publisher' => $j['publisher'] ?? null,
            ':scopus_id' => $j['scopus_id'] ?? null,
            ':sjr'       => isset($j['sjr']) ? (float)$j['sjr'] : null,
            ':quartile'  => $j['quartile'] ?? null,
            ':oa'        => !empty($j['open_access']) ? 1 : 0,
            ':country'   => $j['country'] ?? null,
        ]);

        $jid = (int)$db->lastInsertId();
        if (!$jid) {
            $s = $db->prepare('SELECT id FROM journals WHERE issn=?');
            $s->execute([$j['issn']]);
            $jid = (int)$s->fetchColumn();
        }

        if ($jid && !empty($j['subject_areas'])) {
            $db->prepare('DELETE FROM journal_subjects WHERE journal_id=?')->execute([$jid]);
            $ins = $db->prepare('INSERT INTO journal_subjects (journal_id, subject, asjc_code) VALUES (?,?,?)');
            foreach ($j['subject_areas'] as $subj) {
                if (is_string($subj)) {
                    $ins->execute([$jid, $subj, null]);
                } elseif (is_array($subj)) {
                    $ins->execute([$jid, $subj['name'] ?? '', $subj['code'] ?? null]);
                }
            }
        }
    } catch (Throwable $e) {
        error_log('[scopus persist] ' . $e->getMessage());
    }
}

<?php
/**
 * api/journal.php — Public JSON API: journal data by ISSN
 *
 * GET /api/journal.php?issn=XXXX-XXXX
 *
 * Returns cached/stored journal data. Does NOT call Scopus live —
 * data is served from SQLite + gzip cache only (read-only, fast).
 * To trigger a fresh Scopus fetch, use the POST proxy with _sdg=journal.
 *
 * Response fields:
 *   status, issn, eissn, title, publisher, scopus_id,
 *   sjr, h_index, citescore, snip, quartile, open_access,
 *   country, discontinued, subject_areas[], sdg_codes[],
 *   last_fetched, source
 */

define('PROJECT_ROOT', dirname(__DIR__));

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Cache-Control: public, max-age=3600');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed. Use GET.']);
    exit;
}

// ── Validate ISSN ──────────────────────────────────────────────────────────
$raw = trim($_GET['issn'] ?? '');
$clean = preg_replace('/[^0-9X]/', '', strtoupper($raw));

if (strlen($clean) !== 8) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Parameter issn wajib diisi. Format: XXXX-XXXX (8 digit).',
        'example' => '?issn=1234-5678',
    ]);
    exit;
}

$formatted = substr($clean, 0, 4) . '-' . substr($clean, 4, 4);

// ── Try gzip cache first (fastest) ────────────────────────────────────────
$cache_file = PROJECT_ROOT . '/cache/journal_' . $clean . '.json.gz';
if (file_exists($cache_file)) {
    $cached = @json_decode((string)gzdecode(file_get_contents($cache_file)), true);
    if (!empty($cached['status']) && $cached['status'] === 'success') {
        $cached['source'] = 'cache';
        echo json_encode($cached);
        exit;
    }
}

// ── Fallback: read from SQLite ─────────────────────────────────────────────
try {
    $pdo = null;
    if (function_exists('getDb')) {
        $pdo = getDb();
    } else {
        $db_path = PROJECT_ROOT . '/database/wizdam.db';
        if (file_exists($db_path)) {
            $pdo = new PDO('sqlite:' . $db_path);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

    if (!$pdo) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Jurnal tidak ditemukan. Cek dulu melalui halaman journal-profile.']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT * FROM journals WHERE issn = ? OR issn = ? LIMIT 1');
    $stmt->execute([$clean, $formatted]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode([
            'status'   => 'error',
            'message'  => 'Jurnal dengan ISSN ' . $formatted . ' belum pernah dicek. Gunakan ?page=journal-profile&issn=' . urlencode($formatted) . ' untuk fetch dari Scopus.',
            'fetch_url' => '?page=journal-profile&issn=' . urlencode($formatted),
        ]);
        exit;
    }

    // Load subject areas
    $subjects = [];
    try {
        $s2 = $pdo->prepare('SELECT subject, asjc_code FROM journal_subjects WHERE journal_id = ?');
        $s2->execute([$row['id']]);
        $subjects = $s2->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { /* optional table */ }

    // Load SDG codes from cache if available for richer data
    $sdg_codes = [];
    if (file_exists($cache_file)) {
        $c2 = @json_decode((string)gzdecode(file_get_contents($cache_file)), true);
        if (!empty($c2['sdg_codes'])) $sdg_codes = $c2['sdg_codes'];
    }
    if (empty($sdg_codes) && !empty($subjects)) {
        require_once PROJECT_ROOT . '/includes/sdg_subject_mapping.php';
        $sdg_codes = mapSubjectsToSdgs(array_column($subjects, 'subject'));
    }

    $response = [
        'status'         => 'success',
        'issn'           => $formatted,
        'eissn'          => $row['eissn'] ?? null,
        'title'          => $row['title'] ?? null,
        'publisher'      => $row['publisher'] ?? null,
        'scopus_id'      => $row['scopus_id'] ?? null,
        'sjr'            => isset($row['sjr']) ? (float)$row['sjr'] : null,
        'h_index'        => isset($row['h_index']) ? (int)$row['h_index'] : null,
        'citescore'      => isset($row['citescore']) ? (float)$row['citescore'] : null,
        'snip'           => isset($row['snip']) ? (float)$row['snip'] : null,
        'quartile'       => $row['quartile'] ?? null,
        'open_access'    => !empty($row['open_access']),
        'country'        => $row['country'] ?? null,
        'discontinued'   => !empty($row['discontinued']),
        'subject_areas'  => $subjects,
        'sdg_codes'      => $sdg_codes,
        'last_fetched'   => $row['last_fetched'] ?? null,
        'source'         => 'database',
    ];

    echo json_encode($response);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    exit;
}

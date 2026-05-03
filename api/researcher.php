<?php
/**
 * api/researcher.php — Public JSON API: researcher profile by ORCID
 * Served via: GET ?api=researcher&orcid=XXXX-XXXX-XXXX-XXXX
 */
define('PROJECT_ROOT', dirname(__DIR__));
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=1800');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed. Use GET.']);
    exit;
}

$orcid = trim($_GET['orcid'] ?? '');

if (!$orcid || !preg_match('/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/', $orcid)) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Parameter orcid wajib diisi. Format: XXXX-XXXX-XXXX-XXXX.',
        'example' => '?api=researcher&orcid=0000-0002-1825-0097',
    ]);
    exit;
}

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
        http_response_code(503);
        echo json_encode(['status' => 'error', 'message' => 'Database tidak tersedia.']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT * FROM researchers WHERE orcid = ? LIMIT 1');
    $stmt->execute([$orcid]);
    $researcher = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$researcher) {
        http_response_code(404);
        echo json_encode([
            'status'    => 'error',
            'message'   => 'Peneliti dengan ORCID ' . $orcid . ' tidak ditemukan. Gunakan halaman profil untuk fetch data.',
            'fetch_url' => '?page=orcid-profile&orcid=' . urlencode($orcid),
        ]);
        exit;
    }

    $rid = (int)$researcher['id'];

    $works_stmt = $pdo->prepare("
        SELECT w.id, w.title, w.doi, w.year,
               GROUP_CONCAT(ws.sdg_code || '|' || ws.contributor_type) AS sdg_raw
        FROM works w
        LEFT JOIN work_sdgs ws ON ws.work_id = w.id AND ws.contributor_type != 'Not Relevant'
        WHERE w.researcher_id = :rid
        GROUP BY w.id
        ORDER BY w.year DESC, w.title ASC
    ");
    $works_stmt->execute([':rid' => $rid]);
    $raw_works = $works_stmt->fetchAll(PDO::FETCH_ASSOC);

    $works_out = [];
    $all_sdg_codes = [];

    foreach ($raw_works as $wrow) {
        $sdg_list  = [];
        $sdg_types = [];
        if (!empty($wrow['sdg_raw'])) {
            foreach (explode(',', $wrow['sdg_raw']) as $pair) {
                $parts = explode('|', $pair, 2);
                if (count($parts) === 2) {
                    [$code, $ctype] = $parts;
                    $sdg_list[]         = $code;
                    $sdg_types[$code]   = $ctype;
                    $all_sdg_codes[$code] = true;
                }
            }
        }
        $works_out[] = [
            'title'             => $wrow['title'],
            'doi'               => $wrow['doi'],
            'year'              => $wrow['year'] ? (int)$wrow['year'] : null,
            'sdgs'              => array_values(array_unique($sdg_list)),
            'contributor_types' => $sdg_types,
        ];
    }

    $sdg_summary_stmt = $pdo->prepare("
        SELECT ws.sdg_code, ws.contributor_type,
               COUNT(*)              AS cnt,
               MAX(ws.confidence_score) AS top_confidence
        FROM work_sdgs ws
        JOIN works w ON w.id = ws.work_id
        WHERE w.researcher_id = :rid AND ws.contributor_type != 'Not Relevant'
        GROUP BY ws.sdg_code, ws.contributor_type
        ORDER BY cnt DESC
    ");
    $sdg_summary_stmt->execute([':rid' => $rid]);
    $sdg_rows = $sdg_summary_stmt->fetchAll(PDO::FETCH_ASSOC);

    $sdg_summary = [];
    foreach ($sdg_rows as $sr) {
        $code = $sr['sdg_code'];
        if (!isset($sdg_summary[$code]) || (int)$sr['cnt'] > $sdg_summary[$code]['count']) {
            $sdg_summary[$code] = [
                'count'            => (int)$sr['cnt'],
                'top_confidence'   => $sr['top_confidence'] !== null ? round((float)$sr['top_confidence'], 4) : null,
                'contributor_type' => $sr['contributor_type'],
            ];
        }
    }

    $institutions = json_decode($researcher['institutions'] ?? '[]', true);
    if (!is_array($institutions)) {
        $institutions = $researcher['institutions'] ? [$researcher['institutions']] : [];
    }

    $sdg_codes = array_keys($all_sdg_codes);
    sort($sdg_codes);

    echo json_encode([
        'status'       => 'success',
        'orcid'        => $researcher['orcid'],
        'name'         => $researcher['name'],
        'institutions' => $institutions,
        'total_works'  => (int)($researcher['total_works'] ?? count($works_out)),
        'sdg_codes'    => $sdg_codes,
        'works'        => $works_out,
        'sdg_summary'  => $sdg_summary,
        'last_fetched' => $researcher['last_fetched'],
        'source'       => 'database',
    ]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    exit;
}

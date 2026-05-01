<?php
/**
 * SDGs Classification AJAX Proxy Endpoint
 *
 * Menerima POST dari frontend JavaScript dan meneruskan ke
 * SDG_Classification_API.php via direct include (tanpa HTTP/cURL,
 * aman dari WAF, tanpa overhead network).
 *
 * Format POST request:
 *   _sdg=init    + orcid=xxx [+ refresh=true]
 *   _sdg=batch   + orcid=xxx + offset=N + limit=N [+ refresh=true]
 *   _sdg=summary + orcid=xxx
 *   _sdg=doi     + doi=xxx [+ refresh=true]
 *
 * @author Rochmady and Wizdam Team
 * @version 1.0
 */

// Hanya terima POST (AJAX) atau OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed. Use POST.']);
    exit;
}

// Path ke file API utama (satu direktori yang sama)
define('SDG_API_PATH', __DIR__ . '/SDG_Classification_API.php');

if (!file_exists(SDG_API_PATH)) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'File API tidak ditemukan: ' . SDG_API_PATH]);
    exit;
}

$action = isset($_POST['_sdg']) ? trim($_POST['_sdg']) : '';
if (empty($action)) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Parameter _sdg (action) wajib diisi.']);
    exit;
}

// ---------------------------------------------------------------
// Bangun $_GET sesuai parameter yang dibutuhkan API
// ---------------------------------------------------------------
$params = [];
$refresh = (!empty($_POST['refresh']) && $_POST['refresh'] === 'true');
if ($refresh) $params['refresh'] = 'true';

switch ($action) {
    case 'init':
        if (empty($_POST['orcid'])) {
            while (ob_get_level()) ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Parameter orcid wajib diisi.']);
            exit;
        }
        $params['orcid']  = trim($_POST['orcid']);
        $params['action'] = 'init';
        break;

    case 'batch':
        if (empty($_POST['orcid'])) {
            while (ob_get_level()) ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Parameter orcid wajib diisi.']);
            exit;
        }
        $params['orcid']  = trim($_POST['orcid']);
        $params['action'] = 'batch';
        $params['offset'] = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
        $params['limit']  = isset($_POST['limit'])  ? (int)$_POST['limit']  : 3;
        break;

    case 'summary':
        if (empty($_POST['orcid'])) {
            while (ob_get_level()) ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Parameter orcid wajib diisi.']);
            exit;
        }
        $params['orcid']  = trim($_POST['orcid']);
        $params['action'] = 'summary';
        break;

    case 'doi':
        if (empty($_POST['doi'])) {
            while (ob_get_level()) ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Parameter doi wajib diisi.']);
            exit;
        }
        $rawDoi = trim($_POST['doi']);
        // Bersihkan prefix URL jika ada
        $rawDoi = preg_replace('/^https?:\/\/(dx\.)?doi\.org\//i', '', $rawDoi);
        $params['doi'] = $rawDoi;
        break;

    default:
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Action '$action' tidak dikenal. Gunakan: init, batch, summary, doi."]);
        exit;
}

// ---------------------------------------------------------------
// Spoof REQUEST_METHOD dan set $_GET agar API berjalan normal
// ---------------------------------------------------------------
$_GET                          = $params;
$_SERVER['REQUEST_METHOD']     = 'GET';

// Bersihkan buffer dan kirim Content-Type sebelum include API
while (ob_get_level()) ob_end_clean();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// ---------------------------------------------------------------
// Include API — API membaca $_GET lalu echo JSON dan exit sendiri
// ---------------------------------------------------------------
require SDG_API_PATH;
exit; // Pastikan tidak ada output setelah API selesai

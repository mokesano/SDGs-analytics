<?php
/**
 * api/pdf.php — PDF Upload & Text Extraction Endpoint
 * Receives a PDF upload, extracts text, returns JSON for SDG analysis.
 *
 * POST parameters: pdf (file)
 * Response: { status, text, word_count, char_count } or { status, message }
 */

require_once dirname(__DIR__) . '/includes/config.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

if (empty($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
    $err_msg = 'No PDF uploaded';
    if (isset($_FILES['pdf']['error'])) {
        $codes = [
            UPLOAD_ERR_INI_SIZE   => 'File melebihi batas upload server.',
            UPLOAD_ERR_FORM_SIZE  => 'File melebihi batas form.',
            UPLOAD_ERR_PARTIAL    => 'Upload tidak lengkap.',
            UPLOAD_ERR_NO_FILE    => 'Tidak ada file yang dipilih.',
            UPLOAD_ERR_NO_TMP_DIR => 'Direktori tmp tidak tersedia.',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menyimpan file.',
        ];
        $err_msg = $codes[$_FILES['pdf']['error']] ?? 'Upload error.';
    }
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $err_msg]);
    exit;
}

$file = $_FILES['pdf'];

if ($file['size'] > MAX_UPLOAD_SIZE) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'File terlalu besar. Maksimal ' . round(MAX_UPLOAD_SIZE / 1048576) . 'MB.']);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);
$ext   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if ($mime !== 'application/pdf' || $ext !== 'pdf') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Hanya file PDF yang diterima.']);
    exit;
}

$upload_dir = PROJECT_ROOT . '/storage/uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$safe_name = 'pdf_' . uniqid('', true) . '.pdf';
$dest      = $upload_dir . $safe_name;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan file sementara.']);
    exit;
}

$text = '';

// Method 1: pdftotext (poppler-utils)
$pdftotext = trim((string)shell_exec('which pdftotext 2>/dev/null'));
if (!empty($pdftotext)) {
    $txt_file = $dest . '.txt';
    $cmd      = $pdftotext . ' -enc UTF-8 ' . escapeshellarg($dest) . ' ' . escapeshellarg($txt_file) . ' 2>/dev/null';
    shell_exec($cmd);
    if (file_exists($txt_file)) {
        $text = (string)file_get_contents($txt_file);
        unlink($txt_file);
    }
}

// Method 2: Crude PDF text-layer extraction if pdftotext unavailable/failed
if (empty(trim($text))) {
    $raw = (string)file_get_contents($dest);

    // Extract text between BT..ET markers (PDF content streams)
    preg_match_all('/BT\s+(.+?)\s+ET/s', $raw, $m);
    $parts = [];
    foreach ($m[1] as $block) {
        // Extract strings: (text) or <hex>
        preg_match_all('/\(([^)\\\\]*(?:\\\\.[^)\\\\]*)*)\)/', $block, $str_m);
        foreach ($str_m[1] as $s) {
            $s = str_replace(['\\n','\\r','\\t'], [' ',' ',' '], $s);
            $s = str_replace('\\(', '(', str_replace('\\)', ')', $s));
            $parts[] = $s;
        }
    }
    $text = implode(' ', $parts);

    // Fallback: grab all printable runs >= 4 chars
    if (strlen(trim($text)) < 100) {
        preg_match_all('/[\x20-\x7E]{4,}/', $raw, $runs);
        $text = implode(' ', $runs[0]);
    }

    $text = (string)preg_replace('/\s+/', ' ', trim($text));
    $text = (string)preg_replace('/[^\x20-\x7E\xC0-\xFF\n]/u', ' ', $text);
}

unlink($dest);

$text = trim($text);

if (strlen($text) < 50) {
    http_response_code(422);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Tidak dapat mengekstrak teks dari PDF ini. Pastikan PDF mengandung teks (bukan hasil scan gambar). Untuk PDF scan, gunakan OCR terlebih dahulu.'
    ]);
    exit;
}

// Truncate to 8000 chars to keep analysis fast
$text = mb_substr($text, 0, 8000, 'UTF-8');

echo json_encode([
    'status'     => 'success',
    'text'       => $text,
    'word_count' => str_word_count($text),
    'char_count' => mb_strlen($text, 'UTF-8'),
]);

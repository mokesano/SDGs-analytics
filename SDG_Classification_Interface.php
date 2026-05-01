<?php
/**
 * SDG Classification Presentation Interface
 * Interface modern untuk menampilkan hasil analisis SDG
 *
 * Perubahan dari v2.3 (MINIMAL – semua bagian original tetap utuh):
 * + Proxy block ditambah di atas untuk menghindari WAF
 * + makeApiRequest kini pakai direct PHP include (tidak cURL ke URL eksternal)
 * + ORCID diproses via AJAX sequential (tidak timeout) – JS intercept
 * + DOI tetap diproses via PHP form (seperti aslinya)
 * + Progress section ditambah (hidden by default)
 * Semua CSS, HTML, fungsi PHP, dan JS asli TIDAK diubah.
 *
 * @version 2.4 - AJAX Sequential untuk ORCID
 * @author Rochmady and Wizdam Team
 * Last update: 2025-06-16
 */

// ================================================================
// BAGIAN #0 – PROXY (TAMBAHAN BARU, harus paling atas)
// Menangani request AJAX dari JavaScript untuk ORCID sequential
// Direct PHP include – tidak ada HTTP request, tidak kena WAF
// ================================================================
define('API_FILE_PATH', __DIR__ . '/api/SDGsClassification_v518E.php');
define('AJAX_BATCH_SIZE', 3);

// Cek POST dulu (AJAX), lalu GET (direct/legacy)
$_sdg_post = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['_sdg'])) {
    $_sdg_post = $_POST;
} elseif (isset($_GET['proxy_action'])) {
    // Konversi GET ke format POST agar kode bawah seragam
    $_sdg_post = array_merge(['_sdg'=>$_GET['proxy_action']], $_GET);
}

if ($_sdg_post !== null) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');

    if (!file_exists(API_FILE_PATH)) {
        http_response_code(503);
        echo json_encode(['status'=>'error','message'=>'File API tidak ditemukan: '.API_FILE_PATH]);
        exit;
    }

    $pxa    = $_sdg_post['_sdg'];
    $params = [];

    switch ($pxa) {
        case 'init':
            if (empty($_sdg_post['orcid'])) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'orcid required']); exit; }
            $params = ['orcid'=>trim($_sdg_post['orcid']),'action'=>'init'];
            if (!empty($_sdg_post['refresh'])) $params['refresh'] = 'true';
            break;
        case 'batch':
            if (empty($_sdg_post['orcid'])) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'orcid required']); exit; }
            $params = ['orcid'=>trim($_sdg_post['orcid']),'action'=>'batch',
                       'offset'=>(int)($_sdg_post['offset']??0),'limit'=>(int)($_sdg_post['limit']??AJAX_BATCH_SIZE)];
            if (!empty($_sdg_post['refresh'])) $params['refresh'] = 'true';
            break;
        case 'summary':
            if (empty($_sdg_post['orcid'])) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'orcid required']); exit; }
            $params = ['orcid'=>trim($_sdg_post['orcid']),'action'=>'summary'];
            break;
        case 'doi':
            if (empty($_sdg_post['doi'])) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'doi required']); exit; }
            $params = ['doi'=>trim($_sdg_post['doi'])];
            if (!empty($_sdg_post['refresh'])) $params['refresh'] = 'true';
            break;
        default:
            http_response_code(400); echo json_encode(['status'=>'error','message'=>'Unknown action: '.htmlspecialchars($pxa)]); exit;
    }

    $orig_get = $_GET;
    $_GET = $params;
    // API cek REQUEST_METHOD harus GET – paksa agar tidak kena "Method not allowed"
    $orig_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $prev_err = ini_get('display_errors');
    ini_set('display_errors','0');
    ob_start();
    try { require API_FILE_PATH; } catch (Throwable $t) {
        ob_end_clean(); $_GET = $orig_get; ini_set('display_errors',$prev_err);
        http_response_code(500); echo json_encode(['status'=>'error','message'=>'Fatal: '.$t->getMessage()]); exit;
    }
    $raw = ob_get_clean();
    ini_set('display_errors',$prev_err);
    $_GET = $orig_get;

    // Strip PHP warnings/notices sebelum JSON
    $jpos = false;
    for ($i=0,$l=strlen($raw);$i<$l;$i++) { if ($raw[$i]==='{' || $raw[$i]==='[') { $jpos=$i; break; } }
    $_SERVER['REQUEST_METHOD'] = $orig_method; // kembalikan method asli
    $json_str = ($jpos!==false) ? substr($raw,$jpos) : '';
    if (empty($json_str)) { http_response_code(500); echo json_encode(['status'=>'error','message'=>'API tidak menghasilkan output']); exit; }
    json_decode($json_str);
    if (json_last_error()!==JSON_ERROR_NONE) {
        http_response_code(500);
        echo json_encode(['status'=>'error','message'=>'Output API bukan JSON: '.json_last_error_msg(),'raw'=>base64_encode(substr(strip_tags($raw),0,300))]);
        exit;
    }
    echo $json_str;
    exit;
}

// ================================================================
// Bukan request proxy → lanjut ke halaman HTML biasa (dokumen 5 asli)
// ================================================================

// ==============================================
// KONFIGURASI SISTEM
// ==============================================
$API_BASE_URL = 'https://wizdam.sangia.org/api/SDGsClassification_v518.php';

// ==============================================
// DEFINISI SDG DENGAN SVG ICONS RESMI UN
// ==============================================
$SDG_DEFINITIONS = [
    'SDG1'  => ['title'=>'No Poverty',                              'color'=>'#e5243b','svg_url'=>'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_1.svg'],
    'SDG2'  => ['title'=>'Zero Hunger',                             'color'=>'#dda63a','svg_url'=>'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_2.svg'],
    'SDG3'  => ['title'=>'Good Health and Well-being',              'color'=>'#4c9f38','svg_url'=>'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_3.svg'],
    'SDG4'  => ['title'=>'Quality Education',                       'color'=>'#c5192d','svg_url'=>'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_4.svg'],
    'SDG5'  => ['title'=>'Gender Equality',                         'color'=>'#ff3a21','svg_url'=>'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_5.svg'],
    'SDG6'  => ['title'=>'Clean Water and Sanitation',              'color'=>'#26bde2','svg_url'=>'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_6.svg'],
    'SDG7'  => ['title'=>'Affordable and Clean Energy',             'color'=>'#fcc30b','svg_url'=>'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_7.svg'],
    'SDG8'  => ['title'=>'Decent Work and Economic Growth',         'color'=>'#a21942','svg_url'=>'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_8.svg'],
    'SDG9'  => ['title'=>'Industry, Innovation and Infrastructure', 'color'=>'#fd6925','svg_url'=>'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_9.svg'],
    'SDG10' => ['title'=>'Reduced Inequalities',                    'color'=>'#dd1367','svg_url'=>'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_10.svg'],
    'SDG11' => ['title'=>'Sustainable Cities and Communities',      'color'=>'#fd9d24','svg_url'=>'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_11.svg'],
    'SDG12' => ['title'=>'Responsible Consumption and Production',  'color'=>'#bf8b2e','svg_url'=>'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_12.svg'],
    'SDG13' => ['title'=>'Climate Action',                          'color'=>'#3f7e44','svg_url'=>'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_13.svg'],
    'SDG14' => ['title'=>'Life Below Water',                        'color'=>'#0a97d9','svg_url'=>'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_14.svg'],
    'SDG15' => ['title'=>'Life on Land',                            'color'=>'#56c02b','svg_url'=>'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_15.svg'],
    'SDG16' => ['title'=>'Peace, Justice and Strong Institutions',  'color'=>'#00689d','svg_url'=>'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_16.svg'],
    'SDG17' => ['title'=>'Partnerships for the Goals',              'color'=>'#19486a','svg_url'=>'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_17.svg'],
];

// ==============================================
// FUNGSI UTILITAS – TIDAK DIUBAH DARI v2.3
// ==============================================

/**
 * makeApiRequest – versi direct include (menggantikan cURL ke URL eksternal)
 * Antarmuka tetap sama persis sehingga semua kode pemanggil tidak perlu diubah.
 */
function makeApiRequest($url) {
    $parsed = parse_url($url);
    $params = [];
    if (isset($parsed['query'])) parse_str($parsed['query'], $params);
    if (empty($params)) return ['error' => 'Tidak ada parameter: ' . $url];

    $api_file = API_FILE_PATH;
    if (!file_exists($api_file)) return ['error' => 'File API tidak ditemukan: ' . $api_file];

    $orig_get  = $_GET;
    $_GET      = $params;
    $prev_err  = ini_get('display_errors');
    ini_set('display_errors','0');

    ob_start();
    try { require $api_file; } catch (Throwable $t) {
        ob_end_clean(); $_GET = $orig_get; ini_set('display_errors',$prev_err);
        return ['error' => 'Fatal: ' . $t->getMessage()];
    }
    $raw = ob_get_clean();
    ini_set('display_errors',$prev_err);
    $_GET = $orig_get;

    $jpos = false;
    for ($i=0,$l=strlen($raw);$i<$l;$i++) { if ($raw[$i]==='{' || $raw[$i]==='[') { $jpos=$i; break; } }
    $json = ($jpos!==false) ? substr($raw,$jpos) : $raw;

    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) return ['error' => 'JSON invalid: '.json_last_error_msg().' | '.substr(strip_tags($raw),0,100)];
    return ['data' => $data];
}

// Semua fungsi berikut TIDAK DIUBAH dari v2.3 ─────────────────────

function detectInputType($input) {
    $input = trim($input);
    if (preg_match('/orcid\.org\/(\d{4}-\d{4}-\d{4}-\d{3}[\dX])/', $input, $matches)) return 'orcid';
    if (preg_match('/^(\d{4}-\d{4}-\d{4}-\d{3}[\dX])$/', $input)) return 'orcid';
    if (preg_match('/^0000-\d{4}-\d{4}-\d{3}[\dX]$/', $input)) return 'orcid';
    if (strlen($input) >= 7 && strpos($input, '/') !== false) {
        if (preg_match('/^10\.\d+\//', $input) || preg_match('/doi\.org\//', $input) || preg_match('/dx\.doi\.org\//', $input)) return 'doi';
        if (strlen($input) > 10) return 'doi';
    }
    return null;
}

function cleanInput($input, $type) {
    $input = trim($input);
    if ($type === 'orcid') {
        if (preg_match('/orcid\.org\/(\d{4}-\d{4}-\d{4}-\d{3}[\dX])/', $input, $matches)) $input = $matches[1];
        $input = preg_replace('/[^\d\-X]/i', '', $input);
        if (!preg_match('/^0000-/', $input) && preg_match('/^\d{15}[\dX]$/', $input))
            $input = substr($input,0,4).'-'.substr($input,4,4).'-'.substr($input,8,4).'-'.substr($input,12);
    }
    if ($type === 'doi') {
        $input = str_replace(['https://doi.org/','http://doi.org/','https://dx.doi.org/','http://dx.doi.org/','doi:'], '', $input);
    }
    return $input;
}

function validateOrcid($orcid) {
    $orcid = trim($orcid);
    if (!preg_match('/^0000-\d{4}-\d{4}-\d{3}[\dX]$/', $orcid)) return false;
    $digits = str_replace('-', '', substr($orcid, 0, -1));
    $checkDigit = substr($orcid, -1);
    $total = 0;
    for ($i = 0; $i < strlen($digits); $i++) $total = ($total + intval($digits[$i])) * 2;
    $remainder = $total % 11; $result = (12 - $remainder) % 11;
    $expectedCheckDigit = ($result == 10) ? 'X' : strval($result);
    return $checkDigit === $expectedCheckDigit;
}

function renderSafeHtml($content) {
    if (empty($content)) return '';
    $decoded = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $fixed = fixUnclosedHtmlTags($decoded);
    $safe = strip_tags($fixed, '<strong><b><em><i><u><sup><sub><br><p>');
    return cleanupHtmlStructure($safe);
}

function fixUnclosedHtmlTags($content) {
    $pairedTags = ['strong', 'b', 'em', 'i', 'u', 'sup', 'sub', 'p'];
    $fixed = $content;
    foreach ($pairedTags as $tag) $fixed = balanceHtmlTags($fixed, $tag);
    return resolveNestedTagConflicts($fixed);
}

function balanceHtmlTags($content, $tag) {
    preg_match_all('/<' . preg_quote($tag, '/') . '(?:\s[^>]*)?>/', $content, $openMatches);
    preg_match_all('/<\/' . preg_quote($tag, '/') . '>/', $content, $closeMatches);
    $openTags = $openMatches[0]; $closeTags = $closeMatches[0];
    if (count($openTags) > count($closeTags)) {
        $missingClose = count($openTags) - count($closeTags);
        for ($i = 0; $i < $missingClose; $i++) $content .= '</' . $tag . '>';
    } elseif (count($closeTags) > count($openTags)) {
        $excessClose = count($closeTags) - count($openTags);
        for ($i = 0; $i < $excessClose; $i++) $content = preg_replace('/<\/' . preg_quote($tag, '/') . '>/', '', $content, 1);
    }
    return $content;
}

function resolveNestedTagConflicts($content) {
    $patterns = [
        '/<(strong|b)([^>]*)>([^<]*)<(em|i)([^>]*)>([^<]*)<\/(strong|b)>([^<]*)<\/(em|i)>/i' => '<$1$2><$4$5>$3$6</$4></$1>$7</$4>',
        '/<(strong|b)([^>]*)>([^<]*)<(strong|b)([^>]*)>/i' => '<$1$2>$3',
        '/<\/(strong|b)>([^<]*)<\/(strong|b)>/i' => '</$1>$2',
        '/<(strong|b|em|i|u|sup|sub)(?:\s[^>]*)?>[\s]*<\/\1>/i' => '',
    ];
    foreach ($patterns as $pattern => $replacement) $content = preg_replace($pattern, $replacement, $content);
    return $content;
}

function cleanupHtmlStructure($content) {
    $content = preg_replace('/<p[^>]*>[\s]*<\/p>/i', '', $content);
    $content = preg_replace('/<p([^>]*)>/i', '<p$1>', $content);
    $content = preg_replace('/<\/p>/i', '</p> ', $content);
    $content = preg_replace('/\s+/', ' ', $content);
    return trim($content);
}

function renderSafeHtmlConservative($content) {
    if (empty($content)) return '';
    $decoded = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $decoded = preprocessMalformedHtml($decoded);
    $replacements = [
        '/<(strong|b)(?:\s[^>]*)?>((?:(?!<\/?(?:strong|b)>).)*)<\/(strong|b)>/i' => '<strong>$2</strong>',
        '/<(em|i)(?:\s[^>]*)?>((?:(?!<\/?(?:em|i)>).)*)<\/(em|i)>/i' => '<em>$2</em>',
        '/<u(?:\s[^>]*)?>((?:(?!<\/?u>).)*)<\/u>/i' => '<u>$1</u>',
        '/<(sup|sub)(?:\s[^>]*)?>((?:(?!<\/?(?:sup|sub)>).)*)<\/(sup|sub)>/i' => '<$1>$2</$1>',
        '/<br\s*\/?>/i' => '<br>',
        '/<p(?:\s[^>]*)?>(.*?)<\/p>/is' => '<p>$1</p>',
    ];
    $safe = $decoded;
    foreach ($replacements as $p => $r) $safe = preg_replace($p, $r, $safe);
    $safe = handleUnclosedTags($safe);
    $safe = strip_tags($safe, '<strong><em><u><sup><sub><br><p>');
    return finalHtmlCleanup($safe);
}

function preprocessMalformedHtml($content) {
    $patterns = [
        '/<(\w+)\s+[^>]*?(?:style|class|id)\s*=\s*["\'][^"\']*["\'][^>]*>/i' => '<$1>',
        '/<(strong|b|em|i|u|sup|sub|p)\s*\/>/i' => '<$1></$1>',
        '/<(script|style)[^>]*>.*?<\/\1>/is' => '',
    ];
    foreach ($patterns as $p => $r) $content = preg_replace($p, $r, $content);
    return $content;
}

function handleUnclosedTags($content) {
    $tags = ['strong', 'em', 'u', 'sup', 'sub', 'p'];
    foreach ($tags as $tag) {
        preg_match_all('/<' . $tag . '(?:\s[^>]*)?>/i', $content, $openMatches);
        preg_match_all('/<\/' . $tag . '>/i', $content, $closeMatches);
        $diff = count($openMatches[0]) - count($closeMatches[0]);
        for ($i = 0; $i < $diff; $i++) $content .= '</' . $tag . '>';
    }
    return $content;
}

function finalHtmlCleanup($content) {
    $patterns = [
        '/<(strong|em|u|sup|sub|p)(?:\s[^>]*)?>[\s\r\n]*<\/\1>/i' => '',
        '/\s{2,}/' => ' ', '/\s*<br>\s*/' => '<br>', '/^\s+|\s+$/' => '',
    ];
    foreach ($patterns as $p => $r) $content = preg_replace($p, $r, $content);
    return trim($content);
}

function renderDetailedAnalysis($data, $sdgDefinitions, $index) {
    $output = '<div class="detailed-analysis" id="analysis-' . $index . '">';
    if (isset($data['detailed_analysis']) && !empty($data['detailed_analysis'])) {
        foreach ($data['detailed_analysis'] as $sdg => $analysis) {
            $sdg_info = isset($sdgDefinitions[$sdg]) ? $sdgDefinitions[$sdg] : array('title' => $sdg);
            $output .= '<div class="analysis-section">';
            $output .= '<h5>' . htmlspecialchars($sdg . ': ' . $sdg_info['title']) . ' ';
            $output .= '<span style="color: #667eea;">(Score: ' . round($analysis['score'], 3) . ')</span></h5>';
            if (isset($analysis['components'])) {
                $output .= '<div class="analysis-components">';
                $components = array('keyword_score'=>'Keywords','similarity_score'=>'Similarity','substantive_score'=>'Substantive','causal_score'=>'Causal');
                foreach ($components as $key => $label) {
                    if (isset($analysis['components'][$key])) {
                        $output .= '<div class="component-score"><div class="component-label">' . htmlspecialchars($label) . '</div>';
                        $output .= '<div class="component-value">' . round($analysis['components'][$key], 3) . '</div></div>';
                    }
                }
                $output .= '</div>';
            }
            $output .= '<div class="analysis-info">';
            $contributorType = isset($analysis['contributor_type']['type']) ? $analysis['contributor_type']['type'] : 'Unknown';
            $confidenceLevel = isset($analysis['confidence_level']) ? $analysis['confidence_level'] : 'Unknown';
            $output .= '<span class="info-badge">' . htmlspecialchars($contributorType) . '</span> ';
            $output .= '<span class="info-badge confidence">' . htmlspecialchars($confidenceLevel) . '</span>';
            $output .= '</div>';
            if (isset($analysis['evidence']['keyword_matches'])) {
                $output .= '<div class="evidence-section"><div class="evidence-title">Keyword Evidence:</div>';
                foreach (array_slice($analysis['evidence']['keyword_matches'], 0, 2) as $match) {
                    $output .= '<div class="evidence-item">';
                    $output .= '<strong>' . renderSafeHtmlConservative(isset($match['keyword']) ? $match['keyword'] : '') . '</strong>: ';
                    $output .= truncateHtmlSafely(renderSafeHtmlConservative(isset($match['context']) ? $match['context'] : ''), 120) . '...';
                    $output .= '</div>';
                }
                $output .= '</div>';
            }
            $output .= '</div>';
        }
    } else {
        $output .= '<p style="color: #666; text-align: center; padding: 20px;">No detailed analysis available.</p>';
    }
    return $output . '</div>';
}

function truncateHtmlSafely($html, $length) {
    if (strlen(strip_tags($html)) <= $length) return $html;
    $truncated = ''; $totalLength = 0; $openTags = array();
    preg_match_all('/(<[^>]+>)|([^<]+)/', $html, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        if (isset($match[2])) {
            $text = $match[2]; $remainingLength = $length - $totalLength;
            if (strlen($text) > $remainingLength) { $truncated .= substr($text, 0, $remainingLength); break; }
            $truncated .= $text; $totalLength += strlen($text);
        } else {
            $tag = $match[1]; $truncated .= $tag;
            if (preg_match('/<(\w+)(?:\s[^>]*)?>/i', $tag, $tagMatch)) $openTags[] = $tagMatch[1];
            elseif (preg_match('/<\/(\w+)>/i', $tag, $tagMatch)) {
                $key = array_search($tagMatch[1], array_reverse($openTags, true));
                if ($key !== false) unset($openTags[$key]);
            }
        }
        if ($totalLength >= $length) break;
    }
    foreach (array_reverse($openTags) as $tag) $truncated .= '</' . $tag . '>';
    return $truncated;
}

// ==============================================
// PEMROSESAN FORM – DOI pakai PHP, ORCID pakai AJAX
// ==============================================
$analysis_result = null;
$error_message = null;
$detected_input_type = null;
$clean_input_value = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_value = isset($_POST['input_value']) ? trim($_POST['input_value']) : '';

    if (!empty($input_value)) {
        $detected_type = detectInputType($input_value);
        $detected_input_type = $detected_type;

        if ($detected_type === null) {
            $error_message = 'Input format not recognised. Please enter a valid ORCID ID (format: 0000-0000-0000-0000) or DOI.';
        } else {
            $clean_input = cleanInput($input_value, $detected_type);
            $clean_input_value = $clean_input;

            if ($detected_type === 'orcid' && !validateOrcid($clean_input)) {
                $error_message = 'The ORCID ID is invalid. The correct format is: 0000-0000-0000-0000 (with a valid checksum)';
            } else {
                // DOI: proses via PHP (cepat, tidak timeout)
                if ($detected_type === 'doi') {
                    $api_url = $API_BASE_URL . '?doi=' . urlencode($clean_input);
                    if (isset($_POST['force_refresh'])) $api_url .= '&refresh=true';
                    $api_response = makeApiRequest($api_url);
                    if (isset($api_response['error'])) {
                        $error_message = 'API error: ' . $api_response['error'];
                    } else {
                        $analysis_result = $api_response['data'];
                        if (!is_array($analysis_result)) { $error_message = 'Response API is invalid (not an array)'; $analysis_result = null; }
                        elseif (!isset($analysis_result['status'])) { $error_message = 'The API response does not contain a status'; $analysis_result = null; }
                        elseif ($analysis_result['status'] !== 'success') { $error_message = 'API returns error status: ' . (isset($analysis_result['message']) ? $analysis_result['message'] : 'Unknown error'); $analysis_result = null; }
                    }
                }
                // ORCID: JS yang handle via AJAX – PHP tidak proses di sini
            }
        }
    } else {
        $error_message = 'Please enter a valid ORCID ID or DOI';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SDGs Classification Analysis | Wizdam AI-sikola</title>
    <meta name="description" content="This system uses a hybrid method combining keyword matching, semantic similarity, research depth, and causal analysis to assess research relevance to the SDGs." />
    <meta name="owner" content="PT. Sangia Research Media and Publishing" />
    <meta name="design" content="Rochmady and Wizdam AI Team" />
    <meta name="generator" content="Wizdam AI v5.1.8" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        /* ============================================================
           CSS INI TIDAK DIUBAH DARI v2.3 – DOKUMEN 5 ORIGINAL
           ============================================================ */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Roboto", "Oxygen-Sans", "Ubuntu", "Cantarell", "Helvetica Neue", 'Inter', system-ui, sans-serif; background: #f8f9fa; min-height: 100vh; color: #333; padding-top: 80px; }
        .navbar { position: fixed; top: 0; left: 0; right: 0; background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border-bottom: 1px solid rgba(0,0,0,0.1); z-index: 1000; transition: all 0.3s ease; }
        .navbar.scrolled { background: rgba(255,255,255,0.98); box-shadow: 0 2px 20px rgba(0,0,0,0.1); }
        .navbar-container { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; padding: 15px 20px; }
        .navbar-brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: #333; font-weight: 700; font-size: 1.5rem; }
        .navbar-brand-logo { width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; }
        .navbar-menu { display: flex; align-items: center; gap: 30px; list-style: none; }
        .navbar-menu a { text-decoration: none; color: #666; font-weight: 500; transition: all 0.3s ease; position: relative; padding: 8px 0; }
        .navbar-menu a:hover, .navbar-menu a.active { color: #667eea; }
        .navbar-menu a::after { content: ''; position: absolute; bottom: 0; left: 0; width: 0; height: 2px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); transition: width 0.3s ease; }
        .navbar-menu a:hover::after, .navbar-menu a.active::after { width: 100%; }
        .navbar-toggle { display: none; flex-direction: column; cursor: pointer; padding: 5px; }
        .navbar-toggle span { width: 25px; height: 3px; background: #333; margin: 3px 0; transition: 0.3s; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 27px; color: white; padding: 70px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); position: relative; overflow: hidden; }
        .header::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>'); opacity: 0.3; }
        .header h1 { font-size: 2.5rem; font-weight: 700; margin-bottom: 15px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); position: relative; z-index: 2; }
        .header p { font-size: 1.2rem; opacity: 0.9; max-width: 600px; margin: 0 auto; position: relative; z-index: 2; }
        .search-card { background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); transition: all 0.3s ease; max-width: 900px; margin-left: auto; margin-right: auto; }
        .search-card:hover { box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
        .form-horizontal { display: flex; gap: 15px; align-items: stretch; }
        .input-section { flex: 1; min-width: 0; }
        .button-section { flex-shrink: 0; }
        .input-group { position: relative; margin-bottom: 15px; }
        .floating-input { width: 100%; padding: 15px 20px; border: 2px solid #e1e5e9; border-radius: 12px; font-size: 16px; outline: none; transition: all 0.3s ease; }
        .floating-input:focus { border-color: #667eea; box-shadow: 0 8px 25px rgba(102,126,234,0.15); }
        .floating-label { position: absolute; left: 20px; top: 50%; transform: translateY(-50%); background: white; padding: 0 8px; color: #666; font-size: 16px; transition: all 0.3s ease; pointer-events: none; }
        .floating-input:focus + .floating-label, .floating-input:not(:placeholder-shown) + .floating-label { top: 0; font-size: 12px; color: #667eea; font-weight: 600; }
        .floating-input.input-valid + .floating-label { color: #28a745 !important; }
        .floating-input.input-invalid + .floating-label { color: #dc3545 !important; }
        .floating-input.input-warning + .floating-label { color: #ffc107 !important; }
        .floating-input.input-checking + .floating-label { color: #17a2b8 !important; }
        .floating-input.input-valid { border-color: #28a745 !important; box-shadow: 0 0 0 0.2rem rgba(40,167,69,0.25); }
        .floating-input.input-invalid { border-color: #dc3545 !important; box-shadow: 0 0 0 0.2rem rgba(220,53,69,0.25); }
        .floating-input.input-warning { border-color: #ffc107 !important; box-shadow: 0 0 0 0.2rem rgba(255,193,7,0.25); }
        .floating-input.input-checking { border-color: #17a2b8 !important; box-shadow: 0 0 0 0.2rem rgba(23,162,184,0.25); }
        .input-hint { position: absolute; right: 20px; top: 50%; transform: translateY(-50%); color: #999; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 5px; transition: all 0.3s ease; pointer-events: none; }
        .floating-input:focus + .floating-label + .input-hint, .floating-input:not(:placeholder-shown) + .floating-label + .input-hint { opacity: 0; transform: translateY(-50%) scale(0.8); }
        .input-status { margin: 0; padding: 12px 16px; border-radius: 8px; background: #f8f9fa; border: 1px solid #e9ecef; transition: all 0.3s ease; }
        .status-indicator { display: flex; align-items: center; gap: 8px; font-size: 14px; }
        .input-status.detecting { background: #fff3cd; border-color: #ffeaa7; }
        .input-status.detecting .status-indicator { color: #856404; }
        .input-status.orcid-detected { background: #d4edda; border-color: #c3e6cb; }
        .input-status.orcid-detected .status-indicator { color: #155724; }
        .input-status.doi-detected { background: #d1ecf1; border-color: #bee5eb; }
        .input-status.doi-detected .status-indicator { color: #0c5460; }
        .input-status.invalid { background: #f8d7da; border-color: #f5c6cb; }
        .input-status.invalid .status-indicator { color: #721c24; }
        .refresh-checkbox { display: flex; align-items: center; gap: 10px; margin-top: 15px; font-size: 14px; color: #666; }
        .submit-btn { padding: 15px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; white-space: nowrap; height: 54px; }
        .submit-btn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102,126,234,0.3); }
        .submit-btn:disabled { opacity: 0.7; cursor: not-allowed; }
        .error-message { background: #fee; color: #c33; padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #fcc; }
        .debug-info { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; margin-bottom: 20px; font-family: monospace; font-size: 12px; }
        .u-heading3 { margin-bottom: 17px; }
        .info-general { background: white; border-radius: 20px; padding: 30px; margin-bottom: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); transition: all 0.3s ease; }
        .info-general:hover { transform: translateY(-2px); box-shadow: 0 20px 60px rgba(0,0,0,0.12); }
        .personal-info { display: flex; align-items: center; gap: 20px; margin-bottom: 30px; padding: 20px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 15px; }
        .avatar { width: 80px; height: 80px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 32px; font-weight: bold; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; }
        .stat-card { text-align: center; padding: 20px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 15px; transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .stat-number { font-size: 2rem; font-weight: 700; color: #667eea; }
        .stat-label { font-size: 14px; color: #666; margin-top: 5px; }
        .sdg-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .sdg-card { background: white; padding: 20px; display: flex; align-items: center; gap: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); transition: all 0.3s ease; cursor: pointer; border: 1px solid #f1f3f4; position: relative; overflow: hidden; border-radius: 15px; }
        .sdg-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.15); }
        .sdg-card::after { content: ''; position: absolute; top: 0; right: 0; bottom: 0; width: 6px; }
        .sdg-icon { flex-shrink: 0; background: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1); position: relative; z-index: 2; }
        .sdg-icon img { width: 127px; height: 127px; object-fit: contain; }
        .sdg-content { flex: 1; color: #333; }
        .sdg-title { font-weight: 700; font-size: 18px; margin-bottom: 12px; color: #333; line-height: 1.3; }
        .sdg-stats { display: flex; gap: 25px; margin-bottom: 15px; }
        .sdg-stat-item { text-align: left; }
        .sdg-stat-label { font-size: 12px; color: #666; margin-bottom: 4px; font-weight: 500; }
        .sdg-stat-value { font-size: 16px; font-weight: 700; color: #333; }
        .confidence-bar { background: #e9ecef; height: 8px; border-radius: 4px; overflow: hidden; margin-bottom: 12px; position: relative; }
        .confidence-fill { height: 100%; border-radius: 4px; transition: width 0.8s ease; position: relative; }
        .contributor-type { display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 14px; font-weight: 600; background: #f8f9fa; color: #495057; border: 1px solid #dee2e6; }
        .charts-section { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 30px; }
        .chart-container { background: white; padding: 20px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); overflow: hidden; transition: all 0.3s ease; }
        .chart-container:hover { box-shadow: 0 10px 40px rgba(210,217,249,0.3); }
        .chart-container h4 { margin-bottom: 15px; color: #333; font-weight: 600; }
        .chart-container canvas { max-height: 250px !important; }
        .works-container { margin-top: 30px; }
        .work-item { background: white; border-radius: 17px; padding: 27px; margin-bottom: 25px; border: 2px solid #f1f3f4; transition: all 0.3s ease; }
        .work-item:hover { box-shadow: 0 5px 25px rgba(165,179,243,0.2); border-color: rgba(102,126,234,0.2); }
        .work-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; gap: 20px; }
        .work-title { font-weight: 700; color: #222; line-height: 1.4; flex: 1; font-size: 17px; }
        .work-year { background: #667eea; color: white; padding: 6px 12px; border-radius: 17px; font-size: 12px; font-weight: 600; text-align: center; flex-shrink: 0; }
        .work-meta { display: flex; flex-direction: column; gap: 8px; font-size: 14px; font-weight: 600; color: #666; margin-bottom: 15px; }
        .work-meta-row { display: flex; align-items: center; gap: 8px; }
        .work-meta-row i { width: 16px; color: #667eea; }
        .work-abstract { font-size: 14px; font-weight: 600; color: #222; line-height: 1.5; margin-bottom: 15px; padding: 15px 0; }
        .work-sdgs-section { border-top: 1px solid #e9ecef; padding-top: 15px; }
        .work-sdgs-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; min-height: 32px; }
        .work-sdgs-title { font-size: 14px; font-weight: 600; color: #333; display: flex; align-items: center; gap: 8px; }
        .work-sdgs { display: flex; gap: 10px; flex-wrap: wrap; }
        .work-sdg-tag { display: flex; align-items: center; gap: 8px; padding-right: 7px; border-radius: 0 10px 10px 0; font-size: 15px; font-weight: 600; color: white; position: relative; }
        .work-sdg-tag .sdg-mini-icon img { width: 20px; height: 20px; }
        .sdg-confidence-info { font-size: 14px; opacity: 0.9; margin-left: 4px; }
        .show-more-btn { border: 2px solid #667eea; background: transparent; color: #667eea; padding: 8px 16px; border-radius: 17px; font-size: 12px; cursor: pointer; transition: all 0.3s ease; font-weight: 600; display: flex; align-items: center; gap: 6px; }
        .show-more-btn:hover { background: #667eea; color: white; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(102,126,234,0.3); }
        .show-more-btn .fa-solid, .show-more-btn .fas { font-size: 14px; }
        .detailed-analysis { margin-top: 15px; padding: 20px; background: #f8f9fa; border-radius: 10px; border: 1px solid #e9ecef; display: none; }
        .detailed-analysis.show { display: block; animation: slideDown 0.3s ease; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .analysis-section { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #e9ecef; }
        .analysis-section:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .analysis-section h5 { color: #333; margin-bottom: 12px; font-size: 15px; font-weight: 600; }
        .analysis-components { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 12px; margin-bottom: 15px; }
        .component-score { background: white; padding: 12px; border-radius: 8px; text-align: center; border: 1px solid #e1e5e9; transition: transform 0.2s ease; }
        .component-score:hover { transform: translateY(-1px); }
        .component-label { font-size: 11px; color: #666; margin-bottom: 6px; font-weight: 500; }
        .component-value { font-size: 17px; font-weight: 700; color: #333; }
        .analysis-info { margin: 15px 0; }
        .info-badge { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; background: #667eea; color: white; margin-right: 8px; }
        .info-badge.confidence { background: #28a745; }
        .evidence-section { margin-top: 15px; }
        .evidence-title { font-weight: 600; font-size: 14px; color: #333; margin-bottom: 10px; }
        .evidence-item { font-size: 16px; line-height: 1.5; }
        .evidence-item strong { border-bottom: 4px solid #667eea; }
        .loading-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.95); z-index: 9999; display: none; align-items: center; justify-content: center; flex-direction: column; }
        .spinner { width: 50px; height: 50px; border: 4px solid #f3f3f3; border-top: 4px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 20px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .loading-text { font-weight: 600; color: #667eea; font-size: 18px; margin-bottom: 10px; }
        .loading-subtext { color: #666; font-size: 14px; }
        .none-SDG { text-align: center; color: #666; font-style: italic; padding: 17px; background: #f8f9fa; border-radius: 17px; font-size: 14px; }
        /* ── AJAX Progress (TAMBAHAN BARU) ── */
        #ajaxProgressSection { display:none; background:white; border-radius:20px; padding:25px; margin-bottom:20px; box-shadow:0 10px 30px rgba(0,0,0,.1); max-width:900px; margin-left:auto; margin-right:auto; }
        .ajax-progress-header { display:flex; align-items:center; gap:15px; margin-bottom:15px; }
        .ajax-spinner { width:38px; height:38px; border:4px solid #e9ecef; border-top-color:#667eea; border-radius:50%; animation:spin 1s linear infinite; flex-shrink:0; }
        .ajax-progress-text h3 { font-size:1.05rem; color:#333; margin-bottom:3px; }
        .ajax-progress-text p { font-size:13px; color:#888; }
        .ajax-progress-bar-wrap { background:#e9ecef; border-radius:10px; height:10px; overflow:hidden; margin-bottom:10px; }
        .ajax-progress-bar-fill { height:100%; background:linear-gradient(90deg,#667eea,#764ba2); border-radius:10px; transition:width .5s ease; width:0; }
        .ajax-progress-stats { font-size:13px; color:#888; }
        .ajax-progress-stats span { font-weight:600; color:#333; }
        /* ── ORCID AJAX Results (TAMBAHAN BARU) ── */
        #ajaxResultsSection { display:none; }
        /* Footer */
        .footer { color: white; margin-top: 60px; position: relative; overflow: hidden; background-color: #34495e; background-image: url("//assets.sangia.org/img/SDGs_icon_SVG/border-sdga.svg"); background-position: top center; background-repeat: no-repeat; }
        .footer::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="footergrid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23footergrid)"/></svg>'); }
        .footer-container { max-width: 1200px; margin: 0 auto; padding: 50px 20px 30px; position: relative; z-index: 2; }
        .footer-main { display: grid; grid-template-columns: repeat(5, 1fr); gap: 40px; margin-bottom: 40px; }
        .footer-brand { grid-column: span 2; }
        .footer-section { grid-column: span 1; }
        .footer-logo { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
        .footer-logo-icon { width: 50px; height: 50px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; }
        .footer-logo-text { font-size: 1.5rem; font-weight: 700; color: white; }
        .footer-description { color: #bdc3c7; line-height: 1.6; margin-bottom: 20px; }
        .footer-section h4 { color: white; font-size: 1.1rem; font-weight: 600; margin-bottom: 20px; position: relative; }
        .footer-section h4::after { content: ''; position: absolute; bottom: -8px; left: 0; width: 30px; height: 2px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .footer-links { list-style: none; }
        .footer-links li { margin-bottom: 10px; }
        .footer-links a { color: #bdc3c7; text-decoration: none; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px; }
        .footer-links a:hover { color: #667eea; transform: translateX(5px); }
        .footer-links a i { width: 16px; color: #667eea; }
        .footer-social { display: flex; gap: 15px; margin-top: 20px; }
        .footer-social a { width: 40px; height: 40px; background: rgba(255,255,255,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; transition: all 0.3s ease; }
        .footer-social a:hover { background: #667eea; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.4); }
        .footer-bottom { border-top: 1px solid rgba(255,255,255,0.1); padding-top: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .footer-copyright { color: #bdc3c7; font-size: 14px; }
        .footer-bottom-links { display: flex; gap: 30px; list-style: none; }
        .footer-bottom-links a { color: #bdc3c7; text-decoration: none; font-size: 14px; transition: color 0.3s ease; }
        .footer-bottom-links a:hover { color: #667eea; }
        .floating-elements { position: fixed; bottom: 30px; right: 30px; display: flex; flex-direction: column; gap: 15px; z-index: 1000; }
        .floating-btn { width: 40px; height: 40px; border-radius: 50%; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 20px; transition: all 0.3s ease; box-shadow: 0 4px 20px rgba(0,0,0,0.15); }
        .floating-btn:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(0,0,0,0.2); }
        .back-to-top { background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: white; opacity: 0; visibility: hidden; border-radius: 6px; }
        .back-to-top.show { opacity: 1; visibility: visible; }
        .chatbot-btn { background: linear-gradient(135deg, #667eea 0%, #667eea 100%); color: white; position: relative; width: 50px; height: 50px; right: 5px; font-size: 25px; }
        .chatbot-btn::after { content: ''; position: absolute; top: -2px; right: -2px; width: 12px; height: 12px; background: #ff4757; border-radius: 50%; border: 2px solid white; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.2); opacity: 0.7; } 100% { transform: scale(1); opacity: 1; } }
        .chatbot-modal { position: fixed; bottom: 90px; right: 20px; width: 400px; height: 600px; background: white; border-radius: 17px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); display: none; flex-direction: column; z-index: 1001; overflow: hidden; }
        .chatbot-modal.show { display: flex; animation: slideInUp 0.3s ease; }
        @keyframes slideInUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .chatbot-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; }
        .chatbot-header h4 { margin: 0; font-size: 1.27rem; }
        .chatbot-close { width: 40px; height: 40px; background: none; border: none; color: white; font-size: 20px; cursor: pointer; padding: 5px; border-radius: 50%; transition: background 0.3s ease; }
        .chatbot-close:hover { background: rgba(255,255,255,0.2); }
        .chatbot-body { flex: 1; padding: 20px; overflow-y: auto; background: rgb(255,255,255); }
        .chatbot-message { margin-bottom: 15px; display: flex; gap: 10px; }
        .chatbot-message.bot { justify-content: flex-start; }
        .chatbot-message.user { justify-content: flex-end; }
        .chatbot-message-content { padding: 12px 16px; border-radius: 18px; font-size: 14px; line-height: 1.4; }
        .chatbot-message.bot .chatbot-message-content { background: rgb(245,245,245); color: #333; border-top-left-radius: 6px; }
        .chatbot-message.user .chatbot-message-content { background: rgb(35,146,236); color: white; border-bottom-right-radius: 6px; }
        .chatbot-avatar { width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #667eea 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 17px; flex-shrink: 0; }
        .chatbot-input-area { padding: 20px; border-top: 1px solid #e9ecef; background: white; }
        .chatbot-input-group { display: flex; gap: 10px; }
        .chatbot-input { flex: 1; padding: 12px 16px; border: 2px solid #e9ecef; border-radius: 25px; outline: none; font-size: 14px; transition: border-color 0.3s ease; }
        .chatbot-input:focus { border-color: #28a745; }
        .chatbot-send { width: 45px; height: 45px; font-size: 20px; border: none; border-radius: 50%; background: rgb(35,146,236); color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; }
        .chatbot-send:hover { transform: scale(1.1); }
        .chatbot-typing { display: none; align-items: center; gap: 5px; padding: 12px 16px; background: white; border-radius: 18px; border-bottom-left-radius: 6px; max-width: 80px; }
        .chatbot-typing.show { display: flex; }
        .typing-dot { width: 6px; height: 6px; background: #999; border-radius: 50%; animation: typing 1.4s infinite; }
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes typing { 0%, 60%, 100% { transform: translateY(0); } 30% { transform: translateY(-10px); } }
        @media (max-width: 768px) {
            body { padding-top: 70px; }
            .navbar-container { padding: 12px 20px; }
            .navbar-brand { font-size: 1.3rem; }
            .navbar-brand-logo { width: 35px; height: 35px; font-size: 16px; }
            .navbar-menu { position: fixed; top: 70px; left: -100%; width: 100%; height: calc(100vh - 70px); background: rgba(255,255,255,0.98); backdrop-filter: blur(10px); flex-direction: column; justify-content: flex-start; align-items: center; gap: 30px; padding-top: 50px; transition: left 0.3s ease; }
            .navbar-menu.show { left: 0; }
            .navbar-menu a { font-size: 1.1rem; padding: 15px 0; }
            .navbar-toggle { display: flex; }
            .navbar-toggle.active span:nth-child(1) { transform: rotate(-45deg) translate(-5px, 6px); }
            .navbar-toggle.active span:nth-child(2) { opacity: 0; }
            .navbar-toggle.active span:nth-child(3) { transform: rotate(45deg) translate(-5px, -6px); }
            .header h1 { font-size: 2rem; }
            .header p { font-size: 1rem; }
            .form-horizontal { flex-direction: column; gap: 15px; margin-bottom: 16px; }
            .input-group { margin-bottom: 0; }
            .submit-btn { width: 100%; height: auto; }
            .charts-section { grid-template-columns: 1fr; }
            .work-header { flex-direction: column; gap: 10px; }
            .work-sdgs-header { flex-direction: column; align-items: flex-start; gap: 10px; min-height: auto; }
            .analysis-components { grid-template-columns: repeat(2, 1fr); }
            .personal-info { flex-direction: column; text-align: center; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .sdg-grid { grid-template-columns: 1fr; }
            .sdg-card { flex-direction: column; text-align: center; gap: 15px; }
            .sdg-icon img { width: 100px; height: 100px; }
            .sdg-stats { justify-content: center; }
            .footer-main { grid-template-columns: 1fr; gap: 30px; }
            .footer-brand { grid-column: span 1; text-align: center; }
            .footer-bottom { flex-direction: column; text-align: center; }
            .floating-elements { bottom: 20px; right: 20px; }
            .floating-btn { width: 50px; height: 50px; font-size: 18px; }
            .chatbot-modal { width: 90%; right: 5%; bottom: 90px; height: 70vh; }
        }
        @media (max-width: 630px) { .input-hint { display: none; } }
        @media (max-width: 480px) { .container { padding: 15px; } .search-card { padding: 25px; } .header { padding: 40px 15px; } .work-item { padding: 20px; } .chatbot-modal { height: 60vh; } }
    </style>
</head>
<body>
    <!-- NAVBAR – tidak diubah dari v2.3 -->
    <nav class="navbar" id="navbar">
        <div class="navbar-container">
            <a href="#" class="navbar-brand">
                <div class="navbar-brand-logo"><i class="fas fa-globe"></i></div>
                <span>Wizdam</span>
            </a>
            <ul class="navbar-menu" id="navbarMenu">
                <li><a href="#" class="active"><i class="fas fa-search"></i> Search</a></li>
                <li><a href="#"><i class="fas fa-th"></i> Apps</a></li>
                <li><a href="#"><i class="fas fa-info-circle"></i> About</a></li>
                <li><a href="#"><i class="fas fa-users"></i> Teams</a></li>
                <li><a href="#"><i class="fas fa-archive"></i> Archive</a></li>
                <li><a href="#"><i class="fas fa-question-circle"></i> Help</a></li>
            </ul>
            <div class="navbar-toggle" id="navbarToggle">
                <span></span><span></span><span></span>
            </div>
        </div>
    </nav>

    <!-- HEADER – tidak diubah dari v2.3 -->
    <div class="header">
        <h1><i class="fas fa-globe"></i> Welcome! Wizdam AI-sikola</h1>
        <h2>Sustainable Development Goals (SDGs) Classification</h2>
        <p>Analysis of research contributions to Sustainable Development Goals using advanced AI classification</p>
    </div>

    <div class="container-search">
        <div class="search-card">
            <!-- Form tidak diubah dari v2.3 -->
            <form method="POST" id="analysisForm">
                <div class="form-horizontal">
                    <div class="input-section">
                        <div class="input-group">
                            <input type="text" class="floating-input" name="input_value" id="input_value" placeholder=" " required value="<?php echo htmlspecialchars(isset($_POST['input_value']) ? $_POST['input_value'] : ''); ?>">
                            <label class="floating-label" id="input_label">Enter your ORCID or DOI</label>
                            <div class="input-hint" id="input_hint">
                                <i class="fas fa-info-circle"></i>
                                <span id="hint_text">Example: 0000-0002-5152-9727 or 10.1038/nature12373</span>
                            </div>
                        </div>
                    </div>
                    <div class="button-section">
                        <button type="submit" class="submit-btn" id="submitBtn">
                            <i class="fas fa-search"></i> Analysis
                        </button>
                    </div>
                </div>
                <div class="input-status" id="input_status">
                    <div class="status-indicator" id="status_indicator">
                        <i class="fas fa-question-circle"></i>
                        <span id="status_text">Enter your ORCID or DOI to begin the analysis</span>
                    </div>
                </div>
                <div class="refresh-checkbox">
                    <input type="checkbox" name="force_refresh" value="1" id="force_refresh" <?php echo isset($_POST['force_refresh']) ? 'checked' : ''; ?>>
                    <label for="force_refresh">Force refresh (bypass cache)</label>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================
         TAMBAHAN BARU: AJAX Progress Section (untuk ORCID)
         Tersembunyi secara default, muncul saat ORCID AJAX berjalan
         ============================================================ -->
    <div id="ajaxProgressSection">
        <div class="ajax-progress-header">
            <div class="ajax-spinner"></div>
            <div class="ajax-progress-text">
                <h3 id="ajaxProgressTitle">Start ORCID analize...</h3>
                <p id="ajaxProgressSubtitle">Please wait, the process is running in stages</p>
            </div>
        </div>
        <div class="ajax-progress-bar-wrap">
            <div class="ajax-progress-bar-fill" id="ajaxProgressBar"></div>
        </div>
        <div class="ajax-progress-stats">
            Works processing: <span id="ajaxProgressCount">0</span> / <span id="ajaxProgressTotal">?</span>
            &nbsp;|&nbsp; Batch: <span id="ajaxProgressBatch">0</span>
        </div>
    </div>

    <!-- ============================================================
         TAMBAHAN BARU: AJAX Results Section (untuk ORCID)
         Diisi oleh JavaScript saat ORCID AJAX selesai
         ============================================================ -->
    <div class="container" id="ajaxResultsSection"></div>

    <?php if ($error_message || $analysis_result): ?>
    <!-- ============================================================
         PHP Results Section – tidak diubah dari v2.3
         (Digunakan untuk DOI dan error messages)
         ============================================================ -->
    <div class="container">

        <?php if ($detected_input_type && $clean_input_value): ?>
            <div class="debug-info">
                <strong>Debug Info:</strong><br>
                Input Type Detected: <?php echo htmlspecialchars($detected_input_type); ?><br>
                Cleaned Input: <?php echo htmlspecialchars($clean_input_value); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($analysis_result): ?>
            <div class="results-card" id="resultsSection">

                <?php if (isset($analysis_result['personal_info'])): ?>
                <div class="info-general animate-in">
                    <div class="personal-info">
                        <div class="avatar"><?php echo strtoupper(substr($analysis_result['personal_info']['name'], 0, 2)); ?></div>
                        <div>
                            <h2><?php echo htmlspecialchars($analysis_result['personal_info']['name']); ?></h2>
                            <p><i class="fab fa-orcid"></i> <?php echo htmlspecialchars($analysis_result['personal_info']['orcid']); ?></p>
                            <?php if (!empty($analysis_result['personal_info']['institutions'])): ?>
                                <p><i class="fas fa-university"></i>
                                    <?php echo htmlspecialchars(implode(', ', array_slice($analysis_result['personal_info']['institutions'], 0, 2))); ?>
                                    <?php if (count($analysis_result['personal_info']['institutions']) > 2): ?> et al.<?php endif; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-card"><div class="stat-number"><?php echo count(isset($analysis_result['works']) ? $analysis_result['works'] : array()); ?></div><div class="stat-label">Total Works</div></div>
                        <div class="stat-card"><div class="stat-number"><?php echo count(isset($analysis_result['researcher_sdg_summary']) ? $analysis_result['researcher_sdg_summary'] : array()); ?></div><div class="stat-label">Identified SDGs</div></div>
                        <div class="stat-card"><div class="stat-number"><?php
                            $activeCount = 0;
                            if (isset($analysis_result['contributor_profile'])) {
                                foreach ($analysis_result['contributor_profile'] as $profile) {
                                    if (isset($profile['dominant_type']) && $profile['dominant_type'] === 'Active Contributor') $activeCount++;
                                }
                            }
                            echo $activeCount;
                        ?></div><div class="stat-label">Active Contribution</div></div>
                        <div class="stat-card"><div class="stat-number"><?php
                            $avgConfidence = 0;
                            if (isset($analysis_result['researcher_sdg_summary'])) {
                                $totalConfidence = 0; $count = 0;
                                foreach ($analysis_result['researcher_sdg_summary'] as $summary) {
                                    if (isset($summary['average_confidence'])) { $totalConfidence += $summary['average_confidence']; $count++; }
                                }
                                $avgConfidence = $count > 0 ? round(($totalConfidence / $count) * 100) : 0;
                            }
                            echo $avgConfidence . '%';
                        ?></div><div class="stat-label">Average Confidence</div></div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (isset($analysis_result['researcher_sdg_summary']) && !empty($analysis_result['researcher_sdg_summary'])): ?>
                    <h3 class="u-heading3"><i class="fas fa-chart-pie"></i> Summary of SDG Contributions</h3>
                    <div class="sdg-grid">
                        <?php foreach ($analysis_result['researcher_sdg_summary'] as $sdg => $summary): ?>
                            <?php $sdg_info = isset($SDG_DEFINITIONS[$sdg]) ? $SDG_DEFINITIONS[$sdg] : array('title'=>$sdg,'color'=>'#666','svg_url'=>''); ?>
                            <div class="sdg-card">
                                <div class="sdg-icon"><img src="<?php echo $sdg_info['svg_url']; ?>" alt="<?php echo $sdg_info['title']; ?>"></div>
                                <div class="sdg-content">
                                    <div class="sdg-title"><?php echo $sdg_info['title']; ?></div>
                                    <div class="sdg-stats">
                                        <div class="sdg-stat-item"><div class="sdg-stat-label">Number of Works</div><div class="sdg-stat-value"><?php echo $summary['work_count']; ?> works</div></div>
                                        <div class="sdg-stat-item"><div class="sdg-stat-label">Confidence</div><div class="sdg-stat-value"><?php echo round($summary['average_confidence'] * 100, 1); ?>%</div></div>
                                    </div>
                                    <div class="confidence-bar"><div class="confidence-fill" style="width:<?php echo ($summary['average_confidence'] * 100); ?>%;background:<?php echo $sdg_info['color']; ?>;"></div></div>
                                    <?php if (isset($analysis_result['contributor_profile'][$sdg])): ?>
                                        <div class="contributor-type"><?php echo $analysis_result['contributor_profile'][$sdg]['dominant_type']; ?></div>
                                    <?php endif; ?>
                                </div>
                                <style>.sdg-card:nth-of-type(<?php echo array_search($sdg, array_keys($analysis_result['researcher_sdg_summary'])) + 1; ?>)::after{background:<?php echo $sdg_info['color']; ?>;}</style>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="charts-section">
                        <div class="chart-container"><h4><i class="fas fa-chart-pie"></i> SDG distribution</h4><canvas id="sdgChart"></canvas></div>
                        <div class="chart-container"><h4><i class="fas fa-chart-bar"></i> Contributor Type</h4><canvas id="contributorChart"></canvas></div>
                    </div>
                <?php endif; ?>

                <?php if (isset($analysis_result['doi']) && !isset($analysis_result['personal_info'])): ?>
                    <h3 class="u-heading3"><i class="fas fa-file-alt"></i> Article Analysis</h3>
                    <?php $year = isset($analysis_result['published_date']) ? date('Y', strtotime($analysis_result['published_date'])) : '2024'; ?>
                    <div class="work-item">
                        <div class="work-header">
                            <div class="work-title"><?php echo renderSafeHtmlConservative(isset($analysis_result['title']) ? $analysis_result['title'] : ''); ?></div>
                            <div class="work-year"><?php echo $year; ?></div>
                        </div>
                        <div class="work-meta">
                            <div class="work-meta-row"><i class="fas fa-link"></i><span>DOI: <a href="https://doi.org/<?php echo htmlspecialchars($analysis_result['doi']); ?>"><?php echo htmlspecialchars($analysis_result['doi']); ?></a></span></div>
                            <?php if (!empty($analysis_result['authors'])): ?>
                                <div class="work-meta-row">
                                    <?php $count = count($analysis_result['authors']); $icon = $count==1?'fa-user':($count==2?'fa-user-group':'fa-users'); ?>
                                    <i class="fas <?php echo $icon; ?>"></i>
                                    <span><?php echo htmlspecialchars(implode(', ', array_slice($analysis_result['authors'], 0, 17))); ?><?php if (count($analysis_result['authors']) > 17): ?><i> et al.</i><?php endif; ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($analysis_result['journal'])): ?><div class="work-meta-row"><i class="fas fa-book"></i><span><?php echo htmlspecialchars($analysis_result['journal']); ?></span></div><?php endif; ?>
                            <div class="work-meta-row"><i class="fas fa-chart-line"></i><span><?php echo isset($analysis_result['sdgs']) ? count($analysis_result['sdgs']) : 0; ?> Identified SDGs</span></div>
                        </div>
                        <?php if (!empty($analysis_result['abstract'])): ?>
                            <div class="work-abstract"><strong>Abstract:</strong> <?php echo renderSafeHtmlConservative($analysis_result['abstract']); ?></div>
                        <?php endif; ?>
                        <div class="work-sdgs-section">
                            <?php if (!empty($analysis_result['sdgs'])): ?>
                                <div style="display:flex;justify-content:space-between;align-items:center;">
                                    <div class="work-sdgs">
                                        <?php foreach ($analysis_result['sdgs'] as $sdg): ?>
                                            <?php $sdg_info = isset($SDG_DEFINITIONS[$sdg]) ? $SDG_DEFINITIONS[$sdg] : array('color'=>'#666','title'=>$sdg,'svg_url'=>''); $confidence = isset($analysis_result['sdg_confidence'][$sdg]) ? $analysis_result['sdg_confidence'][$sdg] : 0; ?>
                                            <div class="work-sdg-tag" style="background:<?php echo $sdg_info['color']; ?>">
                                                <div class="sdg-mini-icon"><img src="<?php echo $sdg_info['svg_url']; ?>" alt="<?php echo $sdg_info['title']; ?>" width="20" height="20"></div>
                                                <span><?php echo $sdg; ?><span class="sdg-confidence-info">(<?php echo round($confidence * 100, 1); ?>%)</span></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button class="show-more-btn" onclick="toggleAnalysis(0)"><i class="fas fa-chart-bar"></i> Show Details</button>
                                </div>
                                <?php echo renderDetailedAnalysis($analysis_result, $SDG_DEFINITIONS, 0); ?>
                            <?php else: ?>
                                <div class="none-SDG"><i class="fas fa-info-circle"></i> No SDGs were identified for this.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($analysis_result['works']) && !empty($analysis_result['works'])): ?>
                    <div class="works-container">
                        <h3 class="u-heading3"><i class="fas fa-file-alt"></i> Publications (<?php echo count($analysis_result['works']); ?> work)</h3>
                        <?php foreach ($analysis_result['works'] as $index => $work): ?>
                            <?php $year = isset($work['year']) ? $work['year'] : '2024'; ?>
                            <div class="work-item">
                                <div class="work-header">
                                    <div class="work-title"><?php echo renderSafeHtmlConservative(isset($work['title']) ? $work['title'] : ''); ?></div>
                                    <div class="work-year"><?php echo $year; ?></div>
                                </div>
                                <div class="work-meta">
                                    <?php if (!empty($work['doi'])): ?><div class="work-meta-row"><i class="fas fa-link"></i><span>DOI: <a href="https://doi.org/<?php echo htmlspecialchars($work['doi']); ?>"><?php echo htmlspecialchars($work['doi']); ?></a></span></div><?php endif; ?>
                                    <?php if (isset($work['journal'])): ?><div class="work-meta-row"><i class="fas fa-book"></i><span><?php echo htmlspecialchars($work['journal']); ?></span></div><?php endif; ?>
                                    <div class="work-meta-row"><i class="fas fa-chart-line"></i><span><?php echo isset($work['sdgs']) ? count($work['sdgs']) : 0; ?> Identified SDGs</span></div>
                                </div>
                                <?php if (isset($work['abstract']) && !empty($work['abstract'])): ?>
                                    <div class="work-abstract"><strong>Abstract:</strong> <?php echo renderSafeHtmlConservative($work['abstract']); ?></div>
                                <?php endif; ?>
                                <div class="work-sdgs-section">
                                    <?php if (!empty($work['sdgs'])): ?>
                                        <div style="display:flex;justify-content:space-between;align-items:center;">
                                            <div class="work-sdgs">
                                                <?php foreach ($work['sdgs'] as $sdg): ?>
                                                    <?php $sdg_info = isset($SDG_DEFINITIONS[$sdg]) ? $SDG_DEFINITIONS[$sdg] : array('color'=>'#666','title'=>$sdg,'svg_url'=>''); $confidence = isset($work['sdg_confidence'][$sdg]) ? $work['sdg_confidence'][$sdg] : 0; ?>
                                                    <div class="work-sdg-tag" style="background:<?php echo $sdg_info['color']; ?>">
                                                        <div class="sdg-mini-icon"><img src="<?php echo $sdg_info['svg_url']; ?>" alt="<?php echo $sdg_info['title']; ?>" width="20" height="20"></div>
                                                        <span><?php echo $sdg; ?><span class="sdg-confidence-info">(<?php echo round($confidence * 100, 1); ?>%)</span></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <button class="show-more-btn" onclick="toggleAnalysis(<?php echo $index + 1; ?>)"><i class="fas fa-chart-bar"></i> Show Details</button>
                                        </div>
                                        <?php echo renderDetailedAnalysis($work, $SDG_DEFINITIONS, $index + 1); ?>
                                    <?php else: ?>
                                        <div class="none-SDG"><i class="fas fa-info-circle"></i> No SDGs were identified for this.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- FOOTER – tidak diubah dari v2.3 -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-main">
                <div class="footer-brand">
                    <div class="footer-logo">
                        <div class="footer-logo-icon"><i class="fas fa-globe"></i></div>
                        <div class="footer-logo-text">Wizdam</div>
                    </div>
                    <p class="footer-description">Advanced AI-powered platform for analyzing research contributions to Sustainable Development Goals. Empowering researchers and institutions with intelligent classification and insights.</p>
                    <div class="footer-social">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h4>Platform</h4>
                    <ul class="footer-links">
                        <li><a href="#"><i class="fas fa-search"></i> SDG Analysis</a></li>
                        <li><a href="#"><i class="fas fa-chart-bar"></i> Analytics Dashboard</a></li>
                        <li><a href="#"><i class="fas fa-database"></i> Research Database</a></li>
                        <li><a href="#"><i class="fas fa-api"></i> API Access</a></li>
                        <li><a href="#"><i class="fas fa-download"></i> Bulk Analysis</a></li>
                        <li><a href="#"><i class="fas fa-cog"></i> Integration Tools</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Resources</h4>
                    <ul class="footer-links">
                        <li><a href="#"><i class="fas fa-book"></i> Documentation</a></li>
                        <li><a href="#"><i class="fas fa-graduation-cap"></i> Tutorials</a></li>
                        <li><a href="#"><i class="fas fa-code"></i> API Reference</a></li>
                        <li><a href="#"><i class="fas fa-flask"></i> Research Papers</a></li>
                        <li><a href="#"><i class="fas fa-users"></i> Community Forum</a></li>
                        <li><a href="#"><i class="fas fa-blog"></i> Blog & Updates</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Company</h4>
                    <ul class="footer-links">
                        <li><a href="#"><i class="fas fa-info-circle"></i> About Us</a></li>
                        <li><a href="#"><i class="fas fa-briefcase"></i> Careers</a></li>
                        <li><a href="#"><i class="fas fa-handshake"></i> Partners</a></li>
                        <li><a href="#"><i class="fas fa-newspaper"></i> Press Kit</a></li>
                        <li><a href="#"><i class="fas fa-envelope"></i> Contact</a></li>
                        <li><a href="#"><i class="fas fa-shield-alt"></i> Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <div class="footer-copyright">© 2025 Wizdam by PT. Sangia Research Media and Publishing. All rights reserved.</div>
                <ul class="footer-bottom-links">
                    <li><a href="#">Terms of Service</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Cookie Policy</a></li>
                    <li><a href="#">Accessibility</a></li>
                </ul>
            </div>
        </div>
    </footer>

    <!-- FLOATING ELEMENTS – tidak diubah dari v2.3 -->
    <div class="floating-elements">
        <button class="floating-btn back-to-top" id="backToTop" aria-label="Back to top"><i class="fas fa-arrow-up"></i></button>
        <button class="floating-btn chatbot-btn" id="chatbotBtn" aria-label="Open chatbot"><i class="fas fa-comments"></i></button>
    </div>

    <!-- CHATBOT MODAL – tidak diubah dari v2.3 -->
    <div class="chatbot-modal" id="chatbotModal">
        <div class="chatbot-header">
            <h4><i class="fas fa-robot"></i> Wizdam Assistant</h4>
            <button class="chatbot-close" id="chatbotClose"><i class="fas fa-times"></i></button>
        </div>
        <div class="chatbot-body" id="chatbotBody">
            <div class="chatbot-message bot">
                <div class="chatbot-avatar"><i class="fas fa-robot"></i></div>
                <div class="chatbot-message-content">Hello! I'm Wizdam Assistant. I can help you with:<br>• Understanding SDG classification results<br>• Explaining analysis components<br>• Troubleshooting input formats<br>• General platform questions<br><br>How can I assist you today?</div>
            </div>
            <div class="chatbot-message bot" style="display:none;">
                <div class="chatbot-avatar"><i class="fas fa-robot"></i></div>
                <div class="chatbot-typing" id="chatbotTyping"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div>
            </div>
        </div>
        <div class="chatbot-input-area">
            <div class="chatbot-input-group">
                <input type="text" class="chatbot-input" id="chatbotInput" placeholder="Type your message...">
                <button class="chatbot-send" id="chatbotSend"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>

    <!-- LOADING OVERLAY – tidak diubah dari v2.3 -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
        <div class="loading-text">Analysing SDG contributions...</div>
        <div class="loading-subtext">This process may take a few moments</div>
    </div>

<script>
    // ================================================================
    // JS BAGIAN 1 – TIDAK DIUBAH DARI v2.3 (semua fungsi asli intact)
    // ================================================================
    let isSubmitting = false;
    let progressInterval = null;
    let progressCounter = 0;

    // Navbar scroll effect
    window.addEventListener('scroll', function() {
        const navbar = document.getElementById('navbar');
        if (window.scrollY > 50) navbar.classList.add('scrolled');
        else navbar.classList.remove('scrolled');
    });

    // Mobile menu toggle
    document.getElementById('navbarToggle').addEventListener('click', function() {
        const menu = document.getElementById('navbarMenu');
        menu.classList.toggle('show');
        this.classList.toggle('active');
    });
    document.querySelectorAll('.navbar-menu a').forEach(link => {
        link.addEventListener('click', function() {
            document.getElementById('navbarMenu').classList.remove('show');
            document.getElementById('navbarToggle').classList.remove('active');
        });
    });

    // Back to top
    const backToTopBtn = document.getElementById('backToTop');
    window.addEventListener('scroll', function() {
        if (window.scrollY > 300) backToTopBtn.classList.add('show');
        else backToTopBtn.classList.remove('show');
    });
    backToTopBtn.addEventListener('click', function() { window.scrollTo({ top: 0, behavior: 'smooth' }); });

    // Chatbot
    const chatbotBtn = document.getElementById('chatbotBtn');
    const chatbotModal = document.getElementById('chatbotModal');
    const chatbotClose = document.getElementById('chatbotClose');
    const chatbotInput = document.getElementById('chatbotInput');
    const chatbotSend = document.getElementById('chatbotSend');
    const chatbotBody = document.getElementById('chatbotBody');
    const chatbotTyping = document.getElementById('chatbotTyping');
    const chatbotResponses = {
        'hello': 'Hello! How can I help you with SDG analysis today?',
        'help': 'I can assist you with: SDG classification results, ORCID/DOI formats, analysis interpretation, and platform features.',
        'orcid': 'ORCID format should be: 0000-0000-0000-0000. Make sure all digits are correct and the checksum is valid.',
        'doi': 'DOI format example: 10.1038/nature12373. You can also paste the full DOI URL.',
        'sdg': 'SDGs are the 17 Sustainable Development Goals. Our system analyzes how research contributes to these goals.',
        'analysis': 'The analysis uses 4 components: Keywords (30%), Similarity (30%), Substantive (20%), and Causal (20%).',
        'confidence': 'Confidence scores show how certain the AI is about SDG classification. Higher scores mean stronger evidence.',
        'error': 'For errors, check your input format first. ORCID needs valid checksum, DOI needs proper structure.',
        'default': 'I understand you need help. Could you be more specific? Ask about ORCID, DOI, SDGs, analysis, or errors.'
    };
    function getKeywords(message) {
        const msg = message.toLowerCase();
        if (msg.includes('hello') || msg.includes('hi') || msg.includes('hey')) return 'hello';
        if (msg.includes('help') || msg.includes('assist')) return 'help';
        if (msg.includes('orcid')) return 'orcid';
        if (msg.includes('doi')) return 'doi';
        if (msg.includes('sdg') || msg.includes('sustainable')) return 'sdg';
        if (msg.includes('analysis') || msg.includes('analyze')) return 'analysis';
        if (msg.includes('confidence') || msg.includes('score')) return 'confidence';
        if (msg.includes('error') || msg.includes('problem') || msg.includes('issue')) return 'error';
        return 'default';
    }
    chatbotBtn.addEventListener('click', function() {
        chatbotModal.classList.toggle('show');
        if (chatbotModal.classList.contains('show')) chatbotInput.focus();
    });
    chatbotClose.addEventListener('click', function() { chatbotModal.classList.remove('show'); });
    function addChatMessage(message, isUser = false) {
        const div = document.createElement('div');
        div.className = 'chatbot-message ' + (isUser ? 'user' : 'bot');
        div.innerHTML = isUser
            ? '<div class="chatbot-message-content">' + message + '</div>'
            : '<div class="chatbot-avatar"><i class="fas fa-robot"></i></div><div class="chatbot-message-content">' + message + '</div>';
        chatbotBody.appendChild(div);
        chatbotBody.scrollTop = chatbotBody.scrollHeight;
    }
    function showTyping() { chatbotTyping.parentElement.style.display = 'flex'; chatbotTyping.classList.add('show'); chatbotBody.scrollTop = chatbotBody.scrollHeight; }
    function hideTyping() { chatbotTyping.classList.remove('show'); chatbotTyping.parentElement.style.display = 'none'; }
    function sendChatMessage() {
        const message = chatbotInput.value.trim();
        if (!message) return;
        addChatMessage(message, true);
        chatbotInput.value = '';
        showTyping();
        setTimeout(() => { hideTyping(); addChatMessage(chatbotResponses[getKeywords(message)]); }, 1500);
    }
    chatbotSend.addEventListener('click', sendChatMessage);
    chatbotInput.addEventListener('keypress', function(e) { if (e.key === 'Enter') sendChatMessage(); });
    document.addEventListener('click', function(e) { if (!chatbotModal.contains(e.target) && !chatbotBtn.contains(e.target)) chatbotModal.classList.remove('show'); });

    // Input detection and validation (tidak diubah dari v2.3)
    function detectInputType(value) {
        value = value.trim();
        if (/orcid\.org\/(\d{4}-\d{4}-\d{4}-\d{3}[\dX])/i.test(value)) return 'orcid';
        if (/^0000-/.test(value) && value.includes('-')) return 'orcid';
        if (/^(\d{4}-\d{4}-\d{4}-\d{3}[\dX])$/.test(value)) return 'orcid';
        if (value.length >= 7 && value.indexOf('/') !== -1) {
            if (/^10\.\d+\//.test(value) || /doi\.org\//.test(value) || /dx\.doi\.org\//.test(value)) return 'doi';
            if (value.length > 10) return 'doi';
        }
        return null;
    }
    function validateOrcid(orcid) {
        orcid = orcid.trim();
        if (!/^0000-\d{4}-\d{4}-\d{3}[\dX]$/.test(orcid) || orcid.length !== 19) return false;
        const digits = orcid.replace(/-/g, '').slice(0, -1);
        const checkDigit = orcid.slice(-1);
        let total = 0;
        for (let i = 0; i < digits.length; i++) total = (total + parseInt(digits[i])) * 2;
        const remainder = total % 11; const result = (12 - remainder) % 11;
        const expectedCheckDigit = (result === 10) ? 'X' : result.toString();
        return checkDigit === expectedCheckDigit;
    }
    function validateInput(value, type) {
        if (type === 'orcid') {
            if (value.includes('orcid.org/')) { const m = value.match(/orcid\.org\/(\d{4}-\d{4}-\d{4}-\d{3}[\dX])/i); if (m) value = m[1]; }
            return validateOrcid(value);
        } else if (type === 'doi') { return value.length >= 7 && value.indexOf('/') !== -1; }
        return false;
    }
    function updateInputVisualState(inputElement, labelElement, state) {
        inputElement.classList.remove('input-valid', 'input-invalid', 'input-warning', 'input-checking');
        const states = { valid: ['#28a745','0 0 0 0.2rem rgba(40,167,69,.25)'], invalid: ['#dc3545','0 0 0 0.2rem rgba(220,53,69,.25)'], warning: ['#ffc107','0 0 0 0.2rem rgba(255,193,7,.25)'], checking: ['#007bff','0 0 0 0.2rem rgba(0,123,255,.25)'], default: ['#e1e5e9',''] };
        if (state !== 'default') inputElement.classList.add('input-' + state);
        if (states[state]) { inputElement.style.borderColor = states[state][0]; inputElement.style.boxShadow = states[state][1]; }
        if (labelElement) { labelElement.style.color = state === 'default' ? '#495057' : states[state] ? states[state][0] : ''; labelElement.style.fontWeight = state === 'default' ? 'normal' : '600'; }
        inputElement.style.transition = 'border-color 0.3s ease, box-shadow 0.3s ease';
    }
    function updateInputStatus(value) {
        const statusElement = document.getElementById('input_status');
        const statusText = document.getElementById('status_text');
        const statusIcon = document.querySelector('#status_indicator i');
        const inputElement = document.getElementById('input_value');
        const labelElement = document.querySelector('label[for="input_value"]');
        if (!value.trim()) { statusElement.className = 'input-status'; statusIcon.className = 'fas fa-question-circle'; statusText.textContent = 'Enter ORCID or DOI to start the analysis'; updateInputVisualState(inputElement, labelElement, 'default'); return; }
        const detectedType = detectInputType(value);
        if (detectedType === 'orcid') {
            let cleanedOrcid = value;
            if (value.includes('orcid.org/')) { const m = value.match(/orcid\.org\/(\d{4}-\d{4}-\d{4}-\d{3}[\dX])/i); if (m) cleanedOrcid = m[1]; }
            if (cleanedOrcid.length !== 19 || !cleanedOrcid.match(/^0000-\d{4}-\d{4}-\d{3}[\dX]$/)) { statusElement.className = 'input-status invalid'; statusIcon.className = 'fas fa-exclamation-triangle'; statusText.textContent = 'Invalid ORCID format: ' + cleanedOrcid + ' - Must be 0000-0000-0000-0000 format'; updateInputVisualState(inputElement, labelElement, 'invalid'); return; }
            const isValid = validateOrcid(cleanedOrcid);
            if (isValid) { statusElement.className = 'input-status orcid-detected'; statusIcon.className = 'fas fa-check-circle'; statusText.textContent = 'ORCID detected: ' + cleanedOrcid + ' - Valid'; updateInputVisualState(inputElement, labelElement, 'valid'); }
            else { statusElement.className = 'input-status invalid'; statusIcon.className = 'fas fa-exclamation-triangle'; statusText.textContent = 'ORCID detected: ' + cleanedOrcid + ' - Invalid checksum'; updateInputVisualState(inputElement, labelElement, 'invalid'); }
        } else if (detectedType === 'doi') {
            const isValid = validateInput(value, 'doi');
            if (isValid) { statusElement.className = 'input-status doi-detected'; statusIcon.className = 'fas fa-check-circle'; statusText.textContent = 'DOI detected: ' + value.slice(0, 50) + (value.length > 50 ? '...' : '') + ' - Valid'; updateInputVisualState(inputElement, labelElement, 'valid'); }
            else { statusElement.className = 'input-status invalid'; statusIcon.className = 'fas fa-exclamation-triangle'; statusText.textContent = 'DOI detected but format is invalid'; updateInputVisualState(inputElement, labelElement, 'warning'); }
        } else {
            if (value.startsWith('0000-') || value.includes('orcid')) { statusElement.className = 'input-status invalid'; statusIcon.className = 'fas fa-exclamation-triangle'; statusText.textContent = 'Invalid ORCID format - must follow 0000-0000-0000-0000 pattern'; updateInputVisualState(inputElement, labelElement, 'invalid'); }
            else if (value.length >= 4 && value.length <= 10) { statusElement.className = 'input-status detecting'; statusIcon.className = 'fas fa-search'; statusText.textContent = 'Detecting input formats...'; updateInputVisualState(inputElement, labelElement, 'checking'); }
            else { statusElement.className = 'input-status invalid'; statusIcon.className = 'fas fa-exclamation-triangle'; statusText.textContent = 'Unrecognised format - enter a valid ORCID or DOI'; updateInputVisualState(inputElement, labelElement, 'invalid'); }
        }
    }
    function startProgressCounter() {
        progressCounter = 0;
        const loadingText = document.querySelector('.loading-text');
        if (loadingText) progressInterval = setInterval(() => { progressCounter++; loadingText.textContent = 'Analyzing... ' + progressCounter + 's'; }, 1000);
    }
    function stopProgressCounter() { if (progressInterval) { clearInterval(progressInterval); progressInterval = null; progressCounter = 0; } }
    function resetSubmitButton() {
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.innerHTML = submitBtn.getAttribute('data-original-text') || '<i class="fas fa-search"></i> Analysis';
        submitBtn.disabled = false;
        document.getElementById('loadingOverlay').style.display = 'none';
        isSubmitting = false;
        stopProgressCounter();
        reinitializeInputHandlers();
    }
    function toggleAnalysis(index) {
        const analysisDiv = document.getElementById('analysis-' + index);
        if (!analysisDiv) return;
        const button = analysisDiv.previousElementSibling.querySelector('.show-more-btn');
        if (analysisDiv.classList.contains('show')) { analysisDiv.classList.remove('show'); if (button) button.innerHTML = '<i class="fas fa-chart-bar"></i> Show Details'; }
        else { analysisDiv.classList.add('show'); if (button) button.innerHTML = '<i class="fas fa-eye-slash"></i> Hide Details'; }
    }
    function handleInputInteraction(inputElement, labelElement) {
        updateInputStatus(inputElement.value);
        inputElement.addEventListener('focus', function() { if (!this.style.borderColor || this.style.borderColor === 'rgb(225, 229, 233)') updateInputVisualState(this, labelElement, 'checking'); }, true);
        inputElement.addEventListener('blur', function() { updateInputStatus(this.value); }, true);
        inputElement.addEventListener('paste', function(e) { setTimeout(() => { updateInputStatus(this.value); }, 100); }, true);
        inputElement.addEventListener('input', function() { updateInputStatus(this.value); updateSubmitButtonState(this.value); }, true);
        inputElement.addEventListener('keyup', function() { updateInputStatus(this.value); updateSubmitButtonState(this.value); }, true);
    }
    function updateSubmitButtonState(value) {
        const submitBtn = document.getElementById('submitBtn');
        if (!submitBtn) return;
        const detectedType = detectInputType(value);
        if (value.trim().length > 0) {
            const isValid = validateInput(value, detectedType);
            if (isValid || detectedType) { submitBtn.disabled = false; submitBtn.style.opacity = '1'; }
            else if (value.length >= 4) { submitBtn.disabled = true; submitBtn.style.opacity = '0.7'; }
            else { submitBtn.disabled = true; submitBtn.style.opacity = '0.7'; }
        } else { submitBtn.disabled = false; submitBtn.style.opacity = '1'; }
    }
    function reinitializeInputHandlers() {
        const inputField = document.getElementById('input_value');
        const labelElement = document.querySelector('label[for="input_value"]');
        if (inputField && labelElement) {
            isSubmitting = false;
            updateInputStatus(inputField.value);
            const parent = inputField.parentNode;
            const newInput = inputField.cloneNode(true);
            parent.replaceChild(newInput, inputField);
            handleInputInteraction(newInput, labelElement);
        }
    }

    // ================================================================
    // JS BAGIAN 2 – TAMBAHAN BARU: ORCID AJAX Sequential
    // ================================================================
    const SDG_DEFS = <?php echo json_encode($SDG_DEFINITIONS, JSON_UNESCAPED_UNICODE); ?>;
    const AJAX_BATCH = <?php echo AJAX_BATCH_SIZE; ?>;
    let orcidAbortCtrl = null;
    let ajaxWorkIndex  = 0;
    let ajaxSdgChart   = null;
    let ajaxContribChart = null;

    // URL endpoint = file ini sendiri (same-origin)
    const AJAX_ENDPOINT = '<?php echo htmlspecialchars(strtok($_SERVER['REQUEST_URI'] ?? '/', '?'), ENT_QUOTES); ?>';

    async function ajaxCall(action, params) {
        // Kirim POST form-encoded – parameter TIDAK muncul di URL → WAF tidak bisa filter
        const form = new URLSearchParams();
        form.append('_sdg', action);  // field name sederhana, tidak dicurigai WAF
        if (params) {
            Object.entries(params).forEach(([k, v]) => {
                if (v !== undefined && v !== null && v !== false && v !== '')
                    form.append(k, String(v));
            });
        }
        const resp = await fetch(AJAX_ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: form.toString(),
            signal: orcidAbortCtrl.signal,
        });
        let data;
        try { data = await resp.json(); } catch (_) { throw new Error('HTTP ' + resp.status + ' – respons bukan JSON. Cek error log.'); }
        if (!resp.ok || data.status === 'error') throw new Error(data.message || 'HTTP ' + resp.status);
        return data;
    }
    function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

    function ajaxShowProgress(title, subtitle) {
        document.getElementById('ajaxProgressSection').style.display = 'block';
        document.getElementById('ajaxProgressTitle').textContent = title || '…';
        document.getElementById('ajaxProgressSubtitle').textContent = subtitle || '';
    }
    function ajaxHideProgress() { setTimeout(() => { document.getElementById('ajaxProgressSection').style.display = 'none'; }, 3000); }
    function ajaxSetBar(done, total) {
        const pct = total > 0 ? Math.round((done / total) * 100) : 0;
        document.getElementById('ajaxProgressBar').style.width = pct + '%';
        document.getElementById('ajaxProgressCount').textContent = done;
        document.getElementById('ajaxProgressTotal').textContent = total;
    }
    function escH(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

    function ajaxRenderPersonal(info, total) {
        if (!info) return;
        const initials = (info.name||'NN').split(' ').slice(0,2).map(w=>w[0]).join('').toUpperCase();
        const instHtml = (info.institutions && info.institutions.length)
            ? '<p><i class="fas fa-university"></i> ' + escH(info.institutions.slice(0,2).join(', ')) + (info.institutions.length>2?' et al.':'') + '</p>' : '';
        document.getElementById('ajaxResultsSection').innerHTML = `
        <div class="info-general">
          <div class="personal-info">
            <div class="avatar">${escH(initials)}</div>
            <div>
              <h2>${escH(info.name||'–')}</h2>
              <p><i class="fab fa-orcid"></i> ${escH(info.orcid||'')}</p>
              ${instHtml}
            </div>
          </div>
          <div class="stats-grid">
            <div class="stat-card"><div class="stat-number" id="ajaxStatWorks">${total}</div><div class="stat-label">Total Works</div></div>
            <div class="stat-card"><div class="stat-number" id="ajaxStatSdgs">–</div><div class="stat-label">Identified SDGs</div></div>
            <div class="stat-card"><div class="stat-number" id="ajaxStatActive">–</div><div class="stat-label">Active Contribution</div></div>
            <div class="stat-card"><div class="stat-number" id="ajaxStatConf">–</div><div class="stat-label">Average Confidence</div></div>
          </div>
        </div>
        <div id="ajaxSdgSummary"></div>
        <div id="ajaxCharts"></div>
        <div class="works-container">
          <h3 class="u-heading3"><i class="fas fa-file-alt"></i> Publications (<span id="ajaxWorksCount">0</span> / ${total} work)</h3>
          <div id="ajaxWorksList"></div>
        </div>`;
        document.getElementById('ajaxResultsSection').style.display = 'block';
    }

    function ajaxAppendWorks(works, offset) {
        const list = document.getElementById('ajaxWorksList');
        if (!list) return;
        works.forEach((work, i) => {
            const idx = 'aj' + (offset + i);
            const sdgTags = (work.sdgs || []).map(sdg => {
                const def = SDG_DEFS[sdg] || { color: '#666', title: sdg, svg_url: '' };
                const conf = work.sdg_confidence && work.sdg_confidence[sdg] ? (work.sdg_confidence[sdg]*100).toFixed(1) : '–';
                return `<div class="work-sdg-tag" style="background:${def.color}"><div class="sdg-mini-icon"><img src="${escH(def.svg_url)}" alt="${escH(def.title)}" width="20" height="20"></div><span>${escH(sdg)} <span class="sdg-confidence-info">(${conf}%)</span></span></div>`;
            }).join('');
            const sdgsHtml = work.sdgs && work.sdgs.length
                ? `<div style="display:flex;justify-content:space-between;align-items:center;"><div class="work-sdgs">${sdgTags}</div><button class="show-more-btn" onclick="toggleAnalysis('${idx}')"><i class="fas fa-chart-bar"></i> Show Details</button></div>${buildAjaxDetailedAnalysis(work, idx)}`
                : `<div class="none-SDG"><i class="fas fa-info-circle"></i> No SDGs were identified for this.</div>`;
            const abstract = work.abstract ? `<div class="work-abstract"><strong>Abstract:</strong> ${escH((work.abstract||'').slice(0,400))}${work.abstract.length>400?'…':''}</div>` : '';
            const doi = work.doi ? `<div class="work-meta-row"><i class="fas fa-link"></i><span>DOI: <a href="https://doi.org/${escH(work.doi)}" target="_blank">${escH(work.doi)}</a></span></div>` : '';
            list.insertAdjacentHTML('beforeend', `
            <div class="work-item">
              <div class="work-header"><div class="work-title">${escH(work.title||'(Tanpa judul)')}</div></div>
              <div class="work-meta">${doi}<div class="work-meta-row"><i class="fas fa-chart-line"></i><span>${(work.sdgs||[]).length} Identified SDGs</span></div></div>
              ${abstract}
              <div class="work-sdgs-section">${sdgsHtml}</div>
            </div>`);
            ajaxWorkIndex++;
        });
        const el = document.getElementById('ajaxWorksCount');
        if (el) el.textContent = ajaxWorkIndex;
    }

    function buildAjaxDetailedAnalysis(work, idx) {
        if (!work.detailed_analysis || !Object.keys(work.detailed_analysis).length)
            return `<div class="detailed-analysis" id="analysis-${idx}"><p style="color:#666;text-align:center;padding:20px">No detailed analysis available.</p></div>`;
        let html = `<div class="detailed-analysis" id="analysis-${idx}">`;
        Object.entries(work.detailed_analysis).forEach(([sdg, analysis]) => {
            const def = SDG_DEFS[sdg] || { title: sdg };
            const comp = analysis.components || {};
            const compsHtml = ['keyword_score','similarity_score','substantive_score','causal_score'].map(k =>
                `<div class="component-score"><div class="component-label">${k.replace('_score','').replace('_',' ')}</div><div class="component-value">${(comp[k]||0).toFixed(3)}</div></div>`).join('');
            const evidHtml = (analysis.evidence && analysis.evidence.keyword_matches || []).slice(0,2).map(m =>
                `<div class="evidence-title">Keyword Evidence:</div><div class="evidence-item"><strong>${escH(m.keyword||'')}</strong>: ${m.context||''}</div>`).join('');
            html += `<div class="analysis-section">
              <h5>${escH(sdg+': '+(def.title||sdg))} <span style="color:#667eea">(Score: ${(analysis.score||0).toFixed(3)})</span></h5>
              <div class="analysis-components">${compsHtml}</div>
              <div class="analysis-info"><span class="info-badge">${escH(analysis.contributor_type&&analysis.contributor_type.type||'–')}</span><span class="info-badge confidence">${escH(analysis.confidence_level||'–')}</span></div>
              ${evidHtml}</div>`;
        });
        return html + '</div>';
    }

    function ajaxRenderSummary(summaryData) {
        const summary = summaryData.researcher_sdg_summary || {};
        const profile  = summaryData.contributor_profile     || {};
        const sdgCount = Object.keys(summary).length;
        const activeCount = Object.values(profile).filter(p => p.dominant_type === 'Active Contributor').length;
        let totalConf = 0, confCount = 0;
        Object.values(summary).forEach(s => { totalConf += s.average_confidence; confCount++; });
        const avgConf = confCount > 0 ? Math.round((totalConf / confCount) * 100) : 0;
        const e = id => document.getElementById(id);
        if (e('ajaxStatSdgs'))   e('ajaxStatSdgs').textContent   = sdgCount;
        if (e('ajaxStatActive')) e('ajaxStatActive').textContent = activeCount;
        if (e('ajaxStatConf'))   e('ajaxStatConf').textContent   = avgConf + '%';
        if (!Object.keys(summary).length) return;
        let html = '<h3 class="u-heading3"><i class="fas fa-chart-pie"></i> Summary of SDG Contributions</h3><div class="sdg-grid">';
        Object.entries(summary).forEach(([sdg, sum], i) => {
            const def = SDG_DEFS[sdg] || { title: sdg, color: '#666', svg_url: '' };
            const prf = profile[sdg] || {};
            const pct = (sum.average_confidence * 100).toFixed(1);
            html += `<div class="sdg-card">
              <div class="sdg-icon"><img src="${escH(def.svg_url)}" alt="${escH(def.title||sdg)}"></div>
              <div class="sdg-content">
                <div class="sdg-title">${escH(def.title||sdg)}</div>
                <div class="sdg-stats">
                  <div class="sdg-stat-item"><div class="sdg-stat-label">Number of Works</div><div class="sdg-stat-value">${sum.work_count} works</div></div>
                  <div class="sdg-stat-item"><div class="sdg-stat-label">Confidence</div><div class="sdg-stat-value">${pct}%</div></div>
                </div>
                <div class="confidence-bar"><div class="confidence-fill" style="width:${pct}%;background:${def.color}"></div></div>
                ${prf.dominant_type ? '<div class="contributor-type">'+escH(prf.dominant_type)+'</div>' : ''}
              </div>
              <style>.sdg-card:nth-of-type(${i+1})::after{background:${def.color}}</style>
            </div>`;
        });
        html += '</div>';
        const el = document.getElementById('ajaxSdgSummary');
        if (el) el.innerHTML = html;

        // Charts
        const chartsEl = document.getElementById('ajaxCharts');
        if (chartsEl) {
            chartsEl.innerHTML = `<div class="charts-section">
              <div class="chart-container"><h4><i class="fas fa-chart-pie"></i> SDG distribution</h4><canvas id="ajaxSdgChart"></canvas></div>
              <div class="chart-container"><h4><i class="fas fa-chart-bar"></i> Contributor Type</h4><canvas id="ajaxContribChart"></canvas></div>
            </div>`;
            if (ajaxSdgChart) ajaxSdgChart.destroy();
            ajaxSdgChart = new Chart(document.getElementById('ajaxSdgChart'), {
                type: 'doughnut',
                data: { labels: Object.keys(summary).map(s=>(SDG_DEFS[s]||{}).title||s), datasets: [{ data: Object.values(summary).map(s=>s.work_count), backgroundColor: Object.keys(summary).map(s=>(SDG_DEFS[s]||{}).color||'#666'), borderWidth: 2, borderColor: '#fff' }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { padding: 10, usePointStyle: true, font: { size: 13 } } } } }
            });
            if (ajaxContribChart) ajaxContribChart.destroy();
            const ctypes = {};
            Object.values(profile).forEach(p => { ctypes[p.dominant_type] = (ctypes[p.dominant_type] || 0) + 1; });
            ajaxContribChart = new Chart(document.getElementById('ajaxContribChart'), {
                type: 'bar',
                data: { labels: Object.keys(ctypes), datasets: [{ label: 'Number of SDGs', data: Object.values(ctypes), backgroundColor: ['#667eea','#764ba2','#f093fb','#f5576c','#4facfe'], borderWidth: 0, borderRadius: 8 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { ticks: { stepSize: 1, font: { size: 10 } } }, x: { ticks: { maxRotation: 45, font: { size: 13 } } } } }
            });
        }
    }

    async function startOrcidAjax(orcid, forceRefresh) {
        orcidAbortCtrl = new AbortController();
        ajaxWorkIndex  = 0;
        const rfParam = forceRefresh ? { refresh: 'true' } : {};

        document.getElementById('ajaxResultsSection').innerHTML = '';
        document.getElementById('ajaxResultsSection').style.display = 'none';

        try {
            // Step 1: Init
            ajaxShowProgress('Fetching researcher profile…', 'Fetching list of works from ORCID API…');
            const initData = await ajaxCall('init', Object.assign({ orcid }, rfParam));

            const totalWorks = (typeof initData.total_works === 'number' && !isNaN(initData.total_works))
                ? initData.total_works
                : (Array.isArray(initData.works) ? initData.works.length : 0);

            ajaxRenderPersonal(initData.personal_info, totalWorks);
            ajaxSetBar(0, totalWorks);
            document.getElementById('ajaxProgressBatch').textContent = '0';

            if (totalWorks === 0) { ajaxHideProgress(); return; }

            // Format lama: works sudah ada di response
            if (Array.isArray(initData.works) && initData.works.length > 0 && typeof initData.total_works !== 'number') {
                ajaxAppendWorks(initData.works, 0);
                ajaxSetBar(totalWorks, totalWorks);
                if (initData.researcher_sdg_summary && Object.keys(initData.researcher_sdg_summary).length) {
                    ajaxRenderSummary(initData);
                } else {
                    ajaxShowProgress('Calculating SDG summary…', '');
                    const summaryData = await ajaxCall('summary', { orcid });
                    ajaxRenderSummary(summaryData);
                }
                ajaxShowProgress('Analyzed DONE! ✓', totalWorks + ' works display');
                ajaxHideProgress();
                return;
            }

            // Step 2: Batch loop
            let offset = 0, batchNum = 0, done = 0;
            while (offset < totalWorks) {
                batchNum++;
                const to = Math.min(offset + AJAX_BATCH, totalWorks);
                ajaxShowProgress('Analyzing works ' + (offset+1) + '–' + to + ' from ' + totalWorks + '…', 'Batch #' + batchNum + ' | Processing ' + (to - offset) + ' works');
                document.getElementById('ajaxProgressBatch').textContent = batchNum;

                const batchData = await ajaxCall('batch', Object.assign({ orcid, offset: offset, limit: AJAX_BATCH }, rfParam));
                done += batchData.processed;
                ajaxSetBar(done, totalWorks);
                ajaxAppendWorks(batchData.works, offset);

                if (batchData.is_done || batchData.processed === 0) break;
                offset = batchData.next_offset;
                await sleep(350);
            }

            // Step 3: Summary
            ajaxShowProgress('Calculating SDG summary…', 'Aggregating all batch results…');
            const summaryData = await ajaxCall('summary', { orcid });
            ajaxRenderSummary(summaryData);
            ajaxSetBar(totalWorks, totalWorks);
            ajaxShowProgress('Analized DONE! ✓', totalWorks + ' works analyzed in ' + batchNum + ' batch');
            ajaxHideProgress();

            setTimeout(() => { document.getElementById('ajaxResultsSection').scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 400);

        } catch (err) {
            if (err.name !== 'AbortError') {
                document.getElementById('ajaxProgressSection').style.display = 'none';
                alert('Error ORCID AJAX: ' + err.message);
            }
        } finally {
            resetSubmitButton();
        }
    }

    // ================================================================
    // DOMContentLoaded – tidak diubah dari v2.3, ditambah ORCID intercept
    // ================================================================
    document.addEventListener('DOMContentLoaded', function() {
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.setAttribute('data-original-text', submitBtn.innerHTML);

        const inputField = document.getElementById('input_value');
        const labelElement = document.querySelector('label[for="input_value"]');
        if (inputField) {
            updateInputStatus(inputField.value);
            if (labelElement) handleInputInteraction(inputField, labelElement);
        }

        const form = document.getElementById('analysisForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (isSubmitting) { e.preventDefault(); return false; }

                const inputValue = document.getElementById('input_value').value.trim();
                if (!inputValue) { e.preventDefault(); alert('Please enter a valid ORCID ID or DOI.'); return false; }

                const detectedType = detectInputType(inputValue);
                if (!detectedType) { e.preventDefault(); alert('The input format is not recognized. Please enter a valid ORCID ID or DOI.'); return false; }

                const isValid = validateInput(inputValue, detectedType);
                if (!isValid) {
                    e.preventDefault();
                    if (detectedType === 'orcid') alert('ORCID ID is invalid. Please check your ORCID checksum.');
                    else if (detectedType === 'doi') alert('DOI is invalid. Please check the format of your DOI.');
                    return false;
                }

                // ── ORCID: intercept dan gunakan AJAX (anti-timeout) ──
                if (detectedType === 'orcid') {
                    e.preventDefault();
                    let cleanOrcid = inputValue.trim();
                    const m = cleanOrcid.match(/orcid\.org\/(\d{4}-\d{4}-\d{4}-\d{3}[\dX])/i);
                    if (m) cleanOrcid = m[1];
                    cleanOrcid = cleanOrcid.replace(/[^\d\-X]/gi, '');
                    const forceRefresh = document.getElementById('force_refresh').checked;

                    isSubmitting = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Analysing...';
                    submitBtn.disabled = true;

                    startOrcidAjax(cleanOrcid, forceRefresh);
                    return false;
                }

                // ── DOI: biarkan form submit normal ke PHP ──
                isSubmitting = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Analyzing...';
                submitBtn.disabled = true;
                document.getElementById('loadingOverlay').style.display = 'flex';
                startProgressCounter();

                const timeoutId = setTimeout(function() {
                    resetSubmitButton();
                    if (document.getElementById('loadingOverlay').style.display === 'flex')
                        alert('Timeout: The analysis process is taking longer than expected. Please try again later.');
                }, 130000);
                window.currentTimeoutId = timeoutId;
            });
        }

        if (document.getElementById('resultsSection')) {
            setTimeout(function() {
                document.getElementById('resultsSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
                setTimeout(reinitializeInputHandlers, 1000);
            }, 500);
        }

        isSubmitting = false;
        document.getElementById('loadingOverlay').style.display = 'none';
        stopProgressCounter();

        document.addEventListener('input', function(e) {
            if (e.target && e.target.id === 'input_value') { updateInputStatus(e.target.value); updateSubmitButtonState(e.target.value); }
        }, true);
    });

    <?php if (isset($analysis_result['researcher_sdg_summary']) && !empty($analysis_result['researcher_sdg_summary'])): ?>
    // Charts untuk DOI/full-mode PHP results (tidak diubah dari v2.3)
    document.addEventListener('DOMContentLoaded', function() {
        const sdgData = <?php echo json_encode($analysis_result['researcher_sdg_summary']); ?>;
        const sdgDefinitions = <?php echo json_encode($SDG_DEFINITIONS); ?>;
        const sdgLabels = Object.keys(sdgData).map(function(sdg) { return sdgDefinitions[sdg] && sdgDefinitions[sdg].title ? sdgDefinitions[sdg].title : sdg; });
        const sdgValues = Object.values(sdgData).map(function(item) { return item.work_count; });
        const sdgColors = Object.keys(sdgData).map(function(sdg) { return sdgDefinitions[sdg] && sdgDefinitions[sdg].color ? sdgDefinitions[sdg].color : '#666'; });
        try {
            new Chart(document.getElementById('sdgChart'), {
                type: 'doughnut',
                data: { labels: sdgLabels, datasets: [{ data: sdgValues, backgroundColor: sdgColors, borderWidth: 2, borderColor: '#fff' }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { padding: 10, usePointStyle: true, font: { size: 13 } } } } }
            });
        } catch (error) { console.error('Error creating SDG chart:', error); }
        <?php if (isset($analysis_result['contributor_profile'])): ?>
        try {
            const contributorProfile = <?php echo json_encode($analysis_result['contributor_profile']); ?>;
            const contributorTypes = {};
            Object.values(contributorProfile).forEach(function(profile) { const type = profile.dominant_type; contributorTypes[type] = (contributorTypes[type] || 0) + 1; });
            new Chart(document.getElementById('contributorChart'), {
                type: 'bar',
                data: { labels: Object.keys(contributorTypes), datasets: [{ label: 'Number of SDGs', data: Object.values(contributorTypes), backgroundColor: ['#667eea','#764ba2','#f093fb','#f5576c','#4facfe'].slice(0, Object.keys(contributorTypes).length), borderWidth: 0, borderRadius: 8 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { ticks: { stepSize: 1, font: { size: 10 } } }, x: { ticks: { maxRotation: 45, font: { size: 13 } } } }, layout: { padding: { top: 10, bottom: 10 } } }
            });
        } catch (error) { console.error('Error creating contributor chart:', error); }
        <?php endif; ?>
    });
    <?php endif; ?>

    // Loading overlay SDG spinner (tidak diubah dari v2.3)
    document.addEventListener('DOMContentLoaded', function() {
        const loadingOverlay = document.getElementById('loadingOverlay');
        if (loadingOverlay) {
            const sdgSpinner = document.createElement('div');
            sdgSpinner.className = 'sdg-spinner';
            sdgSpinner.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="sdg-svg-spinner" viewBox="0 0 50 50"><g class="sdg-spinner-group"><path d="M12.8,17.9c.6-1.1,1.4-2,2.2-2.9L7.7,6.9C5.9,8.7,4.3,10.7,3.1,13l9.7,4.9Z" fill="#5fba47"/><path d="M30.9,12.2c1.1.5,2.1,1.2,3.1,1.9l7.4-8C39.4,4.4,37.2,3,34.8,2L30.9,12.2Z" fill="#e4b531"/><path d="M47.7,14.6L38,19.5c.5,1.1.8,2.2,1,3.5l10.9-1c-.4-2.7-1.1-5.1-2.2-7.4" fill="#c3202f"/><path d="M37.3,18.1l9.8-4.9c-1.2-2.2-2.8-4.3-4.6-6.1l-7.4,8c.8.9,1.6,1.9,2.2,3" fill="#4a9e46"/><path d="M10.9,25c0-.2,0-.4,0-.7L0,23.3c0,.6,0,1.1,0,1.7c0,2.1.3,4.1.7,6l10.5-3c-.2-.9-.3-2-.3-3" fill="#408045"/><path d="M35.9,33.9c-.8.9-1.7,1.8-2.6,2.5L39,45.7c2.1-1.4,4-3.2,5.6-5.2l-8.7-6.6Z" fill="#f7c118"/><path d="M39.1,25c0,1-.1,2-.3,3l10.5,3c.5-1.9.7-3.9.7-6c0-.5,0-1,0-1.5l-10.9,1c0,.2,0,.3,0,.5" fill="#ee432a"/><path d="M14.3,34.1L5.6,40.7c1.6,2,3.5,3.7,5.7,5.1L17,36.5c-1-.6-1.9-1.4-2.7-2.4" fill="#f89c28"/><path d="M11.1,22.8c.2-1.2.5-2.4,1-3.5L2.3,14.4c-1.1,2.3-1.8,4.8-2.2,7.4l11,1Z" fill="#1a94d2"/><path d="M37.7,46.6L32,37.3c-1,.6-2.2,1-3.3,1.4l2,10.7c2.4-.6,4.8-1.6,7-2.8" fill="#a01c44"/><path d="M38.4,29.5c-.4,1.1-.9,2.2-1.5,3.1l8.7,6.6c1.4-2,2.5-4.3,3.3-6.7l-10.5-3Z" fill="#26bbe1"/><path d="M27,38.9c-.7.1-1.3.1-2,.1-.6,0-1.1,0-1.6-.1l-2,10.7c1.2.2,2.4.3,3.7.3c1.4,0,2.7-.1,4.1-.3L27,38.9Z" fill="#f26b2c"/><path d="M25.9,10.9c1.2.1,2.4.3,3.5.7L33.3,1.4C31,0.6,28.5,0.1,25.9,0v10.9Z" fill="#e5243c"/><path d="M21.8,38.7c-1.2-.3-2.4-.7-3.5-1.3l-5.7,9.3c2.2,1.3,4.6,2.2,7.2,2.7l2-10.7Z" fill="#db1769"/><path d="M20.8,11.6c1.1-.4,2.3-.6,3.6-.6v-11c-2.6.1-5.1.5-7.5,1.4l3.9,10.2Z" fill="#17486a"/><path d="M13.3,32.9c-.7-1-1.3-2.1-1.7-3.3l-10.5,3c.8,2.5,2,4.8,3.5,6.9l8.7-6.6Z" fill="#c4962e"/><path d="M16.3,13.9c.9-.7,1.9-1.3,3-1.8l-4-10.2c-2.3,1-4.5,2.3-6.4,3.9l7.4,8.1Z" fill="#036a9c"/></g></svg>`;
            const oldSpinner = loadingOverlay.querySelector('.spinner');
            if (oldSpinner) oldSpinner.parentNode.replaceChild(sdgSpinner, oldSpinner);
            const style = document.createElement('style');
            style.textContent = `.sdg-spinner{width:80px;height:80px;margin-bottom:20px;display:flex;align-items:center;justify-content:center;}.sdg-svg-spinner{width:80px;height:80px;animation:sdgSpin 2s linear infinite;}.sdg-spinner-group{transform-origin:25px 25px;animation:sdgPulse 1.5s ease-in-out infinite alternate;}@keyframes sdgSpin{0%{transform:rotate(0deg);}100%{transform:rotate(360deg);}}@keyframes sdgPulse{0%{transform:scale(1);opacity:1;}100%{transform:scale(1.1);opacity:0.8;}}`;
            document.head.appendChild(style);
        }

        // Confidence bar animation (tidak diubah dari v2.3)
        setTimeout(function() {
            document.querySelectorAll('.confidence-fill').forEach(function(fill, index) {
                const width = fill.style.width;
                fill.style.width = '0%';
                fill.style.transition = 'width 0.8s ease-out';
                setTimeout(function() { fill.style.width = width; }, 100 + (index * 50));
            });
        }, 300);

        // SDG card animation (tidak diubah dari v2.3)
        if (document.getElementById('resultsSection')) {
            const style = document.createElement('style');
            style.textContent = `.animate-in{animation:slideInUp 0.6s cubic-bezier(0.4,0,0.2,1) forwards;}@keyframes slideInUp{from{opacity:0;transform:translateY(30px);}to{opacity:1;transform:translateY(0);}}.results-card,.work-item{opacity:1;transform:translateY(0);}.confidence-fill{transition:width 0.8s cubic-bezier(0.4,0,0.2,1);}`;
            document.head.appendChild(style);
            setTimeout(function() {
                document.querySelectorAll('.sdg-card').forEach(function(element, index) {
                    setTimeout(function() { element.classList.add('animate-in'); }, index * 100);
                });
            }, 200);
        }
    });
</script>

<!-- Statcounter (tidak diubah dari v2.3) -->
<script type="text/javascript">var sc_project=13147842;var sc_invisible=1;var sc_security="db94f6d5";</script>
<script type="text/javascript" src="https://www.statcounter.com/counter/counter.js" async></script>
<noscript><div class="statcounter"><a title="Web Analytics Made Easy - Statcounter" href="https://statcounter.com/" target="_blank"><img class="statcounter" src="https://c.statcounter.com/13147842/0/db94f6d5/1/" alt="Web Analytics Made Easy - Statcounter" referrerPolicy="no-referrer-when-downgrade"></a></div></noscript>

</body>
</html>
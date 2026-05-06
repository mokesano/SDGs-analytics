<?php
/**
 * SDG Classification Presentation Interface
 * Interface modern untuk menampilkan hasil analisis SDG
 *
 * Perubahan dari v2.3 (MINIMAL – semua bagian original tetap utuh):
 * + Proxy block ditambah di atas untuk menghindari WAF
 * + ALL AJAX ARCHITECTURE: ORCID dan DOI diproses via AJAX (tanpa reload halaman)
 * + Progress section unified untuk DOI & ORCID
 * + SEMUA FITUR UI (Chatbot, Navbar, Footer) KEMBALI UTUH.
 *
 * @version 2.0.1 - Full AJAX with Complete UI
 * @author Rochmady and Wizdam Team
 */

// ================================================================
// BAGIAN #0 – PROXY (TAMBAHAN BARU, harus paling atas)
// Menangani request AJAX dari JavaScript
// Direct PHP include – tidak ada HTTP request, tidak kena WAF
// ================================================================
define('API_FILE_PATH', __DIR__ . '/api/SDG_Classification_API.php');
define('AJAX_BATCH_SIZE', 5);

// Cek POST dulu (AJAX), lalu GET (direct/legacy)
$_sdg_post = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['_sdg'])) {
    $_sdg_post = $_POST;
} elseif (isset($_GET['proxy_action'])) {
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

    $jpos = false;
    for ($i=0,$l=strlen($raw);$i<$l;$i++) { if ($raw[$i]==='{' || $raw[$i]==='[') { $jpos=$i; break; } }
    $_SERVER['REQUEST_METHOD'] = $orig_method; 
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>SDGs Classification Analysis | Wizdam AI-scola</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="SDGs classification analysis uses a hybrid method combining keyword matching, semantic similarity, research depth, and causal analysis to assess research relevance to the SDGs." />
    <meta name="keywords" content="hybrid method; keyword matching; semantic similarity; research depth; causal analysis" />
    <meta name="access" content="Yes" />
    <meta name="robots" content="INDEX,FOLLOW,NOARCHIVE,NOCACHE,NOODP,NOYDIR" />
    <meta name="applicable-device" content="pc,mobile" />
    <link rel="canonical" href="https://wizdam.sangia.org/" />
    <meta name="google-site-verification" content="9mVvrkXamiUxutovEQqEk2eiRcjLUWHLHcwssZo3GYs" />
    <meta name="referrer" content="origin-when-cross-origin" />
    <meta property="og:title" content="SDGs Classification Analysis | Wizdam AI-scola" />
    <meta property="og:url" content="https://wizdam.sangia.org/" />
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="Sangia Publishing" />
    <meta property="og:image" content="https://assets.sangia.org/img/sdgs/scola-cover.jpg" />
    <meta property="og:description" content="This system uses a hybrid method combining keyword matching, semantic similarity, research depth, and causal analysis to assess research relevance to the SDGs." />
    <meta name="robots" content="max-image-preview:large" />
    <meta name="twitter:site" content="@Scola" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:image:alt" content="SDGs Classification Analysis | Wizdam AI-scola" />
    <meta name="website_owner" content="www.sangia.org" />
    <meta name="owner" content="PT. Sangia Research Media and Publishing" />
    <meta name="design" content="Rochmady and Wizdam AI Team" />
    <meta name="publisher" content="Sangia Wizdam" />
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        /* ============================================================
           CSS INI DIKEMBALIKAN 100% UTUH SEPERTI KODE ASLI ANDA
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
        .u-heading3 { margin-bottom: 17px; }
        .info-general { background: white; border-radius: 20px; padding: 30px; margin-bottom: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); transition: all 0.3s ease; }
        .info-general:hover { transform: translateY(-2px); box-shadow: 0 20px 60px rgba(0,0,0,0.12); }
        .personal-info { display: flex; align-items: center; gap: 20px; margin-bottom: 30px; padding: 20px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 15px; }
        .avatar { width: 100px; height: 100px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 7px; display: flex; align-items: center; justify-content: center; color: white; font-size: 40px; font-weight: bold; }
        .personal-info-name h2 { font-size: 1.9em;line-height: 1.7; }
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
        .chart-container:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.15); }
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
        .none-SDG { text-align: center; color: #666; font-style: italic; padding: 17px; background: #f8f9fa; border-radius: 17px; font-size: 14px; }
        
        /* ── Profile Details ── */
        .profile-details { margin: 18px 0 20px; padding: 18px 20px; background: #f8f9fa; border-radius: 12px; border: 1px solid #e9ecef; font-size: 14px; color: #444; display: flex; flex-direction: column; gap: 10px; }
        .profile-bio { font-style: italic; color: #555; line-height: 1.6; }
        .profile-bio i { color: #667eea; margin-right: 6px; }
        .profile-keywords { display: flex; align-items: center; flex-wrap: wrap; gap: 6px; }
        .profile-keywords > i { color: #667eea; margin-right: 4px; flex-shrink: 0; }
        .kw-tag { background: #e8eafd; color: #4a5568; padding: 3px 10px; border-radius: 20px; font-size: 13px; font-weight: 500; }
        .profile-detail-item { display: flex; align-items: flex-start; gap: 8px; flex-wrap: wrap; }
        .profile-detail-item > i { color: #667eea; margin-top: 2px; flex-shrink: 0; width: 16px; }
        .profile-detail-item a { color: #667eea; text-decoration: none; }
        .profile-detail-item a:hover { text-decoration: underline; }
        .ext-id-tag { background: #fff3cd; color: #856404; padding: 2px 9px; border-radius: 10px; font-size: 12px; font-weight: 600; border: 1px solid #ffeeba; margin-right: 4px; }
        .ext-id-tag a { color: #856404; text-decoration: none; }
        .ext-id-tag a:hover { text-decoration: underline; }
        .profile-section { margin-top: 4px; }
        .profile-section-title { font-weight: 700; color: #333; margin-bottom: 6px; }
        .profile-section-title i { color: #667eea; margin-right: 6px; }
        .profile-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 4px; }
        .profile-list li { padding-left: 18px; position: relative; line-height: 1.5; }
        .profile-list li::before { content: '•'; position: absolute; left: 4px; color: #667eea; }
        .affil-role { font-size: 12px; font-weight: 600; background: #e8f4fd; color: #0c5460; padding: 1px 7px; border-radius: 10px; margin-left: 6px; vertical-align: middle; }
        
        /* ── AJAX Progress (Unified untuk DOI dan ORCID) ── */
        #ajaxProgressSection { display:none; background:white; border-radius:20px; padding:25px; margin-bottom:20px; box-shadow:0 10px 30px rgba(0,0,0,.1); max-width:900px; margin-left:auto; margin-right:auto; }
        .ajax-progress-header { display:flex; align-items:center; gap:15px; margin-bottom:15px; }
        .ajax-spinner { width:38px; height:38px; border:4px solid #e9ecef; border-top-color:#667eea; border-radius:50%; animation:spin 1s linear infinite; flex-shrink:0; }
        .ajax-progress-text h3 { font-size:1.05rem; color:#333; margin-bottom:3px; }
        .ajax-progress-text p { font-size:13px; color:#888; }
        .ajax-progress-bar-wrap { background:#e9ecef; border-radius:10px; height:10px; overflow:hidden; margin-bottom:10px; }
        .ajax-progress-bar-fill { height:100%; background:linear-gradient(90deg,#667eea,#764ba2); border-radius:10px; transition:width .5s ease; width:0; }
        .ajax-progress-stats { font-size:13px; color:#888; }
        .ajax-progress-stats span { font-weight:600; color:#333; }
        
        #ajaxResultsSection { display:none; }
        
        /* Footer & Chatbot */
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
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
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
    <!-- NAVBAR -->
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

    <!-- HEADER -->
    <div class="header">
        <h1><i class="fas fa-globe"></i> Welcome! Wizdam AI-scola</h1>
        <h2>Sustainable Development Goals (SDGs) Classification</h2>
        <p>Analysis of research contributions to Sustainable Development Goals using advanced AI classification</p>
    </div>

    <!-- FORM PENCARIAN -->
    <div class="container-search">
        <div class="search-card">
            <form id="analysisForm">
                <div class="form-horizontal">
                    <div class="input-section">
                        <div class="input-group">
                            <input type="text" class="floating-input" name="input_value" id="input_value" placeholder=" " required>
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
                    <input type="checkbox" name="force_refresh" value="1" id="force_refresh">
                    <label for="force_refresh">Force refresh (bypass cache)</label>
                </div>
            </form>
        </div>
    </div>

    <!-- AJAX Progress Section (Unified loader untuk DOI & ORCID) -->
    <div id="ajaxProgressSection">
        <div class="ajax-progress-header">
            <div class="ajax-spinner"></div>
            <div class="ajax-progress-text">
                <h3 id="ajaxProgressTitle">Start analize...</h3>
                <p id="ajaxProgressSubtitle">Please wait, the process is running</p>
            </div>
        </div>
        <div class="ajax-progress-bar-wrap">
            <div class="ajax-progress-bar-fill" id="ajaxProgressBar"></div>
        </div>
        <div class="ajax-progress-stats" id="ajaxProgressStatsContainer">
            Works processing: <span id="ajaxProgressCount">0</span> / <span id="ajaxProgressTotal">0</span>
            &nbsp;|&nbsp; Batch: <span id="ajaxProgressBatch">0</span>
        </div>
    </div>

    <!-- AJAX Results Section -->
    <div class="container" id="ajaxResultsSection"></div>

    <!-- FOOTER -->
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

    <!-- FLOATING ELEMENTS -->
    <div class="floating-elements">
        <button class="floating-btn back-to-top" id="backToTop" aria-label="Back to top"><i class="fas fa-arrow-up"></i></button>
        <button class="floating-btn chatbot-btn" id="chatbotBtn" aria-label="Open chatbot"><i class="fas fa-comments"></i></button>
    </div>

    <!-- CHATBOT MODAL -->
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

<script>
    // ================================================================
    // UI HANDLERS (Navbar, Chatbot, dll dari kode asli)
    // ================================================================
    
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

    // Chatbot Logic
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

    // ================================================================
    // CORE JAVASCRIPT: Unified Validation & AJAX
    // ================================================================
    let isSubmitting = false;
    const SDG_DEFS = <?php echo json_encode($SDG_DEFINITIONS, JSON_UNESCAPED_UNICODE); ?>;
    const AJAX_BATCH = <?php echo AJAX_BATCH_SIZE; ?>;
    let orcidAbortCtrl = null;
    let ajaxWorkIndex  = 0;
    let ajaxSdgChart   = null;
    let ajaxContribChart = null;

    const AJAX_ENDPOINT = '<?php echo htmlspecialchars(strtok($_SERVER['REQUEST_URI'] ?? '/', '?'), ENT_QUOTES); ?>';

    // Utilitas
    function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }
    function escH(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    function safeHtml(s) {
        if (!s) return '';
        const ALLOWED = new Set(['em','i','b','strong','u','sup','sub']);
        let str = String(s).replace(/&lt;/g,'<').replace(/&gt;/g,'>').replace(/&amp;/g,'&').replace(/&quot;/g,'\"').replace(/&#39;/g,"'");
        str = str.replace(/<(\/?)(\w+)([^>]*)>/g, (match, slash, tagName) => ALLOWED.has(tagName.toLowerCase()) ? '<' + slash + tagName.toLowerCase() + '>' : match.replace(/</g,'&lt;').replace(/>/g,'&gt;'));
        return str.replace(/&(?!(?:amp|lt|gt|quot|apos|#\d+|#x[\da-f]+);)/gi, '&amp;');
    }

    // Input validation
    function detectInputType(value) {
        value = value.trim();
        if (/orcid\.org\/(\d{4}-\d{4}-\d{4}-\d{3}[\dX])/i.test(value)) return 'orcid';
        if (/^(\d{4}-\d{4}-\d{4}-\d{3}[\dX])$/.test(value)) return 'orcid';
        if (value.length >= 7 && value.indexOf('/') !== -1) {
            if (/^10\.\d+\//.test(value) || /doi\.org\//.test(value) || /dx\.doi\.org\//.test(value)) return 'doi';
        }
        return null;
    }

    function validateOrcid(orcid) {
        orcid = orcid.trim();
        if (!/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/.test(orcid) || orcid.length !== 19) return false;
        const digits = orcid.replace(/-/g, '').slice(0, -1);
        const checkDigit = orcid.slice(-1);
        let total = 0;
        for (let i = 0; i < digits.length; i++) total = (total + parseInt(digits[i])) * 2;
        const result = (12 - (total % 11)) % 11;
        return checkDigit === ((result === 10) ? 'X' : result.toString());
    }

    // UI State Management
    function updateInputVisualState(inputElement, labelElement, state) {
        inputElement.classList.remove('input-valid', 'input-invalid', 'input-warning', 'input-checking');
        const states = { valid: ['#28a745','0 0 0 0.2rem rgba(40,167,69,.25)'], invalid: ['#dc3545','0 0 0 0.2rem rgba(220,53,69,.25)'], warning: ['#ffc107','0 0 0 0.2rem rgba(255,193,7,.25)'], checking: ['#007bff','0 0 0 0.2rem rgba(0,123,255,.25)'], default: ['#e1e5e9',''] };
        if (state !== 'default') inputElement.classList.add('input-' + state);
        if (states[state]) { inputElement.style.borderColor = states[state][0]; inputElement.style.boxShadow = states[state][1]; }
        if (labelElement) { labelElement.style.color = state === 'default' ? '#495057' : states[state][0]; labelElement.style.fontWeight = state === 'default' ? 'normal' : '600'; }
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
            let cleaned = value;
            if (value.includes('orcid.org/')) { const m = value.match(/orcid\.org\/(\d{4}-\d{4}-\d{4}-\d{3}[\dX])/i); if(m) cleaned = m[1]; }
            if (validateOrcid(cleaned)) { statusElement.className = 'input-status orcid-detected'; statusIcon.className = 'fas fa-check-circle'; statusText.textContent = 'ORCID Valid'; updateInputVisualState(inputElement, labelElement, 'valid'); }
            else { statusElement.className = 'input-status invalid'; statusIcon.className = 'fas fa-exclamation-triangle'; statusText.textContent = 'Invalid ORCID checksum'; updateInputVisualState(inputElement, labelElement, 'invalid'); }
        } else if (detectedType === 'doi') {
            statusElement.className = 'input-status doi-detected'; statusIcon.className = 'fas fa-check-circle'; statusText.textContent = 'DOI format detected'; updateInputVisualState(inputElement, labelElement, 'valid');
        } else {
            statusElement.className = 'input-status invalid'; statusIcon.className = 'fas fa-exclamation-triangle'; statusText.textContent = 'Unrecognised format'; updateInputVisualState(inputElement, labelElement, 'invalid');
        }
        updateSubmitButtonState(value, detectedType);
    }

    function updateSubmitButtonState(value, detectedType) {
        const btn = document.getElementById('submitBtn');
        if (value.trim().length > 0 && (detectedType || validateOrcid(value))) { btn.disabled = false; btn.style.opacity = '1'; }
        else { btn.disabled = true; btn.style.opacity = '0.7'; }
    }

    function resetSubmitButton() {
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.innerHTML = '<i class="fas fa-search"></i> Analysis';
        submitBtn.disabled = false;
        isSubmitting = false;
    }

    function toggleAnalysis(idx) {
        const div = document.getElementById('analysis-' + idx);
        if (!div) return;
        const btn = div.previousElementSibling.querySelector('.show-more-btn');
        if (div.classList.contains('show')) { div.classList.remove('show'); if(btn) btn.innerHTML = '<i class="fas fa-chart-bar"></i> Show Details'; }
        else { div.classList.add('show'); if(btn) btn.innerHTML = '<i class="fas fa-eye-slash"></i> Hide Details'; }
    }

    // ================================================================
    // UNIFIED AJAX LOGIC (Membungkus DOI dan ORCID)
    // ================================================================
    async function ajaxCall(action, params) {
        const form = new URLSearchParams();
        form.append('_sdg', action);
        if (params) Object.entries(params).forEach(([k, v]) => { if (v !== undefined) form.append(k, String(v)); });
        
        const resp = await fetch(AJAX_ENDPOINT, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: form.toString(), signal: orcidAbortCtrl.signal });
        let data;
        try { data = await resp.json(); } catch (_) { throw new Error('Response is not valid JSON. Server error occurred.'); }
        if (!resp.ok || data.status === 'error') throw new Error(data.message || 'HTTP ' + resp.status);
        return data;
    }

    function ajaxShowProgress(title, subtitle, isDoi = false) {
        document.getElementById('ajaxProgressSection').style.display = 'block';
        document.getElementById('ajaxProgressTitle').textContent = title;
        document.getElementById('ajaxProgressSubtitle').textContent = subtitle;
        document.getElementById('ajaxProgressStatsContainer').style.display = isDoi ? 'none' : 'block';
    }
    
    function ajaxHideProgress() { setTimeout(() => { document.getElementById('ajaxProgressSection').style.display = 'none'; }, 2000); }
    
    function ajaxSetBar(done, total) {
        const pct = total > 0 ? Math.round((done / total) * 100) : 0;
        document.getElementById('ajaxProgressBar').style.width = pct + '%';
        document.getElementById('ajaxProgressCount').textContent = done;
        document.getElementById('ajaxProgressTotal').textContent = total;
    }

    function buildAjaxDetailedAnalysis(work, idx) {
        if (!work.detailed_analysis || !Object.keys(work.detailed_analysis).length) return `<div class="detailed-analysis" id="analysis-${idx}"><p style="text-align:center;">No detailed analysis available.</p></div>`;
        let html = `<div class="detailed-analysis" id="analysis-${idx}">`;
        Object.entries(work.detailed_analysis).forEach(([sdg, analysis]) => {
            const def = SDG_DEFS[sdg] || { title: sdg };
            const comp = analysis.components || {};
            const compsHtml = ['keyword_score','similarity_score','substantive_score','causal_score'].map(k => `<div class="component-score"><div class="component-label">${k.replace('_score','').replace('_',' ')}</div><div class="component-value">${(comp[k]||0).toFixed(3)}</div></div>`).join('');
            const evidHtml = (analysis.evidence && analysis.evidence.keyword_matches || []).slice(0,2).map(m => `<div class="evidence-title">Keyword Evidence:</div><div class="evidence-item"><strong>${escH(m.keyword||'')}</strong>: ${m.context||''}</div>`).join('');
            html += `<div class="analysis-section"><h5>${escH(sdg+': '+(def.title||sdg))} <span style="color:#667eea">(Score: ${(analysis.score||0).toFixed(3)})</span></h5><div class="analysis-components">${compsHtml}</div><div class="analysis-info"><span class="info-badge">${escH(analysis.contributor_type&&analysis.contributor_type.type||'–')}</span><span class="info-badge confidence">${escH(analysis.confidence_level||'–')}</span></div>${evidHtml}</div>`;
        });
        return html + '</div>';
    }

    // ── RENDERER UNTUK SINGLE DOI ──
    function ajaxRenderDoi(data) {
        const list = document.getElementById('ajaxResultsSection');
        if (data.error) {
            list.innerHTML = `<div class="error-message"><i class="fas fa-exclamation-triangle"></i> <strong>Error:</strong> ${escH(data.error)}</div>`;
            return;
        }

        const year = data.published_date ? new Date(data.published_date).getFullYear() : '2024';
        const titleHtml = safeHtml(data.title || '(Tanpa judul)');
        const doiLink = data.doi ? `<div class="work-meta-row"><i class="fas fa-link"></i><span>DOI: <a href="https://doi.org/${escH(data.doi)}" target="_blank">${escH(data.doi)}</a></span></div>` : '';
        
        let contribs = '';
        if (data.authors && data.authors.length) {
            const shown = data.authors.slice(0, 17).map(n => escH(n)).join(', ');
            const more = data.authors.length > 17 ? ' <i>et al.</i>' : '';
            const icon = data.authors.length === 1 ? 'fa-user' : (data.authors.length === 2 ? 'fa-user-group' : 'fa-users');
            contribs = `<div class="work-meta-row"><i class="fas ${icon}"></i><span>${shown}${more}</span></div>`;
        }

        const journal = data.journal ? `<div class="work-meta-row"><i class="fas fa-book"></i><span>${escH(data.journal)}</span></div>` : '';
        const abstract = data.abstract ? `<div class="work-abstract"><strong>Abstract:</strong> ${escH(data.abstract)}</div>` : '';
        const sdgCount = data.sdgs ? data.sdgs.length : 0;

        let sdgsHtml = '';
        if (data.sdgs && data.sdgs.length) {
            const sdgTags = data.sdgs.map(sdg => {
                const def = SDG_DEFS[sdg] || { color: '#666', title: sdg, svg_url: '' };
                const conf = data.sdg_confidence && data.sdg_confidence[sdg] ? (data.sdg_confidence[sdg]*100).toFixed(1) : '–';
                return `<div class="work-sdg-tag" style="background:${def.color}"><div class="sdg-mini-icon"><img src="${escH(def.svg_url)}" width="20" height="20"></div><span>${escH(sdg)} <span class="sdg-confidence-info">(${conf}%)</span></span></div>`;
            }).join('');
            
            const detailedHtml = buildAjaxDetailedAnalysis(data, 'doi_single');
            sdgsHtml = `<div style="display:flex;justify-content:space-between;align-items:center;"><div class="work-sdgs">${sdgTags}</div><button class="show-more-btn" onclick="toggleAnalysis('doi_single')"><i class="fas fa-chart-bar"></i> Show Details</button></div>${detailedHtml}`;
        } else {
            sdgsHtml = `<div class="none-SDG"><i class="fas fa-info-circle"></i> No SDGs were identified for this article.</div>`;
        }

        list.innerHTML = `
        <h3 class="u-heading3"><i class="fas fa-file-alt"></i> Article Analysis</h3>
        <div class="work-item" style="animation: slideInUp 0.6s cubic-bezier(0.4,0,0.2,1) forwards;">
            <div class="work-header">
                <div class="work-title">${titleHtml}</div>
                <div class="work-year">${year}</div>
            </div>
            <div class="work-meta">
                ${doiLink}
                ${contribs}
                ${journal}
                <div class="work-meta-row"><i class="fas fa-chart-line"></i><span>${sdgCount} Identified SDGs</span></div>
            </div>
            ${abstract}
            <div class="work-sdgs-section">
                ${sdgsHtml}
            </div>
        </div>`;
        list.style.display = 'block';
    }

    // ── FUNGSI EKSEKUSI AJAX DOI (BARU) ──
    async function startDoiAjax(doi, forceRefresh) {
        orcidAbortCtrl = new AbortController();
        const rfParam = forceRefresh ? { refresh: 'true' } : {};

        document.getElementById('ajaxResultsSection').innerHTML = '';
        document.getElementById('ajaxResultsSection').style.display = 'none';

        try {
            // Tampilkan loader progres bar khusus untuk style DOI
            ajaxShowProgress('Analyzing DOI...', 'Fetching abstract data and mapping SDGs...', true);
            ajaxSetBar(50, 100); 
            
            const data = await ajaxCall('doi', Object.assign({ doi }, rfParam));
            
            ajaxSetBar(100, 100);
            ajaxRenderDoi(data);
            
            ajaxShowProgress('Analysis Complete ✓', 'Displaying article results.', true);
            ajaxHideProgress();

            setTimeout(() => { document.getElementById('ajaxResultsSection').scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 400);
        } catch (err) {
            if (err.name !== 'AbortError') {
                document.getElementById('ajaxProgressSection').style.display = 'none';
                document.getElementById('ajaxResultsSection').innerHTML = `<div class="error-message"><i class="fas fa-exclamation-triangle"></i> <strong>Error:</strong> Failed to retrieve data. Server responded: ${escH(err.message)}</div>`;
                document.getElementById('ajaxResultsSection').style.display = 'block';
            }
        } finally {
            resetSubmitButton();
        }
    }

    // ── RENDERER UNTUK ORCID (Milik Anda yang asli) ──
    function ajaxRenderPersonal(info, total) {
        if (!info) return;
        const initials = (info.name||'NN').split(' ').filter(w=>w).slice(0,2).map(w=>w[0]).join('').toUpperCase() || 'NN';

        let affiliHtml = '';
        if (info.affiliations && info.affiliations.length) {
            info.affiliations.forEach(a => {
                const role  = a.role       ? `<span class="affil-role">${escH(a.role)}</span>` : '';
                const dept  = a.department ? ` &ndash; ${escH(a.department)}` : '';
                const year  = a.start_date ? ` (${escH(a.start_date.slice(0,4))}${a.is_current ? '–now' : (a.end_date ? '–'+a.end_date.slice(0,4) : '')})` : '';
                affiliHtml += `<p><i class="fas fa-university"></i> <strong>${escH(a.organization)}</strong>${dept}${role}${year}</p>`;
            });
        } else if (info.institutions && info.institutions.length) {
            affiliHtml = '<p><i class="fas fa-university"></i> ' + escH(info.institutions.slice(0,3).join(', ')) + (info.institutions.length>3?' <em>et al.</em>':'') + '</p>';
        }

        const bioHtml = info.bio ? `<div class="profile-bio"><i class="fas fa-quote-left"></i>${escH(info.bio)}</div>` : '';

        let kwHtml = '';
        if (info.keywords && info.keywords.length) {
            const tags = info.keywords.map(k=>`<span class="kw-tag">${escH(k)}</span>`).join('');
            kwHtml = `<div class="profile-keywords"><i class="fas fa-tags"></i><strong>Research interests:</strong> ${tags}</div>`;
        }

        let emailHtml = '';
        if (info.emails && info.emails.length) {
            const links = info.emails.map(e=>`<a href="mailto:${escH(e)}">${escH(e)}</a>`).join(', ');
            emailHtml = `<div class="profile-detail-item"><i class="fas fa-envelope"></i><span>${links}</span></div>`;
        }

        let extIdHtml = '';
        if (info.external_ids && info.external_ids.length) {
            const ids = info.external_ids.map(x => {
                const lbl = escH((x.type||'ID').toUpperCase());
                const val = escH(x.value||'');
                return x.url ? `<span class="ext-id-tag"><a href="${escH(x.url)}" target="_blank">${lbl}: ${val}</a></span>` : `<span class="ext-id-tag">${lbl}: ${val}</span>`;
            }).join(' ');
            extIdHtml = `<div class="profile-detail-item"><i class="fas fa-id-badge"></i><span>${ids}</span></div>`;
        }

        let urlHtml = '';
        if (info.researcher_urls && info.researcher_urls.length) {
            const links = info.researcher_urls.filter(u=>u.url).map(u=> `<a href="${escH(u.url)}" target="_blank" rel="noopener">${escH(u.name||u.url)}</a>` ).join(' &nbsp;|&nbsp; ');
            if (links) urlHtml = `<div class="profile-detail-item"><i class="fas fa-link"></i><span>${links}</span></div>`;
        }

        let eduHtml = '';
        if (info.education_history && info.education_history.length) {
            const items = info.education_history.map(e => {
                const dept   = e.department ? ` (${escH(e.department)})` : '';
                const degree = e.degree     ? `: ${escH(e.degree)}`      : '';
                const yr1  = e.start_date ? e.start_date.slice(0,4) : '';
                const yr2  = e.end_date   ? e.end_date.slice(0,4)   : 'present';
                const yrs  = yr1 ? ` (${yr1}–${yr2})` : '';
                return `<li>${escH(e.organization)}${dept}${degree}${yrs}</li>`;
            }).join('');
            eduHtml = `<div class="profile-section"><div class="profile-section-title"><i class="fas fa-graduation-cap"></i>Education</div><ul class="profile-list">${items}</ul></div>`;
        }

        const hasDetails = bioHtml || kwHtml || emailHtml || extIdHtml || urlHtml || eduHtml;
        const profileDetailsHtml = hasDetails ? `<div class="profile-details">${bioHtml}${kwHtml}${emailHtml}${extIdHtml}${urlHtml}${eduHtml}</div>` : '';

        document.getElementById('ajaxResultsSection').innerHTML = `
        <div class="info-general">
          <div class="personal-info">
            <div class="avatar">${escH(initials)}</div>
            <div class="personal-info-name">
              <h2 class="info-name">${escH(info.name||'–')}</h2>
              <p class="info-orcid"><i class="fab fa-orcid"></i> ${escH(info.orcid||'')}</p>
              ${affiliHtml}
            </div>
          </div>
          ${profileDetailsHtml}
          <div class="stats-grid">
            <div class="stat-card"><div class="stat-number" id="ajaxStatWorks">${total}</div><div class="stat-label">Total Works</div></div>
            <div class="stat-card"><div class="stat-number" id="ajaxStatSdgs">–</div><div class="stat-label">Identified SDGs</div></div>
            <div class="stat-card"><div class="stat-number" id="ajaxStatRelevant">–</div><div class="stat-label">Relevant Contribution</div></div>
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
            const abstract = work.abstract ? `<div class="work-abstract"><strong>Abstract:</strong> ${escH(work.abstract)}</div>` : '';
            const doi = work.doi ? `<div class="work-meta-row"><i class="fas fa-link"></i><span>DOI: <a href="https://doi.org/${escH(work.doi)}" target="_blank" rel="noopener">${escH(work.doi)}</a></span></div>` : '';
            const journal = work.journal ? `<div class="work-meta-row"><i class="fas fa-book"></i><span>${escH(work.journal)}${work.volume?', Vol. '+escH(work.volume):''}${work.issue?' ('+escH(work.issue)+')':''}${work.pages?', pp. '+escH(work.pages):''}</span></div>` : '';
            const contribs = (work.contributors && work.contributors.length)
                ? (() => {
                    const names = work.contributors.map(c=>escH(c.name||'')).filter(Boolean);
                    const shown = names.slice(0,10).join(', ');
                    const more  = names.length > 10 ? ' <em>et al.</em>' : '';
                    const icon  = names.length===1 ? 'fa-user' : (names.length===2 ? 'fa-user-group' : 'fa-users');
                    return `<div class="work-meta-row"><i class="fas ${icon}"></i><span>${shown}${more}</span></div>`;
                  })() : '';
            const workType = work.work_type ? `<div class="work-meta-row"><i class="fas fa-tag"></i><span>${escH(work.work_type.replace(/_/g,' '))}</span></div>` : '';
            const yearBadge = work.year ? `<div class="work-year">${escH(String(work.year))}</div>` : '';
            const titleHtml = safeHtml(work.title || '(Tanpa judul)');
            list.insertAdjacentHTML('beforeend', `
            <div class="work-item">
              <div class="work-header"><div class="work-title">${titleHtml}</div>${yearBadge}</div>
              <div class="work-meta">${doi}${journal}${contribs}${workType}<div class="work-meta-row"><i class="fas fa-chart-line"></i><span>${(work.sdgs||[]).length} Identified SDGs</span></div></div>
              ${abstract}
              <div class="work-sdgs-section">${sdgsHtml}</div>
            </div>`);
            ajaxWorkIndex++;
        });
        const el = document.getElementById('ajaxWorksCount');
        if (el) el.textContent = ajaxWorkIndex;
    }

    function ajaxRenderSummary(summaryData) {
        const summary = summaryData.researcher_sdg_summary || {};
        const profile  = summaryData.contributor_profile     || {};
        const sdgCount = Object.keys(summary).length;
        const relevantCount = Object.values(profile).filter(p => p.dominant_type === 'Relevant Contributor').length;
        const activeCount = Object.values(profile).filter(p => p.dominant_type === 'Active Contributor').length;
        let totalConf = 0, confCount = 0;
        Object.values(summary).forEach(s => { totalConf += s.average_confidence; confCount++; });
        const avgConf = confCount > 0 ? Math.round((totalConf / confCount) * 100) : 0;
        const e = id => document.getElementById(id);
        if (e('ajaxStatSdgs'))   e('ajaxStatSdgs').textContent   = sdgCount;
        if (e('ajaxStatRelevant')) e('ajaxStatRelevant').textContent = relevantCount;
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
            ajaxShowProgress('Fetching researcher profile…', 'Fetching list of works from ORCID API…');
            const initData = await ajaxCall('init', Object.assign({ orcid }, rfParam));

            const totalWorks = (typeof initData.total_works === 'number' && !isNaN(initData.total_works)) ? initData.total_works : (Array.isArray(initData.works) ? initData.works.length : 0);

            ajaxRenderPersonal(initData.personal_info, totalWorks);
            ajaxSetBar(0, totalWorks);
            document.getElementById('ajaxProgressBatch').textContent = '0';

            if (totalWorks === 0) { ajaxHideProgress(); return; }

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

            let offset = 0, batchNum = 0, done = 0;
            while (offset < totalWorks) {
                batchNum++;
                const to = Math.min(offset + AJAX_BATCH, totalWorks);
                ajaxShowProgress('Analyzing works ' + (offset+1) + '–' + to + ' of ' + totalWorks + '…', 'Batch #' + batchNum + ' | Processing ' + (to - offset) + ' works');
                document.getElementById('ajaxProgressBatch').textContent = batchNum;

                const batchData = await ajaxCall('batch', Object.assign({ orcid, offset: offset, limit: AJAX_BATCH }, rfParam));
                done += batchData.processed;
                ajaxSetBar(done, totalWorks);
                ajaxAppendWorks(batchData.works, offset);

                if (batchData.is_done || batchData.processed === 0) break;
                offset = batchData.next_offset;
                await sleep(350);
            }

            ajaxShowProgress('Calculating SDG summary…', 'Aggregating all batch results…');
            const summaryData = await ajaxCall('summary', { orcid });
            ajaxRenderSummary(summaryData);
            ajaxSetBar(totalWorks, totalWorks);
            ajaxShowProgress('Analyzed DONE! ✓', totalWorks + ' works analyzed in ' + batchNum + ' batch');
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
    // INTERCEPTOR FORM SUBMIT
    // ================================================================
    document.addEventListener('DOMContentLoaded', function() {
        const inputField = document.getElementById('input_value');
        if(inputField) {
            inputField.addEventListener('input', function(e) { updateInputStatus(this.value); });
        }

        const form = document.getElementById('analysisForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // BLOKIR TOTAL reload halaman standar untuk semua event form

                if (isSubmitting) return false;

                const inputValue = document.getElementById('input_value').value.trim();
                const detectedType = detectInputType(inputValue);
                
                if (!detectedType) { alert('Format input tidak dikenali. Masukkan ORCID ID atau DOI.'); return false; }

                const forceRefresh = document.getElementById('force_refresh').checked;
                const submitBtn = document.getElementById('submitBtn');
                isSubmitting = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Analyzing...';
                submitBtn.disabled = true;

                // Routing: Eksekusi ke AJAX yang sesuai
                if (detectedType === 'orcid') {
                    let cleanOrcid = inputValue;
                    const m = cleanOrcid.match(/orcid\.org\/(\d{4}-\d{4}-\d{4}-\d{3}[\dX])/i);
                    if (m) cleanOrcid = m[1];
                    cleanOrcid = cleanOrcid.replace(/[^\d\-X]/gi, '');
                    
                    if(!validateOrcid(cleanOrcid)) { alert("Format checksum ORCID tidak valid."); resetSubmitButton(); return false;}
                    startOrcidAjax(cleanOrcid, forceRefresh);
                } 
                else if (detectedType === 'doi') {
                    let cleanDoi = inputValue.replace(/^https?:\/\/(dx\.)?doi\.org\//i, '').replace(/^doi:/i, '');
                    startDoiAjax(cleanDoi, forceRefresh);
                }
            });
        }
    });
</script>

<!-- Statcounter (Dikembalikan persis seperti asli) -->
<script type="text/javascript">var sc_project=13147842;var sc_invisible=1;var sc_security="db94f6d5";</script>
<script type="text/javascript" src="https://www.statcounter.com/counter/counter.js" async></script>
<noscript><div class="statcounter"><a title="Web Analytics Made Easy - Statcounter" href="https://statcounter.com/" target="_blank"><img class="statcounter" src="https://c.statcounter.com/13147842/0/db94f6d5/1/" alt="Web Analytics Made Easy - Statcounter" referrerPolicy="no-referrer-when-downgrade"></a></div></noscript>

</body>
</html>
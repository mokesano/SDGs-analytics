<?php
/**
 * pages/journal-profile.php — Scopus Journal Profile
 * URL: ?page=journal-profile&issn=XXXX-XXXX
 */

require_once PROJECT_ROOT . '/includes/sdg_subject_mapping.php';

$issn_raw  = trim($_GET['issn'] ?? '');
$issn_clean = preg_replace('/[^0-9X]/', '', strtoupper($issn_raw));
$formatted_issn = (strlen($issn_clean) === 8)
    ? substr($issn_clean, 0, 4) . '-' . substr($issn_clean, 4, 4)
    : '';

$journal  = null;
$subjects = [];
$sdg_codes = [];

if ($formatted_issn) {
    // 1. Try SQLite
    try {
        $pdo = function_exists('getDb') ? getDb() : null;
        if (!$pdo) {
            $db_path = PROJECT_ROOT . '/database/wizdam.db';
            if (file_exists($db_path)) {
                $pdo = new PDO('sqlite:' . $db_path);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
        }
        if ($pdo) {
            $stmt = $pdo->prepare('SELECT * FROM journals WHERE issn=? OR eissn=?');
            $stmt->execute([$formatted_issn, $formatted_issn]);
            $journal = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if ($journal) {
                $s2 = $pdo->prepare('SELECT subject, asjc_code FROM journal_subjects WHERE journal_id=?');
                $s2->execute([$journal['id']]);
                $subjects  = $s2->fetchAll(PDO::FETCH_COLUMN, 0);
                $sdg_codes = mapSubjectsToSdgs($subjects);
            }
        }
    } catch (Exception $e) { /* silent */ }

    // 2. Try gzip cache
    if (!$journal) {
        $cache_dir  = PROJECT_ROOT . '/cache';
        $cache_file = $cache_dir . '/journal_' . $issn_clean . '.json.gz';
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < 604800) {
            $cached = @json_decode((string)gzdecode(file_get_contents($cache_file)), true);
            if (!empty($cached['success'])) {
                $journal   = $cached;
                $subjects  = is_array($cached['subject_areas'] ?? null)
                    ? array_map(fn($s) => is_array($s) ? ($s['name'] ?? '') : $s, $cached['subject_areas'])
                    : [];
                $sdg_codes = $cached['sdg_codes'] ?? mapSubjectsToSdgs($subjects);
            }
        }
    }
}

// ── Quartile colors ─────────────────────────────────────────────────────────
$q_colors = ['Q1' => '#4C9F38', 'Q2' => '#26BDE2', 'Q3' => '#FD9D24', 'Q4' => '#9B9B9B'];
$quartile = $journal['quartile'] ?? null;
$q_color  = $q_colors[$quartile] ?? '#9B9B9B';

// ── SDG colors ───────────────────────────────────────────────────────────────
$sdg_colors_php = [
    'SDG1'=>'#E5243B','SDG2'=>'#DDA63A','SDG3'=>'#4C9F38','SDG4'=>'#C5192D',
    'SDG5'=>'#FF3A21','SDG6'=>'#26BDE2','SDG7'=>'#FCC30B','SDG8'=>'#A21942',
    'SDG9'=>'#FD6925','SDG10'=>'#DD1367','SDG11'=>'#FD9D24','SDG12'=>'#BF8B2E',
    'SDG13'=>'#3F7E44','SDG14'=>'#0A97D9','SDG15'=>'#56C02B','SDG16'=>'#00689D',
    'SDG17'=>'#19486A',
];
$sdg_titles_php = [
    'SDG1'=>'No Poverty','SDG2'=>'Zero Hunger','SDG3'=>'Good Health',
    'SDG4'=>'Quality Education','SDG5'=>'Gender Equality','SDG6'=>'Clean Water',
    'SDG7'=>'Clean Energy','SDG8'=>'Decent Work','SDG9'=>'Industry & Innovation',
    'SDG10'=>'Reduced Inequalities','SDG11'=>'Sustainable Cities',
    'SDG12'=>'Responsible Consumption','SDG13'=>'Climate Action',
    'SDG14'=>'Life Below Water','SDG15'=>'Life on Land',
    'SDG16'=>'Peace & Justice','SDG17'=>'Partnerships',
];
?>

<style>
.jp-layout { display:flex; gap:0; min-height:calc(100vh - 80px); }
.jp-sidebar { width:200px; flex-shrink:0; background:#f8fafc; border-right:1px solid #e2e8f0; padding:24px 0; position:sticky; top:80px; height:calc(100vh - 80px); overflow-y:auto; }
.jp-sidebar-nav a { display:flex; align-items:center; gap:10px; padding:10px 20px; color:#64748b; text-decoration:none; font-size:.88rem; font-weight:500; border-left:3px solid transparent; transition:all .2s; }
.jp-sidebar-nav a:hover, .jp-sidebar-nav a.active { color:#ff5627; background:#fff5f2; border-left-color:#ff5627; }
.jp-sidebar-nav a i { width:16px; text-align:center; }
.jp-main { flex:1; padding:32px; max-width:960px; }

/* Header */
.jp-header { display:flex; gap:24px; align-items:flex-start; background:#fff; border-radius:12px; padding:28px; box-shadow:0 2px 12px rgba(0,0,0,.07); margin-bottom:24px; }
.jp-cover { width:90px; height:120px; border-radius:8px; background:linear-gradient(135deg,#1a2e45,#0d4f7c); display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.5rem; font-weight:800; flex-shrink:0; }
.jp-title { font-size:1.25rem; font-weight:800; color:#1a2e45; line-height:1.3; margin-bottom:6px; }
.jp-publisher { color:#64748b; font-size:.9rem; margin-bottom:12px; }
.jp-pills { display:flex; flex-wrap:wrap; gap:8px; }
.jp-pill { display:inline-flex; align-items:center; gap:5px; padding:5px 12px; border-radius:20px; font-size:.8rem; font-weight:600; }
.jp-pill.issn { background:#e8f4fd; color:#0d4f7c; }
.jp-pill.oa { background:#fef3c7; color:#92400e; }
.jp-pill.country { background:#f0fdf4; color:#166534; }

/* Metric cards */
.jp-metrics { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:16px; margin-bottom:24px; }
.jp-metric { background:#fff; border-radius:12px; padding:20px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,.06); border:1px solid #e8ecf0; }
.jp-metric-val { font-size:1.6rem; font-weight:800; color:#1a2e45; }
.jp-metric-label { font-size:.78rem; color:#94a3b8; text-transform:uppercase; letter-spacing:.05em; margin-top:4px; }
.jp-metric.quartile .jp-metric-val { font-size:1.3rem; padding:6px 16px; border-radius:8px; display:inline-block; }

/* Sections */
.jp-section { background:#fff; border-radius:12px; padding:24px; box-shadow:0 2px 8px rgba(0,0,0,.06); margin-bottom:20px; }
.jp-section-title { font-size:1rem; font-weight:700; color:#1a2e45; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
.jp-section-title i { color:#ff5627; }

/* SDG badges */
.sdg-grid { display:flex; flex-wrap:wrap; gap:8px; }
.sdg-chip { display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:20px; color:#fff; font-size:.82rem; font-weight:700; }

/* Subject tags */
.subject-tags { display:flex; flex-wrap:wrap; gap:8px; }
.subject-tag { background:#f1f5f9; color:#334155; padding:5px 12px; border-radius:16px; font-size:.82rem; font-weight:500; }

/* CTA */
.jp-cta { background:linear-gradient(135deg,#1a2e45,#0d4f7c); color:#fff; border-radius:12px; padding:28px; text-align:center; margin-bottom:20px; }
.jp-cta h3 { font-size:1.1rem; margin-bottom:8px; }
.jp-cta p { opacity:.8; font-size:.9rem; margin-bottom:16px; }
.jp-cta-form { display:flex; gap:8px; max-width:400px; margin:0 auto; }
.jp-cta-input { flex:1; padding:10px 14px; border-radius:8px; border:none; font-size:.9rem; }
.jp-cta-btn { padding:10px 18px; background:#ff5627; color:#fff; border:none; border-radius:8px; font-weight:700; cursor:pointer; white-space:nowrap; }

/* Empty / not found */
.jp-empty { text-align:center; padding:80px 20px; color:#94a3b8; }
.jp-empty i { font-size:3rem; margin-bottom:16px; display:block; }
.jp-empty h2 { color:#334155; font-size:1.3rem; margin-bottom:8px; }
.btn-primary { display:inline-flex; align-items:center; gap:6px; padding:10px 20px; background:#ff5627; color:#fff; border-radius:8px; text-decoration:none; font-weight:700; font-size:.9rem; transition:opacity .2s; }
.btn-primary:hover { opacity:.88; }
.btn-secondary { display:inline-flex; align-items:center; gap:6px; padding:10px 20px; background:#e2e8f0; color:#334155; border-radius:8px; text-decoration:none; font-weight:600; font-size:.9rem; }

@media (max-width:768px) {
  .jp-layout { flex-direction:column; }
  .jp-sidebar { width:100%; height:auto; position:static; display:flex; overflow-x:auto; border-right:none; border-bottom:1px solid #e2e8f0; padding:8px; }
  .jp-sidebar-nav { display:flex; flex-direction:row; gap:4px; }
  .jp-sidebar-nav a { border-left:none; border-bottom:3px solid transparent; padding:8px 12px; white-space:nowrap; }
  .jp-main { padding:16px; }
  .jp-header { flex-direction:column; }
  .jp-metrics { grid-template-columns:repeat(2,1fr); }
}
</style>

<div id="main-content" class="jp-layout">

  <!-- Sidebar -->
  <aside class="jp-sidebar">
    <nav class="jp-sidebar-nav">
      <a href="#overview" class="active"><i class="fas fa-info-circle"></i> Overview</a>
      <a href="#metrics"><i class="fas fa-chart-bar"></i> Metrics</a>
      <a href="#sdg-mapping"><i class="fas fa-globe"></i> SDG Mapping</a>
      <a href="#subjects"><i class="fas fa-tags"></i> Subject Areas</a>
      <a href="#cta-section"><i class="fas fa-search"></i> Analisis Artikel</a>
      <hr style="margin:8px 16px;border-color:#e2e8f0;">
      <a href="?page=journal-archive"><i class="fas fa-archive"></i> Arsip Jurnal</a>
    </nav>
  </aside>

  <!-- Main Content -->
  <main class="jp-main">

  <?php if (!$formatted_issn): ?>
    <!-- No ISSN — show search form -->
    <div class="jp-empty">
      <i class="fas fa-book-open"></i>
      <h2>Cari Profil Jurnal Scopus</h2>
      <p>Masukkan ISSN jurnal untuk melihat metrik, quartile, SJR, dan mapping SDG.</p>
      <form method="get" style="max-width:380px;margin:24px auto;" action="">
        <input type="hidden" name="page" value="journal-profile">
        <div style="display:flex;gap:8px;">
          <input type="text" name="issn" class="jp-cta-input" style="border:1px solid #e2e8f0;" placeholder="ISSN, e.g. 1234-5678" required>
          <button type="submit" class="jp-cta-btn"><i class="fas fa-search"></i> Cari</button>
        </div>
      </form>
      <p style="font-size:.85rem;margin-top:8px;">atau <a href="?page=journal-archive" style="color:#ff5627;">lihat arsip jurnal</a></p>
    </div>

  <?php elseif (!$journal): ?>
    <!-- ISSN provided but not found -->
    <div class="jp-empty">
      <i class="fas fa-exclamation-triangle" style="color:#FD9D24;"></i>
      <h2>Jurnal Tidak Ditemukan</h2>
      <p>ISSN <code style="background:#f1f5f9;padding:2px 8px;border-radius:4px;"><?= htmlspecialchars($formatted_issn) ?></code> tidak ditemukan di database Scopus.</p>
      <p style="font-size:.85rem;margin-bottom:24px;">Pastikan ISSN benar dan jurnal terindeks di Scopus. Coba cek di <a href="https://www.scopus.com/sources" target="_blank" style="color:#ff5627;">scopus.com/sources</a>.</p>
      <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
        <a href="?page=journal-archive" class="btn-secondary"><i class="fas fa-archive"></i> Arsip Jurnal</a>
        <a href="?page=home" class="btn-primary"><i class="fas fa-home"></i> Beranda</a>
      </div>
    </div>

  <?php else: ?>
    <!-- Journal found -->

    <!-- Header -->
    <div id="overview" class="jp-header">
      <div class="jp-cover">
        <?= strtoupper(mb_substr(preg_replace('/[^A-Za-z ]/', '', $journal['title'] ?? 'J'), 0, 2)) ?>
      </div>
      <div style="flex:1;">
        <div class="jp-title"><?= htmlspecialchars($journal['title'] ?? 'Unknown Journal') ?></div>
        <div class="jp-publisher"><i class="fas fa-building" style="opacity:.6;margin-right:4px;"></i><?= htmlspecialchars($journal['publisher'] ?? '') ?></div>
        <div class="jp-pills">
          <?php if (!empty($journal['issn'])): ?>
          <span class="jp-pill issn"><i class="fas fa-barcode"></i> ISSN <?= htmlspecialchars($journal['issn']) ?></span>
          <?php endif; ?>
          <?php if ($quartile): ?>
          <span class="jp-pill" style="background:<?= $q_color ?>;color:#fff;"><?= htmlspecialchars($quartile) ?></span>
          <?php endif; ?>
          <?php if (!empty($journal['open_access'])): ?>
          <span class="jp-pill oa"><i class="fas fa-unlock"></i> Open Access</span>
          <?php endif; ?>
          <?php if (!empty($journal['country'])): ?>
          <span class="jp-pill country"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($journal['country']) ?></span>
          <?php endif; ?>
          <?php if (!empty($sdg_codes)): ?>
          <span class="jp-pill" style="background:#e8f4fd;color:#0d4f7c;"><i class="fas fa-globe"></i> <?= count($sdg_codes) ?> SDGs</span>
          <?php endif; ?>
        </div>
      </div>
      <div>
        <a href="?page=journal-profile&issn=<?= urlencode($formatted_issn) ?>&refresh=true" class="btn-secondary" style="font-size:.8rem;padding:7px 12px;">
          <i class="fas fa-sync-alt"></i> Refresh
        </a>
      </div>
    </div>

    <!-- Metrics -->
    <div id="metrics" class="jp-metrics">
      <div class="jp-metric">
        <div class="jp-metric-val"><?= isset($journal['citescore']) && $journal['citescore'] !== null ? number_format((float)$journal['citescore'], 1) : '—' ?></div>
        <div class="jp-metric-label">CiteScore</div>
      </div>
      <div class="jp-metric">
        <div class="jp-metric-val"><?= isset($journal['sjr']) && $journal['sjr'] !== null ? number_format((float)$journal['sjr'], 3) : '—' ?></div>
        <div class="jp-metric-label">SJR</div>
      </div>
      <div class="jp-metric">
        <div class="jp-metric-val"><?= isset($journal['snip']) && $journal['snip'] !== null ? number_format((float)$journal['snip'], 3) : '—' ?></div>
        <div class="jp-metric-label">SNIP</div>
      </div>
      <div class="jp-metric quartile">
        <?php if ($quartile): ?>
        <div class="jp-metric-val" style="background:<?= $q_color ?>;color:#fff;"><?= htmlspecialchars($quartile) ?></div>
        <?php else: ?>
        <div class="jp-metric-val" style="color:#94a3b8;">—</div>
        <?php endif; ?>
        <div class="jp-metric-label">Quartile</div>
      </div>
    </div>

    <!-- SDG Mapping -->
    <div id="sdg-mapping" class="jp-section">
      <div class="jp-section-title"><i class="fas fa-globe"></i> SDG Mapping</div>
      <?php if (!empty($sdg_codes)): ?>
      <p style="font-size:.85rem;color:#64748b;margin-bottom:12px;">Berdasarkan subject area jurnal, penelitian di jurnal ini relevan dengan SDG berikut:</p>
      <div class="sdg-grid">
        <?php foreach ($sdg_codes as $sdg):
            $num = (int)substr($sdg, 3);
            $col = $sdg_colors_php[$sdg] ?? '#666';
            $ttl = $sdg_titles_php[$sdg] ?? $sdg;
        ?>
        <span class="sdg-chip" style="background:<?= $col ?>;" title="<?= htmlspecialchars($ttl) ?>">
          <?= htmlspecialchars($sdg) ?> <span style="opacity:.85;font-weight:400;"><?= htmlspecialchars($ttl) ?></span>
        </span>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <p style="color:#94a3b8;font-style:italic;">Mapping SDG belum tersedia untuk jurnal ini.</p>
      <?php endif; ?>
    </div>

    <!-- Subject Areas -->
    <div id="subjects" class="jp-section">
      <div class="jp-section-title"><i class="fas fa-tags"></i> Subject Areas</div>
      <?php if (!empty($subjects)): ?>
      <div class="subject-tags">
        <?php foreach ($subjects as $s): ?>
        <span class="subject-tag"><?= htmlspecialchars(is_array($s) ? ($s['subject'] ?? '') : $s) ?></span>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <p style="color:#94a3b8;font-style:italic;">Tidak ada data subject area.</p>
      <?php endif; ?>
    </div>

    <!-- CTA -->
    <div id="cta-section" class="jp-cta">
      <h3><i class="fas fa-microscope"></i> Analisis Artikel dari Jurnal Ini</h3>
      <p>Paste DOI artikel dari jurnal ini untuk melihat kontribusi SDG-nya secara detail.</p>
      <div class="jp-cta-form">
        <input type="text" id="jtCta" class="jp-cta-input" placeholder="DOI, e.g. 10.xxxx/xxxxx">
        <button class="jp-cta-btn" onclick="window.location.href='?page=home&q='+encodeURIComponent(document.getElementById('jtCta').value)">
          <i class="fas fa-search"></i> Analisis
        </button>
      </div>
    </div>

  <?php endif; ?>
  </main><!-- /.jp-main -->
</div><!-- /.jp-layout -->

<script>
// Highlight active sidebar link on scroll
(function(){
    const links = document.querySelectorAll('.jp-sidebar-nav a[href^="#"]');
    const ids   = Array.from(links).map(l => l.getAttribute('href').slice(1));
    window.addEventListener('scroll', () => {
        let current = ids[0];
        ids.forEach(id => {
            const el = document.getElementById(id);
            if (el && el.getBoundingClientRect().top < 140) current = id;
        });
        links.forEach(l => {
            l.classList.toggle('active', l.getAttribute('href') === '#' + current);
        });
    }, { passive: true });
})();
</script>

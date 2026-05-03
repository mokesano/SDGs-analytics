<?php
/**
 * pages/journal-archive.php — Archive of all Scopus journals checked on Wizdam AI
 * URL: ?page=journal-archive
 */

$journals    = [];
$total_count = 0;
$page_num    = max(1, (int)($_GET['p'] ?? 1));
$per_page    = 12;
$offset      = ($page_num - 1) * $per_page;
$search      = trim($_GET['q'] ?? '');
$q_filter    = in_array($_GET['qf'] ?? '', ['Q1','Q2','Q3','Q4']) ? $_GET['qf'] : '';

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
    if ($pdo) {
        $conditions = ['last_fetched IS NOT NULL'];
        $params = [];
        if ($search) { $conditions[] = '(title LIKE :q OR issn LIKE :q OR publisher LIKE :q)'; $params[':q'] = '%'.$search.'%'; }
        if ($q_filter) { $conditions[] = 'quartile = :qf'; $params[':qf'] = $q_filter; }
        $where = 'WHERE ' . implode(' AND ', $conditions);

        $c = $pdo->prepare("SELECT COUNT(*) FROM journals $where");
        $c->execute($params);
        $total_count = (int)$c->fetchColumn();

        $stmt = $pdo->prepare("SELECT * FROM journals $where ORDER BY last_fetched DESC LIMIT :lim OFFSET :off");
        $params[':lim'] = $per_page; $params[':off'] = $offset;
        $stmt->execute($params);
        $journals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) { /* silent */ }

$total_pages = $total_count > 0 ? (int)ceil($total_count / $per_page) : 0;
$q_colors    = ['Q1'=>'#4C9F38','Q2'=>'#26BDE2','Q3'=>'#FD9D24','Q4'=>'#9B9B9B'];
?>

<style>
.ja-header-section { background:linear-gradient(135deg,#1a2e45,#0d4f7c); color:#fff; padding:48px 0 32px; margin-bottom:0; }
.ja-filter-bar { background:#fff; border-bottom:1px solid #e2e8f0; padding:16px 0; position:sticky; top:80px; z-index:100; box-shadow:0 2px 8px rgba(0,0,0,.06); }
.ja-filter-inner { display:flex; gap:12px; flex-wrap:wrap; align-items:center; }
.ja-search { flex:1; min-width:200px; position:relative; }
.ja-search input { width:100%; padding:9px 14px 9px 36px; border:1px solid #e2e8f0; border-radius:8px; font-size:.9rem; outline:none; }
.ja-search input:focus { border-color:#ff5627; }
.ja-search i { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; }
.qa-pill { padding:7px 14px; border-radius:20px; border:1.5px solid; cursor:pointer; font-size:.83rem; font-weight:600; text-decoration:none; transition:all .2s; }
.qa-pill.all { border-color:#94a3b8; color:#64748b; }
.qa-pill.all.active, .qa-pill.all:hover { background:#64748b; color:#fff; }
.qa-pill.q1 { border-color:#4C9F38; color:#4C9F38; } .qa-pill.q1.active, .qa-pill.q1:hover { background:#4C9F38; color:#fff; }
.qa-pill.q2 { border-color:#26BDE2; color:#26BDE2; } .qa-pill.q2.active, .qa-pill.q2:hover { background:#26BDE2; color:#fff; }
.qa-pill.q3 { border-color:#FD9D24; color:#FD9D24; } .qa-pill.q3.active, .qa-pill.q3:hover { background:#FD9D24; color:#fff; }
.qa-pill.q4 { border-color:#9B9B9B; color:#9B9B9B; } .qa-pill.q4.active, .qa-pill.q4:hover { background:#9B9B9B; color:#fff; }
.ja-issn-form { display:flex; gap:6px; }
.ja-issn-form input { padding:8px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:.85rem; width:140px; }
.ja-issn-form button { padding:8px 14px; background:#ff5627; color:#fff; border:none; border-radius:8px; font-weight:600; cursor:pointer; font-size:.85rem; }
.ja-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:20px; }
.ja-card { background:#fff; border-radius:12px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,.07); border:1px solid #e8ecf0; display:flex; flex-direction:column; gap:8px; transition:box-shadow .2s,transform .2s; }
.ja-card:hover { box-shadow:0 8px 24px rgba(0,0,0,.12); transform:translateY(-2px); }
.ja-card-head { display:flex; align-items:center; gap:10px; }
.ja-icon { width:42px; height:42px; background:linear-gradient(135deg,#1a2e45,#0d4f7c); border-radius:8px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.1rem; flex-shrink:0; }
.ja-title { font-size:.93rem; font-weight:700; color:#1a2e45; line-height:1.4; }
.ja-publisher { font-size:.8rem; color:#64748b; }
.ja-meta { display:flex; gap:8px; flex-wrap:wrap; }
.ja-badge { padding:3px 10px; border-radius:12px; font-size:.76rem; font-weight:700; color:#fff; }
.ja-stats { display:flex; gap:12px; }
.ja-stat { text-align:center; }
.ja-stat-val { font-size:.95rem; font-weight:700; color:#1a2e45; }
.ja-stat-label { font-size:.7rem; color:#94a3b8; text-transform:uppercase; }
.ja-btn { display:block; text-align:center; padding:8px; background:#f8fafc; color:#334155; border-radius:8px; text-decoration:none; font-size:.83rem; font-weight:600; border:1px solid #e2e8f0; margin-top:4px; transition:all .2s; }
.ja-btn:hover { background:#ff5627; color:#fff; border-color:#ff5627; }
.ja-pagination { display:flex; gap:6px; justify-content:center; padding:32px 0; flex-wrap:wrap; }
.ja-page-btn { padding:7px 14px; border-radius:8px; border:1px solid #e2e8f0; text-decoration:none; color:#334155; font-size:.88rem; transition:all .2s; }
.ja-page-btn.active, .ja-page-btn:hover { background:#ff5627; color:#fff; border-color:#ff5627; }
.ja-empty { text-align:center; padding:80px 20px; color:#94a3b8; grid-column:1/-1; }
.ja-empty i { font-size:3rem; margin-bottom:16px; display:block; }
</style>

<!-- Page Header -->
<div class="ja-header-section">
  <div class="container">
    <div class="section-label" style="color:rgba(255,255,255,.7);background:rgba(255,255,255,.15);display:inline-block;padding:4px 12px;border-radius:20px;font-size:.78rem;font-weight:600;margin-bottom:12px;">Journal Archive</div>
    <h1 style="font-size:2rem;font-weight:800;margin-bottom:8px;">Arsip Jurnal Scopus</h1>
    <p style="opacity:.8;">Semua jurnal yang pernah dicek di Wizdam AI — <?= number_format($total_count) ?> jurnal tersimpan</p>
  </div>
</div>

<!-- Filter Bar -->
<div class="ja-filter-bar">
  <div class="container">
    <form method="get" class="ja-filter-inner">
      <input type="hidden" name="page" value="journal-archive">
      <div class="ja-search">
        <i class="fas fa-search"></i>
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari judul, ISSN, penerbit...">
      </div>
      <?php
        $base = '?page=journal-archive' . ($search ? '&q='.urlencode($search) : '');
        foreach ([''=>'Semua','Q1'=>'Q1','Q2'=>'Q2','Q3'=>'Q3','Q4'=>'Q4'] as $val => $label):
          $cls = strtolower($val ?: 'all');
          $act = ($q_filter === $val) ? 'active' : '';
      ?>
      <a href="<?= $base . ($val ? '&qf='.$val : '') ?>" class="qa-pill <?= $cls ?> <?= $act ?>"><?= $label ?></a>
      <?php endforeach; ?>
      <div class="ja-issn-form">
        <input type="text" id="newIssn" placeholder="Cek ISSN baru..." maxlength="9">
        <button type="button" onclick="if(document.getElementById('newIssn').value) window.location.href='?page=journal-profile&issn='+encodeURIComponent(document.getElementById('newIssn').value)">
          <i class="fas fa-search"></i>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Grid -->
<section class="section" style="padding-top:32px;">
  <div class="container">
    <div class="ja-grid">
      <?php if (empty($journals)): ?>
      <div class="ja-empty">
        <i class="fas fa-book-open"></i>
        <h3 style="color:#334155;">Belum Ada Jurnal Tersimpan</h3>
        <p>Cek jurnal pertama dengan memasukkan ISSN di atas.</p>
        <a href="?page=home" style="display:inline-block;margin-top:12px;padding:10px 20px;background:#ff5627;color:#fff;border-radius:8px;text-decoration:none;font-weight:700;">
          <i class="fas fa-home"></i> Kembali ke Beranda
        </a>
      </div>
      <?php else: ?>
      <?php foreach ($journals as $j):
        $qc  = $q_colors[$j['quartile'] ?? ''] ?? null;
        $initials = strtoupper(mb_substr(preg_replace('/[^A-Za-z ]/','', $j['title'] ?? 'J'), 0, 2));
      ?>
      <div class="ja-card">
        <div class="ja-card-head">
          <div class="ja-icon"><?= $initials ?></div>
          <div style="flex:1;min-width:0;">
            <div class="ja-title" title="<?= htmlspecialchars($j['title'] ?? '') ?>">
              <?= htmlspecialchars(mb_substr($j['title'] ?? 'Unknown Journal', 0, 60) . (mb_strlen($j['title'] ?? '') > 60 ? '…' : '')) ?>
            </div>
            <div class="ja-publisher"><?= htmlspecialchars(mb_substr($j['publisher'] ?? '', 0, 40)) ?></div>
          </div>
        </div>
        <div class="ja-meta">
          <?php if ($j['issn']): ?><span class="ja-badge" style="background:#e8f4fd;color:#0d4f7c;">ISSN <?= htmlspecialchars($j['issn']) ?></span><?php endif; ?>
          <?php if ($qc): ?><span class="ja-badge" style="background:<?= $qc ?>;"><?= htmlspecialchars($j['quartile']) ?></span><?php endif; ?>
          <?php if (!empty($j['open_access'])): ?><span class="ja-badge" style="background:#fef3c7;color:#92400e;">OA</span><?php endif; ?>
          <?php if ($j['country']): ?><span class="ja-badge" style="background:#f0fdf4;color:#166534;"><?= htmlspecialchars($j['country']) ?></span><?php endif; ?>
        </div>
        <div class="ja-stats">
          <?php if ($j['sjr'] !== null): ?>
          <div class="ja-stat"><div class="ja-stat-val"><?= number_format((float)$j['sjr'], 3) ?></div><div class="ja-stat-label">SJR</div></div>
          <?php endif; ?>
          <?php if ($j['h_index']): ?>
          <div class="ja-stat"><div class="ja-stat-val"><?= (int)$j['h_index'] ?></div><div class="ja-stat-label">H-Index</div></div>
          <?php endif; ?>
          <?php if ($j['last_fetched']): ?>
          <div class="ja-stat"><div class="ja-stat-val" style="font-size:.78rem;"><?= date('d M Y', strtotime($j['last_fetched'])) ?></div><div class="ja-stat-label">Dicek</div></div>
          <?php endif; ?>
        </div>
        <a href="?page=journal-profile&issn=<?= urlencode($j['issn'] ?? '') ?>" class="ja-btn">
          <i class="fas fa-external-link-alt"></i> Lihat Profil Jurnal
        </a>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="ja-pagination">
      <?php if ($page_num > 1): ?>
      <a href="?page=journal-archive&p=<?= $page_num-1 ?>&q=<?= urlencode($search) ?><?= $q_filter?'&qf='.$q_filter:'' ?>" class="ja-page-btn">&laquo; Prev</a>
      <?php endif; ?>
      <?php for ($i = max(1,$page_num-2); $i <= min($total_pages,$page_num+2); $i++): ?>
      <a href="?page=journal-archive&p=<?= $i ?>&q=<?= urlencode($search) ?><?= $q_filter?'&qf='.$q_filter:'' ?>" class="ja-page-btn <?= $i===$page_num?'active':'' ?>"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($page_num < $total_pages): ?>
      <a href="?page=journal-archive&p=<?= $page_num+1 ?>&q=<?= urlencode($search) ?><?= $q_filter?'&qf='.$q_filter:'' ?>" class="ja-page-btn">Next &raquo;</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

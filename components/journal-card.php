<?php
/**
 * components/journal-card.php — Reusable Journal Card Component
 *
 * Expected variables:
 *   $journal  array  with keys: title, issn, eissn, publisher, sjr, quartile, h_index, country, open_access
 */

$_j = isset($journal) ? $journal : [];

$_title     = htmlspecialchars($_j['title'] ?? 'Unknown Journal');
$_issn      = htmlspecialchars($_j['issn'] ?? '');
$_publisher = htmlspecialchars($_j['publisher'] ?? '');
$_sjr       = isset($_j['sjr']) ? number_format((float)$_j['sjr'], 3) : 'N/A';
$_quartile  = htmlspecialchars($_j['quartile'] ?? '');
$_hindex    = isset($_j['h_index']) ? (int)$_j['h_index'] : null;
$_country   = htmlspecialchars($_j['country'] ?? '');
$_oa        = !empty($_j['open_access']);

$_q_colors = ['Q1' => '#4C9F38', 'Q2' => '#26BDE2', 'Q3' => '#FD9D24', 'Q4' => '#9B9B9B'];
$_q_color  = $_q_colors[$_quartile] ?? '#9B9B9B';
?>
<div class="journal-card">
    <div class="journal-card-header">
        <div class="journal-icon"><i class="fas fa-book-open"></i></div>
        <div class="journal-meta">
            <?php if ($_quartile): ?>
            <span class="quartile-badge" style="background:<?php echo $_q_color; ?>;color:#fff;padding:2px 8px;border-radius:4px;font-size:.75rem;font-weight:700;"><?php echo $_quartile; ?></span>
            <?php endif; ?>
            <?php if ($_oa): ?>
            <span class="oa-badge" style="background:#FCC30B;color:#333;padding:2px 8px;border-radius:4px;font-size:.75rem;font-weight:700;margin-left:4px;">OA</span>
            <?php endif; ?>
        </div>
    </div>
    <h4 class="journal-title" title="<?php echo $_title; ?>"><?php echo mb_substr($_title, 0, 70) . (mb_strlen($_title) > 70 ? '…' : ''); ?></h4>
    <?php if ($_publisher): ?>
    <div class="journal-publisher"><i class="fas fa-building" style="opacity:.6;margin-right:4px;"></i><?php echo $_publisher; ?></div>
    <?php endif; ?>
    <div class="journal-stats">
        <div class="jstat"><span class="jstat-val"><?php echo $_sjr; ?></span><span class="jstat-label">SJR</span></div>
        <?php if ($_hindex): ?>
        <div class="jstat"><span class="jstat-val"><?php echo $_hindex; ?></span><span class="jstat-label">H-Index</span></div>
        <?php endif; ?>
        <?php if ($_country): ?>
        <div class="jstat"><span class="jstat-val" style="font-size:.8rem;"><?php echo $_country; ?></span><span class="jstat-label">Country</span></div>
        <?php endif; ?>
    </div>
    <?php if ($_issn): ?>
    <div class="journal-issn" style="font-size:.78rem;color:#888;margin-top:8px;">ISSN: <?php echo $_issn; ?></div>
    <?php endif; ?>
    <a href="?page=journal-profile&issn=<?php echo urlencode($_issn); ?>" class="journal-card-btn">
        <i class="fas fa-external-link-alt"></i> Lihat Profil Jurnal
    </a>
</div>
<style>
.journal-card { background:#fff; border-radius:12px; padding:20px; box-shadow:0 2px 12px rgba(0,0,0,.07); border:1px solid #e8ecf0; transition:box-shadow .2s,transform .2s; display:flex; flex-direction:column; gap:8px; }
.journal-card:hover { box-shadow:0 8px 24px rgba(0,0,0,.12); transform:translateY(-2px); }
.journal-card-header { display:flex; align-items:center; gap:10px; }
.journal-icon { width:40px; height:40px; background:linear-gradient(135deg,#1a2e45,#0d4f7c); border-radius:8px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.1rem; flex-shrink:0; }
.journal-title { font-size:.95rem; font-weight:700; color:#1a2e45; line-height:1.4; margin:0; }
.journal-publisher { font-size:.82rem; color:#64748b; }
.journal-stats { display:flex; gap:16px; margin-top:4px; }
.jstat { display:flex; flex-direction:column; align-items:center; }
.jstat-val { font-size:1rem; font-weight:700; color:#1a2e45; }
.jstat-label { font-size:.72rem; color:#94a3b8; text-transform:uppercase; letter-spacing:.05em; }
.journal-card-btn { display:inline-flex; align-items:center; gap:6px; margin-top:8px; padding:8px 14px; background:linear-gradient(135deg,#ff5627,#e0481d); color:#fff; border-radius:8px; text-decoration:none; font-size:.82rem; font-weight:600; transition:opacity .2s; }
.journal-card-btn:hover { opacity:.88; }
</style>

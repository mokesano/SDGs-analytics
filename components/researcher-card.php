<?php
/**
 * components/researcher-card.php — Reusable Researcher Card Component
 *
 * Expected variables (set before including):
 *   $researcher  array  with keys:
 *     name          string
 *     orcid         string
 *     institutions  JSON string or array
 *     total_works   int (optional)
 *     sdg_count     int (optional)
 *     last_fetched  string datetime (optional)
 *     active_count  int (optional)
 */

$_r = isset($researcher) ? $researcher : [];

$_name        = htmlspecialchars($_r['name'] ?? 'Unknown Researcher');
$_orcid       = htmlspecialchars($_r['orcid'] ?? '');
$_total_works = (int)($_r['total_works'] ?? 0);
$_sdg_count   = (int)($_r['sdg_count'] ?? 0);
$_last_fetched = $_r['last_fetched'] ?? null;
$_active_count = isset($_r['active_count']) ? (int)$_r['active_count'] : null;

// Parse institutions
$_inst_raw = $_r['institutions'] ?? '';
if (is_string($_inst_raw)) {
    $_inst_arr = json_decode($_inst_raw, true);
    $_institution = is_array($_inst_arr) && !empty($_inst_arr) ? htmlspecialchars($_inst_arr[0]) : htmlspecialchars($_inst_raw);
} elseif (is_array($_inst_raw) && !empty($_inst_raw)) {
    $_institution = htmlspecialchars($_inst_raw[0]);
} else {
    $_institution = '';
}

// Avatar initials
$_words    = explode(' ', trim($_r['name'] ?? ''));
$_initials = '';
foreach (array_slice($_words, 0, 2) as $_w) {
    $_initials .= strtoupper(mb_substr($_w, 0, 1));
}
if (!$_initials) $_initials = 'R';

// Date formatting
$_date_str = '';
if ($_last_fetched) {
    $_ts = strtotime($_last_fetched);
    $_date_str = $_ts ? date('M j, Y', $_ts) : htmlspecialchars($_last_fetched);
}
?>
<div class="card researcher-card" style="padding:1.25rem 1.5rem;display:flex;flex-direction:column;gap:.75rem;">

    <!-- Header: avatar + name + orcid -->
    <div style="display:flex;align-items:flex-start;gap:1rem;">
        <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,var(--brand,#ff5627),#e0481d);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.875rem;font-weight:700;flex-shrink:0;">
            <?= htmlspecialchars($_initials) ?>
        </div>
        <div style="flex:1;min-width:0;">
            <div style="font-size:1rem;font-weight:700;color:var(--gray-800);margin-bottom:.2rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                <?= $_name ?>
            </div>
            <?php if ($_orcid): ?>
            <a href="https://orcid.org/<?= $_orcid ?>" target="_blank" rel="noopener"
               style="font-size:.75rem;color:var(--gray-400);text-decoration:none;display:flex;align-items:center;gap:.3rem;"
               onmouseover="this.style.color='var(--brand)'" onmouseout="this.style.color='var(--gray-400)'">
                <img src="https://orcid.org/sites/default/files/images/orcid_16x16.png" alt="ORCID" style="width:14px;height:14px;border-radius:50%;">
                <?= $_orcid ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Institution -->
    <?php if ($_institution): ?>
    <div style="font-size:.8rem;color:var(--gray-500);display:flex;align-items:center;gap:.4rem;">
        <i class="fas fa-university" style="color:var(--gray-400);width:14px;"></i>
        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= $_institution ?></span>
    </div>
    <?php endif; ?>

    <!-- Stats Row -->
    <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
        <?php if ($_total_works): ?>
        <span style="font-size:.75rem;color:var(--gray-600);background:var(--gray-100);padding:.2rem .6rem;border-radius:99px;">
            <i class="fas fa-file-alt" style="margin-right:.25rem;color:var(--gray-400);"></i><?= $_total_works ?> works
        </span>
        <?php endif; ?>
        <?php if ($_sdg_count): ?>
        <span style="font-size:.75rem;color:var(--brand,#ff5627);background:var(--brand-muted,#fff2ec);padding:.2rem .6rem;border-radius:99px;font-weight:600;">
            <i class="fas fa-globe" style="margin-right:.25rem;"></i><?= $_sdg_count ?> SDGs
        </span>
        <?php endif; ?>
        <?php if ($_active_count !== null): ?>
        <span style="font-size:.75rem;color:#4c9f38;background:rgba(76,159,56,.1);padding:.2rem .6rem;border-radius:99px;font-weight:600;">
            <i class="fas fa-star" style="margin-right:.25rem;"></i><?= $_active_count ?> active
        </span>
        <?php endif; ?>
    </div>

    <!-- Footer: date + view button -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:.25rem;">
        <?php if ($_date_str): ?>
        <span style="font-size:.7rem;color:var(--gray-400);">
            <i class="fas fa-clock" style="margin-right:.2rem;"></i>Analyzed <?= $_date_str ?>
        </span>
        <?php else: ?>
        <span></span>
        <?php endif; ?>
        <?php if ($_orcid): ?>
        <a href="?page=orcid-profile&orcid=<?= urlencode($_r['orcid'] ?? '') ?>"
           class="btn btn-primary btn-sm"
           style="font-size:.75rem;padding:.3rem .8rem;">
            <i class="fas fa-user"></i> View Profile
        </a>
        <?php endif; ?>
    </div>

</div>

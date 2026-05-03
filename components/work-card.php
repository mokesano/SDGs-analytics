<?php
/**
 * components/work-card.php — Reusable Work/Publication Card Component
 *
 * Expected variables (set before including):
 *   $work  array  with keys:
 *     title             string
 *     doi               string (optional)
 *     abstract          string (optional)
 *     authors           JSON string or array
 *     journal           string (optional)
 *     year              int|string (optional)
 *     sdg_analysis      array of ['sdg_code'=>..., 'contributor_type'=>..., 'confidence_score'=>...]
 *                       OR sdg_codes (comma-separated string) + top_contributor_type + top_confidence
 *     contributor_type  string (optional top-level fallback)
 *     confidence_score  float  (optional top-level fallback)
 */

$_work = isset($work) ? $work : [];

$_title    = htmlspecialchars($_work['title'] ?? 'Untitled Work');
$_doi      = trim($_work['doi'] ?? '');
$_abstract = $_work['abstract'] ?? '';
$_journal  = htmlspecialchars($_work['journal'] ?? '');
$_year     = htmlspecialchars($_work['year'] ?? '');
$_conf     = isset($_work['confidence_score']) ? (float)$_work['confidence_score'] : (isset($_work['top_confidence']) ? (float)$_work['top_confidence'] : null);

// Parse authors
$_authors_raw = $_work['authors'] ?? '';
if (is_string($_authors_raw)) {
    $_authors_decoded = json_decode($_authors_raw, true);
    $_authors = is_array($_authors_decoded) ? $_authors_decoded : [];
} else {
    $_authors = is_array($_authors_raw) ? $_authors_raw : [];
}

$_author_names = [];
foreach ($_authors as $_a) {
    if (is_array($_a) && isset($_a['name'])) {
        $_author_names[] = $_a['name'];
    } elseif (is_string($_a)) {
        $_author_names[] = $_a;
    }
}
$_shown_authors = array_slice($_author_names, 0, 3);
$_author_str    = implode(', ', $_shown_authors);
if (count($_author_names) > 3) {
    $_author_str .= ' <em>et al.</em>';
}

// Parse SDG analysis
$_sdg_items = [];
if (!empty($_work['sdg_analysis']) && is_array($_work['sdg_analysis'])) {
    foreach ($_work['sdg_analysis'] as $_s) {
        if (isset($_s['contributor_type']) && $_s['contributor_type'] !== 'Not Relevant') {
            $_sdg_items[] = $_s;
        }
    }
} elseif (!empty($_work['sdg_codes'])) {
    // Fallback: comma-separated sdg_codes from GROUP_CONCAT
    $_codes = explode(',', $_work['sdg_codes']);
    foreach ($_codes as $_c) {
        $_c = trim($_c);
        if ($_c) {
            $_sdg_items[] = [
                'sdg_code'         => $_c,
                'contributor_type' => $_work['top_contributor_type'] ?? '',
                'confidence_score' => $_work['top_confidence'] ?? null,
            ];
        }
    }
}

// Abstract truncation
$_abstract_full    = $_abstract;
$_abstract_short   = mb_strlen($_abstract) > 200 ? mb_substr($_abstract, 0, 200) . '…' : $_abstract;
$_has_more_abstract = mb_strlen($_abstract) > 200;

$_card_id = 'work-' . substr(md5(($_work['id'] ?? '') . $_title), 0, 8);

// Confidence bar color
$_conf_color = '#94a3b8';
if ($_conf !== null) {
    if ($_conf >= 0.7) $_conf_color = '#4c9f38';
    elseif ($_conf >= 0.4) $_conf_color = '#fcc30b';
    else $_conf_color = '#ef4444';
}
?>
<div class="card work-card" style="margin-bottom:1rem;padding:1.25rem 1.5rem;">

    <!-- Title -->
    <div style="margin-bottom:.6rem;">
        <?php if ($_doi): ?>
            <a href="https://doi.org/<?= htmlspecialchars($_doi) ?>" target="_blank" rel="noopener"
               style="font-size:1rem;font-weight:700;color:var(--gray-800);text-decoration:none;line-height:1.4;"
               onmouseover="this.style.color='var(--brand)'" onmouseout="this.style.color='var(--gray-800)'">
                <?= $_title ?>
                <i class="fas fa-external-link-alt" style="font-size:.65rem;margin-left:.3rem;color:var(--gray-400);"></i>
            </a>
        <?php else: ?>
            <span style="font-size:1rem;font-weight:700;color:var(--gray-800);line-height:1.4;"><?= $_title ?></span>
        <?php endif; ?>
    </div>

    <!-- Authors -->
    <?php if ($_author_str): ?>
    <div style="font-size:.8rem;color:var(--gray-500);margin-bottom:.5rem;">
        <i class="fas fa-users" style="margin-right:.3rem;"></i><?= $_author_str ?>
    </div>
    <?php endif; ?>

    <!-- Journal + Year -->
    <?php if ($_journal || $_year): ?>
    <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem;flex-wrap:wrap;">
        <?php if ($_journal): ?>
        <span style="font-size:.8rem;font-style:italic;color:var(--gray-600);">
            <i class="fas fa-book-open" style="margin-right:.25rem;color:var(--gray-400);"></i><?= $_journal ?>
        </span>
        <?php endif; ?>
        <?php if ($_year): ?>
        <span class="badge badge-dark" style="font-size:.7rem;"><?= $_year ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Abstract -->
    <?php if ($_abstract_short): ?>
    <div style="font-size:.825rem;color:var(--gray-600);line-height:1.55;margin-bottom:.75rem;">
        <span class="abstract-short-<?= $_card_id ?>"><?= htmlspecialchars($_abstract_short) ?></span>
        <?php if ($_has_more_abstract): ?>
        <span class="abstract-full-<?= $_card_id ?>" style="display:none;"><?= htmlspecialchars($_abstract_full) ?></span>
        <button onclick="toggleAbstract('<?= $_card_id ?>')"
                style="background:none;border:none;color:var(--brand);font-size:.775rem;cursor:pointer;padding:0;margin-left:.25rem;"
                id="abs-btn-<?= $_card_id ?>">Show more</button>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- SDG Badges -->
    <?php if (!empty($_sdg_items)): ?>
    <div style="display:flex;flex-wrap:wrap;gap:.35rem;margin-bottom:.75rem;">
        <?php foreach ($_sdg_items as $_si):
            $sdg_code         = $_si['sdg_code'] ?? '';
            $contributor_type = $_si['contributor_type'] ?? '';
            $confidence_score = isset($_si['confidence_score']) ? $_si['confidence_score'] : null;
            $size             = 'sm';
            require __DIR__ . '/sdg-badge.php';
        endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Confidence Score Bar -->
    <?php if ($_conf !== null): ?>
    <div style="margin-top:.5rem;">
        <div style="display:flex;align-items:center;gap:.5rem;">
            <span style="font-size:.7rem;color:var(--gray-400);white-space:nowrap;">Confidence</span>
            <div style="flex:1;height:4px;background:var(--gray-200);border-radius:99px;overflow:hidden;">
                <div style="width:<?= round($_conf * 100) ?>%;height:100%;background:<?= $_conf_color ?>;border-radius:99px;transition:width .4s ease;"></div>
            </div>
            <span style="font-size:.7rem;color:var(--gray-500);white-space:nowrap;font-weight:600;"><?= round($_conf * 100) ?>%</span>
        </div>
    </div>
    <?php endif; ?>

</div>
<script>
function toggleAbstract(id) {
    var shortEl = document.querySelector('.abstract-short-' + id);
    var fullEl  = document.querySelector('.abstract-full-' + id);
    var btn     = document.getElementById('abs-btn-' + id);
    if (!shortEl || !fullEl || !btn) return;
    if (fullEl.style.display === 'none') {
        shortEl.style.display = 'none';
        fullEl.style.display  = 'inline';
        btn.textContent = 'Show less';
    } else {
        shortEl.style.display = 'inline';
        fullEl.style.display  = 'none';
        btn.textContent = 'Show more';
    }
}
</script>

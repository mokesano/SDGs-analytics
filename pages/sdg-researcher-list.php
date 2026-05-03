<?php
$valid_sdgs  = array_map(fn($n) => 'SDG'.$n, range(1, 17));
$valid_types = ['Active Contributor', 'Relevant Contributor', 'Discutor'];

$raw_sdg  = $_GET['sdg']  ?? 'all';
$raw_type = $_GET['type'] ?? 'all';

$selected_sdg  = in_array($raw_sdg, $valid_sdgs) ? $raw_sdg : 'all';
$selected_type = in_array($raw_type, $valid_types) ? $raw_type : 'all';

$per_page = 12;
$page_num = max(1, (int)($_GET['p'] ?? 1));
$offset   = ($page_num - 1) * $per_page;

$researchers = [];
$total_count = 0;

$sdg_colors = [
    'SDG1'  => '#E5243B', 'SDG2'  => '#DDA63A', 'SDG3'  => '#4C9F38',
    'SDG4'  => '#C5192D', 'SDG5'  => '#FF3A21', 'SDG6'  => '#26BDE2',
    'SDG7'  => '#FCC30B', 'SDG8'  => '#A21942', 'SDG9'  => '#FD6925',
    'SDG10' => '#DD1367', 'SDG11' => '#FD9D24', 'SDG12' => '#BF8B2E',
    'SDG13' => '#3F7E44', 'SDG14' => '#0A97D9', 'SDG15' => '#56C02B',
    'SDG16' => '#00689D', 'SDG17' => '#19486A',
];

$db_error = false;

try {
    $db = function_exists('getDb') ? getDb() : new PDO('sqlite:' . PROJECT_ROOT . '/database/wizdam.db');
    if (!function_exists('getDb')) {
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    $where_parts = [];
    $params      = [];

    if ($selected_sdg !== 'all') {
        $where_parts[] = 'ws.sdg_code = :sdg';
        $params[':sdg'] = $selected_sdg;
    }
    if ($selected_type !== 'all') {
        $where_parts[] = 'ws.contributor_type = :type';
        $params[':type'] = $selected_type;
    }

    $where_sql = $where_parts ? 'WHERE ' . implode(' AND ', $where_parts) : '';

    $count_sql = "
        SELECT COUNT(DISTINCT r.id)
        FROM researchers r
        JOIN works w ON w.researcher_id = r.id
        JOIN work_sdgs ws ON ws.work_id = w.id
        $where_sql
    ";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $total_count = (int)$count_stmt->fetchColumn();

    $data_params = array_merge($params, [':lim' => $per_page, ':off' => $offset]);

    $data_sql = "
        SELECT r.name, r.orcid, r.institutions,
               COUNT(DISTINCT w.id)        AS work_count,
               COUNT(DISTINCT ws.sdg_code) AS sdg_count,
               COUNT(ws.id)                AS contrib_count,
               MAX(ws.confidence_score)    AS top_score
        FROM researchers r
        JOIN works w ON w.researcher_id = r.id
        JOIN work_sdgs ws ON ws.work_id = w.id
        $where_sql
        GROUP BY r.id
        ORDER BY contrib_count DESC
        LIMIT :lim OFFSET :off
    ";
    $data_stmt = $db->prepare($data_sql);
    $data_stmt->execute($data_params);
    $researchers = $data_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    $db_error = true;
}

$total_pages = $total_count > 0 ? (int)ceil($total_count / $per_page) : 0;

function srl_initials(string $name): string {
    $words = explode(' ', trim($name));
    $init  = '';
    foreach (array_slice($words, 0, 2) as $w) {
        $init .= strtoupper(mb_substr($w, 0, 1));
    }
    return $init ?: 'R';
}

function srl_institution(string $raw): string {
    $arr = json_decode($raw, true);
    if (is_array($arr) && !empty($arr)) return $arr[0];
    return $raw;
}

function srl_page_url(array $base, int $page): string {
    $params = array_merge($base, ['p' => $page]);
    return '?' . http_build_query($params);
}

$filter_base = ['page' => 'sdg-researcher-list'];
if ($selected_sdg  !== 'all') $filter_base['sdg']  = $selected_sdg;
if ($selected_type !== 'all') $filter_base['type'] = $selected_type;
?>

<style>
.srl-page .page-header {
    background: linear-gradient(135deg, #1a2e45 0%, #0f1e30 100%);
    color: #fff;
    padding: 3rem 0 2.5rem;
    margin-bottom: 0;
}
.srl-page .page-header h1 {
    font-size: 2rem;
    font-weight: 800;
    margin: 0 0 .5rem;
    color: #fff;
}
.srl-page .page-header h1 i { color: #ff5627; margin-right: .5rem; }
.srl-page .page-header p { font-size: 1rem; color: #94a3b8; margin: 0; }

.srl-filter-bar {
    position: sticky;
    top: 0;
    z-index: 100;
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    padding: .875rem 0;
}
.srl-filter-inner {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1.25rem;
    display: flex;
    flex-wrap: wrap;
    gap: .6rem;
    align-items: center;
}
.srl-filter-label {
    font-size: .72rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .06em;
    white-space: nowrap;
}
.srl-pills {
    display: flex;
    flex-wrap: wrap;
    gap: .3rem;
    flex: 1;
}
.srl-pill {
    font-size: .7rem;
    font-weight: 600;
    padding: .22rem .6rem;
    border-radius: 99px;
    border: 1.5px solid #e2e8f0;
    background: #f8fafc;
    color: #475569;
    cursor: pointer;
    transition: all .15s ease;
    white-space: nowrap;
    text-decoration: none;
    display: inline-block;
}
.srl-pill:hover { border-color: #ff5627; color: #ff5627; background: #fff2ec; }
.srl-pill.active { color: #fff !important; }
.srl-type-select {
    font-size: .825rem;
    padding: .38rem .8rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    background: #fff;
    color: #334155;
    cursor: pointer;
    outline: none;
    flex-shrink: 0;
}
.srl-type-select:focus { border-color: #ff5627; }

.srl-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.25rem;
    margin-top: 1.75rem;
    margin-bottom: 2rem;
}

.srl-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    padding: 1.375rem 1.25rem 1.125rem;
    transition: transform .18s ease, box-shadow .18s ease;
    display: flex;
    flex-direction: column;
    gap: .6rem;
    border: 1.5px solid transparent;
}
.srl-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 28px rgba(0,0,0,.11);
    border-color: #ff562720;
}

.srl-card-top {
    display: flex;
    align-items: flex-start;
    gap: .875rem;
}
.srl-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ff5627, #c93e14);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: .9rem;
    font-weight: 800;
    flex-shrink: 0;
    letter-spacing: .02em;
}
.srl-card-info { flex: 1; min-width: 0; }
.srl-card-name {
    font-size: .95rem;
    font-weight: 700;
    color: #1a2e45;
    text-decoration: none;
    line-height: 1.3;
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.srl-card-name:hover { color: #ff5627; }
.srl-card-inst {
    font-size: .75rem;
    color: #64748b;
    margin-top: .2rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.srl-card-badges {
    display: flex;
    flex-wrap: wrap;
    gap: .4rem;
}
.srl-badge {
    font-size: .7rem;
    font-weight: 600;
    padding: .2rem .55rem;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    white-space: nowrap;
}
.srl-badge-works  { background: #eff6ff; color: #3b82f6; }
.srl-badge-sdgs   { background: #fff2ec; color: #ff5627; }
.srl-badge-score  { background: #f0fdf4; color: #16a34a; }

.srl-card-footer {
    border-top: 1px solid #f1f5f9;
    padding-top: .6rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: .72rem;
    color: #94a3b8;
}
.srl-contrib-count { font-weight: 700; color: #1a2e45; font-size: .8rem; }

.srl-empty {
    text-align: center;
    padding: 5rem 2rem;
    color: #64748b;
}
.srl-empty i { font-size: 3.5rem; color: #cbd5e1; margin-bottom: 1rem; display: block; }
.srl-empty h3 { font-size: 1.25rem; font-weight: 700; color: #1e293b; margin-bottom: .5rem; }
.srl-empty p { font-size: .9rem; margin-bottom: 1.5rem; }

.srl-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: .35rem;
    margin: 1.5rem 0 2.5rem;
    flex-wrap: wrap;
}
.srl-pag-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 .6rem;
    border-radius: 8px;
    font-size: .825rem;
    font-weight: 600;
    text-decoration: none;
    color: #475569;
    background: #fff;
    border: 1.5px solid #e2e8f0;
    transition: all .15s ease;
    white-space: nowrap;
}
.srl-pag-btn:hover { border-color: #ff5627; color: #ff5627; background: #fff2ec; }
.srl-pag-btn.active { background: #ff5627; border-color: #ff5627; color: #fff; }
.srl-pag-btn.disabled { opacity: .4; pointer-events: none; }
.srl-pag-ellipsis { font-size: .825rem; color: #94a3b8; padding: 0 .2rem; }

@media (max-width: 600px) {
    .srl-page .page-header { padding: 2rem 0 1.75rem; }
    .srl-page .page-header h1 { font-size: 1.5rem; }
    .srl-grid { grid-template-columns: 1fr; }
}
</style>

<div class="srl-page">

<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-users"></i> Peneliti per SDG</h1>
        <p>
            <?php if ($total_count > 0): ?>
                <?= number_format($total_count) ?> peneliti ditemukan
                <?= $selected_sdg !== 'all' ? ' untuk ' . htmlspecialchars($selected_sdg) : '' ?>
                <?= $selected_type !== 'all' ? ' · ' . htmlspecialchars($selected_type) : '' ?>
            <?php else: ?>
                Jelajahi peneliti berdasarkan SDG dan tipe kontribusi
            <?php endif; ?>
        </p>
    </div>
</div>

<div class="srl-filter-bar">
    <div class="srl-filter-inner">
        <span class="srl-filter-label"><i class="fas fa-filter" style="margin-right:.25rem;"></i>SDG:</span>
        <div class="srl-pills">
            <?php
            $all_url = '?page=sdg-researcher-list'
                . ($selected_type !== 'all' ? '&type=' . urlencode($selected_type) : '');
            ?>
            <a href="<?= $all_url ?>"
               class="srl-pill <?= $selected_sdg === 'all' ? 'active' : '' ?>"
               style="<?= $selected_sdg === 'all' ? 'background:#64748b;border-color:#64748b;' : '' ?>">
                All SDGs
            </a>
            <?php for ($i = 1; $i <= 17; $i++):
                $code = 'SDG' . $i;
                $col  = $sdg_colors[$code] ?? '#64748b';
                $is_active = ($selected_sdg === $code);
                $pill_url  = '?page=sdg-researcher-list&sdg=' . $code
                    . ($selected_type !== 'all' ? '&type=' . urlencode($selected_type) : '');
                $style = $is_active
                    ? "background:{$col};border-color:{$col};"
                    : "border-color:{$col};color:{$col};";
            ?>
            <a href="<?= $pill_url ?>"
               class="srl-pill <?= $is_active ? 'active' : '' ?>"
               style="<?= $style ?>">
                <?= $code ?>
            </a>
            <?php endfor; ?>
        </div>
        <div style="display:flex;align-items:center;gap:.5rem;flex-shrink:0;">
            <span class="srl-filter-label">Tipe:</span>
            <select class="srl-type-select"
                    onchange="location.href='?page=sdg-researcher-list<?= $selected_sdg !== 'all' ? '&sdg=' . urlencode($selected_sdg) : '' ?>&type='+encodeURIComponent(this.value)">
                <option value="all" <?= $selected_type === 'all' ? 'selected' : '' ?>>All Types</option>
                <?php foreach ($valid_types as $t): ?>
                <option value="<?= htmlspecialchars($t) ?>" <?= $selected_type === $t ? 'selected' : '' ?>>
                    <?= htmlspecialchars($t) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<section class="section" style="padding-top:0;">
    <div class="container">

        <?php if ($db_error): ?>
        <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:10px;padding:1.25rem 1.5rem;margin-top:1.5rem;color:#856404;">
            <i class="fas fa-exclamation-triangle" style="margin-right:.5rem;"></i>
            Gagal memuat data. Pastikan database tersedia.
        </div>

        <?php elseif (empty($researchers)): ?>
        <div class="srl-empty">
            <i class="fas fa-users-slash"></i>
            <h3>Tidak Ada Peneliti Ditemukan</h3>
            <p>Belum ada peneliti yang terdaftar<?= $selected_sdg !== 'all' ? ' untuk ' . htmlspecialchars($selected_sdg) : '' ?><?= $selected_type !== 'all' ? ' dengan tipe "' . htmlspecialchars($selected_type) . '"' : '' ?>.</p>
            <a href="?page=home" class="btn btn-primary">Analisis ORCID Sekarang</a>
        </div>

        <?php else: ?>

        <div class="srl-grid">
            <?php foreach ($researchers as $r):
                $init  = srl_initials($r['name'] ?? '');
                $inst  = srl_institution($r['institutions'] ?? '[]');
                $score = isset($r['top_score']) ? round((float)$r['top_score'], 2) : 0;
            ?>
            <div class="srl-card">
                <div class="srl-card-top">
                    <div class="srl-avatar"><?= htmlspecialchars($init) ?></div>
                    <div class="srl-card-info">
                        <a href="?page=orcid-profile&orcid=<?= urlencode($r['orcid'] ?? '') ?>"
                           class="srl-card-name"
                           title="<?= htmlspecialchars($r['name'] ?? 'Unknown') ?>">
                            <?= htmlspecialchars($r['name'] ?? 'Unknown') ?>
                        </a>
                        <div class="srl-card-inst" title="<?= htmlspecialchars($inst) ?>">
                            <?= htmlspecialchars($inst ?: '—') ?>
                        </div>
                    </div>
                </div>

                <div class="srl-card-badges">
                    <span class="srl-badge srl-badge-works">
                        <i class="fas fa-file-alt"></i>
                        <?= (int)($r['work_count'] ?? 0) ?> karya
                    </span>
                    <span class="srl-badge srl-badge-sdgs">
                        <i class="fas fa-globe"></i>
                        <?= (int)($r['sdg_count'] ?? 0) ?> SDG
                    </span>
                    <span class="srl-badge srl-badge-score">
                        <i class="fas fa-star"></i>
                        <?= number_format($score, 2) ?>
                    </span>
                </div>

                <div class="srl-card-footer">
                    <span>
                        <i class="fas fa-link" style="margin-right:.3rem;"></i>
                        <span class="srl-contrib-count"><?= number_format((int)($r['contrib_count'] ?? 0)) ?></span>
                        kontribusi SDG
                    </span>
                    <?php if (!empty($r['orcid'])): ?>
                    <a href="?page=orcid-profile&orcid=<?= urlencode($r['orcid']) ?>"
                       style="color:#ff5627;font-size:.7rem;font-weight:600;text-decoration:none;">
                        Profil <i class="fas fa-arrow-right" style="font-size:.6rem;"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($total_pages > 1):
            $pag_base = ['page' => 'sdg-researcher-list'];
            if ($selected_sdg  !== 'all') $pag_base['sdg']  = $selected_sdg;
            if ($selected_type !== 'all') $pag_base['type'] = $selected_type;
        ?>
        <div class="srl-pagination">
            <a href="<?= srl_page_url($pag_base, $page_num - 1) ?>"
               class="srl-pag-btn <?= $page_num <= 1 ? 'disabled' : '' ?>">
                <i class="fas fa-chevron-left" style="font-size:.7rem;margin-right:.25rem;"></i> Prev
            </a>

            <?php
            $show_pages = [];
            $show_pages[] = 1;
            for ($pp = max(2, $page_num - 2); $pp <= min($total_pages - 1, $page_num + 2); $pp++) {
                $show_pages[] = $pp;
            }
            if ($total_pages > 1) $show_pages[] = $total_pages;
            sort($show_pages);
            $prev_pp = 0;
            foreach ($show_pages as $pp):
                if ($prev_pp && $pp - $prev_pp > 1):
            ?>
                <span class="srl-pag-ellipsis">…</span>
            <?php
                endif;
                $prev_pp = $pp;
            ?>
            <a href="<?= srl_page_url($pag_base, $pp) ?>"
               class="srl-pag-btn <?= $pp === $page_num ? 'active' : '' ?>">
                <?= $pp ?>
            </a>
            <?php endforeach; ?>

            <a href="<?= srl_page_url($pag_base, $page_num + 1) ?>"
               class="srl-pag-btn <?= $page_num >= $total_pages ? 'disabled' : '' ?>">
                Next <i class="fas fa-chevron-right" style="font-size:.7rem;margin-left:.25rem;"></i>
            </a>
        </div>
        <?php endif; ?>

        <?php endif; ?>

    </div>
</section>
</div>

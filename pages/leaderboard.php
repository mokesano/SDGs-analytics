<?php
/**
 * pages/leaderboard.php — Leaderboard Page
 *
 * Displays top researchers ranked by SDG contribution score.
 * Loaded by public/index.php (header/nav/footer already rendered).
 */

$selected_sdg  = preg_replace('/[^A-Za-z0-9]/', '', $_GET['sdg'] ?? 'all');
$selected_type = in_array($_GET['type'] ?? '', ['Active Contributor', 'Relevant Contributor', 'Discutor'])
                 ? $_GET['type'] : 'Active Contributor';

$leaderboard = [];
$top3        = [];

if (function_exists('getDb')) {
    try {
        $db = getDb();

        $where_sdg = ($selected_sdg !== 'all') ? ' AND ws.sdg_code = :sdg ' : '';
        $params    = [':type' => $selected_type];
        if ($selected_sdg !== 'all') $params[':sdg'] = $selected_sdg;

        $sql = "
            SELECT r.name, r.orcid, r.institutions, r.total_works,
                   COUNT(DISTINCT w.id)        AS work_count,
                   COUNT(DISTINCT ws.sdg_code) AS sdg_count,
                   COUNT(ws.id)                AS contrib_count,
                   MAX(ws.confidence_score)    AS top_score
            FROM researchers r
            JOIN works w ON r.id = w.researcher_id
            JOIN work_sdgs ws ON w.id = ws.work_id
            WHERE ws.contributor_type = :type $where_sdg
            GROUP BY r.id
            ORDER BY contrib_count DESC, work_count DESC
            LIMIT 50
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $leaderboard = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $top3 = array_slice($leaderboard, 0, 3);
    } catch (Exception $e) { /* silent */ }
}

// Helper: get initials from name
function lb_initials(string $name): string {
    $words = explode(' ', trim($name));
    $init  = '';
    foreach (array_slice($words, 0, 2) as $w) {
        $init .= strtoupper(mb_substr($w, 0, 1));
    }
    return $init ?: 'R';
}

// Helper: first institution from JSON
function lb_institution(string $raw): string {
    $arr = json_decode($raw, true);
    if (is_array($arr) && !empty($arr)) return $arr[0];
    return $raw;
}

$sdg_colors = [
    'SDG1'  => '#E5243B', 'SDG2'  => '#DDA63A', 'SDG3'  => '#4C9F38',
    'SDG4'  => '#C5192D', 'SDG5'  => '#FF3A21', 'SDG6'  => '#26BDE2',
    'SDG7'  => '#FCC30B', 'SDG8'  => '#A21942', 'SDG9'  => '#FD6925',
    'SDG10' => '#DD1367', 'SDG11' => '#FD9D24', 'SDG12' => '#BF8B2E',
    'SDG13' => '#3F7E44', 'SDG14' => '#0A97D9', 'SDG15' => '#56C02B',
    'SDG16' => '#00689D', 'SDG17' => '#19486A',
];

$type_labels = [
    'Active Contributor'   => ['label' => 'Active Contributor',   'color' => '#4c9f38', 'bg' => 'rgba(76,159,56,.1)'],
    'Relevant Contributor' => ['label' => 'Relevant Contributor', 'color' => '#3b82f6', 'bg' => 'rgba(59,130,246,.1)'],
    'Discutor'             => ['label' => 'Discutor',             'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,.1)'],
];
?>

<style>
/* ── Leaderboard Page Styles ───────────────────────────── */
.leaderboard-page .page-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: #fff;
    padding: 3rem 0 2.5rem;
    margin-bottom: 2rem;
}
.leaderboard-page .page-header h1 {
    font-size: 2rem;
    font-weight: 800;
    margin: 0 0 .5rem;
    color: #fff;
}
.leaderboard-page .page-header h1 i {
    color: #FFD700;
    margin-right: .5rem;
}
.leaderboard-page .page-header p {
    font-size: 1rem;
    color: #94a3b8;
    margin: 0;
}

/* Podium */
.leaderboard-podium {
    display: flex;
    align-items: flex-end;
    justify-content: center;
    gap: 1rem;
    margin: 0 auto 2.5rem;
    max-width: 680px;
}
.podium-card {
    flex: 1;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,.08);
    padding: 1.5rem 1rem 1.25rem;
    text-align: center;
    position: relative;
    transition: transform .2s ease, box-shadow .2s ease;
    border: 2px solid transparent;
}
.podium-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 30px rgba(0,0,0,.12);
}
.podium-card.gold {
    border-color: #FFD700;
    padding-top: 2rem;
    padding-bottom: 1.5rem;
    min-height: 230px;
}
.podium-card.silver { border-color: #C0C0C0; min-height: 200px; }
.podium-card.bronze { border-color: #CD7F32; min-height: 185px; }

.podium-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    font-weight: 800;
    color: #fff;
    margin: 0 auto .75rem;
    position: relative;
}
.podium-card.gold   .podium-avatar { background: linear-gradient(135deg, #FFD700, #f59e0b); width: 72px; height: 72px; }
.podium-card.silver .podium-avatar { background: linear-gradient(135deg, #C0C0C0, #94a3b8); }
.podium-card.bronze .podium-avatar { background: linear-gradient(135deg, #CD7F32, #a16207); }

.podium-rank-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .65rem;
    font-weight: 800;
    color: #fff;
    border: 2px solid #fff;
}
.podium-card.gold   .podium-rank-badge { background: #FFD700; color: #7c5a00; }
.podium-card.silver .podium-rank-badge { background: #C0C0C0; color: #475569; }
.podium-card.bronze .podium-rank-badge { background: #CD7F32; color: #fff; }

.podium-name {
    font-size: .9rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: .2rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.podium-inst {
    font-size: .7rem;
    color: #64748b;
    margin-bottom: .6rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.podium-score {
    font-size: 1.35rem;
    font-weight: 800;
}
.podium-card.gold   .podium-score { color: #f59e0b; }
.podium-card.silver .podium-score { color: #94a3b8; }
.podium-card.bronze .podium-score { color: #CD7F32; }
.podium-score-label {
    font-size: .65rem;
    color: #94a3b8;
    font-weight: 500;
    display: block;
    margin-top: .1rem;
}
.podium-crown {
    font-size: 1.5rem;
    margin-bottom: .25rem;
    display: block;
}

/* Filter bar */
.filter-bar {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    display: flex;
    flex-wrap: wrap;
    gap: .75rem;
    align-items: center;
}
.filter-bar-label {
    font-size: .75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .05em;
    white-space: nowrap;
}
.sdg-pills {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem;
    flex: 1;
}
.sdg-pill {
    font-size: .72rem;
    font-weight: 600;
    padding: .25rem .65rem;
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
.sdg-pill:hover { border-color: var(--brand, #ff5627); color: var(--brand, #ff5627); background: #fff2ec; }
.sdg-pill.active { background: var(--brand, #ff5627); color: #fff; border-color: var(--brand, #ff5627); }
.sdg-pill.sdg-colored.active { color: #fff; }

.type-select {
    font-size: .825rem;
    padding: .4rem .8rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    background: #fff;
    color: #334155;
    cursor: pointer;
    outline: none;
}
.type-select:focus { border-color: var(--brand, #ff5627); }

/* Rank table */
.rank-table-wrap {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
    overflow: hidden;
    margin-bottom: 1.5rem;
}
.rank-table {
    width: 100%;
    border-collapse: collapse;
}
.rank-table th {
    background: #f8fafc;
    font-size: .75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #64748b;
    padding: .875rem 1rem;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}
.rank-table td {
    padding: .875rem 1rem;
    font-size: .875rem;
    color: #334155;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.rank-table tr:last-child td { border-bottom: none; }
.rank-table tbody tr:nth-child(even) td { background: #fafbfd; }
.rank-table tbody tr:hover td { background: #fff7f4; }

.rank-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    font-size: .8rem;
    font-weight: 800;
    color: #fff;
}
.rank-badge.rank-1 { background: linear-gradient(135deg, #FFD700, #f59e0b); color: #7c5a00; }
.rank-badge.rank-2 { background: linear-gradient(135deg, #C0C0C0, #94a3b8); }
.rank-badge.rank-3 { background: linear-gradient(135deg, #CD7F32, #a16207); }
.rank-badge.rank-other { background: #f1f5f9; color: #64748b; }

.researcher-cell {
    display: flex;
    align-items: center;
    gap: .75rem;
}
.researcher-avatar-sm {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--brand, #ff5627), #e0481d);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: .75rem;
    font-weight: 700;
    flex-shrink: 0;
}
.researcher-name { font-weight: 600; color: #1e293b; margin-bottom: .1rem; }
.researcher-orcid { font-size: .7rem; color: #94a3b8; }

.score-pill {
    display: inline-block;
    font-size: .8rem;
    font-weight: 700;
    padding: .25rem .65rem;
    border-radius: 99px;
    background: var(--brand-light, #fff2ec);
    color: var(--brand, #ff5627);
}

/* Sidebar */
.lb-sidebar-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
    padding: 1.25rem;
    margin-bottom: 1.25rem;
}
.lb-sidebar-card h3 {
    font-size: .875rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 1rem;
    display: flex;
    align-items: center;
    gap: .4rem;
}
.lb-sdg-filter-item {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .4rem .5rem;
    border-radius: 6px;
    font-size: .8rem;
    cursor: pointer;
    text-decoration: none;
    color: #334155;
    transition: background .15s;
}
.lb-sdg-filter-item:hover { background: #f8fafc; }
.lb-sdg-filter-item.active { background: var(--brand-light, #fff2ec); color: var(--brand, #ff5627); font-weight: 600; }
.lb-sdg-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #64748b;
}
.empty-state i { font-size: 3.5rem; color: #cbd5e1; margin-bottom: 1rem; display: block; }
.empty-state h3 { font-size: 1.25rem; font-weight: 700; color: #1e293b; margin-bottom: .5rem; }
.empty-state p { font-size: .9rem; margin-bottom: 1.5rem; }

@media (max-width: 768px) {
    .leaderboard-podium { flex-direction: column; align-items: center; }
    .podium-card { width: 100%; max-width: 280px; min-height: auto !important; }
    .lb-layout { flex-direction: column; }
    .lb-sidebar { width: 100%; }
}
</style>

<div class="leaderboard-page">

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-trophy"></i> Leaderboard</h1>
        <p>Peneliti dengan kontribusi tertinggi terhadap Sustainable Development Goals</p>
    </div>
</div>

<section class="section" style="padding-top:0;">
    <div class="container">

        <!-- Filter Bar -->
        <div class="filter-bar">
            <span class="filter-bar-label"><i class="fas fa-filter" style="margin-right:.3rem;"></i>SDG:</span>
            <div class="sdg-pills">
                <a href="?page=leaderboard&sdg=all&type=<?= urlencode($selected_type) ?>"
                   class="sdg-pill <?= $selected_sdg === 'all' ? 'active' : '' ?>">Semua</a>
                <?php for ($i = 1; $i <= 17; $i++):
                    $code = 'SDG' . $i;
                    $col  = $sdg_colors[$code] ?? '#64748b';
                    $is_active = ($selected_sdg === $code);
                    $style = $is_active ? "background:{$col};border-color:{$col};color:#fff;" : "border-color:{$col};color:{$col};";
                ?>
                <a href="?page=leaderboard&sdg=<?= $code ?>&type=<?= urlencode($selected_type) ?>"
                   class="sdg-pill sdg-colored <?= $is_active ? 'active' : '' ?>"
                   style="<?= $style ?>"><?= $code ?></a>
                <?php endfor; ?>
            </div>
            <div style="display:flex;align-items:center;gap:.5rem;flex-shrink:0;">
                <span class="filter-bar-label">Tipe:</span>
                <select class="type-select" onchange="location.href='?page=leaderboard&sdg=<?= urlencode($selected_sdg) ?>&type='+encodeURIComponent(this.value)">
                    <?php foreach (['Active Contributor', 'Relevant Contributor', 'Discutor'] as $t): ?>
                    <option value="<?= htmlspecialchars($t) ?>" <?= $selected_type === $t ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="lb-layout" style="display:flex;gap:1.5rem;align-items:flex-start;">

            <!-- Main Content -->
            <div style="flex:1;min-width:0;">

                <?php if (empty($leaderboard)): ?>
                <!-- Empty State -->
                <div class="rank-table-wrap">
                    <div class="empty-state">
                        <i class="fas fa-trophy"></i>
                        <h3>Belum Ada Data</h3>
                        <p>Analisis ORCID peneliti pertama untuk memulai leaderboard.</p>
                        <a href="?page=home" class="btn btn-primary">Mulai Analisis</a>
                    </div>
                </div>

                <?php else: ?>

                <!-- Top 3 Podium -->
                <?php if (!empty($top3)): ?>
                <div class="leaderboard-podium">
                    <?php
                    // Reorder for podium: silver (2nd), gold (1st), bronze (3rd)
                    $podium_order = [];
                    if (isset($top3[1])) $podium_order[] = ['data' => $top3[1], 'rank' => 2, 'class' => 'silver'];
                    if (isset($top3[0])) $podium_order[] = ['data' => $top3[0], 'rank' => 1, 'class' => 'gold'];
                    if (isset($top3[2])) $podium_order[] = ['data' => $top3[2], 'rank' => 3, 'class' => 'bronze'];

                    foreach ($podium_order as $p):
                        $pd   = $p['data'];
                        $rank = $p['rank'];
                        $cls  = $p['class'];
                        $inst = lb_institution($pd['institutions'] ?? '');
                        $init = lb_initials($pd['name'] ?? '');
                    ?>
                    <div class="podium-card <?= $cls ?>">
                        <?php if ($rank === 1): ?>
                        <span class="podium-crown">👑</span>
                        <?php endif; ?>
                        <div class="podium-avatar">
                            <?= htmlspecialchars($init) ?>
                            <span class="podium-rank-badge"><?= $rank ?></span>
                        </div>
                        <div class="podium-name" title="<?= htmlspecialchars($pd['name'] ?? '') ?>">
                            <?= htmlspecialchars($pd['name'] ?? 'Unknown') ?>
                        </div>
                        <div class="podium-inst" title="<?= htmlspecialchars($inst) ?>">
                            <?= htmlspecialchars($inst ?: '—') ?>
                        </div>
                        <div class="podium-score">
                            <?= number_format((int)($pd['contrib_count'] ?? 0)) ?>
                            <span class="podium-score-label">Wizdam Score</span>
                        </div>
                        <?php if (!empty($pd['orcid'])): ?>
                        <div style="margin-top:.75rem;">
                            <a href="?page=orcid-profile&orcid=<?= urlencode($pd['orcid']) ?>"
                               style="font-size:.7rem;color:var(--brand,#ff5627);text-decoration:none;font-weight:600;">
                                <i class="fas fa-user"></i> Lihat Profil
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Ranked Table -->
                <div class="rank-table-wrap">
                    <table class="rank-table">
                        <thead>
                            <tr>
                                <th style="width:56px;">Rank</th>
                                <th>Peneliti</th>
                                <th>Institusi</th>
                                <th style="text-align:center;">Karya</th>
                                <th style="text-align:center;">SDGs</th>
                                <th>SDG Badge</th>
                                <th style="text-align:center;">Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaderboard as $idx => $row):
                                $rank = $idx + 1;
                                $badge_class = $rank === 1 ? 'rank-1' : ($rank === 2 ? 'rank-2' : ($rank === 3 ? 'rank-3' : 'rank-other'));
                                $inst  = lb_institution($row['institutions'] ?? '');
                                $init  = lb_initials($row['name'] ?? '');
                                // Top SDG — we can't know from this query, just show work_count sdg_count
                            ?>
                            <tr>
                                <td>
                                    <span class="rank-badge <?= $badge_class ?>">
                                        <?php if ($rank <= 3): ?>
                                            <?= $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : '🥉') ?>
                                        <?php else: ?>
                                            <?= $rank ?>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="researcher-cell">
                                        <div class="researcher-avatar-sm"><?= htmlspecialchars($init) ?></div>
                                        <div>
                                            <div class="researcher-name"><?= htmlspecialchars($row['name'] ?? 'Unknown') ?></div>
                                            <?php if (!empty($row['orcid'])): ?>
                                            <div class="researcher-orcid">
                                                <a href="?page=orcid-profile&orcid=<?= urlencode($row['orcid']) ?>"
                                                   style="color:#94a3b8;text-decoration:none;font-size:.7rem;">
                                                    <?= htmlspecialchars($row['orcid']) ?>
                                                </a>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td style="font-size:.8rem;color:#64748b;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                    title="<?= htmlspecialchars($inst) ?>">
                                    <?= htmlspecialchars($inst ?: '—') ?>
                                </td>
                                <td style="text-align:center;">
                                    <span style="font-weight:600;color:#334155;"><?= (int)($row['work_count'] ?? 0) ?></span>
                                </td>
                                <td style="text-align:center;">
                                    <span style="font-weight:600;color:var(--brand,#ff5627);"><?= (int)($row['sdg_count'] ?? 0) ?></span>
                                </td>
                                <td>
                                    <?php
                                    // Show SDG badge for the selected filter, or nothing if "all"
                                    if ($selected_sdg !== 'all'):
                                        $sdg_code = $selected_sdg;
                                        $size = 'sm';
                                        require PROJECT_ROOT . '/components/sdg-badge.php';
                                    else:
                                        // Show a generic count badge
                                        echo '<span style="font-size:.7rem;color:#94a3b8;">' . (int)($row['sdg_count'] ?? 0) . ' SDG</span>';
                                    endif;
                                    ?>
                                </td>
                                <td style="text-align:center;">
                                    <span class="score-pill"><?= number_format((int)($row['contrib_count'] ?? 0)) ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php endif; ?>
            </div>

            <!-- Right Sidebar -->
            <div class="lb-sidebar" style="width:240px;flex-shrink:0;">

                <!-- Filter Leaderboard -->
                <div class="lb-sidebar-card">
                    <h3><i class="fas fa-filter" style="color:var(--brand,#ff5627);"></i> Filter Leaderboard</h3>
                    <a href="?page=leaderboard&sdg=all&type=<?= urlencode($selected_type) ?>"
                       class="lb-sdg-filter-item <?= $selected_sdg === 'all' ? 'active' : '' ?>">
                        <span class="lb-sdg-dot" style="background:#64748b;"></span>
                        Semua SDG
                    </a>
                    <?php for ($i = 1; $i <= 17; $i++):
                        $code = 'SDG' . $i;
                        $col  = $sdg_colors[$code] ?? '#64748b';
                    ?>
                    <a href="?page=leaderboard&sdg=<?= $code ?>&type=<?= urlencode($selected_type) ?>"
                       class="lb-sdg-filter-item <?= $selected_sdg === $code ? 'active' : '' ?>">
                        <span class="lb-sdg-dot" style="background:<?= $col ?>;"></span>
                        SDG <?= $i ?>
                    </a>
                    <?php endfor; ?>
                </div>

                <!-- Tentang Leaderboard -->
                <div class="lb-sidebar-card" style="background:linear-gradient(135deg,#fff7f4,#fff);">
                    <h3><i class="fas fa-info-circle" style="color:var(--brand,#ff5627);"></i> Tentang Leaderboard</h3>
                    <p style="font-size:.8rem;color:#64748b;line-height:1.6;margin:0 0 .75rem;">
                        Leaderboard ini menampilkan peneliti berdasarkan jumlah kontribusi karya terhadap SDGs yang dipilih.
                    </p>
                    <p style="font-size:.8rem;color:#64748b;line-height:1.6;margin:0 0 .75rem;">
                        <strong>Wizdam Score</strong> = total pemetaan karya terhadap SDG oleh tipe kontributor yang dipilih.
                    </p>
                    <div style="display:flex;flex-direction:column;gap:.35rem;margin-top:.5rem;">
                        <?php foreach ($type_labels as $key => $tl): ?>
                        <a href="?page=leaderboard&sdg=<?= urlencode($selected_sdg) ?>&type=<?= urlencode($key) ?>"
                           style="font-size:.75rem;padding:.3rem .6rem;border-radius:6px;background:<?= $tl['bg'] ?>;color:<?= $tl['color'] ?>;font-weight:600;text-decoration:none;<?= $selected_type === $key ? 'outline:2px solid ' . $tl['color'] . ';' : '' ?>">
                            <?= htmlspecialchars($tl['label']) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div><!-- /sidebar -->

        </div><!-- /lb-layout -->

    </div><!-- /container -->
</section>

</div><!-- /leaderboard-page -->

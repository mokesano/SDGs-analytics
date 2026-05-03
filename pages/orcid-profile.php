<?php
/**
 * pages/orcid-profile.php — ORCID Researcher Profile Page
 *
 * Loaded by public/index.php (header/nav/footer already rendered).
 * Requires: ?orcid=XXXX-XXXX-XXXX-XXXX
 */

$orcid = trim($_GET['orcid'] ?? '');

// Validate ORCID format
if ($orcid && !preg_match('/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/', $orcid)) {
    $orcid = '';
}

$researcher = null;
$works      = [];
$sdg_dist   = [];
$yearly     = [];

if (function_exists('getDb') && $orcid) {
    try {
        $db = getDb();

        $stmt = $db->prepare('SELECT * FROM researchers WHERE orcid = ?');
        $stmt->execute([$orcid]);
        $researcher = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($researcher) {
            // Works with SDG data
            $stmt = $db->prepare("
                SELECT w.*,
                       GROUP_CONCAT(ws.sdg_code || ':' || ws.contributor_type || ':' || ROUND(ws.confidence_score,3)) AS sdg_data,
                       MAX(ws.confidence_score) AS top_confidence,
                       (SELECT contributor_type FROM work_sdgs WHERE work_id=w.id ORDER BY confidence_score DESC LIMIT 1) AS top_contributor_type
                FROM works w
                LEFT JOIN work_sdgs ws ON w.id = ws.work_id AND ws.contributor_type != 'Not Relevant'
                WHERE w.researcher_id = ?
                GROUP BY w.id
                ORDER BY w.year DESC, w.title ASC
            ");
            $stmt->execute([$researcher['id']]);
            $works = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // SDG distribution
            $stmt = $db->prepare("
                SELECT ws.sdg_code, ws.contributor_type, COUNT(*) AS cnt
                FROM work_sdgs ws
                JOIN works w ON w.id = ws.work_id
                WHERE w.researcher_id = ? AND ws.contributor_type != 'Not Relevant'
                GROUP BY ws.sdg_code, ws.contributor_type
                ORDER BY cnt DESC
            ");
            $stmt->execute([$researcher['id']]);
            $sdg_dist = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Yearly trend
            $stmt = $db->prepare("SELECT year, COUNT(*) AS cnt FROM works WHERE researcher_id = ? AND year IS NOT NULL GROUP BY year ORDER BY year ASC");
            $stmt->execute([$researcher['id']]);
            $yearly = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) { /* silent */ }
}

// ── Derived stats ─────────────────────────────────────────────────────────────
$unique_sdgs   = [];
$active_count  = 0;
$sdg_dist_by_code = [];

foreach ($sdg_dist as $row) {
    $code = $row['sdg_code'];
    $unique_sdgs[$code] = true;
    if (!isset($sdg_dist_by_code[$code])) $sdg_dist_by_code[$code] = 0;
    $sdg_dist_by_code[$code] += (int)$row['cnt'];
    if ($row['contributor_type'] === 'Active Contributor') $active_count += (int)$row['cnt'];
}
$unique_sdg_count = count($unique_sdgs);

$year_min = PHP_INT_MAX;
$year_max = 0;
foreach ($yearly as $y) {
    if ((int)$y['year'] < $year_min) $year_min = (int)$y['year'];
    if ((int)$y['year'] > $year_max) $year_max = (int)$y['year'];
}
$years_active = ($year_min < PHP_INT_MAX && $year_max > 0) ? ($year_max - $year_min + 1) : 0;

// Institution
$_inst_raw = $researcher['institutions'] ?? '';
$_inst_arr = is_string($_inst_raw) ? json_decode($_inst_raw, true) : $_inst_raw;
$_institution = '';
if (is_array($_inst_arr) && !empty($_inst_arr)) {
    $_institution = htmlspecialchars($_inst_arr[0]);
} elseif (is_string($_inst_raw) && $_inst_raw) {
    $_institution = htmlspecialchars($_inst_raw);
}

// Initials
$_words    = explode(' ', trim($researcher['name'] ?? ''));
$_initials = '';
foreach (array_slice($_words, 0, 2) as $_w) {
    $_initials .= strtoupper(mb_substr($_w, 0, 1));
}
if (!$_initials) $_initials = 'R';

// Last fetched
$_last_fetched = $researcher['last_fetched'] ?? null;
$_date_str = '';
if ($_last_fetched) {
    $_ts = strtotime($_last_fetched);
    $_date_str = $_ts ? date('d M Y', $_ts) : htmlspecialchars($_last_fetched);
}

$sdg_colors = [
    'SDG1'  => '#E5243B', 'SDG2'  => '#DDA63A', 'SDG3'  => '#4C9F38',
    'SDG4'  => '#C5192D', 'SDG5'  => '#FF3A21', 'SDG6'  => '#26BDE2',
    'SDG7'  => '#FCC30B', 'SDG8'  => '#A21942', 'SDG9'  => '#FD6925',
    'SDG10' => '#DD1367', 'SDG11' => '#FD9D24', 'SDG12' => '#BF8B2E',
    'SDG13' => '#3F7E44', 'SDG14' => '#0A97D9', 'SDG15' => '#56C02B',
    'SDG16' => '#00689D', 'SDG17' => '#19486A',
];

// Chart.js data
$yearly_labels = array_column($yearly, 'year');
$yearly_counts = array_column($yearly, 'cnt');

$type_dist = ['Active Contributor' => 0, 'Relevant Contributor' => 0, 'Discutor' => 0];
foreach ($sdg_dist as $row) {
    $t = $row['contributor_type'];
    if (isset($type_dist[$t])) $type_dist[$t] += (int)$row['cnt'];
}
?>

<style>
/* ── ORCID Profile Page Styles ─────────────────────────── */
.profile-layout {
    display: flex;
    gap: 0;
    min-height: calc(100vh - 72px);
}

/* Sidebar */
.profile-sidebar {
    width: 200px;
    flex-shrink: 0;
    background: #fff;
    border-right: 1px solid #e2e8f0;
    padding: 1.5rem 0;
    position: sticky;
    top: 72px;
    height: calc(100vh - 72px);
    overflow-y: auto;
}
.sidebar-nav {
    display: flex;
    flex-direction: column;
    gap: .15rem;
    padding: 0 .75rem;
}
.sidebar-link {
    display: flex;
    align-items: center;
    gap: .6rem;
    padding: .6rem .875rem;
    border-radius: 8px;
    font-size: .85rem;
    font-weight: 500;
    color: #64748b;
    text-decoration: none;
    transition: all .15s ease;
}
.sidebar-link i { width: 16px; text-align: center; font-size: .8rem; }
.sidebar-link:hover { background: #f8fafc; color: #334155; }
.sidebar-link.active { background: var(--brand-light, #fff2ec); color: var(--brand, #ff5627); font-weight: 600; }

/* Main content */
.profile-main {
    flex: 1;
    min-width: 0;
    padding: 2rem 2rem 3rem;
    background: #f8fafc;
}

/* Profile header */
.profile-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border-radius: 16px;
    padding: 2rem;
    color: #fff;
    display: flex;
    align-items: flex-start;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}
.profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--brand, #ff5627), #e0481d);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    font-weight: 800;
    color: #fff;
    flex-shrink: 0;
    border: 3px solid rgba(255,255,255,.15);
}
.profile-header-info { flex: 1; min-width: 0; }
.profile-name {
    font-size: 1.5rem;
    font-weight: 800;
    color: #fff;
    margin: 0 0 .25rem;
}
.profile-orcid-link {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    font-size: .825rem;
    color: #94a3b8;
    text-decoration: none;
    margin-bottom: .5rem;
}
.profile-orcid-link:hover { color: var(--brand, #ff5627); }
.profile-institution {
    font-size: .875rem;
    color: #cbd5e1;
    margin-bottom: .75rem;
    display: flex;
    align-items: center;
    gap: .4rem;
}
.profile-pills {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
}
.profile-pill {
    font-size: .75rem;
    padding: .25rem .65rem;
    border-radius: 99px;
    background: rgba(255,255,255,.1);
    color: #e2e8f0;
    font-weight: 500;
}
.profile-actions { flex-shrink: 0; }

/* Stats row */
.stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
    text-align: center;
    border-top: 3px solid transparent;
    transition: transform .2s, box-shadow .2s;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.08); }
.stat-card:nth-child(1) { border-top-color: var(--brand, #ff5627); }
.stat-card:nth-child(2) { border-top-color: #4c9f38; }
.stat-card:nth-child(3) { border-top-color: #3b82f6; }
.stat-value {
    font-size: 2rem;
    font-weight: 800;
    color: #1e293b;
    line-height: 1;
    margin-bottom: .35rem;
}
.stat-label {
    font-size: .75rem;
    color: #64748b;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: .04em;
}
.stat-icon {
    font-size: 1.25rem;
    margin-bottom: .5rem;
}
.stat-card:nth-child(1) .stat-icon { color: var(--brand, #ff5627); }
.stat-card:nth-child(2) .stat-icon { color: #4c9f38; }
.stat-card:nth-child(3) .stat-icon { color: #3b82f6; }

/* Charts row */
.charts-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.chart-card {
    background: #fff;
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
}
.chart-card h3 {
    font-size: .875rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 1rem;
    display: flex;
    align-items: center;
    gap: .4rem;
}

/* SDG grid */
.sdg-section {
    background: #fff;
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
    margin-bottom: 1.5rem;
}
.sdg-section h3 {
    font-size: .875rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 1rem;
    display: flex;
    align-items: center;
    gap: .4rem;
}
.sdg-grid {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
}

/* Works section */
.works-section {
    background: #fff;
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
    margin-bottom: 1.5rem;
}
.works-section h3 {
    font-size: .875rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 1rem;
    display: flex;
    align-items: center;
    gap: .4rem;
}
.works-more-link {
    display: block;
    text-align: center;
    margin-top: 1rem;
    font-size: .85rem;
    color: var(--brand, #ff5627);
    text-decoration: none;
    font-weight: 600;
}
.works-more-link:hover { text-decoration: underline; }

/* Not found */
.not-found-state {
    text-align: center;
    padding: 5rem 2rem;
}
.not-found-state i { display: block; margin-bottom: 1rem; }
.not-found-state h2 { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin-bottom: .5rem; }
.not-found-state p { color: #64748b; margin-bottom: 1.5rem; }

@media (max-width: 900px) {
    .profile-layout { flex-direction: column; }
    .profile-sidebar { width: 100%; height: auto; position: static; border-right: none; border-bottom: 1px solid #e2e8f0; }
    .sidebar-nav { flex-direction: row; flex-wrap: wrap; }
    .charts-row { grid-template-columns: 1fr; }
    .stats-row { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 600px) {
    .profile-header { flex-direction: column; align-items: center; text-align: center; }
    .stats-row { grid-template-columns: 1fr; }
    .profile-main { padding: 1rem; }
}
</style>

<div class="profile-layout">

    <!-- Left Sidebar -->
    <aside class="profile-sidebar">
        <nav class="sidebar-nav">
            <a href="#overview" class="sidebar-link active">
                <i class="fas fa-chart-pie"></i> Ringkasan
            </a>
            <a href="#works" class="sidebar-link">
                <i class="fas fa-file-alt"></i> Karya
            </a>
            <a href="#sdgs" class="sidebar-link">
                <i class="fas fa-globe"></i> SDG
            </a>
            <a href="#impact" class="sidebar-link">
                <i class="fas fa-chart-line"></i> Dampak
            </a>
            <div style="border-top:1px solid #f1f5f9;margin:.75rem 0;"></div>
            <a href="?page=leaderboard" class="sidebar-link">
                <i class="fas fa-trophy"></i> Leaderboard
            </a>
            <a href="?page=archived" class="sidebar-link">
                <i class="fas fa-archive"></i> Arsip
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="profile-main">

    <?php if (!$orcid): ?>
    <!-- No ORCID provided -->
    <div class="not-found-state">
        <i class="fas fa-search fa-3x" style="color:#94a3b8;"></i>
        <h2>ORCID Tidak Ditemukan</h2>
        <p>Masukkan ID ORCID yang valid untuk melihat profil peneliti.</p>
        <a href="?page=home" class="btn btn-primary">Kembali ke Beranda</a>
    </div>

    <?php elseif (!$researcher): ?>
    <!-- Researcher not found -->
    <div class="not-found-state">
        <i class="fas fa-user-slash fa-3x" style="color:#94a3b8;margin-bottom:16px;"></i>
        <h2>Profil tidak ditemukan</h2>
        <p>ORCID <code><?= htmlspecialchars($orcid) ?></code> belum dianalisis.</p>
        <a href="?page=home&q=<?= urlencode($orcid) ?>" class="btn btn-primary">Analisis Sekarang</a>
    </div>

    <?php else: ?>

    <!-- ── Profile Header ──────────────────────────────────── -->
    <section id="overview">
        <div class="profile-header">
            <div class="profile-avatar"><?= htmlspecialchars($_initials) ?></div>
            <div class="profile-header-info">
                <h1 class="profile-name"><?= htmlspecialchars($researcher['name'] ?? 'Unknown') ?></h1>
                <a href="https://orcid.org/<?= htmlspecialchars($researcher['orcid']) ?>"
                   target="_blank" rel="noopener" class="profile-orcid-link">
                    <img src="https://orcid.org/sites/default/files/images/orcid_16x16.png"
                         alt="ORCID" style="width:14px;height:14px;border-radius:50%;">
                    <?= htmlspecialchars($researcher['orcid']) ?>
                    <i class="fas fa-external-link-alt" style="font-size:.6rem;"></i>
                </a>
                <?php if ($_institution): ?>
                <div class="profile-institution">
                    <i class="fas fa-university"></i>
                    <?= $_institution ?>
                </div>
                <?php endif; ?>
                <div class="profile-pills">
                    <span class="profile-pill"><i class="fas fa-file-alt" style="margin-right:.3rem;"></i><?= (int)($researcher['total_works'] ?? 0) ?> Karya</span>
                    <span class="profile-pill"><i class="fas fa-globe" style="margin-right:.3rem;"></i><?= $unique_sdg_count ?> SDG</span>
                    <?php if ($years_active > 0): ?>
                    <span class="profile-pill"><i class="fas fa-calendar" style="margin-right:.3rem;"></i><?= $years_active ?> Tahun Aktif</span>
                    <?php endif; ?>
                    <?php if ($_date_str): ?>
                    <span class="profile-pill"><i class="fas fa-sync" style="margin-right:.3rem;"></i>Dianalisis <?= $_date_str ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="profile-actions">
                <a href="?page=home&q=<?= urlencode($researcher['orcid']) ?>"
                   class="btn btn-primary btn-sm" style="white-space:nowrap;">
                    <i class="fas fa-sync"></i> Analisis Ulang
                </a>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                <div class="stat-value"><?= number_format((int)($researcher['total_works'] ?? 0)) ?></div>
                <div class="stat-label">Karya</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-globe"></i></div>
                <div class="stat-value"><?= $unique_sdg_count ?></div>
                <div class="stat-label">SDG Dikontribusi</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-star"></i></div>
                <div class="stat-value"><?= number_format($active_count) ?></div>
                <div class="stat-label">Kontribusi Aktif</div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="charts-row" id="impact">
            <div class="chart-card">
                <h3><i class="fas fa-chart-bar" style="color:var(--brand,#ff5627);"></i> Karya per Tahun</h3>
                <?php if (!empty($yearly)): ?>
                <canvas id="yearlyChart" height="200"></canvas>
                <?php else: ?>
                <div style="text-align:center;padding:2rem;color:#94a3b8;font-size:.875rem;">
                    <i class="fas fa-chart-bar fa-2x" style="margin-bottom:.75rem;display:block;"></i>
                    Tidak ada data tahunan
                </div>
                <?php endif; ?>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-chart-pie" style="color:#4c9f38;"></i> Distribusi Tipe Kontribusi</h3>
                <?php if (array_sum($type_dist) > 0): ?>
                <canvas id="sdgDistChart" height="200"></canvas>
                <?php else: ?>
                <div style="text-align:center;padding:2rem;color:#94a3b8;font-size:.875rem;">
                    <i class="fas fa-chart-pie fa-2x" style="margin-bottom:.75rem;display:block;"></i>
                    Tidak ada data distribusi
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ── SDG Badges ──────────────────────────────────────── -->
    <section id="sdgs">
        <div class="sdg-section">
            <h3><i class="fas fa-globe" style="color:#4c9f38;"></i> SDG yang Dikontribusi</h3>
            <?php if (!empty($unique_sdgs)): ?>
            <div class="sdg-grid">
                <?php
                // Sort SDGs numerically
                $sdg_codes_sorted = array_keys($unique_sdgs);
                usort($sdg_codes_sorted, function($a, $b) {
                    return (int)preg_replace('/\D/', '', $a) - (int)preg_replace('/\D/', '', $b);
                });
                foreach ($sdg_codes_sorted as $sdg_code_item):
                    $sdg_code = $sdg_code_item;
                    $size = 'md';
                    $contributor_type = '';
                    $confidence_score = null;
                    require PROJECT_ROOT . '/components/sdg-badge.php';
                endforeach;
                ?>
            </div>
            <?php if (!empty($sdg_dist_by_code)): ?>
            <div style="margin-top:1rem;display:flex;flex-wrap:wrap;gap:.5rem;">
                <?php
                arsort($sdg_dist_by_code);
                foreach (array_slice($sdg_dist_by_code, 0, 5, true) as $sc => $cnt):
                    $col = $sdg_colors[$sc] ?? '#64748b';
                ?>
                <span style="font-size:.75rem;padding:.2rem .6rem;border-radius:99px;background:<?= $col ?>22;color:<?= $col ?>;font-weight:600;border:1px solid <?= $col ?>44;">
                    <?= htmlspecialchars($sc) ?>: <?= $cnt ?> karya
                </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <p style="color:#94a3b8;font-size:.875rem;">Belum ada SDG yang teridentifikasi.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- ── Karya Terbaru ──────────────────────────────────── -->
    <section id="works">
        <div class="works-section">
            <h3>
                <i class="fas fa-file-alt" style="color:var(--brand,#ff5627);"></i>
                Karya Terbaru
                <span style="margin-left:auto;font-size:.75rem;color:#94a3b8;font-weight:400;">
                    Total: <?= count($works) ?> karya
                </span>
            </h3>
            <?php if (!empty($works)):
                $shown_works = array_slice($works, 0, 10);
                foreach ($shown_works as $work_item):
                    // Parse sdg_data for work-card
                    $sdg_analysis = [];
                    if (!empty($work_item['sdg_data'])) {
                        foreach (explode(',', $work_item['sdg_data']) as $part) {
                            $parts = explode(':', $part, 3);
                            if (count($parts) === 3) {
                                $sdg_analysis[] = [
                                    'sdg_code'         => trim($parts[0]),
                                    'contributor_type' => trim($parts[1]),
                                    'confidence_score' => (float)trim($parts[2]),
                                ];
                            }
                        }
                    }
                    $work_item['sdg_analysis'] = $sdg_analysis;
                    $work = $work_item;
                    require PROJECT_ROOT . '/components/work-card.php';
                endforeach;
                if (count($works) > 10):
            ?>
            <a href="#works" class="works-more-link" onclick="showAllWorks(); return false;">
                <i class="fas fa-chevron-down"></i> Tampilkan <?= count($works) - 10 ?> karya lainnya
            </a>
            <div id="works-extra" style="display:none;">
                <?php
                $extra_works = array_slice($works, 10);
                foreach ($extra_works as $work_item):
                    $sdg_analysis = [];
                    if (!empty($work_item['sdg_data'])) {
                        foreach (explode(',', $work_item['sdg_data']) as $part) {
                            $parts = explode(':', $part, 3);
                            if (count($parts) === 3) {
                                $sdg_analysis[] = [
                                    'sdg_code'         => trim($parts[0]),
                                    'contributor_type' => trim($parts[1]),
                                    'confidence_score' => (float)trim($parts[2]),
                                ];
                            }
                        }
                    }
                    $work_item['sdg_analysis'] = $sdg_analysis;
                    $work = $work_item;
                    require PROJECT_ROOT . '/components/work-card.php';
                endforeach;
                ?>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <div style="text-align:center;padding:2rem;color:#94a3b8;">
                <i class="fas fa-file-alt fa-2x" style="margin-bottom:.75rem;display:block;"></i>
                <p>Belum ada karya yang ditemukan.</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <?php endif; // researcher found ?>

    </main>
</div><!-- /profile-layout -->

<?php if ($researcher && (!empty($yearly) || array_sum($type_dist) > 0)): ?>
<!-- Chart.js initialization -->
<script>
(function() {
    // Load Chart.js dynamically if not already present
    function initCharts() {
        <?php if (!empty($yearly)): ?>
        var yearlyCtx = document.getElementById('yearlyChart');
        if (yearlyCtx) {
            new Chart(yearlyCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_map('strval', $yearly_labels)) ?>,
                    datasets: [{
                        label: 'Jumlah Karya',
                        data: <?= json_encode(array_map('intval', $yearly_counts)) ?>,
                        backgroundColor: 'rgba(255, 86, 39, 0.75)',
                        borderColor: 'rgba(255, 86, 39, 1)',
                        borderWidth: 1.5,
                        borderRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { title: function(ctx) { return 'Tahun ' + ctx[0].label; } } }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1, font: { size: 11 } },
                            grid: { color: 'rgba(0,0,0,.04)' }
                        },
                        x: { ticks: { font: { size: 11 } }, grid: { display: false } }
                    }
                }
            });
        }
        <?php endif; ?>

        <?php if (array_sum($type_dist) > 0): ?>
        var sdgCtx = document.getElementById('sdgDistChart');
        if (sdgCtx) {
            var typeData = <?= json_encode(array_values($type_dist)) ?>;
            var typeLabels = <?= json_encode(array_keys($type_dist)) ?>;
            var typeColors = ['#4c9f38', '#3b82f6', '#f59e0b'];
            new Chart(sdgCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: typeLabels,
                    datasets: [{
                        data: typeData,
                        backgroundColor: typeColors,
                        borderWidth: 2,
                        borderColor: '#fff',
                        hoverOffset: 6,
                    }]
                },
                options: {
                    responsive: true,
                    cutout: '60%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { font: { size: 11 }, padding: 12, boxWidth: 12 }
                        }
                    }
                }
            });
        }
        <?php endif; ?>
    }

    if (typeof Chart !== 'undefined') {
        initCharts();
    } else {
        // Load Chart.js from CDN
        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js';
        script.onload = initCharts;
        document.head.appendChild(script);
    }
})();
</script>
<?php endif; ?>

<script>
// Sidebar active section highlight
document.addEventListener('DOMContentLoaded', function() {
    var links = document.querySelectorAll('.sidebar-link[href^="#"]');
    var sections = [];
    links.forEach(function(link) {
        var id = link.getAttribute('href').slice(1);
        var el = document.getElementById(id);
        if (el) sections.push({ el: el, link: link });
    });

    function onScroll() {
        var scrollY = window.scrollY + 120;
        var active = null;
        sections.forEach(function(s) {
            if (s.el.offsetTop <= scrollY) active = s;
        });
        links.forEach(function(l) { l.classList.remove('active'); });
        if (active) active.link.classList.add('active');
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
});

function showAllWorks() {
    var extra = document.getElementById('works-extra');
    if (extra) {
        extra.style.display = '';
        event.target.style.display = 'none';
    }
}
</script>

<?php
$page_title = 'Analytics Dashboard';
$page_description = 'Visualize SDG distribution, researcher contributions, and publication trends on the Wizdam AI analytics dashboard.';

// ── Database queries ──────────────────────────────────────────────────────────
try {
    $db = function_exists('getDb') ? getDb() : new PDO('sqlite:' . dirname(__DIR__) . '/database/wizdam.db', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Stats row counts
    $totalResearchers = (int) $db->query('SELECT COUNT(*) FROM researchers')->fetchColumn();
    $totalWorks       = (int) $db->query('SELECT COUNT(*) FROM works')->fetchColumn();
    $sdgCoverage      = (int) $db->query("SELECT COUNT(DISTINCT sdg_code) FROM work_sdgs")->fetchColumn();
    $totalJournals    = (int) $db->query('SELECT COUNT(*) FROM journals')->fetchColumn();

    // SDG distribution bar chart (active + relevant only)
    $sdgBarRows = $db->query(
        "SELECT sdg_code, COUNT(*) AS cnt
           FROM work_sdgs
          WHERE contributor_type IN ('Active Contributor','Relevant Contributor')
          GROUP BY sdg_code
          ORDER BY sdg_code"
    )->fetchAll(PDO::FETCH_ASSOC);

    // Contributor type doughnut
    $contribRows = $db->query(
        "SELECT contributor_type, COUNT(*) AS cnt
           FROM work_sdgs
          GROUP BY contributor_type"
    )->fetchAll(PDO::FETCH_ASSOC);

    // Yearly publication trend
    $yearRows = $db->query(
        "SELECT year, COUNT(*) AS cnt
           FROM works
          WHERE year IS NOT NULL AND year > 1990
          GROUP BY year
          ORDER BY year"
    )->fetchAll(PDO::FETCH_ASSOC);

    // Top 5 SDGs (most active contributions)
    $topSdgRows = $db->query(
        "SELECT
               sdg_code,
               SUM(CASE WHEN contributor_type = 'Active Contributor' THEN 1 ELSE 0 END) AS active_count,
               COUNT(*) AS total_count
           FROM work_sdgs
          GROUP BY sdg_code
          ORDER BY active_count DESC, total_count DESC
          LIMIT 5"
    )->fetchAll(PDO::FETCH_ASSOC);

    // Recent researchers (last 5 analyzed)
    $recentResearchers = $db->query(
        "SELECT name, orcid, institutions, last_fetched
           FROM researchers
          ORDER BY last_fetched DESC
          LIMIT 5"
    )->fetchAll(PDO::FETCH_ASSOC);

    $dbError = null;
} catch (Exception $e) {
    $dbError = $e->getMessage();
    $totalResearchers = $totalWorks = $sdgCoverage = $totalJournals = 0;
    $sdgBarRows = $contribRows = $yearRows = $topSdgRows = $recentResearchers = [];
}

// ── SDG name lookup ───────────────────────────────────────────────────────────
$sdgNames = [
    'SDG1'  => 'No Poverty',           'SDG2'  => 'Zero Hunger',
    'SDG3'  => 'Good Health',          'SDG4'  => 'Quality Education',
    'SDG5'  => 'Gender Equality',      'SDG6'  => 'Clean Water',
    'SDG7'  => 'Clean Energy',         'SDG8'  => 'Decent Work',
    'SDG9'  => 'Industry & Innovation','SDG10' => 'Reduced Inequalities',
    'SDG11' => 'Sustainable Cities',   'SDG12' => 'Responsible Consumption',
    'SDG13' => 'Climate Action',       'SDG14' => 'Life Below Water',
    'SDG15' => 'Life on Land',         'SDG16' => 'Peace & Justice',
    'SDG17' => 'Partnerships',
];

// ── Prepare JS-ready arrays (JSON-encoded) ────────────────────────────────────
$sdgBarLabels = json_encode(array_column($sdgBarRows, 'sdg_code'));
$sdgBarData   = json_encode(array_map('intval', array_column($sdgBarRows, 'cnt')));

$contribLabels = json_encode(array_column($contribRows, 'contributor_type'));
$contribData   = json_encode(array_map('intval', array_column($contribRows, 'cnt')));

$yearLabels = json_encode(array_map('strval', array_column($yearRows, 'year')));
$yearData   = json_encode(array_map('intval', array_column($yearRows, 'cnt')));
?>

<style>
/* ── Analytics Dashboard page-specific styles ────────────────────────────── */
.dash-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}
.dash-stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,.07);
    border: 1px solid #e8ecf0;
    text-align: center;
}
.dash-stat-number {
    display: block;
    font-size: 2.25rem;
    font-weight: 700;
    color: var(--brand, #ff5627);
    line-height: 1.1;
}
.dash-stat-label {
    display: block;
    font-size: .85rem;
    color: #64748b;
    margin-top: .35rem;
    font-weight: 500;
}
.dash-charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}
.dash-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,.07);
    border: 1px solid #e8ecf0;
}
.dash-card h4 {
    margin: 0 0 1rem;
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: .5rem;
}
.dash-card h4 i { color: var(--brand, #ff5627); }
.dash-canvas-wrap {
    position: relative;
    height: 300px;
    max-height: 300px;
}
.dash-table {
    width: 100%;
    border-collapse: collapse;
    font-size: .9rem;
}
.dash-table th {
    text-align: left;
    padding: .5rem .75rem;
    color: #64748b;
    font-weight: 600;
    font-size: .8rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    border-bottom: 2px solid #f1f5f9;
}
.dash-table td {
    padding: .65rem .75rem;
    border-bottom: 1px solid #f1f5f9;
    color: #1e293b;
    vertical-align: middle;
}
.dash-table tr:last-child td { border-bottom: none; }
.dash-table tr:hover td { background: #f8fafc; }
.dash-rank {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: var(--brand, #ff5627);
    color: #fff;
    font-size: .75rem;
    font-weight: 700;
}
.sdg-badge {
    display: inline-block;
    padding: .2rem .55rem;
    border-radius: 6px;
    font-size: .78rem;
    font-weight: 700;
    color: #fff;
    margin-right: .4rem;
}
.dash-empty {
    text-align: center;
    padding: 2.5rem 1rem;
    color: #94a3b8;
}
.dash-empty i { font-size: 2rem; margin-bottom: .5rem; display: block; }
.researcher-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--brand,#ff5627), #ff8c69);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: .9rem;
    flex-shrink: 0;
}
.researcher-row { display: flex; align-items: center; gap: .75rem; }
.researcher-info { min-width: 0; }
.researcher-name { font-weight: 600; font-size: .9rem; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.researcher-meta { font-size: .78rem; color: #64748b; margin-top: .1rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
</style>

<div class="page-header">
    <div class="container">
        <div class="section-label">Data Analytics</div>
        <h1 class="section-title">Analytics Dashboard</h1>
        <p class="section-subtitle">Real-time visualization of SDG research distribution and contribution trends.</p>
    </div>
</div>

<section class="section">
    <div class="container">

        <?php if ($dbError): ?>
        <div class="alert alert-warning" style="margin-bottom:1.5rem;">
            <i class="fas fa-exclamation-triangle"></i>
            <span>Could not load analytics data: <code><?= htmlspecialchars($dbError) ?></code></span>
        </div>
        <?php endif; ?>

        <!-- ── Stats row ─────────────────────────────────────────────────── -->
        <div class="dash-stats-grid">
            <div class="dash-stat-card reveal">
                <span class="dash-stat-number" data-counter="<?= $totalResearchers ?>">0</span>
                <span class="dash-stat-label">Total Researchers</span>
            </div>
            <div class="dash-stat-card reveal" style="transition-delay:80ms;">
                <span class="dash-stat-number" data-counter="<?= $totalWorks ?>">0</span>
                <span class="dash-stat-label">Total Works Analyzed</span>
            </div>
            <div class="dash-stat-card reveal" style="transition-delay:160ms;">
                <span class="dash-stat-number" data-counter="<?= $sdgCoverage ?>">0</span>
                <span class="dash-stat-label">SDG Coverage</span>
            </div>
            <div class="dash-stat-card reveal" style="transition-delay:240ms;">
                <span class="dash-stat-number" data-counter="<?= $totalJournals ?>">0</span>
                <span class="dash-stat-label">Total Journals</span>
            </div>
        </div>

        <!-- ── Charts grid ───────────────────────────────────────────────── -->
        <div class="dash-charts-grid">

            <!-- SDG Distribution bar chart -->
            <div class="dash-card reveal">
                <h4><i class="fas fa-chart-bar"></i> SDG Distribution (Active + Relevant)</h4>
                <?php if (empty($sdgBarRows)): ?>
                <div class="dash-empty"><i class="fas fa-chart-bar"></i>No SDG classification data yet.</div>
                <?php else: ?>
                <div class="dash-canvas-wrap">
                    <canvas id="sdgBarChart"></canvas>
                </div>
                <?php endif; ?>
            </div>

            <!-- Contributor type doughnut -->
            <div class="dash-card reveal" style="transition-delay:100ms;">
                <h4><i class="fas fa-chart-pie"></i> Contributor Type Distribution</h4>
                <?php if (empty($contribRows)): ?>
                <div class="dash-empty"><i class="fas fa-chart-pie"></i>No contributor data yet.</div>
                <?php else: ?>
                <div class="dash-canvas-wrap">
                    <canvas id="contributorDoughnut"></canvas>
                </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Yearly trend — full width -->
        <div class="dash-card reveal" style="margin-bottom:2rem;">
            <h4><i class="fas fa-chart-line"></i> Yearly Publication Trend</h4>
            <?php if (empty($yearRows)): ?>
            <div class="dash-empty"><i class="fas fa-chart-line"></i>No publication year data yet.</div>
            <?php else: ?>
            <div class="dash-canvas-wrap">
                <canvas id="yearlyChart"></canvas>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── Bottom tables grid ────────────────────────────────────────── -->
        <div class="dash-charts-grid">

            <!-- Top 5 SDGs table -->
            <div class="dash-card reveal">
                <h4><i class="fas fa-trophy"></i> Top 5 SDGs by Active Contributions</h4>
                <?php if (empty($topSdgRows)): ?>
                <div class="dash-empty"><i class="fas fa-trophy"></i>No SDG data yet.</div>
                <?php else: ?>
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>SDG</th>
                            <th>Active</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topSdgRows as $i => $row):
                            $code  = htmlspecialchars($row['sdg_code']);
                            $name  = htmlspecialchars($sdgNames[$row['sdg_code']] ?? $row['sdg_code']);
                            // Derive a colour from SDG_COLORS-compatible list (same as dashboard.js)
                            $sdgColorMap = [
                                'SDG1'=>'#E5243B','SDG2'=>'#DDA63A','SDG3'=>'#4C9F38','SDG4'=>'#C5192D',
                                'SDG5'=>'#FF3A21','SDG6'=>'#26BDE2','SDG7'=>'#FCC30B','SDG8'=>'#A21942',
                                'SDG9'=>'#FD6925','SDG10'=>'#DD1367','SDG11'=>'#FD9D24','SDG12'=>'#BF8B2E',
                                'SDG13'=>'#3F7E44','SDG14'=>'#0A97D9','SDG15'=>'#56C02B','SDG16'=>'#00689D',
                                'SDG17'=>'#19486A',
                            ];
                            $bg = $sdgColorMap[$row['sdg_code']] ?? '#94a3b8';
                        ?>
                        <tr>
                            <td><span class="dash-rank"><?= $i + 1 ?></span></td>
                            <td>
                                <span class="sdg-badge" style="background:<?= $bg ?>;"><?= $code ?></span>
                                <?= $name ?>
                            </td>
                            <td><strong><?= number_format((int)$row['active_count']) ?></strong></td>
                            <td><?= number_format((int)$row['total_count']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <!-- Recent researchers table -->
            <div class="dash-card reveal" style="transition-delay:100ms;">
                <h4><i class="fas fa-users"></i> Recently Analyzed Researchers</h4>
                <?php if (empty($recentResearchers)): ?>
                <div class="dash-empty"><i class="fas fa-users"></i>No researchers analyzed yet.</div>
                <?php else: ?>
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Researcher</th>
                            <th>Analyzed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentResearchers as $r):
                            $name         = htmlspecialchars($r['name'] ?? 'Unknown');
                            $initials     = implode('', array_map(fn($w) => strtoupper($w[0]), array_slice(explode(' ', trim($name)), 0, 2)));
                            $institutions = json_decode($r['institutions'] ?? '[]', true);
                            $firstInst    = htmlspecialchars(is_array($institutions) && count($institutions) ? $institutions[0] : '—');
                            $fetched      = $r['last_fetched'] ? date('d M Y', strtotime($r['last_fetched'])) : '—';
                        ?>
                        <tr>
                            <td>
                                <div class="researcher-row">
                                    <span class="researcher-avatar"><?= htmlspecialchars($initials) ?></span>
                                    <div class="researcher-info">
                                        <div class="researcher-name"><?= $name ?></div>
                                        <div class="researcher-meta"><?= $firstInst ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="white-space:nowrap; color:#64748b; font-size:.83rem;"><?= $fetched ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

        </div><!-- /bottom tables grid -->

    </div><!-- /container -->
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Chart data injected from PHP
    const sdgBarLabels    = <?= $sdgBarLabels ?>;
    const sdgBarData      = <?= $sdgBarData ?>;
    const contribLabels   = <?= $contribLabels ?>;
    const contribData     = <?= $contribData ?>;
    const yearLabels      = <?= $yearLabels ?>;
    const yearData        = <?= $yearData ?>;

    if (typeof createSdgBarChart === 'function' && sdgBarLabels.length) {
        createSdgBarChart('sdgBarChart', sdgBarLabels, sdgBarData);
    }
    if (typeof createContributorDoughnut === 'function' && contribLabels.length) {
        createContributorDoughnut('contributorDoughnut', contribLabels, contribData);
    }
    if (typeof createYearlyTrendChart === 'function' && yearLabels.length) {
        createYearlyTrendChart('yearlyChart', yearLabels, yearData);
    }
});
</script>

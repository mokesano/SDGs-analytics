<?php
$page_title = 'Analytics Dashboard';
$page_description = 'Visualize SDG distribution, researcher contributions, and publication trends on the Wizdam AI analytics dashboard.';

// Load database connection
require_once __DIR__ . '/../includes/database.php';
$db = getDB();

// Fetch real analytics data from database
$stats = [
    'researchers' => 0,
    'works' => 0,
    'sdgs_covered' => 0,
    'avg_confidence' => 0,
    'active_contributors' => 0
];

$sdg_distribution = array_fill(1, 17, 0);
$monthly_trend = [];
$contributor_types = ['Active Contributor' => 0, 'Relevant Contributor' => 0, 'Discutor' => 0, 'Not Relevant' => 0];

try {
    // Total researchers analyzed
    $stmt = $db->query("SELECT COUNT(*) FROM researchers WHERE last_fetched IS NOT NULL");
    $stats['researchers'] = (int)$stmt->fetchColumn();
    
    // Total works classified
    $stmt = $db->query("SELECT COUNT(*) FROM works");
    $stats['works'] = (int)$stmt->fetchColumn();
    
    // SDGs covered
    $stmt = $db->query("SELECT COUNT(DISTINCT sdg_code) FROM work_sdgs");
    $stats['sdgs_covered'] = (int)$stmt->fetchColumn();
    
    // Average confidence score
    $stmt = $db->query("SELECT AVG(confidence_score) FROM work_sdgs WHERE confidence_score IS NOT NULL");
    $avg = $stmt->fetchColumn();
    $stats['avg_confidence'] = $avg ? round((float)$avg * 100) : 0;
    
    // Active contributors (unique researchers with at least one 'Active Contributor' classification)
    $stmt = $db->query("SELECT COUNT(DISTINCT w.researcher_id) FROM works w JOIN work_sdgs ws ON w.id = ws.work_id WHERE ws.contributor_type = 'Active Contributor'");
    $stats['active_contributors'] = (int)$stmt->fetchColumn();
    
    // SDG distribution
    $stmt = $db->query("SELECT sdg_code, COUNT(*) as count FROM work_sdgs GROUP BY sdg_code ORDER BY sdg_code");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sdg_num = (int)str_replace('SDG', '', $row['sdg_code']);
        if ($sdg_num >= 1 && $sdg_num <= 17) {
            $sdg_distribution[$sdg_num] = (int)$row['count'];
        }
    }
    
    // Contributor type distribution
    $stmt = $db->query("SELECT contributor_type, COUNT(*) as count FROM work_sdgs GROUP BY contributor_type");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($contributor_types[$row['contributor_type']])) {
            $contributor_types[$row['contributor_type']] = (int)$row['count'];
        }
    }
    
    // Monthly trend (last 6 months)
    $stmt = $db->query("SELECT strftime('%Y-%m', created_at) as month, COUNT(*) as count FROM works WHERE created_at >= date('now', '-6 months') GROUP BY month ORDER BY month");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $monthly_trend[] = ['month' => $row['month'], 'count' => (int)$row['count']];
    }
} catch (Exception $e) {
    error_log("Analytics dashboard error: " . $e->getMessage());
}

$has_data = ($stats['researchers'] > 0 || $stats['works'] > 0);
?>
<div class="page-header">
    <div class="container">
        <div class="section-label">Data Analytics</div>
        <h1 class="section-title">Analytics Dashboard</h1>
        <p class="section-subtitle">Real-time visualization of SDG research distribution and contribution trends.</p>
    </div>
</div>

<section class="section">
    <div class="container">

        <?php if (!$has_data): ?>
        <div class="alert alert-info" style="margin-bottom:1.5rem;">
            <i class="fas fa-chart-bar"></i>
            <span><strong>No data available yet.</strong> The dashboard will display real analytics once researchers and works have been analyzed and stored in the database.</span>
        </div>
        <?php else: ?>

        <!-- Stats Overview -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:2rem;">
            <div class="stat-card reveal">
                <span class="stat-number"><?= number_format($stats['researchers']) ?></span>
                <span class="stat-label">Researchers Analyzed</span>
            </div>
            <div class="stat-card reveal" style="transition-delay:100ms;">
                <span class="stat-number"><?= number_format($stats['works']) ?></span>
                <span class="stat-label">Works Classified</span>
            </div>
            <div class="stat-card reveal" style="transition-delay:200ms;">
                <span class="stat-number"><?= $stats['sdgs_covered'] ?></span>
                <span class="stat-label">SDGs Covered</span>
            </div>
            <div class="stat-card reveal" style="transition-delay:300ms;">
                <span class="stat-number"><?= $stats['avg_confidence'] ?>%</span>
                <span class="stat-label">Avg Confidence</span>
            </div>
            <div class="stat-card reveal" style="transition-delay:400ms;">
                <span class="stat-number"><?= number_format($stats['active_contributors']) ?></span>
                <span class="stat-label">Active Contributors</span>
            </div>
        </div>

        <!-- Charts Grid -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem;" class="charts-section reveal">
            <div class="chart-container">
                <h4 style="margin-bottom:1rem;"><i class="fas fa-chart-pie" style="color:var(--brand,#ff5627);"></i> SDG Distribution</h4>
                <canvas id="sdgDistChart" height="280"></canvas>
            </div>
            <div class="chart-container">
                <h4 style="margin-bottom:1rem;"><i class="fas fa-chart-bar" style="color:var(--brand,#ff5627);"></i> Top SDGs by Work Count</h4>
                <canvas id="sdgBarChart" height="280"></canvas>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;" class="charts-section reveal">
            <div class="chart-container">
                <h4 style="margin-bottom:1rem;"><i class="fas fa-chart-line" style="color:var(--brand,#ff5627);"></i> Monthly Analyses Trend</h4>
                <canvas id="trendChart" height="220"></canvas>
            </div>
            <div class="chart-container">
                <h4 style="margin-bottom:1rem;"><i class="fas fa-users" style="color:var(--brand,#ff5627);"></i> Contributor Type Distribution</h4>
                <canvas id="contribChart" height="220"></canvas>
            </div>
        </div>
    </div>
<?php endif; ?>
</section>

<?php if ($has_data): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') return;

    const SDG_COLORS = ['#E5243B','#DDA63A','#4C9F38','#C5192D','#FF3A21','#26BDE2','#FCC30B','#A21942','#FD6925','#DD1367','#FD9D24','#BF8B2E','#3F7E44','#0A97D9','#56C02B','#00689D','#19486A'];
    const SDG_LABELS = ['SDG1','SDG2','SDG3','SDG4','SDG5','SDG6','SDG7','SDG8','SDG9','SDG10','SDG11','SDG12','SDG13','SDG14','SDG15','SDG16','SDG17'];
    
    const sdgData = <?= json_encode(array_values($sdg_distribution)) ?>;
    const contributorData = <?= json_encode(array_values($contributor_types)) ?>;
    const trendLabels = <?= json_encode(array_column($monthly_trend, 'month')) ?>;
    const trendData = <?= json_encode(array_column($monthly_trend, 'count')) ?>;

    new Chart(document.getElementById('sdgDistChart'), {
        type: 'doughnut',
        data: { labels: SDG_LABELS, datasets: [{ data: sdgData, backgroundColor: SDG_COLORS, borderWidth: 2, borderColor: '#fff' }] },
        options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'right', labels:{ font:{size:10}, padding:6 } } } }
    });

    new Chart(document.getElementById('sdgBarChart'), {
        type: 'bar',
        data: {
            labels: SDG_LABELS,
            datasets: [{ label:'Works', data: sdgData, backgroundColor: SDG_COLORS, borderWidth: 0, borderRadius: 6 }]
        },
        options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:false} }, scales:{ x:{ticks:{font:{size:10}}}, y:{ticks:{font:{size:11}}} } }
    });

    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: trendLabels.map(m => m ? (m.substring(5) + '-' + m.substring(0,2)) : ''),
            datasets: [{ label:'Analyses', data: trendData, borderColor:'#ff5627', backgroundColor:'rgba(255,86,39,.1)', fill:true, tension:.4, pointBackgroundColor:'#ff5627', borderWidth:2 }]
        },
        options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{ y:{ticks:{font:{size:11}}} } }
    });

    new Chart(document.getElementById('contribChart'), {
        type: 'doughnut',
        data: {
            labels: ['Active Contributor','Relevant Contributor','Discutor','Not Relevant'],
            datasets: [{ data: contributorData, backgroundColor:['#4c9f38','#26bde2','#fcc30b','#94a3b8'], borderWidth: 2, borderColor: '#fff' }]
        },
        options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom', labels:{font:{size:11},padding:10} } } }
    });
});
</script>
<?php endif; ?>

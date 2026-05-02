<?php
/**
 * Home Page - Wizdam Sikola
 * SDGs Classification & Research Analytics Platform
 */

require_once __DIR__ . '/../includes/database.php';

// Fetch real-time statistics from database
$totalResearchers = 0;
$totalWorks = 0;
$totalJournals = 0;
$sdgsCovered = 0;
$sdgDistribution = [];
$monthlyTrend = [];
$topContributors = [];
$hasData = false;

try {
    $db = getDB();
    
    $totalResearchers = (int)$db->query("SELECT COUNT(*) FROM researchers")->fetchColumn();
    $totalWorks = (int)$db->query("SELECT COUNT(*) FROM works")->fetchColumn();
    $totalJournals = (int)$db->query("SELECT COUNT(*) FROM journals")->fetchColumn();
    $sdgsCovered = (int)$db->query("SELECT COUNT(DISTINCT sdg_code) FROM work_sdgs")->fetchColumn();
    
    $stmt = $db->query("SELECT sdg_code, COUNT(*) as count FROM work_sdgs GROUP BY sdg_code ORDER BY count DESC LIMIT 10");
    $sdgDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->query("SELECT strftime('%Y-%m', created_at) as month, COUNT(*) as count FROM works WHERE created_at >= date('now', '-6 months') GROUP BY month ORDER BY month ASC");
    $monthlyTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->query("SELECT r.name, r.orcid, COUNT(ws.id) as contribution_count FROM researchers r JOIN works w ON r.id = w.researcher_id JOIN work_sdgs ws ON w.id = ws.work_id WHERE ws.contributor_type = 'Active Contributor' GROUP BY r.id ORDER BY contribution_count DESC LIMIT 5");
    $topContributors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasData = ($totalResearchers > 0 || $totalWorks > 0);
} catch (PDOException $e) {
    error_log("Home page DB error: " . $e->getMessage());
}

$pageTitle = 'Wizdam Sikola - SDGs Classification & Research Analytics Platform';
$pageDescription = 'Platform analisis dan klasifikasi penelitian terhadap Tujuan Pembangunan Berkelanjutan (SDGs). Temukan peneliti, jurnal, dan karya ilmiah berkontribusi pada SDGs.';

ob_start();
?>

<!-- Hero Section -->
<section class="relative overflow-hidden bg-white dark:bg-bg-dark py-20 lg:py-32">
    <div class="absolute inset-0 bg-gradient-to-br from-bg-soft/50 to-transparent dark:from-bg-darkSoft/30"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="text-center scroll-reveal">
            <h1 class="text-5xl lg:text-6xl font-heading font-extrabold text-text dark:text-text-dark mb-6">
                <span class="gradient-text">SDGs Research</span><br />
                <span class="text-text dark:text-text-dark">Analytics Platform</span>
            </h1>
            <p class="text-xl text-text-muted max-w-3xl mx-auto mb-10">
                Analisis dampak penelitian terhadap 17 Tujuan Pembangunan Berkelanjutan. 
                Temukan peneliti, jurnal Scopus, dan karya ilmiah yang berkontribusi pada masa depan berkelanjutan.
            </p>
            <div class="max-w-2xl mx-auto">
                <form action="/orcid/search" method="GET" class="flex flex-col sm:flex-row gap-4">
                    <input type="text" name="q" placeholder="Cari ORCID peneliti (contoh: 0000-0000-0000-0000)" class="input flex-1 text-lg py-4" required />
                    <button type="submit" class="btn btn-primary text-lg px-8 py-4">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Analisis
                    </button>
                </form>
                <p class="text-sm text-text-muted mt-4">
                    Atau jelajahi <a href="/archived" class="text-primary hover:underline">arsip peneliti</a> dan 
                    <a href="/journal-archive" class="text-primary hover:underline">profil jurnal Scopus</a>
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Cards -->
<?php if ($hasData): ?>
<section class="py-16 bg-bg-soft dark:bg-bg-darkMuted">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="card card-hover scroll-reveal">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-text-muted text-sm font-medium uppercase tracking-wide">Researchers</p>
                        <p class="text-4xl font-heading font-bold text-text dark:text-text-dark mt-2"><?php echo number_format($totalResearchers); ?></p>
                    </div>
                    <div class="p-4 bg-primary/10 rounded-full">
                        <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="card card-hover scroll-reveal" style="transition-delay: 100ms;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-text-muted text-sm font-medium uppercase tracking-wide">Works Analyzed</p>
                        <p class="text-4xl font-heading font-bold text-text dark:text-text-dark mt-2"><?php echo number_format($totalWorks); ?></p>
                    </div>
                    <div class="p-4 bg-sdg4/10 rounded-full">
                        <svg class="w-8 h-8 text-sdg4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="card card-hover scroll-reveal" style="transition-delay: 200ms;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-text-muted text-sm font-medium uppercase tracking-wide">Journals Tracked</p>
                        <p class="text-4xl font-heading font-bold text-text dark:text-text-dark mt-2"><?php echo number_format($totalJournals); ?></p>
                    </div>
                    <div class="p-4 bg-sdg17/10 rounded-full">
                        <svg class="w-8 h-8 text-sdg17" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="card card-hover scroll-reveal" style="transition-delay: 300ms;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-text-muted text-sm font-medium uppercase tracking-wide">SDG Goals Covered</p>
                        <p class="text-4xl font-heading font-bold text-text dark:text-text-dark mt-2"><?php echo $sdgsCovered; ?>/17</p>
                    </div>
                    <div class="p-4 bg-accent/10 rounded-full">
                        <svg class="w-8 h-8 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Charts Section -->
<section class="py-16 bg-white dark:bg-bg-dark">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="card scroll-reveal">
                <h3 class="text-xl font-heading font-bold text-text dark:text-text-dark mb-6">Top SDG Distribution</h3>
                <?php if (!empty($sdgDistribution)): ?>
                <canvas id="sdgDistributionChart" height="300"></canvas>
                <?php else: ?>
                <div class="text-center py-12 text-text-muted">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <p>Belum ada data distribusi SDG</p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="card scroll-reveal">
                <h3 class="text-xl font-heading font-bold text-text dark:text-text-dark mb-6">Monthly Analysis Trend</h3>
                <?php if (!empty($monthlyTrend)): ?>
                <canvas id="monthlyTrendChart" height="300"></canvas>
                <?php else: ?>
                <div class="text-center py-12 text-text-muted">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                    </svg>
                    <p>Belum ada data tren bulanan</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Top Contributors -->
<?php if (!empty($topContributors)): ?>
<section class="py-16 bg-bg-soft dark:bg-bg-darkMuted">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-heading font-bold text-center text-text dark:text-text-dark mb-12 scroll-reveal">Top Active Contributors</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
            <?php foreach ($topContributors as $index => $contributor): ?>
            <div class="card card-hover text-center scroll-reveal" style="transition-delay: <?php echo $index * 100; ?>ms;">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-br from-primary to-accent flex items-center justify-center text-white font-bold text-xl">
                    <?php echo strtoupper(substr($contributor['name'], 0, 1)); ?>
                </div>
                <h4 class="font-semibold text-text dark:text-text-dark mb-1 truncate"><?php echo htmlspecialchars($contributor['name']); ?></h4>
                <p class="text-sm text-text-muted mb-3"><?php echo $contributor['contribution_count']; ?> contributions</p>
                <a href="/orcid/<?php echo urlencode($contributor['orcid']); ?>" class="text-primary text-sm hover:underline">View Profile →</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
<?php endif; ?>

<!-- Call to Action -->
<section class="py-20 bg-gradient-to-r from-primary to-accent text-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center scroll-reveal">
        <h2 class="text-4xl font-heading font-bold mb-6">Ready to Analyze Your Research?</h2>
        <p class="text-xl mb-8 opacity-90">Mulai analisis penelitian Anda sekarang dan temukan kontribusinya terhadap SDGs.</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/register" class="btn bg-white text-primary hover:bg-bg-soft font-semibold px-8 py-4">Get Started Free</a>
            <a href="/about" class="btn border-2 border-white text-white hover:bg-white/10 font-semibold px-8 py-4">Learn More</a>
        </div>
    </div>
</section>

<!-- Chart.js Scripts -->
<?php if ($hasData && (!empty($sdgDistribution) || !empty($monthlyTrend))): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
    const sdgColors = {
        'SDG1': '#E5243B', 'SDG2': '#DDA63A', 'SDG3': '#4C9F38',
        'SDG4': '#C5192D', 'SDG5': '#FF3A21', 'SDG6': '#26BDE2',
        'SDG7': '#FCC30B', 'SDG8': '#A21942', 'SDG9': '#FD6925',
        'SDG10': '#DD1367', 'SDG11': '#FD9D24', 'SDG12': '#BF8B2E',
        'SDG13': '#3F7E44', 'SDG14': '#0A97D9', 'SDG15': '#56C02B',
        'SDG16': '#00689D', 'SDG17': '#19486A'
    };
    
    <?php if (!empty($sdgDistribution)): ?>
    const sdgCtx = document.getElementById('sdgDistributionChart');
    if (sdgCtx) {
        new Chart(sdgCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($sdgDistribution, 'sdg_code')); ?>,
                datasets: [{
                    label: 'Number of Works',
                    data: <?php echo json_encode(array_column($sdgDistribution, 'count')); ?>,
                    backgroundColor: <?php echo json_encode(array_map(fn($s) => sdgColors[$s['sdg_code']] ?? '#64748B', $sdgDistribution)); ?>,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
    <?php endif; ?>
    
    <?php if (!empty($monthlyTrend)): ?>
    const trendCtx = document.getElementById('monthlyTrendChart');
    if (trendCtx) {
        new Chart(trendCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthlyTrend, 'month')); ?>,
                datasets: [{
                    label: 'Works Analyzed',
                    data: <?php echo json_encode(array_column($monthlyTrend, 'count')); ?>,
                    borderColor: '#1E40AF',
                    backgroundColor: 'rgba(30, 64, 175, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
    <?php endif; ?>
})();
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

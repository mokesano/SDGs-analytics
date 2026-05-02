<?php
/**
 * Homepage - Wizdam Sikola
 * SDGs Classification & Research Analytics Platform
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Wizdam Sikola - SDGs Research Analytics';
$pageDescription = 'Platform analisis dan klasifikasi penelitian berdasarkan Sustainable Development Goals (SDGs)';

// Get real-time statistics from database
$stats = [
    'researchers' => 0,
    'works' => 0,
    'journals' => 0,
    'sdg_coverage' => 0
];

try {
    $db = getDB();
    
    // Count researchers
    $stmt = $db->query("SELECT COUNT(*) FROM researchers");
    $stats['researchers'] = (int)$stmt->fetchColumn();
    
    // Count works
    $stmt = $db->query("SELECT COUNT(*) FROM works");
    $stats['works'] = (int)$stmt->fetchColumn();
    
    // Count journals
    $stmt = $db->query("SELECT COUNT(*) FROM journals");
    $stats['journals'] = (int)$stmt->fetchColumn();
    
    // Count unique SDG coverage
    $stmt = $db->query("SELECT COUNT(DISTINCT sdg_code) FROM work_sdgs");
    $stats['sdg_coverage'] = (int)$stmt->fetchColumn();
    
} catch (Exception $e) {
    error_log("Homepage stats error: " . $e->getMessage());
}

$hasData = ($stats['researchers'] > 0 || $stats['works'] > 0);

ob_start();
?>

<!-- Hero Section -->
<section class="relative overflow-hidden bg-gradient-to-br from-bg via-bg-soft to-bg-muted dark:from-bg-dark dark:via-bg-darkSoft dark:to-bg-darkMuted py-20 lg:py-32">
    <div class="absolute inset-0 opacity-10 dark:opacity-5">
        <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, #1E40AF 1px, transparent 0); background-size: 40px 40px;"></div>
    </div>
    
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-6xl font-heading font-extrabold mb-6 scroll-reveal">
            <span class="gradient-text">SDGs Research Analytics</span>
        </h1>
        <p class="text-xl text-text-muted max-w-3xl mx-auto mb-12 scroll-reveal" style="transition-delay: 100ms;">
            Platform cerdas untuk mengklasifikasikan dan menganalisis dampak penelitian terhadap 17 Tujuan Pembangunan Berkelanjutan PBB
        </p>
        
        <!-- Search Form -->
        <div class="max-w-2xl mx-auto scroll-reveal" style="transition-delay: 200ms;">
            <form action="/search" method="GET" class="flex flex-col sm:flex-row gap-4">
                <input type="text" name="q" placeholder="Masukkan ORCID, DOI, atau ISSN..." 
                       class="input flex-1 text-lg py-4" required>
                <button type="submit" class="btn btn-primary text-lg py-4 px-8">
                    Analisis
                </button>
            </form>
        </div>
    </div>
</section>

<!-- Stats Cards -->
<section class="py-16 bg-white dark:bg-bg-dark">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Researchers Card -->
            <div class="card card-hover scroll-reveal border-l-4 border-l-primary">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-text-muted text-sm font-medium uppercase tracking-wide">Researchers</p>
                        <p class="text-4xl font-bold mt-2"><?php echo number_format($stats['researchers']); ?></p>
                    </div>
                    <div class="p-3 bg-primary/10 rounded-full">
                        <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Works Card -->
            <div class="card card-hover scroll-reveal border-l-4 border-l-sdg4" style="transition-delay: 100ms;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-text-muted text-sm font-medium uppercase tracking-wide">Works Analyzed</p>
                        <p class="text-4xl font-bold mt-2"><?php echo number_format($stats['works']); ?></p>
                    </div>
                    <div class="p-3 bg-sdg4/10 rounded-full">
                        <svg class="w-8 h-8 text-sdg4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Journals Card -->
            <div class="card card-hover scroll-reveal border-l-4 border-l-sdg17" style="transition-delay: 200ms;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-text-muted text-sm font-medium uppercase tracking-wide">Journals</p>
                        <p class="text-4xl font-bold mt-2"><?php echo number_format($stats['journals']); ?></p>
                    </div>
                    <div class="p-3 bg-sdg17/10 rounded-full">
                        <svg class="w-8 h-8 text-sdg17" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- SDG Coverage Card -->
            <div class="card card-hover scroll-reveal border-l-4 border-l-sdg13" style="transition-delay: 300ms;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-text-muted text-sm font-medium uppercase tracking-wide">SDG Goals Covered</p>
                        <p class="text-4xl font-bold mt-2"><?php echo $stats['sdg_coverage']; ?>/17</p>
                    </div>
                    <div class="p-3 bg-sdg13/10 rounded-full">
                        <svg class="w-8 h-8 text-sdg13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if (!$hasData): ?>
<!-- Empty State -->
<section class="py-16 bg-bg-soft dark:bg-bg-darkSoft">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="card max-w-2xl mx-auto">
            <svg class="w-16 h-16 mx-auto text-text-muted mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="text-xl font-semibold mb-2">Belum Ada Data</h3>
            <p class="text-text-muted mb-6">Mulai dengan menganalisis peneliti atau jurnal menggunakan form pencarian di atas.</p>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Features Section -->
<section class="py-16 bg-white dark:bg-bg-dark">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-heading font-bold mb-4">Fitur Utama</h2>
            <p class="text-text-muted max-w-2xl mx-auto">Platform lengkap untuk analisis dampak penelitian terhadap SDGs</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="card scroll-reveal">
                <div class="w-12 h-12 bg-sdg4/10 rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-sdg4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-2">Analisis Mendalam</h3>
                <p class="text-text-muted">Klasifikasi otomatis menggunakan AI untuk memetakan penelitian ke 17 tujuan SDG dengan akurasi tinggi.</p>
            </div>
            
            <div class="card scroll-reveal" style="transition-delay: 100ms;">
                <div class="w-12 h-12 bg-sdg13/10 rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-sdg13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-2">Real-time Processing</h3>
                <p class="text-text-muted">Proses batch sequential yang efisien untuk menganalisis ratusan karya tanpa timeout.</p>
            </div>
            
            <div class="card scroll-reveal" style="transition-delay: 200ms;">
                <div class="w-12 h-12 bg-sdg17/10 rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-sdg17" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-2">Leaderboard & Analytics</h3>
                <p class="text-text-muted">Dashboard interaktif untuk melihat kontributor aktif dan distribusi penelitian per SDG.</p>
            </div>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>

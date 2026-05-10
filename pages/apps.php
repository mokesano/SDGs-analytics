<?php
$page_title = 'Aplikasi & Fitur';
$page_description = 'Jelajahi semua tools dan aplikasi yang tersedia di platform Wizdam AI SDG Classification Analysis.';

$apps = [
    [
        'icon' => 'fab fa-orcid',
        'icon_color' => '#a6ce39',
        'icon_bg' => 'rgba(166,206,57,.12)',
        'title' => 'ORCID Researcher Analysis',
        'desc' => 'Analisis profil lengkap peneliti menggunakan ORCID ID. Dapatkan peta kontribusi SDG dari seluruh portofolio publikasi — karya, dampak, contributor type, dan confidence score per SDG.',
        'status' => 'active',
        'status_label' => 'Aktif',
        'tags' => ['Sequential Batch', 'Anti-Timeout', 'Cached'],
        'link' => '?page=home',
        'cta' => 'Mulai Analisis ORCID',
    ],
    [
        'icon' => 'fas fa-file-alt',
        'icon_color' => '#ff5627',
        'icon_bg' => 'rgba(255,86,39,.1)',
        'title' => 'DOI Article Analysis',
        'desc' => 'Klasifikasi artikel tunggal via DOI — SDG relevance score, contributor type, dan analisis tiga sumber metadata: CrossRef, OpenAlex, dan Semantic Scholar sebagai fallback.',
        'status' => 'active',
        'status_label' => 'Aktif',
        'tags' => ['Multi-Source', 'CrossRef + OpenAlex'],
        'link' => '?page=home',
        'cta' => 'Analisis DOI Artikel',
    ],
    [
        'icon' => 'fas fa-file-pdf',
        'icon_color' => '#8b5cf6',
        'icon_bg' => 'rgba(139,92,246,.1)',
        'title' => 'PDF Upload Analysis',
        'desc' => 'Upload file PDF artikel Anda, sistem akan mengekstrak teks secara otomatis menggunakan parser PHP dan menjalankan analisis SDG terhadap konten penuh dokumen.',
        'status' => 'soon',
        'status_label' => 'Segera Hadir',
        'tags' => ['PDF Parser', 'Text Extraction'],
        'link' => '#',
        'cta' => 'Segera Hadir',
    ],
    [
        'icon' => 'fas fa-link',
        'icon_color' => '#0ea5e9',
        'icon_bg' => 'rgba(14,165,233,.1)',
        'title' => 'URL Article Fetch',
        'desc' => 'Submit URL artikel ilmiah, platform akan menggunakan Readability.php untuk mengekstrak konten bersih dari halaman web, lalu menjalankan analisis SDG otomatis.',
        'status' => 'soon',
        'status_label' => 'Segera Hadir',
        'tags' => ['Readability.php', 'Auto-Extract'],
        'link' => '#',
        'cta' => 'Segera Hadir',
    ],
    [
        'icon' => 'fas fa-layer-group',
        'icon_color' => '#10b981',
        'icon_bg' => 'rgba(16,185,129,.1)',
        'title' => 'Bulk Analysis',
        'desc' => 'Analisis ratusan ORCID ID sekaligus via upload file CSV. Ideal untuk pemetaan SDG tingkat departemen, fakultas, atau institusi secara menyeluruh.',
        'status' => 'soon',
        'status_label' => 'Segera Hadir',
        'tags' => ['CSV Upload', 'Batch Processing', 'Export'],
        'link' => '#',
        'cta' => 'Segera Hadir',
    ],
    [
        'icon' => 'fas fa-newspaper',
        'icon_color' => '#f59e0b',
        'icon_bg' => 'rgba(245,158,11,.1)',
        'title' => 'Journal Profile',
        'desc' => 'Profil jurnal Scopus lengkap — SJR score, quartile ranking, subject areas, dan pemetaan SDG dominan dari karya-karya yang dipublikasikan di jurnal tersebut.',
        'status' => 'soon',
        'status_label' => 'Segera Hadir',
        'tags' => ['Scopus', 'SJR', 'Quartile'],
        'link' => '#',
        'cta' => 'Segera Hadir',
    ],
    [
        'icon' => 'fas fa-code',
        'icon_color' => '#6366f1',
        'icon_bg' => 'rgba(99,102,241,.1)',
        'title' => 'API Public Access',
        'desc' => 'JSON API endpoint publik untuk integrasi dengan OJS, sistem manajemen riset, atau aplikasi pihak ketiga. Supports init/batch/summary/doi endpoints.',
        'status' => 'active',
        'status_label' => 'Aktif',
        'tags' => ['REST API', 'JSON Response', 'OJS Ready'],
        'link' => '?page=api-reference',
        'cta' => 'Lihat Dokumentasi API',
    ],
    [
        'icon' => 'fas fa-trophy',
        'icon_color' => '#f59e0b',
        'icon_bg' => 'rgba(245,158,11,.1)',
        'title' => 'Leaderboard',
        'desc' => 'Ranking peneliti berdasarkan kontribusi SDG — jumlah karya teranalisis, SDG yang paling banyak dikontribusikan, dan skor active contributor tertinggi.',
        'status' => 'active',
        'status_label' => 'Aktif',
        'tags' => ['Ranking', 'SDG Score', 'Real-time'],
        'link' => '?page=analitics-dashboard',
        'cta' => 'Buka Leaderboard',
    ],
];
?>

<div class="page-header">
    <div class="container">
        <div class="section-label">Platform Tools</div>
        <h1 class="section-title">Aplikasi & Fitur Wizdam AI</h1>
        <p class="section-subtitle">Rangkaian tools lengkap untuk analisis, klasifikasi, dan visualisasi kontribusi penelitian terhadap SDG.</p>
    </div>
</div>

<section class="section">
    <div class="container">

        <!-- Filter bar -->
        <div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:2rem;">
            <button class="apps-filter-btn active" onclick="filterApps('all', this)"><i class="fas fa-th"></i> Semua</button>
            <button class="apps-filter-btn" onclick="filterApps('active', this)"><i class="fas fa-check-circle"></i> Aktif</button>
            <button class="apps-filter-btn" onclick="filterApps('soon', this)"><i class="fas fa-clock"></i> Segera Hadir</button>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.5rem;" id="apps-grid">
            <?php foreach ($apps as $i => $app): ?>
            <div class="app-card magic-card reveal apps-item" data-status="<?= $app['status'] ?>" style="transition-delay:<?= ($i % 4) * 80 ?>ms;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem;">
                    <div class="app-icon" style="background:<?= $app['icon_bg'] ?>;color:<?= $app['icon_color'] ?>;width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;">
                        <i class="<?= $app['icon'] ?>"></i>
                    </div>
                    <span class="badge <?= $app['status'] === 'active' ? 'badge-success' : 'badge-warning' ?>">
                        <?= $app['status'] === 'active' ? '<i class="fas fa-circle" style="font-size:.5rem;"></i> ' : '<i class="fas fa-clock"></i> ' ?>
                        <?= $app['status_label'] ?>
                    </span>
                </div>
                <h3 style="font-size:1.05rem;color:var(--dark,#1a2e45);margin-bottom:.5rem;"><?= htmlspecialchars($app['title']) ?></h3>
                <p style="color:var(--gray-500);font-size:.875rem;line-height:1.65;margin-bottom:1.25rem;"><?= htmlspecialchars($app['desc']) ?></p>
                <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:1.25rem;">
                    <?php foreach ($app['tags'] as $tag): ?>
                    <span class="badge badge-dark"><?= htmlspecialchars($tag) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php if ($app['status'] === 'active'): ?>
                <a href="<?= $app['link'] ?>" class="btn btn-primary" style="width:100%;justify-content:center;">
                    <i class="<?= $app['icon'] ?>"></i> <?= htmlspecialchars($app['cta']) ?>
                </a>
                <?php else: ?>
                <button class="btn btn-secondary" style="width:100%;justify-content:center;cursor:not-allowed;opacity:.7;" disabled>
                    <i class="fas fa-clock"></i> <?= htmlspecialchars($app['cta']) ?>
                </button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Bottom CTA -->
        <div style="text-align:center;margin-top:3rem;padding:2rem;background:var(--gray-50,#f8fafc);border-radius:16px;border:1px solid var(--gray-200);">
            <i class="fas fa-lightbulb" style="font-size:2rem;color:#f59e0b;margin-bottom:1rem;display:block;"></i>
            <h3 style="color:var(--dark,#1a2e45);margin-bottom:.5rem;">Ada ide fitur baru?</h3>
            <p style="color:var(--gray-500);font-size:.875rem;margin-bottom:1.25rem;">Kami terbuka untuk masukan dan permintaan fitur dari komunitas peneliti.</p>
            <a href="?page=contact" class="btn btn-outline"><i class="fas fa-paper-plane"></i> Kirim Permintaan Fitur</a>
        </div>

    </div>
</section>

<style>
.apps-filter-btn {
    padding: .5rem 1rem;
    border: 1.5px solid var(--gray-300, #e2e8f0);
    background: white;
    border-radius: 8px;
    font-size: .875rem;
    font-weight: 600;
    color: var(--gray-600);
    cursor: pointer;
    transition: all .2s;
    display: inline-flex;
    align-items: center;
    gap: .4rem;
}
.apps-filter-btn.active, .apps-filter-btn:hover {
    background: #ff5627;
    border-color: #ff5627;
    color: white;
}
</style>

<script>
function filterApps(status, btn) {
    document.querySelectorAll('.apps-filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.apps-item').forEach(item => {
        if (status === 'all' || item.dataset.status === status) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}
</script>

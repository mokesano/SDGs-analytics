<?php
$page_title = 'Karir';
$page_description = 'Bergabunglah dengan tim Wizdam AI dan bantu membangun masa depan analisis riset akademik berbasis SDG di Indonesia.';

$positions = [
    [
        'title' => 'AI/ML Engineer',
        'type' => 'Full-time',
        'location' => 'Remote',
        'dept' => 'Engineering',
        'dept_color' => '#6366f1',
        'desc' => 'Kembangkan dan optimalkan algoritma klasifikasi SDG berbasis NLP. Bertanggung jawab atas peningkatan akurasi model dan pengembangan fitur AI baru.',
        'skills' => ['Python', 'NLP', 'Transformers', 'scikit-learn', 'TF-IDF'],
        'icon' => 'fas fa-brain',
    ],
    [
        'title' => 'Full Stack Developer (PHP/JS)',
        'type' => 'Full-time',
        'location' => 'Remote',
        'dept' => 'Engineering',
        'dept_color' => '#ff5627',
        'desc' => 'Bangun dan pelihara platform web Wizdam AI. Bertanggung jawab atas frontend React/Vanilla JS dan backend PHP, serta integrasi dengan API eksternal.',
        'skills' => ['PHP 8+', 'JavaScript', 'SQLite', 'REST API', 'CSS/SCSS'],
        'icon' => 'fas fa-code',
    ],
    [
        'title' => 'Research Scientist (SDG Domain Expert)',
        'type' => 'Part-time',
        'location' => 'Remote',
        'dept' => 'Research',
        'dept_color' => '#10b981',
        'desc' => 'Validasi dan kurasikan dataset SDG, kembangkan panduan klasifikasi, serta pastikan metodologi platform sesuai dengan standar internasional UN AURORA.',
        'skills' => ['SDG Knowledge', 'Academic Research', 'Data Curation', 'NLP Basics', 'UN AURORA'],
        'icon' => 'fas fa-microscope',
    ],
];

$benefits = [
    ['icon' => 'fas fa-laptop-house', 'color' => '#6366f1', 'title' => 'Remote / Hybrid', 'desc' => 'Kerja dari mana saja di Indonesia — fleksibel dan hasil-oriented.'],
    ['icon' => 'fas fa-flask', 'color' => '#ff5627', 'title' => 'Research-Focused', 'desc' => 'Lingkungan kerja yang mendorong eksplorasi dan inovasi ilmiah.'],
    ['icon' => 'fas fa-globe-asia', 'color' => '#10b981', 'title' => 'Dampak Nyata', 'desc' => 'Kontribusi Anda langsung mendukung riset SDG Indonesia dan dunia.'],
    ['icon' => 'fas fa-chart-line', 'color' => '#f59e0b', 'title' => 'Berkembang Bersama', 'desc' => 'Akses ke jurnal, konferensi, dan pelatihan untuk pengembangan karir.'],
];
?>

<div class="page-header">
    <div class="container">
        <div class="section-label">Karir</div>
        <h1 class="section-title">Bergabunglah Membangun Masa Depan Riset Akademik</h1>
        <p class="section-subtitle">Wizdam AI mencari individu berbakat yang bersemangat menggabungkan kecerdasan buatan dengan dampak sosial nyata melalui SDG.</p>
    </div>
</div>

<section class="section">
<div class="container">

    <!-- Benefits -->
    <div style="margin-bottom:3rem;">
        <div class="section-label" style="margin-bottom:.5rem;">Mengapa Wizdam AI?</div>
        <h2 style="font-size:1.3rem;color:var(--dark,#1a2e45);margin-bottom:1.5rem;">Bergabung dengan Tim yang Bermisi</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.25rem;">
            <?php foreach ($benefits as $b): ?>
            <div class="card" style="display:flex;flex-direction:column;align-items:flex-start;gap:.75rem;">
                <div style="width:48px;height:48px;background:<?= $b['color'] ?>18;color:<?= $b['color'] ?>;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;">
                    <i class="<?= $b['icon'] ?>"></i>
                </div>
                <div>
                    <strong style="color:var(--dark,#1a2e45);display:block;margin-bottom:.25rem;"><?= htmlspecialchars($b['title']) ?></strong>
                    <span style="font-size:.875rem;color:var(--gray-500);line-height:1.6;"><?= htmlspecialchars($b['desc']) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Open Positions -->
    <div style="margin-bottom:3rem;">
        <div class="section-label" style="margin-bottom:.5rem;">Posisi Terbuka</div>
        <h2 style="font-size:1.3rem;color:var(--dark,#1a2e45);margin-bottom:1.5rem;">3 Posisi Tersedia</h2>
        <div style="display:flex;flex-direction:column;gap:1.25rem;">
            <?php foreach ($positions as $i => $pos): ?>
            <div class="card careers-position-card reveal" style="transition-delay:<?= $i * 100 ?>ms;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;margin-bottom:1rem;">
                    <div style="display:flex;align-items:center;gap:.75rem;">
                        <div style="width:48px;height:48px;background:<?= $pos['dept_color'] ?>18;color:<?= $pos['dept_color'] ?>;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;">
                            <i class="<?= $pos['icon'] ?>"></i>
                        </div>
                        <div>
                            <h3 style="font-size:1.1rem;color:var(--dark,#1a2e45);margin-bottom:.2rem;"><?= htmlspecialchars($pos['title']) ?></h3>
                            <div style="font-size:.8rem;color:<?= $pos['dept_color'] ?>;font-weight:700;"><?= htmlspecialchars($pos['dept']) ?></div>
                        </div>
                    </div>
                    <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                        <span class="badge badge-success"><i class="fas fa-briefcase"></i> <?= htmlspecialchars($pos['type']) ?></span>
                        <span class="badge badge-info"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($pos['location']) ?></span>
                    </div>
                </div>
                <p style="font-size:.875rem;color:var(--gray-600);line-height:1.7;margin-bottom:1rem;"><?= htmlspecialchars($pos['desc']) ?></p>
                <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:1.25rem;">
                    <span style="font-size:.78rem;font-weight:700;color:var(--gray-400);text-transform:uppercase;letter-spacing:.04em;align-self:center;">Skills:</span>
                    <?php foreach ($pos['skills'] as $skill): ?>
                    <span class="badge badge-dark"><?= htmlspecialchars($skill) ?></span>
                    <?php endforeach; ?>
                </div>
                <a href="?page=contact" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Lamar Posisi Ini</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- No fitting position CTA -->
    <div class="section-dark" style="border-radius:16px;padding:2.5rem;text-align:center;">
        <i class="fas fa-handshake" style="font-size:2.5rem;color:#ff5627;margin-bottom:1rem;display:block;"></i>
        <h3 style="color:white;font-size:1.2rem;margin-bottom:.5rem;">Tidak ada posisi yang sesuai?</h3>
        <p style="color:rgba(255,255,255,.65);font-size:.875rem;max-width:480px;margin:0 auto 1.5rem;line-height:1.7;">
            Kami selalu terbuka untuk talenta luar biasa yang memiliki semangat terhadap riset dan teknologi AI. Kirim CV dan ceritakan visi Anda kepada kami.
        </p>
        <div style="display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap;">
            <a href="mailto:careers@wizdam.ai" class="btn btn-primary"><i class="fas fa-envelope"></i> careers@wizdam.ai</a>
            <a href="?page=contact" class="btn btn-outline-white"><i class="fas fa-comment"></i> Kirim Pesan</a>
        </div>
    </div>

</div>
</section>

<style>
.careers-position-card:hover { border-color: #ff5627 !important; }
</style>

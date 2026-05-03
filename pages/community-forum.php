<?php
$page_title = 'Komunitas';
$page_description = 'Bergabunglah dengan komunitas peneliti Wizdam AI — diskusi, berbagi pengalaman, dan kolaborasi seputar SDG dan analisis riset.';

$discussions = [
    [
        'title' => 'Cara meningkatkan confidence score analisis SDG untuk artikel bidang hukum?',
        'author' => 'Dr. Ahmad Fauzi',
        'avatar' => 'AF',
        'color' => '#6366f1',
        'replies' => 12,
        'views' => 87,
        'tag' => 'Tips & Trik',
        'tag_color' => '#6366f1',
        'time' => '2 hari lalu',
    ],
    [
        'title' => 'Bug report: ORCID ID dengan huruf akhir "X" menghasilkan error validasi',
        'author' => 'Ir. Siti Rahayu',
        'avatar' => 'SR',
        'color' => '#ff5627',
        'replies' => 4,
        'views' => 31,
        'tag' => 'Bug Report',
        'tag_color' => '#ef4444',
        'time' => '5 hari lalu',
    ],
    [
        'title' => 'Diskusi: Apakah penelitian bidang hukum adat bisa berkontribusi pada SDG 16?',
        'author' => 'Prof. Bambang Santoso',
        'avatar' => 'BS',
        'color' => '#10b981',
        'replies' => 23,
        'views' => 156,
        'tag' => 'Diskusi SDG',
        'tag_color' => '#10b981',
        'time' => '1 minggu lalu',
    ],
];

$guidelines = [
    'Hormati sesama anggota — gunakan bahasa yang sopan dan konstruktif dalam setiap diskusi.',
    'Bagikan pengalaman nyata — ulasan dan tips berbasis pengalaman langsung lebih berharga daripada spekulasi.',
    'Laporkan bug secara detail — sertakan ORCID/DOI yang digunakan dan pesan error yang muncul.',
    'Jaga relevansi — pastikan pertanyaan dan diskusi berkaitan dengan SDG, riset akademik, atau platform Wizdam.',
];
?>

<div class="page-header">
    <div class="container">
        <div class="section-label">Komunitas</div>
        <h1 class="section-title">Komunitas Peneliti Wizdam</h1>
        <p class="section-subtitle">Tempat berbagi pengalaman, bertanya, berdiskusi, dan berkolaborasi seputar SDG dan analisis riset akademik.</p>
    </div>
</div>

<section class="section">
<div class="container">

    <!-- Platform Links -->
    <div style="margin-bottom:2.5rem;">
        <div class="section-label" style="margin-bottom:.75rem;">Platform Diskusi</div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;">
            <div class="card" style="text-align:center;border:2px dashed var(--gray-300);">
                <i class="fab fa-discord" style="font-size:2.5rem;color:#5865f2;margin-bottom:.75rem;display:block;"></i>
                <h3 style="font-size:1rem;margin-bottom:.35rem;">Discord Server</h3>
                <p style="font-size:.8rem;color:var(--gray-400);margin-bottom:1rem;">Chat real-time dengan komunitas peneliti Wizdam</p>
                <span class="badge badge-warning">Segera Hadir</span>
            </div>
            <div class="card" style="text-align:center;">
                <i class="fab fa-github" style="font-size:2.5rem;color:#1f1f1f;margin-bottom:.75rem;display:block;"></i>
                <h3 style="font-size:1rem;margin-bottom:.35rem;">GitHub Discussions</h3>
                <p style="font-size:.8rem;color:var(--gray-400);margin-bottom:1rem;">Diskusi teknis, bug report, dan feature request</p>
                <a href="https://github.com/mokesano/SDGs-analytics/discussions" target="_blank" rel="noopener" class="btn btn-primary" style="font-size:.85rem;padding:.5rem 1rem;">
                    <i class="fab fa-github"></i> Buka GitHub Discussions
                </a>
            </div>
            <div class="card" style="text-align:center;">
                <i class="fas fa-envelope-open-text" style="font-size:2.5rem;color:#ff5627;margin-bottom:.75rem;display:block;"></i>
                <h3 style="font-size:1rem;margin-bottom:.35rem;">Mailing List</h3>
                <p style="font-size:.8rem;color:var(--gray-400);margin-bottom:1rem;">Update berita, tips, dan artikel terbaru via email</p>
                <a href="?page=blog" class="btn btn-outline" style="font-size:.85rem;padding:.5rem 1rem;">
                    <i class="fas fa-bell"></i> Daftar Notifikasi
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Discussions -->
    <div style="margin-bottom:2.5rem;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;flex-wrap:wrap;gap:.75rem;">
            <div>
                <div class="section-label" style="margin-bottom:.25rem;">Forum Preview</div>
                <h2 style="font-size:1.2rem;color:var(--dark,#1a2e45);margin:0;">Diskusi Terbaru</h2>
            </div>
            <a href="https://github.com/mokesano/SDGs-analytics/discussions" target="_blank" rel="noopener" class="btn btn-outline" style="font-size:.85rem;">
                <i class="fas fa-external-link-alt"></i> Lihat Semua
            </a>
        </div>
        <div style="display:flex;flex-direction:column;gap:1rem;">
            <?php foreach ($discussions as $disc): ?>
            <div class="card community-disc-card">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;">
                    <div style="display:flex;gap:.75rem;align-items:flex-start;flex:1;min-width:200px;">
                        <div style="width:36px;height:36px;background:<?= $disc['color'] ?>;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;flex-shrink:0;">
                            <?= htmlspecialchars($disc['avatar']) ?>
                        </div>
                        <div>
                            <h4 style="font-size:.95rem;color:var(--dark,#1a2e45);margin-bottom:.25rem;line-height:1.5;"><?= htmlspecialchars($disc['title']) ?></h4>
                            <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">
                                <span class="badge" style="background:<?= $disc['tag_color'] ?>18;color:<?= $disc['tag_color'] ?>;border:1px solid <?= $disc['tag_color'] ?>30;"><?= htmlspecialchars($disc['tag']) ?></span>
                                <span style="font-size:.78rem;color:var(--gray-400);"><i class="fas fa-user"></i> <?= htmlspecialchars($disc['author']) ?></span>
                                <span style="font-size:.78rem;color:var(--gray-400);"><i class="fas fa-clock"></i> <?= htmlspecialchars($disc['time']) ?></span>
                            </div>
                        </div>
                    </div>
                    <div style="display:flex;gap:1rem;font-size:.8rem;color:var(--gray-400);flex-shrink:0;">
                        <span><i class="fas fa-comment"></i> <?= $disc['replies'] ?> balasan</span>
                        <span><i class="fas fa-eye"></i> <?= $disc['views'] ?> lihat</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Community Guidelines -->
    <div class="card" style="margin-bottom:2rem;">
        <h2 style="font-size:1.1rem;color:var(--dark,#1a2e45);margin-bottom:1rem;">
            <i class="fas fa-scroll" style="color:#ff5627;margin-right:.5rem;"></i>Panduan Komunitas
        </h2>
        <div style="display:flex;flex-direction:column;gap:.6rem;">
            <?php foreach ($guidelines as $i => $rule): ?>
            <div style="display:flex;gap:.75rem;align-items:flex-start;font-size:.875rem;color:var(--gray-600);line-height:1.6;">
                <span style="flex-shrink:0;width:24px;height:24px;background:#ff5627;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.75rem;margin-top:.05rem;"><?= $i + 1 ?></span>
                <?= htmlspecialchars($rule) ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Join CTA -->
    <div class="section-dark" style="border-radius:16px;padding:2.5rem;text-align:center;">
        <i class="fas fa-users" style="font-size:2.5rem;color:#ff5627;margin-bottom:1rem;display:block;"></i>
        <h3 style="color:white;font-size:1.2rem;margin-bottom:.5rem;">Bergabunglah dengan Komunitas</h3>
        <p style="color:rgba(255,255,255,.65);font-size:.875rem;max-width:480px;margin:0 auto 1.5rem;line-height:1.7;">
            Ribuan peneliti Indonesia sudah menggunakan Wizdam AI. Bergabunglah, bagikan pengalaman, dan bantu meningkatkan platform bersama-sama.
        </p>
        <div style="display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap;">
            <a href="https://github.com/mokesano/SDGs-analytics/discussions" target="_blank" rel="noopener" class="btn btn-primary"><i class="fab fa-github"></i> Mulai Diskusi</a>
            <a href="?page=contact" class="btn btn-outline-white"><i class="fas fa-envelope"></i> Hubungi Tim</a>
        </div>
    </div>

</div>
</section>

<style>
.community-disc-card:hover { border-color: #ff5627 !important; }
</style>

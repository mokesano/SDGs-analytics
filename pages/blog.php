<?php
$page_title = 'Blog';
$page_description = 'Blog Wizdam AI — artikel tentang SDG, AI dalam penelitian akademik, dan panduan platform. Segera hadir.';

$upcoming = [
    [
        'category' => 'Kecerdasan Buatan',
        'cat_color' => '#6366f1',
        'title' => 'Bagaimana AI Merevolusi Pengukuran Dampak SDG dalam Penelitian Akademik',
        'excerpt' => 'Eksplorasi mendalam tentang bagaimana teknologi NLP dan machine learning mengubah cara kita mengukur dan memahami kontribusi ilmiah terhadap agenda SDG global.',
        'author' => 'Wizdam AI Team',
        'date' => 'Januari 2025',
        'read_time' => '8 menit',
    ],
    [
        'category' => 'Panduan Peneliti',
        'cat_color' => '#10b981',
        'title' => 'Panduan Lengkap ORCID untuk Peneliti Indonesia',
        'excerpt' => 'Dari pendaftaran ORCID hingga memaksimalkan visibilitas publikasi Anda secara global. Panduan praktis yang dirancang khusus untuk peneliti Indonesia.',
        'author' => 'Wizdam AI Team',
        'date' => 'Februari 2025',
        'read_time' => '6 menit',
    ],
    [
        'category' => 'Analisis SDG',
        'cat_color' => '#ff5627',
        'title' => 'Mengapa Abstrak Penelitian Anda Penting untuk Klasifikasi SDG',
        'excerpt' => 'Abstrak yang ditulis dengan baik tidak hanya meningkatkan visibilitas artikel Anda, tetapi juga secara langsung memengaruhi akurasi klasifikasi SDG otomatis oleh sistem AI.',
        'author' => 'Wizdam AI Team',
        'date' => 'Maret 2025',
        'read_time' => '5 menit',
    ],
];
?>

<div class="page-header">
    <div class="container">
        <div class="section-label">Blog</div>
        <h1 class="section-title">Wizdam AI Blog</h1>
        <p class="section-subtitle">Wawasan, panduan, dan cerita dari komunitas peneliti SDG Indonesia.</p>
    </div>
</div>

<section class="section">
<div class="container">

    <!-- Coming Soon Banner -->
    <div class="blog-coming-soon">
        <div class="blog-coming-soon-inner">
            <div style="font-size:3rem;margin-bottom:1rem;"><i class="fas fa-pen-fancy" style="color:#ff5627;"></i></div>
            <h2 style="font-size:1.75rem;color:white;margin-bottom:.75rem;">Blog Segera Hadir</h2>
            <p style="color:rgba(255,255,255,.65);max-width:500px;margin:0 auto 2rem;line-height:1.8;">
                Tim Wizdam AI sedang mempersiapkan konten berkualitas tinggi tentang SDG, kecerdasan buatan dalam penelitian, dan panduan platform. Daftarkan email Anda untuk notifikasi pertama.
            </p>

            <!-- Newsletter Form -->
            <form id="newsletterForm" onsubmit="handleNewsletter(event)" style="display:flex;gap:.75rem;max-width:420px;margin:0 auto;flex-wrap:wrap;justify-content:center;">
                <input type="email" id="newsletterEmail" placeholder="email@universitas.ac.id" required
                    style="flex:1;min-width:200px;padding:.75rem 1rem;border-radius:8px;border:2px solid rgba(255,255,255,.2);background:rgba(255,255,255,.1);color:white;font-size:.9rem;outline:none;"
                    onfocus="this.style.borderColor='#ff5627'" onblur="this.style.borderColor='rgba(255,255,255,.2)'">
                <button type="submit" class="btn btn-primary" style="white-space:nowrap;">
                    <i class="fas fa-bell"></i> Beri Tahu Saya
                </button>
            </form>
            <div id="newsletterSuccess" style="display:none;color:#a7f3d0;margin-top:1rem;font-weight:600;">
                <i class="fas fa-check-circle"></i> Terima kasih! Anda akan dihubungi saat blog diluncurkan.
            </div>
        </div>
    </div>

    <!-- Upcoming Articles -->
    <div style="margin-top:3rem;">
        <div class="section-label" style="margin-bottom:.5rem;">Pratinjau</div>
        <h2 style="font-size:1.3rem;color:var(--dark,#1a2e45);margin-bottom:1.75rem;">Artikel yang Akan Datang</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.5rem;">
            <?php foreach ($upcoming as $i => $article): ?>
            <div class="blog-preview-card reveal" style="transition-delay:<?= $i * 100 ?>ms;">
                <div class="blog-preview-header" style="background:linear-gradient(135deg,<?= $article['cat_color'] ?>22,<?= $article['cat_color'] ?>11);">
                    <span class="badge" style="background:<?= $article['cat_color'] ?>20;color:<?= $article['cat_color'] ?>;border:1px solid <?= $article['cat_color'] ?>40;">
                        <?= htmlspecialchars($article['category']) ?>
                    </span>
                    <span class="badge badge-warning" style="margin-left:.4rem;"><i class="fas fa-clock"></i> Segera Hadir</span>
                </div>
                <div class="blog-preview-body">
                    <h3><?= htmlspecialchars($article['title']) ?></h3>
                    <p><?= htmlspecialchars($article['excerpt']) ?></p>
                    <div class="blog-preview-meta">
                        <span><i class="fas fa-user"></i> <?= htmlspecialchars($article['author']) ?></span>
                        <span><i class="fas fa-calendar"></i> <?= htmlspecialchars($article['date']) ?></span>
                        <span><i class="fas fa-clock"></i> <?= htmlspecialchars($article['read_time']) ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Stay Updated -->
    <div style="margin-top:3rem;padding:2rem;background:var(--gray-50,#f8fafc);border-radius:16px;border:1px solid var(--gray-200);text-align:center;">
        <i class="fas fa-rss" style="font-size:1.75rem;color:#f59e0b;margin-bottom:.75rem;display:block;"></i>
        <h3 style="color:var(--dark,#1a2e45);margin-bottom:.5rem;">Ikuti Perkembangan Kami</h3>
        <p style="color:var(--gray-500);font-size:.875rem;margin-bottom:1.25rem;">Sementara menunggu blog diluncurkan, ikuti kami di media sosial atau lihat dokumentasi platform.</p>
        <div style="display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap;">
            <a href="https://github.com/mokesano/SDGs-analytics" target="_blank" rel="noopener" class="btn btn-outline"><i class="fab fa-github"></i> GitHub</a>
            <a href="?page=documentation" class="btn btn-outline"><i class="fas fa-book"></i> Dokumentasi</a>
        </div>
    </div>

</div>
</section>

<style>
.blog-coming-soon {
    background: linear-gradient(135deg, #1a2e45 0%, #2d4a6e 100%);
    border-radius: 20px;
    padding: 3.5rem 2rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.blog-coming-soon::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 200px; height: 200px;
    background: rgba(255,86,39,.15);
    border-radius: 50%;
    pointer-events: none;
}
.blog-coming-soon::after {
    content: '';
    position: absolute;
    bottom: -60px; left: -40px;
    width: 250px; height: 250px;
    background: rgba(99,102,241,.1);
    border-radius: 50%;
    pointer-events: none;
}
.blog-coming-soon-inner { position: relative; z-index: 1; }

.blog-preview-card {
    background: white;
    border-radius: 14px;
    overflow: hidden;
    border: 1.5px solid var(--gray-200);
    transition: transform .2s, box-shadow .2s;
}
.blog-preview-card:hover { transform: translateY(-4px); box-shadow: 0 12px 28px rgba(0,0,0,.09); }
.blog-preview-header { padding: 1.25rem 1.25rem .75rem; }
.blog-preview-body { padding: .75rem 1.25rem 1.5rem; }
.blog-preview-body h3 { font-size: 1rem; color: var(--dark,#1a2e45); line-height: 1.5; margin-bottom: .6rem; }
.blog-preview-body p { font-size: .85rem; color: var(--gray-500); line-height: 1.65; margin-bottom: 1rem; }
.blog-preview-meta { display: flex; gap: .75rem; flex-wrap: wrap; font-size: .78rem; color: var(--gray-400); }
.blog-preview-meta span { display: flex; align-items: center; gap: .3rem; }
</style>

<script>
function handleNewsletter(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mendaftar...';
    setTimeout(() => {
        e.target.style.display = 'none';
        document.getElementById('newsletterSuccess').style.display = 'block';
    }, 800);
}
</script>

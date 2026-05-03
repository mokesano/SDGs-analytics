<?php
$page_title = 'Kemitraan';
$page_description = 'Jalin kemitraan dengan Wizdam AI untuk memperluas dampak penelitian SDG Anda — institusi akademik, penerbit, dan lembaga pemerintah.';

$integrations = [
    ['name' => 'ORCID', 'url' => 'https://orcid.org', 'color' => '#a6ce39', 'icon' => 'fab fa-orcid', 'desc' => 'Identifikasi unik peneliti dan data publikasi'],
    ['name' => 'CrossRef', 'url' => 'https://crossref.org', 'color' => '#c32027', 'icon' => 'fas fa-link', 'desc' => 'Metadata bibliografi dan DOI resolution'],
    ['name' => 'OpenAlex', 'url' => 'https://openalex.org', 'color' => '#3b82f6', 'icon' => 'fas fa-database', 'desc' => 'Database publikasi ilmiah terbuka'],
    ['name' => 'Semantic Scholar', 'url' => 'https://semanticscholar.org', 'color' => '#8b5cf6', 'icon' => 'fas fa-brain', 'desc' => 'AI-powered academic research tool'],
    ['name' => 'Dimensions', 'url' => 'https://dimensions.ai', 'color' => '#f59e0b', 'icon' => 'fas fa-chart-bar', 'desc' => 'Research intelligence platform'],
];

$partnership_types = [
    [
        'icon' => 'fas fa-university',
        'color' => '#6366f1',
        'title' => 'Institusi Akademik',
        'desc' => 'Universitas, lembaga penelitian, dan pusat studi yang ingin memetakan kontribusi SDG dari seluruh staf dan mahasiswa mereka. Kami menyediakan integrasi khusus dan laporan institusional.',
        'benefits' => ['Laporan SDG tingkat departemen/fakultas', 'Integrasi dengan sistem informasi riset', 'Dashboard khusus institusi', 'Akses bulk analysis via API'],
    ],
    [
        'icon' => 'fas fa-newspaper',
        'color' => '#ff5627',
        'title' => 'Penerbit Penelitian',
        'desc' => 'Jurnal ilmiah dan penerbit akademik yang ingin menampilkan profil SDG dari karya yang dipublikasikan. Tingkatkan dampak dan visibilitas jurnal Anda.',
        'benefits' => ['Klasifikasi SDG otomatis untuk setiap artikel', 'Widget SDG badge untuk website jurnal', 'Integrasi dengan OJS via plugin', 'Laporan SDG tahunan jurnal'],
    ],
    [
        'icon' => 'fas fa-landmark',
        'color' => '#10b981',
        'title' => 'Lembaga Pemerintah',
        'desc' => 'Kementerian, badan riset, dan lembaga pemerintah yang ingin memantau kemajuan riset nasional dalam mendukung SDG sebagai bagian dari kebijakan ilmu pengetahuan dan teknologi.',
        'benefits' => ['Pemetaan riset nasional vs. SDG', 'Data agregat untuk perencanaan kebijakan', 'Laporan kustom sesuai kebutuhan', 'Koordinasi dengan agenda VNR Indonesia'],
    ],
];
?>

<div class="page-header">
    <div class="container">
        <div class="section-label">Kemitraan</div>
        <h1 class="section-title">Kemitraan untuk Dampak yang Lebih Besar</h1>
        <p class="section-subtitle">Bersama-sama kita dapat mempercepat pengukuran dan peningkatan kontribusi penelitian Indonesia terhadap Tujuan Pembangunan Berkelanjutan.</p>
    </div>
</div>

<section class="section">
<div class="container">

    <!-- Partnership Types -->
    <div style="margin-bottom:3rem;">
        <div class="section-label" style="margin-bottom:.5rem;">Jenis Kemitraan</div>
        <h2 style="font-size:1.3rem;color:var(--dark,#1a2e45);margin-bottom:1.75rem;">Kami Bermitra Dengan</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem;">
            <?php foreach ($partnership_types as $i => $pt): ?>
            <div class="card reveal" style="transition-delay:<?= $i * 100 ?>ms;">
                <div style="width:56px;height:56px;background:<?= $pt['color'] ?>18;color:<?= $pt['color'] ?>;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;margin-bottom:1rem;">
                    <i class="<?= $pt['icon'] ?>"></i>
                </div>
                <h3 style="font-size:1.1rem;color:var(--dark,#1a2e45);margin-bottom:.5rem;"><?= htmlspecialchars($pt['title']) ?></h3>
                <p style="font-size:.875rem;color:var(--gray-500);line-height:1.7;margin-bottom:1rem;"><?= htmlspecialchars($pt['desc']) ?></p>
                <ul style="padding-left:0;list-style:none;display:flex;flex-direction:column;gap:.4rem;">
                    <?php foreach ($pt['benefits'] as $benefit): ?>
                    <li style="display:flex;align-items:flex-start;gap:.5rem;font-size:.8rem;color:var(--gray-600);">
                        <i class="fas fa-check-circle" style="color:<?= $pt['color'] ?>;margin-top:.15rem;flex-shrink:0;"></i>
                        <?= htmlspecialchars($benefit) ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Current Integrations -->
    <div style="margin-bottom:3rem;">
        <div class="section-label" style="margin-bottom:.5rem;">Ekosistem Data</div>
        <h2 style="font-size:1.3rem;color:var(--dark,#1a2e45);margin-bottom:1.5rem;">Integrasi yang Sudah Aktif</h2>
        <div style="display:flex;gap:1rem;flex-wrap:wrap;">
            <?php foreach ($integrations as $int): ?>
            <a href="<?= $int['url'] ?>" target="_blank" rel="noopener" class="partner-integration-badge" style="border-color:<?= $int['color'] ?>40;color:<?= $int['color'] ?>;">
                <i class="<?= $int['icon'] ?>"></i>
                <div>
                    <div style="font-weight:700;line-height:1;"><?= htmlspecialchars($int['name']) ?></div>
                    <div style="font-size:.75rem;color:var(--gray-400);line-height:1.2;margin-top:.2rem;"><?= htmlspecialchars($int['desc']) ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Partnership Form -->
    <div class="card">
        <h2 style="font-size:1.2rem;color:var(--dark,#1a2e45);margin-bottom:.5rem;">
            <i class="fas fa-handshake" style="color:#ff5627;margin-right:.5rem;"></i>Ajukan Kemitraan
        </h2>
        <p style="font-size:.875rem;color:var(--gray-500);margin-bottom:1.5rem;">Ceritakan tentang organisasi Anda dan bagaimana kita bisa berkolaborasi.</p>
        <div id="partnerSuccess" style="display:none;" class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span>Terima kasih atas minat Anda! Tim kami akan menghubungi Anda dalam 3–5 hari kerja.</span>
        </div>
        <form id="partnerForm" onsubmit="handlePartner(event)">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                <div class="form-group">
                    <label class="form-label">Nama Organisasi <span style="color:#ff5627;">*</span></label>
                    <input type="text" class="form-control" placeholder="Universitas / Lembaga / Instansi" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Jenis Organisasi <span style="color:#ff5627;">*</span></label>
                    <select class="form-control" required>
                        <option value="">-- Pilih Jenis --</option>
                        <option>Universitas / Perguruan Tinggi</option>
                        <option>Lembaga Penelitian</option>
                        <option>Penerbit Jurnal / OJS</option>
                        <option>Kementerian / BRIN / Lembaga Pemerintah</option>
                        <option>NGO / Organisasi Internasional</option>
                        <option>Lainnya</option>
                    </select>
                </div>
            </div>
            <div class="form-group" style="margin-bottom:1rem;">
                <label class="form-label">Email Kontak <span style="color:#ff5627;">*</span></label>
                <input type="email" class="form-control" placeholder="email@organisasi.ac.id" required>
            </div>
            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label">Deskripsi Kebutuhan Kemitraan <span style="color:#ff5627;">*</span></label>
                <textarea class="form-control" rows="4" placeholder="Ceritakan tentang organisasi Anda dan bagaimana Anda ingin bermitra dengan Wizdam AI..." required style="resize:vertical;"></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Kirim Permintaan Kemitraan</button>
        </form>
    </div>

</div>
</section>

<style>
.partner-integration-badge {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .75rem 1.25rem;
    border: 2px solid;
    border-radius: 10px;
    background: white;
    text-decoration: none;
    transition: transform .15s, box-shadow .15s;
    font-size: .9rem;
}
.partner-integration-badge:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,.1); }
.partner-integration-badge i { font-size: 1.4rem; }

.alert-success {
    background: rgba(16,185,129,.1);
    border: 1px solid rgba(16,185,129,.3);
    color: #065f46;
    padding: .75rem 1rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: .5rem;
    margin-bottom: 1rem;
    font-size: .875rem;
}

@media (max-width: 640px) {
    .form-group + .form-group { margin-top: 0; }
    [style*="grid-template-columns:1fr 1fr"] { grid-template-columns: 1fr !important; }
}
</style>

<script>
function handlePartner(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button[type=submit]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';
    setTimeout(() => {
        e.target.style.display = 'none';
        document.getElementById('partnerSuccess').style.display = 'flex';
    }, 900);
}
</script>

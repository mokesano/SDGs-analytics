<?php
$page_title = 'Tutorial';
$page_description = 'Panduan langkah demi langkah untuk menggunakan platform Wizdam AI SDG Classification — dari analisis ORCID pertama hingga penggunaan API.';

$tutorials = [
    [
        'icon' => 'fab fa-orcid',
        'icon_color' => '#a6ce39',
        'title' => 'Analisis ORCID Pertama Anda',
        'duration' => '5 menit',
        'level' => 'Pemula',
        'level_badge' => 'badge-success',
        'desc' => 'Pelajari cara menganalisis seluruh portofolio publikasi peneliti menggunakan ORCID ID. Dari menemukan ORCID ID hingga membaca hasil.',
        'steps' => [
            'Buka <a href="?page=home">halaman utama</a> Wizdam AI dan pilih tab ORCID.',
            'Temukan ORCID ID Anda di <a href="https://orcid.org" target="_blank">orcid.org</a> atau halaman profil institusi Anda. Format: <code>XXXX-XXXX-XXXX-XXXX</code>.',
            'Masukkan ORCID ID atau URL lengkap (<code>https://orcid.org/XXXX-...</code>) ke kolom input.',
            'Klik tombol <strong>"Analisis ORCID"</strong> dan tunggu proses init selesai.',
            'Amati progress bar yang menampilkan kemajuan batch processing secara real-time.',
            'Setelah selesai, baca hasil: SDG summary, contributor type, dan distribusi kontribusi per SDG.',
        ],
    ],
    [
        'icon' => 'fas fa-chart-bar',
        'icon_color' => '#ff5627',
        'title' => 'Memahami Hasil Analisis SDG',
        'duration' => '7 menit',
        'level' => 'Menengah',
        'level_badge' => 'badge-warning',
        'desc' => 'Panduan interpreting hasil klasifikasi — SDG badge, confidence score, contributor type, dan cara membaca distribusi SDG peneliti.',
        'steps' => [
            '<strong>SDG Badge</strong>: Setiap badge menampilkan nomor SDG (1–17) dengan warna resmi UN. Badge yang muncul menunjukkan SDG yang relevan dengan karya tersebut.',
            '<strong>Confidence Score</strong>: Nilai 0–1. Lebih dari 0.70 = high confidence (sangat reliable). 0.40–0.70 = medium. Di bawah 0.40 = low confidence.',
            '<strong>Active Contributor</strong> (skor ≥ 0.65): Karya Anda berkontribusi langsung dan substansial pada SDG tersebut.',
            '<strong>Relevant Contributor</strong> (0.40–0.64): Karya relevan namun kontribusinya tidak langsung.',
            '<strong>Discutor</strong> (0.20–0.39): Karya membahas topik SDG tanpa kontribusi langsung yang terukur.',
            'Untuk profil ORCID, lihat grafik distribusi SDG untuk memahami pola kontribusi keseluruhan peneliti.',
        ],
    ],
    [
        'icon' => 'fas fa-file-alt',
        'icon_color' => '#3b82f6',
        'title' => 'Analisis DOI Artikel',
        'duration' => '3 menit',
        'level' => 'Pemula',
        'level_badge' => 'badge-success',
        'desc' => 'Cara menemukan DOI artikel ilmiah dan menggunakannya untuk klasifikasi SDG instan dengan Wizdam AI.',
        'steps' => [
            'Buka halaman artikel yang ingin Anda analisis di website jurnal atau Google Scholar.',
            'Temukan DOI artikel — biasanya tertera di halaman detail artikel dengan format <code>10.xxxx/xxxxx</code> atau sebagai link <code>https://doi.org/...</code>.',
            'Di Wizdam AI, pilih tab <strong>"DOI"</strong> di halaman utama.',
            'Copy-paste DOI atau URL DOI lengkap ke kolom input.',
            'Klik <strong>"Analisis Artikel"</strong> — proses hanya membutuhkan beberapa detik.',
            'Baca hasil: judul, abstrak yang ditemukan, SDG yang relevan, confidence score, dan contributor type.',
            'Jika abstrak tidak tersedia, sistem otomatis mencari ke OpenAlex dan Semantic Scholar.',
        ],
    ],
    [
        'icon' => 'fas fa-code',
        'icon_color' => '#8b5cf6',
        'title' => 'Menggunakan API Wizdam',
        'duration' => '10 menit',
        'level' => 'Developer',
        'level_badge' => 'badge-info',
        'desc' => 'Quick start untuk developer — integrasikan Wizdam AI ke aplikasi, OJS journal, atau skrip penelitian Anda.',
        'steps' => [
            'Baca <a href="?page=api-reference">API Reference</a> untuk memahami endpoint yang tersedia.',
            'Semua request menggunakan <strong>HTTP POST</strong> ke <code>https://www.wizdam.sangia.org/public/index.php</code>.',
            'Untuk analisis DOI: kirim form-data dengan <code>_sdg=doi</code> dan <code>doi=10.xxxx/xxxxx</code>.',
            'Untuk analisis ORCID: jalankan 3 tahap berurutan — <code>init</code> → loop <code>batch</code> → <code>summary</code>.',
            'Response selalu dalam format JSON. Periksa field <code>status</code> untuk mengetahui apakah request berhasil.',
            'Implementasikan exponential backoff untuk error 429 (rate limit): tunggu 2s, 4s, 8s sebelum retry.',
            'Lihat contoh kode JavaScript dan PHP lengkap di <a href="?page=api-reference#example-js">API Reference</a>.',
        ],
    ],
];
?>

<div class="page-header">
    <div class="container">
        <div class="section-label">Tutorial</div>
        <h1 class="section-title">Panduan Langkah demi Langkah</h1>
        <p class="section-subtitle">Tutorial praktis untuk memaksimalkan penggunaan platform Wizdam AI SDG Classification.</p>
    </div>
</div>

<section class="section">
<div class="container">

    <div style="display:flex;flex-direction:column;gap:2.5rem;">
        <?php foreach ($tutorials as $i => $tut): ?>
        <div class="card reveal" style="transition-delay:<?= $i * 80 ?>ms;" id="tutorial-<?= $i ?>">
            <!-- Header -->
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;margin-bottom:1.25rem;">
                <div style="display:flex;align-items:center;gap:.75rem;">
                    <div style="width:52px;height:52px;background:<?= $tut['icon_color'] ?>18;color:<?= $tut['icon_color'] ?>;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;">
                        <i class="<?= $tut['icon'] ?>"></i>
                    </div>
                    <div>
                        <h3 style="font-size:1.15rem;color:var(--dark,#1a2e45);margin-bottom:.25rem;"><?= htmlspecialchars($tut['title']) ?></h3>
                        <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
                            <span class="badge <?= $tut['level_badge'] ?>"><?= htmlspecialchars($tut['level']) ?></span>
                            <span class="badge badge-dark"><i class="fas fa-clock"></i> <?= htmlspecialchars($tut['duration']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <p style="font-size:.875rem;color:var(--gray-500);line-height:1.7;margin-bottom:1.5rem;"><?= htmlspecialchars($tut['desc']) ?></p>

            <!-- Steps -->
            <div style="display:flex;flex-direction:column;gap:.75rem;margin-bottom:1.5rem;">
                <?php foreach ($tut['steps'] as $j => $step): ?>
                <div style="display:flex;gap:1rem;align-items:flex-start;">
                    <div style="flex-shrink:0;width:28px;height:28px;background:<?= $tut['icon_color'] ?>;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;margin-top:.1rem;">
                        <?= $j + 1 ?>
                    </div>
                    <p style="font-size:.875rem;color:var(--gray-600);line-height:1.7;margin:0;"><?= $step ?></p>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- CTA -->
            <div>
                <?php if ($i === 0 || $i === 2): ?>
                <a href="?page=home" class="btn btn-primary"><i class="fas fa-play"></i> Mulai Tutorial</a>
                <?php elseif ($i === 3): ?>
                <a href="?page=api-reference" class="btn btn-primary"><i class="fas fa-code"></i> Buka API Reference</a>
                <?php else: ?>
                <a href="?page=home" class="btn btn-primary"><i class="fas fa-play"></i> Mulai Tutorial</a>
                <?php endif; ?>
                <a href="?page=help" class="btn btn-outline" style="margin-left:.75rem;"><i class="fas fa-question-circle"></i> Bantuan</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Bottom CTA -->
    <div style="margin-top:3rem;text-align:center;padding:2rem;background:var(--gray-50,#f8fafc);border-radius:16px;border:1px solid var(--gray-200);">
        <h3 style="color:var(--dark,#1a2e45);margin-bottom:.5rem;">Siap untuk mulai?</h3>
        <p style="color:var(--gray-500);font-size:.875rem;margin-bottom:1.25rem;">Langsung coba platform atau baca dokumentasi lengkap untuk pemahaman lebih mendalam.</p>
        <div style="display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap;">
            <a href="?page=home" class="btn btn-primary"><i class="fas fa-rocket"></i> Coba Wizdam AI</a>
            <a href="?page=documentation" class="btn btn-outline"><i class="fas fa-book"></i> Baca Dokumentasi</a>
        </div>
    </div>

</div>
</section>

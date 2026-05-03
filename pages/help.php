<?php
$page_title = 'Pusat Bantuan';
$page_description = 'Temukan jawaban atas pertanyaan umum tentang platform Wizdam AI SDG Classification — ORCID, DOI, hasil analisis, dan API.';

$faqs = [
    [
        'q' => 'Apa itu ORCID dan bagaimana cara menemukan ORCID ID saya?',
        'a' => 'ORCID (Open Researcher and Contributor ID) adalah pengidentifikasi unik 16 digit untuk peneliti akademik di seluruh dunia. Format: XXXX-XXXX-XXXX-XXXX, contoh: 0000-0002-5152-9727. Daftar gratis di <a href="https://orcid.org" target="_blank" rel="noopener">orcid.org</a>. ORCID Anda bisa ditemukan di profil akademik, email konfirmasi ORCID, atau halaman profil institusi Anda.',
    ],
    [
        'q' => 'Bagaimana cara kerja klasifikasi SDG di Wizdam?',
        'a' => 'Platform menggunakan tiga metode yang digabungkan: (1) <strong>Keyword matching</strong> terhadap database kata kunci SDG resmi UN AURORA — mencocokkan istilah spesifik dari judul, abstrak, dan keywords artikel. (2) <strong>Cosine similarity</strong> berbasis TF-IDF — mengukur kedekatan semantik antara teks artikel dengan deskripsi target SDG. (3) <strong>Causal relationship analysis</strong> — mendeteksi apakah penelitian menganalisis hubungan sebab-akibat yang langsung berkaitan dengan capaian SDG. Gabungan ketiganya menghasilkan confidence score 0–1 per SDG.',
    ],
    [
        'q' => 'Apa bedanya Active Contributor, Relevant Contributor, dan Discutor?',
        'a' => '<strong>Active Contributor</strong> (confidence ≥ 0.65): penelitian secara langsung dan substansial berkontribusi pada SDG — ada kontribusi nyata dan terukur terhadap capaian target SDG. <strong>Relevant Contributor</strong> (0.40 – 0.64): penelitian relevan dengan SDG namun kontribusinya tidak langsung atau masih pada tahap eksplorasi. <strong>Discutor</strong> (0.20 – 0.39): penelitian mendiskusikan isu terkait SDG tetapi tidak berkontribusi secara langsung. <strong>Not Relevant</strong> (&lt; 0.20): penelitian tidak terkait dengan SDG tersebut.',
    ],
    [
        'q' => 'Kenapa abstrak artikel saya tidak lengkap atau kosong?',
        'a' => 'Beberapa penerbit tidak menyertakan abstrak lengkap di CrossRef karena kebijakan hak cipta. Wizdam mencoba tiga sumber secara berurutan: <strong>CrossRef → OpenAlex → Semantic Scholar</strong>. Jika ketiganya tidak memiliki abstrak, analisis dilakukan hanya berdasarkan judul dan keyword yang tersedia. Hasilnya tetap valid namun confidence score mungkin lebih rendah.',
    ],
    [
        'q' => 'Apakah data saya tersimpan di server Wizdam?',
        'a' => 'Hasil analisis disimpan di database lokal kami untuk keperluan cache (7 hari), leaderboard, dan arsip statistik agregat. ORCID ID dan DOI bersifat data publik. Kami tidak menyimpan data pribadi dan tidak membagikan hasil analisis individu ke pihak ketiga. Anda dapat meminta penghapusan data melalui email ke <a href="mailto:privacy@wizdam.ai">privacy@wizdam.ai</a>.',
    ],
    [
        'q' => 'Seberapa akurat klasifikasi SDG Wizdam?',
        'a' => 'Akurasi sekitar 78–85% berdasarkan validasi internal dengan dataset peneliti SDG yang telah dikurasi secara manual. Akurasi tertinggi dicapai pada artikel dengan abstrak lengkap dan keyword eksplisit. Kami terus meningkatkan akurasi melalui pembaruan database keyword dan penyempurnaan model similarity.',
    ],
    [
        'q' => 'Bisakah saya menganalisis DOI yang tidak ada di CrossRef?',
        'a' => 'Ya. Jika DOI tidak ditemukan di CrossRef, sistem secara otomatis mencari di <strong>OpenAlex</strong> dan kemudian <strong>Semantic Scholar</strong> sebagai fallback. Selama salah satu sumber memiliki metadata artikel tersebut, analisis akan tetap berjalan.',
    ],
    [
        'q' => 'Berapa lama proses analisis ORCID?',
        'a' => 'Sekitar 2–5 menit tergantung jumlah karya peneliti. Sistem memproses <strong>3 karya per batch request</strong> untuk menghindari server timeout. Untuk peneliti dengan 50+ karya, proses dapat memakan waktu hingga 10 menit. Progress bar akan menampilkan perkembangan secara real-time.',
    ],
    [
        'q' => 'Bagaimana format ORCID yang benar untuk dimasukkan?',
        'a' => 'Wizdam menerima dua format: (1) <strong>Format langsung</strong>: <code>0000-0002-5152-9727</code>. (2) <strong>URL lengkap</strong>: <code>https://orcid.org/0000-0002-5152-9727</code>. Sistem akan secara otomatis mengekstrak ORCID ID dari URL jika dimasukkan dalam format tersebut.',
    ],
    [
        'q' => 'Apa itu confidence score dan bagaimana cara membacanya?',
        'a' => 'Confidence score adalah nilai 0–1 yang menunjukkan tingkat keyakinan sistem terhadap relevansi penelitian dengan SDG tertentu. <strong>&gt; 0.70</strong>: sangat relevan (high confidence). <strong>0.40–0.70</strong>: relevan (medium confidence). <strong>0.20–0.40</strong>: kemungkinan relevan (low confidence). <strong>&lt; 0.20</strong>: tidak relevan.',
    ],
];
?>

<div class="page-header">
    <div class="container">
        <div class="section-label">Pusat Bantuan</div>
        <h1 class="section-title">Bagaimana Kami Dapat Membantu?</h1>
        <p class="section-subtitle">Temukan jawaban cepat untuk pertanyaan umum tentang platform Wizdam AI.</p>
    </div>
</div>

<section class="section">
    <div class="container" style="max-width:860px;">

        <!-- Quick Links -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:2.5rem;">
            <a href="?page=documentation" class="help-quick-link">
                <i class="fas fa-book" style="color:#ff5627;font-size:1.4rem;margin-bottom:.5rem;display:block;"></i>
                <strong>Dokumentasi</strong>
                <span>Panduan lengkap platform</span>
            </a>
            <a href="?page=tutorials" class="help-quick-link">
                <i class="fas fa-play-circle" style="color:#10b981;font-size:1.4rem;margin-bottom:.5rem;display:block;"></i>
                <strong>Tutorial</strong>
                <span>Panduan langkah demi langkah</span>
            </a>
            <a href="?page=api-reference" class="help-quick-link">
                <i class="fas fa-code" style="color:#6366f1;font-size:1.4rem;margin-bottom:.5rem;display:block;"></i>
                <strong>API Reference</strong>
                <span>Dokumentasi endpoint API</span>
            </a>
            <a href="?page=contact" class="help-quick-link">
                <i class="fas fa-envelope" style="color:#f59e0b;font-size:1.4rem;margin-bottom:.5rem;display:block;"></i>
                <strong>Hubungi Kami</strong>
                <span>Tanya langsung ke tim</span>
            </a>
        </div>

        <!-- FAQ Accordion -->
        <h2 style="font-size:1.3rem;color:var(--dark,#1a2e45);margin-bottom:1.25rem;">
            <i class="fas fa-question-circle" style="color:#ff5627;margin-right:.5rem;"></i>
            Pertanyaan yang Sering Diajukan
        </h2>

        <div class="help-faq-list" id="helpFaqList">
            <?php foreach ($faqs as $i => $faq): ?>
            <div class="help-faq-item" id="faq-<?= $i ?>">
                <button class="help-faq-question" onclick="toggleFaq(<?= $i ?>)" aria-expanded="false" aria-controls="faq-body-<?= $i ?>">
                    <span><?= htmlspecialchars($faq['q']) ?></span>
                    <i class="fas fa-chevron-down help-faq-chevron"></i>
                </button>
                <div class="help-faq-body" id="faq-body-<?= $i ?>" aria-hidden="true">
                    <div class="help-faq-answer">
                        <?= $faq['a'] ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Still have questions CTA -->
        <div style="margin-top:3rem;padding:2.5rem;background:linear-gradient(135deg,#1a2e45,#2d4a6e);border-radius:16px;text-align:center;">
            <i class="fas fa-comments" style="font-size:2.5rem;color:#ff5627;margin-bottom:1rem;display:block;"></i>
            <h3 style="color:white;font-size:1.2rem;margin-bottom:.5rem;">Masih punya pertanyaan?</h3>
            <p style="color:rgba(255,255,255,.65);font-size:.875rem;margin-bottom:1.5rem;">
                Tim kami siap membantu. Kirim pertanyaan Anda dan kami akan merespons dalam 2–3 hari kerja.
            </p>
            <a href="?page=contact" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Kirim Pertanyaan</a>
        </div>

    </div>
</section>

<style>
.help-quick-link {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 1.25rem 1rem;
    background: white;
    border: 1.5px solid var(--gray-200, #e2e8f0);
    border-radius: 12px;
    text-decoration: none;
    color: var(--dark, #1a2e45);
    transition: all .2s;
    gap: .15rem;
}
.help-quick-link:hover {
    border-color: #ff5627;
    box-shadow: 0 4px 16px rgba(255,86,39,.12);
    transform: translateY(-2px);
}
.help-quick-link strong { font-size: .95rem; }
.help-quick-link span { font-size: .8rem; color: var(--gray-500); }

.help-faq-list { display: flex; flex-direction: column; gap: .75rem; }
.help-faq-item {
    border: 1.5px solid var(--gray-200, #e2e8f0);
    border-radius: 12px;
    overflow: hidden;
    transition: border-color .2s;
}
.help-faq-item.open { border-color: #ff5627; }
.help-faq-question {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background: none;
    border: none;
    cursor: pointer;
    text-align: left;
    font-size: .95rem;
    font-weight: 600;
    color: var(--dark, #1a2e45);
    transition: background .15s;
}
.help-faq-question:hover { background: var(--gray-50, #f8fafc); }
.help-faq-item.open .help-faq-question { background: rgba(255,86,39,.06); color: #ff5627; }
.help-faq-chevron { flex-shrink: 0; transition: transform .3s; color: var(--gray-400); font-size: .85rem; }
.help-faq-item.open .help-faq-chevron { transform: rotate(180deg); color: #ff5627; }

.help-faq-body {
    max-height: 0;
    overflow: hidden;
    transition: max-height .35s ease;
}
.help-faq-body.open { max-height: 500px; }
.help-faq-answer {
    padding: 0 1.25rem 1.25rem;
    font-size: .875rem;
    color: var(--gray-600);
    line-height: 1.8;
}
.help-faq-answer code {
    background: var(--gray-100, #f1f5f9);
    padding: .1em .4em;
    border-radius: 4px;
    font-family: monospace;
    font-size: .85em;
}
</style>

<script>
function toggleFaq(index) {
    const item = document.getElementById('faq-' + index);
    const body = document.getElementById('faq-body-' + index);
    const btn  = item.querySelector('.help-faq-question');
    const isOpen = item.classList.contains('open');

    // close all
    document.querySelectorAll('.help-faq-item.open').forEach(el => {
        el.classList.remove('open');
        el.querySelector('.help-faq-body').classList.remove('open');
        el.querySelector('.help-faq-question').setAttribute('aria-expanded', 'false');
    });

    if (!isOpen) {
        item.classList.add('open');
        body.classList.add('open');
        btn.setAttribute('aria-expanded', 'true');
    }
}
</script>

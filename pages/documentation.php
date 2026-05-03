<?php
$page_title = 'Dokumentasi';
$page_description = 'Dokumentasi lengkap platform Wizdam AI SDG Classification — panduan memulai, analisis ORCID dan DOI, memahami hasil, dan referensi data.';
?>
<div class="page-header">
    <div class="container">
        <div class="section-label">Developer Docs</div>
        <h1 class="section-title">Dokumentasi Platform</h1>
        <p class="section-subtitle">Panduan lengkap untuk menggunakan dan mengintegrasikan platform Wizdam AI SDG Classification.</p>
    </div>
</div>
<section class="section">
    <div class="container">
        <div class="layout-with-sidebar">
            <aside class="sidebar">
                <nav class="sidebar-nav">
                    <div class="sidebar-nav-section">
                        <div class="sidebar-nav-title">Memulai</div>
                        <a href="#getting-started" class="sidebar-nav-link active" onclick="scrollToSection('getting-started')"><i class="fas fa-rocket"></i> Panduan Cepat</a>
                        <a href="#how-it-works" class="sidebar-nav-link" onclick="scrollToSection('how-it-works')"><i class="fas fa-cogs"></i> Cara Kerja</a>
                    </div>
                    <div class="sidebar-nav-section" style="border-top:1px solid var(--gray-200);padding-top:.5rem;">
                        <div class="sidebar-nav-title">Analisis</div>
                        <a href="#orcid-analysis" class="sidebar-nav-link" onclick="scrollToSection('orcid-analysis')"><i class="fab fa-orcid"></i> ORCID Analysis</a>
                        <a href="#doi-analysis" class="sidebar-nav-link" onclick="scrollToSection('doi-analysis')"><i class="fas fa-file-alt"></i> DOI Analysis</a>
                    </div>
                    <div class="sidebar-nav-section" style="border-top:1px solid var(--gray-200);padding-top:.5rem;">
                        <div class="sidebar-nav-title">Memahami Hasil</div>
                        <a href="#results" class="sidebar-nav-link" onclick="scrollToSection('results')"><i class="fas fa-chart-bar"></i> Membaca Hasil</a>
                        <a href="#contributor-types" class="sidebar-nav-link" onclick="scrollToSection('contributor-types')"><i class="fas fa-users"></i> Contributor Types</a>
                        <a href="#confidence-score" class="sidebar-nav-link" onclick="scrollToSection('confidence-score')"><i class="fas fa-percentage"></i> Confidence Score</a>
                    </div>
                    <div class="sidebar-nav-section" style="border-top:1px solid var(--gray-200);padding-top:.5rem;">
                        <div class="sidebar-nav-title">Referensi</div>
                        <a href="#data-sources" class="sidebar-nav-link" onclick="scrollToSection('data-sources')"><i class="fas fa-database"></i> Sumber Data</a>
                        <a href="#api-overview" class="sidebar-nav-link" onclick="scrollToSection('api-overview')"><i class="fas fa-code"></i> API Overview</a>
                        <a href="?page=api-reference" class="sidebar-nav-link"><i class="fas fa-external-link-alt"></i> API Reference</a>
                    </div>
                </nav>
            </aside>
            <main>

                <!-- Getting Started -->
                <div id="getting-started" class="card" style="margin-bottom:1.5rem;">
                    <div class="card-header">
                        <h2 style="font-size:1.25rem;margin:0;"><i class="fas fa-rocket" style="color:#ff5627;"></i> Panduan Cepat</h2>
                    </div>
                    <p>Mulai menggunakan Wizdam AI dalam tiga langkah mudah:</p>
                    <div style="display:flex;flex-direction:column;gap:1rem;margin-top:1rem;">
                        <div class="doc-step">
                            <div class="doc-step-num">1</div>
                            <div>
                                <strong>Masukkan ORCID ID atau DOI artikel</strong>
                                <p style="margin:.25rem 0 0;color:var(--gray-500);font-size:.875rem;">Di halaman utama, pilih mode analisis (ORCID atau DOI) dan masukkan identifier Anda. ORCID format: <code>XXXX-XXXX-XXXX-XXXX</code>. DOI format: <code>10.xxxx/xxxxx</code>.</p>
                            </div>
                        </div>
                        <div class="doc-step">
                            <div class="doc-step-num">2</div>
                            <div>
                                <strong>Klik "Analisis" dan tunggu proses selesai</strong>
                                <p style="margin:.25rem 0 0;color:var(--gray-500);font-size:.875rem;">Sistem akan memproses data secara bertahap (batch) untuk analisis ORCID, atau satu kali request untuk DOI. Progress bar menampilkan perkembangan secara real-time.</p>
                            </div>
                        </div>
                        <div class="doc-step">
                            <div class="doc-step-num">3</div>
                            <div>
                                <strong>Baca hasil — SDG badge, contributor type, confidence score</strong>
                                <p style="margin:.25rem 0 0;color:var(--gray-500);font-size:.875rem;">Hasil menampilkan SDG yang relevan dengan badge berwarna sesuai warna resmi UN, jenis kontribusi peneliti, dan skor kepercayaan untuk setiap SDG.</p>
                            </div>
                        </div>
                    </div>
                    <div style="margin-top:1.25rem;">
                        <a href="?page=home" class="btn btn-primary"><i class="fas fa-play"></i> Coba Sekarang</a>
                        <a href="?page=tutorials" class="btn btn-outline" style="margin-left:.75rem;"><i class="fas fa-graduation-cap"></i> Lihat Tutorial</a>
                    </div>
                </div>

                <!-- How it works -->
                <div id="how-it-works" class="card" style="margin-bottom:1.5rem;">
                    <div class="card-header">
                        <h2 style="font-size:1.25rem;margin:0;"><i class="fas fa-cogs" style="color:#ff5627;"></i> Cara Kerja Platform</h2>
                    </div>
                    <p>Wizdam AI menggunakan kombinasi tiga metode analisis untuk menghasilkan klasifikasi SDG yang akurat:</p>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-top:1rem;">
                        <div class="stat-card" style="text-align:left;">
                            <div style="font-weight:700;margin-bottom:.35rem;color:var(--dark,#1a2e45);">1. Keyword Matching</div>
                            <div style="font-size:.85rem;color:var(--gray-500);line-height:1.6;">Pencocokan dengan database kata kunci SDG resmi UN AURORA. Mencari istilah spesifik di judul, abstrak, dan keyword artikel.</div>
                        </div>
                        <div class="stat-card" style="text-align:left;">
                            <div style="font-weight:700;margin-bottom:.35rem;color:var(--dark,#1a2e45);">2. Cosine Similarity</div>
                            <div style="font-size:.85rem;color:var(--gray-500);line-height:1.6;">Mengukur kedekatan semantik antara vektor TF-IDF teks artikel dengan deskripsi target SDG menggunakan cosine similarity.</div>
                        </div>
                        <div class="stat-card" style="text-align:left;">
                            <div style="font-weight:700;margin-bottom:.35rem;color:var(--dark,#1a2e45);">3. Causal Analysis</div>
                            <div style="font-size:.85rem;color:var(--gray-500);line-height:1.6;">Mendeteksi hubungan kausalitas dalam teks — apakah penelitian membahas dampak langsung dan terukur terhadap SDG target.</div>
                        </div>
                    </div>
                </div>

                <!-- ORCID Analysis -->
                <div id="orcid-analysis" class="card" style="margin-bottom:1.5rem;">
                    <div class="card-header">
                        <h2 style="font-size:1.25rem;margin:0;"><i class="fab fa-orcid" style="color:#a6ce39;"></i> Analisis ORCID</h2>
                    </div>
                    <h4 style="margin:.75rem 0 .4rem;">Format yang Diterima</h4>
                    <pre style="background:var(--dark-code,#0d1117);color:#79c0ff;padding:1rem;border-radius:8px;overflow-x:auto;font-size:.85rem;line-height:1.7;"><!-- Format langsung -->
0000-0002-5152-9727

<!-- URL lengkap juga diterima -->
https://orcid.org/0000-0002-5152-9727</pre>
                    <h4 style="margin:1rem 0 .4rem;">Proses 3-Tahap Sequential</h4>
                    <p style="color:var(--gray-600);font-size:.875rem;line-height:1.7;">Untuk menghindari server timeout, analisis ORCID dijalankan dalam 3 tahap:</p>
                    <ol style="padding-left:1.5rem;color:var(--gray-600);font-size:.875rem;line-height:2.2;">
                        <li><strong>Init</strong> — Ambil profil peneliti dan daftar seluruh karya dari ORCID API.</li>
                        <li><strong>Batch</strong> — Proses 3 karya per request secara berulang hingga semua karya selesai dianalisis.</li>
                        <li><strong>Summary</strong> — Gabungkan semua hasil batch menjadi ringkasan profil SDG peneliti.</li>
                    </ol>
                    <div class="alert alert-info" style="margin-top:1rem;"><i class="fas fa-info-circle"></i><span>Profil ORCID harus bersifat publik. ORCID yang baru dibuat memerlukan waktu 24–48 jam untuk tersedia di API.</span></div>
                </div>

                <!-- DOI Analysis -->
                <div id="doi-analysis" class="card" style="margin-bottom:1.5rem;">
                    <div class="card-header">
                        <h2 style="font-size:1.25rem;margin:0;"><i class="fas fa-file-alt" style="color:#ff5627;"></i> Analisis DOI</h2>
                    </div>
                    <h4 style="margin:.75rem 0 .4rem;">Format DOI</h4>
                    <pre style="background:var(--dark-code,#0d1117);color:#79c0ff;padding:1rem;border-radius:8px;overflow-x:auto;font-size:.85rem;line-height:1.7;"><!-- Format prefix/suffix -->
10.1038/nature12373

<!-- URL DOI juga diterima -->
https://doi.org/10.1038/nature12373</pre>
                    <h4 style="margin:1rem 0 .4rem;">Sumber Metadata</h4>
                    <p style="color:var(--gray-600);font-size:.875rem;">Wizdam mencari metadata artikel dari tiga sumber secara berurutan:</p>
                    <ol style="padding-left:1.5rem;color:var(--gray-600);font-size:.875rem;line-height:2.2;">
                        <li><strong>CrossRef</strong> — Sumber utama untuk metadata bibliografi dan abstrak.</li>
                        <li><strong>OpenAlex</strong> — Fallback pertama jika CrossRef tidak memiliki abstrak lengkap.</li>
                        <li><strong>Semantic Scholar</strong> — Fallback kedua untuk abstrak tambahan.</li>
                    </ol>
                </div>

                <!-- Understanding Results -->
                <div id="results" class="card" style="margin-bottom:1.5rem;">
                    <div class="card-header">
                        <h2 style="font-size:1.25rem;margin:0;"><i class="fas fa-chart-bar" style="color:#ff5627;"></i> Membaca Hasil Analisis</h2>
                    </div>
                    <p style="color:var(--gray-600);font-size:.875rem;line-height:1.7;">Hasil analisis menampilkan informasi berikut untuk setiap karya atau profil peneliti:</p>
                    <ul style="padding-left:1.5rem;color:var(--gray-600);font-size:.875rem;line-height:2.2;">
                        <li><strong>SDG Badge</strong>: Icon dan nomor SDG yang relevan dengan warna resmi UN (mis. SDG 3 = hijau, SDG 13 = hijau tua).</li>
                        <li><strong>Contributor Type</strong>: Klasifikasi peran peneliti (Active / Relevant / Discutor / Not Relevant).</li>
                        <li><strong>Confidence Score</strong>: Nilai 0–1 yang menunjukkan tingkat keyakinan sistem.</li>
                        <li><strong>SDG Summary</strong>: Untuk analisis ORCID, distribusi kontribusi seluruh karya terhadap 17 SDG.</li>
                    </ul>
                </div>

                <!-- Contributor Types -->
                <div id="contributor-types" class="card" style="margin-bottom:1.5rem;">
                    <div class="card-header">
                        <h2 style="font-size:1.25rem;margin:0;"><i class="fas fa-users" style="color:#ff5627;"></i> Tipe Kontributor</h2>
                    </div>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead><tr><th>Tipe</th><th>Rentang Skor</th><th>Deskripsi</th></tr></thead>
                            <tbody>
                                <tr><td><span class="badge badge-success">Active Contributor</span></td><td>≥ 0.65</td><td>Kontribusi langsung dan substansial terhadap capaian SDG</td></tr>
                                <tr><td><span class="badge badge-info">Relevant Contributor</span></td><td>0.40 – 0.64</td><td>Relevan dengan SDG namun kontribusinya tidak langsung</td></tr>
                                <tr><td><span class="badge badge-warning">Discutor</span></td><td>0.20 – 0.39</td><td>Membahas isu SDG tanpa kontribusi langsung yang terukur</td></tr>
                                <tr><td><span class="badge badge-dark">Not Relevant</span></td><td>&lt; 0.20</td><td>Tidak terkait secara signifikan dengan SDG tersebut</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Confidence Score -->
                <div id="confidence-score" class="card" style="margin-bottom:1.5rem;">
                    <div class="card-header">
                        <h2 style="font-size:1.25rem;margin:0;"><i class="fas fa-percentage" style="color:#ff5627;"></i> Confidence Score</h2>
                    </div>
                    <p style="color:var(--gray-600);font-size:.875rem;margin-bottom:1rem;">Confidence score adalah nilai gabungan dari tiga komponen analisis, dinormalisasi ke skala 0–1:</p>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead><tr><th>Rentang</th><th>Interpretasi</th></tr></thead>
                            <tbody>
                                <tr><td><strong>&gt; 0.70</strong></td><td>High confidence — sangat relevan, dapat diandalkan</td></tr>
                                <tr><td><strong>0.40 – 0.70</strong></td><td>Medium confidence — relevan, butuh verifikasi manual opsional</td></tr>
                                <tr><td><strong>0.20 – 0.40</strong></td><td>Low confidence — mungkin relevan, interpretasi kontekstual diperlukan</td></tr>
                                <tr><td><strong>&lt; 0.20</strong></td><td>Tidak relevan untuk SDG tersebut</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-info" style="margin-top:1rem;"><i class="fas fa-info-circle"></i><span>Warna SDG mengikuti warna resmi United Nations SDG color palette.</span></div>
                </div>

                <!-- Data Sources -->
                <div id="data-sources" class="card" style="margin-bottom:1.5rem;">
                    <div class="card-header">
                        <h2 style="font-size:1.25rem;margin:0;"><i class="fas fa-database" style="color:#ff5627;"></i> Sumber Data</h2>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:.75rem;">
                        <div style="display:flex;gap:1rem;align-items:flex-start;">
                            <span class="badge badge-success" style="white-space:nowrap;">ORCID</span>
                            <p style="font-size:.875rem;color:var(--gray-600);margin:0;line-height:1.6;">Profil peneliti, daftar karya (works), dan metadata publikasi dari API publik ORCID (<a href="https://orcid.org" target="_blank">orcid.org</a>).</p>
                        </div>
                        <div style="display:flex;gap:1rem;align-items:flex-start;">
                            <span class="badge badge-brand" style="white-space:nowrap;">CrossRef</span>
                            <p style="font-size:.875rem;color:var(--gray-600);margin:0;line-height:1.6;">Metadata bibliografi lengkap: judul, penulis, jurnal, tahun, abstrak, dan keyword dari <a href="https://crossref.org" target="_blank">crossref.org</a>.</p>
                        </div>
                        <div style="display:flex;gap:1rem;align-items:flex-start;">
                            <span class="badge badge-info" style="white-space:nowrap;">OpenAlex</span>
                            <p style="font-size:.875rem;color:var(--gray-600);margin:0;line-height:1.6;">Abstrak dan metadata tambahan sebagai fallback pertama dari <a href="https://openalex.org" target="_blank">openalex.org</a>.</p>
                        </div>
                        <div style="display:flex;gap:1rem;align-items:flex-start;">
                            <span class="badge badge-warning" style="white-space:nowrap;">Semantic Scholar</span>
                            <p style="font-size:.875rem;color:var(--gray-600);margin:0;line-height:1.6;">Abstrak fallback kedua dari <a href="https://semanticscholar.org" target="_blank">semanticscholar.org</a> untuk artikel yang tidak tersedia di CrossRef/OpenAlex.</p>
                        </div>
                    </div>
                </div>

                <!-- API Overview -->
                <div id="api-overview" class="card">
                    <div class="card-header">
                        <h2 style="font-size:1.25rem;margin:0;"><i class="fas fa-code" style="color:#ff5627;"></i> API Overview</h2>
                    </div>
                    <p style="color:var(--gray-600);font-size:.875rem;">Platform menyediakan JSON API publik. Semua request menggunakan <strong>HTTP POST</strong> ke base URL:</p>
                    <pre style="background:var(--dark-code,#0d1117);color:#79c0ff;padding:1rem;border-radius:8px;overflow-x:auto;font-size:.85rem;margin:.75rem 0;">https://www.wizdam.sangia.org/public/index.php</pre>
                    <p style="color:var(--gray-600);font-size:.875rem;margin-bottom:1rem;">Parameter utama adalah <code>_sdg</code> yang menentukan aksi: <code>init</code>, <code>batch</code>, <code>summary</code>, atau <code>doi</code>.</p>
                    <a href="?page=api-reference" class="btn btn-outline"><i class="fas fa-external-link-alt"></i> Buka API Reference Lengkap</a>
                </div>

            </main>
        </div>
    </div>
</section>

<style>
.doc-step {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}
.doc-step-num {
    flex-shrink: 0;
    width: 32px; height: 32px;
    background: #ff5627;
    color: white;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700;
    font-size: .9rem;
}
</style>

<script>
function scrollToSection(id) {
    const el = document.getElementById(id);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        document.querySelectorAll('.sidebar-nav-link').forEach(l => l.classList.remove('active'));
        event.target.classList.add('active');
    }
}
</script>

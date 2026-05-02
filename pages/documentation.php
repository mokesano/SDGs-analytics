<?php
$page_title = 'Documentation';
$page_description = 'Complete documentation for Wizdam AI-sikola SDG Classification Analysis Platform.';
?>
<div class="page-header">
    <div class="container">
        <div class="section-label">Developer Docs</div>
        <h1 class="section-title">Documentation</h1>
        <p class="section-subtitle">Everything you need to integrate and use the Wizdam AI SDG Classification Platform.</p>
    </div>
</div>
<section class="section">
    <div class="container">
        <div class="layout-with-sidebar">
            <aside class="sidebar">
                <nav class="sidebar-nav">
                    <div class="sidebar-nav-section">
                        <div class="sidebar-nav-title">Getting Started</div>
                        <a href="#overview" class="sidebar-nav-link active"><i class="fas fa-home"></i> Overview</a>
                        <a href="#quick-start" class="sidebar-nav-link"><i class="fas fa-rocket"></i> Quick Start</a>
                        <a href="#authentication" class="sidebar-nav-link"><i class="fas fa-key"></i> Authentication</a>
                    </div>
                    <div class="sidebar-nav-section" style="border-top:1px solid var(--gray-200);padding-top:.5rem;">
                        <div class="sidebar-nav-title">API Reference</div>
                        <a href="#orcid-api" class="sidebar-nav-link"><i class="fab fa-orcid"></i> ORCID Analysis</a>
                        <a href="#doi-api" class="sidebar-nav-link"><i class="fas fa-file-alt"></i> DOI Analysis</a>
                        <a href="#responses" class="sidebar-nav-link"><i class="fas fa-code"></i> Response Format</a>
                    </div>
                    <div class="sidebar-nav-section" style="border-top:1px solid var(--gray-200);padding-top:.5rem;">
                        <div class="sidebar-nav-title">Guides</div>
                        <a href="#sdg-scoring" class="sidebar-nav-link"><i class="fas fa-chart-bar"></i> SDG Scoring</a>
                        <a href="#contributor-types" class="sidebar-nav-link"><i class="fas fa-users"></i> Contributor Types</a>
                        <a href="#error-handling" class="sidebar-nav-link"><i class="fas fa-exclamation-triangle"></i> Error Handling</a>
                    </div>
                </nav>
            </aside>
            <main>
                <div id="overview" class="card" style="margin-bottom:1.5rem;">
                    <div class="card-header"><h2 style="font-size:1.25rem;margin:0;"><i class="fas fa-info-circle" style="color:var(--brand,#ff5627);"></i> Overview</h2></div>
                    <p>Wizdam AI-sikola adalah platform analisis riset berbasis AI yang mengklasifikasikan karya akademik ke dalam 17 Tujuan Pembangunan Berkelanjutan (SDG) PBB. Dua mode analisis utama:</p>
                    <ul style="list-style:disc;padding-left:1.5rem;color:var(--gray-600);line-height:2.2;">
                        <li><strong>ORCID Analysis</strong> — Analisis portofolio lengkap peneliti berdasarkan ORCID ID dengan sequential batch processing</li>
                        <li><strong>DOI Analysis</strong> — Klasifikasi artikel tunggal berdasarkan Digital Object Identifier dengan multi-source enrichment</li>
                    </ul>
                    <div class="alert alert-info" style="margin-top:1rem;"><i class="fas fa-info-circle"></i><span>Platform beroperasi via POST request ke <code>public/index.php</code> dengan parameter <code>_sdg</code> sebagai action selector.</span></div>
                </div>
                <div id="quick-start" class="card" style="margin-bottom:1.5rem;">
                    <div class="card-header"><h2 style="font-size:1.25rem;margin:0;"><i class="fas fa-rocket" style="color:var(--brand,#ff5627);"></i> Quick Start</h2></div>
                    <p>Analisis peneliti berdasarkan ORCID ID:</p>
                    <pre style="background:var(--dark-code,#0d1117);color:#79c0ff;padding:1.25rem;border-radius:10px;overflow-x:auto;font-size:.85rem;line-height:1.7;margin-top:.75rem;">POST /
Content-Type: application/x-www-form-urlencoded

_sdg=init&orcid=0000-0002-5152-9727</pre>
                    <p style="margin-top:1rem;">Analisis artikel berdasarkan DOI:</p>
                    <pre style="background:var(--dark-code,#0d1117);color:#79c0ff;padding:1.25rem;border-radius:10px;overflow-x:auto;font-size:.85rem;line-height:1.7;margin-top:.75rem;">POST /
Content-Type: application/x-www-form-urlencoded

_sdg=doi&doi=10.1038/nature12373</pre>
                </div>
                <div id="authentication" class="card" style="margin-bottom:1.5rem;">
                    <div class="card-header"><h2 style="font-size:1.25rem;margin:0;"><i class="fas fa-key" style="color:var(--brand,#ff5627);"></i> Authentication</h2></div>
                    <p>Saat ini platform beroperasi tanpa autentikasi untuk penggunaan via browser.</p>
                    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i><span><strong>TODO:</strong> Sistem API key sedang dalam pengembangan. Gunakan endpoint <a href="?page=api-access">API Access</a> untuk mendaftar akses awal.</span></div>
                </div>
                <div id="orcid-api" class="card" style="margin-bottom:1.5rem;">
                    <div class="card-header"><h2 style="font-size:1.25rem;margin:0;"><i class="fab fa-orcid" style="color:#a6ce39;"></i> ORCID Analysis — 3-Step Sequential</h2></div>
                    <p>ORCID analisis dilakukan dalam 3 tahap untuk menghindari server timeout:</p>
                    <h4 style="margin:1.25rem 0 .5rem;">Step 1 — Init</h4>
                    <div class="table-wrapper"><table class="table"><thead><tr><th>Parameter</th><th>Type</th><th>Description</th></tr></thead><tbody>
                        <tr><td><code>_sdg=init</code></td><td>string</td><td>Action type</td></tr>
                        <tr><td><code>orcid</code></td><td>string</td><td>Format: 0000-0000-0000-000X</td></tr>
                        <tr><td><code>refresh</code></td><td>string</td><td>Optional: "true" to bypass cache</td></tr>
                    </tbody></table></div>
                    <h4 style="margin:1.25rem 0 .5rem;">Step 2 — Batch (loop)</h4>
                    <div class="table-wrapper"><table class="table"><thead><tr><th>Parameter</th><th>Type</th><th>Description</th></tr></thead><tbody>
                        <tr><td><code>_sdg=batch</code></td><td>string</td><td>Action type</td></tr>
                        <tr><td><code>orcid</code></td><td>string</td><td>Same ORCID ID</td></tr>
                        <tr><td><code>offset</code></td><td>int</td><td>Starting index for this batch</td></tr>
                        <tr><td><code>limit</code></td><td>int</td><td>Works per batch (default: 3)</td></tr>
                    </tbody></table></div>
                    <h4 style="margin:1.25rem 0 .5rem;">Step 3 — Summary</h4>
                    <div class="table-wrapper"><table class="table"><thead><tr><th>Parameter</th><th>Type</th><th>Description</th></tr></thead><tbody>
                        <tr><td><code>_sdg=summary</code></td><td>string</td><td>Action type</td></tr>
                        <tr><td><code>orcid</code></td><td>string</td><td>Same ORCID ID</td></tr>
                    </tbody></table></div>
                </div>
                <div id="doi-api" class="card" style="margin-bottom:1.5rem;">
                    <div class="card-header"><h2 style="font-size:1.25rem;margin:0;"><i class="fas fa-file-alt" style="color:var(--brand,#ff5627);"></i> DOI Analysis</h2></div>
                    <div class="table-wrapper"><table class="table"><thead><tr><th>Parameter</th><th>Type</th><th>Description</th></tr></thead><tbody>
                        <tr><td><code>_sdg=doi</code></td><td>string</td><td>Action type</td></tr>
                        <tr><td><code>doi</code></td><td>string</td><td>e.g. 10.1038/nature12373</td></tr>
                        <tr><td><code>refresh</code></td><td>string</td><td>Optional: "true" to bypass cache</td></tr>
                    </tbody></table></div>
                </div>
                <div id="sdg-scoring" class="card" style="margin-bottom:1.5rem;">
                    <div class="card-header"><h2 style="font-size:1.25rem;margin:0;"><i class="fas fa-chart-bar" style="color:var(--brand,#ff5627);"></i> SDG Scoring Components</h2></div>
                    <p>Setiap karya dievaluasi dengan 4 komponen skor yang digabungkan:</p>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-top:1rem;">
                        <div class="stat-card" style="text-align:left;"><div style="font-weight:700;margin-bottom:.25rem;">Keyword Score</div><div style="font-size:.85rem;color:var(--gray-500);">Pencocokan kata kunci SDG dalam judul, abstrak, dan keywords.</div></div>
                        <div class="stat-card" style="text-align:left;"><div style="font-weight:700;margin-bottom:.25rem;">Similarity Score</div><div style="font-size:.85rem;color:var(--gray-500);">Cosine similarity antara teks artikel dengan deskripsi SDG target.</div></div>
                        <div class="stat-card" style="text-align:left;"><div style="font-weight:700;margin-bottom:.25rem;">Substantive Score</div><div style="font-size:.85rem;color:var(--gray-500);">Bobot kontribusi berdasarkan kedalaman pembahasan.</div></div>
                        <div class="stat-card" style="text-align:left;"><div style="font-weight:700;margin-bottom:.25rem;">Causal Score</div><div style="font-size:.85rem;color:var(--gray-500);">Deteksi analisis kausalitas dan dampak langsung terhadap SDG.</div></div>
                    </div>
                </div>
                <div id="contributor-types" class="card" style="margin-bottom:1.5rem;">
                    <div class="card-header"><h2 style="font-size:1.25rem;margin:0;"><i class="fas fa-users" style="color:var(--brand,#ff5627);"></i> Contributor Types</h2></div>
                    <div class="table-wrapper"><table class="table"><thead><tr><th>Type</th><th>Score Range</th><th>Description</th></tr></thead><tbody>
                        <tr><td><span class="badge badge-success">Active Contributor</span></td><td>≥ 0.65</td><td>Kontribusi langsung dan substansial terhadap SDG</td></tr>
                        <tr><td><span class="badge badge-info">Relevant Contributor</span></td><td>0.40 – 0.64</td><td>Relevan dengan SDG namun tidak langsung</td></tr>
                        <tr><td><span class="badge badge-warning">Discutor</span></td><td>0.20 – 0.39</td><td>Membahas topik SDG tanpa kontribusi langsung</td></tr>
                        <tr><td><span class="badge badge-dark">Not Relevant</span></td><td>&lt; 0.20</td><td>Tidak terkait dengan SDG tersebut</td></tr>
                    </tbody></table></div>
                </div>
                <div id="error-handling" class="card">
                    <div class="card-header"><h2 style="font-size:1.25rem;margin:0;"><i class="fas fa-exclamation-triangle" style="color:var(--brand,#ff5627);"></i> Error Handling</h2></div>
                    <p>Semua error dikembalikan dalam format JSON:</p>
                    <pre style="background:var(--dark-code,#0d1117);color:#f97583;padding:1.25rem;border-radius:10px;overflow-x:auto;font-size:.85rem;line-height:1.7;margin-top:.75rem;">{ "status": "error", "message": "ORCID not found or API rate limit exceeded." }</pre>
                    <div class="alert alert-info" style="margin-top:1rem;"><i class="fas fa-info-circle"></i><span>HTTP 400 = bad request, 429 = rate limit (implement exponential backoff), 500 = server error.</span></div>
                </div>
            </main>
        </div>
    </div>
</section>

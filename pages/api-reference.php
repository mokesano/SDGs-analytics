<?php
$page_title = 'API Reference';
$page_description = 'Referensi lengkap endpoint API Wizdam AI SDG Classification — parameter, response schema, dan contoh integrasi JavaScript/PHP.';
?>
<div class="page-header">
    <div class="container">
        <div class="section-label">Developer Reference</div>
        <h1 class="section-title">API Reference</h1>
        <p class="section-subtitle">Referensi endpoint, skema request/response, dan contoh kode untuk integrasi dengan platform Wizdam AI.</p>
    </div>
</div>
<section class="section">
    <div class="container">
        <div class="layout-with-sidebar">
            <aside class="sidebar">
                <nav class="sidebar-nav">
                    <div class="sidebar-nav-section">
                        <div class="sidebar-nav-title">Endpoints</div>
                        <a href="#base-url" class="sidebar-nav-link active"><i class="fas fa-globe"></i> Base URL</a>
                        <a href="#init" class="sidebar-nav-link"><i class="fas fa-play"></i> _sdg=init</a>
                        <a href="#batch" class="sidebar-nav-link"><i class="fas fa-layer-group"></i> _sdg=batch</a>
                        <a href="#summary" class="sidebar-nav-link"><i class="fas fa-chart-pie"></i> _sdg=summary</a>
                        <a href="#doi" class="sidebar-nav-link"><i class="fas fa-file-alt"></i> _sdg=doi</a>
                    </div>
                    <div class="sidebar-nav-section" style="border-top:1px solid var(--gray-200);padding-top:.5rem;">
                        <div class="sidebar-nav-title">Contoh Kode</div>
                        <a href="#example-js" class="sidebar-nav-link"><i class="fab fa-js-square"></i> JavaScript</a>
                        <a href="#example-php" class="sidebar-nav-link"><i class="fab fa-php"></i> PHP (cURL)</a>
                    </div>
                    <div class="sidebar-nav-section" style="border-top:1px solid var(--gray-200);padding-top:.5rem;">
                        <div class="sidebar-nav-title">Respons</div>
                        <a href="#response-codes" class="sidebar-nav-link"><i class="fas fa-info-circle"></i> HTTP Codes</a>
                        <a href="#error-format" class="sidebar-nav-link"><i class="fas fa-exclamation-triangle"></i> Error Format</a>
                    </div>
                </nav>
            </aside>
            <main>

                <!-- Base URL -->
                <div id="base-url" class="card" style="margin-bottom:1.5rem;">
                    <div class="card-header"><h2 style="font-size:1.25rem;margin:0;"><i class="fas fa-globe" style="color:#ff5627;"></i> Base URL</h2></div>
                    <p style="color:var(--gray-600);font-size:.875rem;margin-bottom:.75rem;">Semua request dikirim via <strong>HTTP POST</strong> ke endpoint berikut:</p>
                    <pre style="background:var(--dark-code,#0d1117);color:#79c0ff;padding:1.25rem;border-radius:10px;overflow-x:auto;font-size:.9rem;line-height:1.7;">https://www.wizdam.sangia.org/public/index.php</pre>
                    <div class="alert alert-info" style="margin-top:1rem;"><i class="fas fa-info-circle"></i><span>Content-Type: <code>application/x-www-form-urlencoded</code> atau <code>multipart/form-data</code>. Response selalu <code>application/json</code>.</span></div>
                </div>

                <!-- Init -->
                <div id="init" class="card" style="margin-bottom:1.5rem;">
                    <div class="card-header">
                        <div style="display:flex;align-items:center;gap:.75rem;">
                            <span class="badge badge-brand" style="font-family:monospace;font-size:.85rem;">POST</span>
                            <code style="font-size:1rem;">_sdg=init</code>
                        </div>
                    </div>
                    <p style="color:var(--gray-600);font-size:.875rem;margin:.75rem 0 1rem;">Inisialisasi analisis peneliti ORCID. Mengembalikan profil peneliti dan daftar karya (stubs).</p>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead><tr><th>Parameter</th><th>Tipe</th><th>Deskripsi</th></tr></thead>
                            <tbody>
                                <tr><td><code>_sdg</code></td><td>string</td><td>Harus bernilai <code>"init"</code></td></tr>
                                <tr><td><code>orcid</code></td><td>string</td><td>ORCID ID format <code>XXXX-XXXX-XXXX-XXXX</code></td></tr>
                                <tr><td><code>refresh</code></td><td>string</td><td>Opsional: <code>"true"</code> untuk bypass cache</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <h4 style="margin:1rem 0 .5rem;font-size:.9rem;">Contoh Response</h4>
                    <pre style="background:var(--dark-code,#0d1117);color:#e6edf3;padding:1.25rem;border-radius:10px;overflow-x:auto;font-size:.82rem;line-height:1.8;">{
  "status": "success",
  "personal_info": {
    "name": "Dr. Rochmady",
    "orcid": "0000-0002-5152-9727",
    "affiliation": "Universitas Iqra Buru"
  },
  "total_works": 24,
  "works_stubs": [
    { "title": "Article Title", "doi": "10.xxxx/xxxxx", "year": 2023 }
  ]
}</pre>
                </div>

                <!-- Batch -->
                <div id="batch" class="card" style="margin-bottom:1.5rem;">
                    <div class="card-header">
                        <div style="display:flex;align-items:center;gap:.75rem;">
                            <span class="badge badge-brand" style="font-family:monospace;font-size:.85rem;">POST</span>
                            <code style="font-size:1rem;">_sdg=batch</code>
                        </div>
                    </div>
                    <p style="color:var(--gray-600);font-size:.875rem;margin:.75rem 0 1rem;">Proses satu batch karya. Ulangi dengan <code>offset</code> yang meningkat hingga <code>is_done=true</code>.</p>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead><tr><th>Parameter</th><th>Tipe</th><th>Deskripsi</th></tr></thead>
                            <tbody>
                                <tr><td><code>_sdg</code></td><td>string</td><td>Harus bernilai <code>"batch"</code></td></tr>
                                <tr><td><code>orcid</code></td><td>string</td><td>ORCID ID yang sama dengan init</td></tr>
                                <tr><td><code>offset</code></td><td>int</td><td>Index mulai batch (0, 3, 6, ...)</td></tr>
                                <tr><td><code>limit</code></td><td>int</td><td>Karya per batch, default: <code>3</code></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <h4 style="margin:1rem 0 .5rem;font-size:.9rem;">Contoh Response</h4>
                    <pre style="background:var(--dark-code,#0d1117);color:#e6edf3;padding:1.25rem;border-radius:10px;overflow-x:auto;font-size:.82rem;line-height:1.8;">{
  "status": "success",
  "offset": 0,
  "is_done": false,
  "works": [
    {
      "title": "Impact of Education on SDG4",
      "doi": "10.1234/edu.2023.001",
      "abstract": "This study examines...",
      "sdg_analysis": { "SDG4": 0.87, "SDG1": 0.32 },
      "contributor_type": "Active Contributor"
    }
  ]
}</pre>
                </div>

                <!-- Summary -->
                <div id="summary" class="card" style="margin-bottom:1.5rem;">
                    <div class="card-header">
                        <div style="display:flex;align-items:center;gap:.75rem;">
                            <span class="badge badge-brand" style="font-family:monospace;font-size:.85rem;">POST</span>
                            <code style="font-size:1rem;">_sdg=summary</code>
                        </div>
                    </div>
                    <p style="color:var(--gray-600);font-size:.875rem;margin:.75rem 0 1rem;">Hasilkan ringkasan SDG agregat setelah semua batch selesai diproses.</p>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead><tr><th>Parameter</th><th>Tipe</th><th>Deskripsi</th></tr></thead>
                            <tbody>
                                <tr><td><code>_sdg</code></td><td>string</td><td>Harus bernilai <code>"summary"</code></td></tr>
                                <tr><td><code>orcid</code></td><td>string</td><td>ORCID ID yang sama</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <h4 style="margin:1rem 0 .5rem;font-size:.9rem;">Contoh Response</h4>
                    <pre style="background:var(--dark-code,#0d1117);color:#e6edf3;padding:1.25rem;border-radius:10px;overflow-x:auto;font-size:.82rem;line-height:1.8;">{
  "status": "success",
  "personal_info": { "name": "Dr. Rochmady", "orcid": "0000-0002-5152-9727" },
  "total_works_analyzed": 24,
  "researcher_sdg_summary": { "SDG4": 0.76, "SDG3": 0.61, "SDG16": 0.45 },
  "contributor_profile": "Active Contributor"
}</pre>
                </div>

                <!-- DOI -->
                <div id="doi" class="card" style="margin-bottom:1.5rem;">
                    <div class="card-header">
                        <div style="display:flex;align-items:center;gap:.75rem;">
                            <span class="badge badge-brand" style="font-family:monospace;font-size:.85rem;">POST</span>
                            <code style="font-size:1rem;">_sdg=doi</code>
                        </div>
                    </div>
                    <p style="color:var(--gray-600);font-size:.875rem;margin:.75rem 0 1rem;">Klasifikasi satu artikel berdasarkan DOI dengan pengayaan metadata multi-sumber.</p>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead><tr><th>Parameter</th><th>Tipe</th><th>Deskripsi</th></tr></thead>
                            <tbody>
                                <tr><td><code>_sdg</code></td><td>string</td><td>Harus bernilai <code>"doi"</code></td></tr>
                                <tr><td><code>doi</code></td><td>string</td><td>Contoh: <code>10.1234/example</code></td></tr>
                                <tr><td><code>refresh</code></td><td>string</td><td>Opsional: <code>"true"</code> untuk bypass cache</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <h4 style="margin:1rem 0 .5rem;font-size:.9rem;">Contoh Response</h4>
                    <pre style="background:var(--dark-code,#0d1117);color:#e6edf3;padding:1.25rem;border-radius:10px;overflow-x:auto;font-size:.82rem;line-height:1.8;">{
  "status": "success",
  "title": "Sustainable Agriculture and Food Security in Indonesia",
  "doi": "10.1234/example",
  "journal": "Asian Journal of Agriculture",
  "year": 2024,
  "authors": ["Dr. Ahmad Fauzi", "Dr. Siti Rahayu"],
  "abstract": "This study investigates...",
  "sdg_analysis": { "SDG2": 0.91, "SDG15": 0.54 },
  "confidence_score": 0.91,
  "contributor_type": "Active Contributor"
}</pre>
                </div>

                <!-- JavaScript Example -->
                <div id="example-js" class="card" style="margin-bottom:1.5rem;">
                    <div class="card-header"><h2 style="font-size:1.25rem;margin:0;"><i class="fab fa-js-square" style="color:#f7df1e;"></i> Contoh JavaScript (Fetch API)</h2></div>
                    <pre style="background:var(--dark-code,#0d1117);color:#e6edf3;padding:1.25rem;border-radius:10px;overflow-x:auto;font-size:.82rem;line-height:1.9;">// Analisis artikel via DOI
async function analyzeDOI(doi) {
  const form = new FormData();
  form.append('_sdg', 'doi');
  form.append('doi', doi);

  const res = await fetch('https://www.wizdam.sangia.org/public/index.php', {
    method: 'POST',
    body: form
  });

  const data = await res.json();
  console.log('SDG Analysis:', data.sdg_analysis);
  console.log('Contributor Type:', data.contributor_type);
  return data;
}

// Analisis ORCID (sequential batch)
async function analyzeORCID(orcid) {
  const base = 'https://www.wizdam.sangia.org/public/index.php';

  // Step 1: Init
  const initForm = new FormData();
  initForm.append('_sdg', 'init');
  initForm.append('orcid', orcid);
  const initData = await (await fetch(base, { method: 'POST', body: initForm })).json();

  const total = initData.total_works;
  let offset = 0;

  // Step 2: Batch loop
  while (offset &lt; total) {
    const batchForm = new FormData();
    batchForm.append('_sdg', 'batch');
    batchForm.append('orcid', orcid);
    batchForm.append('offset', offset);
    batchForm.append('limit', 3);
    const batchData = await (await fetch(base, { method: 'POST', body: batchForm })).json();
    console.log('Batch at offset', offset, ':', batchData.works.length, 'works');
    if (batchData.is_done) break;
    offset += 3;
  }

  // Step 3: Summary
  const summaryForm = new FormData();
  summaryForm.append('_sdg', 'summary');
  summaryForm.append('orcid', orcid);
  const summary = await (await fetch(base, { method: 'POST', body: summaryForm })).json();
  return summary;
}

analyzeDOI('10.1234/example').then(d => console.log(d));</pre>
                </div>

                <!-- PHP Example -->
                <div id="example-php" class="card" style="margin-bottom:1.5rem;">
                    <div class="card-header"><h2 style="font-size:1.25rem;margin:0;"><i class="fab fa-php" style="color:#8892bf;"></i> Contoh PHP (cURL)</h2></div>
                    <pre style="background:var(--dark-code,#0d1117);color:#e6edf3;padding:1.25rem;border-radius:10px;overflow-x:auto;font-size:.82rem;line-height:1.9;">&lt;?php
// Analisis artikel via DOI
function analyzeDOI(string $doi): array {
    $ch = curl_init('https://www.wizdam.sangia.org/public/index.php');
    curl_setopt_array($ch, [
        CURLOPT_POST           =&gt; true,
        CURLOPT_POSTFIELDS     =&gt; ['_sdg' =&gt; 'doi', 'doi' =&gt; $doi],
        CURLOPT_RETURNTRANSFER =&gt; true,
        CURLOPT_TIMEOUT        =&gt; 30,
    ]);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $result;
}

$data = analyzeDOI('10.1234/example');
echo 'Contributor Type: ' . $data['contributor_type'] . PHP_EOL;
echo 'Top SDG: ' . array_key_first($data['sdg_analysis']) . PHP_EOL;
?&gt;</pre>
                </div>

                <!-- HTTP Status Codes -->
                <div id="response-codes" class="card" style="margin-bottom:1.5rem;">
                    <div class="card-header"><h2 style="font-size:1.25rem;margin:0;"><i class="fas fa-info-circle" style="color:#ff5627;"></i> HTTP Status Codes</h2></div>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead><tr><th>Kode</th><th>Makna</th></tr></thead>
                            <tbody>
                                <tr><td><span class="badge badge-success">200</span></td><td>Success — request berhasil diproses</td></tr>
                                <tr><td><span class="badge badge-warning">400</span></td><td>Bad Request — parameter tidak valid atau hilang</td></tr>
                                <tr><td><span class="badge badge-warning">404</span></td><td>Not Found — ORCID atau DOI tidak ditemukan di sumber manapun</td></tr>
                                <tr><td><span class="badge badge-brand">429</span></td><td>Rate Limit — terlalu banyak request; implementasikan exponential backoff</td></tr>
                                <tr><td><span class="badge badge-dark">500</span></td><td>Server Error — kesalahan internal; coba lagi setelah beberapa saat</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Error Format -->
                <div id="error-format" class="card">
                    <div class="card-header"><h2 style="font-size:1.25rem;margin:0;"><i class="fas fa-exclamation-triangle" style="color:#ff5627;"></i> Format Error</h2></div>
                    <p style="color:var(--gray-600);font-size:.875rem;margin-bottom:.75rem;">Semua error dikembalikan dalam format JSON yang konsisten:</p>
                    <pre style="background:var(--dark-code,#0d1117);color:#f97583;padding:1.25rem;border-radius:10px;overflow-x:auto;font-size:.85rem;line-height:1.8;">{
  "status": "error",
  "message": "ORCID not found or profile is set to private.",
  "code": 404
}</pre>
                    <div class="alert alert-warning" style="margin-top:1rem;"><i class="fas fa-exclamation-triangle"></i><span>Untuk error 429, implementasikan exponential backoff: tunggu 2s, 4s, 8s, 16s sebelum retry.</span></div>
                </div>

            </main>
        </div>
    </div>
</section>

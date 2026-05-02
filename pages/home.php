<?php
/**
 * Home Page — SDG Classification Analysis
 *
 * Semua proses analisis dilakukan via AJAX (tidak via PHP form submission):
 *  - ORCID : AJAX sequential batch (init → batch loop → summary) — anti timeout
 *  - DOI   : AJAX satu call → JSON → JavaScript render hasil
 *
 * AJAX endpoint = public/index.php (file ini di-include dari sana).
 *
 * @version 5.2.0
 * @author Rochmady and Wizdam Team
 */

define('HOME_AJAX_BATCH', 3);
?>

<!-- ============================================================
     PROGRESS SECTION — tersembunyi, muncul saat AJAX berjalan
     ============================================================ -->
<div id="ajaxProgressSection" class="progress-section" style="display:none">
    <div style="display:flex;align-items:center;gap:15px;margin-bottom:15px;">
        <div class="ajax-spinner" id="ajaxSpinner"></div>
        <div>
            <h3 id="ajaxProgressTitle" style="font-size:1.05rem;color:var(--gray-800,#1e293b);margin:0 0 3px;">Preparing analysis…</h3>
            <p  id="ajaxProgressSubtitle" style="font-size:13px;color:var(--gray-500,#64748b);margin:0;">Please wait</p>
        </div>
    </div>
    <div class="ajax-progress-bar-track">
        <div id="ajaxProgressBar" class="ajax-progress-bar-fill" style="width:0;"></div>
    </div>
    <div style="font-size:13px;color:var(--gray-500,#64748b);margin-top:6px;">
        Works: <span id="ajaxProgressCount" style="font-weight:700;color:var(--gray-800,#1e293b);">0</span>
             / <span id="ajaxProgressTotal" style="font-weight:700;color:var(--gray-800,#1e293b);">?</span>
        &nbsp;|&nbsp; Batch: <span id="ajaxProgressBatch" style="font-weight:700;color:var(--brand,#ff5627);">0</span>
    </div>
</div>

<!-- ============================================================
     HERO SECTION
     ============================================================ -->
<section class="hero-section dotted-bg">
    <div class="ambient-blob" style="background:rgba(255,86,39,.06);top:-150px;left:-150px;"></div>
    <div class="ambient-blob" style="background:rgba(255,86,39,.04);bottom:-100px;right:-100px;animation-delay:5s;width:400px;height:400px;"></div>
    <div class="container">
        <div class="hero-grid">

            <!-- Left: Content -->
            <div class="hero-content reveal active">
                <div class="hero-badge">
                    <i class="fas fa-globe" style="color:var(--brand,#ff5627);"></i>
                    AI-Powered SDG Research Platform
                </div>
                <h1 class="hero-title">
                    Classify Research into<br>
                    <span class="hero-title-accent">17 UN Sustainable</span><br>
                    Development Goals
                </h1>
                <p class="hero-subtitle">
                    Enter an ORCID ID to analyze a researcher's full publication portfolio, or a DOI to classify a single article using multi-component NLP analysis.
                </p>

                <!-- Search Form Card -->
                <div class="search-card magic-card" style="margin-top:0;">
                    <form id="analysisForm" method="POST" action="" autocomplete="off">

                        <div style="margin-bottom:14px;">
                            <div class="input-group">
                                <input
                                    type="text"
                                    id="input_value"
                                    name="input_value"
                                    class="form-input floating-input"
                                    placeholder=" "
                                    required
                                    autocomplete="off"
                                    spellcheck="false"
                                    style="padding-right:3rem;"
                                >
                                <label class="floating-label" for="input_value">Enter ORCID ID or DOI</label>
                            </div>
                            <div style="margin-top:8px;font-size:13px;color:var(--gray-400,#94a3b8);">
                                <i class="fas fa-info-circle"></i>
                                Example: <strong>0000-0002-5152-9727</strong> (ORCID) &nbsp;|&nbsp;
                                         <strong>10.1038/nature12373</strong> (DOI)
                            </div>
                            <div class="input-status" id="input_status" style="margin-top:6px;font-size:13px;display:flex;align-items:center;gap:5px;">
                                <span id="status_icon" class="fas fa-question-circle" style="color:var(--gray-300,#cbd5e1);"></span>
                                <span id="status_text" style="color:var(--gray-400,#94a3b8);">Enter your ORCID or DOI to begin</span>
                            </div>
                        </div>

                        <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                <i class="fas fa-search"></i> Analyze
                            </button>
                            <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--gray-500,#64748b);cursor:pointer;user-select:none;">
                                <input type="checkbox" id="force_refresh" name="force_refresh" value="1" style="cursor:pointer;accent-color:var(--brand,#ff5627);">
                                Force refresh (bypass cache)
                            </label>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right: Feature Cards (dark) -->
            <div class="hero-cards reveal" style="transition-delay:200ms;">
                <!-- ORCID Card -->
                <div class="code-card magic-card">
                    <div class="code-card-header">
                        <span class="dot red"></span>
                        <span class="dot yellow"></span>
                        <span class="dot green"></span>
                        <span class="code-filename">orcid_classifier.php</span>
                    </div>
                    <div class="code-card-body">
                        <p>Analisis lengkap portofolio peneliti via ORCID ID. Sequential batch processing anti-timeout dengan SDG mapping otomatis.</p>
                        <pre class="code-snippet">$analyzer->classify([
  'orcid' => '0000-0002-5152-9727',
  'mode'  => 'sequential_batch',
]);</pre>
                        <a href="#" onclick="event.preventDefault();document.getElementById('input_value').focus();" class="btn btn-primary btn-sm">
                            Try ORCID Analysis <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <!-- DOI Card -->
                <div class="code-card magic-card">
                    <div class="code-card-header">
                        <span class="dot red"></span>
                        <span class="dot yellow"></span>
                        <span class="dot green"></span>
                        <span class="code-filename">doi_classifier.php</span>
                    </div>
                    <div class="code-card-body">
                        <p>Klasifikasi artikel tunggal berdasarkan DOI. Multi-source: Crossref + OpenAlex + Semantic Scholar.</p>
                        <pre class="code-snippet">$result = $sdgApi->analyze([
  'doi'  => '10.1038/nature12373',
  'mode' => 'doi_single',
]);</pre>
                        <a href="#" onclick="event.preventDefault();document.getElementById('input_value').focus();" class="btn btn-outline-white btn-sm">
                            Try DOI Analysis <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ============================================================
     DARK FEATURES SECTION
     ============================================================ -->
<section class="section-dark reveal">
    <div class="container">
        <div class="text-center" style="margin-bottom:1rem;">
            <div class="section-label">Platform Features</div>
            <h2 class="section-title-white">Arsitektur Tanpa Kompromi</h2>
            <p class="section-subtitle-muted" style="max-width:560px;margin:0 auto;">
                Dibangun untuk analisis riset akademik skala besar dengan akurasi tinggi dan zero timeout.
            </p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-layer-group"></i></div>
                <h4 style="color:#fff;margin-bottom:.5rem;font-size:1rem;">Sequential Batch</h4>
                <p style="color:rgba(255,255,255,.5);font-size:.875rem;margin:0;line-height:1.6;">
                    Anti-timeout processing: karya peneliti diproses dalam batch kecil berurutan untuk menghindari server timeout.
                </p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-database"></i></div>
                <h4 style="color:#fff;margin-bottom:.5rem;font-size:1rem;">Multi-Source</h4>
                <p style="color:rgba(255,255,255,.5);font-size:.875rem;margin:0;line-height:1.6;">
                    Data dari ORCID + Crossref + OpenAlex + Semantic Scholar digabung untuk metadata paling lengkap.
                </p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                <h4 style="color:#fff;margin-bottom:.5rem;font-size:1rem;">Smart Cache</h4>
                <p style="color:rgba(255,255,255,.5);font-size:.875rem;margin:0;line-height:1.6;">
                    Hasil analisis di-cache gzip selama 7 hari. Pencarian kedua untuk ORCID yang sama: instan.
                </p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-globe-asia"></i></div>
                <h4 style="color:#fff;margin-bottom:.5rem;font-size:1rem;">17 SDG Goals</h4>
                <p style="color:rgba(255,255,255,.5);font-size:.875rem;margin:0;line-height:1.6;">
                    Klasifikasi lengkap ke semua 17 Tujuan Pembangunan Berkelanjutan PBB dengan warna resmi UN.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     STATS SECTION
     ============================================================ -->
<section class="section-muted reveal">
    <div class="container">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;text-align:center;">
            <div class="stat-card">
                <span class="stat-number">17</span>
                <span class="stat-label">SDG Goals Covered</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">3</span>
                <span class="stat-label">Works per Batch</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">4+</span>
                <span class="stat-label">API Sources</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">7d</span>
                <span class="stat-label">Cache TTL</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">∞</span>
                <span class="stat-label">Works Supported</span>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     SDG GOALS PREVIEW
     ============================================================ -->
<section class="section reveal">
    <div class="container">
        <div class="text-center" style="margin-bottom:2rem;">
            <div class="section-label">UN Agenda 2030</div>
            <h2 class="section-title">17 Sustainable Development Goals</h2>
            <p class="section-subtitle" style="margin:0 auto;">
                Platform ini mengklasifikasikan riset akademik ke dalam 17 Tujuan SDG resmi PBB menggunakan warna identitas visual UN.
            </p>
        </div>
        <div class="sdg-preview-grid">
            <?php
            $sdg_tiles = [
                1 => 'No Poverty',         2 => 'Zero Hunger',
                3 => 'Good Health',        4 => 'Quality Education',
                5 => 'Gender Equality',    6 => 'Clean Water',
                7 => 'Affordable Energy',  8 => 'Decent Work',
                9 => 'Industry & Innovation', 10 => 'Reduced Inequalities',
                11 => 'Sustainable Cities', 12 => 'Responsible Consumption',
                13 => 'Climate Action',    14 => 'Life Below Water',
                15 => 'Life on Land',      16 => 'Peace & Justice',
                17 => 'Partnerships',
            ];
            foreach ($sdg_tiles as $num => $name):
            ?>
            <div class="sdg-tile sdg-tile-<?= $num ?>" title="SDG <?= $num ?>: <?= htmlspecialchars($name) ?>">
                <span class="sdg-num"><?= $num ?></span>
                <span style="font-size:.65rem;line-height:1.2;display:block;"><?= htmlspecialchars($name) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================================================
     HOW IT WORKS
     ============================================================ -->
<section class="section-muted reveal">
    <div class="container">
        <div class="text-center" style="margin-bottom:2.5rem;">
            <div class="section-label">How It Works</div>
            <h2 class="section-title">3 Langkah Mudah</h2>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1.5rem;">
            <div style="text-align:center;padding:2rem 1.5rem;">
                <div style="width:64px;height:64px;border-radius:50%;background:rgba(255,86,39,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;font-size:1.5rem;color:var(--brand,#ff5627);">
                    <i class="fas fa-search"></i>
                </div>
                <h4 style="margin-bottom:.5rem;color:var(--gray-800,#1e293b);">1. Input</h4>
                <p style="color:var(--gray-500,#64748b);font-size:.875rem;line-height:1.6;margin:0;">
                    Masukkan ORCID ID peneliti atau DOI artikel pada kotak pencarian.
                </p>
            </div>
            <div style="text-align:center;padding:2rem 1.5rem;">
                <div style="width:64px;height:64px;border-radius:50%;background:rgba(255,86,39,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;font-size:1.5rem;color:var(--brand,#ff5627);">
                    <i class="fas fa-cogs"></i>
                </div>
                <h4 style="margin-bottom:.5rem;color:var(--gray-800,#1e293b);">2. Analisis</h4>
                <p style="color:var(--gray-500,#64748b);font-size:.875rem;line-height:1.6;margin:0;">
                    AI mengambil metadata dari berbagai sumber dan menghitung skor SDG multi-komponen.
                </p>
            </div>
            <div style="text-align:center;padding:2rem 1.5rem;">
                <div style="width:64px;height:64px;border-radius:50%;background:rgba(255,86,39,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;font-size:1.5rem;color:var(--brand,#ff5627);">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <h4 style="margin-bottom:.5rem;color:var(--gray-800,#1e293b);">3. Hasil</h4>
                <p style="color:var(--gray-500,#64748b);font-size:.875rem;line-height:1.6;margin:0;">
                    Tampilkan profil SDG lengkap: distribusi kontribusi, confidence score, dan contributor type.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     RESULTS SECTION — diisi oleh JavaScript saat analisis selesai
     ============================================================ -->
<div class="container" id="ajaxResultsSection" style="display:none; padding-top:1.5rem; padding-bottom:2rem;"></div>

<!-- Loading Overlay -->
<div id="loadingOverlay" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,.92);z-index:9000;flex-direction:column;align-items:center;justify-content:center;">
    <div class="spinner"></div>
    <div style="font-size:1.1rem;font-weight:700;color:var(--gray-800,#1e293b);">Analyzing…</div>
    <div style="font-size:.9rem;color:var(--gray-500,#64748b);margin-top:6px;">Please wait</div>
</div>

<style>
/* ── Home page specific styles ─── */
.floating-input { width:100%; padding:14px 16px; font-size:1rem; border:2px solid var(--gray-200,#e2e8f0); border-radius:10px; outline:none; transition:border-color .2s; background:#fff; }
.floating-input:focus { border-color:var(--brand,#ff5627); box-shadow:0 0 0 3px rgba(255,86,39,.12); }
.floating-input.valid   { border-color:var(--success,#22c55e); }
.floating-input.invalid { border-color:var(--danger,#ef4444); }
.floating-input::placeholder { color:transparent; }
.floating-label { position:absolute;left:16px;top:50%;transform:translateY(-50%);font-size:0.95rem;color:var(--gray-400,#94a3b8);pointer-events:none;transition:all .2s;background:#fff;padding:0 4px;border-radius:4px; }
.floating-input:not(:placeholder-shown) ~ .floating-label,
.floating-input:focus ~ .floating-label { top:-2px;transform:translateY(-50%) scale(.82);color:var(--brand,#ff5627);font-weight:600; }
.floating-input.valid   ~ .floating-label { color:var(--success,#22c55e); }
.floating-input.invalid ~ .floating-label { color:var(--danger,#ef4444); }
</style>

<script>
(function() {
    // ── Konstanta ──────────────────────────────────────────────────
    const SDG_DEFS    = <?php echo json_encode($SDG_DEFINITIONS, JSON_UNESCAPED_UNICODE); ?>;
    const AJAX_BATCH  = <?php echo HOME_AJAX_BATCH; ?>;
    // Endpoint = file ini sendiri (index.php yang di-serve oleh router)
    const AJAX_ENDPOINT = '<?php echo htmlspecialchars(strtok($_SERVER['REQUEST_URI'] ?? '/', '?'), ENT_QUOTES); ?>';

    // ── State ──────────────────────────────────────────────────────
    let isSubmitting   = false;
    let orcidAbortCtrl = null;
    let ajaxWorkIndex  = 0;
    let ajaxSdgChart   = null;
    let ajaxContribChart = null;

    // ── Helpers ────────────────────────────────────────────────────
    function escH(s) {
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

    // ── Error display (in-page, bukan alert) ──────────────────────
    function showError(message) {
        const prog = document.getElementById('ajaxProgressSection');
        if (!prog) { console.error(message); return; }
        prog.style.display = 'block';
        prog.innerHTML = `
            <div style="display:flex;align-items:flex-start;gap:16px;padding:4px 0;">
                <div style="width:44px;height:44px;background:#fff0f0;border-radius:50%;display:flex;align-items:center;
                            justify-content:center;flex-shrink:0;border:2px solid #fca5a5;">
                    <i class="fas fa-exclamation-triangle" style="color:#dc2626;font-size:18px;"></i>
                </div>
                <div style="flex:1;">
                    <h3 style="color:#dc2626;margin:0 0 6px;font-size:1rem;">Analysis Failed</h3>
                    <p style="color:#555;margin:0;font-size:.9rem;line-height:1.5;">${escH(message)}</p>
                    <button onclick="document.getElementById('ajaxProgressSection').style.display='none';"
                        style="margin-top:12px;padding:6px 16px;background:#dc2626;color:#fff;border:none;
                               border-radius:8px;cursor:pointer;font-size:.85rem;font-weight:600;">
                        Close
                    </button>
                </div>
            </div>`;
    }

    // ── Progress ───────────────────────────────────────────────────
    function showProgress(title, subtitle, showSpinner) {
        const prog = document.getElementById('ajaxProgressSection');
        if (!prog) return;
        prog.style.display = 'block';
        const spinner = document.getElementById('ajaxSpinner');
        if (spinner) spinner.style.display = showSpinner === false ? 'none' : 'block';
        const el_t = document.getElementById('ajaxProgressTitle');
        const el_s = document.getElementById('ajaxProgressSubtitle');
        if (el_t) el_t.textContent = title   || '…';
        if (el_s) el_s.textContent = subtitle || '';
    }
    function hideProgress() {
        setTimeout(() => {
            const prog = document.getElementById('ajaxProgressSection');
            if (prog) prog.style.display = 'none';
        }, 3000);
    }
    function setBar(done, total) {
        const pct = total > 0 ? Math.round((done / total) * 100) : 0;
        const bar = document.getElementById('ajaxProgressBar');
        if (bar) bar.style.width = pct + '%';
        const ec = document.getElementById('ajaxProgressCount');
        const et = document.getElementById('ajaxProgressTotal');
        if (ec) ec.textContent = done;
        if (et) et.textContent = total;
    }

    // ── AJAX call ─────────────────────────────────────────────────
    async function ajaxCall(action, params) {
        const form = new URLSearchParams();
        form.append('_sdg', action);
        if (params) {
            Object.entries(params).forEach(([k, v]) => {
                if (v !== undefined && v !== null && v !== false && v !== '')
                    form.append(k, String(v));
            });
        }
        const resp = await fetch(AJAX_ENDPOINT, {
            method : 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body   : form.toString(),
            signal : orcidAbortCtrl ? orcidAbortCtrl.signal : undefined,
        });
        let data;
        try { data = await resp.json(); }
        catch (_) { throw new Error('Server returned non-JSON (HTTP ' + resp.status + '). Check server error logs.'); }
        if (!resp.ok || data.status === 'error') throw new Error(data.message || 'HTTP ' + resp.status);
        return data;
    }

    // ── Validation ────────────────────────────────────────────────
    function detectType(value) {
        const v = value.trim();
        if (/orcid\.org\/(\d{4}-\d{4}-\d{4}-\d{3}[\dX])/i.test(v)) return 'orcid';
        if (/^(\d{4}-\d{4}-\d{4}-\d{3}[\dX])$/.test(v))              return 'orcid';
        if (/^10\.\d{4,}\//.test(v))                                   return 'doi';
        if (/doi\.org\//.test(v) || /dx\.doi\.org\//.test(v))         return 'doi';
        return null;
    }
    function validateOrcid(orcid) {
        const clean = orcid.replace(/^https?:\/\/(www\.)?orcid\.org\//i, '').trim();
        if (!/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/.test(clean)) return false;
        const digits = clean.replace(/-/g, '').slice(0, -1);
        const check  = clean.slice(-1);
        let total = 0;
        for (let i = 0; i < digits.length; i++) total = (total + parseInt(digits[i])) * 2;
        const rem = total % 11;
        const exp = (12 - rem) % 11;
        return check === (exp === 10 ? 'X' : String(exp));
    }
    function validateDoi(doi) {
        const clean = doi.replace(/^https?:\/\/(dx\.)?doi\.org\//i, '').trim();
        return /^10\.\d{4,}\/[^\s]+$/.test(clean);
    }

    // ── Input status ──────────────────────────────────────────────
    function updateInputStatus(value) {
        const icon = document.getElementById('status_icon');
        const text = document.getElementById('status_text');
        const inp  = document.getElementById('input_value');
        if (!icon || !text || !inp) return;
        const v = value.trim();
        if (!v) {
            icon.className = 'fas fa-question-circle'; icon.style.color = 'var(--gray-300,#cbd5e1)';
            text.textContent = 'Enter ORCID or DOI to begin';
            text.style.color = 'var(--gray-400,#94a3b8)';
            inp.classList.remove('valid','invalid'); return;
        }
        const type = detectType(v);
        if (type === 'orcid') {
            const ok = validateOrcid(v);
            icon.className = ok ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
            icon.style.color = ok ? 'var(--success,#22c55e)' : 'var(--danger,#ef4444)';
            text.textContent = ok ? 'Valid ORCID ID — ready to analyze researcher profile'
                                  : 'Invalid ORCID — check the 16-digit format and checksum';
            text.style.color = ok ? 'var(--success,#22c55e)' : 'var(--danger,#ef4444)';
            inp.classList.toggle('valid', ok); inp.classList.toggle('invalid', !ok);
        } else if (type === 'doi') {
            const ok = validateDoi(v);
            icon.className = ok ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
            icon.style.color = ok ? 'var(--success,#22c55e)' : 'var(--danger,#ef4444)';
            text.textContent = ok ? 'Valid DOI — ready to analyze article'
                                  : 'Invalid DOI — format should be 10.xxxx/xxxxx';
            text.style.color = ok ? 'var(--success,#22c55e)' : 'var(--danger,#ef4444)';
            inp.classList.toggle('valid', ok); inp.classList.toggle('invalid', !ok);
        } else {
            icon.className = 'fas fa-exclamation-circle'; icon.style.color = 'var(--warning,#f59e0b)';
            text.textContent = 'Format not recognized. Use ORCID (0000-0000-0000-0000) or DOI (10.xxxx/xxxxx).';
            text.style.color = 'var(--warning,#f59e0b)';
            inp.classList.remove('valid'); inp.classList.add('invalid');
        }
    }

    // ── Reset ─────────────────────────────────────────────────────
    function resetButton() {
        const btn = document.getElementById('submitBtn');
        if (btn) { btn.innerHTML = '<i class="fas fa-search"></i> Analyze'; btn.disabled = false; }
        const ov = document.getElementById('loadingOverlay');
        if (ov) ov.style.display = 'none';
        isSubmitting = false;
    }

    // ── ORCID rendering helpers ───────────────────────────────────
    function renderPersonal(info, total) {
        if (!info) return;
        const initials = (info.name || 'NN').split(' ').slice(0,2).map(w => w[0]).join('').toUpperCase();
        const instHtml = info.institutions && info.institutions.length
            ? `<p style="margin:4px 0;font-size:.9rem;color:var(--gray-500,#64748b);"><i class="fas fa-university"></i> ${escH(info.institutions.slice(0,2).join(', '))}${info.institutions.length > 2 ? ' et al.' : ''}</p>` : '';
        document.getElementById('ajaxResultsSection').innerHTML = `
        <div class="info-general">
            <div class="personal-info">
                <div class="avatar">${escH(initials)}</div>
                <div>
                    <h2 style="margin:0 0 4px;font-size:1.2rem;">${escH(info.name || '–')}</h2>
                    <p style="margin:4px 0;font-size:.9rem;color:var(--gray-500,#64748b);"><i class="fab fa-orcid" style="color:#a6ce39;"></i> ${escH(info.orcid || '')}</p>
                    ${instHtml}
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-card"><span class="stat-number">${total}</span><span class="stat-label">Total Works</span></div>
                <div class="stat-card"><span class="stat-number" id="ajaxStatSdgs">–</span><span class="stat-label">Identified SDGs</span></div>
                <div class="stat-card"><span class="stat-number" id="ajaxStatActive">–</span><span class="stat-label">Active Contributor</span></div>
                <div class="stat-card"><span class="stat-number" id="ajaxStatConf">–</span><span class="stat-label">Avg Confidence</span></div>
            </div>
        </div>
        <div id="ajaxSdgSummary"></div>
        <div id="ajaxCharts"></div>
        <div class="works-container">
            <h3 class="u-heading3"><i class="fas fa-file-alt"></i> Publications
                (<span id="ajaxWorksCount">0</span> / ${total} analyzed)</h3>
            <div id="ajaxWorksList"></div>
        </div>`;
        document.getElementById('ajaxResultsSection').style.display = 'block';
    }

    function appendWorks(works, offset) {
        const list = document.getElementById('ajaxWorksList');
        if (!list) return;
        works.forEach((work, i) => {
            const idx = 'w' + (offset + i);
            const sdgTags = (work.sdgs || []).map(sdg => {
                const def  = SDG_DEFS[sdg] || { color:'#666', title:sdg, svg_url:'' };
                const conf = work.sdg_confidence && work.sdg_confidence[sdg]
                    ? (work.sdg_confidence[sdg] * 100).toFixed(1) : '–';
                return `<div class="work-sdg-tag" style="background:${def.color}">
                    <div class="sdg-mini-icon"><img src="${escH(def.svg_url)}" alt="${escH(def.title)}" width="20" height="20"></div>
                    <span>${escH(sdg)} <span class="sdg-confidence-info">(${conf}%)</span></span>
                </div>`;
            }).join('');

            const sdgSection = work.sdgs && work.sdgs.length
                ? `<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                       <div style="display:flex;flex-wrap:wrap;">${sdgTags}</div>
                       <button class="show-more-btn" onclick="toggleDetails('${idx}')">
                           <i class="fas fa-chart-bar"></i> Show Details
                       </button>
                   </div>${buildDetailedAnalysis(work, idx)}`
                : `<div class="none-SDG"><i class="fas fa-info-circle"></i> No SDGs identified with sufficient confidence.</div>`;

            const abstract = work.abstract
                ? `<div class="work-abstract"><strong>Abstract:</strong> ${escH(work.abstract.slice(0,400))}${work.abstract.length > 400 ? '…' : ''}</div>` : '';
            const doi = work.doi
                ? `<span><i class="fas fa-link"></i> <a href="https://doi.org/${escH(work.doi)}" target="_blank" rel="noopener">${escH(work.doi)}</a></span>` : '';

            list.insertAdjacentHTML('beforeend', `
            <div class="work-item">
                <div class="work-title">${escH(work.title || '(No title)')}</div>
                <div class="work-meta">${doi}<span><i class="fas fa-chart-line"></i> ${(work.sdgs||[]).length} SDGs identified</span></div>
                ${abstract}
                <div style="margin-top:10px;">${sdgSection}</div>
            </div>`);
            ajaxWorkIndex++;
        });
        const el = document.getElementById('ajaxWorksCount');
        if (el) el.textContent = ajaxWorkIndex;
    }

    function buildDetailedAnalysis(work, idx) {
        if (!work.detailed_analysis || !Object.keys(work.detailed_analysis).length)
            return `<div class="detailed-analysis" id="analysis-${idx}"><p style="color:#666;padding:16px;text-align:center;">No detailed analysis available.</p></div>`;
        let html = `<div class="detailed-analysis" id="analysis-${idx}">`;
        Object.entries(work.detailed_analysis).forEach(([sdg, analysis]) => {
            const def  = SDG_DEFS[sdg] || { title: sdg };
            const comp = analysis.components || {};
            const compsHtml = ['keyword_score','similarity_score','substantive_score','causal_score'].map(k =>
                `<div class="component-score">
                    <div class="component-label">${k.replace('_score','').replace('_',' ')}</div>
                    <div class="component-value">${(comp[k]||0).toFixed(3)}</div>
                </div>`).join('');
            const kwEvidence = (analysis.evidence && analysis.evidence.keyword_matches || []).slice(0,2).map(m =>
                `<div style="font-size:12px;margin-top:4px;color:#555;"><strong>${escH(m.keyword||'')}</strong>: ${escH(m.context||'')}</div>`).join('');
            html += `<div class="analysis-section">
                <h5 style="margin:0 0 8px;color:var(--gray-800,#1e293b);">${escH(sdg + ': ' + (def.title||sdg))}
                    <span style="color:var(--brand,#ff5627);font-weight:400;">(Score: ${(analysis.score||0).toFixed(3)})</span></h5>
                <div class="analysis-components">${compsHtml}</div>
                <div style="margin-top:8px;">
                    <span class="info-badge">${escH(analysis.contributor_type&&analysis.contributor_type.type||'–')}</span>
                    <span class="info-badge confidence">${escH(analysis.confidence_level||'–')}</span>
                </div>
                ${kwEvidence}
            </div>`;
        });
        return html + '</div>';
    }

    window.toggleDetails = function(idx) {
        const div = document.getElementById('analysis-' + idx);
        const btn = document.querySelector(`[onclick="toggleDetails('${idx}')"]`);
        if (!div) return;
        if (div.classList.contains('show')) {
            div.classList.remove('show');
            if (btn) btn.innerHTML = '<i class="fas fa-chart-bar"></i> Show Details';
        } else {
            div.classList.add('show');
            if (btn) btn.innerHTML = '<i class="fas fa-eye-slash"></i> Hide Details';
        }
    };

    function renderSummary(summaryData) {
        const summary = summaryData.researcher_sdg_summary || {};
        const profile = summaryData.contributor_profile     || {};
        const sdgCount   = Object.keys(summary).length;
        const activeCount = Object.values(profile).filter(p => p.dominant_type === 'Active Contributor').length;
        let totalConf = 0, confCount = 0;
        Object.values(summary).forEach(s => { totalConf += s.average_confidence; confCount++; });
        const avgConf = confCount > 0 ? Math.round((totalConf / confCount) * 100) : 0;

        const e = id => document.getElementById(id);
        if (e('ajaxStatSdgs'))   e('ajaxStatSdgs').textContent   = sdgCount;
        if (e('ajaxStatActive')) e('ajaxStatActive').textContent = activeCount;
        if (e('ajaxStatConf'))   e('ajaxStatConf').textContent   = avgConf + '%';

        if (!sdgCount) return;

        // SDG Grid
        let html = '<div class="info-general"><h3 class="u-heading3"><i class="fas fa-chart-pie"></i> SDG Contribution Summary</h3><div class="sdg-grid">';
        Object.entries(summary).forEach(([sdg, sum], i) => {
            const def = SDG_DEFS[sdg] || { title:sdg, color:'var(--brand,#ff5627)', svg_url:'' };
            const prf = profile[sdg] || {};
            const pct = (sum.average_confidence * 100).toFixed(1);
            html += `<div class="sdg-card" style="--sdg-color:${def.color};">
                <div style="margin-bottom:10px;"><img src="${escH(def.svg_url)}" alt="${escH(def.title||sdg)}" style="width:48px;height:48px;border-radius:8px;"></div>
                <div>
                    <div class="sdg-title">${escH(def.title||sdg)}</div>
                    <div class="sdg-stats">
                        <div><div class="sdg-stat-label">Works</div><div class="sdg-stat-value">${sum.work_count}</div></div>
                        <div><div class="sdg-stat-label">Confidence</div><div class="sdg-stat-value">${pct}%</div></div>
                    </div>
                    <div class="confidence-bar"><div class="confidence-fill" style="width:${pct}%;background:${def.color};"></div></div>
                    ${prf.dominant_type ? `<div class="contributor-type">${escH(prf.dominant_type)}</div>` : ''}
                </div>
            </div>`;
        });
        html += '</div></div>';

        const summaryEl = document.getElementById('ajaxSdgSummary');
        if (summaryEl) summaryEl.innerHTML = html;

        // Charts
        const chartsEl = document.getElementById('ajaxCharts');
        if (chartsEl && typeof Chart !== 'undefined') {
            chartsEl.innerHTML = `<div class="info-general"><div class="charts-section">
                <div class="chart-container"><h4 style="margin-bottom:12px;"><i class="fas fa-chart-pie"></i> SDG Distribution</h4><canvas id="ajaxSdgChart"></canvas></div>
                <div class="chart-container"><h4 style="margin-bottom:12px;"><i class="fas fa-chart-bar"></i> Contributor Type</h4><canvas id="ajaxContribChart"></canvas></div>
            </div></div>`;

            if (ajaxSdgChart) ajaxSdgChart.destroy();
            ajaxSdgChart = new Chart(document.getElementById('ajaxSdgChart'), {
                type: 'doughnut',
                data: {
                    labels: Object.keys(summary).map(s => (SDG_DEFS[s]||{}).title || s),
                    datasets: [{ data: Object.values(summary).map(s => s.work_count),
                        backgroundColor: Object.keys(summary).map(s => (SDG_DEFS[s]||{}).color || 'var(--brand,#ff5627)'),
                        borderWidth: 2, borderColor: '#fff' }]
                },
                options: { responsive:true, maintainAspectRatio:false,
                    plugins:{ legend:{ position:'bottom', labels:{ padding:10, usePointStyle:true, font:{ size:12 } } } } }
            });

            if (ajaxContribChart) ajaxContribChart.destroy();
            const ctypes = {};
            Object.values(profile).forEach(p => { ctypes[p.dominant_type] = (ctypes[p.dominant_type] || 0) + 1; });
            ajaxContribChart = new Chart(document.getElementById('ajaxContribChart'), {
                type: 'bar',
                data: {
                    labels: Object.keys(ctypes),
                    datasets: [{ label:'SDGs', data: Object.values(ctypes),
                        backgroundColor: ['#ff5627','#e0481d','#fd9d24','#4c9f38','#0a97d9'].slice(0, Object.keys(ctypes).length),
                        borderWidth: 0, borderRadius: 8 }]
                },
                options: { responsive:true, maintainAspectRatio:false,
                    plugins:{ legend:{ display:false } },
                    scales:{ y:{ ticks:{ stepSize:1, font:{ size:10 } } }, x:{ ticks:{ maxRotation:45, font:{ size:12 } } } } }
            });
        }
    }

    // ── DOI result rendering ──────────────────────────────────────
    function renderDoiResult(data) {
        const el = document.getElementById('ajaxResultsSection');
        if (!el) return;
        if (!data || data.status !== 'success') {
            showError(data && data.message ? data.message : 'Unknown error from API.');
            return;
        }
        const title   = escH(data.title   || '(No title)');
        const doi     = escH(data.doi     || '');
        const journal = escH(data.journal || '');
        const year    = escH(String(data.year || ''));
        const authors = Array.isArray(data.authors) ? data.authors.map(a => escH(a)).join(', ') : '';
        const abst    = data.abstract
            ? escH(data.abstract.slice(0,500)) + (data.abstract.length > 500 ? '…' : '') : '';
        const scores  = data.sdg_scores || {};
        const sorted  = Object.entries(scores).sort((a,b) => b[1] - a[1]).filter(([,v]) => v >= 0.20);
        const tagHtml = sorted.map(([sdg, score]) => {
            const def = SDG_DEFS[sdg] || { color:'var(--brand,#ff5627)', title:sdg, svg_url:'' };
            return `<div class="work-sdg-tag" style="background:${def.color}">
                <div class="sdg-mini-icon"><img src="${escH(def.svg_url)}" alt="${escH(def.title)}" width="20" height="20"></div>
                <span>${escH(sdg)}: ${escH(def.title)} <span class="sdg-confidence-info">(${(score*100).toFixed(1)}%)</span></span>
            </div>`;
        }).join('');

        el.innerHTML = `
        <div class="info-general">
            <div class="personal-info" style="align-items:flex-start;">
                <div class="avatar" style="font-size:1.5rem;"><i class="fas fa-file-alt"></i></div>
                <div>
                    <h2 style="font-size:1.05rem;line-height:1.4;margin:0 0 8px;">${title}</h2>
                    ${authors ? `<p style="color:var(--gray-500,#64748b);font-size:.9rem;margin:0 0 4px;"><i class="fas fa-users"></i> ${authors}</p>` : ''}
                    ${journal  ? `<p style="color:var(--gray-500,#64748b);font-size:.9rem;margin:0 0 4px;"><i class="fas fa-book"></i> ${journal}${year ? ' (' + year + ')' : ''}</p>` : ''}
                    ${doi      ? `<p style="font-size:.9rem;margin:0;"><i class="fas fa-link" style="color:var(--brand,#ff5627);"></i> <a href="https://doi.org/${doi}" target="_blank" rel="noopener" style="color:var(--brand,#ff5627);">https://doi.org/${doi}</a></p>` : ''}
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-card"><span class="stat-number">${sorted.length}</span><span class="stat-label">Identified SDGs</span></div>
                <div class="stat-card"><span class="stat-number">${sorted.length > 0 ? (sorted[0][1]*100).toFixed(0)+'%' : '–'}</span><span class="stat-label">Top Confidence</span></div>
            </div>
        </div>
        ${abst ? `<div class="info-general"><h4 style="margin-bottom:10px;"><i class="fas fa-align-left"></i> Abstract</h4><p style="color:var(--gray-600,#475569);line-height:1.7;margin:0;">${abst}</p></div>` : ''}
        <div class="info-general">
            <h4 style="margin-bottom:12px;"><i class="fas fa-tags"></i> SDG Classification</h4>
            ${sorted.length
                ? `<div style="display:flex;flex-wrap:wrap;">${tagHtml}</div>`
                : `<div class="none-SDG"><i class="fas fa-info-circle"></i> No SDGs identified with sufficient confidence for this article.</div>`}
        </div>`;
        el.style.display = 'block';
    }

    // ── ORCID AJAX sequential ─────────────────────────────────────
    async function startOrcidAjax(orcid, forceRefresh) {
        orcidAbortCtrl = new AbortController();
        ajaxWorkIndex  = 0;
        const rfParam  = forceRefresh ? { refresh:'true' } : {};

        document.getElementById('ajaxResultsSection').innerHTML = '';
        document.getElementById('ajaxResultsSection').style.display = 'none';

        try {
            // Step 1: Init
            showProgress('Fetching researcher profile…', 'Connecting to ORCID API…');
            const initData = await ajaxCall('init', Object.assign({ orcid }, rfParam));
            const totalWorks = typeof initData.total_works === 'number'
                ? initData.total_works
                : (Array.isArray(initData.works) ? initData.works.length : 0);

            renderPersonal(initData.personal_info, totalWorks);
            setBar(0, totalWorks);
            document.getElementById('ajaxProgressBatch').textContent = '0';

            if (totalWorks === 0) { hideProgress(); return; }

            // Legacy format: works sudah ada di init response
            if (Array.isArray(initData.works) && initData.works.length > 0 && typeof initData.total_works !== 'number') {
                appendWorks(initData.works, 0);
                setBar(totalWorks, totalWorks);
                if (initData.researcher_sdg_summary && Object.keys(initData.researcher_sdg_summary).length) {
                    renderSummary(initData);
                } else {
                    showProgress('Calculating SDG summary…', '');
                    const summaryData = await ajaxCall('summary', { orcid });
                    renderSummary(summaryData);
                }
                showProgress('Analysis complete ✓', totalWorks + ' works processed');
                hideProgress();
                return;
            }

            // Step 2: Batch loop
            let offset = 0, batchNum = 0, done = 0;
            while (offset < totalWorks) {
                batchNum++;
                const to = Math.min(offset + AJAX_BATCH, totalWorks);
                showProgress(
                    `Analyzing works ${offset+1}–${to} of ${totalWorks}…`,
                    `Batch #${batchNum} | Processing ${to - offset} works`
                );
                document.getElementById('ajaxProgressBatch').textContent = batchNum;

                const batchData = await ajaxCall('batch', Object.assign({ orcid, offset, limit: AJAX_BATCH }, rfParam));
                done += batchData.processed;
                setBar(done, totalWorks);
                appendWorks(batchData.works, offset);

                if (batchData.is_done || batchData.processed === 0) break;
                offset = batchData.next_offset;
                await sleep(350);
            }

            // Step 3: Summary
            showProgress('Calculating SDG summary…', 'Aggregating all batch results…');
            const summaryData = await ajaxCall('summary', { orcid });
            renderSummary(summaryData);
            setBar(totalWorks, totalWorks);
            showProgress('Analysis complete ✓', `${totalWorks} works analyzed in ${batchNum} batches`);
            hideProgress();

            setTimeout(() => {
                document.getElementById('ajaxResultsSection')
                    .scrollIntoView({ behavior:'smooth', block:'start' });
            }, 400);

        } catch (err) {
            if (err.name !== 'AbortError') {
                document.getElementById('ajaxProgressSection').style.display = 'none';
                showError(err.message);
            }
        } finally {
            resetButton();
        }
    }

    // ── DOI AJAX ─────────────────────────────────────────────────
    async function startDoiAjax(doi, forceRefresh) {
        orcidAbortCtrl = new AbortController();
        document.getElementById('ajaxResultsSection').innerHTML = '';
        document.getElementById('ajaxResultsSection').style.display = 'none';
        try {
            showProgress('Fetching article data…', 'Retrieving metadata from Crossref for DOI: ' + escH(doi));
            const rfParam = forceRefresh ? { refresh:'true' } : {};
            const data = await ajaxCall('doi', Object.assign({ doi }, rfParam));
            renderDoiResult(data);
            hideProgress();
            setTimeout(() => {
                document.getElementById('ajaxResultsSection')
                    .scrollIntoView({ behavior:'smooth', block:'start' });
            }, 400);
        } catch (err) {
            if (err.name !== 'AbortError') {
                document.getElementById('ajaxProgressSection').style.display = 'none';
                showError(err.message);
            }
        } finally {
            resetButton();
        }
    }

    // ── Form submit handler ───────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function() {
        const inp  = document.getElementById('input_value');
        const form = document.getElementById('analysisForm');
        const btn  = document.getElementById('submitBtn');

        if (inp) {
            inp.addEventListener('input', () => updateInputStatus(inp.value));
            inp.addEventListener('paste', () => setTimeout(() => updateInputStatus(inp.value), 50));
            updateInputStatus(inp.value);
        }

        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (isSubmitting) return;

                const value = inp ? inp.value.trim() : '';
                if (!value) {
                    showError('Please enter a valid ORCID ID (0000-0000-0000-0000) or DOI (10.xxxx/xxxxx).');
                    return;
                }

                const type = detectType(value);
                if (!type) {
                    showError('Format not recognized. Enter a valid ORCID ID (0000-0000-0000-0000) or DOI (10.xxxx/xxxxx).');
                    return;
                }

                const forceRefresh = document.getElementById('force_refresh').checked;

                if (type === 'orcid') {
                    if (!validateOrcid(value)) {
                        showError('Invalid ORCID ID. The correct format is: 0000-0000-0000-0000 with a valid checksum digit. Verify your ORCID at orcid.org.');
                        return;
                    }
                    let cleanOrcid = value;
                    const m = cleanOrcid.match(/orcid\.org\/(\d{4}-\d{4}-\d{4}-\d{3}[\dX])/i);
                    if (m) cleanOrcid = m[1];
                    cleanOrcid = cleanOrcid.replace(/[^\d\-X]/gi, '');

                    isSubmitting = true;
                    if (btn) { btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Analysing…'; btn.disabled = true; }
                    startOrcidAjax(cleanOrcid, forceRefresh);

                } else if (type === 'doi') {
                    if (!validateDoi(value)) {
                        showError('Invalid DOI. Please use the format: 10.xxxx/xxxxx (e.g. 10.1038/nature12373).');
                        return;
                    }
                    let cleanDoi = value.replace(/^https?:\/\/(dx\.)?doi\.org\//i, '');

                    isSubmitting = true;
                    if (btn) { btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Analyzing…'; btn.disabled = true; }
                    startDoiAjax(cleanDoi, forceRefresh);
                }
            });
        }

        // Animasi confidence bars jika ada
        setTimeout(() => {
            document.querySelectorAll('.confidence-fill').forEach((fill, i) => {
                const w = fill.style.width;
                fill.style.width = '0%';
                setTimeout(() => { fill.style.width = w; }, 100 + i * 50);
            });
        }, 300);
    });
})();
</script>

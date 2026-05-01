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

// Konstanta untuk JavaScript
define('HOME_AJAX_BATCH', 3);
?>

<!-- ============================================================
     PROGRESS SECTION — tersembunyi, muncul saat AJAX berjalan
     ============================================================ -->
<div id="ajaxProgressSection" style="display:none; max-width:900px; margin:20px auto; background:#fff; border-radius:16px; padding:24px; box-shadow:0 8px 30px rgba(0,0,0,.1);">
    <div style="display:flex;align-items:center;gap:15px;margin-bottom:15px;">
        <div class="ajax-spinner" id="ajaxSpinner" style="width:38px;height:38px;border:4px solid #e9ecef;border-top-color:#667eea;border-radius:50%;animation:spin 1s linear infinite;flex-shrink:0;"></div>
        <div>
            <h3 id="ajaxProgressTitle" style="font-size:1.05rem;color:#333;margin:0 0 3px;">Preparing analysis…</h3>
            <p  id="ajaxProgressSubtitle" style="font-size:13px;color:#888;margin:0;">Please wait</p>
        </div>
    </div>
    <div style="background:#e9ecef;border-radius:10px;height:10px;overflow:hidden;margin-bottom:10px;">
        <div id="ajaxProgressBar" style="height:100%;background:linear-gradient(90deg,#667eea,#764ba2);border-radius:10px;transition:width .5s ease;width:0;"></div>
    </div>
    <div style="font-size:13px;color:#888;">
        Works: <span id="ajaxProgressCount" style="font-weight:600;color:#333;">0</span>
             / <span id="ajaxProgressTotal" style="font-weight:600;color:#333;">?</span>
        &nbsp;|&nbsp; Batch: <span id="ajaxProgressBatch" style="font-weight:600;color:#333;">0</span>
    </div>
</div>

<!-- ============================================================
     MAIN CONTENT
     ============================================================ -->
<div class="container" id="mainContent">

    <!-- Hero / Header -->
    <div class="header">
        <h1><i class="fas fa-globe"></i> Welcome! Wizdam AI-sikola</h1>
        <h2>Sustainable Development Goals (SDGs) Classification Analysis</h2>
        <p>AI-powered platform for analyzing research contributions to the 17 United Nations Sustainable Development Goals</p>
    </div>

    <!-- Search Form -->
    <div class="search-card">
        <h2><i class="fas fa-search"></i> Analyze Research Contributions</h2>
        <p>Enter an ORCID ID to analyze a researcher's profile, or a DOI to analyze a single article.</p>

        <form id="analysisForm" method="POST" action="" autocomplete="off">

            <div class="form-group">
                <div class="input-group" style="position:relative;">
                    <input
                        type="text"
                        id="input_value"
                        name="input_value"
                        class="floating-input form-input"
                        placeholder=" "
                        required
                        autocomplete="off"
                        spellcheck="false"
                    >
                    <label class="floating-label" for="input_value">Enter ORCID ID or DOI</label>
                </div>
                <div class="input-hint" style="margin-top:8px;font-size:13px;color:#888;">
                    <i class="fas fa-info-circle"></i>
                    Example: <strong>0000-0002-5152-9727</strong> (ORCID) &nbsp;|&nbsp;
                             <strong>10.1038/nature12373</strong> (DOI)
                </div>
                <div class="input-status" id="input_status" style="margin-top:6px;font-size:13px;">
                    <span id="status_icon" class="fas fa-question-circle" style="color:#aaa;margin-right:4px;"></span>
                    <span id="status_text" style="color:#aaa;">Enter your ORCID or DOI to begin</span>
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-top:12px;">
                <button type="submit" class="submit-btn btn btn-primary" id="submitBtn">
                    <i class="fas fa-search"></i> Analyze
                </button>
                <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#666;cursor:pointer;">
                    <input type="checkbox" id="force_refresh" name="force_refresh" value="1" style="cursor:pointer;">
                    Force refresh (bypass cache)
                </label>
            </div>
        </form>
    </div>

    <!-- Feature Cards -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin:24px 0;">
        <div style="background:#fff;border-radius:12px;padding:20px;text-align:center;box-shadow:0 2px 12px rgba(0,0,0,.07);">
            <div style="font-size:2rem;margin-bottom:10px;">🎯</div>
            <strong>17 SDG Goals</strong>
            <p style="font-size:13px;color:#666;margin-top:6px;">Classify research across all UN Sustainable Development Goals</p>
        </div>
        <div style="background:#fff;border-radius:12px;padding:20px;text-align:center;box-shadow:0 2px 12px rgba(0,0,0,.07);">
            <div style="font-size:2rem;margin-bottom:10px;">🔬</div>
            <strong>ORCID Analysis</strong>
            <p style="font-size:13px;color:#666;margin-top:6px;">Analyze a researcher's complete publication portfolio</p>
        </div>
        <div style="background:#fff;border-radius:12px;padding:20px;text-align:center;box-shadow:0 2px 12px rgba(0,0,0,.07);">
            <div style="font-size:2rem;margin-bottom:10px;">📄</div>
            <strong>DOI Analysis</strong>
            <p style="font-size:13px;color:#666;margin-top:6px;">Classify a single article by its DOI identifier</p>
        </div>
        <div style="background:#fff;border-radius:12px;padding:20px;text-align:center;box-shadow:0 2px 12px rgba(0,0,0,.07);">
            <div style="font-size:2rem;margin-bottom:10px;">⚡</div>
            <strong>AI-Powered</strong>
            <p style="font-size:13px;color:#666;margin-top:6px;">Multi-component scoring: keywords, similarity, causal analysis</p>
        </div>
    </div>
</div>

<!-- ============================================================
     RESULTS SECTION — diisi oleh JavaScript saat analisis selesai
     ============================================================ -->
<div class="container" id="ajaxResultsSection" style="display:none;"></div>

<!-- Loading Overlay -->
<div id="loadingOverlay" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,.92);z-index:9000;flex-direction:column;align-items:center;justify-content:center;">
    <div class="spinner" style="width:60px;height:60px;border:5px solid #e9ecef;border-top-color:#667eea;border-radius:50%;animation:spin 1s linear infinite;margin-bottom:20px;"></div>
    <div class="loading-text" style="font-size:1.1rem;font-weight:600;color:#333;">Analyzing…</div>
    <div class="loading-subtext" style="font-size:0.9rem;color:#888;margin-top:6px;">Please wait</div>
</div>

<style>
@keyframes spin { to { transform:rotate(360deg); } }
.floating-input { width:100%; padding:14px 16px; font-size:1rem; border:2px solid #e2e8f0; border-radius:10px; outline:none; transition:border-color .2s; }
.floating-input:focus { border-color:#667eea; }
.floating-input.valid   { border-color:#22c55e; }
.floating-input.invalid { border-color:#ef4444; }
.floating-label { position:absolute;left:16px;top:50%;transform:translateY(-50%);font-size:0.95rem;color:#aaa;pointer-events:none;transition:all .2s;background:#fff;padding:0 4px; }
.floating-input:not(:placeholder-shown) ~ .floating-label,
.floating-input:focus ~ .floating-label { top:-2px;transform:translateY(-50%) scale(.85);color:#667eea; }
.submit-btn { padding:12px 28px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none;border-radius:10px;font-size:1rem;font-weight:600;cursor:pointer;transition:opacity .2s; }
.submit-btn:disabled { opacity:.6;cursor:not-allowed; }
.work-sdg-tag { display:inline-flex;align-items:center;gap:6px;padding:5px 10px;border-radius:20px;color:#fff;font-size:13px;font-weight:600;margin:3px; }
.sdg-mini-icon img { border-radius:3px; }
.sdg-confidence-info { font-weight:400;opacity:.85; }
.none-SDG { color:#888;font-style:italic;padding:10px 0; }
.work-item { background:#fff;border-radius:12px;padding:20px;margin-bottom:14px;box-shadow:0 2px 12px rgba(0,0,0,.06);border-left:4px solid #667eea; }
.work-title { font-weight:700;font-size:1rem;color:#1e293b;margin-bottom:8px;line-height:1.4; }
.work-meta { display:flex;flex-wrap:wrap;gap:10px;font-size:13px;color:#64748b;margin-bottom:10px; }
.work-meta a { color:#667eea;text-decoration:none; }
.work-abstract { font-size:13px;color:#555;line-height:1.6;margin-bottom:10px;border-left:3px solid #e2e8f0;padding-left:10px; }
.sdg-card { background:#fff;border-radius:12px;padding:18px;box-shadow:0 2px 12px rgba(0,0,0,.07);position:relative;overflow:hidden; }
.sdg-card::after { content:'';position:absolute;top:0;left:0;width:4px;height:100%;background:#667eea; }
.sdg-icon img { width:48px;height:48px;border-radius:8px; }
.sdg-title { font-weight:700;font-size:.95rem;margin-bottom:10px;color:#1e293b; }
.sdg-stats { display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:13px;margin-bottom:8px; }
.sdg-stat-label { color:#888; }
.sdg-stat-value { font-weight:600;color:#333; }
.confidence-bar { background:#e9ecef;border-radius:10px;height:8px;overflow:hidden;margin-bottom:6px; }
.confidence-fill { height:100%;border-radius:10px;transition:width .8s ease-out; }
.contributor-type { display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;background:#f0f4ff;color:#667eea; }
.show-more-btn { padding:6px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;font-size:12px;color:#667eea;transition:background .2s; }
.show-more-btn:hover { background:#eef2ff; }
.detailed-analysis { display:none;margin-top:14px;padding:14px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0; }
.detailed-analysis.show { display:block; }
.analysis-section { margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid #e2e8f0; }
.analysis-section:last-child { border-bottom:none;margin-bottom:0; }
.analysis-components { display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin:8px 0; }
.component-score { background:#fff;border-radius:8px;padding:8px;border:1px solid #e2e8f0;text-align:center; }
.component-label { font-size:11px;color:#888;text-transform:uppercase;margin-bottom:4px; }
.component-value { font-size:1rem;font-weight:700;color:#667eea; }
.info-badge { display:inline-block;padding:2px 8px;border-radius:12px;font-size:11px;background:#f0f4ff;color:#667eea;margin-right:4px; }
.info-badge.confidence { background:#f0fdf4;color:#16a34a; }
.stats-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:12px;margin:16px 0; }
.stat-card { background:#fff;border-radius:10px;padding:16px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.07); }
.stat-number { font-size:1.8rem;font-weight:700;color:#667eea;line-height:1; }
.stat-label { font-size:12px;color:#888;margin-top:4px; }
.sdg-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px;margin:16px 0; }
.charts-section { display:grid;grid-template-columns:1fr 1fr;gap:20px;margin:16px 0; }
.chart-container { background:#fff;border-radius:12px;padding:18px;box-shadow:0 2px 12px rgba(0,0,0,.07); }
.chart-container canvas { max-height:250px; }
.info-general { background:#fff;border-radius:16px;padding:24px;box-shadow:0 4px 20px rgba(0,0,0,.08);margin-bottom:16px; }
.personal-info { display:flex;align-items:center;gap:18px;margin-bottom:16px; }
.avatar { width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.4rem;font-weight:700;flex-shrink:0; }
.works-container { background:#fff;border-radius:16px;padding:24px;box-shadow:0 4px 20px rgba(0,0,0,.08);margin-top:16px; }
.u-heading3 { font-size:1.1rem;font-weight:700;color:#1e293b;margin-bottom:16px; }
@media (max-width:640px) {
    .charts-section { grid-template-columns:1fr; }
    .analysis-components { grid-template-columns:1fr; }
}
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
                        style="margin-top:12px;padding:6px 14px;background:#dc2626;color:#fff;border:none;
                               border-radius:6px;cursor:pointer;font-size:.85rem;">
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
            icon.className = 'fas fa-question-circle'; icon.style.color = '#aaa';
            text.textContent = 'Enter ORCID or DOI to begin';
            text.style.color = '#aaa';
            inp.classList.remove('valid','invalid'); return;
        }
        const type = detectType(v);
        if (type === 'orcid') {
            const ok = validateOrcid(v);
            icon.className = ok ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
            icon.style.color = ok ? '#22c55e' : '#ef4444';
            text.textContent = ok ? 'Valid ORCID ID — ready to analyze researcher profile'
                                  : 'Invalid ORCID — check the 16-digit format and checksum';
            text.style.color = ok ? '#22c55e' : '#ef4444';
            inp.classList.toggle('valid', ok); inp.classList.toggle('invalid', !ok);
        } else if (type === 'doi') {
            const ok = validateDoi(v);
            icon.className = ok ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
            icon.style.color = ok ? '#22c55e' : '#ef4444';
            text.textContent = ok ? 'Valid DOI — ready to analyze article'
                                  : 'Invalid DOI — format should be 10.xxxx/xxxxx';
            text.style.color = ok ? '#22c55e' : '#ef4444';
            inp.classList.toggle('valid', ok); inp.classList.toggle('invalid', !ok);
        } else {
            icon.className = 'fas fa-exclamation-circle'; icon.style.color = '#f59e0b';
            text.textContent = 'Format not recognized. Use ORCID (0000-0000-0000-0000) or DOI (10.xxxx/xxxxx).';
            text.style.color = '#f59e0b';
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
            ? `<p style="margin:4px 0;font-size:.9rem;color:#64748b;"><i class="fas fa-university"></i> ${escH(info.institutions.slice(0,2).join(', '))}${info.institutions.length > 2 ? ' et al.' : ''}</p>` : '';
        document.getElementById('ajaxResultsSection').innerHTML = `
        <div class="info-general">
            <div class="personal-info">
                <div class="avatar">${escH(initials)}</div>
                <div>
                    <h2 style="margin:0 0 4px;font-size:1.2rem;">${escH(info.name || '–')}</h2>
                    <p style="margin:4px 0;font-size:.9rem;color:#64748b;"><i class="fab fa-orcid" style="color:#a6ce39;"></i> ${escH(info.orcid || '')}</p>
                    ${instHtml}
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-number">${total}</div><div class="stat-label">Total Works</div></div>
                <div class="stat-card"><div class="stat-number" id="ajaxStatSdgs">–</div><div class="stat-label">Identified SDGs</div></div>
                <div class="stat-card"><div class="stat-number" id="ajaxStatActive">–</div><div class="stat-label">Active Contributor</div></div>
                <div class="stat-card"><div class="stat-number" id="ajaxStatConf">–</div><div class="stat-label">Avg Confidence</div></div>
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
                <h5 style="margin:0 0 8px;color:#1e293b;">${escH(sdg + ': ' + (def.title||sdg))}
                    <span style="color:#667eea;font-weight:400;">(Score: ${(analysis.score||0).toFixed(3)})</span></h5>
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
            const def = SDG_DEFS[sdg] || { title:sdg, color:'#667eea', svg_url:'' };
            const prf = profile[sdg] || {};
            const pct = (sum.average_confidence * 100).toFixed(1);
            html += `<div class="sdg-card">
                <div class="sdg-icon" style="margin-bottom:10px;"><img src="${escH(def.svg_url)}" alt="${escH(def.title||sdg)}" style="width:48px;height:48px;border-radius:8px;"></div>
                <div>
                    <div class="sdg-title">${escH(def.title||sdg)}</div>
                    <div class="sdg-stats">
                        <div><div class="sdg-stat-label">Works</div><div class="sdg-stat-value">${sum.work_count}</div></div>
                        <div><div class="sdg-stat-label">Confidence</div><div class="sdg-stat-value">${pct}%</div></div>
                    </div>
                    <div class="confidence-bar"><div class="confidence-fill" style="width:${pct}%;background:${def.color};"></div></div>
                    ${prf.dominant_type ? `<div class="contributor-type">${escH(prf.dominant_type)}</div>` : ''}
                </div>
                <style>.sdg-card:nth-of-type(${i+1})::after{background:${def.color}}</style>
            </div>`;
        });
        html += '</div></div>';

        const summaryEl = document.getElementById('ajaxSdgSummary');
        if (summaryEl) summaryEl.innerHTML = html;

        // Charts
        const chartsEl = document.getElementById('ajaxCharts');
        if (chartsEl && typeof Chart !== 'undefined') {
            chartsEl.innerHTML = `<div class="info-general"><div class="charts-section">
                <div class="chart-container"><h4><i class="fas fa-chart-pie"></i> SDG Distribution</h4><canvas id="ajaxSdgChart"></canvas></div>
                <div class="chart-container"><h4><i class="fas fa-chart-bar"></i> Contributor Type</h4><canvas id="ajaxContribChart"></canvas></div>
            </div></div>`;

            if (ajaxSdgChart) ajaxSdgChart.destroy();
            ajaxSdgChart = new Chart(document.getElementById('ajaxSdgChart'), {
                type: 'doughnut',
                data: {
                    labels: Object.keys(summary).map(s => (SDG_DEFS[s]||{}).title || s),
                    datasets: [{ data: Object.values(summary).map(s => s.work_count),
                        backgroundColor: Object.keys(summary).map(s => (SDG_DEFS[s]||{}).color || '#667eea'),
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
                        backgroundColor: ['#667eea','#764ba2','#f093fb','#f5576c','#4facfe'].slice(0, Object.keys(ctypes).length),
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
            const def = SDG_DEFS[sdg] || { color:'#667eea', title:sdg, svg_url:'' };
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
                    ${authors ? `<p style="color:#64748b;font-size:.9rem;margin:0 0 4px;"><i class="fas fa-users"></i> ${authors}</p>` : ''}
                    ${journal  ? `<p style="color:#64748b;font-size:.9rem;margin:0 0 4px;"><i class="fas fa-book"></i> ${journal}${year ? ' (' + year + ')' : ''}</p>` : ''}
                    ${doi      ? `<p style="font-size:.9rem;margin:0;"><i class="fas fa-link" style="color:#667eea;"></i> <a href="https://doi.org/${doi}" target="_blank" rel="noopener" style="color:#667eea;">https://doi.org/${doi}</a></p>` : ''}
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-number">${sorted.length}</div><div class="stat-label">Identified SDGs</div></div>
                <div class="stat-card"><div class="stat-number">${sorted.length > 0 ? (sorted[0][1]*100).toFixed(0)+'%' : '–'}</div><div class="stat-label">Top Confidence</div></div>
            </div>
        </div>
        ${abst ? `<div class="info-general"><h4 style="margin-bottom:10px;"><i class="fas fa-align-left"></i> Abstract</h4><p style="color:#555;line-height:1.7;margin:0;">${abst}</p></div>` : ''}
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

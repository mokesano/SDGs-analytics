<?php
$page_title = 'API Reference';
$page_description = 'Complete API reference for the Wizdam AI SDG Classification Platform.';
?>
<div class="page-header">
    <div class="container">
        <div class="section-label">Developer Reference</div>
        <h1 class="section-title">API Reference</h1>
        <p class="section-subtitle">Full endpoint reference, request/response schemas, and integration examples.</p>
    </div>
</div>
<section class="section">
    <div class="container">
        <div class="alert alert-info" style="margin-bottom:2rem;"><i class="fas fa-info-circle"></i><span>For full usage guide, see the <a href="?page=documentation">Documentation</a> page.</span></div>
        <h2 style="font-size:1.25rem;margin-bottom:1rem;">Endpoints</h2>
        <div class="card" style="margin-bottom:1.25rem;">
            <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem;"><span class="badge badge-brand" style="font-family:monospace;">POST</span><code>_sdg=init</code></div>
            <p style="color:var(--gray-600);font-size:.875rem;margin-bottom:.5rem;">Initialize ORCID researcher analysis. Returns personal_info and total_works.</p>
            <div style="font-size:.8rem;"><strong>Params:</strong> <span class="badge badge-dark">orcid</span> <span class="badge badge-dark">refresh (optional)</span></div>
        </div>
        <div class="card" style="margin-bottom:1.25rem;">
            <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem;"><span class="badge badge-brand" style="font-family:monospace;">POST</span><code>_sdg=batch</code></div>
            <p style="color:var(--gray-600);font-size:.875rem;margin-bottom:.5rem;">Process a batch of works. Loop until is_done=true.</p>
            <div style="font-size:.8rem;"><strong>Params:</strong> <span class="badge badge-dark">orcid</span> <span class="badge badge-dark">offset</span> <span class="badge badge-dark">limit (default 3)</span></div>
        </div>
        <div class="card" style="margin-bottom:1.25rem;">
            <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem;"><span class="badge badge-brand" style="font-family:monospace;">POST</span><code>_sdg=summary</code></div>
            <p style="color:var(--gray-600);font-size:.875rem;margin-bottom:.5rem;">Generate aggregated SDG summary after all batches are done.</p>
            <div style="font-size:.8rem;"><strong>Params:</strong> <span class="badge badge-dark">orcid</span></div>
        </div>
        <div class="card" style="margin-bottom:1.25rem;">
            <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem;"><span class="badge badge-brand" style="font-family:monospace;">POST</span><code>_sdg=doi</code></div>
            <p style="color:var(--gray-600);font-size:.875rem;margin-bottom:.5rem;">Classify a single article by DOI with multi-source metadata enrichment.</p>
            <div style="font-size:.8rem;"><strong>Params:</strong> <span class="badge badge-dark">doi</span> <span class="badge badge-dark">refresh (optional)</span></div>
        </div>
        <h2 style="font-size:1.25rem;margin:2rem 0 1rem;">Sample Response (DOI)</h2>
        <pre style="background:var(--dark-code,#0d1117);color:#e6edf3;padding:1.25rem;border-radius:10px;overflow-x:auto;font-size:.82rem;line-height:1.8;">{
  "status": "success", "title": "Article Title", "doi": "10.1234/example",
  "journal": "Journal Name", "year": 2024,
  "authors": ["Author A"], "abstract": "Abstract text...",
  "sdg_scores": { "SDG3": 0.82, "SDG13": 0.61 }
}</pre>
    </div>
</section>

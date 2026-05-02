<?php
$page_title = 'Apps';
$page_description = 'Explore all tools and applications available on the Wizdam AI SDG Classification Analysis Platform.';
?>
<div class="page-header">
    <div class="container">
        <div class="section-label">Platform Tools</div>
        <h1 class="section-title">Applications & Tools</h1>
        <p class="section-subtitle">Powerful tools for SDG research analysis, classification, and visualization.</p>
    </div>
</div>

<section class="section">
    <div class="container">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.5rem;">

            <div class="app-card magic-card reveal">
                <div class="app-icon"><i class="fab fa-orcid"></i></div>
                <h3 style="font-size:1.1rem;margin-bottom:.5rem;">ORCID Researcher Analyzer</h3>
                <p style="color:var(--gray-500);font-size:.875rem;line-height:1.6;margin-bottom:1.25rem;">
                    Analyze a researcher's complete publication portfolio using their ORCID ID. Get full SDG classification across all 17 goals with contributor type mapping.
                </p>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.25rem;">
                    <span class="badge badge-brand">Sequential Batch</span>
                    <span class="badge badge-success">Anti-Timeout</span>
                    <span class="badge badge-info">Cached</span>
                </div>
                <a href="?page=home" class="btn btn-primary" style="width:100%;justify-content:center;">
                    <i class="fab fa-orcid"></i> Launch ORCID Analyzer
                </a>
            </div>

            <div class="app-card magic-card reveal" style="transition-delay:100ms;">
                <div class="app-icon"><i class="fas fa-file-alt"></i></div>
                <h3 style="font-size:1.1rem;margin-bottom:.5rem;">DOI Article Classifier</h3>
                <p style="color:var(--gray-500);font-size:.875rem;line-height:1.6;margin-bottom:1.25rem;">
                    Classify any single academic article using its DOI. Multi-source metadata from Crossref, OpenAlex, and Semantic Scholar.
                </p>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.25rem;">
                    <span class="badge badge-brand">Multi-Source</span>
                    <span class="badge badge-warning">Crossref + OpenAlex</span>
                </div>
                <a href="?page=home" class="btn btn-primary" style="width:100%;justify-content:center;">
                    <i class="fas fa-file-alt"></i> Launch DOI Classifier
                </a>
            </div>

            <div class="app-card magic-card reveal" style="transition-delay:200ms;">
                <div class="app-icon" style="background:rgba(99,102,241,.1);color:#6366f1;"><i class="fas fa-chart-line"></i></div>
                <h3 style="font-size:1.1rem;margin-bottom:.5rem;">Analytics Dashboard</h3>
                <p style="color:var(--gray-500);font-size:.875rem;line-height:1.6;margin-bottom:1.25rem;">
                    Visualize SDG distribution trends, researcher leaderboards, and publication analytics with interactive charts and heatmaps.
                </p>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.25rem;">
                    <span class="badge badge-dark">Chart.js</span>
                    <span class="badge badge-dark">ApexCharts</span>
                </div>
                <a href="?page=analitics-dashboard" class="btn btn-secondary" style="width:100%;justify-content:center;">
                    <i class="fas fa-chart-line"></i> Open Dashboard
                </a>
            </div>

            <div class="app-card magic-card reveal" style="transition-delay:300ms;">
                <div class="app-icon" style="background:rgba(16,185,129,.1);color:#10b981;"><i class="fas fa-layer-group"></i></div>
                <h3 style="font-size:1.1rem;margin-bottom:.5rem;">Bulk Analysis</h3>
                <p style="color:var(--gray-500);font-size:.875rem;line-height:1.6;margin-bottom:1.25rem;">
                    Process multiple ORCID IDs or DOIs simultaneously. Export results as CSV or JSON for further analysis.
                </p>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.25rem;">
                    <span class="badge badge-dark">CSV Export</span>
                    <span class="badge badge-dark">JSON Export</span>
                    <span class="badge badge-warning">Coming Soon</span>
                </div>
                <a href="?page=bulk-analysis" class="btn btn-secondary" style="width:100%;justify-content:center;">
                    <i class="fas fa-layer-group"></i> Bulk Analysis
                </a>
            </div>

            <div class="app-card magic-card reveal" style="transition-delay:400ms;">
                <div class="app-icon" style="background:rgba(245,158,11,.1);color:#f59e0b;"><i class="fas fa-key"></i></div>
                <h3 style="font-size:1.1rem;margin-bottom:.5rem;">API Access</h3>
                <p style="color:var(--gray-500);font-size:.875rem;line-height:1.6;margin-bottom:1.25rem;">
                    Get API access to integrate SDG classification directly into your own applications, OJS, or research management systems.
                </p>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.25rem;">
                    <span class="badge badge-brand">REST API</span>
                    <span class="badge badge-info">JSON Response</span>
                </div>
                <a href="?page=api-access" class="btn btn-outline" style="width:100%;justify-content:center;">
                    <i class="fas fa-key"></i> Get API Key
                </a>
            </div>

            <div class="app-card magic-card reveal" style="transition-delay:500ms;">
                <div class="app-icon" style="background:rgba(14,165,233,.1);color:#0ea5e9;"><i class="fas fa-plug"></i></div>
                <h3 style="font-size:1.1rem;margin-bottom:.5rem;">Integration Tools</h3>
                <p style="color:var(--gray-500);font-size:.875rem;line-height:1.6;margin-bottom:1.25rem;">
                    Plugins and widgets for embedding SDG analysis results into your website, OJS journal, or institutional repository.
                </p>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.25rem;">
                    <span class="badge badge-dark">OJS Plugin</span>
                    <span class="badge badge-warning">Coming Soon</span>
                </div>
                <a href="?page=integration-tools" class="btn btn-secondary" style="width:100%;justify-content:center;">
                    <i class="fas fa-plug"></i> View Integrations
                </a>
            </div>

        </div>
    </div>
</section>

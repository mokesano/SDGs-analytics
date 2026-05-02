<?php
/**
 * ORCID Profile Page
 * Menampilkan profil peneliti dengan detail karya dan analisis SDG
 * 
 * URL: /orcid/{orcid} atau index.php?page=orcid-profile&orcid={orcid}
 * 
 * @version 1.0.0
 * @author Wizdam Team
 */

if (!isset($page_title)) {
    $page_title = 'Researcher Profile - SDGs Classification Analysis';
}

// Ambil ORCID dari URL parameter
$orcid = isset($_GET['orcid']) ? trim($_GET['orcid']) : '';

// Validasi format ORCID
if (empty($orcid) || !preg_match('/^\d{4}-\d{4}-\d{4}-\d{3}[0-9X]$/', $orcid)) {
    // Redirect ke home jika ORCID tidak valid
    echo '<div class="container" style="padding:60px 20px;text-align:center;">';
    echo '<h2>Invalid ORCID</h2>';
    echo '<p>The ORCID ID provided is not valid.</p>';
    echo '<a href="index.php" class="btn btn-primary">Back to Home</a>';
    echo '</div>';
    return;
}

// Include database helper
require_once PROJECT_ROOT . '/includes/database.php';

// Cek apakah data sudah ada di database
$researcher = null;
try {
    Database::initialize();
    $researcher = Database::getResearcherByOrcid($orcid);
} catch (Exception $e) {
    error_log('Error loading researcher: ' . $e->getMessage());
}
?>

<style>
.profile-header {
    background: linear-gradient(135deg, var(--sdg4, #C5192D) 0%, var(--sdg16, #00689D) 100%);
    color: white;
    padding: 60px 0;
    margin-bottom: 40px;
}

.profile-info-card {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.sdg-badge-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 12px;
    margin: 20px 0;
}

.work-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    border-left: 4px solid var(--sdg4, #C5192D);
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    transition: transform 0.2s, box-shadow 0.2s;
}

.work-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}

.work-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--gray-800, #1e293b);
    margin-bottom: 8px;
}

.work-meta {
    font-size: 0.9rem;
    color: var(--gray-500, #64748b);
    margin-bottom: 12px;
}

.work-sdg-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.sdg-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    color: white;
}

.loading-state {
    text-align: center;
    padding: 60px 20px;
}

.spinner {
    border: 4px solid rgba(0,0,0,0.1);
    border-top-color: var(--sdg4, #C5192D);
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<div class="profile-header">
    <div class="container">
        <?php if ($researcher): ?>
            <h1 style="font-size: 2.5rem; margin-bottom: 10px;"><?php echo htmlspecialchars($researcher['name']); ?></h1>
            <p style="font-size: 1.1rem; opacity: 0.9;">
                <i class="fas fa-fingerprint"></i> <?php echo htmlspecialchars($orcid); ?>
            </p>
            <?php if (!empty($researcher['institutions'])): ?>
                <p style="margin-top: 15px; opacity: 0.85;">
                    <i class="fas fa-university"></i> 
                    <?php 
                    $insts = json_decode($researcher['institutions'], true);
                    echo htmlspecialchars(implode(' | ', is_array($insts) ? $insts : []));
                    ?>
                </p>
            <?php endif; ?>
        <?php else: ?>
            <h1 style="font-size: 2.5rem; margin-bottom: 10px;">Loading Profile...</h1>
            <p><i class="fas fa-fingerprint"></i> <?php echo htmlspecialchars($orcid); ?></p>
        <?php endif; ?>
    </div>
</div>

<div class="container">
    <?php if ($researcher): ?>
        <!-- Profile Info -->
        <div class="profile-info-card">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div>
                    <div style="font-size: 0.85rem; color: var(--gray-500); text-transform: uppercase; letter-spacing: 0.5px;">Total Works</div>
                    <div style="font-size: 2rem; font-weight: 700; color: var(--sdg4);"><?php echo (int)$researcher['total_works']; ?></div>
                </div>
                <div>
                    <div style="font-size: 0.85rem; color: var(--gray-500); text-transform: uppercase; letter-spacing: 0.5px;">SDG Areas</div>
                    <div style="font-size: 2rem; font-weight: 700; color: var(--sdg13);"><?php echo $researcher['sdg_count'] ?? 0; ?></div>
                </div>
                <div>
                    <div style="font-size: 0.85rem; color: var(--gray-500); text-transform: uppercase; letter-spacing: 0.5px;">Last Updated</div>
                    <div style="font-size: 1rem; font-weight: 600; color: var(--gray-700);">
                        <?php echo date('d M Y', strtotime($researcher['last_fetched'])); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- SDG Distribution -->
        <div class="profile-info-card">
            <h3 style="margin-bottom: 20px; color: var(--gray-800);">
                <i class="fas fa-chart-pie"></i> SDG Distribution
            </h3>
            <div id="sdgDistribution" class="sdg-badge-grid">
                <!-- Will be populated by JavaScript -->
                <div class="loading-state">
                    <div class="spinner"></div>
                    <p>Loading SDG distribution...</p>
                </div>
            </div>
        </div>

        <!-- Works List -->
        <div class="profile-info-card">
            <h3 style="margin-bottom: 20px; color: var(--gray-800);">
                <i class="fas fa-book"></i> Research Works
            </h3>
            <div id="worksList">
                <div class="loading-state">
                    <div class="spinner"></div>
                    <p>Loading works...</p>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Loading State for new profile -->
        <div class="loading-state">
            <div class="spinner"></div>
            <h3>Loading Researcher Profile...</h3>
            <p style="color: var(--gray-500);">Fetching data from ORCID and analyzing SDG classification</p>
        </div>
        
        <script>
        // Auto-trigger analysis for this ORCID
        document.addEventListener('DOMContentLoaded', function() {
            const orcid = '<?php echo htmlspecialchars($orcid); ?>';
            // Trigger the same AJAX flow as the home page search
            if (window.initOrcidAnalysis) {
                window.initOrcidAnalysis(orcid);
            }
        });
        </script>
    <?php endif; ?>
</div>

<script>
// Load works and SDG data
document.addEventListener('DOMContentLoaded', function() {
    const orcid = '<?php echo htmlspecialchars($orcid); ?>';
    
    // Fetch works data via AJAX
    fetch('index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `_sdg=init&orcid=${encodeURIComponent(orcid)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success' && data.works_stubs) {
            renderWorks(data.works_stubs);
            renderSgdDistribution(data.works_stubs);
        }
    })
    .catch(error => {
        console.error('Error loading profile:', error);
        document.getElementById('worksList').innerHTML = 
            '<p style="color: red;">Error loading works. Please try again.</p>';
    });
});

function renderWorks(works) {
    const container = document.getElementById('worksList');
    if (!works || works.length === 0) {
        container.innerHTML = '<p>No works found.</p>';
        return;
    }
    
    container.innerHTML = works.map(work => `
        <div class="work-card">
            <div class="work-title">${escapeHtml(work.title)}</div>
            <div class="work-meta">
                ${work.doi ? `<i class="fas fa-link"></i> <a href="https://doi.org/${escapeHtml(work.doi)}" target="_blank">${escapeHtml(work.doi)}</a>` : ''}
            </div>
            <div class="work-sdg-tags" id="sdg-tags-${work.index}">
                <span style="font-size: 0.85rem; color: var(--gray-500);">Click "Analyze" to see SDG classification</span>
            </div>
        </div>
    `).join('');
}

function renderSgdDistribution(works) {
    // Placeholder - will be implemented with actual SDG analysis data
    const container = document.getElementById('sdgDistribution');
    container.innerHTML = '<p style="color: var(--gray-500);">SDG distribution will appear after analysis</p>';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php
/**
 * Journal Profile Page
 * Menampilkan profil jurnal dengan metadata Scopus dan mapping SDG
 * 
 * URL: /journal/{issn} atau index.php?page=journal-profile&issn={issn}
 * 
 * @version 1.0.0
 * @author Wizdam Team
 */

if (!isset($page_title)) {
    $page_title = 'Journal Profile - SDGs Classification Analysis';
}

// Ambil ISSN dari URL parameter
$issn = isset($_GET['issn']) ? trim($_GET['issn']) : '';

// Validasi format ISSN (8 digit dengan hyphen)
if (empty($issn) || !preg_match('/^\d{4}-\d{3}[0-9X]$/', $issn)) {
    echo '<div class="container" style="padding:60px 20px;text-align:center;">';
    echo '<h2>Invalid ISSN</h2>';
    echo '<p>The ISSN provided is not valid.</p>';
    echo '<a href="index.php" class="btn btn-primary">Back to Home</a>';
    echo '</div>';
    return;
}

// Include database helper
require_once PROJECT_ROOT . '/includes/database.php';

// Cek apakah data sudah ada di database
$journal = null;
try {
    Database::initialize();
    
    $db = Database::getInstance();
    $stmt = $db->prepare("
        SELECT j.*, GROUP_CONCAT(js.subject, ', ') as subjects
        FROM journals j
        LEFT JOIN journal_subjects js ON j.id = js.journal_id
        WHERE j.issn = :issn OR j.eissn = :issn
        GROUP BY j.id
    ");
    $stmt->execute([':issn' => $issn]);
    $journal = $stmt->fetch();
} catch (Exception $e) {
    error_log('Error loading journal: ' . $e->getMessage());
}
?>

<style>
.journal-header {
    background: linear-gradient(135deg, var(--sdg9, #FD6925) 0%, var(--sdg4, #C5192D) 100%);
    color: white;
    padding: 60px 0;
    margin-bottom: 40px;
}

.journal-info-card {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.quarter-badge {
    display: inline-block;
    padding: 8px 20px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 1.2rem;
}

.quarter-q1 { background: #00A651; color: white; }
.quarter-q2 { background: #0085C8; color: white; }
.quarter-q3 { background: #FCC30B; color: black; }
.quarter-q4 { background: #E5243B; color: white; }

.metric-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.metric-item {
    text-align: center;
    padding: 15px;
    background: var(--gray-50, #f8fafc);
    border-radius: 8px;
}

.metric-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--sdg9, #FD6925);
}

.metric-label {
    font-size: 0.85rem;
    color: var(--gray-500, #64748b);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 5px;
}

.loading-state {
    text-align: center;
    padding: 60px 20px;
}

.spinner {
    border: 4px solid rgba(0,0,0,0.1);
    border-top-color: var(--sdg9, #FD6925);
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

<div class="journal-header">
    <div class="container">
        <?php if ($journal): ?>
            <h1 style="font-size: 2.5rem; margin-bottom: 10px;"><?php echo htmlspecialchars($journal['title']); ?></h1>
            <p style="font-size: 1.1rem; opacity: 0.9;">
                <i class="fas fa-barcode"></i> ISSN: <?php echo htmlspecialchars($journal['issn']); ?>
                <?php if (!empty($journal['eissn'])): ?>
                    | eISSN: <?php echo htmlspecialchars($journal['eissn']); ?>
                <?php endif; ?>
            </p>
            <?php if (!empty($journal['publisher'])): ?>
                <p style="margin-top: 15px; opacity: 0.85;">
                    <i class="fas fa-building"></i> <?php echo htmlspecialchars($journal['publisher']); ?>
                </p>
            <?php endif; ?>
        <?php else: ?>
            <h1 style="font-size: 2.5rem; margin-bottom: 10px;">Loading Journal...</h1>
            <p><i class="fas fa-barcode"></i> ISSN: <?php echo htmlspecialchars($issn); ?></p>
        <?php endif; ?>
    </div>
</div>

<div class="container">
    <?php if ($journal): ?>
        <!-- Journal Metrics -->
        <div class="journal-info-card">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
                <div>
                    <?php if (!empty($journal['quartile'])): ?>
                        <span class="quarter-badge quarter-<?php echo strtolower($journal['quartile']); ?>">
                            <?php echo htmlspecialchars($journal['quartile']); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if (!empty($journal['open_access'])): ?>
                        <span style="background: #56C02B; color: white; padding: 6px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                            <i class="fas fa-lock-open"></i> Open Access
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="metric-grid">
                <div class="metric-item">
                    <div class="metric-value"><?php echo $journal['sjr'] ?? '-'; ?></div>
                    <div class="metric-label">SJR</div>
                </div>
                <div class="metric-item">
                    <div class="metric-value"><?php echo $journal['h_index'] ?? '-'; ?></div>
                    <div class="metric-label">H-Index</div>
                </div>
                <div class="metric-item">
                    <div class="metric-value"><?php echo !empty($journal['country']) ? $journal['country'] : '-'; ?></div>
                    <div class="metric-label">Country</div>
                </div>
            </div>
            
            <?php if (!empty($journal['subjects'])): ?>
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--gray-200);">
                    <strong style="color: var(--gray-700);">Subject Areas:</strong>
                    <p style="color: var(--gray-600); margin-top: 8px;"><?php echo htmlspecialchars($journal['subjects']); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- SDG Mapping -->
        <div class="journal-info-card">
            <h3 style="margin-bottom: 20px; color: var(--gray-800);">
                <i class="fas fa-bullseye"></i> SDG Mapping
            </h3>
            <div id="sdgMapping">
                <p style="color: var(--gray-500);">SDG mapping based on journal subject areas will appear here.</p>
            </div>
        </div>

    <?php else: ?>
        <!-- Loading State for new journal -->
        <div class="loading-state">
            <div class="spinner"></div>
            <h3>Loading Journal Profile...</h3>
            <p style="color: var(--gray-500);">Fetching metadata from Scopus API</p>
        </div>
        
        <script>
        // Auto-trigger journal analysis for this ISSN
        document.addEventListener('DOMContentLoaded', function() {
            const issn = '<?php echo htmlspecialchars($issn); ?>';
            // This would trigger the Scopus API fetch
            console.log('Would fetch journal data for ISSN:', issn);
        });
        </script>
    <?php endif; ?>
</div>

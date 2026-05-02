<?php
/**
 * Leaderboard Page - Phase 4: Analytics & Leaderboard
 * 
 * Menampilkan papan peringkat peneliti berdasarkan kontribusi SDG
 * dengan filter per SDG, tipe kontributor, dan institusi.
 */

$page_title = __('leaderboard.page_title');
$page_description = __('leaderboard.page_subtitle');

// Load database
require_once PROJECT_ROOT . '/includes/database.php';
$db = getDB();

// Parameters
$selected_sdg = isset($_GET['sdg']) ? strtoupper(trim($_GET['sdg'])) : '';
$contributor_type = isset($_GET['type']) ? trim($_GET['type']) : 'all';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$page_num = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page_num - 1) * $limit;

// Validasi SDG code
$valid_sdgs = [];
for ($i = 1; $i <= 17; $i++) {
    $valid_sdgs[] = 'SDG' . $i;
}
if ($selected_sdg && !in_array($selected_sdg, $valid_sdgs)) {
    $selected_sdg = '';
}

// Build query untuk leaderboard
try {
    $base_query = "
        SELECT 
            r.id,
            r.orcid,
            r.name,
            r.institutions,
            COUNT(DISTINCT w.id) as total_works,
            COUNT(DISTINCT ws.work_id) as sdg_works,
            AVG(ws.confidence_score) as avg_confidence,
            SUM(ws.confidence_score) as contribution_score
        FROM researchers r
        INNER JOIN works w ON r.id = w.researcher_id
        INNER JOIN work_sdgs ws ON w.id = ws.work_id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($selected_sdg) {
        $base_query .= " AND ws.sdg_code = ?";
        $params[] = $selected_sdg;
    }
    
    if ($contributor_type !== 'all' && in_array($contributor_type, ['Active Contributor', 'Relevant Contributor', 'Discutor'])) {
        $base_query .= " AND ws.contributor_type = ?";
        $params[] = $contributor_type;
    }
    
    $base_query .= "
        GROUP BY r.id, r.orcid, r.name, r.institutions
        HAVING contribution_score > 0
        ORDER BY contribution_score DESC
        LIMIT ? OFFSET ?
    ";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($base_query);
    $stmt->execute($params);
    $researchers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for pagination
    $count_query = "
        SELECT COUNT(DISTINCT r.id) as total
        FROM researchers r
        INNER JOIN works w ON r.id = w.researcher_id
        INNER JOIN work_sdgs ws ON w.id = ws.work_id
        WHERE 1=1
    ";
    
    $count_params = [];
    if ($selected_sdg) {
        $count_query .= " AND ws.sdg_code = ?";
        $count_params[] = $selected_sdg;
    }
    if ($contributor_type !== 'all' && in_array($contributor_type, ['Active Contributor', 'Relevant Contributor', 'Discutor'])) {
        $count_query .= " AND ws.contributor_type = ?";
        $count_params[] = $contributor_type;
    }
    
    $stmt = $db->prepare($count_query);
    $stmt->execute($count_params);
    $total_count = $stmt->fetchColumn();
    $total_pages = ceil($total_count / $limit);
    
} catch (PDOException $e) {
    error_log("Leaderboard query error: " . $e->getMessage());
    $researchers = [];
    $total_count = 0;
    $total_pages = 0;
}

// SDG definitions untuk display
$sdg_definitions = [
    'SDG1' => ['icon' => '🏛️', 'color' => '#E5243B', 'name' => __('sdg.1_name')],
    'SDG2' => ['icon' => '🌾', 'color' => '#DDA63A', 'name' => __('sdg.2_name')],
    'SDG3' => ['icon' => '🏥', 'color' => '#4C9F38', 'name' => __('sdg.3_name')],
    'SDG4' => ['icon' => '📚', 'color' => '#C5192D', 'name' => __('sdg.4_name')],
    'SDG5' => ['icon' => '⚖️', 'color' => '#FF3A21', 'name' => __('sdg.5_name')],
    'SDG6' => ['icon' => '💧', 'color' => '#26BDE2', 'name' => __('sdg.6_name')],
    'SDG7' => ['icon' => '⚡', 'color' => '#FCC30B', 'name' => __('sdg.7_name')],
    'SDG8' => ['icon' => '💼', 'color' => '#A21942', 'name' => __('sdg.8_name')],
    'SDG9' => ['icon' => '🔬', 'color' => '#FD6925', 'name' => __('sdg.9_name')],
    'SDG10' => ['icon' => '🤝', 'color' => '#DD1367', 'name' => __('sdg.10_name')],
    'SDG11' => ['icon' => '🏙️', 'color' => '#FD9D24', 'name' => __('sdg.11_name')],
    'SDG12' => ['icon' => '♻️', 'color' => '#BF8B2E', 'name' => __('sdg.12_name')],
    'SDG13' => ['icon' => '🌍', 'color' => '#3F7E44', 'name' => __('sdg.13_name')],
    'SDG14' => ['icon' => '🐟', 'color' => '#0A97D9', 'name' => __('sdg.14_name')],
    'SDG15' => ['icon' => '🌳', 'color' => '#56C02B', 'name' => __('sdg.15_name')],
    'SDG16' => ['icon' => '⚖️', 'color' => '#00689D', 'name' => __('sdg.16_name')],
    'SDG17' => ['icon' => '🤝', 'color' => '#19486A', 'name' => __('sdg.17_name')],
];
?>

<div class="page-header">
    <div class="container">
        <div class="section-label"><?php echo __('leaderboard.browse_by_sdg'); ?></div>
        <h1 class="section-title"><?php echo __('leaderboard.page_title'); ?></h1>
        <p class="section-subtitle"><?php echo __('leaderboard.page_subtitle'); ?></p>
    </div>
</div>

<section class="section">
    <div class="container">
        
        <!-- Filter Controls -->
        <div class="filter-bar reveal" style="margin-bottom:2rem;padding:1.5rem;background:var(--bg-soft,#f8fafc);border-radius:12px;">
            <form method="GET" action="" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;align-items:end;">
                
                <div>
                    <label style="display:block;margin-bottom:0.5rem;font-weight:600;font-size:0.9rem;"><?php echo __('leaderboard.select_sdg'); ?></label>
                    <select name="sdg" onchange="this.form.submit()" style="width:100%;padding:0.75rem;border:1px solid var(--border,#e2e8f0);border-radius:8px;font-size:0.95rem;">
                        <option value=""><?php echo __('leaderboard.all_sdgs'); ?></option>
                        <?php foreach ($valid_sdgs as $sdg): ?>
                            <option value="<?php echo $sdg; ?>" <?php echo $selected_sdg === $sdg ? 'selected' : ''; ?>>
                                <?php echo $sdg_definitions[$sdg]['icon'] . ' ' . $sdg . ' - ' . $sdg_definitions[$sdg]['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label style="display:block;margin-bottom:0.5rem;font-weight:600;font-size:0.9rem;"><?php echo __('leaderboard.filter_type'); ?></label>
                    <select name="type" onchange="this.form.submit()" style="width:100%;padding:0.75rem;border:1px solid var(--border,#e2e8f0);border-radius:8px;font-size:0.95rem;">
                        <option value="all" <?php echo $contributor_type === 'all' ? 'selected' : ''; ?>><?php echo __('leaderboard.all_types'); ?></option>
                        <option value="Active Contributor" <?php echo $contributor_type === 'Active Contributor' ? 'selected' : ''; ?>><?php echo __('leaderboard.active_contributors'); ?></option>
                        <option value="Relevant Contributor" <?php echo $contributor_type === 'Relevant Contributor' ? 'selected' : ''; ?>><?php echo __('leaderboard.relevant_contributors'); ?></option>
                        <option value="Discutor" <?php echo $contributor_type === 'Discutor' ? 'selected' : ''; ?>><?php echo __('leaderboard.discutors'); ?></option>
                    </select>
                </div>
                
                <div>
                    <label style="display:block;margin-bottom:0.5rem;font-weight:600;font-size:0.9rem;"><?php echo __('leaderboard.sort_by'); ?></label>
                    <select name="sort" onchange="this.form.submit()" style="width:100%;padding:0.75rem;border:1px solid var(--border,#e2e8f0);border-radius:8px;font-size:0.95rem;">
                        <option value="score"><?php echo __('leaderboard.sort_by_score'); ?></option>
                        <option value="works"><?php echo __('leaderboard.sort_by_works'); ?></option>
                        <option value="confidence"><?php echo __('leaderboard.sort_by_confidence'); ?></option>
                    </select>
                </div>
                
            </form>
        </div>

        <!-- Results Info -->
        <?php if ($total_count > 0): ?>
            <div style="margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;" class="reveal">
                <span style="color:var(--text-muted,#64748b);">
                    <?php printf(__('leaderboard.showing_results'), $offset + 1, min($offset + $limit, $total_count), $total_count); ?>
                </span>
                <div style="display:flex;gap:0.5rem;">
                    <button onclick="exportToCSV()" class="btn btn-outline" style="padding:0.5rem 1rem;font-size:0.9rem;">
                        <i class="fas fa-download"></i> <?php echo __('leaderboard.export_csv'); ?>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Leaderboard Table -->
        <?php if (empty($researchers)): ?>
            <div class="alert alert-info reveal">
                <i class="fas fa-info-circle"></i>
                <span><?php echo __('leaderboard.no_data'); ?></span>
            </div>
        <?php else: ?>
            <div class="leaderboard-table reveal" style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="border-bottom:2px solid var(--border,#e2e8f0);">
                            <th style="padding:1rem;text-align:left;font-weight:600;"><?php echo __('leaderboard.rank'); ?></th>
                            <th style="padding:1rem;text-align:left;font-weight:600;"><?php echo __('leaderboard.researcher'); ?></th>
                            <th style="padding:1rem;text-align:center;font-weight:600;"><?php echo __('leaderboard.contribution_score'); ?></th>
                            <th style="padding:1rem;text-align:center;font-weight:600;"><?php echo __('leaderboard.total_works'); ?></th>
                            <th style="padding:1rem;text-align:center;font-weight:600;"><?php echo __('leaderboard.avg_confidence'); ?></th>
                            <th style="padding:1rem;text-align:center;font-weight:600;"><?php echo __('leaderboard.top_sdgs'); ?></th>
                            <th style="padding:1rem;text-align:center;font-weight:600;"><?php echo __('leaderboard.actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = $offset + 1;
                        foreach ($researchers as $index => $researcher): 
                            $institutions = json_decode($researcher['institutions'], true) ?: [];
                            $primary_institution = !empty($institutions) ? $institutions[0] : '';
                            
                            // Tentukan medal/badge untuk top 3
                            $badge = '';
                            if ($rank === 1) $badge = '🥇';
                            elseif ($rank === 2) $badge = '🥈';
                            elseif ($rank === 3) $badge = '🥉';
                        ?>
                            <tr style="border-bottom:1px solid var(--border,#e2e8f0);transition:background 0.2s;" onmouseover="this.style.background='var(--bg-soft,#f8fafc)'" onmouseout="this.style.background='transparent'">
                                <td style="padding:1rem;font-weight:600;font-size:1.1rem;">
                                    <?php echo $badge ? $badge : '#' . $rank; ?>
                                </td>
                                <td style="padding:1rem;">
                                    <div style="display:flex;flex-direction:column;gap:0.25rem;">
                                        <a href="/orcid/<?php echo htmlspecialchars($researcher['orcid']); ?>" style="font-weight:600;color:var(--primary,#1e40af);text-decoration:none;">
                                            <?php echo htmlspecialchars($researcher['name'] ?: 'Unknown Researcher'); ?>
                                        </a>
                                        <?php if ($primary_institution): ?>
                                            <span style="font-size:0.85rem;color:var(--text-muted,#64748b);">
                                                <i class="fas fa-university"></i> <?php echo htmlspecialchars($primary_institution); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="padding:1rem;text-align:center;">
                                    <span style="display:inline-block;padding:0.5rem 1rem;background:linear-gradient(135deg,#ff5627,#ff8a50);color:#fff;border-radius:20px;font-weight:700;font-size:0.95rem;">
                                        <?php echo number_format($researcher['contribution_score'], 2); ?>
                                    </span>
                                </td>
                                <td style="padding:1rem;text-align:center;font-weight:500;">
                                    <?php echo number_format($researcher['total_works']); ?>
                                </td>
                                <td style="padding:1rem;text-align:center;">
                                    <span style="display:inline-block;padding:0.25rem 0.75rem;background:<?php 
                                        echo $researcher['avg_confidence'] >= 0.7 ? '#4c9f38' : ($researcher['avg_confidence'] >= 0.4 ? '#26bde2' : '#fcc30b');
                                    ?>;color:#fff;border-radius:12px;font-size:0.85rem;font-weight:600;">
                                        <?php echo number_format($researcher['avg_confidence'] * 100, 1); ?>%
                                    </span>
                                </td>
                                <td style="padding:1rem;text-align:center;">
                                    <span style="font-size:1.2rem;">
                                        <?php echo $selected_sdg ? $sdg_definitions[$selected_sdg]['icon'] : '📊'; ?>
                                    </span>
                                </td>
                                <td style="padding:1rem;text-align:center;">
                                    <a href="/orcid/<?php echo htmlspecialchars($researcher['orcid']); ?>" class="btn btn-primary" style="padding:0.5rem 1rem;font-size:0.85rem;border-radius:8px;">
                                        <i class="fas fa-eye"></i> <?php echo __('leaderboard.view_profile'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php 
                            $rank++;
                        endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination reveal" style="margin-top:2rem;display:flex;justify-content:center;gap:0.5rem;flex-wrap:wrap;">
                    <?php if ($page_num > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page_num - 1])); ?>" class="btn btn-outline" style="padding:0.5rem 1rem;">
                            <i class="fas fa-chevron-left"></i> <?php echo __('pagination.previous'); ?>
                        </a>
                    <?php endif; ?>
                    
                    <?php 
                    $start_page = max(1, $page_num - 2);
                    $end_page = min($total_pages, $page_num + 2);
                    for ($i = $start_page; $i <= $end_page; $i++): 
                    ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                           class="btn <?php echo $i === $page_num ? 'btn-primary' : 'btn-outline'; ?>" 
                           style="padding:0.5rem 1rem;min-width:40px;">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page_num < $total_pages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page_num + 1])); ?>" class="btn btn-outline" style="padding:0.5rem 1rem;">
                            <?php echo __('pagination.next'); ?> <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Ranking Categories Info -->
        <div class="ranking-categories reveal" style="margin-top:3rem;display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem;">
            
            <div class="category-card" style="padding:1.5rem;background:linear-gradient(135deg,rgba(76,159,56,0.1),rgba(76,159,56,0.05));border-radius:12px;border-left:4px solid #4c9f38;">
                <h4 style="margin-bottom:0.75rem;display:flex;align-items:center;gap:0.5rem;">
                    <span style="font-size:1.5rem;">🎯</span>
                    <?php echo __('leaderboard.active_contributors'); ?>
                </h4>
                <p style="color:var(--text-muted,#64748b);font-size:0.9rem;line-height:1.6;">
                    <?php echo __('leaderboard.active_contributors_desc'); ?>
                </p>
            </div>

            <div class="category-card" style="padding:1.5rem;background:linear-gradient(135deg,rgba(38,189,226,0.1),rgba(38,189,226,0.05));border-radius:12px;border-left:4px solid #26bde2;">
                <h4 style="margin-bottom:0.75rem;display:flex;align-items:center;gap:0.5rem;">
                    <span style="font-size:1.5rem;">📋</span>
                    <?php echo __('leaderboard.relevant_contributors'); ?>
                </h4>
                <p style="color:var(--text-muted,#64748b);font-size:0.9rem;line-height:1.6;">
                    <?php echo __('leaderboard.relevant_contributors_desc'); ?>
                </p>
            </div>

            <div class="category-card" style="padding:1.5rem;background:linear-gradient(135deg,rgba(252,195,11,0.1),rgba(252,195,11,0.05));border-radius:12px;border-left:4px solid #fcc30b;">
                <h4 style="margin-bottom:0.75rem;display:flex;align-items:center;gap:0.5rem;">
                    <span style="font-size:1.5rem;">💬</span>
                    <?php echo __('leaderboard.discutors'); ?>
                </h4>
                <p style="color:var(--text-muted,#64748b);font-size:0.9rem;line-height:1.6;">
                    <?php echo __('leaderboard.discutors_desc'); ?>
                </p>
            </div>

        </div>

        <!-- CTA Section -->
        <div class="cta-section reveal" style="margin-top:3rem;text-align:center;padding:2.5rem;background:linear-gradient(135deg,rgba(255,86,39,0.1),rgba(255,138,80,0.05));border-radius:16px;">
            <h3 style="margin-bottom:1rem;font-size:1.5rem;"><?php echo __('leaderboard.cta_heading'); ?></h3>
            <p style="color:var(--text-muted,#64748b);margin-bottom:1.5rem;max-width:600px;margin-left:auto;margin-right:auto;">
                <?php echo __('leaderboard.cta_text'); ?>
            </p>
            <a href="/home" class="btn btn-primary" style="padding:0.75rem 2rem;font-size:1rem;border-radius:10px;">
                <i class="fas fa-rocket"></i> <?php echo __('leaderboard.analyze_now'); ?>
            </a>
        </div>

        <p class="reveal" style="margin-top:2rem;text-align:center;color:var(--text-muted,#64748b);font-size:0.9rem;">
            <i class="fas fa-sync-alt"></i> <?php echo __('leaderboard.update_note'); ?>
        </p>

    </div>
</section>

<script>
function exportToCSV() {
    // TODO: Implement CSV export functionality
    alert('<?php echo __('leaderboard.export_coming_soon'); ?>');
}
</script>

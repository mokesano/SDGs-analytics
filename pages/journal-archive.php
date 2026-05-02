<?php
/**
 * Journal Archive Page
 * Menampilkan daftar semua jurnal yang pernah dianalisis
 * 
 * URL: /journals atau index.php?page=journal-archive
 * 
 * @version 1.0.0
 * @author Wizdam Team
 */

if (!isset($page_title)) {
    $page_title = __('journal.archive_title');
}

// Include dependencies
require_once PROJECT_ROOT . '/includes/scopus_wrapper.php';

// Pagination
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$page_num = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page_num - 1) * $limit;

// Search & Filter
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$quartile_filter = isset($_GET['quartile']) ? strtoupper($_GET['quartile']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'recent';

// Initialize wrapper
$scopusWrapper = new ScopusWrapper();

// Get journals
if (!empty($search)) {
    $journals = $scopusWrapper->searchJournals($search, $limit);
} else {
    $journals = $scopusWrapper->getAllJournals($limit + 1, $offset);
}

// Check if more results exist
$has_more = count($journals) > $limit;
if ($has_more) {
    array_pop($journals); // Remove extra item used for detection
}

// Apply quartile filter
if (!empty($quartile_filter) && !empty($journals)) {
    $journals = array_filter($journals, function($j) use ($quartile_filter) {
        return strtoupper($j['quartile'] ?? '') === $quartile_filter;
    });
}

// Apply sorting
if (!empty($journals)) {
    switch ($sort) {
        case 'alpha':
            usort($journals, function($a, $b) {
                return strcmp($a['title'], $b['title']);
            });
            break;
        case 'sjr':
            usort($journals, function($a, $b) {
                return ($b['sjr'] ?? 0) <=> ($a['sjr'] ?? 0);
            });
            break;
        case 'recent':
        default:
            // Already sorted by recent from DB
            break;
    }
}
?>

<style>
.journal-archive-header {
    background: linear-gradient(135deg, var(--sdg9, #FD6925) 0%, var(--sdg4, #C5192D) 100%);
    color: white;
    padding: 60px 0;
    margin-bottom: 40px;
}

.archive-controls {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.search-form {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 15px;
    margin-bottom: 20px;
}

.search-input {
    padding: 12px 20px;
    border: 2px solid var(--gray-200);
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s;
}

.search-input:focus {
    outline: none;
    border-color: var(--sdg9, #FD6925);
}

.filter-group {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: center;
}

.filter-select {
    padding: 10px 15px;
    border: 2px solid var(--gray-200);
    border-radius: 8px;
    font-size: 0.9rem;
    cursor: pointer;
    transition: border-color 0.3s;
}

.filter-select:focus {
    outline: none;
    border-color: var(--sdg9, #FD6925);
}

.journal-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 25px;
    margin-bottom: 40px;
}

.journal-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    transition: transform 0.3s, box-shadow 0.3s;
    border-left: 4px solid transparent;
}

.journal-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}

.journal-card.q1 { border-left-color: #00A651; }
.journal-card.q2 { border-left-color: #0085C8; }
.journal-card.q3 { border-left-color: #FCC30B; }
.journal-card.q4 { border-left-color: #E5243B; }

.journal-card-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--gray-800);
    margin-bottom: 10px;
    line-height: 1.4;
}

.journal-card-meta {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.journal-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-q1 { background: #00A651; color: white; }
.badge-q2 { background: #0085C8; color: white; }
.badge-q3 { background: #FCC30B; color: black; }
.badge-q4 { background: #E5243B; color: white; }

.badge-sjr {
    background: var(--gray-100);
    color: var(--gray-700);
}

.journal-card-publisher {
    color: var(--gray-600);
    font-size: 0.9rem;
    margin-bottom: 10px;
}

.journal-card-subjects {
    color: var(--gray-500);
    font-size: 0.85rem;
    margin-bottom: 15px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.journal-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 15px;
    border-top: 1px solid var(--gray-100);
}

.journal-date {
    font-size: 0.8rem;
    color: var(--gray-500);
}

.btn-view-profile {
    padding: 8px 20px;
    background: var(--sdg9, #FD6925);
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    text-decoration: none;
    transition: background 0.3s;
}

.btn-view-profile:hover {
    background: #e55a1a;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--gray-500);
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.3;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 30px;
}

.pagination-btn {
    padding: 10px 18px;
    background: white;
    border: 2px solid var(--gray-200);
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
}

.pagination-btn.active {
    background: var(--sdg9, #FD6925);
    border-color: var(--sdg9, #FD6925);
    color: white;
}

.pagination-btn:hover:not(.active) {
    border-color: var(--sdg9, #FD6925);
}
</style>

<div class="journal-archive-header">
    <div class="container">
        <h1 style="font-size: 2.5rem; margin-bottom: 10px;"><?php echo __('journal.archive_title'); ?></h1>
        <p style="font-size: 1.1rem; opacity: 0.9;">
            <?php echo __('journal.archive_subtitle'); ?>
        </p>
    </div>
</div>

<div class="container">
    <!-- Search & Filter Controls -->
    <div class="archive-controls">
        <form method="GET" action="" class="search-form">
            <input type="text" 
                   name="q" 
                   class="search-input" 
                   placeholder="<?php echo __('journal.search_placeholder'); ?>"
                   value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn-view-profile">
                <i class="fas fa-search"></i> <?php echo __('journal.search_another'); ?>
            </button>
        </form>
        
        <div class="filter-group">
            <select name="quartile" class="filter-select" onchange="updateFilters()">
                <option value=""><?php echo __('journal.all_quartiles'); ?></option>
                <option value="Q1" <?php echo $quartile_filter === 'Q1' ? 'selected' : ''; ?>>Q1</option>
                <option value="Q2" <?php echo $quartile_filter === 'Q2' ? 'selected' : ''; ?>>Q2</option>
                <option value="Q3" <?php echo $quartile_filter === 'Q3' ? 'selected' : ''; ?>>Q3</option>
                <option value="Q4" <?php echo $quartile_filter === 'Q4' ? 'selected' : ''; ?>>Q4</option>
            </select>
            
            <select name="sort" class="filter-select" onchange="updateFilters()">
                <option value="recent" <?php echo $sort === 'recent' ? 'selected' : ''; ?>><?php echo __('journal.sort_recent'); ?></option>
                <option value="alpha" <?php echo $sort === 'alpha' ? 'selected' : ''; ?>><?php echo __('journal.sort_alpha'); ?></option>
                <option value="sjr" <?php echo $sort === 'sjr' ? 'selected' : ''; ?>><?php echo __('journal.sort_sjr'); ?></option>
            </select>
            
            <?php if (!empty($journals)): ?>
                <span style="margin-left: auto; color: var(--gray-600);">
                    <strong><?php echo count($journals); ?></strong> <?php echo strtolower(__('journal.total_count')); ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Journal Grid -->
    <?php if (!empty($journals)): ?>
        <div class="journal-grid">
            <?php foreach ($journals as $journal): ?>
                <div class="journal-card <?php echo strtolower($journal['quartile'] ?? ''); ?>">
                    <h3 class="journal-card-title">
                        <?php echo htmlspecialchars($journal['title']); ?>
                    </h3>
                    
                    <div class="journal-card-meta">
                        <?php if (!empty($journal['quartile'])): ?>
                            <span class="journal-badge badge-<?php echo strtolower($journal['quartile']); ?>">
                                <?php echo htmlspecialchars($journal['quartile']); ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if (!empty($journal['sjr'])): ?>
                            <span class="journal-badge badge-sjr">
                                SJR: <?php echo number_format($journal['sjr'], 3); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <p class="journal-card-publisher">
                        <i class="fas fa-building"></i> <?php echo htmlspecialchars($journal['publisher'] ?? 'N/A'); ?>
                        <?php if (!empty($journal['country'])): ?>
                            • <?php echo htmlspecialchars($journal['country']); ?>
                        <?php endif; ?>
                    </p>
                    
                    <?php if (!empty($journal['subjects'])): ?>
                        <p class="journal-card-subjects">
                            <?php echo htmlspecialchars($journal['subjects']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <div class="journal-card-footer">
                        <span class="journal-date">
                            <i class="fas fa-clock"></i> 
                            <?php 
                            if (!empty($journal['last_fetched'])) {
                                echo date('M d, Y', strtotime($journal['last_fetched']));
                            } else {
                                echo __('journal.last_checked');
                            }
                            ?>
                        </span>
                        
                        <a href="index.php?page=journal-profile&issn=<?php echo urlencode($journal['issn']); ?>" 
                           class="btn-view-profile">
                            <?php echo __('journal.view_profile'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($page_num > 1 || $has_more): ?>
            <div class="pagination">
                <?php if ($page_num > 1): ?>
                    <a href="?page=<?php echo $page; ?>&page=<?php echo $page_num - 1; ?><?php echo !empty($search) ? '&q=' . urlencode($search) : ''; ?><?php echo !empty($quartile_filter) ? '&quartile=' . $quartile_filter : ''; ?><?php echo $sort !== 'recent' ? '&sort=' . $sort : ''; ?>" 
                       class="pagination-btn">
                        <i class="fas fa-chevron-left"></i> Prev
                    </a>
                <?php endif; ?>
                
                <span class="pagination-btn active"><?php echo $page_num; ?></span>
                
                <?php if ($has_more): ?>
                    <a href="?page=<?php echo $page; ?>&page=<?php echo $page_num + 1; ?><?php echo !empty($search) ? '&q=' . urlencode($search) : ''; ?><?php echo !empty($quartile_filter) ? '&quartile=' . $quartile_filter : ''; ?><?php echo $sort !== 'recent' ? '&sort=' . $sort : ''; ?>" 
                       class="pagination-btn">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-book"></i>
            <h3><?php echo __('journal.no_results'); ?></h3>
            <p><?php echo __('journal.search_another'); ?></p>
        </div>
    <?php endif; ?>
</div>

<script>
function updateFilters() {
    const url = new URL(window.location);
    const quartile = document.querySelector('select[name="quartile"]').value;
    const sort = document.querySelector('select[name="sort"]').value;
    
    if (quartile) {
        url.searchParams.set('quartile', quartile);
    } else {
        url.searchParams.delete('quartile');
    }
    
    if (sort !== 'recent') {
        url.searchParams.set('sort', sort);
    } else {
        url.searchParams.delete('sort');
    }
    
    window.location.href = url.toString();
}
</script>

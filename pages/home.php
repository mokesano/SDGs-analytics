<?php
/**
 * SDG Frontend - Home Page
 * Halaman utama untuk analisis SDG - versi yang berfungsi
 * 
 * @version 5.1.8
 * @author Rochmady and Wizdam Team
 * @license MIT
 */

// Inisialisasi variabel
$analysis_result = null;
$error_message = null;
$processing_time = 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['input_value'])) {
    $start_time = microtime(true);
    $input_value = trim($_POST['input_value']);
    
    try {
        // Validasi input
        if (validateOrcid($input_value) || validateDoi($input_value)) {
            $is_orcid = validateOrcid($input_value);
            
            if ($is_orcid) {
                $analysis_result = processOrcidAnalysis($input_value);
            } else {
                $analysis_result = processDoiAnalysis($input_value);
            }
        } else {
            $error_message = 'Please enter a valid ORCID ID (0000-0000-0000-0000) or DOI (10.xxxx/xxxxx)';
        }
    } catch (Exception $e) {
        $error_message = 'Analysis error: ' . $e->getMessage();
    }
    
    $processing_time = round(microtime(true) - $start_time, 2);
}

// Generate CSRF token
$csrf_token = generateCsrfToken();
?>

<div class="container">
    <!-- Header Section -->
    <div class="header">
        <h1><i class="fas fa-chart-line"></i> Welcome! Wizdam AI-sikola</h1>
        <h2>Sustainable Development Goals (SDGs) Classification Analysis</h2>
        <p>Advanced AI-powered platform for analyzing research contributions to the United Nations Sustainable Development Goals</p>
    </div>

    <!-- Main Form -->
    <div class="search-card">
        <h2><i class="fas fa-search"></i> Analyze Research Contributions</h2>
        <p>Enter an ORCID ID or DOI to analyze research contributions to the 17 Sustainable Development Goals</p>
        
        <form method="POST" action="" class="analysis-form">
            <input type="hidden" name="_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label for="input_value">Enter ORCID ID or DOI:</label>
                <input 
                    type="text" 
                    id="input_value" 
                    name="input_value" 
                    class="form-input" 
                    placeholder="e.g., 0000-0002-1825-0097 or 10.1038/nature12373"
                    value="<?php echo isset($_POST['input_value']) ? htmlspecialchars($_POST['input_value']) : ''; ?>"
                    required
                >
                <div class="input-help">
                    <strong>ORCID ID:</strong> 0000-0000-0000-0000 format | 
                    <strong>DOI:</strong> 10.xxxx/xxxxx format
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-chart-bar"></i> Analyze Now
            </button>
        </form>
    </div>

    <!-- Error Message -->
    <?php if ($error_message): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
        <?php if ($processing_time > 0): ?>
        <br><small>Processing time: <?php echo $processing_time; ?>s</small>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Results Section -->
    <?php if ($analysis_result): ?>
    <div class="results-container">
        <h2><i class="fas fa-chart-line"></i> Analysis Results</h2>
        
        <!-- ORCID Results -->
        <?php if (isset($analysis_result['personal_info'])): ?>
        <div class="profile-section">
            <h3><i class="fas fa-user"></i> Researcher Profile</h3>
            <div class="researcher-info">
                <div class="researcher-name">
                    <?php echo htmlspecialchars($analysis_result['personal_info']['name'] ?? 'Unknown Researcher'); ?>
                </div>
                <?php if (!empty($analysis_result['personal_info']['affiliation'])): ?>
                <div class="researcher-affiliation">
                    <i class="fas fa-university"></i>
                    <?php echo htmlspecialchars($analysis_result['personal_info']['affiliation']); ?>
                </div>
                <?php endif; ?>
                <div class="researcher-orcid">
                    <i class="fab fa-orcid"></i>
                    <a href="https://orcid.org/<?php echo htmlspecialchars($analysis_result['personal_info']['orcid']); ?>" target="_blank">
                        <?php echo htmlspecialchars($analysis_result['personal_info']['orcid']); ?>
                    </a>
                </div>
            </div>

            <!-- SDG Summary -->
            <?php if (!empty($analysis_result['sdg_summary'])): ?>
            <div class="sdg-summary">
                <h4><i class="fas fa-bullseye"></i> SDG Contribution Summary</h4>
                <div class="sdg-grid">
                    <?php foreach ($analysis_result['sdg_summary'] as $sdg => $summary): ?>
                        <?php if (($summary['work_count'] ?? 0) > 0): ?>
                            <?php $sdg_info = getSdgInfo($sdg); ?>
                            <?php if ($sdg_info): ?>
                            <div class="sdg-card" style="border-left: 4px solid <?php echo $sdg_info['color']; ?>">
                                <div class="sdg-title"><?php echo $sdg; ?>: <?php echo htmlspecialchars($sdg_info['title']); ?></div>
                                <div class="sdg-stats">
                                    <span><?php echo $summary['work_count']; ?> publications</span>
                                    <?php if (isset($summary['avg_confidence'])): ?>
                                    <span>Avg: <?php echo formatScore($summary['avg_confidence']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Chart Canvas -->
            <div class="chart-section">
                <h4><i class="fas fa-chart-pie"></i> SDG Distribution</h4>
                <canvas id="sdgChart" width="400" height="300"></canvas>
            </div>
        </div>
        <?php endif; ?>

        <!-- DOI Results -->
        <?php if (isset($analysis_result['doi']) && !isset($analysis_result['personal_info'])): ?>
        <div class="article-section">
            <h3><i class="fas fa-file-alt"></i> Article Analysis</h3>
            
            <div class="article-info">
                <div class="article-title">
                    <?php echo htmlspecialchars($analysis_result['title'] ?? 'Unknown Title'); ?>
                </div>
                
                <?php if (!empty($analysis_result['authors'])): ?>
                <div class="article-authors">
                    <i class="fas fa-users"></i>
                    <?php echo htmlspecialchars(implode(', ', array_slice($analysis_result['authors'], 0, 3))); ?>
                    <?php if (count($analysis_result['authors']) > 3): ?>
                        <span>et al.</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="article-meta">
                    <?php if (!empty($analysis_result['published'])): ?>
                    <span><i class="fas fa-calendar"></i> <?php echo htmlspecialchars($analysis_result['published']); ?></span>
                    <?php endif; ?>
                    
                    <?php if (!empty($analysis_result['journal'])): ?>
                    <span><i class="fas fa-book"></i> <?php echo htmlspecialchars($analysis_result['journal']); ?></span>
                    <?php endif; ?>
                    
                    <span>
                        <i class="fas fa-link"></i>
                        <a href="https://doi.org/<?php echo htmlspecialchars($analysis_result['doi']); ?>" target="_blank">
                            <?php echo htmlspecialchars($analysis_result['doi']); ?>
                        </a>
                    </span>
                </div>
            </div>

            <!-- SDG Scores -->
            <?php if (!empty($analysis_result['sdg_scores'])): ?>
            <div class="sdg-scores">
                <h4><i class="fas fa-tags"></i> SDG Classification</h4>
                <?php 
                $sdg_scores = $analysis_result['sdg_scores'];
                arsort($sdg_scores);
                ?>
                <?php foreach ($sdg_scores as $sdg => $score): ?>
                    <?php if ($score > 0.1): ?>
                        <?php $sdg_info = getSdgInfo($sdg); ?>
                        <?php if ($sdg_info): ?>
                        <div class="score-item">
                            <div class="score-label">
                                <?php echo $sdg; ?>: <?php echo htmlspecialchars($sdg_info['title']); ?>
                            </div>
                            <div class="score-bar">
                                <div class="score-fill" style="width: <?php echo ($score * 100); ?>%; background-color: <?php echo $sdg_info['color']; ?>"></div>
                            </div>
                            <div class="score-value"><?php echo formatScore($score); ?></div>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Works List -->
        <?php if (!empty($analysis_result['works'])): ?>
        <div class="works-section">
            <h3><i class="fas fa-list"></i> Publications (<?php echo count($analysis_result['works']); ?>)</h3>
            
            <?php foreach ($analysis_result['works'] as $index => $work): ?>
            <div class="work-item">
                <div class="work-title">
                    <?php echo htmlspecialchars($work['title'] ?? 'Untitled Work'); ?>
                </div>
                
                <div class="work-meta">
                    <?php if (!empty($work['year'])): ?>
                    <span><i class="fas fa-calendar"></i> <?php echo htmlspecialchars($work['year']); ?></span>
                    <?php endif; ?>
                    
                    <?php if (!empty($work['journal'])): ?>
                    <span><i class="fas fa-book"></i> <?php echo htmlspecialchars($work['journal']); ?></span>
                    <?php endif; ?>
                    
                    <?php if (!empty($work['doi'])): ?>
                    <span>
                        <i class="fas fa-link"></i>
                        <a href="https://doi.org/<?php echo htmlspecialchars($work['doi']); ?>" target="_blank">DOI</a>
                    </span>
                    <?php endif; ?>
                </div>

                <!-- SDG Badges -->
                <?php if (!empty($work['sdg_scores'])): ?>
                <div class="work-sdgs">
                    <?php 
                    $work_scores = $work['sdg_scores'];
                    arsort($work_scores);
                    $top_sdgs = array_slice($work_scores, 0, 5, true);
                    ?>
                    <?php foreach ($top_sdgs as $sdg => $score): ?>
                        <?php if ($score > 0.2): ?>
                            <?php $sdg_info = getSdgInfo($sdg); ?>
                            <?php if ($sdg_info): ?>
                            <span class="sdg-badge" style="background-color: <?php echo $sdg_info['color']; ?>">
                                <?php echo $sdg; ?>: <?php echo formatScore($score); ?>
                            </span>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Detailed Analysis -->
                <div class="detailed-analysis" style="display: none;" id="analysis-<?php echo $index; ?>">
                    <?php
                    $analysisData = isset($work['detailed_analysis']) ? $work['detailed_analysis'] : 
                                   (isset($work['sdg_analysis']) ? $work['sdg_analysis'] : []);
                    
                    if (!empty($analysisData)) {
                        foreach ($analysisData as $sdg => $analysis) {
                            $sdg_info = getSdgInfo($sdg);
                            if ($sdg_info) {
                                echo '<div class="analysis-section">';
                                echo '<h5 style="color: ' . $sdg_info['color'] . '">' . 
                                     htmlspecialchars($sdg . ': ' . $sdg_info['title']) . '</h5>';
                                
                                if (isset($analysis['explanation'])) {
                                    echo '<div class="explanation">' . 
                                         renderSafeHtml($analysis['explanation']) . '</div>';
                                }
                                
                                echo '</div>';
                            }
                        }
                    } else {
                        echo '<p>No detailed analysis available for this work.</p>';
                    }
                    ?>
                </div>

                <button class="btn btn-sm" onclick="toggleDetails(<?php echo $index; ?>)">
                    <i class="fas fa-chevron-down"></i> Show Details
                </button>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Processing Info -->
        <div class="processing-info">
            <small>
                <i class="fas fa-clock"></i> Analysis completed in <?php echo $processing_time; ?>s
                | <i class="fas fa-calendar"></i> <?php echo date('Y-m-d H:i:s'); ?>
            </small>
        </div>
    </div>
    <?php endif; ?>

    <!-- Example Section -->
    <div class="examples-section">
        <h3><i class="fas fa-lightbulb"></i> Example Identifiers</h3>
        <div class="examples-grid">
            <div class="example-item" onclick="useExample('0000-0002-1825-0097')">
                <strong>ORCID Example:</strong><br>
                0000-0002-1825-0097<br>
                <small>Researcher with multiple publications</small>
            </div>
            <div class="example-item" onclick="useExample('10.1038/nature12373')">
                <strong>DOI Example:</strong><br>
                10.1038/nature12373<br>
                <small>Single research article</small>
            </div>
        </div>
    </div>

    <!-- SDG Information -->
    <div class="sdg-info">
        <h3><i class="fas fa-globe"></i> About Sustainable Development Goals</h3>
        <p>The 17 SDGs are a universal call to action to end poverty, protect the planet, and ensure peace and prosperity for all by 2030.</p>
        
        <div class="sdg-mini-grid">
            <?php foreach ($SDG_DEFINITIONS as $sdg_code => $sdg_info): ?>
            <div class="sdg-mini-card" title="<?php echo htmlspecialchars($sdg_info['description']); ?>">
                <span class="sdg-number" style="background-color: <?php echo $sdg_info['color']; ?>">
                    <?php echo $sdg_info['number']; ?>
                </span>
                <span class="sdg-mini-title"><?php echo htmlspecialchars($sdg_info['title']); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
function useExample(value) {
    document.getElementById('input_value').value = value;
}

function toggleDetails(index) {
    const details = document.getElementById('analysis-' + index);
    const button = details.previousElementSibling;
    const icon = button.querySelector('i');
    
    if (details.style.display === 'none') {
        details.style.display = 'block';
        icon.className = 'fas fa-chevron-up';
        button.innerHTML = '<i class="fas fa-chevron-up"></i> Hide Details';
    } else {
        details.style.display = 'none';
        icon.className = 'fas fa-chevron-down';
        button.innerHTML = '<i class="fas fa-chevron-down"></i> Show Details';
    }
}

// Initialize chart if data exists
<?php if ($analysis_result && isset($analysis_result['sdg_summary'])): ?>
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('sdgChart');
    if (canvas && typeof Chart !== 'undefined') {
        const ctx = canvas.getContext('2d');
        
        const data = {
            labels: <?php echo json_encode(array_keys($analysis_result['sdg_summary'])); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($analysis_result['sdg_summary'], 'work_count')); ?>,
                backgroundColor: <?php echo json_encode(array_map(function($sdg) { return getSdgColor($sdg); }, array_keys($analysis_result['sdg_summary']))); ?>,
                borderWidth: 2,
                borderColor: '#fff'
            }]
        };
        
        new Chart(ctx, {
            type: 'pie',
            data: data,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: true,
                        text: 'SDG Distribution'
                    }
                }
            }
        });
    }
});
<?php endif; ?>

// Auto-scroll to results
<?php if ($analysis_result): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.results-container').scrollIntoView({ 
        behavior: 'smooth', 
        block: 'start' 
    });
});
<?php endif; ?>
</script>
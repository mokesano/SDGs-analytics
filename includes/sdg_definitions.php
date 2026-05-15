<?php
/**
 * SDG Frontend - SDG Definitions
 * Definisi lengkap 17 Sustainable Development Goals sesuai standar UN
 * 
 * @version 1.0.0 (PHP 7.4+ Compatible)
 * @author Rochmady and Wizdam Team
 * @license MIT
 */

// ==============================================
// DEFINISI 17 SDGs DENGAN DATA LENGKAP
// ==============================================

// Define as global variable for backward compatibility
$SDG_DEFINITIONS = [
    'SDG1' => [
        'number' => 1,
        'title' => 'No Poverty',
        'description' => 'End poverty in all its forms everywhere',
        'color' => '#e5243b',
        'svg_url' => 'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_1.svg',
        'targets' => [
            '1.1' => 'Eradicate extreme poverty',
            '1.2' => 'Reduce poverty by at least 50%',
            '1.3' => 'Implement social protection systems',
            '1.4' => 'Ensure equal rights to economic resources',
            '1.5' => 'Build resilience of the poor'
        ],
        'keywords' => ['poverty', 'extreme poverty', 'social protection', 'economic inclusion', 'vulnerability']
    ],
    
    'SDG2' => [
        'number' => 2,
        'title' => 'Zero Hunger',
        'description' => 'End hunger, achieve food security and improved nutrition and promote sustainable agriculture',
        'color' => '#dda63a',
        'svg_url' => 'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_2.svg',
        'targets' => [
            '2.1' => 'End hunger and malnutrition',
            '2.2' => 'End all forms of malnutrition',
            '2.3' => 'Double agricultural productivity',
            '2.4' => 'Ensure sustainable food production',
            '2.5' => 'Maintain genetic diversity'
        ],
        'keywords' => ['hunger', 'food security', 'nutrition', 'agriculture', 'sustainable farming']
    ],
    
    'SDG3' => [
        'number' => 3,
        'title' => 'Good Health and Well-being',
        'description' => 'Ensure healthy lives and promote well-being for all at all ages',
        'color' => '#4c9f38',
        'svg_url' => 'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_3.svg',
        'targets' => [
            '3.1' => 'Reduce maternal mortality',
            '3.2' => 'End preventable deaths of children',
            '3.3' => 'End epidemics of major diseases',
            '3.4' => 'Reduce mortality from non-communicable diseases',
            '3.5' => 'Strengthen prevention of substance abuse'
        ],
        'keywords' => ['health', 'healthcare', 'disease', 'mortality', 'well-being', 'medical']
    ],
    
    'SDG4' => [
        'number' => 4,
        'title' => 'Quality Education',
        'description' => 'Ensure inclusive and equitable quality education and promote lifelong learning opportunities for all',
        'color' => '#c5192d',
        'svg_url' => 'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_4.svg',
        'targets' => [
            '4.1' => 'Free primary and secondary education',
            '4.2' => 'Access to pre-primary education',
            '4.3' => 'Equal access to technical and higher education',
            '4.4' => 'Increase relevant skills for employment',
            '4.5' => 'Eliminate gender disparities in education'
        ],
        'keywords' => ['education', 'learning', 'school', 'literacy', 'skills', 'training']
    ],
    
    'SDG5' => [
        'number' => 5,
        'title' => 'Gender Equality',
        'description' => 'Achieve gender equality and empower all women and girls',
        'color' => '#ff3a21',
        'svg_url' => 'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_5.svg',
        'targets' => [
            '5.1' => 'End discrimination against women and girls',
            '5.2' => 'Eliminate violence against women and girls',
            '5.3' => 'Eliminate harmful practices',
            '5.4' => 'Value unpaid care and domestic work',
            '5.5' => 'Ensure full participation in leadership'
        ],
        'keywords' => ['gender', 'women', 'equality', 'empowerment', 'discrimination', 'violence']
    ],
    
    'SDG6' => [
        'number' => 6,
        'title' => 'Clean Water and Sanitation',
        'description' => 'Ensure availability and sustainable management of water and sanitation for all',
        'color' => '#26bde2',
        'svg_url' => 'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_6.svg',
        'targets' => [
            '6.1' => 'Safe and affordable drinking water',
            '6.2' => 'Access to sanitation and hygiene',
            '6.3' => 'Improve water quality',
            '6.4' => 'Increase water-use efficiency',
            '6.5' => 'Implement integrated water resources management'
        ],
        'keywords' => ['water', 'sanitation', 'hygiene', 'clean water', 'water management', 'wastewater']
    ],
    
    'SDG7' => [
        'number' => 7,
        'title' => 'Affordable and Clean Energy',
        'description' => 'Ensure access to affordable, reliable, sustainable and modern energy for all',
        'color' => '#fcc30b',
        'svg_url' => 'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_7.svg',
        'targets' => [
            '7.1' => 'Universal access to modern energy',
            '7.2' => 'Increase share of renewable energy',
            '7.3' => 'Double the rate of energy efficiency',
            '7.a' => 'Enhance international cooperation',
            '7.b' => 'Expand and upgrade energy services'
        ],
        'keywords' => ['energy', 'renewable energy', 'clean energy', 'electricity', 'solar', 'wind']
    ],
    
    'SDG8' => [
        'number' => 8,
        'title' => 'Decent Work and Economic Growth',
        'description' => 'Promote sustained, inclusive and sustainable economic growth, full and productive employment and decent work for all',
        'color' => '#a21942',
        'svg_url' => 'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_8.svg',
        'targets' => [
            '8.1' => 'Sustain economic growth',
            '8.2' => 'Achieve higher productivity through innovation',
            '8.3' => 'Promote policies for decent job creation',
            '8.4' => 'Improve resource efficiency',
            '8.5' => 'Full employment and decent work'
        ],
        'keywords' => ['employment', 'economic growth', 'jobs', 'work', 'productivity', 'entrepreneurship']
    ],
    
    'SDG9' => [
        'number' => 9,
        'title' => 'Industry, Innovation and Infrastructure',
        'description' => 'Build resilient infrastructure, promote inclusive and sustainable industrialization and foster innovation',
        'color' => '#fd6925',
        'svg_url' => 'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_9.svg',
        'targets' => [
            '9.1' => 'Develop sustainable infrastructure',
            '9.2' => 'Promote inclusive industrialization',
            '9.3' => 'Increase access to financial services',
            '9.4' => 'Upgrade infrastructure for sustainability',
            '9.5' => 'Enhance scientific research and innovation'
        ],
        'keywords' => ['infrastructure', 'innovation', 'industry', 'technology', 'research', 'development']
    ],
    
    'SDG10' => [
        'number' => 10,
        'title' => 'Reduced Inequalities',
        'description' => 'Reduce inequality within and among countries',
        'color' => '#dd1367',
        'svg_url' => 'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_10.svg',
        'targets' => [
            '10.1' => 'Reduce income inequalities',
            '10.2' => 'Promote universal social, economic and political inclusion',
            '10.3' => 'Ensure equal opportunity',
            '10.4' => 'Adopt fiscal and social policies for equality',
            '10.5' => 'Improve regulation of financial markets'
        ],
        'keywords' => ['inequality', 'inclusion', 'discrimination', 'social protection', 'migration']
    ],
    
    'SDG11' => [
        'number' => 11,
        'title' => 'Sustainable Cities and Communities',
        'description' => 'Make cities and human settlements inclusive, safe, resilient and sustainable',
        'color' => '#fd9d24',
        'svg_url' => 'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_11.svg',
        'targets' => [
            '11.1' => 'Safe and affordable housing',
            '11.2' => 'Affordable and sustainable transport',
            '11.3' => 'Inclusive and sustainable urbanization',
            '11.4' => 'Protect cultural and natural heritage',
            '11.5' => 'Reduce deaths from disasters'
        ],
        'keywords' => ['cities', 'urban', 'housing', 'transport', 'sustainable development', 'planning']
    ],
    
    'SDG12' => [
        'number' => 12,
        'title' => 'Responsible Consumption and Production',
        'description' => 'Ensure sustainable consumption and production patterns',
        'color' => '#bf8b2e',
        'svg_url' => 'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_12.svg',
        'targets' => [
            '12.1' => 'Implement sustainable consumption and production',
            '12.2' => 'Sustainable management of natural resources',
            '12.3' => 'Halve per capita global food waste',
            '12.4' => 'Responsible management of chemicals and waste',
            '12.5' => 'Substantially reduce waste generation'
        ],
        'keywords' => ['consumption', 'production', 'waste', 'recycling', 'sustainability', 'circular economy']
    ],
    
    'SDG13' => [
        'number' => 13,
        'title' => 'Climate Action',
        'description' => 'Take urgent action to combat climate change and its impacts',
        'color' => '#3f7e44',
        'svg_url' => 'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_13.svg',
        'targets' => [
            '13.1' => 'Strengthen resilience to climate hazards',
            '13.2' => 'Integrate climate change measures',
            '13.3' => 'Improve education on climate change',
            '13.a' => 'Implement climate commitments',
            '13.b' => 'Promote mechanisms for climate planning'
        ],
        'keywords' => ['climate change', 'global warming', 'greenhouse gas', 'emissions', 'adaptation', 'mitigation']
    ],
    
    'SDG14' => [
        'number' => 14,
        'title' => 'Life Below Water',
        'description' => 'Conserve and sustainably use the oceans, seas and marine resources for sustainable development',
        'color' => '#0a97d9',
        'svg_url' => 'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_14.svg',
        'targets' => [
            '14.1' => 'Reduce marine pollution',
            '14.2' => 'Protect marine and coastal ecosystems',
            '14.3' => 'Minimize ocean acidification',
            '14.4' => 'Regulate fishing and end overfishing',
            '14.5' => 'Conserve coastal and marine areas'
        ],
        'keywords' => ['ocean', 'marine', 'sea', 'fishing', 'pollution', 'coral reef', 'biodiversity']
    ],
    
    'SDG15' => [
        'number' => 15,
        'title' => 'Life on Land',
        'description' => 'Protect, restore and promote sustainable use of terrestrial ecosystems, sustainably manage forests, combat desertification, and halt and reverse land degradation and halt biodiversity loss',
        'color' => '#56c02b',
        'svg_url' => 'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_15.svg',
        'targets' => [
            '15.1' => 'Conserve and restore terrestrial ecosystems',
            '15.2' => 'End deforestation and restore forests',
            '15.3' => 'Combat desertification',
            '15.4' => 'Conserve mountain ecosystems',
            '15.5' => 'Reduce degradation of natural habitats'
        ],
        'keywords' => ['forest', 'biodiversity', 'ecosystem', 'wildlife', 'conservation', 'deforestation']
    ],
    
    'SDG16' => [
        'number' => 16,
        'title' => 'Peace, Justice and Strong Institutions',
        'description' => 'Promote peaceful and inclusive societies for sustainable development, provide access to justice for all and build effective, accountable and inclusive institutions at all levels',
        'color' => '#00689d',
        'svg_url' => 'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_16.svg',
        'targets' => [
            '16.1' => 'Reduce violence everywhere',
            '16.2' => 'End abuse and exploitation of children',
            '16.3' => 'Promote rule of law and equal access to justice',
            '16.4' => 'Reduce illicit financial and arms flows',
            '16.5' => 'Substantially reduce corruption and bribery'
        ],
        'keywords' => ['peace', 'justice', 'institutions', 'governance', 'corruption', 'violence', 'rule of law']
    ],
    
    'SDG17' => [
        'number' => 17,
        'title' => 'Partnerships for the Goals',
        'description' => 'Strengthen the means of implementation and revitalize the global partnership for sustainable development',
        'color' => '#19486a',
        'svg_url' => 'https://assets.sangia.org/img/SDGs_icon_SVG/Artboard_17.svg',
        'targets' => [
            '17.1' => 'Strengthen domestic resource mobilization',
            '17.2' => 'Implement all development assistance commitments',
            '17.3' => 'Mobilize financial resources for developing countries',
            '17.4' => 'Assist developing countries in debt sustainability',
            '17.5' => 'Adopt investment promotion regimes'
        ],
        'keywords' => ['partnership', 'cooperation', 'development', 'finance', 'technology transfer', 'capacity building']
    ]
];

// ==============================================
// FUNGSI HELPER UNTUK SDG DEFINITIONS
// ==============================================

/**
 * Get SDG information by SDG code
 */
function getSdgInfo($sdg_code) {
    global $SDG_DEFINITIONS;
    return isset($SDG_DEFINITIONS[$sdg_code]) ? $SDG_DEFINITIONS[$sdg_code] : null;
}

/**
 * Get all SDG definitions
 */
function getAllSdgDefinitions() {
    global $SDG_DEFINITIONS;
    return $SDG_DEFINITIONS;
}

/**
 * Get SDG by number
 */
function getSdgByNumber($number) {
    global $SDG_DEFINITIONS;
    foreach ($SDG_DEFINITIONS as $code => $info) {
        if ($info['number'] == $number) {
            return array_merge(['code' => $code], $info);
        }
    }
    return null;
}

/**
 * Search SDGs by keyword
 */
function searchSdgsByKeyword($keyword) {
    global $SDG_DEFINITIONS;
    $results = [];
    $keyword = strtolower($keyword);
    
    foreach ($SDG_DEFINITIONS as $code => $info) {
        // Search in title
        if (stripos($info['title'], $keyword) !== false) {
            $results[$code] = $info;
            continue;
        }
        
        // Search in description
        if (stripos($info['description'], $keyword) !== false) {
            $results[$code] = $info;
            continue;
        }
        
        // Search in keywords
        foreach ($info['keywords'] as $sdg_keyword) {
            if (stripos($sdg_keyword, $keyword) !== false) {
                $results[$code] = $info;
                break;
            }
        }
    }
    
    return $results;
}

/**
 * Get SDG color by code
 */
function getSdgColor($sdg_code) {
    $info = getSdgInfo($sdg_code);
    return $info ? $info['color'] : '#cccccc';
}

/**
 * Get SDG title by code
 */
function getSdgTitle($sdg_code) {
    $info = getSdgInfo($sdg_code);
    return $info ? $info['title'] : 'Unknown SDG';
}

/**
 * Get SDG SVG URL by code
 */
function getSdgSvgUrl($sdg_code) {
    $info = getSdgInfo($sdg_code);
    return $info ? $info['svg_url'] : '';
}

/**
 * Validate SDG code
 */
function isValidSdgCode($sdg_code) {
    global $SDG_DEFINITIONS;
    return isset($SDG_DEFINITIONS[$sdg_code]);
}

/**
 * Get SDG keywords for text analysis
 */
function getSdgKeywords($sdg_code) {
    $info = getSdgInfo($sdg_code);
    return $info ? $info['keywords'] : [];
}

/**
 * Get all SDG codes
 */
function getAllSdgCodes() {
    global $SDG_DEFINITIONS;
    return array_keys($SDG_DEFINITIONS);
}

/**
 * Format SDG code for display
 */
function formatSdgCode($sdg_code) {
    $info = getSdgInfo($sdg_code);
    if (!$info) return $sdg_code;
    
    return sprintf('SDG %d: %s', $info['number'], $info['title']);
}

/**
 * Get SDG targets by code
 */
function getSdgTargets($sdg_code) {
    $info = getSdgInfo($sdg_code);
    return $info ? $info['targets'] : [];
}

/**
 * Generate SDG badge HTML
 */
function generateSdgBadge($sdg_code, $score = null, $show_score = true) {
    $info = getSdgInfo($sdg_code);
    if (!$info) return '';
    
    $score_text = '';
    if ($show_score && $score !== null) {
        $score_text = sprintf(' (%.1f%%)', $score * 100);
    }
    
    return sprintf(
        '<span class="sdg-badge" style="background-color: %s; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; margin: 2px;">%s%s</span>',
        $info['color'],
        $sdg_code,
        $score_text
    );
}

/**
 * Get SDG statistics
 */
function getSdgStatistics() {
    global $SDG_DEFINITIONS;
    
    return [
        'total_sdgs' => count($SDG_DEFINITIONS),
        'total_targets' => array_sum(array_map(function($sdg) {
            return count($sdg['targets']);
        }, $SDG_DEFINITIONS)),
        'total_keywords' => array_sum(array_map(function($sdg) {
            return count($sdg['keywords']);
        }, $SDG_DEFINITIONS))
    ];
}

/**
 * Export SDG definitions to JSON
 */
function exportSdgDefinitionsToJson() {
    global $SDG_DEFINITIONS;
    return json_encode($SDG_DEFINITIONS, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

/**
 * Get random SDG for testing/demo
 */
function getRandomSdg() {
    global $SDG_DEFINITIONS;
    $codes = array_keys($SDG_DEFINITIONS);
    $random_code = $codes[array_rand($codes)];
    return array_merge(['code' => $random_code], $SDG_DEFINITIONS[$random_code]);
}

// ==============================================
// SDG MAPPING UNTUK ANALISIS
// ==============================================

/**
 * Extended keyword mapping untuk analisis yang lebih akurat
 */
$SDG_KEYWORD_MAPPING = [
    'SDG1' => [
        'primary' => ['poverty', 'poor', 'income', 'economic hardship', 'financial vulnerability'],
        'secondary' => ['social protection', 'welfare', 'livelihood', 'economic inclusion', 'basic needs'],
        'indicators' => ['poverty rate', 'income distribution', 'social safety net', 'economic mobility']
    ],
    'SDG2' => [
        'primary' => ['hunger', 'food security', 'nutrition', 'malnutrition', 'agriculture'],
        'secondary' => ['farming', 'crop', 'livestock', 'food production', 'dietary'],
        'indicators' => ['food access', 'nutritional status', 'agricultural productivity', 'food systems']
    ],
    'SDG3' => [
        'primary' => ['health', 'disease', 'medical', 'healthcare', 'mortality'],
        'secondary' => ['hospital', 'treatment', 'medicine', 'prevention', 'epidemic'],
        'indicators' => ['life expectancy', 'disease prevalence', 'health coverage', 'medical access']
    ],
    // ... Continue untuk SDG lainnya sesuai kebutuhan analisis
];

/**
 * Get extended keyword mapping for analysis
 */
function getSdgKeywordMapping($sdg_code = null) {
    global $SDG_KEYWORD_MAPPING;
    
    if ($sdg_code) {
        return isset($SDG_KEYWORD_MAPPING[$sdg_code]) ? $SDG_KEYWORD_MAPPING[$sdg_code] : [];
    }
    
    return $SDG_KEYWORD_MAPPING;
}

// ==============================================
// ALIAS FOR BACKWARD COMPATIBILITY
// ==============================================
// Test suite expects $SDG_KEYWORDS array
$SDG_KEYWORDS = [];
foreach ($SDG_DEFINITIONS as $code => $definition) {
    $SDG_KEYWORDS[$code] = $definition['keywords'];
}

?>
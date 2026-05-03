<?php
/**
 * components/sdg-badge.php — Reusable SDG Badge Component
 *
 * Expected variables (set before including):
 *   $sdg_code         string   e.g. 'SDG3'
 *   $contributor_type string   optional, e.g. 'Active Contributor'
 *   $confidence_score float    optional, 0-1
 *   $size             string   'sm' | 'md' | 'lg'  (default 'md')
 */

$_sdg_colors = [
    'SDG1'  => '#E5243B',
    'SDG2'  => '#DDA63A',
    'SDG3'  => '#4C9F38',
    'SDG4'  => '#C5192D',
    'SDG5'  => '#FF3A21',
    'SDG6'  => '#26BDE2',
    'SDG7'  => '#FCC30B',
    'SDG8'  => '#A21942',
    'SDG9'  => '#FD6925',
    'SDG10' => '#DD1367',
    'SDG11' => '#FD9D24',
    'SDG12' => '#BF8B2E',
    'SDG13' => '#3F7E44',
    'SDG14' => '#0A97D9',
    'SDG15' => '#56C02B',
    'SDG16' => '#00689D',
    'SDG17' => '#19486A',
];

$_sdg_titles = [
    'SDG1'  => 'No Poverty',
    'SDG2'  => 'Zero Hunger',
    'SDG3'  => 'Good Health and Well-being',
    'SDG4'  => 'Quality Education',
    'SDG5'  => 'Gender Equality',
    'SDG6'  => 'Clean Water and Sanitation',
    'SDG7'  => 'Affordable and Clean Energy',
    'SDG8'  => 'Decent Work and Economic Growth',
    'SDG9'  => 'Industry, Innovation and Infrastructure',
    'SDG10' => 'Reduced Inequalities',
    'SDG11' => 'Sustainable Cities and Communities',
    'SDG12' => 'Responsible Consumption and Production',
    'SDG13' => 'Climate Action',
    'SDG14' => 'Life Below Water',
    'SDG15' => 'Life on Land',
    'SDG16' => 'Peace, Justice and Strong Institutions',
    'SDG17' => 'Partnerships for the Goals',
];

$_sdg_code  = isset($sdg_code) ? strtoupper(trim($sdg_code)) : '';
$_size      = isset($size) ? $size : 'md';
$_ct        = isset($contributor_type) ? $contributor_type : '';
$_conf      = isset($confidence_score) ? $confidence_score : null;

$_color     = isset($_sdg_colors[$_sdg_code])  ? $_sdg_colors[$_sdg_code]  : '#64748b';
$_title_str = isset($_sdg_titles[$_sdg_code])  ? $_sdg_titles[$_sdg_code]  : $_sdg_code;

$_number    = preg_replace('/[^0-9]/', '', $_sdg_code);

$_tooltip = $_title_str;
if ($_ct)   $_tooltip .= ' — ' . $_ct;
if ($_conf !== null) $_tooltip .= ' (' . round($_conf * 100) . '%)';

$_size_styles = [
    'sm' => 'font-size:.65rem;padding:.2rem .45rem;border-radius:4px;',
    'md' => 'font-size:.75rem;padding:.25rem .6rem;border-radius:5px;',
    'lg' => 'font-size:.875rem;padding:.35rem .8rem;border-radius:6px;',
];
$_style_extra = isset($_size_styles[$_size]) ? $_size_styles[$_size] : $_size_styles['md'];
?>
<span class="sdg-badge sdg-badge-<?= htmlspecialchars($_number) ?> sdg-badge-<?= htmlspecialchars($_size) ?>"
      title="<?= htmlspecialchars($_tooltip) ?>"
      style="background:<?= htmlspecialchars($_color) ?>;color:white;display:inline-block;font-weight:700;letter-spacing:.02em;line-height:1.2;white-space:nowrap;<?= $_style_extra ?>">
    SDG<?= htmlspecialchars($_number) ?>
</span>

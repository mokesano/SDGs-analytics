<?php
$page_title = 'Our Team';
$page_description = 'Meet the team behind Wizdam AI-sikola — researchers, developers, and designers working to advance SDG analytics.';

$team_members = [
    ['initials' => 'RC', 'name' => 'Rochmady', 'role' => 'Lead Developer & Researcher', 'bio' => 'Memimpin pengembangan platform Wizdam AI dan arsitektur analisis SDG berbasis NLP.', 'orcid' => '0000-0002-5152-9727', 'github' => 'mokesano'],
    ['initials' => 'WA', 'name' => 'Wizdam AI Team', 'role' => 'AI & NLP Engineering', 'bio' => 'Tim teknis yang bertanggung jawab atas algoritma klasifikasi SDG dan integrasi API multi-sumber.', 'orcid' => '', 'github' => 'wizdam-ai'],
    ['initials' => 'SR', 'name' => 'Sangia Research', 'role' => 'Research & Academic Advisory', 'bio' => 'PT. Sangia Research Media and Publishing — penyedia infrastruktur riset dan advisory akademik.', 'orcid' => '', 'github' => ''],
];
?>
<div class="page-header">
    <div class="container">
        <div class="section-label">About the Team</div>
        <h1 class="section-title">Meet Our Team</h1>
        <p class="section-subtitle">The researchers, developers, and academics behind Wizdam AI-sikola.</p>
    </div>
</div>

<section class="section">
    <div class="container">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1.5rem;margin-bottom:3rem;">
            <?php foreach ($team_members as $i => $member): ?>
            <div class="team-card reveal" style="transition-delay:<?= $i * 100 ?>ms;">
                <div class="team-avatar"><?= htmlspecialchars($member['initials']) ?></div>
                <div class="team-name"><?= htmlspecialchars($member['name']) ?></div>
                <div class="team-role"><?= htmlspecialchars($member['role']) ?></div>
                <div class="team-bio"><?= htmlspecialchars($member['bio']) ?></div>
                <div class="team-social">
                    <?php if ($member['orcid']): ?>
                    <a href="https://orcid.org/<?= htmlspecialchars($member['orcid']) ?>" target="_blank" rel="noopener" title="ORCID">
                        <i class="fab fa-orcid"></i>
                    </a>
                    <?php endif; ?>
                    <?php if ($member['github']): ?>
                    <a href="https://github.com/<?= htmlspecialchars($member['github']) ?>" target="_blank" rel="noopener" title="GitHub">
                        <i class="fab fa-github"></i>
                    </a>
                    <?php endif; ?>
                    <a href="?page=contact" title="Contact">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Join the team CTA -->
        <div class="section-dark" style="border-radius:var(--radius-xl,16px);padding:3rem;text-align:center;">
            <div class="section-label">Join Us</div>
            <h2 class="section-title-white" style="margin-bottom:1rem;">Interested in Contributing?</h2>
            <p style="color:rgba(255,255,255,.6);max-width:520px;margin:0 auto 2rem;line-height:1.7;">
                Kami selalu terbuka untuk kolaborasi dengan peneliti, pengembang, dan akademisi yang ingin memajukan platform analisis SDG.
            </p>
            <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
                <a href="?page=contact" class="btn btn-primary"><i class="fas fa-envelope"></i> Get in Touch</a>
                <a href="https://github.com/mokesano/SDGs-analytics" target="_blank" rel="noopener" class="btn btn-outline-white"><i class="fab fa-github"></i> GitHub Repository</a>
            </div>
        </div>
    </div>
</section>

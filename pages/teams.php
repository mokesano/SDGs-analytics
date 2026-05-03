<?php
$page_title = 'Tim Kami';
$page_description = 'Kenali para peneliti, engineer, dan akademisi di balik platform Wizdam AI SDG Classification.';

$team_members = [
    [
        'initials' => 'RC',
        'name' => 'Dr. Rochmady',
        'role' => 'Lead Researcher & AI Architect',
        'institution' => 'Universitas Iqra Buru',
        'bio' => 'Memimpin arsitektur platform Wizdam AI dan pengembangan algoritma klasifikasi SDG berbasis NLP. Peneliti aktif di bidang kecerdasan buatan untuk analisis teks akademik.',
        'color' => 'linear-gradient(135deg,#ff5627,#e03e18)',
        'orcid' => '0000-0002-5152-9727',
        'github' => 'mokesano',
        'linkedin' => '',
    ],
    [
        'initials' => 'AR',
        'name' => 'Dr. Andi Rahwan',
        'role' => 'Research Scientist & SDG Analyst',
        'institution' => 'PT. Sangia Research Media and Publishing',
        'bio' => 'Pakar analisis kebijakan SDG dan validasi model klasifikasi. Bertanggung jawab atas kualitas kategorisasi dan pengembangan dataset SDG berbasis UN AURORA.',
        'color' => 'linear-gradient(135deg,#1a2e45,#2d4a6e)',
        'orcid' => '',
        'github' => '',
        'linkedin' => '',
    ],
    [
        'initials' => 'FM',
        'name' => 'Faris Mubarok',
        'role' => 'Full Stack Developer & Platform Engineer',
        'institution' => 'Wizdam AI Team',
        'bio' => 'Bertanggung jawab atas pengembangan frontend dan backend platform, integrasi API multi-sumber (ORCID, CrossRef, OpenAlex), serta optimasi performa sistem.',
        'color' => 'linear-gradient(135deg,#6366f1,#4f46e5)',
        'orcid' => '',
        'github' => 'wizdam-ai',
        'linkedin' => '',
    ],
    [
        'initials' => 'NS',
        'name' => 'Nadia Safira',
        'role' => 'Data Science & NLP Specialist',
        'institution' => 'Wizdam AI Team',
        'bio' => 'Spesialis pemrosesan bahasa alami dan machine learning. Mengembangkan pipeline TF-IDF, cosine similarity, dan causal relationship analysis untuk meningkatkan akurasi klasifikasi.',
        'color' => 'linear-gradient(135deg,#10b981,#059669)',
        'orcid' => '',
        'github' => '',
        'linkedin' => '',
    ],
];
?>

<div class="page-header">
    <div class="container">
        <div class="section-label">Tim Kami</div>
        <h1 class="section-title">Orang-Orang di Balik Wizdam AI</h1>
        <p class="section-subtitle">Peneliti, engineer, dan akademisi yang berdedikasi membangun platform analisis SDG terdepan untuk komunitas riset Indonesia.</p>
    </div>
</div>

<section class="section">
    <div class="container">

        <!-- Team Cards -->
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.75rem;margin-bottom:3rem;">
            <?php foreach ($team_members as $i => $member): ?>
            <div class="team-card reveal" style="transition-delay:<?= $i * 100 ?>ms;">
                <div class="team-avatar" style="background:<?= $member['color'] ?>;"><?= htmlspecialchars($member['initials']) ?></div>
                <div class="team-name"><?= htmlspecialchars($member['name']) ?></div>
                <div class="team-role"><?= htmlspecialchars($member['role']) ?></div>
                <?php if ($member['institution']): ?>
                <div style="font-size:.8rem;color:var(--gray-400);margin-bottom:.75rem;"><i class="fas fa-university" style="margin-right:.35rem;"></i><?= htmlspecialchars($member['institution']) ?></div>
                <?php endif; ?>
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
                    <?php if ($member['linkedin']): ?>
                    <a href="https://linkedin.com/in/<?= htmlspecialchars($member['linkedin']) ?>" target="_blank" rel="noopener" title="LinkedIn">
                        <i class="fab fa-linkedin"></i>
                    </a>
                    <?php endif; ?>
                    <a href="?page=contact" title="Hubungi">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Institutional Partner -->
        <div class="card" style="margin-bottom:2rem;display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;">
            <div style="width:64px;height:64px;background:rgba(255,86,39,.1);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-building" style="font-size:1.75rem;color:#ff5627;"></i>
            </div>
            <div style="flex:1;min-width:200px;">
                <div style="font-size:.8rem;font-weight:700;color:#ff5627;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.25rem;">Lembaga Penyelenggara</div>
                <h3 style="font-size:1.1rem;color:var(--dark,#1a2e45);margin-bottom:.35rem;">PT. Sangia Research Media and Publishing</h3>
                <p style="font-size:.875rem;color:var(--gray-600);line-height:1.6;">
                    Lembaga riset dan penerbitan akademik berbasis di Indonesia yang menaungi pengembangan platform Wizdam AI, mendukung infrastruktur, dan memberikan advisory akademik.
                </p>
            </div>
        </div>

        <!-- Join CTA -->
        <div class="section-dark" style="border-radius:16px;padding:3rem;text-align:center;">
            <div class="section-label">Bergabung dengan Tim</div>
            <h2 style="color:white;font-size:1.5rem;margin:1rem 0;">Kami Selalu Mencari Bakat Terbaik</h2>
            <p style="color:rgba(255,255,255,.65);max-width:520px;margin:0 auto 2rem;line-height:1.7;">
                Kami selalu mencari peneliti, AI engineer, dan developer berbakat yang ingin membangun masa depan analisis riset akademik. Bergabunglah dengan misi kami.
            </p>
            <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
                <a href="?page=careers" class="btn btn-primary"><i class="fas fa-briefcase"></i> Lihat Posisi Terbuka</a>
                <a href="?page=contact" class="btn btn-outline-white"><i class="fas fa-envelope"></i> Hubungi Kami</a>
                <a href="https://github.com/mokesano/SDGs-analytics" target="_blank" rel="noopener" class="btn btn-outline-white"><i class="fab fa-github"></i> GitHub Repository</a>
            </div>
        </div>

    </div>
</section>

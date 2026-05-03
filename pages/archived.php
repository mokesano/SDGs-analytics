<?php
/**
 * pages/archived.php — Riwayat analisis ORCID & DOI
 * URL: ?page=archived
 */

$page_num   = max(1, (int)($_GET['p'] ?? 1));
$per_page   = 20;
$offset     = ($page_num - 1) * $per_page;
$search     = trim($_GET['q'] ?? '');
$total_count = 0;
$researchers = [];

try {
    $pdo = null;
    if (function_exists('getDb')) {
        $pdo = getDb();
    } else {
        $db_path = PROJECT_ROOT . '/database/wizdam.db';
        if (file_exists($db_path)) {
            $pdo = new PDO('sqlite:' . $db_path);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

    if ($pdo) {
        $conditions = ['1=1'];
        $params = [];
        if ($search) {
            $conditions[] = '(name LIKE :q OR orcid LIKE :q OR institution LIKE :q)';
            $params[':q'] = '%' . $search . '%';
        }
        $where = 'WHERE ' . implode(' AND ', $conditions);

        $c = $pdo->prepare("SELECT COUNT(*) FROM researchers $where");
        $c->execute($params);
        $total_count = (int)$c->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT r.id, r.orcid, r.name, r.institution,
                   COUNT(DISTINCT w.id) AS work_count,
                   COUNT(DISTINCT ws.sdg_code) AS sdg_count,
                   r.last_updated
            FROM researchers r
            LEFT JOIN works w ON w.orcid = r.orcid
            LEFT JOIN work_sdgs ws ON ws.work_id = w.id
            $where
            GROUP BY r.id
            ORDER BY r.last_updated DESC
            LIMIT :lim OFFSET :off
        ");
        $params[':lim'] = $per_page;
        $params[':off'] = $offset;
        $stmt->execute($params);
        $researchers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) { /* silent */ }

$total_pages = $total_count > 0 ? (int)ceil($total_count / $per_page) : 0;
?>

<div class="page-header">
    <div class="container">
        <div class="section-label">Search History</div>
        <h1 class="section-title">Archived Analyses</h1>
        <p class="section-subtitle">Semua profil peneliti yang pernah dianalisis — <?= number_format($total_count) ?> peneliti tersimpan</p>
    </div>
</div>

<section class="section">
    <div class="container">

        <!-- Filter & Search Bar -->
        <form method="get" class="card" style="margin-bottom:1.5rem;">
            <input type="hidden" name="page" value="archived">
            <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:center;">
                <div style="flex:1;min-width:200px;position:relative;">
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                           class="form-input" placeholder="Cari nama, ORCID, atau institusi…"
                           style="padding-left:2.5rem;">
                    <i class="fas fa-search" style="position:absolute;left:.875rem;top:50%;transform:translateY(-50%);color:var(--gray-400);"></i>
                </div>
                <button type="submit" class="btn btn-primary" style="white-space:nowrap;">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <?php if ($search): ?>
                <a href="?page=archived" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Reset
                </a>
                <?php endif; ?>
                <div style="font-size:.875rem;color:var(--gray-500);">
                    Menampilkan <strong style="color:var(--brand,#ff5627);"><?= number_format($total_count) ?></strong> peneliti
                </div>
            </div>
        </form>

        <?php if (empty($researchers)): ?>
        <div class="card" style="text-align:center;padding:80px 20px;color:var(--gray-400);">
            <i class="fas fa-database" style="font-size:3rem;margin-bottom:16px;display:block;"></i>
            <?php if ($search): ?>
            <h3 style="color:var(--gray-600);">Tidak ada hasil untuk "<?= htmlspecialchars($search) ?>"</h3>
            <p>Coba kata kunci yang berbeda.</p>
            <?php else: ?>
            <h3 style="color:var(--gray-600);">Belum Ada Riwayat Analisis</h3>
            <p>Analisis ORCID peneliti pertama untuk mengisi arsip ini.</p>
            <a href="?page=home" class="btn btn-primary" style="margin-top:12px;">
                <i class="fas fa-search"></i> Mulai Analisis
            </a>
            <?php endif; ?>
        </div>
        <?php else: ?>

        <div class="table-wrapper reveal">
            <table class="table" id="archiveTable">
                <thead>
                    <tr>
                        <th>Peneliti</th>
                        <th>ORCID ID</th>
                        <th>Institusi</th>
                        <th>Karya</th>
                        <th>SDG</th>
                        <th>Terakhir Dianalisis</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($researchers as $r): ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:.75rem;">
                                <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--brand,#ff5627),#e0481d);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.75rem;font-weight:700;flex-shrink:0;">
                                    <?= strtoupper(mb_substr(preg_replace('/[^A-Za-z ]/', '', $r['name'] ?? 'NN'), 0, 2)) ?>
                                </div>
                                <div style="font-weight:600;color:var(--gray-800);">
                                    <a href="?page=orcid-profile&orcid=<?= urlencode($r['orcid'] ?? '') ?>" style="color:inherit;text-decoration:none;">
                                        <?= htmlspecialchars($r['name'] ?? 'Unknown') ?>
                                    </a>
                                </div>
                            </div>
                        </td>
                        <td>
                            <code style="font-size:.8rem;color:var(--gray-600);"><?= htmlspecialchars($r['orcid'] ?? '') ?></code>
                        </td>
                        <td style="font-size:.875rem;color:var(--gray-500);">
                            <?= htmlspecialchars(mb_substr($r['institution'] ?? '–', 0, 40)) ?>
                        </td>
                        <td><span class="badge badge-dark"><?= (int)$r['work_count'] ?> karya</span></td>
                        <td><span class="badge badge-brand"><?= (int)$r['sdg_count'] ?> SDG</span></td>
                        <td style="font-size:.8rem;color:var(--gray-400);">
                            <?= $r['last_updated'] ? date('d M Y', strtotime($r['last_updated'])) : '–' ?>
                        </td>
                        <td>
                            <a href="?page=orcid-profile&orcid=<?= urlencode($r['orcid'] ?? '') ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-user"></i> Profil
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div style="display:flex;justify-content:center;gap:.5rem;margin-top:1.5rem;flex-wrap:wrap;">
            <?php if ($page_num > 1): ?>
            <a href="?page=archived&p=<?= $page_num-1 ?>&q=<?= urlencode($search) ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-chevron-left"></i>
            </a>
            <?php endif; ?>
            <?php for ($i = max(1, $page_num-2); $i <= min($total_pages, $page_num+2); $i++): ?>
            <a href="?page=archived&p=<?= $i ?>&q=<?= urlencode($search) ?>"
               class="btn btn-sm <?= $i === $page_num ? 'btn-primary' : 'btn-secondary' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page_num < $total_pages): ?>
            <a href="?page=archived&p=<?= $page_num+1 ?>&q=<?= urlencode($search) ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</section>

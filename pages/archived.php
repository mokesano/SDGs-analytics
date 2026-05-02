<?php
$page_title = 'Archived Searches';
$page_description = 'Browse previously analyzed ORCID profiles and DOI articles on the Wizdam AI SDG Classification Platform.';

// Static placeholder data — will be replaced with DB queries once SQLite is set up
$archived_researchers = [
    ['orcid' => '0000-0002-5152-9727', 'name' => 'Rochmady', 'institution' => 'Universitas Iqra Buru', 'works' => 24, 'sdgs' => 8, 'date' => '2025-05-01'],
    ['orcid' => '0000-0002-1825-0097', 'name' => 'Sample Researcher A', 'institution' => 'Universitas Indonesia', 'works' => 42, 'sdgs' => 12, 'date' => '2025-04-28'],
    ['orcid' => '0000-0003-4560-0123', 'name' => 'Sample Researcher B', 'institution' => 'Institut Teknologi Bandung', 'works' => 18, 'sdgs' => 5, 'date' => '2025-04-25'],
    ['orcid' => '0000-0001-7852-3654', 'name' => 'Sample Researcher C', 'institution' => 'Universitas Gadjah Mada', 'works' => 35, 'sdgs' => 9, 'date' => '2025-04-20'],
    ['orcid' => '0000-0002-9910-4567', 'name' => 'Sample Researcher D', 'institution' => 'Universitas Airlangga', 'works' => 11, 'sdgs' => 4, 'date' => '2025-04-15'],
    ['orcid' => '0000-0003-2210-8899', 'name' => 'Sample Researcher E', 'institution' => 'Universitas Hasanuddin', 'works' => 29, 'sdgs' => 7, 'date' => '2025-04-10'],
];
?>
<div class="page-header">
    <div class="container">
        <div class="section-label">Search History</div>
        <h1 class="section-title">Archived Analyses</h1>
        <p class="section-subtitle">Previously analyzed ORCID profiles and DOI articles. Click to re-analyze.</p>
    </div>
</div>

<section class="section">
    <div class="container">

        <!-- Filter & Search Bar -->
        <div class="card" style="margin-bottom:1.5rem;">
            <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:center;">
                <div style="flex:1;min-width:200px;position:relative;">
                    <input type="text" id="archiveSearch" class="form-input" placeholder=" " style="padding-left:2.5rem;" oninput="filterArchive(this.value)">
                    <label class="floating-label" style="left:2.5rem;">Search name or ORCID…</label>
                    <i class="fas fa-search" style="position:absolute;left:.875rem;top:50%;transform:translateY(-50%);color:var(--gray-400);"></i>
                </div>
                <select class="form-input" style="width:auto;min-width:140px;" onchange="filterBySdg(this.value)">
                    <option value="">All SDGs</option>
                    <?php for ($i=1; $i<=17; $i++): ?>
                    <option value="<?= $i ?>">SDG <?= $i ?></option>
                    <?php endfor; ?>
                </select>
                <div style="font-size:.875rem;color:var(--gray-500);">
                    Showing <span id="archiveCount" style="font-weight:700;color:var(--brand,#ff5627);"><?= count($archived_researchers) ?></span> researchers
                </div>
            </div>
        </div>

        <!-- TODO: Replace this with real DB data once SQLite is set up -->
        <div class="alert alert-info" style="margin-bottom:1.5rem;">
            <i class="fas fa-database"></i>
            <span><strong>Note:</strong> Displaying placeholder data. Real archive will be populated from SQLite database once <code>feat/sqlite-setup</code> is merged.</span>
        </div>

        <div class="table-wrapper reveal">
            <table class="table" id="archiveTable">
                <thead>
                    <tr>
                        <th>Researcher</th>
                        <th>ORCID ID</th>
                        <th>Institution</th>
                        <th>Works</th>
                        <th>SDGs</th>
                        <th>Analyzed</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($archived_researchers as $r): ?>
                    <tr data-name="<?= strtolower(htmlspecialchars($r['name'])) ?>" data-orcid="<?= htmlspecialchars($r['orcid']) ?>">
                        <td>
                            <div style="display:flex;align-items:center;gap:.75rem;">
                                <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--brand,#ff5627),#e0481d);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.75rem;font-weight:700;flex-shrink:0;">
                                    <?= strtoupper(substr($r['name'], 0, 2)) ?>
                                </div>
                                <div>
                                    <div style="font-weight:600;color:var(--gray-800);"><?= htmlspecialchars($r['name']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <code style="font-size:.8rem;color:var(--gray-600);"><?= htmlspecialchars($r['orcid']) ?></code>
                        </td>
                        <td style="font-size:.875rem;color:var(--gray-500);"><?= htmlspecialchars($r['institution']) ?></td>
                        <td><span class="badge badge-dark"><?= $r['works'] ?> works</span></td>
                        <td><span class="badge badge-brand"><?= $r['sdgs'] ?> SDGs</span></td>
                        <td style="font-size:.8rem;color:var(--gray-400);"><?= $r['date'] ?></td>
                        <td>
                            <a href="?page=home#analysis" onclick="document.getElementById('input_value') && (document.getElementById('input_value').value = '<?= htmlspecialchars($r['orcid']) ?>')" class="btn btn-primary btn-sm">
                                <i class="fas fa-search"></i> Analyze
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Placeholder -->
        <div style="display:flex;justify-content:center;gap:.5rem;margin-top:1.5rem;">
            <button class="btn btn-secondary btn-sm" disabled><i class="fas fa-chevron-left"></i></button>
            <button class="btn btn-primary btn-sm">1</button>
            <button class="btn btn-secondary btn-sm">2</button>
            <button class="btn btn-secondary btn-sm">3</button>
            <button class="btn btn-secondary btn-sm"><i class="fas fa-chevron-right"></i></button>
        </div>
    </div>
</section>

<script>
function filterArchive(query) {
    const q = query.toLowerCase();
    const rows = document.querySelectorAll('#archiveTable tbody tr');
    let count = 0;
    rows.forEach(row => {
        const name  = row.getAttribute('data-name') || '';
        const orcid = row.getAttribute('data-orcid') || '';
        const visible = !q || name.includes(q) || orcid.includes(q);
        row.style.display = visible ? '' : 'none';
        if (visible) count++;
    });
    document.getElementById('archiveCount').textContent = count;
}

function filterBySdg(sdg) {
    // TODO: implement real filtering once DB is connected
    console.log('Filter by SDG:', sdg);
}
</script>

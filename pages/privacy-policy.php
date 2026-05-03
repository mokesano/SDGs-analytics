<?php
$page_title = 'Kebijakan Privasi';
$page_description = 'Kebijakan privasi Wizdam AI — informasi tentang data yang dikumpulkan, cara penggunaannya, dan hak pengguna.';
?>
<div class="page-header">
    <div class="container">
        <div class="section-label">Legal</div>
        <h1 class="section-title">Kebijakan Privasi</h1>
        <p class="section-subtitle">Bagaimana Wizdam AI mengumpulkan, menggunakan, dan melindungi data Anda.</p>
    </div>
</div>
<section class="section">
    <div class="container" style="max-width:800px;">

        <div class="card" style="margin-bottom:1rem;background:rgba(255,86,39,.06);border-color:rgba(255,86,39,.2);">
            <p style="font-size:.875rem;color:var(--gray-600);margin:0;">
                <i class="fas fa-calendar-alt" style="color:#ff5627;margin-right:.5rem;"></i>
                <strong>Berlaku mulai:</strong> 1 Mei 2025 &nbsp;|&nbsp;
                <strong>Dikelola oleh:</strong> PT. Sangia Research Media and Publishing, Indonesia
            </p>
        </div>

        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-header"><h2 style="font-size:1.1rem;margin:0;"><i class="fas fa-database" style="color:#ff5627;"></i> Data yang Kami Kumpulkan</h2></div>
            <p style="color:var(--gray-600);font-size:.875rem;margin-bottom:.75rem;">Saat menggunakan platform Wizdam AI, kami dapat mengumpulkan informasi berikut:</p>
            <ul style="list-style:none;padding:0;display:flex;flex-direction:column;gap:.6rem;">
                <li style="display:flex;gap:.75rem;font-size:.875rem;color:var(--gray-600);align-items:flex-start;">
                    <i class="fas fa-check-circle" style="color:#ff5627;margin-top:.15rem;flex-shrink:0;"></i>
                    <div><strong>ORCID ID</strong> — Data publik yang Anda masukkan untuk analisis. Disimpan di cache dan database lokal.</div>
                </li>
                <li style="display:flex;gap:.75rem;font-size:.875rem;color:var(--gray-600);align-items:flex-start;">
                    <i class="fas fa-check-circle" style="color:#ff5627;margin-top:.15rem;flex-shrink:0;"></i>
                    <div><strong>DOI (Digital Object Identifier)</strong> — Identifier artikel publik yang Anda masukkan untuk analisis.</div>
                </li>
                <li style="display:flex;gap:.75rem;font-size:.875rem;color:var(--gray-600);align-items:flex-start;">
                    <i class="fas fa-check-circle" style="color:#ff5627;margin-top:.15rem;flex-shrink:0;"></i>
                    <div><strong>Hasil analisis</strong> — SDG classification, confidence score, dan contributor type yang dihasilkan platform.</div>
                </li>
                <li style="display:flex;gap:.75rem;font-size:.875rem;color:var(--gray-600);align-items:flex-start;">
                    <i class="fas fa-check-circle" style="color:#ff5627;margin-top:.15rem;flex-shrink:0;"></i>
                    <div><strong>IP address</strong> — Disimpan sementara di log server untuk keperluan keamanan dan analisis trafik agregat.</div>
                </li>
                <li style="display:flex;gap:.75rem;font-size:.875rem;color:var(--gray-600);align-items:flex-start;">
                    <i class="fas fa-check-circle" style="color:#ff5627;margin-top:.15rem;flex-shrink:0;"></i>
                    <div><strong>Informasi browser</strong> — User agent, bahasa, dan resolusi layar untuk kompatibilitas platform.</div>
                </li>
            </ul>
        </div>

        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-header"><h2 style="font-size:1.1rem;margin:0;"><i class="fas fa-lock" style="color:#ff5627;"></i> Cara Penggunaan Data</h2></div>
            <p style="color:var(--gray-600);font-size:.875rem;line-height:1.8;">Data yang dikumpulkan digunakan <strong>semata-mata</strong> untuk tujuan berikut:</p>
            <ul style="list-style:disc;padding-left:1.5rem;color:var(--gray-600);font-size:.875rem;line-height:2;">
                <li>Menjalankan analisis SDG dan menampilkan hasil kepada pengguna</li>
                <li>Cache sementara untuk mempercepat analisis berulang pada data yang sama</li>
                <li>Leaderboard peneliti berdasarkan kontribusi SDG (berdasarkan data publik ORCID)</li>
                <li>Statistik agregat platform (jumlah analisis, distribusi SDG populer, dll.)</li>
                <li>Peningkatan akurasi model dan kualitas layanan</li>
            </ul>
            <div class="alert alert-info" style="margin-top:1rem;"><i class="fas fa-info-circle"></i><span><strong>Kami tidak menjual data pribadi kepada pihak ketiga</strong> dan tidak menggunakan data untuk iklan.</span></div>
        </div>

        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-header"><h2 style="font-size:1.1rem;margin:0;"><i class="fas fa-hdd" style="color:#ff5627;"></i> Penyimpanan Data</h2></div>
            <div style="display:flex;flex-direction:column;gap:.75rem;">
                <div style="display:flex;gap:1rem;align-items:flex-start;">
                    <span class="badge badge-warning" style="white-space:nowrap;">Cache</span>
                    <p style="font-size:.875rem;color:var(--gray-600);margin:0;line-height:1.6;">Hasil analisis di-cache selama <strong>7 hari</strong> untuk mempercepat permintaan berulang. Setelah 7 hari, cache dihapus otomatis.</p>
                </div>
                <div style="display:flex;gap:1rem;align-items:flex-start;">
                    <span class="badge badge-info" style="white-space:nowrap;">Database</span>
                    <p style="font-size:.875rem;color:var(--gray-600);margin:0;line-height:1.6;">Database lokal SQLite menyimpan data leaderboard dan arsip. Tidak ada transfer ke server pihak ketiga.</p>
                </div>
                <div style="display:flex;gap:1rem;align-items:flex-start;">
                    <span class="badge badge-dark" style="white-space:nowrap;">Log Server</span>
                    <p style="font-size:.875rem;color:var(--gray-600);margin:0;line-height:1.6;">Log akses (IP address, timestamp) disimpan <strong>sementara</strong> dan dihapus secara berkala.</p>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-header"><h2 style="font-size:1.1rem;margin:0;"><i class="fas fa-external-link-alt" style="color:#ff5627;"></i> API Pihak Ketiga</h2></div>
            <p style="color:var(--gray-600);font-size:.875rem;margin-bottom:.75rem;">Platform menggunakan API eksternal berikut untuk mengambil data publikasi:</p>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Layanan</th><th>Data yang Diambil</th><th>Kebijakan Privasi</th></tr></thead>
                    <tbody>
                        <tr><td>ORCID</td><td>Profil peneliti, daftar karya (data publik)</td><td><a href="https://info.orcid.org/privacy-policy/" target="_blank">orcid.org/privacy-policy</a></td></tr>
                        <tr><td>CrossRef</td><td>Metadata artikel, abstrak</td><td><a href="https://www.crossref.org/privacy/" target="_blank">crossref.org/privacy</a></td></tr>
                        <tr><td>OpenAlex</td><td>Abstrak artikel (fallback)</td><td><a href="https://openalex.org/privacy-policy" target="_blank">openalex.org/privacy</a></td></tr>
                        <tr><td>Semantic Scholar</td><td>Abstrak artikel (fallback)</td><td><a href="https://www.semanticscholar.org/privacy-policy" target="_blank">semanticscholar.org/privacy</a></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-header"><h2 style="font-size:1.1rem;margin:0;"><i class="fas fa-user-shield" style="color:#ff5627;"></i> Hak Pengguna</h2></div>
            <p style="color:var(--gray-600);font-size:.875rem;line-height:1.8;">Anda memiliki hak untuk:</p>
            <ul style="list-style:disc;padding-left:1.5rem;color:var(--gray-600);font-size:.875rem;line-height:2;">
                <li>Meminta penghapusan data analisis Anda dari database kami</li>
                <li>Meminta informasi tentang data apa yang kami simpan terkait ORCID/DOI Anda</li>
                <li>Mengajukan keberatan terhadap penggunaan data tertentu</li>
            </ul>
            <p style="color:var(--gray-600);font-size:.875rem;margin-top:.75rem;">Untuk menggunakan hak Anda, kirim email ke: <a href="mailto:privacy@wizdam.ai" style="color:#ff5627;font-weight:600;">privacy@wizdam.ai</a></p>
        </div>

        <div class="card">
            <div class="card-header"><h2 style="font-size:1.1rem;margin:0;"><i class="fas fa-envelope" style="color:#ff5627;"></i> Kontak Privasi</h2></div>
            <p style="color:var(--gray-600);font-size:.875rem;line-height:1.7;">
                Untuk pertanyaan tentang kebijakan privasi ini, hubungi kami di:
                <a href="mailto:privacy@wizdam.ai" style="color:#ff5627;font-weight:600;">privacy@wizdam.ai</a>
            </p>
            <p style="color:var(--gray-400);font-size:.8rem;margin-top:.75rem;">
                PT. Sangia Research Media and Publishing, Indonesia &nbsp;|&nbsp;
                Terakhir diperbarui: 1 Mei 2025
            </p>
        </div>

    </div>
</section>

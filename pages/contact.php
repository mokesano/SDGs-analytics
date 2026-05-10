<?php
$page_title = 'Hubungi Kami';
$page_description = 'Hubungi tim Wizdam AI untuk pertanyaan, laporan bug, permintaan fitur, kemitraan, atau keperluan pers.';
?>

<div class="page-header">
    <div class="container">
        <div class="section-label">Kontak</div>
        <h1 class="section-title">Hubungi Kami</h1>
        <p class="section-subtitle">Ada pertanyaan, ide, atau ingin berkolaborasi? Kami senang mendengar dari Anda.</p>
    </div>
</div>

<section class="section">
<div class="container">
    <div style="display:grid;grid-template-columns:1fr 1.5fr;gap:2.5rem;align-items:start;">

        <!-- Contact Info -->
        <div>
            <div style="display:flex;flex-direction:column;gap:1rem;margin-bottom:2rem;">
                <div class="contact-info-card">
                    <div class="contact-info-icon" style="background:rgba(255,86,39,.1);color:#ff5627;">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div>
                        <div class="contact-info-label">Email</div>
                        <a href="mailto:contact@wizdam.ai" class="contact-info-value">contact@wizdam.ai</a>
                        <div class="contact-info-note">Respons dalam 2–3 hari kerja</div>
                    </div>
                </div>

                <div class="contact-info-card">
                    <div class="contact-info-icon" style="background:rgba(31,31,31,.08);color:#1f1f1f;">
                        <i class="fab fa-github"></i>
                    </div>
                    <div>
                        <div class="contact-info-label">GitHub</div>
                        <a href="https://github.com/mokesano/SDGs-analytics" target="_blank" rel="noopener" class="contact-info-value">github.com/mokesano/SDGs-analytics</a>
                        <div class="contact-info-note">Issue tracker & source code</div>
                    </div>
                </div>

                <div class="contact-info-card">
                    <div class="contact-info-icon" style="background:rgba(59,130,246,.1);color:#3b82f6;">
                        <i class="fas fa-building"></i>
                    </div>
                    <div>
                        <div class="contact-info-label">Institusi</div>
                        <div class="contact-info-value" style="color:var(--dark,#1a2e45);">PT. Sangia Research Media and Publishing</div>
                        <div class="contact-info-note">Indonesia</div>
                    </div>
                </div>
            </div>

            <!-- Social Links -->
            <div>
                <div style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--gray-400);margin-bottom:.75rem;">Ikuti Kami</div>
                <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
                    <a href="https://twitter.com/wizdamai" target="_blank" rel="noopener" class="contact-social-btn" style="background:#1da1f2;">
                        <i class="fab fa-twitter"></i> Twitter
                    </a>
                    <a href="https://linkedin.com/company/wizdam-ai" target="_blank" rel="noopener" class="contact-social-btn" style="background:#0077b5;">
                        <i class="fab fa-linkedin"></i> LinkedIn
                    </a>
                    <a href="https://github.com/mokesano" target="_blank" rel="noopener" class="contact-social-btn" style="background:#1f1f1f;">
                        <i class="fab fa-github"></i> GitHub
                    </a>
                    <a href="https://youtube.com/@wizdamai" target="_blank" rel="noopener" class="contact-social-btn" style="background:#ff0000;">
                        <i class="fab fa-youtube"></i> YouTube
                    </a>
                </div>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="card">
            <h2 style="font-size:1.15rem;margin-bottom:1.25rem;color:var(--dark,#1a2e45);">
                <i class="fas fa-paper-plane" style="color:#ff5627;margin-right:.5rem;"></i>Kirim Pesan
            </h2>
            <div id="contactSuccess" style="display:none;" class="alert alert-success" role="alert">
                <i class="fas fa-check-circle"></i>
                <span>Pesan berhasil dikirim! Kami akan merespons dalam 2–3 hari kerja.</span>
            </div>
            <form id="contactForm" onsubmit="handleContactSubmit(event)">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap <span style="color:#ff5627;">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Dr. Nama Anda" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email <span style="color:#ff5627;">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="nama@universitas.ac.id" required>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:1rem;">
                    <label class="form-label">Subjek <span style="color:#ff5627;">*</span></label>
                    <select name="subject" class="form-control" required>
                        <option value="">-- Pilih Topik --</option>
                        <option value="general">Pertanyaan Umum</option>
                        <option value="bug">Laporan Bug</option>
                        <option value="feature">Permintaan Fitur</option>
                        <option value="partnership">Kemitraan & Kolaborasi</option>
                        <option value="press">Pers & Media</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:1.25rem;">
                    <label class="form-label">Pesan <span style="color:#ff5627;">*</span></label>
                    <textarea name="message" class="form-control" rows="5" placeholder="Tuliskan pertanyaan atau pesan Anda di sini..." required style="resize:vertical;"></textarea>
                </div>
                <p style="font-size:.8rem;color:var(--gray-400);margin-bottom:1rem;">
                    <i class="fas fa-info-circle"></i> Pesan akan dicatat dan direspons dalam 2–3 hari kerja.
                </p>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                    <i class="fas fa-paper-plane"></i> Kirim Pesan
                </button>
            </form>
        </div>

    </div>
</div>
</section>

<style>
.contact-info-card {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background: white;
    border: 1.5px solid var(--gray-200, #e2e8f0);
    border-radius: 12px;
    transition: border-color .2s, box-shadow .2s;
}
.contact-info-card:hover { border-color: #ff5627; box-shadow: 0 4px 14px rgba(255,86,39,.1); }
.contact-info-icon {
    width: 44px; height: 44px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}
.contact-info-label { font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--gray-400); margin-bottom: .15rem; }
.contact-info-value { font-size: .9rem; font-weight: 600; color: #ff5627; text-decoration: none; }
.contact-info-value:hover { text-decoration: underline; }
.contact-info-note { font-size: .78rem; color: var(--gray-400); margin-top: .1rem; }

.contact-social-btn {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .5rem .9rem;
    border-radius: 8px;
    color: white;
    text-decoration: none;
    font-size: .85rem;
    font-weight: 600;
    transition: opacity .2s, transform .15s;
}
.contact-social-btn:hover { opacity: .85; transform: translateY(-1px); }

.alert-success {
    background: rgba(16,185,129,.1);
    border: 1px solid rgba(16,185,129,.3);
    color: #065f46;
    padding: .75rem 1rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: .5rem;
    margin-bottom: 1rem;
    font-size: .875rem;
}

@media (max-width: 640px) {
    .container > div[style*="grid-template-columns:1fr 1.5fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
function handleContactSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const btn = form.querySelector('button[type=submit]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';
    setTimeout(() => {
        form.style.display = 'none';
        document.getElementById('contactSuccess').style.display = 'flex';
    }, 900);
}
</script>

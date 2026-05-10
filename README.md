# SDGs-analytics – Platform Klasifikasi Riset untuk 17 SDGs

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-777bb3.svg)](https://php.net)

**SDGs-analytics** adalah platform analitik sumber terbuka yang memetakan kontribusi publikasi ilmiah ke **17 Tujuan Pembangunan Berkelanjutan (SDGs).**  
Cukup masukkan ORCID peneliti atau unggah PDF—sistem akan menganalisis teks dengan AI, mencocokkannya dengan SDGs yang relevan, lalu menyajikannya dalam dashboard visual yang kaya. Dibangun dengan PHP modern, SQLite, dan library front-end seperti Chart.js dan D3.js.

---

## ✨ Apa yang Bisa Dilakukan?

- 🔍 **Pencarian via ORCID** – Ambil metadata karya peneliti dari ORCID, perkaya dengan Crossref, dan klasifikasikan SDG setiap publikasi.  
- 📊 **Dashboard Visual** – Grafik distribusi SDG, tren tahunan, diagram kontributor, dan papan peringkat interaktif.  
- 📄 **Upload & Analisis PDF** – Unggah dokumen, teks diekstrak, dan langsung dapatkan hasil klasifikasi SDG.  
- 🧠 **AI Klasifikasi Teks** – Memanfaatkan Hugging Face Inference API untuk klasifikasi akurat.  
- 🔎 **Full-Text Search** – Cari abstrak publikasi dengan SQLite FTS5, cepat dan ringan.  
- 👥 **Sistem Pengguna** – Registrasi, login, dan pemulihan kata sandi dengan keamanan CSRF + Argon2ID.  
- 📋 **Leaderboard Kontributor** – Filter berdasarkan SDG dan tipe kontributor, lihat siapa yang paling berdampak.  
- 📤 **Ekspor DOCX** – Simpan hasil analisis dalam bentuk dokumen Word.

---

## 🖥️ Demo Langsung

Kunjungi platform yang sudah berjalan di:  
🌐 **[wizdam.sangia.org](https://wizdam.sangia.org)**

---

## 🧱 Teknologi di Balik Layar

### Backend (Native PHP, Tanpa Framework)
- **PHP 8.1+** – Kode utama, tanpa framework (77% dari total kode).
- **SQLite 3** – Database ringan dengan WAL mode dan foreign key support.
- **GuzzleHTTP** – HTTP client dengan logika retry, komunikasi ke API eksternal.
- **Readability.php** – Ekstraksi konten utama dari halaman artikel.
- **Poppler (`pdftotext`)** – Konversi PDF ke teks via server.
- **PhpWord** – Menghasilkan dokumen laporan .docx.
- **Argon2ID** – Standar hashing kata sandi terbaru.

### Frontend
- **Vite** – Build tool modern untuk aset front-end.
- **Tailwind CSS** – Tampilan responsif tanpa ribet.
- **Chart.js** – Diagram interaktif ringan.
- **D3.js** – Visualisasi data kustom yang kompleks.
- **ApexCharts** – Alternatif chart dinamis.
- **Leaflet** – Peta interaktif open-source.

### AI & Data
- **Hugging Face Inference API** – Model klasifikasi teks.
- **SQLite FTS5** – Mesin pencarian teks lengkap di database.

---

## ⚙️ Instalasi

### Yang Dibutuhkan
- PHP ≥ 8.1
- Composer
- Node.js ≥ 18
- SQLite 3
- Poppler-utils (untuk fitur PDF)

### Langkah Cepat

```bash
# 1. Clone repo
git clone https://github.com/mokesano/SDGs-analytics.git
cd SDGs-analytics

# 2. Install dependensi PHP
composer install

# 3. Install dependensi Node
npm install

# 4. Build aset frontend
npm run build

# 5. Siapkan database
#    SQLite akan otomatis dibuat. Pastikan folder database/ bisa ditulis.
```

Arahkan web server Anda ke direktori `public/` (atau gunakan `php -S localhost:8000 -t public`).

---

## 📁 Struktur Direktori

```
SDGs-analytics/
├── api/                 # Endpoint internal API
├── assets/              # File CSS, JS, gambar
├── components/          # Komponen PHP reusable (layout, card, dll)
├── config/              # Konfigurasi database, API keys, dll
├── database/            # Skema SQL (schema.sql) & file SQLite
├── desain-UI-UX/        # Aset desain dan wireframe
├── includes/            # File inti: bootstrap, routing, helper
├── logs/                # Pencatatan aktivitas
├── pages/               # Halaman front-end (dashboard, profil, login)
├── public/              # Document root, entry point (index.php)
├── node_modules/        # Dependensi Node (setelah npm install)
├── vendor/              # Dependensi Composer
├── ACTION_PLAN.md       # Rencana pengembangan ke depan
├── CHANGELOG.md         # Log perubahan versi
├── SECURITY.md          # Kebijakan pelaporan keamanan
├── composer.json
├── package.json
└── ...
```

---

## 🚀 Mulai Menggunakan

1. Buka platform di browser.
2. **Cari Peneliti**: Masukkan ORCID (contoh: `0000-0002-5152-9727`).  
   Sistem akan mengambil karya dari ORCID, mengecek metadata tambahan di Crossref, dan mengklasifikasikan SDGs.
3. **Dashboard Pribadi**: Lihat diagram distribusi SDG, tren publikasi, dan kontribusi peneliti.
4. **Upload PDF**: Menu khusus untuk mengunggah PDF baru, ekstrak teks otomatis, lalu analisis.
5. **Leaderboard**: Telusuri kontributor teratas untuk SDG tertentu.

---

## 🗺️ Rencana Pengembangan (Roadmap)

Lihat detail di [`ACTION_PLAN.md`](ACTION_PLAN.md) – fase selanjutnya mencakup:
- Integrasi dengan CodeIgniter 4 untuk arsitektur lebih solid
- Dukungan database MySQL
- API publik berformat JSON
- Dashboard admin untuk manajemen data
- Rekomendasi kolaborator riset berbasis SDG

---

## 🤝 Ingin Berkontribusi?

Sangat diterima! Silakan ikuti langkah berikut:
1. Fork repositori ini.
2. Buat branch fitur baru (`git checkout -b fitur-keren`).
3. Commit perubahan (`git commit -m 'Menambahkan fitur keren'`).
4. Push ke branch (`git push origin fitur-keren`).
5. Buat Pull Request.

Pastikan membaca [`SECURITY.md`](SECURITY.md) jika menemukan celah keamanan.

---

## 👤 Kredit & Pengembang

- **Pengembang Utama**: [Rochmady](https://github.com/mokesano) – [ORCID: 0000-0002-5152-9727](https://orcid.org/0000-0002-5152-9727)  
- **Tim**: Wizdam AI Team – PT. Sangia Research Media and Publishing  
- **Platform Aktif**: [wizdam.sangia.org](https://wizdam.sangia.org)  
- **Lisensi**: [MIT License](LICENSE) – bebas digunakan, diubah, dan didistribusikan.

---

## 📜 Lisensi

Proyek ini dilisensikan di bawah **MIT License**. Lihat file [LICENSE](LICENSE) untuk detail lengkap.

---

**SDGs-analytics** – Memetakan riset, mengukur dampak, dan membangun masa depan berkelanjutan bersama.
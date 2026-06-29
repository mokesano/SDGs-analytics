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


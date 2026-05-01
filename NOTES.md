Baik, saya tangkap visinya dengan jelas. Ini insight komprehensif saya:

---

## Framework: Custom PHP vs Laravel vs CI4

**Rekomendasi saya: Custom PHP dengan struktur yang Anda rancang sendiri** — dan ini pilihan yang tepat untuk kasus ini. Alasannya:

| Aspek | Laravel/CI4 | Custom PHP Anda |
|---|---|---|
| Overhead | Berat, banyak layer tidak terpakai | Ringan, semua kode langsung relevan |
| Routing | Butuh setup Eloquent, Blade, dll | Router sederhana cukup 50 baris |
| Anti-timeout | Harus diadaptasi ke framework | Sudah terbukti berjalan |
| Learning curve tim | Tinggi | Rendah, semua PHP standar |
| Deploy di shared hosting | Kadang ada constraint | Bebas hambatan |

Struktur yang Anda rancang **sudah mengikuti pola MVC modern** tanpa overhead framework. Ini justru lebih bersih.

---

## Evaluasi Struktur yang Diusulkan

Struktur Anda sudah sangat solid. Satu saran pengayaan:

```
sdg/
├── public/              ← document root (bagus)
│   ├── index.php        ← front controller + router
│   └── .htaccess
│
├── includes/
│   ├── config.php       ← DB, API keys, base URL
│   ├── bootstrap.php    ← autoload, session, routing table
│   └── functions.php
│
├── api/
│   ├── sdgs.php         ← ORCID sequential batch (sudah ada)
│   ├── scopus.php       ← Scopus journal API
│   └── cache/           ← ← TAMBAHKAN ini (pisah dari public)
│
├── assets/              ← CSS, JS, images (bagus)
│
├── pages/               ← content pages (bagus)
│
├── components/          ← reusable partials (bagus)
│
├── cache/               ← ← TAMBAHKAN: cache file .gz di sini
├── logs/                ← ← TAMBAHKAN: error_log terstruktur
└── storage/             ← ← TAMBAHKAN: arsip ORCID & journal JSON
    ├── orcid/
    │   └── 0000-0002-5152-9727.json
    └── journals/
        └── 1234-5678.json
```

---

## URL Routing — Cara Kerjanya

`public/index.php` menjadi **front controller** tunggal yang membaca `REQUEST_URI` dan mendelegasikan ke handler:

```
GET /                          → pages/home.php
GET /orcid/0000-0002-5152-9727 → pages/orcid-profile.php
GET /journal/1234-5678         → pages/journal-profile.php
GET /archived                  → pages/archived.php
GET /about                     → pages/about.php
POST / (body: _sdg=init)       → api/sdgs.php (proxy)
```

Satu `.htaccess` dengan `RewriteRule ^ index.php [L,QSA]` sudah cukup menangani semua ini — persis pola yang sudah Anda pakai sekarang.

---

## Fitur Arsip ORCID & Journal — Strategi Penyimpanan

Karena Anda tidak menyebut database, ada dua opsi yang cocok:

**Opsi A — File JSON (tanpa DB, cocok untuk mulai cepat):**
Setiap ORCID/ISSN yang sudah dianalisis disimpan sebagai `storage/orcid/{orcid}.json`. Halaman arsip tinggal `scandir()` folder tersebut. Kekurangannya: sulit di-query kompleks saat data besar.

**Opsi B — SQLite (satu file, tidak perlu server MySQL):**
Satu file `storage/wizdam.db` menyimpan tabel `orcid_profiles` dan `journals`. Query SQL tetap bisa dilakukan, deploy semudah file JSON. Ini rekomendasi saya untuk jangka panjang.

---

## Desain Visual — Referensi mstechnomedia.com

Dari referensi yang Anda tunjuk, karakteristik desainnya adalah:

- **Dark background** dengan gradient biru-ungu-hitam yang dalam
- **Glassmorphism cards** — card transparan dengan blur backdrop
- **Animated gradient text** pada heading
- **Micro-animations** — elemen muncul saat scroll (Intersection Observer)
- **Grid layout asimetris** — hero section full-width, konten dalam grid dinamis
- **Accent color** neon/vivid (biru elektrik, ungu) di atas background gelap
- **Typography kontras tinggi** — heading bold besar, body light/thin

Kabar baiknya: CSS variables dan Tailwind utility classes sudah cukup untuk mereplikasi estetika ini **tanpa library tambahan**. Semua efek glassmorphism, gradient animated, dan scroll reveal bisa diimplementasi dengan vanilla CSS + ~100 baris JS.

---

## Alur Kerja GitHub yang Direkomendasikan

Mengingat Anda ingin langsung ke repository:

```
main branch          ← production (wizdam.sangia.org)
├── develop          ← staging/testing
│   ├── feature/orcid-profile
│   ├── feature/journal-scopus
│   └── feature/archive-system
└── hotfix/*
```

Setiap fitur dari poin 1–4 yang Anda sebutkan bisa menjadi branch tersendiri yang merge ke `develop` dulu sebelum ke `main`.

---

## Prioritas Pengerjaan yang Saya Sarankan

Berdasarkan yang sudah ada dan yang ingin dicapai, urutan paling efisien:

1. **Router + struktur folder** — fondasi semua fitur berikutnya
2. **Halaman ORCID profile** (`/orcid/{id}`) — memindahkan interface yang sudah ada ke halaman dedicated
3. **Sistem arsip** — simpan hasil analisis ke `storage/`, tampilkan di `/archived`
4. **Scopus journal profile** (`/journal/{issn}`) — API baru, halaman baru
5. **Desain visual dark/glassmorphism** — bisa paralel dengan poin 2–4

Kapan saja siap, kita bisa mulai dari mana saja di GitHub.
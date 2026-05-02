# Build Assets dengan Vite

## Perintah Build

```bash
npm run build
```

## Output

File akan di-build ke `dist/assets/`:
- `styles-[hash].css` - Tailwind CSS dengan SDG color system
- `main.js` - JavaScript untuk scroll reveal, dark mode, smooth scroll

## Update CSS Hash di layouts/main.php

Setiap kali build ulang, update hash CSS di `layouts/main.php`:

```php
<link rel="stylesheet" href="/dist/assets/styles-RLXKZ-n-.css">
```

Ganti `styles-RLXKZ-n-.css` dengan nama file terbaru.

## Development Mode

Untuk development dengan hot-reload:

```bash
npm run dev
```

## Struktur File Source

```
assets/
├── css/
│   └── styles.css      # Tailwind directives + custom components
├── js/
│   ├── main.js         # Entry point
│   └── utils.js        # Helper functions
```

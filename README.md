# SyntaxDocs — Multi-Application Shared Core & UI Design System

Arsitektur terpadu berbasis **Shared Core Framework** dan **Shared UI Design System** untuk melayani 10+ produk web yang berbeda (seperti CMS, School System, E-Commerce, dan lainnya) tanpa menulis ulang boilerplate koneksi database, middleware keamanan, atau styling template.

---

## 📁 Struktur Workspace

```text
syntaxdocs/
├── packages/
│   ├── core-php/             # Shared Core Framework (PHP)
│   │   ├── src/              # Request, Response, Router, Onion Pipeline, Multi-Conn DB, Auth, View
│   │   ├── templates/        # Built-in Component Templates (navbar, modal, card, table, dll)
│   │   ├── migrations/       # Migrasi bawaan tabel users & sessions
│   │   └── bin/core          # CLI tool (php bin/core migrate)
│   │
│   └── ui-cdn/               # Shared UI Design System (Aset CDN Terpusat)
│       └── public/           # Static files: CSS tokens, BEM components, Vanilla JS engine
│           ├── css/core.min.css
│           ├── js/core.min.js
│           └── index.html    # Interactive Showcase & UI Test Suite
│
├── apps/                     # Contoh Produk-Produk Mandiri
│   ├── cms/                  # Produk 1: Panel CMS (artikel, modal hapus, database db_cms)
│   └── school/               # Produk 2: Sistem Sekolah (data siswa, tema emerald green, db_school)
│
├── docs/                     # Dokumentasi & Spesifikasi Portabilitas
│   ├── specs/                # Kontrak schema UI & API untuk Node.js / Golang
│   └── superpowers/          # Arsip Architectural Design Spec & Implementation Plan
│
└── tests/                    # Script verifikasi otomatis
    ├── verify_contracts.js   # Verifier spesifikasi & aset (Node.js)
    └── run_tests.php         # Unit test suite Core PHP
```

---

## 🚀 Cara Menggunakan Komponen di Aplikasi Produk

### 1. Memanggil Komponen di PHP View
Di aplikasi produk mana pun (misal: CMS atau School), komponen dipanggil dengan data (props) bersih:

```php
use Syntax\Core\View\Component;

// Render Dialog Modal
<?= Component::render('modal', [
    'id' => 'deleteConfirmModal',
    'title' => 'Konfirmasi Penghapusan',
    'body' => 'Apakah Anda yakin ingin menghapus data ini?',
    'cancel_label' => 'Batal',
    'confirm_label' => 'Ya, Hapus',
    'confirm_action' => '/items/delete/1',
    'variant' => 'danger'
]) ?>

// Render Data Table
<?= Component::render('table', [
    'headers' => ['ID', 'Nama', 'Role', 'Aksi'],
    'rows' => [
        ['#1', 'Ahmad', '<span class="c-badge c-badge--success">Admin</span>', '<button class="c-btn c-btn--sm c-btn--secondary">Edit</button>']
    ],
    'striped' => true,
    'hover' => true
]) ?>
```

### 2. Memuat UI dari Central CDN
Master layout HTML produk cukup memuat stylesheet dan script dari CDN:

```html
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <link rel="stylesheet" href="http://cdn.company.local/css/core.min.css">
  <!-- Opsional: Custom theme produk untuk override warna -->
  <link rel="stylesheet" href="theme.css">
</head>
<body>
  <?= $navbar ?>
  <main class="c-container py-6">
    <?= $content ?>
  </main>
  <script src="http://cdn.company.local/js/core.min.js"></script>
</body>
</html>
```

### 3. Kustomisasi Tema per Produk (Theming)
Setiap produk dapat memiliki identitas warna sendiri hanya dengan membuat file `theme.css` di aplikasinya tanpa mengubah file CDN:

```css
/* apps/school/public/theme.css */
:root {
  --color-primary: #059669;        /* Hijau emerald */
  --color-primary-hover: #047857;
  --color-primary-light: #ecfdf5;
}
```

---

## 🌐 Roadmap Portabilitas (Node.js & Golang)

Sistem ini dirancang berbasis **Contract-First**:
1. **Aset UI Nol Sentuhan (Zero-Touch UI)**: Layanan Node.js (Fastify/Express) atau Golang (Chi/Fiber) cukup menyematkan tag `<link>` dan `<script>` CDN yang sama. Semua modal dialog (`data-toggle="modal"`), dropdown, dan form langsung aktif di browser.
2. **Spesifikasi Props Komponen**: Didokumentasikan di [`docs/specs/components-spec.json`](docs/specs/components-spec.json).
3. **Format JSON Respon API Standar**: Mengikuti skema [`docs/specs/api-contract.json`](docs/specs/api-contract.json).
4. **Panduan Porting**: Langkah implementasi kode di Node.js dan Go dapat dibaca di [`docs/specs/portability-guide.md`](docs/specs/portability-guide.md).

---

## 🧪 Menjalankan Pengujian & Verifikasi

### Verifikasi Spesifikasi & Asset (Node.js):
```bash
node tests/verify_contracts.js
```

### Unit Test Core PHP:
```bash
php tests/run_tests.php
```

### Melihat Showcase Komponen Visual:
Buka file `packages/ui-cdn/public/index.html` di web browser apa pun.

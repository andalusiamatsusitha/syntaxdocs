# Implementation Plan: Shared Core Framework & UI Component System Multi-Aplikasi

- **Tanggal**: 2026-10-02
- **Spec Acuan**: [2026-10-02-shared-core-and-ui-system-design.md](file:///C:/Users/andal/Documents/Project/syntaxdocs/docs/superpowers/specs/2026-10-02-shared-core-and-ui-system-design.md)
- **Status**: Siap Dieksekusi (Ready to Execute)

---

## 1. Rencana Tahapan Eksekusi (Phase Roadmap)

### Fase 1: Shared UI Design System & CDN (`packages/ui-cdn`)
Fokus: Membangun aset frontend independen (CSS & Vanilla JS) yang netral terhadap bahasa backend dan siap di-host via CDN.
- [ ] **Task 1.1: Token & Base CSS**
  - Buat `packages/ui-cdn/public/css/tokens.css` (CSS variables warna, tipografi, radius, shadow, spacing).
  - Buat `packages/ui-cdn/public/css/base.css` (modern reset, font smoothing, box-sizing, grid/flex container).
- [ ] **Task 1.2: BEM Component Styles**
  - Buat `packages/ui-cdn/public/css/components.css` (`.c-btn`, `.c-navbar`, `.c-sidebar`, `.c-card`, `.c-modal`, `.c-table`, `.c-form-group`, `.c-alert`, `.c-badge`).
  - Buat file bundle minified / aggregated `packages/ui-cdn/public/css/core.min.css`.
- [ ] **Task 1.3: Vanilla JS Event Delegation Engine**
  - Buat `packages/ui-cdn/public/js/core.js` (Event delegation untuk `data-toggle="modal"`, `data-dismiss="modal"`, `data-toggle="dropdown"`, `data-toggle="sidebar"`, `data-dismiss="alert"`).
  - Buat bundle `packages/ui-cdn/public/js/core.min.js`.
- [ ] **Task 1.4: Showcase / Interactive Component Preview**
  - Buat `packages/ui-cdn/public/index.html` yang menampilkan seluruh komponen dan menguji interaksi JavaScript secara visual di browser.

### Fase 2: Shared Core PHP Backend (`packages/core-php`)
Fokus: Membangun library backend mandiri (PSR-7/15 inspired) dengan multi-connection database, middleware, router, migrasi, dan component helper.
- [ ] **Task 2.1: Bootstrap & Autoloading**
  - Buat `packages/core-php/composer.json` (namespace `Syntax\Core\`).
  - Buat `packages/core-php/autoload.php` (autoloader native PSR-4 mandiri agar tetap bisa berjalan tanpa composer binary lokal).
- [ ] **Task 2.2: HTTP Abstraction & Router**
  - Buat `src/Http/Request.php` (kapsulasi headers, query, post, files, method, uri).
  - Buat `src/Http/Response.php` (HTML, JSON, redirect emitter).
  - Buat `src/Http/Router.php` (HTTP verbs, dynamic params `{id}`, route groups, middleware attaching).
- [ ] **Task 2.3: Middleware Pipeline (PSR-15)**
  - Buat `src/Http/Middleware/MiddlewareInterface.php`.
  - Buat `src/Http/Middleware/Pipeline.php` (onion pattern dispatcher).
  - Buat `CorsMiddleware.php`, `SecurityHeadersMiddleware.php`, `SessionMiddleware.php`, `CsrfMiddleware.php`.
- [ ] **Task 2.4: Multi-Connection Database & Query Builder**
  - Buat `src/Database/ConnectionManager.php` (PDO manager dengan kemampuan register multi-koneksi: `default`, `auth_central`).
  - Buat `src/Database/QueryBuilder.php` (metode `table()`, `where()`, `get()`, `first()`, `insert()`, `update()`, `delete()`, transactions).
- [ ] **Task 2.5: CLI Migration Runner & Base Schemas**
  - Buat `src/Database/MigrationRunner.php`.
  - Buat skema dasar: `migrations/001_create_users_table.php` dan `002_create_sessions_table.php`.
  - Buat CLI script `bin/core` (`php bin/core migrate`).
- [ ] **Task 2.6: Autentikasi & Security**
  - Buat `src/Auth/PasswordHasher.php` (Argon2id/Bcrypt).
  - Buat `src/Auth/AuthManager.php` (`Auth::attempt`, `Auth::user`, `Auth::check`, `Auth::logout`).
  - Buat `src/Auth/AuthMiddleware.php`.
- [ ] **Task 2.7: View & Component Rendering Engine**
  - Buat `src/View/Component.php` (`Component::render('modal', $props)`).
  - Buat template HTML komponen di `packages/core-php/templates/` (`navbar.php`, `modal.php`, `card.php`, `table.php`, `form-group.php`, `alert.php`).

### Fase 3: Multi-Language Architecture Contracts (`docs/specs/`)
Fokus: Menyediakan kontrak formal agar pengembang Node.js & Golang di masa depan dapat mengintegrasikan atau membangun service baru dengan standar identik.
- [ ] **Task 3.1: Component Props Specification**
  - Buat `docs/specs/components-spec.json` (schema JSON input props untuk Navbar, Modal, Card, Table, Form Group).
- [ ] **Task 3.2: Unified API JSON Envelope Contract**
  - Buat `docs/specs/api-contract.json` (standar response REST API).
- [ ] **Task 3.3: Portability Guide (Node.js & Golang)**
  - Buat `docs/specs/portability-guide.md` (panduan pemetaan kode Express/Fastify dan Chi/Fiber, serta integrasi zero-touch CDN).

### Fase 4: Demo Aplikasi Produk (`apps/cms` & `apps/school`)
Fokus: Membuktikan secara nyata bahwa 1 Core & 1 UI CDN dapat digunakan oleh 2 produk yang berbeda dengan tema dan database masing-masing.
- [ ] **Task 4.1: Demo Produk 1 — CMS (`apps/cms`)**
  - Setup struktur folder CMS (`public/index.php`, `routes/web.php`, `views/`).
  - Implementasi halaman Dashboard Artikel menggunakan `Component::render()`.
- [ ] **Task 4.2: Demo Produk 2 — School Management (`apps/school`)**
  - Setup struktur folder School.
  - Implementasi tema warna kustom (`--color-primary` hijau) meng-override CDN token, menunjukkan kemudahan re-theming per produk.

### Fase 5: Pengujian, Verifikasi & Dokumentasi
- [ ] **Task 5.1: Automated Integration Test Script**
  - Buat test runner verifikasi di `tests/run_tests.php` (menguji Router, Pipeline, Query Builder, Component Engine).
- [ ] **Task 5.2: Dokumentasi Root `README.md`**
  - Panduan instalasi monorepo, cara menjalankan CDN, cara membuat produk baru, dan cara menjalankan migrasi.

---

## 2. Cara Kerja Bertahap (Step-by-Step)
Sesuai arahan, eksekusi akan dilakukan secara bertahap mulai dari **Fase 1 (UI CDN)**, dilanjutkan **Fase 2 (PHP Core)**, **Fase 3 (Contracts)**, **Fase 4 (Demo Apps)**, dan diakhiri **Fase 5 (Testing & Verifikasi)**.
Di setiap akhir tahapan, hasil pengerjaan akan dilaporkan dan diverifikasi.

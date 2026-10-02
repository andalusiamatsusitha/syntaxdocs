# Desain Arsitektur: Shared Core Framework & UI Component System Multi-Aplikasi

- **Tanggal**: 2026-10-02
- **Status**: Disetujui (Approved)
- **Cakupan**: Architectural Specification untuk fondasi bersama multi-produk (PHP + MySQL + HTML/CSS/Vanilla JS) dan roadmap interoperabilitas Node.js & Golang.

---

## 1. Ringkasan Eksekutif & Tujuan

Proyek ini bertujuan membangun arsitektur terpadu berupa **1 Shared Core Framework** dan **1 Shared UI Design System** yang dapat digunakan secara bersama oleh 10+ produk aplikasi berbasis web yang berbeda (seperti CMS, Sistem Informasi Sekolah, E-Commerce, dan lainnya).

Dengan pendekatan ini:
- Pengembang **tidak perlu membuat ulang** koneksi database, middleware keamanan, sesi, routing, hashing password, maupun template CSS/JS di setiap aplikasi produk.
- Setiap produk memiliki database MySQL mandiri, namun Core mendukung *multi-connection* jika sewaktu-waktu dibutuhkan integrasi terpusat (seperti SSO atau database reporting).
- Aset UI (HTML/CSS/Vanilla JS) sepenuhnya independen dan disajikan via CDN internal terpusat, menjamin nol dependensi framework frontend (bebas React/Vue/jQuery) dan performa tinggi.
- Fondasi dirancang berbasis *Contract-First*, sehingga di masa depan produk baru yang dibangun dengan **Node.js** atau **Golang** dapat langsung mengonsumsi UI CDN dan mengadopsi pola arsitektur yang identik.

---

## 2. Keputusan Arsitektur Utama (ADR Summary)

| Domain | Keputusan | Alasan & Dampak |
| :--- | :--- | :--- |
| **Repositori & Distribusi** | Modular Monorepo Workspace (Composer Path Repository) | Memudahkan pengembangan dan testing integrasi antar library dan aplikasi dalam satu repo tanpa overhead private registry di awal. Siap dipisah jika dibutuhkan. |
| **Pemisahan Layer** | Decoupled Architecture (Backend Core terpisah dari UI CDN) | Aset UI netral terhadap bahasa backend; PHP Core murni menyediakan koneksi DB, middleware, dan view helper. |
| **Backend Engine** | Modern Vanilla PHP berbasis Standar PSR (PSR-7, PSR-15, PDO) | Ringan, zero-bloat, tanpa lock-in framework, dan struktur middleware-nya identik dengan Node.js (Express) & Golang (Chi/Fiber). |
| **Database Model** | Database Terpisah per Produk + Siap Multi-Connection | Data tiap produk (CMS, School, dll) terisolasi secara aman di DB masing-masing; Core siap berpindah koneksi ke DB bersama jika dibutuhkan. |
| **Frontend Styling** | CSS Variables (Design Tokens) + Metodologi BEM | Memungkinkan setiap produk melakukan re-theming (misal warna khas produk) hanya dengan meng-override CSS variables tanpa merusak komponen. |
| **Frontend Scripting** | Vanilla JS Event Delegation via Data-Attributes | Zero library, ukuran sangat kecil (<15KB), event `modal`, `dropdown`, `sidebar` otomatis aktif pada tag ber-attribute `data-toggle`. |
| **Aset Distribusi** | Centralized Static Server / Internal CDN | Seluruh produk cukup menyematkan tag `<link>` dan `<script>` yang sama dari satu host CDN internal terpusat. |
| **Autentikasi** | Independent Auth per Produk dengan Standard Core Library | Tiap produk mengelola user-nya sendiri, tetapi menggunakan `AuthMiddleware`, `PasswordHasher`, dan form component standar dari Core. |
| **Migrasi Database** | CLI Migration Runner Ringan di Core | Menjalankan skema dasar (`users`, `sessions`, `migrations`) dan migrasi spesifik domain produk secara teratur. |

---

## 3. Struktur Direktori Workspace

```text
syntaxdocs/
├── packages/
│   ├── core-php/                       # Shared Core Backend (PHP Package)
│   │   ├── composer.json               # Definisi package: syntax/core-php (PSR-4: Syntax\Core\)
│   │   ├── bin/
│   │   │   └── core                    # CLI Executable (php bin/core migrate)
│   │   ├── src/
│   │   │   ├── Application.php         # Entrypoint bootstrap core
│   │   │   ├── Database/
│   │   │   │   ├── ConnectionManager.php   # Multi-connection PDO factory
│   │   │   │   ├── QueryBuilder.php        # Fluent query builder ringan
│   │   │   │   ├── MigrationRunner.php     # CLI database migration runner
│   │   │   │   └── Schema/                 # Skema tabel dasar (users, sessions)
│   │   │   ├── Http/
│   │   │   │   ├── Request.php         # HTTP Request abstraction (PSR-7 inspired)
│   │   │   │   ├── Response.php        # HTTP Response & JSON emitter
│   │   │   │   ├── Router.php          # URL matching, methods, route-groups
│   │   │   │   └── Middleware/
│   │   │   │       ├── MiddlewareInterface.php # Kontrak middleware (handle($req, $next))
│   │   │   │       ├── Pipeline.php            # Onion middleware dispatcher
│   │   │   │       ├── CorsMiddleware.php
│   │   │   │       ├── SecurityHeadersMiddleware.php
│   │   │   │       ├── SessionMiddleware.php
│   │   │   │       └── CsrfMiddleware.php
│   │   │   ├── Auth/
│   │   │   │   ├── AuthManager.php     # Helper Auth::user(), Auth::check(), Auth::attempt()
│   │   │   │   ├── AuthMiddleware.php  # Guard route middleware
│   │   │   │   ├── PasswordHasher.php  # Argon2id / Bcrypt wrapper
│   │   │   │   └── TokenDriver.php     # Bearer/JWT token generator & validator
│   │   │   ├── View/
│   │   │   │   ├── Component.php       # Component::render('modal', $props)
│   │   │   │   └── ViewEngine.php      # Master layout & partial renderer
│   │   │   └── Support/
│   │   │       ├── Env.php             # .env parser
│   │   │       ├── Validator.php       # Input validation engine
│   │   │       ├── Logger.php          # File logging
│   │   │       └── Helpers.php         # Global helpers (env, view, response, sanitize)
│   │   └── templates/                  # Base HTML component snippets
│   │       ├── navbar.php
│   │       ├── modal.php
│   │       ├── card.php
│   │       ├── table.php
│   │       ├── form-group.php
│   │       └── toast.php
│   │
│   └── ui-cdn/                         # Shared UI Design System (Central Static Assets)
│       ├── package.json                # Build tooling (esbuild / terser / clean-css)
│       ├── public/                     # Folder statis yang disajikan oleh Web Server/CDN
│       │   ├── css/
│       │   │   ├── tokens.css          # CSS Variables (colors, spacing, fonts, radius)
│       │   │   ├── base.css            # CSS reset, body, typography, container
│       │   │   ├── components.css      # Styling BEM komponen UI (.c-*)
│       │   │   └── core.min.css        # Bundled & minified stylesheet
│       │   └── js/
│       │       ├── core.js             # Vanilla JS event delegator (data-attributes)
│       │       └── core.min.js         # Minified JavaScript library
│       └── components/                 # Spesifikasi markup HTML referensi per komponen
│           ├── navbar.html
│           ├── modal.html
│           └── card.html
│
├── apps/                               # Aplikasi Produk Mandiri
│   ├── cms/                            # Produk 1: Content Management System
│   │   ├── composer.json               # Require "syntax/core-php": "@dev"
│   │   ├── .env                        # DB_DATABASE=db_cms, STATIC_CDN_URL=...
│   │   ├── config/                     # Konfigurasi aplikasi
│   │   ├── database/
│   │   │   └── migrations/             # Migrasi khusus tabel CMS (articles, pages)
│   │   ├── public/
│   │   │   └── index.php               # Front controller web root
│   │   ├── routes/
│   │   │   └── web.php                 # Route definitions
│   │   ├── src/
│   │   │   └── Controllers/            # Domain logic CMS
│   │   └── views/
│   │       ├── layouts/main.php        # Layout memuat CDN CSS & JS
│   │       └── articles/index.php
│   │
│   └── school/                         # Produk 2: Sistem Informasi Sekolah
│       ├── composer.json
│       ├── .env                        # DB_DATABASE=db_school
│       └── ...
│
└── docs/
    ├── specs/                          # Kontrak Arsitektur Multi-Bahasa
    │   ├── api-contract.json           # JSON Envelope specification
    │   ├── components-spec.json        # Prop schemas untuk komponen UI
    │   └── portability-guide.md        # Panduan porting ke Node.js & Golang
    └── superpowers/specs/              # Arsip spesifikasi arsitektur
```

---

## 4. Rincian Arsitektur Backend PHP Core (`packages/core-php`)

### 4.1. Request, Middleware, dan Routing Pipeline

Arsitektur HTTP mengadopsi mekanisme *Onion Pipeline* standar PSR-15:
1. `Request`: Mengkapsulasi `$_GET`, `$_POST`, `$_SERVER`, cookies, dan headers ke objek immutable yang aman.
2. `Pipeline`: Mengeksekusi tumpukan middleware secara berurutan.
   - Global Middlewares: `CorsMiddleware` -> `SecurityHeadersMiddleware` -> `SessionMiddleware` -> `CsrfMiddleware`.
   - Route Middlewares: Dipasang spesifik per rute (misal: `AuthMiddleware`, `RoleMiddleware`).
3. `Router`: Mendukung parameter dinamis (misal `/articles/{id}`), pengelompokan rute (`Router::group(['prefix' => '/admin', 'middleware' => [AuthMiddleware::class]], ...)`), dan pemetaan ke Controller.
4. `Response`: Menyediakan helper respon cepat:
   - `Response::json($data, $status = 200)`
   - `Response::html($content, $status = 200)`
   - `Response::redirect($url)`

### 4.2. Database & Multi-Connection Manager

- **PDO Wrapper**: Menggunakan prepared statements secara default untuk pencegahan mutlak serangan SQL Injection.
- **Multi-Connection Pooling**:
  ```php
  // Default connection ke database produk
  $users = DB::table('users')->where('active', 1)->get();

  // Koneksi sekunder (misal ke database SSO bersama di masa depan)
  $centralUser = DB::connection('auth_central')->table('accounts')->where('email', $email)->first();
  ```
- **Transaction Support**:
  ```php
  DB::beginTransaction();
  try {
      DB::table('orders')->insert([...]);
      DB::table('order_items')->insert([...]);
      DB::commit();
  } catch (\Throwable $e) {
      DB::rollBack();
      throw $e;
  }
  ```

### 4.3. CLI Migration Runner

Core menyediakan CLI migration tool mandiri:
- Perintah: `php bin/core migrate [--connection=default]`
- Membaca file migrasi terstruktur di `database/migrations/` (format: `YYYY_MM_DD_HHMMSS_nama_migrasi.php`).
- Mencatat histori eksekusi pada tabel metadata `_migrations` di database target.
- Core menyediakan migrasi bawaan untuk tabel `users` (id, email, password, name, created_at, updated_at) dan `sessions`.

### 4.4. Autentikasi & Keamanan

- **Password Hashing**: Menggunakan algoritma native PHP modern (`PASSWORD_ARGON2ID` dengan fallback ke `PASSWORD_BCRYPT`).
- **Session Management**: Cookie sesi terenkripsi dengan atribut `HttpOnly`, `SameSite=Lax`, dan flag `Secure` otomatis aktif di HTTPS.
- **CSRF Protection**: Token anti-pemalsuan otomatis divalidasi pada semua request non-idempoten (POST, PUT, DELETE).
- **XSS Sanitization**: Helper template melakukan auto-escaping (`htmlspecialchars($val, ENT_QUOTES, 'UTF-8')`).

---

## 5. Rincian Frontend System & UI CDN (`packages/ui-cdn`)

### 5.1. CSS Design Tokens & Theming

File `tokens.css` mendefinisikan variabel global:
```css
:root {
  --color-primary: #2563eb;
  --color-primary-hover: #1d4ed8;
  --color-surface: #ffffff;
  --color-background: #f8fafc;
  --color-text: #0f172a;
  --color-text-muted: #64748b;
  --color-danger: #ef4444;
  --color-success: #10b981;
  --font-sans: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  --radius-sm: 0.25rem;
  --radius-md: 0.5rem;
  --radius-lg: 0.75rem;
  --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
}
```
**Kemudahan Re-theming**: Aplikasi `school` dapat mengubah tema ke nuansa hijau zamrud hanya dengan menambahkan:
```css
/* apps/school/public/theme.css */
:root {
  --color-primary: #059669;
  --color-primary-hover: #047857;
}
```
Seluruh komponen UI (tombol, navbar, border aktif) otomatis berubah warna tanpa memodifikasi file CDN.

### 5.2. Vanilla JavaScript Event Engine (`core.js`)

`core.js` berjalan dengan ukuran minimal (<15KB minified), tanpa library eksternal, memanfaatkan global event delegation:
```javascript
// Contoh arsitektur event delegation di core.js
document.addEventListener('click', (e) => {
  // Modal Toggle
  const modalToggle = e.target.closest('[data-toggle="modal"]');
  if (modalToggle) {
    e.preventDefault();
    const targetSelector = modalToggle.getAttribute('data-target');
    const modalEl = document.querySelector(targetSelector);
    if (modalEl) CoreUI.Modal.open(modalEl);
    return;
  }

  // Modal Dismiss
  const modalDismiss = e.target.closest('[data-dismiss="modal"]');
  if (modalDismiss) {
    e.preventDefault();
    const modalEl = modalDismiss.closest('.c-modal');
    if (modalEl) CoreUI.Modal.close(modalEl);
    return;
  }

  // Dropdown Toggle
  const dropdownToggle = e.target.closest('[data-toggle="dropdown"]');
  if (dropdownToggle) {
    e.preventDefault();
    const dropdownMenu = dropdownToggle.nextElementSibling;
    dropdownMenu?.classList.toggle('is-open');
    return;
  }

  // Sidebar Toggle
  const sidebarToggle = e.target.closest('[data-toggle="sidebar"]');
  if (sidebarToggle) {
    e.preventDefault();
    document.body.classList.toggle('has-sidebar-open');
  }
});
```

### 5.3. PHP Component Engine (`Component::render`)

Contoh pemanggilan komponen di view aplikasi produk:
```php
<?= Component::render('modal', [
    'id' => 'deleteConfirmModal',
    'title' => 'Konfirmasi Hapus Siswa',
    'body' => 'Data nilai dan presensi siswa ini akan dihapus secara permanen.',
    'confirm_label' => 'Ya, Hapus Data',
    'confirm_action' => '/students/delete/' . $student['id'],
    'variant' => 'danger'
]) ?>
```
Output HTML yang dihasilkan oleh template internal:
```html
<div class="c-modal" id="deleteConfirmModal" aria-hidden="true" role="dialog">
  <div class="c-modal__backdrop" data-dismiss="modal"></div>
  <div class="c-modal__dialog">
    <div class="c-modal__header">
      <h3 class="c-modal__title">Konfirmasi Hapus Siswa</h3>
      <button class="c-modal__close" data-dismiss="modal" aria-label="Tutup">&times;</button>
    </div>
    <div class="c-modal__body">
      Data nilai dan presensi siswa ini akan dihapus secara permanen.
    </div>
    <div class="c-modal__footer">
      <button type="button" class="c-btn c-btn--secondary" data-dismiss="modal">Batal</button>
      <form method="POST" action="/students/delete/10" class="d-inline">
        <?= Csrf::field() ?>
        <button type="submit" class="c-btn c-btn--danger">Ya, Hapus Data</button>
      </form>
    </div>
  </div>
</div>
```

---

## 6. Katalog & Kontrak Komponen Standar

Core menyediakan komponen dasar siap pakai dengan styling konsisten:
1. **Navbar** (`navbar.php`): Header navigasi dengan brand logo, tautan menu responsif, dropdown profil, dan toggle mobile.
2. **Sidebar** (`sidebar.php`): Navigasi vertikal collapsible untuk dashboard admin/aplikasi.
3. **Card** (`card.php`): Kontainer konten dengan header, body, dan footer.
4. **Modal** (`modal.php`): Dialog pop-up interaktif untuk konfirmasi, form, atau informasi detail.
5. **Table** (`table.php`): Tabel data responsif dengan styling zebra, hover, dan pagination footer.
6. **Form Group** (`form-group.php`): Wrapper input label, field text/select/textarea, helper text, dan error validation state.
7. **Toast / Alert** (`toast.php` / `alert.php`): Notifikasi feedback sukses, peringatan, atau error.

---

## 7. Blueprint Portabilitas Node.js & Golang (Future Roadmap)

Agar produk masa depan dapat menggunakan Node.js atau Golang tanpa merusak ekosistem yang sudah terbangun, ikuti 4 aturan kontrak berikut:

### 7.1. Zero-Touch Frontend Integration
Aplikasi berbasis Node.js (misal Fastify/Express) atau Golang (misal Chi/Fiber) merender HTML master layout dengan tag CDN yang persis sama:
```html
<link rel="stylesheet" href="http://cdn.company.local/css/core.min.css">
<script src="http://cdn.company.local/js/core.min.js"></script>
```
Semua styling BEM dan interaksi JavaScript (modal, dropdown, sidebar) langsung berfungsi tanpa instalasi paket NPM tambahan.

### 7.2. Keseragaman Variabel Lingkungan (`.env`)
Seluruh produk lintas bahasa wajib menggunakan standar variabel lingkungan yang sama:
```env
APP_NAME=SyntaxApp
APP_ENV=production
APP_PORT=8000
APP_KEY=secret_key_base64_string

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_product
DB_USERNAME=root
DB_PASSWORD=secret

STATIC_CDN_URL=http://cdn.company.local
```

### 7.3. Kontrak JSON API Envelope (Unified Response)
Jika produk berkomunikasi via REST API, format struktur JSON wajib mengikuti spesifikasi berikut:
```json
{
  "success": true,
  "code": 200,
  "message": "Data berhasil dimuat",
  "data": {},
  "errors": null,
  "meta": {
    "page": 1,
    "per_page": 25,
    "total": 120
  }
}
```

### 7.4. Pemetaan Pola Kode (Architecture Translation Matrix)

| Konsep Arsitektur | PHP (Syntax Core PHP) | Node.js (Syntax Core Node) | Golang (Syntax Core Go) |
| :--- | :--- | :--- | :--- |
| **Routing** | `Router::get('/items', [ItemCtrl::class, 'index'])` | `router.get('/items', itemCtrl.index)` | `r.Get("/items", itemCtrl.Index)` |
| **Middleware** | `public function handle(Request $req, callable $next): Response` | `(req: Request, res: Response, next: NextFunction) => void` | `func(next http.Handler) http.Handler` |
| **DB Query** | `DB::table('users')->where('id', 1)->first()` | `db('users').where({ id: 1 }).first()` | `db.Table("users").Where("id = ?", 1).First(&user)` |
| **Component Helper** | `Component::render('modal', $props)` | `renderComponent('modal', props)` | `RenderComponent("modal", props)` |

---

## 8. Strategi Verifikasi & Testing

1. **Unit Testing Core PHP**:
   - Pengujian `Router`: Pencocokan route, parameter regex, middleware resolution.
   - Pengujian `Pipeline`: Urutan eksekusi middleware dan penanganan exception.
   - Pengujian `QueryBuilder`: Akurasi SQL generation dan parameter binding PDO.
   - Pengujian `Component Engine`: Validasi output HTML dan escaping karakter berbahaya.
2. **Visual & CDN Testing**:
   - Pengujian visual layout komponen di berbagai resolusi layar (Mobile, Tablet, Desktop).
   - Pengujian fungsi Vanilla JS event delegation (buka/tutup modal, dropdown, dan sidebar) di browser modern.
3. **Integration Testing dengan Demo Apps**:
   - Menjalankan 2 aplikasi contoh (`apps/cms` dan `apps/school`) yang mengonsumsi `packages/core-php` via Composer dan `packages/ui-cdn` via CDN lokal.
   - Menjalankan migrasi database di kedua database yang berbeda untuk memverifikasi isolasi multi-database.

---

## 9. Kesimpulan & Langkah Selanjutnya

Dokumen ini menjadi acuan tunggal (*single source of truth*) dalam pengembangan ekosistem shared core syntaxdocs. 
Setelah spesifikasi ini ditinjau dan disetujui, langkah berikutnya adalah memanggil skill `writing-plans` untuk merinci tahapan implementasi kode secara berurutan (TDD, pembuatan packages, pengujian, dan demo apps).

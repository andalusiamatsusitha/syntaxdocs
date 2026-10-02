# Implementation Plan: Syntax Orbital Ground Station (3D Parallax Mission Control)

- **Tanggal**: 2026-10-02
- **Spec Acuan**: [2026-10-02-orbital-ground-station-3d-design.md](file:///c:/Users/andal/Documents/Project/syntaxdocs/docs/superpowers/specs/2026-10-02-orbital-ground-station-3d-design.md)
- **Status**: Siap Dieksekusi Setelah Persetujuan (Pending Plan Approval)

---

## 1. Rencana Tahapan Eksekusi (Phase Roadmap)

### Fase 1: Struktur Aplikasi & Skema Basis Data (`apps/orbital`)
Fokus: Menyiapkan direktori aplikasi produk ketiga, konfigurasi environment, dan skema migrasi database `db_orbital` (MySQL dengan fallback otomatis SQLite lokal).
- [x] **Task 1.1: Setup Folder & Konfigurasi**
  - Buat folder `apps/orbital/`, `apps/orbital/public/`, `apps/orbital/public/js/`, `apps/orbital/routes/`, `apps/orbital/database/migrations/`.
  - Buat `apps/orbital/composer.json` (menghubungkan ke `syntax/core-php`).
  - Buat `apps/orbital/.env.example` dan `apps/orbital/.env` dengan pengaturan `DB_DATABASE=db_orbital`, `DB_FALLBACK_SQLITE=true`.
- [x] **Task 1.2: Skema Tabel Satelit & Telemetri**
  - Buat file migrasi `database/migrations/001_create_orbital_tables.php` yang mendefinisikan tabel `satellites`, `telemetry_logs`, dan `uplink_commands`.
  - Buat seeder data satelit realistis: `SYNTAX-OBSERVER-01` (LEO), `NOCTIS-SAR-04` (Polar SSO), `HELIOS-SOLAR-09` (MEO), dan `AETHER-RELAY-02` (GEO).

---

### Fase 2: Backend Routes & REST API Endpoints
Fokus: Mengimplementasikan routing Core PHP dan endpoint RESTful sesuai kontrak `docs/specs/api-contract.json`.
- [x] **Task 2.1: Entrypoint & Router Bootstrap**
  - Buat `apps/orbital/public/index.php` yang menginisialisasi `Syntax\Core\Application`.
  - Daftarkan routing web (`routes/web.php`) dan routing API (`routes/api.php`).
- [x] **Task 2.2: REST API Endpoints**
  - `GET /api/satellites`: Mengembalikan katalog satelit terformat JSON envelope standar (`docs/specs/api-contract.json`).
  - `GET /api/telemetry/{id}`: Mengembalikan data sinyal radio (downlink SNR), suhu sensor, dan posisi orbit terkini.
  - `POST /api/commands`: Menerima instruksi uplink ground command (misal: "REORIENT_SOLAR", "SENSOR_CALIBRATE", "PING") dan menyimpan ke log perintah.

---

### Fase 3: Theming & Antislop HUD CSS
Fokus: Merancang sistem token visual dan styling HUD antariksa di `apps/orbital/public/theme.css` yang meng-override CDN token tanpa generic slop.
- [x] **Task 3.1: Token Astro-Navy & Telemetry Radar**
  - Definisikan variabel warna: `--color-primary: #06b6d4`, `--color-accent-amber: #f59e0b`, `--color-accent-emerald: #10b981`, `--color-bg-space: #060913`.
  - Styling font monospace telemetri, panel glassmorphism teknikal (`.c-hud-panel`), status reticle crosshair, dan badge status sinyal.
- [x] **Task 3.2: Layout Web View & Integrasi CDN**
  - Di `routes/web.php`, rancang master layout HTML yang memuat `packages/ui-cdn/public/css/core.min.css`, `theme.css`, dan script CDN `core.min.js`.
  - Render komponen navbar atas menggunakan `Component::render('navbar')`, modal konfirmasi command menggunakan `Component::render('modal')`, serta panel kontrol samping.

---

### Fase 4: Engine 3D Three.js & Parallax Multi-Layer
Fokus: Membangun sistem rendering WebGL 60fps dengan Vanilla Three.js (ESM native) di `apps/orbital/public/js/orbital-engine.js`.
- [x] **Task 4.1: Scene Setup, Lighting & Starfield**
  - Inisialisasi Three.js WebGLRenderer, PerspectiveCamera, dan Scene.
  - Ambient light halus + Directional sunlight yang menciptakan garis terminator siang/malam di bola bumi.
  - Partikel 2.500 bintang dengan variasi kedalaman dan parallax drift.
- [x] **Task 4.2: Bola Bumi Prosedural & Atmosfer Fresnel Haze**
  - Geometri sphere beresolusi tinggi dengan tekstur permukaan bumi, garis khatulistiwa/meridian, dan custom Fresnel cyan atmospheric glow shader.
- [x] **Task 4.3: Lintasan Orbit & Model Satelit Interaktif**
  - Render kurva lintasan orbit elips 3D 3 dimensi (LEO, MEO, GEO) dalam warna telemetry amber semi-transparan.
  - Pembuatan model 3D satelit prosedural (badan bus satelit emas/perak, panel surya biru ganda, antena komunikasi, dan beacon berkedip).
  - Loop perhitungan posisi orbit real-time berdasarkan sudut inklinasi dan kecepatan revolusi.
- [x] **Task 4.4: Dual Parallax (Mouse-Look + Scroll Storyline) & Target Lock**
  - Mouse-parallax: Kamera berotasi halus merespons pergerakan kursor mouse.
  - Scroll-storyline: Transisi kamera mulus antar 4 stage (Overview Luar Angkasa -> Armada LEO -> Target Lock Satelit -> Ground Uplink).
  - Raycaster: Hover menampilkan tooltip status; klik satelit mengarahkan kamera secara lerp ke satelit tersebut.
  - Dukungan toggle OrbitControls untuk rotasi bebas 360°.

---

### Fase 5: Controller HUD & Integrasi Interaktif
Fokus: Menghubungkan visual 3D dengan panel data HUD di `apps/orbital/public/js/orbital-hud.js`.
- [x] **Task 5.1: Live Telemetry Feeder**
  - Fetch data satelit dari `/api/satellites` dan render ke daftar satelit di HUD samping.
  - Update dinamik koordinat geografis (lat/lon/alt), kecepatan, dan sinyal transponder.
- [x] **Task 5.2: Ground Command Uplink Execution**
  - Tombol aksi untuk mengirimkan perintah ground station (membuka modal konfirmasi via `Component::render('modal')`).
  - Pengiriman POST request ke `/api/commands` dan visualisasi sinar transmisi laser dari stasiun bumi ke satelit.

---

### Fase 6: Pengujian, Verifikasi & Dukungan Preview
Fokus: Menjamin seluruh sistem berjalan mulus dan lulus verifikasi kontrak.
- [x] **Task 6.1: Verifikasi Kontrak & Asset**
  - Update `tests/verify_contracts.js` untuk mencakup verifikasi eksistensi file `apps/orbital`.
  - Jalankan pengujian otomatis `node tests/verify_contracts.js`.
- [x] **Task 6.2: Konfigurasi Nginx / Preview Server**
  - Update konfigurasi Nginx di `docker/nginx/conf.d/` dan `preview.js` agar produk orbital dapat diakses langsung di browser.
- [x] **Task 6.3: Uji Tinjau Visual di Browser**
  - Uji rendering WebGL, kelancaran 60fps, responsivitas parallax kursor, dan eksekusi modal dialog.

---

## 2. Cara Kerja Bertahap (Execution Protocol)
Eksekusi rencana ini akan dijalankan secara berurutan mulai dari **Fase 1**, dilanjutkan ke **Fase 2 s.d. Fase 6**. Di setiap akhir fase, status verifikasi akan dipastikan sebelum berlanjut ke tahap berikutnya.

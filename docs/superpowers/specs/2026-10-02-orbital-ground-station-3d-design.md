# Architectural Design Specification: Syntax Orbital Ground Station (3D Parallax Mission Control)

- **Tanggal**: 2026-10-02
- **Status**: Siap Ditinjau (Draft for Approval)
- **Aplikasi**: `apps/orbital`
- **Cakupan**: Perancangan produk baru berbasis Vanilla Three.js (ESM), Shared Core PHP Framework, Multi-Connection Database (`db_orbital`), dan Shared UI CDN Design System.

---

## 1. Ringkasan Eksekutif & Tujuan Produk

**Syntax Orbital Ground Station** adalah aplikasi web interaktif pemantau konstelasi satelit dan telemetri cuaca antariksa (*space weather*). Produk ini dirancang sebagai etalase produk ketiga di dalam ekosistem monorepo **SyntaxDocs**, mendemonstrasikan kapabilitas integrasi visual 3D berkinerja tinggi (60fps WebGL) dengan sistem arsitektur backend bersama (*Shared Core*).

### Tujuan Utama:
1. **Fungsionalitas Parallax Riil (Purpose-Driven)**: Parallax 3D bukan sekadar hiasan visual, melainkan merepresentasikan layer kedalaman orbit nyata: deep space, atmosfer bumi, lintasan orbit LEO/GEO, model satelit aktif, dan ground station radar.
2. **Kendali Kamera Presisi & Target Lock**: Mendukung *scroll-driven storyline* (overview luar angkasa hingga ground uplink), mouse-look tilt parallax, raycasting klik satelit dengan camera lerp halus, serta tombol toggle kendali bebas 360° (*OrbitControls*).
3. **Pemanfaatan Maksimal Shared Core & UI CDN**: Mengonsumsi `packages/core-php` untuk routing, database PDO, REST API standard envelope, dan rendering modal dialog / kartu HUD menggunakan `Component::render()`, dipadukan dengan aset terpusat `packages/ui-cdn`.
4. **Anti-Slop & Identitas Mandiri**: Bebas dari generic AI slop (tidak menggunakan gradasi ungu-cyan kabur atau kubus mengambang tanpa makna). Mengadopsi tema *Astro-Navy & Telemetry Amber/Cyan Radar* dengan tipografi monospace presisi.

---

## 2. Keputusan Arsitektur Utama (ADR Summary)

| Domain | Keputusan Arsitektur | Rationale & Dampak |
| :--- | :--- | :--- |
| **Lokasi Modul** | `apps/orbital/` | Menjadi produk mandiri ketiga di dalam monorepo sejajar dengan `apps/cms` dan `apps/school`. |
| **Engine 3D** | Vanilla Three.js via Browser ESM | Menggunakan Three.js modern (r128+) via ES Module native langsung di browser. Bebas bundler/npm build yang berat, waktu load instan, dan kontrol WebGL maksimal. |
| **Parallax Engine** | Multi-Layer Dual-Input (Scroll + Cursor) | Scroll mengatur transisi altitude kamera dan tahapan misi; posisi kursor menggeser camera look-at secara halus (*mouse parallax*). |
| **Backend & Routing** | `Syntax\Core\Application` (PHP) | Memanfaatkan routing, onion middleware pipeline, dan session guard dari `packages/core-php`. |
| **Database Model** | Dedicated `db_orbital` (MySQL + SQLite Fallback) | Menggunakan `ConnectionManager` Core PHP. Tabel: `satellites`, `telemetry_logs`, dan `uplink_commands`. Fallback otomatis ke SQLite lokal jika container MySQL tidak aktif. |
| **Komunikasi Data** | RESTful JSON API (`docs/specs/api-contract.json`) | Endpoint `/api/satellites`, `/api/telemetry/{id}`, dan POST `/api/commands` mengembalikan JSON envelope terstandarisasi. |
| **Theming & UI** | BEM CSS Tokens (`theme.css`) + UI CDN | Menimpa `--color-primary` menjadi `#06b6d4` (laser cyan), aksen `#f59e0b` (telemetry amber), dan background obsidian cosmic `#060913`. |

---

## 3. Struktur Direktori Aplikasi (`apps/orbital`)

```text
apps/orbital/
├── .env.example                  # Konfigurasi APP_ENV, DB_DATABASE (db_orbital), STATIC_CDN_URL
├── .env                          # Local environment
├── composer.json                 # Referensi path repository ke packages/core-php
├── database/
│   └── migrations/               # Migrasi khusus domain orbital (satellites & telemetries)
│       └── 001_create_orbital_tables.php
├── public/
│   ├── index.php                 # Entrypoint bootstrap web
│   ├── theme.css                 # Custom design tokens HUD antariksa
│   └── js/
│       ├── orbital-engine.js     # Three.js scene, lighting, Earth, particles, raycaster, lerp
│       └── orbital-hud.js        # Controller HUD, telemetri live updater, event listener
└── routes/
    ├── api.php                   # Endpoint REST: /api/satellites, /api/telemetry, /api/commands
    └── web.php                   # Web view route: GET /
```

---

## 4. Arsitektur 3D WebGL & Pipeline Parallax

### 4.1. Lapisan Kedalaman 3D (Z-Index / Depth Planes)
1. **Background Starfield (`z: -2000` s.d. `-800`)**:
   - 2.500 partikel bintang dengan variasi ukuran dan warna temperatur bintang (biru, putih, amber halus).
   - Bergerak paling lambat saat mouse bergerak (faktor parallax 0.05).
2. **Earth Celestial Sphere (`radius: 120`, center at `(0, 0, 0)`)**:
   - Bola 3D prosedural dengan material MeshStandardMaterial: tekstur permukaan bumi, normal map benua, specular water reflections, dan garis terminator pencahayaan matahari (directional light).
   - Atmospheric Haze: Mesh sphere kedua sedikit lebih besar dengan custom Fresnel glow shader (cyan haze).
   - Latitude/Longitude Wireframe Grid: Garis koordinat kartografi antariksa transparan.
3. **Orbital Tracks Layer (LEO & GEO)**:
   - Lintasan orbit elips berbentuk 3D curves (`THREE.EllipseCurve` / `THREE.BufferGeometry`) dengan warna telemetry amber semi-transparan (`#f59e0b`).
   - Kecondongan bidang orbit (*orbital inclination*) yang bervariasi: ISS (51.6°), Polar Sun-Synchronous (98°), Geostationary (0°).
4. **Active Satellite Constellation**:
   - Mesh satelit prosedural: badan utama satelit (chassis emas/perak), panel surya biru fotovoltaik ganda, dan antena parabola komunikasi.
   - Beacon LED berkedip (`THREE.PointLight` / billboard sprite) menunjukkan status operasional (hijau = aktif, amber = warning, cyan = uplink).
5. **Raycast Reticle & Tracking Vector**:
   - Garis vektor laser 3D yang menghubungkan Ground Station di permukaan bumi langsung ke satelit yang sedang dikunci (*target locked*).
6. **Foreground Glass HUD**:
   - Lapisan HTML/DOM di atas WebGL canvas berisi panel telemetri, altimeter, kompas ground station, dan terminal log.

### 4.2. Tahapan Storyline Scroll Parallax
- **Stage 1 (Scroll 0% - 25%): Orbital Constellation Overview**
  - Kamera: `position(0, 80, 450)`, `lookAt(0, 0, 0)`
  - Fokus: Pemandangan luas seluruh bumi dan seluruh jaringan satelit.
- **Stage 2 (Scroll 25% - 55%): Low Earth Orbit Fleet Surveillance**
  - Kamera: Bergerak maju meluncur ke `position(0, 40, 240)`
  - Fokus: Lintasan orbit LEO, kecepatan partikel meningkat, reticle data armada satelit muncul.
- **Stage 3 (Scroll 55% - 85%): Satellite Target Lock & Telemetri**
  - Kamera: Fokus mengunci ke salah satu satelit utama (misal: `SYNTAX-OBSERVER-01`), mendekat ke `position(sat.x + 30, sat.y + 15, sat.z + 40)`
  - Panel telemetri samping otomatis memuat status solar panel, suhu internal, dan sinyal transponder.
- **Stage 4 (Scroll 85% - 100%): Ground Station Uplink Deck**
  - Kamera: Perspektif atmosfer dekat permukaan bumi menghadap ke atas menuju satelit saat sinyal data dipancarkan.
  - Form dialog modal untuk mengirimkan *Command Uplink* (reorientasi panel surya, kalibrasi sensor, ping telemetri).

---

## 5. Skema Basis Data (`db_orbital`)

### Tabel: `satellites`
| Kolom | Tipe | Deskripsi |
| :--- | :--- | :--- |
| `id` | INT AUTO_INCREMENT PRIMARY KEY | ID Internal |
| `norad_id` | VARCHAR(20) UNIQUE | Nomor Registrasi Antariksa (NORAD Cat ID) |
| `name` | VARCHAR(100) NOT NULL | Nama Satelit (misal: SYNTAX-OBSERVER-1) |
| `orbit_type` | ENUM('LEO', 'MEO', 'GEO', 'SSO') | Tipe Orbit |
| `altitude_km` | FLOAT NOT NULL | Ketinggian orbit (contoh: 540.5 km) |
| `velocity_kms` | FLOAT NOT NULL | Kecepatan orbit (contoh: 7.62 km/s) |
| `inclination_deg` | FLOAT NOT NULL | Sudut kemiringan orbit terhadap khatulistiwa |
| `period_min` | FLOAT NOT NULL | Waktu satu putaran penuh mengelilingi bumi |
| `status` | ENUM('active', 'standby', 'calibrating', 'decaying') | Status operasional |
| `battery_pct` | INT DEFAULT 100 | Kapasitas daya baterai onboard |
| `solar_output_w` | FLOAT DEFAULT 1200.0 | Daya yang dihasilkan panel surya (Watt) |
| `last_contact` | TIMESTAMP | Waktu kontak sinyal radio terakhir |

### Tabel: `telemetry_logs`
| Kolom | Tipe | Deskripsi |
| :--- | :--- | :--- |
| `id` | BIGINT AUTO_INCREMENT PRIMARY KEY | ID Log |
| `satellite_id` | INT REFERENCES satellites(id) | Relasi ke satelit |
| `downlink_snr_db` | FLOAT | Rasio sinyal sinyal downlink (Signal-to-Noise Ratio) |
| `payload_temp_c` | FLOAT | Suhu sensor payload (°C) |
| `sub_lat` | FLOAT | Koordinat sub-satellite latitude |
| `sub_lon` | FLOAT | Koordinat sub-satellite longitude |
| `created_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | Waktu pembacaan |

### Tabel: `uplink_commands`
| Kolom | Tipe | Deskripsi |
| :--- | :--- | :--- |
| `id` | BIGINT AUTO_INCREMENT PRIMARY KEY | ID Perintah |
| `satellite_id` | INT REFERENCES satellites(id) | Target satelit |
| `command_type` | VARCHAR(50) | Jenis instruksi (PING, REORIENT_SOLAR, SENSOR_CALIBRATE) |
| `payload_args` | TEXT NULL | Parameter JSON tambahan |
| `status` | ENUM('queued', 'transmitted', 'acknowledged', 'failed') | Status eksekusi |
| `created_at` | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | Waktu pengiriman perintah |

---

## 6. Spesifikasi Antarmuka REST API

Semua endpoint mengembalikan format standar envelope sesuai [`docs/specs/api-contract.json`](docs/specs/api-contract.json):

### 6.1. `GET /api/satellites`
Mengembalikan daftar seluruh satelit aktif dengan parameter orbit untuk plotting 3D.
```json
{
  "success": true,
  "code": 200,
  "message": "Satellites retrieved successfully",
  "data": [
    {
      "id": 1,
      "norad_id": "SYN-48201",
      "name": "SYNTAX-OBSERVER-01",
      "orbit_type": "LEO",
      "altitude_km": 540.5,
      "velocity_kms": 7.62,
      "inclination_deg": 51.6,
      "status": "active",
      "battery_pct": 98,
      "solar_output_w": 1420.5
    }
  ]
}
```

### 6.2. `GET /api/telemetry/{id}`
Mengambil pembacaan telemetri terkini untuk satelit spesifik.

### 6.3. `POST /api/commands`
Mengirimkan instruksi ground command ke satelit (misal: "Recalibrate Gyroscope").
- Request Body: `{"satellite_id": 1, "command_type": "REORIENT_SOLAR"}`
- Response: Status `transmitted` beserta waktu eksekusi.

---

## 7. Desain Sistem HUD & Anti-Slop Visual Direction

### 7.1. Tokens & Identitas Visual (`theme.css`)
```css
:root {
  --color-primary: #06b6d4;             /* Laser Cyan Radar */
  --color-primary-hover: #0891b2;
  --color-primary-light: rgba(6, 182, 212, 0.15);
  --color-accent-amber: #f59e0b;        /* Orbital Vector Amber */
  --color-accent-emerald: #10b981;      /* Systems Operational Green */
  --color-bg-space: #060913;            /* Deep Obsidian Navy */
  --color-panel-glass: rgba(11, 19, 41, 0.82);
  --border-tech: 1px solid rgba(6, 182, 212, 0.28);
  --font-mono: 'JetBrains Mono', 'Space Mono', monospace;
}
```

### 7.2. Aturan Bebas Slop (Anti-Slop Gates)
- **Purpose Test**: Setiap elemen HUD memiliki fungsi operasional yang terukur (indikator status radio, kompas azimut, tombol command uplink). Tidak ada ornamen statis yang hanya berfungsi sebagai "pemanis".
- **Kontras & Keterbacaan**: Semua angka koordinat menggunakan kontras rasio minimal 7:1 terhadap latar belakang gelap.
- **Performa 60fps**: Polycount satelit dioptimalkan secara geometris (procedural low-to-mid poly), tekstur bumi dikompresi efisien, dan rendering loop menggunakan requestAnimationFrame dengan dynamic resolution scale jika terdeteksi hardware berkinerja rendah.

---

## 8. Rencana Pengujian & Verifikasi
1. **Automated Asset & Contract Test**: Memastikan route API mengembalikan status 200 dengan format JSON envelope sesuai `docs/specs/api-contract.json`.
2. **WebGL Context & Fallback Test**: Memastikan browser tanpa WebGL menampilkan notifikasi anggun (*graceful fallback*) ke tampilan tabel data satelit 2D.
3. **Responsive & Event Delegation Test**: Memastikan interaksi `data-toggle="modal"` dari `packages/ui-cdn` berfungsi mulus saat tombol kirim command diklik di dalam HUD 3D.

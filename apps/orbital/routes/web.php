<?php

use Syntax\Core\Http\Request;
use Syntax\Core\Http\Response;
use Syntax\Core\View\Component;

/** @var \Syntax\Core\Http\Router $router */

$router->get('/', function (Request $req) {
    $cdnUrl = env('STATIC_CDN_URL', 'http://localhost:8090');

    // 1. Render Navbar Component via Shared Core (Clean Mission Control Status Bar)
    $navbar = Component::render('navbar', [
        'brand' => 'Syntax Orbital Station',
        'brand_url' => '/',
        'brand_icon' => 'fa-solid fa-satellite-dish',
        'items' => [],
        'right_html' => '<div class="orbital-top-status"><span><span class="orbital-status-dot"></span> LIVE</span><span><i class="fa-regular fa-clock" style="margin-right:4px; opacity:0.75;"></i><span id="utc-live-clock">UTC 00:00:00</span></span><span class="c-badge c-badge--primary" style="background:#06b6d4; color:#050811; font-weight:700;"><i class="fa-solid fa-shield-halved" style="margin-right:4px;"></i>NORAD Active</span></div>'
    ]);

    // 2. Render Modal Component for Ground Command Transmission
    $modalBody = <<<HTML
        <div id="command-feedback" style="display:none;"></div>
        <form id="form-uplink-command">
            <input type="hidden" id="command-sat-id" value="1">
            
            <!-- Target Satellite Telemetry HUD Card -->
            <div class="orbital-modal-target-card">
                <div class="orbital-modal-target-icon">
                    <i class="fa-solid fa-satellite"></i>
                </div>
                <div class="orbital-modal-target-info">
                    <span class="orbital-modal-target-label">Target Satelit Terkunci</span>
                    <span id="modal-sat-name" class="orbital-modal-target-name">SYNTAX-OBSERVER-01 (SYN-48201)</span>
                </div>
            </div>

            <div class="c-form-group mb-4">
                <label for="command-type-select" class="c-form-label" style="font-family:var(--font-mono); color:#94a3b8; font-size:0.8rem; display:flex; align-items:center; gap:6px; margin-bottom:8px;">
                    <i class="fa-solid fa-code" style="color:var(--color-primary);"></i> Jenis Instruksi Command
                </label>
                <select id="command-type-select" class="c-form-select">
                    <option value="PING_TRANSPONDER">PING_TRANSPONDER (Uji Latensi Transponder Radio)</option>
                    <option value="REORIENT_SOLAR">REORIENT_SOLAR (Kalibrasi Orientasi Array Panel Surya)</option>
                    <option value="SENSOR_CALIBRATE">SENSOR_CALIBRATE (Kalibrasi Sensor Spektrometri & Suhu)</option>
                    <option value="SAFE_STANDBY">SAFE_STANDBY (Alihkan Satelit ke Mode Siaga Aman)</option>
                </select>
            </div>

            <!-- RF Specs Box -->
            <div class="orbital-modal-specs-box">
                <div class="orbital-modal-specs-item">
                    <i class="fa-solid fa-wifi" style="color:var(--color-primary);"></i>
                    <span>Uplink: <strong>14.250 GHz (Ku-Band)</strong></span>
                </div>
                <div class="orbital-modal-specs-item">
                    <i class="fa-solid fa-shield-halved" style="color:var(--color-accent-emerald);"></i>
                    <span>Enkripsi: <strong>AES-256-GCM</strong></span>
                </div>
            </div>

            <button type="submit" class="orbital-btn-uplink-submit">
                <i class="fa-solid fa-paper-plane"></i> Kirim Perintah Uplink
            </button>
        </form>
HTML;

    $modal = Component::render('modal', [
        'id' => 'commandUplinkModal',
        'title' => '<i class="fa-solid fa-tower-broadcast" style="margin-right:8px;"></i>Transmisi Perintah Ground Station',
        'body' => $modalBody,
        'cancel_label' => 'Tutup'
    ]);

    $html = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Syntax Orbital Ground Station | 3D Parallax Mission Control</title>
    <!-- 1. Central UI CDN Stylesheet -->
    <link rel="stylesheet" href="{$cdnUrl}/css/core.min.css?v=3">
    <!-- 2. Application Custom Theming -->
    <link rel="stylesheet" href="/theme.css?v=3">
    <!-- 3. Font Awesome 6 & Google Material Symbols (Vector Material Icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">
    <!-- 4. Importmap for Three.js Modern ES Modules -->
    <script type="importmap">
    {
      "imports": {
        "three": "https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.module.js",
        "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.160.0/examples/jsm/"
      }
    }
    </script>
</head>
<body class="theme-orbital">

    <!-- Navbar Component -->
    {$navbar}

    <!-- 3D WebGL Canvas Layer -->
    <div id="orbital-canvas-container"></div>

    <!-- 3D Hover Tooltip Reticle -->
    <div id="orbital-3d-tooltip"></div>

    <!-- Parallax Scroll Storyline Content -->
    <div class="orbital-storyline-container c-container">
        
        <!-- Stage 1: Hero Overview & Metrics Ticker -->
        <section id="stage-1" class="orbital-stage-section">
            <div class="orbital-stage-content">
                <span class="orbital-stage-badge">
                    <i class="fa-solid fa-satellite" style="margin-right:6px;"></i>
                    Tahap 01 // Orbital Constellation Overview
                </span>
                <h1 class="orbital-stage-title">Pemantauan Konstelasi Satelit Global</h1>
                <p class="orbital-stage-desc">
                    Platform kendali telemetri antariksa real-time berbasis WebGL 60fps dengan simulasi gerak orbit Keplerian, raycasting target lock, dan transmisi stasiun bumi Ku-Band.
                </p>
                <div style="display:flex; gap:14px; flex-wrap:wrap;">
                    <a href="#stage-2" class="c-btn c-btn--primary" style="font-family:var(--font-mono); font-weight:700; display:inline-flex; align-items:center; gap:8px;">
                        Jelajahi Armada LEO <i class="fa-solid fa-arrow-down"></i>
                    </a>
                    <button type="button" class="c-btn c-btn--secondary" data-toggle="modal" data-target="#commandUplinkModal" style="font-family:var(--font-mono); display:inline-flex; align-items:center; gap:8px;">
                        Kirim Command Uplink <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </div>

                <!-- Horizontal Live Metrics Ticker Bar -->
                <div class="orbital-metrics-ticker">
                    <div class="orbital-ticker-item">
                        <span class="orbital-ticker-label"><i class="fa-solid fa-satellite" style="margin-right:6px; color:#06b6d4;"></i>Armada Aktif</span>
                        <span class="orbital-ticker-value" style="color:#06b6d4;">4 Satelit</span>
                    </div>
                    <div class="orbital-ticker-item">
                        <span class="orbital-ticker-label"><i class="fa-solid fa-gauge-high" style="margin-right:6px; color:#94a3b8;"></i>Kecepatan Orbit</span>
                        <span class="orbital-ticker-value">7.62 km/s</span>
                    </div>
                    <div class="orbital-ticker-item">
                        <span class="orbital-ticker-label"><i class="fa-solid fa-signal" style="margin-right:6px; color:#10b981;"></i>Downlink Signal</span>
                        <span class="orbital-ticker-value" style="color:#10b981;">+19.4 dB</span>
                    </div>
                    <div class="orbital-ticker-item">
                        <span class="orbital-ticker-label"><i class="fa-solid fa-tower-broadcast" style="margin-right:6px; color:#f59e0b;"></i>Ku-Band Uplink</span>
                        <span class="orbital-ticker-value" style="color:#f59e0b;">14.25 GHz</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stage 2: LEO Fleet Surveillance & Interactive Pills -->
        <section id="stage-2" class="orbital-stage-section">
            <div class="orbital-stage-content">
                <span class="orbital-stage-badge"><i class="fa-solid fa-crosshairs" style="margin-right:6px;"></i>Tahap 02 // LEO Fleet Radar</span>
                <h2 class="orbital-stage-title">Pengawasan Orbit Rendah Bumi</h2>
                <p class="orbital-stage-desc">
                    Kamera meluncur turun ke Low Earth Orbit (LEO). Pilih salah satu satelit di bawah ini atau klik langsung objek 3D di layar untuk mengarahkan kamera mengunci target.
                </p>

                <!-- Interactive Satellite Selector Pills -->
                <div id="orbital-satellite-pills" class="orbital-satellite-pills">
                    <!-- Populated dynamically by orbital-hud.js -->
                </div>

                <div style="margin-top:24px;">
                    <a href="#stage-3" class="c-btn c-btn--secondary" style="font-family:var(--font-mono); display:inline-flex; align-items:center; gap:8px;">
                        Buka Matriks Telemetri <i class="fa-solid fa-arrow-down"></i>
                    </a>
                </div>
            </div>
        </section>

        <!-- Stage 3: Modern Telemetry Grid Section -->
        <section id="stage-3" class="orbital-stage-section" style="min-height:120vh;">
            <div style="max-width:960px; pointer-events:auto;">
                <span class="orbital-stage-badge"><i class="fa-solid fa-microchip" style="margin-right:6px;"></i>Tahap 03 // Matriks Telemetri Subsistem</span>
                <h2 class="orbital-stage-title">Instrumen Telemetri & Analisis Sinyal</h2>
                <p class="orbital-stage-desc">
                    Matriks instrumen penerbangan antariksa: radar pemantau cone stasiun bumi, spektrum gelombang RF, status subsistem panel surya, dan log transmisi paket live.
                </p>

                <!-- Telemetry Matrix Grid Layout -->
                <div class="orbital-bento-grid">
                    <!-- Card 1: 2D Radar Ground Station Scope -->
                    <div class="bento-card">
                        <div class="bento-card__header">
                            <span><i class="fa-solid fa-satellite-dish" style="margin-right:6px; color:#06b6d4;"></i>Radar Scope Stasiun Bumi</span>
                            <span style="color:#06b6d4;"><i class="fa-solid fa-arrows-spin" style="margin-right:4px;"></i>SWEEP 360°</span>
                        </div>
                        <div class="radar-scope-container">
                            <canvas id="radar-canvas"></canvas>
                        </div>
                        <div style="font-family:var(--font-mono); font-size:0.75rem; color:#94a3b8; text-align:center;">
                            <i class="fa-solid fa-location-dot" style="margin-right:4px; color:#06b6d4;"></i>Radius Jangkauan: <strong>1,200 km</strong> <span style="margin:0 8px; opacity:0.35;">|</span> <i class="fa-solid fa-tower-broadcast" style="margin-right:4px; color:#06b6d4;"></i>Station: <strong>SYN-JAKARTA-01</strong>
                        </div>
                    </div>

                    <!-- Card 2: RF Waveform Spectrum & Signal -->
                    <div class="bento-card">
                        <div class="bento-card__header">
                            <span><i class="fa-solid fa-wave-square" style="margin-right:6px; color:#10b981;"></i>RF Downlink Signal Oscilloscope</span>
                            <span id="hud-active-snr" style="color:#10b981; font-weight:700;">19.4 dB</span>
                        </div>
                        <canvas id="waveform-canvas"></canvas>
                        <div style="display:flex; justify-content:space-between; font-family:var(--font-mono); font-size:0.75rem; color:#94a3b8; margin-top:8px;">
                            <span>Carrier: <strong>14.250 GHz</strong></span>
                            <span>Noise Floor: <strong>-118 dBm</strong></span>
                            <span>Modulation: <strong>QPSK</strong></span>
                        </div>
                    </div>

                    <!-- Card 3: Active Satellite Subsystems & Power -->
                    <div class="bento-card">
                        <div class="bento-card__header">
                            <span><i class="fa-solid fa-satellite" style="margin-right:6px; color:#06b6d4;"></i><span id="hud-active-name">SYNTAX-OBSERVER-01</span></span>
                            <span id="hud-active-norad" style="color:#f59e0b;">SYN-48201</span>
                        </div>
                        
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:12px; font-family:var(--font-mono); font-size:0.8rem;">
                            <div>
                                <span style="color:#94a3b8;"><i class="fa-solid fa-arrows-up-down" style="margin-right:4px; font-size:0.75rem;"></i>Ketinggian:</span> <strong id="hud-active-alt" style="color:#06b6d4;">540.5 km</strong>
                            </div>
                            <div>
                                <span style="color:#94a3b8;"><i class="fa-solid fa-gauge-high" style="margin-right:4px; font-size:0.75rem;"></i>Kecepatan:</span> <strong id="hud-active-vel">7.62 km/s</strong>
                            </div>
                            <div>
                                <span style="color:#94a3b8;"><i class="fa-solid fa-location-crosshairs" style="margin-right:4px; font-size:0.75rem;"></i>Sub-Lat:</span> <strong id="hud-active-lat">-12.4182°</strong>
                            </div>
                            <div>
                                <span style="color:#94a3b8;"><i class="fa-solid fa-location-dot" style="margin-right:4px; font-size:0.75rem;"></i>Sub-Lon:</span> <strong id="hud-active-lon">107.8291°</strong>
                            </div>
                        </div>

                        <!-- Progress Bars -->
                        <div style="margin-bottom:8px;">
                            <div style="display:flex; justify-content:space-between; font-family:var(--font-mono); font-size:0.75rem; margin-bottom:4px;">
                                <span style="color:#94a3b8;"><i class="fa-solid fa-battery-three-quarters" style="margin-right:4px; color:#10b981;"></i>Kapasitas Baterai:</span>
                                <span id="hud-active-battery" style="color:#10b981; font-weight:700;">98%</span>
                            </div>
                            <div style="background:rgba(255,255,255,0.08); height:6px; border-radius:3px; overflow:hidden;">
                                <div id="hud-battery-bar" style="background:#10b981; height:100%; width:98%; transition:width 0.3s;"></div>
                            </div>
                        </div>

                        <div>
                            <div style="display:flex; justify-content:space-between; font-family:var(--font-mono); font-size:0.75rem; margin-bottom:4px;">
                                <span style="color:#94a3b8;"><i class="fa-solid fa-solar-panel" style="margin-right:4px; color:#f59e0b;"></i>Daya Panel Surya:</span>
                                <span id="hud-active-solar" style="color:#f59e0b; font-weight:700;">1480 W</span>
                            </div>
                            <div style="background:rgba(255,255,255,0.08); height:6px; border-radius:3px; overflow:hidden;">
                                <div id="hud-solar-bar" style="background:#f59e0b; height:100%; width:40%; transition:width 0.3s;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 4: Live Mission Packet Log -->
                    <div class="bento-card">
                        <div class="bento-card__header">
                            <span><i class="fa-solid fa-terminal" style="margin-right:6px; color:#06b6d4;"></i>Live Mission Packet Log</span>
                            <span style="color:#06b6d4;"><i class="fa-solid fa-bolt" style="margin-right:4px;"></i>STREAMING</span>
                        </div>
                        <div id="terminal-log-box" class="terminal-log-box">
                            <div class="terminal-line"><span class="terminal-line--dim">[SYSTEM_BOOT]</span> Ground Station telemetry bridge initialized.</div>
                            <div class="terminal-line"><span class="terminal-line--dim">[NETWORK_SYNC]</span> 4 orbital flight nodes synchronized.</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stage 4: Ground Station Uplink Deck -->
        <section id="stage-4" class="orbital-stage-section">
            <div class="orbital-stage-content">
                <span class="orbital-stage-badge"><i class="fa-solid fa-tower-broadcast" style="margin-right:6px;"></i>Tahap 04 // Ground Station Uplink</span>
                <h2 class="orbital-stage-title">Transmisi Stasiun Bumi Ku-Band</h2>
                <p class="orbital-stage-desc">
                    Perspektif antena stasiun bumi menghadap langsung ke arah satelit pengorbit. Sistem siap memancarkan perintah sinkronisasi orbit, kalibrasi sensor, dan reorientasi panel surya.
                </p>
                <button type="button" class="c-btn c-btn--primary" data-toggle="modal" data-target="#commandUplinkModal" style="font-family:var(--font-mono); font-weight:700; display:inline-flex; align-items:center; gap:8px;">
                    Buka Terminal Command Uplink <i class="fa-solid fa-paper-plane"></i>
                </button>
            </div>
        </section>
    </div>

    <!-- Floating Bottom Command Dock (Mission Deck) -->
    <div class="orbital-command-dock">
        <a href="#stage-1" class="dock-btn is-active"><i class="fa-solid fa-compass"></i> Overview</a>
        <a href="#stage-2" class="dock-btn"><i class="fa-solid fa-satellite"></i> Armada LEO</a>
        <a href="#stage-3" class="dock-btn"><i class="fa-solid fa-chart-simple"></i> Matriks Telemetri</a>
        <a href="#stage-4" class="dock-btn"><i class="fa-solid fa-tower-broadcast"></i> Ground Uplink</a>
        <div class="dock-divider"></div>
        <button id="dock-toggle-orbit" class="dock-btn"><i class="fa-solid fa-arrows-spin"></i> 360° Free Look</button>
        <button id="dock-reset-cam" class="dock-btn"><i class="fa-solid fa-crosshairs"></i> Reset Kamera</button>
        <div class="dock-divider"></div>
        <button type="button" class="dock-btn dock-btn--primary" data-toggle="modal" data-target="#commandUplinkModal">
            <i class="fa-solid fa-paper-plane"></i> Transmisi Uplink
        </button>
    </div>

    <!-- Command Uplink Modal Dialog (BEM .c-modal) -->
    {$modal}

    <!-- 1. Central UI CDN JavaScript Engine -->
    <script src="{$cdnUrl}/js/core.min.js"></script>
    <!-- 2. HUD Controller & 3D WebGL Engine (ES Module) -->
    <script type="module" src="/js/orbital-hud.js"></script>
</body>
</html>
HTML;

    return Response::html($html);
});

/**
 * Syntax Orbital Ground Station | Modern HUD & Telemetry Controller
 * Manages live telemetry streams, 2D Radar Scope, RF Waveform, Terminal Logs, and Command Dock.
 */

import { OrbitalEngine } from '/js/orbital-engine.js';

async function initHud() {
  const EngineClass = typeof OrbitalEngine !== 'undefined' ? OrbitalEngine : window.OrbitalEngine;
  if (!EngineClass) {
    console.error('OrbitalEngine class could not be resolved');
    return;
  }
  const engine = new EngineClass('orbital-canvas-container');

  let activeSatellites = [];
  let currentSatelliteId = 1;
  let telemetryInterval = null;

  // DOM Elements
  const satPillsContainer = document.getElementById('orbital-satellite-pills');
  const satNameEl = document.getElementById('hud-active-name');
  const satNoradEl = document.getElementById('hud-active-norad');
  const satAltEl = document.getElementById('hud-active-alt');
  const satVelEl = document.getElementById('hud-active-vel');
  const satLatEl = document.getElementById('hud-active-lat');
  const satLonEl = document.getElementById('hud-active-lon');
  const satSnrEl = document.getElementById('hud-active-snr');
  const satTempEl = document.getElementById('hud-active-temp');
  const satSolarEl = document.getElementById('hud-active-solar');
  const satBatteryEl = document.getElementById('hud-active-battery');
  const satBatteryBar = document.getElementById('hud-battery-bar');
  const satSolarBar = document.getElementById('hud-solar-bar');
  const modalSatName = document.getElementById('modal-sat-name');
  const modalSatInput = document.getElementById('command-sat-id');
  const commandFeedback = document.getElementById('command-feedback');
  const terminalLogBox = document.getElementById('terminal-log-box');
  const utcClockEl = document.getElementById('utc-live-clock');

  // 1. Live UTC Clock
  function updateUtcClock() {
    if (!utcClockEl) return;
    const now = new Date();
    const h = String(now.getUTCHours()).padStart(2, '0');
    const m = String(now.getUTCMinutes()).padStart(2, '0');
    const s = String(now.getUTCSeconds()).padStart(2, '0');
    utcClockEl.textContent = `UTC ${h}:${m}:${s}`;
  }
  setInterval(updateUtcClock, 1000);
  updateUtcClock();

  // 2. Fetch Satellites Catalog
  try {
    const res = await fetch('/api/satellites');
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const json = await res.json();
    if (json.success && json.data) {
      activeSatellites = json.data;
      engine.loadSatellitesData(activeSatellites);
      renderSatellitePills(activeSatellites);
      if (activeSatellites.length > 0) {
        selectSatellite(activeSatellites[0].id);
      }
    } else {
      throw new Error(json.message || 'No satellite data returned');
    }
  } catch (err) {
    console.error('Failed to load satellites:', err);
    if (satPillsContainer) {
      satPillsContainer.innerHTML = `
        <div class="c-alert c-alert--danger" style="display:inline-flex; align-items:center; gap:10px; margin:0;">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <span>Gagal memuat katalog armada satelit (${err.message}).</span>
          <button type="button" class="c-btn c-btn--secondary" style="padding:4px 10px; font-size:0.75rem;" onclick="location.reload()">
            <i class="fa-solid fa-rotate-right"></i> Hubungkan Ulang
          </button>
        </div>
      `;
    }
    addTerminalLog(`[NETWORK_ERROR] Telemetry downlink failed: ${err.message}`, 'warn');
  }

  // 3. Render Satellite Selector Pills
  function renderSatellitePills(satellites) {
    if (!satPillsContainer) return;
    satPillsContainer.innerHTML = '';

    satellites.forEach(sat => {
      const pill = document.createElement('button');
      pill.type = 'button';
      pill.className = `orbital-sat-pill ${sat.id === currentSatelliteId ? 'is-active' : ''}`;
      pill.id = `sat-pill-${sat.id}`;
      pill.innerHTML = `
        <span><i class="fa-solid fa-satellite" style="margin-right:6px; color:var(--color-primary);"></i>${sat.name}</span>
        <span class="orbital-sat-pill__badge">${sat.orbit_type}</span>
      `;

      pill.addEventListener('click', () => {
        selectSatellite(sat.id);
        engine.focusSatellite(sat.id);
      });

      satPillsContainer.appendChild(pill);
    });
  }

  // 4. Select Satellite & Fetch Telemetry
  function selectSatellite(id) {
    currentSatelliteId = id;

    // Update active class on pills
    document.querySelectorAll('.orbital-sat-pill').forEach(el => el.classList.remove('is-active'));
    const targetPill = document.getElementById(`sat-pill-${id}`);
    if (targetPill) targetPill.classList.add('is-active');

    // Update modal target
    const currentSat = activeSatellites.find(s => s.id === id);
    if (currentSat && modalSatName && modalSatInput) {
      modalSatName.textContent = `${currentSat.name} (${currentSat.norad_id})`;
      modalSatInput.value = currentSat.id;
    }

    addTerminalLog(`[SAT_TARGET_LOCK] Focus switched to ID #${id} (${currentSat ? currentSat.name : ''})`);

    // Refresh telemetry immediately and poll
    fetchTelemetry(id);
    if (telemetryInterval) clearInterval(telemetryInterval);
    telemetryInterval = setInterval(() => fetchTelemetry(currentSatelliteId), 3000);
  }

  // 5. Fetch Telemetry Data from API
  async function fetchTelemetry(id) {
    try {
      const res = await fetch(`/api/telemetry/${id}`);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const json = await res.json();
      if (json.success && json.data) {
        const d = json.data;
        if (satNameEl) satNameEl.textContent = d.name;
        if (satNoradEl) satNoradEl.textContent = d.norad_id;
        if (satAltEl) satAltEl.textContent = `${d.altitude_km.toFixed(1)} km`;
        if (satVelEl) satVelEl.textContent = `${d.velocity_kms.toFixed(2)} km/s`;
        if (satLatEl) satLatEl.textContent = `${d.sub_lat.toFixed(4)}°`;
        if (satLonEl) satLonEl.textContent = `${d.sub_lon.toFixed(4)}°`;
        if (satSnrEl) satSnrEl.textContent = `${d.downlink_snr_db.toFixed(1)} dB`;
        if (satTempEl) satTempEl.textContent = `${d.payload_temp_c.toFixed(1)} °C`;
        if (satSolarEl) satSolarEl.textContent = `${d.solar_output_w.toFixed(0)} W`;
        if (satBatteryEl) satBatteryEl.textContent = `${d.battery_pct}%`;

        // Progress bar updates
        if (satBatteryBar) satBatteryBar.style.width = `${d.battery_pct}%`;
        if (satSolarBar) {
          const maxW = 5000;
          const pct = Math.min(100, (d.solar_output_w / maxW) * 100);
          satSolarBar.style.width = `${pct}%`;
        }

        // Restore active live status dot
        const topStatusDot = document.querySelector('.orbital-status-dot');
        if (topStatusDot) {
          topStatusDot.style.backgroundColor = 'var(--color-accent-emerald)';
          topStatusDot.style.boxShadow = '0 0 8px var(--color-accent-emerald)';
        }
      }
    } catch (err) {
      console.warn('Telemetry fetch error:', err);
      if (satSnrEl) satSnrEl.textContent = 'SIGNAL LOST';
      const topStatusDot = document.querySelector('.orbital-status-dot');
      if (topStatusDot) {
        topStatusDot.style.backgroundColor = 'var(--color-accent-red)';
        topStatusDot.style.boxShadow = '0 0 8px var(--color-accent-red)';
      }
    }
  }

  // 6. Listen to 3D Viewport Raycast Selection
  window.addEventListener('orbital:satellite:selected', (e) => {
    const sat = e.detail.satellite;
    if (sat) {
      selectSatellite(sat.id);
    }
  });

  // 7. Interactive 2D Radar Canvas Scope
  const radarCanvas = document.getElementById('radar-canvas');
  if (radarCanvas) {
    const rCtx = radarCanvas.getContext('2d');
    let sweepAngle = 0;

    function drawRadar() {
      const w = radarCanvas.width = 200;
      const h = radarCanvas.height = 200;
      const cx = w / 2;
      const cy = h / 2;
      const maxR = 90;

      rCtx.clearRect(0, 0, w, h);

      // Radar dark background
      rCtx.fillStyle = '#061021';
      rCtx.beginPath();
      rCtx.arc(cx, cy, maxR, 0, Math.PI * 2);
      rCtx.fill();

      // Range rings
      rCtx.strokeStyle = 'rgba(6, 182, 212, 0.28)';
      rCtx.lineWidth = 1;
      [0.33, 0.66, 1].forEach(frac => {
        rCtx.beginPath();
        rCtx.arc(cx, cy, maxR * frac, 0, Math.PI * 2);
        rCtx.stroke();
      });

      // Crosshairs
      rCtx.beginPath();
      rCtx.moveTo(cx - maxR, cy);
      rCtx.lineTo(cx + maxR, cy);
      rCtx.moveTo(cx, cy - maxR);
      rCtx.lineTo(cx, cy + maxR);
      rCtx.stroke();

      // Sweeping Beam
      sweepAngle += 0.035;
      const sweepX = cx + maxR * Math.cos(sweepAngle);
      const sweepY = cy + maxR * Math.sin(sweepAngle);

      const grad = rCtx.createRadialGradient(cx, cy, 0, cx, cy, maxR);
      grad.addColorStop(0, 'rgba(6, 182, 212, 0.25)');
      grad.addColorStop(1, 'rgba(6, 182, 212, 0)');

      rCtx.fillStyle = grad;
      rCtx.beginPath();
      rCtx.moveTo(cx, cy);
      rCtx.arc(cx, cy, maxR, sweepAngle - 0.4, sweepAngle);
      rCtx.closePath();
      rCtx.fill();

      // Sweeping Line
      rCtx.strokeStyle = '#06b6d4';
      rCtx.lineWidth = 1.5;
      rCtx.beginPath();
      rCtx.moveTo(cx, cy);
      rCtx.lineTo(sweepX, sweepY);
      rCtx.stroke();

      // Draw Satellite Blips
      activeSatellites.forEach((sat, i) => {
        const blipAngle = (i * Math.PI) / 2 + (Date.now() / 10000) * (sat.velocity_kms / 5);
        const dist = 35 + (i * 15);
        const bx = cx + dist * Math.cos(blipAngle);
        const by = cy + dist * Math.sin(blipAngle);

        rCtx.fillStyle = sat.id === currentSatelliteId ? '#f59e0b' : '#06b6d4';
        rCtx.beginPath();
        rCtx.arc(bx, by, 3.5, 0, Math.PI * 2);
        rCtx.fill();

        if (sat.id === currentSatelliteId) {
          rCtx.strokeStyle = 'rgba(245, 158, 11, 0.5)';
          rCtx.beginPath();
          rCtx.arc(bx, by, 7, 0, Math.PI * 2);
          rCtx.stroke();
        }
      });

      requestAnimationFrame(drawRadar);
    }
    drawRadar();
  }

  // 8. Live RF Waveform Oscilloscope Canvas
  const waveCanvas = document.getElementById('waveform-canvas');
  if (waveCanvas) {
    const wCtx = waveCanvas.getContext('2d');
    let waveStep = 0;

    function drawWaveform() {
      const w = waveCanvas.width = waveCanvas.clientWidth || 360;
      const h = waveCanvas.height = 100;
      const cy = h / 2;

      wCtx.clearRect(0, 0, w, h);

      // Grid Lines
      wCtx.strokeStyle = 'rgba(255, 255, 255, 0.05)';
      wCtx.lineWidth = 1;
      for (let y = 10; y < h; y += 20) {
        wCtx.beginPath();
        wCtx.moveTo(0, y);
        wCtx.lineTo(w, y);
        wCtx.stroke();
      }

      // Sine RF Carrier with noise
      waveStep += 0.08;
      wCtx.strokeStyle = '#06b6d4';
      wCtx.lineWidth = 2;
      wCtx.beginPath();

      for (let x = 0; x < w; x++) {
        const noise = (Math.sin(x * 0.15 + waveStep * 2) * 4) + (Math.random() * 2);
        const y = cy + Math.sin(x * 0.04 + waveStep) * 22 + noise;
        if (x === 0) wCtx.moveTo(x, y);
        else wCtx.lineTo(x, y);
      }
      wCtx.stroke();

      requestAnimationFrame(drawWaveform);
    }
    drawWaveform();
  }

  // 9. Live Mission Terminal Log Feeder
  function addTerminalLog(msg, type = 'info') {
    if (!terminalLogBox) return;
    const now = new Date();
    const timeStr = now.toTimeString().split(' ')[0];
    const line = document.createElement('div');
    line.className = `terminal-line ${type === 'success' ? 'terminal-line--green' : (type === 'warn' ? 'terminal-line--amber' : '')}`;
    line.innerHTML = `<span class="terminal-line--dim">[${timeStr}]</span> ${msg}`;
    terminalLogBox.appendChild(line);
    terminalLogBox.scrollTop = terminalLogBox.scrollHeight;
  }

  const automatedLogMessages = [
    'NORAD_TLE_UPDATE: Ephemeris orbital parameters verified',
    'KU_BAND_TRANSPONDER: Downlink lock acquired at 14.250 GHz',
    'GYRO_STABILIZER: Attitude quaternion within 0.02° tolerance',
    'GROUND_RADAR_BEACON: Slant range 682.4 km | Azimuth 148.2°'
  ];
  let logIdx = 0;
  setInterval(() => {
    if (activeSatellites.length > 0) {
      const cur = activeSatellites.find(s => s.id === currentSatelliteId) || activeSatellites[0];
      const msg = automatedLogMessages[logIdx % automatedLogMessages.length];
      addTerminalLog(`${cur.norad_id}: ${msg}`);
      logIdx++;
    }
  }, 4000);

  // 10. Floating Bottom Command Dock Controls
  const toggleOrbitBtn = document.getElementById('dock-toggle-orbit');
  let isFreeOrbit = false;
  if (toggleOrbitBtn) {
    toggleOrbitBtn.addEventListener('click', () => {
      isFreeOrbit = !isFreeOrbit;
      engine.toggleFreeOrbit(isFreeOrbit);
      toggleOrbitBtn.classList.toggle('is-active', isFreeOrbit);
      toggleOrbitBtn.innerHTML = isFreeOrbit 
        ? `<i class="fa-solid fa-lock"></i> Kunci Parallax` 
        : `<i class="fa-solid fa-arrows-spin"></i> 360° Free Look`;
    });
  }

  const resetCamBtn = document.getElementById('dock-reset-cam');
  if (resetCamBtn) {
    resetCamBtn.addEventListener('click', () => {
      engine.clearFocus();
      engine.updateStorylineCamera();
      addTerminalLog('[CAMERA_COMMAND] Camera lock reset to orbital overview');
    });
  }

  // 11. Active Storyline Stage Spy for Command Dock
  const dockStageLinks = document.querySelectorAll('.orbital-command-dock a[href^="#stage-"]');
  const stageSections = document.querySelectorAll('.orbital-stage-section');

  function updateActiveDockLink() {
    let activeStageId = 'stage-1';
    const scrollTrigger = window.scrollY + window.innerHeight * 0.35;

    stageSections.forEach(section => {
      if (scrollTrigger >= section.offsetTop) {
        activeStageId = section.getAttribute('id');
      }
    });

    dockStageLinks.forEach(link => {
      if (link.getAttribute('href') === `#${activeStageId}`) {
        link.classList.add('is-active');
      } else {
        link.classList.remove('is-active');
      }
    });
  }

  window.addEventListener('scroll', updateActiveDockLink, { passive: true });
  updateActiveDockLink();

  // 12. Command Uplink Form Handler
  const commandForm = document.getElementById('form-uplink-command');
  if (commandForm) {
    commandForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const satId = document.getElementById('command-sat-id').value;
      const cmdType = document.getElementById('command-type-select').value;
      const submitBtn = commandForm.querySelector('button[type="submit"]');

      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Transmitting Uplink...';
      }

      try {
        const res = await fetch('/api/commands', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            satellite_id: parseInt(satId, 10),
            command_type: cmdType
          })
        });

        const json = await res.json();
        if (json.success) {
          if (commandFeedback) {
            commandFeedback.style.display = 'block';
            commandFeedback.className = 'c-alert c-alert--success';
            commandFeedback.innerHTML = `<i class="fa-solid fa-circle-check" style="margin-right:6px;"></i><strong>Uplink Berhasil:</strong> Perintah <code>${cmdType}</code> terkirim (Latency: ${json.data.latency_ms}ms, Transponder: ${json.data.uplink_frequency_ghz} GHz).`;
          }
          addTerminalLog(`[UPLINK_SUCCESS] ${cmdType} -> SAT #${satId} (Latency: ${json.data.latency_ms}ms)`, 'success');
          setTimeout(() => {
            if (window.CoreUI && window.CoreUI.Modal) {
              window.CoreUI.Modal.close('#commandUplinkModal');
            }
          }, 1400);
        }
      } catch (err) {
        if (commandFeedback) {
          commandFeedback.style.display = 'block';
          commandFeedback.className = 'c-alert c-alert--danger';
          commandFeedback.innerHTML = '<i class="fa-solid fa-triangle-exclamation" style="margin-right:6px;"></i> Gagal memancarkan perintah uplink ke satelit.';
        }
        addTerminalLog(`[UPLINK_FAIL] Transponder error communicating with SAT #${satId}`, 'warn');
      } finally {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Kirim Perintah Uplink';
        }
      }
    });
  }
}

// Execute when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initHud);
} else {
  initHud();
}

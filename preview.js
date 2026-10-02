/**
 * SyntaxDocs Local Static Preview Server (Node.js)
 * Run with: node preview.js
 * Serves the Central UI CDN, Component Showcase, and Orbital 3D Ground Station
 */

const http = require('http');
const fs = require('fs');
const path = require('path');

const PORT = parseInt(process.env.PORT, 10) || 8085;
const CDN_DIR = path.join(__dirname, 'packages/ui-cdn/public');
const ORBITAL_DIR = path.join(__dirname, 'apps/orbital/public');

const MIME_TYPES = {
  '.html': 'text/html; charset=UTF-8',
  '.css': 'text/css; charset=UTF-8',
  '.js': 'application/javascript; charset=UTF-8',
  '.json': 'application/json; charset=UTF-8',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.svg': 'image/svg+xml',
  '.ico': 'image/x-icon',
};

// Mock Satellite Database for Preview Server
const SATELLITES = [
  {
    id: 1,
    norad_id: 'SYN-48201',
    name: 'SYNTAX-OBSERVER-01',
    orbit_type: 'LEO',
    altitude_km: 540.5,
    velocity_kms: 7.62,
    inclination_deg: 51.6,
    period_min: 95.4,
    status: 'active',
    battery_pct: 98,
    solar_output_w: 1480.0
  },
  {
    id: 2,
    norad_id: 'SYN-51042',
    name: 'NOCTIS-SAR-04',
    orbit_type: 'SSO',
    altitude_km: 680.0,
    velocity_kms: 7.51,
    inclination_deg: 98.2,
    period_min: 98.2,
    status: 'active',
    battery_pct: 92,
    solar_output_w: 2100.0
  },
  {
    id: 3,
    norad_id: 'SYN-33918',
    name: 'HELIOS-SOLAR-09',
    orbit_type: 'MEO',
    altitude_km: 20200.0,
    velocity_kms: 3.87,
    inclination_deg: 55.0,
    period_min: 718.0,
    status: 'calibrating',
    battery_pct: 88,
    solar_output_w: 3200.0
  },
  {
    id: 4,
    norad_id: 'SYN-29104',
    name: 'AETHER-RELAY-02',
    orbit_type: 'GEO',
    altitude_km: 35786.0,
    velocity_kms: 3.07,
    inclination_deg: 0.05,
    period_min: 1436.1,
    status: 'active',
    battery_pct: 100,
    solar_output_w: 4500.0
  }
];

const server = http.createServer((req, res) => {
  // Enable CORS
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  if (req.method === 'OPTIONS') {
    res.writeHead(204);
    res.end();
    return;
  }

  const urlObj = new URL(req.url, `http://${req.headers.host}`);
  const pathname = urlObj.pathname;

  // 1. API Endpoint: GET /api/satellites
  if (pathname === '/api/satellites' && req.method === 'GET') {
    res.writeHead(200, { 'Content-Type': 'application/json; charset=UTF-8' });
    res.end(JSON.stringify({
      success: true,
      code: 200,
      message: 'Active orbital constellation catalog retrieved successfully',
      data: SATELLITES,
      meta: { timestamp: Math.floor(Date.now() / 1000), total: SATELLITES.length }
    }));
    return;
  }

  // 2. API Endpoint: GET /api/telemetry/:id
  if (pathname.startsWith('/api/telemetry/') && req.method === 'GET') {
    const id = parseInt(pathname.split('/')[3], 10);
    const sat = SATELLITES.find(s => s.id === id) || SATELLITES[0];
    const now = Math.floor(Date.now() / 1000);
    const orbitFraction = (now % (sat.period_min * 60)) / (sat.period_min * 60);

    res.writeHead(200, { 'Content-Type': 'application/json; charset=UTF-8' });
    res.end(JSON.stringify({
      success: true,
      code: 200,
      message: 'Live satellite telemetry retrieved',
      data: {
        satellite_id: sat.id,
        name: sat.name,
        norad_id: sat.norad_id,
        orbit_type: sat.orbit_type,
        altitude_km: sat.altitude_km,
        velocity_kms: sat.velocity_kms,
        inclination_deg: sat.inclination_deg,
        period_min: sat.period_min,
        status: sat.status,
        battery_pct: sat.battery_pct,
        solar_output_w: sat.solar_output_w,
        downlink_snr_db: Number((18.5 + Math.sin(now / 10) * 3.2).toFixed(1)),
        payload_temp_c: Number((21.4 + Math.cos(now / 15) * 4.1).toFixed(1)),
        sub_lat: Number((Math.sin(orbitFraction * 2 * Math.PI) * sat.inclination_deg).toFixed(4)),
        sub_lon: Number((((now * 0.05) % 360) - 180).toFixed(4)),
        timestamp: new Date().toISOString()
      },
      meta: { timestamp: now }
    }));
    return;
  }

  // 3. API Endpoint: POST /api/commands
  if (pathname === '/api/commands' && req.method === 'POST') {
    let body = '';
    req.on('data', chunk => { body += chunk; });
    req.on('end', () => {
      let parsed = {};
      try { parsed = JSON.parse(body); } catch (e) {}
      const satId = parsed.satellite_id || 1;
      const cmdType = parsed.command_type || 'PING_TRANSPONDER';

      res.writeHead(201, { 'Content-Type': 'application/json; charset=UTF-8' });
      res.end(JSON.stringify({
        success: true,
        code: 201,
        message: `Command ${cmdType} transmitted successfully to satellite #${satId}`,
        data: {
          command_id: Math.floor(1000 + Math.random() * 9000),
          satellite_id: satId,
          command_type: cmdType,
          status: 'transmitted',
          uplink_frequency_ghz: 14.25,
          latency_ms: Math.floor(18 + Math.random() * 25),
          transmitted_at: new Date().toISOString()
        },
        meta: { timestamp: Math.floor(Date.now() / 1000) }
      }));
    });
    return;
  }

  // 4. File Routing
  let targetFile = null;

  if (pathname === '/orbital' || pathname === '/orbital/') {
    targetFile = path.join(ORBITAL_DIR, 'index.html');
  } else if (pathname === '/theme.css') {
    targetFile = path.join(ORBITAL_DIR, 'theme.css');
  } else if (pathname.startsWith('/js/orbital-')) {
    targetFile = path.join(ORBITAL_DIR, pathname);
  } else if (pathname.startsWith('/orbital/')) {
    const rel = pathname.replace('/orbital/', '');
    targetFile = path.join(ORBITAL_DIR, rel || 'index.html');
  } else if (pathname === '/') {
    targetFile = path.join(CDN_DIR, 'index.html');
  } else {
    // Check in CDN directory first, then orbital directory
    const cdnCandidate = path.join(CDN_DIR, pathname);
    if (fs.existsSync(cdnCandidate) && fs.statSync(cdnCandidate).isFile()) {
      targetFile = cdnCandidate;
    } else {
      const orbCandidate = path.join(ORBITAL_DIR, pathname);
      if (fs.existsSync(orbCandidate) && fs.statSync(orbCandidate).isFile()) {
        targetFile = orbCandidate;
      }
    }
  }

  if (!targetFile || !fs.existsSync(targetFile) || !fs.statSync(targetFile).isFile()) {
    res.writeHead(404, { 'Content-Type': 'text/plain; charset=UTF-8' });
    res.end('404 Not Found');
    return;
  }

  const ext = path.extname(targetFile).toLowerCase();
  const contentType = MIME_TYPES[ext] || 'application/octet-stream';

  res.writeHead(200, { 'Content-Type': contentType });
  fs.createReadStream(targetFile).pipe(res);
});

server.listen(PORT, () => {
  console.log('\n======================================================');
  console.log(`  SYNTAX ECOSYSTEM & ORBITAL 3D RUNNING`);
  console.log(`  - Showcase & Central UI CDN: http://localhost:${PORT}`);
  console.log(`  - Orbital 3D Parallax App:   http://localhost:${PORT}/orbital/`);
  console.log('  Press Ctrl+C to stop the server');
  console.log('======================================================\n');
});

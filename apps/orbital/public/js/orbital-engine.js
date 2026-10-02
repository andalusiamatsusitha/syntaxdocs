/**
 * Syntax Orbital Ground Station | 3D WebGL Parallax Engine
 * Powered by Vanilla Three.js (ESM). Zero-bloat, 60fps native performance.
 */

import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

class OrbitalEngine {
  constructor(canvasContainerId) {
    this.container = document.getElementById(canvasContainerId);
    if (!this.container) return;

    this.scene = null;
    this.camera = null;
    this.renderer = null;
    this.controls = null;
    this.raycaster = new THREE.Raycaster();
    this.mouse = new THREE.Vector2(-999, -999);
    this.normalizedMouse = { x: 0, y: 0 };

    // Scene Objects
    this.earthGroup = null;
    this.earthMesh = null;
    this.atmosphereMesh = null;
    this.starfield = null;
    this.satellites = [];
    this.orbitLines = [];
    this.uplinkBeam = null;

    // Camera & Parallax State
    this.isFreeOrbit = false;
    this.selectedSatellite = null;
    this.targetCameraPos = new THREE.Vector3(0, 80, 480);
    this.targetLookAt = new THREE.Vector3(0, 0, 0);
    this.currentLookAt = new THREE.Vector3(0, 0, 0);
    this.scrollProgress = 0;

    // Clock
    this.clock = new THREE.Clock();

    this.init();
  }

  init() {
    this.setupRenderer();
    this.setupScene();
    this.setupLighting();
    this.createStarfield();
    this.createEarth();
    this.setupEventListeners();
    this.animate();
  }

  setupRenderer() {
    this.renderer = new THREE.WebGLRenderer({
      antialias: true,
      powerPreference: 'high-performance',
      alpha: true
    });
    this.renderer.setSize(window.innerWidth, window.innerHeight);
    this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
    this.renderer.toneMappingExposure = 1.1;

    this.canvas = this.renderer.domElement;
    this.canvas.id = 'orbital-webgl-canvas';
    this.container.appendChild(this.canvas);

    this.camera = new THREE.PerspectiveCamera(45, window.innerWidth / window.innerHeight, 0.1, 4000);
    this.camera.position.copy(this.targetCameraPos);

    this.controls = new OrbitControls(this.camera, this.canvas);
    this.controls.enableDamping = true;
    this.controls.dampingFactor = 0.05;
    this.controls.minDistance = 140;
    this.controls.maxDistance = 1200;
    this.controls.enabled = false; // Disabled by default in favor of parallax storyline
  }

  setupScene() {
    this.scene = new THREE.Scene();
    this.scene.fog = new THREE.FogExp2(0x060913, 0.00035);
  }

  setupLighting() {
    // Subtle ambient space light
    const ambientLight = new THREE.AmbientLight(0x1e293b, 1.2);
    this.scene.add(ambientLight);

    // Sun Directional Light
    const sunLight = new THREE.DirectionalLight(0xffffff, 2.8);
    sunLight.position.set(450, 180, 300);
    this.scene.add(sunLight);

    // Subtle blue rim back-light
    const rimLight = new THREE.DirectionalLight(0x06b6d4, 0.9);
    rimLight.position.set(-400, -100, -300);
    this.scene.add(rimLight);
  }

  createStarfield() {
    const starCount = 2800;
    const geometry = new THREE.BufferGeometry();
    const positions = new Float32Array(starCount * 3);
    const colors = new Float32Array(starCount * 3);

    const palette = [
      new THREE.Color('#ffffff'),
      new THREE.Color('#93c5fd'),
      new THREE.Color('#fde68a'),
      new THREE.Color('#67e8f9')
    ];

    for (let i = 0; i < starCount; i++) {
      const radius = 1200 + Math.random() * 1500;
      const theta = Math.random() * Math.PI * 2;
      const phi = Math.acos(Math.random() * 2 - 1);

      positions[i * 3] = radius * Math.sin(phi) * Math.cos(theta);
      positions[i * 3 + 1] = radius * Math.sin(phi) * Math.sin(theta);
      positions[i * 3 + 2] = radius * Math.cos(phi);

      const color = palette[Math.floor(Math.random() * palette.length)];
      colors[i * 3] = color.r;
      colors[i * 3 + 1] = color.g;
      colors[i * 3 + 2] = color.b;
    }

    geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));

    const material = new THREE.PointsMaterial({
      size: 2.2,
      vertexColors: true,
      transparent: true,
      opacity: 0.85
    });

    this.starfield = new THREE.Points(geometry, material);
    this.scene.add(this.starfield);
  }

  createEarth() {
    this.earthGroup = new THREE.Group();
    this.earthGroup.rotation.z = (23.5 * Math.PI) / 180; // Earth axial tilt

    // 1. Procedural Earth Texture Canvas
    const canvas = document.createElement('canvas');
    canvas.width = 2048;
    canvas.height = 1024;
    const ctx = canvas.getContext('2d');

    // Deep ocean base
    ctx.fillStyle = '#08142b';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Procedural continent shapes & latitude lines
    ctx.fillStyle = '#1e3a5f';
    for (let i = 0; i < 40; i++) {
      const cx = Math.random() * canvas.width;
      const cy = Math.random() * canvas.height;
      const rw = 120 + Math.random() * 260;
      const rh = 80 + Math.random() * 180;
      ctx.beginPath();
      ctx.ellipse(cx, cy, rw, rh, Math.random() * Math.PI, 0, Math.PI * 2);
      ctx.fill();
    }

    // Grid lines (lat / lon coordinate lines)
    ctx.strokeStyle = 'rgba(6, 182, 212, 0.16)';
    ctx.lineWidth = 1.5;
    for (let x = 0; x <= canvas.width; x += canvas.width / 12) {
      ctx.beginPath();
      ctx.moveTo(x, 0);
      ctx.lineTo(x, canvas.height);
      ctx.stroke();
    }
    for (let y = 0; y <= canvas.height; y += canvas.height / 8) {
      ctx.beginPath();
      ctx.moveTo(0, y);
      ctx.lineTo(canvas.width, y);
      ctx.stroke();
    }

    const earthTexture = new THREE.CanvasTexture(canvas);

    // Earth Sphere Mesh
    const earthGeo = new THREE.SphereGeometry(100, 64, 64);
    const earthMat = new THREE.MeshStandardMaterial({
      map: earthTexture,
      roughness: 0.7,
      metalness: 0.15
    });

    this.earthMesh = new THREE.Mesh(earthGeo, earthMat);
    this.earthGroup.add(this.earthMesh);

    // 2. Atmospheric Fresnel Glow Shell
    const atmoGeo = new THREE.SphereGeometry(103.5, 48, 48);
    const atmoMat = new THREE.ShaderMaterial({
      vertexShader: `
        varying vec3 vNormal;
        void main() {
          vNormal = normalize(normalMatrix * normal);
          gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
        }
      `,
      fragmentShader: `
        varying vec3 vNormal;
        void main() {
          float intensity = pow(0.68 - dot(vNormal, vec3(0, 0, 1.0)), 2.8);
          gl_FragColor = vec4(0.02, 0.71, 0.83, 1.0) * intensity * 1.6;
        }
      `,
      blending: THREE.AdditiveBlending,
      side: THREE.BackSide,
      transparent: true
    });

    this.atmosphereMesh = new THREE.Mesh(atmoGeo, atmoMat);
    this.earthGroup.add(this.atmosphereMesh);

    this.scene.add(this.earthGroup);
  }

  loadSatellitesData(satellitesData) {
    // Clear existing
    this.satellites.forEach(s => this.scene.remove(s.group));
    this.orbitLines.forEach(l => this.scene.remove(l));
    this.satellites = [];
    this.orbitLines = [];

    satellitesData.forEach((sat, index) => {
      // Scale altitude for visual aesthetic balance (Earth radius is 100)
      let visualRadius = 100 + (sat.altitude_km / 180);
      if (sat.orbit_type === 'GEO') visualRadius = 240;
      else if (sat.orbit_type === 'MEO') visualRadius = 180;
      else visualRadius = 125 + (index * 8);

      const inclination = (sat.inclination_deg * Math.PI) / 180;

      // 1. Create Orbit Line
      const orbitCurve = new THREE.EllipseCurve(
        0, 0,
        visualRadius, visualRadius,
        0, 2 * Math.PI,
        false, 0
      );
      const points = orbitCurve.getPoints(90);
      const orbitGeo = new THREE.BufferGeometry().setFromPoints(
        points.map(p => new THREE.Vector3(p.x, 0, p.y))
      );
      const orbitMat = new THREE.LineBasicMaterial({
        color: index === 0 ? 0xf59e0b : 0x06b6d4,
        transparent: true,
        opacity: index === 0 ? 0.75 : 0.4
      });
      const orbitLine = new THREE.Line(orbitGeo, orbitMat);
      orbitLine.rotation.x = Math.PI / 2 - inclination;
      orbitLine.rotation.z = (index * 45 * Math.PI) / 180;
      this.scene.add(orbitLine);
      this.orbitLines.push(orbitLine);

      // 2. Build 3D Satellite Model
      const satGroup = new THREE.Group();

      // Bus Body
      const bodyGeo = new THREE.BoxGeometry(4.5, 4.5, 6);
      const bodyMat = new THREE.MeshStandardMaterial({
        color: 0xe2e8f0,
        metalness: 0.85,
        roughness: 0.25
      });
      const bodyMesh = new THREE.Mesh(bodyGeo, bodyMat);
      satGroup.add(bodyMesh);

      // Solar Panels Wings
      const panelGeo = new THREE.BoxGeometry(16, 0.4, 4);
      const panelMat = new THREE.MeshStandardMaterial({
        color: 0x1e3a8a,
        metalness: 0.6,
        roughness: 0.3
      });
      const panelMesh = new THREE.Mesh(panelGeo, panelMat);
      satGroup.add(panelMesh);

      // Beacon Light
      const beaconLight = new THREE.PointLight(
        sat.status === 'active' ? 0x06b6d4 : 0xf59e0b,
        2.5,
        25
      );
      satGroup.add(beaconLight);

      // Raycast Target Bounding Mesh (invisible hit box)
      const hitGeo = new THREE.SphereGeometry(9, 8, 8);
      const hitMat = new THREE.MeshBasicMaterial({ visible: false });
      const hitMesh = new THREE.Mesh(hitGeo, hitMat);
      hitMesh.userData = { satellite: sat, index };
      satGroup.add(hitMesh);

      this.scene.add(satGroup);

      this.satellites.push({
        data: sat,
        group: satGroup,
        hitMesh,
        visualRadius,
        inclination,
        rotationZ: orbitLine.rotation.z,
        speed: (2 * Math.PI) / (sat.period_min * 1.5),
        currentAngle: (index * Math.PI) / 2
      });
    });
  }

  setupEventListeners() {
    window.addEventListener('resize', () => this.onResize());

    // Mouse movement for parallax & raycasting
    window.addEventListener('mousemove', (e) => {
      this.mouse.x = (e.clientX / window.innerWidth) * 2 - 1;
      this.mouse.y = -(e.clientY / window.innerHeight) * 2 + 1;

      // Damped normalized mouse for camera parallax
      this.normalizedMouse.x = (e.clientX - window.innerWidth / 2) / (window.innerWidth / 2);
      this.normalizedMouse.y = (e.clientY - window.innerHeight / 2) / (window.innerHeight / 2);

      this.handleRaycastHover(e);
    });

    // Click for target lock
    window.addEventListener('click', (e) => {
      this.handleRaycastClick(e);
    });

    // Scroll listener for storyline parallax stages
    window.addEventListener('scroll', () => {
      const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
      this.scrollProgress = maxScroll > 0 ? window.scrollY / maxScroll : 0;
      this.updateStorylineCamera();
    });
  }

  handleRaycastHover(e) {
    if (this.isFreeOrbit) return;

    this.raycaster.setFromCamera(this.mouse, this.camera);
    const hitMeshes = this.satellites.map(s => s.hitMesh);
    const intersects = this.raycaster.intersectObjects(hitMeshes);

    const tooltip = document.getElementById('orbital-3d-tooltip');
    if (intersects.length > 0) {
      document.body.style.cursor = 'pointer';
      const sat = intersects[0].object.userData.satellite;
      if (tooltip) {
        tooltip.style.display = 'block';
        tooltip.style.left = `${e.clientX}px`;
        tooltip.style.top = `${e.clientY - 45}px`;
        tooltip.innerHTML = `<strong>${sat.name}</strong> [${sat.orbit_type}]<br><span style="color:#06b6d4;">Alt: ${sat.altitude_km} km | Vel: ${sat.velocity_kms} km/s</span>`;
      }
    } else {
      document.body.style.cursor = 'default';
      if (tooltip) tooltip.style.display = 'none';
    }
  }

  handleRaycastClick(e) {
    if (this.isFreeOrbit) return;

    this.raycaster.setFromCamera(this.mouse, this.camera);
    const hitMeshes = this.satellites.map(s => s.hitMesh);
    const intersects = this.raycaster.intersectObjects(hitMeshes);

    if (intersects.length > 0) {
      const satObj = intersects[0].object.userData.satellite;
      this.focusSatellite(satObj.id);
    }
  }

  focusSatellite(satelliteId) {
    const target = this.satellites.find(s => s.data.id === satelliteId);
    if (!target) return;

    this.selectedSatellite = target;

    // Dispatch event to HUD
    window.dispatchEvent(new CustomEvent('orbital:satellite:selected', {
      detail: { satellite: target.data }
    }));
  }

  clearFocus() {
    this.selectedSatellite = null;
  }

  toggleFreeOrbit(enabled) {
    this.isFreeOrbit = enabled;
    this.controls.enabled = enabled;
    if (!enabled) {
      this.controls.reset();
      this.updateStorylineCamera();
    }
  }

  updateStorylineCamera() {
    if (this.isFreeOrbit) return;

    const p = this.scrollProgress;

    if (this.selectedSatellite) {
      // Target Lock Override
      const satPos = this.selectedSatellite.group.position;
      this.targetCameraPos.set(satPos.x + 35, satPos.y + 15, satPos.z + 40);
      this.targetLookAt.copy(satPos);
      return;
    }

    // 4 Storyline Stages Interpolation
    if (p <= 0.25) {
      // Stage 1: Overview
      const t = p / 0.25;
      this.targetCameraPos.set(0, 80 + t * 20, 480 - t * 80);
      this.targetLookAt.set(0, 0, 0);
    } else if (p <= 0.55) {
      // Stage 2: LEO Fleet Surveillance
      const t = (p - 0.25) / 0.3;
      this.targetCameraPos.set(40 * Math.sin(t * Math.PI), 50 - t * 20, 400 - t * 150);
      this.targetLookAt.set(0, 10, 0);
    } else if (p <= 0.85) {
      // Stage 3: Satellite Target Zoom
      const t = (p - 0.55) / 0.3;
      const firstSat = this.satellites[0];
      if (firstSat) {
        const satPos = firstSat.group.position;
        this.targetCameraPos.lerpVectors(
          new THREE.Vector3(0, 30, 250),
          new THREE.Vector3(satPos.x + 30, satPos.y + 12, satPos.z + 35),
          t
        );
        this.targetLookAt.lerpVectors(new THREE.Vector3(0, 0, 0), satPos, t);
      }
    } else {
      // Stage 4: Ground Station Uplink Deck
      const t = (p - 0.85) / 0.15;
      this.targetCameraPos.set(0, 112 + t * 4, 60 - t * 10);
      this.targetLookAt.set(0, 180, 0);
    }
  }

  onResize() {
    this.camera.aspect = window.innerWidth / window.innerHeight;
    this.camera.updateProjectionMatrix();
    this.renderer.setSize(window.innerWidth, window.innerHeight);
  }

  animate() {
    requestAnimationFrame(() => this.animate());

    const delta = this.clock.getDelta();
    const elapsedTime = this.clock.getElapsedTime();

    // 1. Earth Slow Planetary Rotation
    if (this.earthMesh) {
      this.earthMesh.rotation.y += delta * 0.05;
    }

    // 2. Starfield subtle cosmic drift
    if (this.starfield) {
      this.starfield.rotation.y = elapsedTime * 0.005;
    }

    // 3. Update Satellites along orbital planes
    this.satellites.forEach(sat => {
      sat.currentAngle += sat.speed * delta;
      const r = sat.visualRadius;

      // Position in local orbit plane
      const localX = r * Math.cos(sat.currentAngle);
      const localZ = r * Math.sin(sat.currentAngle);

      // Rotate by orbit inclination & longitude
      const orbitMatrix = new THREE.Matrix4();
      orbitMatrix.makeRotationZ(sat.rotationZ);
      const inclinationMatrix = new THREE.Matrix4();
      inclinationMatrix.makeRotationX(Math.PI / 2 - sat.inclination);

      const pos = new THREE.Vector3(localX, 0, localZ);
      pos.applyMatrix4(inclinationMatrix);
      pos.applyMatrix4(orbitMatrix);

      sat.group.position.copy(pos);
      sat.group.lookAt(0, 0, 0); // Always point antenna toward Earth center
    });

    // 4. Parallax Camera Damping & Mouse-Look
    if (!this.isFreeOrbit) {
      // Apply mouse-look offset to camera position
      const mouseParallaxX = this.normalizedMouse.x * 22;
      const mouseParallaxY = -this.normalizedMouse.y * 16;

      const destX = this.targetCameraPos.x + mouseParallaxX;
      const destY = this.targetCameraPos.y + mouseParallaxY;
      const destZ = this.targetCameraPos.z;

      this.camera.position.x += (destX - this.camera.position.x) * 0.06;
      this.camera.position.y += (destY - this.camera.position.y) * 0.06;
      this.camera.position.z += (destZ - this.camera.position.z) * 0.06;

      this.currentLookAt.lerp(this.targetLookAt, 0.06);
      this.camera.lookAt(this.currentLookAt);
    } else {
      this.controls.update();
    }

    this.renderer.render(this.scene, this.camera);
  }
}

// Export singleton instance attached to window & ES Module
window.OrbitalEngine = OrbitalEngine;
export { OrbitalEngine };
export default OrbitalEngine;

<script setup>
import { onMounted, ref, onUnmounted, watch } from 'vue';
import * as THREE from 'three';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls';

const props = defineProps({
    satellites: {
        type: Array,
        default: () => []
    },
    weatherMetrics: {
        type: Object,
        default: () => ({})
    }
});

const globeContainer = ref(null);
let scene, camera, renderer, globe, clouds, controls, starfield;
let satelliteMarkers = new Map();
let orbitPaths = new Map();
let selectedSatellite = ref(null);
let hoveredSatellite = ref(null);
let hoveredSurface = ref(null); // { lat, lng }
let toolTipPos = ref({ x: 0, y: 0 });

const emit = defineEmits(['select', 'hover']);

// Color Map for Categories
const CATEGORY_COLORS = {
    'COMMUNICATION': 0x0ea5e9, // Bright Blue (Cyan-ish)
    'NAVIGATION': 0x22c55e,    // Bright Green
    'SCIENTIFIC': 0xa855f7,    // Bright Purple
    'SPACE_DEBRIS': 0xf97316,  // Strong Orange
    'STATION': 0xffffff,       // White (ISS)
    'WEATHER': 0x10b981,       // Green (Keep for backward compatibility or merge)
    'DEFAULT': 0xcccccc
};

onMounted(() => {
    initScene();
    animate();
});

onUnmounted(() => {
    if (renderer) renderer.dispose();
    window.removeEventListener('resize', onWindowResize);
    window.removeEventListener('mousedown', onMouseDown);
    window.removeEventListener('mousemove', onMouseMove);
});

watch(() => props.satellites, (newSats) => {
    updateSatelliteMarkers(newSats);
}, { deep: true });

const initScene = () => {
    scene = new THREE.Scene();
    scene.background = new THREE.Color(0x020205);

    camera = new THREE.PerspectiveCamera(45, window.innerWidth / window.innerHeight, 0.1, 2000);
    camera.position.set(0, 2, 4);

    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(globeContainer.value.clientWidth, globeContainer.value.clientHeight);
    renderer.setPixelRatio(window.devicePixelRatio);
    globeContainer.value.appendChild(renderer.domElement);

    // High Intensity Lighting for Contrast
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.4);
    scene.add(ambientLight);

    const sunLight = new THREE.DirectionalLight(0xffffff, 1.5);
    sunLight.position.set(5, 3, 5);
    scene.add(sunLight);

    // 1. Starfield (Enhanced Density)
    const starGeometry = new THREE.BufferGeometry();
    const starMaterial = new THREE.PointsMaterial({ color: 0xffffff, size: 0.8, transparent: true, opacity: 0.8 });
    const starVertices = [];
    for (let i = 0; i < 8000; i++) {
        const x = (Math.random() - 0.5) * 2000;
        const y = (Math.random() - 0.5) * 2000;
        const z = (Math.random() - 0.5) * 2000;
        starVertices.push(x, y, z);
    }
    starGeometry.setAttribute('position', new THREE.Float32BufferAttribute(starVertices, 3));
    starfield = new THREE.Points(starGeometry, starMaterial);
    scene.add(starfield);

    // 2. Earth Globe (High Res Placeholder)
    const geometry = new THREE.SphereGeometry(1, 128, 128);
    const textureLoader = new THREE.TextureLoader();
    
    const earthMaterial = new THREE.MeshPhongMaterial({
        map: textureLoader.load('https://unpkg.com/three-globe/example/img/earth-blue-marble.jpg'),
        bumpMap: textureLoader.load('https://unpkg.com/three-globe/example/img/earth-topology.png'),
        bumpScale: 0.02,
        specularMap: textureLoader.load('https://unpkg.com/three-globe/example/img/earth-water-mask.png'),
        specular: new THREE.Color('grey'),
        shininess: 10
    });
    
    globe = new THREE.Mesh(geometry, earthMaterial);
    scene.add(globe);

    // 3. Atmosphere (Fresnel Effect - Stronger Cyan)
    const atmosGeometry = new THREE.SphereGeometry(1.04, 128, 128);
    const atmosMaterial = new THREE.ShaderMaterial({
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
                float intensity = pow(0.7 - dot(vNormal, vec3(0.0, 0.0, 1.0)), 3.0);
                gl_FragColor = vec4(0.1, 0.8, 1.0, 1.0) * intensity;
            }
        `,
        blending: THREE.AdditiveBlending,
        side: THREE.BackSide,
        transparent: true
    });
    const atmosphere = new THREE.Mesh(atmosGeometry, atmosMaterial);
    scene.add(atmosphere);

    // 4. Clouds (Semi-Transparent)
    const cloudGeometry = new THREE.SphereGeometry(1.02, 64, 64);
    const cloudMaterial = new THREE.MeshPhongMaterial({
        map: textureLoader.load('https://unpkg.com/three-globe/example/img/earth-clouds.png'),
        transparent: true,
        opacity: 0.3
    });
    clouds = new THREE.Mesh(cloudGeometry, cloudMaterial);
    scene.add(clouds);

    // Controls
    controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.dampingFactor = 0.05;
    controls.minDistance = 1.3;
    controls.maxDistance = 15;

    updateSatelliteMarkers(props.satellites);
    window.addEventListener('resize', onWindowResize);
    window.addEventListener('mousedown', onMouseDown);
    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('keydown', onKeyDown);
};

const onKeyDown = (e) => {
    switch(e.key) {
        case '1': // North View
            camera.position.set(0, 4, 0);
            break;
        case '2': // Pacific View
            camera.position.set(4, 0, 4);
            break;
        case '3': // Atlantic View
            camera.position.set(-4, 0, 4);
            break;
        case '4': // South View
            camera.position.set(0, -4, 0);
            break;
        case 'r':
        case 'R':
            controls.autoRotate = !controls.autoRotate;
            break;
        case 'Escape':
            selectedSatellite.value = null;
            emit('select', null);
            resetPaths();
            break;
    }
    controls.update();
};

const onMouseMove = (event) => {
    const rect = renderer.domElement.getBoundingClientRect();
    const mouse = new THREE.Vector2(
        ((event.clientX - rect.left) / rect.width) * 2 - 1,
        -((event.clientY - rect.top) / rect.height) * 2 + 1
    );

    const raycaster = new THREE.Raycaster();
    raycaster.params.Points.threshold = 0.1;
    raycaster.setFromCamera(mouse, camera);

    const intersects = raycaster.intersectObjects(Array.from(satelliteMarkers.values()));
    
    if (intersects.length > 0) {
        const marker = intersects[0].object;
        for (let [id, m] of satelliteMarkers) {
            if (m === marker) {
                const sat = props.satellites.find(s => s.id === id);
                hoveredSatellite.value = sat;
                hoveredSurface.value = null;
                toolTipPos.value = { x: event.clientX, y: event.clientY };
                emit('hover', sat);
                
                // Visual feedback on marker
                m.scale.set(1.5, 1.5, 1.5);
                break;
            }
        }
    } else {
        // Check for Earth surface hits
        const sphereIntersects = raycaster.intersectObject(globe);
        if (sphereIntersects.length > 0) {
            const hit = sphereIntersects[0];
            const point = hit.point.clone().normalize();
            
            // Convert point to Lat/Lng
            const lat = Math.asin(point.y) * (180 / Math.PI);
            const lng = Math.atan2(point.z, -point.x) * (180 / Math.PI);
            
            hoveredSurface.value = { lat, lng };
            toolTipPos.value = { x: event.clientX, y: event.clientY };
            hoveredSatellite.value = null;

            // Reset marker scales
            for (let m of satelliteMarkers.values()) {
                m.scale.set(1, 1, 1);
            }
        } else {
            if (hoveredSatellite.value || hoveredSurface.value) {
                // Reset scales
                for (let m of satelliteMarkers.values()) {
                    m.scale.set(1, 1, 1);
                }
            }
            hoveredSatellite.value = null;
            hoveredSurface.value = null;
            emit('hover', null);
        }
    }
};

const updateSatelliteMarkers = (sats) => {
    const satIds = new Set(sats.map(s => s.id));
    
    // Remove old markers and paths
    for (let [id, marker] of satelliteMarkers) {
        if (!satIds.has(id)) {
            scene.remove(marker);
            satelliteMarkers.delete(id);
            if (orbitPaths.has(id)) {
                scene.remove(orbitPaths.get(id));
                orbitPaths.delete(id);
            }
        }
    }

    sats.forEach(sat => {
        if (!sat.latitude || !sat.longitude) return;

        const color = CATEGORY_COLORS[sat.type] || CATEGORY_COLORS.DEFAULT;
        const radius = 1 + (sat.altitude / 6371);

        // 1. Update/Add Marker
        let marker = satelliteMarkers.get(sat.id);
        if (!marker) {
            const markerGeo = new THREE.SphereGeometry(0.02, 16, 16); // Increased size
            const markerMat = new THREE.MeshBasicMaterial({ 
                color: color,
                transparent: true,
                opacity: 0.9
            });
            marker = new THREE.Mesh(markerGeo, markerMat);
            
            // Core Glow (Bigger)
            const spriteMat = new THREE.SpriteMaterial({
                map: createGlowTexture(color),
                color: color,
                transparent: true,
                blending: THREE.AdditiveBlending
            });
            const sprite = new THREE.Sprite(spriteMat);
            sprite.scale.set(0.12, 0.12, 1);
            marker.add(sprite);

            scene.add(marker);
            satelliteMarkers.set(sat.id, marker);
        }
        const pos = calcPosFromLatLng(sat.latitude, sat.longitude, radius);
        marker.position.copy(pos);

        // 2. Add/Update Orbit Path (Visual Representation)
        if (!orbitPaths.has(sat.id)) {
            const pathGeometry = new THREE.BufferGeometry();
            const points = [];
            const segments = 128;
            for (let i = 0; i <= segments; i++) {
                const angle = (i / segments) * Math.PI * 2;
                // Simplified circular orbit at current altitude
                const x = radius * Math.cos(angle);
                const z = radius * Math.sin(angle);
                points.push(new THREE.Vector3(x, 0, z));
            }
            pathGeometry.setFromPoints(points);
            
            // Tilt the orbit based on latitude/inclination placeholder
            const tilt = (sat.latitude / 90) * Math.PI / 2;
            pathGeometry.rotateX(tilt);
            pathGeometry.rotateZ(sat.longitude * Math.PI / 180);

            const pathMaterial = new THREE.LineBasicMaterial({ 
                color: color, 
                transparent: true, 
                opacity: 0.15 
            });
            const pathLine = new THREE.Line(pathGeometry, pathMaterial);
            scene.add(pathLine);
            orbitPaths.set(sat.id, pathLine);
        }
    });
};

const calcPosFromLatLng = (lat, lng, radius) => {
    const phi = (90 - lat) * (Math.PI / 180);
    const theta = (lng + 180) * (Math.PI / 180);
    return new THREE.Vector3(
        -radius * Math.sin(phi) * Math.cos(theta),
        radius * Math.cos(phi),
        radius * Math.sin(phi) * Math.sin(theta)
    );
};

const createGlowTexture = (color) => {
    const canvas = document.createElement('canvas');
    canvas.width = 64; canvas.height = 64;
    const ctx = canvas.getContext('2d');
    const grad = ctx.createRadialGradient(32, 32, 0, 32, 32, 32);
    grad.addColorStop(0, 'rgba(255,255,255,1)');
    grad.addColorStop(0.3, `rgba(${hexToRgb(color)}, 0.4)`);
    grad.addColorStop(1, 'rgba(0,0,0,0)');
    ctx.fillStyle = grad; ctx.fillRect(0, 0, 64, 64);
    return new THREE.CanvasTexture(canvas);
};

const hexToRgb = (hex) => {
    const r = (hex >> 16) & 255; const g = (hex >> 8) & 255; const b = hex & 255;
    return `${r},${g},${b}`;
};

const onMouseDown = (event) => {
    const rect = renderer.domElement.getBoundingClientRect();
    const mouse = new THREE.Vector2(
        ((event.clientX - rect.left) / rect.width) * 2 - 1,
        -((event.clientY - rect.top) / rect.height) * 2 + 1
    );

    const raycaster = new THREE.Raycaster();
    raycaster.setFromCamera(mouse, camera);

    const intersects = raycaster.intersectObjects(Array.from(satelliteMarkers.values()));
    if (intersects.length > 0) {
        const marker = intersects[0].object;
        for (let [id, m] of satelliteMarkers) {
            if (m === marker) {
                const sat = props.satellites.find(s => s.id === id);
                selectedSatellite.value = sat;
                emit('select', sat);
                highlightPath(id);
                break;
            }
        }
    } else {
        selectedSatellite.value = null;
        emit('select', null);
        resetPaths();
    }
};

const highlightPath = (id) => {
    resetPaths();
    const path = orbitPaths.get(id);
    if (path) {
        path.material.opacity = 0.8;
        path.material.linewidth = 2; // WebGL linewidth restriction applies
    }
};

const resetPaths = () => {
    for (let path of orbitPaths.values()) {
        path.material.opacity = 0.15;
    }
};

const onWindowResize = () => {
    camera.aspect = globeContainer.value.clientWidth / globeContainer.value.clientHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(globeContainer.value.clientWidth, globeContainer.value.clientHeight);
};

const animate = () => {
    requestAnimationFrame(animate);
    if (clouds) clouds.rotation.y += 0.0003;
    if (globe) globe.rotation.y += 0.0001;
    if (starfield) starfield.rotation.y -= 0.00005;
    
    controls.update();
    renderer.render(scene, camera);
};
</script>

<template>
    <div ref="globeContainer" class="w-full h-full relative cursor-crosshair overflow-hidden">
        <!-- Floating HUD Element -->
        <div class="absolute bottom-6 left-6 z-10 pointer-events-none">
            <div class="flex flex-col space-y-1">
                <div class="flex items-center space-x-2 text-[10px] text-white/40 uppercase tracking-[0.2em] font-black">
                    <span class="w-1.5 h-1.5 rounded-full bg-vibrant-blue shadow-[0_0_10px_#4f46e5]"></span>
                    <span>Kinetic Subsystem Online</span>
                </div>
                <div class="text-[8px] font-mono text-white/20 uppercase tracking-widest pl-3.5 italic">
                    Orbital Sync: Active
                </div>
            </div>
        </div>

        <!-- Selection Info (Minimal HUD) -->
        <div v-if="selectedSatellite" class="absolute top-1/2 left-8 -translate-y-1/2 glass p-4 rounded-xl border-l-4 border-vibrant-blue z-20 w-48 animate-in fade-in slide-in-from-left duration-300">
            <h4 class="text-[10px] font-black text-vibrant-blue uppercase tracking-widest mb-1">{{ selectedSatellite.type }}</h4>
            <p class="text-xs font-bold text-white mb-2">{{ selectedSatellite.name }}</p>
            <div class="space-y-1 text-[9px] font-mono text-white/50">
                <div class="flex justify-between"><span>ALT:</span> <span>{{ Math.round(selectedSatellite.altitude) }}km</span></div>
                <div class="flex justify-between"><span>VEL:</span> <span>{{ Math.round(selectedSatellite.velocity) }}k/s</span></div>
            </div>
        </div>

        <!-- Hover Tooltip -->
        <div v-if="hoveredSatellite || hoveredSurface" 
             class="fixed z-[100] pointer-events-none transition-all duration-75 ease-out"
             :style="{ left: toolTipPos.x + 20 + 'px', top: toolTipPos.y + 'px' }">
            
            <!-- Satellite Tooltip -->
            <div v-if="hoveredSatellite" class="glass border border-white/10 px-3 py-2 rounded-lg shadow-2xl flex flex-col space-y-1">
                <div class="flex items-center space-x-2">
                    <span :style="{ backgroundColor: 'rgba(' + hexToRgb(CATEGORY_COLORS[hoveredSatellite.type] || CATEGORY_COLORS.DEFAULT) + ', 1)' }" class="w-1.5 h-1.5 rounded-full"></span>
                    <span class="text-[9px] font-black text-white/90 uppercase tracking-tighter">{{ hoveredSatellite.name }}</span>
                </div>
                <div class="text-[8px] font-mono text-white/40 uppercase">
                    {{ hoveredSatellite.latitude.toFixed(2) }}°N / {{ hoveredSatellite.longitude.toFixed(2) }}°E
                </div>
            </div>

            <!-- Surface Tooltip (Weather Intelligence) -->
            <div v-else-if="hoveredSurface" class="glass border border-white/10 px-3 py-3 rounded-lg shadow-2xl min-w-[140px] space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black text-white/90 uppercase tracking-widest">Surface Int.</span>
                    <span class="text-[8px] font-mono text-white/30 uppercase tracking-tighter">{{ hoveredSurface.lat.toFixed(1) }}°, {{ hoveredSurface.lng.toFixed(1) }}°</span>
                </div>

                <!-- If over Vietnam Sector (Roughly) -->
                <div v-if="hoveredSurface.lat > 8 && hoveredSurface.lat < 24 && hoveredSurface.lng > 102 && hoveredSurface.lng < 110" class="space-y-2 pt-2 border-t border-white/5">
                    <div class="flex items-baseline justify-between">
                        <span class="text-[8px] text-white/30 uppercase">Atm. Pressure</span>
                        <span class="text-[10px] font-bold text-white">{{ weatherMetrics.pressure }} hPa</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="text-[8px] text-white/30 uppercase">Cloud Density</span>
                        <span class="text-[10px] font-bold text-vibrant-blue">{{ Math.round(weatherMetrics.cloud_density) }}%</span>
                    </div>
                </div>
                <div v-else class="text-[8px] text-white/20 uppercase italic">Awaiting Sector Scan...</div>
            </div>
        </div>
    </div>
</template>

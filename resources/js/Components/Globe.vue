<script setup>
import { onMounted, ref, onUnmounted, watch } from 'vue';
import * as THREE from 'three';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls';

const props = defineProps({
    satellites: {
        type: Array,
        default: () => []
    }
});

const globeContainer = ref(null);
let scene, camera, renderer, globe, clouds, controls, starfield;
let satelliteMarkers = new Map();

// Color Map for Categories
const CATEGORY_COLORS = {
    'STATION': 0xffffff, // White (ISS)
    'COMMUNICATION': 0x4f46e5, // Blue
    'WEATHER': 0x10b981, // Green
    'OBSERVATION': 0x8b5cf6, // Purple
    'NAVIGATION': 0xf59e0b, // Orange
    'DEFAULT': 0xcccccc
};

onMounted(() => {
    initScene();
    animate();
});

onUnmounted(() => {
    if (renderer) renderer.dispose();
    window.removeEventListener('resize', onWindowResize);
});

// Watch for satellite updates to update markers
watch(() => props.satellites, (newSats) => {
    updateSatelliteMarkers(newSats);
}, { deep: true });

const initScene = () => {
    scene = new THREE.Scene();
    scene.background = new THREE.Color(0x05050a);

    camera = new THREE.PerspectiveCamera(50, window.innerWidth / window.innerHeight, 0.1, 2000);
    camera.position.z = 4;

    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(globeContainer.value.clientWidth, globeContainer.value.clientHeight);
    renderer.setPixelRatio(window.devicePixelRatio);
    globeContainer.value.appendChild(renderer.domElement);

    // Lights
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.4);
    scene.add(ambientLight);

    const sunLight = new THREE.DirectionalLight(0xffffff, 1.2);
    sunLight.position.set(5, 3, 5);
    scene.add(sunLight);

    // 1. Starfield
    const starGeometry = new THREE.BufferGeometry();
    const starMaterial = new THREE.PointsMaterial({ color: 0xffffff, size: 0.7 });
    const starVertices = [];
    for (let i = 0; i < 5000; i++) {
        const x = (Math.random() - 0.5) * 2000;
        const y = (Math.random() - 0.5) * 2000;
        const z = (Math.random() - 0.5) * 2000;
        starVertices.push(x, y, z);
    }
    starGeometry.setAttribute('position', new THREE.Float32BufferAttribute(starVertices, 3));
    starfield = new THREE.Points(starGeometry, starMaterial);
    scene.add(starfield);

    // 2. Earth Globe
    const geometry = new THREE.SphereGeometry(1, 64, 64);
    const textureLoader = new THREE.TextureLoader();
    
    const earthMaterial = new THREE.MeshPhongMaterial({
        map: textureLoader.load('https://unpkg.com/three-globe/example/img/earth-blue-marble.jpg'),
        bumpMap: textureLoader.load('https://unpkg.com/three-globe/example/img/earth-topology.png'),
        bumpScale: 0.02,
        specularMap: textureLoader.load('https://unpkg.com/three-globe/example/img/earth-water-mask.png'),
        specular: new THREE.Color('grey'),
        shininess: 5
    });
    
    globe = new THREE.Mesh(geometry, earthMaterial);
    scene.add(globe);

    // 3. Atmosphere (Fresnel Effect)
    const atmosGeometry = new THREE.SphereGeometry(1.05, 64, 64);
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
                float intensity = pow(0.6 - dot(vNormal, vec3(0.0, 0.0, 1.0)), 2.0);
                gl_FragColor = vec4(0.3, 0.6, 1.0, 1.0) * intensity;
            }
        `,
        blending: THREE.AdditiveBlending,
        side: THREE.BackSide,
        transparent: true
    });
    const atmosphere = new THREE.Mesh(atmosGeometry, atmosMaterial);
    scene.add(atmosphere);

    // 4. Clouds
    const cloudGeometry = new THREE.SphereGeometry(1.02, 64, 64);
    const cloudMaterial = new THREE.MeshPhongMaterial({
        map: textureLoader.load('https://unpkg.com/three-globe/example/img/earth-clouds.png'),
        transparent: true,
        opacity: 0.4
    });
    clouds = new THREE.Mesh(cloudGeometry, cloudMaterial);
    scene.add(clouds);

    // Controls
    controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.dampingFactor = 0.05;
    controls.rotateSpeed = 0.5;
    controls.minDistance = 1.5;
    controls.maxDistance = 10;

    updateSatelliteMarkers(props.satellites);
    window.addEventListener('resize', onWindowResize);
};

const updateSatelliteMarkers = (sats) => {
    // Remove old markers that are not in the new list
    const satIds = new Set(sats.map(s => s.id));
    for (let [id, marker] of satelliteMarkers) {
        if (!satIds.has(id)) {
            scene.remove(marker);
            satelliteMarkers.delete(id);
        }
    }

    // Add or Update markers
    sats.forEach(sat => {
        if (!sat.latitude || !sat.longitude) return;

        const color = CATEGORY_COLORS[sat.type] || CATEGORY_COLORS.DEFAULT;
        const radius = 1 + (sat.altitude / 6371); // Normalize altitude to globe radius (6371km = 1 segment)

        let marker = satelliteMarkers.get(sat.id);
        if (!marker) {
            const markerGeo = new THREE.SphereGeometry(0.008, 8, 8);
            const markerMat = new THREE.MeshBasicMaterial({ color: color });
            marker = new THREE.Mesh(markerGeo, markerMat);
            
            // Add a subtle glow/point
            const spriteMat = new THREE.SpriteMaterial({
                map: createGlowTexture(color),
                color: color,
                transparent: true,
                blending: THREE.AdditiveBlending
            });
            const sprite = new THREE.Sprite(spriteMat);
            sprite.scale.set(0.05, 0.05, 1);
            marker.add(sprite);

            scene.add(marker);
            satelliteMarkers.set(sat.id, marker);
        }

        const position = calcPosFromLatLng(sat.latitude, sat.longitude, radius);
        marker.position.copy(position);
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
    canvas.width = 64;
    canvas.height = 64;
    const ctx = canvas.getContext('2d');
    const gradient = ctx.createRadialGradient(32, 32, 0, 32, 32, 32);
    gradient.addColorStop(0, 'rgba(255,255,255,1)');
    gradient.addColorStop(0.2, `rgba(${hexToRgb(color)}, 0.8)`);
    gradient.addColorStop(0.5, `rgba(${hexToRgb(color)}, 0.2)`);
    gradient.addColorStop(1, 'rgba(0,0,0,0)');
    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, 64, 64);
    const texture = new THREE.CanvasTexture(canvas);
    return texture;
};

const hexToRgb = (hex) => {
    const r = (hex >> 16) & 255;
    const g = (hex >> 8) & 255;
    const b = hex & 255;
    return `${r},${g},${b}`;
};

const onWindowResize = () => {
    camera.aspect = globeContainer.value.clientWidth / globeContainer.value.clientHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(globeContainer.value.clientWidth, globeContainer.value.clientHeight);
};

const animate = () => {
    requestAnimationFrame(animate);
    
    if (clouds) clouds.rotation.y += 0.0002;
    if (globe) globe.rotation.y += 0.0001;
    if (starfield) starfield.rotation.y -= 0.00005;

    controls.update();
    renderer.render(scene, camera);
};
</script>

<template>
    <div ref="globeContainer" class="w-full h-full relative cursor-grab active:cursor-grabbing overflow-hidden">
        <!-- Floating HUD Element -->
        <div class="absolute bottom-6 left-6 z-10 pointer-events-none">
            <div class="flex flex-col space-y-1">
                <div class="flex items-center space-x-2 text-[10px] text-white/40 uppercase tracking-[0.2em] font-black">
                    <span class="w-1.5 h-1.5 rounded-full bg-vibrant-blue shadow-[0_0_10px_#4f46e5]"></span>
                    <span>Kinetic Subsystem Online</span>
                </div>
                <div class="text-[8px] font-mono text-white/20 uppercase tracking-widest pl-3.5 italic">
                    Vector Analysis: Enabled
                </div>
            </div>
        </div>
    </div>
</template>

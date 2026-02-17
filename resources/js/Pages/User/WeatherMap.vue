<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import Globe from 'globe.gl';
import * as THREE from 'three';
import axios from 'axios';

const globeContainer = ref(null);
const activeLayer = ref('clouds');
const activeStorms = ref([]);
const activeSatellites = ref([]);
const selectedPoint = ref(null);
const selectedSatellite = ref(null);
const pointData = ref(null);
const isLoadingPoint = ref(false);

const layers = [
    { id: 'clouds', name: 'CLOUD_DENSITY', color: 'vibrant-blue' },
    { id: 'precip', name: 'PRECIPITATION', color: 'vibrant-green' },
    { id: 'wind', name: 'WIND_SPEED', color: 'yellow-500' },
];

onMounted(async () => {
    // Parallel Fetching
    try {
        const [stormRes, satRes] = await Promise.all([
            axios.get('/api/weather/storms'),
            axios.get('/api/v1/weather/satellites')
        ]);
        activeStorms.value = stormRes.data;
        activeSatellites.value = satRes.data.data;
    } catch (e) {
        console.error('Failed to fetch data', e);
    }

    const width = globeContainer.value.offsetWidth;
    const height = globeContainer.value.offsetHeight;

    const world = Globe()
        (globeContainer.value)
        .width(width)
        .height(height)
        .globeImageUrl('https://unpkg.com/three-globe/example/img/earth-night.jpg')
        .bumpImageUrl('https://unpkg.com/three-globe/example/img/earth-topology.png')
        .backgroundColor('#020205')
        
        // --- Storms Layer ---
        .ringsData(activeStorms.value)
        .ringColor(() => '#ef4444')
        .ringMaxRadius(5)
        .ringPropagationSpeed(2)
        .ringRepeatPeriod(1000)
        .pointsData(activeStorms.value)
        .pointColor(() => '#ef4444')
        .pointAltitude(0.01)
        .pointRadius(0.5)
        .labelsData(activeStorms.value)
        .labelLat(d => d.latitude)
        .labelLng(d => d.longitude)
        .labelText(d => d.name)
        .labelSize(1.5)
        .labelColor(() => '#ef4444')
        .labelResolution(2)

        // --- Satellites Layer ---
        .customLayerData(activeSatellites.value)
        .customThreeObject(d => {
            const isStrategic = d.norad_id === '41836' || d.norad_id === '40267' || d.norad_id === '25544'; 
            const size = isStrategic ? 1.2 : 0.6;
            const color = isStrategic ? '#00ffff' : '#0088ff';
            
            const group = new THREE.Group();
            
            // Core mesh
            const mesh = new THREE.Mesh(
                new THREE.BoxGeometry(size, size, size),
                new THREE.MeshPhongMaterial({ color, emissive: color, emissiveIntensity: 0.5 })
            );
            group.add(mesh);

            // Glow effect
            const glowMesh = new THREE.Mesh(
                new THREE.SphereGeometry(size * 1.5, 16, 16),
                new THREE.MeshBasicMaterial({ color, transparent: true, opacity: 0.15 })
            );
            group.add(glowMesh);

            return group;
        })
        .customThreeObjectUpdate((obj, d) => {
            const { lat, lng, alt } = d.position;
            Object.assign(obj.position, world.getCoords(lat, lng, alt));
            obj.lookAt(0, 0, 0); // Always point towards earth
        })
        .onCustomLayerClick(d => {
            selectedSatellite.value = d;
            selectedPoint.value = null; // Close point HUD if satellite is selected
            
            // Move camera to satellite
            world.pointOfView({ 
                lat: d.position.lat, 
                lng: d.position.lng, 
                altitude: 1.5 
            }, 1000);
        })

        // --- Orbit Paths Layer ---
        .pathsData(activeSatellites.value.map(s => s.path))
        .pathColor(() => 'rgba(0, 136, 255, 0.25)')
        .pathDashLength(0.05)
        .pathDashGap(0.01)
        .pathDashAnimateTime(60000) // Much slower for realism
        .pathAltitude(d => d[0][2] * 0.1) // Scaling down altitude for better visualization fit
        .pathStroke(0.15)
        
        // --- Satellite Labels ---
        .labelsData(activeSatellites.value.filter(s => s.norad_id === '41836' || s.norad_id === '25544' || s.norad_id === '40267'))
        .labelLat(d => d.position.lat)
        .labelLng(d => d.position.lng)
        .labelText(d => d.name)
        .labelSize(1.2)
        .labelDotRadius(0)
        .labelColor(() => '#00ffff')
        .labelAltitude(d => d.position.alt * 0.1 + 0.05)
        
        .onGlobeClick(async ({ lat, lng }) => {
            selectedPoint.value = { lat, lng };
            isLoadingPoint.value = true;
            pointData.value = null;

            try {
                const response = await axios.get('/api/v1/weather/point-info', {
                    params: { lat, lng }
                });
                pointData.value = response.data.data;
                isLoadingPoint.value = false;
            } catch (e) {
                console.error('Failed to fetch point intelligence', e);
                isLoadingPoint.value = false;
            }
        });

    world.controls().autoRotate = true;
    world.controls().autoRotateSpeed = 0.5;

    world.pointOfView({ lat: 10, lng: 106, altitude: 2.5 }, 2000);

    const handleResize = () => {
        if (!globeContainer.value) return;
        world.width(globeContainer.value.offsetWidth);
        world.height(globeContainer.value.offsetHeight);
    };

    window.addEventListener('resize', handleResize);
});
</script>

<template>
    <Head title="Professional Weather Map" />

    <UserLayout>
        <div class="h-[calc(100vh-12rem)] relative bg-[#050508] border border-white/5 overflow-hidden">
            <!-- Sidebar Controls -->
            <div class="absolute top-8 left-8 z-10 w-64 space-y-6">
                <div>
                    <h2 class="text-2xl font-black uppercase tracking-tighter italic">METEO_LAYERS</h2>
                    <p class="text-[9px] text-white/30 uppercase tracking-[0.3em] mt-1">Global Atmospheric Visualization</p>
                </div>

                <div class="space-y-2">
                    <button v-for="layer in layers" :key="layer.id"
                        @click="activeLayer = layer.id"
                        :class="activeLayer === layer.id ? 'bg-vibrant-blue/10 border-vibrant-blue/50 text-white' : 'bg-black/40 border-white/5 text-white/40'"
                        class="w-full text-left px-6 py-4 border transition-all flex items-center justify-between group">
                        <span class="text-[10px] font-black tracking-widest uppercase">{{ layer.name }}</span>
                        <div v-if="activeLayer === layer.id" class="w-1.5 h-1.5 rounded-full bg-vibrant-blue shadow-[0_0_10px_#0088ff]"></div>
                    </button>
                </div>

                <!-- Legend -->
                <div class="bg-black/60 backdrop-blur-md border border-white/5 p-4">
                    <p class="text-[8px] font-black text-white/40 uppercase mb-3">CONCENTRATION_INDEX</p>
                    <div class="h-2 w-full bg-gradient-to-r from-blue-900 via-vibrant-blue to-white rounded-full"></div>
                    <div class="flex justify-between mt-2 text-[7px] font-mono text-white/20">
                        <span>LOW</span>
                        <span>NOMINAL</span>
                        <span>CRITICAL</span>
                    </div>
                </div>
            </div>

            <!-- Satellite Telemetry HUD -->
            <Transition
                enter-active-class="transition duration-500 ease-out"
                enter-from-class="translate-x-full opacity-0"
                enter-to-class="translate-x-0 opacity-100"
                leave-active-class="transition duration-300 ease-in"
                leave-from-class="translate-x-0 opacity-100"
                leave-to-class="translate-x-full opacity-0"
            >
                <div v-if="selectedSatellite" class="absolute top-8 right-8 z-30 w-80 bg-black/90 backdrop-blur-2xl border border-vibrant-blue/50 shadow-[0_0_50px_rgba(0,136,255,0.2)]">
                    <div class="p-5 border-b border-vibrant-blue/20 bg-vibrant-blue/10 flex justify-between items-center">
                        <div>
                            <p class="text-[8px] font-black text-vibrant-blue uppercase tracking-[0.4em] mb-1">Satellite_Intel</p>
                            <h3 class="text-lg font-black uppercase tracking-tighter italic leading-none">{{ selectedSatellite.name }}</h3>
                        </div>
                        <button @click="selectedSatellite = null" class="text-white/40 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-6">
                        <!-- Technical Profile -->
                        <div class="grid grid-cols-2 gap-px bg-white/5 border border-white/10">
                            <div class="p-4 bg-[#0a0a0f]">
                                <p class="text-[8px] font-black text-white/20 uppercase mb-1">NORAD_ID</p>
                                <p class="text-sm font-black text-vibrant-blue font-mono">{{ selectedSatellite.norad_id }}</p>
                            </div>
                            <div class="p-4 bg-[#0a0a0f]">
                                <p class="text-[8px] font-black text-white/20 uppercase mb-1">TYPE</p>
                                <p class="text-sm font-black text-white italic truncate">{{ selectedSatellite.type }}</p>
                            </div>
                        </div>

                        <!-- Live Telemetry -->
                        <div class="space-y-4">
                            <h4 class="text-[10px] font-black text-white/40 uppercase tracking-widest border-l-2 border-vibrant-blue pl-3">Live_Telemetry_Stream</h4>
                            
                            <div class="space-y-3">
                                <div class="flex justify-between items-end border-b border-white/5 pb-2">
                                    <span class="text-[9px] text-white/30 font-bold uppercase">Altitude</span>
                                    <span class="text-sm font-black text-white">{{ selectedSatellite.telemetry.altitude.toLocaleString() }} <span class="text-[9px] text-white/20">KM</span></span>
                                </div>
                                <div class="flex justify-between items-end border-b border-white/5 pb-2">
                                    <span class="text-[9px] text-white/30 font-bold uppercase">Velocity</span>
                                    <span class="text-sm font-black text-vibrant-green">{{ selectedSatellite.telemetry.velocity.toFixed(3) }} <span class="text-[9px] text-white/20">KM/S</span></span>
                                </div>
                                <div class="flex justify-between items-end border-b border-white/5 pb-2">
                                    <span class="text-[9px] text-white/30 font-bold uppercase">Orbit_Period</span>
                                    <span class="text-sm font-black text-yellow-500">{{ selectedSatellite.telemetry.period.toFixed(2) }} <span class="text-[9px] text-white/20">MIN</span></span>
                                </div>
                            </div>
                        </div>

                        <!-- Position Vectors -->
                        <div class="p-4 bg-vibrant-blue/5 border border-vibrant-blue/20">
                            <p class="text-[8px] font-black text-vibrant-blue/60 uppercase tracking-widest mb-3 text-center">CURRENT_SPATIAL_VECTORS</p>
                            <div class="flex justify-around font-mono text-xs font-bold italic">
                                <div><span class="text-white/30 mr-1">LAT:</span> {{ selectedSatellite.position.lat.toFixed(4) }}</div>
                                <div><span class="text-white/30 mr-1">LNG:</span> {{ selectedSatellite.position.lng.toFixed(4) }}</div>
                            </div>
                        </div>

                        <div class="pt-4">
                            <button class="w-full py-3 bg-white text-black text-[9px] font-black uppercase tracking-[0.3em] hover:bg-vibrant-blue hover:text-white transition-all shadow-[0_0_20px_rgba(255,255,255,0.1)]">
                                ACCESS_SENSOR_PAYLOAD
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- Point Intelligence HUD -->
            <Transition
                enter-active-class="transition duration-500 ease-out"
                enter-from-class="translate-x-full opacity-0"
                enter-to-class="translate-x-0 opacity-100"
                leave-active-class="transition duration-300 ease-in"
                leave-from-class="translate-x-0 opacity-100"
                leave-to-class="translate-x-full opacity-0"
            >
                <div v-if="selectedPoint" class="absolute top-8 right-8 z-20 w-80 bg-black/80 backdrop-blur-xl border border-vibrant-blue/30 shadow-[0_0_30px_rgba(0,136,255,0.1)]">
                    <div class="p-4 border-b border-white/10 flex justify-between items-center bg-vibrant-blue/5">
                        <div class="flex items-center space-x-3">
                            <div class="w-2 h-2 bg-vibrant-blue animate-pulse"></div>
                            <h3 class="text-[10px] font-black uppercase tracking-[0.2em]">PT_INTELLIGENCE</h3>
                        </div>
                        <button @click="selectedPoint = null" class="text-white/20 hover:text-white transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>

                    <!-- Loading State -->
                    <div v-if="isLoadingPoint" class="p-12 text-center">
                        <div class="inline-block w-8 h-8 border-2 border-vibrant-blue border-t-transparent rounded-full animate-spin mb-4"></div>
                        <p class="text-[9px] font-black text-vibrant-blue uppercase tracking-widest animate-pulse">Retrieving_Data...</p>
                    </div>

                    <!-- Data Display -->
                    <div v-else-if="pointData" class="p-6 space-y-6">
                        <div class="space-y-1">
                            <p class="text-[9px] text-white/30 uppercase tracking-widest">COORDINATE_TARGET</p>
                            <p class="text-sm font-black italic text-vibrant-blue">{{ selectedPoint.lat.toFixed(4) }}° N, {{ selectedPoint.lng.toFixed(4) }}° E</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-3 bg-white/[0.02] border border-white/5">
                                <p class="text-[8px] font-black text-white/20 uppercase mb-1">Temperature</p>
                                <p class="text-xl font-black italic">{{ pointData.temperature.toFixed(1) }}°C</p>
                            </div>
                            <div class="p-3 bg-white/[0.02] border border-white/5">
                                <p class="text-[8px] font-black text-white/20 uppercase mb-1">Wind_Speed</p>
                                <p class="text-xl font-black italic">{{ pointData.wind_speed.toFixed(1) }} <span class="text-[10px]">km/h</span></p>
                            </div>
                            <div class="p-3 bg-white/[0.02] border border-white/5">
                                <p class="text-[8px] font-black text-white/20 uppercase mb-1">Pressure</p>
                                <p class="text-xl font-black italic">{{ (pointData.pressure / 1).toFixed(0) }} <span class="text-[10px]">hPa</span></p>
                            </div>
                            <div class="p-3 bg-white/[0.02] border border-white/5">
                                <p class="text-[8px] font-black text-white/20 uppercase mb-1">Humidity</p>
                                <p class="text-xl font-black italic">{{ pointData.humidity.toFixed(0) }}%</p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex justify-between items-center text-[9px] font-black uppercase tracking-widest">
                                <span class="text-white/40">Cloud_Density</span>
                                <span class="text-vibrant-blue">{{ pointData.cloud_density.toFixed(0) }}%</span>
                            </div>
                            <div class="h-1 w-full bg-white/5 overflow-hidden">
                                <div class="h-full bg-vibrant-blue transition-all duration-1000" :style="{ width: pointData.cloud_density + '%' }"></div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-white/5">
                            <button class="w-full py-3 bg-vibrant-blue text-white text-[9px] font-black uppercase tracking-widest hover:scale-[1.02] transition-all">
                                GENERATE_DETAILED_FORECAST
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- Bottom Left Info -->
            <div class="absolute bottom-8 left-8 z-10 p-4 border-l border-white/20 bg-black/40 backdrop-blur-sm">
                <p class="text-[10px] font-black uppercase italic text-vibrant-blue">ACTIVE_DATA_SOURCE: HIMAWARI_9</p>
                <p class="text-[8px] text-white/40 font-mono mt-1 uppercase">Resolving spatial vectors @ 2.5km/px</p>
            </div>


            <!-- Globe Container -->
            <div ref="globeContainer" class="w-full h-full cursor-grab active:cursor-grabbing"></div>
            
            <!-- HUD Overlay Decoration -->
            <div class="absolute inset-0 pointer-events-none border-[20px] border-black/10"></div>
        </div>
    </UserLayout>
</template>

<style scoped>
/* HUD aesthetics would go here */
</style>

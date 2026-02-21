<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import Globe from 'globe.gl';
import * as THREE from 'three';
import axios from 'axios';
import { 
    Satellite, Cloud, CloudLightning, CloudRain, Wind, Zap, 
    Droplets, Thermometer, Sparkles, AlertTriangle, Ship, Leaf,
    Globe as GlobeIcon, Map as MapIcon, Sun
} from 'lucide-vue-next';

const globeContainer = ref(null);
const leafletContainer = ref(null);
const viewMode = ref('GLOBE'); // GLOBE, SATELLITE, FLAT
const activeLayers = ref(['clouds', 'satellites']); // Multi-layer selection
const activeStorms = ref([]);
const activeSatellites = ref([]);
const selectedPoint = ref(null);
const selectedSatellite = ref(null);
const pointData = ref(null);
const isLoadingPoint = ref(false);
const showBottomForecast = ref(false);
const forecastData = ref([]);
const isLoadingForecast = ref(false);
const orbitTick = ref(0);
const lastFetchTime = ref(Date.now());
const isPOVMode = ref(false);

const togglePOV = () => {
    isPOVMode.value = !isPOVMode.value;
    if (isPOVMode.value && selectedSatellite.value && world) {
        // Initial transition
        world.pointOfView({
            lat: selectedSatellite.value.position.lat,
            lng: selectedSatellite.value.position.lng,
            altitude: 0.5
        }, 1000);
    }
};

const propagateSatellites = () => {
    if (activeSatellites.value.length === 0) return;
    
    const now = isLive.value ? Date.now() : playbackTime.value;
    const delta = (now - lastFetchTime.value) * timeMultiplier.value;
    
    activeSatellites.value.forEach(sat => {
        if (!sat.path || sat.path.length < 2 || !sat.telemetry) return;
        
        const path = sat.path;
        const totalPoints = path.length;
        const periodMs = (sat.telemetry.period || 90) * 60 * 1000;
        const segmentDuration = periodMs / totalPoints;
        
        const totalProgress = delta / segmentDuration;
        // Wrap around logic for indices to prevent negative bounds
        const index = ((Math.floor(totalProgress) % totalPoints) + totalPoints) % totalPoints;
        const nextIndex = (index + 1) % totalPoints;
        const progress = ((totalProgress % 1) + 1) % 1; 
        
        const currentPos = path[index];
        const nextPos = path[nextIndex];
        
        if (!currentPos || !nextPos) return;

        const nextLat = currentPos[0] + (nextPos[0] - currentPos[0]) * progress;
        const nextLng = currentPos[1] + (nextPos[1] - currentPos[1]) * progress;

        sat.position = {
            lat: nextLat,
            lng: nextLng,
            alt: currentPos[2] || 0.1
        };

        if (selectedSatellite.value && selectedSatellite.value.norad_id === sat.norad_id) {
            selectedSatellite.value.position = { ...sat.position };
            if (isPOVMode.value && world) {
                world.pointOfView({ lat: nextLat, lng: nextLng, altitude: 0.4 }, 0); 
            }
        }
    });

    if (world) {
        // Toggle satellite visibility based on activeLayers array
        if (activeLayers.value.includes('satellites')) {
            // Force a deep update by mapping to new objects if needed, 
            // but usually customLayerData handles this if the array ref changes.
            world.customLayerData([...activeSatellites.value.map(s => ({...s}))]);
        } else {
            world.customLayerData([]);
        }
        if (Math.floor(Date.now() / 100) % 10 === 0) syncCommsLinks();
    }
};

const refreshTacticalData = async () => {
    try {
        const token = 'vethinh_strategic_internal_token_2026';
        const res = await axios.get(`/api/internal-map/satellites?token=${token}`);
        activeSatellites.value = res.data.data;
        lastFetchTime.value = Date.now();
        
        if (world) {
            world.customLayerData(activeSatellites.value);
            world.pathsData(activeSatellites.value.map(s => s.path));
        }
        console.log("TACTICAL_SYNC_COMPLETE: Orbital paths updated.");
    } catch (e) {
        console.error("Tactical sync failed", e);
    }
};

const fetchForecast = async (lat, lng) => {
    isLoadingForecast.value = true;
    showBottomForecast.value = true;
    try {
        const token = 'vethinh_strategic_internal_token_2026';
        const response = await axios.get('/api/internal-map/forecast', {
            params: { lat, lng, token }
        });
        forecastData.value = response.data.data;
    } catch (e) {
        console.error('Forecast fetch failed', e);
    } finally {
        isLoadingForecast.value = false;
    }
};

const handleGlobeClick = async (arg1, arg2, arg3) => {
    // Globe.gl event dispatching logic:
    // onGlobeClick: (coords, event) -> coords = { lat, lng }
    // onPolygonClick: (polygon, event, coords) -> coords = { lat, lng }
    // onPointClick: (point, event) -> point = { latitude, longitude }
    // onLabelClick: (label, event) -> label = { latitude, longitude }
    
    let lat, lng;
    
    if (arg3 && arg3.lat !== undefined) {
        // onPolygonClick case
        lat = arg3.lat;
        lng = arg3.lng;
    } else if (arg1 && arg1.lat !== undefined) {
        // onGlobeClick or custom {lat, lng} case
        lat = arg1.lat;
        lng = arg1.lng;
    } else if (arg1 && arg1.latitude !== undefined) {
        // onPointClick or onLabelClick case
        lat = arg1.latitude;
        lng = arg1.longitude;
    }

    if (lat === undefined || lng === undefined) {
        console.warn('Could not resolve coordinates from click', { arg1, arg2, arg3 });
        return;
    }

    // Drawing Mode Logic
    if (isDrawingZone.value) {
        currentZonePoints.value.push([lat, lng]);
        if (world) {
            // Visualize temporary polygon
            world.polygonsData([
                ...watchZones.value,
                { id: 'preview', points: currentZonePoints.value, threat: 'SCANNING' }
            ])
            .polygonCapColor(d => d.threat === 'SCANNING' ? 'rgba(255, 0, 0, 0.3)' : 'rgba(0, 136, 255, 0.05)')
            .polygonSideColor(() => 'rgba(0, 136, 255, 0.02)')
            .polygonStrokeColor(d => d.threat === 'SCANNING' ? '#ff0000' : 'rgba(255, 255, 255, 0.1)');
        }
        return;
    }

    selectedPoint.value = { lat, lng };
    isLoadingPoint.value = true;
    pointData.value = null;
    
    // Trigger Windy-style Bottom Forecast
    fetchForecast(lat, lng);

    try {
        const token = 'vethinh_strategic_internal_token_2026';
        const response = await axios.get('/api/internal-map/point-info', {
            params: { lat, lng, token }
        });
        
        // Inject AI Analysis metrics
        pointData.value = {
            ...response.data.data,
            ai_analysis: {
                cloud_depth: 15 + Math.random() * 45, // Simulating volumetric in KM
                cyclone_genesis: Math.abs(lat) < 20 ? (10 + Math.random() * 20) : (Math.random() * 5),
                anomaly_detected: Math.random() > 0.8
            }
        };
        isLoadingPoint.value = false;
    } catch (e) {
        console.error('Failed to fetch point intelligence', e);
        isLoadingPoint.value = false;
    }
};

// Pro Upgrade State
const searchQuery = ref('');
const searchResults = ref([]);
const isSearching = ref(false);
const groundStations = ref([]);
const radarFacilities = ref([]); // NEW: Physical Radar Stations
const radarTimestamp = ref(null);
const showRadar = ref(false);
const isSyncingSatellites = ref(false);
const showGroundStations = ref(true);
const showLightning = ref(false);
const lightningData = ref([]);
const isDrawingZone = ref(false);
const currentZonePoints = ref([]);
const watchZones = ref([]);
const telemetryData = ref(null);
const showSensorPayload = ref(false);

const toggleSensorPayload = () => {
    showSensorPayload.value = !showSensorPayload.value;
};

// Polling Timer for Telemetry
let telemetryInterval = null;

const fetchLatestTelemetry = async () => {
    if (!selectedSatellite.value) return;
    try {
        const res = await axios.get(`/storage/telemetry/${selectedSatellite.value.norad_id}/latest.json?t=${Date.now()}`);
        telemetryData.value = res.data;
    } catch (e) {
        // Silently fail to keep the loop clean
    }
};

// Watcher for selectedSatellite to fetch telemetry
watch(selectedSatellite, async (newSat) => {
    if (telemetryInterval) clearInterval(telemetryInterval);
    
    if (newSat) {
        await fetchLatestTelemetry(); // Instant first fetch
        telemetryInterval = setInterval(fetchLatestTelemetry, 1000); // 1Hz Polling
    } else {
        telemetryData.value = null;
    }
});

onUnmounted(() => {
    if (telemetryInterval) clearInterval(telemetryInterval);
});
const auroraData = ref([]);
const riskHeatmapData = ref([]);
const windParticles = ref([]);
const marineData = ref([]);
const ndviData = ref([]);
const isLive = ref(true);
const playbackTime = ref(Date.now());
const modelMode = ref('ECMWF'); // ECMWF, GFS, COMPARE
const timeMultiplier = ref(300); // 300x for fast orbital visualization

const imageryConstellations = [
    { id: '41836', name: 'ASIA_PACIFIC (HIMAWARI)', region: 'ASPAC', norad_id: '41836' },
    { id: '60133', name: 'AMERICAS (GOES-EAST)', region: 'AMER', norad_id: '60133' },
    { id: '28912', name: 'EMEA (METEOSAT)', region: 'EMEA', norad_id: '28912' },
];
const selectedConstellation = ref(imageryConstellations[0]);

const toggleLayer = (id) => {
    const index = activeLayers.value.indexOf(id);
    if (index === -1) {
        activeLayers.value.push(id);
    } else {
        activeLayers.value.splice(index, 1);
        // Explicitly clear layers if unticked
        if (id === 'satellites') world.customLayerData([]);
        if (id === 'wind') world.pathsData(activeLayers.value.includes('satellites') ? activeSatellites.value.map(s => s.path) : []);
    }
    syncGlobeLayers();
};

const syncGlobeLayers = () => {
    if (!world) return;

    // 0. Update Globe Surface (Clouds/Imagery)
    if (activeLayers.value.includes('clouds')) {
        // Use the selected constellation's latest image
        // Or implement 12h history logic if needed. 
        // For real-time, we use the 'latest' pointer.
        const url = `/storage/imagery/${selectedConstellation.value.id}/latest.jpg?t=${Date.now()}`;
        world.globeImageUrl(url);
    } else {
        world.globeImageUrl('https://unpkg.com/three-globe/example/img/earth-blue-marble.jpg');
    }

    // 1. Unified Points Layer (Storms + Marine + Lightning + Ground Stations)
    let combinedPoints = [];
    if (activeLayers.value.includes('storms')) {
        combinedPoints = [...combinedPoints, ...activeStorms.value.map(s => ({ ...s, isStorm: true }))];
    }
    if (activeLayers.value.includes('marine')) {
        if (marineData.value.length === 0) generateMarineData();
        combinedPoints = [...combinedPoints, ...marineData.value.map(v => ({ ...v, isMarine: true }))];
    }
    if (activeLayers.value.includes('lightning') && showLightning.value) {
        combinedPoints = [...combinedPoints, ...lightningData.value.map(l => ({ ...l, isLightning: true }))];
    }
    if (groundStations.value.length > 0) {
        combinedPoints = [...combinedPoints, ...groundStations.value.map(s => ({ ...s, isStation: true }))];
    }
    if (showRadar.value && radarFacilities.value.length > 0) {
        combinedPoints = [...combinedPoints, ...radarFacilities.value.map(s => ({ ...s, isRadar: true, lat: s.latitude, lng: s.longitude }))];
    }
    
    world.pointsData(combinedPoints)
         .pointColor(d => d.isStorm ? '#ef4444' : (d.isMarine ? '#00ccff' : (d.isStation ? '#00ffaa' : (d.isLightning ? '#ffffff' : (d.isRadar ? '#facc15' : '#0088ff')))))
         .pointAltitude(d => d.isRadar ? 0.05 : 0.01)
         .pointRadius(d => d.isRadar ? 0.6 : 0.5);

    // 2. Unified Rings Layer (Storms + Aurora)
    let combinedRings = [];
    if (activeLayers.value.includes('storms')) {
        combinedRings = [...combinedRings, ...activeStorms.value];
    }
    if (activeLayers.value.includes('aurora')) {
        if (auroraData.value.length === 0) generateAuroraData();
        combinedRings = [...combinedRings, ...auroraData.value];
    }
    world.ringsData(combinedRings);

    // 3. Unified Heatmaps (Risk + SST)
    let combinedHeatmaps = [];
    if (activeLayers.value.includes('risk')) {
        combinedHeatmaps.push({
            data: riskHeatmapData.value.length ? riskHeatmapData.value : generateRiskData(),
            lat: d => d.lat, lng: d => d.lng, weight: d => d.weight,
            radius: 15, opacity: 0.4, colorInterpolator: t => `rgba(255, 0, 0, ${t})`
        });
    }
    if (activeLayers.value.includes('sst')) {
        combinedHeatmaps.push({
            data: Array.from({ length: 100 }, () => ({ lat: (Math.random() - 0.5) * 160, lng: (Math.random() - 0.5) * 360, temp: 20 + Math.random() * 10 })),
            lat: d => d.lat, lng: d => d.lng, weight: d => (d.temp - 20) / 10,
            radius: 25, opacity: 0.3, colorInterpolator: t => `rgba(255, 165, 0, ${t})`
        });
    }
    world.heatmapsData(combinedHeatmaps);

    // 4. Hexbins (AQI)
    if (activeLayers.value.includes('aqi')) renderAQILayer();
    else world.hexBinPointsData([]);

    // 5. Polygons (NDVI + Watch Zones)
    if (activeLayers.value.includes('ndvi')) renderNDVILayer();
    else {
        // Keep watch zones visible if not in NDVI mode
        world.polygonsData(watchZones.value);
    }

    // 6. Paths (Wind + Orbits)
    const showOrbits = activeLayers.value.includes('satellites');
    const showWind = activeLayers.value.includes('wind');
    
    // Wrap orbit paths in objects to carry metadata
    const orbitPaths = showOrbits ? activeSatellites.value.map(s => ({
        path: s.path,
        isOrbit: true,
        norad_id: s.norad_id
    })) : [];
    
    if (showWind) {
        if (windParticles.value.length === 0) generateWindParticles();
        world.pathsData([...orbitPaths, ...windParticles.value])
             .pathColor(d => d.isOrbit ? '#00ffff' : 'rgba(255, 255, 255, 0.3)')
             .pathDashLength(d => d.isOrbit ? 0.1 : 0.4)
             .pathDashGap(0.01)
             .pathDashAnimateTime(d => d.isOrbit ? 20000 : 2000)
             .pathStroke(d => d.isOrbit ? 0.25 : 0.1);
    } else {
        world.pathsData(orbitPaths)
             .pathColor(() => '#00ffff')
             .pathStroke(0.25)
             .pathDashLength(0.1)
             .pathDashGap(0.01)
             .pathDashAnimateTime(20000);
    }
    
    // Ensure rotation is active
    if (world.controls()) {
        world.controls().autoRotate = true;
        world.controls().autoRotateSpeed = 0.05;
    }

    // 7. Custom Layers (Satellites)
    if (activeLayers.value.includes('satellites')) {
        world.customLayerData([...activeSatellites.value]);
    } else {
        world.customLayerData([]);
    }

    // 8. Labels
    const strategicSats = activeLayers.value.includes('satellites') 
        ? activeSatellites.value.filter(s => s.norad_id === '41836' || s.norad_id === '25544' || s.norad_id === '40267')
        : [];
    world.labelsData([...activeStorms.value, ...strategicSats]);
};

// Auto-sync on layer changes
watch(activeLayers, () => syncGlobeLayers(), { deep: true });

const generateMarineData = () => {
    marineData.value = Array.from({ length: 500 }, () => ({
        lat: (Math.random() - 0.5) * 120,
        lng: (Math.random() - 0.5) * 360,
        name: `VESSEL_${Math.floor(Math.random() * 9000 + 1000)}`,
        type: ['CONTAINER', 'TANKER', 'CARGO'][Math.floor(Math.random() * 3)],
        speed: (Math.random() * 25).toFixed(1) + ' kn'
    }));
};

const generateRiskData = () => {
    riskHeatmapData.value = Array.from({ length: 50 }, () => ({
        lat: (Math.random() - 0.5) * 120,
        lng: (Math.random() - 0.5) * 360,
        weight: Math.random()
    }));
    return riskHeatmapData.value;
};

const generateAuroraData = () => {
    auroraData.value = [
        { lat: 80, lng: 0, color: '#00ff88', maxR: 40, propagationSpeed: 1, repeatPeriod: 2000 },
        { lat: -80, lng: 0, color: '#00ccff', maxR: 35, propagationSpeed: 0.8, repeatPeriod: 2500 }
    ];
};
const toggleDrawingMode = () => {
    isDrawingZone.value = !isDrawingZone.value;
    if (isDrawingZone.value) {
        currentZonePoints.value = [];
        notifyDrawingStart();
    } else if (currentZonePoints.value.length > 2) {
        saveWatchZone();
    } else {
        if (world) world.polygonsData(watchZones.value);
    }
};

const toggleAurora = () => {
    if (auroraData.value.length > 0) {
        auroraData.value = [];
        if (world) world.ringsData([]);
    } else {
        // North & South Pole Auroras
        auroraData.value = [
            { lat: 80, lng: 0, color: '#00ff88', maxR: 40, propagationSpeed: 1, repeatPeriod: 2000 },
            { lat: -80, lng: 0, color: '#00ccff', maxR: 35, propagationSpeed: 0.8, repeatPeriod: 2500 }
        ];
        if (world) {
            world.ringsData(auroraData.value)
                 .ringColor(d => d.color)
                 .ringMaxRadius('maxR')
                 .ringPropagationSpeed('propagationSpeed')
                 .ringRepeatPeriod('repeatPeriod');
        }
    }
};

const toggleRiskHeatmap = () => {
    if (riskHeatmapData.value.length > 0) {
        riskHeatmapData.value = [];
        if (world) world.heatmapsData([]);
    } else {
        // Generate random risk clusters
        riskHeatmapData.value = Array.from({ length: 50 }, () => ({
            lat: (Math.random() - 0.5) * 120,
            lng: (Math.random() - 0.5) * 360,
            weight: Math.random()
        }));
        if (world) {
            world.heatmapsData([
                {
                    data: riskHeatmapData.value,
                    lat: d => d.lat,
                    lng: d => d.lng,
                    weight: d => d.weight,
                    radius: 15,
                    opacity: 0.4,
                    colorInterpolator: t => `rgba(255, 0, 0, ${t})`
                }
            ]);
        }
    }
};
// Layer Synchronization logic is now managed by syncGlobeLayers() triggered from UI

const renderAQILayer = () => {
    // Simulating AQI hotspots (e.g. Asia/India/China)
    const points = Array.from({ length: 200 }, () => ({
        lat: 10 + (Math.random() * 40),
        lng: 70 + (Math.random() * 80),
        value: 50 + Math.random() * 450
    }));
    if (world) {
        world.hexBinPointsData(points)
             .hexBinResolution(4)
             .hexTopColor(d => d.sumWeight > 300 ? '#ef4444' : '#eab308')
             .hexSideColor(() => 'rgba(255,255,255,0.1)')
             .hexBinMerge(true)
             .hexTopCurvatureColor('#ef4444');
    }
};

const renderSSTLayer = () => {
    // SST is better as heatmap
    const points = Array.from({ length: 300 }, () => ({
        lat: (Math.random() - 0.5) * 60, // Near equator
        lng: (Math.random() - 0.5) * 360,
        weight: Math.random()
    }));
    if (world) {
        world.heatmapsData([{
            data: points,
            lat: d => d.lat,
            lng: d => d.lng,
            weight: d => d.weight,
            radius: 20,
            opacity: 0.5,
            colorInterpolator: t => `interpolateWarm(${t})`
        }]);
    }
};

const generateWindParticles = () => {
    // Generate ~2000 wind vectors
    const particles = [];
    for (let i = 0; i < 2000; i++) {
        const startLat = (Math.random() - 0.5) * 160;
        const startLng = (Math.random() - 0.5) * 360;
        
        // Simulating wind flow along latitudes (Trade winds/Westerlies)
        const length = 2 + Math.random() * 5;
        const windDirection = startLat > 0 ? 1 : -1; // General direction
        const endLat = startLat + (Math.random() - 0.5) * 2;
        const endLng = startLng + (5 + Math.random() * 10) * windDirection;

        particles.push([
            [startLat, startLng, 0.01],
            [endLat, endLng, 0.01]
        ]);
    }
    windParticles.value = particles;
};

const toggleWindLayer = (active) => {
    const showOrbits = activeLayers.value.includes('satellites');
    const orbitPaths = showOrbits ? activeSatellites.value.map(s => s.path) : [];
    
    if (active) {
        generateWindParticles();
        if (world) {
            world.pathsData([...orbitPaths, ...windParticles.value])
                 .pathColor(d => d.norad_id ? 'rgba(0, 136, 255, 0.4)' : 'rgba(255, 255, 255, 0.3)')
                 .pathDashLength(d => d.norad_id ? 0.01 : 0.5)
                 .pathDashGap(0.1)
                 .pathDashAnimateTime(d => d.norad_id ? 0 : 2000)
                 .pathStroke(d => d.norad_id ? 0.3 : 0.15);
        }
    } else {
        if (world) {
            world.pathsData(orbitPaths)
                 .pathColor('rgba(0, 136, 255, 0.4)')
                 .pathStroke(0.3)
                 .pathDashAnimateTime(0);
        }
    }
};

const renderMarineLayer = () => {
    // Simulating global shipping traffic
    const vessels = Array.from({ length: 500 }, () => ({
        lat: (Math.random() - 0.5) * 120,
        lng: (Math.random() - 0.5) * 360,
        name: `VESSEL_${Math.floor(Math.random() * 9000 + 1000)}`,
        type: ['CONTAINER', 'TANKER', 'CARGO'][Math.floor(Math.random() * 3)],
        speed: (Math.random() * 25).toFixed(1) + ' kn'
    }));
    
    marineData.value = vessels;
    if (world) {
        world.pointsData(marineData.value)
             .pointColor(() => '#00ccff')
             .pointRadius(0.4)
             .pointAltitude(0.02)
             .pointLabel(d => `VESSEL: ${d.name}\nTYPE: ${d.type}\nSPEED: ${d.speed}`);
    }
};

const renderNDVILayer = () => {
    // NDVI is represented by coloring countries or regions based on vegetation health
    // We use the already loaded countries data for this
    fetch('https://raw.githubusercontent.com/vasturiano/globe.gl/master/example/datasets/ne_110m_admin_0_countries.geojson')
        .then(res => res.json())
        .then(countries => {
            if (world) {
                world.polygonsData(countries.features)
                     .polygonCapColor(() => {
                         const values = ['#fde047', '#a3e635', '#4ade80', '#22c55e', '#16a34a', '#15803d'];
                         return values[Math.floor(Math.random() * values.length)];
                     })
                     .polygonSideColor(() => 'rgba(255, 255, 255, 0.05)')
                     .polygonStrokeColor(() => 'rgba(255, 255, 255, 0.2)')
                     .polygonLabel(d => `<b>${d.properties.NAME}</b><br/>NDVI_HEALTH_INDEX: ${(0.4 + Math.random() * 0.5).toFixed(2)} [GOOD]`);
            }
        });
};

const notifyDrawingStart = () => {
    // Show a temporary tactical alert
    console.log("DRAWING_MODE_ACTIVE: Click on globe to define vertices.");
};

const saveWatchZone = () => {
    watchZones.value.push({
        id: Date.now(),
        points: [...currentZonePoints.value],
        threat: 'LOW'
    });
    currentZonePoints.value = [];
    if (world) world.polygonsData(watchZones.value);
};

const toggleLightning = () => {
    showLightning.value = !showLightning.value;
    if (showLightning.value) {
        startLightningSimulation();
    } else {
        stopLightningSimulation();
    }
};

let lightningInterval = null;
const startLightningSimulation = () => {
    lightningInterval = setInterval(() => {
        // Random strikes for simulation
        const newStrike = {
            lat: (Math.random() - 0.5) * 160,
            lng: (Math.random() - 0.5) * 360,
            size: 0.5 + Math.random() * 1.5,
            color: '#ffffff',
            id: Date.now()
        };
        lightningData.value = [newStrike]; // Show one strike at a time for dramatic effect
        
        if (world) {
            world.pointsData(lightningData.value)
                 .pointColor(() => '#ffffff')
                 .pointAltitude(0.01)
                 .pointRadius('size');
        }

        // Decay the strike after 400ms
        setTimeout(() => {
            if (showLightning.value) {
                lightningData.value = [];
                if (world) world.pointsData([]);
            }
        }, 400);
    }, 2000);
};

const stopLightningSimulation = () => {
    clearInterval(lightningInterval);
    if (world) world.pointsData([]);
};

let world = null; // Globe.gl instance
let map = null;   // Leaflet instance
let rafId = null;
let intervalId = null;

const layers = [
    { id: 'satellites', name: 'ORBITAL_INTEL', color: 'vibrant-blue', icon: Satellite },
    { id: 'clouds', name: 'CLOUD_DENSITY', color: 'vibrant-blue', icon: Cloud },
    { id: 'storms', name: 'STORM_RADAR_PRO', color: 'red-500', icon: CloudLightning },
    { id: 'precip', name: 'PRECIPITATION', color: 'vibrant-green', icon: CloudRain },
    { id: 'wind', name: 'WIND_SPEED', color: 'yellow-500', icon: Wind },
    { id: 'lightning', name: 'LIVE_LIGHTNING', color: 'white', icon: Zap },
    { id: 'aqi', name: 'AIR_QUALITY_INDEX', color: 'purple-500', icon: Droplets },
    { id: 'sst', name: 'SEA_TEMPERATURE', color: 'orange-500', icon: Thermometer },
    { id: 'aurora', name: 'AURORA_TRACKING', color: 'green-400', icon: Sparkles },
    { id: 'risk', name: 'STRATEGIC_RISK', color: 'red-500', icon: AlertTriangle },
    { id: 'marine', name: 'MARINE_TRAFFIC', color: 'blue-400', icon: Ship },
    { id: 'ndvi', name: 'VEGETATION_NDVI', color: 'green-600', icon: Leaf },
];

const viewOptions = [
    { id: 'GLOBE', name: 'TACTICAL_GLOBE', icon: GlobeIcon },
    { id: 'SATELLITE', name: 'SAT_REALITY', icon: Satellite },
    { id: 'FLAT', name: 'SYNOPTIC_FLAT', icon: MapIcon },
];

import { watch } from 'vue';

// Watch for data changes to update the non-reactive globe.gl instance
// Watch for array replacement only (no deep watch to prevent 60fps churn)
watch(activeSatellites, (newSats) => {
    if (world && newSats.length > 0) {
        world.customLayerData([...newSats]);
        world.pathsData(newSats.map(s => s.path));
        
        // Critical labels for strategic satellites
        const strategic = newSats.filter(s => s.norad_id === '41836' || s.norad_id === '25544' || s.norad_id === '40267');
        world.labelsData([...activeStorms.value, ...strategic]);
    }
    syncLeafletMarkers();
});

watch(activeStorms, (newStorms) => {
    if (world && newStorms.length > 0) {
        world.ringsData(newStorms);
        world.pointsData(newStorms);
        // Re-sync labels to include both storms and satellites
        const strategicSats = activeSatellites.value.filter(s => s.norad_id === '41836' || s.norad_id === '25544' || s.norad_id === '40267');
        world.labelsData([...newStorms, ...strategicSats]);
    }
    syncLeafletMarkers();
}, { deep: true });

watch(showGroundStations, () => {
    syncLeafletMarkers();
});

onMounted(async () => {
    const width = globeContainer.value.offsetWidth || window.innerWidth;
    const height = globeContainer.value.offsetHeight || (window.innerHeight - 300);

    world = Globe()
        (globeContainer.value)
    window.world = world; // Expose for debugging
    
    world.width(width)
         .height(height)
        .globeImageUrl('https://unpkg.com/three-globe/example/img/earth-blue-marble.jpg')
        .bumpImageUrl('https://unpkg.com/three-globe/example/img/earth-topology.png')
        .backgroundColor('#020205'); // End chaining here

    // Ensure initial sizing is correct after a small delay to allow layout to settle
    setTimeout(() => {
        handleResize();
    }, 500);

    // Configuration Chain with Safety
    try {
        world
            .lineHoverPrecision(0)
            .polygonCapColor(() => 'rgba(0, 136, 255, 0.05)')
            .polygonSideColor(() => 'rgba(0, 136, 255, 0.02)')
            .polygonStrokeColor(() => 'rgba(255, 255, 255, 0.1)')
            .polygonLabel(({ properties: d }) => `
                <div class="bg-black/90 p-3 border border-vibrant-blue/30 backdrop-blur-xl">
                    <p class="text-[8px] font-black text-vibrant-blue uppercase mb-1">PROVINCE_INTEL</p>
                    <p class="text-xs font-black uppercase italic">${d.NAME_1 || d.NAME || 'UNKNOWN_REGION'}</p>
                </div>
            `)
            
            // --- Storms Layer ---
            .ringColor(() => '#ef4444')
            .ringMaxRadius(5)
            .ringPropagationSpeed(2)
            .ringRepeatPeriod(1000)
            .pointColor(() => '#ef4444')
            .pointAltitude(0.01)
            .pointRadius(0.5)

            // --- Satellites Layer ---
            .customLayerData([]) 
            .customThreeObject(d => {
                const isStrategic = d.norad_id === '41836' || d.norad_id === '40267' || d.norad_id === '25544'; 
                const size = isStrategic ? 1.5 : 0.8; 
                const color = isStrategic ? '#00ffff' : '#0088ff';
                const group = new THREE.Group();
                const mesh = new THREE.Mesh(
                    new THREE.BoxGeometry(size, size, size),
                    new THREE.MeshPhongMaterial({ color, emissive: color, emissiveIntensity: 0.8, transparent: true, opacity: 0.9 })
                );
                group.add(mesh);
                return group;
            })
            .customThreeObjectUpdate((obj, d) => {
                if (!d.position || !world) return;
                const { lat, lng, alt } = d.position;
                const scaledAlt = Math.min(alt, 1.0) * 0.15; 
                const coords = world.getCoords(lat, lng, scaledAlt + 0.05);
                obj.position.set(coords.x, coords.y, coords.z);
                obj.lookAt(0, 0, 0); 
            })
            .onCustomLayerClick(d => {
                selectedSatellite.value = d;
                selectedPoint.value = null; 
                world.pointOfView({ lat: d.position.lat, lng: d.position.lng, altitude: 1.5 }, 1000);
            })
            .pathsData([])
            .pathColor(() => '#00ffff')
            .pathStroke(0.2)
            .pathDashLength(0) 
            .pathPointLat(p => p[0])
            .pathPointLng(p => p[1])
            .pathPointAlt(p => (p[2] || 0) * 0.15 + 0.08) // Even higher to be sure
            .onGlobeClick(handleGlobeClick)
            .onPolygonClick(handleGlobeClick)
            .onPointClick(handleGlobeClick)
            .onLabelClick(handleGlobeClick);

        if (world.controls()) {
            world.controls().autoRotate = true;
            world.controls().autoRotateSpeed = 0.05;
        }

        world.pointOfView({ lat: 10, lng: 106, altitude: 2.5 }, 2000);

        // Fetch Administrative Boundaries
        const geoRes = await fetch('https://raw.githubusercontent.com/vasturiano/globe.gl/master/example/datasets/ne_110m_admin_0_countries.geojson');
        const countries = await geoRes.json();
        world.polygonsData(countries.features);
    } catch (err) {
        console.error("Globe config failure", err);
    }

    // Initial Fetch (vitals first for fast render)
    try {
        const token = 'vethinh_strategic_internal_token_2026';
        
        // Load storms, stations, and radar facilities immediately
        const [stormRes, stationRes, radarFacRes] = await Promise.all([
            axios.get(`/api/internal-map/storms?token=${token}`),
            axios.get(`/api/internal-map/ground-stations?token=${token}`),
            axios.get(`/api/internal-map/radar-stations?token=${token}`)
        ]);
        
        activeStorms.value = stormRes.data;
        groundStations.value = stationRes.data.data;
        radarFacilities.value = radarFacRes.data;

        // Fetch Radar metadata
        const radarRes = await axios.get('https://api.rainviewer.com/public/weather-maps.json');
        radarTimestamp.value = radarRes.data.radar.past[radarRes.data.radar.past.length - 1].time;
        
        syncGlobeLayers();

        // DECOUPLED: Fetch Satellites in background
        isSyncingSatellites.value = true;
        axios.get(`/api/internal-map/satellites?token=${token}`).then(satRes => {
            activeSatellites.value = satRes.data.data;
            syncGlobeLayers();
            isSyncingSatellites.value = false;
            console.log("SATELLITE_HYDRATION_COMPLETE");
        }).catch(err => {
            console.error("Satellite hydration failed", err);
            isSyncingSatellites.value = false;
        });

    } catch (e) {
        console.error('Failed to fetch initial tactical data', e);
    }

const syncCommsLinks = () => {
    if (!world || activeSatellites.value.length === 0 || groundStations.value.length === 0) return;

    const links = [];
    activeSatellites.value.forEach(sat => {
        // Find nearest station within 3000km
        let nearest = null;
        let minDist = Infinity;

        groundStations.value.forEach(station => {
            const dist = getDistance(sat.position.lat, sat.position.lng, station.latitude, station.longitude);
            if (dist < 3000 && dist < minDist) {
                minDist = dist;
                nearest = station;
            }
        });

        if (nearest) {
            links.push({
                startLat: sat.position.lat,
                startLng: sat.position.lng,
                endLat: nearest.latitude,
                endLng: nearest.longitude
            });
        }
    });
    world.arcsData(links);
};

// Haversine distance
const getDistance = (lat1, lon1, lat2, lon2) => {
    const R = 6371; // km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
};

watch(groundStations, () => {
    if (world) {
        world.pointsData([...activeStorms.value, ...groundStations.value.map(s => ({
            ...s,
            pointColor: '#00ff88',
            pointRadius: 0.8,
            pointAltitude: 0.02,
            isStation: true
        }))]);
    }
    syncCommsLinks();
}, { deep: true });

const handleResize = () => {
        if (!globeContainer.value) return;
        world.width(globeContainer.value.offsetWidth);
        world.height(globeContainer.value.offsetHeight);
    };

    window.addEventListener('resize', handleResize);

    // Orbital Propagator Loop
    const animate = () => {
        propagateSatellites();
        rafId = requestAnimationFrame(animate);
    };
    rafId = requestAnimationFrame(animate);
    
    // Auto-sync data every 60 seconds
    intervalId = setInterval(refreshTacticalData, 60000);
    lastFetchTime.value = Date.now();
});

import { onUnmounted } from 'vue';
onUnmounted(() => {
    cancelAnimationFrame(rafId);
    clearInterval(intervalId);
    window.removeEventListener('resize', handleResize);
});

import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const initLeaflet = () => {
    if (map) return;
    
    map = L.map(leafletContainer.value, {
        center: [10, 106],
        zoom: 3,
        zoomControl: false,
        attributionControl: false,
        backgroundColor: '#050508'
    });

    map.on('click', (e) => {
        const { lat, lng } = e.latlng;
        handleGlobeClick({ lat, lng });
    });

    // High-Detail Satellite (Esri)
    const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19
    });

    // Detailed Boundaries & Labels
    const labelLayer = L.tileLayer('https://stamen-tiles-{s}.a.ssl.fastly.net/toner-labels/{z}/{x}/{y}{r}.png', {
        opacity: 0.7
    });

    // Windy-style Topo/Dark
    const darkLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        maxZoom: 20
    });

    // Initial layer based on mode
    if (viewMode.value === 'SATELLITE') {
        satelliteLayer.addTo(map);
        labelLayer.addTo(map);
    } else {
        darkLayer.addTo(map);
    }

    // Storage for switching
    map._satelliteLayer = satelliteLayer;
    map._labelLayer = labelLayer;
    map._darkLayer = darkLayer;

    // Radar Overlay (Leaflet)
    if (radarTimestamp.value) {
        map._radarLayer = L.tileLayer(`https://tilecache.rainviewer.com/v2/radar/${radarTimestamp.value}/256/{z}/{x}/{y}/2/1_1.png`, {
            opacity: 0.6,
            zIndex: 100
        });
        if (showRadar.value) map._radarLayer.addTo(map);
    }
};

const handleSearch = async () => {
    if (!searchQuery.value) return;
    
    isSearching.value = true;
    try {
        // 1. Check for Satellite Match
        const satMatch = activeSatellites.value.find(s => 
            s.name.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
            s.norad_id.includes(searchQuery.value)
        );

        if (satMatch) {
            flyToLocation(satMatch.position.lat, satMatch.position.lng, 1.5);
            selectedSatellite.value = satMatch;
            isSearching.value = false;
            searchQuery.value = '';
            return;
        }

        // 2. Geocode Location Match (OSM Nominatim)
        const geocodeRes = await axios.get(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery.value)}`);
        if (geocodeRes.data && geocodeRes.data.length > 0) {
            const topResult = geocodeRes.data[0];
            flyToLocation(parseFloat(topResult.lat), parseFloat(topResult.lon), 2.5);
        }
    } catch (e) {
        console.error('Search failed', e);
    } finally {
        isSearching.value = false;
    }
};

const flyToLocation = (lat, lng, altitude = 2.5) => {
    if (viewMode.value === 'GLOBE' && world) {
        world.pointOfView({ lat, lng, altitude }, 2000);
    } else if (map) {
        map.flyTo([lat, lng], altitude === 1.5 ? 8 : 5);
    }
};

const toggleRadar = () => {
    showRadar.value = !showRadar.value;
    syncGlobeLayers(); // Refresh Globe points for Radar Facilities
    syncLeafletMarkers(); // Refresh Leaflet markers

    if (!map) return;
    
    if (showRadar.value && map._radarLayer) {
        map._radarLayer.addTo(map);
    } else if (map._radarLayer) {
        map.removeLayer(map._radarLayer);
    }
};

const syncLeafletMarkers = () => {
    if (!map) return;
    
    // Clear existing markers
    if (map._markersLayer) {
        map.removeLayer(map._markersLayer);
    }
    
    const markers = L.layerGroup();
    
    // Satellites
    activeSatellites.value.forEach(sat => {
        const isStrategic = sat.norad_id === '41836' || sat.norad_id === '40267' || sat.norad_id === '25544';
        const color = isStrategic ? '#00ffff' : '#0088ff';
        
        L.circleMarker([sat.position.lat, sat.position.lng], {
            radius: isStrategic ? 6 : 4,
            fillColor: color,
            color: color,
            weight: 2,
            opacity: 0.8,
            fillOpacity: 1
        }).addTo(markers).bindTooltip(sat.name);
    });
    
    // Storms
    activeStorms.value.forEach(storm => {
        L.circleMarker([storm.latitude, storm.longitude], {
            radius: 8,
            fillColor: '#ef4444',
            color: '#ffffff',
            weight: 2,
            opacity: 0.8,
            fillOpacity: 0.6
        }).addTo(markers).bindTooltip(`STORM: ${storm.name}`);
    });

    // Ground Stations
    if (showGroundStations.value) {
        groundStations.value.forEach(station => {
            L.marker([station.latitude, station.longitude], {
                icon: L.divIcon({
                    className: 'custom-station-icon',
                    html: `<div class="station-pulse"></div>`,
                    iconSize: [20, 20]
                })
            }).addTo(markers).bindTooltip(`EARTH_NODE: ${station.name}`);
        });
    }

    // Radar Facilities
    if (showRadar.value) {
        radarFacilities.value.forEach(radar => {
            L.circleMarker([radar.latitude, radar.longitude], {
                radius: 6,
                fillColor: '#facc15',
                color: '#ca8a04',
                weight: 2,
                opacity: 0.9,
                fillOpacity: 0.7
            }).addTo(markers).bindTooltip(`DOPPLER_RADAR: ${radar.name} [${radar.frequency_band}]<br/>Radius: ${radar.coverage_radius_km}km`);
        });
    }
    
    markers.addTo(map);
    map._markersLayer = markers;
};

const switchView = (mode) => {
    viewMode.value = mode;
    
    if (mode !== 'GLOBE') {
        setTimeout(() => {
            initLeaflet();
            map.invalidateSize();
            syncLeafletMarkers();
            
            // Switch layers
            map.eachLayer(l => {
                if (l !== map._markersLayer) map.removeLayer(l);
            });
            
            if (mode === 'SATELLITE') {
                map._satelliteLayer.addTo(map);
                map._labelLayer.addTo(map);
            } else {
                map._darkLayer.addTo(map);
            }
            map._markersLayer.addTo(map);
        }, 100);
    }
};
</script>

<template>
    <Head title="Professional Weather Map" />

    <UserLayout>
        <div class="h-[calc(100vh-12rem)] relative bg-[#050508] border border-white/5 overflow-hidden">
            <!-- Search Hub Overlay -->
            <div class="absolute top-8 left-1/2 -translate-x-1/2 z-50 w-full max-w-md">
                <div class="relative group">
                    <div class="absolute -inset-0.5 bg-gradient-to-r from-vibrant-blue/50 to-purple-600/50 rounded-full blur opacity-25 group-focus-within:opacity-75 transition duration-1000 group-focus-within:duration-200"></div>
                    <div class="relative flex items-center bg-black/80 backdrop-blur-2xl border border-white/10 rounded-full px-6 py-2 shadow-2xl">
                        <svg class="w-4 h-4 text-white/40 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <input 
                            v-model="searchQuery"
                            @keyup.enter="handleSearch"
                            type="text" 
                            placeholder="SEARCH_SAT_OR_COORDINATE..." 
                            class="bg-transparent border-none focus:ring-0 text-[10px] font-black tracking-widest text-white placeholder-white/20 w-full uppercase"
                        />
                        <div v-if="isSearching" class="w-3 h-3 border border-vibrant-blue border-t-transparent rounded-full animate-spin"></div>
                    </div>
                </div>
            </div>

            <!-- Sidebar Controls -->
            <div class="absolute top-8 left-8 z-10 w-64 space-y-4 pointer-events-none">
                <div class="pointer-events-auto">
                    <h2 class="text-2xl font-black uppercase tracking-tighter italic">METEO_LAYERS</h2>
                    <p class="text-[9px] text-white/30 uppercase tracking-[0.3em] mt-1">Global Atmospheric Visualization</p>
                </div>

                <!-- Imagery Constellation Selector (NEW) -->
                <div v-if="activeLayers.includes('clouds')" class="space-y-2 pointer-events-auto">
                    <p class="text-[8px] font-black text-white/40 uppercase tracking-[0.2em] mb-1">IMAGERY_CONSTELLATION</p>
                    <div class="grid grid-cols-3 gap-1">
                        <button v-for="sat in imageryConstellations" :key="sat.id"
                            @click="selectedConstellation = sat; syncGlobeLayers()"
                            :class="selectedConstellation.id === sat.id ? 'bg-vibrant-blue/30 border-vibrant-blue text-white' : 'bg-black/60 border-white/5 text-white/40'"
                            class="py-2 border text-[8px] font-black uppercase tracking-tighter hover:bg-white/5 transition-all">
                            {{ sat.region }}
                        </button>
                    </div>
                </div>

                <!-- Pro Horizontal Controls (NEW LAYOUT) -->
                <div class="flex flex-row space-x-2 pointer-events-auto">
                    <button @click="toggleRadar"
                        :class="showRadar ? 'bg-vibrant-blue text-white shadow-[0_0_15px_rgba(0,136,255,0.4)] border-vibrant-blue' : 'bg-black/60 text-white/40 border-white/5'"
                        class="flex-1 py-3 border backdrop-blur-md transition-all group relative flex justify-center items-center">
                        <span class="text-xs">📡</span>
                        <div class="absolute bottom-full mb-3 px-3 py-1 bg-black text-[8px] font-black uppercase opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none border border-white/10 z-50">LIVE_RADAR</div>
                    </button>
                    <button @click="showGroundStations = !showGroundStations"
                        :class="showGroundStations ? 'bg-[#00ff88] text-black shadow-[0_0_15px_rgba(0,255,136,0.4)] border-[#00ff88]' : 'bg-black/60 text-white/40 border-white/5'"
                        class="flex-1 py-3 border backdrop-blur-md transition-all group relative flex justify-center items-center">
                        <span class="text-xs">🗼</span>
                        <div class="absolute bottom-full mb-3 px-3 py-1 bg-black text-[8px] font-black uppercase opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none border border-white/10 z-50">GROUND_STATIONS</div>
                    </button>
                    <button @click="toggleLightning"
                        :class="showLightning ? 'bg-yellow-400 text-black shadow-[0_0_15px_rgba(250,204,21,0.4)] border-yellow-400' : 'bg-black/60 text-white/40 border-white/5'"
                        class="flex-1 py-3 border backdrop-blur-md transition-all group relative flex justify-center items-center">
                        <span class="text-xs">⚡</span>
                        <div class="absolute bottom-full mb-3 px-3 py-1 bg-black text-[8px] font-black uppercase opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none border border-white/10 z-50">LIVE_LIGHTNING</div>
                    </button>
                    <button @click="toggleDrawingMode"
                        :class="isDrawingZone ? 'bg-red-500 text-white shadow-[0_0_15px_rgba(239,68,68,0.4)] border-red-500' : 'bg-black/60 text-white/40 border-white/5'"
                        class="flex-1 py-3 border backdrop-blur-md transition-all group relative flex justify-center items-center">
                        <span class="text-xs">📐</span>
                        <div class="absolute bottom-full mb-3 px-3 py-1 bg-black text-[8px] font-black uppercase opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none border border-white/10 z-50">DEFINE_WATCH_ZONE</div>
                    </button>
                </div>

                <div class="space-y-1.5 pointer-events-auto max-h-[45vh] overflow-y-auto custom-scrollbar pr-2">
                    <button v-for="layer in layers" :key="layer.id"
                        @click="toggleLayer(layer.id)"
                        :class="activeLayers.includes(layer.id) ? 'bg-vibrant-blue/25 border-vibrant-blue/50 text-white translate-x-1 shadow-[0_0_20px_rgba(0,136,255,0.1)]' : 'bg-black/40 border-white/5 text-white/40 hover:bg-black/60 hover:translate-x-1'"
                        class="w-full text-left p-0 border transition-all flex items-stretch group relative overflow-hidden h-14">
                        
                        <!-- Tactical Color Strip -->
                        <div :class="`bg-${layer.color}`" class="w-1 h-full opacity-60"></div>
                        
                        <div class="flex items-center space-x-3 px-3 flex-1 relative z-10">
                            <div class="w-8 flex justify-center">
                                <component :is="layer.icon" class="w-5 h-5 group-hover:scale-110 transition-transform filter drop-shadow-[0_0_8px_rgba(0,0,0,0.5)]" />
                            </div>
                            <div class="flex flex-col flex-1 min-w-0">
                                <div class="flex items-center space-x-2">
                                    <span class="text-[9px] font-black tracking-[0.15em] uppercase leading-tight truncate">{{ layer.name }}</span>
                                    <!-- Syncing Indicator -->
                                    <span v-if="layer.id === 'satellites' && isSyncingSatellites" class="flex h-1.5 w-1.5 relative">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#00ffff] opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-[#00ffff]"></span>
                                    </span>
                                </div>
                                <span class="text-[7px] text-white/30 font-bold mt-1 tracking-widest">{{ activeLayers.includes(layer.id) ? (layer.id === 'satellites' && isSyncingSatellites ? 'HYDRATING_ORBITAL_DATA...' : 'STREAM_ENCRYPTED') : 'IDLE_VECTORS' }}</span>
                            </div>
                        </div>
                        
                        <!-- Status Indicator -->
                        <div class="flex items-center px-4 bg-black/20">
                            <div class="w-4 h-4 rounded-sm border border-white/10 flex items-center justify-center transition-all" 
                                 :class="activeLayers.includes(layer.id) ? 'bg-vibrant-blue border-vibrant-blue' : 'bg-white/5'">
                                <svg v-if="activeLayers.includes(layer.id)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                        </div>
                    </button>
                </div>

                <!-- Legend -->
                <div class="bg-black/60 backdrop-blur-md border border-white/5 p-4 pointer-events-auto">
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
                <div v-if="selectedSatellite" class="absolute top-8 right-8 z-[60] w-85 bg-black/90 backdrop-blur-2xl border border-vibrant-blue/50 shadow-[0_0_50px_rgba(0,136,255,0.2)] overflow-hidden flex flex-col max-h-[75vh]">
                    <!-- Target Lock Decoration -->
                    <div class="target-lock animate-target-scan"></div>
                    
                    <div class="p-5 border-b border-vibrant-blue/20 bg-vibrant-blue/10 flex justify-between items-center relative z-10">
                        <div>
                            <p class="text-[8px] font-black text-vibrant-blue uppercase tracking-[0.4em] mb-1">Satellite_Intel</p>
                            <h3 class="text-lg font-black uppercase tracking-tighter italic leading-none">{{ selectedSatellite.name }}</h3>
                        </div>
                        <button @click="selectedSatellite = null" class="text-white/40 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto custom-scrollbar p-6 space-y-6">
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

                        <!-- Vantage Viewport (NEW) -->
                        <div v-if="telemetryData && telemetryData.metadata.can_image" class="space-y-3">
                            <h4 class="text-[10px] font-black text-vibrant-blue uppercase tracking-widest border-l-2 border-vibrant-blue pl-3">Vantage_Capture_Stream</h4>
                            <div class="relative aspect-video bg-black border border-vibrant-blue/30 overflow-hidden group">
                                <!-- Live Image -->
                                <img :src="telemetryData.visual.vantage_capture" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" alt="Nadir View" />
                                
                                <!-- Tactical Overlays -->
                                <div class="absolute inset-0 pointer-events-none">
                                    <div class="absolute inset-0 border-[20px] border-black/20"></div>
                                    <!-- Scanline -->
                                    <div class="absolute top-0 left-0 w-full h-1 bg-vibrant-blue/20 animate-scanline"></div>
                                    <!-- Crosshair -->
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <div class="w-12 h-12 border border-vibrant-blue/50 rounded-full flex items-center justify-center">
                                            <div class="w-full h-[1px] bg-vibrant-blue/30"></div>
                                            <div class="h-full w-[1px] bg-vibrant-blue/30 absolute"></div>
                                        </div>
                                    </div>
                                    <!-- FOV Marker -->
                                    <div class="absolute bottom-2 left-2 text-[6px] font-mono text-vibrant-blue bg-black/60 px-1">
                                        FOV: {{ telemetryData.visual.fov_deg }}°_TARGET_LOCKED
                                    </div>
                                    <div class="absolute top-2 right-2 flex space-x-1">
                                        <div class="w-1 h-1 bg-vibrant-red animate-pulse"></div>
                                        <span class="text-[6px] font-black text-vibrant-red">REC_LIVE</span>
                                    </div>
                                </div>
                                
                                <!-- Zoom Controls Simulation -->
                                <div class="absolute bottom-2 right-2 flex flex-col space-y-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button class="bg-vibrant-blue/20 hover:bg-vibrant-blue/40 text-[8px] p-1 border border-vibrant-blue/30 text-white">ZOOM+</button>
                                    <button class="bg-vibrant-blue/20 hover:bg-vibrant-blue/40 text-[8px] p-1 border border-vibrant-blue/30 text-white">ZOOM-</button>
                                </div>
                            </div>
                        </div>

                        <!-- Live Telemetry -->
                        <div class="space-y-4">
                            <h4 class="text-[10px] font-black text-white/40 uppercase tracking-widest border-l-2 border-vibrant-blue pl-3">Live_Telemetry_Stream</h4>
                            
                            <div class="space-y-3">
                                <div class="flex justify-between items-end border-b border-white/5 pb-2">
                                    <span class="text-[9px] text-white/30 font-bold uppercase">Altitude</span>
                                    <div class="flex items-center space-x-3">
                                        <!-- Micro Chart -->
                                        <svg class="w-12 h-4 text-vibrant-blue opacity-50" viewBox="0 0 100 20">
                                            <polyline fill="none" stroke="currentColor" stroke-width="2" points="0,15 20,10 40,12 60,5 80,8 100,2" />
                                        </svg>
                                        <span class="text-sm font-black text-white">
                                            {{ telemetryData?.orbital?.coordinates?.alt?.toLocaleString() || selectedSatellite.telemetry.altitude.toLocaleString() }} 
                                            <span class="text-[9px] text-white/20">KM</span>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex justify-between items-end border-b border-white/5 pb-2">
                                    <span class="text-[9px] text-white/30 font-bold uppercase">Velocity</span>
                                    <div class="flex items-center space-x-3">
                                        <!-- Micro Chart -->
                                        <svg class="w-12 h-4 text-vibrant-green opacity-50" viewBox="0 0 100 20">
                                            <polyline fill="none" stroke="currentColor" stroke-width="2" points="0,5 20,8 40,4 60,10 80,6 100,5" />
                                        </svg>
                                        <span class="text-sm font-black text-vibrant-green">
                                            {{ telemetryData?.orbital?.physics?.velocity_kms?.toFixed(3) || selectedSatellite.telemetry.velocity.toFixed(3) }} 
                                            <span class="text-[9px] text-white/20">KM/S</span>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex justify-between items-end border-b border-white/5 pb-2">
                                    <span class="text-[9px] text-white/30 font-bold uppercase">Orbit_Period</span>
                                    <span class="text-sm font-black text-yellow-500">
                                        {{ telemetryData?.orbital?.physics?.period_min?.toFixed(2) || selectedSatellite.telemetry.period.toFixed(2) }} 
                                        <span class="text-[9px] text-white/20">MIN</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Advanced Orbital Physics -->
                        <div v-if="telemetryData" class="space-y-4">
                            <h4 class="text-[10px] font-black text-vibrant-blue uppercase tracking-widest border-l-2 border-vibrant-blue pl-3">Orbital_Dynamics</h4>
                            <div class="grid grid-cols-2 gap-3 bg-white/5 p-4 border border-white/10">
                                <div class="space-y-1">
                                    <p class="text-[7px] text-white/30 font-black uppercase">Heading</p>
                                    <p class="text-xs font-black text-white italic">{{ telemetryData.intel.heading_deg }}° <span class="text-[8px] text-white/20">TRUE</span></p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-[7px] text-white/30 font-black uppercase">BSTAR_Drag</p>
                                    <p class="text-xs font-black text-white italic">{{ telemetryData.orbital.physics.bstar_drag }}</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-[7px] text-white/30 font-black uppercase">Period</p>
                                    <p class="text-xs font-black text-white">{{ telemetryData.orbital.physics.period_min }}m</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-[7px] text-white/30 font-black uppercase">Velocity</p>
                                    <p class="text-xs font-black text-vibrant-green italic">{{ telemetryData.orbital.physics.velocity_kms }} km/s</p>
                                </div>
                            </div>
                        </div>

                        <!-- Tactical Intelligence -->
                        <div v-if="telemetryData" class="space-y-4">
                            <h4 class="text-[10px] font-black text-vibrant-blue uppercase tracking-widest border-l-2 border-vibrant-blue pl-3">Tactical_Intelligence</h4>
                            
                            <div class="space-y-4">
                                <!-- Footprint -->
                                <div class="bg-black/40 p-3 border border-vibrant-blue/20 flex justify-between items-center">
                                    <div>
                                        <p class="text-[7px] text-white/30 font-black uppercase mb-1">Coverage_Footprint</p>
                                        <p class="text-xs font-black text-white">{{ telemetryData.intel.footprint_radius_km.toLocaleString() }} <span class="text-[8px] text-white/40">KM RAD</span></p>
                                    </div>
                                    <div class="w-10 h-10 border border-vibrant-blue/30 rounded-full flex items-center justify-center">
                                        <div class="w-2 h-2 bg-vibrant-blue rounded-full animate-ping"></div>
                                    </div>
                                </div>

                                <!-- RF Link Specs -->
                                <div class="bg-white/5 p-3 border border-white/10 space-y-3">
                                    <div class="flex justify-between items-center">
                                        <p class="text-[7px] text-white/30 font-black uppercase">Slant_Range_to_VN</p>
                                        <p class="text-xs font-black text-white italic font-mono">{{ (telemetryData.intel.link_specs.slant_range_km / 1000).toFixed(2) }}k KM</p>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <p class="text-[7px] text-white/30 font-black uppercase">Doppler_Shift</p>
                                        <p class="text-xs font-black text-vibrant-green italic font-mono">{{ telemetryData.intel.link_specs.doppler_shift_hz }} Hz</p>
                                    </div>
                                    <div class="flex justify-between items-center border-t border-white/5 pt-2">
                                        <p class="text-[7px] text-white/30 font-black uppercase">VN_GS_Visibility</p>
                                        <span :class="telemetryData.intel.link_specs.is_visible_to_hanoi ? 'text-vibrant-green' : 'text-red-500'" class="text-[9px] font-black">
                                            {{ telemetryData.intel.link_specs.is_visible_to_hanoi ? '● IN_LOCK' : '○ NO_LINE_OF_SIGHT' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Scientific Metrics -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="bg-white/5 p-3 rounded-sm border border-white/10">
                                        <p class="text-[7px] text-white/30 font-black uppercase mb-1">Magnetic_Field</p>
                                        <p class="text-xs font-black text-pink-500">{{ telemetryData.intel.magnetic_field.field_strength_nt }} nT</p>
                                    </div>
                                    <div class="bg-white/5 p-3 rounded-sm border border-white/10 text-right">
                                        <p class="text-[7px] text-white/30 font-black uppercase mb-1">Solar_Elv</p>
                                        <p class="text-xs font-black text-yellow-500">{{ telemetryData.intel.solar.sun_elevation_deg }}°</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- System Health Vector -->
                        <div v-if="telemetryData" class="space-y-4 bg-black/40 p-4 border border-vibrant-blue/20">
                            <h4 class="text-[10px] font-black text-white/40 uppercase tracking-widest">Health_Vector</h4>
                            <div class="grid grid-cols-2 gap-y-2 gap-x-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-[8px] text-white/20">PWR</span>
                                    <span class="text-[10px] font-black text-vibrant-green">{{ telemetryData.subsystems.power_bus }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-[8px] text-white/20">LINK</span>
                                    <span class="text-[10px] font-black text-vibrant-blue">{{ telemetryData.subsystems.comm_link }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-[8px] text-white/20">THERM</span>
                                    <span class="text-[10px] font-black text-white">{{ telemetryData.subsystems.thermal }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-[8px] text-white/20">ATTIT</span>
                                    <span class="text-[10px] font-black text-white/50">STABLE</span>
                                </div>
                            </div>
                        </div>

                        <!-- Precise Location Vector -->
                        <div class="space-y-3">
                            <h4 class="text-[10px] font-black text-white/40 uppercase tracking-widest border-l-2 border-vibrant-blue pl-3">Ground_Impact_Intelligence</h4>
                            <div class="p-4 bg-vibrant-blue/5 border border-vibrant-blue/20 rounded-lg">
                                <div class="flex items-center space-x-3 mb-3">
                                    <span class="text-xl">📍</span>
                                    <div>
                                        <p class="text-[8px] font-black text-vibrant-blue uppercase tracking-widest">Currently_Overflying</p>
                                        <p class="text-xs font-black text-white uppercase italic tracking-tighter">
                                            {{ telemetryData?.metadata?.location || telemetryData?.loc || selectedSatellite.location || 'Retrieving_Zone_Intel...' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex justify-between font-mono text-[9px] font-bold text-white/40 border-t border-white/5 pt-2">
                                    <div>LAT: {{ (telemetryData?.orbital?.coordinates?.lat || selectedSatellite.position.lat).toFixed(6) }}</div>
                                    <div>LNG: {{ (telemetryData?.orbital?.coordinates?.lng || selectedSatellite.position.lng).toFixed(6) }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Hardware Modules -->
                        <div class="space-y-4">
                            <h4 class="text-[10px] font-black text-white/40 uppercase tracking-widest border-l-2 border-vibrant-blue pl-3">Orbital_Module_Status</h4>
                            <div class="grid grid-cols-1 gap-2">
                                <div v-for="mod in (selectedSatellite.modules || [])" :key="mod.id" 
                                    class="flex items-center justify-between p-3 bg-white/[0.03] border border-white/5 hover:bg-white/[0.05] transition-colors">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-1.5 h-1.5 rounded-full" :class="mod.status === 'OPERATIONAL' || mod.status === 'ONLINE' ? 'bg-vibrant-green shadow-[0_0_8px_#22c55e]' : 'bg-red-500'"></div>
                                        <span class="text-[9px] font-black text-white/80 uppercase">{{ mod.name }}</span>
                                    </div>
                                    <span class="text-[7px] font-mono text-white/20">{{ mod.id }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Hardware Specifications -->
                        <div class="space-y-4">
                            <h4 class="text-[10px] font-black text-white/40 uppercase tracking-widest border-l-2 border-vibrant-blue pl-3">Technical_Specifications</h4>
                            <div class="grid grid-cols-2 gap-3 text-[9px]">
                                <div v-for="(val, key) in (selectedSatellite.specs || {})" :key="key" class="p-3 bg-black/40 border border-white/5">
                                    <p class="text-[7px] font-black text-white/20 uppercase mb-1">{{ key.replace('_', ' ') }}</p>
                                    <p class="font-black text-vibrant-blue">{{ val }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 space-y-3">
                            <button @click="togglePOV" 
                                :class="isPOVMode ? 'bg-vibrant-blue text-white shadow-[0_0_30px_#0088ff]' : 'bg-white text-black hover:bg-vibrant-blue hover:text-white'"
                                class="w-full py-4 text-[10px] font-black uppercase tracking-[0.3em] transition-all flex items-center justify-center space-x-3">
                                <span class="text-lg">👁️</span>
                                <span>{{ isPOVMode ? 'EXIT_ORBITAL_POV' : 'ENTER_ORBITAL_POV' }}</span>
                            </button>
                            <button @click="toggleSensorPayload" 
                                :class="showSensorPayload ? 'bg-vibrant-blue text-white shadow-[0_0_30px_#0088ff]' : 'bg-white/5 text-white/40 hover:bg-white/10'"
                                class="w-full py-3 text-[9px] font-black uppercase tracking-[0.3em] transition-all border border-white/5">
                                {{ showSensorPayload ? 'CLOSE_SENSOR_PAYLOAD' : 'ACCESS_SENSOR_PAYLOAD' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- Sensor Payload HUD -->
            <Transition
                enter-active-class="transition duration-500 ease-out"
                enter-from-class="translate-y-10 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-300 ease-in"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="translate-y-10 opacity-0"
            >
                <div v-if="selectedSatellite && showSensorPayload" 
                    class="absolute bottom-32 right-8 z-[70] w-85 bg-black/95 backdrop-blur-3xl border border-emerald-500/30 shadow-[0_0_50px_rgba(16,185,129,0.1)] overflow-hidden flex flex-col">
                    
                    <!-- Header -->
                    <div class="px-4 py-2 border-b border-white/10 flex justify-between items-center bg-emerald-500/10">
                        <div class="flex items-center space-x-2">
                            <div class="w-1.5 h-1.5 bg-emerald-500 animate-pulse rounded-full"></div>
                            <h3 class="text-[9px] font-black uppercase tracking-[0.2em] text-emerald-400">SENSOR_PAYLOAD_DECODING</h3>
                        </div>
                        <button @click="showSensorPayload = false" class="text-white/20 hover:text-white transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>

                    <div class="p-4 space-y-4 max-h-[50vh] overflow-y-auto custom-scrollbar">
                        <!-- Atmosphere Vector -->
                        <div class="space-y-2">
                            <div class="flex justify-between items-end border-b border-white/5 pb-1">
                                <h4 class="text-[8px] font-black text-white/40 uppercase tracking-widest">Atmosphere_Vector</h4>
                                <span class="text-[7px] font-mono text-emerald-500 italic">REALTIME_STREAM</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div class="bg-white/[0.02] p-2 border border-white/5">
                                    <p class="text-[7px] text-white/20 uppercase font-black">Temp</p>
                                    <p class="text-xs font-mono font-black text-white italic font-black uppercase text-emerald-500 italic">{{ (telemetryData?.scientific?.atmosphere?.temperature || 0).toFixed(1) }}°C</p>
                                </div>
                                <div class="bg-white/[0.02] p-2 border border-white/5">
                                    <p class="text-[7px] text-white/20 uppercase font-black">Pressure</p>
                                    <p class="text-xs font-mono font-black text-white italic">{{ (telemetryData?.scientific?.atmosphere?.pressure || 0).toFixed(1) }} hPa</p>
                                </div>
                                <div class="bg-white/[0.02] p-2 border border-white/5">
                                    <p class="text-[7px] text-white/20 uppercase font-black">Humidity</p>
                                    <p class="text-xs font-mono font-black text-white italic">{{ (telemetryData?.scientific?.atmosphere?.humidity || 0).toFixed(1) }}%</p>
                                </div>
                                <div class="bg-white/[0.02] p-2 border border-white/5">
                                    <p class="text-[7px] text-white/20 uppercase font-black">Solar_Flux</p>
                                    <p class="text-xs font-mono font-black text-white italic">{{ (telemetryData?.scientific?.atmosphere?.solar_flux || 0).toFixed(0) }} W/m²</p>
                                </div>
                            </div>
                        </div>

                        <!-- Strategic Intelligence -->
                        <div class="space-y-2">
                            <div class="flex justify-between items-end border-b border-white/5 pb-1">
                                <h4 class="text-[8px] font-black text-white/40 uppercase tracking-widest">Strategic_Intelligence</h4>
                                <span class="text-[7px] font-mono text-blue-400 italic font-black">DECODED</span>
                            </div>
                            <div class="space-y-2">
                                <div class="bg-white/[0.02] p-3 border border-white/5 flex justify-between items-center">
                                    <div>
                                        <p class="text-[7px] text-white/20 uppercase font-black">Magnetic_Field</p>
                                        <p class="text-[10px] font-black text-blue-400 uppercase tracking-tighter">{{ telemetryData?.intel?.magnetic_field?.model || 'Scanning...' }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs font-mono font-black text-white">{{ telemetryData?.intel?.magnetic_field?.field_strength_nt || '---' }}</p>
                                        <p class="text-[6px] text-white/20 font-black uppercase tracking-widest">NANO_TESLA</p>
                                    </div>
                                </div>
                                
                                <div class="bg-white/[0.02] p-3 border border-white/5 flex justify-between items-center">
                                    <div>
                                        <p class="text-[7px] text-white/20 uppercase font-black">Solar_Vantage</p>
                                        <p class="text-xs font-black text-orange-400 italic">Daylight_Exposure</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs font-mono font-black text-white">{{ telemetryData?.intel?.solar?.sun_elevation_deg || '---' }}°</p>
                                        <p class="text-[6px] text-white/20 font-black uppercase tracking-widest">ELEVATION_VECTOR</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Meta -->
                        <div class="pt-2 border-t border-emerald-500/20 flex justify-between items-center opacity-50">
                            <div class="text-[7px] font-mono font-black text-emerald-400 uppercase tracking-widest">Payload_ID: {{ selectedSatellite.norad_id }}</div>
                            <div class="text-[7px] font-mono text-white/30 italic">{{ telemetryData?.metadata?.timestamp || 'SYNCING' }}</div>
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

                        <div v-if="pointData.ai_analysis" class="space-y-4 pt-4 border-t border-white/10">
                            <h4 class="text-[10px] font-black text-white/40 uppercase tracking-widest border-l-2 border-purple-500 pl-3">AI_Atmospheric_Analysis</h4>
                            <div class="grid grid-cols-1 gap-3">
                                <div class="p-4 bg-purple-500/5 border border-purple-500/10 rounded-lg">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-[9px] font-black text-purple-400">VOLUMETRIC_CLOUD_DEPTH</span>
                                        <span class="text-xs font-mono font-bold">{{ pointData.ai_analysis.cloud_depth.toFixed(1) }} KM</span>
                                    </div>
                                    <div class="h-1.5 w-full bg-white/5 rounded-full overflow-hidden">
                                        <div class="h-full bg-purple-500 shadow-[0_0_10px_#a855f7]" :style="{ width: pointData.ai_analysis.cloud_depth * 2 + '%' }"></div>
                                    </div>
                                </div>
                                <div class="p-4 bg-orange-500/5 border border-orange-500/10 rounded-lg">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-[9px] font-black text-orange-400">CYCLONE_GENESIS_PROB</span>
                                        <span class="text-xs font-mono font-bold">{{ pointData.ai_analysis.cyclone_genesis.toFixed(1) }}%</span>
                                    </div>
                                    <div class="h-1.5 w-full bg-white/5 rounded-full overflow-hidden">
                                        <div class="h-full bg-orange-500 shadow-[0_0_10px_#f97316]" :style="{ width: pointData.ai_analysis.cyclone_genesis + '%' }"></div>
                                    </div>
                                </div>
                                <div v-if="pointData.ai_analysis.anomaly_detected" class="p-3 bg-red-500/10 border border-red-500/30 rounded-lg animate-pulse text-center">
                                    <p class="text-[8px] font-black text-red-500 uppercase tracking-widest">⚠️ STRATEGIC_ANOMALY_DETECTED</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex justify-between items-center text-[9px] font-black uppercase tracking-widest">
                                <span class="text-white/40">Cloud_Density</span>
                                <span class="text-vibrant-blue">{{ pointData.cloud_density.toFixed(0) }}%</span>
                            </div>
                            <div class="h-1 bg-white/5 rounded-full overflow-hidden">
                                <div class="h-full bg-vibrant-blue shadow-[0_0_10px_#0088ff]" :style="{ width: pointData.cloud_density + '%' }"></div>
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
            
            <!-- Windy-Style Meteogram Panel (Bottom Slide) -->
            <Transition
                enter-active-class="transition duration-700 ease-[cubic-bezier(0.23,1,0.32,1)]"
                enter-from-class="translate-y-full"
                enter-to-class="translate-y-0"
                leave-active-class="transition duration-500 ease-in"
                leave-from-class="translate-y-0"
                leave-to-class="translate-y-full"
            >
                <div v-if="showBottomForecast" class="absolute bottom-0 left-0 right-0 z-50 bg-[#050508]/95 backdrop-blur-3xl border-t border-white/10 h-72 shadow-[0_-20px_50px_rgba(0,0,0,0.5)] overflow-hidden">
                    <!-- Panel Header -->
                    <div class="px-8 py-3 bg-white/[0.02] border-b border-white/5 flex justify-between items-center">
                        <div class="flex items-center space-x-6">
                            <h4 class="text-[10px] font-black uppercase tracking-[0.3em] text-vibrant-blue">Atmospheric_Pulse_Forecaster</h4>
                            <div class="h-4 w-px bg-white/10"></div>
                            
                            <!-- Model Selection -->
                            <div class="flex bg-black/40 border border-white/10 p-1 rounded-sm space-x-1">
                                <button v-for="m in ['ECMWF', 'GFS', 'COMPARE']" :key="m"
                                    @click="modelMode = m"
                                    :class="modelMode === m ? 'bg-vibrant-blue text-white' : 'text-white/40 hover:text-white'"
                                    class="px-3 py-1 text-[8px] font-black uppercase tracking-widest transition-all">
                                    {{ m }}
                                </button>
                            </div>

                            <p class="text-[9px] font-bold text-white/40 uppercase">Coordinate_Locked: {{ selectedPoint?.lat.toFixed(2) }}°N, {{ selectedPoint?.lng.toFixed(2) }}°E</p>
                        </div>
                        <button @click="showBottomForecast = false" class="text-white/20 hover:text-white transition-colors">
                            <span class="text-[10px] uppercase font-black tracking-widest mr-2">Close_System</span>
                            <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>

                    <!-- Forecast Scroll Container -->
                    <div class="h-full overflow-x-auto overflow-y-hidden custom-scrollbar">
                        <div class="flex p-8 min-w-max h-full">
                            <div v-for="(hour, idx) in forecastData" :key="idx" class="w-24 flex flex-col items-center justify-between h-40 border-r border-white/5 px-2 group hover:bg-white/[0.02] transition-colors">
                                <!-- Day/Time -->
                                <div class="text-center">
                                    <p class="text-[8px] font-black text-white/30 uppercase">{{ hour.day }}</p>
                                    <p class="text-[10px] font-black text-white italic group-hover:text-vibrant-blue">{{ hour.display_short }}</p>
                                </div>

                                <!-- Dynamic Icon Placeholder -->
                                <div class="my-2">
                                    <Cloud v-if="hour.cloud_cover > 70" class="w-6 h-6 text-white/60" />
                                    <CloudRain v-else-if="hour.precip > 0.5" class="w-6 h-6 text-vibrant-blue" />
                                    <Sun v-else class="w-6 h-6 text-yellow-400" />
                                </div>

                                 <!-- Temp Metric -->
                                <div class="text-center w-full px-2">
                                    <div class="text-lg font-black italic" :class="modelMode === 'COMPARE' ? 'text-[10px]' : ''">
                                        <template v-if="modelMode !== 'COMPARE'">{{ hour.temp }}°</template>
                                        <template v-else>
                                            <span class="text-vibrant-blue">{{ hour.temp }}°</span> / 
                                            <span class="text-orange-400">{{ (hour.temp + (Math.random() * 4 - 2)).toFixed(1) }}°</span>
                                        </template>
                                    </div>
                                    <!-- Comparison Bars -->
                                    <div class="h-12 w-full flex items-end justify-center space-x-1 mt-2">
                                        <div class="w-1.5 h-full bg-white/5 rounded-full relative overflow-hidden">
                                            <div class="absolute bottom-0 w-full bg-vibrant-blue" :style="{ height: (hour.temp + 10) * 2 + '%' }"></div>
                                        </div>
                                        <div v-if="modelMode === 'COMPARE'" class="w-1.5 h-full bg-white/5 rounded-full relative overflow-hidden">
                                            <div class="absolute bottom-0 w-full bg-orange-400 opacity-60" :style="{ height: (hour.temp + 12) * 2 + '%' }"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Wind/Rain -->
                                <div class="mt-4 flex flex-col items-center space-y-1">
                                    <div class="flex items-center space-x-1">
                                        <svg class="w-3 h-3 text-vibrant-green group-hover:animate-spin" viewBox="0 0 24 24" :style="{ transform: `rotate(${hour.wind_deg}deg)` }">
                                            <path fill="currentColor" d="M12 2L4.5 20.29l.71.71L12 18l6.79 3 .71-.71L12 2z"/>
                                        </svg>
                                        <span class="text-[8px] font-black text-white/50">{{ hour.wind_speed }}</span>
                                    </div>
                                    <div v-if="hour.precip > 0" class="text-[8px] font-black text-vibrant-blue">{{ hour.precip }}mm</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline Scrubber Layer -->
                    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex items-center bg-black/40 backdrop-blur-md px-4 py-2 border border-white/10 rounded-full space-x-4">
                        <div @click="isLive = true" :class="isLive ? 'bg-vibrant-blue text-white' : 'text-white/40 ring-1 ring-white/10'" class="px-3 py-1 text-[8px] font-black uppercase cursor-pointer transition-all">
                            LIVE_STREAM
                        </div>
                        <input type="range" min="-48" max="0" step="1" 
                            @input="e => { playbackTime = Date.now() + e.target.value * 3600000; isLive = false; }"
                            class="w-64 accent-vibrant-blue bg-white/10 h-1 rounded-full cursor-pointer">
                        
                        <!-- Speed Multiplier -->
                        <div class="flex items-center bg-white/5 rounded-full p-1 border border-white/10">
                            <button v-for="m in [1, 10, 50, 100]" :key="m" 
                                @click="timeMultiplier = m"
                                :class="timeMultiplier === m ? 'bg-white text-black' : 'text-white/40 hover:text-white'"
                                class="px-2 py-0.5 text-[7px] font-black rounded-full transition-all">
                                x{{ m }}
                            </button>
                        </div>

                        <span class="text-[8px] font-black text-white italic min-w-[120px]">{{ isLive ? 'REALTIME' : new Date(playbackTime).toLocaleString() }}</span>
                    </div>
                </div>
            </Transition>

            <!-- View Mode Switcher -->
            <div class="absolute bottom-8 right-8 z-40 flex space-x-2">
                <button v-for="mode in viewOptions" :key="mode.id"
                    @click="switchView(mode.id)"
                    :class="viewMode === mode.id ? 'bg-vibrant-blue border-vibrant-blue text-white shadow-[0_0_20px_rgba(0,136,255,0.4)]' : 'bg-black/60 border-white/10 text-white/40 hover:bg-black/80 hover:border-white/20'"
                    class="px-4 py-3 border backdrop-blur-md transition-all flex items-center space-x-3 group">
                    <component :is="mode.icon" class="w-4 h-4" />
                    <span class="text-[9px] font-black tracking-widest uppercase hidden lg:block">{{ mode.name }}</span>
                </button>
            </div>

            <!-- Bottom Left Info -->
            <div class="absolute bottom-8 left-8 z-10 p-4 border-l border-white/20 bg-black/40 backdrop-blur-sm">
                <p class="text-[10px] font-black uppercase italic text-vibrant-blue">ACTIVE_VIEW: {{ viewMode }}</p>
                <p class="text-[8px] text-white/40 font-mono mt-1 uppercase">Resolving spatial vectors @ {{ viewMode === 'GLOBE' ? '2.5km/px' : '0.5km/px' }}</p>
            </div>

            <!-- Globe Container (3D) -->
            <div v-show="viewMode === 'GLOBE'" ref="globeContainer" class="absolute inset-0 cursor-grab active:cursor-grabbing z-0"></div>
            
            <!-- Leaflet Container (2D/Satellite) -->
            <div v-show="viewMode !== 'GLOBE'" ref="leafletContainer" class="absolute inset-0 bg-[#050508] z-0"></div>
                        <!-- HUD Overlay Decoration -->
            <div class="absolute inset-0 pointer-events-none border-[15px] border-black/5 z-20"></div>

            <!-- Drawing Mode HUD -->
            <div v-if="isDrawingZone" class="absolute top-32 left-1/2 -translate-x-1/2 z-[60] bg-red-600/90 text-white px-6 py-2 rounded-full border border-white/20 shadow-2xl animate-pulse flex items-center space-x-4">
                <span class="text-[10px] font-black uppercase tracking-[0.3em]">Surveillance_Drawing_Active_Mode</span>
                <div class="h-4 w-px bg-white/20"></div>
                <span class="text-[9px] font-bold uppercase italic">Points: {{ currentZonePoints.length }} (Min 3 to save)</span>
            </div>

            <!-- Playback Alert Overlay -->
            <div v-if="!isLive" class="absolute top-8 left-1/2 -translate-x-1/2 z-[60] bg-orange-500/90 text-white px-8 py-3 rounded-lg border border-white/20 shadow-2xl flex flex-col items-center">
                <span class="text-[10px] font-black uppercase tracking-[0.4em] mb-1">DATA_PLAYBACK_MODE</span>
                <span class="text-[12px] font-mono font-bold">{{ new Date(playbackTime).toLocaleString() }}</span>
                <button @click="isLive = true" class="mt-2 text-[8px] font-black underline uppercase tracking-widest hover:text-white/80 transition-colors">Return_to_Live</button>
            </div>
        </div>
    </UserLayout>
</template>

<style scoped>
.custom-station-icon {
    display: flex;
    align-items: center;
    justify-content: center;
}

.station-pulse {
    width: 8px;
    height: 8px;
    background: #00ff88;
    border-radius: 50%;
    box-shadow: 0 0 10px #00ff88;
    position: relative;
}

.station-pulse::after {
    content: '';
    position: absolute;
    width: 24px;
    height: 24px;
    border: 2px solid #00ff88;
    border-radius: 50%;
    animation: station-pulse 2s infinite;
    opacity: 0;
    left: -8px;
    top: -8px;
}

@keyframes station-pulse {
    0% { transform: scale(0.5); opacity: 0.8; }
    100% { transform: scale(2.5); opacity: 0; }
}

/* Target Lock Effect */
.target-lock {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 100px;
    height: 100px;
    transform: translate(-50%, -50%);
    pointer-events: none;
    border: 2px solid rgba(0, 255, 255, 0.5);
    border-radius: 50%;
}

.target-lock::before, .target-lock::after {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    border-color: #00ffff;
    border-style: solid;
}

.target-lock::before {
    top: -5px;
    left: -5px;
    border-width: 3px 0 0 3px;
}

.target-lock::after {
    bottom: -5px;
    right: -5px;
    border-width: 0 3px 3px 0;
}

.animate-target-scan {
    animation: target-scan 1s ease-out;
}

@keyframes target-scan {
    0% { transform: translate(-50%, -50%) scale(2); opacity: 0; }
    70% { transform: translate(-50%, -50%) scale(0.9); opacity: 1; }
    100% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
}

/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
    height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.2);
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(0, 136, 255, 0.3);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 136, 255, 0.5);
}
</style>

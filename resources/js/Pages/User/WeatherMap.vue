<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import Globe from 'globe.gl';
import * as THREE from 'three';
import axios from 'axios';

const globeContainer = ref(null);
const leafletContainer = ref(null);
const viewMode = ref('GLOBE'); // GLOBE, SATELLITE, FLAT
const activeLayer = ref('clouds');
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
    
    activeSatellites.value = activeSatellites.value.map(sat => {
        if (!sat.path || sat.path.length < 2) return sat;
        
        // Simple linear interpolation along the path
        const path = sat.path;
        const totalPoints = path.length;
        const speed = 0.05; // Orbital speed factor
        
        orbitTick.value += speed / 1000;
        const index = Math.floor(Date.now() / 2000) % totalPoints;
        const nextIndex = (index + 1) % totalPoints;
        
        const currentPos = path[index];
        const nextPos = path[nextIndex];
        
        // Progress between the two points (0 to 1)
        const progress = (Date.now() % 2000) / 2000;
        
        const nextLat = currentPos[0] + (nextPos[0] - currentPos[0]) * progress;
        const nextLng = currentPos[1] + (nextPos[1] - currentPos[1]) * progress;

        // If this is the selected satellite and POV is active, update camera
        if (isPOVMode.value && selectedSatellite.value && selectedSatellite.value.norad_id === sat.norad_id && world) {
            world.pointOfView({
                lat: nextLat,
                lng: nextLng,
                altitude: 0.4 // Dynamic following altitude
            }, 100); // Small duration for smooth chasing
        }

        return {
            ...sat,
            position: {
                lat: nextLat,
                lng: nextLng,
                alt: currentPos[2] || 0.1
            }
        };
    });

    if (world) {
        world.customLayerData(activeSatellites.value);
        syncCommsLinks(); // Update ground station lines
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
        pointData.value = response.data.data;
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
const radarTimestamp = ref(null);
const showRadar = ref(false);
const showGroundStations = ref(true);
const showLightning = ref(false);
const lightningData = ref([]);

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

const layers = [
    { id: 'clouds', name: 'CLOUD_DENSITY', color: 'vibrant-blue' },
    { id: 'precip', name: 'PRECIPITATION', color: 'vibrant-green' },
    { id: 'wind', name: 'WIND_SPEED', color: 'yellow-500' },
];

const viewOptions = [
    { id: 'GLOBE', name: 'TACTICAL_GLOBE', icon: '🌐' },
    { id: 'SATELLITE', name: 'SAT_REALITY', icon: '🛰️' },
    { id: 'FLAT', name: 'SYNOPTIC_FLAT', icon: '🗺️' },
];

import { watch } from 'vue';

// Watch for data changes to update the non-reactive globe.gl instance
watch(activeSatellites, (newSats) => {
    if (world && newSats.length > 0) {
        console.log(`Syncing ${newSats.length} satellites to globe`);
        world.customLayerData(newSats);
        world.pathsData(newSats.map(s => s.path));
        
        // Critical labels for strategic satellites
        const strategic = newSats.filter(s => s.norad_id === '41836' || s.norad_id === '25544' || s.norad_id === '40267');
        world.labelsData([...activeStorms.value, ...strategic]);
    }
    syncLeafletMarkers();
}, { deep: true });

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
    const width = globeContainer.value.offsetWidth;
    const height = globeContainer.value.offsetHeight;

    world = Globe()
        (globeContainer.value)
        .width(width)
        .height(height)
        .globeImageUrl('https://unpkg.com/three-globe/example/img/earth-blue-marble.jpg')
        .bumpImageUrl('https://unpkg.com/three-globe/example/img/earth-topology.png')
        .backgroundColor('#020205')
        
        // --- Boundaries Layer ---
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
        .customLayerData([]) // Start empty
        .customThreeObject(d => {
            const isStrategic = d.norad_id === '41836' || d.norad_id === '40267' || d.norad_id === '25544'; 
            const size = isStrategic ? 1.5 : 0.8; 
            const color = isStrategic ? '#00ffff' : '#0088ff';
            
            const group = new THREE.Group();
            const mesh = new THREE.Mesh(
                new THREE.BoxGeometry(size, size, size),
                new THREE.MeshPhongMaterial({ 
                    color, 
                    emissive: color, 
                    emissiveIntensity: 0.8,
                    transparent: true,
                    opacity: 0.9
                })
            );
            group.add(mesh);

            const glowMesh = new THREE.Mesh(
                new THREE.SphereGeometry(size * 2, 8, 8),
                new THREE.MeshBasicMaterial({ color, transparent: true, opacity: 0.2 })
            );
            group.add(glowMesh);

            return group;
        })
        .customThreeObjectUpdate((obj, d) => {
            if (!d.position) return;
            const { lat, lng, alt } = d.position;
            const scaledAlt = Math.min(alt, 1.0) * 0.15; 
            const coords = world.getCoords(lat, lng, scaledAlt + 0.05);
            obj.position.set(coords.x, coords.y, coords.z);
            obj.lookAt(0, 0, 0); 
        })
        .onCustomLayerClick(d => {
            selectedSatellite.value = d;
            selectedPoint.value = null; 
            
            world.pointOfView({ 
                lat: d.position.lat, 
                lng: d.position.lng, 
                altitude: 1.5 
            }, 1000);
        })

        // --- Comms Links (Arcs) ---
        .arcsData([])
        .arcColor(() => '#00ff88')
        .arcDashLength(0.4)
        .arcDashGap(0.2)
        .arcDashAnimateTime(3000)
        .arcStroke(0.1)
        .arcAltitudeAutoScale(0.2)

        // --- Orbit Paths Layer ---
        .pathsData([]) // Start empty
        .pathColor(() => 'rgba(0, 255, 255, 0.4)') // Brighter orbits
        .pathDashLength(0.08)
        .pathDashGap(0.02)
        .pathDashAnimateTime(30000) 
        .pathStroke(0.18)
        .pathPointLat(p => p[0])
        .pathPointLng(p => p[1])
        .pathPointAlt(p => p[2] * 0.15) // Consistent scaling
        
        // --- Shared Labels ---
        .labelLat(d => d.latitude || d.position?.lat)
        .labelLng(d => d.longitude || d.position?.lng)
        .labelText(d => d.name)
        .labelAltitude(d => d.position ? (Math.min(d.position.alt, 1.0) * 0.15 + 0.1) : 0.02)
        .onGlobeClick(handleGlobeClick)
        .onPolygonClick(handleGlobeClick)
        .onPointClick(handleGlobeClick)
        .onLabelClick(handleGlobeClick);
    world.controls().autoRotate = true;
    world.controls().autoRotateSpeed = 0.1;

    world.pointOfView({ lat: 10, lng: 106, altitude: 2.5 }, 2000);

    // Load Administrative Boundaries
    fetch('https://raw.githubusercontent.com/vasturiano/globe.gl/master/example/datasets/ne_110m_admin_0_countries.geojson')
        .then(res => res.json())
        .then(countries => {
            world.polygonsData(countries.features);
        });

    // Initial Fetch (triggered AFTER globe setup)
    try {
        const token = 'vethinh_strategic_internal_token_2026';
        const [stormRes, satRes, stationRes] = await Promise.all([
            axios.get(`/api/internal-map/storms?token=${token}`),
            axios.get(`/api/internal-map/satellites?token=${token}`),
            axios.get(`/api/internal-map/ground-stations?token=${token}`)
        ]);
        
        activeStorms.value = stormRes.data;
        activeSatellites.value = satRes.data.data;
        groundStations.value = stationRes.data.data;

        // Fetch Radar metadata
        const radarRes = await axios.get('https://api.rainviewer.com/public/weather-maps.json');
        radarTimestamp.value = radarRes.data.radar.past[radarRes.data.radar.past.length - 1].time;
        
        // Manual Force Sync for non-reactive globe.gl
        if (world) {
            if (activeSatellites.value.length > 0) {
                const newSats = activeSatellites.value;
                world.customLayerData(newSats);
                world.pathsData(newSats.map(s => s.path));
                const strategic = newSats.filter(s => s.norad_id === '41836' || s.norad_id === '25544' || s.norad_id === '40267');
                world.labelsData([...activeStorms.value, ...strategic]);
            }
            
            if (groundStations.value.length > 0) {
                world.pointsData([...activeStorms.value, ...groundStations.value.map(s => ({
                    ...s,
                    pointColor: '#00ff88',
                    pointRadius: 0.8,
                    pointAltitude: 0.02,
                    isStation: true
                }))]);
            }
        }
    } catch (e) {
        console.error('Failed to fetch data', e);
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

    // Orbital Propagator Loop (Every 100ms for smooth motion)
    const propagatorId = setInterval(propagateSatellites, 100);
    
    // Cleanup
    return () => {
        clearInterval(propagatorId);
        window.removeEventListener('resize', handleResize);
    };
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
    if (!map) return;
    
    if (showRadar.value && map._radarLayer) {
        map._radarLayer.addTo(map);
    } else if (map._radarLayer) {
        map.removeLayer(map._radarLayer);
    }
    
    // Globe Radar logic (using custom textures is complex, we start with 2D first)
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
            }).addTo(markers).bindTooltip(`STATION: ${station.name}`);
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

            <!-- Pro Control Panels -->
            <div class="absolute top-24 left-8 z-10 flex flex-col space-y-2">
                <button @click="toggleRadar"
                    :class="showRadar ? 'bg-vibrant-blue text-white shadow-[0_0_15px_rgba(0,136,255,0.4)]' : 'bg-black/60 text-white/40'"
                    class="p-3 border border-white/10 backdrop-blur-md transition-all group relative">
                    <span class="text-xs">📡</span>
                    <div class="absolute left-full ml-4 px-3 py-1 bg-black text-[8px] font-black uppercase opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none border border-white/10">LIVE_RADAR</div>
                </button>
                <button @click="showGroundStations = !showGroundStations"
                    :class="showGroundStations ? 'bg-[#00ff88] text-black shadow-[0_0_15px_rgba(0,255,136,0.4)]' : 'bg-black/60 text-white/40'"
                    class="p-3 border border-white/10 backdrop-blur-md transition-all group relative">
                    <span class="text-xs">🗼</span>
                    <div class="absolute left-full ml-4 px-3 py-1 bg-black text-[8px] font-black uppercase opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none border border-white/10">GROUND_STATIONS</div>
                </button>
            </div>

            <!-- Sidebar Controls -->
            <div class="absolute top-8 left-8 z-10 w-64 space-y-6 pointer-events-none">
                <div class="pointer-events-auto">
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
                <div v-if="selectedSatellite" class="absolute top-8 right-8 z-30 w-80 bg-black/90 backdrop-blur-2xl border border-vibrant-blue/50 shadow-[0_0_50px_rgba(0,136,255,0.2)] overflow-hidden flex flex-col max-h-[85vh]">
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
                                        <span class="text-sm font-black text-white">{{ selectedSatellite.telemetry.altitude.toLocaleString() }} <span class="text-[9px] text-white/20">KM</span></span>
                                    </div>
                                </div>
                                <div class="flex justify-between items-end border-b border-white/5 pb-2">
                                    <span class="text-[9px] text-white/30 font-bold uppercase">Velocity</span>
                                    <div class="flex items-center space-x-3">
                                        <!-- Micro Chart -->
                                        <svg class="w-12 h-4 text-vibrant-green opacity-50" viewBox="0 0 100 20">
                                            <polyline fill="none" stroke="currentColor" stroke-width="2" points="0,5 20,8 40,4 60,10 80,6 100,5" />
                                        </svg>
                                        <span class="text-sm font-black text-vibrant-green">{{ selectedSatellite.telemetry.velocity.toFixed(3) }} <span class="text-[9px] text-white/20">KM/S</span></span>
                                    </div>
                                </div>
                                <div class="flex justify-between items-end border-b border-white/5 pb-2">
                                    <span class="text-[9px] text-white/30 font-bold uppercase">Orbit_Period</span>
                                    <span class="text-sm font-black text-yellow-500">{{ selectedSatellite.telemetry.period.toFixed(2) }} <span class="text-[9px] text-white/20">MIN</span></span>
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
                                        <p class="text-xs font-black text-white uppercase italic">{{ selectedSatellite.location || 'INTELLERNATIONAL_WATERS' }}</p>
                                    </div>
                                </div>
                                <div class="flex justify-between font-mono text-[9px] font-bold text-white/40 border-t border-white/5 pt-2">
                                    <div>LAT: {{ selectedSatellite.position.lat.toFixed(6) }}</div>
                                    <div>LNG: {{ selectedSatellite.position.lng.toFixed(6) }}</div>
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
                            <button class="w-full py-3 bg-white/5 text-white/40 text-[9px] font-black uppercase tracking-[0.3em] hover:bg-white/10 transition-all border border-white/5">
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
                                    <span v-if="hour.cloud_cover > 70" class="text-xl">☁️</span>
                                    <span v-else-if="hour.precip > 0.5" class="text-xl">🌧️</span>
                                    <span v-else class="text-xl">☀️</span>
                                </div>

                                <!-- Temp Metric -->
                                <div class="text-center">
                                    <div class="text-lg font-black italic">{{ hour.temp }}°</div>
                                    <!-- Simple bar for temp trend -->
                                    <div class="h-12 w-1.5 bg-white/5 rounded-full mt-2 relative overflow-hidden">
                                        <div class="absolute bottom-0 w-full bg-vibrant-blue" :style="{ height: (hour.temp + 10) * 2 + '%' }"></div>
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
                        <span class="text-[8px] font-black text-white/30 uppercase">Local_Timeline</span>
                        <input type="range" min="0" max="47" class="w-96 accent-vibrant-blue bg-white/10 h-1 rounded-full cursor-pointer">
                    </div>
                </div>
            </Transition>

            <!-- View Mode Switcher -->
            <div class="absolute bottom-8 right-8 z-40 flex space-x-2">
                <button v-for="mode in viewOptions" :key="mode.id"
                    @click="switchView(mode.id)"
                    :class="viewMode === mode.id ? 'bg-vibrant-blue border-vibrant-blue text-white shadow-[0_0_20px_rgba(0,136,255,0.4)]' : 'bg-black/60 border-white/10 text-white/40 hover:bg-black/80 hover:border-white/20'"
                    class="px-4 py-3 border backdrop-blur-md transition-all flex items-center space-x-3 group">
                    <span class="text-sm">{{ mode.icon }}</span>
                    <span class="text-[9px] font-black tracking-widest uppercase hidden lg:block">{{ mode.name }}</span>
                </button>
            </div>

            <!-- Bottom Left Info -->
            <div class="absolute bottom-8 left-8 z-10 p-4 border-l border-white/20 bg-black/40 backdrop-blur-sm">
                <p class="text-[10px] font-black uppercase italic text-vibrant-blue">ACTIVE_VIEW: {{ viewMode }}</p>
                <p class="text-[8px] text-white/40 font-mono mt-1 uppercase">Resolving spatial vectors @ {{ viewMode === 'GLOBE' ? '2.5km/px' : '0.5km/px' }}</p>
            </div>

            <!-- Globe Container (3D) -->
            <div v-show="viewMode === 'GLOBE'" ref="globeContainer" class="w-full h-full cursor-grab active:cursor-grabbing"></div>
            
            <!-- Leaflet Container (2D/Satellite) -->
            <div v-show="viewMode !== 'GLOBE'" ref="leafletContainer" class="w-full h-full bg-[#050508] z-0"></div>
            
            <!-- HUD Overlay Decoration -->
            <div class="absolute inset-0 pointer-events-none border-[15px] border-black/5 z-20"></div>
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

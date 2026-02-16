<script setup>
import { Head } from '@inertiajs/vue3';
import Globe from '@/Components/Globe.vue';
import SatelliteTelemetryPanel from '@/Components/SatelliteTelemetryPanel.vue';
import { ref, onMounted, computed } from 'vue';

const metrics = ref({
    cloud_coverage: 0,
    cloud_density: 0,
    rain_intensity: 0,
    pressure: 0,
    risk_score: 0,
    risk_level: 'LOADING...',
    confidence_score: 0,
    provenance: null,
    captured_at: null,
    image_url: null
});

const selectedLocation = ref(null); 
const selectedSatellite = ref(null);
const satellites = ref([]);
const activeLayers = ref(['COMMUNICATION', 'NAVIGATION', 'STATION', 'SCIENTIFIC', 'WEATHER', 'SPACE_DEBRIS', 'RISK_HEATMAP']);
const now = ref(new Date());

const filteredSatellites = computed(() => {
    return satellites.value.filter(s => activeLayers.value.includes(s.type));
});

const handleSatelliteClick = (satellite) => {
    selectedSatellite.value = satellite;
    // Auto-focus location if needed or just show panel
};

const toggleLayer = (layer) => {
    if (activeLayers.value.includes(layer)) {
        activeLayers.value = activeLayers.value.filter(l => l !== layer);
    } else {
        activeLayers.value.push(layer);
    }
};

onMounted(() => {
    fetchLatestMetrics();
    fetchLiveSatellites();
    
    setInterval(() => {
        now.value = new Date();
    }, 1000);

    if (window.Echo) {
        window.Echo.channel('weather.live')
            .listen('.weather.updated', (e) => {
                metrics.value = e.data;
            });

        window.Echo.channel('satellites.live')
            .listen('.satellite.updated', (e) => {
                const index = satellites.value.findIndex(s => s.id === e.data.id);
                if (index !== -1) {
                    satellites.value[index] = { ...satellites.value[index], ...e.data };
                } else {
                    satellites.value.push(e.data);
                }
            });
    }
});

const fetchLatestMetrics = async () => {
    try {
        const response = await fetch('/api/v1/weather/latest', {
            headers: { 'X-API-KEY': 'vetinh_dev_key_123' }
        });
        const json = await response.json();
        if (json.status === 'success') {
            metrics.value = {
                ...json.data,
                risk_score: json.data.risk.score,
                risk_level: json.data.risk.level,
                confidence_score: json.data.risk.confidence
            };
        }
    } catch (e) {
        console.error('Failed to fetch metrics:', e);
    }
};

const fetchLiveSatellites = async () => {
    try {
        const response = await fetch('/api/v1/satellites/live', {
            headers: { 'X-API-KEY': 'vetinh_dev_key_123' }
        });
        const json = await response.json();
        if (json.status === 'success') {
            satellites.value = json.data.map(s => ({
                ...s,
                ...s.last_track
            }));
        }
    } catch (e) {
        console.error('Failed to fetch satellites:', e);
    }
};

const handleSurfaceClick = async (data) => {
    selectedLocation.value = { ...data, history: [] };
    
    try {
        const response = await fetch(`/api/v1/weather/history?lat=${data.lat}&lng=${data.lng}`, {
            headers: { 'X-API-KEY': 'vetinh_dev_key_123' }
        });
        const json = await response.json();
        if (json.status === 'success') {
            selectedLocation.value.history = json.data;
            if (json.meta && json.meta.location) {
                selectedLocation.value.province = json.meta.location.province;
                selectedLocation.value.district = json.meta.location.district;
                selectedLocation.value.commune = json.meta.location.commune;
            }
        }
    } catch (e) {
        console.error('Failed to fetch location history:', e);
    }
};
</script>

<template>
    <Head title="Vetinh | Orbital Intelligence" />

    <div class="min-h-screen bg-[#020408] text-white selection:bg-vibrant-blue/30 overflow-hidden font-sans">
        <!-- 1. TACTICAL HUD FRAME (Brackets & Borders) -->
        <div class="fixed inset-0 pointer-events-none z-[60] border-[1px] border-white/5 mx-4 my-4 rounded-3xl">
            <!-- Corner Brackets -->
            <div class="absolute top-0 left-0 w-8 h-8 border-t-2 border-l-2 border-vibrant-blue/40 rounded-tl-2xl"></div>
            <div class="absolute top-0 right-0 w-8 h-8 border-t-2 border-r-2 border-vibrant-blue/40 rounded-tr-2xl"></div>
            <div class="absolute bottom-0 left-0 w-8 h-8 border-b-2 border-l-2 border-vibrant-blue/40 rounded-bl-2xl"></div>
            <div class="absolute bottom-0 right-0 w-8 h-8 border-b-2 border-r-2 border-vibrant-blue/40 rounded-br-2xl"></div>
            
            <!-- HUD Scanning Line -->
            <div class="absolute inset-x-0 h-px bg-gradient-to-r from-transparent via-vibrant-blue/10 to-transparent top-1/4 animate-pulse"></div>
        </div>

        <!-- 2. CORE GLOBE VIEW (Full Screen Background) -->
        <Globe 
            :satellites="filteredSatellites"
            :weatherMetrics="metrics"
            :activeLayers="activeLayers"
            @surface-click="handleSurfaceClick"
        />

        <!-- 3. TOP TELEMETRY STRIP -->
        <header class="fixed top-12 left-16 right-16 z-50 flex justify-between items-start pointer-events-none">
            <div class="flex flex-col space-y-1 pointer-events-auto">
                <div class="flex items-center space-x-3">
                    <div class="w-2 h-10 bg-vibrant-blue shadow-[0_0_15px_#4f46e5]"></div>
                    <div>
                        <h1 class="text-2xl font-black tracking-[0.3em] uppercase leading-none italic">STARWEATHER</h1>
                        <p class="text-[9px] font-mono text-white/40 tracking-[0.5em] mt-1.5 uppercase">Orbital Intelligence / tactical_os_v1.3</p>
                    </div>
                </div>
            </div>

            <!-- SYSTEM PERFORMANCE -->
            <div class="flex space-x-12 pointer-events-auto bg-black/40 backdrop-blur-xl border border-white/10 px-8 py-4 rounded-2xl shadow-2xl">
                <div v-for="(val, label) in { 'Uptime': '99.98%', 'Latency': '12ms', 'Sync': 'LIVE' }" :key="label" class="flex flex-col items-end">
                    <span class="text-[8px] font-black text-white/30 uppercase tracking-widest mb-1">{{ label }}</span>
                    <span class="text-lg font-mono text-vibrant-blue font-bold tracking-tighter">{{ val }}</span>
                </div>
                <div class="flex flex-col items-end pl-4 border-l border-white/10">
                    <span class="text-[8px] font-black text-white/30 uppercase tracking-widest mb-1">Clock</span>
                    <span class="text-lg font-mono text-white/80 font-bold tracking-tighter">{{ now.toLocaleTimeString() }}</span>
                </div>
            </div>
        </header>

        <!-- 4. SECTOR INTELLIGENCE CONSOLE (Integrated Right Panel) -->
        <aside class="fixed right-12 top-40 bottom-40 w-[420px] z-50 animate-in fade-in slide-in-from-right duration-700">
            <div class="h-full flex flex-col bg-black/60 backdrop-blur-2xl border border-white/5 rounded-3xl overflow-hidden shadow-[0_0_100px_rgba(0,0,0,0.8)]">
                <!-- Header Tab Section -->
                <div class="flex border-b border-white/10 bg-white/5">
                    <button class="flex-1 py-5 text-[10px] font-black tracking-[0.3em] uppercase border-b-2 border-vibrant-blue text-vibrant-blue bg-vibrant-blue/5">LOCATION_INTEL</button>
                    <button class="flex-1 py-5 text-[10px] font-black tracking-[0.2em] uppercase border-b-2 border-transparent text-white/20 hover:text-white/40 transition-colors">SPECTRAL_FLUX</button>
                </div>

                <!-- Scrollable Content -->
                <div class="flex-1 overflow-y-auto p-10 space-y-10 custom-scrollbar overflow-x-hidden">
                    <div v-if="selectedLocation">
                        <!-- Location ID Strip -->
                        <div class="flex flex-col space-y-3 mb-8">
                            <div class="flex items-center space-x-2">
                                <span class="px-2 py-0.5 bg-vibrant-blue text-black text-[10px] font-black rounded uppercase">Sector {{ Math.floor(selectedLocation.lat) }}</span>
                                <div class="w-1.5 h-1.5 rounded-full bg-vibrant-blue animate-ping"></div>
                                <span class="text-[10px] font-mono text-vibrant-blue/60 tracking-wider">{{ selectedLocation.lat.toFixed(6) }}N / {{ selectedLocation.lng.toFixed(6) }}E</span>
                            </div>
                            <h2 class="text-4xl font-black text-white uppercase tracking-tighter leading-none py-2">
                                {{ selectedLocation.province || 'MARITIME_ZONE' }}
                            </h2>
                            <p class="text-[11px] text-white/30 uppercase font-black tracking-[0.4em] border-l-2 border-vibrant-blue pl-4">
                                {{ selectedLocation.district ? `${selectedLocation.district} // ${selectedLocation.commune}` : 'NEURAL_LINK_ACTIVE' }}
                            </p>
                        </div>

                        <!-- Real-time Telemetry Grid -->
                        <div class="grid grid-cols-2 gap-5">
                            <!-- Temperature Card -->
                            <div class="flex flex-col p-6 bg-white/[0.03] border border-white/5 rounded-2xl group hover:border-vibrant-blue/30 transition-all">
                                <span class="text-[9px] font-black text-white/20 uppercase mb-3 tracking-widest group-hover:text-vibrant-blue/50">Core_Thermal</span>
                                <div class="flex items-baseline space-x-2">
                                    <span class="text-4xl font-black">{{ selectedLocation.temp }}</span>
                                    <span class="text-sm font-bold text-white/20">°C</span>
                                </div>
                                <!-- Sparkline -->
                                <div class="h-8 mt-4 flex items-end space-x-1 outline outline-1 outline-white/5 p-1 rounded">
                                    <div v-for="i in 15" :key="i" :style="{ height: (30 + Math.random() * 70) + '%' }" class="flex-1 bg-vibrant-blue/20 rounded-t-[1px]"></div>
                                </div>
                            </div>

                            <!-- Wind Velocity -->
                            <div class="flex flex-col p-6 bg-white/[0.03] border border-white/5 rounded-2xl">
                                <span class="text-[9px] font-black text-white/20 uppercase mb-3 tracking-widest">Kinetic_Force</span>
                                <div class="flex items-baseline space-x-2">
                                    <span class="text-4xl font-black text-vibrant-green">{{ selectedLocation.windSpeed }}</span>
                                    <span class="text-sm font-bold text-vibrant-green/40">KMH</span>
                                </div>
                                <div class="text-[9px] font-mono text-vibrant-green/60 mt-2 uppercase tracking-tighter italic">GUSTS: {{ selectedLocation.windGusts }} K/S</div>
                            </div>
                        </div>

                        <!-- Secondary Metrics (Detailed List) -->
                        <div class="space-y-4 mt-10">
                            <h3 class="text-[10px] font-black text-white/20 uppercase tracking-[0.5em] mb-6 flex items-center">
                                <span class="w-4 h-px bg-white/10 mr-4"></span>
                                Atmospheric_Readout
                            </h3>
                            <div v-for="(val, label) in { 
                                'Pressure': selectedLocation.pressure + ' hPa',
                                'Humidity': selectedLocation.humidity + '%',
                                'UV Index': selectedLocation.uvIndex,
                                'Visibility': selectedLocation.visibility + ' km',
                                'Cloud Density': selectedLocation.clouds + '%',
                                'Dew Point': selectedLocation.dewPoint + '°'
                            }" :key="label" 
                                 class="flex items-center justify-between p-4 border border-white/5 bg-white/[0.01] hover:bg-white/[0.03] transition-all cursor-default rounded-xl">
                                <span class="text-[11px] text-white/40 uppercase font-mono tracking-tighter">{{ label }}</span>
                                <div class="flex items-center space-x-4">
                                    <span class="text-sm font-black text-white tracking-widest">{{ val }}</span>
                                    <div class="w-16 h-1 bg-white/5 rounded-full overflow-hidden">
                                        <div class="h-full bg-vibrant-blue/40" :style="{ width: '70%' }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TRANSMIT BUTTON -->
                        <div class="mt-12">
                            <button @click="transmitIntel" 
                                    class="w-full py-5 bg-vibrant-blue hover:bg-vibrant-blue/80 text-black text-[12px] font-black uppercase tracking-[0.4em] transition-all flex items-center justify-center space-x-3 rounded-2xl shadow-[0_10px_30px_rgba(79,70,229,0.3)]">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M8 12h.01M12 12h.01M16 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>INITIALIZE_TRANSMIT</span>
                            </button>
                        </div>
                    </div>

                    <!-- EMPTY STATE -->
                    <div v-else class="h-full flex flex-col items-center justify-center text-center space-y-8 py-32 opacity-40">
                        <div class="relative">
                            <div class="w-24 h-24 rounded-full border-2 border-dashed border-vibrant-blue/20 animate-spin-slow"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="w-4 h-4 bg-vibrant-blue shadow-[0_0_20px_#4f46e5] rounded-sm"></div>
                            </div>
                        </div>
                        <div>
                            <p class="text-[12px] font-black text-white uppercase tracking-[0.6em]">System Standby</p>
                            <p class="text-[9px] font-mono text-vibrant-blue/40 uppercase mt-2 tracking-widest">Select Sector on Globe to Begin Analysis</p>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- 5. TACTICAL ORBITAL DOCK (Bottom Integrated) -->
        <nav class="fixed bottom-12 left-1/2 -translate-x-1/2 z-50 flex flex-col items-center">
            <!-- Coordinates Readout -->
            <div class="mb-6 flex items-center space-x-4">
                <div class="text-[10px] font-mono text-vibrant-blue/80 bg-black/60 px-6 py-2 rounded-full border border-vibrant-blue/30 backdrop-blur-xl shadow-2xl">
                    <span class="opacity-40 uppercase mr-2 tracking-widest font-black text-[8px]">Scan_Coord:</span>
                    16.047079°N | 108.206230°E
                </div>
            </div>

            <div class="flex items-center space-x-1 px-4 py-3 bg-black/60 backdrop-blur-3xl rounded-[2.5rem] border border-white/10 shadow-[0_30px_100px_rgba(0,0,0,0.8)]">
                <button v-for="layer in [
                    { id: 'COMMUNICATION', label: 'COMMS', icon: 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z' },
                    { id: 'NAVIGATION', label: 'GPS', icon: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z' },
                    { id: 'SCIENTIFIC', label: 'SCIENT', icon: 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.183.244l-.28.14a2 2 0 01-2.983-1.882v-2.43a2 2 0 01.468-1.285l3.946-4.654A2 2 0 017.51 5H16.49a2 2 0 011.492.673l3.946 4.654a2 2 0 01.468 1.285v2.43a2 2 0 01-2.983 1.882l-.28-.14z' },
                    { id: 'WEATHER', label: 'METEO', icon: 'M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z' },
                    { id: 'SPACE_DEBRIS', label: 'DEBRIS', icon: 'M13 10V3L4 14h7v7l9-11h-7z' }
                ]" :key="layer.id"
                        @click="toggleLayer(layer.id)"
                        class="group relative flex flex-col items-center justify-center w-20 h-20 rounded-3xl transition-all duration-500"
                        :class="activeLayers.includes(layer.id) ? 'bg-vibrant-blue/20 scale-95' : 'hover:bg-white/5'">
                    
                    <svg class="w-7 h-7 mb-2 transition-transform group-hover:scale-110" 
                         :class="activeLayers.includes(layer.id) ? 'text-vibrant-blue' : 'text-white/20'"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="layer.icon" />
                    </svg>
                    <span class="text-[8px] font-black uppercase tracking-widest leading-none"
                          :class="activeLayers.includes(layer.id) ? 'text-vibrant-blue' : 'text-white/10'">
                        {{ layer.label }}
                    </span>

                    <!-- Active Indicator -->
                    <div v-if="activeLayers.includes(layer.id)" 
                         class="absolute top-2 right-2 w-2 h-2 bg-vibrant-blue rounded-full shadow-[0_0_10px_#4f46e5]"></div>
                </button>

                <div class="w-px h-10 bg-white/10 mx-4"></div>

                <!-- Global Threat Toggle -->
                <button @click="toggleLayer('RISK_HEATMAP')"
                        class="flex flex-col items-center justify-center px-10 h-20 rounded-3xl transition-all duration-500 group overflow-hidden relative"
                        :class="activeLayers.includes('RISK_HEATMAP') ? 'bg-red-600/20' : 'bg-white/5 hover:bg-white/10'">
                    <span class="text-[8px] font-black text-white/20 uppercase tracking-widest leading-none mb-2">Threat_Level</span>
                    <span class="text-[12px] font-black uppercase tracking-[0.2em]"
                          :class="activeLayers.includes('RISK_HEATMAP') ? 'text-red-500 underline underline-offset-4 decoration-2' : 'text-white/40 group-hover:text-white'">
                          RISK MAP
                    </span>
                    <div v-if="activeLayers.includes('RISK_HEATMAP')" class="absolute top-0 right-0 w-full h-[2px] bg-red-600 animate-pulse"></div>
                </button>
            </div>
        </nav>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 3px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(79, 70, 229, 0.4);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(79, 70, 229, 0.7);
}

@keyframes spin-slow {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.animate-spin-slow {
    animation: spin-slow 20s linear infinite;
}
</style>

<script setup>
import { Head } from '@inertiajs/vue3';
import Globe from '@/Components/Globe.vue';
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
const satellites = ref([]);
const activeLayers = ref(['COMMUNICATION', 'NAVIGATION', 'STATION', 'SCIENTIFIC', 'WEATHER', 'SPACE_DEBRIS', 'RISK_HEATMAP']);
const now = ref(new Date());

const filteredSatellites = computed(() => {
    return satellites.value.filter(s => activeLayers.value.includes(s.type));
});

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

    <div class="h-screen w-screen bg-black overflow-hidden font-sans selection:bg-vibrant-blue/30 relative text-white">
        <!-- 1. Global Viewport (The Globe) -->
        <Globe 
            :satellites="filteredSatellites"
            :weatherMetrics="metrics"
            :activeLayers="activeLayers"
            @surface-click="handleSurfaceClick"
        />

        <!-- 2. Cinematic Minimalist Overlay Layer -->
        <div class="absolute inset-0 pointer-events-none">
            
            <!-- A. Top Branding & Search Island -->
            <div class="absolute top-8 left-8 right-8 flex justify-between items-start pointer-events-auto">
                <div class="group flex items-center space-x-4 bg-black/20 backdrop-blur-2xl p-2 pr-6 rounded-full border border-white/5 shadow-2xl hover:border-vibrant-blue/20 transition-all duration-500">
                    <div class="w-10 h-10 rounded-full bg-vibrant-blue flex items-center justify-center shadow-lg shadow-vibrant-blue/40">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9-9H3m9 9L3 5m0 0l4 4m-4-4l4-4"/></svg>
                    </div>
                    <div>
                        <h1 class="text-sm font-black tracking-widest text-white leading-none">VETINH <span class="text-vibrant-blue/60 italic font-medium">OS</span></h1>
                        <p class="text-[8px] text-white/30 uppercase font-bold tracking-[0.2em] mt-0.5">Orbital Intelligence Hub</p>
                    </div>
                </div>

                <div class="hidden md:flex items-center bg-black/40 backdrop-blur-3xl border border-white/10 rounded-2xl px-4 py-2 space-x-3 w-80 group hover:w-96 transition-all duration-700 shadow-2xl">
                    <svg class="w-4 h-4 text-white/20 group-hover:text-vibrant-blue transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" placeholder="Search Orbitals..." class="bg-transparent border-none text-[11px] text-white/80 placeholder:text-white/20 focus:ring-0 w-full font-medium">
                    <span class="text-[8px] text-white/20 font-black border border-white/10 px-1.5 py-0.5 rounded uppercase">cmd+k</span>
                </div>
            </div>

            <!-- B. Floating Intelligence Card (Location Intel) -->
            <transition name="cinematic-pop">
                <div v-if="selectedLocation" 
                     class="absolute top-24 right-8 w-[400px] pointer-events-auto">
                    <div class="backdrop-blur-3xl bg-black/40 rounded-[3rem] border border-white/10 shadow-[0_32px_128px_-16px_rgba(0,0,0,0.8)] overflow-hidden flex flex-col max-h-[calc(100vh-200px)] group/card">
                        
                        <!-- Premium Header -->
                        <div class="p-8 pb-4 flex items-center justify-between border-b border-white/5">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-vibrant-blue to-indigo-600 flex items-center justify-center shadow-lg shadow-vibrant-blue/20">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21l-8.228-9.904A17.963 17.963 0 014 22m4-19l4 4m0 0l4-4m-4 4v12"/></svg>
                                </div>
                                <div>
                                    <h2 class="text-sm font-black tracking-[0.2em] text-white uppercase italic">
                                        <span v-if="selectedLocation.province">{{ selectedLocation.province }}</span>
                                        <span v-else>Location</span>
                                        <span class="text-vibrant-blue"> Intel</span>
                                    </h2>
                                    <p class="text-[9px] text-white/30 uppercase mt-0.5 font-mono">
                                        <span v-if="selectedLocation.district" class="text-vibrant-blue/60">{{ selectedLocation.district }} / {{ selectedLocation.commune }} • </span>
                                        {{ selectedLocation.lat.toFixed(4) }}°N / {{ selectedLocation.lng.toFixed(4) }}°E
                                    </p>
                                </div>
                            </div>
                            <button @click="selectedLocation = null" class="w-10 h-10 rounded-full bg-white/5 hover:bg-white/15 flex items-center justify-center transition-all duration-300">
                                <svg class="w-5 h-5 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <!-- Data Scroll Area -->
                        <div class="flex-1 overflow-y-auto p-8 space-y-8 no-scrollbar">
                            <!-- Hero Metrics -->
                            <div class="flex justify-between items-center bg-white/[0.03] p-8 rounded-[2.5rem] border border-white/5 group-hover/card:border-white/10 transition-colors duration-500">
                                <div class="space-y-1">
                                    <span class="text-[10px] text-white/30 uppercase font-black tracking-widest leading-none">Air Temp</span>
                                    <div class="flex items-baseline space-x-1">
                                        <span class="text-6xl font-black text-white leading-none tracking-tighter">{{ selectedLocation.temp }}°</span>
                                        <span class="text-xl font-bold text-white/20">C</span>
                                    </div>
                                </div>
                                <div class="h-16 w-px bg-white/10 mx-4"></div>
                                <div class="space-y-1 text-right">
                                    <span class="text-[10px] text-vibrant-green font-black uppercase tracking-widest leading-none">Wind Power</span>
                                    <div class="flex items-baseline space-x-1 justify-end">
                                        <span class="text-4xl font-black text-vibrant-green leading-none">{{ selectedLocation.windSpeed }}</span>
                                        <span class="text-sm font-bold text-white/20 uppercase tracking-tighter">kmh</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Atmospheric Grid -->
                            <div class="grid grid-cols-2 gap-4">
                                <div v-for="(val, label) in { 
                                    'Pressure': selectedLocation.pressure + ' hPa',
                                    'Humidity': selectedLocation.humidity + '%',
                                    'UV Index': selectedLocation.uvIndex,
                                    'Precip': selectedLocation.precip + ' mm',
                                    'Visibility': selectedLocation.visibility + ' km',
                                    'Clouds': selectedLocation.clouds + '%'
                                }" :key="label" 
                                     class="p-5 bg-white/[0.02] border border-white/5 rounded-3xl flex flex-col space-y-1 hover:bg-white/[0.05] transition-all duration-300 group/item">
                                    <span class="text-[9px] text-white/20 uppercase font-black tracking-widest group-hover/item:text-vibrant-blue transition-colors">{{ label }}</span>
                                    <span class="text-sm font-mono font-bold text-white/90">{{ val }}</span>
                                </div>
                            </div>

                            <!-- 24H Logic Waveform -->
                            <div class="space-y-4">
                                <div class="flex items-center justify-between px-2">
                                    <span class="text-[10px] font-black text-white/20 uppercase tracking-widest">Atmospheric Waveform</span>
                                    <div class="flex space-x-1">
                                        <div v-for="i in 3" :key="i" class="w-1.5 h-1.5 rounded-full bg-vibrant-blue/40 animate-pulse"></div>
                                    </div>
                                </div>
                                <div class="h-24 flex items-end justify-between p-6 bg-black/40 rounded-[2rem] border border-white/5 outline outline-1 outline-white/5">
                                    <div v-for="(point, idx) in (selectedLocation.history || Array(24).fill({pressure: 1013}))" :key="idx" 
                                         class="w-1.5 bg-vibrant-blue/30 rounded-full hover:bg-vibrant-blue transition-all duration-500"
                                         :style="{ height: (point.pressure ? (point.pressure - 980) / 60 * 100 : 20) + '%' }">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Action -->
                        <div class="p-8 pt-2">
                            <button class="w-full bg-vibrant-blue text-white py-5 rounded-[2rem] text-[11px] font-black uppercase tracking-[0.4em] overflow-hidden group/btn relative">
                                <div class="absolute inset-0 bg-white/20 translate-y-full group-hover/btn:translate-y-0 transition-transform duration-500"></div>
                                <span class="relative">Transmit Intelligence</span>
                            </button>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- C. Cinematic Orbital Dock (Bottom Center) -->
            <div class="absolute bottom-10 left-1/2 -translate-x-1/2 pointer-events-auto group/dock scale-110">
                <div class="flex items-end space-x-2 p-3 bg-black/20 backdrop-blur-3xl rounded-[3rem] border border-white/10 shadow-[0_32px_64px_-16px_rgba(0,0,0,0.6)] group-hover/dock:bg-black/40 transition-all duration-700 active:scale-[0.98]">
                    
                    <!-- App Launcher Icons (Pseudo Side-bar replacements) -->
                    <div class="flex items-center space-x-1 px-3 border-r border-white/10 mr-2">
                        <button v-for="icon in ['box', 'layers', 'activity']" :key="icon" 
                                class="w-14 h-14 rounded-2xl hover:bg-white/10 flex items-center justify-center transition-all duration-300 hover:scale-110 group/icon overflow-hidden">
                            <div class="w-7 h-7 text-white/40 group-hover/icon:text-white transition-colors">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path v-if="icon === 'box'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    <path v-if="icon === 'layers'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    <path v-if="icon === 'activity'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                        </button>
                    </div>

                    <!-- Layers Toggles (The actual logic items) -->
                    <div class="flex items-center space-x-2 pr-3">
                        <button v-for="layer in ['COMMUNICATION', 'NAVIGATION', 'STATION', 'SCIENTIFIC', 'WEATHER', 'SPACE_DEBRIS', 'RISK_HEATMAP']" 
                                :key="layer" 
                                class="h-14 px-5 rounded-2xl flex items-center space-x-4 transition-all duration-300 group/layer overflow-hidden"
                                :class="[activeLayers.includes(layer) ? (layer === 'RISK_HEATMAP' ? 'bg-red-600 shadow-lg shadow-red-600/30' : 'bg-vibrant-blue shadow-lg shadow-vibrant-blue/30') : 'bg-white/5 hover:bg-white/10 shadow-none']"
                                @click="toggleLayer(layer)">
                            <span :class="[activeLayers.includes(layer) ? 'bg-white' : 'bg-white/20']" class="w-2 h-2 rounded-full transition-colors"></span>
                            <span class="text-[10px] font-black uppercase tracking-[0.2em]" :class="[activeLayers.includes(layer) ? 'text-white' : 'text-white/40 group-hover/layer:text-white/80']">
                                {{ layer === 'RISK_HEATMAP' ? 'RISK MAP' : layer.toLowerCase().replace('_', ' ') }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- D. Cinematic HUD Gauges (Corners) -->
            <div class="absolute bottom-8 left-8 p-8 bg-black/20 backdrop-blur-2xl rounded-[3rem] border border-white/5 flex items-center space-x-8 shadow-2xl pointer-events-auto shadow-vibrant-blue/5">
                <div class="flex flex-col">
                    <span class="text-[9px] text-white/20 uppercase font-black tracking-widest leading-none">Global Scan Health</span>
                    <div class="flex items-center space-x-4 mt-2">
                        <span class="text-xs font-black text-vibrant-green uppercase">Optimal</span>
                        <div class="flex space-x-1">
                            <div v-for="i in 5" :key="i" class="w-1.5 h-4 rounded-full" :class="[i < 5 ? 'bg-vibrant-green/80' : 'bg-white/10 animate-pulse']"></div>
                        </div>
                    </div>
                </div>
                <div class="w-px h-10 bg-white/10"></div>
                <div class="flex flex-col">
                    <span class="text-[9px] text-white/20 uppercase font-black tracking-widest leading-none">System Sync</span>
                    <span class="text-[11px] font-mono font-bold text-white/80 mt-2 uppercase">{{ now.toLocaleTimeString() }}</span>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
.cinematic-pop-enter-active, .cinematic-pop-leave-active {
  transition: all 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.cinematic-pop-enter-from, .cinematic-pop-leave-to {
  opacity: 0;
  transform: scale(0.9) translateY(40px);
  filter: blur(20px);
}

.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.search-glow:focus-within {
    box-shadow: 0 0 30px rgba(79, 70, 229, 0.2);
    border-color: rgba(79, 70, 229, 0.4);
}
</style>

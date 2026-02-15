<script setup>
import { Head } from '@inertiajs/vue3';
import Globe from '@/Components/Globe.vue';
import { ref, onMounted, computed } from 'vue';

const metrics = ref({
    cloud_coverage: 0,
    rain_intensity: 0,
    risk_score: 0,
    risk_level: 'LOADING...',
    captured_at: null,
    image_url: null
});

const satellites = ref([]);
const activeLayers = ref(['COMMUNICATION', 'WEATHER', 'STATION', 'OBSERVATION', 'NAVIGATION']);

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
                risk_level: json.data.risk.level
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
</script>

<template>
    <Head title="Vetinh | Orbital Intelligence" />

    <div class="fixed inset-0 bg-[#05050a] text-white flex overflow-hidden">
        <!-- 1. Left Control Panel -->
        <aside class="w-72 glass m-6 rounded-2xl p-6 flex flex-col space-y-8 z-30 pointer-events-auto">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-vibrant-blue rounded-xl flex items-center justify-center glow-blue">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-black italic tracking-tighter uppercase leading-none">Vetinh</h1>
                    <p class="text-[9px] text-white/30 uppercase tracking-[0.3em] font-bold">Orbital Explorer</p>
                </div>
            </div>

            <!-- Satellite Layers -->
            <div class="space-y-4">
                <h3 class="text-[10px] font-black text-white/40 uppercase tracking-[0.2em] mb-4">Satellite Layers</h3>
                <div class="space-y-2">
                    <button v-for="layer in ['COMMUNICATION', 'NAVIGATION', 'STATION', 'OBSERVATION', 'WEATHER']" 
                            :key="layer"
                            @click="toggleLayer(layer)"
                            class="w-full flex items-center justify-between p-3 rounded-xl transition-all duration-300 border"
                            :class="activeLayers.includes(layer) ? 'bg-white/5 border-white/10' : 'bg-transparent border-transparent opacity-40 grayscale'">
                        <div class="flex items-center space-x-3">
                            <span class="w-2 h-2 rounded-full" :class="{
                                'bg-vibrant-blue': layer === 'COMMUNICATION',
                                'bg-vibrant-orange': layer === 'NAVIGATION',
                                'bg-white': layer === 'STATION',
                                'bg-vibrant-purple': layer === 'OBSERVATION',
                                'bg-vibrant-green': layer === 'WEATHER'
                            }"></span>
                            <span class="text-[11px] font-bold tracking-tight text-white/80 uppercase">{{ layer.toLowerCase() }}</span>
                        </div>
                        <span class="text-[10px] font-mono text-white/30">{{ satellites.filter(s => s.type === layer).length }}</span>
                    </button>
                </div>
            </div>

            <div class="flex-1"></div>

            <!-- System Info -->
            <div class="p-4 bg-white/5 rounded-2xl border border-white/5 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-white/40 uppercase">Global Health</span>
                    <span class="text-[10px] text-vibrant-green font-bold">OPTIMAL</span>
                </div>
                <div class="h-1 bg-white/5 rounded-full overflow-hidden">
                    <div class="h-full bg-vibrant-green w-[92%]"></div>
                </div>
            </div>
        </aside>

        <!-- 2. Central Globe Area -->
        <main class="flex-1 relative">
            <Globe :satellites="filteredSatellites" />

            <!-- Top Search / HUD -->
            <div class="absolute top-8 left-1/2 -translate-x-1/2 w-[400px] z-30">
                <div class="glass flex items-center p-1 rounded-2xl border-white/10 group">
                    <div class="p-3 text-white/40">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" placeholder="Search Orbitals (Landsat, ISS, Himawari...)" 
                           class="flex-1 bg-transparent border-none text-xs focus:ring-0 text-white placeholder:text-white/20 font-bold uppercase tracking-tight">
                    <div class="pr-2">
                        <kbd class="px-2 py-1 bg-white/5 rounded-lg text-[10px] font-mono text-white/40">CMD+K</kbd>
                    </div>
                </div>
            </div>

            <!-- Right Analytics Panel -->
            <div class="absolute top-8 right-8 w-80 space-y-4 z-30">
                <!-- Weather Fusion Hud -->
                <div class="glass p-6 rounded-2xl border-t-2 border-vibrant-blue relative overflow-hidden group">
                    <div class="relative z-10 space-y-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-[10px] font-black text-white/50 uppercase tracking-[0.2em]">Regional Scan</h2>
                            <div class="px-2 py-0.5 bg-vibrant-blue/20 text-vibrant-blue text-[8px] rounded font-black">H9-LIVE</div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <p class="text-[9px] text-white/30 uppercase font-black">Cloud Coverage</p>
                                <p class="text-2xl font-black font-outfit">{{ metrics.cloud_coverage }}<span class="text-xs text-white/20 ml-0.5">%</span></p>
                            </div>
                            <div class="space-y-1 text-right">
                                <p class="text-[9px] text-white/30 uppercase font-black">Risk Score</p>
                                <p :class="{
                                    'text-vibrant-green': metrics.risk_level === 'LOW',
                                    'text-yellow-400': metrics.risk_level === 'MEDIUM',
                                    'text-vibrant-orange': metrics.risk_level === 'HIGH',
                                    'text-red-500': metrics.risk_level === 'CRITICAL'
                                }" class="text-2xl font-black font-outfit">{{ metrics.risk_score }}</p>
                            </div>
                        </div>
                    </div>
                    <!-- Subtle Glow in background -->
                    <div class="absolute -top-10 -right-10 w-32 h-32 bg-vibrant-blue/10 blur-3xl group-hover:bg-vibrant-blue/20 transition duration-1000"></div>
                </div>

                <!-- Vietnam Micro-Map Overlay -->
                <div class="glass p-2 rounded-2xl border border-white/5 group relative">
                    <img :src="metrics.image_url || 'https://via.placeholder.com/400x600/05050a/4f46e5?text=WAITING+FOR+H9'" 
                         class="w-full h-48 object-cover rounded-xl grayscale group-hover:grayscale-0 transition duration-700" 
                         alt="Satellite Scan">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#05050a]/80 to-transparent flex flex-col justify-end p-4">
                        <p class="text-[9px] font-black text-white uppercase tracking-widest text-center">Pacific/Vietnam Sector</p>
                    </div>
                </div>

                <!-- Legend Overlay -->
                <div class="glass p-5 rounded-2xl">
                    <h3 class="text-[10px] font-black text-white/40 uppercase tracking-[0.2em] mb-4">Live Legend</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-[10px] font-bold">
                            <span class="text-white/40 italic uppercase">Orbit Types</span>
                            <div class="flex space-x-2">
                                <span class="px-1.5 py-0.5 bg-white/5 rounded text-white/60">LEO</span>
                                <span class="px-1.5 py-0.5 bg-white/5 rounded text-white/20">MEO</span>
                                <span class="px-1.5 py-0.5 bg-white/5 rounded text-white/20">GEO</span>
                            </div>
                        </div>
                        <div class="pt-2 border-t border-white/5 grid grid-cols-2 gap-y-2">
                            <div class="flex items-center space-x-2 text-[9px] text-white/50">
                                <kbd class="px-1 bg-white/10 rounded">ESC</kbd> <span>Deselect</span>
                            </div>
                            <div class="flex items-center space-x-2 text-[9px] text-white/50">
                                <kbd class="px-1 bg-white/10 rounded">R</kbd> <span>Rotate</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Time Control Panel -->
            <div class="absolute bottom-8 left-1/2 -translate-x-1/2 z-30">
                <div class="glass px-8 py-3 rounded-full flex items-center space-x-8 border-white/10">
                    <button class="text-white/40 hover:text-white transition">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M4.5 3.5v13l9-6.5-9-6.5z"/></svg>
                    </button>
                    <div class="flex flex-col items-center">
                        <p class="text-[8px] text-white/30 uppercase font-black tracking-widest leading-none mb-1">Time Speed</p>
                        <div class="flex items-center space-x-3">
                            <span class="text-[10px] font-mono text-vibrant-blue font-bold tracking-tighter uppercase italic">10X Realtime</span>
                            <div class="w-32 h-1 bg-white/10 rounded-full relative">
                                <div class="absolute left-0 top-0 h-full w-[60%] bg-vibrant-blue shadow-[0_0_10px_#4f46e5] rounded-full"></div>
                                <div class="absolute left-[60%] top-1/2 -translate-y-1/2 w-2 h-2 bg-white rounded-full shadow-lg"></div>
                            </div>
                        </div>
                    </div>
                    <button class="text-white/40 hover:text-white transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </button>
                </div>
            </div>
        </main>
    </div>
</template>

<style>
/* Custom animations for the UI overhaul */
@keyframes pulse-vibrant {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.8; transform: scale(1.05); }
}

.glow-blue {
    box-shadow: 0 0 25px rgba(79, 70, 229, 0.4);
}
</style>

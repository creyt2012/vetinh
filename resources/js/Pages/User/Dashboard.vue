<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { MapPin } from 'lucide-vue-next';

const props = defineProps({
    metrics: Array,
    satellites: Array,
    storms: Array
});

const riskScore = ref(props.storms?.length > 0 ? 68 : 34);

const weatherData = ref({
    temp: props.metrics?.[0]?.temperature || 24,
    humidity: props.metrics?.[0]?.humidity || 65,
    wind: props.metrics?.[0]?.wind_speed || 12,
    visibility: 95
});

const selectedSatellite = ref(null);
const isLoadingSat = ref(false);

const selectSatellite = async (sat) => {
    selectedSatellite.value = { ...sat, loading: true };
    isLoadingSat.value = true;
    
    try {
        const token = 'vethinh_strategic_internal_token_2026';
        // We fetch fresh real-time data for this specific satellite
        const response = await axios.get(`/api/internal-map/satellites?token=${token}`);
        const fullData = response.data.data.find(s => s.norad_id === sat.norad_id);
        
        if (fullData) {
            selectedSatellite.value = fullData;
        } else {
            // Fallback for satellites not in current tracking batch
            selectedSatellite.value = {
                ...sat,
                location: 'SCANNING...',
                telemetry: { altitude: 550, velocity: 7.6, period: 95 },
                modules: [
                    { id: 'MOD-01', name: 'Standard Payload', status: 'ONLINE' }
                ],
                specs: { operator: 'Global Network', mass: '850 KG' }
            };
        }
    } catch (e) {
        console.error('Failed to fetch satellite intel', e);
    } finally {
        isLoadingSat.value = false;
    }
};
</script>

<template>
    <Head title="Intelligence Dashboard" />

    <UserLayout>
        <div class="grid grid-cols-12 gap-8 mb-10">
            <!-- Left Panel: Global Risk HUD -->
            <div class="col-span-12 lg:col-span-8 space-y-8">
                <div class="flex justify-between items-end">
                    <div>
                        <h2 class="text-4xl font-black uppercase tracking-tighter italic leading-none">GLOBAL_INTELLIGENCE</h2>
                        <p class="text-xs text-vibrant-blue uppercase tracking-[0.4em] font-black mt-2">Real-time Weather & Orbital Asset Surveillance</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[9px] font-black text-white/20 uppercase tracking-widest">LOCAL_INDEX_TIME</p>
                        <p class="text-lg font-mono font-black italic">{{ new Date().toLocaleTimeString() }}</p>
                    </div>
                </div>

                <!-- Main HUD Display -->
                <div class="aspect-video bg-[#050508] border border-white/5 relative group overflow-hidden">
                    <!-- Scanner Animation Overlay (Decorative) -->
                    <div class="absolute inset-0 pointer-events-none opacity-20">
                        <div class="w-full h-1 bg-vibrant-blue/20 absolute top-0 animate-[scan_4s_linear_infinite]"></div>
                        <!-- Background Grid/Map Placeholder -->
                        <div class="absolute inset-0 flex items-center justify-center opacity-10 grayscale scale-150">
                             <div class="w-full h-full bg-[url('https://unpkg.com/three-globe/example/img/earth-blue-marble.jpg')] bg-cover opacity-30"></div>
                        </div>
                    </div>
                    
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="text-center relative">
                            <!-- Tactical Ring Breakdown -->
                            <div class="absolute -inset-12 border border-white/5 rounded-full pointer-events-none"></div>
                            <div class="absolute -inset-20 border border-white/5 rounded-full opacity-30 pointer-events-none"></div>
                            
                            <!-- Detailed Metrics around the circle -->
                            <div class="absolute -top-16 left-1/2 -translate-x-1/2 whitespace-nowrap">
                                <p class="text-[7px] font-black text-vibrant-blue tracking-[.3em] uppercase">SYSTEM_INTEGRITY: 100%</p>
                            </div>
                            <div class="absolute top-1/2 -left-28 -translate-y-1/2 text-right">
                                <p class="text-[6px] font-black text-white/30 tracking-widest uppercase mb-0.5">STORM_THREAT</p>
                                <p class="text-[9px] font-black" :class="storms?.length > 0 ? 'text-red-500' : 'text-vibrant-green'">{{ storms?.length > 0 ? 'ACTIVE' : 'NOMINAL' }}</p>
                            </div>
                            <div class="absolute top-1/2 -right-28 -translate-y-1/2 text-left">
                                <p class="text-[6px] font-black text-white/30 tracking-widest uppercase mb-0.5">ASSET_LATENCY</p>
                                <p class="text-[9px] font-black text-vibrant-blue">14MS</p>
                            </div>

                            <div class="w-40 h-40 rounded-full border border-vibrant-blue/20 flex items-center justify-center relative bg-black/40">
                                <!-- Pulsing Ring instead of spinning -->
                                <div class="absolute inset-0 rounded-full border-2 border-vibrant-blue/40 animate-pulse"></div>
                                <div class="absolute inset-3 rounded-full border border-vibrant-blue/10 border-t-vibrant-blue/60 animate-spin duration-[15000ms]"></div>
                                
                                <div v-if="storms?.length > 0" class="absolute inset-0 animate-ping opacity-20 rounded-full bg-red-500"></div>
                                <div class="text-center relative z-10">
                                    <p class="text-[8px] font-black text-vibrant-blue/60 uppercase tracking-[0.3em] mb-1">THREAT_LEVEL</p>
                                    <h1 class="text-5xl font-black italic text-white drop-shadow-[0_0_15px_rgba(0,136,255,0.5)] leading-none">{{ riskScore }}%</h1>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Corners UI -->
                    <div class="absolute top-6 left-6 p-4 border-l border-t border-white/10 opacity-40">
                         <p class="text-[10px] font-black tracking-widest uppercase text-vibrant-blue mb-1">INTELLIGENCE_DIAGNOSTICS</p>
                        <p class="text-[8px] font-black tracking-widest uppercase">Sector_Grid: Alpha-09</p>
                        <p class="text-[8px] font-black tracking-widest uppercase">Targeting_Active</p>
                    </div>
                    <div class="absolute bottom-6 left-6 p-4 border-l border-b border-white/10 opacity-40">
                        <p class="text-[7px] font-bold text-vibrant-blue uppercase tracking-widest mb-1">LIVE_DATA_STREAM</p>
                        <div class="space-y-0.5">
                            <p class="text-[6px] font-mono text-white/40 italic">REQ_SENT: 18:22:45... OK</p>
                            <p class="text-[6px] font-mono text-white/40 italic">IMG_INGEST: HIMAWARI_9... LATEST</p>
                        </div>
                    </div>
                    <div class="absolute bottom-6 right-6 p-4 border-r border-b border-vibrant-blue/10">
                        <p class="text-[8px] font-black text-vibrant-blue tracking-widest uppercase">Encryption_Type: AES-256</p>
                    </div>
                </div>

                <!-- Status Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div v-for="(val, key) in weatherData" :key="key" 
                        class="bg-white/[0.02] border border-white/5 p-6 hover:bg-white/[0.04] transition-all">
                        <p class="text-[9px] font-black text-white/20 uppercase tracking-widest mb-1">{{ key }}</p>
                        <p class="text-2xl font-black font-mono">{{ val }}{{ key === 'temp' ? '°C' : key === 'wind' ? 'km/h' : '%' }}</p>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Mission Vitals -->
            <div class="col-span-12 lg:col-span-4 space-y-8 h-full">
                <div class="bg-[#050508] border border-white/5 p-8 h-full">
                    <h3 class="text-sm font-black uppercase tracking-widest mb-8 flex items-center">
                        <span class="w-2 h-2 bg-vibrant-blue mr-3 rounded-full animate-pulse shadow-[0_0_10px_#0088ff]"></span>
                        ORBITAL_ASSETS_LIVE
                    </h3>

                    <div class="space-y-6">
                        <div v-for="sat in satellites" :key="sat.name" 
                            @click="selectSatellite(sat)"
                            :class="selectedSatellite?.norad_id === sat.norad_id ? 'border-vibrant-blue bg-vibrant-blue/10' : 'bg-white/[0.03] border-white/5'"
                            class="p-4 border group relative overflow-hidden cursor-pointer hover:border-vibrant-blue/50 transition-all active:scale-[0.98]">
                            <div class="flex justify-between items-start mb-2 relative z-10">
                                <div>
                                    <h5 class="text-xs font-black uppercase italic" :class="selectedSatellite?.norad_id === sat.norad_id ? 'text-vibrant-blue' : ''">{{ sat.name }}</h5>
                                    <p class="text-[9px] text-white/20 font-mono tracking-widest">{{ sat.orbit || 'LEO' }} // {{ sat.status?.toUpperCase() || 'ACTIVE' }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] font-black text-vibrant-green font-mono">{{ sat.battery || '98%' }}</span>
                                </div>
                            </div>
                            
                            <!-- Battery Bar Visualization -->
                            <div class="h-[2px] w-full bg-white/5 relative z-10">
                                <div class="h-full bg-vibrant-blue transition-all duration-1000" :style="{ width: sat.battery || '98%' }"></div>
                            </div>

                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/[0.02] to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                        </div>

                        <!-- Active Alerts Log -->
                        <div class="mt-12">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="text-[9px] font-black text-vibrant-blue uppercase tracking-[0.3em]">Critical_Alerts</h3>
                                <Link href="#" class="text-[8px] font-black underline opacity-40 hover:opacity-100 transition-opacity">VIEW_HISTORY</Link>
                            </div>
                            <div class="space-y-4">
                                <div v-for="storm in storms" :key="storm.id" class="flex items-start space-x-4 p-3 border border-red-500/10 bg-red-500/5">
                                    <div class="w-1 h-8 bg-red-500"></div>
                                    <div>
                                        <p class="text-[10px] font-black text-red-500 uppercase">{{ storm.name }}_DETECTION</p>
                                        <p class="text-[9px] text-white/40 mt-0.5">{{ storm.latitude }}, {{ storm.longitude }} - Wind: {{ storm.max_wind_speed }}km/h</p>
                                    </div>
                                </div>
                                <div v-if="storms?.length === 0" class="p-6 text-center border border-white/5 opacity-20 uppercase text-[8px] tracking-widest font-black">
                                    NO_CYCOGENESIS_DETECTED
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Satellite Intelligence HUD (Slide-over) -->
        <Transition
            enter-active-class="transition duration-500 ease-out"
            enter-from-class="translate-x-full opacity-0"
            enter-to-class="translate-x-0 opacity-100"
            leave-active-class="transition duration-300 ease-in"
            leave-from-class="translate-x-0 opacity-100"
            leave-to-class="translate-x-full opacity-0"
        >
            <div v-if="selectedSatellite" class="fixed top-24 right-8 bottom-32 w-96 bg-black/90 backdrop-blur-2xl border border-vibrant-blue/50 shadow-[0_0_50px_rgba(0,136,255,0.2)] z-[60] flex flex-col overflow-hidden">
                <!-- Header -->
                <div class="p-6 border-b border-vibrant-blue/20 bg-vibrant-blue/10 flex justify-between items-center relative overflow-hidden">
                    <div class="relative z-10">
                        <p class="text-[8px] font-black text-vibrant-blue uppercase tracking-[0.4em] mb-1">Satellite_Intel</p>
                        <h3 class="text-xl font-black uppercase tracking-tighter italic leading-none">{{ selectedSatellite.name }}</h3>
                    </div>
                    <button @click="selectedSatellite = null" class="relative z-10 text-white/40 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <!-- Background Decoration -->
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-vibrant-blue/5 rounded-full blur-2xl"></div>
                </div>

                <!-- HUD Content -->
                <div class="flex-1 overflow-y-auto custom-scrollbar p-8 space-y-8 relative">
                    <!-- Loading Overlay -->
                    <div v-if="isLoadingSat" class="absolute inset-0 z-20 bg-black/60 backdrop-blur-md flex flex-col items-center justify-center space-y-4 animate-in fade-in duration-300">
                        <div class="w-12 h-12 border-2 border-vibrant-blue border-t-transparent rounded-full animate-spin"></div>
                        <p class="text-[9px] font-black text-vibrant-blue uppercase tracking-[0.4em] animate-pulse">ACQUIRING_DATA_LINK...</p>
                    </div>

                    <!-- Technical Profile -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 bg-white/[0.03] border border-white/5">
                            <p class="text-[8px] font-black text-white/20 uppercase mb-1">NORAD_ID</p>
                            <p class="text-sm font-black text-vibrant-blue font-mono">{{ selectedSatellite.norad_id }}</p>
                        </div>
                        <div class="p-4 bg-white/[0.03] border border-white/5">
                            <p class="text-[8px] font-black text-white/20 uppercase mb-1">TX_STATUS</p>
                            <p class="text-sm font-black text-vibrant-green italic uppercase">{{ selectedSatellite.status || 'ACTIVE' }}</p>
                        </div>
                    </div>

                    <!-- Precise Location Vector -->
                    <div class="space-y-4">
                        <h4 class="text-[10px] font-black text-white/40 uppercase tracking-widest border-l-2 border-vibrant-blue pl-3">Ground_Impact_Intelligence</h4>
                        <div class="p-5 bg-vibrant-blue/5 border border-vibrant-blue/20 rounded-xl relative group">
                            <div class="flex items-center space-x-4 mb-4">
                                <MapPin class="w-6 h-6 text-vibrant-blue animate-pulse" />
                                <div>
                                    <p class="text-[8px] font-black text-vibrant-blue uppercase tracking-widest mb-1">Currently_Overflying</p>
                                    <p class="text-sm font-black text-white uppercase italic">{{ selectedSatellite.location || 'INTELLERNATIONAL_WATERS' }}</p>
                                </div>
                            </div>
                            <div class="flex justify-between font-mono text-[10px] font-bold text-white/40 border-t border-white/5 pt-3">
                                <div class="flex flex-col">
                                    <span class="text-[8px] opacity-50 mb-0.5">LATITUDE</span>
                                    <span class="text-white">{{ selectedSatellite.position?.lat?.toFixed(6) || '0.000000' }}</span>
                                </div>
                                <div class="flex flex-col text-right">
                                    <span class="text-[8px] opacity-50 mb-0.5">LONGITUDE</span>
                                    <span class="text-white">{{ selectedSatellite.position?.lng?.toFixed(6) || '0.000000' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Telemetry -->
                    <div class="space-y-5">
                        <h4 class="text-[10px] font-black text-white/40 uppercase tracking-widest border-l-2 border-vibrant-blue pl-3">Live_Telemetry_Stream</h4>
                        <div class="grid grid-cols-1 gap-1">
                            <div class="flex justify-between items-center p-4 bg-white/[0.02] border border-white/5">
                                <span class="text-[9px] text-white/30 font-bold uppercase">Altitude</span>
                                <span class="text-sm font-black text-white">{{ (selectedSatellite.telemetry?.altitude || 550).toLocaleString() }} <span class="text-[10px] opacity-20 ml-1">KM</span></span>
                            </div>
                            <div class="flex justify-between items-center p-4 bg-white/[0.02] border border-white/5">
                                <span class="text-[9px] text-white/30 font-bold uppercase">Orbital Velocity</span>
                                <span class="text-sm font-black text-vibrant-green">{{ (selectedSatellite.telemetry?.velocity || 7.6).toFixed(3) }} <span class="text-[10px] opacity-20 ml-1">KM/S</span></span>
                            </div>
                        </div>
                    </div>

                    <!-- Orbital Modules -->
                    <div class="space-y-4">
                         <h4 class="text-[10px] font-black text-white/40 uppercase tracking-widest border-l-2 border-vibrant-blue pl-3">Orbital_Module_Status</h4>
                        <div class="space-y-2">
                            <div v-for="mod in (selectedSatellite.modules || [])" :key="mod.id" 
                                class="flex items-center justify-between p-4 bg-white/[0.03] border border-white/5 hover:bg-white/[0.05] transition-all">
                                <div class="flex items-center space-x-4">
                                    <div class="w-2 h-2 rounded-full shadow-[0_0_10px_currentColor] animate-pulse" :class="mod.status === 'OPERATIONAL' || mod.status === 'ONLINE' ? 'text-vibrant-green bg-vibrant-green' : 'text-red-500 bg-red-500'"></div>
                                    <span class="text-[10px] font-black text-white/80 uppercase">{{ mod.name }}</span>
                                </div>
                                <span class="text-[8px] font-mono text-white/20 uppercase tracking-widest">{{ mod.id }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action -->
                    <div class="pt-4">
                        <Link :href="route('weather-map')" class="block w-full py-4 bg-vibrant-blue text-white text-[10px] font-black uppercase tracking-[0.3em] text-center hover:bg-vibrant-blue/80 transition-all shadow-[0_0_30px_rgba(0,136,255,0.2)]">
                            VIEW_ON_GLOBAL_MAP
                        </Link>
                    </div>
                </div>
            </div>
        </Transition>
    </UserLayout>
</template>

<style scoped>
@keyframes scan {
    from { top: 0; }
    to { top: 100%; }
}
</style>

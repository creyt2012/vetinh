<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue';
import axios from 'axios';
import { MapPin, Zap, Thermometer, Radio, Activity } from 'lucide-vue-next';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

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
const telemetryData = ref(null);
const isLoadingSat = ref(false);
const healthHistory = ref([]);
const chartRef = ref(null);
const healthChart = null;
let pollTimer = null;

const systemHealth = ref({});
const missionQuota = ref({
    used: 450,
    total: 1000,
    percentage: 45
});

const fetchSystemHealth = async () => {
    try {
        const res = await axios.get('/api/v1/health/system', {
            headers: { 'X-API-KEY': 'vetinh_dev_key_123' }
        });
        systemHealth.value = res.data.data;
    } catch (e) {
        console.error('Failed to fetch system health', e);
    }
};

const fetchMissionQuota = async () => {
    // In a real app, fetch from backend. For now, simulate.
    missionQuota.value = {
        used: 612,
        total: 1000,
        percentage: 61.2
    };
};

const fetchSatelliteData = async (noradId) => {
    try {
        const res = await axios.get(`/api/user/satellite/${noradId}?t=${Date.now()}`);
        telemetryData.value = res.data.data;
    } catch (e) {
        console.error('Failed to fetch telemetry', e);
    }
};

const fetchSatelliteHistory = async (noradId) => {
    try {
        const res = await axios.get(`/api/user/satellite/${noradId}/history`);
        healthHistory.value = res.data.data;
        initChart();
    } catch (e) {
        console.error('Failed to fetch history', e);
    }
};

const initChart = () => {
    if (!chartRef.value || healthHistory.value.length === 0) return;
    
    if (healthChart) {
        healthChart.destroy();
    }

    const ctx = chartRef.value.getContext('2d');
    healthChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: healthHistory.value.map(d => d.time),
            datasets: [
                {
                    label: 'Power (%)',
                    data: healthHistory.value.map(d => d.power),
                    borderColor: '#0088ff',
                    backgroundColor: 'rgba(0, 136, 255, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Thermal (C)',
                    data: healthHistory.value.map(d => d.thermal),
                    borderColor: '#ff3366',
                    borderWidth: 1,
                    tension: 0.4,
                    pointRadius: 0
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { display: false },
                y: { 
                    grid: { color: 'rgba(255,255,255,0.05)' },
                    ticks: { color: 'rgba(255,255,255,0.3)', font: { size: 8 } }
                }
            }
        }
    });
};

const selectSatellite = async (sat) => {
    if (pollTimer) clearInterval(pollTimer);
    
    selectedSatellite.value = sat;
    isLoadingSat.value = true;
    
    await fetchSatelliteData(sat.norad_id);
    await fetchSatelliteHistory(sat.norad_id);
    
    isLoadingSat.value = false;
    
    // Start 1Hz polling for the selected satellite
    pollTimer = setInterval(() => fetchSatelliteData(sat.norad_id), 1000);
};

onMounted(() => {
    fetchSystemHealth();
    fetchMissionQuota();
});

onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
    if (healthChart) healthChart.destroy();
});
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
                    <div class="absolute inset-0 pointer-events-none opacity-20">
                        <div class="w-full h-1 bg-vibrant-blue/20 absolute top-0 animate-[scan_4s_linear_infinite]"></div>
                        <div class="absolute inset-0 flex items-center justify-center opacity-10 grayscale scale-150">
                             <div class="w-full h-full bg-[url('https://unpkg.com/three-globe/example/img/earth-blue-marble.jpg')] bg-cover opacity-30"></div>
                        </div>
                    </div>
                    
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="text-center relative">
                            <div class="absolute -inset-12 border border-white/5 rounded-full pointer-events-none"></div>
                            <div class="absolute -inset-20 border border-white/5 rounded-full opacity-30 pointer-events-none"></div>
                            
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

                    <div class="absolute top-6 left-6 p-4 border-l border-t border-white/10 opacity-40">
                         <p class="text-[10px] font-black tracking-widest uppercase text-vibrant-blue mb-1">INTELLIGENCE_DIAGNOSTICS</p>
                        <p class="text-[8px] font-black tracking-widest uppercase">Sector_Grid: Alpha-09</p>
                        <p class="text-[8px] font-black tracking-widest uppercase">Targeting_Active</p>
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

                <!-- NEW: System Vitals & Quota -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-vibrant-blue/5 border border-vibrant-blue/20 p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-[10px] font-black text-vibrant-blue uppercase tracking-[0.2em]">Mission_Quota_Usage</h4>
                            <span class="text-[10px] font-mono text-white/40">{{ missionQuota.used }} / {{ missionQuota.total }} MB</span>
                        </div>
                        <div class="h-1.5 bg-white/5 rounded-full overflow-hidden">
                            <div class="h-full bg-vibrant-blue transition-all duration-1000" :style="{ width: missionQuota.percentage + '%' }"></div>
                        </div>
                        <p class="text-[8px] font-black text-white/20 uppercase tracking-widest mt-2 italic">Auto-refreshing daily...</p>
                    </div>

                    <div class="bg-black/40 border border-white/5 p-6 flex items-center justify-between">
                        <div>
                            <h4 class="text-[10px] font-black text-white/40 uppercase tracking-[0.2em] mb-1">API_Gateway_Latency</h4>
                            <p class="text-2xl font-black font-mono text-vibrant-green">{{ systemHealth['API Gateway']?.latency_ms || 12 }}<span class="text-[10px] opacity-40">MS</span></p>
                        </div>
                        <div class="text-right">
                             <div class="w-1.5 h-1.5 rounded-full bg-vibrant-green mx-auto mb-1 animate-pulse"></div>
                             <span class="text-[8px] font-black text-vibrant-green uppercase tracking-widest">Stable</span>
                        </div>
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
                                    <span class="text-[10px] font-black text-vibrant-green font-mono">98%</span>
                                </div>
                            </div>
                            
                            <div class="h-[2px] w-full bg-white/5 relative z-10">
                                <div class="h-full bg-vibrant-blue transition-all duration-1000" style="width: 98%"></div>
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
            <div v-if="selectedSatellite && telemetryData" class="fixed top-24 right-8 bottom-8 w-[450px] bg-black/95 backdrop-blur-2xl border border-vibrant-blue/50 shadow-[0_0_50px_rgba(0,136,255,0.2)] z-[60] flex flex-col overflow-hidden">
                <!-- Header -->
                <div class="p-6 border-b border-vibrant-blue/20 bg-vibrant-blue/10 flex justify-between items-center relative overflow-hidden">
                    <div class="relative z-10">
                        <p class="text-[8px] font-black text-vibrant-blue uppercase tracking-[0.4em] mb-1">Mission_Asset_Intel</p>
                        <h3 class="text-xl font-black uppercase tracking-tighter italic leading-none">{{ telemetryData.metadata.name }}</h3>
                    </div>
                    <button @click="selectedSatellite = null" class="relative z-10 text-white/40 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>

                <!-- HUD Content -->
                <div class="flex-1 overflow-y-auto custom-scrollbar p-8 space-y-8 relative">
                    <!-- LOADING OVERLAY -->
                    <div v-if="isLoadingSat" class="absolute inset-0 z-20 bg-black/80 flex flex-col items-center justify-center space-y-4">
                        <div class="w-12 h-12 border-2 border-vibrant-blue border-t-transparent rounded-full animate-spin"></div>
                        <p class="text-[9px] font-black text-vibrant-blue uppercase tracking-widest">ESTABLISHING_DATA_LINK</p>
                    </div>

                    <!-- ORBITAL VECTOR -->
                    <div class="grid grid-cols-3 gap-2">
                        <div class="p-3 bg-white/5 border border-white/10">
                            <p class="text-[7px] text-white/40 uppercase mb-1">LATITUDE</p>
                            <p class="text-xs font-mono font-black text-white">{{ telemetryData.orbital.coordinates.lat.toFixed(4) }}°</p>
                        </div>
                        <div class="p-3 bg-white/5 border border-white/10">
                            <p class="text-[7px] text-white/40 uppercase mb-1">LONGITUDE</p>
                            <p class="text-xs font-mono font-black text-white">{{ telemetryData.orbital.coordinates.lng.toFixed(4) }}°</p>
                        </div>
                        <div class="p-3 bg-white/5 border border-white/10">
                            <p class="text-[7px] text-white/40 uppercase mb-1">ALTITUDE</p>
                            <p class="text-xs font-mono font-black text-vibrant-blue">{{ telemetryData.orbital.coordinates.alt.toFixed(0) }}km</p>
                        </div>
                    </div>

                    <!-- MISSION VITALS (HEALTH) -->
                    <div class="space-y-4">
                        <h4 class="text-[10px] font-black text-vibrant-blue uppercase tracking-widest border-l-2 border-vibrant-blue pl-3">Subsystem_Health_Vector</h4>
                        <div class="grid grid-cols-2 gap-px bg-white/5 border border-white/10">
                            <div class="p-4 bg-black flex items-center space-x-3">
                                <Zap class="w-4 h-4 text-vibrant-yellow" />
                                <div>
                                    <p class="text-[7px] text-white/20 uppercase">Power_Bus</p>
                                    <p class="text-sm font-black text-white">{{ telemetryData.subsystems.power_bus }}</p>
                                </div>
                            </div>
                            <div class="p-4 bg-black flex items-center space-x-3">
                                <Thermometer class="w-4 h-4 text-vibrant-red" />
                                <div>
                                    <p class="text-[7px] text-white/20 uppercase">Thermal_CPU</p>
                                    <p class="text-sm font-black text-white">{{ telemetryData.subsystems.thermal }}</p>
                                </div>
                            </div>
                            <div class="p-4 bg-black flex items-center space-x-3">
                                <Radio class="w-4 h-4 text-vibrant-blue" />
                                <div>
                                    <p class="text-[7px] text-white/20 uppercase">Link_Signal</p>
                                    <p class="text-sm font-black text-white">{{ telemetryData.subsystems.comm_link }}</p>
                                </div>
                            </div>
                            <div class="p-4 bg-black flex items-center space-x-3">
                                <Activity class="w-4 h-4 text-vibrant-green" />
                                <div>
                                    <p class="text-[7px] text-white/20 uppercase">Orientation</p>
                                    <p class="text-sm font-black text-white">{{ telemetryData.subsystems.attitude }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SATELLITE HEALTH TREND (CHART) -->
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <h4 class="text-[10px] font-black text-white/40 uppercase tracking-widest border-l-2 border-white/20 pl-3">24H_Performance_Metrics</h4>
                            <span class="text-[8px] font-mono text-vibrant-blue">HISTORICAL_LOG</span>
                        </div>
                        <div class="h-40 bg-white/[0.02] border border-white/5 p-4 relative">
                            <canvas ref="chartRef"></canvas>
                        </div>
                    </div>

                    <!-- SCIENTIFIC PAYLOAD -->
                    <div class="space-y-4">
                        <h4 class="text-[10px] font-black text-white/40 uppercase tracking-widest border-l-2 border-white/20 pl-3">Atmospheric_Observation</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 border border-white/5 bg-white/[0.01]">
                                <p class="text-[7px] text-white/30 uppercase mb-1">Local_Temp</p>
                                <p class="text-lg font-black">{{ telemetryData.scientific.atmosphere.temperature }}°C</p>
                            </div>
                            <div class="p-4 border border-white/5 bg-white/[0.01]">
                                <p class="text-[7px] text-white/30 uppercase mb-1">Pressure</p>
                                <p class="text-lg font-black">{{ telemetryData.scientific.atmosphere.pressure }}hPa</p>
                            </div>
                        </div>
                    </div>

                    <!-- ACTION -->
                    <div class="pt-4">
                        <Link :href="route('weather.map')" class="block w-full py-4 bg-vibrant-blue text-white text-[10px] font-black uppercase tracking-[0.3em] text-center hover:bg-vibrant-blue/80 transition-all">
                            TACTICAL_MAP_REDIRECTION
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

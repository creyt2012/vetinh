<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

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
                    
                        <div class="text-center relative">
                            <!-- Tactical Ring Breakdown -->
                            <div class="absolute -inset-16 border border-white/5 rounded-full pointer-events-none"></div>
                            <div class="absolute -inset-24 border border-white/5 rounded-full opacity-50 pointer-events-none"></div>
                            
                            <!-- Detailed Metrics around the circle -->
                            <div class="absolute -top-20 left-1/2 -translate-x-1/2 whitespace-nowrap">
                                <p class="text-[7px] font-black text-vibrant-blue tracking-[.3em] uppercase">SYSTEM_INTEGRITY: 100%</p>
                            </div>
                            <div class="absolute top-1/2 -left-32 -translate-y-1/2 text-right">
                                <p class="text-[7px] font-black text-white/40 tracking-widest uppercase mb-1">STORM_THREAT</p>
                                <p class="text-[10px] font-black" :class="storms?.length > 0 ? 'text-red-500' : 'text-vibrant-green'">{{ storms?.length > 0 ? 'ACTIVE' : 'NOMINAL' }}</p>
                            </div>
                            <div class="absolute top-1/2 -right-32 -translate-y-1/2 text-left">
                                <p class="text-[7px] font-black text-white/40 tracking-widest uppercase mb-1">ASSET_LATENCY</p>
                                <p class="text-[10px] font-black text-vibrant-blue">14MS</p>
                            </div>

                            <div class="w-48 h-48 rounded-full border border-vibrant-blue/20 flex items-center justify-center relative bg-black/40">
                                <!-- Pulsing Ring instead of spinning -->
                                <div class="absolute inset-0 rounded-full border-2 border-vibrant-blue/40 animate-pulse"></div>
                                <div class="absolute inset-4 rounded-full border border-vibrant-blue/10 border-t-vibrant-blue/60 animate-spin duration-[15000ms]"></div>
                                
                                <div v-if="storms?.length > 0" class="absolute inset-0 animate-ping opacity-20 rounded-full bg-red-500"></div>
                                <div class="text-center relative z-10">
                                    <p class="text-[9px] font-black text-vibrant-blue uppercase tracking-[0.3em] mb-1">THREAT_LEVEL</p>
                                    <h1 class="text-6xl font-black italic shadow-vibrant-blue/40 drop-shadow-[0_0_15px_rgba(0,136,255,0.5)]" :class="riskScore > 50 ? 'text-red-500' : 'text-white'">{{ riskScore }}%</h1>
                                </div>
                            </div>
                        </div>

                    <!-- Corners UI -->
                    <div class="absolute top-6 left-6 p-4 border-l border-t border-white/10 opacity-40">
                         <p class="text-[10px] font-black tracking-widest uppercase text-vibrant-blue mb-1">INTELLIGENCE_DIAGNOSTICS</p>
                        <p class="text-[8px] font-black tracking-widest uppercase">Sector_Grid: Alpha-09</p>
                        <p class="text-[8px] font-black tracking-widest uppercase">Targeting_Active</p>
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
                        <div v-for="sat in satellites" :key="sat.name" class="p-4 bg-white/[0.03] border border-white/5 group relative overflow-hidden">
                            <div class="flex justify-between items-start mb-2 relative z-10">
                                <div>
                                    <h5 class="text-xs font-black uppercase italic">{{ sat.name }}</h5>
                                    <p class="text-[9px] text-white/20 font-mono tracking-widest">{{ sat.orbit }} // {{ sat.status.toUpperCase() }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] font-black text-vibrant-green font-mono">{{ sat.battery }}</span>
                                </div>
                            </div>
                            
                            <!-- Battery Bar Visualization -->
                            <div class="h-[2px] w-full bg-white/5 relative z-10">
                                <div class="h-full bg-vibrant-blue transition-all duration-1000" :style="{ width: sat.battery }"></div>
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
    </UserLayout>
</template>

<style scoped>
@keyframes scan {
    from { top: 0; }
    to { top: 100%; }
}
</style>

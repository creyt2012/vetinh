<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { Activity, Cpu, Server, Database, Globe } from 'lucide-vue-next';

const healthData = ref(null);
const isLoading = ref(true);

const fetchHealth = async () => {
    try {
        const res = await axios.get('/api/v1/health/system', {
            headers: { 'X-API-KEY': 'vetinh_dev_key_123' }
        });
        healthData.value = res.data.data;
    } catch (e) {
        console.error('Failed to fetch health data', e);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    fetchHealth();
    setInterval(fetchHealth, 10000); // 10s updates
});
</script>

<template>
    <Head title="System Diagnostics" />

    <UserLayout>
        <div class="space-y-10">
            <!-- Header -->
            <div class="flex justify-between items-end">
                <div>
                    <h2 class="text-4xl font-black uppercase tracking-tighter italic leading-none text-white">SYSTEM_DIAGNOSTICS</h2>
                    <p class="text-xs text-vibrant-blue uppercase tracking-[0.4em] font-black mt-2">Core Infrastructure Health & API Gateway Latency</p>
                </div>
                <div class="text-right">
                    <p class="text-[9px] font-black text-white/20 uppercase tracking-widest">Global_System_Uptime</p>
                    <p class="text-2xl font-black italic text-vibrant-green">99.998%</p>
                </div>
            </div>

            <!-- Health Grid -->
            <div class="grid grid-cols-12 gap-8">
                <!-- Services Status -->
                <div class="col-span-12 lg:col-span-8 space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="(metric, service) in healthData" :key="service" 
                            class="bg-[#050508] border border-white/5 p-6 flex flex-col justify-between group hover:border-vibrant-blue/30 transition-all">
                            <div class="flex justify-between items-center mb-6">
                                <div class="flex items-center space-x-3">
                                    <div class="p-2 bg-white/5 border border-white/10 group-hover:border-vibrant-blue/20 transition-colors">
                                        <component :is="service.includes('DB') ? Database : service.includes('API') ? Globe : Server" class="w-5 h-5 opacity-60" />
                                    </div>
                                    <h4 class="text-sm font-black uppercase tracking-widest leading-none">{{ service.replace('_', ' ') }}</h4>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 rounded-full" :class="metric.status === 'HEALTHY' ? 'bg-vibrant-green animate-pulse' : 'bg-vibrant-yellow'"></div>
                                    <span class="text-[9px] font-black">{{ metric.status }}</span>
                                </div>
                            </div>
                            
                            <div class="flex justify-between items-end">
                                <div>
                                    <p class="text-[8px] font-black text-white/20 uppercase tracking-widest mb-1">Response_Time</p>
                                    <p class="text-2xl font-black font-mono">{{ metric.latency_ms }}<span class="text-xs opacity-30">ms</span></p>
                                </div>
                                <div class="w-32 h-8">
                                    <svg viewBox="0 0 100 20" class="w-full h-full text-vibrant-blue opacity-20">
                                        <path d="M0 10 L10 12 L20 8 L30 15 L40 5 L50 12 L60 8 L70 15 L80 10 L90 12 L100 10" fill="none" stroke="currentColor" stroke-width="2" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resource HUD -->
                    <div class="bg-black/40 border border-white/5 p-8 relative overflow-hidden">
                        <h3 class="text-[10px] font-black text-vibrant-blue uppercase tracking-[0.3em] mb-8">COMPUTE_CLUSTER_LOAD</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-[9px] font-black text-white/40 uppercase">CPU_UTILIZATION</span>
                                    <span class="text-[10px] font-black">42%</span>
                                </div>
                                <div class="h-1 bg-white/5 rounded-full overflow-hidden">
                                    <div class="h-full bg-vibrant-blue" style="width: 42%"></div>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-[9px] font-black text-white/40 uppercase">MEMORY_POOL</span>
                                    <span class="text-[10px] font-black">68%</span>
                                </div>
                                <div class="h-1 bg-white/5 rounded-full overflow-hidden">
                                    <div class="h-full bg-vibrant-blue" style="width: 68%"></div>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-[9px] font-black text-white/40 uppercase">NETWORK_IO</span>
                                    <span class="text-[10px] font-black">1.2 GB/S</span>
                                </div>
                                <div class="h-1 bg-white/5 rounded-full overflow-hidden">
                                    <div class="h-full bg-vibrant-blue" style="width: 85%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Diagnostics Sidebar -->
                <div class="col-span-12 lg:col-span-4 space-y-8">
                    <div class="bg-[#050508] border border-white/5 p-8">
                        <h3 class="text-sm font-black uppercase tracking-widest mb-8 flex items-center">
                            <Cpu class="w-4 h-4 text-vibrant-blue mr-3" />
                            NETWORK_NODES
                        </h3>
                        <div class="space-y-6">
                            <div v-for="node in ['SINGAPORE-01', 'TOKYO-V3', 'LONDON-AWS', 'VIRGINIA-US']" :key="node"
                                class="flex items-center justify-between p-3 border border-white/5 bg-white/[0.02]">
                                <span class="text-[9px] font-black text-white/60 uppercase tracking-widest">{{ node }}</span>
                                <span class="text-[8px] font-mono text-vibrant-green">ONLINE</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-vibrant-blue/5 border border-vibrant-blue/20 p-8 flex flex-col items-center text-center">
                        <div class="w-16 h-16 rounded-full border-2 border-vibrant-blue flex items-center justify-center mb-6 animate-pulse">
                            <Activity class="w-8 h-8 text-vibrant-blue" />
                        </div>
                        <h4 class="text-xs font-black uppercase tracking-widest mb-2">Diagnostic_Sync: ACTIVE</h4>
                        <p class="text-[9px] text-white/40 uppercase tracking-widest">Watching for telemetry dropouts...</p>
                    </div>
                </div>
            </div>
        </div>
    </UserLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const props = defineProps({
    stats: Object,
    satellite_distribution: Object,
    usage_trend: Object,
    recent_keys: Array,
    recent_logs: Array
});

const formatTime = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
};

const typeChartRef = ref(null);
const trendChartRef = ref(null);
const statusChartRef = ref(null);

onMounted(() => {
    // 1. Orbital Asset Distribution (Doughnut)
    new Chart(typeChartRef.value, {
        type: 'doughnut',
        data: {
            labels: Object.keys(props.satellite_distribution.by_type),
            datasets: [{
                data: Object.values(props.satellite_distribution.by_type),
                backgroundColor: ['#0088ff', '#00ffaa', '#ffaa00', '#ff0055', '#7a00ff'],
                borderWidth: 0,
                hoverOffset: 15
            }]
        },
        options: {
            cutout: '80%',
            plugins: {
                legend: { display: false }
            }
        }
    });

    // 2. Data Throughput Trend (Line)
    new Chart(trendChartRef.value, {
        type: 'line',
        data: {
            labels: props.usage_trend.labels,
            datasets: [{
                label: 'Throughput',
                data: props.usage_trend.data,
                borderColor: '#0088ff',
                backgroundColor: 'rgba(0, 136, 255, 0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#0088ff',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { display: false },
                x: { grid: { display: false }, border: { display: false }, ticks: { color: 'rgba(255,255,255,0.3)', font: { size: 9 } } }
            },
            plugins: { legend: { display: false } }
        }
    });

    // 3. Mission Status Matrix (Bar)
    new Chart(statusChartRef.value, {
        type: 'bar',
        data: {
            labels: Object.keys(props.satellite_distribution.by_status),
            datasets: [{
                data: Object.values(props.satellite_distribution.by_status),
                backgroundColor: 'rgba(0, 255, 170, 0.2)',
                borderColor: '#00ffaa',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            scales: {
                x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { display: false } },
                y: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.5)', font: { size: 10, weight: '900' } } }
            },
            plugins: { legend: { display: false } }
        }
    });
});

</script>

<template>
    <AdminLayout>
        <template #header>TACTICAL_INTELLIGENCE_CENTER</template>
        <Head title="Intelligence Dashboard - Mission Control" />

        <div class="space-y-8 pb-20">
            <!-- Global Telemetry Row -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                <div v-for="(val, label) in stats" :key="label" 
                    class="bg-[#08080C] border border-white/5 p-6 relative overflow-hidden group hover:border-vibrant-blue/30 transition-all duration-500">
                    <p class="text-[8px] font-black text-white/20 uppercase tracking-[0.4em] mb-1 italic">{{ label.replace(/_/g, '.') }}</p>
                    <h3 class="text-3xl font-black font-outfit text-white tabular-nums group-hover:text-vibrant-blue transition-colors">{{ val }}</h3>
                    <div class="absolute bottom-0 left-0 w-full h-[1px] bg-gradient-to-r from-transparent via-vibrant-blue/20 to-transparent"></div>
                </div>
            </div>

            <!-- Main Intelligence Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                
                <!-- Orbital Density Analysis -->
                <div class="lg:col-span-4 bg-[#08080C] border border-white/5 p-8 relative group overflow-hidden">
                    <div class="flex justify-between items-start mb-10">
                        <div>
                            <p class="text-[9px] font-black text-vibrant-blue uppercase tracking-[0.5em] mb-2">Sector_Analysis</p>
                            <h3 class="text-xl font-black uppercase italic tracking-tighter">ORBITAL_ASSET_DENSITY</h3>
                        </div>
                        <div class="w-12 h-12 border border-vibrant-blue/20 rounded-full flex items-center justify-center animate-spin-slow">
                             <div class="w-1 h-1 bg-vibrant-blue rounded-full"></div>
                        </div>
                    </div>

                    <div class="relative h-64 flex items-center justify-center">
                        <canvas ref="typeChartRef"></canvas>
                        <!-- Center HUD Overlay -->
                        <div class="absolute flex flex-col items-center justify-center text-center pointer-events-none">
                            <p class="text-[8px] font-black text-white/20 uppercase tracking-widest">Global</p>
                            <p class="text-3xl font-black font-outfit text-white">{{ stats.total_satellites }}</p>
                            <p class="text-[8px] font-black text-vibrant-blue uppercase tracking-widest">Units</p>
                        </div>
                    </div>

                    <div class="mt-8 grid grid-cols-2 gap-4">
                        <div v-for="(count, type) in satellite_distribution.by_type" :key="type" class="flex items-center space-x-3 p-3 bg-white/[0.02] border border-white/5">
                            <div class="w-1.5 h-1.5 bg-vibrant-blue shadow-[0_0_8px_#0088ff]"></div>
                            <span class="text-[9px] font-black text-white/40 uppercase tracking-widest truncate">{{ type }}</span>
                            <span class="flex-1 text-right text-[10px] font-black font-mono text-vibrant-blue">{{ count }}</span>
                        </div>
                    </div>
                </div>

                <!-- Signal Throughput Map -->
                <div class="lg:col-span-8 space-y-6">
                    <div class="bg-[#08080C] border border-white/5 p-8 relative overflow-hidden">
                         <div class="flex justify-between items-end mb-8">
                            <div>
                                <p class="text-[9px] font-black text-vibrant-blue uppercase tracking-[.5em] mb-2">Signal_Propagation</p>
                                <h3 class="text-xl font-black uppercase italic tracking-tighter">DATA_THROUGHPUT_MAP</h3>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-mono font-black text-vibrant-green tracking-widest">+12.4% <span class="text-white/20 ml-2">THRU_PEAK</span></p>
                            </div>
                        </div>
                        <div class="h-64 relative">
                            <canvas ref="trendChartRef"></canvas>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Mission Status Bar -->
                        <div class="bg-[#08080C] border border-white/5 p-6">
                            <p class="text-[9px] font-black text-white/30 uppercase tracking-[.4em] mb-6">Asset_Readiness_Index</p>
                            <div class="h-40">
                                <canvas ref="statusChartRef"></canvas>
                            </div>
                        </div>

                        <!-- Recent Key HUD -->
                        <div class="bg-[#08080C] border border-white/5 p-6">
                            <p class="text-[9px] font-black text-white/30 uppercase tracking-[.4em] mb-6">Security_Provisioning_History</p>
                            <div class="space-y-3">
                                <div v-for="key in recent_keys" :key="key.id" class="flex justify-between items-center p-3 border border-white/5 hover:bg-white/[0.02] transition-colors group">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-1 h-1 rounded-full animate-pulse" :class="key.is_active ? 'bg-vibrant-green shadow-[0_0_5px_#00ffaa]' : 'bg-red-500'"></div>
                                        <p class="text-[9px] font-black uppercase text-white/80 tracking-widest">{{ key.name }}</p>
                                    </div>
                                    <p class="text-[8px] font-mono text-white/20 tracking-tighter uppercase">{{ key.key.substring(0, 8) }}...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Mission Audit Trail -->
                    <div class="bg-[#08080C] border border-white/5 p-8 relative overflow-hidden">
                        <p class="text-[9px] font-black text-vibrant-blue uppercase tracking-[.5em] mb-6">Execution_Audit_Trail</p>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-white/5">
                                        <th class="py-4 text-[9px] font-black text-white/20 uppercase tracking-widest">Mark_Time</th>
                                        <th class="py-4 text-[9px] font-black text-white/20 uppercase tracking-widest">Operator</th>
                                        <th class="py-4 text-[9px] font-black text-white/20 uppercase tracking-widest">Action_Event</th>
                                        <th class="py-4 text-[9px] font-black text-white/20 uppercase tracking-widest text-right">Payload_Sig</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="log in recent_logs" :key="log.id" class="border-b border-white/[0.02] group hover:bg-white/[0.01]">
                                        <td class="py-4 text-[10px] font-mono text-white/40">{{ formatTime(log.created_at) }}</td>
                                        <td class="py-4">
                                            <span class="text-[10px] font-black uppercase text-white/80">{{ log.user?.name || 'SYSTEM_CORE' }}</span>
                                        </td>
                                        <td class="py-4">
                                            <span class="px-2 py-0.5 text-[8px] font-black rounded" 
                                                :class="log.action.includes('DEACTIVATED') ? 'bg-red-500/20 text-red-400' : 'bg-vibrant-blue/20 text-vibrant-blue'">
                                                {{ log.action }}
                                            </span>
                                        </td>
                                        <td class="py-4 text-right">
                                            <span class="text-[9px] font-mono text-white/20">0x{{ log.id.toString(16).padStart(4, '0') }}</span>
                                        </td>
                                    </tr>
                                    <tr v-if="recent_logs.length === 0">
                                        <td colspan="4" class="py-10 text-center text-[10px] font-black text-white/10 uppercase tracking-widest italic">No activity detected on current frequency</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.font-outfit { font-family: 'Outfit', sans-serif; }
.text-vibrant-blue { color: #0088ff; }
.text-vibrant-green { color: #00ffaa; }
.bg-vibrant-blue { background-color: #0088ff; }

.animate-spin-slow {
    animation: spin 8s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    sla: Object,
    recentLogs: Array
});

const charts = ref({});

onMounted(() => {
    Object.keys(props.sla).forEach(service => {
        const ctx = document.getElementById(`chart-${service}`).getContext('2d');
        const history = props.sla[service].history;
        
        charts.value[service] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: history.map(h => new Date(h.recorded_at).toLocaleTimeString()),
                datasets: [{
                    label: 'Latency (ms)',
                    data: history.map(h => h.latency_ms),
                    borderColor: '#0088ff',
                    backgroundColor: 'rgba(0, 136, 255, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { display: false },
                    y: { 
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.05)' },
                        ticks: { color: 'rgba(255,255,255,0.3)', font: { size: 9 } }
                    }
                }
            }
        });
    });
});

const getStatusColor = (status) => {
    switch (status) {
        case 'operational': return 'text-vibrant-green';
        case 'degraded': return 'text-yellow-500';
        case 'down': return 'text-red-500';
        default: return 'text-white/20';
    }
};
</script>

<template>
    <Head title="SLA & System Health" />

    <AdminLayout>
        <template #header>System_Integrity_Monitor</template>

        <div class="space-y-10">
            <!-- Service Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div v-for="(data, service) in sla" :key="service" 
                    class="bg-[#050508] border border-white/5 p-8 relative overflow-hidden group">
                    
                    <div class="flex justify-between items-start mb-8">
                        <div>
                            <p class="text-[9px] font-black text-white/20 uppercase tracking-[0.4em] mb-1">Service_Node</p>
                            <h4 class="text-xl font-black tracking-tighter italic uppercase text-vibrant-blue">{{ service }}</h4>
                        </div>
                        <div :class="getStatusColor(data.current?.status)" class="text-[10px] font-black uppercase tracking-widest border border-current px-3 py-1">
                            {{ data.current?.status || 'UNKNOWN' }}
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <div class="bg-white/[0.02] border border-white/5 p-4">
                            <p class="text-[8px] font-bold text-white/20 uppercase tracking-widest mb-1">UPTIME_24H</p>
                            <p class="text-lg font-black font-mono text-vibrant-green">{{ data.uptime_24h.toFixed(2) }}%</p>
                        </div>
                        <div class="bg-white/[0.02] border border-white/5 p-4">
                            <p class="text-[8px] font-bold text-white/20 uppercase tracking-widest mb-1">AVG_LATENCY</p>
                            <p class="text-lg font-black font-mono text-white">{{ data.avg_latency.toFixed(1) }}ms</p>
                        </div>
                    </div>

                    <div class="h-32 mb-4">
                        <canvas :id="`chart-${service}`"></canvas>
                    </div>

                    <div class="pt-4 border-t border-white/5 flex justify-between items-center">
                        <span class="text-[8px] font-bold text-white/10 uppercase tracking-widest italic">Last Check: {{ data.current ? new Date(data.current.recorded_at).toLocaleTimeString() : 'N/A' }}</span>
                        <div class="w-2 h-2 rounded-full shadow-[0_0_10px_#00ffaa] animate-pulse bg-vibrant-green"></div>
                    </div>
                </div>
            </div>

            <!-- Audit/Health Logs -->
            <div class="bg-[#050508] border border-white/5 p-10">
                <div class="flex justify-between items-end mb-8 border-b border-white/5 pb-6">
                    <div>
                        <h3 class="text-xl font-black uppercase tracking-tighter italic mr-4">INFRASTRUCTURE_EVENT_LOG</h3>
                        <p class="text-[10px] text-white/30 uppercase tracking-[0.3em] mt-1">Real-time health pulse & micro-outage detection</p>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] font-black text-vibrant-blue uppercase tracking-widest">AUTO_RELOAD_ACTIVE</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-[11px] font-mono">
                        <thead>
                            <tr class="text-white/20 border-b border-white/5 uppercase tracking-widest">
                                <th class="pb-4 font-bold px-4">Timestamp</th>
                                <th class="pb-4 font-bold px-4">Service</th>
                                <th class="pb-4 font-bold px-4">Status</th>
                                <th class="pb-4 font-bold px-4 text-right">Latency</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.02]">
                            <tr v-for="log in recentLogs" :key="log.id" class="hover:bg-white/[0.01] transition-colors">
                                <td class="py-4 px-4 text-white/40 uppercase tracking-tighter">{{ new Date(log.recorded_at).toLocaleString() }}</td>
                                <td class="py-4 px-4 font-black uppercase tracking-widest text-vibrant-blue">{{ log.service_name }}</td>
                                <td class="py-4 px-4">
                                    <span :class="getStatusColor(log.status)" class="uppercase italic font-black tracking-widest">
                                        {{ log.status }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-right font-black">{{ log.latency_ms.toFixed(2) }}ms</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

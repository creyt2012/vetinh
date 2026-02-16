<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    reports: Array
});

const getRiskColor = (level) => {
    if (level === 'CRITICAL') return 'text-red-500 bg-red-500/10 border-red-500/20';
    return 'text-vibrant-blue bg-vibrant-blue/10 border-vibrant-blue/20';
};
</script>

<template>
    <Head title="Meteorological Reports" />

    <UserLayout>
        <div class="space-y-10">
            <!-- Header -->
            <div class="flex justify-between items-end">
                <div>
                    <h2 class="text-3xl font-black uppercase tracking-tighter italic">METEOROLOGICAL_REPORTS</h2>
                    <p class="text-[10px] text-vibrant-blue uppercase tracking-[0.4em] font-black mt-1">Strategic Weather Intelligence Aggregation</p>
                </div>
                <button class="px-8 py-3 bg-white/5 border border-white/10 text-[10px] font-black uppercase tracking-widest hover:bg-white/20 transition-all">REFRESH_LOGS</button>
            </div>

            <!-- Reports Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-if="reports.length === 0" class="col-span-full py-20 border border-dashed border-white/5 flex flex-col items-center justify-center opacity-20">
                    <svg class="w-12 h-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <p class="text-[10px] font-black uppercase tracking-[0.3em]">NO_REPORTS_GENERATED_YET</p>
                </div>

                <div v-for="report in reports" :key="report.id" class="bg-[#050508] border border-white/5 p-6 space-y-6 group hover:border-vibrant-blue/30 transition-all">
                    <div class="flex justify-between items-start">
                        <div class="w-10 h-10 bg-vibrant-blue/10 flex items-center justify-center border border-vibrant-blue/20">
                            <svg class="w-5 h-5 text-vibrant-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <span :class="getRiskColor(report.metadata?.risk_level)" class="px-2 py-1 border text-[8px] font-black uppercase">
                            {{ report.metadata?.risk_level || 'STABLE' }}
                        </span>
                    </div>

                    <div>
                        <h4 class="text-xs font-black uppercase italic tracking-widest truncate">{{ report.filename }}</h4>
                        <p class="text-[9px] text-white/30 uppercase tracking-widest mt-1">{{ new Date(report.created_at).toLocaleDateString() }} • {{ report.metadata?.total_observations }} OBSERVATIONS</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4 border-t border-white/5 pt-4">
                        <div>
                            <p class="text-[8px] font-black text-white/20 uppercase">Avg_Temp</p>
                            <p class="text-[11px] font-mono font-bold">{{ report.metadata?.average_temp.toFixed(1) }}°C</p>
                        </div>
                        <div>
                            <p class="text-[8px] font-black text-white/20 uppercase">Max_Wind</p>
                            <p class="text-[11px] font-mono font-bold">{{ report.metadata?.max_wind_speed }} KM/H</p>
                        </div>
                    </div>

                    <a :href="route('user.reports.download', report.id)" class="block w-full py-3 bg-vibrant-blue text-white text-[9px] font-black uppercase tracking-widest text-center hover:scale-[1.02] transition-all">
                        DOWNLOAD_RESOURCES
                    </a>
                </div>
            </div>
        </div>
    </UserLayout>
</template>

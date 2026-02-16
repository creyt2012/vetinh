<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    logs: Object
});

const getActionColor = (action) => {
    if (action.includes('CREATE') || action.includes('STORE')) return 'text-vibrant-green';
    if (action.includes('DELETE') || action.includes('DESTROY')) return 'text-red-500';
    if (action.includes('UPDATE')) return 'text-yellow-500';
    return 'text-vibrant-blue';
};
</script>

<template>
    <Head title="System Audit Logs" />

    <AdminLayout>
        <template #header>
            <h2 class="font-black text-xl text-white leading-tight uppercase tracking-[0.3em] italic">
                SYSTEM_AUDIT_LOGS
            </h2>
        </template>

        <div class="py-12 px-6 lg:px-12">
            <div class="bg-[#050508] border border-white/5 overflow-hidden">
                <div class="p-8 border-b border-white/5 bg-white/[0.02]">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-sm font-black text-vibrant-blue uppercase tracking-widest">TACTICAL_HISTORY</h3>
                            <p class="text-[10px] text-white/30 uppercase tracking-widest mt-1">Surveillance of all administrative mutations</p>
                        </div>
                        <div class="flex space-x-4">
                            <input type="text" placeholder="FILTER_BY_USER..." class="bg-black/50 border border-white/10 text-[10px] uppercase font-black px-4 py-2 focus:ring-1 focus:ring-vibrant-blue outline-none transition-all">
                            <button class="px-6 py-2 bg-vibrant-blue text-white text-[10px] font-black uppercase tracking-widest hover:scale-105 transition-all">EXPORT_JSON</button>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-white/5 bg-white/[0.01]">
                                <th class="p-6 text-[10px] font-black text-white/40 uppercase tracking-widest">Timestamp</th>
                                <th class="p-6 text-[10px] font-black text-white/40 uppercase tracking-widest">User</th>
                                <th class="p-6 text-[10px] font-black text-white/40 uppercase tracking-widest">Action</th>
                                <th class="p-6 text-[10px] font-black text-white/40 uppercase tracking-widest">Model</th>
                                <th class="p-6 text-[10px] font-black text-white/40 uppercase tracking-widest text-right">IP_Address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.03]">
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-white/[0.02] transition-colors">
                                <td class="p-6 font-mono text-[11px] text-white/60">
                                    {{ new Date(log.created_at).toLocaleString() }}
                                </td>
                                <td class="p-6">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-6 h-6 rounded-full bg-vibrant-blue/20 flex items-center justify-center border border-vibrant-blue/30 text-[9px] font-black text-vibrant-blue uppercase">
                                            {{ log.user?.name?.substring(0, 1) }}
                                        </div>
                                        <span class="text-[11px] font-black uppercase italic tracking-tighter">{{ log.user?.name }}</span>
                                    </div>
                                </td>
                                <td class="p-6 text-[10px] font-black italic tracking-widest" :class="getActionColor(log.action)">
                                    {{ log.action }}
                                </td>
                                <td class="p-6 text-[11px] font-mono text-white/40">
                                    {{ log.model_type?.split('\\').pop() }} #{{ log.model_id }}
                                </td>
                                <td class="p-6 text-right font-mono text-[10px] text-white/20 uppercase tracking-widest">
                                    {{ log.ip_address }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div v-if="logs.data.length === 0" class="p-20 text-center opacity-20 uppercase text-xs tracking-[0.5em] font-black italic">
                    NO_MUTATION_RECORDS_DETECTED
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

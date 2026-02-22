<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Ship, MoreVertical, Trash2, Edit2, CheckCircle } from 'lucide-vue-next';

const props = defineProps({
    vessels: Object
});

const statusColors = {
    'UNDER_WAY': 'text-vibrant-blue bg-vibrant-blue/10',
    'ANCHORED': 'text-white/40 bg-white/5',
    'MOORED': 'text-white/40 bg-white/5',
    'RESEARCH_UNIT': 'text-vibrant-green bg-vibrant-green/10'
};

const deleteVessel = (id) => {
    if (confirm('Are you sure you want to remove this vessel from surveillance?')) {
        // useForm or router.delete logic here
    }
};
</script>

<template>
    <Head title="Vessel Management - Admin" />

    <AdminLayout>
        <template #header>VESSEL_SURVEILLANCE_INDEX</template>

        <div class="space-y-8 pb-20">
            <div class="flex justify-between items-end">
                <div>
                    <p class="text-[9px] font-black text-vibrant-blue uppercase tracking-[0.5em] mb-2">Maritime_Registry</p>
                    <h3 class="text-3xl font-black uppercase italic tracking-tighter">GLOBAL_ASSET_TRACKING</h3>
                </div>
            </div>

            <div class="bg-[#08080C] border border-white/5 relative overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-white/5">
                            <th class="p-6 text-[9px] font-black text-white/20 uppercase tracking-widest pl-10">MMSI/IDENTITY</th>
                            <th class="p-6 text-[9px] font-black text-white/20 uppercase tracking-widest">TYPE</th>
                            <th class="p-6 text-[9px] font-black text-white/20 uppercase tracking-widest">COORDINATES</th>
                            <th class="p-6 text-[9px] font-black text-white/20 uppercase tracking-widest">STATUS_VECTOR</th>
                            <th class="p-6 text-[9px] font-black text-white/20 uppercase tracking-widest text-right pr-10">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="vessel in vessels.data" :key="vessel.id" class="border-b border-white/[0.02] group hover:bg-white/[0.01] transition-colors">
                            <td class="p-6 pl-10">
                                <div class="flex items-center space-x-4">
                                    <div class="w-10 h-10 bg-white/5 flex items-center justify-center text-vibrant-blue group-hover:scale-110 transition-transform">
                                        <Ship class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <p class="text-[11px] font-black text-white uppercase italic tracking-wider">{{ vessel.name }}</p>
                                        <p class="text-[9px] font-mono text-white/20">MMSI: {{ vessel.mmsi }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-6">
                                <span class="text-[9px] font-black text-white/40 uppercase tracking-widest">{{ vessel.type }}</span>
                            </td>
                            <td class="p-6">
                                <span class="text-[10px] font-mono text-white/60 tabular-nums">
                                    {{ vessel.latitude.toFixed(4) }}N / {{ vessel.longitude.toFixed(4) }}E
                                </span>
                            </td>
                            <td class="p-6">
                                <div class="flex items-center space-x-2">
                                    <div class="w-1 h-1 rounded-full animate-pulse" :class="vessel.status === 'UNDER_WAY' ? 'bg-vibrant-blue shadow-[0_0_5px_#0088ff]' : 'bg-white/20'"></div>
                                    <span class="px-2 py-0.5 text-[8px] font-black rounded uppercase" :class="statusColors[vessel.status] || 'bg-white/5 text-white/40'">
                                        {{ vessel.status }}
                                    </span>
                                </div>
                            </td>
                            <td class="p-6 text-right pr-10">
                                <div class="flex justify-end space-x-2">
                                    <button class="p-2 bg-white/5 hover:bg-white/10 text-white/40 hover:text-white transition-colors">
                                        <Edit2 class="w-3.5 h-3.5" />
                                    </button>
                                    <button @click="deleteVessel(vessel.id)" class="p-2 bg-red-500/5 hover:bg-red-500/20 text-red-500/40 hover:text-red-500 transition-colors">
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex justify-center" v-if="vessels.links.length > 3">
                <nav class="flex space-x-2">
                    <template v-for="(link, k) in vessels.links" :key="k">
                        <div v-if="link.url === null" :key="k" class="px-4 py-2 bg-white/5 border border-white/5 text-[9px] font-black text-white/10 uppercase tracking-widest cursor-not-allowed" v-html="link.label"></div>
                        <Link v-else :key="`link-${k}`" :href="link.url" class="px-4 py-2 bg-white/5 border border-white/5 text-[9px] font-black uppercase tracking-widest hover:border-vibrant-blue transition-colors" :class="{ 'border-vibrant-blue text-vibrant-blue': link.active }" v-html="link.label"></Link>
                    </template>
                </nav>
            </div>
        </div>
    </AdminLayout>
</template>

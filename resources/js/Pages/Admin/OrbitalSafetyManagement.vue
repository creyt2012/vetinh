<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { AlertTriangle, ShieldCheck, Zap, MoreVertical } from 'lucide-vue-next';

const props = defineProps({
    conjunctions: Array
});

const getRiskColor = (prob) => {
    const p = parseFloat(prob);
    if (p > 1e-3) return 'text-red-500';
    if (p > 1e-4) return 'text-orange-500';
    return 'text-vibrant-blue';
};
</script>

<template>
    <Head title="Orbital Safety - Admin" />

    <AdminLayout>
        <template #header>ORBITAL_CONJUNCTION_MANAGEMENT</template>

        <div class="space-y-8 pb-20">
            <div>
                <p class="text-[9px] font-black text-vibrant-blue uppercase tracking-[0.5em] mb-2">Space_Debris_Monitoring</p>
                <h3 class="text-3xl font-black uppercase italic tracking-tighter">COLLISION_AVOIDANCE_HUB</h3>
            </div>

            <div class="grid grid-cols-1 gap-6">
                <div v-for="conj in conjunctions" :key="conj.id" 
                    class="bg-[#08080C] border border-white/5 p-8 relative group overflow-hidden hover:border-vibrant-blue/30 transition-all duration-500">
                    
                    <div class="flex justify-between items-start mb-8 relative z-10">
                        <div class="flex items-center space-x-6">
                            <div class="w-16 h-16 bg-red-500/10 border border-red-500/20 flex items-center justify-center">
                                <AlertTriangle class="w-8 h-8 text-red-500 animate-pulse" />
                            </div>
                            <div>
                                <h4 class="text-sm font-black text-white uppercase tracking-widest mb-1 italic">CONJUNCTION_EVENT_{{ conj.id }}</h4>
                                <p class="text-[10px] font-mono text-white/30 uppercase tracking-tighter">TCA: {{ conj.tca }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-[9px] font-black text-white/20 uppercase tracking-[0.4em] mb-2">RISK_PROBABILITY</p>
                            <p class="text-3xl font-black font-mono tracking-tighter" :class="getRiskColor(conj.probability)">{{ conj.probability }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10 relative z-10">
                        <!-- Asset A -->
                        <div class="p-6 bg-white/[0.02] border border-white/5">
                            <p class="text-[8px] font-black text-vibrant-blue uppercase tracking-widest mb-4 italic">PRIMARY_ASSET</p>
                            <div class="flex justify-between items-end">
                                <div>
                                    <p class="text-lg font-black text-white uppercase">{{ conj.satellite_a?.name || 'UNKNOWN' }}</p>
                                    <p class="text-[9px] font-mono text-white/40 uppercase tracking-widest mt-1">NORAD: {{ conj.satellite_a?.norad_id }}</p>
                                </div>
                                <div class="w-8 h-8 border border-vibrant-blue/20 flex items-center justify-center">
                                     <Zap class="w-4 h-4 text-vibrant-blue" />
                                </div>
                            </div>
                        </div>

                        <!-- Asset B -->
                        <div class="p-6 bg-white/[0.02] border border-white/5">
                            <p class="text-[8px] font-black text-orange-500 uppercase tracking-widest mb-4 italic">THREAT_OBJECT</p>
                            <div class="flex justify-between items-end">
                                <div>
                                    <p class="text-lg font-black text-white uppercase">{{ conj.satellite_b?.name || 'SPACE_DEBRIS' }}</p>
                                    <p class="text-[9px] font-mono text-white/40 uppercase tracking-widest mt-1">NORAD: {{ conj.satellite_b?.norad_id || 'CAT_ID_PENDING' }}</p>
                                </div>
                                <div class="w-8 h-8 border border-orange-500/20 flex items-center justify-center">
                                     <ShieldCheck class="w-4 h-4 text-orange-500" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-between items-center relative z-10 pt-8 border-t border-white/5">
                        <div class="flex space-x-8">
                             <div>
                                <p class="text-[8px] font-black text-white/20 uppercase tracking-widest mb-1">Miss_Distance</p>
                                <p class="text-xs font-black text-white tabular-nums">{{ conj.distance }} <span class="text-[9px]">KM</span></p>
                            </div>
                             <div>
                                <p class="text-[8px] font-black text-white/20 uppercase tracking-widest mb-1">Status</p>
                                <p class="text-xs font-black text-vibrant-green uppercase tracking-widest">{{ conj.status }}</p>
                            </div>
                        </div>
                        <button class="bg-vibrant-blue px-6 py-2 text-[10px] font-black uppercase tracking-[0.2em] italic hover:bg-vibrant-blue/80 transition-all">
                            MANEUVER_PLAN_PROPOSAL
                        </button>
                    </div>

                    <!-- Background Pattern -->
                    <div class="absolute -right-20 -bottom-20 w-48 h-48 bg-vibrant-blue/5 rounded-full blur-[100px] pointer-events-none group-hover:bg-vibrant-blue/10 transition-colors"></div>
                </div>

                <div v-if="conjunctions.length === 0" class="py-20 text-center border border-dashed border-white/10">
                    <p class="text-[10px] font-black text-white/20 uppercase tracking-[0.5em] italic">No active conjunction risks detected in current orbital plane</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

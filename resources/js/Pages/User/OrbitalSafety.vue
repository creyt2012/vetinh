<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { ShieldAlert, Zap, Radio, Target, Activity } from 'lucide-vue-next';

const conjunctions = ref([]);
const isLoading = ref(true);

const fetchConjunctions = async () => {
    try {
        const res = await axios.get('/api/v1/satellites/conjunctions', {
            headers: { 'X-API-KEY': 'vetinh_dev_key_123' }
        });
        conjunctions.value = res.data.data;
    } catch (e) {
        console.error('Failed to fetch conjunctions', e);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    fetchConjunctions();
});
</script>

<template>
    <Head title="Orbital Safety" />

    <UserLayout>
        <div class="space-y-10">
            <!-- Header -->
            <div class="flex justify-between items-end">
                <div>
                    <h2 class="text-4xl font-black uppercase tracking-tighter italic leading-none text-white">ORBITAL_SAFETY</h2>
                    <p class="text-xs text-vibrant-red uppercase tracking-[0.4em] font-black mt-2">Collision Risk Assessment & Close Approach Monitoring</p>
                </div>
                <div class="text-right">
                    <p class="text-[9px] font-black text-white/20 uppercase tracking-widest">Active_Conjunction_Risks</p>
                    <p class="text-2xl font-black italic text-vibrant-red">{{ conjunctions.length }}</p>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="grid grid-cols-12 gap-8">
                <!-- Conjunction Feed -->
                <div class="col-span-12 lg:col-span-8 space-y-6">
                    <div v-if="isLoading" class="h-64 border border-white/5 bg-[#050508] flex items-center justify-center">
                        <div class="flex flex-col items-center space-y-4">
                            <div class="w-12 h-12 border-2 border-vibrant-red border-t-transparent rounded-full animate-spin"></div>
                            <p class="text-[10px] font-black text-vibrant-red uppercase tracking-widest">Processing_Probability_Vectors</p>
                        </div>
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="c in conjunctions" :key="c.id" 
                            class="bg-[#050508] border border-red-500/10 p-6 flex items-center justify-between group hover:border-red-500/30 transition-all border-l-4 border-l-vibrant-red">
                            
                            <div class="flex items-center space-x-12">
                                <!-- Assets -->
                                <div class="flex items-center space-x-6">
                                    <div class="text-center">
                                        <div class="w-10 h-10 bg-white/5 border border-white/10 flex items-center justify-center mb-1">
                                            <Zap class="w-5 h-5 text-vibrant-blue" />
                                        </div>
                                        <p class="text-[8px] font-black uppercase text-white/40">{{ c.satellite_a?.name || 'ASSET_A' }}</p>
                                    </div>
                                    <div class="relative w-16 h-px bg-white/10">
                                        <div class="absolute inset-0 bg-vibrant-red/40 animate-[ping_2s_infinite]"></div>
                                    </div>
                                    <div class="text-center">
                                        <div class="w-10 h-10 bg-white/5 border border-white/10 flex items-center justify-center mb-1">
                                            <Target class="w-5 h-5 text-white/60" />
                                        </div>
                                        <p class="text-[8px] font-black uppercase text-white/40">{{ c.satellite_b?.name || 'ASSET_B' }}</p>
                                    </div>
                                </div>

                                <!-- Metrics -->
                                <div class="hidden md:grid grid-cols-2 gap-x-12 gap-y-1 border-l border-white/5 pl-12">
                                    <div>
                                        <p class="text-[7px] text-white/20 uppercase font-bold">Miss_Distance</p>
                                        <p class="text-xs font-black text-white">{{ c.distance }} km</p>
                                    </div>
                                    <div>
                                        <p class="text-[7px] text-white/20 uppercase font-bold">Probability</p>
                                        <p class="text-xs font-black text-vibrant-red">{{ c.probability }}</p>
                                    </div>
                                    <div class="col-span-2 mt-1">
                                        <p class="text-[7px] text-white/20 uppercase font-bold">TCA (Time of Closest Approach)</p>
                                        <p class="text-[10px] font-mono font-bold text-vibrant-blue">{{ new Date(c.tca).toLocaleString() }}</p>
                                    </div>
                                </div>
                            </div>

                            <button class="px-6 py-2 bg-vibrant-red/10 border border-vibrant-red/20 text-vibrant-red text-[9px] font-black uppercase tracking-widest hover:bg-vibrant-red hover:text-white transition-all">RESOLVE_MANEUVER</button>
                        </div>

                        <div v-if="conjunctions.length === 0" class="h-64 border border-white/5 bg-[#050508] flex items-center justify-center text-white/20 uppercase text-[10px] tracking-[0.4em] font-black italic">
                            ORBITAL_SLOT_NOMINAL
                        </div>
                    </div>
                </div>

                <!-- Analysis Sidebar -->
                <div class="col-span-12 lg:col-span-4 space-y-8">
                    <div class="bg-[#050508] border border-white/5 p-8">
                        <h3 class="text-sm font-black uppercase tracking-widest mb-8 flex items-center">
                            <ShieldAlert class="w-4 h-4 text-vibrant-red mr-3" />
                            RISK_ANALYSIS
                        </h3>
                        <div class="space-y-6">
                            <div class="p-4 bg-red-500/5 border border-red-500/10">
                                <p class="text-[10px] font-black text-vibrant-red uppercase mb-2">Space_Debris_Alert</p>
                                <p class="text-xs font-medium text-white/80">Heightened debris cloud activity detected in LEO-800km orbit from recent payload fragmentation.</p>
                            </div>
                            <div class="p-4 bg-white/5 border border-white/10">
                                <p class="text-[10px] font-black text-white/40 uppercase mb-2">Solar_Activity</p>
                                <p class="text-xs font-medium text-white/80">Solar flare forecasted; drag compensation maneuvers required for assets < 400km.</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-black border border-white/5 p-8 relative overflow-hidden">
                        <div class="relative z-10">
                            <h3 class="text-[9px] font-black text-white/20 uppercase tracking-widest mb-6">Collision_Avoidance_Status</h3>
                            <div class="flex items-center space-x-4">
                                <div class="flex-1 h-2 bg-white/5 rounded-full overflow-hidden">
                                     <div class="h-full bg-vibrant-red w-1/4 animate-pulse"></div>
                                </div>
                                <span class="text-[10px] font-black">25% RISK</span>
                            </div>
                        </div>
                        <Activity class="absolute -bottom-4 -right-4 w-24 h-24 opacity-5 text-vibrant-red" />
                    </div>
                </div>
            </div>
        </div>
    </UserLayout>
</template>

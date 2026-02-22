<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { Ship, Anchor, Navigation, Activity } from 'lucide-vue-next';

const vessels = ref([]);
const isLoading = ref(true);

const fetchVessels = async () => {
    try {
        const res = await axios.get('/api/v1/marine/vessels', {
            headers: { 'X-API-KEY': 'vetinh_dev_key_123' }
        });
        vessels.value = res.data.data;
    } catch (e) {
        console.error('Failed to fetch vessels', e);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    fetchVessels();
    setInterval(fetchVessels, 30000); // Update every 30s
});
</script>

<template>
    <Head title="Marine Surveillance" />

    <UserLayout>
        <div class="space-y-10">
            <!-- Header -->
            <div class="flex justify-between items-end">
                <div>
                    <h2 class="text-4xl font-black uppercase tracking-tighter italic leading-none text-white">MARINE_SURVEILLANCE</h2>
                    <p class="text-xs text-vibrant-blue uppercase tracking-[0.4em] font-black mt-2">AI-Driven AIS Tracking & Maritime Intelligence</p>
                </div>
                <div class="text-right">
                    <p class="text-[9px] font-black text-white/20 uppercase tracking-widest">Global_vessel_count</p>
                    <p class="text-2xl font-black italic text-vibrant-blue">{{ vessels.length }}</p>
                </div>
            </div>

            <!-- Main Control Grid -->
            <div class="grid grid-cols-12 gap-8">
                <!-- Vessel List -->
                <div class="col-span-12 lg:col-span-8 space-y-6">
                    <div v-if="isLoading" class="h-64 border border-white/5 bg-[#050508] flex items-center justify-center">
                        <div class="flex flex-col items-center space-y-4">
                            <div class="w-12 h-12 border-2 border-vibrant-blue border-t-transparent rounded-full animate-spin"></div>
                            <p class="text-[10px] font-black text-vibrant-blue uppercase tracking-widest">Establishing_AIS_Feed</p>
                        </div>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="vessel in vessels" :key="vessel.mmsi" 
                            class="bg-[#050508] border border-white/5 p-6 hover:border-vibrant-blue/30 transition-all group relative overflow-hidden">
                            <div class="flex justify-between items-start mb-4 relative z-10">
                                <div class="flex items-center space-x-3">
                                    <div class="p-2 bg-vibrant-blue/10 border border-vibrant-blue/20">
                                        <Ship class="w-5 h-5 text-vibrant-blue" />
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-black uppercase italic tracking-tight text-white group-hover:text-vibrant-blue transition-colors">{{ vessel.name }}</h4>
                                        <p class="text-[9px] text-white/30 font-mono tracking-widest">MMSI: {{ vessel.mmsi }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="px-2 py-0.5 bg-vibrant-green/10 text-vibrant-green text-[8px] font-black uppercase border border-vibrant-green/20">Active</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-2 mb-4 relative z-10">
                                <div class="p-2 bg-white/5 border border-white/10">
                                    <p class="text-[7px] text-white/20 uppercase">Speed</p>
                                    <p class="text-xs font-black">{{ vessel.speed_knots }} kts</p>
                                </div>
                                <div class="p-2 bg-white/5 border border-white/10">
                                    <p class="text-[7px] text-white/20 uppercase">Course</p>
                                    <p class="text-xs font-black">{{ vessel.course }}°</p>
                                </div>
                                <div class="p-2 bg-white/5 border border-white/10 text-vibrant-blue">
                                    <p class="text-[7px] opacity-40 uppercase">Class</p>
                                    <p class="text-[8px] font-black whitespace-nowrap">{{ vessel.type }}</p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2 text-[9px] font-black text-white/20 uppercase tracking-widest relative z-10">
                                <Navigation class="w-3 h-3" />
                                <span>Dest: {{ vessel.destination }}</span>
                                <span class="mx-2 opacity-50">/</span>
                                <span>ETA: {{ new Date(vessel.eta).toLocaleDateString() }}</span>
                            </div>

                            <!-- Aesthetics -->
                            <div class="absolute bottom-0 right-0 p-1 opacity-5">
                                <Activity class="w-16 h-16" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Surveillance Sidebar -->
                <div class="col-span-12 lg:col-span-4 space-y-8">
                    <div class="bg-[#050508] border border-white/5 p-8">
                        <h3 class="text-sm font-black uppercase tracking-widest mb-8 flex items-center">
                            <Anchor class="w-4 h-4 text-vibrant-blue mr-3" />
                            ZONE_SURVEILLANCE
                        </h3>
                        <div class="space-y-6">
                            <div class="p-4 bg-white/5 border border-white/10 border-l-4 border-l-vibrant-blue">
                                <p class="text-[10px] font-black text-white/40 uppercase mb-2">High_Risk_Sector</p>
                                <p class="text-xs font-medium text-white/80">South China Sea - Increasing vessel density detected in Typhoon corridor.</p>
                            </div>
                            <div class="p-4 bg-white/5 border border-white/10">
                                <p class="text-[10px] font-black text-white/40 uppercase mb-2">Fleet_Advisory</p>
                                <p class="text-xs font-medium text-white/80">Recommended 15° heading adjustment for tankers in Sector-4.</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-vibrant-blue/5 border border-vibrant-blue/20 p-8">
                        <h3 class="text-[10px] font-black text-vibrant-blue uppercase tracking-[0.3em] mb-4 text-center">INTELLIGENCE_STATUS</h3>
                        <div class="flex flex-col items-center space-y-2">
                            <div class="w-full h-1 bg-vibrant-blue/20 rounded-full overflow-hidden">
                                <div class="h-full bg-vibrant-blue animate-[loading_2s_ease-in-out_infinite]" style="width: 40%"></div>
                            </div>
                            <p class="text-[8px] font-mono text-vibrant-blue/60 uppercase">Decoding AIS Stream...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </UserLayout>
</template>

<style scoped>
@keyframes loading {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(250%); }
}
</style>

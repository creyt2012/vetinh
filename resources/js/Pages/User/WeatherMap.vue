<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import Globe from 'globe.gl';

const globeContainer = ref(null);
const activeLayer = ref('clouds');

const layers = [
    { id: 'clouds', name: 'CLOUD_DENSITY', color: 'vibrant-blue' },
    { id: 'precip', name: 'PRECIPITATION', color: 'vibrant-green' },
    { id: 'wind', name: 'WIND_SPEED', color: 'yellow-500' },
];

onMounted(() => {
    const world = Globe()
        (globeContainer.value)
        .globeImageUrl('//unpkg.com/three-globe/example/img/earth-night.jpg')
        .bumpImageUrl('//unpkg.com/three-globe/example/img/earth-topology.png')
        .backgroundColor('#020205')
        .showAtmosphere(true)
        .atmosphereColor('#0088ff')
        .atmosphereDaylightAlpha(0.1);

    // Initial view
    world.pointOfView({ lat: 10, lng: 106, altitude: 2 });

    // Handle resize
    window.addEventListener('resize', () => {
        world.width(globeContainer.value.offsetWidth);
        world.height(globeContainer.value.offsetHeight);
    });
});
</script>

<template>
    <Head title="Professional Weather Map" />

    <UserLayout>
        <div class="h-[calc(100vh-12rem)] relative bg-[#050508] border border-white/5 overflow-hidden">
            <!-- Sidebar Controls -->
            <div class="absolute top-8 left-8 z-10 w-64 space-y-6">
                <div>
                    <h2 class="text-2xl font-black uppercase tracking-tighter italic">METEO_LAYERS</h2>
                    <p class="text-[9px] text-white/30 uppercase tracking-[0.3em] mt-1">Global Atmospheric Visualization</p>
                </div>

                <div class="space-y-2">
                    <button v-for="layer in layers" :key="layer.id"
                        @click="activeLayer = layer.id"
                        :class="activeLayer === layer.id ? 'bg-vibrant-blue/10 border-vibrant-blue/50 text-white' : 'bg-black/40 border-white/5 text-white/40'"
                        class="w-full text-left px-6 py-4 border transition-all flex items-center justify-between group">
                        <span class="text-[10px] font-black tracking-widest uppercase">{{ layer.name }}</span>
                        <div v-if="activeLayer === layer.id" class="w-1.5 h-1.5 rounded-full bg-vibrant-blue shadow-[0_0_10px_#0088ff]"></div>
                    </button>
                </div>

                <!-- Legend -->
                <div class="bg-black/60 backdrop-blur-md border border-white/5 p-4">
                    <p class="text-[8px] font-black text-white/40 uppercase mb-3">CONCENTRATION_INDEX</p>
                    <div class="h-2 w-full bg-gradient-to-r from-blue-900 via-vibrant-blue to-white rounded-full"></div>
                    <div class="flex justify-between mt-2 text-[7px] font-mono text-white/20">
                        <span>LOW</span>
                        <span>NOMINAL</span>
                        <span>CRITICAL</span>
                    </div>
                </div>
            </div>

            <!-- Bottom Left Info -->
            <div class="absolute bottom-8 left-8 z-10 p-4 border-l border-white/20 bg-black/40 backdrop-blur-sm">
                <p class="text-[10px] font-black uppercase italic text-vibrant-blue">ACTIVE_DATA_SOURCE: HIMAWARI_9</p>
                <p class="text-[8px] text-white/40 font-mono mt-1 uppercase">Resolving spatial vectors @ 2.5km/px</p>
            </div>

            <!-- Right Panel: Micro Vitals -->
            <div class="absolute top-8 right-8 z-10 space-y-4">
                <div class="bg-black/40 backdrop-blur-md border border-white/5 p-4 text-right">
                    <p class="text-[8px] font-black text-white/20 uppercase">CURSOR_COORDINATES</p>
                    <p class="text-xs font-mono font-black italic text-vibrant-blue">10.7626° N, 106.6602° E</p>
                </div>
            </div>

            <!-- Globe Container -->
            <div ref="globeContainer" class="w-full h-full cursor-grab active:cursor-grabbing"></div>
            
            <!-- HUD Overlay Decoration -->
            <div class="absolute inset-0 pointer-events-none border-[20px] border-black/10"></div>
        </div>
    </UserLayout>
</template>

<style scoped>
/* HUD aesthetics would go here */
</style>

<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    location: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['close']);

const history = ref([]);
const loading = ref(true);
const selectedItem = ref(null);

onMounted(async () => {
    try {
        const response = await fetch(`/api/v1/satellites/imagery-history?lat=${props.location.lat}&lng=${props.location.lng}`, {
            headers: { 'X-API-KEY': 'vetinh_dev_key_123' }
        });
        const result = await response.json();
        if (result.status === 'success') {
            history.value = result.data;
            if (history.value.length > 0) {
                selectedItem.value = history.value[0];
            }
        }
    } catch (e) {
        console.error("Failed to fetch imagery history", e);
    } finally {
        loading.value = false;
    }
});

const formatDate = (isoStr) => {
    return new Date(isoStr).toLocaleString('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-md p-6 animate-in fade-in duration-300">
        <div class="w-full max-w-5xl bg-zinc-950 border border-white/10 rounded-3xl overflow-hidden shadow-[0_0_50px_rgba(0,0,0,0.5)] flex flex-col h-[80vh]">
            
            <!-- Header -->
            <div class="px-8 py-6 border-b border-white/5 flex justify-between items-center bg-gradient-to-r from-vibrant-blue/10 to-transparent">
                <div>
                    <h2 class="text-2xl font-black italic tracking-tighter uppercase leading-none">Satellite Time Machine</h2>
                    <p class="text-[10px] font-mono text-white/40 uppercase tracking-[0.4em] mt-2">
                        Location: {{ location.lat.toFixed(4) }}°N, {{ location.lng.toFixed(4) }}°E 
                        <span v-if="location.province" class="ml-2 text-vibrant-blue">/ {{ location.province }}</span>
                    </p>
                </div>
                <button @click="$emit('close')" class="w-10 h-10 rounded-full border border-white/10 flex items-center justify-center hover:bg-white/5 transition-colors group">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white/40 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex-1 flex overflow-hidden">
                <!-- Main Viewer -->
                <div class="flex-1 bg-black relative flex items-center justify-center p-8">
                    <div v-if="loading" class="flex flex-col items-center space-y-4">
                        <div class="w-12 h-12 border-2 border-vibrant-blue border-t-transparent rounded-full animate-spin"></div>
                        <p class="text-[10px] font-black text-white/40 uppercase tracking-[0.2em]">Synchronizing Optical Stream...</p>
                    </div>

                    <template v-else-if="selectedItem">
                        <img :src="selectedItem.image_url" class="max-w-full max-h-full object-contain rounded-xl shadow-2xl animate-in zoom-in-95 duration-500 border border-white/5" />
                        
                        <!-- HUD Overlays -->
                        <div class="absolute top-12 left-12 space-y-4">
                            <div class="bg-black/60 backdrop-blur-md p-4 rounded-xl border border-white/10">
                                <p class="text-[8px] font-black text-white/40 uppercase tracking-widest mb-1">Capture_Time</p>
                                <p class="text-lg font-black italic tracking-tighter text-white">{{ formatDate(selectedItem.timestamp) }}</p>
                            </div>
                            <div class="bg-black/60 backdrop-blur-md p-4 rounded-xl border border-white/10 flex space-x-8">
                                <div>
                                    <p class="text-[8px] font-black text-white/40 uppercase tracking-widest mb-1">Coverage</p>
                                    <p class="text-lg font-black text-vibrant-blue">{{ selectedItem.metrics.coverage }}%</p>
                                </div>
                                <div v-if="selectedItem.metrics.temp">
                                    <p class="text-[8px] font-black text-white/40 uppercase tracking-widest mb-1">S_Temp</p>
                                    <p class="text-lg font-black text-orange-400">{{ selectedItem.metrics.temp }}°C</p>
                                </div>
                            </div>
                        </div>

                        <div class="absolute bottom-12 right-12">
                            <div class="bg-vibrant-blue/20 backdrop-blur-md px-4 py-2 rounded-full border border-vibrant-blue/30">
                                <p class="text-[8px] font-black text-vibrant-blue uppercase tracking-widest">Authentication: Verified Strategic Asset</p>
                            </div>
                        </div>
                    </template>

                    <div v-else class="text-center">
                        <p class="text-white/40 font-black uppercase tracking-widest">No spectral data found for this sector.</p>
                    </div>
                </div>

                <!-- Timeline Sidebar -->
                <div class="w-80 border-l border-white/5 bg-zinc-900/50 overflow-y-auto p-6 space-y-4">
                    <p class="text-[10px] font-black text-white/30 uppercase tracking-[0.2em] mb-4">Observation Timeline ({{ history.length }})</p>
                    
                    <div v-for="item in history" :key="item.id" 
                         @click="selectedItem = item"
                         :class="[
                             'p-4 rounded-2xl border transition-all cursor-pointer group',
                             selectedItem?.id === item.id 
                                ? 'bg-vibrant-blue/10 border-vibrant-blue shadow-[0_0_20px_rgba(0,136,255,0.1)]' 
                                : 'bg-white/5 border-white/5 hover:border-white/20'
                         ]">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 rounded-lg bg-black border border-white/10 overflow-hidden">
                                <img :src="item.image_url" class="w-full h-full object-cover opacity-60 group-hover:opacity-100 transition-opacity" />
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-white leading-tight">{{ formatDate(item.timestamp).split(' ')[1] }}</p>
                                <p class="text-[8px] font-mono text-white/40">{{ formatDate(item.timestamp).split(' ')[0] }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

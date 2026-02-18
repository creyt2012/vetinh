<script setup>
import { computed } from 'vue';

const props = defineProps({
    satellite: {
        type: Object,
        required: true
    }
});

const statusColor = computed(() => {
    switch (props.satellite.status) {
        case 'ACTIVE': return '#22c55e';
        case 'INACTIVE': return '#ef4444';
        default: return '#f59e0b';
    }
});

const formatCoord = (val) => val ? val.toFixed(4) : '0.0000';
</script>

<template>
    <div class="bg-black/80 backdrop-blur-2xl border border-white/10 rounded-2xl overflow-hidden shadow-2xl animate-in fade-in slide-in-from-bottom-4 duration-500">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-white/5 bg-gradient-to-r from-vibrant-blue/10 to-transparent flex justify-between items-center">
            <div>
                <h3 class="text-lg font-black tracking-tighter uppercase italic leading-none">{{ satellite.name }}</h3>
                <p class="text-[8px] font-mono text-white/40 uppercase tracking-[0.3em] mt-1.5">NORAD_ID: {{ satellite.norad_id }} / OP_STATUS: {{ satellite.status }}</p>
            </div>
            <div class="w-3 h-3 rounded-full shadow-[0_0_10px_currentColor] animate-pulse" :style="{ color: statusColor, backgroundColor: 'currentColor' }"></div>
        </div>

        <!-- Telemetry Grid -->
        <div class="p-6 space-y-6">
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <p class="text-[8px] font-black text-white/30 uppercase tracking-widest">Velocity</p>
                    <div class="flex items-baseline space-x-1">
                        <span class="text-xl font-black text-white leading-none">{{ satellite.velocity || '7.6' }}</span>
                        <span class="text-[10px] font-bold text-white/20">KM/S</span>
                    </div>
                </div>
                <div class="space-y-1 text-right">
                    <p class="text-[8px] font-black text-white/30 uppercase tracking-widest">Altitude</p>
                    <div class="flex items-baseline justify-end space-x-1">
                        <span class="text-xl font-black text-white leading-none">{{ satellite.altitude || '550' }}</span>
                        <span class="text-[10px] font-bold text-white/20">KM</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <p class="text-[8px] font-black text-white/30 uppercase tracking-widest">Orbital Period</p>
                    <div class="flex items-baseline space-x-1">
                        <span class="text-xl font-black text-vibrant-blue leading-none">{{ satellite.period || '95.4' }}</span>
                        <span class="text-[10px] font-bold text-vibrant-blue/30">MIN</span>
                    </div>
                </div>
                <div class="space-y-1 text-right">
                    <p class="text-[8px] font-black text-white/30 uppercase tracking-widest">Inclination</p>
                    <div class="flex items-baseline justify-end space-x-1">
                        <span class="text-xl font-black text-white/80 leading-none">53.0</span>
                        <span class="text-[10px] font-bold text-white/20">DEG</span>
                    </div>
                </div>
            </div>

            <!-- Real-time Payload Metrics -->
            <div v-if="satellite.payload" class="grid grid-cols-3 gap-2 bg-vibrant-blue/5 border border-vibrant-blue/20 rounded-xl p-3 animate-in zoom-in-95 duration-300">
                <div class="text-center">
                    <p class="text-[7px] font-black text-white/40 uppercase">IR_BRIGHT</p>
                    <p class="text-xs font-black text-vibrant-blue">{{ satellite.payload.brightness || '182' }}</p>
                </div>
                <div class="text-center border-x border-vibrant-blue/10">
                    <p class="text-[7px] font-black text-white/40 uppercase">PRESSURE</p>
                    <p class="text-xs font-black text-white">{{ satellite.payload.pressure || '1013' }} <span class="text-[6px] text-white/20">hPa</span></p>
                </div>
                <div class="text-center">
                    <p class="text-[7px] font-black text-white/40 uppercase">S_TEMP</p>
                    <p class="text-xs font-black text-orange-400">{{ satellite.payload.temperature || '24.5' }}°C</p>
                </div>
            </div>

            <!-- Coordinates -->
            <div class="bg-white/5 border border-white/10 rounded-xl p-4 flex justify-between items-center group relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-vibrant-blue/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                <div class="space-y-1 relative z-10">
                    <p class="text-[8px] font-black text-white/30 uppercase tracking-widest">Latitude</p>
                    <p class="text-xs font-mono font-bold text-white/80 tracking-widest">{{ formatCoord(satellite.latitude) }}°N</p>
                </div>
                <div class="w-px h-8 bg-white/10"></div>
                <div class="space-y-1 text-right relative z-10">
                    <p class="text-[8px] font-black text-white/30 uppercase tracking-widest">Longitude</p>
                    <p class="text-xs font-mono font-bold text-white/80 tracking-widest">{{ formatCoord(satellite.longitude) }}°E</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="grid grid-cols-2 gap-3 pt-2">
                <button class="bg-vibrant-blue text-white text-[9px] font-black uppercase tracking-[0.2em] py-3 rounded-lg hover:bg-vibrant-blue/80 transition-all border border-vibrant-blue shadow-[0_0_15px_rgba(0,136,255,0.3)]">Mission_Details</button>
                <button class="bg-white/5 text-white/60 text-[9px] font-black uppercase tracking-[0.2em] py-3 rounded-lg hover:bg-white/10 transition-all border border-white/10" @click="$emit('show-history')">Time_Machine</button>
            </div>
        </div>

        <!-- Footer Decoration -->
        <div class="h-1 bg-gradient-to-r from-vibrant-blue via-white/20 to-vibrant-blue opacity-50"></div>
    </div>
</template>

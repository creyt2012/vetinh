<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    alerts: Array
});

const getSeverityClass = (metadata) => {
    const level = metadata?.level || 'INFO';
    if (level === 'CRITICAL') return 'text-red-500 border-red-500/20 bg-red-500/5';
    if (level === 'WARNING') return 'text-yellow-500 border-yellow-500/20 bg-yellow-500/5';
    return 'text-vibrant-blue border-vibrant-blue/20 bg-vibrant-blue/5';
};
</script>

<template>
    <Head title="My Intelligence Alerts" />

    <UserLayout>
        <div class="space-y-10">
            <!-- Header -->
            <div class="flex justify-between items-end">
                <div>
                    <h2 class="text-3xl font-black uppercase tracking-tighter italic">PERSONAL_ALERT_FEED</h2>
                    <p class="text-[10px] text-vibrant-blue uppercase tracking-[0.4em] font-black mt-1">Surveillance Logs & Risk Notifications</p>
                </div>
                <div class="flex space-x-4">
                    <button class="px-6 py-2 bg-white/5 border border-white/10 text-[9px] font-black uppercase tracking-widest hover:bg-white/10 transition-all">MARK_ALL_READ</button>
                    <button class="px-6 py-2 bg-vibrant-blue text-white text-[9px] font-black uppercase tracking-widest hover:scale-105 transition-all">CONFIGURE_NOTIFICATIONS</button>
                </div>
            </div>

            <!-- Alerts Stream -->
            <div class="space-y-4">
                <div v-if="alerts.length === 0" class="h-64 border border-white/5 bg-[#050508] flex items-center justify-center text-white/20 uppercase text-[10px] tracking-[0.4em] font-black italic">
                    NO_ACTIVE_THREATS_DETECTED
                </div>

                <div v-for="alert in alerts" :key="alert.id" 
                    :class="getSeverityClass(alert.metadata)"
                    class="p-6 border flex items-center justify-between group relative overflow-hidden transition-all hover:scale-[1.01]">
                    
                    <div class="flex items-center space-x-8 relative z-10">
                        <div class="text-right w-32 border-r border-current/20 pr-6">
                            <p class="text-[10px] font-black uppercase opacity-60">TIMESTAMP</p>
                            <p class="text-[11px] font-mono font-bold">{{ new Date(alert.created_at).toLocaleTimeString() }}</p>
                        </div>
                        
                        <div>
                            <h4 class="text-sm font-black uppercase tracking-widest mb-1">{{ alert.metadata?.type || 'SYSTEM_NOTIFICATION' }}</h4>
                            <p class="text-[11px] font-medium opacity-80">{{ alert.description }}</p>
                        </div>
                    </div>

                    <div class="relative z-10 hidden md:block text-right">
                        <p class="text-[9px] font-black uppercase opacity-40">Verification_Status</p>
                        <p class="text-[10px] font-black uppercase italic tracking-widest">{{ alert.metadata?.level || 'INFO' }}_RESOLVED</p>
                    </div>

                    <!-- Aesthetics -->
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/[0.01] to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                </div>
            </div>
            
            <div class="pt-10 border-t border-white/5 flex items-center justify-center text-[9px] font-black text-white/20 uppercase tracking-[0.5em]">
                END_OF_INTELLIGENCE_STREAM
            </div>
        </div>
    </UserLayout>
</template>

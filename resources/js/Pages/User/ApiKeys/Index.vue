<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    apiKeys: Array
});
</script>

<template>
    <Head title="API Intelligence Portal" />

    <UserLayout>
        <div class="max-w-4xl mx-auto space-y-12">
            <!-- Header -->
            <div class="text-center">
                <h2 class="text-4xl font-black uppercase tracking-tighter italic text-vibrant-blue mb-2">INTELLIGENCE_API_PORTAL</h2>
                <p class="text-[11px] text-white/40 uppercase tracking-[0.4em] font-black">Direct Access Layer for Weather & Orbital Data</p>
            </div>

            <!-- API Keys Section -->
            <div class="bg-[#050508] border border-white/5 p-10 space-y-8">
                <div class="flex justify-between items-end border-b border-white/5 pb-6">
                    <div>
                        <h4 class="text-sm font-black uppercase tracking-widest">ACTIVE_ACCESS_TOKENS</h4>
                        <p class="text-[9px] text-white/20 uppercase tracking-widest mt-1">Manage your integration credentials</p>
                    </div>
                    <button class="px-8 py-3 bg-vibrant-blue text-white text-[10px] font-black uppercase tracking-widest hover:scale-105 transition-all shadow-[0_0_20px_rgba(0,136,255,0.2)]">
                        GENERATE_NEW_SECRET
                    </button>
                </div>

                <div v-if="apiKeys.length === 0" class="py-12 text-center">
                    <p class="text-[10px] font-black uppercase text-white/10 tracking-[0.3em] italic">NO_ACTIVE_TOKENS_FOUND</p>
                </div>

                <div v-for="key in apiKeys" :key="key.id" class="p-6 bg-white/[0.02] border border-white/5 flex items-center justify-between group">
                    <div>
                        <div class="flex items-center space-x-3 mb-2">
                            <span class="w-2 h-2 rounded-full bg-vibrant-green"></span>
                            <h5 class="text-sm font-black uppercase italic">{{ key.name }}</h5>
                        </div>
                        <p class="text-[10px] font-mono text-white/30 tracking-widest uppercase truncate max-w-md">{{ key.key_prefix }}••••••••••••••••</p>
                    </div>
                    
                    <div class="flex space-x-4">
                        <div class="text-right">
                            <p class="text-[8px] font-black text-white/20 uppercase">Last_Active</p>
                            <p class="text-[10px] font-black uppercase text-vibrant-blue">{{ key.last_used_at ? new Date(key.last_used_at).toLocaleDateString() : 'NEVER' }}</p>
                        </div>
                        <button class="p-2 bg-white/5 hover:bg-red-500/20 text-white/40 hover:text-red-500 transition-all border border-white/5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Documentation Link -->
            <div class="p-10 border border-vibrant-blue/20 bg-vibrant-blue/5 flex items-center justify-between">
                <div>
                    <h4 class="text-lg font-black uppercase tracking-tighter italic">INTEGRATION_GUIDE_V1.4</h4>
                    <p class="text-[10px] text-white/40 uppercase tracking-widest mt-1">RESTful endpoints, Authentication & Rate-limits</p>
                </div>
                <button class="px-10 py-4 border border-vibrant-blue text-vibrant-blue text-[10px] font-black uppercase tracking-widest hover:bg-vibrant-blue hover:text-white transition-all">
                    VIEW_DOCUMENTATION
                </button>
            </div>
        </div>
    </UserLayout>
</template>

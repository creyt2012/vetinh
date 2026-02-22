<script setup>
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const isMenuOpen = ref(false);

const navigation = [
    { name: 'DASHBOARD', route: 'dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { name: 'WEATHER_MAP', route: 'weather.map', icon: 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7l5-2.5 5.553 2.776a1 1 0 01.447.894v10.764a1 1 0 01-1.447.894L15 17l-6 3z' },
    { name: 'MARINE_TRACKING', route: 'user.marine.index', icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
    { name: 'ORBITAL_SAFETY', route: 'user.orbital.safety', icon: 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z' },
    { name: 'SYSTEM_HEALTH', route: 'user.health.index', icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' },
    { name: 'ASSET_DISCOVERY', route: 'stac.discovery', icon: 'M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7C5 4 4 5 4 7zm4-2h8V4H8v1z' },
    { name: 'MY_ALERTS', route: 'alerts.index', icon: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9' },
    { name: 'REPORTS', route: 'user.reports.index', icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
];

const isActive = (route) => {
    // Basic active check logic - could be refined with usePage()
    return false;
};
</script>

<template>
    <div class="h-screen bg-[#020205] text-white font-outfit selection:bg-vibrant-blue/30 overflow-hidden flex flex-col">
        <!-- Top Tactical Bar -->
        <header class="h-16 border-b border-white/5 bg-black/40 backdrop-blur-xl flex items-center justify-between px-8 z-50">
            <div class="flex items-center space-x-4">
                <div class="w-8 h-8 bg-vibrant-blue flex items-center justify-center rounded-sm">
                    <span class="text-xs font-black italic">V</span>
                </div>
                <h1 class="text-sm font-black uppercase tracking-[0.4em] italic">VETINH_INTELLIGENCE</h1>
            </div>

            <!-- Desktop Nav -->
            <nav class="hidden md:flex space-x-12">
                <Link v-for="item in navigation" :key="item.name" :href="route(item.route)" 
                    class="text-[10px] font-black uppercase tracking-[0.2em] transition-all hover:text-vibrant-blue flex items-center space-x-2 opacity-60 hover:opacity-100">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                    </svg>
                    <span>{{ item.name }}</span>
                </Link>
            </nav>

            <div class="flex items-center space-x-6">
                <!-- Status Indicator -->
                <div class="hidden lg:flex items-center space-x-2 px-3 py-1 bg-white/5 border border-white/10 rounded-full">
                    <div class="w-1.5 h-1.5 rounded-full bg-vibrant-green animate-pulse"></div>
                    <span class="text-[8px] font-black uppercase tracking-widest opacity-40">System_Online</span>
                </div>
                
                <Link :href="route('admin.dashboard')" class="text-[10px] font-black uppercase text-vibrant-blue border border-vibrant-blue/30 px-4 py-2 hover:bg-vibrant-blue hover:text-white transition-all">
                    LOGOUT
                </Link>
            </div>
        </header>

        <!-- Main Content Viewport -->
        <main class="flex-1 overflow-y-auto custom-scrollbar relative">
            <div class="p-8 pb-20 max-w-[1600px] mx-auto">
                <slot />
            </div>

            <!-- Global Footer Status -->
            <footer class="fixed bottom-0 left-0 right-0 h-8 bg-black/80 backdrop-blur-md border-t border-white/5 flex items-center justify-between px-8 text-[8px] font-mono text-white/20 uppercase tracking-widest z-50">
                <div class="flex items-center space-x-6">
                    <span class="text-vibrant-blue">DECODING_MODE: OPTIMIZED</span>
                    <span>LATENCY: 14MS</span>
                </div>
                <div class="flex items-center space-x-6">
                    <span>HIMAWARI_9: CONNECTED</span>
                    <span class="text-vibrant-green">Uptime: 99.99%</span>
                </div>
            </footer>
        </main>
    </div>
</template>

<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(0, 136, 255, 0.3);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 136, 255, 0.6);
}
</style>

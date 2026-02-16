<script setup>
import { Link, Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const isSidebarOpen = ref(true);

const navItems = [
    { name: 'Dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', route: 'admin.dashboard' },
    { name: 'Satellites', icon: 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8', route: 'admin.satellites.index' },
    { name: 'API Management', icon: 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z', route: 'admin.apikeys.index' },
    { name: 'User Control', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', route: 'admin.users.index' },
];

const isActive = (routeName) => {
    // Basic active check (can be improved with Ziggy)
    return window.location.pathname.includes(routeName.split('.')[1]);
};

</script>

<template>
    <div class="min-h-screen bg-[#020205] text-white flex font-inter overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-72 border-r border-white/5 bg-[#050508] flex flex-col relative z-20 shadow-[20px_0_50px_rgba(0,0,0,0.5)]">
            <!-- Sidebar Header -->
            <div class="p-8 border-b border-white/5">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="w-3 h-3 bg-vibrant-blue shadow-[0_0_10px_#0088ff] animate-pulse"></div>
                    <p class="text-[10px] font-black tracking-[.4em] text-vibrant-blue uppercase">Admin_Hub</p>
                </div>
                <h1 class="text-2xl font-black font-outfit italic tracking-tighter uppercase leading-none">MISSION<br/><span class="text-vibrant-blue">CONTROL</span></h1>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 p-6 space-y-2 mt-4">
                <Link v-for="item in navItems" :key="item.name" :href="route(item.route)" 
                    :class="[
                        isActive(item.route) ? 'bg-vibrant-blue/10 border-vibrant-blue/30 text-white' : 'text-white/40 hover:text-white hover:bg-white/5 border-transparent'
                    ]"
                    class="flex items-center space-x-4 px-6 py-4 border transition-all duration-300 group overflow-hidden relative">
                    
                    <!-- Hover/Active Glow -->
                    <div v-if="isActive(item.route)" class="absolute left-0 top-0 bottom-0 w-1 bg-vibrant-blue shadow-[5px_0_15px_#0088ff]"></div>
                    
                    <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                    </svg>
                    
                    <span class="text-[11px] font-black uppercase tracking-[0.2em] transition-all">{{ item.name }}</span>
                    
                    <!-- HUD Scanline (Decoration) -->
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-vibrant-blue/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                </Link>
            </nav>

            <!-- Bottom Info -->
            <div class="p-8 border-t border-white/5 space-y-4">
                <div class="bg-white/[0.02] border border-white/5 p-4 rounded-lg">
                    <p class="text-[8px] font-bold text-white/30 uppercase tracking-[0.3em] mb-2">System_Status</p>
                    <div class="flex items-center space-x-2">
                        <div class="w-1.5 h-1.5 rounded-full bg-vibrant-green shadow-[0_0_8px_#00ffaa]"></div>
                        <span class="text-[10px] font-mono font-bold text-vibrant-green tracking-widest">LIVE_SYNC_ACTIVE</span>
                    </div>
                </div>
                
                <Link method="post" href="/logout" as="button" class="w-full py-4 text-[9px] font-black uppercase tracking-[0.3em] bg-white/5 hover:bg-red-500/20 hover:text-red-500 transition-all border border-transparent hover:border-red-500/50"> TERMINATE_SESSION </Link>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto relative custom-scrollbar">
            <!-- Top HUD Bar -->
            <header class="h-20 border-b border-white/5 flex items-center justify-between px-10 bg-[#020205]/80 backdrop-blur-xl sticky top-0 z-10">
                <div class="flex items-center space-x-4">
                    <div class="flex flex-col">
                        <p class="text-[9px] font-bold text-white/20 uppercase tracking-[.4em]">Sector_Navigation</p>
                        <h2 class="text-xs font-black uppercase tracking-widest text-white/80"><slot name="header"></slot></h2>
                    </div>
                </div>

                <div class="flex items-center space-x-6">
                    <div class="text-right">
                        <p class="text-[8px] font-bold text-white/20 uppercase tracking-widest">Operator_Auth</p>
                        <p class="text-[10px] font-black uppercase tracking-widest text-vibrant-blue italic text-vibrant-blue">CREYT_DEV_MASTER</p>
                    </div>
                    <div class="w-10 h-10 rounded-full border border-vibrant-blue/30 bg-vibrant-blue/10 flex items-center justify-center p-2 overflow-hidden shadow-[0_0_15px_rgba(0,136,255,0.2)]">
                        <img src="https://api.dicebear.com/7.x/pixel-art/svg?seed=Admin" class="w-full h-full opacity-80" />
                    </div>
                </div>
            </header>

            <!-- Page Content Slot -->
            <div class="p-10 max-w-7xl mx-auto min-h-[calc(100vh-80px)]">
                <slot />
            </div>

            <!-- Background Aesthetic -->
            <div class="fixed top-0 right-0 w-[500px] h-[500px] bg-vibrant-blue/5 blur-[150px] -z-10 pointer-events-none"></div>
            <div class="fixed bottom-0 left-0 w-[300px] h-[300px] bg-vibrant-blue/5 blur-[120px] -z-10 pointer-events-none"></div>
        </main>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Outfit:wght@400;700;900&display=swap');

.font-outfit { font-family: 'Outfit', sans-serif; }
.font-inter { font-family: 'Inter', sans-serif; }
.text-vibrant-blue { color: #0088ff; }
.bg-vibrant-blue { background-color: #0088ff; }
.border-vibrant-blue { border-color: #0088ff; }
.text-vibrant-green { color: #00ffaa; }
.bg-vibrant-green { background-color: #00ffaa; }

.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #020205;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(0, 136, 255, 0.2);
    border-radius: 20px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #0088ff;
}
</style>

<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head } from '@inertiajs/vue3';

const endpoints = [
    { method: 'GET', path: '/api/v1/weather/realtime', desc: 'Fetch latest telemetry from all ground stations' },
    { method: 'GET', path: '/api/v1/satellite/tle', desc: 'Retrieve Two-Line Element data for orbital tracking' },
    { method: 'POST', path: '/api/v1/alerts/subscribe', desc: 'Register webhook for real-time threat notifications' },
    { method: 'GET', path: '/api/v1/forecast/storm-path', desc: 'Get predicted trajectory for active cyclogenesis' },
];
</script>

<template>
    <Head title="API Documentation Hub" />

    <UserLayout>
        <div class="max-w-5xl mx-auto space-y-12">
            <!-- Header -->
            <div class="border-b border-white/5 pb-8">
                <h2 class="text-4xl font-black uppercase tracking-tighter italic text-vibrant-blue mb-4">API_DOCUMENT_HUB_V1</h2>
                <p class="text-sm text-white/40 uppercase tracking-[0.4em] font-black leading-relaxed">
                    Build predictive intelligence into your own infrastructure using our low-latency RESTful interface.
                </p>
            </div>

            <div class="grid grid-cols-12 gap-10">
                <!-- Sidebar Nav -->
                <div class="col-span-12 lg:col-span-3 space-y-6">
                    <div class="space-y-4">
                        <p class="text-[10px] font-black text-vibrant-blue uppercase tracking-widest">GETTING_STARTED</p>
                        <nav class="flex flex-col space-y-2">
                            <a href="#" class="text-[11px] font-bold uppercase text-white/60 hover:text-white transition-colors underline decoration-vibrant-blue/30">Authentication</a>
                            <a href="#" class="text-[11px] font-bold uppercase text-white/60 hover:text-white transition-colors">Rate Limits</a>
                            <a href="#" class="text-[11px] font-bold uppercase text-white/60 hover:text-white transition-colors">Error Codes</a>
                        </nav>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="col-span-12 lg:col-span-9 space-y-12">
                    <!-- Auth Section -->
                    <section class="space-y-4">
                        <h3 class="text-sm font-black uppercase tracking-widest flex items-center">
                            <span class="w-2 h-2 bg-vibrant-blue mr-3"></span>
                            AUTHENTICATION
                        </h3>
                        <div class="p-6 bg-white/[0.02] border border-white/5 font-mono text-[11px] leading-relaxed text-white/60">
                            <p>All requests must include the <span class="text-vibrant-blue">X-SATELLITE-KEY</span> header with your personal access token.</p>
                            <div class="mt-4 p-4 bg-black/40 border border-white/5">
                                curl -H "X-SATELLITE-KEY: your_secret_key" https://api.vetinh.com/v1/weather/realtime
                            </div>
                        </div>
                    </section>

                    <!-- Endpoints Section -->
                    <section class="space-y-6">
                        <h3 class="text-sm font-black uppercase tracking-widest flex items-center">
                            <span class="w-2 h-2 bg-vibrant-blue mr-3"></span>
                            CORE_ENDPOINTS
                        </h3>
                        <div class="space-y-4">
                            <div v-for="ep in endpoints" :key="ep.path" class="border border-white/5 hover:border-vibrant-blue/30 transition-all overflow-hidden">
                                <div class="flex items-center space-x-4 p-4 bg-white/[0.01]">
                                    <span class="px-3 py-1 bg-vibrant-blue text-white text-[9px] font-black italic">{{ ep.method }}</span>
                                    <span class="text-[11px] font-mono font-bold text-white/80">{{ ep.path }}</span>
                                </div>
                                <div class="p-4 bg-black/20">
                                    <p class="text-[10px] text-white/40 uppercase tracking-widest">{{ ep.desc }}</p>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </UserLayout>
</template>

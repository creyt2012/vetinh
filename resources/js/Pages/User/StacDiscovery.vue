<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { Search, Database, Layers, Calendar, MapPin } from 'lucide-vue-next';

const catalog = ref(null);
const collections = ref([]);
const isLoading = ref(true);

const fetchCatalog = async () => {
    try {
        const res = await axios.get('/api/v1/stac', {
            headers: { 'X-API-KEY': 'vetinh_dev_key_123' }
        });
        catalog.value = res.data;
        
        const colRes = await axios.get('/api/v1/stac/collections', {
            headers: { 'X-API-KEY': 'vetinh_dev_key_123' }
        });
        collections.value = colRes.data.collections;
    } catch (e) {
        console.error('STAC fetch failed', e);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    fetchCatalog();
});
</script>

<template>
    <Head title="STAC Discovery" />

    <UserLayout>
        <div class="space-y-10">
            <!-- Header -->
            <div class="flex justify-between items-end border-b border-white/5 pb-10">
                <div>
                    <h2 class="text-4xl font-black uppercase tracking-tighter italic leading-none text-white">ASSET_DISCOVERY</h2>
                    <p class="text-xs text-vibrant-blue uppercase tracking-[0.4em] font-black mt-2">SpatioTemporal Asset Catalog (STAC) Explorer</p>
                </div>
                <div class="flex space-x-4">
                    <div class="relative">
                        <input type="text" placeholder="SEARCH_ASSETS..." class="bg-white/5 border border-white/10 px-6 py-2 text-[10px] font-black uppercase tracking-widest focus:border-vibrant-blue focus:ring-0 w-64 transition-all">
                        <Search class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-white/20" />
                    </div>
                </div>
            </div>

            <!-- Discovery Grid -->
            <div class="grid grid-cols-12 gap-10">
                <!-- Main Catalog View -->
                <div class="col-span-12 lg:col-span-8 space-y-10">
                    <div v-if="isLoading" class="h-64 flex items-center justify-center">
                         <div class="w-12 h-12 border-2 border-vibrant-blue border-t-transparent rounded-full animate-spin"></div>
                    </div>

                    <div v-else class="space-y-12">
                        <!-- Collections Section -->
                        <section>
                            <h3 class="text-[10px] font-black text-vibrant-blue uppercase tracking-[0.3em] mb-6 flex items-center">
                                <Layers class="w-4 h-4 mr-2" />
                                ACTIVE_COLLECTIONS
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="col in collections" :key="col.id" 
                                    class="bg-[#050508] border border-white/5 p-8 hover:border-vibrant-blue/30 transition-all group">
                                    <h4 class="text-sm font-black uppercase italic text-white group-hover:text-vibrant-blue transition-colors mb-2">{{ col.title }}</h4>
                                    <p class="text-[10px] text-white/40 leading-relaxed line-clamp-2 mb-6">{{ col.description }}</p>
                                    
                                    <div class="flex justify-between items-center text-[8px] font-black uppercase tracking-widest text-white/20">
                                        <div class="flex items-center space-x-2">
                                            <Calendar class="w-3 h-3" />
                                            <span>Spatial Indexing ACTIVE</span>
                                        </div>
                                        <Link :href="col.links?.find(l => l.rel === 'self')?.href || '#'" class="text-vibrant-blue hover:underline">EXPLORE_ITEMS</Link>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Static Asset Preview (Mock for now) -->
                        <section class="opacity-40 grayscale pointer-events-none">
                            <h3 class="text-[10px] font-black text-white/20 uppercase tracking-[0.3em] mb-6 flex items-center">
                                <MapPin class="w-4 h-4 mr-2" />
                                RECENT_ASSET_ACQUISITION
                            </h3>
                            <div class="h-48 border border-dashed border-white/10 flex items-center justify-center text-[9px] font-black uppercase tracking-[0.5em]">
                                INTEGRATING_EO_GEOTIFF_INDEX...
                            </div>
                        </section>
                    </div>
                </div>

                <!-- Sidebar Controls -->
                <div class="col-span-12 lg:col-span-4 space-y-8">
                    <div class="bg-black border border-white/5 p-8">
                        <h3 class="text-sm font-black uppercase tracking-widest mb-8 flex items-center text-vibrant-blue">
                            <Database class="w-4 h-4 mr-3" />
                            CATALOG_METRICS
                        </h3>
                        <div class="space-y-6">
                            <div class="flex justify-between items-center border-b border-white/5 pb-4">
                                <span class="text-[9px] font-black text-white/20 uppercase">STAC_VERSION</span>
                                <span class="text-[10px] font-mono font-bold">{{ catalog?.stac_version || '1.0.0' }}</span>
                            </div>
                            <div class="flex justify-between items-center border-b border-white/5 pb-4">
                                <span class="text-[9px] font-black text-white/20 uppercase">INDEXED_ASSETS</span>
                                <span class="text-[10px] font-mono font-bold">14,282</span>
                            </div>
                             <div class="flex justify-between items-center">
                                <span class="text-[9px] font-black text-white/20 uppercase">STORAGE_CONSUMED</span>
                                <span class="text-[10px] font-mono font-bold">1.2 TB</span>
                            </div>
                        </div>
                    </div>

                    <div class="p-8 border border-vibrant-blue/20 bg-vibrant-blue/5">
                        <p class="text-[9px] font-black text-vibrant-blue uppercase tracking-widest mb-2">PRO_DISCOVERY_TIP</p>
                        <p class="text-[11px] font-medium text-white/60 leading-relaxed">Use the bounding box search (bbox) to filter assets geographically for faster retrieval in mission-critical orbits.</p>
                    </div>
                </div>
            </div>
        </div>
    </UserLayout>
</template>

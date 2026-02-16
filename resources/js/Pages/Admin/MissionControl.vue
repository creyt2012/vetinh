<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, onMounted } from 'vue';
import axios from 'axios';

const files = ref([]);
const uploading = ref(false);
const uploadType = ref('EXCEL_WEATHER');
const fileInput = ref(null);

const fetchFiles = async () => {
    try {
        const response = await axios.get('/api/v1/mission-control/files', {
            headers: { 'X-API-KEY': 'vetinh_dev_key_123' }
        });
        files.value = response.data.data.data;
    } catch (e) {
        console.error("Failed to fetch mission files", e);
    }
};

const handleUpload = async () => {
    if (!fileInput.value.files[0]) return;
    
    uploading.value = true;
    const formData = new FormData();
    formData.append('file', fileInput.value.files[0]);
    formData.append('type', uploadType.value);

    try {
        await axios.post('/api/v1/mission-control/upload', formData, {
            headers: { 
                'X-API-KEY': 'vetinh_dev_key_123',
                'Content-Type': 'multipart/form-data'
            }
        });
        alert("File uploaded successfully. Processing started.");
        fetchFiles();
    } catch (e) {
        alert("Upload failed: " + (e.response?.data?.message || e.message));
    } finally {
        uploading.value = false;
        fileInput.value.value = '';
    }
};

onMounted(fetchFiles);
</script>

<template>
    <AdminLayout title="Mission Control">
        <div class="p-8 space-y-8">
            <header class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-black tracking-tighter uppercase italic">Mission Control</h1>
                    <p class="text-xs font-mono text-white/40 uppercase tracking-widest mt-1">Data Ingestion & Asset Synchronization</p>
                </div>
            </header>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Upload Panel -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-black/40 border border-white/10 rounded-2xl p-6 backdrop-blur-xl">
                        <h2 class="text-sm font-black uppercase tracking-widest mb-6 flex items-center space-x-2">
                            <span class="w-2 h-2 rounded-full bg-vibrant-blue shadow-[0_0_8px_#4f46e5]"></span>
                            <span>Ingest New Asset</span>
                        </h2>

                        <div class="space-y-4">
                            <div>
                                <label class="text-[10px] font-mono text-white/40 uppercase block mb-1.5">Ingestion Type</label>
                                <select v-model="uploadType" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-vibrant-blue transition-all outline-none">
                                    <option value="EXCEL_WEATHER">METEOROLOGICAL BATCH (XLSX)</option>
                                    <option value="GEOJSON_RISK">GEOSPATIAL RISK AREA (GEOJSON)</option>
                                </select>
                            </div>

                            <div>
                                <label class="text-[10px] font-mono text-white/40 uppercase block mb-1.5">Source File</label>
                                <input type="file" ref="fileInput" class="hidden" @change="handleUpload" />
                                <button @click="fileInput.click()" 
                                        :disabled="uploading"
                                        class="w-full h-32 border-2 border-dashed border-white/10 rounded-2xl flex flex-col items-center justify-center space-y-2 hover:border-vibrant-blue/50 hover:bg-vibrant-blue/5 transition-all group">
                                    <div v-if="!uploading" class="text-center">
                                        <svg class="w-8 h-8 text-white/20 mx-auto group-hover:text-vibrant-blue transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        <span class="text-[10px] font-black text-white/40 uppercase tracking-widest mt-2 block">Cick to select or drop file</span>
                                    </div>
                                    <div v-else class="flex items-center space-x-2">
                                        <div class="w-4 h-4 border-2 border-vibrant-blue border-t-transparent rounded-full animate-spin"></div>
                                        <span class="text-[10px] font-black uppercase tracking-widest text-vibrant-blue">Uploading...</span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Ingestions -->
                <div class="lg:col-span-2">
                    <div class="bg-black/40 border border-white/10 rounded-2xl overflow-hidden backdrop-blur-xl">
                        <div class="p-6 border-b border-white/5">
                            <h2 class="text-sm font-black uppercase tracking-widest flex items-center space-x-2">
                                <span class="w-2 h-2 rounded-full bg-vibrant-green shadow-[0_0_8px_#22c55e]"></span>
                                <span>Recent Operations</span>
                            </h2>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="text-[10px] font-mono text-white/20 uppercase tracking-widest">
                                    <tr>
                                        <th class="px-6 py-4">Filename</th>
                                        <th class="px-6 py-4">Type</th>
                                        <th class="px-6 py-4">Status</th>
                                        <th class="px-6 py-4 text-right">Processed At</th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm border-t border-white/5 divide-y divide-white/5">
                                    <tr v-for="file in files" :key="file.id" class="hover:bg-white/[0.02] transition-colors">
                                        <td class="px-6 py-4 font-mono text-xs uppercase">{{ file.original_name }}</td>
                                        <td class="px-6 py-4">
                                            <span class="text-[9px] font-black px-2 py-0.5 rounded bg-white/5 border border-white/10 uppercase">{{ file.type }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center space-x-2">
                                                <span :class="{
                                                    'w-1.5 h-1.5 rounded-full animate-pulse': true,
                                                    'bg-yellow-500': file.status === 'PROCESSING' || file.status === 'PENDING',
                                                    'bg-vibrant-green': file.status === 'COMPLETED',
                                                    'bg-red-500': file.status === 'FAILED'
                                                }"></span>
                                                <span class="text-[10px] font-bold uppercase tracking-tighter">{{ file.status }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right font-mono text-[10px] text-white/40">
                                            {{ file.processed_at || '--' }}
                                        </td>
                                    </tr>
                                    <tr v-if="files.length === 0">
                                        <td colspan="4" class="px-6 py-12 text-center text-white/20 font-mono text-xs uppercase tracking-widest">No active ingestion operations found</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    satellites: Array
});

const editingSatellite = ref(null);
const showAddModal = ref(false);

const form = useForm({
    name: '',
    norad_id: '',
    type: 'COMMUNICATION',
    status: 'ACTIVE',
    tle_line1: '',
    tle_line2: '',
    data_source: '',
    source_url: '',
    dataset_name: '',
    priority: 0,
    api_config: {}
});

const editForm = useForm({
    id: null,
    name: '',
    type: '',
    status: '',
    tle_line1: '',
    tle_line2: '',
    data_source: '',
    source_url: '',
    dataset_name: '',
    priority: 0,
    api_config: {}
});

const submitAdd = () => {
    form.post(route('admin.satellites.store'), {
        onSuccess: () => {
            showAddModal.value = false;
            form.reset();
        }
    });
};

const openEdit = (sat) => {
    editingSatellite.value = sat;
    editForm.id = sat.id;
    editForm.name = sat.name;
    editForm.type = sat.type;
    editForm.status = sat.status;
    editForm.tle_line1 = sat.tle_line1;
    editForm.tle_line2 = sat.tle_line2;
    editForm.data_source = sat.data_source || '';
    editForm.source_url = sat.source_url || '';
    editForm.dataset_name = sat.dataset_name || '';
    editForm.priority = sat.priority || 0;
    editForm.api_config = sat.api_config || {};
};

const submitUpdate = () => {
    editForm.put(route('admin.satellites.update', editForm.id), {
        onSuccess: () => editingSatellite.value = null
    });
};

const deleteSat = (id) => {
    if (confirm('Are you sure you want to remove this orbital asset?')) {
        useForm({}).delete(route('admin.satellites.destroy', id));
    }
};

const types = ['COMMUNICATION', 'NAVIGATION', 'OBSERVATION', 'SCIENTIFIC', 'SPACE_DEBRIS', 'STATION'];
const statuses = ['ACTIVE', 'INACTIVE', 'DECOMMISSIONED'];

// Helper for UI
const route = window.route;
</script>

<template>
    <AdminLayout>
        <template #header>SATELLITE_ASSET_INVENTORY</template>
        <Head title="Mission Control - Backend Administration" />
        
        <div class="flex justify-between items-center mb-10">
            <div>
                <p class="text-vibrant-blue font-black tracking-[.2em] text-[10px] uppercase mb-1">Orbital_Fleet_Status</p>
                <h3 class="text-3xl font-black font-outfit uppercase tracking-tighter italic">ACTIVE_ASSETS</h3>
            </div>
            <button @click="showAddModal = true" class="px-8 py-4 bg-vibrant-blue text-black hover:bg-white transition uppercase text-[10px] font-black tracking-[0.3em] shadow-[0_0_30px_rgba(0,136,255,0.3)]">Deploy_New_Asset</button>
        </div>

        <!-- Inventory Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div v-for="sat in satellites" :key="sat.id" 
                class="group relative bg-[#08080C] border border-white/5 p-8 hover:border-vibrant-blue/50 transition-all duration-700 overflow-hidden shadow-2xl">
                
                <div class="relative z-10">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <p class="text-[10px] text-vibrant-blue font-black mb-1 opacity-60">ID: {{ sat.norad_id }} • P: {{ sat.priority }}</p>
                            <h3 class="text-2xl font-black uppercase italic tracking-tight text-white group-hover:text-vibrant-blue transition-colors">{{ sat.name }}</h3>
                        </div>
                        <span :class="{
                            'bg-vibrant-green/10 text-vibrant-green border-vibrant-green/30': sat.status === 'ACTIVE',
                            'bg-red-500/10 text-red-500 border-red-500/30': sat.status !== 'ACTIVE'
                        }" class="px-3 py-1 border text-[9px] font-black uppercase tracking-widest">
                            {{ sat.status }}
                        </span>
                    </div>

                    <div class="space-y-4 mb-8">
                        <div class="flex justify-between border-b border-white/5 pb-2">
                            <span class="text-[9px] text-white/30 uppercase font-bold tracking-widest">Source_Origin</span>
                            <span class="text-[10px] font-mono font-bold text-white/80 uppercase">{{ sat.data_source || 'UNCATEGORIZED' }}</span>
                        </div>
                        <div class="flex justify-between border-b border-white/5 pb-2">
                            <span class="text-[9px] text-white/30 uppercase font-bold tracking-widest">Dataset_ID</span>
                            <span class="text-[10px] font-mono font-bold text-white/80 lowercase truncate max-w-[150px]">{{ sat.dataset_name || 'nil' }}</span>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2 pt-2">
                        <button @click="openEdit(sat)" class="flex-1 py-3 text-[9px] font-black uppercase tracking-[0.2em] bg-white/5 hover:bg-vibrant-blue hover:text-black transition-all duration-300">ADMIN_CONFIG</button>
                        <button @click="deleteSat(sat.id)" class="px-5 py-3 text-[10px] font-black uppercase text-red-500/40 hover:text-red-500 hover:bg-red-500/10 transition-all font-mono">X</button>
                    </div>
                </div>

                <!-- HUD Decorative elements -->
                <div class="absolute top-0 right-0 w-16 h-[2px] bg-vibrant-blue/20 group-hover:bg-vibrant-blue transition-colors"></div>
                <div class="absolute top-0 right-0 w-[2px] h-16 bg-vibrant-blue/20 group-hover:bg-vibrant-blue transition-colors"></div>
            </div>
        </div>

        <!-- Edit Modal (Mission Config) -->
        <div v-if="editingSatellite" class="fixed inset-0 bg-black/95 backdrop-blur-xl z-[100] flex items-center justify-center p-6">
            <div class="w-full max-w-4xl bg-[#08080C] border border-white/10 rounded-2xl overflow-hidden shadow-[0_0_100px_rgba(0,0,0,1)]">
                <div class="p-8 bg-vibrant-blue/5 border-b border-white/10 flex justify-between items-center">
                    <h2 class="text-4xl font-black uppercase italic tracking-tighter">ASSET_MISSION_CONFIG: <span class="text-vibrant-blue">{{ editForm.name }}</span></h2>
                    <button @click="editingSatellite = null" class="w-12 h-12 rounded-full border border-white/10 flex items-center justify-center hover:bg-white/10 transition-all font-mono">ESC</button>
                </div>
                
                <form @submit.prevent="submitUpdate" class="p-10 grid grid-cols-2 gap-10 max-h-[80vh] overflow-y-auto custom-scrollbar">
                    <!-- Core Identity -->
                    <div class="space-y-8">
                        <h3 class="text-[11px] font-black uppercase tracking-[0.5em] text-vibrant-blue mb-4">Core_Telemetry</h3>
                        <div class="space-y-4">
                            <div class="space-y-1">
                                <label class="text-[10px] uppercase font-black text-white/40 tracking-widest">Satellite Designation</label>
                                <input v-model="editForm.name" type="text" class="w-full bg-white/5 border border-white/10 px-5 py-4 text-sm font-bold focus:border-vibrant-blue outline-none transition transition-all" />
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="text-[10px] uppercase font-black text-white/40 tracking-widest">Operation_Type</label>
                                    <select v-model="editForm.type" class="w-full bg-[#101015] border border-white/10 px-5 py-4 text-xs font-bold focus:border-vibrant-blue outline-none uppercase cursor-pointer hover:bg-white/5 transition">
                                        <option v-for="t in types" :key="t" :value="t">{{ t }}</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] uppercase font-black text-white/40 tracking-widest">Mission_Status</label>
                                    <select v-model="editForm.status" class="w-full bg-[#101015] border border-white/10 px-5 py-4 text-xs font-bold focus:border-vibrant-blue outline-none uppercase cursor-pointer hover:bg-white/5 transition">
                                        <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <h3 class="text-[11px] font-black uppercase tracking-[0.5em] text-vibrant-blue pt-4">Orbital_Parameters</h3>
                        <div class="space-y-4">
                            <div class="space-y-1">
                                <label class="text-[10px] uppercase font-black text-white/40 tracking-widest">TLE_DATA_STR_1</label>
                                <textarea v-model="editForm.tle_line1" rows="2" class="w-full bg-white/5 border border-white/10 px-5 py-4 text-[11px] font-mono focus:border-vibrant-blue outline-none transition resize-none"></textarea>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] uppercase font-black text-white/40 tracking-widest">TLE_DATA_STR_2</label>
                                <textarea v-model="editForm.tle_line2" rows="2" class="w-full bg-white/5 border border-white/10 px-5 py-4 text-[11px] font-mono focus:border-vibrant-blue outline-none transition resize-none"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Mission Config -->
                    <div class="space-y-8 bg-white/[0.01] p-8 border border-white/5 rounded-2xl">
                        <h3 class="text-[11px] font-black uppercase tracking-[0.5em] text-vibrant-blue">Intelligence_Sourcing</h3>
                        <div class="space-y-4">
                            <div class="space-y-1">
                                <label class="text-[10px] uppercase font-black text-white/40 tracking-widest">Primary_Data_Origin</label>
                                <input v-model="editForm.data_source" type="text" placeholder="e.g. CELESTRAK, NOAA, JAXA" class="w-full bg-white/5 border border-white/10 px-5 py-4 text-sm font-bold focus:border-vibrant-blue outline-none transition" />
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] uppercase font-black text-white/40 tracking-widest">Source_Payload_URL</label>
                                <input v-model="editForm.source_url" type="text" placeholder="https://..." class="w-full bg-white/5 border border-white/10 px-5 py-4 text-sm font-mono focus:border-vibrant-blue outline-none transition" />
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="text-[10px] uppercase font-black text-white/40 tracking-widest">Dataset_Identifier</label>
                                    <input v-model="editForm.dataset_name" type="text" placeholder="gp-weather-v1" class="w-full bg-white/5 border border-white/10 px-5 py-4 text-sm font-mono focus:border-vibrant-blue outline-none transition" />
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] uppercase font-black text-white/40 tracking-widest">Mission_Priority</label>
                                    <input v-model.number="editForm.priority" type="number" class="w-full bg-white/5 border border-white/10 px-5 py-4 text-sm font-bold focus:border-vibrant-blue outline-none transition" />
                                </div>
                            </div>
                        </div>

                        <div class="pt-10 flex flex-col space-y-4">
                            <button type="submit" class="w-full py-6 bg-vibrant-blue text-black text-xs font-black uppercase tracking-[0.5em] hover:bg-white hover:scale-[1.02] transition-all transform shadow-[0_15px_40px_rgba(0,136,255,0.4)]">INITIALIZE_RECONFIGURATION</button>
                            <button type="button" @click="editingSatellite = null" class="w-full py-4 border border-white/10 text-[10px] font-black uppercase tracking-widest text-white/40 hover:text-white transition-colors">Abort_Action</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Modal (Same styling architecture) -->
        <div v-if="showAddModal" class="fixed inset-0 bg-black/95 backdrop-blur-xl z-[100] flex items-center justify-center p-6">
            <div class="w-full max-w-4xl bg-[#08080C] border border-white/10 rounded-2xl overflow-hidden shadow-[0_0_100px_rgba(0,0,0,1)]">
                <div class="p-8 bg-vibrant-blue/5 border-b border-white/10 flex justify-between items-center">
                    <h2 class="text-4xl font-black uppercase italic tracking-tighter text-white">DEPLOY_NEW_MISSION_ASSET</h2>
                    <button @click="showAddModal = false" class="w-12 h-12 rounded-full border border-white/10 flex items-center justify-center hover:bg-white/10 transition-all font-mono">ESC</button>
                </div>
                
                <form @submit.prevent="submitAdd" class="p-10 grid grid-cols-2 gap-10 max-h-[80vh] overflow-y-auto custom-scrollbar">
                    <div class="space-y-8">
                        <div class="space-y-4">
                            <div class="space-y-1">
                                <label class="text-[10px] uppercase font-black text-white/40 tracking-widest">Satellite Designation</label>
                                <input v-model="form.name" type="text" placeholder="Satellite Name" class="w-full bg-white/5 border border-white/10 px-5 py-4 text-sm font-bold focus:border-vibrant-blue outline-none transition" />
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="text-[10px] uppercase font-black text-white/40 tracking-widest">NORAD_TRACKING_ID</label>
                                    <input v-model="form.norad_id" type="text" placeholder="NORAD ID" class="w-full bg-white/5 border border-white/10 px-5 py-4 text-sm font-mono focus:border-vibrant-blue outline-none transition" />
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] uppercase font-black text-white/40 tracking-widest">Operation_Type</label>
                                    <select v-model="form.type" class="w-full bg-[#101015] border border-white/10 px-5 py-4 text-xs font-bold focus:border-vibrant-blue outline-none uppercase transition">
                                        <option v-for="t in types" :key="t" :value="t">{{ t }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 pt-6 border-t border-white/5">
                            <div class="space-y-1">
                                <label class="text-[10px] uppercase font-black text-white/40 tracking-widest">TLE_DATA_STR_1</label>
                                <textarea v-model="form.tle_line1" rows="2" class="w-full bg-white/5 border border-white/10 px-5 py-4 text-[11px] font-mono focus:border-vibrant-blue outline-none transition resize-none"></textarea>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] uppercase font-black text-white/40 tracking-widest">TLE_DATA_STR_2</label>
                                <textarea v-model="form.tle_line2" rows="2" class="w-full bg-white/5 border border-white/10 px-5 py-4 text-[11px] font-mono focus:border-vibrant-blue outline-none transition resize-none"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-8 bg-white/[0.01] p-8 border border-white/5 rounded-2xl">
                         <div class="space-y-4">
                            <div class="space-y-1">
                                <label class="text-[10px] uppercase font-black text-white/40 tracking-widest">Data_Origin</label>
                                <input v-model="form.data_source" type="text" placeholder="e.g. CELESTRAK" class="w-full bg-white/5 border border-white/10 px-5 py-4 text-sm font-bold focus:border-vibrant-blue outline-none transition" />
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] uppercase font-black text-white/40 tracking-widest">Dataset_Identifier</label>
                                <input v-model="form.dataset_name" type="text" placeholder="weather-main-v1" class="w-full bg-white/5 border border-white/10 px-5 py-4 text-sm font-mono focus:border-vibrant-blue outline-none transition" />
                            </div>
                        </div>

                        <div class="pt-20 flex flex-col space-y-4">
                            <button type="submit" class="w-full py-6 bg-vibrant-blue text-black text-xs font-black uppercase tracking-[0.5em] hover:bg-white hover:scale-[1.02] transition-all transform shadow-[0_15px_40px_rgba(0,136,255,0.4)]">AUTHORIZE_DEPLOYMENT</button>
                            <button type="button" @click="showAddModal = false" class="w-full py-4 border border-white/10 text-[10px] font-black uppercase tracking-widest text-white/40 hover:text-white transition-colors">Cancel_Mission</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </AdminLayout>
</template>

<style scoped>
.font-outfit { font-family: 'Outfit', sans-serif; }
.font-inter { font-family: 'Inter', sans-serif; }
.text-vibrant-blue { color: #0088ff; }
.bg-vibrant-blue { background-color: #0088ff; }
.border-vibrant-blue { border-color: #0088ff; }
.text-vibrant-green { color: #00ffaa; }
.border-vibrant-green { border-color: #00ffaa; }

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(0, 136, 255, 0.2);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 136, 255, 0.5);
}
</style>

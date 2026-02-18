<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { TrendingUp, TrendingDown } from 'lucide-vue-next';

const props = defineProps({
    rules: Array
});

const isModalOpen = ref(false);
const editingRule = ref(null);

const form = useForm({
    name: '',
    parameter: 'temperature',
    operator: '>',
    threshold: 0,
    severity: 'WARNING',
    is_active: true,
    channels: ['telegram']
});

const parameters = [
    { value: 'temperature', label: 'TEMPERATURE' },
    { value: 'humidity', label: 'HUMIDITY' },
    { value: 'pressure', label: 'PRESSURE' },
    { value: 'rain_intensity', label: 'RAIN_INTENSITY' },
    { value: 'wind_speed', label: 'WIND_SPEED' }
];

const operators = [
    { value: '>', label: 'GREATER_THAN (>)' },
    { value: '<', label: 'LESS_THAN (<)' },
    { value: '=', label: 'EQUAL_TO (=)' },
    { value: 'trend_up', label: 'TREND_UP', icon: TrendingUp },
    { value: 'trend_down', label: 'TREND_DOWN', icon: TrendingDown }
];

const severities = ['INFO', 'WARNING', 'CRITICAL'];
const availableChannels = ['telegram', 'zalo', 'slack', 'web_push'];

const openCreateModal = () => {
    editingRule.value = null;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = (rule) => {
    editingRule.value = rule;
    form.name = rule.name;
    form.parameter = rule.parameter;
    form.operator = rule.operator;
    form.threshold = rule.threshold;
    form.severity = rule.severity;
    form.is_active = rule.is_active;
    form.channels = rule.channels || [];
    isModalOpen.value = true;
};

const submit = () => {
    if (editingRule.value) {
        form.put(route('admin.alerts.rules.update', editingRule.value.id), {
            onSuccess: () => isModalOpen.value = false
        });
    } else {
        form.post(route('admin.alerts.rules.store'), {
            onSuccess: () => isModalOpen.value = false
        });
    }
};

const deleteRule = (id) => {
    if (confirm('Deactivate and delete this intelligence rule?')) {
        form.delete(route('admin.alerts.rules.destroy', id));
    }
};

const toggleChannel = (channel) => {
    const index = form.channels.indexOf(channel);
    if (index > -1) {
        form.channels.splice(index, 1);
    } else {
        form.channels.push(channel);
    }
};
</script>

<template>
    <Head title="Condition Rules Management" />

    <AdminLayout>
        <template #header>Intelligence_Condition_Engine</template>

        <div class="space-y-8">
            <!-- Header -->
            <div class="flex justify-between items-end">
                <div>
                    <h3 class="text-2xl font-black uppercase tracking-tighter italic text-vibrant-blue">ALERT_LOGIC_SCHEMAS</h3>
                    <p class="text-xs text-white/40 uppercase tracking-widest mt-1">Multi-Variable Pattern Matching & Risk Thresholds</p>
                </div>
                <button @click="openCreateModal" class="px-8 py-3 bg-white/5 border border-vibrant-blue/30 text-vibrant-blue text-[10px] font-black uppercase tracking-[0.2em] hover:bg-vibrant-blue hover:text-white transition-all shadow-[0_0_20px_rgba(0,136,255,0.1)]">
                    DEFINE_NEW_HEURISTIC
                </button>
            </div>

            <!-- Rules List -->
            <div class="space-y-4">
                <div v-for="rule in rules" :key="rule.id" 
                    class="bg-[#050508] border border-white/5 p-6 flex items-center justify-between group relative overflow-hidden">
                    
                    <div v-if="rule.is_active" class="absolute left-0 top-0 bottom-0 w-1 bg-vibrant-blue shadow-[5px_0_15px_#0088ff]"></div>
                    <div v-else class="absolute left-0 top-0 bottom-0 w-1 bg-white/10"></div>

                    <div class="flex items-center space-x-8">
                        <div class="flex flex-col">
                            <h4 class="text-sm font-black uppercase tracking-widest">{{ rule.name }}</h4>
                            <div class="flex items-center space-x-4 mt-2">
                                <span class="text-[9px] font-mono text-vibrant-blue bg-vibrant-blue/10 px-2 py-0.5 border border-vibrant-blue/20">
                                    IF {{ rule.parameter.toUpperCase() }} {{ rule.operator }} {{ rule.threshold }}
                                </span>
                                <span :class="{
                                    'text-vibrant-blue': rule.severity === 'INFO',
                                    'text-yellow-500': rule.severity === 'WARNING',
                                    'text-red-500': rule.severity === 'CRITICAL'
                                }" class="text-[9px] font-black uppercase tracking-widest">
                                    [{{ rule.severity }}]
                                </span>
                            </div>
                        </div>

                        <div class="flex space-x-1">
                            <div v-for="channel in rule.channels" :key="channel" 
                                class="w-6 h-6 border border-white/10 flex items-center justify-center bg-white/5 grayscale group-hover:grayscale-0 transition-all opacity-40 group-hover:opacity-100"
                                :title="channel.toUpperCase()">
                                <span class="text-[8px] font-black">{{ channel[0].toUpperCase() }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">
                        <div class="text-right mr-4">
                            <p class="text-[8px] font-bold text-white/20 uppercase">STATUS</p>
                            <p :class="rule.is_active ? 'text-vibrant-green' : 'text-white/20'" class="text-[10px] font-black uppercase tracking-widest italic">
                                {{ rule.is_active ? 'ENABLED' : 'DORMANT' }}
                            </p>
                        </div>
                        <div class="flex space-x-2">
                            <button @click="openEditModal(rule)" class="p-2 bg-white/5 hover:bg-vibrant-blue/20 text-white/40 hover:text-white transition-all border border-white/5 hover:border-vibrant-blue/30">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            <button @click="deleteRule(rule.id)" class="p-2 bg-white/5 hover:bg-red-500/20 text-white/40 hover:text-red-500 transition-all border border-white/5 hover:border-red-500/30">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CRUD Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-[#020205]/95 backdrop-blur-md">
            <div class="bg-[#050508] border border-white/10 p-12 max-w-3xl w-full relative shadow-[0_0_150px_rgba(0,136,255,0.05)]">
                <button @click="isModalOpen = false" class="absolute top-8 right-8 text-white/20 hover:text-white uppercase text-[10px] font-black items-center flex space-x-2">
                    <span>DISMISS_INTERFACE</span>
                    <span class="px-2 py-1 bg-white/5 border border-white/10">[ESC]</span>
                </button>
                
                <h3 class="text-2xl font-black uppercase tracking-tighter italic mb-10 text-vibrant-blue">
                    {{ editingRule ? 'PATCH_CONDITION_LATER' : 'INITIALize_INTEL_THRESHOLD' }}
                </h3>

                <form @submit.prevent="submit" class="space-y-8">
                    <div class="space-y-2">
                        <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Logic Name</label>
                        <input v-model="form.name" type="text" placeholder="e.g. HIGH_TEMP_THRESHOLD_HCM" class="w-full bg-white/5 border border-white/10 px-6 py-4 text-xs font-mono focus:border-vibrant-blue outline-none transition-all placeholder:text-white/10" required>
                    </div>

                    <div class="grid grid-cols-3 gap-6">
                        <div class="space-y-2">
                            <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Parameter</label>
                            <select v-model="form.parameter" class="w-full bg-[#0a0a0f] border border-white/10 px-6 py-4 text-[10px] font-black tracking-widest uppercase focus:border-vibrant-blue outline-none transition-all">
                                <option v-for="p in parameters" :key="p.value" :value="p.value">{{ p.label }}</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Operator</label>
                            <select v-model="form.operator" class="w-full bg-[#0a0a0f] border border-white/10 px-6 py-4 text-[10px] font-black tracking-widest uppercase focus:border-vibrant-blue outline-none transition-all">
                                <option v-for="o in operators" :key="o.value" :value="o.value">{{ o.label }}</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[9px] font-black text-white/30 uppercase tracking-widest">Threshold_Value</label>
                            <input v-model="form.threshold" type="number" step="any" class="w-full bg-white/5 border border-white/10 px-6 py-4 text-xs font-mono focus:border-vibrant-blue outline-none transition-all" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-8">
                        <div class="space-y-4">
                            <label class="text-[9px] font-black text-white/30 uppercase tracking-widest border-b border-white/5 pb-2 block">Detection_Severity</label>
                            <div class="flex space-x-4">
                                <button v-for="s in severities" :key="s" type="button" @click="form.severity = s"
                                    :class="form.severity === s ? 'bg-white/10 border-white/40 text-white' : 'bg-transparent border-white/5 text-white/20'"
                                    class="flex-1 py-3 text-[9px] font-black border transition-all">
                                    {{ s }}
                                </button>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <label class="text-[9px] font-black text-white/30 uppercase tracking-widest border-b border-white/5 pb-2 block">Transmission_Channels</label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="c in availableChannels" :key="c" type="button" @click="toggleChannel(c)"
                                    :class="form.channels.includes(c) ? 'bg-vibrant-blue/20 border-vibrant-blue/50 text-vibrant-blue' : 'bg-transparent border-white/5 text-white/20'"
                                    class="px-4 py-2 text-[8px] font-black border transition-all uppercase tracking-widest">
                                    {{ c }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4 pt-4">
                        <button type="submit" :disabled="form.processing" class="flex-1 py-5 bg-vibrant-blue text-white text-[11px] font-black uppercase tracking-[0.4em] font-outfit shadow-[0_0_30px_rgba(0,136,255,0.2)] hover:scale-[1.01] active:scale-95 transition-all disabled:opacity-50">
                            {{ editingRule ? 'PATCH_LOGIC_STORAGE' : 'INITIALIZE_HEURISTIC' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Save, Server, Database, Globe, Zap } from 'lucide-vue-next';

const props = defineProps({
    settings: {
        type: Object,
        required: true
    }
});

// Convert grouped settings to a flat array for the form
const flatSettings = [];
Object.values(props.settings).forEach(group => {
    group.forEach(setting => {
        flatSettings.push({
            id: setting.id,
            key: setting.key,
            value: setting.value,
            type: setting.type,
            group: setting.group,
            description: setting.description
        });
    });
});

const form = useForm({
    settings: flatSettings
});

const submit = () => {
    form.post(route('admin.settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Success handled by flash message in layout
        }
    });
};

const getGroupIcon = (groupName) => {
    switch(groupName) {
        case 'networking': return Server;
        case 'ai_core': return Zap;
        case 'storage': return Database;
        case 'api_limits': return Globe;
        default: return Server;
    }
};

const formatGroupName = (name) => {
    return name.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};
</script>

<template>
    <Head title="System Configuration" />

    <AdminLayout>
        <template #header>
            System Configuration
        </template>

        <div class="py-6 sm:py-8 h-full overflow-y-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <form @submit.prevent="submit" class="space-y-8 pb-20">
                    
                    <div v-for="(groupSettings, groupName) in settings" :key="groupName" class="bg-[#111822] border border-slate-800 rounded-xl overflow-hidden shadow-2xl">
                        <div class="px-6 py-4 border-b border-slate-800 bg-[#0f141e] flex items-center gap-3">
                            <component :is="getGroupIcon(groupName)" class="w-5 h-5 text-emerald-500" />
                            <h3 class="text-lg font-medium text-slate-200 uppercase tracking-wider">{{ formatGroupName(groupName) }} Engine</h3>
                        </div>
                        
                        <div class="p-6 space-y-6">
                            <div v-for="(setting, index) in groupSettings" :key="setting.id" class="flex flex-col md:flex-row md:items-start gap-4 p-4 rounded-lg bg-slate-900/50 border border-slate-800/50 hover:border-emerald-500/30 transition-colors">
                                <div class="flex-grow">
                                    <label :for="'setting_' + setting.id" class="block text-sm font-medium text-slate-300">
                                        {{ setting.key.replace(/_/g, ' ').toUpperCase() }}
                                    </label>
                                    <p class="mt-1 text-xs text-slate-500">{{ setting.description }}</p>
                                </div>
                                <div class="mt-2 md:mt-0 w-full md:w-64 flex-shrink-0">
                                    
                                    <!-- Find the corresponding form field by ID to preserve reactivity -->
                                    <template v-for="(formSetting, fIndex) in form.settings" :key="'f_'+fIndex">
                                        <div v-if="formSetting.id === setting.id">
                                            
                                            <!-- Boolean Toggle -->
                                            <div v-if="setting.type === 'boolean'" class="flex items-center">
                                                <select :id="'setting_' + setting.id" v-model="form.settings[fIndex].value" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-slate-700 focus:outline-none focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm rounded-md bg-slate-800 text-slate-300">
                                                    <option value="true">Enabled (Online)</option>
                                                    <option value="false">Disabled (Offline)</option>
                                                </select>
                                            </div>

                                            <!-- Integer Input -->
                                            <div v-else-if="setting.type === 'integer'" class="relative rounded-md shadow-sm">
                                                <input :id="'setting_' + setting.id" type="number" v-model="form.settings[fIndex].value" class="mt-1 block w-full pr-12 sm:text-sm border-slate-700 rounded-md bg-slate-800 text-slate-200 focus:ring-emerald-500 focus:border-emerald-500" />
                                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                    <span class="text-slate-500 sm:text-xs pt-1">val</span>
                                                </div>
                                            </div>

                                            <!-- String/Default Input -->
                                            <div v-else>
                                                <input :id="'setting_' + setting.id" type="text" v-model="form.settings[fIndex].value" class="mt-1 block w-full sm:text-sm border-slate-700 rounded-md bg-slate-800 text-slate-200 focus:ring-emerald-500 focus:border-emerald-500" />
                                            </div>
                                            
                                        </div>
                                    </template>

                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fixed Action Bar -->
                    <div class="fixed bottom-0 left-0 right-0 md:pl-64 z-50">
                        <div class="bg-slate-900 border-t border-slate-800 px-6 py-4 flex items-center justify-between shadow-2xl">
                            <div class="text-sm text-slate-400">
                                <span v-if="form.isDirty" class="text-amber-500 flex items-center gap-2">
                                    <AlertTriangle class="w-4 h-4" /> Unsaved infrastructure changes
                                </span>
                            </div>
                            <button type="submit" :disabled="form.processing" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 focus:ring-offset-slate-900 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                <Save class="w-4 h-4 mr-2" />
                                {{ form.processing ? 'Comitting Changes...' : 'Save Configuration' }}
                            </button>
                        </div>
                    </div>

                </form>

            </div>
        </div>
    </AdminLayout>
</template>

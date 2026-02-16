<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    users: Array,
    roles: Array
});

const getRoleBadgeColor = (roleName) => {
    if (roleName === 'ADMINISTRATOR') return 'border-red-500/30 text-red-500 bg-red-500/5';
    if (roleName === 'MISSION_OPERATOR') return 'border-vibrant-blue/30 text-vibrant-blue bg-vibrant-blue/5';
    if (roleName === 'DATA_ANALYST') return 'border-vibrant-green/30 text-vibrant-green bg-vibrant-green/5';
    return 'border-white/20 text-white/40 bg-white/5';
};
</script>

<template>
    <Head title="RBAC Management" />

    <AdminLayout>
        <template #header>
            <h2 class="font-black text-xl text-white leading-tight uppercase tracking-[0.3em] italic">
                AUTH_&_RBAC_GOVERNANCE
            </h2>
        </template>

        <div class="py-12 px-6 lg:px-12 grid grid-cols-12 gap-8">
            <!-- Roles Overview -->
            <div class="col-span-12 lg:col-span-4 space-y-6">
                <div class="bg-[#050508] border border-white/5 p-8">
                    <h3 class="text-xs font-black text-vibrant-blue uppercase tracking-widest mb-6">DEFINED_ROLES</h3>
                    <div class="space-y-4">
                        <div v-for="role in roles" :key="role.id" class="p-4 border border-white/5 bg-white/[0.01] group hover:border-vibrant-blue/30 transition-all">
                            <h4 class="text-[11px] font-black uppercase italic mb-1 tracking-widest">{{ role.name }}</h4>
                            <p class="text-[10px] text-white/30 uppercase leading-relaxed">{{ role.description }}</p>
                        </div>
                    </div>
                    <button class="w-full mt-6 py-3 border border-dashed border-white/10 text-[9px] font-black uppercase tracking-widest text-white/20 hover:text-white hover:border-white/30 transition-all">
                        PROPOSE_NEW_ROLE
                    </button>
                </div>
            </div>

            <!-- User Privileges -->
            <div class="col-span-12 lg:col-span-8">
                <div class="bg-[#050508] border border-white/5 h-full">
                    <div class="p-8 border-b border-white/5 flex justify-between items-center">
                        <div>
                            <h3 class="text-sm font-black text-vibrant-blue uppercase tracking-widest">PRIVILEGE_ASSIGNMENT</h3>
                            <p class="text-[10px] text-white/30 uppercase tracking-widest mt-1">Mapping operators to administrative scopes</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-white/5 bg-white/[0.01]">
                                    <th class="p-6 text-[10px] font-black text-white/40 uppercase tracking-widest">Identified_Operator</th>
                                    <th class="p-6 text-[10px] font-black text-white/40 uppercase tracking-widest">Assigned_Scopes</th>
                                    <th class="p-6 text-[10px] font-black text-white/40 uppercase tracking-widest">Last_Auth</th>
                                    <th class="p-6 text-[10px] font-black text-white/40 uppercase tracking-widest text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/[0.03]">
                                <tr v-for="user in users" :key="user.id" class="hover:bg-white/[0.02] transition-colors">
                                    <td class="p-6">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 bg-vibrant-blue flex items-center justify-center text-[10px] font-black italic">
                                                {{ user.name.substring(0, 1) }}
                                            </div>
                                            <div>
                                                <p class="text-[11px] font-black uppercase tracking-tighter italic leading-none">{{ user.name }}</p>
                                                <p class="text-[9px] text-white/20 font-mono mt-1">{{ user.email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-6">
                                        <span class="px-3 py-1 border text-[9px] font-black uppercase" :class="getRoleBadgeColor('ADMINISTRATOR')">
                                            ADMINISTRATOR
                                        </span>
                                    </td>
                                    <td class="p-6 text-[10px] font-mono text-white/30 uppercase">
                                        {{ user.last_login_at || 'NEVER' }}
                                    </td>
                                    <td class="p-6 text-right">
                                        <button class="p-2 border border-white/5 hover:border-vibrant-blue/30 text-white/20 hover:text-vibrant-blue transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

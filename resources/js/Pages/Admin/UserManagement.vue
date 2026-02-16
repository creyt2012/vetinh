<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    users: Array
});

const showAddUser = ref(false);

const form = useForm({
    name: '',
    email: '',
    password: '',
});

const submitAdd = () => {
    form.post(route('admin.users.store'), {
        onSuccess: () => {
            showAddUser.value = false;
            form.reset();
        }
    });
};

const deleteUser = (id) => {
    if (confirm('Permanently wipe this operator profile?')) {
        useForm({}).delete(route('admin.users.destroy', id));
    }
};

</script>

<template>
    <AdminLayout>
        <template #header>OPERATOR_LEVEL_CONTROL</template>
        <Head title="User Management - Mission Control" />

        <div class="space-y-8">
            <div class="flex justify-between items-end">
                <div>
                    <p class="text-vibrant-blue font-black tracking-[0.4em] text-[10px] uppercase mb-2">Personnel_Database</p>
                    <h3 class="text-4xl font-black font-outfit uppercase tracking-tighter italic leading-none">COMMAND_OFFICERS</h3>
                </div>
                <button @click="showAddUser = true" class="px-8 py-4 bg-vibrant-blue text-black hover:bg-white transition uppercase text-[10px] font-black tracking-[0.3em] shadow-[0_0_30px_rgba(0,136,255,0.3)]">Enlist_New_Operator</button>
            </div>

            <!-- User List -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="user in users" :key="user.id" class="bg-[#08080C] border border-white/5 p-8 relative group hover:border-vibrant-blue/50 transition-all duration-500 flex flex-col items-center text-center">
                    <div class="w-20 h-20 rounded-full border-2 border-vibrant-blue/20 bg-vibrant-blue/5 p-1 mb-6 group-hover:border-vibrant-blue/50 transition-all overflow-hidden">
                        <img :src="`https://api.dicebear.com/7.x/pixel-art/svg?seed=${user.email}`" class="w-full h-full opacity-70 group-hover:opacity-100 group-hover:scale-110 transition-all" />
                    </div>
                    
                    <h3 class="text-xl font-black uppercase italic tracking-tight text-white mb-1">{{ user.name }}</h3>
                    <p class="text-[10px] font-mono font-bold text-white/30 truncate w-full mb-6">{{ user.email }}</p>
                    
                    <div class="flex space-x-2 w-full pt-4 border-t border-white/5">
                        <button class="flex-1 py-3 bg-white/5 text-[9px] font-black uppercase tracking-widest hover:bg-vibrant-blue hover:text-black transition-all">CONFIG</button>
                        <button @click="deleteUser(user.id)" class="px-5 py-3 text-red-500/30 hover:text-red-500 hover:bg-red-500/10 transition-all font-mono font-black border border-transparent hover:border-red-500/30">X</button>
                    </div>

                    <!-- Tactical Overlays -->
                    <div class="absolute top-4 left-4 flex space-x-1">
                        <div class="w-1.5 h-1.5 rounded-full bg-vibrant-green animate-pulse"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Modal -->
        <div v-if="showAddUser" class="fixed inset-0 bg-black/95 backdrop-blur-xl z-[100] flex items-center justify-center p-6">
             <div class="w-full max-w-xl bg-[#08080C] border border-white/10 rounded-2xl overflow-hidden shadow-2xl">
                <div class="p-8 bg-vibrant-blue/5 border-b border-white/10 flex justify-between items-center">
                    <h2 class="text-3xl font-black uppercase italic tracking-tighter">ENLIST_SYSTEM_OPERATOR</h2>
                    <button @click="showAddUser = false" class="w-10 h-10 rounded-full border border-white/10 flex items-center justify-center hover:bg-white/10 transition-all font-mono text-xs">ESC</button>
                </div>
                <form @submit.prevent="submitAdd" class="p-10 space-y-8">
                    <div class="space-y-1">
                        <label class="text-[10px] uppercase font-black text-white/40 tracking-widest">Full_Name</label>
                        <input v-model="form.name" type="text" placeholder="Operator Name" class="w-full bg-white/5 border border-white/10 px-5 py-4 text-sm font-bold focus:border-vibrant-blue outline-none transition" />
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] uppercase font-black text-white/40 tracking-widest">Auth_Email</label>
                        <input v-model="form.email" type="email" placeholder="operator@starweather.io" class="w-full bg-white/5 border border-white/10 px-5 py-4 text-sm font-bold focus:border-vibrant-blue outline-none transition" />
                    </div>
                     <div class="space-y-1">
                        <label class="text-[10px] uppercase font-black text-white/40 tracking-widest">Mission_Password</label>
                        <input v-model="form.password" type="password" placeholder="••••••••" class="w-full bg-white/5 border border-white/10 px-5 py-4 text-sm font-bold focus:border-vibrant-blue outline-none transition" />
                    </div>
                    <div class="pt-6">
                         <button type="submit" class="w-full py-6 bg-vibrant-blue text-black text-xs font-black uppercase tracking-[0.5em] hover:bg-white transition-all transform shadow-[0_15px_40px_rgba(0,136,255,0.4)]">ENLIST_PERSONNEL</button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.font-outfit { font-family: 'Outfit', sans-serif; }
.text-vibrant-blue { color: #0088ff; }
.bg-vibrant-blue { background-color: #0088ff; }
.text-vibrant-green { color: #00ffaa; }
</style>

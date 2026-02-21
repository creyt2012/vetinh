
<script setup>
import { ref, onMounted } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';

const plans = ref([]);
const loading = ref(true);
const selectedGateway = ref('stripe');
const processing = ref(false);

const fetchPlans = async () => {
    try {
        const response = await axios.get('/api/v1/plans', {
            headers: { 'X-API-KEY': 'vetinh_dev_key_123' } // Using dev key for now
        });
        plans.value = response.data.data;
    } catch (error) {
        console.error("Failed to fetch plans", error);
    } finally {
        loading.value = false;
    }
};

const handleCheckout = async (planId) => {
    processing.value = true;
    try {
        const response = await axios.post('/api/v1/payments/checkout', {
            plan_id: planId,
            gateway: selectedGateway.value
        }, {
            headers: { 'X-API-KEY': 'vetinh_dev_key_123' }
        });
        
        if (response.data.session_id) {
            alert(`Redirecting to ${selectedGateway.value} (Session: ${response.data.session_id})`);
            // In production, window.location.href = response.data.url;
        }
    } catch (error) {
        alert("Payment failed to initialize");
    } finally {
        processing.value = false;
    }
};

onMounted(fetchPlans);
</script>

<template>
    <Head title="Enterprise Billing" />

    <AdminLayout>
        <div class="py-12 bg-[#050505] min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Tactical Header -->
                <div class="mb-12 border-b border-vibrant-blue/20 pb-8">
                    <div class="flex items-center space-x-4 mb-2">
                        <div class="w-2 h-8 bg-vibrant-blue shadow-[0_0_15px_rgba(79,70,229,0.5)]"></div>
                        <h1 class="text-4xl font-black text-white tracking-tighter italic uppercase">Mission_Billing_Center</h1>
                    </div>
                    <p class="text-white/40 font-mono text-sm tracking-widest">SELECT_YOUR_TIER // GLOBAL_OPERATIONS_ENABLED</p>
                </div>

                <!-- Gateway Selection -->
                <div class="flex space-x-4 mb-12 p-1 bg-white/5 rounded-xl w-fit border border-white/10">
                    <button 
                        @click="selectedGateway = 'stripe'"
                        :class="[selectedGateway === 'stripe' ? 'bg-vibrant-blue text-white' : 'text-white/40 hover:text-white']"
                        class="px-6 py-2 rounded-lg font-black text-xs uppercase tracking-widest transition-all"
                    >
                        Stripe_Secure
                    </button>
                    <button 
                        @click="selectedGateway = 'paypal'"
                        :class="[selectedGateway === 'paypal' ? 'bg-[#003087] text-white' : 'text-white/40 hover:text-white']"
                        class="px-6 py-2 rounded-lg font-black text-xs uppercase tracking-widest transition-all"
                    >
                        PayPal_Vault
                    </button>
                </div>

                <!-- Plans Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div v-for="plan in plans" :key="plan.id" 
                         class="relative group p-8 bg-black/40 border border-white/10 rounded-2xl hover:border-vibrant-blue/50 transition-all duration-500 overflow-hidden">
                        
                        <!-- Background Glow -->
                        <div class="absolute -top-24 -right-24 w-48 h-48 bg-vibrant-blue/5 blur-[100px] group-hover:bg-vibrant-blue/20 transition-all"></div>

                        <div class="relative z-10">
                            <h3 class="text-xs font-black text-vibrant-blue uppercase tracking-[0.3em] mb-4">{{ plan.name }}</h3>
                            <div class="flex items-baseline space-x-2 mb-8">
                                <span class="text-5xl font-black text-white">${{ plan.price }}</span>
                                <span class="text-white/20 font-mono text-xs uppercase">/{{ plan.interval }}</span>
                            </div>

                            <div class="space-y-4 mb-12">
                                <div v-for="feature in plan.features" :key="feature" class="flex items-center space-x-3">
                                    <svg class="w-4 h-4 text-vibrant-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span class="text-sm text-white/60 font-medium">{{ feature }}</span>
                                </div>
                            </div>

                            <button 
                                @click="handleCheckout(plan.id)"
                                :disabled="processing"
                                class="w-full py-4 bg-white/5 border border-white/10 text-white font-black uppercase tracking-[0.2em] text-xs hov:bg-vibrant-blue hov:border-vibrant-blue transition-all relative overflow-hidden group/btn"
                            >
                                <span class="relative z-10">{{ processing ? 'INITIALIZING...' : 'ENGAGE_PLAN' }}</span>
                                <div class="absolute inset-0 bg-gradient-to-r from-vibrant-blue to-indigo-600 translate-y-full group-hover/btn:translate-y-0 transition-transform duration-300"></div>
                            </button>
                        </div>

                        <!-- Tactical Border -->
                        <div class="absolute top-0 right-0 w-8 h-8 border-t-2 border-r-2 border-white/5 group-hover:border-vibrant-blue/40 transition-colors"></div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.hov\:bg-vibrant-blue:hover {
    background-color: rgb(79 70 229);
}
.hov\:border-vibrant-blue:hover {
    border-color: rgb(79 70 229);
}
</style>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    settings: Object
});

const form = useForm({
    channels: props.settings.channels || {
        telegram: { enabled: false, chat_id: '', bot_token: '' },
        slack: { enabled: false, webhook_url: '' },
        zalo: { enabled: false, oa_id: '', template_id: '' },
        web_push: { enabled: false, endpoint: '' },
    },
    thresholds: props.settings.thresholds || {
        critical_risk_score: 80,
        min_conjunction_dist: 50,
    }
});

const saveSettings = () => {
    form.post(route('admin.alerts.update'), {
        preserveScroll: true,
        onSuccess: () => alert('Settings saved successfully.')
    });
};
</script>

<template>
    <AdminLayout title="Alert Settings">
        <div class="p-8 max-w-4xl space-y-8">
            <header>
                <h1 class="text-3xl font-black tracking-tighter uppercase italic">Alert Configuration</h1>
                <p class="text-[10px] font-mono text-white/40 uppercase tracking-widest mt-1">Multi-Channel Notification Dispatcher</p>
            </header>

            <form @submit.prevent="saveSettings" class="space-y-8">
                <!-- Channels Section -->
                <div class="bg-black/40 border border-white/10 rounded-2xl p-8 backdrop-blur-xl space-y-8">
                    <h2 class="text-sm font-black uppercase tracking-widest flex items-center space-x-2">
                        <span class="w-2 h-2 rounded-full bg-vibrant-blue shadow-[0_0_8px_#0088ff]"></span>
                        <span>Communication Channels</span>
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Telegram -->
                        <div class="space-y-4 p-6 border border-white/5 rounded-xl bg-white/[0.02]">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs font-black uppercase italic text-vibrant-blue">Telegram_Bot</span>
                                <input type="checkbox" v-model="form.channels.telegram.enabled" class="toggle toggle-primary" />
                            </div>
                            <div class="space-y-3">
                                <input v-model="form.channels.telegram.bot_token" placeholder="BOT_TOKEN" class="w-full bg-black/40 border border-white/10 px-4 py-2 text-[10px] font-mono" />
                                <input v-model="form.channels.telegram.chat_id" placeholder="CHAT_ID" class="w-full bg-black/40 border border-white/10 px-4 py-2 text-[10px] font-mono" />
                            </div>
                        </div>

                        <!-- Zalo -->
                        <div class="space-y-4 p-6 border border-white/5 rounded-xl bg-white/[0.02]">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs font-black uppercase italic text-vibrant-blue">Zalo_OA_Service</span>
                                <input type="checkbox" v-model="form.channels.zalo.enabled" class="toggle toggle-primary" />
                            </div>
                            <div class="space-y-3">
                                <input v-model="form.channels.zalo.oa_id" placeholder="Zalo_OA_ID" class="w-full bg-black/40 border border-white/10 px-4 py-2 text-[10px] font-mono" />
                                <input v-model="form.channels.zalo.template_id" placeholder="TEMPLATE_ID" class="w-full bg-black/40 border border-white/10 px-4 py-2 text-[10px] font-mono" />
                            </div>
                        </div>

                        <!-- Slack -->
                        <div class="space-y-4 p-6 border border-white/5 rounded-xl bg-white/[0.02]">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs font-black uppercase italic text-vibrant-blue">Slack_Integration</span>
                                <input type="checkbox" v-model="form.channels.slack.enabled" class="toggle toggle-primary" />
                            </div>
                            <input v-model="form.channels.slack.webhook_url" placeholder="https://hooks.slack.com/services/..." class="w-full bg-black/40 border border-white/10 px-4 py-2 text-[10px] font-mono mt-3" />
                        </div>

                        <!-- Web Push -->
                        <div class="space-y-4 p-6 border border-white/5 rounded-xl bg-white/[0.02]">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs font-black uppercase italic text-vibrant-blue">Browser_Web_Push</span>
                                <input type="checkbox" v-model="form.channels.web_push.enabled" class="toggle toggle-primary" />
                            </div>
                            <div class="p-3 bg-white/5 border border-white/5 text-[9px] font-mono text-white/40 italic">
                                SYSTEM_AUTO_VAPID_ACTIVE: Giao thức Web Push chuẩn VAPID sẽ tự động kích hoạt cho tất cả thiết bị đã đăng ký.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Thresholds Section -->
                <div class="bg-black/40 border border-white/10 rounded-2xl p-8 backdrop-blur-xl space-y-8">
                    <h2 class="text-sm font-black uppercase tracking-widest flex items-center space-x-2">
                        <span class="w-2 h-2 rounded-full bg-vibrant-green shadow-[0_0_8px_#22c55e]"></span>
                        <span>Intelligence Thresholds</span>
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="text-[10px] font-mono text-white/40 uppercase block mb-2">Weather Risk Criticality</label>
                            <input type="range" min="0" max="100" v-model="form.thresholds.critical_risk_score" class="w-full accent-vibrant-blue" />
                            <p class="text-right text-[10px] font-mono mt-1">{{ form.thresholds.critical_risk_score }} / 100</p>
                        </div>
                        <div>
                            <label class="text-[10px] font-mono text-white/40 uppercase block mb-2">Conjunction Proximity (KM)</label>
                            <input type="number" v-model="form.thresholds.min_conjunction_dist" class="w-full bg-black/40 border border-white/10 rounded-lg px-4 py-2 text-xs font-mono" />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" 
                            :disabled="form.processing"
                            class="px-8 py-4 bg-vibrant-blue text-white font-black uppercase tracking-widest text-[10px] rounded-xl shadow-[0_0_20px_rgba(0,136,255,0.4)] hover:scale-105 transition-all active:scale-95 disabled:opacity-50">
                        Deploy Configuration
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

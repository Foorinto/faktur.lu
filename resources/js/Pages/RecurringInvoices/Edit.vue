<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BillingNav from '@/Components/BillingNav.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    recurringInvoice: Object,
    clients: Array,
    frequencies: Array,
    defaultVatRate: { type: Number, default: 17 },
});

const frequencyLabels = {
    weekly: t('recurring_invoice_freq_weekly'),
    monthly: t('recurring_invoice_freq_monthly'),
    quarterly: t('recurring_invoice_freq_quarterly'),
    yearly: t('recurring_invoice_freq_yearly'),
};

const units = ['unit', 'hour', 'day', 'month', 'kg', 'km', 'm2', 'forfait'];

const form = useForm({
    client_id: props.recurringInvoice.client_id,
    title: props.recurringInvoice.title || '',
    frequency: props.recurringInvoice.frequency,
    next_invoice_date: props.recurringInvoice.next_invoice_date?.split('T')[0],
    ends_at: props.recurringInvoice.ends_at?.split('T')[0] || '',
    is_active: props.recurringInvoice.is_active,
    auto_finalize: props.recurringInvoice.auto_finalize,
    auto_send: props.recurringInvoice.auto_send,
    payment_delay_days: props.recurringInvoice.payment_delay_days,
    notes: props.recurringInvoice.notes || '',
    vat_mention: props.recurringInvoice.vat_mention || '',
    footer_message: props.recurringInvoice.footer_message || '',
    currency: props.recurringInvoice.currency,
    items: props.recurringInvoice.items.map(item => ({
        title: item.title,
        description: item.description || '',
        quantity: item.quantity,
        unit: item.unit,
        unit_price: item.unit_price,
        vat_rate: item.vat_rate,
    })),
});

const addItem = () => {
    form.items.push({ title: '', description: '', quantity: 1, unit: 'unit', unit_price: 0, vat_rate: props.defaultVatRate });
};

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

const totalHt = () => {
    return form.items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
};

const totalTtc = () => {
    return form.items.reduce((sum, item) => {
        const ht = item.quantity * item.unit_price;
        return sum + ht + (ht * item.vat_rate / 100);
    }, 0);
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount);
};

const submit = () => {
    form.put(route('recurring-invoices.update', props.recurringInvoice.id));
};
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ t('recurring_invoice_edit_title') }}</h1>
        </template>

        <BillingNav class="mb-6" />

        <div class="mb-4">
            <p class="text-sm text-slate-500">{{ recurringInvoice.invoices_generated }} {{ t('recurring_invoice_invoices_generated') }}</p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
                <!-- Client & Paramètres -->
                <div class="bg-white dark:bg-surface-dark rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">{{ t('recurring_invoice_section_settings') }}</h2>

                    <div class="grid sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('recurring_invoice_field_client_required') }}</label>
                            <select v-model="form.client_id" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500">
                                <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('recurring_invoice_field_title') }}</label>
                            <input v-model="form.title" type="text" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('recurring_invoice_field_frequency_required') }}</label>
                            <select v-model="form.frequency" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500">
                                <option v-for="freq in frequencies" :key="freq" :value="freq">{{ frequencyLabels[freq] }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('recurring_invoice_field_next_required') }}</label>
                            <input v-model="form.next_invoice_date" type="date" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('recurring_invoice_field_ends_at') }}</label>
                            <input v-model="form.ends_at" type="date" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('recurring_invoice_field_payment_delay') }}</label>
                            <input v-model="form.payment_delay_days" type="number" min="0" max="365" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500" />
                        </div>
                    </div>

                    <div class="mt-5 flex flex-wrap gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-primary-500 focus:ring-primary-500" />
                            <span class="text-sm text-slate-700 dark:text-slate-300">{{ t('recurring_invoice_option_active') }}</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input v-model="form.auto_finalize" type="checkbox" class="rounded border-gray-300 text-primary-500 focus:ring-primary-500" />
                            <span class="text-sm text-slate-700 dark:text-slate-300">{{ t('recurring_invoice_option_auto_finalize') }}</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input v-model="form.auto_send" type="checkbox" :disabled="!form.auto_finalize" class="rounded border-gray-300 text-primary-500 focus:ring-primary-500 disabled:opacity-50" />
                            <span class="text-sm text-slate-700 dark:text-slate-300" :class="{ 'opacity-50': !form.auto_finalize }">{{ t('recurring_invoice_option_auto_send_short') }}</span>
                        </label>
                    </div>
                </div>

                <!-- Lignes -->
                <div class="bg-white dark:bg-surface-dark rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ t('recurring_invoice_section_lines') }}</h2>
                        <button type="button" @click="addItem" class="text-sm text-primary-500 hover:text-primary-600 font-medium">{{ t('recurring_invoice_add_line') }}</button>
                    </div>

                    <div class="space-y-4">
                        <div v-for="(item, index) in form.items" :key="index" class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4">
                            <div class="grid sm:grid-cols-6 gap-3">
                                <div class="sm:col-span-3">
                                    <label class="block text-xs font-medium text-slate-500 mb-1">{{ t('recurring_invoice_field_designation_required') }}</label>
                                    <input v-model="item.title" type="text" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">{{ t('recurring_invoice_field_qty') }}</label>
                                    <input v-model="item.quantity" type="number" step="0.01" min="0" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">{{ t('recurring_invoice_field_unit_price_ht') }}</label>
                                    <input v-model="item.unit_price" type="number" step="0.01" min="0" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500" />
                                </div>
                                <div class="flex items-end gap-2">
                                    <div class="flex-1">
                                        <label class="block text-xs font-medium text-slate-500 mb-1">{{ t('recurring_invoice_field_vat_pct') }}</label>
                                        <input v-model="item.vat_rate" type="number" step="0.01" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500" />
                                    </div>
                                    <button v-if="form.items.length > 1" type="button" @click="removeItem(index)" class="p-2 text-slate-400 hover:text-red-500">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="mt-2 grid sm:grid-cols-6 gap-3">
                                <div class="sm:col-span-3">
                                    <input v-model="item.description" type="text" :placeholder="t('recurring_invoice_field_description_placeholder')" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500" />
                                </div>
                                <div>
                                    <select v-model="item.unit" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500">
                                        <option v-for="u in units" :key="u" :value="u">{{ u }}</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2 text-right text-sm font-medium text-slate-700 dark:text-slate-300 self-center">
                                    {{ formatCurrency(item.quantity * item.unit_price) }} {{ t('recurring_invoice_amount_suffix_ht') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 text-right space-y-1">
                        <p class="text-sm text-slate-600">{{ t('recurring_invoice_total_ht_label') }} <span class="font-semibold text-slate-900 dark:text-white">{{ formatCurrency(totalHt()) }}</span></p>
                        <p class="text-sm text-slate-600">{{ t('recurring_invoice_total_ttc_label') }} <span class="font-semibold text-slate-900 dark:text-white">{{ formatCurrency(totalTtc()) }}</span></p>
                    </div>
                </div>

                <!-- Notes -->
                <div class="bg-white dark:bg-surface-dark rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">{{ t('recurring_invoice_section_notes') }}</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('recurring_invoice_field_notes') }}</label>
                            <textarea v-model="form.notes" rows="2" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('recurring_invoice_field_footer') }}</label>
                            <textarea v-model="form.footer_message" rows="2" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500"></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <Link :href="route('recurring-invoices.index')" class="px-5 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 rounded-xl transition-colors">
                        {{ t('recurring_invoice_cancel') }}
                    </Link>
                    <button type="submit" :disabled="form.processing" class="bg-primary-500 hover:bg-primary-600 disabled:bg-slate-400 text-white font-medium px-6 py-2.5 rounded-xl transition-colors text-sm">
                        {{ form.processing ? t('recurring_invoice_updating') : t('recurring_invoice_save') }}
                    </button>
                </div>
        </form>
    </AppLayout>
</template>

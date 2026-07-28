<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import RichTextEditor from '@/Components/RichTextEditor.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import BillingNav from '@/Components/BillingNav.vue';
import ProductAutocomplete from '@/Components/ProductAutocomplete.vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    clients: Array,
    frequencies: Array,
    defaultVatRate: { type: Number, default: 17 },
    isVatExempt: Boolean,
});

const frequencyLabels = {
    weekly: t('recurring_invoice_freq_weekly'),
    monthly: t('recurring_invoice_freq_monthly'),
    quarterly: t('recurring_invoice_freq_quarterly'),
    yearly: t('recurring_invoice_freq_yearly'),
};

const units = ['unit', 'hour', 'day', 'month', 'kg', 'km', 'm2', 'forfait'];

const form = useForm({
    client_id: '',
    title: '',
    frequency: 'monthly',
    next_invoice_date: new Date().toISOString().split('T')[0],
    ends_at: '',
    auto_finalize: false,
    auto_send: false,
    payment_delay_days: 30,
    notes: '',
    vat_mention: '',
    footer_message: '',
    currency: 'EUR',
    items: [
        { title: '', description: '', quantity: 1, unit: 'unit', unit_price: 0, discount_type: 'percent', discount_value: 0, vat_rate: props.defaultVatRate },
    ],
    discounts: [],
});

const addDiscountLine = () => form.discounts.push({ label: '', type: 'percent', value: null });
const removeDiscountLine = (index) => form.discounts.splice(index, 1);

const addItem = () => {
    form.items.push({ title: '', description: '', quantity: 1, unit: 'unit', unit_price: 0, discount_type: 'percent', discount_value: 0, vat_rate: props.defaultVatRate });
};

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

// Pre-fill a line from a selected catalogue product (FEAT-095).
const applyProduct = (index, product) => {
    const item = form.items[index];
    item.title = product.designation;
    if (product.description) { item.description = product.description; }
    item.unit_price = Number(product.unit_price_ht);
    if (product.unit && units.includes(product.unit)) { item.unit = product.unit; }
    // En franchise de TVA, le taux du catalogue ne doit pas écraser le 0 %.
    item.vat_rate = props.isVatExempt ? 0 : Number(product.vat_rate);
};

// Net line total after discount (mirrors backend InvoiceItem::applyLineDiscount)
const lineNetHt = (item) => {
    const gross = (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0);
    const dv = parseFloat(item.discount_value) || 0;
    let discount = 0;
    if (dv > 0) {
        discount = item.discount_type === 'amount'
            ? Math.min(dv, gross)
            : gross * Math.min(dv, 100) / 100;
    }
    return Math.max(0, gross - discount);
};

const subtotalHt = () => form.items.reduce((sum, item) => sum + lineNetHt(item), 0);

const globalDiscountTotal = () => {
    const subtotal = subtotalHt();
    let discount = 0;
    for (const d of form.discounts) {
        const v = parseFloat(d.value) || 0;
        discount += d.type === 'amount' ? v : subtotal * v / 100;
    }
    return Math.min(discount, subtotal);
};

const discountLineAmount = (discount) => {
    const v = parseFloat(discount.value) || 0;
    return discount.type === 'amount' ? v : subtotalHt() * v / 100;
};

const totalHt = () => subtotalHt() - globalDiscountTotal();

const totalTtc = () => {
    const subtotal = subtotalHt();
    const factor = subtotal > 0 ? (subtotal - globalDiscountTotal()) / subtotal : 1;
    let vat = 0;
    for (const item of form.items) {
        vat += lineNetHt(item) * factor * (parseFloat(item.vat_rate) || 0) / 100;
    }
    return totalHt() + vat;
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount);
};

const submit = () => {
    form.post(route('recurring-invoices.store'));
};
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ t('recurring_invoice_new') }}</h1>
        </template>

        <BillingNav class="mb-6" />

        <div class="mb-4">
            <Link :href="route('recurring-invoices.index')" class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300">
                {{ t('recurring_invoice_back_to_list') }}
            </Link>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
                <!-- Client & Paramètres -->
                <div class="bg-white dark:bg-surface-dark rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">{{ t('recurring_invoice_section_settings') }}</h2>

                    <div class="grid sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('recurring_invoice_field_client_required') }}</label>
                            <select v-model="form.client_id" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500">
                                <option value="" disabled>{{ t('recurring_invoice_select_client') }}</option>
                                <optgroup :label="t('recurring_invoice_group_active')">
                                    <option v-for="client in clients.filter(c => c.status === 'active')" :key="client.id" :value="client.id">{{ client.name }}</option>
                                </optgroup>
                                <optgroup v-if="clients.some(c => c.status !== 'active')" :label="t('recurring_invoice_group_others')">
                                    <option v-for="client in clients.filter(c => c.status !== 'active')" :key="client.id" :value="client.id">{{ client.name }}</option>
                                </optgroup>
                            </select>
                            <p v-if="form.errors.client_id" class="mt-1 text-sm text-red-600">{{ form.errors.client_id }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('recurring_invoice_field_title_optional') }}</label>
                            <input v-model="form.title" type="text" :placeholder="t('recurring_invoice_field_title_placeholder')" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500" />
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
                            <p v-if="form.errors.next_invoice_date" class="mt-1 text-sm text-red-600">{{ form.errors.next_invoice_date }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('recurring_invoice_field_ends_at_optional') }}</label>
                            <input v-model="form.ends_at" type="date" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('recurring_invoice_field_payment_delay') }}</label>
                            <input v-model="form.payment_delay_days" type="number" min="0" max="365" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500" />
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="mt-5 flex flex-wrap gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input v-model="form.auto_finalize" type="checkbox" class="rounded border-gray-300 text-primary-500 focus:ring-primary-500" />
                            <span class="text-sm text-slate-700 dark:text-slate-300">{{ t('recurring_invoice_option_auto_finalize') }}</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input v-model="form.auto_send" type="checkbox" :disabled="!form.auto_finalize" class="rounded border-gray-300 text-primary-500 focus:ring-primary-500 disabled:opacity-50" />
                            <span class="text-sm text-slate-700 dark:text-slate-300" :class="{ 'opacity-50': !form.auto_finalize }">{{ t('recurring_invoice_option_auto_send') }}</span>
                        </label>
                    </div>
                </div>

                <!-- Lignes -->
                <div class="bg-white dark:bg-surface-dark rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ t('recurring_invoice_section_lines') }}</h2>
                        <button type="button" @click="addItem" class="text-sm text-primary-500 hover:text-primary-600 font-medium">
                            {{ t('recurring_invoice_add_line') }}
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div v-for="(item, index) in form.items" :key="index" class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4">
                            <div class="grid sm:grid-cols-6 gap-3">
                                <div class="sm:col-span-3">
                                    <label class="block text-xs font-medium text-slate-500 mb-1">{{ t('recurring_invoice_field_designation_required') }}</label>
                                    <ProductAutocomplete v-model="item.title" :placeholder="t('recurring_invoice_field_designation_placeholder')" @select="applyProduct(index, $event)" />
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
                                        <input v-model="item.vat_rate" type="number" step="0.01" min="0" max="100" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500" />
                                    </div>
                                    <button v-if="form.items.length > 1" type="button" @click="removeItem(index)" class="p-2 text-slate-400 hover:text-red-500 transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="mt-2 grid sm:grid-cols-6 gap-3">
                                <div class="sm:col-span-2">
                                    <input v-model="item.description" type="text" :placeholder="t('recurring_invoice_field_description_placeholder_optional')" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500" />
                                </div>
                                <div>
                                    <select v-model="item.unit" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500">
                                        <option v-for="u in units" :key="u" :value="u">{{ u }}</option>
                                    </select>
                                </div>
                                <div class="flex" :title="t('discount')">
                                    <input v-model.number="item.discount_value" type="number" step="0.01" min="0" :max="item.discount_type === 'percent' ? 100 : null" placeholder="0" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-l-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500" />
                                    <select v-model="item.discount_type" :aria-label="t('discount_type')" class="border border-l-0 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-r-lg text-sm focus:ring-2 focus:ring-primary-500">
                                        <option value="percent">%</option>
                                        <option value="amount">€</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2 text-right text-sm font-medium text-slate-700 dark:text-slate-300 self-center">
                                    {{ formatCurrency(lineNetHt(item)) }} {{ t('recurring_invoice_amount_suffix_ht') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Remises globales -->
                    <div class="mt-4">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300">{{ t('discount') }}</h3>
                            <button type="button" @click="addDiscountLine" class="text-sm text-primary-500 hover:text-primary-600 font-medium">+ {{ t('discount') }}</button>
                        </div>
                        <div v-for="(discount, index) in form.discounts" :key="index" class="flex items-center gap-2 mb-2">
                            <input v-model="discount.label" type="text" :placeholder="t('discount_label_placeholder')" class="flex-1 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500" />
                            <input v-model.number="discount.value" type="number" step="0.01" min="0" :max="discount.type === 'percent' ? 100 : null" placeholder="0" class="w-16 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-l-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500" />
                            <select v-model="discount.type" :aria-label="t('discount_type')" class="-ml-2 border border-l-0 border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-r-lg text-sm focus:ring-2 focus:ring-primary-500">
                                <option value="percent">%</option>
                                <option value="amount">€</option>
                            </select>
                            <span class="w-20 text-right text-sm text-amber-700 dark:text-amber-400">− {{ formatCurrency(discountLineAmount(discount)) }}</span>
                            <button type="button" @click="removeDiscountLine(index)" class="p-2 text-slate-400 hover:text-red-500">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Totaux -->
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 text-right space-y-1">
                        <p class="text-sm text-slate-600 dark:text-slate-400">{{ t('recurring_invoice_total_ht_label') }} <span class="font-semibold text-slate-900 dark:text-white">{{ formatCurrency(totalHt()) }}</span></p>
                        <p class="text-sm text-slate-600 dark:text-slate-400">{{ t('recurring_invoice_total_ttc_label') }} <span class="font-semibold text-slate-900 dark:text-white">{{ formatCurrency(totalTtc()) }}</span></p>
                    </div>
                </div>

                <!-- Notes -->
                <div class="bg-white dark:bg-surface-dark rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">{{ t('recurring_invoice_section_notes') }}</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('recurring_invoice_field_notes_invoice') }}</label>
                            <textarea v-model="form.notes" rows="2" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('recurring_invoice_field_footer') }}</label>
                            <RichTextEditor use-company-link-color v-model="form.footer_message" />
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex items-center justify-end gap-3">
                    <Link :href="route('recurring-invoices.index')" class="px-5 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors">
                        {{ t('recurring_invoice_cancel') }}
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-primary-500 hover:bg-primary-600 disabled:bg-slate-400 text-white font-medium px-6 py-2.5 rounded-xl transition-colors text-sm"
                    >
                        {{ form.processing ? t('recurring_invoice_creating') : t('recurring_invoice_create_submit') }}
                    </button>
                </div>
        </form>
    </AppLayout>
</template>

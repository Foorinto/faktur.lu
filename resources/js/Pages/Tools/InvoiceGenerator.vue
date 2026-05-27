<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';
import SchemaJsonLd from '@/Components/SchemaJsonLd.vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';
import { useToolSchemas } from '@/Composables/useToolSchemas';

const { t } = useTranslations();
const { localizedRoute, currentLocale } = useLocalizedRoute();
const { breadcrumb, faqPage, webApplication, howTo, wikidata } = useToolSchemas();

const today = new Date().toISOString().slice(0, 10);
const dueDateDefault = new Date(Date.now() + 30 * 24 * 3600 * 1000).toISOString().slice(0, 10);

const sender = ref({ name: '', address: '', vat_number: '', iban: '', bic: '', email: '', phone: '' });
const client = ref({ name: '', address: '', vat_number: '', country: 'LU' });
const invoice = ref({
    number: 'F-' + new Date().getFullYear() + '-001',
    date: today,
    due_date: dueDateDefault,
    notes: '',
});
const lines = ref([
    { description: '', quantity: 1, unit_price: 0, vat_rate: 17 },
]);
const currency = ref('EUR');
const reverseCharge = ref(false);
const generating = ref(false);
const error = ref(null);

const VAT_RATES = [17, 14, 8, 3, 0];
const CURRENCIES = ['EUR', 'USD', 'GBP', 'CHF'];

const subtotal = computed(() =>
    lines.value.reduce((acc, l) => acc + (l.quantity * l.unit_price || 0), 0)
);
const vatTotal = computed(() => {
    if (reverseCharge.value) return 0;
    return lines.value.reduce((acc, l) => acc + (l.quantity * l.unit_price * l.vat_rate / 100 || 0), 0);
});
const total = computed(() => subtotal.value + vatTotal.value);

const fmt = (n) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n) + ' ' + (currency.value === 'EUR' ? '€' : currency.value);

const addLine = () => {
    if (lines.value.length >= 30) return;
    lines.value.push({ description: '', quantity: 1, unit_price: 0, vat_rate: 17 });
};

const removeLine = (i) => {
    if (lines.value.length === 1) return;
    lines.value.splice(i, 1);
};

const formValid = computed(() => {
    return sender.value.name.trim() !== ''
        && client.value.name.trim() !== ''
        && invoice.value.number.trim() !== ''
        && invoice.value.date !== ''
        && lines.value.length > 0
        && lines.value.every(l => l.description.trim() !== '' && l.quantity > 0 && l.unit_price >= 0);
});

const generatePdf = async () => {
    if (!formValid.value || generating.value) return;
    generating.value = true;
    error.value = null;

    try {
        const response = await axios.post(`/${currentLocale()}/tools/generate-invoice-pdf`, {
            sender: sender.value,
            client: client.value,
            invoice: invoice.value,
            lines: lines.value,
            currency: currency.value,
            language: currentLocale(),
            reverse_charge: reverseCharge.value,
        }, { responseType: 'blob' });

        // Trigger download
        const blob = new Blob([response.data], { type: 'application/pdf' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `facture-${invoice.value.number.replace(/[^a-zA-Z0-9-_]/g, '_')}.pdf`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (e) {
        console.error('[generateInvoicePdf]', e);
        error.value = e.response?.status === 429
            ? t('tools.invoice_generator.error_rate_limit')
            : t('tools.invoice_generator.error_generic');
    } finally {
        generating.value = false;
    }
};

const faqs = computed(() => [
    { q: t('tools.invoice_generator.faq.q1'), a: t('tools.invoice_generator.faq.a1') },
    { q: t('tools.invoice_generator.faq.q2'), a: t('tools.invoice_generator.faq.a2') },
    { q: t('tools.invoice_generator.faq.q3'), a: t('tools.invoice_generator.faq.a3') },
]);

const schemas = computed(() => [
    breadcrumb(t('tools.invoice_generator.breadcrumb')),
    faqPage(faqs.value),
    webApplication({
        name: t('tools.invoice_generator.title'),
        description: t('tools.invoice_generator.meta_description'),
        url: localizedRoute('tools.invoice_generator'),
        category: 'BusinessApplication',
        about: [wikidata.luxembourg, wikidata.invoice, wikidata.vat],
    }),
    howTo({
        name: t('tools.invoice_generator.title'),
        description: t('tools.invoice_generator.meta_description'),
        totalTimeMin: 5,
        steps: [
            { name: t('tools.invoice_generator.sender_title'), text: t('tools.invoice_generator.sender_title') },
            { name: t('tools.invoice_generator.client_title'), text: t('tools.invoice_generator.client_title') },
            { name: t('tools.invoice_generator.items_title'), text: t('tools.invoice_generator.items_title') },
            { name: t('tools.invoice_generator.download_button'), text: t('tools.invoice_generator.download_button') },
        ],
    }),
]);

const relatedTools = computed(() => [
    { key: 'vat_calculator', route: localizedRoute('tools.vat_calculator') },
    { key: 'iban_validator', route: localizedRoute('tools.iban_validator') },
    { key: 'templates', route: localizedRoute('tools.templates') },
]);
</script>

<template>
    <SchemaJsonLd :schemas="schemas" />
    <SeoHead
        :title="t('tools.invoice_generator.page_title')"
        :description="t('tools.invoice_generator.meta_description')"
        canonical-path="/outils/generateur-facture"
        route-name="tools.invoice_generator"
    />

    <MarketingLayout>
        <div class="py-12 sm:py-16">
            <div class="mx-auto max-w-5xl px-6 lg:px-8">
                <!-- Breadcrumb -->
                <nav class="mb-8 text-sm">
                    <Link :href="localizedRoute('home')" class="text-slate-500 hover:text-slate-700">faktur.lu</Link>
                    <span class="text-slate-400 mx-2">/</span>
                    <Link :href="localizedRoute('tools')" class="text-slate-500 hover:text-slate-700">{{ t('tools.index.breadcrumb') }}</Link>
                    <span class="text-slate-400 mx-2">/</span>
                    <span class="text-slate-900">{{ t('tools.invoice_generator.breadcrumb') }}</span>
                </nav>

                <!-- Hero -->
                <div class="text-center mb-10">
                    <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 leading-tight mb-4">
                        {{ t('tools.invoice_generator.title') }}
                    </h1>
                    <p class="text-lg text-slate-600">
                        {{ t('tools.invoice_generator.subtitle') }}
                    </p>
                </div>

                <!-- Form -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 sm:p-8 mb-8">
                    <!-- Sender + Client -->
                    <div class="grid sm:grid-cols-2 gap-6 mb-6">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900 mb-3">{{ t('tools.invoice_generator.sender_title') }}</h2>
                            <div class="space-y-3">
                                <input v-model="sender.name" type="text" required :placeholder="t('tools.invoice_generator.sender_name')" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 text-sm" />
                                <textarea v-model="sender.address" rows="2" :placeholder="t('tools.invoice_generator.sender_address')" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 text-sm"></textarea>
                                <input v-model="sender.vat_number" type="text" :placeholder="t('tools.invoice_generator.sender_vat')" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 text-sm" />
                                <input v-model="sender.iban" type="text" placeholder="IBAN" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 text-sm font-mono" />
                                <input v-model="sender.email" type="email" placeholder="email@example.com" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 text-sm" />
                            </div>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900 mb-3">{{ t('tools.invoice_generator.client_title') }}</h2>
                            <div class="space-y-3">
                                <input v-model="client.name" type="text" required :placeholder="t('tools.invoice_generator.client_name')" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 text-sm" />
                                <textarea v-model="client.address" rows="2" :placeholder="t('tools.invoice_generator.client_address')" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 text-sm"></textarea>
                                <input v-model="client.vat_number" type="text" :placeholder="t('tools.invoice_generator.client_vat')" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 text-sm" />
                                <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                                    <input v-model="reverseCharge" type="checkbox" class="rounded border-gray-300 text-primary-500 focus:ring-primary-500" />
                                    {{ t('tools.invoice_generator.reverse_charge_label') }}
                                </label>
                                <p v-if="reverseCharge" class="text-xs text-amber-700 bg-amber-50 border-l-4 border-amber-400 p-2 rounded">
                                    {{ t('tools.invoice_generator.reverse_charge_help') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice meta -->
                    <div class="grid sm:grid-cols-3 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('tools.invoice_generator.invoice_number') }}</label>
                            <input v-model="invoice.number" type="text" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('tools.invoice_generator.invoice_date') }}</label>
                            <input v-model="invoice.date" type="date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('tools.invoice_generator.due_date') }}</label>
                            <input v-model="invoice.due_date" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 text-sm" />
                        </div>
                    </div>

                    <!-- Lines -->
                    <div class="mb-6">
                        <h2 class="text-lg font-semibold text-slate-900 mb-3">{{ t('tools.invoice_generator.lines_title') }}</h2>
                        <div class="space-y-3">
                            <div v-for="(line, i) in lines" :key="i" class="grid grid-cols-12 gap-2 items-end p-3 bg-slate-50 rounded-lg">
                                <div class="col-span-12 sm:col-span-5">
                                    <label class="block text-xs text-slate-500 mb-1">{{ t('tools.invoice_generator.line_description') }}</label>
                                    <input v-model="line.description" type="text" required class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm" />
                                </div>
                                <div class="col-span-4 sm:col-span-2">
                                    <label class="block text-xs text-slate-500 mb-1">{{ t('tools.invoice_generator.line_quantity') }}</label>
                                    <input v-model.number="line.quantity" type="number" step="0.01" min="0" required class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm" />
                                </div>
                                <div class="col-span-4 sm:col-span-2">
                                    <label class="block text-xs text-slate-500 mb-1">{{ t('tools.invoice_generator.line_unit_price') }}</label>
                                    <input v-model.number="line.unit_price" type="number" step="0.01" min="0" required class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm" />
                                </div>
                                <div class="col-span-3 sm:col-span-2">
                                    <label class="block text-xs text-slate-500 mb-1">{{ t('tools.invoice_generator.line_vat') }}</label>
                                    <select v-model.number="line.vat_rate" :disabled="reverseCharge" class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm disabled:bg-slate-100 disabled:text-slate-400">
                                        <option v-for="r in VAT_RATES" :key="r" :value="r">{{ r }}%</option>
                                    </select>
                                </div>
                                <div class="col-span-1 sm:col-span-1 flex justify-end">
                                    <button type="button" @click="removeLine(i)" :disabled="lines.length === 1" class="p-1.5 text-red-500 hover:bg-red-50 rounded disabled:opacity-30 disabled:cursor-not-allowed">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="button" @click="addLine" :disabled="lines.length >= 30" class="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 border border-dashed border-primary-400 text-primary-600 rounded-lg text-sm hover:bg-primary-50 disabled:opacity-50">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                            {{ t('tools.invoice_generator.add_line') }}
                        </button>
                    </div>

                    <!-- Totals preview -->
                    <div class="bg-slate-50 rounded-lg p-4 mb-6">
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div>
                                <p class="text-xs text-slate-500 uppercase">{{ t('tools.invoice_generator.subtotal') }}</p>
                                <p class="text-lg font-bold text-slate-900">{{ fmt(subtotal) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-primary-600 uppercase">{{ t('tools.invoice_generator.vat') }}</p>
                                <p class="text-lg font-bold text-primary-600">{{ fmt(vatTotal) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-emerald-600 uppercase">{{ t('tools.invoice_generator.total') }}</p>
                                <p class="text-lg font-bold text-emerald-700">{{ fmt(total) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-700 mb-1">{{ t('tools.invoice_generator.notes') }}</label>
                        <textarea v-model="invoice.notes" rows="2" :placeholder="t('tools.invoice_generator.notes_placeholder')" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 text-sm"></textarea>
                    </div>

                    <!-- Generate -->
                    <div v-if="error" class="mb-4 p-3 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded">{{ error }}</div>
                    <button type="button" @click="generatePdf" :disabled="!formValid || generating" class="w-full px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-xl disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        <span v-if="generating">{{ t('tools.invoice_generator.generating') }}…</span>
                        <span v-else>{{ t('tools.invoice_generator.download_button') }}</span>
                    </button>
                </div>

                <!-- CTA upgrade -->
                <div class="bg-gradient-to-br from-primary-50 to-[#00f5d4]/10 rounded-2xl p-6 sm:p-8 text-center mb-12">
                    <h2 class="text-xl font-bold text-slate-900 mb-2">{{ t('tools.invoice_generator.upgrade_title') }}</h2>
                    <p class="text-slate-600 mb-6 leading-relaxed">{{ t('tools.invoice_generator.upgrade_subtitle') }}</p>
                    <Link :href="route('register')" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-xl transition-colors">
                        {{ t('tools.invoice_generator.upgrade_button') }}
                    </Link>
                </div>

                <!-- FAQ -->
                <div class="mb-12">
                    <h2 class="text-2xl font-bold text-slate-900 text-center mb-8">{{ t('tools.invoice_generator.faq.title') }}</h2>
                    <div class="space-y-4">
                        <details v-for="(faq, i) in faqs" :key="i" class="group bg-white rounded-xl border border-gray-200">
                            <summary class="flex items-center justify-between cursor-pointer px-6 py-4 font-medium text-slate-900">
                                {{ faq.q }}
                                <svg class="w-5 h-5 text-slate-400 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                            </summary>
                            <div class="px-6 pb-4 text-sm text-slate-600 leading-relaxed">
                                {{ faq.a }}
                            </div>
                        </details>
                    </div>
                </div>

                <!-- Related tools (internal linking) -->
                <div class="mb-12">
                    <h2 class="text-xl font-bold text-slate-900 mb-6">{{ t('tools.related_title') }}</h2>
                    <div class="grid sm:grid-cols-3 gap-4">
                        <Link
                            v-for="tool in relatedTools"
                            :key="tool.key"
                            :href="tool.route"
                            class="block p-4 rounded-xl border border-gray-200 hover:border-primary-500 hover:shadow-sm transition-all bg-white"
                        >
                            <p class="font-semibold text-slate-900 mb-1">{{ t('tools.index.' + tool.key + '.title') }}</p>
                            <p class="text-sm text-slate-600">{{ t('tools.index.' + tool.key + '.description') }}</p>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </MarketingLayout>
</template>

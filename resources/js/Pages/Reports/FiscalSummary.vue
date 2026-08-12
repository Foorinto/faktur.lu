<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AccountingNav from '@/Components/AccountingNav.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useTour } from '@/Composables/useTour';

const { t } = useTranslations();
const { startTour } = useTour();

onMounted(() => setTimeout(() => startTour('fiscalSummary'), 600));

const props = defineProps({
    year: [String, Number],
    years: Array,
    summary: Object,
});

const selectedYear = ref(props.year);

const changeYear = () => {
    router.get(route('reports.fiscal-summary'), {
        year: selectedYear.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const exportPdf = () => {
    window.location.href = route('reports.fiscal-summary.pdf', { year: selectedYear.value });
};

const exportCsv = () => {
    window.location.href = route('reports.fiscal-summary.csv', { year: selectedYear.value });
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0);
};

const months = [
    t('months.january'), t('months.february'), t('months.march'), t('months.april'),
    t('months.may'), t('months.june'), t('months.july'), t('months.august'),
    t('months.september'), t('months.october'), t('months.november'), t('months.december'),
];

const showTaxxGuide = ref(false);
const showMyguichetGuide = ref(false);

const categoryLabels = {
    hardware: 'expense_categories.hardware',
    software: 'expense_categories.software',
    hosting: 'expense_categories.hosting',
    office: 'expense_categories.office',
    travel: 'expense_categories.travel',
    training: 'expense_categories.training',
    professional_services: 'expense_categories.professional_services',
    telecommunications: 'expense_categories.telecommunications',
    other: 'expense_categories.other',
};
</script>

<template>
    <Head :title="t('fiscal_summary_title', { year: year })" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                {{ t('fiscal_summary_title', { year: year }) }}
            </h1>
        </template>
        <template #header-actions>
            <select
                data-tour="fiscal-summary-year"
                v-model="selectedYear"
                @change="changeYear"
                class="rounded-xl border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            >
                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>
            <button
                type="button"
                @click="exportCsv"
                class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-slate-300 dark:hover:bg-gray-800"
            >
                <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
                CSV
            </button>
            <button
                type="button"
                @click="exportPdf"
                class="inline-flex items-center rounded-xl bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500"
            >
                <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
                PDF
            </button>
        </template>

        <AccountingNav class="mb-6" />

        <div class="space-y-6">
            <!-- KPI Cards -->
            <div data-tour="fiscal-summary-intro" class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-surface-card px-4 py-5">
                    <dt class="truncate text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('fiscal_revenue_ht') }}</dt>
                    <dd class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white">{{ formatCurrency(summary.revenue.total_ht) }}</dd>
                </div>
                <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-surface-card px-4 py-5">
                    <dt class="truncate text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('fiscal_expenses_ht') }}</dt>
                    <dd class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white">{{ formatCurrency(summary.expenses.total_ht) }}</dd>
                </div>
                <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-surface-card px-4 py-5">
                    <dt class="truncate text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('fiscal_net_vat') }}</dt>
                    <dd :class="['mt-1 text-2xl font-semibold', summary.vat.balance >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-pink-600 dark:text-pink-400']">
                        {{ formatCurrency(summary.vat.balance) }}
                    </dd>
                </div>
                <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-surface-card px-4 py-5">
                    <dt class="truncate text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('fiscal_net_profit') }}</dt>
                    <dd :class="['mt-1 text-2xl font-semibold', summary.net_profit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-pink-600 dark:text-pink-400']">
                        {{ formatCurrency(summary.net_profit) }}
                    </dd>
                </div>
            </div>

            <!-- Monthly Revenue -->
            <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-surface-card">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('fiscal_monthly_revenue') }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('fiscal_summary_month') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('fiscal_revenue_ht') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <tr v-for="(amount, month) in summary.revenue.by_month" :key="month" class="hover:bg-slate-50 dark:hover:bg-gray-800/50">
                                <td class="px-6 py-3 text-sm text-slate-900 dark:text-white">{{ months[month - 1] }}</td>
                                <td class="px-6 py-3 text-right text-sm font-mono text-slate-900 dark:text-white">{{ formatCurrency(amount) }}</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-slate-50 dark:bg-gray-800">
                            <tr>
                                <td class="px-6 py-3 text-sm font-bold text-slate-900 dark:text-white">
                                    {{ t('fiscal_summary_total_invoices', { count: summary.revenue.invoice_count }) }}
                                </td>
                                <td class="px-6 py-3 text-right text-sm font-bold font-mono text-slate-900 dark:text-white">{{ formatCurrency(summary.revenue.total_ht) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Expenses by Category -->
            <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-surface-card">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('fiscal_expenses_by_category') }}</h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ t('fiscal_form152_line') }} {{ t('fiscal_summary_form152_indep_suffix') }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('fiscal_category') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('fiscal_form152_line') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('fiscal_summary_ht') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('fiscal_vat_deductible') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('fiscal_count') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <tr v-for="(data, cat) in summary.expenses.by_category" :key="cat" class="hover:bg-slate-50 dark:hover:bg-gray-800/50">
                                <!-- Le libellé vient du serveur : les
                                     catégories sont renommables, et la liste
                                     figée d'origine réaffichait « Fournitures
                                     de bureau » à qui avait rebaptisé la
                                     sienne « Loyer et charges ». -->
                                <td class="px-6 py-3 text-sm text-slate-900 dark:text-white">{{ data.label || t(categoryLabels[cat] || cat) }}</td>
                                <td class="px-6 py-3 text-xs text-slate-500 dark:text-slate-400">{{ data.form152_label }}</td>
                                <td class="px-6 py-3 text-right text-sm font-mono text-slate-900 dark:text-white">{{ formatCurrency(data.total_ht) }}</td>
                                <td class="px-6 py-3 text-right text-sm font-mono text-slate-900 dark:text-white">
                                    {{ formatCurrency(data.total_vat_deductible) }}
                                    <!-- La TVA payée mais non récupérable ici
                                         n'est pas rien : c'est elle qui fait
                                         l'objet d'une demande de remboursement.
                                         La taire ferait croire à une erreur de
                                         saisie. -->
                                    <span
                                        v-if="data.total_vat_non_deductible > 0"
                                        class="block text-xs font-normal text-amber-700 dark:text-amber-500"
                                    >
                                        {{ t('fiscal_of_which_non_deductible', { amount: formatCurrency(data.total_vat_non_deductible) }) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-right text-sm text-slate-900 dark:text-white">{{ data.count }}</td>
                            </tr>
                            <tr v-if="Object.keys(summary.expenses.by_category).length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-slate-500 dark:text-slate-400">{{ t('fiscal_no_data') }}</td>
                            </tr>
                        </tbody>
                        <tfoot v-if="Object.keys(summary.expenses.by_category).length > 0" class="bg-slate-50 dark:bg-gray-800">
                            <tr>
                                <td colspan="2" class="px-6 py-3 text-sm font-bold text-slate-900 dark:text-white">{{ t('fiscal_summary_total') }}</td>
                                <td class="px-6 py-3 text-right text-sm font-bold font-mono text-slate-900 dark:text-white">{{ formatCurrency(summary.expenses.total_ht) }}</td>
                                <td class="px-6 py-3 text-right text-sm font-bold font-mono text-slate-900 dark:text-white">{{ formatCurrency(summary.expenses.total_vat_deductible) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- VAT Summary -->
            <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-surface-card">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('fiscal_summary_vat') }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <!-- Sans autoliquidation, deux lignes suffisent et
                                 rien ne change. Dès qu'il y en a, « collectée »
                                 devient faux — ces montants n'ont été encaissés
                                 auprès d'aucun client — et le détail des deux
                                 origines devient nécessaire. -->
                            <tr class="hover:bg-slate-50 dark:hover:bg-gray-800/50">
                                <td class="px-6 py-3 text-sm text-slate-900 dark:text-white">
                                    {{ summary.vat.has_reverse_charge ? t('fiscal_vat_due') : t('fiscal_vat_collected') }}
                                </td>
                                <td class="px-6 py-3 text-right text-sm font-mono text-slate-900 dark:text-white">{{ formatCurrency(summary.vat.collected) }}</td>
                            </tr>
                            <template v-if="summary.vat.has_reverse_charge">
                                <tr class="bg-slate-50/50 dark:bg-gray-800/30">
                                    <td class="py-2 pl-12 pr-6 text-xs text-slate-500 dark:text-slate-400">{{ t('fiscal_vat_on_sales') }}</td>
                                    <td class="px-6 py-2 text-right text-xs font-mono text-slate-500 dark:text-slate-400">{{ formatCurrency(summary.vat.collected_on_sales) }}</td>
                                </tr>
                                <tr class="bg-slate-50/50 dark:bg-gray-800/30">
                                    <td class="py-2 pl-12 pr-6 text-xs text-slate-500 dark:text-slate-400">{{ t('fiscal_vat_reverse_charge_line') }}</td>
                                    <td class="px-6 py-2 text-right text-xs font-mono text-slate-500 dark:text-slate-400">{{ formatCurrency(summary.vat.reverse_charge_due) }}</td>
                                </tr>
                            </template>

                            <tr class="hover:bg-slate-50 dark:hover:bg-gray-800/50">
                                <td class="px-6 py-3 text-sm text-slate-900 dark:text-white">{{ t('fiscal_vat_deductible') }}</td>
                                <td class="px-6 py-3 text-right text-sm font-mono text-slate-900 dark:text-white">{{ formatCurrency(summary.vat.deductible) }}</td>
                            </tr>
                            <template v-if="summary.vat.has_reverse_charge">
                                <tr class="bg-slate-50/50 dark:bg-gray-800/30">
                                    <td class="py-2 pl-12 pr-6 text-xs text-slate-500 dark:text-slate-400">{{ t('fiscal_vat_on_purchases') }}</td>
                                    <td class="px-6 py-2 text-right text-xs font-mono text-slate-500 dark:text-slate-400">{{ formatCurrency(summary.vat.deductible_on_purchases) }}</td>
                                </tr>
                                <tr class="bg-slate-50/50 dark:bg-gray-800/30">
                                    <td class="py-2 pl-12 pr-6 text-xs text-slate-500 dark:text-slate-400">{{ t('fiscal_vat_reverse_charge_line') }}</td>
                                    <td class="px-6 py-2 text-right text-xs font-mono text-slate-500 dark:text-slate-400">{{ formatCurrency(summary.vat.reverse_charge_deductible) }}</td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-slate-50 dark:bg-gray-800">
                            <tr>
                                <td class="px-6 py-3 text-sm font-bold text-slate-900 dark:text-white">{{ t('fiscal_vat_balance') }}</td>
                                <td :class="['px-6 py-3 text-right text-sm font-bold font-mono', summary.vat.balance >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-pink-600 dark:text-pink-400']">
                                    {{ formatCurrency(summary.vat.balance) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>

                    <!-- Le même montant des deux côtés surprend au premier
                         coup d'œil. Une phrase ici évite d'avoir à chercher
                         ailleurs pourquoi. -->
                    <p
                        v-if="summary.vat.has_reverse_charge"
                        class="border-t border-gray-200 px-6 py-3 text-xs text-slate-500 dark:border-gray-700 dark:text-slate-400"
                    >
                        {{ t('fiscal_reverse_charge_explanation') }}
                    </p>
                </div>

                <!-- VAT breakdown by rate -->
                <div v-if="summary.revenue.vat_breakdown && summary.revenue.vat_breakdown.length > 0" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">{{ t('fiscal_vat_breakdown') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead class="bg-slate-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('fiscal_summary_rate') }}</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('fiscal_summary_base_ht') }}</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('fiscal_summary_vat') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                <tr v-for="vat in summary.revenue.vat_breakdown" :key="vat.rate">
                                    <td class="px-4 py-2 text-sm text-slate-900 dark:text-white">{{ vat.rate }}%</td>
                                    <td class="px-4 py-2 text-right text-sm font-mono text-slate-900 dark:text-white">{{ formatCurrency(vat.base) }}</td>
                                    <td class="px-4 py-2 text-right text-sm font-mono text-slate-900 dark:text-white">{{ formatCurrency(vat.amount) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Export explanation + Disclaimer -->
            <div class="rounded-2xl bg-slate-50 border border-slate-200 px-6 py-5 dark:bg-gray-800/50 dark:border-gray-700 space-y-3">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-1">{{ t('fiscal_export_purpose_title') }}</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ t('fiscal_export_purpose_desc') }}</p>
                </div>
                <ul class="text-sm text-slate-600 dark:text-slate-400 space-y-1 list-disc list-inside">
                    <li>{{ t('fiscal_export_use_1') }}</li>
                    <li>{{ t('fiscal_export_use_2') }}</li>
                    <li>{{ t('fiscal_export_use_3') }}</li>
                </ul>
                <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 dark:bg-amber-900/20 dark:border-amber-800">
                    <p class="text-sm text-amber-800 dark:text-amber-300">{{ t('fiscal_disclaimer') }}</p>
                </div>
            </div>

            <!-- Tax Filing Guide -->
            <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-surface-card">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('fiscal_filing_guide') }}</h2>
                </div>
                <div class="p-6 space-y-4">
                    <!-- Key Dates -->
                    <div class="rounded-xl bg-blue-50 border border-blue-200 px-5 py-4 dark:bg-blue-900/20 dark:border-blue-800">
                        <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-2">{{ t('fiscal_key_dates') }}</h3>
                        <ul class="text-sm text-blue-700 dark:text-blue-400 space-y-1">
                            <li>{{ t('fiscal_acd_forms_available') }}</li>
                            <li>{{ t('fiscal_filing_deadline') }}</li>
                            <li>{{ t('fiscal_quarterly_payments') }}</li>
                        </ul>
                    </div>

                    <!-- taxx.lu Guide -->
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700">
                        <button
                            @click="showTaxxGuide = !showTaxxGuide"
                            class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50 dark:hover:bg-gray-800/50 transition-colors rounded-xl"
                        >
                            <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ t('fiscal_guide_taxx') }}</span>
                            <svg :class="['h-5 w-5 text-slate-400 transition-transform', showTaxxGuide ? 'rotate-180' : '']" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div v-show="showTaxxGuide" class="px-5 pb-5">
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">{{ t('fiscal_summary_taxx_subtitle') }}</p>
                            <ol class="space-y-2 text-sm text-slate-700 dark:text-slate-300 list-decimal list-inside">
                                <li>{{ t('fiscal_summary_taxx_step1') }}</li>
                                <li>{{ t('fiscal_summary_taxx_step2') }}</li>
                                <li>{{ t('fiscal_summary_taxx_step3') }}</li>
                                <li>{{ t('fiscal_summary_taxx_step4') }}</li>
                                <li>{{ t('fiscal_summary_taxx_step5') }}</li>
                                <li>{{ t('fiscal_summary_taxx_step6') }}</li>
                            </ol>
                            <a
                                href="https://taxx.lu/en/tax-return-guide/income-from-self-employment-activities"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1 mt-3 text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400"
                            >
                                {{ t('fiscal_summary_taxx_link') }}
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.22 14.78a.75.75 0 001.06 0l7.22-7.22v5.69a.75.75 0 001.5 0v-7.5a.75.75 0 00-.75-.75h-7.5a.75.75 0 000 1.5h5.69l-7.22 7.22a.75.75 0 000 1.06z" clip-rule="evenodd" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- MyGuichet.lu Guide -->
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700">
                        <button
                            @click="showMyguichetGuide = !showMyguichetGuide"
                            class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50 dark:hover:bg-gray-800/50 transition-colors rounded-xl"
                        >
                            <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ t('fiscal_guide_myguichet') }}</span>
                            <svg :class="['h-5 w-5 text-slate-400 transition-transform', showMyguichetGuide ? 'rotate-180' : '']" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div v-show="showMyguichetGuide" class="px-5 pb-5">
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">{{ t('fiscal_summary_myguichet_subtitle') }}</p>
                            <ol class="space-y-2 text-sm text-slate-700 dark:text-slate-300 list-decimal list-inside">
                                <li>{{ t('fiscal_summary_myguichet_step1') }}</li>
                                <li>{{ t('fiscal_summary_myguichet_step2') }}</li>
                                <li>{{ t('fiscal_summary_myguichet_step3') }}</li>
                                <li>{{ t('fiscal_summary_myguichet_step4') }}</li>
                                <li>{{ t('fiscal_summary_myguichet_step5') }}</li>
                                <li>{{ t('fiscal_summary_myguichet_step6') }}</li>
                            </ol>
                            <a
                                href="https://guichet.public.lu/en/citoyens/fiscalite/declaration-impot-decompte/activite-professionnelle/declaration-revenus/declaration-impot-electronique.html"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1 mt-3 text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400"
                            >
                                {{ t('fiscal_summary_myguichet_link') }}
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.22 14.78a.75.75 0 001.06 0l7.22-7.22v5.69a.75.75 0 001.5 0v-7.5a.75.75 0 00-.75-.75h-7.5a.75.75 0 000 1.5h5.69l-7.22 7.22a.75.75 0 000 1.06z" clip-rule="evenodd" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Useful Links -->
                    <div class="rounded-xl bg-slate-50 border border-slate-200 px-5 py-4 dark:bg-gray-800/50 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">{{ t('fiscal_useful_links') }}</h3>
                        <ul class="text-sm text-slate-600 dark:text-slate-400 space-y-1">
                            <li>
                                <a href="https://impotsdirects.public.lu/fr/formulaires/pers_physiques.html" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:text-primary-500 dark:text-primary-400 underline">
                                    {{ t('fiscal_summary_link_acd_forms') }}
                                </a>
                            </li>
                            <li>
                                <a href="https://www.guidedesimpots.lu/formulaires/" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:text-primary-500 dark:text-primary-400 underline">
                                    {{ t('fiscal_summary_link_guide_impots') }}
                                </a>
                            </li>
                            <li>
                                <a href="https://taxx.lu/en/tax-return-guide/forms" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:text-primary-500 dark:text-primary-400 underline">
                                    {{ t('fiscal_summary_link_taxxlu_forms') }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

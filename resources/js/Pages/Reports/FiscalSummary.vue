<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AccountingNav from '@/Components/AccountingNav.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

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
    'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
    'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre',
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
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
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
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Mois</th>
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
                                    Total ({{ summary.revenue.invoice_count }} facture{{ summary.revenue.invoice_count > 1 ? 's' : '' }})
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
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ t('fiscal_form152_line') }} — Formulaire 152 (indépendants)</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('fiscal_category') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('fiscal_form152_line') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">HT</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('fiscal_vat_deductible') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('fiscal_count') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <tr v-for="(data, cat) in summary.expenses.by_category" :key="cat" class="hover:bg-slate-50 dark:hover:bg-gray-800/50">
                                <td class="px-6 py-3 text-sm text-slate-900 dark:text-white">{{ t(categoryLabels[cat] || cat) }}</td>
                                <td class="px-6 py-3 text-xs text-slate-500 dark:text-slate-400">{{ data.form152_label }}</td>
                                <td class="px-6 py-3 text-right text-sm font-mono text-slate-900 dark:text-white">{{ formatCurrency(data.total_ht) }}</td>
                                <td class="px-6 py-3 text-right text-sm font-mono text-slate-900 dark:text-white">{{ formatCurrency(data.total_vat) }}</td>
                                <td class="px-6 py-3 text-right text-sm text-slate-900 dark:text-white">{{ data.count }}</td>
                            </tr>
                            <tr v-if="Object.keys(summary.expenses.by_category).length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-slate-500 dark:text-slate-400">{{ t('fiscal_no_data') }}</td>
                            </tr>
                        </tbody>
                        <tfoot v-if="Object.keys(summary.expenses.by_category).length > 0" class="bg-slate-50 dark:bg-gray-800">
                            <tr>
                                <td colspan="2" class="px-6 py-3 text-sm font-bold text-slate-900 dark:text-white">Total</td>
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
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">TVA</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <tr class="hover:bg-slate-50 dark:hover:bg-gray-800/50">
                                <td class="px-6 py-3 text-sm text-slate-900 dark:text-white">{{ t('fiscal_vat_collected') }}</td>
                                <td class="px-6 py-3 text-right text-sm font-mono text-slate-900 dark:text-white">{{ formatCurrency(summary.vat.collected) }}</td>
                            </tr>
                            <tr class="hover:bg-slate-50 dark:hover:bg-gray-800/50">
                                <td class="px-6 py-3 text-sm text-slate-900 dark:text-white">{{ t('fiscal_vat_deductible') }}</td>
                                <td class="px-6 py-3 text-right text-sm font-mono text-slate-900 dark:text-white">{{ formatCurrency(summary.vat.deductible) }}</td>
                            </tr>
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
                </div>

                <!-- VAT breakdown by rate -->
                <div v-if="summary.revenue.vat_breakdown && summary.revenue.vat_breakdown.length > 0" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">{{ t('fiscal_vat_breakdown') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead class="bg-slate-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Taux</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Base HT</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">TVA</th>
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
                            <li>Formulaires ACD disponibles : vers le 7 avril de chaque année</li>
                            <li>Date limite de dépôt : 31 décembre de l'année suivante</li>
                            <li>Acomptes trimestriels (indépendants) : 10 mars, 10 juin, 10 septembre, 10 décembre</li>
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
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Service en ligne payant (~69-199 €) — sans LuxTrust, email uniquement.</p>
                            <ol class="space-y-2 text-sm text-slate-700 dark:text-slate-300 list-decimal list-inside">
                                <li>Créez un compte gratuit sur taxx.lu (email uniquement, sans LuxTrust ni eID).</li>
                                <li>Importez vos justificatifs via AutoScan (app mobile ou upload PDF) — certificats de salaire, relevés, etc.</li>
                                <li>Complétez votre déclaration étape par étape — le système traduit les termes fiscaux en langage clair.</li>
                                <li>Pour les indépendants : dans la section « Bénéfice d'une profession libérale », saisissez manuellement vos montants de revenus et dépenses. Gardez ce récapitulatif sous les yeux comme aide-mémoire.</li>
                                <li>Joignez le formulaire 152 si vous êtes en méthode recettes-dépenses (cas le plus courant pour les freelances).</li>
                                <li>Vérifiez l'estimation d'impôt via l'Opti Score, puis téléchargez et soumettez à l'ACD.</li>
                            </ol>
                            <a
                                href="https://taxx.lu/en/tax-return-guide/income-from-self-employment-activities"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1 mt-3 text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400"
                            >
                                Guide indépendants taxx.lu
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
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Plateforme officielle gratuite du gouvernement luxembourgeois — nécessite LuxTrust ou eID.</p>
                            <ol class="space-y-2 text-sm text-slate-700 dark:text-slate-300 list-decimal list-inside">
                                <li>Connectez-vous sur myguichet.lu avec votre produit LuxTrust ou votre carte d'identité électronique.</li>
                                <li>Accédez à l'assistant électronique depuis votre eSpace privé (ou professionnel pour les fiduciaires).</li>
                                <li>Répondez aux questions guidées — l'assistant s'adapte à votre situation fiscale.</li>
                                <li>Saisissez manuellement vos revenus et dépenses professionnels dans les champs correspondants. Ce récapitulatif vous sert de référence pour retrouver les bons montants.</li>
                                <li>Joignez les justificatifs demandés (formulaire 152 le cas échéant, factures, etc.).</li>
                                <li>Soumettez électroniquement — la déclaration est traitée automatiquement par l'ACD.</li>
                            </ol>
                            <a
                                href="https://guichet.public.lu/en/citoyens/fiscalite/declaration-impot-decompte/activite-professionnelle/declaration-revenus/declaration-impot-electronique.html"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1 mt-3 text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400"
                            >
                                Assistant électronique MyGuichet.lu
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
                                    Formulaires ACD (personnes physiques)
                                </a>
                            </li>
                            <li>
                                <a href="https://www.guidedesimpots.lu/formulaires/" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:text-primary-500 dark:text-primary-400 underline">
                                    Guide des Impôts — Tous les formulaires
                                </a>
                            </li>
                            <li>
                                <a href="https://taxx.lu/en/tax-return-guide/forms" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:text-primary-500 dark:text-primary-400 underline">
                                    taxx.lu — Guide des formulaires fiscaux
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

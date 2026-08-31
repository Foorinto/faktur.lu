<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import CashflowChart from '@/Components/Dashboard/CashflowChart.vue';
import FranchiseAlert from '@/Components/FranchiseAlert.vue';
import QuotaAlertBanner from '@/Components/QuotaAlertBanner.vue';
import OnboardingChecklist from '@/Components/OnboardingChecklist.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useTour } from '@/Composables/useTour';

const { t } = useTranslations();
const { startDashboardTour } = useTour();

onMounted(() => {
    setTimeout(() => startDashboardTour(), 800);
});

const props = defineProps({
    kpis: Object,
    revenueChart: Array,
    unpaidInvoices: Array,
    unbilledTimeByClient: Array,
    recentInvoices: Array,
    availableYears: Array,
    selectedYear: Number,
    franchiseAlert: Object,
    cashflowForecast: Object,
    onboardingChecklist: Object,
    quotaAlerts: { type: Array, default: () => [] },
    // Encaissements de l'année, par moyen (FEAT-114).
    encaissementsParMoyen: {
        type: Object,
        default: () => ({ annee: 0, verrouille: false, total: 0, lignes: [] }),
    },
});

/**
 * Le seuil de franchise ne concerne QUE les entreprises sous ce régime.
 *
 * Un assujetti l'a déjà franchi ou n'y a jamais été : lui montrer « 11,6 % —
 * 44 176 € restant avant le seuil » annonce une échéance qui n'existe pas
 * pour lui. Un client l'a signalé (2026-08-31). Le bandeau d'alerte, lui,
 * vérifiait déjà le régime : la carte ne le faisait pas.
 */
const sousRegimeDeFranchise = computed(
    () => props.franchiseAlert?.is_franchise_regime ?? false,
);

const selectedYear = ref(props.selectedYear);

const hoveredMonth = ref(null);

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-LU', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0);
};

const formatPercentage = (value) => {
    if (value === null || value === undefined) return '-';
    const sign = value > 0 ? '+' : '';
    return `${sign}${value.toFixed(1)}%`;
};

const changeYear = (year) => {
    selectedYear.value = year;
    router.get(route('dashboard'), { year }, { preserveState: true });
};

// Compute max revenue for chart scaling
const maxRevenue = computed(() => {
    if (!props.revenueChart) return 0;
    return Math.max(...props.revenueChart.map(m => m.revenue), 1);
});

// Y-axis scale ticks (5 nice round values from 0 to max)
const yAxisTicks = computed(() => {
    const max = maxRevenue.value;
    if (max <= 1) return [0];
    // Find a nice round step
    const rawStep = max / 4;
    const magnitude = Math.pow(10, Math.floor(Math.log10(rawStep)));
    const normalized = rawStep / magnitude;
    let niceStep;
    if (normalized <= 1) niceStep = 1 * magnitude;
    else if (normalized <= 2) niceStep = 2 * magnitude;
    else if (normalized <= 5) niceStep = 5 * magnitude;
    else niceStep = 10 * magnitude;
    const ticks = [];
    for (let v = 0; v <= max; v += niceStep) {
        ticks.push(v);
    }
    // Ensure we have the top tick above max
    if (ticks[ticks.length - 1] < max) {
        ticks.push(ticks[ticks.length - 1] + niceStep);
    }
    return ticks;
});

const chartMax = computed(() => {
    const ticks = yAxisTicks.value;
    return ticks[ticks.length - 1] || 1;
});

const formatShortCurrency = (value) => {
    if (value >= 1000) return `${(value / 1000).toFixed(value % 1000 === 0 ? 0 : 1)}k €`;
    return `${value} €`;
};

// Progress bar color based on percentage
const getProgressBarColor = (percentage) => {
    if (percentage >= 100) return 'bg-accent-rose';
    if (percentage >= 80) return 'bg-amber-500';
    return 'bg-primary-500';
};

// Alert level color
const getAlertColor = (level) => {
    if (level === 'critical') return 'bg-pink-50 border-pink-200 text-pink-800 dark:bg-pink-900/20 dark:border-pink-800 dark:text-pink-200';
    if (level === 'warning') return 'bg-amber-50 border-amber-200 text-amber-800 dark:bg-amber-900/20 dark:border-amber-800 dark:text-amber-200';
    return 'bg-sky-50 border-sky-200 text-sky-800 dark:bg-sky-900/20 dark:border-sky-800 dark:text-sky-200';
};

const getAlertIcon = (level) => {
    if (level === 'critical') return 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z';
    if (level === 'warning') return 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z';
    return 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
};

// Status badge for invoices
const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-100 text-slate-700 dark:bg-gray-800 dark:text-slate-300',
        finalized: 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400',
        sent: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        paid: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    };
    return badges[status] || badges.draft;
};

const getStatusLabel = (status) => {
    return t(status) || status;
};
</script>

<template>
    <Head :title="t('dashboard')" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">
                {{ t('dashboard') }}
            </h1>
        </template>
        <template #header-actions>
            <div class="flex items-center space-x-2">
                <label for="year" class="text-sm font-medium text-slate-600 dark:text-slate-400">{{ t('year') }} :</label>
                <select
                    id="year"
                    v-model="selectedYear"
                    @change="changeYear(selectedYear)"
                    class="rounded-xl border-gray-300 py-1.5 pl-3 pr-8 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                >
                    <option v-for="year in availableYears" :key="year" :value="year">
                        {{ year }}
                    </option>
                </select>
            </div>
        </template>

        <!-- Onboarding Checklist -->
        <div v-if="onboardingChecklist" class="mb-6">
            <OnboardingChecklist :checklist="onboardingChecklist" />
        </div>

        <!-- Franchise Alert (TVA threshold warning) -->
        <QuotaAlertBanner :alerts="quotaAlerts" />

        <FranchiseAlert v-if="franchiseAlert" :franchise-alert="franchiseAlert" />

        <!-- Alerts -->
        <div v-if="kpis?.alerts?.length > 0" class="mb-6 space-y-3">
            <div
                v-for="(alert, index) in kpis.alerts"
                :key="index"
                :class="['rounded-2xl border p-4', getAlertColor(alert.level)]"
            >
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getAlertIcon(alert.level)" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium">{{ alert.title }}</h3>
                        <p class="mt-1 text-sm opacity-90">{{ alert.message }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI Cards -->
        <div data-tour="dashboard-kpis" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <!-- CA Annuel -->
            <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-500">
                                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="truncate text-sm font-medium text-slate-500 dark:text-slate-400">
                                    {{ t('annual_revenue') }}
                                </dt>
                                <dd class="flex items-baseline">
                                    <span class="text-2xl font-bold text-slate-900 dark:text-white">
                                        {{ formatCurrency(kpis?.annual_revenue) }}
                                    </span>
                                    <span
                                        v-if="kpis?.annual_revenue_change !== null"
                                        :class="[
                                            'ml-2 text-sm font-medium',
                                            kpis?.annual_revenue_change >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-pink-600 dark:text-pink-400'
                                        ]"
                                    >
                                        {{ formatPercentage(kpis?.annual_revenue_change) }}
                                    </span>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bénéfice Net -->
            <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#00f5d4]">
                                <svg class="h-6 w-6 text-slate-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="truncate text-sm font-medium text-slate-500 dark:text-slate-400">
                                    {{ t('net_profit') }}
                                </dt>
                                <dd class="flex items-baseline">
                                    <span :class="[
                                        'text-2xl font-bold',
                                        kpis?.net_profit >= 0 ? 'text-slate-900 dark:text-white' : 'text-pink-600 dark:text-pink-400'
                                    ]">
                                        {{ formatCurrency(kpis?.net_profit) }}
                                    </span>
                                    <span
                                        v-if="kpis?.net_profit_change !== null"
                                        :class="[
                                            'ml-2 text-sm font-medium',
                                            kpis?.net_profit_change >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-pink-600 dark:text-pink-400'
                                        ]"
                                    >
                                        {{ formatPercentage(kpis?.net_profit_change) }}
                                    </span>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Factures impayées -->
            <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div :class="[
                                'flex h-12 w-12 items-center justify-center rounded-xl',
                                kpis?.unpaid_invoices?.overdue_count > 0 ? 'bg-accent-rose' : 'bg-accent-blue'
                            ]">
                                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="truncate text-sm font-medium text-slate-500 dark:text-slate-400">
                                    {{ t('unpaid_invoices') }}
                                </dt>
                                <dd>
                                    <span class="text-2xl font-bold text-slate-900 dark:text-white">
                                        {{ formatCurrency(kpis?.unpaid_invoices?.total_amount) }}
                                    </span>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">
                                        {{ kpis?.unpaid_invoices?.count || 0 }} {{ t('invoices').toLowerCase() }}
                                        <span v-if="kpis?.unpaid_invoices?.overdue_count > 0" class="text-pink-600 dark:text-pink-400 font-medium">
                                            ({{ kpis?.unpaid_invoices?.overdue_count }} {{ t('overdue').toLowerCase() }})
                                        </span>
                                    </p>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Temps non facturé -->
            <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-accent-yellow">
                                <svg class="h-6 w-6 text-slate-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="truncate text-sm font-medium text-slate-500 dark:text-slate-400">
                                    {{ t('unbilled_time') }}
                                </dt>
                                <dd>
                                    <span class="text-2xl font-bold text-slate-900 dark:text-white">
                                        {{ kpis?.unbilled_time?.formatted || '0:00' }}
                                    </span>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">
                                        {{ kpis?.unbilled_time?.hours?.toFixed(1) || 0 }} {{ t('hours') }}
                                    </p>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Second Row: Progress Bars -->
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- VAT Franchise Threshold -->
            <div
                v-if="sousRegimeDeFranchise"
                class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50"
            >
                <div class="p-6">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                        {{ t('vat_franchise_threshold') }}
                    </h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ t('article_57_threshold') }} {{ formatCurrency(kpis?.vat_franchise_threshold) }}
                    </p>
                    <div class="mt-4">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-600 dark:text-slate-400">
                                {{ formatCurrency(kpis?.vat_franchise_progress?.current) }} / {{ formatCurrency(kpis?.vat_franchise_threshold) }}
                            </span>
                            <span class="font-semibold text-slate-900 dark:text-white">
                                {{ kpis?.vat_franchise_progress?.percentage?.toFixed(1) || 0 }}%
                            </span>
                        </div>
                        <div class="mt-2 h-3 w-full rounded-full bg-slate-200 dark:bg-gray-800">
                            <div
                                :class="['h-3 rounded-full transition-all', getProgressBarColor(kpis?.vat_franchise_progress?.percentage)]"
                                :style="{ width: `${Math.min(100, kpis?.vat_franchise_progress?.percentage || 0)}%` }"
                            ></div>
                        </div>
                        <p v-if="kpis?.vat_franchise_progress?.remaining > 0" class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                            {{ t('remaining_before_threshold', { amount: formatCurrency(kpis?.vat_franchise_progress?.remaining) }) }}
                        </p>
                        <p v-else class="mt-2 text-sm font-medium text-pink-600 dark:text-pink-400">
                            {{ t('threshold_exceeded_vat') }}
                        </p>
                        <a
                            v-if="kpis?.vat_franchise_article_url"
                            :href="kpis.vat_franchise_article_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
                        >
                            {{ t('vat_franchise_learn_more') }}
                            <span aria-hidden="true">↗</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Simplified Accounting Threshold -->
            <!-- Seul en ligne quand la franchise ne s'applique pas : une carte
                 orpheline sur une demi-largeur se lit comme un manque. -->
            <div
                :class="[
                    'overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50',
                    sousRegimeDeFranchise ? '' : 'lg:col-span-2',
                ]"
            >
                <div class="p-6">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                        {{ t('simplified_accounting_threshold') }}
                    </h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ t('threshold_of') }} {{ formatCurrency(kpis?.simplified_accounting_threshold) }}
                    </p>
                    <div class="mt-4">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-600 dark:text-slate-400">
                                {{ formatCurrency(kpis?.simplified_accounting_progress?.current) }} / {{ formatCurrency(kpis?.simplified_accounting_threshold) }}
                            </span>
                            <span class="font-semibold text-slate-900 dark:text-white">
                                {{ kpis?.simplified_accounting_progress?.percentage?.toFixed(1) || 0 }}%
                            </span>
                        </div>
                        <div class="mt-2 h-3 w-full rounded-full bg-slate-200 dark:bg-gray-800">
                            <div
                                :class="['h-3 rounded-full transition-all', getProgressBarColor(kpis?.simplified_accounting_progress?.percentage)]"
                                :style="{ width: `${Math.min(100, kpis?.simplified_accounting_progress?.percentage || 0)}%` }"
                            ></div>
                        </div>
                        <p v-if="kpis?.simplified_accounting_progress?.remaining > 0" class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                            {{ t('remaining_before_threshold', { amount: formatCurrency(kpis?.simplified_accounting_progress?.remaining) }) }}
                        </p>
                        <p v-else class="mt-2 text-sm font-medium text-pink-600 dark:text-pink-400">
                            {{ t('threshold_exceeded_accounting') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cashflow Forecast -->
        <div v-if="cashflowForecast?.has_data" class="mt-6">
            <CashflowChart :forecast="cashflowForecast" />
        </div>

        <!-- Chiffre d'affaires mensuel, sur toute la largeur -->
        <div class="mt-6">
            <!-- Revenue Chart -->
            <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                <div class="p-6">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                        {{ t('monthly_revenue') }}
                    </h3>
                    <div class="mt-4">
                        <div class="flex">
                            <!-- Y-axis labels -->
                            <div class="flex flex-col-reverse justify-between pr-2 text-right h-64 w-12 sm:w-14 flex-shrink-0">
                                <span
                                    v-for="tick in yAxisTicks"
                                    :key="tick"
                                    class="text-xs text-slate-400 dark:text-slate-500 leading-none"
                                >
                                    {{ formatShortCurrency(tick) }}
                                </span>
                            </div>
                            <!-- Chart area -->
                            <div class="flex-1 relative">
                                <!-- Grid lines -->
                                <div class="absolute inset-0 flex flex-col-reverse justify-between pointer-events-none">
                                    <div
                                        v-for="tick in yAxisTicks"
                                        :key="'grid-' + tick"
                                        class="border-t border-slate-200/50 dark:border-slate-700/50 w-full"
                                    ></div>
                                </div>
                                <!-- Bars -->
                                <div class="relative flex h-64 items-stretch justify-between gap-1 sm:gap-2">
                                    <div
                                        v-for="month in revenueChart"
                                        :key="month.month"
                                        class="relative flex flex-1 flex-col items-center justify-end"
                                        @mouseenter="hoveredMonth = month.month"
                                        @mouseleave="hoveredMonth = null"
                                    >
                                        <!-- Tooltip -->
                                        <div
                                            v-if="hoveredMonth === month.month"
                                            class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 z-10 whitespace-nowrap rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-medium text-white shadow-lg dark:bg-white dark:text-slate-900"
                                        >
                                            {{ month.label }} : {{ formatCurrency(month.revenue) }}
                                            <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-slate-900 dark:border-t-white"></div>
                                        </div>
                                        <div
                                            class="w-full rounded-t-lg bg-accent-rose hover:bg-pink-500 transition-colors cursor-pointer"
                                            :style="{ height: `${(month.revenue / chartMax) * 100}%`, minHeight: month.revenue > 0 ? '4px' : '0' }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- X-axis labels -->
                        <div class="flex mt-2 ml-12 sm:ml-14">
                            <div
                                v-for="month in revenueChart"
                                :key="'label-' + month.month"
                                class="flex-1 text-center text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 truncate"
                            >
                                {{ month.label }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Récapitulatif TVA et encaissements du mois, côte à côte -->
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- VAT Summary -->
            <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                <div class="p-6">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                        {{ t('vat_summary', { year: selectedYear }) }}
                    </h3>
                    <dl class="mt-4 space-y-4">
                        <div class="flex justify-between">
                            <dt class="text-sm text-slate-500 dark:text-slate-400">{{ t('collected_vat') }}</dt>
                            <dd class="text-sm font-medium text-slate-900 dark:text-white">
                                {{ formatCurrency(kpis?.vat_summary?.collected) }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-slate-500 dark:text-slate-400">{{ t('deductible_vat') }}</dt>
                            <dd class="text-sm font-medium text-slate-900 dark:text-white">
                                {{ formatCurrency(kpis?.vat_summary?.deductible) }}
                            </dd>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4 flex justify-between">
                            <dt class="text-sm font-semibold text-slate-900 dark:text-white">{{ t('balance') }}</dt>
                            <dd :class="[
                                'text-sm font-bold',
                                kpis?.vat_summary?.balance >= 0 ? 'text-pink-600 dark:text-pink-400' : 'text-emerald-600 dark:text-emerald-400'
                            ]">
                                {{ formatCurrency(kpis?.vat_summary?.balance) }}
                            </dd>
                        </div>
                        <div v-if="kpis?.vat_summary?.to_pay > 0" class="text-sm text-slate-500 dark:text-slate-400">
                            {{ t('vat_to_pay') }} <span class="font-medium text-pink-600 dark:text-pink-400">{{ formatCurrency(kpis?.vat_summary?.to_pay) }}</span>
                        </div>
                        <div v-else-if="kpis?.vat_summary?.credit > 0" class="text-sm text-slate-500 dark:text-slate-400">
                            {{ t('vat_credit') }} <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ formatCurrency(kpis?.vat_summary?.credit) }}</span>
                        </div>
                    </dl>

                    <!-- Ces trois lignes ressemblent à une déclaration sans en
                         être une : elles ne connaissent que ce qui a été saisi
                         ici, et ne tiennent pas compte des régularisations que
                         seul un comptable identifie. -->
                    <p class="mt-4 border-t border-gray-200 pt-3 text-xs text-slate-500 dark:border-gray-700 dark:text-slate-400">
                        {{ t('notice_vat_dashboard') }}
                    </p>
                </div>
            </div>

            <!--
                Encaissements de l'année, par moyen de paiement (FEAT-114).

                Le chiffre d'affaires mensuel était déjà là ; ce qui manquait,
                c'est comment il a été réglé. Récapitulatif d'un coup d'œil ;
                chaque ligne renvoie au listing des factures filtré sur son
                moyen pour qui veut le détail.
            -->
            <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                <div class="p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                            {{ t('dashboard_payments_by_method', { year: selectedYear }) }}
                        </h3>
                        <!--
                            « Voir tout » mène au livre de recettes et non au
                            listing : c'est là que la période se choisit
                            librement et que la ventilation se détaille. Les
                            liens par moyen, eux, mènent aux factures.
                        -->
                        <Link
                            :href="route('reports.revenue-book', {
                                start_date: `${selectedYear}-01-01`,
                                end_date: `${selectedYear}-12-31`,
                            })"
                            class="whitespace-nowrap text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400"
                        >
                            {{ t('view_all') }}
                        </Link>
                    </div>

                    <!-- Année hors du plan Gratuit : même règle que le livre de recettes. -->
                    <p
                        v-if="encaissementsParMoyen.verrouille"
                        class="mt-4 text-sm text-slate-500 dark:text-slate-400"
                    >
                        {{ t('revenue_book_history_locked_note') }}
                        <Link
                            :href="route('subscription.index')"
                            class="font-medium text-primary-600 underline underline-offset-2 hover:text-primary-700 dark:text-primary-400"
                        >
                            {{ t('revenue_book_unlock_history') }}
                        </Link>
                    </p>

                    <p
                        v-else-if="!encaissementsParMoyen.lignes.length"
                        class="mt-4 text-sm text-slate-500 dark:text-slate-400"
                    >
                        {{ t('dashboard_payments_none') }}
                    </p>

                    <div v-else class="mt-4 space-y-3">
                        <Link
                            v-for="ligne in encaissementsParMoyen.lignes"
                            :key="ligne.label"
                            :href="route('invoices.index', { payment_method: ligne.method ?? 'unknown' })"
                            class="block rounded-lg -mx-2 px-2 py-1 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800"
                        >
                            <div class="flex items-baseline justify-between gap-3">
                                <span class="text-sm text-slate-700 dark:text-slate-300">
                                    {{ ligne.label }}
                                    <span class="text-xs text-slate-400 dark:text-slate-500">
                                        {{ t('dashboard_payments_count', { count: ligne.nombre }) }}
                                    </span>
                                </span>
                                <span class="whitespace-nowrap text-sm font-medium tabular-nums text-slate-900 dark:text-white">
                                    {{ formatCurrency(ligne.total) }}
                                    <span class="ml-1 text-xs font-normal text-slate-400">{{ ligne.part }} %</span>
                                </span>
                            </div>
                            <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                <div
                                    class="h-full rounded-full bg-accent-rose"
                                    :style="{ width: `${ligne.part}%` }"
                                ></div>
                            </div>
                        </Link>

                        <div class="flex justify-between border-t border-gray-200 pt-3 dark:border-gray-700">
                            <span class="text-sm text-slate-500 dark:text-slate-400">{{ t('total') }}</span>
                            <span class="text-sm font-semibold tabular-nums text-slate-900 dark:text-white">
                                {{ formatCurrency(encaissementsParMoyen.total) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fourth Row: Lists -->
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Unpaid Invoices -->
            <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                            {{ t('unpaid_invoices') }}
                        </h3>
                        <Link :href="route('invoices.index')" class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                            {{ t('view_all') }}
                        </Link>
                    </div>
                    <div class="mt-4">
                        <div v-if="unpaidInvoices?.length === 0" class="text-center py-4 text-sm text-slate-500 dark:text-slate-400">
                            {{ t('no_unpaid_invoices') }}
                        </div>
                        <ul v-else class="divide-y divide-slate-100 dark:divide-slate-700">
                            <li v-for="invoice in unpaidInvoices" :key="invoice.id" class="py-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <Link :href="route('invoices.show', invoice.id)" class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                                            {{ invoice.number }}
                                        </Link>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ invoice.client_name }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ formatCurrency(invoice.total_ttc) }}</p>
                                        <p :class="[
                                            'text-xs',
                                            invoice.is_overdue ? 'text-pink-600 dark:text-pink-400 font-medium' : 'text-slate-500 dark:text-slate-400'
                                        ]">
                                            <span v-if="invoice.is_overdue">
                                                {{ t('overdue') }} ({{ invoice.days_overdue }} {{ t('days_short') }})
                                            </span>
                                            <span v-else-if="invoice.due_at">
                                                {{ t('due_date') }} : {{ invoice.due_at }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Unbilled Time by Client -->
            <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                            {{ t('unbilled_time_by_client') }}
                        </h3>
                        <Link :href="route('time-entries.summary')" class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                            {{ t('view_all') }}
                        </Link>
                    </div>
                    <div class="mt-4">
                        <div v-if="unbilledTimeByClient?.length === 0" class="text-center py-4 text-sm text-slate-500 dark:text-slate-400">
                            {{ t('no_unbilled_time') }}
                        </div>
                        <ul v-else class="divide-y divide-slate-100 dark:divide-slate-700">
                            <li v-for="entry in unbilledTimeByClient" :key="entry.client_id" class="py-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-slate-900 dark:text-white">{{ entry.client_name }}</p>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ entry.formatted }} ({{ entry.hours }}h)</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                            ~{{ formatCurrency(entry.estimated_amount) }}
                                        </p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">
                                            {{ formatCurrency(entry.hourly_rate) }}/h
                                        </p>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Invoices -->
        <div class="mt-6 overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                        {{ t('recent_invoices') }}
                    </h3>
                    <Link :href="route('invoices.index')" class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                        {{ t('view_all') }}
                    </Link>
                </div>
                <div class="mt-4">
                    <div v-if="recentInvoices?.length === 0" class="text-center py-4 text-sm text-slate-500 dark:text-slate-400">
                        {{ t('no_invoice') }}
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead>
                                <tr>
                                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('number') }}</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('client') }}</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('date') }}</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('status') }}</th>
                                    <th class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('amount') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                <tr v-for="invoice in recentInvoices" :key="invoice.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="whitespace-nowrap px-3 py-3">
                                        <Link :href="route('invoices.show', invoice.id)" class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                                            {{ invoice.number }}
                                            <span v-if="invoice.is_credit_note" class="ml-1 text-xs text-slate-500">({{ t('credit_note') }})</span>
                                        </Link>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-900 dark:text-white">
                                        {{ invoice.client_name }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-sm text-slate-500 dark:text-slate-400">
                                        {{ invoice.issued_at }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3">
                                        <span :class="['inline-flex rounded-xl px-2.5 py-0.5 text-xs font-semibold', getStatusBadge(invoice.status)]">
                                            {{ getStatusLabel(invoice.status) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-right text-sm font-semibold text-slate-900 dark:text-white">
                                        {{ formatCurrency(invoice.total_ttc) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-6">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-4">
                {{ t('quick_actions') }}
            </h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Link
                    :href="route('invoices.create')"
                    class="group flex items-center justify-center rounded-2xl border-2 border-dashed border-gray-300 p-6 text-center hover:border-primary-500 hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-all dark:border-gray-700 dark:hover:border-primary-400 dark:hover:bg-primary-900/20"
                >
                    <div>
                        <div class="mx-auto h-12 w-12 rounded-xl bg-primary-100 flex items-center justify-center group-hover:bg-primary-500 transition-colors dark:bg-primary-900/30">
                            <svg class="h-6 w-6 text-primary-600 group-hover:text-white transition-colors dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </div>
                        <span class="mt-3 block text-sm font-medium text-slate-900 dark:text-slate-300">
                            {{ t('new_invoice') }}
                        </span>
                    </div>
                </Link>

                <Link
                    :href="route('clients.create')"
                    class="group flex items-center justify-center rounded-2xl border-2 border-dashed border-gray-300 p-6 text-center hover:border-accent-blue hover:bg-sky-50 focus:outline-none focus:ring-2 focus:ring-accent-blue focus:ring-offset-2 transition-all dark:border-gray-700 dark:hover:border-accent-blue dark:hover:bg-sky-900/20"
                >
                    <div>
                        <div class="mx-auto h-12 w-12 rounded-xl bg-sky-100 flex items-center justify-center group-hover:bg-accent-blue transition-colors dark:bg-sky-900/30">
                            <svg class="h-6 w-6 text-sky-600 group-hover:text-white transition-colors dark:text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                        </div>
                        <span class="mt-3 block text-sm font-medium text-slate-900 dark:text-slate-300">
                            {{ t('new_client') }}
                        </span>
                    </div>
                </Link>

                <Link
                    :href="route('time-entries.index')"
                    class="group flex items-center justify-center rounded-2xl border-2 border-dashed border-gray-300 p-6 text-center hover:border-accent-yellow hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-accent-yellow focus:ring-offset-2 transition-all dark:border-gray-700 dark:hover:border-accent-yellow dark:hover:bg-amber-900/20"
                >
                    <div>
                        <div class="mx-auto h-12 w-12 rounded-xl bg-amber-100 flex items-center justify-center group-hover:bg-accent-yellow transition-colors dark:bg-amber-900/30">
                            <svg class="h-6 w-6 text-amber-600 group-hover:text-slate-900 transition-colors dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="mt-3 block text-sm font-medium text-slate-900 dark:text-slate-300">
                            {{ t('time_tracking') }}
                        </span>
                    </div>
                </Link>

                <Link
                    :href="route('expenses.create')"
                    class="group flex items-center justify-center rounded-2xl border-2 border-dashed border-gray-300 p-6 text-center hover:border-accent-rose hover:bg-pink-50 focus:outline-none focus:ring-2 focus:ring-accent-rose focus:ring-offset-2 transition-all dark:border-gray-700 dark:hover:border-accent-rose dark:hover:bg-pink-900/20"
                >
                    <div>
                        <div class="mx-auto h-12 w-12 rounded-xl bg-pink-100 flex items-center justify-center group-hover:bg-accent-rose transition-colors dark:bg-pink-900/30">
                            <svg class="h-6 w-6 text-pink-600 group-hover:text-white transition-colors dark:text-pink-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <span class="mt-3 block text-sm font-medium text-slate-900 dark:text-slate-300">
                            {{ t('new_expense') }}
                        </span>
                    </div>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

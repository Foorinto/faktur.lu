<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import FranchiseAlert from '@/Components/FranchiseAlert.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    kpis: Object,
    revenueChart: Array,
    unpaidInvoices: Array,
    unbilledTimeByClient: Array,
    recentInvoices: Array,
    availableYears: Array,
    selectedYear: Number,
    franchiseAlert: Object,
});

const selectedYear = ref(props.selectedYear);

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

// Progress bar color based on percentage
const getProgressBarColor = (percentage) => {
    if (percentage >= 100) return 'bg-accent-pink';
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
        draft: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
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
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold text-slate-900 dark:text-white">
                    {{ t('dashboard') }}
                </h1>
                <!-- Year Selector -->
                <div class="flex items-center space-x-2">
                    <label for="year" class="text-sm font-medium text-slate-600 dark:text-slate-400">{{ t('year') }} :</label>
                    <select
                        id="year"
                        v-model="selectedYear"
                        @change="changeYear(selectedYear)"
                        class="rounded-xl border-slate-300 py-1.5 pl-3 pr-8 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white"
                    >
                        <option v-for="year in availableYears" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>
                </div>
            </div>
        </template>

        <!-- Franchise Alert (TVA threshold warning) -->
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
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <!-- CA Annuel -->
            <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
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
            <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
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
            <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div :class="[
                                'flex h-12 w-12 items-center justify-center rounded-xl',
                                kpis?.unpaid_invoices?.overdue_count > 0 ? 'bg-accent-pink' : 'bg-accent-blue'
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
            <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
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
            <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
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
                        <div class="mt-2 h-3 w-full rounded-full bg-slate-200 dark:bg-slate-700">
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
                    </div>
                </div>
            </div>

            <!-- Simplified Accounting Threshold -->
            <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                <div class="p-6">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                        {{ t('simplified_accounting_threshold') }}
                    </h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ t('article_57_threshold') }} {{ formatCurrency(kpis?.simplified_accounting_threshold) }}
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
                        <div class="mt-2 h-3 w-full rounded-full bg-slate-200 dark:bg-slate-700">
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

        <!-- Third Row: Revenue Chart & VAT Summary -->
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Revenue Chart -->
            <div class="lg:col-span-2 overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                <div class="p-6">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                        {{ t('monthly_revenue') }}
                    </h3>
                    <div class="mt-4">
                        <div class="flex h-48 items-end justify-between space-x-2">
                            <div
                                v-for="month in revenueChart"
                                :key="month.month"
                                class="flex flex-1 flex-col items-center"
                            >
                                <div
                                    class="w-full rounded-t-lg bg-primary-500 hover:bg-primary-600 transition-colors cursor-pointer"
                                    :style="{ height: `${(month.revenue / maxRevenue) * 100}%`, minHeight: month.revenue > 0 ? '4px' : '0' }"
                                    :title="`${month.label}: ${formatCurrency(month.revenue)}`"
                                ></div>
                                <span class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ month.label }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VAT Summary -->
            <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
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
                        <div class="border-t border-slate-200 dark:border-slate-700 pt-4 flex justify-between">
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
                </div>
            </div>
        </div>

        <!-- Fourth Row: Lists -->
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Unpaid Invoices -->
            <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
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
            <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
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
        <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
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
                                <tr v-for="invoice in recentInvoices" :key="invoice.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
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
                    class="group flex items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 p-6 text-center hover:border-primary-500 hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-all dark:border-slate-600 dark:hover:border-primary-400 dark:hover:bg-primary-900/20"
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
                    class="group flex items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 p-6 text-center hover:border-accent-blue hover:bg-sky-50 focus:outline-none focus:ring-2 focus:ring-accent-blue focus:ring-offset-2 transition-all dark:border-slate-600 dark:hover:border-accent-blue dark:hover:bg-sky-900/20"
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
                    class="group flex items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 p-6 text-center hover:border-accent-yellow hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-accent-yellow focus:ring-offset-2 transition-all dark:border-slate-600 dark:hover:border-accent-yellow dark:hover:bg-amber-900/20"
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
                    class="group flex items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 p-6 text-center hover:border-accent-pink hover:bg-pink-50 focus:outline-none focus:ring-2 focus:ring-accent-pink focus:ring-offset-2 transition-all dark:border-slate-600 dark:hover:border-accent-pink dark:hover:bg-pink-900/20"
                >
                    <div>
                        <div class="mx-auto h-12 w-12 rounded-xl bg-pink-100 flex items-center justify-center group-hover:bg-accent-pink transition-colors dark:bg-pink-900/30">
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

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    kpis: Object,
    revenueChart: Array,
    unpaidInvoices: Array,
    unbilledTimeByClient: Array,
    recentInvoices: Array,
    availableYears: Array,
    selectedYear: Number,
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
    if (percentage >= 100) return 'bg-red-600';
    if (percentage >= 80) return 'bg-yellow-500';
    return 'bg-indigo-600';
};

// Alert level color
const getAlertColor = (level) => {
    if (level === 'critical') return 'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-200';
    if (level === 'warning') return 'bg-yellow-50 border-yellow-200 text-yellow-800 dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-200';
    return 'bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-200';
};

const getAlertIcon = (level) => {
    if (level === 'critical') return 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z';
    if (level === 'warning') return 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z';
    return 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
};

// Status badge for invoices
const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        finalized: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        sent: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        paid: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    };
    return badges[status] || badges.draft;
};

const getStatusLabel = (status) => {
    const labels = {
        draft: 'Brouillon',
        finalized: 'Finalisée',
        sent: 'Envoyée',
        paid: 'Payée',
    };
    return labels[status] || status;
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Dashboard
                </h1>
                <!-- Year Selector -->
                <div class="flex items-center space-x-2">
                    <label for="year" class="text-sm font-medium text-gray-700 dark:text-gray-300">Année :</label>
                    <select
                        id="year"
                        v-model="selectedYear"
                        @change="changeYear(selectedYear)"
                        class="rounded-md border-gray-300 py-1.5 pl-3 pr-8 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                        <option v-for="year in availableYears" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>
                </div>
            </div>
        </template>

        <!-- Alerts -->
        <div v-if="kpis?.alerts?.length > 0" class="mb-6 space-y-3">
            <div
                v-for="(alert, index) in kpis.alerts"
                :key="index"
                :class="['rounded-lg border p-4', getAlertColor(alert.level)]"
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
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900">
                                <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                                    CA Annuel (HT)
                                </dt>
                                <dd class="flex items-baseline">
                                    <span class="text-2xl font-semibold text-gray-900 dark:text-white">
                                        {{ formatCurrency(kpis?.annual_revenue) }}
                                    </span>
                                    <span
                                        v-if="kpis?.annual_revenue_change !== null"
                                        :class="[
                                            'ml-2 text-sm font-medium',
                                            kpis?.annual_revenue_change >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
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
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900">
                                <svg class="h-6 w-6 text-green-600 dark:text-green-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Bénéfice Net
                                </dt>
                                <dd class="flex items-baseline">
                                    <span :class="[
                                        'text-2xl font-semibold',
                                        kpis?.net_profit >= 0 ? 'text-gray-900 dark:text-white' : 'text-red-600 dark:text-red-400'
                                    ]">
                                        {{ formatCurrency(kpis?.net_profit) }}
                                    </span>
                                    <span
                                        v-if="kpis?.net_profit_change !== null"
                                        :class="[
                                            'ml-2 text-sm font-medium',
                                            kpis?.net_profit_change >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
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
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div :class="[
                                'flex h-12 w-12 items-center justify-center rounded-lg',
                                kpis?.unpaid_invoices?.overdue_count > 0 ? 'bg-red-100 dark:bg-red-900' : 'bg-yellow-100 dark:bg-yellow-900'
                            ]">
                                <svg :class="[
                                    'h-6 w-6',
                                    kpis?.unpaid_invoices?.overdue_count > 0 ? 'text-red-600 dark:text-red-300' : 'text-yellow-600 dark:text-yellow-300'
                                ]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Factures impayées
                                </dt>
                                <dd>
                                    <span class="text-2xl font-semibold text-gray-900 dark:text-white">
                                        {{ formatCurrency(kpis?.unpaid_invoices?.total_amount) }}
                                    </span>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ kpis?.unpaid_invoices?.count || 0 }} facture(s)
                                        <span v-if="kpis?.unpaid_invoices?.overdue_count > 0" class="text-red-600 dark:text-red-400">
                                            ({{ kpis?.unpaid_invoices?.overdue_count }} en retard)
                                        </span>
                                    </p>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Temps non facturé -->
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900">
                                <svg class="h-6 w-6 text-purple-600 dark:text-purple-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Temps non facturé
                                </dt>
                                <dd>
                                    <span class="text-2xl font-semibold text-gray-900 dark:text-white">
                                        {{ kpis?.unbilled_time?.formatted || '0:00' }}
                                    </span>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ kpis?.unbilled_time?.hours?.toFixed(1) || 0 }} heures
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
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="p-6">
                    <h3 class="text-base font-medium text-gray-900 dark:text-white">
                        Seuil de franchise TVA
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Article 57 - Seuil de {{ formatCurrency(kpis?.vat_franchise_threshold) }}
                    </p>
                    <div class="mt-4">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">
                                {{ formatCurrency(kpis?.vat_franchise_progress?.current) }} / {{ formatCurrency(kpis?.vat_franchise_threshold) }}
                            </span>
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ kpis?.vat_franchise_progress?.percentage?.toFixed(1) || 0 }}%
                            </span>
                        </div>
                        <div class="mt-2 h-3 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                            <div
                                :class="['h-3 rounded-full transition-all', getProgressBarColor(kpis?.vat_franchise_progress?.percentage)]"
                                :style="{ width: `${Math.min(100, kpis?.vat_franchise_progress?.percentage || 0)}%` }"
                            ></div>
                        </div>
                        <p v-if="kpis?.vat_franchise_progress?.remaining > 0" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Reste {{ formatCurrency(kpis?.vat_franchise_progress?.remaining) }} avant le seuil
                        </p>
                        <p v-else class="mt-2 text-sm font-medium text-red-600 dark:text-red-400">
                            Seuil dépassé - Assujettissement TVA obligatoire
                        </p>
                    </div>
                </div>
            </div>

            <!-- Simplified Accounting Threshold -->
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="p-6">
                    <h3 class="text-base font-medium text-gray-900 dark:text-white">
                        Seuil de comptabilité simplifiée
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Seuil de {{ formatCurrency(kpis?.simplified_accounting_threshold) }}
                    </p>
                    <div class="mt-4">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">
                                {{ formatCurrency(kpis?.simplified_accounting_progress?.current) }} / {{ formatCurrency(kpis?.simplified_accounting_threshold) }}
                            </span>
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ kpis?.simplified_accounting_progress?.percentage?.toFixed(1) || 0 }}%
                            </span>
                        </div>
                        <div class="mt-2 h-3 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                            <div
                                :class="['h-3 rounded-full transition-all', getProgressBarColor(kpis?.simplified_accounting_progress?.percentage)]"
                                :style="{ width: `${Math.min(100, kpis?.simplified_accounting_progress?.percentage || 0)}%` }"
                            ></div>
                        </div>
                        <p v-if="kpis?.simplified_accounting_progress?.remaining > 0" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Reste {{ formatCurrency(kpis?.simplified_accounting_progress?.remaining) }} avant le seuil
                        </p>
                        <p v-else class="mt-2 text-sm font-medium text-red-600 dark:text-red-400">
                            Seuil dépassé - Comptabilité complète obligatoire
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Third Row: Revenue Chart & VAT Summary -->
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Revenue Chart -->
            <div class="lg:col-span-2 overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="p-6">
                    <h3 class="text-base font-medium text-gray-900 dark:text-white">
                        Chiffre d'affaires mensuel
                    </h3>
                    <div class="mt-4">
                        <div class="flex h-48 items-end justify-between space-x-2">
                            <div
                                v-for="month in revenueChart"
                                :key="month.month"
                                class="flex flex-1 flex-col items-center"
                            >
                                <div
                                    class="w-full rounded-t bg-indigo-500 hover:bg-indigo-600 transition-colors"
                                    :style="{ height: `${(month.revenue / maxRevenue) * 100}%`, minHeight: month.revenue > 0 ? '4px' : '0' }"
                                    :title="`${month.label}: ${formatCurrency(month.revenue)}`"
                                ></div>
                                <span class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ month.label }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VAT Summary -->
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="p-6">
                    <h3 class="text-base font-medium text-gray-900 dark:text-white">
                        Résumé TVA {{ selectedYear }}
                    </h3>
                    <dl class="mt-4 space-y-4">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">TVA collectée</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ formatCurrency(kpis?.vat_summary?.collected) }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">TVA déductible</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ formatCurrency(kpis?.vat_summary?.deductible) }}
                            </dd>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4 flex justify-between">
                            <dt class="text-sm font-medium text-gray-900 dark:text-white">Solde</dt>
                            <dd :class="[
                                'text-sm font-semibold',
                                kpis?.vat_summary?.balance >= 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'
                            ]">
                                {{ formatCurrency(kpis?.vat_summary?.balance) }}
                            </dd>
                        </div>
                        <div v-if="kpis?.vat_summary?.to_pay > 0" class="text-sm text-gray-500 dark:text-gray-400">
                            TVA à reverser : <span class="font-medium text-red-600 dark:text-red-400">{{ formatCurrency(kpis?.vat_summary?.to_pay) }}</span>
                        </div>
                        <div v-else-if="kpis?.vat_summary?.credit > 0" class="text-sm text-gray-500 dark:text-gray-400">
                            Crédit de TVA : <span class="font-medium text-green-600 dark:text-green-400">{{ formatCurrency(kpis?.vat_summary?.credit) }}</span>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Fourth Row: Lists -->
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Unpaid Invoices -->
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white">
                            Factures impayées
                        </h3>
                        <Link :href="route('invoices.index')" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                            Voir tout
                        </Link>
                    </div>
                    <div class="mt-4">
                        <div v-if="unpaidInvoices?.length === 0" class="text-center py-4 text-sm text-gray-500 dark:text-gray-400">
                            Aucune facture impayée
                        </div>
                        <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                            <li v-for="invoice in unpaidInvoices" :key="invoice.id" class="py-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <Link :href="route('invoices.show', invoice.id)" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                            {{ invoice.number }}
                                        </Link>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ invoice.client_name }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ formatCurrency(invoice.total_ttc) }}</p>
                                        <p :class="[
                                            'text-xs',
                                            invoice.is_overdue ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-500 dark:text-gray-400'
                                        ]">
                                            <span v-if="invoice.is_overdue">
                                                En retard ({{ invoice.days_overdue }} j)
                                            </span>
                                            <span v-else-if="invoice.due_at">
                                                Échéance : {{ invoice.due_at }}
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
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-medium text-gray-900 dark:text-white">
                            Temps non facturé par client
                        </h3>
                        <Link :href="route('time-entries.summary')" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                            Voir tout
                        </Link>
                    </div>
                    <div class="mt-4">
                        <div v-if="unbilledTimeByClient?.length === 0" class="text-center py-4 text-sm text-gray-500 dark:text-gray-400">
                            Aucun temps non facturé
                        </div>
                        <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                            <li v-for="entry in unbilledTimeByClient" :key="entry.client_id" class="py-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ entry.client_name }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ entry.formatted }} ({{ entry.hours }}h)</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            ~{{ formatCurrency(entry.estimated_amount) }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
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
        <div class="mt-6 overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-medium text-gray-900 dark:text-white">
                        Factures récentes
                    </h3>
                    <Link :href="route('invoices.index')" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                        Voir tout
                    </Link>
                </div>
                <div class="mt-4">
                    <div v-if="recentInvoices?.length === 0" class="text-center py-4 text-sm text-gray-500 dark:text-gray-400">
                        Aucune facture
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Numéro</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Client</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Date</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Statut</th>
                                    <th class="px-3 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Montant</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="invoice in recentInvoices" :key="invoice.id">
                                    <td class="whitespace-nowrap px-3 py-3">
                                        <Link :href="route('invoices.show', invoice.id)" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                            {{ invoice.number }}
                                            <span v-if="invoice.is_credit_note" class="ml-1 text-xs text-gray-500">(Avoir)</span>
                                        </Link>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-900 dark:text-white">
                                        {{ invoice.client_name }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-500 dark:text-gray-400">
                                        {{ invoice.issued_at }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3">
                                        <span :class="['inline-flex rounded-full px-2 text-xs font-semibold leading-5', getStatusBadge(invoice.status)]">
                                            {{ getStatusLabel(invoice.status) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-right text-sm font-medium text-gray-900 dark:text-white">
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
            <h3 class="text-base font-medium text-gray-900 dark:text-white mb-4">
                Actions rapides
            </h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Link
                    :href="route('invoices.create')"
                    class="flex items-center justify-center rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-indigo-500 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:hover:border-indigo-400 dark:hover:bg-gray-700/50"
                >
                    <div>
                        <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        <span class="mt-2 block text-sm font-medium text-gray-900 dark:text-gray-300">
                            Nouvelle facture
                        </span>
                    </div>
                </Link>

                <Link
                    :href="route('clients.create')"
                    class="flex items-center justify-center rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-indigo-500 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:hover:border-indigo-400 dark:hover:bg-gray-700/50"
                >
                    <div>
                        <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        <span class="mt-2 block text-sm font-medium text-gray-900 dark:text-gray-300">
                            Nouveau client
                        </span>
                    </div>
                </Link>

                <Link
                    :href="route('time-entries.index')"
                    class="flex items-center justify-center rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-indigo-500 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:hover:border-indigo-400 dark:hover:bg-gray-700/50"
                >
                    <div>
                        <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="mt-2 block text-sm font-medium text-gray-900 dark:text-gray-300">
                            Suivi du temps
                        </span>
                    </div>
                </Link>

                <Link
                    :href="route('expenses.create')"
                    class="flex items-center justify-center rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-indigo-500 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:hover:border-indigo-400 dark:hover:bg-gray-700/50"
                >
                    <div>
                        <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <span class="mt-2 block text-sm font-medium text-gray-900 dark:text-gray-300">
                            Nouvelle dépense
                        </span>
                    </div>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

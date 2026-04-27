<script setup>
import { computed } from 'vue';
import { useTour } from '@/Composables/useTour';
import { useTranslations } from '@/Composables/useTranslations';

const { startTour } = useTour();
const { t } = useTranslations();

// Map route name patterns to tour keys
const routeTourMap = {
    'dashboard': 'dashboard',
    'clients.index': 'clients',
    'clients.create': 'clientCreate',
    'invoices.index': 'invoices',
    'invoices.create': 'invoiceCreate',
    'quotes.index': 'quotes',
    'quotes.create': 'quoteCreate',
    'expenses.index': 'expenses',
    'expenses.create': 'expenseCreate',
    'projects.index': 'projects',
    'projects.create': 'projectCreate',
    'time-tracking.index': 'timeTracking',
    'revenue-book.index': 'revenueBook',
    'faia.index': 'faiaExport',
    'accounting-export.index': 'accountingExport',
    'fiscal-summary.index': 'fiscalSummary',
    'hr.dashboard': 'hrDashboard',
    'hr.employees.index': 'hrEmployees',
    'hr.employees.create': 'hrEmployeeCreate',
    'hr.leaves.index': 'hrLeaves',
    'hr.leaves.calendar': 'hrLeavesCalendar',
    'hr.expenses.index': 'hrExpenses',
    'business.edit': 'settings',
};

const currentTourKey = computed(() => {
    try {
        const currentRoute = route().current();
        if (!currentRoute) return null;
        return routeTourMap[currentRoute] || null;
    } catch {
        return null;
    }
});

const restart = () => {
    if (currentTourKey.value) {
        startTour(currentTourKey.value, true);
    }
};
</script>

<template>
    <button
        v-if="currentTourKey"
        @click="restart"
        :title="t('restart_tour') || 'Relancer la visite guidée'"
        class="fixed bottom-6 right-6 z-40 group flex items-center justify-center w-12 h-12 rounded-full bg-primary-500 hover:bg-primary-600 text-white shadow-lg hover:shadow-xl transition-all hover:scale-110 focus:outline-none focus:ring-2 focus:ring-primary-400 focus:ring-offset-2"
        aria-label="Relancer la visite guidée"
    >
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
        </svg>

        <!-- Tooltip on hover -->
        <span class="absolute right-full mr-3 px-3 py-1.5 rounded-lg bg-slate-900 text-white text-xs font-medium whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none shadow-lg">
            {{ t('restart_tour') || 'Relancer la visite' }}
        </span>
    </button>
</template>

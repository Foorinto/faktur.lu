<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import HRNav from '@/Components/HRNav.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    widgets: { type: Object, required: true },
});
</script>

<template>
    <Head :title="t('hr.dashboard')" />
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-slate-800 dark:text-white">{{ t('hr.dashboard') }}</h2>
                <Link :href="route('hr.employees.create')" class="rounded-xl bg-primary-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-600">
                    + {{ t('hr.new_employee') }}
                </Link>
            </div>
        </template>

        <HRNav class="mb-6" />
        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- KPI Cards -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6 mb-8">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.total_employees') }}</p>
                        <p class="mt-1 text-3xl font-bold text-slate-900 dark:text-white">{{ widgets.total_employees }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.entries_this_month') }}</p>
                        <p class="mt-1 text-3xl font-bold text-emerald-600">{{ widgets.entries_this_month }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.exits_this_month') }}</p>
                        <p class="mt-1 text-3xl font-bold text-rose-600">{{ widgets.exits_this_month }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.absences_today') }}</p>
                        <p class="mt-1 text-3xl font-bold text-amber-600">{{ widgets.absences_today }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.pending_requests') }}</p>
                        <p class="mt-1 text-3xl font-bold text-blue-600">{{ widgets.pending_requests }}</p>
                    </div>
                    <Link :href="route('hr.expenses.index', { status: 'pending' })" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700">
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.pending_expenses') }}</p>
                        <p class="mt-1 text-3xl font-bold text-orange-600">{{ widgets.pending_expenses }}</p>
                        <p v-if="widgets.pending_expenses_total > 0" class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400">
                            {{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(widgets.pending_expenses_total) }}
                        </p>
                    </Link>
                </div>

                <!-- Quick Links -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-5 mb-8">
                    <Link :href="route('hr.employees.index')" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-100 text-primary-600 dark:bg-primary-900/30 dark:text-primary-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        </div>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.employees') }}</span>
                    </Link>
                    <Link :href="route('hr.leaves.index')" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.leave_requests') }}</span>
                    </Link>
                    <Link :href="route('hr.leaves.calendar')" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.calendar') }}</span>
                    </Link>
                    <Link :href="route('hr.departments.index')" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-100 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        </div>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.departments') }}</span>
                    </Link>
                    <Link :href="route('hr.leave-types.index')" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                        </div>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.leave_types') }}</span>
                    </Link>
                </div>

                <!-- Widget Lists -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Upcoming Birthdays -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">{{ t('hr.upcoming_birthdays') }}</h3>
                        <div v-if="widgets.upcoming_birthdays.length > 0" class="space-y-3">
                            <Link v-for="item in widgets.upcoming_birthdays" :key="item.id" :href="route('hr.employees.show', item.id)" class="flex items-center justify-between text-sm hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg p-2 -mx-2">
                                <span class="text-slate-700 dark:text-slate-300">{{ item.full_name }}</span>
                                <span class="text-slate-500 dark:text-slate-400">{{ item.birth_date }}</span>
                            </Link>
                        </div>
                        <p v-else class="text-sm text-slate-500 dark:text-slate-400">{{ t('hr.no_data') }}</p>
                    </div>

                    <!-- Trial Periods Ending -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">{{ t('hr.trial_periods_ending') }}</h3>
                        <div v-if="widgets.trial_periods_ending.length > 0" class="space-y-3">
                            <Link v-for="item in widgets.trial_periods_ending" :key="item.id" :href="route('hr.employees.show', item.id)" class="flex items-center justify-between text-sm hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg p-2 -mx-2">
                                <span class="text-slate-700 dark:text-slate-300">{{ item.full_name }}</span>
                                <span class="text-amber-600 dark:text-amber-400">{{ item.days_left }} {{ t('hr.days_left') }}</span>
                            </Link>
                        </div>
                        <p v-else class="text-sm text-slate-500 dark:text-slate-400">{{ t('hr.no_data') }}</p>
                    </div>

                    <!-- Contracts to Renew -->
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">{{ t('hr.contracts_to_renew') }}</h3>
                        <div v-if="widgets.contracts_to_renew.length > 0" class="space-y-3">
                            <Link v-for="item in widgets.contracts_to_renew" :key="item.id" :href="route('hr.employees.show', item.id)" class="flex items-center justify-between text-sm hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg p-2 -mx-2">
                                <span class="text-slate-700 dark:text-slate-300">{{ item.full_name }}</span>
                                <span class="text-rose-600 dark:text-rose-400">{{ item.contract_end }}</span>
                            </Link>
                        </div>
                        <p v-else class="text-sm text-slate-500 dark:text-slate-400">{{ t('hr.no_data') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import HRNav from '@/Components/HRNav.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    widgets: { type: Object, required: true },
    settingsCounts: { type: Object, required: true },
});

const settingsSections = [
    {
        title: () => t('hr.departments'),
        description: () => t('hr.settings_departments_desc'),
        route: 'hr.departments.index',
        countKey: 'departments',
        icon: 'building',
    },
    {
        title: () => t('hr.leave_types'),
        description: () => t('hr.settings_leave_types_desc'),
        route: 'hr.leave-types.index',
        countKey: 'leaveTypes',
        icon: 'calendar',
    },
    {
        title: () => t('hr.expense_categories'),
        description: () => t('hr.settings_expense_categories_desc'),
        route: 'hr.expense-categories.index',
        countKey: 'expenseCategories',
        icon: 'receipt',
    },
    {
        title: () => t('hr.onboarding_templates'),
        description: () => t('hr.settings_onboarding_desc'),
        route: 'hr.onboarding-templates.index',
        countKey: 'onboardingTemplates',
        icon: 'clipboard',
    },
];
</script>

<template>
    <Head :title="t('hr.dashboard')" />
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-slate-800 dark:text-white">{{ t('hr.dashboard') }}</h2>
                <Link :href="route('hr.employees.create')" class="rounded-xl bg-accent-rose px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-pink-500">
                    + {{ t('hr.new_employee') }}
                </Link>
            </div>
        </template>

        <HRNav class="mb-6" />
        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- KPI Cards -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6 mb-8">
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-surface-card">
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.total_employees') }}</p>
                        <p class="mt-1 text-3xl font-bold text-slate-900 dark:text-white">{{ widgets.total_employees }}</p>
                    </div>
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-surface-card">
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.entries_this_month') }}</p>
                        <p class="mt-1 text-3xl font-bold text-emerald-600">{{ widgets.entries_this_month }}</p>
                    </div>
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-surface-card">
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.exits_this_month') }}</p>
                        <p class="mt-1 text-3xl font-bold text-rose-600">{{ widgets.exits_this_month }}</p>
                    </div>
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-surface-card">
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.absences_today') }}</p>
                        <p class="mt-1 text-3xl font-bold text-amber-600">{{ widgets.absences_today }}</p>
                    </div>
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-surface-card">
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.pending_requests') }}</p>
                        <p class="mt-1 text-3xl font-bold text-blue-600">{{ widgets.pending_requests }}</p>
                    </div>
                    <Link :href="route('hr.expenses.index', { status: 'pending' })" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-surface-card dark:hover:bg-gray-800">
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.pending_expenses') }}</p>
                        <p class="mt-1 text-3xl font-bold text-orange-600">{{ widgets.pending_expenses }}</p>
                        <p v-if="widgets.pending_expenses_total > 0" class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400">
                            {{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(widgets.pending_expenses_total) }}
                        </p>
                    </Link>
                </div>

                <!-- Widget Lists -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Upcoming Birthdays -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-surface-card">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">{{ t('hr.upcoming_birthdays') }}</h3>
                        <div v-if="widgets.upcoming_birthdays.length > 0" class="space-y-3">
                            <Link v-for="item in widgets.upcoming_birthdays" :key="item.id" :href="route('hr.employees.show', item.id)" class="flex items-center justify-between text-sm hover:bg-gray-50 dark:hover:bg-gray-800 rounded-lg p-2 -mx-2">
                                <span class="text-slate-700 dark:text-slate-300">{{ item.full_name }}</span>
                                <span class="text-slate-500 dark:text-slate-400">{{ item.birth_date }}</span>
                            </Link>
                        </div>
                        <p v-else class="text-sm text-slate-500 dark:text-slate-400">{{ t('hr.no_data') }}</p>
                    </div>

                    <!-- Trial Periods Ending -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-surface-card">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">{{ t('hr.trial_periods_ending') }}</h3>
                        <div v-if="widgets.trial_periods_ending.length > 0" class="space-y-3">
                            <Link v-for="item in widgets.trial_periods_ending" :key="item.id" :href="route('hr.employees.show', item.id)" class="flex items-center justify-between text-sm hover:bg-gray-50 dark:hover:bg-gray-800 rounded-lg p-2 -mx-2">
                                <span class="text-slate-700 dark:text-slate-300">{{ item.full_name }}</span>
                                <span class="text-amber-600 dark:text-amber-400">{{ item.days_left }} {{ t('hr.days_left') }}</span>
                            </Link>
                        </div>
                        <p v-else class="text-sm text-slate-500 dark:text-slate-400">{{ t('hr.no_data') }}</p>
                    </div>

                    <!-- Contracts to Renew -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-surface-card">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">{{ t('hr.contracts_to_renew') }}</h3>
                        <div v-if="widgets.contracts_to_renew.length > 0" class="space-y-3">
                            <Link v-for="item in widgets.contracts_to_renew" :key="item.id" :href="route('hr.employees.show', item.id)" class="flex items-center justify-between text-sm hover:bg-gray-50 dark:hover:bg-gray-800 rounded-lg p-2 -mx-2">
                                <span class="text-slate-700 dark:text-slate-300">{{ item.full_name }}</span>
                                <span class="text-rose-600 dark:text-rose-400">{{ item.contract_end }}</span>
                            </Link>
                        </div>
                        <p v-else class="text-sm text-slate-500 dark:text-slate-400">{{ t('hr.no_data') }}</p>
                    </div>
                </div>

                <!-- Settings -->
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white mt-8 mb-4">{{ t('hr.hr_settings') }}</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Link
                        v-for="section in settingsSections"
                        :key="section.route"
                        :href="route(section.route)"
                        class="group overflow-hidden rounded-2xl bg-white border border-gray-200 shadow-sm p-5 transition-all hover:border-primary-300 hover:shadow-primary-100/50 dark:bg-surface-card dark:border-gray-700 dark:hover:border-primary-600"
                    >
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-400">
                                <svg v-if="section.icon === 'building'" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4 16.5v-13h-.25a.75.75 0 010-1.5h12.5a.75.75 0 010 1.5H16v13h.25a.75.75 0 010 1.5h-3.5a.75.75 0 01-.75-.75v-2.5a.75.75 0 00-.75-.75h-2.5a.75.75 0 00-.75.75v2.5a.75.75 0 01-.75.75h-3.5a.75.75 0 010-1.5H4zm3-11a.5.5 0 01.5-.5h1a.5.5 0 01.5.5v1a.5.5 0 01-.5.5h-1a.5.5 0 01-.5-.5v-1zm.5 3.5a.5.5 0 00-.5.5v1a.5.5 0 00.5.5h1a.5.5 0 00.5-.5v-1a.5.5 0 00-.5-.5h-1zm3.5-3.5a.5.5 0 01.5-.5h1a.5.5 0 01.5.5v1a.5.5 0 01-.5.5h-1a.5.5 0 01-.5-.5v-1zm.5 3.5a.5.5 0 00-.5.5v1a.5.5 0 00.5.5h1a.5.5 0 00.5-.5v-1a.5.5 0 00-.5-.5h-1z" clip-rule="evenodd" />
                                </svg>
                                <svg v-else-if="section.icon === 'calendar'" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" />
                                </svg>
                                <svg v-else-if="section.icon === 'receipt'" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 003 3.5v13.227a.75.75 0 001.138.641l1.112-.667a.25.25 0 01.259 0l1.984 1.19a.75.75 0 00.772 0l1.984-1.19a.25.25 0 01.258 0l1.984 1.19a.75.75 0 00.772 0l1.984-1.19a.25.25 0 01.258 0l1.112.667A.75.75 0 0017 16.727V3.5A1.5 1.5 0 0015.5 2h-11zM6 7a.75.75 0 000 1.5h8A.75.75 0 0014 7H6zm0 3.5a.75.75 0 000 1.5h5a.75.75 0 000-1.5H6z" clip-rule="evenodd" />
                                </svg>
                                <svg v-else-if="section.icon === 'clipboard'" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M15.988 3.012A2.25 2.25 0 0118 5.25v6.5A2.25 2.25 0 0115.75 14H13.5v-3.379a3 3 0 00-.879-2.121l-3.12-3.121a3 3 0 00-1.402-.791 2.252 2.252 0 011.913-1.576A2.25 2.25 0 0112.25 1h1.5a2.25 2.25 0 012.238 2.012zM11.5 3.25a.75.75 0 01.75-.75h1.5a.75.75 0 01.75.75v.25a.75.75 0 01-.75.75h-1.5a.75.75 0 01-.75-.75v-.25z" clip-rule="evenodd" />
                                    <path d="M3.5 6A1.5 1.5 0 002 7.5v9A1.5 1.5 0 003.5 18h7a1.5 1.5 0 001.5-1.5v-5.379a1.5 1.5 0 00-.44-1.06L8.44 6.939A1.5 1.5 0 007.378 6H3.5z" />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h4 class="text-sm font-semibold text-slate-900 group-hover:text-primary-600 dark:text-white dark:group-hover:text-primary-400">
                                    {{ section.title() }}
                                </h4>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                    {{ section.description() }}
                                </p>
                                <p class="mt-1 text-xs font-medium text-primary-600 dark:text-primary-400">
                                    {{ t('hr.configured_items', { count: settingsCounts[section.countKey] }) }}
                                </p>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

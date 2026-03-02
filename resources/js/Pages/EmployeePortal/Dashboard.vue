<script setup>
import EmployeePortalLayout from '@/Layouts/EmployeePortalLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    employee: { type: Object, required: true },
    pendingLeaves: { type: Number, default: 0 },
    pendingExpenses: { type: Number, default: 0 },
    recentDocuments: { type: Array, default: () => [] },
});

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('fr-FR');
};
</script>

<template>
    <Head :title="t('employee_portal.dashboard')" />

    <EmployeePortalLayout>
        <!-- Welcome -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                {{ t('employee_portal.welcome', { name: employee.first_name }) }}
            </h1>
            <p v-if="employee.department" class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ employee.job_title }} — {{ employee.department.name }}
            </p>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-8">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-surface-card">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('employee_portal.pending_leave_requests') }}</p>
                <p class="mt-1 text-3xl font-bold" :class="pendingLeaves > 0 ? 'text-amber-600' : 'text-slate-900 dark:text-white'">{{ pendingLeaves }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-surface-card">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('employee_portal.pending_expense_reports') }}</p>
                <p class="mt-1 text-3xl font-bold" :class="pendingExpenses > 0 ? 'text-amber-600' : 'text-slate-900 dark:text-white'">{{ pendingExpenses }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-surface-card">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('employee_portal.my_documents') }}</p>
                <p class="mt-1 text-3xl font-bold text-slate-900 dark:text-white">{{ recentDocuments.length }}</p>
            </div>
        </div>

        <!-- Leave Balances -->
        <div v-if="employee.leave_balances?.length" class="mb-8">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-white mb-4">{{ t('employee_portal.leave_balances') }}</h2>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="balance in employee.leave_balances"
                    :key="balance.id"
                    class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-surface-card"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ balance.leave_type?.name }}</span>
                        <span
                            class="inline-block h-3 w-3 rounded-full"
                            :style="{ backgroundColor: balance.leave_type?.color || '#6366f1' }"
                        ></span>
                    </div>
                    <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">
                        {{ Math.round((balance.initial_days - balance.used_days) * 10) / 10 }}
                        <span class="text-sm font-normal text-slate-500">/ {{ balance.initial_days }} {{ t('hr.days') }}</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Quick Actions + Recent Documents -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Quick Actions -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-surface-card">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white mb-4">{{ t('employee_portal.quick_actions') }}</h2>
                <div class="space-y-3">
                    <Link
                        :href="route('employee-portal.leaves.index')"
                        class="flex items-center justify-between rounded-xl p-3 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                    >
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('employee_portal.submit_leave') }}</span>
                        <svg class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                        </svg>
                    </Link>
                    <Link
                        :href="route('employee-portal.expenses.index')"
                        class="flex items-center justify-between rounded-xl p-3 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                    >
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('employee_portal.submit_expense') }}</span>
                        <svg class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                        </svg>
                    </Link>
                    <Link
                        :href="route('employee-portal.profile.edit')"
                        class="flex items-center justify-between rounded-xl p-3 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                    >
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('employee_portal.my_profile') }}</span>
                        <svg class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                        </svg>
                    </Link>
                </div>
            </div>

            <!-- Recent Documents -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-surface-card">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white mb-4">{{ t('employee_portal.recent_documents') }}</h2>
                <div v-if="recentDocuments.length > 0" class="space-y-3">
                    <div v-for="doc in recentDocuments" :key="doc.id" class="flex items-center justify-between text-sm">
                        <div>
                            <p class="font-medium text-slate-700 dark:text-slate-300">{{ doc.name }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ formatDate(doc.created_at) }}</p>
                        </div>
                        <a
                            :href="`/storage/${doc.file_path}`"
                            target="_blank"
                            class="text-primary-600 hover:text-primary-700 dark:text-primary-400"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10.75 2.75a.75.75 0 00-1.5 0v8.614L6.295 8.235a.75.75 0 10-1.09 1.03l4.25 4.5a.75.75 0 001.09 0l4.25-4.5a.75.75 0 00-1.09-1.03l-2.955 3.129V2.75z" />
                                <path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z" />
                            </svg>
                        </a>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-500 dark:text-slate-400">{{ t('hr.no_data') }}</p>
            </div>
        </div>
    </EmployeePortalLayout>
</template>

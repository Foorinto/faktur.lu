<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const currentRoute = computed(() => usePage().url);

const links = [
    { label: () => t('hr.dashboard'), href: 'hr.dashboard', match: '/hr' },
    { label: () => t('hr.employees'), href: 'hr.employees.index', match: '/hr/employees' },
    { label: () => t('hr.trombinoscope'), href: 'hr.trombinoscope', match: '/hr/trombinoscope' },
    { label: () => t('hr.departments'), href: 'hr.departments.index', match: '/hr/departments' },
    { label: () => t('hr.leave_types'), href: 'hr.leave-types.index', match: '/hr/leave-types' },
    { label: () => t('hr.leave_requests'), href: 'hr.leaves.index', match: '/hr/leaves' },
    { label: () => t('hr.expense_categories'), href: 'hr.expense-categories.index', match: '/hr/expense-categories' },
    { label: () => t('hr.expenses'), href: 'hr.expenses.index', match: '/hr/expenses' },
    { label: () => t('hr.onboarding_templates'), href: 'hr.onboarding-templates.index', match: '/hr/onboarding-templates' },
];

const isActive = (match) => {
    const url = currentRoute.value;
    if (match === '/hr') return url === '/hr' || url === '/hr/';
    return url.startsWith(match);
};
</script>

<template>
    <nav class="flex items-center gap-1 overflow-x-auto pb-px">
        <Link
            v-for="link in links"
            :key="link.href"
            :href="route(link.href)"
            :class="[
                'whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-medium transition-colors',
                isActive(link.match)
                    ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400'
                    : 'text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-300'
            ]"
        >
            {{ link.label() }}
        </Link>
    </nav>
</template>

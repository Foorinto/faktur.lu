<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const currentRoute = computed(() => usePage().url);

const links = [
    { label: () => t('hr.dashboard'), href: 'hr.dashboard', match: ['/hr', '/hr/departments', '/hr/leave-types', '/hr/expense-categories', '/hr/onboarding-templates'] },
    { label: () => t('hr.employees'), href: 'hr.employees.index', match: ['/hr/employees'] },
    { label: () => t('hr.trombinoscope'), href: 'hr.trombinoscope', match: ['/hr/trombinoscope'] },
    { label: () => t('hr.leave_requests'), href: 'hr.leaves.index', match: ['/hr/leaves'] },
    { label: () => t('hr_calendar_title'), href: 'hr.shared-calendar.index', match: ['/hr/shared-calendar', '/hr/events', '/hr/rooms'] },
    { label: () => t('hr.expenses'), href: 'hr.expenses.index', match: ['/hr/expenses'] },
];

const isActive = (match) => {
    const url = currentRoute.value;
    return match.some(m => m === '/hr' ? (url === '/hr' || url === '/hr/') : url.startsWith(m));
};
</script>

<template>
    <nav class="flex items-center gap-1 overflow-x-auto pb-px">
        <Link
            v-for="link in links"
            :key="link.href"
            :href="route(link.href)"
            :class="[
                'whitespace-nowrap rounded-lg px-2 sm:px-3 py-1.5 text-xs sm:text-sm font-medium transition-colors',
                isActive(link.match)
                    ? 'bg-accent-rose text-white dark:bg-accent-rose dark:text-white'
                    : 'text-slate-500 hover:bg-gray-50 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-gray-800 dark:hover:text-slate-300'
            ]"
        >
            {{ link.label() }}
        </Link>
    </nav>
</template>

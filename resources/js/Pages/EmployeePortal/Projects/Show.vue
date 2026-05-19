<script setup>
import EmployeePortalLayout from '@/Layouts/EmployeePortalLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

defineProps({
    project: { type: Object, required: true },
    tasks: { type: Array, required: true },
    timeEntries: { type: Array, required: true },
});

const formatDuration = (seconds) => {
    if (!seconds) return '0h';
    const h = Math.floor(seconds / 3600);
    const m = Math.round((seconds % 3600) / 60);
    return m > 0 ? `${h}h${String(m).padStart(2, '0')}` : `${h}h`;
};

const formatDate = (iso) => {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const statusColor = (status) => ({
    backlog: 'bg-slate-100 text-slate-700',
    next: 'bg-blue-100 text-blue-700',
    in_progress: 'bg-emerald-100 text-emerald-700',
    waiting_for: 'bg-amber-100 text-amber-700',
    done: 'bg-violet-100 text-violet-700',
}[status] || 'bg-slate-100 text-slate-700');
</script>

<template>
    <Head :title="project.title" />

    <EmployeePortalLayout>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
            <Link
                :href="route('employee-portal.projects.index')"
                class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                {{ t('employee_portal.my_projects_title') }}
            </Link>

            <!-- Project header -->
            <div class="rounded-2xl bg-white dark:bg-surface-card border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center gap-3 mb-3">
                    <span v-if="project.color" class="w-4 h-4 rounded-full" :style="{ backgroundColor: project.color }" />
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ project.title }}</h1>
                </div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="statusColor(project.status)">
                    {{ t('project_status_' + project.status) }}
                </span>
                <p v-if="project.description" class="mt-3 text-slate-600 dark:text-slate-400 whitespace-pre-wrap">{{ project.description }}</p>
                <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                    <div v-if="project.client">
                        <p class="text-slate-500 dark:text-slate-400">{{ t('client') }}</p>
                        <p class="font-medium text-slate-900 dark:text-white">{{ project.client.name }}</p>
                    </div>
                    <div v-if="project.due_date">
                        <p class="text-slate-500 dark:text-slate-400">{{ t('due_date') }}</p>
                        <p class="font-medium text-slate-900 dark:text-white">{{ formatDate(project.due_date) }}</p>
                    </div>
                </div>
            </div>

            <!-- My tasks -->
            <div class="rounded-2xl bg-white dark:bg-surface-card border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                    {{ t('employee_portal.my_tasks') }} ({{ tasks.length }})
                </h2>
                <ul v-if="tasks.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700">
                    <li v-for="task in tasks" :key="task.id" class="py-3">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ task.title }}</p>
                                <p v-if="task.description" class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ task.description }}</p>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="text-xs" :class="statusColor(task.status)">{{ t('task_status_' + task.status) }}</span>
                                <span v-if="task.due_date" class="text-xs text-slate-500">{{ formatDate(task.due_date) }}</span>
                            </div>
                        </div>
                    </li>
                </ul>
                <p v-else class="text-sm text-slate-500 py-2">{{ t('employee_portal.no_tasks_assigned') }}</p>
            </div>

            <!-- Recent time entries -->
            <div class="rounded-2xl bg-white dark:bg-surface-card border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                    {{ t('employee_portal.recent_time_entries') }}
                </h2>
                <ul v-if="timeEntries.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700">
                    <li v-for="entry in timeEntries" :key="entry.id" class="py-2 flex items-center justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-slate-900 dark:text-white truncate">{{ entry.description || t('without_description') }}</p>
                            <p class="text-xs text-slate-500">{{ formatDate(entry.started_at) }}</p>
                        </div>
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">{{ formatDuration(entry.duration_seconds) }}</p>
                    </li>
                </ul>
                <p v-else class="text-sm text-slate-500 py-2">{{ t('employee_portal.no_time_entries') }}</p>
            </div>
        </div>
    </EmployeePortalLayout>
</template>

<script setup>
import EmployeePortalLayout from '@/Layouts/EmployeePortalLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

defineProps({
    projects: { type: Array, required: true },
});

const statusColor = (status) => ({
    backlog: 'bg-slate-100 text-slate-700 dark:bg-gray-700 dark:text-slate-300',
    next: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    in_progress: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    waiting_for: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    done: 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300',
}[status] || 'bg-slate-100 text-slate-700');
</script>

<template>
    <Head :title="t('employee_portal.my_projects_title')" />

    <EmployeePortalLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                {{ t('employee_portal.my_projects_title') }}
            </h1>

            <div v-if="projects.length === 0" class="rounded-2xl bg-white dark:bg-surface-card border border-gray-200 dark:border-gray-700 p-12 text-center">
                <p class="text-slate-500 dark:text-slate-400">{{ t('employee_portal.no_projects') }}</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <Link
                    v-for="project in projects"
                    :key="project.id"
                    :href="route('employee-portal.projects.show', project.id)"
                    class="group block rounded-2xl bg-white dark:bg-surface-card border border-gray-200 dark:border-gray-700 p-5 hover:border-primary-300 hover:shadow-sm transition-all"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span
                                    v-if="project.color"
                                    class="w-3 h-3 rounded-full flex-shrink-0"
                                    :style="{ backgroundColor: project.color }"
                                />
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white truncate group-hover:text-primary-500">
                                    {{ project.title }}
                                </h3>
                            </div>
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                :class="statusColor(project.status)"
                            >
                                {{ t('project_status.' + project.status) }}
                            </span>
                            <p v-if="project.client" class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                                {{ t('client') }}: {{ project.client.name }}
                            </p>
                        </div>
                        <svg class="h-5 w-5 text-slate-300 group-hover:text-primary-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                    <div class="mt-4 flex items-center gap-4 text-sm text-slate-500 dark:text-slate-400">
                        <span class="inline-flex items-center gap-1">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                            {{ project.my_tasks_count }} {{ t('tasks') }}
                        </span>
                        <span class="inline-flex items-center gap-1">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            {{ project.my_hours_this_month }}h
                        </span>
                    </div>
                </Link>
            </div>
        </div>
    </EmployeePortalLayout>
</template>

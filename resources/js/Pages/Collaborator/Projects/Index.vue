<script setup>
import CollaboratorLayout from '@/Layouts/CollaboratorLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';
import debounce from 'lodash/debounce';

const { t } = useTranslations();

const props = defineProps({
    projects: Array,
    filters: Object,
    statuses: Object,
});

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');

const applyFilters = () => {
    router.get(route('collaborator.projects.index'), {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const debouncedSearch = debounce(applyFilters, 300);
watch(search, debouncedSearch);
watch(statusFilter, applyFilters);

const getStatusColor = (status) => {
    const colors = {
        backlog: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
        todo: 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400',
        in_progress: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        review: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        done: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    };
    return colors[status] || colors.backlog;
};
</script>

<template>
    <Head :title="t('my_projects')" />

    <CollaboratorLayout>
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ t('my_projects') }}</h1>
            <Link
                :href="route('collaborator.projects.create')"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-500"
            >
                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                {{ t('new_project') || 'Nouveau projet' }}
            </Link>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input
                    v-model="search"
                    type="text"
                    :placeholder="t('search') + '...'"
                    class="w-full rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white text-sm"
                />
            </div>
            <select
                v-model="statusFilter"
                class="rounded-xl border-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white text-sm"
            >
                <option value="">{{ t('all_statuses') || 'Tous les statuts' }}</option>
                <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
            </select>
        </div>

        <!-- Projects Grid -->
        <div v-if="projects.length === 0" class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-200 dark:border-slate-700 dark:shadow-slate-900/50 p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
            </svg>
            <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">{{ t('no_projects') || 'Aucun projet.' }}</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <Link
                v-for="project in projects"
                :key="project.id"
                :href="route('collaborator.projects.show', project.id)"
                class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-200 dark:border-slate-700 dark:shadow-slate-900/50 p-5 hover:shadow-lg transition-shadow block"
            >
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center space-x-2">
                        <div
                            class="w-3 h-3 rounded-full flex-shrink-0"
                            :style="{ backgroundColor: project.color || '#6366f1' }"
                        ></div>
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white truncate">{{ project.title }}</h3>
                    </div>
                    <span :class="getStatusColor(project.status)" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0">
                        {{ project.status_label }}
                    </span>
                </div>

                <p v-if="project.description" class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 mb-3">
                    {{ project.description }}
                </p>

                <div class="space-y-2">
                    <!-- Progress -->
                    <div v-if="project.tasks_count > 0" class="flex items-center justify-between text-xs">
                        <span class="text-slate-500 dark:text-slate-400">
                            {{ project.completed_tasks_count }}/{{ project.tasks_count }} {{ t('tasks') || 'tâches' }}
                        </span>
                        <span class="text-slate-500 dark:text-slate-400">
                            {{ Math.round((project.completed_tasks_count / project.tasks_count) * 100) }}%
                        </span>
                    </div>
                    <div v-if="project.tasks_count > 0" class="w-full h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div
                            class="h-full bg-primary-500 rounded-full transition-all"
                            :style="{ width: Math.round((project.completed_tasks_count / project.tasks_count) * 100) + '%' }"
                        ></div>
                    </div>

                    <!-- Meta -->
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span v-if="project.client_name">{{ project.client_name }}</span>
                        <span v-if="project.total_time_spent">{{ project.total_time_spent }}</span>
                        <span v-if="project.due_date">{{ project.due_date }}</span>
                    </div>
                </div>
            </Link>
        </div>
    </CollaboratorLayout>
</template>

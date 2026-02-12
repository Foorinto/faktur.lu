<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';
import debounce from 'lodash/debounce';
import KanbanBoard from '@/Components/KanbanBoard.vue';
import TimelineView from '@/Components/TimelineView.vue';

const { t } = useTranslations();

const props = defineProps({
    projects: {
        type: [Object, Array],
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
    view: {
        type: String,
        default: 'list',
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    clients: {
        type: Array,
        required: true,
    },
    statuses: {
        type: Object,
        required: true,
    },
    colors: {
        type: Object,
        required: true,
    },
});

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const clientFilter = ref(props.filters.client_id || '');
const showArchived = ref(props.filters.show_archived || false);

// Get default view from localStorage or props
const getDefaultView = () => {
    const savedView = localStorage.getItem('projectsDefaultView');
    return savedView || props.view;
};
const currentView = ref(getDefaultView());
const savedDefaultView = ref(localStorage.getItem('projectsDefaultView') || '');

// Save default view preference
const saveAsDefaultView = () => {
    localStorage.setItem('projectsDefaultView', currentView.value);
    savedDefaultView.value = currentView.value;
};

const isDefaultView = computed(() => {
    return savedDefaultView.value === currentView.value;
});

// On mount, redirect to saved default view if different
onMounted(() => {
    const savedView = localStorage.getItem('projectsDefaultView');
    if (savedView && savedView !== props.view && !props.filters.search && !props.filters.status && !props.filters.client_id) {
        switchView(savedView);
    }
});

const updateFilters = debounce(() => {
    router.get(route('projects.index'), {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        client_id: clientFilter.value || undefined,
        show_archived: showArchived.value || undefined,
        view: currentView.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, statusFilter, clientFilter, showArchived], updateFilters);

const switchView = (view) => {
    currentView.value = view;
    router.get(route('projects.index'), {
        ...props.filters,
        view: view,
    }, {
        preserveState: true,
        replace: true,
    });
};

const getStatusBadgeClass = (status) => {
    const classes = {
        backlog: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
        next: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        in_progress: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        waiting_for: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
        done: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    };
    return classes[status] || classes.backlog;
};

const formatDuration = (seconds) => {
    if (!seconds) return '0h';
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    if (minutes === 0) return `${hours}h`;
    return `${hours}h${minutes.toString().padStart(2, '0')}`;
};

const projectsData = computed(() => {
    return Array.isArray(props.projects) ? props.projects : props.projects.data;
});

const archiveProject = (project, action) => {
    router.post(route('projects.archive', project.id), { action }, {
        preserveScroll: true,
    });
};

const deleteProject = (project) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce projet ? Cette action est irréversible.')) {
        router.delete(route('projects.destroy', project.id));
    }
};

const updateProjectStatus = (project, newStatus) => {
    router.post(route('projects.status', project.id), { status: newStatus }, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="t('projects')" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ t('projects') }}
                </h1>
                <Link
                    :href="route('projects.create')"
                    class="inline-flex items-center rounded-xl bg-primary-500 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
                >
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                    </svg>
                    {{ t('new_project') }}
                </Link>
            </div>
        </template>

        <!-- Stats cards -->
        <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4">
            <div class="rounded-2xl bg-white p-4 border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.total }}</div>
                <div class="text-sm text-slate-500 dark:text-slate-400">{{ t('projects') }}</div>
            </div>
            <div class="rounded-2xl bg-white p-4 border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ stats.in_progress }}</div>
                <div class="text-sm text-slate-500 dark:text-slate-400">{{ t('project_status.in_progress') }}</div>
            </div>
            <div class="rounded-2xl bg-white p-4 border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                <div class="text-2xl font-bold text-pink-600 dark:text-pink-400">{{ stats.overdue }}</div>
                <div class="text-sm text-slate-500 dark:text-slate-400">{{ t('overdue') }}</div>
            </div>
            <div class="rounded-2xl bg-white p-4 border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ stats.done_this_week }}</div>
                <div class="text-sm text-slate-500 dark:text-slate-400">{{ t('project_status.done') }} (semaine)</div>
            </div>
        </div>

        <!-- View tabs & Filters -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <!-- View tabs -->
            <div class="flex items-center gap-2">
            <div class="inline-flex rounded-xl bg-slate-100 p-1 dark:bg-slate-800">
                <button
                    @click="switchView('list')"
                    :class="[
                        'rounded-lg px-4 py-2 text-sm font-medium transition-colors',
                        currentView === 'list'
                            ? 'bg-white text-slate-900 shadow dark:bg-slate-700 dark:text-white'
                            : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
                    ]"
                >
                    {{ t('project_view_list') }}
                </button>
                <button
                    @click="switchView('kanban')"
                    :class="[
                        'rounded-lg px-4 py-2 text-sm font-medium transition-colors',
                        currentView === 'kanban'
                            ? 'bg-white text-slate-900 shadow dark:bg-slate-700 dark:text-white'
                            : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
                    ]"
                >
                    {{ t('project_view_kanban') }}
                </button>
                <button
                    @click="switchView('timeline')"
                    :class="[
                        'rounded-lg px-4 py-2 text-sm font-medium transition-colors',
                        currentView === 'timeline'
                            ? 'bg-white text-slate-900 shadow dark:bg-slate-700 dark:text-white'
                            : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
                    ]"
                >
                    {{ t('project_view_timeline') }}
                </button>
            </div>
            <!-- Set as default view button -->
            <button
                @click="saveAsDefaultView"
                :class="[
                    'rounded-lg p-2 transition-colors',
                    isDefaultView
                        ? 'text-primary-500'
                        : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'
                ]"
                :title="isDefaultView ? 'Vue par défaut' : 'Définir comme vue par défaut'"
            >
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd" />
                </svg>
            </button>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input
                        v-model="search"
                        type="text"
                        :placeholder="t('search') + '...'"
                        class="block w-48 rounded-xl border-0 py-2 pl-9 pr-3 text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-primary-500 dark:bg-slate-800 dark:text-white dark:ring-slate-700 sm:text-sm"
                    />
                </div>

                <select
                    v-model="statusFilter"
                    class="rounded-xl border-0 py-2 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-800 dark:text-white dark:ring-slate-700 sm:text-sm"
                >
                    <option value="">{{ t('all_statuses') }}</option>
                    <option v-for="(label, value) in statuses" :key="value" :value="value">
                        {{ label }}
                    </option>
                </select>

                <select
                    v-model="clientFilter"
                    class="rounded-xl border-0 py-2 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-800 dark:text-white dark:ring-slate-700 sm:text-sm"
                >
                    <option value="">{{ t('all_clients') }}</option>
                    <option v-for="client in clients" :key="client.id" :value="client.id">
                        {{ client.name }}
                    </option>
                </select>

                <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
                    <input
                        type="checkbox"
                        v-model="showArchived"
                        class="rounded border-slate-300 text-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-800"
                    />
                    {{ t('show_archived') }}
                </label>
            </div>
        </div>

        <!-- List View -->
        <div v-if="currentView === 'list'" class="overflow-hidden rounded-2xl bg-white border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead>
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 dark:text-white sm:pl-6">
                            {{ t('project') }}
                        </th>
                        <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white md:table-cell">
                            Client
                        </th>
                        <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white lg:table-cell">
                            {{ t('due_date') }}
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white">
                            {{ t('completion') }}
                        </th>
                        <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white lg:table-cell">
                            {{ t('time_spent') }}
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white">
                            Statut
                        </th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <tr v-if="projectsData.length === 0">
                        <td colspan="7" class="py-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                            </svg>
                            <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">{{ t('no_projects') }}</p>
                            <Link
                                :href="route('projects.create')"
                                class="mt-4 inline-flex items-center text-primary-600 hover:text-primary-500 dark:text-primary-400"
                            >
                                {{ t('create_first_project') }}
                            </Link>
                        </td>
                    </tr>
                    <tr
                        v-for="project in projectsData"
                        :key="project.id"
                        :class="[
                            'hover:bg-slate-50 dark:hover:bg-slate-700/50',
                            project.is_archived ? 'opacity-60' : ''
                        ]"
                    >
                        <td class="py-4 pl-4 pr-3 sm:pl-6">
                            <div class="flex items-center gap-3">
                                <div
                                    v-if="project.color"
                                    class="h-3 w-3 rounded-full"
                                    :style="{ backgroundColor: project.color }"
                                />
                                <div>
                                    <Link
                                        :href="route('projects.show', project.id)"
                                        class="font-medium text-slate-900 hover:text-primary-600 dark:text-white dark:hover:text-primary-400"
                                    >
                                        {{ project.title }}
                                    </Link>
                                    <span v-if="project.is_archived" class="ml-2 text-xs text-slate-400">({{ t('archived') }})</span>
                                </div>
                            </div>
                        </td>
                        <td class="hidden whitespace-nowrap px-3 py-4 text-sm text-slate-500 dark:text-slate-400 md:table-cell">
                            {{ project.client?.name || '-' }}
                        </td>
                        <td class="hidden whitespace-nowrap px-3 py-4 text-sm lg:table-cell">
                            <span
                                v-if="project.due_date"
                                :class="[
                                    project.is_overdue ? 'text-pink-600 dark:text-pink-400' : 'text-slate-500 dark:text-slate-400'
                                ]"
                            >
                                {{ new Date(project.due_date).toLocaleDateString('fr-FR') }}
                            </span>
                            <span v-else class="text-slate-400">-</span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            <div class="flex items-center gap-2">
                                <div class="h-2 w-24 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-600">
                                    <div
                                        class="h-full rounded-full bg-primary-500"
                                        :style="{ width: `${project.completion_percentage || 0}%` }"
                                    />
                                </div>
                                <span class="text-slate-600 dark:text-slate-400">
                                    {{ project.completed_tasks_count || 0 }}/{{ project.tasks_count || 0 }}
                                </span>
                            </div>
                        </td>
                        <td class="hidden whitespace-nowrap px-3 py-4 text-sm text-slate-500 dark:text-slate-400 lg:table-cell">
                            {{ formatDuration(project.time_entries_sum_duration_seconds) }}
                            <span v-if="project.budget_hours" class="text-slate-400">
                                / {{ project.budget_hours }}h
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            <select
                                :value="project.status"
                                @change="updateProjectStatus(project, $event.target.value)"
                                :class="[getStatusBadgeClass(project.status), 'cursor-pointer rounded-xl border-0 py-0.5 pl-2.5 pr-7 text-xs font-medium focus:ring-2 focus:ring-primary-500']"
                            >
                                <option v-for="(label, value) in statuses" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                            <div class="flex items-center justify-end gap-2">
                                <Link
                                    :href="route('projects.show', project.id)"
                                    class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-300"
                                    title="Voir"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M10 12.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z" />
                                        <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 010-1.186A10.004 10.004 0 0110 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0110 17c-4.257 0-7.893-2.66-9.336-6.41zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                    </svg>
                                </Link>
                                <Link
                                    :href="route('projects.edit', project.id)"
                                    class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-300"
                                    title="Modifier"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" />
                                    </svg>
                                </Link>
                                <button
                                    v-if="!project.is_archived"
                                    @click="archiveProject(project, 'archive')"
                                    class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-300"
                                    title="Archiver"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M2 3a1 1 0 00-1 1v1a1 1 0 001 1h16a1 1 0 001-1V4a1 1 0 00-1-1H2z" />
                                        <path fill-rule="evenodd" d="M2 7.5h16l-.811 7.71a2 2 0 01-1.99 1.79H4.802a2 2 0 01-1.99-1.79L2 7.5zM7 11a1 1 0 011-1h4a1 1 0 110 2H8a1 1 0 01-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <button
                                    v-else
                                    @click="archiveProject(project, 'unarchive')"
                                    class="rounded-lg p-1.5 text-emerald-400 hover:bg-slate-100 hover:text-emerald-600 dark:hover:bg-slate-700"
                                    title="Désarchiver"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.606 12.97a.75.75 0 01-.134 1.051 2.494 2.494 0 00-.93 2.437 2.494 2.494 0 002.437-.93.75.75 0 111.186.918 3.995 3.995 0 01-4.482 1.332.75.75 0 01-.461-.461 3.994 3.994 0 011.332-4.482.75.75 0 011.052.134z" clip-rule="evenodd" />
                                        <path fill-rule="evenodd" d="M5.752 12A13.07 13.07 0 008 14.248v4.002c0 .414.336.75.75.75a5 5 0 004.797-6.414 12.984 12.984 0 005.45-10.848.75.75 0 00-.735-.735 12.984 12.984 0 00-10.849 5.45A5 5 0 001 11.25c.001.414.337.75.751.75h4.002zM13 9a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <button
                                    @click="deleteProject(project)"
                                    class="rounded-lg p-1.5 text-red-400 hover:bg-slate-100 hover:text-red-600 dark:hover:bg-slate-700"
                                    title="Supprimer"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.519.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="props.projects.links && props.projects.links.length > 3" class="border-t border-slate-200 px-4 py-3 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-slate-700 dark:text-slate-400">
                        {{ props.projects.from }} - {{ props.projects.to }} sur {{ props.projects.total }}
                    </div>
                    <nav class="isolate inline-flex -space-x-px rounded-xl shadow-sm">
                        <template v-for="(link, index) in props.projects.links" :key="index">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    link.active
                                        ? 'z-10 bg-primary-500 text-white'
                                        : 'bg-white text-slate-900 ring-1 ring-inset ring-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-600 dark:hover:bg-slate-700',
                                    'relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20',
                                    index === 0 ? 'rounded-l-xl' : '',
                                    index === props.projects.links.length - 1 ? 'rounded-r-xl' : '',
                                ]"
                                v-html="link.label"
                                preserve-scroll
                            />
                        </template>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Kanban View -->
        <KanbanBoard
            v-else-if="currentView === 'kanban'"
            :items="projectsData"
            :statuses="statuses"
            item-type="project"
            @reorder="(items) => router.post(route('projects.reorder'), { projects: items }, { preserveScroll: true })"
        />

        <!-- Timeline View -->
        <TimelineView
            v-else-if="currentView === 'timeline'"
            :projects="projectsData"
        />
    </AppLayout>
</template>

<script setup>
import CollaboratorLayout from '@/Layouts/CollaboratorLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    organization: Object,
    projects: Array,
    stats: Object,
    runningTimer: Object,
});

// Live timer
const elapsed = ref('00:00:00');
let timerInterval = null;

const updateElapsed = () => {
    if (!props.runningTimer) return;
    const start = new Date(props.runningTimer.started_at);
    const now = new Date();
    const diff = Math.floor((now - start) / 1000);
    const h = String(Math.floor(diff / 3600)).padStart(2, '0');
    const m = String(Math.floor((diff % 3600) / 60)).padStart(2, '0');
    const s = String(diff % 60).padStart(2, '0');
    elapsed.value = `${h}:${m}:${s}`;
};

onMounted(() => {
    if (props.runningTimer) {
        updateElapsed();
        timerInterval = setInterval(updateElapsed, 1000);
    }
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});

const stopTimer = () => {
    if (props.runningTimer) {
        router.post(route('collaborator.time.stop', props.runningTimer.id), {}, {
            preserveScroll: true,
        });
    }
};

const getStatusColor = (status) => {
    const colors = {
        backlog: 'bg-slate-100 text-slate-700 dark:bg-gray-800 dark:text-slate-300',
        todo: 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400',
        in_progress: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        review: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        done: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    };
    return colors[status] || colors.backlog;
};
</script>

<template>
    <Head :title="t('collaborator_dashboard')" />

    <CollaboratorLayout>
        <!-- Welcome -->
        <div class="mb-8 flex items-start justify-between gap-4 flex-wrap">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                    {{ t('collaborator_dashboard') }}
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    {{ organization.name }}
                </p>
            </div>
            <a
                :href="route('collaborator.upgrade.show')"
                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border border-primary-200 dark:border-primary-700 bg-primary-50 dark:bg-primary-900/20 text-sm text-primary-700 dark:text-primary-300 hover:bg-primary-100 dark:hover:bg-primary-900/40"
                :title="t('upgrade_intro')"
            >
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a1 1 0 011 1v2.586l1.707-1.707a1 1 0 111.414 1.414L12.414 7H15a1 1 0 110 2h-2.586l1.707 1.707a1 1 0 11-1.414 1.414L11 10.414V13a1 1 0 11-2 0v-2.586l-1.707 1.707a1 1 0 11-1.414-1.414L7.586 9H5a1 1 0 110-2h2.586L5.879 5.293a1 1 0 011.414-1.414L9 5.586V3a1 1 0 011-1z" /></svg>
                {{ t('upgrade_cta') }}
            </a>
        </div>

        <!-- Running Timer Banner -->
        <div v-if="runningTimer" class="mb-6 bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 rounded-2xl p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center space-x-3">
                    <span class="relative flex flex-shrink-0 h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-primary-500"></span>
                    </span>
                    <div>
                        <p class="text-sm font-medium text-primary-900 dark:text-primary-100">
                            {{ runningTimer.description || t('timer_running') }}
                        </p>
                        <p v-if="runningTimer.project_name" class="text-xs text-primary-600 dark:text-primary-400">
                            {{ runningTimer.project_name }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="text-lg font-mono font-bold text-primary-700 dark:text-primary-300">{{ elapsed }}</span>
                    <button
                        @click="stopTimer"
                        class="w-full sm:w-auto inline-flex items-center justify-center px-3 py-1.5 border border-transparent rounded-xl text-sm font-medium text-white bg-pink-600 hover:bg-pink-500"
                    >
                        <svg class="mr-1 h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <rect x="6" y="6" width="12" height="12" rx="1" />
                        </svg>
                        {{ t('stop') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-white dark:bg-surface-card rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-200 dark:border-gray-700 dark:shadow-gray-900/50 p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 bg-primary-50 dark:bg-primary-900/20 rounded-xl">
                        <svg class="h-6 w-6 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('active_projects') }}</p>
                        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.active_projects }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-surface-card rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-200 dark:border-gray-700 dark:shadow-gray-900/50 p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl">
                        <svg class="h-6 w-6 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('this_week') }}</p>
                        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.hours_this_week }}h</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-surface-card rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-200 dark:border-gray-700 dark:shadow-gray-900/50 p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl">
                        <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('today') }}</p>
                        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.hours_today }}h</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Projects -->
        <div class="bg-white dark:bg-surface-card rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-200 dark:border-gray-700 dark:shadow-gray-900/50 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-base font-medium text-slate-900 dark:text-white">{{ t('recent_projects') }}</h2>
                <Link
                    :href="route('collaborator.projects.index')"
                    class="text-sm text-primary-600 hover:text-primary-800 dark:text-primary-400"
                >
                    {{ t('view_all') }}
                </Link>
            </div>

            <div v-if="projects.length === 0" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
                {{ t('no_projects') }}
            </div>

            <ul v-else class="divide-y divide-slate-200 dark:divide-slate-700">
                <li v-for="project in projects" :key="project.id">
                    <Link
                        :href="route('collaborator.projects.show', project.id)"
                        class="block px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-3 h-3 rounded-full flex-shrink-0"
                                    :style="{ backgroundColor: project.color || '#6366f1' }"
                                ></div>
                                <div>
                                    <p class="text-sm font-medium text-slate-900 dark:text-white">{{ project.title }}</p>
                                    <div class="flex items-center space-x-2 mt-1">
                                        <span :class="getStatusColor(project.status)" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium">
                                            {{ project.status_label }}
                                        </span>
                                        <span v-if="project.due_date" class="text-xs text-slate-400">{{ project.due_date }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    {{ project.completed_tasks_count }}/{{ project.tasks_count }} {{ t('tasks') }}
                                </p>
                                <div v-if="project.tasks_count > 0" class="mt-1 w-20 h-1.5 bg-slate-200 dark:bg-gray-800 rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-primary-500 rounded-full"
                                        :style="{ width: Math.round((project.completed_tasks_count / project.tasks_count) * 100) + '%' }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </Link>
                </li>
            </ul>
        </div>
    </CollaboratorLayout>
</template>

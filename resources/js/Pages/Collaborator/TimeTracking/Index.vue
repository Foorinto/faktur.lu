<script setup>
import CollaboratorLayout from '@/Layouts/CollaboratorLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    timeEntries: Array,
    projects: Array,
    runningTimer: Object,
    totalHours: Number,
    period: String,
});

// Timer form
const timerForm = useForm({
    description: '',
    project_id: '',
    task_id: '',
});

// Manual entry form
const showManualEntry = ref(false);
const manualForm = useForm({
    description: '',
    project_id: '',
    task_id: '',
    started_at: '',
    stopped_at: '',
});

// Tasks filtered by selected project
const timerTasks = computed(() => {
    if (!timerForm.project_id) return [];
    const project = props.projects.find(p => p.id === Number(timerForm.project_id));
    return project?.tasks || [];
});

const manualTasks = computed(() => {
    if (!manualForm.project_id) return [];
    const project = props.projects.find(p => p.id === Number(manualForm.project_id));
    return project?.tasks || [];
});

// Live timer display
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

const startTimer = () => {
    timerForm.post(route('collaborator.time.start'), {
        preserveScroll: true,
        onSuccess: () => {
            timerForm.reset();
            // Start live timer
            timerInterval = setInterval(updateElapsed, 1000);
        },
    });
};

const stopTimer = () => {
    if (props.runningTimer) {
        router.post(route('collaborator.time.stop', props.runningTimer.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                if (timerInterval) clearInterval(timerInterval);
                elapsed.value = '00:00:00';
            },
        });
    }
};

const submitManualEntry = () => {
    manualForm.post(route('collaborator.time.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showManualEntry.value = false;
            manualForm.reset();
        },
    });
};

const deleteEntry = (entryId) => {
    if (confirm(t('confirm_delete') || 'Confirmer la suppression ?')) {
        router.delete(route('collaborator.time.destroy', entryId), {
            preserveScroll: true,
        });
    }
};

const changePeriod = (newPeriod) => {
    router.get(route('collaborator.time.index'), { period: newPeriod }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatDuration = (seconds) => {
    if (!seconds) return '0h00';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    return `${h}h${String(m).padStart(2, '0')}`;
};

const formatTime = (isoString) => {
    if (!isoString) return '';
    return new Date(isoString).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
};

const formatDate = (isoString) => {
    if (!isoString) return '';
    return new Date(isoString).toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' });
};
</script>

<template>
    <Head :title="t('my_time')" />

    <CollaboratorLayout>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ t('my_time') }}</h1>
        </div>

        <!-- Timer -->
        <div class="bg-white dark:bg-surface-card rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-200 dark:border-gray-700 dark:shadow-gray-900/50 p-6 mb-6">
            <div v-if="runningTimer" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center space-x-3">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-primary-500"></span>
                    </span>
                    <div>
                        <p class="text-sm font-medium text-slate-900 dark:text-white">
                            {{ runningTimer.description || t('no_description') || 'Sans description' }}
                        </p>
                        <p v-if="runningTimer.project_name" class="text-xs text-slate-500 dark:text-slate-400">
                            {{ runningTimer.project_name }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="text-2xl font-mono font-bold text-primary-700 dark:text-primary-300">{{ elapsed }}</span>
                    <button
                        @click="stopTimer"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-xl text-sm font-medium text-white bg-pink-600 hover:bg-pink-500"
                    >
                        <svg class="mr-1 h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <rect x="6" y="6" width="12" height="12" rx="1" />
                        </svg>
                        {{ t('stop') }}
                    </button>
                </div>
            </div>

            <div v-else>
                <form @submit.prevent="startTimer" class="flex flex-col sm:flex-row gap-3">
                    <input
                        v-model="timerForm.description"
                        type="text"
                        :placeholder="t('what_are_you_working_on') || 'Sur quoi travaillez-vous ?'"
                        class="flex-1 rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm"
                    />
                    <select
                        v-model="timerForm.project_id"
                        @change="timerForm.task_id = ''"
                        class="rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm"
                    >
                        <option value="">{{ t('no_project') || 'Aucun projet' }}</option>
                        <option v-for="project in projects" :key="project.id" :value="project.id">
                            {{ project.title }}
                        </option>
                    </select>
                    <select
                        v-if="timerTasks.length"
                        v-model="timerForm.task_id"
                        class="rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm"
                    >
                        <option value="">{{ t('task') || 'Tâche' }} ({{ t('optional') || 'optionnel' }})</option>
                        <option v-for="task in timerTasks" :key="task.id" :value="task.id">
                            {{ task.title }}
                        </option>
                    </select>
                    <button
                        type="submit"
                        :disabled="timerForm.processing"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-xl text-sm font-medium text-white bg-primary-600 hover:bg-primary-500 disabled:opacity-50"
                    >
                        <svg class="mr-1 h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z" />
                        </svg>
                        {{ t('start') }}
                    </button>
                </form>
            </div>
        </div>

        <!-- Period Tabs + Manual Entry -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
            <div class="flex space-x-1 bg-slate-100 dark:bg-surface-card rounded-xl p-1">
                <button
                    v-for="p in ['today', 'week', 'month']"
                    :key="p"
                    @click="changePeriod(p)"
                    class="px-4 py-1.5 rounded-lg text-sm font-medium transition-colors"
                    :class="period === p
                        ? 'bg-white dark:bg-gray-800 text-slate-900 dark:text-white shadow-sm'
                        : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'"
                >
                    {{ p === 'today' ? (t('today') || "Aujourd'hui") : p === 'week' ? (t('this_week') || 'Cette semaine') : (t('this_month') || 'Ce mois') }}
                </button>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                    {{ t('total') || 'Total' }}: <strong>{{ totalHours }}h</strong>
                </span>
                <button
                    @click="showManualEntry = !showManualEntry"
                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-700 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-gray-800"
                >
                    <svg class="mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    {{ t('manual_entry') || 'Saisie manuelle' }}
                </button>
            </div>
        </div>

        <!-- Manual Entry Form -->
        <div v-if="showManualEntry" class="bg-white dark:bg-surface-card rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-200 dark:border-gray-700 dark:shadow-gray-900/50 p-6 mb-6">
            <h3 class="text-sm font-medium text-slate-900 dark:text-white mb-4">{{ t('manual_entry') || 'Saisie manuelle' }}</h3>
            <form @submit.prevent="submitManualEntry" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('description') }}</label>
                        <input
                            v-model="manualForm.description"
                            type="text"
                            class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('project') }}</label>
                        <select
                            v-model="manualForm.project_id"
                            @change="manualForm.task_id = ''"
                            class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm"
                        >
                            <option value="">{{ t('no_project') || 'Aucun projet' }}</option>
                            <option v-for="project in projects" :key="project.id" :value="project.id">
                                {{ project.title }}
                            </option>
                        </select>
                    </div>
                    <div v-if="manualTasks.length">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('task') || 'Tâche' }}</label>
                        <select
                            v-model="manualForm.task_id"
                            class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm"
                        >
                            <option value="">{{ t('optional') || 'Optionnel' }}</option>
                            <option v-for="task in manualTasks" :key="task.id" :value="task.id">
                                {{ task.title }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('start') }} *</label>
                        <input
                            v-model="manualForm.started_at"
                            type="datetime-local"
                            required
                            class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('end') || 'Fin' }} *</label>
                        <input
                            v-model="manualForm.stopped_at"
                            type="datetime-local"
                            required
                            class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm"
                        />
                    </div>
                </div>
                <div v-if="manualForm.errors.started_at || manualForm.errors.stopped_at" class="text-sm text-pink-600">
                    {{ manualForm.errors.started_at || manualForm.errors.stopped_at }}
                </div>
                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" @click="showManualEntry = false" class="px-4 py-2 text-sm text-slate-700 dark:text-slate-300 w-full sm:w-auto justify-center inline-flex items-center">
                        {{ t('cancel') }}
                    </button>
                    <button
                        type="submit"
                        :disabled="manualForm.processing"
                        class="w-full sm:w-auto justify-center inline-flex items-center px-4 py-2 border border-transparent rounded-xl text-sm font-medium text-white bg-primary-600 hover:bg-primary-500 disabled:opacity-50"
                    >
                        {{ t('save') }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Time Entries List -->
        <div class="bg-white dark:bg-surface-card rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-200 dark:border-gray-700 dark:shadow-gray-900/50 overflow-hidden">
            <div v-if="timeEntries.length === 0" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                <svg class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm">{{ t('no_time_entries') || 'Aucune entrée de temps pour cette période.' }}</p>
            </div>

            <ul v-else class="divide-y divide-slate-200 dark:divide-slate-700">
                <li v-for="entry in timeEntries" :key="entry.id" class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-2">
                                <p class="text-sm font-medium text-slate-900 dark:text-white truncate">
                                    {{ entry.description || t('no_description') || 'Sans description' }}
                                </p>
                                <span v-if="entry.is_running" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400">
                                    {{ t('running') || 'En cours' }}
                                </span>
                            </div>
                            <div class="flex items-center space-x-3 mt-1 text-xs text-slate-500 dark:text-slate-400">
                                <span v-if="entry.project_name">{{ entry.project_name }}</span>
                                <span>{{ formatDate(entry.started_at) }}</span>
                                <span>{{ formatTime(entry.started_at) }} - {{ entry.stopped_at ? formatTime(entry.stopped_at) : '...' }}</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3 ml-4">
                            <span class="text-sm font-mono font-medium text-slate-700 dark:text-slate-300">
                                {{ entry.is_running ? elapsed : entry.duration_formatted }}
                            </span>
                            <button
                                v-if="!entry.is_running"
                                @click="deleteEntry(entry.id)"
                                class="text-slate-400 hover:text-pink-500 transition-colors"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </CollaboratorLayout>
</template>

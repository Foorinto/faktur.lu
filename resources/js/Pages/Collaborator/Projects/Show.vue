<script setup>
import CollaboratorLayout from '@/Layouts/CollaboratorLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    project: Object,
    timeEntries: Array,
    statuses: Object,
});

const showAddTask = ref(false);

const taskForm = useForm({
    title: '',
    description: '',
    priority: 'normal',
    due_date: '',
    parent_id: null,
});

const submitTask = () => {
    taskForm.post(route('collaborator.tasks.store', props.project.id), {
        preserveScroll: true,
        onSuccess: () => {
            showAddTask.value = false;
            taskForm.reset();
        },
    });
};

const toggleTask = (taskId) => {
    router.post(route('collaborator.tasks.toggle', taskId), {}, {
        preserveScroll: true,
    });
};

const deleteTask = (taskId) => {
    if (confirm(t('confirm_delete') || 'Confirmer la suppression ?')) {
        router.delete(route('collaborator.tasks.destroy', taskId), {
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

const getPriorityColor = (priority) => {
    const colors = {
        low: 'text-slate-400',
        normal: 'text-sky-500',
        high: 'text-pink-500',
    };
    return colors[priority] || colors.normal;
};
</script>

<template>
    <Head :title="project.title" />

    <CollaboratorLayout>
        <!-- Back + Title -->
        <div class="mb-6">
            <Link
                :href="route('collaborator.projects.index')"
                class="text-sm text-primary-600 hover:text-primary-800 dark:text-primary-400 flex items-center mb-3"
            >
                <svg class="mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                {{ t('back_to_projects') || 'Retour aux projets' }}
            </Link>

            <div class="flex items-start justify-between">
                <div class="flex items-center space-x-3">
                    <div
                        class="w-4 h-4 rounded-full flex-shrink-0"
                        :style="{ backgroundColor: project.color || '#6366f1' }"
                    ></div>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ project.title }}</h1>
                        <div class="flex flex-wrap items-center gap-2 mt-1">
                            <span :class="getStatusColor(project.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                {{ project.status_label }}
                            </span>
                            <span v-if="project.client_name" class="text-sm text-slate-500 dark:text-slate-400">{{ project.client_name }}</span>
                            <span v-if="project.due_date" class="text-sm text-slate-500 dark:text-slate-400">{{ project.due_date }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="project.description" class="mt-3 text-sm text-slate-600 dark:text-slate-400 prose prose-sm dark:prose-invert max-w-none" v-html="project.description"></div>
        </div>

        <!-- Progress Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-surface-card rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ project.completion_percentage }}%</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ t('completion') || 'Complétion' }}</p>
            </div>
            <div class="bg-white dark:bg-surface-card rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ project.tasks.length }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ t('tasks') || 'Tâches' }}</p>
            </div>
            <div class="bg-white dark:bg-surface-card rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ project.total_time_spent || '0h' }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ t('time_spent') || 'Temps passé' }}</p>
            </div>
            <div v-if="project.budget_hours" class="bg-white dark:bg-surface-card rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ project.budget_hours }}h</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ t('budget') || 'Budget' }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Tasks -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-surface-card rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-200 dark:border-gray-700 dark:shadow-gray-900/50 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-base font-medium text-slate-900 dark:text-white">{{ t('tasks') || 'Tâches' }}</h2>
                        <button
                            @click="showAddTask = !showAddTask"
                            class="w-full sm:w-auto justify-center inline-flex items-center px-3 py-1.5 border border-transparent rounded-xl text-sm font-medium text-white bg-primary-600 hover:bg-primary-500"
                        >
                            <svg class="mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            {{ t('add_task') || 'Ajouter' }}
                        </button>
                    </div>

                    <!-- Add task form -->
                    <div v-if="showAddTask" class="px-6 py-4 bg-slate-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                        <form @submit.prevent="submitTask" class="space-y-3">
                            <input
                                v-model="taskForm.title"
                                type="text"
                                :placeholder="t('task_title') || 'Titre de la tâche'"
                                required
                                class="block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm"
                            />
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <select
                                    v-model="taskForm.priority"
                                    class="w-full sm:w-auto rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm"
                                >
                                    <option value="low">{{ t('low') || 'Basse' }}</option>
                                    <option value="normal">{{ t('normal') || 'Normale' }}</option>
                                    <option value="high">{{ t('high') || 'Haute' }}</option>
                                </select>
                                <input
                                    v-model="taskForm.due_date"
                                    type="date"
                                    class="w-full sm:w-auto rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white text-sm"
                                />
                                <button
                                    type="submit"
                                    :disabled="taskForm.processing"
                                    class="w-full sm:w-auto justify-center inline-flex items-center px-3 py-2 border border-transparent rounded-xl text-sm font-medium text-white bg-primary-600 hover:bg-primary-500 disabled:opacity-50"
                                >
                                    {{ t('add') || 'Ajouter' }}
                                </button>
                            </div>
                            <p v-if="taskForm.errors.title" class="text-sm text-pink-600">{{ taskForm.errors.title }}</p>
                        </form>
                    </div>

                    <div v-if="project.tasks.length === 0" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
                        {{ t('no_tasks') || 'Aucune tâche.' }}
                    </div>

                    <ul v-else class="divide-y divide-slate-200 dark:divide-slate-700">
                        <li v-for="task in project.tasks" :key="task.id" class="px-6 py-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <button
                                        @click="toggleTask(task.id)"
                                        class="flex-shrink-0 w-5 h-5 rounded border-2 flex items-center justify-center transition-colors"
                                        :class="task.is_completed
                                            ? 'bg-primary-500 border-primary-500'
                                            : 'border-gray-300 dark:border-gray-700 hover:border-primary-400'"
                                    >
                                        <svg v-if="task.is_completed" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                    <div>
                                        <p class="text-sm" :class="task.is_completed ? 'line-through text-slate-400' : 'text-slate-900 dark:text-white'">
                                            {{ task.title }}
                                        </p>
                                        <div class="flex items-center space-x-2 mt-0.5">
                                            <span v-if="task.priority !== 'normal'" :class="getPriorityColor(task.priority)" class="text-xs font-medium">
                                                {{ task.priority }}
                                            </span>
                                            <span v-if="task.due_date" class="text-xs text-slate-400">{{ task.due_date }}</span>
                                        </div>
                                    </div>
                                </div>
                                <button
                                    @click="deleteTask(task.id)"
                                    class="text-slate-400 hover:text-pink-500 transition-colors"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Subtasks -->
                            <ul v-if="task.children && task.children.length > 0" class="ml-8 mt-2 space-y-1">
                                <li v-for="child in task.children" :key="child.id" class="flex items-center justify-between py-1">
                                    <div class="flex items-center space-x-2">
                                        <button
                                            @click="toggleTask(child.id)"
                                            class="flex-shrink-0 w-4 h-4 rounded border-2 flex items-center justify-center transition-colors"
                                            :class="child.is_completed
                                                ? 'bg-primary-500 border-primary-500'
                                                : 'border-gray-300 dark:border-gray-700 hover:border-primary-400'"
                                        >
                                            <svg v-if="child.is_completed" class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                        <span class="text-xs" :class="child.is_completed ? 'line-through text-slate-400' : 'text-slate-700 dark:text-slate-300'">
                                            {{ child.title }}
                                        </span>
                                    </div>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Time Entries -->
            <div>
                <div class="bg-white dark:bg-surface-card rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-200 dark:border-gray-700 dark:shadow-gray-900/50 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-base font-medium text-slate-900 dark:text-white">{{ t('my_time') }}</h2>
                    </div>

                    <div v-if="timeEntries.length === 0" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400 text-sm">
                        {{ t('no_time_entries') || 'Aucune entrée de temps.' }}
                    </div>

                    <ul v-else class="divide-y divide-slate-200 dark:divide-slate-700">
                        <li v-for="entry in timeEntries" :key="entry.id" class="px-6 py-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-slate-900 dark:text-white">{{ entry.description || '-' }}</p>
                                    <p class="text-xs text-slate-400">
                                        {{ new Date(entry.started_at).toLocaleDateString('fr-FR') }}
                                    </p>
                                </div>
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                    {{ entry.is_running ? '...' : entry.duration_formatted }}
                                </span>
                            </div>
                        </li>
                    </ul>

                    <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700">
                        <Link
                            :href="route('collaborator.time.index')"
                            class="text-sm text-primary-600 hover:text-primary-800 dark:text-primary-400"
                        >
                            {{ t('view_all_time') || 'Voir tout le temps' }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </CollaboratorLayout>
</template>

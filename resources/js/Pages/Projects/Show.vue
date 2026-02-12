<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';
import draggable from 'vuedraggable';
import RichTextDisplay from '@/Components/RichTextDisplay.vue';

const { t } = useTranslations();

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    taskView: {
        type: String,
        default: 'list',
    },
    taskSort: {
        type: String,
        default: 'manual',
    },
    statuses: {
        type: Object,
        required: true,
    },
    taskStatuses: {
        type: Object,
        required: true,
    },
    taskPriorities: {
        type: Object,
        required: true,
    },
});

const currentTaskView = ref(props.taskView);
const currentSort = ref(props.taskSort);
const showAddTask = ref(false);
const editingTask = ref(null);
const addingSubtaskTo = ref(null); // ID of task we're adding a subtask to
const expandedTasks = ref(new Set()); // IDs of tasks with expanded subtasks

// Watch for prop changes (when view changes from URL)
watch(() => props.taskView, (newVal) => {
    currentTaskView.value = newVal;
});

// Local copy of tasks for drag and drop
const localTasks = ref([...props.project.tasks]);
watch(() => props.project.tasks, (newTasks) => {
    localTasks.value = [...newTasks];
}, { deep: true });

// Sorted tasks computed - sorts client-side for instant response
const sortedTasks = computed(() => {
    const tasks = [...localTasks.value];

    switch (currentSort.value) {
        case 'priority': {
            const priorityOrder = { high: 1, normal: 2, low: 3 };
            return tasks.sort((a, b) => {
                const diff = (priorityOrder[a.priority] || 2) - (priorityOrder[b.priority] || 2);
                if (diff !== 0) return diff;
                return (a.sort_order || 0) - (b.sort_order || 0);
            });
        }
        case 'due_date':
            return tasks.sort((a, b) => {
                // Tasks without due_date go to the end
                if (!a.due_date && !b.due_date) return (a.sort_order || 0) - (b.sort_order || 0);
                if (!a.due_date) return 1;
                if (!b.due_date) return -1;
                const dateDiff = new Date(a.due_date) - new Date(b.due_date);
                if (dateDiff !== 0) return dateDiff;
                return (a.sort_order || 0) - (b.sort_order || 0);
            });
        case 'title':
            return tasks.sort((a, b) => {
                const titleDiff = (a.title || '').localeCompare(b.title || '', 'fr');
                if (titleDiff !== 0) return titleDiff;
                return (a.sort_order || 0) - (b.sort_order || 0);
            });
        default: // 'manual'
            return tasks.sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
    }
});

// Also sort children of each task
const getSortedChildren = (task) => {
    if (!task.children || task.children.length === 0) return [];
    const children = [...task.children];

    switch (currentSort.value) {
        case 'priority': {
            const priorityOrder = { high: 1, normal: 2, low: 3 };
            return children.sort((a, b) => {
                const diff = (priorityOrder[a.priority] || 2) - (priorityOrder[b.priority] || 2);
                if (diff !== 0) return diff;
                return (a.sort_order || 0) - (b.sort_order || 0);
            });
        }
        case 'due_date':
            return children.sort((a, b) => {
                if (!a.due_date && !b.due_date) return (a.sort_order || 0) - (b.sort_order || 0);
                if (!a.due_date) return 1;
                if (!b.due_date) return -1;
                const dateDiff = new Date(a.due_date) - new Date(b.due_date);
                if (dateDiff !== 0) return dateDiff;
                return (a.sort_order || 0) - (b.sort_order || 0);
            });
        case 'title':
            return children.sort((a, b) => {
                const titleDiff = (a.title || '').localeCompare(b.title || '', 'fr');
                if (titleDiff !== 0) return titleDiff;
                return (a.sort_order || 0) - (b.sort_order || 0);
            });
        default:
            return children.sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
    }
};

// Computed with getter/setter for draggable (needed because draggable mutates the array)
const displayTasks = computed({
    get() {
        return sortedTasks.value;
    },
    set(newValue) {
        localTasks.value = newValue;
    }
});

const taskForm = useForm({
    title: '',
    description: '',
    status: 'backlog',
    priority: 'normal',
    due_date: '',
    estimated_hours: '',
});

const editForm = useForm({
    title: '',
    description: '',
    status: '',
    priority: '',
    due_date: '',
    estimated_hours: '',
});

const subtaskForm = useForm({
    title: '',
    priority: 'normal',
    due_date: '',
});

// Helper to format date for input[type="date"]
const formatDateForInput = (date) => {
    if (!date) return '';
    // If already in YYYY-MM-DD format, return as-is
    if (typeof date === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(date)) {
        return date;
    }
    // If ISO string like "2026-02-12T00:00:00.000000Z", extract just the date part
    if (typeof date === 'string' && date.includes('T')) {
        return date.split('T')[0];
    }
    // If it's a date string without time, return as-is
    if (typeof date === 'string') {
        return date.substring(0, 10);
    }
    return '';
};

// Helper to format date for display (DD/MM/YYYY) without timezone issues
const formatDateDisplay = (date) => {
    if (!date) return '';
    // Extract YYYY-MM-DD part
    const dateStr = formatDateForInput(date);
    if (!dateStr) return '';
    // Convert YYYY-MM-DD to DD/MM/YYYY
    const [year, month, day] = dateStr.split('-');
    return `${day}/${month}/${year}`;
};

const switchTaskView = (view) => {
    router.get(
        route('projects.show', props.project.id),
        { task_view: view, sort: currentSort.value },
        { preserveScroll: true }
    );
};

const switchSort = (sort) => {
    // Sort client-side - no server call needed
    currentSort.value = sort;
};

// Toggle task expansion (show/hide subtasks)
const toggleTaskExpansion = (taskId) => {
    if (expandedTasks.value.has(taskId)) {
        expandedTasks.value.delete(taskId);
    } else {
        expandedTasks.value.add(taskId);
    }
};

const isTaskExpanded = (taskId) => expandedTasks.value.has(taskId);

// Subtask methods
const startAddSubtask = (taskId) => {
    addingSubtaskTo.value = taskId;
    subtaskForm.reset();
    // Auto-expand the task when adding a subtask
    expandedTasks.value.add(taskId);
};

const cancelAddSubtask = () => {
    addingSubtaskTo.value = null;
    subtaskForm.reset();
};

const submitSubtask = (parentTask) => {
    subtaskForm.post(route('tasks.subtasks.store', parentTask.id), {
        preserveScroll: true,
        onSuccess: () => {
            subtaskForm.reset();
            addingSubtaskTo.value = null;
        },
    });
};

const submitTask = () => {
    taskForm.post(route('tasks.store', props.project.id), {
        preserveScroll: true,
        onSuccess: () => {
            taskForm.reset();
            showAddTask.value = false;
        },
    });
};

const toggleTask = (task) => {
    router.post(route('tasks.toggle', task.id), {}, { preserveScroll: true });
};

const startEditTask = (task) => {
    editingTask.value = task.id;
    editForm.title = task.title;
    editForm.description = task.description || '';
    editForm.status = task.status;
    editForm.priority = task.priority;
    editForm.due_date = formatDateForInput(task.due_date);
    editForm.estimated_hours = task.estimated_hours || '';
};

const saveEditTask = (task) => {
    editForm.put(route('tasks.update', task.id), {
        preserveScroll: true,
        onSuccess: () => {
            editingTask.value = null;
        },
    });
};

const cancelEditTask = () => {
    editingTask.value = null;
};

const deleteTask = (task) => {
    const hasSubtasks = task.children && task.children.length > 0;
    const message = hasSubtasks
        ? `Êtes-vous sûr de vouloir supprimer cette tâche et ses ${task.children.length} sous-tâche(s) ?`
        : 'Êtes-vous sûr de vouloir supprimer cette tâche ?';

    if (confirm(message)) {
        router.delete(route('tasks.destroy', task.id), { preserveScroll: true });
    }
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

const getPriorityBadgeClass = (priority) => {
    const classes = {
        low: 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400',
        normal: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        high: 'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400',
    };
    return classes[priority] || classes.normal;
};

const formatDuration = (seconds) => {
    if (!seconds) return '0h';
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    if (minutes === 0) return `${hours}h`;
    return `${hours}h${minutes.toString().padStart(2, '0')}`;
};

const completionPercentage = computed(() => {
    if (!props.project.tasks_count) return 0;
    return Math.round((props.project.completed_tasks_count / props.project.tasks_count) * 100);
});

// For Kanban, create reactive copy grouped by status
const kanbanTasks = ref({});

// Sort function for kanban tasks
const sortKanbanTasks = (tasks) => {
    const sorted = [...tasks];
    switch (currentSort.value) {
        case 'priority': {
            const priorityOrder = { high: 1, normal: 2, low: 3 };
            return sorted.sort((a, b) => {
                const diff = (priorityOrder[a.priority] || 2) - (priorityOrder[b.priority] || 2);
                if (diff !== 0) return diff;
                return (a.sort_order || 0) - (b.sort_order || 0);
            });
        }
        case 'due_date':
            return sorted.sort((a, b) => {
                if (!a.due_date && !b.due_date) return (a.sort_order || 0) - (b.sort_order || 0);
                if (!a.due_date) return 1;
                if (!b.due_date) return -1;
                const dateDiff = new Date(a.due_date) - new Date(b.due_date);
                if (dateDiff !== 0) return dateDiff;
                return (a.sort_order || 0) - (b.sort_order || 0);
            });
        case 'title':
            return sorted.sort((a, b) => {
                const titleDiff = (a.title || '').localeCompare(b.title || '', 'fr');
                if (titleDiff !== 0) return titleDiff;
                return (a.sort_order || 0) - (b.sort_order || 0);
            });
        default:
            return sorted.sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
    }
};

const initKanbanTasks = () => {
    const grouped = {};
    Object.keys(props.taskStatuses).forEach(status => {
        const filtered = props.project.tasks.filter(t => t.status === status);
        grouped[status] = sortKanbanTasks(filtered);
    });
    kanbanTasks.value = grouped;
};
watch(() => props.project.tasks, initKanbanTasks, { immediate: true, deep: true });
// Re-sort when sort mode changes
watch(() => currentSort.value, initKanbanTasks);

// Sort options for display
const sortOptions = [
    { value: 'manual', label: 'Manuel' },
    { value: 'priority', label: 'Priorité' },
    { value: 'due_date', label: 'Échéance' },
    { value: 'title', label: 'Nom' },
];

// List view drag and drop
const onListDragEnd = () => {
    const tasks = [];
    let order = 0;

    // Build flat list with parent/child relationships preserved
    localTasks.value.forEach(task => {
        tasks.push({
            id: task.id,
            sort_order: order++,
            parent_id: null,
        });
        // Add children if expanded
        if (task.children) {
            task.children.forEach(child => {
                tasks.push({
                    id: child.id,
                    sort_order: order++,
                    parent_id: task.id,
                });
            });
        }
    });

    router.post(route('tasks.reorder-list', props.project.id), { tasks }, { preserveScroll: true });
};

// Kanban drag and drop
const onKanbanDragEnd = () => {
    const tasks = [];
    let order = 0;
    Object.keys(kanbanTasks.value).forEach(status => {
        kanbanTasks.value[status].forEach(task => {
            tasks.push({
                id: task.id,
                sort_order: order++,
                status: status,
            });
        });
    });
    router.post(route('tasks.reorder', props.project.id), { tasks }, { preserveScroll: true });
};
</script>

<template>
    <Head :title="project.title" />

    <AppLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link
                    :href="route('projects.index')"
                    class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-300"
                >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                    </svg>
                </Link>
                <div class="flex items-center gap-3">
                    <div
                        v-if="project.color"
                        class="h-4 w-4 rounded-full"
                        :style="{ backgroundColor: project.color }"
                    />
                    <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                        {{ project.title }}
                    </h1>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Main content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Project header card -->
                <div class="rounded-2xl bg-white p-6 border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                    <div class="flex items-start justify-between">
                        <div>
                            <RichTextDisplay v-if="project.description" :content="project.description" class="mb-4" />
                            <div class="flex flex-wrap items-center gap-4 text-sm">
                                <span
                                    :class="getStatusBadgeClass(project.status)"
                                    class="inline-flex items-center rounded-xl px-3 py-1 text-sm font-medium"
                                >
                                    {{ statuses[project.status] }}
                                </span>
                                <span v-if="project.client" class="text-slate-500 dark:text-slate-400">
                                    Client: {{ project.client.name }}
                                </span>
                                <span v-if="project.due_date" :class="project.is_overdue ? 'text-pink-600 dark:text-pink-400' : 'text-slate-500 dark:text-slate-400'">
                                    {{ t('due_date') }}: {{ formatDateDisplay(project.due_date) }}
                                </span>
                            </div>
                        </div>
                        <Link
                            :href="route('projects.edit', project.id)"
                            class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-300"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" />
                            </svg>
                        </Link>
                    </div>

                    <!-- Progress bar -->
                    <div class="mt-6">
                        <div class="flex items-center justify-between text-sm mb-2">
                            <span class="text-slate-600 dark:text-slate-400">{{ t('completion') }}</span>
                            <span class="font-medium text-slate-900 dark:text-white">
                                {{ project.completed_tasks_count || 0 }}/{{ project.tasks_count || 0 }} {{ t('tasks_completed') }}
                            </span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-600">
                            <div
                                class="h-full rounded-full bg-primary-500 transition-all"
                                :style="{ width: `${completionPercentage}%` }"
                            />
                        </div>
                    </div>
                </div>

                <!-- Tasks section -->
                <div class="rounded-2xl bg-white p-6 border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('tasks') }}</h2>
                        <div class="flex items-center gap-3">
                            <!-- Sort selector -->
                            <div v-if="currentTaskView === 'list'" class="flex items-center gap-2">
                                <span class="text-xs text-slate-500 dark:text-slate-400">Tri :</span>
                                <select
                                    :value="currentSort"
                                    @change="switchSort($event.target.value)"
                                    class="rounded-lg border-0 py-1 pl-2 pr-7 text-xs text-slate-700 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-slate-300 dark:ring-slate-600"
                                >
                                    <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>
                            <!-- View toggle -->
                            <div class="inline-flex rounded-lg bg-slate-100 p-0.5 dark:bg-slate-700">
                                <button
                                    @click="switchTaskView('list')"
                                    :class="[
                                        'rounded-md px-3 py-1 text-sm font-medium transition-colors',
                                        currentTaskView === 'list'
                                            ? 'bg-white text-slate-900 shadow dark:bg-slate-600 dark:text-white'
                                            : 'text-slate-600 dark:text-slate-400'
                                    ]"
                                >
                                    Liste
                                </button>
                                <button
                                    @click="switchTaskView('kanban')"
                                    :class="[
                                        'rounded-md px-3 py-1 text-sm font-medium transition-colors',
                                        currentTaskView === 'kanban'
                                            ? 'bg-white text-slate-900 shadow dark:bg-slate-600 dark:text-white'
                                            : 'text-slate-600 dark:text-slate-400'
                                    ]"
                                >
                                    Kanban
                                </button>
                            </div>
                            <button
                                @click="showAddTask = !showAddTask"
                                class="inline-flex items-center rounded-lg bg-primary-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-600"
                            >
                                <svg class="mr-1 h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                                </svg>
                                {{ t('add_task') }}
                            </button>
                        </div>
                    </div>

                    <!-- Add task form -->
                    <div v-if="showAddTask" class="mb-4 rounded-xl bg-slate-50 p-4 dark:bg-slate-700/50">
                        <form @submit.prevent="submitTask" class="space-y-3">
                            <input
                                v-model="taskForm.title"
                                type="text"
                                :placeholder="t('task') + '...'"
                                class="block w-full rounded-lg border-0 py-2 px-3 text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-primary-500 dark:bg-slate-800 dark:text-white dark:ring-slate-600 sm:text-sm"
                                required
                            />
                            <div class="flex items-center gap-3">
                                <select
                                    v-model="taskForm.priority"
                                    class="rounded-lg border-0 py-1.5 pl-3 pr-8 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-800 dark:text-white dark:ring-slate-600"
                                >
                                    <option v-for="(label, value) in taskPriorities" :key="value" :value="value">
                                        {{ label }}
                                    </option>
                                </select>
                                <input
                                    v-model="taskForm.due_date"
                                    type="date"
                                    class="rounded-lg border-0 py-1.5 px-3 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-800 dark:text-white dark:ring-slate-600"
                                />
                                <div class="flex-1" />
                                <button
                                    type="button"
                                    @click="showAddTask = false"
                                    class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400"
                                >
                                    {{ t('cancel') }}
                                </button>
                                <button
                                    type="submit"
                                    :disabled="taskForm.processing"
                                    class="rounded-lg bg-primary-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-600 disabled:opacity-50"
                                >
                                    {{ t('add') }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- List view -->
                    <div v-if="currentTaskView === 'list'" class="space-y-1">
                        <div v-if="displayTasks.length === 0" class="py-8 text-center text-slate-500 dark:text-slate-400">
                            {{ t('no_tasks') }}
                        </div>

                        <!-- Draggable task list -->
                        <draggable
                            v-model="displayTasks"
                            item-key="id"
                            handle=".drag-handle"
                            :disabled="currentSort !== 'manual'"
                            @end="onListDragEnd"
                            class="space-y-1"
                        >
                            <template #item="{ element: task }">
                                <div class="task-item">
                                    <!-- Parent task row -->
                                    <div :class="['group flex items-center gap-2 rounded-xl p-3 transition-colors', task.is_completed ? 'bg-slate-50 dark:bg-slate-700/30' : 'hover:bg-slate-50 dark:hover:bg-slate-700/50']">
                                        <!-- Drag handle -->
                                        <div v-if="currentSort === 'manual'" class="drag-handle shrink-0 cursor-move rounded p-0.5 text-slate-300 hover:text-slate-500 dark:text-slate-600 dark:hover:text-slate-400">
                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <!-- Expand button -->
                                        <button v-if="task.children && task.children.length > 0" @click="toggleTaskExpansion(task.id)" class="shrink-0 rounded p-0.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                                            <svg :class="['h-4 w-4 transition-transform', isTaskExpanded(task.id) ? 'rotate-90' : '']" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                        <div v-else-if="currentSort !== 'manual'" class="w-5 shrink-0"></div>
                                        <!-- Checkbox -->
                                        <button @click="toggleTask(task)" :class="['flex h-5 w-5 shrink-0 items-center justify-center rounded border-2 transition-colors', task.is_completed ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-slate-300 hover:border-primary-500 dark:border-slate-600']">
                                            <svg v-if="task.is_completed" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                        <!-- Task content -->
                                        <div class="flex-1 min-w-0">
                                            <div v-if="editingTask !== task.id" class="flex items-center gap-2">
                                                <span :class="['text-sm', task.is_completed ? 'text-slate-400 line-through' : 'text-slate-900 dark:text-white']">{{ task.title }}</span>
                                                <span :class="getPriorityBadgeClass(task.priority)" class="inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium">{{ taskPriorities[task.priority] }}</span>
                                                <span v-if="task.due_date" :class="task.is_overdue ? 'text-pink-500' : 'text-slate-400'" class="text-xs">{{ formatDateDisplay(task.due_date) }}</span>
                                                <span v-if="task.children && task.children.length > 0" class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600 dark:bg-slate-700 dark:text-slate-400">
                                                    <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10zm0 5.25a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75a.75.75 0 01-.75-.75z" clip-rule="evenodd" /></svg>
                                                    {{ task.children.filter(c => c.is_completed).length }}/{{ task.children.length }}
                                                </span>
                                            </div>
                                            <div v-else class="flex items-center gap-2">
                                                <input v-model="editForm.title" type="text" class="flex-1 rounded-lg border-0 py-1 px-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-800 dark:text-white dark:ring-slate-600" />
                                                <select v-model="editForm.priority" class="rounded-lg border-0 py-1 pl-2 pr-6 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
                                                    <option v-for="(label, value) in taskPriorities" :key="value" :value="value">{{ label }}</option>
                                                </select>
                                                <input v-model="editForm.due_date" type="date" class="rounded-lg border-0 py-1 px-2 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-800 dark:text-white dark:ring-slate-600" />
                                                <button @click="saveEditTask(task)" class="text-emerald-500 hover:text-emerald-600"><svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg></button>
                                                <button @click="cancelEditTask" class="text-slate-400 hover:text-slate-600"><svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg></button>
                                            </div>
                                        </div>
                                        <!-- Actions -->
                                        <div v-if="editingTask !== task.id" class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button @click="startAddSubtask(task.id)" class="rounded p-1 text-slate-400 hover:bg-slate-200 hover:text-slate-600 dark:hover:bg-slate-600" title="Ajouter une sous-tâche">
                                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
                                            </button>
                                            <button @click="startEditTask(task)" class="rounded p-1 text-slate-400 hover:bg-slate-200 hover:text-slate-600 dark:hover:bg-slate-600">
                                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" /></svg>
                                            </button>
                                            <button @click="deleteTask(task)" class="rounded p-1 text-slate-400 hover:bg-red-100 hover:text-red-600 dark:hover:bg-red-900/30">
                                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.519.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" /></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <!-- Subtasks -->
                                    <div v-if="(task.children && task.children.length > 0 && isTaskExpanded(task.id)) || addingSubtaskTo === task.id" class="ml-7 space-y-1 border-l-2 border-slate-200 pl-4 dark:border-slate-600">
                                        <div v-for="subtask in getSortedChildren(task)" :key="subtask.id" :class="['group flex items-center gap-2 rounded-lg p-2 transition-colors', subtask.is_completed ? 'bg-slate-50 dark:bg-slate-700/30' : 'hover:bg-slate-50 dark:hover:bg-slate-700/50']">
                                            <button @click="toggleTask(subtask)" :class="['flex h-4 w-4 shrink-0 items-center justify-center rounded border-2 transition-colors', subtask.is_completed ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-slate-300 hover:border-primary-500 dark:border-slate-600']">
                                                <svg v-if="subtask.is_completed" class="h-2.5 w-2.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                            </button>
                                            <template v-if="editingTask !== subtask.id">
                                                <span :class="['flex-1 text-sm', subtask.is_completed ? 'text-slate-400 line-through' : 'text-slate-700 dark:text-slate-300']">{{ subtask.title }}</span>
                                                <span :class="getPriorityBadgeClass(subtask.priority)" class="inline-flex items-center rounded px-1 py-0.5 text-xs font-medium">{{ taskPriorities[subtask.priority] }}</span>
                                                <span v-if="subtask.due_date" :class="subtask.is_overdue ? 'text-pink-500' : 'text-slate-400'" class="text-xs">{{ formatDateDisplay(subtask.due_date) }}</span>
                                                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <button @click="startEditTask(subtask)" class="rounded p-1 text-slate-400 hover:bg-slate-200 hover:text-slate-600 dark:hover:bg-slate-600"><svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" /></svg></button>
                                                    <button @click="deleteTask(subtask)" class="rounded p-1 text-slate-400 hover:bg-red-100 hover:text-red-600 dark:hover:bg-red-900/30"><svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.519.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" /></svg></button>
                                                </div>
                                            </template>
                                            <template v-else>
                                                <input v-model="editForm.title" type="text" class="flex-1 rounded-lg border-0 py-1 px-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-800 dark:text-white dark:ring-slate-600" />
                                                <select v-model="editForm.priority" class="rounded-lg border-0 py-1 pl-2 pr-6 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
                                                    <option v-for="(label, value) in taskPriorities" :key="value" :value="value">{{ label }}</option>
                                                </select>
                                                <input v-model="editForm.due_date" type="date" class="rounded-lg border-0 py-1 px-2 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-800 dark:text-white dark:ring-slate-600" />
                                                <button @click="saveEditTask(subtask)" class="text-emerald-500 hover:text-emerald-600"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg></button>
                                                <button @click="cancelEditTask" class="text-slate-400 hover:text-slate-600"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg></button>
                                            </template>
                                        </div>
                                        <div v-if="addingSubtaskTo === task.id" class="flex items-center gap-2 rounded-lg bg-slate-100 p-2 dark:bg-slate-700/50">
                                            <input v-model="subtaskForm.title" type="text" placeholder="Nouvelle sous-tâche..." class="flex-1 rounded-lg border-0 py-1 px-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-800 dark:text-white dark:ring-slate-600" @keyup.enter="submitSubtask(task)" @keyup.escape="cancelAddSubtask" />
                                            <select v-model="subtaskForm.priority" class="rounded-lg border-0 py-1 pl-2 pr-6 text-xs text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-800 dark:text-white dark:ring-slate-600">
                                                <option v-for="(label, value) in taskPriorities" :key="value" :value="value">{{ label }}</option>
                                            </select>
                                            <button @click="submitSubtask(task)" :disabled="subtaskForm.processing || !subtaskForm.title" class="rounded-lg bg-primary-500 px-2 py-1 text-xs font-medium text-white hover:bg-primary-600 disabled:opacity-50">Ajouter</button>
                                            <button @click="cancelAddSubtask" class="text-slate-400 hover:text-slate-600"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg></button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </draggable>
                    </div>

                    <!-- Kanban view -->
                    <div v-else-if="currentTaskView === 'kanban'">
                        <!-- Sort selector for Kanban -->
                        <div class="mb-4 flex items-center gap-2">
                            <span class="text-xs text-slate-500 dark:text-slate-400">Tri :</span>
                            <select
                                :value="currentSort"
                                @change="switchSort($event.target.value)"
                                class="rounded-lg border-0 py-1 pl-2 pr-7 text-xs text-slate-700 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-slate-300 dark:ring-slate-600"
                            >
                                <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>

                        <div class="flex gap-4 overflow-x-auto pb-4">
                            <div
                                v-for="(label, status) in taskStatuses"
                                :key="status"
                                class="flex-shrink-0 w-64"
                            >
                                <div class="mb-2 flex items-center justify-between">
                                    <h3 class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                        {{ label }}
                                    </h3>
                                    <span class="text-xs text-slate-400">{{ kanbanTasks[status]?.length || 0 }}</span>
                                </div>
                                <draggable
                                    v-model="kanbanTasks[status]"
                                    group="tasks"
                                    item-key="id"
                                    :disabled="currentSort !== 'manual'"
                                    @end="onKanbanDragEnd"
                                    class="min-h-[100px] space-y-2 rounded-xl bg-slate-100 p-2 dark:bg-slate-700/50"
                                >
                                    <template #item="{ element: task }">
                                        <div
                                            :class="[
                                                'rounded-lg bg-white p-3 shadow-sm dark:bg-slate-800',
                                                currentSort === 'manual' ? 'cursor-move' : '',
                                                task.is_completed ? 'opacity-60' : ''
                                            ]"
                                        >
                                            <div class="flex items-start gap-2">
                                                <button
                                                    @click="toggleTask(task)"
                                                    :class="[
                                                        'mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded border transition-colors',
                                                        task.is_completed
                                                            ? 'border-emerald-500 bg-emerald-500 text-white'
                                                            : 'border-slate-300 dark:border-slate-600'
                                                    ]"
                                                >
                                                    <svg v-if="task.is_completed" class="h-2.5 w-2.5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                                <span :class="['text-sm flex-1', task.is_completed ? 'line-through text-slate-400' : 'text-slate-900 dark:text-white']">
                                                    {{ task.title }}
                                                </span>
                                            </div>
                                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                                <span
                                                    :class="getPriorityBadgeClass(task.priority)"
                                                    class="inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium"
                                                >
                                                    {{ taskPriorities[task.priority] }}
                                                </span>
                                                <span v-if="task.due_date" :class="task.is_overdue ? 'text-pink-500' : 'text-slate-400'" class="text-xs">
                                                    {{ formatDateDisplay(task.due_date) }}
                                                </span>
                                                <!-- Subtask count badge -->
                                                <span
                                                    v-if="task.children && task.children.length > 0"
                                                    class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-1.5 py-0.5 text-xs text-slate-600 dark:bg-slate-700 dark:text-slate-400"
                                                >
                                                    <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10zm0 5.25a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75a.75.75 0 01-.75-.75z" clip-rule="evenodd" />
                                                    </svg>
                                                    {{ task.children.filter(c => c.is_completed).length }}/{{ task.children.length }}
                                                </span>
                                            </div>
                                        </div>
                                    </template>
                                </draggable>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Stats -->
                <div class="rounded-2xl bg-white p-6 border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                    <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-4">Statistiques</h3>
                    <div class="space-y-4">
                        <div>
                            <div class="text-sm text-slate-500 dark:text-slate-400">{{ t('time_spent') }}</div>
                            <div class="text-xl font-semibold text-slate-900 dark:text-white">
                                {{ formatDuration(project.time_entries_sum_duration_seconds) }}
                                <span v-if="project.budget_hours" class="text-sm font-normal text-slate-400">
                                    / {{ project.budget_hours }}h
                                </span>
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-slate-500 dark:text-slate-400">{{ t('tasks') }}</div>
                            <div class="text-xl font-semibold text-slate-900 dark:text-white">
                                {{ project.completed_tasks_count || 0 }} / {{ project.tasks_count || 0 }}
                            </div>
                        </div>
                        <div v-if="project.client">
                            <div class="text-sm text-slate-500 dark:text-slate-400">Client</div>
                            <div class="text-slate-900 dark:text-white">{{ project.client.name }}</div>
                        </div>
                    </div>
                </div>

                <!-- Recent time entries -->
                <div v-if="project.time_entries && project.time_entries.length > 0" class="rounded-2xl bg-white p-6 border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                    <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-4">Temps récent</h3>
                    <div class="space-y-3">
                        <div
                            v-for="entry in project.time_entries"
                            :key="entry.id"
                            class="flex items-center justify-between text-sm"
                        >
                            <div>
                                <div class="text-slate-900 dark:text-white">
                                    {{ entry.task?.title || entry.description || 'Sans description' }}
                                </div>
                                <div class="text-xs text-slate-400">
                                    {{ new Date(entry.started_at).toLocaleDateString('fr-FR') }}
                                </div>
                            </div>
                            <div class="text-slate-600 dark:text-slate-400">
                                {{ formatDuration(entry.duration_seconds) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

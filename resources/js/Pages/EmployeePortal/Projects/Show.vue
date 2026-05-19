<script setup>
import EmployeePortalLayout from '@/Layouts/EmployeePortalLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';
import RichTextDisplay from '@/Components/RichTextDisplay.vue';

const { t } = useTranslations();

defineProps({
    project: { type: Object, required: true },
    tasks: { type: Array, required: true },
    timeEntries: { type: Array, required: true },
    taskStatuses: { type: Object, default: () => ({}) },
    taskPriorities: { type: Object, default: () => ({}) },
});

const getPriorityBadgeClass = (priority) => ({
    low: 'bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-slate-400',
    normal: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    high: 'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400',
}[priority] || 'bg-blue-100 text-blue-700');

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

const taskAssignees = (task) => {
    const out = [];
    (task.assigned_employees || []).forEach(e => out.push({ label: [e.first_name, e.last_name].filter(Boolean).join(' ') }));
    (task.assigned_users || []).forEach(u => out.push({ label: u.name }));
    return out;
};

const toggleTask = (task) => {
    router.post(route('employee-portal.tasks.toggle', task.id), {}, { preserveScroll: true });
};

const changeTaskStatus = (task, status) => {
    if (status === task.status) return;
    router.patch(route('employee-portal.tasks.status', task.id), { status }, { preserveScroll: true });
};
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
                    {{ t('project_status.' + project.status) }}
                </span>
                <RichTextDisplay v-if="project.description" :content="project.description" class="mt-3" />
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

            <!-- Project tasks (all) -->
            <div class="rounded-2xl bg-white dark:bg-surface-card border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                    {{ t('tasks') }} ({{ tasks.length }})
                </h2>
                <ul v-if="tasks.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700">
                    <li v-for="task in tasks" :key="task.id" class="py-3">
                        <div class="flex items-center gap-2">
                            <button
                                @click="toggleTask(task)"
                                :class="['flex h-5 w-5 shrink-0 items-center justify-center rounded border-2 transition-colors', task.is_completed ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-gray-300 hover:border-primary-500 dark:border-gray-700']"
                                :title="t(task.is_completed ? 'mark_incomplete' : 'mark_complete')"
                            >
                                <svg v-if="task.is_completed" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                            </button>
                            <div class="flex-1 min-w-0 flex items-center gap-2 flex-wrap">
                                <span :class="['text-sm font-medium', task.is_completed ? 'text-slate-400 line-through' : 'text-slate-900 dark:text-white']">{{ task.title }}</span>
                                <span :class="getPriorityBadgeClass(task.priority)" class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium">{{ taskPriorities[task.priority] }}</span>
                                <select
                                    :value="task.status"
                                    @change="changeTaskStatus(task, $event.target.value)"
                                    :class="['text-xs rounded-full border-0 py-0.5 pl-2 pr-7 font-medium ring-1 ring-inset focus:ring-2 focus:ring-primary-500 cursor-pointer', statusColor(task.status)]"
                                >
                                    <option v-for="(label, value) in taskStatuses" :key="value" :value="value">{{ label }}</option>
                                </select>
                                <span v-for="(a, i) in taskAssignees(task)" :key="'ea'+i" class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2 py-0.5 text-xs text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">
                                    <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" /></svg>
                                    {{ a.label }}
                                </span>
                                <span v-if="task.due_date" class="text-xs text-slate-400">{{ formatDate(task.due_date) }}</span>
                            </div>
                        </div>
                        <p v-if="task.description" class="ml-7 mt-1 text-xs text-slate-500 dark:text-slate-400 truncate">{{ task.description }}</p>
                    </li>
                </ul>
                <p v-else class="text-sm text-slate-500 py-2">{{ t('no_tasks') }}</p>
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

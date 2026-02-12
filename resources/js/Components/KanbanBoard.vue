<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';
import draggable from 'vuedraggable';

const { t } = useTranslations();

const props = defineProps({
    items: {
        type: Array,
        required: true,
    },
    statuses: {
        type: Object,
        required: true,
    },
    itemType: {
        type: String,
        default: 'project', // 'project' or 'task'
    },
});

const emit = defineEmits(['reorder']);

// Create reactive copy of items grouped by status
const itemsByStatus = ref({});

const initializeGroups = () => {
    const grouped = {};
    Object.keys(props.statuses).forEach(status => {
        grouped[status] = props.items.filter(item => item.status === status);
    });
    itemsByStatus.value = grouped;
};

// Initialize on mount and when items change
watch(() => props.items, initializeGroups, { immediate: true, deep: true });

const getStatusColor = (status) => {
    const colors = {
        backlog: 'bg-slate-100 dark:bg-slate-700',
        next: 'bg-blue-50 dark:bg-blue-900/20',
        in_progress: 'bg-yellow-50 dark:bg-yellow-900/20',
        waiting_for: 'bg-orange-50 dark:bg-orange-900/20',
        done: 'bg-emerald-50 dark:bg-emerald-900/20',
    };
    return colors[status] || colors.backlog;
};

const getStatusHeaderColor = (status) => {
    const colors = {
        backlog: 'text-slate-600 dark:text-slate-400',
        next: 'text-blue-600 dark:text-blue-400',
        in_progress: 'text-yellow-600 dark:text-yellow-400',
        waiting_for: 'text-orange-600 dark:text-orange-400',
        done: 'text-emerald-600 dark:text-emerald-400',
    };
    return colors[status] || colors.backlog;
};

const formatDuration = (seconds) => {
    if (!seconds) return '0h';
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    if (minutes === 0) return `${hours}h`;
    return `${hours}h${minutes.toString().padStart(2, '0')}`;
};

const onDragEnd = () => {
    // Flatten all items with updated status and sort_order
    const items = [];
    let order = 0;

    Object.keys(itemsByStatus.value).forEach(status => {
        itemsByStatus.value[status].forEach(item => {
            items.push({
                id: item.id,
                sort_order: order++,
                status: status,
            });
        });
    });

    emit('reorder', items);
};
</script>

<template>
    <div class="flex gap-4 overflow-x-auto pb-4">
        <div
            v-for="(label, status) in statuses"
            :key="status"
            class="flex-shrink-0 w-72"
        >
            <!-- Column header -->
            <div :class="['rounded-t-xl p-3', getStatusColor(status)]">
                <div class="flex items-center justify-between">
                    <h3 :class="['text-sm font-semibold', getStatusHeaderColor(status)]">
                        {{ label }}
                    </h3>
                    <span class="rounded-full bg-white/50 px-2 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-800/50 dark:text-slate-400">
                        {{ itemsByStatus[status]?.length || 0 }}
                    </span>
                </div>
            </div>

            <!-- Column content -->
            <draggable
                v-model="itemsByStatus[status]"
                group="kanban"
                item-key="id"
                @end="onDragEnd"
                :class="['min-h-[200px] space-y-3 rounded-b-xl p-3', getStatusColor(status)]"
            >
                <template #item="{ element: item }">
                    <div
                        class="rounded-xl bg-white p-4 shadow-sm cursor-move border border-slate-200 hover:shadow-md transition-shadow dark:bg-slate-800 dark:border-slate-700"
                    >
                        <!-- Color indicator -->
                        <div class="flex items-start gap-3">
                            <div
                                v-if="item.color"
                                class="mt-1 h-3 w-3 shrink-0 rounded-full"
                                :style="{ backgroundColor: item.color }"
                            />

                            <div class="flex-1 min-w-0">
                                <!-- Title -->
                                <Link
                                    :href="route(itemType === 'project' ? 'projects.show' : 'tasks.show', item.id)"
                                    class="font-medium text-slate-900 hover:text-primary-600 dark:text-white dark:hover:text-primary-400 line-clamp-2"
                                >
                                    {{ item.title }}
                                </Link>

                                <!-- Client (for projects) -->
                                <p v-if="item.client" class="mt-1 text-sm text-slate-500 dark:text-slate-400 truncate">
                                    {{ item.client.name }}
                                </p>

                                <!-- Meta info -->
                                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                                    <!-- Due date -->
                                    <span
                                        v-if="item.due_date"
                                        :class="[
                                            'inline-flex items-center gap-1 rounded-lg px-2 py-0.5',
                                            item.is_overdue
                                                ? 'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400'
                                                : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400'
                                        ]"
                                    >
                                        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" />
                                        </svg>
                                        {{ new Date(item.due_date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' }) }}
                                    </span>

                                    <!-- Time spent -->
                                    <span
                                        v-if="item.time_entries_sum_duration_seconds"
                                        class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2 py-0.5 text-slate-600 dark:bg-slate-700 dark:text-slate-400"
                                    >
                                        <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" />
                                        </svg>
                                        {{ formatDuration(item.time_entries_sum_duration_seconds) }}
                                    </span>
                                </div>

                                <!-- Progress bar (for projects with tasks) -->
                                <div v-if="item.tasks_count > 0" class="mt-3">
                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-600">
                                            <div
                                                class="h-full rounded-full bg-primary-500"
                                                :style="{ width: `${(item.completed_tasks_count / item.tasks_count) * 100}%` }"
                                            />
                                        </div>
                                        <span class="text-xs text-slate-500 dark:text-slate-400">
                                            {{ item.completed_tasks_count }}/{{ item.tasks_count }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </draggable>
        </div>
    </div>
</template>

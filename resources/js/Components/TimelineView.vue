<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    projects: {
        type: Array,
        required: true,
    },
});

const zoomLevel = ref('month'); // 'week', 'month', 'quarter'
const scrollContainer = ref(null);

// Calculate date range based on projects
const dateRange = computed(() => {
    const today = new Date();
    let start = new Date(today);
    let end = new Date(today);

    // Find earliest and latest dates from projects
    props.projects.forEach(project => {
        const createdAt = new Date(project.created_at);
        const dueDate = project.due_date ? new Date(project.due_date) : null;

        if (createdAt < start) start = createdAt;
        if (dueDate && dueDate > end) end = dueDate;
    });

    // Add padding based on zoom level
    const padding = zoomLevel.value === 'week' ? 7 : zoomLevel.value === 'month' ? 30 : 90;
    start.setDate(start.getDate() - padding);
    end.setDate(end.getDate() + padding);

    return { start, end };
});

// Calculate number of days in range
const totalDays = computed(() => {
    const diff = dateRange.value.end - dateRange.value.start;
    return Math.ceil(diff / (1000 * 60 * 60 * 24));
});

// Day width based on zoom level
const dayWidth = computed(() => {
    switch (zoomLevel.value) {
        case 'week': return 40;
        case 'month': return 12;
        case 'quarter': return 4;
        default: return 12;
    }
});

// Timeline width
const timelineWidth = computed(() => totalDays.value * dayWidth.value);

// Generate header dates (months or weeks)
const headerDates = computed(() => {
    const dates = [];
    const current = new Date(dateRange.value.start);

    if (zoomLevel.value === 'week') {
        // Show individual days
        while (current <= dateRange.value.end) {
            dates.push({
                date: new Date(current),
                label: current.getDate().toString(),
                isToday: isSameDay(current, new Date()),
                isWeekend: current.getDay() === 0 || current.getDay() === 6,
            });
            current.setDate(current.getDate() + 1);
        }
    } else {
        // Show months
        while (current <= dateRange.value.end) {
            const monthStart = new Date(current.getFullYear(), current.getMonth(), 1);
            const monthEnd = new Date(current.getFullYear(), current.getMonth() + 1, 0);

            dates.push({
                date: monthStart,
                label: monthStart.toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' }),
                width: getDaysInRange(monthStart, monthEnd, dateRange.value.start, dateRange.value.end) * dayWidth.value,
            });

            current.setMonth(current.getMonth() + 1);
        }
    }

    return dates;
});

// Today position
const todayPosition = computed(() => {
    const today = new Date();
    if (today < dateRange.value.start || today > dateRange.value.end) return null;

    const diff = today - dateRange.value.start;
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    return days * dayWidth.value;
});

// Calculate project bar position and width
const getProjectBar = (project) => {
    const start = new Date(project.created_at);
    const end = project.due_date ? new Date(project.due_date) : new Date();

    // Clamp to date range
    const barStart = start < dateRange.value.start ? dateRange.value.start : start;
    const barEnd = end > dateRange.value.end ? dateRange.value.end : end;

    const startDiff = barStart - dateRange.value.start;
    const startDays = Math.floor(startDiff / (1000 * 60 * 60 * 24));

    const endDiff = barEnd - dateRange.value.start;
    const endDays = Math.floor(endDiff / (1000 * 60 * 60 * 24));

    const duration = endDays - startDays;

    return {
        left: startDays * dayWidth.value,
        width: Math.max(duration * dayWidth.value, 20), // Minimum 20px width
    };
};

// Helper functions
const isSameDay = (d1, d2) => {
    return d1.getDate() === d2.getDate() &&
           d1.getMonth() === d2.getMonth() &&
           d1.getFullYear() === d2.getFullYear();
};

const getDaysInRange = (monthStart, monthEnd, rangeStart, rangeEnd) => {
    const start = monthStart < rangeStart ? rangeStart : monthStart;
    const end = monthEnd > rangeEnd ? rangeEnd : monthEnd;
    const diff = end - start;
    return Math.max(Math.ceil(diff / (1000 * 60 * 60 * 24)), 0) + 1;
};

const scrollToToday = () => {
    if (scrollContainer.value && todayPosition.value !== null) {
        const containerWidth = scrollContainer.value.clientWidth;
        scrollContainer.value.scrollLeft = todayPosition.value - containerWidth / 2;
    }
};

onMounted(() => {
    scrollToToday();
});

const getStatusLabel = (status) => {
    const labels = {
        backlog: t('project_status.backlog'),
        next: t('project_status.next'),
        in_progress: t('project_status.in_progress'),
        waiting_for: t('project_status.waiting_for'),
        done: t('project_status.done'),
    };
    return labels[status] || status;
};

const getStatusColor = (status) => {
    const colors = {
        backlog: 'bg-slate-400',
        next: 'bg-blue-500',
        in_progress: 'bg-yellow-500',
        waiting_for: 'bg-orange-500',
        done: 'bg-emerald-500',
    };
    return colors[status] || 'bg-slate-400';
};
</script>

<template>
    <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800">
        <!-- Toolbar -->
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3 dark:border-slate-700">
            <div class="flex items-center gap-2">
                <button
                    @click="zoomLevel = 'week'"
                    :class="[
                        'rounded-lg px-3 py-1.5 text-sm font-medium transition-colors',
                        zoomLevel === 'week'
                            ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400'
                            : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700'
                    ]"
                >
                    {{ t('this_week') }}
                </button>
                <button
                    @click="zoomLevel = 'month'"
                    :class="[
                        'rounded-lg px-3 py-1.5 text-sm font-medium transition-colors',
                        zoomLevel === 'month'
                            ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400'
                            : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700'
                    ]"
                >
                    {{ t('this_month') }}
                </button>
                <button
                    @click="zoomLevel = 'quarter'"
                    :class="[
                        'rounded-lg px-3 py-1.5 text-sm font-medium transition-colors',
                        zoomLevel === 'quarter'
                            ? 'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400'
                            : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700'
                    ]"
                >
                    Trimestre
                </button>
            </div>
            <button
                @click="scrollToToday"
                class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-sm font-medium text-primary-600 hover:bg-primary-50 dark:text-primary-400 dark:hover:bg-primary-900/20"
            >
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2z" clip-rule="evenodd" />
                </svg>
                {{ t('today') }}
            </button>
        </div>

        <!-- Timeline -->
        <div class="flex">
            <!-- Project names column -->
            <div class="flex-shrink-0 w-48 border-r border-slate-200 dark:border-slate-700">
                <!-- Header spacer -->
                <div class="h-10 border-b border-slate-200 dark:border-slate-700"></div>
                <!-- Project names -->
                <div v-for="project in projects" :key="project.id" class="h-12 flex items-center px-3 border-b border-slate-100 dark:border-slate-700">
                    <div class="flex items-center gap-2 min-w-0">
                        <span
                            class="h-2.5 w-2.5 flex-shrink-0 rounded-full"
                            :style="{ backgroundColor: project.color || '#9b5de5' }"
                        ></span>
                        <Link
                            :href="route('projects.show', project.id)"
                            class="truncate text-sm font-medium text-slate-900 hover:text-primary-600 dark:text-white dark:hover:text-primary-400"
                        >
                            {{ project.title }}
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Timeline area -->
            <div ref="scrollContainer" class="flex-1 overflow-x-auto">
                <div :style="{ width: timelineWidth + 'px' }">
                    <!-- Header with dates -->
                    <div class="h-10 flex border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700">
                        <template v-if="zoomLevel === 'week'">
                            <div
                                v-for="date in headerDates"
                                :key="date.date.toISOString()"
                                :class="[
                                    'flex-shrink-0 flex items-center justify-center text-xs border-r border-slate-200 dark:border-slate-600',
                                    date.isToday ? 'bg-primary-100 font-bold text-primary-700 dark:bg-primary-900/30 dark:text-primary-400' : '',
                                    date.isWeekend ? 'bg-slate-100 dark:bg-slate-600' : ''
                                ]"
                                :style="{ width: dayWidth + 'px' }"
                            >
                                {{ date.label }}
                            </div>
                        </template>
                        <template v-else>
                            <div
                                v-for="month in headerDates"
                                :key="month.date.toISOString()"
                                class="flex-shrink-0 flex items-center justify-center text-xs font-medium text-slate-600 dark:text-slate-300 border-r border-slate-200 dark:border-slate-600"
                                :style="{ width: month.width + 'px' }"
                            >
                                {{ month.label }}
                            </div>
                        </template>
                    </div>

                    <!-- Project bars -->
                    <div class="relative">
                        <!-- Today line -->
                        <div
                            v-if="todayPosition !== null"
                            class="absolute top-0 bottom-0 w-0.5 bg-pink-500 z-10"
                            :style="{ left: todayPosition + 'px' }"
                        >
                            <div class="absolute -top-3 left-1/2 -translate-x-1/2 rounded bg-pink-500 px-1.5 py-0.5 text-[10px] font-medium text-white">
                                {{ t('today') }}
                            </div>
                        </div>

                        <!-- Project rows -->
                        <div
                            v-for="project in projects"
                            :key="project.id"
                            class="relative h-12 border-b border-slate-100 dark:border-slate-700"
                        >
                            <!-- Bar -->
                            <div
                                class="absolute top-2 h-8 rounded-lg flex items-center px-2 cursor-pointer transition-opacity hover:opacity-90"
                                :style="{
                                    left: getProjectBar(project).left + 'px',
                                    width: getProjectBar(project).width + 'px',
                                    backgroundColor: project.color || '#9b5de5',
                                }"
                            >
                                <span class="truncate text-xs font-medium text-white drop-shadow-sm">
                                    {{ project.title }}
                                </span>
                                <!-- Progress indicator if tasks exist -->
                                <div
                                    v-if="project.tasks_count > 0"
                                    class="absolute bottom-0.5 left-1 right-1 h-1 rounded-full bg-white/30"
                                >
                                    <div
                                        class="h-full rounded-full bg-white"
                                        :style="{ width: `${(project.completed_tasks_count / project.tasks_count) * 100}%` }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Due date marker -->
                            <div
                                v-if="project.due_date && project.is_overdue"
                                class="absolute top-2 h-8 w-1 rounded bg-pink-500"
                                :style="{ left: getProjectBar(project).left + getProjectBar(project).width - 4 + 'px' }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-if="projects.length === 0" class="py-12 text-center">
            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ t('no_projects') }}</p>
        </div>
    </div>
</template>

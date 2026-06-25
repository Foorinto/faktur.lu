<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import HRNav from '@/Components/HRNav.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useTour } from '@/Composables/useTour';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';

const { t } = useTranslations();
const { startTour } = useTour();

onMounted(() => setTimeout(() => startTour('hrLeavesCalendar'), 600));

const props = defineProps({
    leaveRequests: { type: Array, required: true },
    month: { type: Number, required: true },
    year: { type: Number, required: true },
});

const calendarRef = ref(null);

const events = computed(() => {
    return props.leaveRequests.map(lr => {
        const endDate = new Date(lr.end_date);
        endDate.setDate(endDate.getDate() + 1);

        return {
            id: lr.id,
            title: `${lr.employee?.first_name} ${lr.employee?.last_name}`,
            start: lr.start_date,
            end: endDate.toISOString().slice(0, 10),
            backgroundColor: lr.leave_type?.color || '#94a3b8',
            borderColor: lr.leave_type?.color || '#94a3b8',
            classNames: lr.status === 'pending' ? ['fc-event-pending'] : [],
            extendedProps: {
                employeeId: lr.employee?.id,
                leaveType: lr.leave_type?.name,
                status: lr.status,
                daysCount: lr.days_count,
            },
        };
    });
});

const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    initialDate: `${props.year}-${String(props.month).padStart(2, '0')}-01`,
    locale: 'fr',
    firstDay: 1,
    height: 'auto',
    headerToolbar: false,
    events: events.value,
    eventClick: (info) => {
        const empId = info.event.extendedProps.employeeId;
        if (empId) {
            router.get(route('hr.employees.leaves', empId));
        }
    },
    eventDidMount: (info) => {
        const { leaveType, status, daysCount } = info.event.extendedProps;
        const statusLabel = status === 'pending' ? ` (${t('hr.leave_status_pending')})` : '';
        info.el.title = `${info.event.title}\n${leaveType} - ${daysCount} ${t('hr.days')}${statusLabel}`;
    },
    dayMaxEvents: 4,
    moreLinkText: (n) => `+${n}`,
    fixedWeekCount: false,
}));

const prevMonth = () => {
    let m = props.month - 1;
    let y = props.year;
    if (m < 1) { m = 12; y--; }
    router.get(route('hr.leaves.calendar'), { month: m, year: y }, { preserveState: true });
};

const nextMonth = () => {
    let m = props.month + 1;
    let y = props.year;
    if (m > 12) { m = 1; y++; }
    router.get(route('hr.leaves.calendar'), { month: m, year: y }, { preserveState: true });
};

const goToday = () => {
    const now = new Date();
    router.get(route('hr.leaves.calendar'), { month: now.getMonth() + 1, year: now.getFullYear() }, { preserveState: true });
};

const monthNames = computed(() => [
    t('month_january'), t('month_february'), t('month_march'), t('month_april'), t('month_may'), t('month_june'),
    t('month_july'), t('month_august'), t('month_september'), t('month_october'), t('month_november'), t('month_december'),
]);
</script>

<template>
    <Head :title="`${t('hr.calendar')} - ${monthNames[month - 1]} ${year}`" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                {{ t('hr.calendar') }}
            </h1>
        </template>
        <template #header-actions>
            <Link
                :href="route('hr.leaves.index')"
                class="inline-flex items-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-slate-300 dark:ring-slate-600"
            >
                {{ t('hr.leave_requests') }}
            </Link>
        </template>

        <HRNav class="mb-6" />

        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Month navigation -->
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
                    <div class="flex items-center gap-4">
                        <button
                            @click="prevMonth"
                            class="rounded-xl p-2 text-slate-400 hover:bg-gray-50 hover:text-slate-600 dark:hover:bg-gray-800 dark:hover:text-slate-300"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white min-w-[200px] text-center">
                            {{ monthNames[month - 1] }} {{ year }}
                        </h2>
                        <button
                            @click="nextMonth"
                            class="rounded-xl p-2 text-slate-400 hover:bg-gray-50 hover:text-slate-600 dark:hover:bg-gray-800 dark:hover:text-slate-300"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                    <button
                        @click="goToday"
                        class="w-full sm:w-auto rounded-xl bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-slate-300 dark:ring-slate-600"
                    >
                        {{ t('hr.today') }}
                    </button>
                </div>

                <!-- FullCalendar -->
                <div data-tour="hr-calendar-view" class="fc-wrapper overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50 p-4">
                    <FullCalendar ref="calendarRef" :options="calendarOptions" />
                </div>

                <!-- Legend -->
                <div v-if="leaveRequests.length > 0" class="mt-4 flex flex-wrap items-center gap-4">
                    <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">{{ t('hr.legend') }} :</div>
                    <div
                        v-for="lt in [...new Map(leaveRequests.map(lr => [lr.leave_type?.id, lr.leave_type])).values()].filter(Boolean)"
                        :key="lt.id"
                        class="flex items-center gap-1.5"
                    >
                        <span class="h-3 w-3 rounded" :style="{ backgroundColor: lt.color || '#94a3b8' }"></span>
                        <span class="text-xs text-slate-600 dark:text-slate-400">{{ lt.name }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="h-3 w-3 rounded bg-slate-400 opacity-60"></span>
                        <span class="text-xs text-slate-600 dark:text-slate-400">{{ t('hr.leave_status_pending') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
/* FullCalendar theme overrides */
.fc-wrapper .fc {
    --fc-border-color: theme('colors.slate.200');
    --fc-today-bg-color: theme('colors.primary.50');
    --fc-page-bg-color: transparent;
    --fc-neutral-bg-color: theme('colors.slate.50');
    --fc-event-border-color: transparent;
    font-family: inherit;
}

.dark .fc-wrapper .fc {
    --fc-border-color: theme('colors.slate.700');
    --fc-today-bg-color: rgba(99, 102, 241, 0.08);
    --fc-neutral-bg-color: theme('colors.slate.800');
}

.fc-wrapper .fc .fc-col-header-cell {
    padding: 10px 0;
    font-size: 0.75rem;
    font-weight: 600;
    color: theme('colors.slate.500');
    background: theme('colors.slate.50');
    border-bottom: 1px solid var(--fc-border-color);
}

.dark .fc-wrapper .fc .fc-col-header-cell {
    background: theme('colors.slate.700');
    color: theme('colors.slate.400');
}

.fc-wrapper .fc .fc-daygrid-day-number {
    font-size: 0.8rem;
    font-weight: 500;
    color: theme('colors.slate.700');
    padding: 6px 8px;
}

.dark .fc-wrapper .fc .fc-daygrid-day-number {
    color: theme('colors.slate.300');
}

.fc-wrapper .fc .fc-day-today .fc-daygrid-day-number {
    background: theme('colors.primary.500');
    color: white;
    border-radius: 9999px;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 4px;
}

.fc-wrapper .fc .fc-daygrid-day.fc-day-sat,
.fc-wrapper .fc .fc-daygrid-day.fc-day-sun {
    background: theme('colors.slate.50');
}

.dark .fc-wrapper .fc .fc-daygrid-day.fc-day-sat,
.dark .fc-wrapper .fc .fc-daygrid-day.fc-day-sun {
    background: rgba(30, 41, 59, 0.5);
}

.fc-wrapper .fc .fc-daygrid-day {
    min-height: 100px;
}

.fc-wrapper .fc .fc-event {
    border-radius: 6px;
    padding: 1px 6px;
    font-size: 0.72rem;
    font-weight: 500;
    cursor: pointer;
    border: none;
    margin-bottom: 1px;
}

.fc-wrapper .fc .fc-event:hover {
    filter: brightness(0.9);
}

.fc-wrapper .fc .fc-event-pending {
    opacity: 0.55;
}

.fc-wrapper .fc .fc-more-link {
    font-size: 0.7rem;
    font-weight: 600;
    color: theme('colors.primary.600');
    padding: 2px 4px;
}

.dark .fc-wrapper .fc .fc-more-link {
    color: theme('colors.primary.400');
}

.fc-wrapper .fc .fc-daygrid-day-frame {
    min-height: 100px;
}

.fc-wrapper .fc table {
    border-radius: 12px;
    overflow: hidden;
}

.fc-wrapper .fc .fc-scrollgrid {
    border-radius: 12px;
    overflow: hidden;
}
</style>

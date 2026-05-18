<script setup>
import EmployeePortalLayout from '@/Layouts/EmployeePortalLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import axios from 'axios';
import { useTranslations } from '@/Composables/useTranslations';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';

const { t, locale } = useTranslations();

const props = defineProps({
    employees: { type: Array, required: true },
    rooms: { type: Array, required: true },
    sharedCalendarEnabled: { type: Boolean, default: true },
    currentEmployeeId: { type: [Number, null], default: null },
});

const calendarRef = ref(null);
const events = ref([]);

const selectedEmployees = ref(props.employees.map(e => e.id));
const selectedTypes = ref(['meeting', 'training', 'team_building', 'deadline', 'other']);
const showLeaves = ref(true);
const showEvents = ref(true);

const fetchEvents = async (info, successCallback, failureCallback) => {
    try {
        const params = new URLSearchParams();
        params.append('start', info.startStr);
        params.append('end', info.endStr);
        selectedEmployees.value.forEach(id => params.append('employees[]', id));
        selectedTypes.value.forEach(t => params.append('types[]', t));

        const { data } = await axios.get(route('employee-portal.shared-calendar.events') + '?' + params.toString());

        const combined = [];
        if (showEvents.value) {
            combined.push(...data.events.map(e => ({ ...e, backgroundColor: e.color, borderColor: e.color })));
        }
        if (showLeaves.value) {
            combined.push(...data.leaves.map(l => ({ ...l, backgroundColor: l.color, borderColor: l.color, display: 'block' })));
        }
        events.value = combined;
        successCallback(combined);
    } catch (error) {
        console.error('Calendar fetch error:', error);
        failureCallback(error);
    }
};

const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    locale: locale() || 'fr',
    firstDay: 1,
    height: 'auto',
    headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
    buttonText: { today: t('hr_calendar_today') },
    events: fetchEvents,
    eventClick: (info) => {
        if (info.event.id?.startsWith('evt-')) {
            router.get(route('employee-portal.shared-calendar.events.show', info.event.id.replace('evt-', '')));
        }
    },
    dateClick: (info) => {
        router.get(route('employee-portal.shared-calendar.events.create'), { date: info.dateStr });
    },
    dayMaxEvents: 4,
    moreLinkText: (n) => `+${n}`,
    fixedWeekCount: false,
}));

const refresh = () => {
    calendarRef.value?.getApi().refetchEvents();
};

const toggleEmployee = (id) => {
    if (selectedEmployees.value.includes(id)) {
        selectedEmployees.value = selectedEmployees.value.filter(e => e !== id);
    } else {
        selectedEmployees.value.push(id);
    }
    refresh();
};
</script>

<template>
    <Head :title="t('hr_calendar_title')" />

    <EmployeePortalLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ t('hr_calendar_title') }}</h1>
                <Link
                    :href="route('employee-portal.shared-calendar.events.create')"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-primary-500 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-600 self-start sm:self-auto"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ t('hr_event_new') }}
                </Link>
            </div>

            <div v-if="!sharedCalendarEnabled" class="rounded-2xl bg-yellow-50 border border-yellow-200 p-6 text-center">
                <p class="text-yellow-800">{{ t('hr_calendar_disabled_description') }}</p>
            </div>

            <div v-else class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6">
                <!-- Sidebar filters -->
                <aside class="bg-white dark:bg-surface-card rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 h-fit">
                    <div class="mb-5">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">{{ t('hr_calendar_filter_display') }}</h3>
                        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 mb-2 cursor-pointer">
                            <input type="checkbox" v-model="showEvents" @change="refresh" class="rounded text-primary-500" />
                            {{ t('hr_calendar_filter_events') }}
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" v-model="showLeaves" @change="refresh" class="rounded text-primary-500" />
                            {{ t('hr_calendar_filter_leaves') }}
                        </label>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-2">{{ t('hr_calendar_filter_employees') }}</h3>
                        <div class="space-y-1.5 max-h-72 overflow-y-auto pr-1">
                            <label v-for="emp in employees" :key="emp.id" class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 rounded px-2 py-1">
                                <input type="checkbox" :checked="selectedEmployees.includes(emp.id)" @change="toggleEmployee(emp.id)" class="rounded text-primary-500" />
                                <span class="truncate">{{ emp.first_name }} {{ emp.last_name }}</span>
                            </label>
                        </div>
                    </div>
                </aside>

                <!-- Calendar -->
                <div class="bg-white dark:bg-surface-card rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                    <FullCalendar ref="calendarRef" :options="calendarOptions" />
                </div>
            </div>
        </div>
    </EmployeePortalLayout>
</template>

<style scoped>
:deep(.fc-button) { background-color: white; color: #475569; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 0.4rem 0.75rem; text-transform: capitalize; font-weight: 500; }
:deep(.fc-button-active) { background-color: #9b5de5 !important; color: white !important; border-color: #9b5de5 !important; }
:deep(.fc-event) { border-radius: 0.375rem; padding: 2px 4px; font-size: 0.75rem; cursor: pointer; }
</style>

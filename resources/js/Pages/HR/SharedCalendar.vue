<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import HRNav from '@/Components/HRNav.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
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
    isOrganizationOwner: { type: Boolean, default: false },
});

const calendarRef = ref(null);
const events = ref([]);
const loading = ref(false);

// Custom tooltip (no delay, replaces native title attribute)
const tooltip = ref({ visible: false, content: '', x: 0, y: 0 });

const escapeHtml = (str) => String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

const formatDate = (d) => {
    if (!d) return '';
    return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const positionTooltip = (jsEvent, el) => {
    const rect = el.getBoundingClientRect();
    // Place tooltip below the event by default, switch above if not enough room
    const tooltipHeight = 200; // approximate
    const willOverflowBottom = (rect.bottom + tooltipHeight) > window.innerHeight;

    tooltip.value.x = Math.min(window.innerWidth - 320, rect.left);
    tooltip.value.y = willOverflowBottom ? Math.max(10, rect.top - tooltipHeight) : rect.bottom + 6;
};

// Filters
const selectedEmployees = ref(props.employees.map(e => e.id));
const selectedTypes = ref(['meeting', 'training', 'team_building', 'deadline', 'other']);
const showLeaves = ref(true);
const showEvents = ref(true);

// Filter dropdowns
const openDropdown = ref(null);
const displayDropdownRef = ref(null);
const employeesDropdownRef = ref(null);

const handleClickOutside = (e) => {
    if (
        displayDropdownRef.value && !displayDropdownRef.value.contains(e.target)
        && employeesDropdownRef.value && !employeesDropdownRef.value.contains(e.target)
    ) {
        openDropdown.value = null;
    }
};

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));

const fetchEvents = async (info, successCallback, failureCallback) => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        params.append('start', info.startStr);
        params.append('end', info.endStr);
        selectedEmployees.value.forEach(id => params.append('employees[]', id));
        selectedTypes.value.forEach(t => params.append('types[]', t));

        const { data } = await axios.get(route('hr.shared-calendar.events') + '?' + params.toString());

        const combined = [];
        if (showEvents.value) {
            combined.push(...data.events.map(e => ({
                ...e,
                backgroundColor: e.color,
                borderColor: e.color,
            })));
        }
        if (showLeaves.value) {
            combined.push(...data.leaves.map(l => ({
                ...l,
                backgroundColor: l.color,
                borderColor: l.color,
                display: 'block',
            })));
        }
        events.value = combined;
        successCallback(combined);
    } catch (error) {
        console.error('Calendar fetch error:', error);
        failureCallback(error);
    } finally {
        loading.value = false;
    }
};

const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    locale: locale() || 'fr',
    firstDay: 1,
    height: 'auto',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: '',
    },
    buttonText: {
        today: t('hr_calendar_today'),
    },
    events: fetchEvents,
    eventClick: (info) => {
        if (info.event.id.startsWith('evt-')) {
            router.get(route('hr.events.show', info.event.id.replace('evt-', '')));
        }
    },
    eventMouseEnter: (info) => {
        const props = info.event.extendedProps;
        const lines = [];

        if (info.event.id?.startsWith('lv-')) {
            if (props.employee_name) lines.push(`<strong>${escapeHtml(props.employee_name)}</strong>`);
            if (props.leave_type) lines.push(escapeHtml(props.leave_type));
            if (props.days_count) lines.push(`${props.days_count} ${t('hr.days')}`);
            if (props.status_label) {
                const isPending = props.status === 'pending';
                const cls = isPending ? 'text-amber-300' : 'text-emerald-300';
                lines.push(`<span class="${cls}">[${escapeHtml(props.status_label)}]</span>`);
            }
            if (props.start_date && props.end_date) {
                lines.push(`<span class="text-slate-300">${formatDate(props.start_date)} → ${formatDate(props.end_date)}</span>`);
            }
            if (props.reason) lines.push(`<div class="mt-2 pt-2 border-t border-slate-600">${escapeHtml(t('hr.reason'))}: ${escapeHtml(props.reason)}</div>`);
        } else if (info.event.id?.startsWith('evt-')) {
            lines.push(`<strong>${escapeHtml(info.event.title)}</strong>`);
            const start = info.event.start ? new Date(info.event.start).toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' }) : null;
            const end = info.event.end ? new Date(info.event.end).toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' }) : null;
            if (start && end) lines.push(`<span class="text-slate-300">${start} → ${end}</span>`);
            if (props.location_type === 'room' && props.room) lines.push(`📍 ${escapeHtml(props.room)}`);
            else if (props.location_type === 'address' && props.address) lines.push(`📍 ${escapeHtml(props.address)}`);
            else if (props.location_type === 'video' && props.video_url) lines.push(`🎥 ${escapeHtml(props.video_url)}`);
            if (props.creator) lines.push(`${t('hr_event_creator')}: ${escapeHtml(props.creator)}`);
            if (props.participants_count) lines.push(`${t('hr_event_participants')}: ${props.participants_count}`);
            if (props.description) lines.push(`<div class="mt-2 pt-2 border-t border-slate-600">${escapeHtml(props.description)}</div>`);
        }

        tooltip.value.content = lines.join('<br>');
        tooltip.value.visible = true;
        positionTooltip(info.jsEvent, info.el);
    },
    eventMouseLeave: () => {
        tooltip.value.visible = false;
    },
    dateClick: (info) => {
        router.get(route('hr.events.create'), { date: info.dateStr });
    },
    dayMaxEvents: 4,
    moreLinkText: (n) => `+${n}`,
    fixedWeekCount: false,
}));

const refresh = () => {
    if (calendarRef.value) {
        calendarRef.value.getApi().refetchEvents();
    }
};

const toggleEmployee = (id) => {
    if (selectedEmployees.value.includes(id)) {
        selectedEmployees.value = selectedEmployees.value.filter(e => e !== id);
    } else {
        selectedEmployees.value.push(id);
    }
    refresh();
};

const selectAllEmployees = () => {
    selectedEmployees.value = props.employees.map(e => e.id);
    refresh();
};

const clearEmployees = () => {
    selectedEmployees.value = [];
    refresh();
};
</script>

<template>
    <Head :title="t('hr_calendar_title')" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                {{ t('hr_calendar_title') }}
            </h1>
        </template>
        <template #header-actions>
            <Link
                :href="route('hr.events.create')"
                class="inline-flex items-center gap-1.5 rounded-xl bg-primary-500 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-600"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                {{ t('hr_event_new') }}
            </Link>
            <Link
                :href="route('hr.leaves.index', { new: 1 })"
                class="inline-flex items-center gap-1.5 rounded-xl bg-white px-3 py-2 text-sm font-semibold text-emerald-600 shadow-sm ring-1 ring-inset ring-emerald-200 hover:bg-emerald-50 dark:bg-gray-800 dark:text-emerald-400 dark:ring-emerald-700"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                {{ t('hr.add_leave') }}
            </Link>
            <Link
                v-if="isOrganizationOwner"
                :href="route('hr.rooms.index')"
                class="inline-flex items-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-slate-300 dark:ring-slate-600"
            >
                {{ t('hr_room_manage') }}
            </Link>
        </template>

        <HRNav class="mb-6" />

        <div v-if="!sharedCalendarEnabled" class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="rounded-2xl bg-yellow-50 border border-yellow-200 p-6 text-center">
                <h2 class="text-lg font-semibold text-yellow-800">{{ t('hr_calendar_disabled_title') }}</h2>
                <p class="mt-2 text-yellow-700">{{ t('hr_calendar_disabled_description') }}</p>
            </div>
        </div>

        <div v-else class="py-6">
            <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-8">
                <!-- Filters bar (top, horizontal) -->
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <!-- Display dropdown -->
                    <div class="relative" ref="displayDropdownRef">
                        <button
                            @click="openDropdown = openDropdown === 'display' ? null : 'display'"
                            class="inline-flex items-center gap-2 rounded-xl bg-white dark:bg-gray-800 px-3 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 shadow-sm ring-1 ring-inset ring-gray-200 dark:ring-slate-600 hover:bg-gray-50"
                        >
                            <span>{{ t('hr_calendar_filter_display') }}</span>
                            <span class="px-1.5 py-0.5 rounded bg-primary-100 text-primary-700 text-xs dark:bg-primary-900/30 dark:text-primary-300">
                                {{ (showEvents ? 1 : 0) + (showLeaves ? 1 : 0) }}/2
                            </span>
                            <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': openDropdown === 'display' }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div v-if="openDropdown === 'display'" class="absolute z-20 mt-2 w-56 rounded-xl bg-white dark:bg-surface-card shadow-lg ring-1 ring-black ring-opacity-5 dark:ring-slate-700 p-3 space-y-2">
                            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 rounded px-2 py-1.5">
                                <input type="checkbox" v-model="showEvents" @change="refresh" class="rounded text-primary-500" />
                                {{ t('hr_calendar_filter_events') }}
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 rounded px-2 py-1.5">
                                <input type="checkbox" v-model="showLeaves" @change="refresh" class="rounded text-primary-500" />
                                {{ t('hr_calendar_filter_leaves') }}
                            </label>
                        </div>
                    </div>

                    <!-- Employees dropdown -->
                    <div class="relative" ref="employeesDropdownRef">
                        <button
                            @click="openDropdown = openDropdown === 'employees' ? null : 'employees'"
                            class="inline-flex items-center gap-2 rounded-xl bg-white dark:bg-gray-800 px-3 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 shadow-sm ring-1 ring-inset ring-gray-200 dark:ring-slate-600 hover:bg-gray-50"
                        >
                            <span>{{ t('hr_calendar_filter_employees') }}</span>
                            <span class="px-1.5 py-0.5 rounded bg-primary-100 text-primary-700 text-xs dark:bg-primary-900/30 dark:text-primary-300">
                                {{ selectedEmployees.length }}/{{ employees.length }}
                            </span>
                            <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': openDropdown === 'employees' }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div v-if="openDropdown === 'employees'" class="absolute z-20 mt-2 w-72 rounded-xl bg-white dark:bg-surface-card shadow-lg ring-1 ring-black ring-opacity-5 dark:ring-slate-700 p-3">
                            <div class="flex items-center justify-between pb-2 mb-2 border-b border-gray-100 dark:border-gray-700">
                                <button @click="selectAllEmployees" class="text-xs font-medium text-primary-500 hover:underline">{{ t('hr_calendar_filter_all') }}</button>
                                <button @click="clearEmployees" class="text-xs font-medium text-slate-400 hover:underline">{{ t('hr_calendar_filter_none') }}</button>
                            </div>
                            <div class="space-y-1 max-h-72 overflow-y-auto pr-1">
                                <label
                                    v-for="emp in employees"
                                    :key="emp.id"
                                    class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 rounded px-2 py-1.5"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="selectedEmployees.includes(emp.id)"
                                        @change="toggleEmployee(emp.id)"
                                        class="rounded text-primary-500"
                                    />
                                    <span class="truncate">{{ emp.first_name }} {{ emp.last_name }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calendar (full width) -->
                <div class="bg-white dark:bg-surface-card rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                    <FullCalendar ref="calendarRef" :options="calendarOptions" />
                </div>
            </div>
        </div>

        <!-- Custom tooltip (instant, no delay) -->
        <Teleport to="body">
            <div
                v-show="tooltip.visible"
                class="fixed z-50 max-w-xs px-3 py-2 text-xs text-white bg-slate-800 dark:bg-slate-900 rounded-lg shadow-xl pointer-events-none whitespace-normal leading-relaxed"
                :style="{ left: tooltip.x + 'px', top: tooltip.y + 'px' }"
                v-html="tooltip.content"
            />
        </Teleport>
    </AppLayout>
</template>

<style scoped>
:deep(.fc) {
    font-family: inherit;
}
:deep(.fc-button) {
    background-color: white;
    color: #475569;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 0.4rem 0.75rem;
    text-transform: capitalize;
    font-weight: 500;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
}
:deep(.fc-button-primary:hover) {
    background-color: #f9fafb;
    color: #1e293b;
}
:deep(.fc-button-active) {
    background-color: #9b5de5 !important;
    color: white !important;
    border-color: #9b5de5 !important;
}
:deep(.fc-event) {
    border-radius: 0.375rem;
    padding: 2px 4px;
    font-size: 0.75rem;
    cursor: pointer;
}
:deep(.fc-event-pending) {
    background-image: repeating-linear-gradient(
        45deg,
        transparent,
        transparent 4px,
        rgba(255, 255, 255, 0.25) 4px,
        rgba(255, 255, 255, 0.25) 8px
    ) !important;
    opacity: 0.85;
    font-style: italic;
}
</style>

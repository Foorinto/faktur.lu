<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    entries: Object,
    runningTimer: Object,
    summary: Object,
    filters: Object,
    clients: Array,
    projects: Array,
    periods: Array,
    draftInvoices: Array,
    defaultHourlyRate: [Number, String],
    isVatExempt: Boolean,
});

const defaultVatRate = props.isVatExempt ? 0 : 17;

const clientFilter = ref(props.filters.client_id || '');
const billedFilter = ref(props.filters.billed || '');
const periodFilter = ref(props.filters.period || '');

const selectedEntries = ref([]);
const showConvertModal = ref(false);
const showAddToInvoiceModal = ref(false);
const selectedEntryForInvoice = ref(null);

// Timer form
const timerForm = useForm({
    client_id: '',
    project_id: '',
    task_id: '',
    project_name: '',
    description: '',
});

// Manual entry form
const manualForm = useForm({
    client_id: '',
    project_id: '',
    task_id: '',
    project_name: '',
    description: '',
    date: new Date().toISOString().split('T')[0],
    duration: '',
});

// Projects filtered by selected client for timer
const timerProjects = computed(() => {
    if (!timerForm.client_id) return [];
    return props.projects.filter(p => p.client_id === timerForm.client_id || !p.client_id);
});

// Tasks for selected project in timer
const timerTasks = computed(() => {
    if (!timerForm.project_id) return [];
    const project = props.projects.find(p => p.id === timerForm.project_id);
    return project?.tasks || [];
});

// Projects filtered by selected client for manual form
const manualProjects = computed(() => {
    if (!manualForm.client_id) return [];
    return props.projects.filter(p => p.client_id === manualForm.client_id || !p.client_id);
});

// Tasks for selected project in manual form
const manualTasks = computed(() => {
    if (!manualForm.project_id) return [];
    const project = props.projects.find(p => p.id === manualForm.project_id);
    return project?.tasks || [];
});

// Reset project/task when client changes
watch(() => timerForm.client_id, () => {
    timerForm.project_id = '';
    timerForm.task_id = '';
});

watch(() => timerForm.project_id, () => {
    timerForm.task_id = '';
});

watch(() => manualForm.client_id, () => {
    manualForm.project_id = '';
    manualForm.task_id = '';
});

watch(() => manualForm.project_id, () => {
    manualForm.task_id = '';
});

// Convert to invoice form
const convertForm = useForm({
    time_entry_ids: [],
    hourly_rate: 100,
    vat_rate: defaultVatRate,
    group_by_project: true,
});

// Add to existing invoice form
const addToInvoiceForm = useForm({
    invoice_id: '',
    hourly_rate: '',
    vat_rate: defaultVatRate,
});

// Live timer display
const currentTimerSeconds = ref(0);
const stoppingTimer = ref(false);
const timerDescriptionRef = ref(null);
let timerInterval = null;
let timerStartedAt = null;

// Auto-resize textarea
const autoResizeTextarea = (event) => {
    const textarea = event.target;
    textarea.style.height = 'auto';
    textarea.style.height = Math.min(textarea.scrollHeight, 128) + 'px';
};

// Calculate elapsed time from server's started_at
const calculateElapsedSeconds = () => {
    if (timerStartedAt) {
        return Math.floor((Date.now() - timerStartedAt) / 1000);
    }
    return 0;
};

const startLocalTimer = () => {
    // Clear any existing interval
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }

    if (props.runningTimer && props.runningTimer.started_at) {
        // Use the server's started_at time to calculate elapsed time
        timerStartedAt = new Date(props.runningTimer.started_at).getTime();
        currentTimerSeconds.value = calculateElapsedSeconds();

        timerInterval = setInterval(() => {
            currentTimerSeconds.value = calculateElapsedSeconds();
        }, 1000);
    }
};

const stopLocalTimer = () => {
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
    timerStartedAt = null;
    currentTimerSeconds.value = 0;
};

onMounted(() => {
    if (props.runningTimer) {
        startLocalTimer();
    }
});

onUnmounted(() => {
    stopLocalTimer();
});

// Watch for changes in runningTimer prop (e.g., after page refresh via Inertia)
watch(() => props.runningTimer, (newTimer, oldTimer) => {
    if (newTimer && !oldTimer) {
        // Timer started
        startLocalTimer();
    } else if (!newTimer && oldTimer) {
        // Timer stopped
        stopLocalTimer();
    } else if (newTimer && oldTimer && newTimer.id !== oldTimer.id) {
        // Different timer - restart local timer
        startLocalTimer();
    }
}, { deep: true });

const formattedTimerDuration = computed(() => {
    const hours = Math.floor(currentTimerSeconds.value / 3600);
    const minutes = Math.floor((currentTimerSeconds.value % 3600) / 60);
    const seconds = currentTimerSeconds.value % 60;
    return `${hours}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
});

const updateFilters = () => {
    router.get(route('time-entries.index'), {
        client_id: clientFilter.value || undefined,
        billed: billedFilter.value || undefined,
        period: periodFilter.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

watch([clientFilter, billedFilter, periodFilter], updateFilters);

const startTimer = () => {
    if (timerForm.processing) return;

    timerForm.post(route('time-entries.start'), {
        preserveScroll: true,
        onSuccess: () => {
            timerForm.reset();
            // Local timer will be started by the watcher when props.runningTimer updates
        },
    });
};

const stopTimer = () => {
    if (!props.runningTimer || stoppingTimer.value) return;

    stoppingTimer.value = true;

    router.post(route('time-entries.stop', props.runningTimer.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            stopLocalTimer();
        },
        onFinish: () => {
            stoppingTimer.value = false;
        },
    });
};

const addManualEntry = () => {
    manualForm.post(route('time-entries.store'), {
        preserveScroll: true,
        onSuccess: () => {
            manualForm.reset();
            manualForm.date = new Date().toISOString().split('T')[0];
        },
    });
};

const deleteEntry = (entry) => {
    if (confirm(t('delete_time_entry'))) {
        router.delete(route('time-entries.destroy', entry.id), {
            preserveScroll: true,
        });
    }
};

const toggleEntrySelection = (entryId) => {
    const index = selectedEntries.value.indexOf(entryId);
    if (index === -1) {
        selectedEntries.value.push(entryId);
    } else {
        selectedEntries.value.splice(index, 1);
    }
};

const toggleAllEntries = () => {
    const unbilledEntries = props.entries.data.filter(e => !e.is_billed);
    if (selectedEntries.value.length === unbilledEntries.length) {
        selectedEntries.value = [];
    } else {
        selectedEntries.value = unbilledEntries.map(e => e.id);
    }
};

const openConvertModal = () => {
    if (selectedEntries.value.length === 0) {
        alert(t('select_at_least_one'));
        return;
    }

    // Get hourly rate from first selected entry's client
    const firstEntry = props.entries.data.find(e => e.id === selectedEntries.value[0]);
    if (firstEntry && firstEntry.client) {
        convertForm.hourly_rate = firstEntry.client.default_hourly_rate || 100;
    }

    showConvertModal.value = true;
};

const convertToInvoice = () => {
    convertForm.time_entry_ids = selectedEntries.value;
    convertForm.post(route('time-entries.to-invoice'), {
        onSuccess: () => {
            showConvertModal.value = false;
            selectedEntries.value = [];
        },
    });
};

// Filter draft invoices for the selected entry's client
const availableInvoicesForEntry = computed(() => {
    if (!selectedEntryForInvoice.value) return [];
    return props.draftInvoices.filter(
        inv => inv.client_id === selectedEntryForInvoice.value.client_id
    );
});

const openAddToInvoiceModal = (entry) => {
    selectedEntryForInvoice.value = entry;
    addToInvoiceForm.reset();
    addToInvoiceForm.vat_rate = defaultVatRate;

    // Set hourly rate: entry rate > client rate > global rate
    const client = props.clients.find(c => c.id === entry.client_id);
    addToInvoiceForm.hourly_rate = entry.hourly_rate
        || client?.default_hourly_rate
        || props.defaultHourlyRate
        || 0;

    // Pre-select first available invoice if any
    const available = props.draftInvoices.filter(inv => inv.client_id === entry.client_id);
    if (available.length > 0) {
        addToInvoiceForm.invoice_id = available[0].id;
    }

    showAddToInvoiceModal.value = true;
};

const addToInvoice = () => {
    if (!selectedEntryForInvoice.value) return;

    addToInvoiceForm.post(route('time-entries.add-to-invoice', selectedEntryForInvoice.value.id), {
        onSuccess: () => {
            showAddToInvoiceModal.value = false;
            selectedEntryForInvoice.value = null;
        },
    });
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR');
};

const formatTime = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <Head :title="t('time_tracking')" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ t('time_tracking') }}
                </h1>
                <Link
                    :href="route('time-entries.summary')"
                    class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600"
                >
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M15.5 2A1.5 1.5 0 0014 3.5v13a1.5 1.5 0 001.5 1.5h1a1.5 1.5 0 001.5-1.5v-13A1.5 1.5 0 0016.5 2h-1zM9.5 6A1.5 1.5 0 008 7.5v9A1.5 1.5 0 009.5 18h1a1.5 1.5 0 001.5-1.5v-9A1.5 1.5 0 0010.5 6h-1zM3.5 10A1.5 1.5 0 002 11.5v5A1.5 1.5 0 003.5 18h1A1.5 1.5 0 006 16.5v-5A1.5 1.5 0 004.5 10h-1z" />
                    </svg>
                    {{ t('reports') }}
                </Link>
            </div>
        </template>

        <!-- Running Timer Section -->
        <div class="mb-6 overflow-hidden rounded-2xl bg-gradient-to-r from-primary-500 to-purple-600 shadow-lg">
            <div class="px-6 py-5">
                <div v-if="runningTimer" class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-primary-100">{{ t('running_timer') }}</p>
                        <p class="mt-1 text-3xl font-bold text-white">{{ formattedTimerDuration }}</p>
                        <p class="mt-1 text-sm text-primary-200">
                            {{ runningTimer.client?.name }} - {{ runningTimer.project?.title || runningTimer.project_name || t('no_project') }}
                            <span v-if="runningTimer.task" class="text-primary-300">/ {{ runningTimer.task.title }}</span>
                        </p>
                    </div>
                    <button
                        @click="stopTimer"
                        :disabled="stoppingTimer"
                        class="inline-flex items-center rounded-full bg-white/20 px-6 py-3 text-sm font-semibold text-white backdrop-blur hover:bg-white/30 focus:outline-none focus:ring-2 focus:ring-white disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg v-if="stoppingTimer" class="mr-2 h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="mr-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.5 3.5A1.5 1.5 0 017 5v10a1.5 1.5 0 01-3 0V5a1.5 1.5 0 011.5-1.5zm8 0A1.5 1.5 0 0115 5v10a1.5 1.5 0 01-3 0V5a1.5 1.5 0 011.5-1.5z" clip-rule="evenodd" />
                        </svg>
                        {{ stoppingTimer ? t('stopping') : t('stop') }}
                    </button>
                </div>

                <div v-else>
                    <p class="mb-4 text-sm font-medium text-primary-100">{{ t('start_new_timer') }}</p>
                    <form @submit.prevent="startTimer" class="flex flex-wrap gap-3">
                        <select
                            v-model="timerForm.client_id"
                            required
                            class="rounded-xl border-0 bg-white/10 py-2 pl-3 pr-10 text-white placeholder-primary-200 backdrop-blur focus:ring-2 focus:ring-white sm:text-sm"
                        >
                            <option value="" class="text-slate-900">{{ t('select_client') }}</option>
                            <option v-for="client in clients" :key="client.id" :value="client.id" class="text-slate-900">
                                {{ client.name }}
                            </option>
                        </select>
                        <select
                            v-if="timerProjects.length > 0"
                            v-model="timerForm.project_id"
                            class="rounded-xl border-0 bg-white/10 py-2 pl-3 pr-10 text-white placeholder-primary-200 backdrop-blur focus:ring-2 focus:ring-white sm:text-sm"
                        >
                            <option value="" class="text-slate-900">{{ t('project') }} ({{ t('optional') }})</option>
                            <option v-for="project in timerProjects" :key="project.id" :value="project.id" class="text-slate-900">
                                {{ project.title }}
                            </option>
                        </select>
                        <input
                            v-else
                            v-model="timerForm.project_name"
                            type="text"
                            :placeholder="t('project_name')"
                            class="rounded-xl border-0 bg-white/10 py-2 px-3 text-white placeholder-primary-200 backdrop-blur focus:ring-2 focus:ring-white sm:text-sm"
                        />
                        <select
                            v-if="timerTasks.length > 0"
                            v-model="timerForm.task_id"
                            class="rounded-xl border-0 bg-white/10 py-2 pl-3 pr-10 text-white placeholder-primary-200 backdrop-blur focus:ring-2 focus:ring-white sm:text-sm"
                        >
                            <option value="" class="text-slate-900">{{ t('task') }} ({{ t('optional') }})</option>
                            <option v-for="task in timerTasks" :key="task.id" :value="task.id" class="text-slate-900">
                                {{ task.title }}
                            </option>
                        </select>
                        <textarea
                            v-model="timerForm.description"
                            :placeholder="`${t('description')} (${t('optional')})`"
                            rows="1"
                            @input="autoResizeTextarea"
                            ref="timerDescriptionRef"
                            class="flex-1 rounded-xl border-0 bg-white/10 py-2 px-3 text-white placeholder-primary-200 backdrop-blur focus:ring-2 focus:ring-white sm:text-sm resize-y min-h-[38px] max-h-32"
                        ></textarea>
                        <button
                            type="submit"
                            :disabled="timerForm.processing || !timerForm.client_id"
                            class="inline-flex items-center rounded-full bg-white px-6 py-2 text-sm font-semibold text-primary-600 shadow-sm hover:bg-primary-50 disabled:opacity-50"
                        >
                            <svg class="mr-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                            </svg>
                            {{ t('start') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800 px-4 py-5">
                <dt class="truncate text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('total_time') }}</dt>
                <dd class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white">
                    {{ summary.total_formatted }}
                </dd>
            </div>
            <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800 px-4 py-5">
                <dt class="truncate text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('unbilled') }}</dt>
                <dd class="mt-1 text-2xl font-semibold text-amber-600 dark:text-amber-400">
                    {{ summary.unbilled_formatted }}
                </dd>
            </div>
            <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800 px-4 py-5">
                <dt class="truncate text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('selected') }}</dt>
                <dd class="mt-1 text-2xl font-semibold text-primary-600 dark:text-primary-400">
                    {{ selectedEntries.length }}
                </dd>
            </div>
        </div>

        <!-- Manual Entry Form -->
        <div class="mb-6 overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-sm font-medium text-slate-900 dark:text-white">{{ t('add_manual_entry') }}</h3>
            </div>
            <form @submit.prevent="addManualEntry" class="px-6 py-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-6">
                    <div class="sm:col-span-1">
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300">{{ t('date') }}</label>
                        <input
                            v-model="manualForm.date"
                            type="date"
                            required
                            class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white sm:text-sm"
                        />
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300">{{ t('client') }}</label>
                        <select
                            v-model="manualForm.client_id"
                            required
                            class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white sm:text-sm"
                        >
                            <option value="">{{ t('select') }}</option>
                            <option v-for="client in clients" :key="client.id" :value="client.id">
                                {{ client.name }}
                            </option>
                        </select>
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300">{{ t('project') }}</label>
                        <select
                            v-if="manualProjects.length > 0"
                            v-model="manualForm.project_id"
                            class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white sm:text-sm"
                        >
                            <option value="">{{ t('select') }}</option>
                            <option v-for="project in manualProjects" :key="project.id" :value="project.id">
                                {{ project.title }}
                            </option>
                        </select>
                        <input
                            v-else
                            v-model="manualForm.project_name"
                            type="text"
                            :placeholder="t('project_name')"
                            class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white sm:text-sm"
                        />
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300">{{ t('duration_hhmm') }}</label>
                        <input
                            v-model="manualForm.duration"
                            type="text"
                            required
                            placeholder="1:30"
                            pattern="\d+:\d{2}"
                            class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white sm:text-sm"
                        />
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300">{{ t('description') }}</label>
                        <input
                            v-model="manualForm.description"
                            type="text"
                            :placeholder="t('description')"
                            class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white sm:text-sm"
                        />
                    </div>
                    <div class="sm:col-span-1 flex items-end">
                        <button
                            type="submit"
                            :disabled="manualForm.processing"
                            class="w-full inline-flex justify-center items-center rounded-xl bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 disabled:opacity-50"
                        >
                            {{ t('add') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Filters & Actions -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap gap-4">
                <select
                    v-model="clientFilter"
                    class="rounded-xl border-0 py-1.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-primary-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600 sm:text-sm"
                >
                    <option value="">{{ t('all_clients') }}</option>
                    <option v-for="client in clients" :key="client.id" :value="client.id">
                        {{ client.name }}
                    </option>
                </select>

                <select
                    v-model="billedFilter"
                    class="rounded-xl border-0 py-1.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-primary-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600 sm:text-sm"
                >
                    <option value="">{{ t('all_statuses') }}</option>
                    <option value="0">{{ t('unbilled') }}</option>
                    <option value="1">{{ t('billed') }}</option>
                </select>

                <select
                    v-model="periodFilter"
                    class="rounded-xl border-0 py-1.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-primary-600 dark:bg-slate-800 dark:text-white dark:ring-slate-600 sm:text-sm"
                >
                    <option value="">{{ t('all_periods') }}</option>
                    <option v-for="period in periods" :key="period.value" :value="period.value">
                        {{ period.label }}
                    </option>
                </select>
            </div>

            <button
                v-if="selectedEntries.length > 0"
                @click="openConvertModal"
                class="inline-flex items-center rounded-xl bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500"
            >
                <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 003 3.5v13A1.5 1.5 0 004.5 18h11a1.5 1.5 0 001.5-1.5V7.621a1.5 1.5 0 00-.44-1.06l-4.12-4.122A1.5 1.5 0 0011.378 2H4.5zm4.75 6.75a.75.75 0 011.5 0v2.546l.943-1.048a.75.75 0 011.114 1.004l-2.25 2.5a.75.75 0 01-1.114 0l-2.25-2.5a.75.75 0 111.114-1.004l.943 1.048V8.75z" clip-rule="evenodd" />
                </svg>
                {{ t('convert_to_invoice') }} ({{ selectedEntries.length }})
            </button>
        </div>

        <!-- Time Entries Table -->
        <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700">
                    <tr>
                        <th class="py-3.5 pl-4 pr-3 sm:pl-6">
                            <input
                                type="checkbox"
                                :checked="selectedEntries.length > 0 && selectedEntries.length === entries.data.filter(e => !e.is_billed).length"
                                @change="toggleAllEntries"
                                class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                            />
                        </th>
                        <th class="py-3.5 px-3 text-left text-sm font-semibold text-slate-900 dark:text-white">
                            {{ t('date') }}
                        </th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white">
                            {{ t('client_project') }}
                        </th>
                        <th class="hidden px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white md:table-cell">
                            {{ t('description') }}
                        </th>
                        <th class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900 dark:text-white">
                            {{ t('duration') }}
                        </th>
                        <th class="hidden px-3 py-3.5 text-right text-sm font-semibold text-slate-900 dark:text-white lg:table-cell">
                            {{ t('amount') }}
                        </th>
                        <th class="px-3 py-3.5 text-center text-sm font-semibold text-slate-900 dark:text-white">
                            {{ t('status') }}
                        </th>
                        <th class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                            <span class="sr-only">{{ t('actions') }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-800">
                    <tr v-if="entries.data.length === 0">
                        <td colspan="8" class="py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="mt-2">{{ t('no_time_entries') }}</p>
                            <p class="mt-1 text-xs">{{ t('start_new_timer') }}</p>
                        </td>
                    </tr>
                    <tr v-for="entry in entries.data" :key="entry.id" class="hover:bg-slate-50 dark:hover:bg-slate-700">
                        <td class="py-4 pl-4 pr-3 sm:pl-6">
                            <input
                                v-if="!entry.is_billed"
                                type="checkbox"
                                :checked="selectedEntries.includes(entry.id)"
                                @change="toggleEntrySelection(entry.id)"
                                class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                            />
                        </td>
                        <td class="whitespace-nowrap py-4 px-3 text-sm text-slate-500 dark:text-slate-400">
                            <div>{{ formatDate(entry.started_at) }}</div>
                            <div class="text-xs">{{ formatTime(entry.started_at) }} - {{ formatTime(entry.stopped_at) }}</div>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4">
                            <div class="font-medium text-slate-900 dark:text-white">
                                {{ entry.client?.name || t('unknown_client') }}
                            </div>
                            <div class="text-sm text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                <span
                                    v-if="entry.project?.color"
                                    class="inline-block h-2 w-2 rounded-full"
                                    :style="{ backgroundColor: entry.project.color }"
                                ></span>
                                {{ entry.project?.title || entry.project_name || t('no_project') }}
                                <span v-if="entry.task" class="text-slate-400 dark:text-slate-500">/ {{ entry.task.title }}</span>
                            </div>
                        </td>
                        <td class="hidden px-3 py-4 text-sm text-slate-500 dark:text-slate-400 md:table-cell">
                            <span class="truncate max-w-xs block">{{ entry.description || '-' }}</span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-right text-sm font-mono font-medium text-slate-900 dark:text-white">
                            {{ entry.duration_formatted }}
                        </td>
                        <td class="hidden whitespace-nowrap px-3 py-4 text-right text-sm text-slate-500 dark:text-slate-400 lg:table-cell">
                            {{ entry.amount ? formatCurrency(entry.amount) : '-' }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-center text-sm">
                            <span
                                :class="[
                                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                    entry.is_billed
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                        : 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200'
                                ]"
                            >
                                {{ entry.is_billed ? t('billed') : t('unbilled') }}
                            </span>
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                            <div v-if="!entry.is_billed" class="flex items-center justify-end gap-2">
                                <button
                                    v-if="draftInvoices.some(inv => inv.client_id === entry.client_id)"
                                    @click="openAddToInvoiceModal(entry)"
                                    class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300"
                                    :title="t('add_to_invoice')"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                                    </svg>
                                </button>
                                <button
                                    @click="deleteEntry(entry)"
                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                    :title="t('delete')"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.519.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="entries.links && entries.links.length > 3" class="mt-6 flex items-center justify-between">
            <div class="text-sm text-slate-700 dark:text-slate-400">
                {{ t('showing') }} {{ entries.from }} {{ t('to') }} {{ entries.to }} {{ t('of') }} {{ entries.total }} {{ t('results') }}
            </div>
            <nav class="isolate inline-flex -space-x-px rounded-xl shadow-sm">
                <template v-for="(link, index) in entries.links" :key="index">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        :class="[
                            link.active
                                ? 'z-10 bg-primary-600 text-white'
                                : 'text-slate-900 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 dark:text-slate-300 dark:ring-slate-600 dark:hover:bg-slate-700',
                            'relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20',
                            index === 0 ? 'rounded-l-md' : '',
                            index === entries.links.length - 1 ? 'rounded-r-md' : '',
                        ]"
                        v-html="link.label"
                        preserve-scroll
                    />
                </template>
            </nav>
        </div>

        <!-- Convert to Invoice Modal -->
        <div v-if="showConvertModal" class="relative z-50">
            <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity"></div>
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div class="relative transform overflow-hidden rounded-2xl bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all dark:bg-slate-800 sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                                {{ t('convert_to_invoice') }}
                            </h3>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                                {{ selectedEntries.length }} {{ t('selected') }}
                            </p>
                        </div>

                        <form @submit.prevent="convertToInvoice" class="mt-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    {{ t('hourly_rate') }}
                                </label>
                                <div class="mt-1 relative rounded-xl shadow-sm">
                                    <input
                                        v-model="convertForm.hourly_rate"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        required
                                        class="block w-full rounded-xl border-slate-300 pr-12 focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white sm:text-sm"
                                    />
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <span class="text-slate-500 dark:text-slate-400 sm:text-sm">EUR/h</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    {{ t('vat_rate') }}
                                </label>
                                <select
                                    v-if="!isVatExempt"
                                    v-model="convertForm.vat_rate"
                                    class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white sm:text-sm"
                                >
                                    <option :value="0">0%</option>
                                    <option :value="3">3%</option>
                                    <option :value="8">8%</option>
                                    <option :value="14">14%</option>
                                    <option :value="17">17%</option>
                                </select>
                                <div
                                    v-else
                                    class="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 text-slate-500 dark:border-slate-600 dark:bg-slate-600 dark:text-slate-400 sm:text-sm"
                                >
                                    0%
                                </div>
                            </div>

                            <div class="flex items-center">
                                <input
                                    v-model="convertForm.group_by_project"
                                    type="checkbox"
                                    id="group_by_project"
                                    class="h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                                />
                                <label for="group_by_project" class="ml-2 block text-sm text-slate-700 dark:text-slate-300">
                                    {{ t('group_by_project') }}
                                </label>
                            </div>

                            <div class="mt-6 flex justify-end space-x-3">
                                <button
                                    type="button"
                                    @click="showConvertModal = false"
                                    class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600"
                                >
                                    {{ t('cancel') }}
                                </button>
                                <button
                                    type="submit"
                                    :disabled="convertForm.processing"
                                    class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 disabled:opacity-50"
                                >
                                    {{ t('create_invoice') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add to Invoice Modal -->
        <div v-if="showAddToInvoiceModal" class="relative z-50">
            <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity"></div>
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div class="relative transform overflow-hidden rounded-2xl bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all dark:bg-slate-800 sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                                {{ t('add_to_invoice') }}
                            </h3>
                            <p v-if="selectedEntryForInvoice" class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                                {{ selectedEntryForInvoice.client?.name }} - {{ selectedEntryForInvoice.duration_formatted }}
                            </p>
                        </div>

                        <form @submit.prevent="addToInvoice" class="mt-6 space-y-4">
                            <div v-if="availableInvoicesForEntry.length === 0" class="rounded-xl bg-amber-50 p-4 dark:bg-amber-900/20">
                                <p class="text-sm text-amber-800 dark:text-amber-200">
                                    {{ t('no_draft_invoice') }}
                                </p>
                                <Link
                                    :href="route('invoices.create')"
                                    class="mt-2 inline-flex text-sm font-medium text-amber-800 hover:text-amber-700 dark:text-amber-200"
                                >
                                    {{ t('create_new_invoice') }}
                                </Link>
                            </div>

                            <div v-else>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                    {{ t('invoice') }}
                                </label>
                                <select
                                    v-model="addToInvoiceForm.invoice_id"
                                    required
                                    class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white sm:text-sm"
                                >
                                    <option value="">{{ t('select') }}</option>
                                    <option v-for="invoice in availableInvoicesForEntry" :key="invoice.id" :value="invoice.id">
                                        {{ invoice.title || invoice.display_number }} - {{ invoice.created_at }} ({{ formatCurrency(invoice.total_ht) }} {{ t('ht') }})
                                    </option>
                                </select>
                            </div>

                            <div v-if="availableInvoicesForEntry.length > 0" class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        {{ t('hourly_rate') }}
                                    </label>
                                    <div class="mt-1 relative rounded-xl shadow-sm">
                                        <input
                                            v-model="addToInvoiceForm.hourly_rate"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            required
                                            class="block w-full rounded-xl border-slate-300 pr-12 focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white sm:text-sm"
                                        />
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                            <span class="text-slate-500 dark:text-slate-400 sm:text-sm">€/h</span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                        {{ t('vat_rate') }}
                                    </label>
                                    <select
                                        v-if="!isVatExempt"
                                        v-model="addToInvoiceForm.vat_rate"
                                        class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700 dark:text-white sm:text-sm"
                                    >
                                        <option :value="0">0%</option>
                                        <option :value="3">3%</option>
                                        <option :value="8">8%</option>
                                        <option :value="14">14%</option>
                                        <option :value="17">17%</option>
                                    </select>
                                    <div
                                        v-else
                                        class="mt-1 block w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 text-slate-500 dark:border-slate-600 dark:bg-slate-600 dark:text-slate-400 sm:text-sm"
                                    >
                                        0%
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end space-x-3">
                                <button
                                    type="button"
                                    @click="showAddToInvoiceModal = false; selectedEntryForInvoice = null;"
                                    class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600"
                                >
                                    {{ t('cancel') }}
                                </button>
                                <button
                                    v-if="availableInvoicesForEntry.length > 0"
                                    type="submit"
                                    :disabled="addToInvoiceForm.processing || !addToInvoiceForm.invoice_id"
                                    class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 disabled:opacity-50"
                                >
                                    {{ t('add') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import HRNav from '@/Components/HRNav.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useAvatarColor } from '@/Composables/useAvatarColor';

const { t } = useTranslations();
const { getAvatarClasses } = useAvatarColor();

const props = defineProps({
    leaveRequests: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusCounts: { type: Object, default: () => ({}) },
    employees: { type: Array, default: () => [] },
    leaveTypes: { type: Array, default: () => [] },
    pendingDays: { type: Object, default: () => ({}) },
});

const statusFilter = ref(props.filters.status || '');
const employeeFilter = ref(props.filters.employee || '');

const totalCount = computed(() => {
    return Object.values(props.statusCounts).reduce((sum, c) => sum + c, 0);
});

const statusTabs = computed(() => [
    { value: '', label: t('hr.all'), count: totalCount.value },
    { value: 'pending', label: t('hr.leave_status_pending'), count: props.statusCounts.pending || 0 },
    { value: 'approved', label: t('hr.leave_status_approved'), count: props.statusCounts.approved || 0 },
    { value: 'rejected', label: t('hr.leave_status_rejected'), count: props.statusCounts.rejected || 0 },
    { value: 'cancelled', label: t('hr.leave_status_cancelled'), count: props.statusCounts.cancelled || 0 },
]);

const setStatus = (status) => {
    statusFilter.value = status;
    router.get(route('hr.leaves.index'), {
        status: status || undefined,
        employee: employeeFilter.value || undefined,
    }, { preserveState: true, replace: true });
};

const filterByEmployee = () => {
    router.get(route('hr.leaves.index'), {
        status: statusFilter.value || undefined,
        employee: employeeFilter.value || undefined,
    }, { preserveState: true, replace: true });
};

const getStatusClass = (status) => {
    const classes = {
        pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        rejected: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
        cancelled: 'bg-slate-100 text-slate-700 dark:bg-gray-800 dark:text-slate-300',
    };
    return classes[status] || classes.pending;
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('fr-FR');
};

// Actions
const approveRequest = (id) => {
    router.patch(route('hr.leaves.approve', id));
};

const showRejectModal = ref(false);
const rejectingId = ref(null);
const rejectForm = useForm({ rejection_reason: '' });

const openReject = (id) => {
    rejectingId.value = id;
    rejectForm.rejection_reason = '';
    showRejectModal.value = true;
};

const submitReject = () => {
    rejectForm.patch(route('hr.leaves.reject', rejectingId.value), {
        onSuccess: () => { showRejectModal.value = false; },
    });
};

const cancelRequest = (id) => {
    router.patch(route('hr.leaves.cancel', id));
};

const reactivateRequest = (id) => {
    router.patch(route('hr.leaves.reactivate', id));
};

const deleteRequest = (id) => {
    if (confirm(t('hr.confirm_delete_request'))) {
        router.delete(route('hr.leaves.destroy', id));
    }
};

// Edit request modal
const showEditModal = ref(false);
const editForm = useForm({
    leave_type_id: '',
    start_date: '',
    end_date: '',
    days_count: 1,
    reason: '',
});
const editingRequest = ref(null);

const openEdit = (lr) => {
    editingRequest.value = lr;
    editForm.leave_type_id = lr.leave_type_id;
    editForm.start_date = lr.start_date?.substring(0, 10) || '';
    editForm.end_date = lr.end_date?.substring(0, 10) || '';
    editForm.days_count = Number(lr.days_count);
    editForm.reason = lr.reason || '';
    showEditModal.value = true;
};

const editRemainingDays = computed(() => {
    if (!editingRequest.value || !editForm.leave_type_id) return null;
    const emp = props.employees.find(e => e.id == editingRequest.value.employee_id);
    const lt = props.leaveTypes.find(l => l.id == editForm.leave_type_id);
    if (!emp || !lt) return null;
    const balance = emp.leave_balances?.find(b => b.leave_type_id == editForm.leave_type_id);
    const baseRemaining = balance ? Number(balance.remaining_days) : Number(lt.default_days_per_year ?? 0);
    // Exclude current request from pending count (it's being edited)
    let pending = Number(props.pendingDays?.[editingRequest.value.employee_id]?.[editForm.leave_type_id] ?? 0);
    if (editingRequest.value.status === 'pending' && editingRequest.value.leave_type_id == editForm.leave_type_id) {
        pending = Math.max(0, pending - Number(editingRequest.value.days_count));
    }
    return Math.round((baseRemaining - pending) * 10) / 10;
});

const editDaysExceeded = computed(() => {
    if (editRemainingDays.value === null) return false;
    return Number(editForm.days_count) > editRemainingDays.value;
});

const canSubmitEdit = computed(() => {
    return editForm.leave_type_id && editForm.start_date && editForm.end_date && Number(editForm.days_count) >= 0.5 && !editDaysExceeded.value && !editForm.processing;
});

const submitEdit = () => {
    if (editDaysExceeded.value) return;
    editForm.put(route('hr.leaves.update', editingRequest.value.id), {
        onSuccess: () => { showEditModal.value = false; editForm.reset(); editingRequest.value = null; },
    });
};

// New request modal
const showNewModal = ref(false);
const newForm = useForm({
    employee_id: '',
    leave_type_id: '',
    start_date: '',
    end_date: '',
    days_count: 1,
    reason: '',
});

const remainingDays = computed(() => {
    if (!newForm.employee_id || !newForm.leave_type_id) return null;
    const emp = props.employees.find(e => e.id == newForm.employee_id);
    const lt = props.leaveTypes.find(l => l.id == newForm.leave_type_id);
    if (!emp || !lt) return null;
    const balance = emp.leave_balances?.find(b => b.leave_type_id == newForm.leave_type_id);
    const baseRemaining = balance ? Number(balance.remaining_days) : Number(lt.default_days_per_year ?? 0);
    const pending = Number(props.pendingDays?.[newForm.employee_id]?.[newForm.leave_type_id] ?? 0);
    return Math.round((baseRemaining - pending) * 10) / 10;
});

const daysExceeded = computed(() => {
    if (remainingDays.value === null) return false;
    return Number(newForm.days_count) > remainingDays.value;
});

const canSubmitNew = computed(() => {
    return newForm.employee_id && newForm.leave_type_id && newForm.start_date && newForm.end_date && Number(newForm.days_count) >= 0.5 && !daysExceeded.value && !newForm.processing;
});

const submitNew = () => {
    if (daysExceeded.value) return;
    newForm.post(route('hr.leaves.store'), {
        onSuccess: () => { showNewModal.value = false; newForm.reset(); },
    });
};
</script>

<template>
    <Head :title="t('hr.leave_requests')" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ t('hr.leave_requests') }}
                </h1>
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('hr.leaves.calendar')"
                        class="inline-flex items-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-slate-300 dark:ring-slate-600"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        {{ t('hr.calendar') }}
                    </Link>
                    <button
                        @click="showNewModal = true"
                        class="inline-flex items-center rounded-xl bg-accent-rose px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-pink-500"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                        </svg>
                        {{ t('hr.new_request') }}
                    </button>
                </div>
            </div>
        </template>

        <HRNav class="mb-6" />

        <!-- Status tabs -->
        <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
            <nav class="flex space-x-4 overflow-x-auto">
                <button
                    v-for="tab in statusTabs"
                    :key="tab.value"
                    @click="setStatus(tab.value)"
                    :class="[
                        'whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium transition-colors',
                        statusFilter === tab.value
                            ? 'border-accent-rose text-accent-rose dark:text-pink-400'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-gray-300 dark:text-slate-400 dark:hover:text-slate-300'
                    ]"
                >
                    {{ tab.label }}
                    <span
                        v-if="tab.count > 0"
                        :class="[
                            'ml-1.5 rounded-full px-2 py-0.5 text-xs',
                            statusFilter === tab.value
                                ? 'bg-pink-100 text-accent-rose dark:bg-pink-900/30 dark:text-pink-300'
                                : 'bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-slate-400'
                        ]"
                    >
                        {{ tab.count }}
                    </span>
                </button>
            </nav>
        </div>

        <!-- Filter -->
        <div class="mb-6">
            <select
                v-model="employeeFilter"
                @change="filterByEmployee"
                class="rounded-xl border-0 py-1.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-surface-card dark:text-white dark:ring-slate-600 sm:text-sm sm:leading-6"
            >
                <option value="">{{ t('hr.all_employees') }}</option>
                <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.last_name }} {{ emp.first_name }}</option>
            </select>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-gray-800">
                    <tr>
                        <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 dark:text-white sm:pl-6">{{ t('hr.employee') }}</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white">{{ t('hr.type') }}</th>
                        <th class="hidden px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white sm:table-cell">{{ t('hr.period') }}</th>
                        <th class="px-3 py-3.5 text-center text-sm font-semibold text-slate-900 dark:text-white">{{ t('hr.days') }}</th>
                        <th class="px-3 py-3.5 text-center text-sm font-semibold text-slate-900 dark:text-white">{{ t('hr.status') }}</th>
                        <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">{{ t('actions') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-surface-card">
                    <tr v-if="leaveRequests.data.length === 0">
                        <td colspan="6" class="py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                            {{ t('hr.no_leave_requests') }}
                        </td>
                    </tr>
                    <tr v-for="lr in leaveRequests.data" :key="lr.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 sm:pl-6">
                            <div class="flex items-center">
                                <div class="h-8 w-8 flex-shrink-0">
                                    <img v-if="lr.employee?.photo_path" :src="`/storage/${lr.employee.photo_path}`" class="h-8 w-8 rounded-lg object-cover" />
                                    <div v-else :class="['flex h-8 w-8 items-center justify-center rounded-lg', getAvatarClasses(lr.employee?.first_name + ' ' + lr.employee?.last_name)]">
                                        <span class="text-xs font-bold">
                                            {{ lr.employee?.first_name?.charAt(0) }}{{ lr.employee?.last_name?.charAt(0) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <Link
                                        :href="route('hr.employees.show', lr.employee?.id)"
                                        class="text-sm font-medium text-slate-900 hover:text-primary-600 dark:text-white dark:hover:text-primary-400"
                                    >
                                        {{ lr.employee?.last_name }} {{ lr.employee?.first_name }}
                                    </Link>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: lr.leave_type?.color || '#94a3b8' }"></span>
                                <span class="text-slate-700 dark:text-slate-300">{{ lr.leave_type?.name }}</span>
                            </span>
                        </td>
                        <td class="hidden whitespace-nowrap px-3 py-4 text-sm text-slate-500 dark:text-slate-400 sm:table-cell">
                            {{ formatDate(lr.start_date) }} — {{ formatDate(lr.end_date) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-center text-slate-700 dark:text-slate-300">
                            {{ lr.days_count }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-center">
                            <span :class="getStatusClass(lr.status)" class="inline-flex items-center rounded-xl px-2 py-0.5 text-xs font-medium">
                                {{ t('hr.leave_status_' + lr.status) }}
                            </span>
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm sm:pr-6">
                            <div class="flex items-center justify-end space-x-1">
                                <button
                                    v-if="lr.status === 'pending' || lr.status === 'rejected'"
                                    @click="openEdit(lr)"
                                    class="rounded-lg p-2 text-slate-400 hover:bg-primary-50 hover:text-primary-600 dark:hover:bg-primary-900/20 dark:hover:text-primary-400"
                                    :title="t('hr.edit_request')"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" />
                                    </svg>
                                </button>
                                <button
                                    v-if="lr.status === 'pending'"
                                    @click="approveRequest(lr.id)"
                                    class="rounded-lg p-2 text-emerald-500 hover:bg-emerald-50 hover:text-emerald-700 dark:hover:bg-emerald-900/20"
                                    :title="t('hr.approve')"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <button
                                    v-if="lr.status === 'pending'"
                                    @click="openReject(lr.id)"
                                    class="rounded-lg p-2 text-rose-500 hover:bg-rose-50 hover:text-rose-700 dark:hover:bg-rose-900/20"
                                    :title="t('hr.reject')"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                    </svg>
                                </button>
                                <button
                                    v-if="lr.status === 'approved'"
                                    @click="cancelRequest(lr.id)"
                                    class="rounded-lg p-2 text-slate-400 hover:bg-gray-50 hover:text-slate-600 dark:hover:bg-gray-800"
                                    :title="t('hr.cancel_request')"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                    </svg>
                                </button>
                                <button
                                    v-if="lr.status === 'cancelled'"
                                    @click="reactivateRequest(lr.id)"
                                    class="rounded-lg p-2 text-emerald-500 hover:bg-emerald-50 hover:text-emerald-700 dark:hover:bg-emerald-900/20"
                                    :title="t('hr.reactivate_request')"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.433a.75.75 0 000-1.5H4.598a.75.75 0 00-.75.75v3.634a.75.75 0 001.5 0v-2.033l.312.311a7 7 0 0011.712-3.138.75.75 0 00-1.06-.179zm-1.624-7.848a7 7 0 00-11.712 3.138.75.75 0 001.06.179 5.5 5.5 0 019.201-2.466l.312.311H10.116a.75.75 0 000 1.5h3.634a.75.75 0 00.75-.75V1.854a.75.75 0 00-1.5 0v2.033l-.312-.311z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <button
                                    v-if="lr.status === 'pending'"
                                    @click="deleteRequest(lr.id)"
                                    class="rounded-lg p-2 text-slate-400 hover:bg-pink-50 hover:text-pink-600 dark:hover:bg-pink-900/20 dark:hover:text-pink-400"
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
        <div v-if="leaveRequests.links && leaveRequests.links.length > 3" class="mt-6 flex items-center justify-between">
            <div class="text-sm text-slate-700 dark:text-slate-400">
                {{ t('showing_x_to_y_of_z', { from: leaveRequests.from, to: leaveRequests.to, total: leaveRequests.total, items: t('hr.leave_requests').toLowerCase() }) }}
            </div>
            <nav class="isolate inline-flex -space-x-px rounded-xl shadow-sm">
                <template v-for="(link, index) in leaveRequests.links" :key="index">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        :class="[
                            link.active
                                ? 'z-10 bg-accent-rose text-white'
                                : 'text-slate-900 ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:text-slate-300 dark:ring-slate-600 dark:hover:bg-gray-800',
                            'relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20',
                            index === 0 ? 'rounded-l-xl' : '',
                            index === leaveRequests.links.length - 1 ? 'rounded-r-xl' : '',
                        ]"
                        v-html="link.label"
                        preserve-scroll
                    />
                    <span
                        v-else
                        :class="[
                            'relative inline-flex items-center px-4 py-2 text-sm font-semibold text-slate-400 dark:text-slate-500',
                            index === 0 ? 'rounded-l-xl' : '',
                            index === leaveRequests.links.length - 1 ? 'rounded-r-xl' : '',
                        ]"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </div>

        <!-- Reject Modal -->
        <Teleport to="body">
            <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="fixed inset-0 bg-slate-900/50" @click="showRejectModal = false"></div>
                <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-surface-card">
                    <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-4">{{ t('hr.reject_request') }}</h3>
                    <form @submit.prevent="submitReject" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.rejection_reason') }} *</label>
                            <textarea v-model="rejectForm.rejection_reason" rows="3" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm"></textarea>
                            <p v-if="rejectForm.errors.rejection_reason" class="mt-1 text-xs text-rose-600">{{ rejectForm.errors.rejection_reason }}</p>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showRejectModal = false" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-slate-300 dark:ring-slate-600">
                                {{ t('cancel') }}
                            </button>
                            <button type="submit" :disabled="rejectForm.processing" class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-500 disabled:opacity-50">
                                {{ t('hr.reject') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- New Request Modal -->
        <Teleport to="body">
            <div v-if="showNewModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="fixed inset-0 bg-slate-900/50" @click="showNewModal = false"></div>
                <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-surface-card">
                    <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-4">{{ t('hr.new_leave_request') }}</h3>
                    <form @submit.prevent="submitNew" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.employee') }} *</label>
                            <select v-model="newForm.employee_id" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm">
                                <option value="">{{ t('hr.select_employee') }}</option>
                                <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.last_name }} {{ emp.first_name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.leave_type') }} *</label>
                            <select v-model="newForm.leave_type_id" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm">
                                <option value="">{{ t('hr.select_type') }}</option>
                                <option v-for="lt in leaveTypes" :key="lt.id" :value="lt.id">{{ lt.name }}</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.start_date') }} *</label>
                                <input v-model="newForm.start_date" type="date" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.end_date') }} *</label>
                                <input v-model="newForm.end_date" type="date" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.business_days_count') }} *</label>
                            <input v-model.number="newForm.days_count" type="number" min="0.5" step="0.5" :max="remainingDays ?? undefined" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" :class="{ 'ring-2 ring-rose-500 dark:ring-rose-500': daysExceeded }" />
                            <div v-if="remainingDays !== null" class="mt-2 flex items-center justify-between rounded-lg px-3 py-2" :class="daysExceeded ? 'bg-rose-50 dark:bg-rose-900/20' : 'bg-slate-50 dark:bg-gray-800/50'">
                                <span class="text-xs font-medium" :class="daysExceeded ? 'text-rose-700 dark:text-rose-400' : 'text-slate-600 dark:text-slate-400'">
                                    {{ t('hr.remaining_days_info', { count: remainingDays }) }}
                                </span>
                                <span v-if="daysExceeded" class="text-xs font-semibold text-rose-600 dark:text-rose-400">
                                    {{ t('hr.days_exceeded', { max: remainingDays }) }}
                                </span>
                            </div>
                            <p v-if="newForm.errors.days_count" class="mt-1 text-xs text-rose-600">{{ newForm.errors.days_count }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.reason') }}</label>
                            <textarea v-model="newForm.reason" rows="2" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm"></textarea>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showNewModal = false" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-slate-300 dark:ring-slate-600">
                                {{ t('cancel') }}
                            </button>
                            <button type="submit" :disabled="!canSubmitNew" class="rounded-xl bg-accent-rose px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-pink-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ t('hr.submit_request') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
        <!-- Edit Request Modal -->
        <Teleport to="body">
            <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="fixed inset-0 bg-slate-900/50" @click="showEditModal = false"></div>
                <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-surface-card">
                    <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-1">{{ t('hr.edit_leave_request') }}</h3>
                    <p v-if="editingRequest?.status === 'rejected'" class="mb-4 text-sm text-amber-600 dark:text-amber-400">
                        {{ t('hr.resubmit_info') }}
                    </p>
                    <p v-else class="mb-4 text-sm text-slate-500 dark:text-slate-400">
                        {{ editingRequest?.employee?.last_name }} {{ editingRequest?.employee?.first_name }}
                    </p>
                    <form @submit.prevent="submitEdit" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.leave_type') }} *</label>
                            <select v-model="editForm.leave_type_id" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm">
                                <option value="">{{ t('hr.select_type') }}</option>
                                <option v-for="lt in leaveTypes" :key="lt.id" :value="lt.id">{{ lt.name }}</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.start_date') }} *</label>
                                <input v-model="editForm.start_date" type="date" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.end_date') }} *</label>
                                <input v-model="editForm.end_date" type="date" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.business_days_count') }} *</label>
                            <input v-model.number="editForm.days_count" type="number" min="0.5" step="0.5" :max="editRemainingDays ?? undefined" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" :class="{ 'ring-2 ring-rose-500 dark:ring-rose-500': editDaysExceeded }" />
                            <div v-if="editRemainingDays !== null" class="mt-2 flex items-center justify-between rounded-lg px-3 py-2" :class="editDaysExceeded ? 'bg-rose-50 dark:bg-rose-900/20' : 'bg-slate-50 dark:bg-gray-800/50'">
                                <span class="text-xs font-medium" :class="editDaysExceeded ? 'text-rose-700 dark:text-rose-400' : 'text-slate-600 dark:text-slate-400'">
                                    {{ t('hr.remaining_days_info', { count: editRemainingDays }) }}
                                </span>
                                <span v-if="editDaysExceeded" class="text-xs font-semibold text-rose-600 dark:text-rose-400">
                                    {{ t('hr.days_exceeded', { max: editRemainingDays }) }}
                                </span>
                            </div>
                            <p v-if="editForm.errors.days_count" class="mt-1 text-xs text-rose-600">{{ editForm.errors.days_count }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.reason') }}</label>
                            <textarea v-model="editForm.reason" rows="2" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm"></textarea>
                        </div>
                        <div v-if="editingRequest?.rejection_reason" class="rounded-lg bg-rose-50 p-3 dark:bg-rose-900/20">
                            <p class="text-xs font-medium text-rose-700 dark:text-rose-400">{{ t('hr.previous_rejection_reason') }}</p>
                            <p class="mt-1 text-sm text-rose-600 dark:text-rose-300">{{ editingRequest.rejection_reason }}</p>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showEditModal = false" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-slate-300 dark:ring-slate-600">
                                {{ t('cancel') }}
                            </button>
                            <button type="submit" :disabled="!canSubmitEdit" class="rounded-xl bg-accent-rose px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-pink-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ editingRequest?.status === 'rejected' ? t('hr.resubmit_request') : t('hr.update_request') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

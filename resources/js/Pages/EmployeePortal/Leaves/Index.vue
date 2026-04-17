<script setup>
import EmployeePortalLayout from '@/Layouts/EmployeePortalLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    leaveRequests: { type: Object, required: true },
    leaveBalances: { type: Array, default: () => [] },
    leaveTypes: { type: Array, default: () => [] },
    pendingDays: { type: Object, default: () => ({}) },
});

const showModal = ref(false);
const form = useForm({
    leave_type_id: '',
    start_date: '',
    end_date: '',
    days_count: 1,
    reason: '',
});

const submitLeave = () => {
    form.post(route('employee-portal.leaves.store'), {
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        },
    });
};

const cancelLeave = (id) => {
    if (confirm(t('employee_portal.confirm_cancel_leave'))) {
        router.patch(route('employee-portal.leaves.cancel', id));
    }
};

const deleteLeave = (id) => {
    if (confirm(t('employee_portal.confirm_delete_leave'))) {
        router.delete(route('employee-portal.leaves.destroy', id));
    }
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('fr-FR');
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

const getAvailableDays = (balance) => {
    const pending = props.pendingDays[balance.leave_type_id] || 0;
    return Math.round((balance.initial_days - balance.used_days - pending) * 10) / 10;
};
</script>

<template>
    <Head :title="t('employee_portal.my_leaves')" />

    <EmployeePortalLayout>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ t('employee_portal.my_leaves') }}</h1>
            <button
                @click="showModal = true"
                class="w-full sm:w-auto justify-center rounded-xl bg-accent-rose px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-pink-500"
            >
                + {{ t('employee_portal.submit_leave') }}
            </button>
        </div>

        <!-- Balances -->
        <div v-if="leaveBalances.length" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <div
                v-for="balance in leaveBalances"
                :key="balance.id"
                class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-surface-card"
            >
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ balance.leave_type?.name }}</span>
                    <span class="inline-block h-3 w-3 rounded-full" :style="{ backgroundColor: balance.leave_type?.color || '#6366f1' }"></span>
                </div>
                <p class="text-2xl font-bold text-slate-900 dark:text-white">
                    {{ balance.used_days * 1 }}
                    <span class="text-sm font-normal text-slate-500">/ {{ balance.initial_days * 1 }} {{ t('hr.days') || 'jours' }}</span>
                </p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    {{ getAvailableDays(balance) }} {{ t('hr.available') || 'disponibles' }}
                    <span v-if="pendingDays[balance.leave_type_id]"> · {{ pendingDays[balance.leave_type_id] }} {{ t('hr.pending_short') }}</span>
                </p>
            </div>
        </div>

        <!-- Leave Requests Table -->
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-surface-card overflow-hidden">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-gray-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">{{ t('hr.leave_type') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">{{ t('hr.dates') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">{{ t('hr.days') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">{{ t('hr.status') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">{{ t('actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <tr v-for="leave in leaveRequests.data" :key="leave.id">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="inline-block h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: leave.leave_type?.color || '#6366f1' }"></span>
                                <span class="text-sm text-slate-900 dark:text-white">{{ leave.leave_type?.name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">
                            {{ formatDate(leave.start_date) }} — {{ formatDate(leave.end_date) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ leave.days_count }}</td>
                        <td class="px-4 py-3">
                            <span :class="getStatusClass(leave.status)" class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium">
                                {{ t('hr.status_' + leave.status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button
                                v-if="leave.status === 'pending' || leave.status === 'approved'"
                                @click="cancelLeave(leave.id)"
                                class="text-xs text-slate-500 hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-400"
                            >
                                {{ t('hr.cancel') }}
                            </button>
                            <button
                                v-if="leave.status === 'pending'"
                                @click="deleteLeave(leave.id)"
                                class="ml-2 text-xs text-slate-500 hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-400"
                            >
                                {{ t('delete') }}
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!leaveRequests.data?.length">
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                            {{ t('hr.no_data') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/50" @click="showModal = false"></div>
            <div class="relative bg-white dark:bg-surface-card rounded-2xl shadow-xl p-6 w-full max-w-lg mx-4">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">{{ t('employee_portal.submit_leave') }}</h2>
                <form @submit.prevent="submitLeave" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('hr.leave_type') }}</label>
                        <select v-model="form.leave_type_id" class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="">—</option>
                            <option v-for="lt in leaveTypes" :key="lt.id" :value="lt.id">{{ lt.name }}</option>
                        </select>
                        <p v-if="form.errors.leave_type_id" class="mt-1 text-xs text-rose-600">{{ form.errors.leave_type_id }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('hr.start_date') }}</label>
                            <input v-model="form.start_date" type="date" class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                            <p v-if="form.errors.start_date" class="mt-1 text-xs text-rose-600">{{ form.errors.start_date }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('hr.end_date') }}</label>
                            <input v-model="form.end_date" type="date" class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                            <p v-if="form.errors.end_date" class="mt-1 text-xs text-rose-600">{{ form.errors.end_date }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('hr.days_count') }}</label>
                        <input v-model="form.days_count" type="number" step="0.5" min="0.5" class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                        <p v-if="form.errors.days_count" class="mt-1 text-xs text-rose-600">{{ form.errors.days_count }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('hr.reason') }}</label>
                        <textarea v-model="form.reason" rows="2" class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white"></textarea>
                    </div>
                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end pt-2">
                        <button type="button" @click="showModal = false" class="w-full sm:w-auto justify-center rounded-xl px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-gray-800">
                            {{ t('cancel') }}
                        </button>
                        <button type="submit" :disabled="form.processing" class="w-full sm:w-auto justify-center rounded-xl bg-accent-rose px-4 py-2 text-sm font-semibold text-white hover:bg-pink-500 disabled:opacity-50">
                            {{ t('employee_portal.submit_leave') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </EmployeePortalLayout>
</template>

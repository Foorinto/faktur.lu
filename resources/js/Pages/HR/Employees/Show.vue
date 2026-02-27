<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import HRNav from '@/Components/HRNav.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    employee: { type: Object, required: true },
    activeTab: { type: String, default: 'info' },
    leaveRequests: { type: [Object, null], default: null },
    leaveTypes: { type: Array, default: () => [] },
    countries: { type: Array, default: () => [] },
    nationalities: { type: Array, default: () => [] },
    pendingDays: { type: Object, default: () => ({}) },
    expenseReports: { type: Array, default: () => [] },
});

const countryName = (code) => {
    if (!code) return '—';
    const found = props.countries.find(c => c.code === code);
    return found ? found.name : code;
};

const nationalityName = (code) => {
    if (!code) return '—';
    const found = props.nationalities.find(n => n.code === code);
    return found ? found.name : code;
};

const getStatusBadgeClass = (status) => {
    const classes = {
        active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        long_leave: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        terminated: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
    };
    return classes[status] || classes.active;
};

const getLeaveStatusClass = (status) => {
    const classes = {
        pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        rejected: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
        cancelled: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
    };
    return classes[status] || classes.pending;
};

const formatCurrency = (amount, currency = 'EUR') => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency }).format(amount);
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('fr-FR');
};

const deleteEmployee = () => {
    if (confirm(t('hr.confirm_delete_employee', { name: props.employee.full_name }))) {
        router.delete(route('hr.employees.destroy', props.employee.id));
    }
};

// Leave request form
const showLeaveModal = ref(false);
const leaveForm = useForm({
    employee_id: props.employee.id,
    leave_type_id: '',
    start_date: '',
    end_date: '',
    days_count: 1,
    reason: '',
});

const leaveRemainingDays = computed(() => {
    if (!leaveForm.leave_type_id) return null;
    const balance = props.employee.leave_balances?.find(b => b.leave_type_id == leaveForm.leave_type_id);
    const baseRemaining = balance ? Number(balance.remaining_days) : Number(props.leaveTypes.find(l => l.id == leaveForm.leave_type_id)?.default_days_per_year ?? 0);
    const pending = Number(props.pendingDays?.[leaveForm.leave_type_id] ?? 0);
    return Math.round((baseRemaining - pending) * 10) / 10;
});

const leaveDaysExceeded = computed(() => {
    if (leaveRemainingDays.value === null) return false;
    return Number(leaveForm.days_count) > leaveRemainingDays.value;
});

const canSubmitLeave = computed(() => {
    return leaveForm.leave_type_id && leaveForm.start_date && leaveForm.end_date && Number(leaveForm.days_count) >= 0.5 && !leaveDaysExceeded.value && !leaveForm.processing;
});

const submitLeave = () => {
    if (leaveDaysExceeded.value) return;
    leaveForm.post(route('hr.leaves.store'), {
        onSuccess: () => { showLeaveModal.value = false; leaveForm.reset(); leaveForm.employee_id = props.employee.id; },
    });
};
</script>

<template>
    <Head :title="employee.full_name" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('hr.employees.index')"
                        class="text-slate-400 hover:text-slate-500 dark:text-slate-500 dark:hover:text-slate-400"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                        </svg>
                    </Link>
                    <div class="h-10 w-10 flex-shrink-0">
                        <img
                            v-if="employee.photo_path"
                            :src="`/storage/${employee.photo_path}`"
                            :alt="employee.full_name"
                            class="h-10 w-10 rounded-xl object-cover"
                        />
                        <div v-else class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-100 dark:bg-primary-900/30">
                            <span class="text-sm font-medium text-primary-600 dark:text-primary-400">
                                {{ employee.first_name.charAt(0) }}{{ employee.last_name.charAt(0) }}
                            </span>
                        </div>
                    </div>
                    <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                        {{ employee.full_name }}
                    </h1>
                    <span
                        :class="getStatusBadgeClass(employee.status)"
                        class="inline-flex items-center rounded-xl px-3 py-1 text-xs font-medium"
                    >
                        {{ t('hr.status_' + employee.status) }}
                    </span>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        :href="route('hr.employees.edit', employee.id)"
                        class="inline-flex items-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 hover:bg-slate-50 dark:bg-slate-700 dark:text-white dark:ring-slate-600 dark:hover:bg-slate-600"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" />
                        </svg>
                        {{ t('edit') }}
                    </Link>
                    <button
                        @click="deleteEmployee"
                        class="inline-flex items-center rounded-xl bg-pink-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-pink-500"
                    >
                        {{ t('delete') }}
                    </button>
                </div>
            </div>
        </template>

        <HRNav class="mb-6" />

        <!-- Tabs -->
        <div class="mb-6 border-b border-slate-200 dark:border-slate-700">
            <nav class="flex space-x-8">
                <Link
                    :href="route('hr.employees.show', employee.id)"
                    :class="[
                        'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm',
                        activeTab === 'info'
                            ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'
                    ]"
                >
                    {{ t('hr.info') }}
                </Link>
                <Link
                    :href="route('hr.employees.leaves', employee.id)"
                    :class="[
                        'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm',
                        activeTab === 'leaves'
                            ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'
                    ]"
                >
                    {{ t('hr.leaves') }}
                </Link>
                <Link
                    :href="route('hr.employees.expenses', employee.id)"
                    :class="[
                        'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm',
                        activeTab === 'expenses'
                            ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'
                    ]"
                >
                    {{ t('hr.expenses') }}
                </Link>
            </nav>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <!-- Info Tab -->
                <template v-if="activeTab === 'info'">
                    <!-- Personal -->
                    <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                            <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('hr.personal_info') }}</h2>
                        </div>
                        <dl class="divide-y divide-slate-200 dark:divide-slate-700">
                            <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.full_name') }}</dt>
                                <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:col-span-2 sm:mt-0">{{ employee.full_name }}</dd>
                            </div>
                            <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.email_pro') }}</dt>
                                <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                                    <a :href="`mailto:${employee.email_pro}`" class="text-primary-600 hover:text-primary-500 dark:text-primary-400">{{ employee.email_pro }}</a>
                                </dd>
                            </div>
                            <div v-if="employee.email_perso" class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.email_perso') }}</dt>
                                <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:col-span-2 sm:mt-0">{{ employee.email_perso }}</dd>
                            </div>
                            <div v-if="employee.phone" class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.phone') }}</dt>
                                <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                                    <a :href="`tel:${employee.phone}`" class="text-primary-600 hover:text-primary-500 dark:text-primary-400">{{ employee.phone }}</a>
                                </dd>
                            </div>
                            <div v-if="employee.phone_perso" class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.phone_perso') }}</dt>
                                <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                                    <a :href="`tel:${employee.phone_perso}`" class="text-primary-600 hover:text-primary-500 dark:text-primary-400">{{ employee.phone_perso }}</a>
                                </dd>
                            </div>
                            <div v-if="employee.birth_date" class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.birth_date') }}</dt>
                                <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:col-span-2 sm:mt-0">{{ formatDate(employee.birth_date) }}</dd>
                            </div>
                            <div v-if="employee.nationality" class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.nationality') }}</dt>
                                <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:col-span-2 sm:mt-0">{{ nationalityName(employee.nationality) }}</dd>
                            </div>
                            <div v-if="employee.address" class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.address') }}</dt>
                                <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:col-span-2 sm:mt-0">
                                    {{ employee.address }}
                                    <template v-if="employee.postal_code || employee.city"><br />{{ employee.postal_code }} {{ employee.city }}</template>
                                    <template v-if="employee.country && employee.country !== 'LU'"><br />{{ countryName(employee.country) }}</template>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Contract -->
                    <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                            <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('hr.contract_details') }}</h2>
                        </div>
                        <dl class="divide-y divide-slate-200 dark:divide-slate-700">
                            <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.contract_type') }}</dt>
                                <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:col-span-2 sm:mt-0">{{ t('hr.contract_' + employee.contract_type.toLowerCase()) }}</dd>
                            </div>
                            <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.contract_start') }}</dt>
                                <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:col-span-2 sm:mt-0">{{ formatDate(employee.contract_start) }}</dd>
                            </div>
                            <div v-if="employee.contract_end" class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.contract_end') }}</dt>
                                <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:col-span-2 sm:mt-0">{{ formatDate(employee.contract_end) }}</dd>
                            </div>
                            <div v-if="employee.trial_end_date" class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.trial_end_date') }}</dt>
                                <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:col-span-2 sm:mt-0">{{ formatDate(employee.trial_end_date) }}</dd>
                            </div>
                            <div v-if="employee.job_title" class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.job_title') }}</dt>
                                <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:col-span-2 sm:mt-0">{{ employee.job_title }}</dd>
                            </div>
                            <div v-if="employee.department" class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.department') }}</dt>
                                <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:col-span-2 sm:mt-0">
                                    <span class="inline-flex items-center gap-1.5">
                                        <span v-if="employee.department.color" class="h-2 w-2 rounded-full" :style="{ backgroundColor: employee.department.color }"></span>
                                        {{ employee.department.name }}
                                    </span>
                                </dd>
                            </div>
                            <div v-if="employee.manager" class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.manager') }}</dt>
                                <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                                    <Link :href="route('hr.employees.show', employee.manager.id)" class="text-primary-600 hover:text-primary-500 dark:text-primary-400">
                                        {{ employee.manager.last_name }} {{ employee.manager.first_name }}
                                    </Link>
                                </dd>
                            </div>
                            <div v-if="employee.work_location" class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.work_location') }}</dt>
                                <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:col-span-2 sm:mt-0">{{ employee.work_location }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Emergency Contact -->
                    <div v-if="employee.emergency_contact?.name" class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                            <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('hr.emergency_contact') }}</h2>
                        </div>
                        <dl class="divide-y divide-slate-200 dark:divide-slate-700">
                            <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.contact_name') }}</dt>
                                <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:col-span-2 sm:mt-0">{{ employee.emergency_contact.name }}</dd>
                            </div>
                            <div v-if="employee.emergency_contact.phone" class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.contact_phone') }}</dt>
                                <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:col-span-2 sm:mt-0">{{ employee.emergency_contact.phone }}</dd>
                            </div>
                            <div v-if="employee.emergency_contact.relationship" class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.contact_relationship') }}</dt>
                                <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:col-span-2 sm:mt-0">{{ employee.emergency_contact.relationship }}</dd>
                            </div>
                        </dl>
                    </div>
                </template>

                <!-- Leaves Tab -->
                <template v-if="activeTab === 'leaves'">
                    <!-- Leave Balances -->
                    <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                            <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('hr.leave_balances') }}</h2>
                            <button
                                @click="showLeaveModal = true"
                                class="inline-flex items-center rounded-xl bg-primary-500 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-primary-600"
                            >
                                + {{ t('hr.new_request') }}
                            </button>
                        </div>
                        <div v-if="employee.leave_balances && employee.leave_balances.length > 0" class="divide-y divide-slate-200 dark:divide-slate-700">
                            <div v-for="balance in employee.leave_balances" :key="balance.id" class="px-6 py-3 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: balance.leave_type?.color || '#94a3b8' }"></span>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ balance.leave_type?.name }}</span>
                                </div>
                                <div class="text-sm">
                                    <span class="font-semibold text-slate-900 dark:text-white">{{ balance.remaining_days }}</span>
                                    <span class="text-slate-500 dark:text-slate-400"> / {{ balance.initial_days }} {{ t('hr.days') }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400">
                            {{ t('hr.no_leave_balances') }}
                        </div>
                    </div>

                    <!-- Leave Requests -->
                    <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                            <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('hr.leave_requests') }}</h2>
                        </div>
                        <div v-if="leaveRequests && leaveRequests.data && leaveRequests.data.length > 0">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                <thead class="bg-slate-50 dark:bg-slate-700">
                                    <tr>
                                        <th class="py-3 pl-6 pr-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400">{{ t('hr.type') }}</th>
                                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400">{{ t('hr.period') }}</th>
                                        <th class="px-3 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400">{{ t('hr.days') }}</th>
                                        <th class="px-3 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400">{{ t('hr.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                    <tr v-for="lr in leaveRequests.data" :key="lr.id">
                                        <td class="py-3 pl-6 pr-3 text-sm">
                                            <span class="inline-flex items-center gap-1.5">
                                                <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: lr.leave_type?.color || '#94a3b8' }"></span>
                                                <span class="text-slate-700 dark:text-slate-300">{{ lr.leave_type?.name }}</span>
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 text-sm text-slate-500 dark:text-slate-400">
                                            {{ formatDate(lr.start_date) }} — {{ formatDate(lr.end_date) }}
                                        </td>
                                        <td class="px-3 py-3 text-sm text-center text-slate-700 dark:text-slate-300">{{ lr.days_count }}</td>
                                        <td class="px-3 py-3 text-sm text-center">
                                            <span :class="getLeaveStatusClass(lr.status)" class="inline-flex items-center rounded-xl px-2 py-0.5 text-xs font-medium">
                                                {{ t('hr.leave_status_' + lr.status) }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400">
                            {{ t('hr.no_leave_requests') }}
                        </div>
                    </div>
                </template>

                <!-- Expenses Tab -->
                <template v-if="activeTab === 'expenses'">
                    <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                            <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('hr.expenses') }}</h2>
                        </div>
                        <div v-if="expenseReports && expenseReports.length > 0">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                <thead class="bg-slate-50 dark:bg-slate-700">
                                    <tr>
                                        <th class="py-3 pl-6 pr-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400">{{ t('hr.expense_date') }}</th>
                                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400">{{ t('hr.category') }}</th>
                                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400">{{ t('hr.vendor') }}</th>
                                        <th class="px-3 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400">{{ t('hr.amount_ttc') }}</th>
                                        <th class="px-3 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400">{{ t('hr.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                    <tr v-for="exp in expenseReports" :key="exp.id">
                                        <td class="py-3 pl-6 pr-3 text-sm text-slate-700 dark:text-slate-300">{{ formatDate(exp.date) }}</td>
                                        <td class="px-3 py-3 text-sm">
                                            <span class="inline-flex items-center gap-1.5">
                                                <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: exp.category?.color || '#94a3b8' }"></span>
                                                <span class="text-slate-700 dark:text-slate-300">{{ exp.category?.name }}</span>
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 text-sm text-slate-700 dark:text-slate-300">{{ exp.vendor }}</td>
                                        <td class="px-3 py-3 text-sm text-right font-medium text-slate-900 dark:text-white">{{ formatCurrency(exp.amount_ttc) }}</td>
                                        <td class="px-3 py-3 text-sm text-center">
                                            <span :class="getLeaveStatusClass(exp.status)" class="inline-flex items-center rounded-xl px-2 py-0.5 text-xs font-medium">
                                                {{ t('hr.leave_status_' + exp.status) }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400">
                            {{ t('hr.no_expenses') }}
                        </div>
                    </div>
                </template>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Salary -->
                <div v-if="employee.salary_gross" class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('hr.salary_banking') }}</h2>
                    </div>
                    <div class="px-6 py-4 space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.salary_gross') }}</dt>
                            <dd class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white">
                                {{ formatCurrency(employee.salary_gross, employee.salary_currency || 'EUR') }}
                            </dd>
                        </div>
                        <div v-if="employee.bank_iban">
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('hr.bank_iban') }}</dt>
                            <dd class="mt-1 text-sm font-mono text-slate-900 dark:text-white">{{ employee.bank_iban }}</dd>
                        </div>
                    </div>
                </div>

                <!-- Quick Leave Balances (info tab) -->
                <div v-if="activeTab === 'info' && employee.leave_balances && employee.leave_balances.length > 0" class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('hr.leave_balances') }}</h2>
                    </div>
                    <div class="px-6 py-4 space-y-3">
                        <div v-for="balance in employee.leave_balances" :key="balance.id" class="flex items-center justify-between">
                            <span class="text-sm text-slate-600 dark:text-slate-400">{{ balance.leave_type?.name }}</span>
                            <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ balance.remaining_days }}j</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Leaves (info tab) -->
                <div v-if="activeTab === 'info' && employee.leave_requests && employee.leave_requests.length > 0" class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('hr.recent_leaves') }}</h2>
                    </div>
                    <div class="divide-y divide-slate-200 dark:divide-slate-700">
                        <div v-for="lr in employee.leave_requests.slice(0, 5)" :key="lr.id" class="px-6 py-3 flex items-center justify-between">
                            <div>
                                <span class="text-sm text-slate-700 dark:text-slate-300">{{ lr.leave_type?.name }}</span>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ formatDate(lr.start_date) }} — {{ formatDate(lr.end_date) }}</p>
                            </div>
                            <span :class="getLeaveStatusClass(lr.status)" class="inline-flex items-center rounded-xl px-2 py-0.5 text-xs font-medium">
                                {{ t('hr.leave_status_' + lr.status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Metadata -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                    <div class="px-6 py-4">
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-slate-500 dark:text-slate-400">{{ t('created_at') }}</dt>
                                <dd class="text-slate-900 dark:text-white">{{ formatDate(employee.created_at) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-slate-500 dark:text-slate-400">{{ t('updated_at') }}</dt>
                                <dd class="text-slate-900 dark:text-white">{{ formatDate(employee.updated_at) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leave Request Modal -->
        <Teleport to="body">
            <div v-if="showLeaveModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="fixed inset-0 bg-slate-900/50" @click="showLeaveModal = false"></div>
                <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-800">
                    <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-4">{{ t('hr.new_leave_request') }}</h3>
                    <form @submit.prevent="submitLeave" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.leave_type') }} *</label>
                            <select v-model="leaveForm.leave_type_id" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600 sm:text-sm">
                                <option value="">{{ t('hr.select_type') }}</option>
                                <option v-for="lt in leaveTypes" :key="lt.id" :value="lt.id">{{ lt.name }}</option>
                            </select>
                            <p v-if="leaveForm.errors.leave_type_id" class="mt-1 text-xs text-rose-600">{{ leaveForm.errors.leave_type_id }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.start_date') }} *</label>
                                <input v-model="leaveForm.start_date" type="date" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600 sm:text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.end_date') }} *</label>
                                <input v-model="leaveForm.end_date" type="date" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600 sm:text-sm" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.business_days_count') }} *</label>
                            <input v-model.number="leaveForm.days_count" type="number" min="0.5" step="0.5" :max="leaveRemainingDays ?? undefined" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600 sm:text-sm" :class="{ 'ring-2 ring-rose-500 dark:ring-rose-500': leaveDaysExceeded }" />
                            <div v-if="leaveRemainingDays !== null" class="mt-2 flex items-center justify-between rounded-lg px-3 py-2" :class="leaveDaysExceeded ? 'bg-rose-50 dark:bg-rose-900/20' : 'bg-slate-50 dark:bg-slate-700/50'">
                                <span class="text-xs font-medium" :class="leaveDaysExceeded ? 'text-rose-700 dark:text-rose-400' : 'text-slate-600 dark:text-slate-400'">
                                    {{ t('hr.remaining_days_info', { count: leaveRemainingDays }) }}
                                </span>
                                <span v-if="leaveDaysExceeded" class="text-xs font-semibold text-rose-600 dark:text-rose-400">
                                    {{ t('hr.days_exceeded', { max: leaveRemainingDays }) }}
                                </span>
                            </div>
                            <p v-if="leaveForm.errors.days_count" class="mt-1 text-xs text-rose-600">{{ leaveForm.errors.days_count }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.reason') }}</label>
                            <textarea v-model="leaveForm.reason" rows="2" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600 sm:text-sm"></textarea>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showLeaveModal = false" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-200 hover:bg-slate-50 dark:bg-slate-700 dark:text-slate-300 dark:ring-slate-600">
                                {{ t('cancel') }}
                            </button>
                            <button type="submit" :disabled="!canSubmitLeave" class="rounded-xl bg-primary-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ t('hr.submit_request') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

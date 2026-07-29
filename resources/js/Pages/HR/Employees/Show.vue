<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import HRNav from '@/Components/HRNav.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useAvatarColor } from '@/Composables/useAvatarColor';

const { t } = useTranslations();
const { getAvatarClasses } = useAvatarColor();

const props = defineProps({
    employee: { type: Object, required: true },
    activeTab: { type: String, default: 'info' },
    leaveRequests: { type: [Object, null], default: null },
    leaveTypes: { type: Array, default: () => [] },
    countries: { type: Array, default: () => [] },
    nationalities: { type: Array, default: () => [] },
    pendingDays: { type: Object, default: () => ({}) },
    expenseReports: { type: Array, default: () => [] },
    documents: { type: Array, default: () => [] },
    evaluations: { type: Array, default: () => [] },
    evaluators: { type: Array, default: () => [] },
    onboardingTasks: { type: Array, default: () => [] },
    onboardingTemplates: { type: Array, default: () => [] },
});

const countryName = (code) => {
    if (!code) return '-';
    const found = props.countries.find(c => c.code === code);
    return found ? found.name : code;
};

const nationalityName = (code) => {
    if (!code) return '-';
    const found = props.nationalities.find(n => n.code === code);
    return found ? found.name : code;
};

const getStatusBadgeClass = (status) => {
    const classes = {
        active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        long_leave: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        terminated: 'bg-slate-100 text-slate-700 dark:bg-gray-800 dark:text-slate-300',
    };
    return classes[status] || classes.active;
};

const getLeaveStatusClass = (status) => {
    const classes = {
        pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        rejected: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
        cancelled: 'bg-slate-100 text-slate-700 dark:bg-gray-800 dark:text-slate-300',
    };
    return classes[status] || classes.pending;
};

const formatCurrency = (amount, currency = 'EUR') => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency }).format(amount);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR');
};

const deleteEmployee = () => {
    if (confirm(t('hr.confirm_delete_employee', { name: props.employee.full_name }))) {
        router.delete(route('hr.employees.destroy', props.employee.id));
    }
};

const activatePortal = () => {
    router.post(route('hr.employees.activate-portal', props.employee.id));
};

const deactivatePortal = () => {
    if (confirm(t('employee_portal.confirm_deactivate'))) {
        router.post(route('hr.employees.deactivate-portal', props.employee.id));
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

// Document upload
const showDocModal = ref(false);
const docForm = useForm({
    type: '',
    name: '',
    file: null,
    expiry_date: '',
    notes: '',
});

const docTypes = [
    { value: 'contract', key: 'type_contract' },
    { value: 'amendment', key: 'type_amendment' },
    { value: 'payslip', key: 'type_payslip' },
    { value: 'id_card', key: 'type_id_card' },
    { value: 'certificate', key: 'type_certificate' },
    { value: 'other', key: 'type_other' },
];

const getDocTypeLabel = (type) => {
    const found = docTypes.find(d => d.value === type);
    return found ? t('hr.' + found.key) : type;
};

const getDocTypeBadgeClass = (type) => {
    const classes = {
        contract: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        amendment: 'bg-indigo-100 text-gray-700 dark:bg-indigo-900/30 dark:text-indigo-400',
        payslip: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        id_card: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        certificate: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
        other: 'bg-slate-100 text-slate-700 dark:bg-gray-800 dark:text-slate-300',
    };
    return classes[type] || classes.other;
};

const isExpired = (date) => {
    if (!date) return false;
    return new Date(date) < new Date();
};

const onDocFileChange = (e) => {
    docForm.file = e.target.files[0] || null;
};

const submitDocument = () => {
    docForm.post(route('hr.employees.documents.store', props.employee.id), {
        forceFormData: true,
        onSuccess: () => {
            showDocModal.value = false;
            docForm.reset();
        },
    });
};

const deleteDocument = (doc) => {
    if (confirm(t('hr.confirm_delete_document'))) {
        router.delete(route('hr.employees.documents.destroy', [props.employee.id, doc.id]));
    }
};

// Evaluations
const showEvalModal = ref(false);
const evalForm = useForm({
    title: '',
    description: '',
    date: '',
    evaluator_id: '',
});

const submitEvaluation = () => {
    evalForm.post(route('hr.employees.evaluations.store', props.employee.id), {
        onSuccess: () => {
            showEvalModal.value = false;
            evalForm.reset();
        },
    });
};

// Onboarding
const taskForm = useForm({
    title: '',
});

const completedCount = computed(() => props.onboardingTasks.filter(t => t.completed_at).length);
const totalCount = computed(() => props.onboardingTasks.length);
const progressPercent = computed(() => totalCount.value > 0 ? Math.round((completedCount.value / totalCount.value) * 100) : 0);

const submitTask = () => {
    taskForm.post(route('hr.employees.onboarding.store', props.employee.id), {
        onSuccess: () => taskForm.reset(),
    });
};

const toggleTask = (task) => {
    router.patch(route('hr.employees.onboarding.toggle', [props.employee.id, task.id]));
};

const deleteTask = (task) => {
    router.delete(route('hr.employees.onboarding.destroy', [props.employee.id, task.id]));
};

const selectedTemplate = ref('');
const applyTemplate = () => {
    if (!selectedTemplate.value) return;
    router.post(route('hr.employees.onboarding.apply-template', props.employee.id), {
        template_id: selectedTemplate.value,
    }, {
        onSuccess: () => { selectedTemplate.value = ''; },
    });
};
</script>

<template>
    <Head :title="employee.full_name" />

    <AppLayout>
        <template #header>
            <div class="flex items-center space-x-3 min-w-0">
                <Link
                    :href="route('hr.employees.index')"
                    class="flex-shrink-0 text-slate-400 hover:text-slate-500 dark:text-slate-500 dark:hover:text-slate-400"
                >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                    </svg>
                </Link>
                <div class="h-10 w-10 flex-shrink-0">
                    <img
                        v-if="employee.photo_path"
                        :src="route('files.employee-photo', employee.id)"
                        :alt="employee.full_name"
                        class="h-10 w-10 rounded-xl object-cover"
                    />
                    <div v-else :class="['flex h-10 w-10 items-center justify-center rounded-xl', getAvatarClasses(employee.first_name + ' ' + employee.last_name)]">
                        <span class="text-sm font-bold">
                            {{ employee.first_name.charAt(0) }}{{ employee.last_name.charAt(0) }}
                        </span>
                    </div>
                </div>
                <h1 class="text-lg sm:text-xl font-semibold text-slate-900 dark:text-white truncate">
                    {{ employee.full_name }}
                </h1>
                <span
                    :class="getStatusBadgeClass(employee.status)"
                    class="flex-shrink-0 inline-flex items-center rounded-xl px-2.5 py-0.5 text-xs font-medium"
                >
                    {{ t('hr.status_' + employee.status) }}
                </span>
            </div>
        </template>
        <template #header-actions>
            <!-- Portal activation -->
            <button
                v-if="!employee.account_id && employee.email_pro"
                @click="activatePortal"
                class="inline-flex items-center rounded-xl bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500"
            >
                <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.23 1.23 0 00.41 1.412A9.957 9.957 0 0010 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 00-13.074.003z" />
                </svg>
                {{ t('employee_portal.activate_portal') }}
            </button>
            <span
                v-if="employee.account_id"
                class="inline-flex items-center rounded-xl bg-emerald-100 px-3 py-2 text-sm font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400"
            >
                {{ t('employee_portal.portal_active') }}
            </span>
            <button
                v-if="employee.account_id"
                @click="deactivatePortal"
                class="inline-flex items-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-white dark:ring-slate-600 dark:hover:bg-gray-800"
            >
                {{ t('employee_portal.deactivate_portal') }}
            </button>
            <Link
                :href="route('hr.employees.edit', employee.id)"
                class="inline-flex items-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-white dark:ring-slate-600 dark:hover:bg-gray-800"
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
        </template>

        <HRNav class="mb-6" />

        <!-- Tabs -->
        <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
            <nav class="flex space-x-4 sm:space-x-8 overflow-x-auto">
                <Link
                    :href="route('hr.employees.show', employee.id)"
                    :class="[
                        'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm',
                        activeTab === 'info'
                            ? 'border-accent-rose text-accent-rose dark:text-pink-400'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-gray-300 dark:text-slate-400 dark:hover:text-slate-300'
                    ]"
                >
                    {{ t('hr.info') }}
                </Link>
                <Link
                    :href="route('hr.employees.leaves', employee.id)"
                    :class="[
                        'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm',
                        activeTab === 'leaves'
                            ? 'border-accent-rose text-accent-rose dark:text-pink-400'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-gray-300 dark:text-slate-400 dark:hover:text-slate-300'
                    ]"
                >
                    {{ t('hr.leaves') }}
                </Link>
                <Link
                    :href="route('hr.employees.expenses', employee.id)"
                    :class="[
                        'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm',
                        activeTab === 'expenses'
                            ? 'border-accent-rose text-accent-rose dark:text-pink-400'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-gray-300 dark:text-slate-400 dark:hover:text-slate-300'
                    ]"
                >
                    {{ t('hr.expenses') }}
                </Link>
                <Link
                    :href="route('hr.employees.documents', employee.id)"
                    :class="[
                        'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm',
                        activeTab === 'documents'
                            ? 'border-accent-rose text-accent-rose dark:text-pink-400'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-gray-300 dark:text-slate-400 dark:hover:text-slate-300'
                    ]"
                >
                    {{ t('hr.documents') }}
                </Link>
                <Link
                    :href="route('hr.employees.evaluations', employee.id)"
                    :class="[
                        'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm',
                        activeTab === 'evaluations'
                            ? 'border-accent-rose text-accent-rose dark:text-pink-400'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-gray-300 dark:text-slate-400 dark:hover:text-slate-300'
                    ]"
                >
                    {{ t('hr.evaluations') }}
                </Link>
                <Link
                    :href="route('hr.employees.onboarding', employee.id)"
                    :class="[
                        'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm',
                        activeTab === 'onboarding'
                            ? 'border-accent-rose text-accent-rose dark:text-pink-400'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-gray-300 dark:text-slate-400 dark:hover:text-slate-300'
                    ]"
                >
                    {{ t('hr.onboarding') }}
                </Link>
            </nav>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <!-- Info Tab -->
                <template v-if="activeTab === 'info'">
                    <!-- Personal -->
                    <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
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
                    <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
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
                    <div v-if="employee.emergency_contact?.name" class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
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
                    <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('hr.leave_balances') }}</h2>
                            <button
                                @click="showLeaveModal = true"
                                class="inline-flex justify-center items-center rounded-xl bg-accent-rose px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-pink-500 w-full sm:w-auto"
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
                    <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('hr.leave_requests') }}</h2>
                        </div>
                        <div v-if="leaveRequests && leaveRequests.data && leaveRequests.data.length > 0">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                <thead class="bg-slate-50 dark:bg-gray-800">
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
                                            {{ formatDate(lr.start_date) }} - {{ formatDate(lr.end_date) }}
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
                    <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('hr.expenses') }}</h2>
                        </div>
                        <div v-if="expenseReports && expenseReports.length > 0">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                <thead class="bg-slate-50 dark:bg-gray-800">
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

                <!-- Documents Tab -->
                <template v-if="activeTab === 'documents'">
                    <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('hr.documents') }}</h2>
                            <button
                                @click="showDocModal = true"
                                class="inline-flex justify-center items-center rounded-xl bg-accent-rose px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-pink-500 w-full sm:w-auto"
                            >
                                + {{ t('hr.new_document') }}
                            </button>
                        </div>
                        <div v-if="documents && documents.length > 0" class="divide-y divide-slate-200 dark:divide-slate-700">
                            <div v-for="doc in documents" :key="doc.id" class="px-6 py-4 flex items-start justify-between gap-4">
                                <div class="flex items-start gap-3 min-w-0">
                                    <!-- File icon -->
                                    <div class="flex-shrink-0 mt-0.5">
                                        <svg class="h-8 w-8 text-slate-400 dark:text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-sm font-medium text-slate-900 dark:text-white">{{ doc.name }}</span>
                                            <span :class="getDocTypeBadgeClass(doc.type)" class="inline-flex items-center rounded-xl px-2 py-0.5 text-xs font-medium">
                                                {{ getDocTypeLabel(doc.type) }}
                                            </span>
                                            <span v-if="doc.expiry_date && isExpired(doc.expiry_date)" class="inline-flex items-center rounded-xl px-2 py-0.5 text-xs font-medium bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400">
                                                {{ t('hr.expired') }}
                                            </span>
                                        </div>
                                        <div class="mt-1 flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                                            <a :href="route('files.employee-document', doc.id)" target="_blank" class="text-primary-600 hover:text-primary-500 dark:text-primary-400 hover:underline truncate">
                                                {{ doc.original_name }}
                                            </a>
                                            <span v-if="doc.expiry_date && !isExpired(doc.expiry_date)">
                                                {{ t('hr.expires_on') }} {{ formatDate(doc.expiry_date) }}
                                            </span>
                                            <span v-else-if="doc.expiry_date && isExpired(doc.expiry_date)">
                                                {{ t('hr.expires_on') }} {{ formatDate(doc.expiry_date) }}
                                            </span>
                                        </div>
                                        <p v-if="doc.notes" class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ doc.notes }}</p>
                                    </div>
                                </div>
                                <button
                                    @click="deleteDocument(doc)"
                                    class="flex-shrink-0 text-slate-400 hover:text-rose-600 dark:text-slate-500 dark:hover:text-rose-400"
                                    :title="t('delete')"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div v-else class="px-6 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                            {{ t('hr.no_documents') }}
                        </div>
                    </div>
                </template>

                <!-- Evaluations Tab -->
                <template v-if="activeTab === 'evaluations'">
                    <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('hr.evaluations') }}</h2>
                            <button
                                @click="showEvalModal = true"
                                class="inline-flex justify-center items-center rounded-xl bg-accent-rose px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-pink-500 w-full sm:w-auto"
                            >
                                + {{ t('hr.add_evaluation') }}
                            </button>
                        </div>
                        <div v-if="evaluations && evaluations.length > 0" class="divide-y divide-slate-200 dark:divide-slate-700">
                            <Link
                                v-for="ev in evaluations"
                                :key="ev.id"
                                :href="route('hr.employees.evaluations.show', [employee.id, ev.id])"
                                class="block px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="min-w-0">
                                        <span class="text-sm font-medium text-slate-900 dark:text-white">{{ ev.title }}</span>
                                        <div class="flex items-center gap-2 mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                            <span>{{ formatDate(ev.date) }}</span>
                                            <span>&middot;</span>
                                            <span>{{ t('hr.evaluator') }}: {{ ev.evaluator?.first_name }} {{ ev.evaluator?.last_name }}</span>
                                        </div>
                                    </div>
                                    <svg class="h-5 w-5 flex-shrink-0 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </Link>
                        </div>
                        <div v-else class="px-6 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                            {{ t('hr.no_evaluations') }}
                        </div>
                    </div>
                </template>

                <!-- Onboarding Tab -->
                <template v-if="activeTab === 'onboarding'">
                    <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <h2 class="text-lg font-medium text-slate-900 dark:text-white flex-shrink-0">{{ t('hr.onboarding') }}</h2>
                                <div class="flex flex-wrap items-center gap-2">
                                    <template v-if="onboardingTemplates.length > 0">
                                        <select
                                            v-model="selectedTemplate"
                                            class="rounded-xl border-0 py-1.5 text-sm text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600"
                                        >
                                            <option value="">{{ t('hr.select_template') }}</option>
                                            <option v-for="tpl in onboardingTemplates" :key="tpl.id" :value="tpl.id">{{ tpl.name }}</option>
                                        </select>
                                        <button
                                            @click="applyTemplate"
                                            :disabled="!selectedTemplate"
                                            class="rounded-xl bg-accent-rose px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-pink-500 disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap w-full sm:w-auto"
                                        >
                                            {{ t('hr.apply_template') }}
                                        </button>
                                    </template>
                                    <span v-if="totalCount > 0" class="text-sm text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                        {{ t('hr.tasks_completed', { completed: completedCount, total: totalCount }) }}
                                    </span>
                                </div>
                            </div>
                            <!-- Progress bar -->
                            <div v-if="totalCount > 0" class="mt-3">
                                <div class="h-2 w-full rounded-full bg-slate-200 dark:bg-gray-800">
                                    <div
                                        class="h-2 rounded-full transition-all duration-300"
                                        :class="progressPercent === 100 ? 'bg-emerald-500' : 'bg-primary-500'"
                                        :style="{ width: progressPercent + '%' }"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- Task list -->
                        <div v-if="onboardingTasks.length > 0" class="divide-y divide-slate-200 dark:divide-slate-700">
                            <div v-for="task in onboardingTasks" :key="task.id" class="px-6 py-3 flex items-center gap-3">
                                <button @click="toggleTask(task)" class="flex-shrink-0">
                                    <svg v-if="task.completed_at" class="h-5 w-5 text-emerald-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                    </svg>
                                    <div v-else class="h-5 w-5 rounded-full border-2 border-gray-300 dark:border-gray-700 hover:border-primary-400 dark:hover:border-primary-500"></div>
                                </button>
                                <span
                                    class="flex-1 text-sm"
                                    :class="task.completed_at ? 'line-through text-slate-400 dark:text-slate-500' : 'text-slate-700 dark:text-slate-300'"
                                >
                                    {{ task.title }}
                                </span>
                                <button
                                    @click="deleteTask(task)"
                                    class="flex-shrink-0 text-slate-400 hover:text-rose-600 dark:text-slate-500 dark:hover:text-rose-400"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div v-else class="px-6 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                            {{ t('hr.no_onboarding_tasks') }}
                        </div>

                        <!-- Add task inline form -->
                        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                            <form @submit.prevent="submitTask" class="flex items-center gap-3">
                                <input
                                    v-model="taskForm.title"
                                    type="text"
                                    :placeholder="t('hr.task_title')"
                                    required
                                    class="flex-1 rounded-xl border-0 py-1.5 text-sm text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600"
                                />
                                <button
                                    type="submit"
                                    :disabled="taskForm.processing || !taskForm.title"
                                    class="inline-flex items-center rounded-xl bg-accent-rose px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-pink-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    +
                                </button>
                            </form>
                            <p v-if="taskForm.errors.title" class="mt-1 text-xs text-rose-600">{{ taskForm.errors.title }}</p>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Salary -->
                <div v-if="employee.salary_gross" class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
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
                <div v-if="activeTab === 'info' && employee.leave_balances && employee.leave_balances.length > 0" class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
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
                <div v-if="activeTab === 'info' && employee.leave_requests && employee.leave_requests.length > 0" class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('hr.recent_leaves') }}</h2>
                    </div>
                    <div class="divide-y divide-slate-200 dark:divide-slate-700">
                        <div v-for="lr in employee.leave_requests.slice(0, 5)" :key="lr.id" class="px-6 py-3 flex items-center justify-between">
                            <div>
                                <span class="text-sm text-slate-700 dark:text-slate-300">{{ lr.leave_type?.name }}</span>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ formatDate(lr.start_date) }} - {{ formatDate(lr.end_date) }}</p>
                            </div>
                            <span :class="getLeaveStatusClass(lr.status)" class="inline-flex items-center rounded-xl px-2 py-0.5 text-xs font-medium">
                                {{ t('hr.leave_status_' + lr.status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Metadata -->
                <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
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
                <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-surface-card">
                    <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-4">{{ t('hr.new_leave_request') }}</h3>
                    <form @submit.prevent="submitLeave" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.leave_type') }} *</label>
                            <select v-model="leaveForm.leave_type_id" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm">
                                <option value="">{{ t('hr.select_type') }}</option>
                                <option v-for="lt in leaveTypes" :key="lt.id" :value="lt.id">{{ lt.name }}</option>
                            </select>
                            <p v-if="leaveForm.errors.leave_type_id" class="mt-1 text-xs text-rose-600">{{ leaveForm.errors.leave_type_id }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.start_date') }} *</label>
                                <input v-model="leaveForm.start_date" type="date" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.end_date') }} *</label>
                                <input v-model="leaveForm.end_date" type="date" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.business_days_count') }} *</label>
                            <input v-model.number="leaveForm.days_count" type="number" min="0.5" step="0.5" :max="leaveRemainingDays ?? undefined" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" :class="{ 'ring-2 ring-rose-500 dark:ring-rose-500': leaveDaysExceeded }" />
                            <div v-if="leaveRemainingDays !== null" class="mt-2 flex items-center justify-between rounded-lg px-3 py-2" :class="leaveDaysExceeded ? 'bg-rose-50 dark:bg-rose-900/20' : 'bg-slate-50 dark:bg-gray-800/50'">
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
                            <textarea v-model="leaveForm.reason" rows="2" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm"></textarea>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showLeaveModal = false" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-slate-300 dark:ring-slate-600">
                                {{ t('cancel') }}
                            </button>
                            <button type="submit" :disabled="!canSubmitLeave" class="rounded-xl bg-accent-rose px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-pink-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ t('hr.submit_request') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Evaluation Modal -->
        <Teleport to="body">
            <div v-if="showEvalModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="fixed inset-0 bg-slate-900/50" @click="showEvalModal = false"></div>
                <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl dark:bg-surface-card">
                    <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-4">{{ t('hr.add_evaluation') }}</h3>
                    <form @submit.prevent="submitEvaluation" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.evaluation_title') }} *</label>
                            <input v-model="evalForm.title" type="text" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                            <p v-if="evalForm.errors.title" class="mt-1 text-xs text-rose-600">{{ evalForm.errors.title }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.evaluator') }} *</label>
                                <select v-model="evalForm.evaluator_id" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm">
                                    <option value="">{{ t('hr.select_type') }}</option>
                                    <option v-for="ev in evaluators" :key="ev.id" :value="ev.id">{{ ev.last_name }} {{ ev.first_name }}</option>
                                </select>
                                <p v-if="evalForm.errors.evaluator_id" class="mt-1 text-xs text-rose-600">{{ evalForm.errors.evaluator_id }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.evaluation_date') }} *</label>
                                <input v-model="evalForm.date" type="date" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                                <p v-if="evalForm.errors.date" class="mt-1 text-xs text-rose-600">{{ evalForm.errors.date }}</p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('hr.evaluation_description') }} *</label>
                            <RichTextEditor v-model="evalForm.description" />
                            <p v-if="evalForm.errors.description" class="mt-1 text-xs text-rose-600">{{ evalForm.errors.description }}</p>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showEvalModal = false" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-slate-300 dark:ring-slate-600">
                                {{ t('cancel') }}
                            </button>
                            <button type="submit" :disabled="evalForm.processing" class="rounded-xl bg-accent-rose px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-pink-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ t('save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Document Upload Modal -->
        <Teleport to="body">
            <div v-if="showDocModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="fixed inset-0 bg-slate-900/50" @click="showDocModal = false"></div>
                <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-surface-card">
                    <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-4">{{ t('hr.new_document') }}</h3>
                    <form @submit.prevent="submitDocument" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.document_type') }} *</label>
                            <select v-model="docForm.type" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm">
                                <option value="">{{ t('hr.select_type') }}</option>
                                <option v-for="dt in docTypes" :key="dt.value" :value="dt.value">{{ t('hr.' + dt.key) }}</option>
                            </select>
                            <p v-if="docForm.errors.type" class="mt-1 text-xs text-rose-600">{{ docForm.errors.type }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.document_name') }} *</label>
                            <input v-model="docForm.name" type="text" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                            <p v-if="docForm.errors.name" class="mt-1 text-xs text-rose-600">{{ docForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.justification') }} *</label>
                            <input type="file" @change="onDocFileChange" required accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:text-slate-400 dark:file:bg-primary-900/30 dark:file:text-primary-400" />
                            <p v-if="docForm.errors.file" class="mt-1 text-xs text-rose-600">{{ docForm.errors.file }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.document_expiry') }}</label>
                            <input v-model="docForm.expiry_date" type="date" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                            <p v-if="docForm.errors.expiry_date" class="mt-1 text-xs text-rose-600">{{ docForm.errors.expiry_date }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.document_notes') }}</label>
                            <textarea v-model="docForm.notes" rows="2" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm"></textarea>
                            <p v-if="docForm.errors.notes" class="mt-1 text-xs text-rose-600">{{ docForm.errors.notes }}</p>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showDocModal = false" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-slate-300 dark:ring-slate-600">
                                {{ t('cancel') }}
                            </button>
                            <button type="submit" :disabled="docForm.processing" class="rounded-xl bg-accent-rose px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-pink-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ t('save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

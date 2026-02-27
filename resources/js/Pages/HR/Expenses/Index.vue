<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import HRNav from '@/Components/HRNav.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    expenses: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusCounts: { type: Object, default: () => ({}) },
    employees: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
});

const statusFilter = ref(props.filters.status || '');
const employeeFilter = ref(props.filters.employee || '');
const categoryFilter = ref(props.filters.category || '');
const fromFilter = ref(props.filters.from || '');
const toFilter = ref(props.filters.to || '');

const totalCount = computed(() => {
    return Object.values(props.statusCounts).reduce((sum, c) => sum + c, 0);
});

const statusTabs = computed(() => [
    { value: '', label: t('hr.all'), count: totalCount.value },
    { value: 'pending', label: t('hr.leave_status_pending'), count: props.statusCounts.pending || 0 },
    { value: 'approved', label: t('hr.leave_status_approved'), count: props.statusCounts.approved || 0 },
    { value: 'rejected', label: t('hr.leave_status_rejected'), count: props.statusCounts.rejected || 0 },
]);

const applyFilters = () => {
    router.get(route('hr.expenses.index'), {
        status: statusFilter.value || undefined,
        employee: employeeFilter.value || undefined,
        category: categoryFilter.value || undefined,
        from: fromFilter.value || undefined,
        to: toFilter.value || undefined,
    }, { preserveState: true, replace: true });
};

const setStatus = (status) => {
    statusFilter.value = status;
    applyFilters();
};

const getStatusClass = (status) => {
    const classes = {
        pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        rejected: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
    };
    return classes[status] || classes.pending;
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('fr-FR');
};

const formatAmount = (amount) => {
    if (!amount && amount !== 0) return '—';
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount);
};

const paymentMethodLabel = (method) => {
    const labels = { card: t('hr.payment_card'), cash: t('hr.payment_cash'), transfer: t('hr.payment_transfer') };
    return labels[method] || '—';
};

// New expense modal
const showNewModal = ref(false);
const newForm = useForm({
    employee_id: '',
    expense_category_id: '',
    date: '',
    description: '',
    vendor: '',
    amount_ht: '',
    vat_rate: 17,
    payment_method: '',
    receipts: [],
});

const newAmountVat = computed(() => {
    const ht = parseFloat(newForm.amount_ht) || 0;
    const rate = parseFloat(newForm.vat_rate) || 0;
    return (ht * rate / 100).toFixed(2);
});

const newAmountTtc = computed(() => {
    const ht = parseFloat(newForm.amount_ht) || 0;
    return (ht + parseFloat(newAmountVat.value)).toFixed(2);
});

const newMaxAmountExceeded = computed(() => {
    if (!newForm.expense_category_id || !newForm.amount_ht) return false;
    const cat = props.categories.find(c => c.id == newForm.expense_category_id);
    if (!cat || !cat.max_amount) return false;
    return parseFloat(newForm.amount_ht) > parseFloat(cat.max_amount);
});

const newCategoryMaxAmount = computed(() => {
    if (!newForm.expense_category_id) return null;
    const cat = props.categories.find(c => c.id == newForm.expense_category_id);
    return cat?.max_amount ? parseFloat(cat.max_amount) : null;
});

const addNewReceipts = (event) => {
    const files = Array.from(event.target.files || []);
    newForm.receipts = [...newForm.receipts, ...files];
    event.target.value = '';
};

const removeNewReceipt = (index) => {
    newForm.receipts = newForm.receipts.filter((_, i) => i !== index);
};

const submitNew = () => {
    newForm.post(route('hr.expenses.store'), {
        forceFormData: true,
        onSuccess: () => { showNewModal.value = false; newForm.reset(); newForm.vat_rate = 17; },
    });
};

// Edit expense modal
const showEditModal = ref(false);
const editingExpense = ref(null);
const editForm = useForm({
    _method: 'PUT',
    expense_category_id: '',
    date: '',
    description: '',
    vendor: '',
    amount_ht: '',
    vat_rate: 17,
    payment_method: '',
    receipts: [],
});

const editAmountVat = computed(() => {
    const ht = parseFloat(editForm.amount_ht) || 0;
    const rate = parseFloat(editForm.vat_rate) || 0;
    return (ht * rate / 100).toFixed(2);
});

const editAmountTtc = computed(() => {
    const ht = parseFloat(editForm.amount_ht) || 0;
    return (ht + parseFloat(editAmountVat.value)).toFixed(2);
});

const editMaxAmountExceeded = computed(() => {
    if (!editForm.expense_category_id || !editForm.amount_ht) return false;
    const cat = props.categories.find(c => c.id == editForm.expense_category_id);
    if (!cat || !cat.max_amount) return false;
    return parseFloat(editForm.amount_ht) > parseFloat(cat.max_amount);
});

const editCategoryMaxAmount = computed(() => {
    if (!editForm.expense_category_id) return null;
    const cat = props.categories.find(c => c.id == editForm.expense_category_id);
    return cat?.max_amount ? parseFloat(cat.max_amount) : null;
});

const openEdit = (expense) => {
    editingExpense.value = expense;
    editForm.expense_category_id = expense.expense_category_id;
    editForm.date = expense.date?.substring(0, 10) || '';
    editForm.description = expense.description || '';
    editForm.vendor = expense.vendor;
    editForm.amount_ht = Number(expense.amount_ht);
    editForm.vat_rate = Number(expense.vat_rate);
    editForm.payment_method = expense.payment_method || '';
    editForm.receipts = [];
    showEditModal.value = true;
};

const addEditReceipts = (event) => {
    const files = Array.from(event.target.files || []);
    editForm.receipts = [...editForm.receipts, ...files];
    event.target.value = '';
};

const removeEditReceipt = (index) => {
    editForm.receipts = editForm.receipts.filter((_, i) => i !== index);
};

const deleteExistingReceipt = (receiptId) => {
    if (!editingExpense.value) return;
    router.delete(route('hr.expenses.receipts.destroy', [editingExpense.value.id, receiptId]), {
        preserveScroll: true,
        onSuccess: () => {
            editingExpense.value.receipts = editingExpense.value.receipts.filter(r => r.id !== receiptId);
        },
    });
};

const submitEdit = () => {
    editForm.post(route('hr.expenses.update', editingExpense.value.id), {
        forceFormData: true,
        onSuccess: () => { showEditModal.value = false; editForm.reset(); editingExpense.value = null; },
    });
};

// Approve
const approveExpense = (id) => {
    router.patch(route('hr.expenses.approve', id));
};

// Reject modal
const showRejectModal = ref(false);
const rejectingId = ref(null);
const rejectForm = useForm({ rejection_reason: '' });

const openReject = (id) => {
    rejectingId.value = id;
    rejectForm.rejection_reason = '';
    showRejectModal.value = true;
};

const submitReject = () => {
    rejectForm.patch(route('hr.expenses.reject', rejectingId.value), {
        onSuccess: () => { showRejectModal.value = false; },
    });
};

// Delete
const deleteExpense = (id) => {
    if (confirm(t('hr.confirm_delete_expense'))) {
        router.delete(route('hr.expenses.destroy', id));
    }
};
</script>

<template>
    <Head :title="t('hr.expenses')" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ t('hr.expenses') }}
                </h1>
                <button
                    @click="showNewModal = true"
                    class="inline-flex items-center rounded-xl bg-primary-500 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-600"
                >
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                    </svg>
                    {{ t('hr.new_expense') }}
                </button>
            </div>
        </template>

        <HRNav class="mb-6" />

        <!-- Status tabs -->
        <div class="mb-4 border-b border-slate-200 dark:border-slate-700">
            <nav class="flex space-x-4 overflow-x-auto">
                <button
                    v-for="tab in statusTabs"
                    :key="tab.value"
                    @click="setStatus(tab.value)"
                    :class="[
                        'whitespace-nowrap py-3 px-1 border-b-2 text-sm font-medium transition-colors',
                        statusFilter === tab.value
                            ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'
                    ]"
                >
                    {{ tab.label }}
                    <span
                        v-if="tab.count > 0"
                        :class="[
                            'ml-1.5 rounded-full px-2 py-0.5 text-xs',
                            statusFilter === tab.value
                                ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300'
                                : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400'
                        ]"
                    >
                        {{ tab.count }}
                    </span>
                </button>
            </nav>
        </div>

        <!-- Filters -->
        <div class="mb-6 flex flex-wrap gap-3">
            <select
                v-model="employeeFilter"
                @change="applyFilters"
                class="rounded-xl border-0 py-1.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-800 dark:text-white dark:ring-slate-600 sm:text-sm"
            >
                <option value="">{{ t('hr.all_employees') }}</option>
                <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.last_name }} {{ emp.first_name }}</option>
            </select>
            <select
                v-model="categoryFilter"
                @change="applyFilters"
                class="rounded-xl border-0 py-1.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-800 dark:text-white dark:ring-slate-600 sm:text-sm"
            >
                <option value="">{{ t('hr.all_categories') }}</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </select>
            <input
                v-model="fromFilter"
                @change="applyFilters"
                type="date"
                :placeholder="t('hr.from')"
                class="rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-800 dark:text-white dark:ring-slate-600 sm:text-sm"
            />
            <input
                v-model="toFilter"
                @change="applyFilters"
                type="date"
                :placeholder="t('hr.to')"
                class="rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-800 dark:text-white dark:ring-slate-600 sm:text-sm"
            />
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700">
                    <tr>
                        <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 dark:text-white sm:pl-6">{{ t('hr.expense_date') }}</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white">{{ t('hr.employee') }}</th>
                        <th class="hidden px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white sm:table-cell">{{ t('hr.category') }}</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900 dark:text-white">{{ t('hr.vendor') }}</th>
                        <th class="px-3 py-3.5 text-right text-sm font-semibold text-slate-900 dark:text-white">{{ t('hr.amount_ttc') }}</th>
                        <th class="px-3 py-3.5 text-center text-sm font-semibold text-slate-900 dark:text-white">{{ t('hr.status') }}</th>
                        <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">{{ t('actions') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-800">
                    <tr v-if="expenses.data.length === 0">
                        <td colspan="7" class="py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                            {{ t('hr.no_expenses') }}
                        </td>
                    </tr>
                    <tr v-for="exp in expenses.data" :key="exp.id" class="hover:bg-slate-50 dark:hover:bg-slate-700">
                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-slate-700 dark:text-slate-300 sm:pl-6">
                            {{ formatDate(exp.date) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4">
                            <div class="flex items-center">
                                <div class="h-8 w-8 flex-shrink-0">
                                    <img v-if="exp.employee?.photo_path" :src="`/storage/${exp.employee.photo_path}`" class="h-8 w-8 rounded-lg object-cover" />
                                    <div v-else class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-100 dark:bg-primary-900/30">
                                        <span class="text-xs font-medium text-primary-600 dark:text-primary-400">
                                            {{ exp.employee?.first_name?.charAt(0) }}{{ exp.employee?.last_name?.charAt(0) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <span class="text-sm font-medium text-slate-900 dark:text-white">
                                        {{ exp.employee?.last_name }} {{ exp.employee?.first_name }}
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="hidden whitespace-nowrap px-3 py-4 text-sm sm:table-cell">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: exp.category?.color || '#94a3b8' }"></span>
                                <span class="text-slate-700 dark:text-slate-300">{{ exp.category?.name }}</span>
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-700 dark:text-slate-300">
                            {{ exp.vendor }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-right font-medium text-slate-900 dark:text-white">
                            {{ formatAmount(exp.amount_ttc) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-center">
                            <span :class="getStatusClass(exp.status)" class="inline-flex items-center rounded-xl px-2 py-0.5 text-xs font-medium">
                                {{ t('hr.leave_status_' + exp.status) }}
                            </span>
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm sm:pr-6">
                            <div class="flex items-center justify-end space-x-1">
                                <button
                                    v-if="exp.status === 'pending' || exp.status === 'rejected'"
                                    @click="openEdit(exp)"
                                    class="rounded-lg p-2 text-slate-400 hover:bg-primary-50 hover:text-primary-600 dark:hover:bg-primary-900/20 dark:hover:text-primary-400"
                                    :title="t('hr.edit_expense')"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" />
                                    </svg>
                                </button>
                                <button
                                    v-if="exp.status === 'pending'"
                                    @click="approveExpense(exp.id)"
                                    class="rounded-lg p-2 text-emerald-500 hover:bg-emerald-50 hover:text-emerald-700 dark:hover:bg-emerald-900/20"
                                    :title="t('hr.approve')"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <button
                                    v-if="exp.status === 'pending'"
                                    @click="openReject(exp.id)"
                                    class="rounded-lg p-2 text-rose-500 hover:bg-rose-50 hover:text-rose-700 dark:hover:bg-rose-900/20"
                                    :title="t('hr.reject')"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                    </svg>
                                </button>
                                <!-- Receipts indicator -->
                                <span
                                    v-if="exp.receipts && exp.receipts.length > 0"
                                    class="inline-flex items-center gap-1 rounded-lg p-2 text-slate-400"
                                    :title="t('hr.receipts') + ' (' + exp.receipts.length + ')'"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M3 3.5A1.5 1.5 0 014.5 2h6.879a1.5 1.5 0 011.06.44l4.122 4.12A1.5 1.5 0 0117 7.622V16.5a1.5 1.5 0 01-1.5 1.5h-11A1.5 1.5 0 013 16.5v-13z" />
                                    </svg>
                                    <span class="text-xs">{{ exp.receipts.length }}</span>
                                </span>
                                <button
                                    v-if="exp.status === 'pending'"
                                    @click="deleteExpense(exp.id)"
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
        <div v-if="expenses.links && expenses.links.length > 3" class="mt-6 flex items-center justify-between">
            <div class="text-sm text-slate-700 dark:text-slate-400">
                {{ t('showing_x_to_y_of_z', { from: expenses.from, to: expenses.to, total: expenses.total, items: t('hr.expenses').toLowerCase() }) }}
            </div>
            <nav class="isolate inline-flex -space-x-px rounded-xl shadow-sm">
                <template v-for="(link, index) in expenses.links" :key="index">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        :class="[
                            link.active
                                ? 'z-10 bg-primary-500 text-white'
                                : 'text-slate-900 ring-1 ring-inset ring-slate-200 hover:bg-slate-50 dark:text-slate-300 dark:ring-slate-600 dark:hover:bg-slate-700',
                            'relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20',
                            index === 0 ? 'rounded-l-xl' : '',
                            index === expenses.links.length - 1 ? 'rounded-r-xl' : '',
                        ]"
                        v-html="link.label"
                        preserve-scroll
                    />
                    <span
                        v-else
                        :class="[
                            'relative inline-flex items-center px-4 py-2 text-sm font-semibold text-slate-400 dark:text-slate-500',
                            index === 0 ? 'rounded-l-xl' : '',
                            index === expenses.links.length - 1 ? 'rounded-r-xl' : '',
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
                <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-800">
                    <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-4">{{ t('hr.reject_expense') }}</h3>
                    <form @submit.prevent="submitReject" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.rejection_reason') }} *</label>
                            <textarea v-model="rejectForm.rejection_reason" rows="3" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600 sm:text-sm"></textarea>
                            <p v-if="rejectForm.errors.rejection_reason" class="mt-1 text-xs text-rose-600">{{ rejectForm.errors.rejection_reason }}</p>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showRejectModal = false" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-200 hover:bg-slate-50 dark:bg-slate-700 dark:text-slate-300 dark:ring-slate-600">
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

        <!-- New Expense Modal -->
        <Teleport to="body">
            <div v-if="showNewModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto">
                <div class="fixed inset-0 bg-slate-900/50" @click="showNewModal = false"></div>
                <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-800 my-8">
                    <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-4">{{ t('hr.new_expense') }}</h3>
                    <form @submit.prevent="submitNew" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.employee') }} *</label>
                                <select v-model="newForm.employee_id" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600 sm:text-sm">
                                    <option value="">{{ t('hr.select_employee') }}</option>
                                    <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.last_name }} {{ emp.first_name }}</option>
                                </select>
                                <p v-if="newForm.errors.employee_id" class="mt-1 text-xs text-rose-600">{{ newForm.errors.employee_id }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.category') }} *</label>
                                <select v-model="newForm.expense_category_id" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600 sm:text-sm">
                                    <option value="">{{ t('hr.select_category') }}</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                </select>
                                <p v-if="newForm.errors.expense_category_id" class="mt-1 text-xs text-rose-600">{{ newForm.errors.expense_category_id }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.expense_date') }} *</label>
                                <input v-model="newForm.date" type="date" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600 sm:text-sm" />
                                <p v-if="newForm.errors.date" class="mt-1 text-xs text-rose-600">{{ newForm.errors.date }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.payment_method') }}</label>
                                <select v-model="newForm.payment_method" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600 sm:text-sm">
                                    <option value="">—</option>
                                    <option value="card">{{ t('hr.payment_card') }}</option>
                                    <option value="cash">{{ t('hr.payment_cash') }}</option>
                                    <option value="transfer">{{ t('hr.payment_transfer') }}</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.vendor') }} *</label>
                            <input v-model="newForm.vendor" type="text" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600 sm:text-sm" />
                            <p v-if="newForm.errors.vendor" class="mt-1 text-xs text-rose-600">{{ newForm.errors.vendor }}</p>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.amount_ht') }} *</label>
                                <input v-model="newForm.amount_ht" type="number" step="0.01" min="0.01" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600 sm:text-sm" />
                                <p v-if="newForm.errors.amount_ht" class="mt-1 text-xs text-rose-600">{{ newForm.errors.amount_ht }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.vat_rate') }} (%)</label>
                                <input v-model="newForm.vat_rate" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600 sm:text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.amount_ttc') }}</label>
                                <div class="mt-1 flex items-center rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-semibold text-slate-900 ring-1 ring-inset ring-slate-200 dark:bg-slate-700 dark:text-white dark:ring-slate-600">
                                    {{ formatAmount(newAmountTtc) }}
                                </div>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ t('hr.amount_vat') }}: {{ formatAmount(newAmountVat) }}</p>
                            </div>
                        </div>
                        <div v-if="newMaxAmountExceeded" class="flex items-start gap-2 rounded-lg bg-amber-50 p-3 dark:bg-amber-900/20">
                            <svg class="h-5 w-5 flex-shrink-0 text-amber-500 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                            </svg>
                            <p class="text-sm text-amber-700 dark:text-amber-400">
                                {{ t('hr.max_amount_exceeded', { max: formatAmount(newCategoryMaxAmount) }) }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.description') }}</label>
                            <textarea v-model="newForm.description" rows="2" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600 sm:text-sm"></textarea>
                        </div>
                        <!-- Receipts (multi) -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.receipts') }}</label>
                            <div v-if="newForm.receipts.length > 0" class="mt-1 space-y-1.5">
                                <div v-for="(file, index) in newForm.receipts" :key="index" class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 ring-1 ring-inset ring-slate-200 dark:bg-slate-700 dark:ring-slate-600">
                                    <span class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 truncate">
                                        <svg class="h-4 w-4 flex-shrink-0 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M3 3.5A1.5 1.5 0 014.5 2h6.879a1.5 1.5 0 011.06.44l4.122 4.12A1.5 1.5 0 0117 7.622V16.5a1.5 1.5 0 01-1.5 1.5h-11A1.5 1.5 0 013 16.5v-13z" />
                                        </svg>
                                        {{ file.name }}
                                    </span>
                                    <button type="button" @click="removeNewReceipt(index)" class="rounded-lg p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/20 dark:hover:text-rose-400">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <input type="file" @input="addNewReceipts" multiple accept=".jpg,.jpeg,.png,.pdf" class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-primary-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-primary-700 hover:file:bg-primary-100 dark:text-slate-400 dark:file:bg-primary-900/30 dark:file:text-primary-400" />
                            <p v-if="newForm.errors['receipts.0']" class="mt-1 text-xs text-rose-600">{{ newForm.errors['receipts.0'] }}</p>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showNewModal = false" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-200 hover:bg-slate-50 dark:bg-slate-700 dark:text-slate-300 dark:ring-slate-600">
                                {{ t('cancel') }}
                            </button>
                            <button type="submit" :disabled="newForm.processing" class="rounded-xl bg-primary-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-600 disabled:opacity-50">
                                {{ t('create') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Edit Expense Modal -->
        <Teleport to="body">
            <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto">
                <div class="fixed inset-0 bg-slate-900/50" @click="showEditModal = false"></div>
                <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-800 my-8">
                    <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-1">{{ t('hr.edit_expense') }}</h3>
                    <p v-if="editingExpense?.status === 'rejected'" class="mb-4 text-sm text-amber-600 dark:text-amber-400">
                        {{ t('hr.resubmit_info') }}
                    </p>
                    <p v-else class="mb-4 text-sm text-slate-500 dark:text-slate-400">
                        {{ editingExpense?.employee?.last_name }} {{ editingExpense?.employee?.first_name }}
                    </p>
                    <form @submit.prevent="submitEdit" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.category') }} *</label>
                                <select v-model="editForm.expense_category_id" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600 sm:text-sm">
                                    <option value="">{{ t('hr.select_category') }}</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.expense_date') }} *</label>
                                <input v-model="editForm.date" type="date" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600 sm:text-sm" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.vendor') }} *</label>
                                <input v-model="editForm.vendor" type="text" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600 sm:text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.payment_method') }}</label>
                                <select v-model="editForm.payment_method" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600 sm:text-sm">
                                    <option value="">—</option>
                                    <option value="card">{{ t('hr.payment_card') }}</option>
                                    <option value="cash">{{ t('hr.payment_cash') }}</option>
                                    <option value="transfer">{{ t('hr.payment_transfer') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.amount_ht') }} *</label>
                                <input v-model="editForm.amount_ht" type="number" step="0.01" min="0.01" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600 sm:text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.vat_rate') }} (%)</label>
                                <input v-model="editForm.vat_rate" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600 sm:text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.amount_ttc') }}</label>
                                <div class="mt-1 flex items-center rounded-xl bg-slate-50 px-3 py-1.5 text-sm font-semibold text-slate-900 ring-1 ring-inset ring-slate-200 dark:bg-slate-700 dark:text-white dark:ring-slate-600">
                                    {{ formatAmount(editAmountTtc) }}
                                </div>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ t('hr.amount_vat') }}: {{ formatAmount(editAmountVat) }}</p>
                            </div>
                        </div>
                        <div v-if="editMaxAmountExceeded" class="flex items-start gap-2 rounded-lg bg-amber-50 p-3 dark:bg-amber-900/20">
                            <svg class="h-5 w-5 flex-shrink-0 text-amber-500 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                            </svg>
                            <p class="text-sm text-amber-700 dark:text-amber-400">
                                {{ t('hr.max_amount_exceeded', { max: formatAmount(editCategoryMaxAmount) }) }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.description') }}</label>
                            <textarea v-model="editForm.description" rows="2" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600 sm:text-sm"></textarea>
                        </div>
                        <!-- Existing receipts -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.receipts') }}</label>
                            <div v-if="editingExpense?.receipts?.length > 0" class="mt-1 space-y-1.5">
                                <div v-for="receipt in editingExpense.receipts" :key="receipt.id" class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 ring-1 ring-inset ring-slate-200 dark:bg-slate-700 dark:ring-slate-600">
                                    <a :href="`/storage/${receipt.file_path}`" target="_blank" class="flex items-center gap-2 text-sm text-primary-600 hover:underline dark:text-primary-400 truncate">
                                        <svg class="h-4 w-4 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M3 3.5A1.5 1.5 0 014.5 2h6.879a1.5 1.5 0 011.06.44l4.122 4.12A1.5 1.5 0 0117 7.622V16.5a1.5 1.5 0 01-1.5 1.5h-11A1.5 1.5 0 013 16.5v-13z" />
                                        </svg>
                                        {{ receipt.original_name }}
                                    </a>
                                    <button type="button" @click="deleteExistingReceipt(receipt.id)" class="rounded-lg p-1 text-slate-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/20 dark:hover:text-rose-400" :title="t('hr.remove_receipt')">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <!-- New files to add -->
                            <div v-if="editForm.receipts.length > 0" class="mt-1.5 space-y-1.5">
                                <div v-for="(file, index) in editForm.receipts" :key="'new-' + index" class="flex items-center justify-between rounded-lg bg-emerald-50 px-3 py-2 ring-1 ring-inset ring-emerald-200 dark:bg-emerald-900/20 dark:ring-emerald-800">
                                    <span class="flex items-center gap-2 text-sm text-emerald-700 dark:text-emerald-400 truncate">
                                        <svg class="h-4 w-4 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                                        </svg>
                                        {{ file.name }}
                                    </span>
                                    <button type="button" @click="removeEditReceipt(index)" class="rounded-lg p-1 text-emerald-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/20 dark:hover:text-rose-400">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <input type="file" @input="addEditReceipts" multiple accept=".jpg,.jpeg,.png,.pdf" class="mt-1.5 block w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-primary-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-primary-700 hover:file:bg-primary-100 dark:text-slate-400 dark:file:bg-primary-900/30 dark:file:text-primary-400" />
                        </div>
                        <div v-if="editingExpense?.rejection_reason" class="rounded-lg bg-rose-50 p-3 dark:bg-rose-900/20">
                            <p class="text-xs font-medium text-rose-700 dark:text-rose-400">{{ t('hr.previous_rejection_reason') }}</p>
                            <p class="mt-1 text-sm text-rose-600 dark:text-rose-300">{{ editingExpense.rejection_reason }}</p>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showEditModal = false" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-200 hover:bg-slate-50 dark:bg-slate-700 dark:text-slate-300 dark:ring-slate-600">
                                {{ t('cancel') }}
                            </button>
                            <button type="submit" :disabled="editForm.processing" class="rounded-xl bg-primary-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-600 disabled:opacity-50">
                                {{ editingExpense?.status === 'rejected' ? t('hr.resubmit_request') : t('save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import EmployeePortalLayout from '@/Layouts/EmployeePortalLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    expenses: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
});

const showModal = ref(false);
const form = useForm({
    expense_category_id: '',
    date: '',
    description: '',
    vendor: '',
    amount_ht: '',
    vat_rate: 17,
    payment_method: 'card',
    receipts: [],
});

const submitExpense = () => {
    form.post(route('employee-portal.expenses.store'), {
        forceFormData: true,
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        },
    });
};

const deleteExpense = (id) => {
    if (confirm(t('employee_portal.confirm_delete_expense'))) {
        router.delete(route('employee-portal.expenses.destroy', id));
    }
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('fr-FR');
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount);
};

const getStatusClass = (status) => {
    const classes = {
        pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        rejected: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
    };
    return classes[status] || classes.pending;
};

const handleFileChange = (e) => {
    form.receipts = Array.from(e.target.files);
};
</script>

<template>
    <Head :title="t('employee_portal.my_expenses')" />

    <EmployeePortalLayout>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">{{ t('employee_portal.my_expenses') }}</h1>
            <button
                @click="showModal = true"
                class="rounded-xl bg-primary-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-600"
            >
                + {{ t('employee_portal.submit_expense') }}
            </button>
        </div>

        <!-- Expenses Table -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800 overflow-hidden">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">{{ t('hr.date') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">{{ t('hr.category') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">{{ t('hr.vendor') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">{{ t('hr.amount_ttc') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">{{ t('hr.status') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">{{ t('actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <tr v-for="expense in expenses.data" :key="expense.id">
                        <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ formatDate(expense.date) }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ expense.category?.name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ expense.vendor }}</td>
                        <td class="px-4 py-3 text-sm text-right font-medium text-slate-900 dark:text-white">{{ formatCurrency(expense.amount_ttc) }}</td>
                        <td class="px-4 py-3">
                            <span :class="getStatusClass(expense.status)" class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium">
                                {{ t('hr.status_' + expense.status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button
                                v-if="expense.status === 'pending'"
                                @click="deleteExpense(expense.id)"
                                class="text-xs text-slate-500 hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-400"
                            >
                                {{ t('delete') }}
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!expenses.data?.length">
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                            {{ t('hr.no_data') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/50" @click="showModal = false"></div>
            <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6 w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">{{ t('employee_portal.submit_expense') }}</h2>
                <form @submit.prevent="submitExpense" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('hr.category') }}</label>
                            <select v-model="form.expense_category_id" class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                                <option value="">—</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                            <p v-if="form.errors.expense_category_id" class="mt-1 text-xs text-rose-600">{{ form.errors.expense_category_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('hr.date') }}</label>
                            <input v-model="form.date" type="date" class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white" />
                            <p v-if="form.errors.date" class="mt-1 text-xs text-rose-600">{{ form.errors.date }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('hr.vendor') }}</label>
                        <input v-model="form.vendor" type="text" class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white" />
                        <p v-if="form.errors.vendor" class="mt-1 text-xs text-rose-600">{{ form.errors.vendor }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('hr.description') }}</label>
                        <textarea v-model="form.description" rows="2" class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white"></textarea>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('hr.amount_ht') }}</label>
                            <input v-model="form.amount_ht" type="number" step="0.01" min="0.01" class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white" />
                            <p v-if="form.errors.amount_ht" class="mt-1 text-xs text-rose-600">{{ form.errors.amount_ht }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('hr.vat_rate') }} %</label>
                            <input v-model="form.vat_rate" type="number" step="0.01" min="0" max="100" class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('hr.payment_method') }}</label>
                            <select v-model="form.payment_method" class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                                <option value="card">{{ t('hr.card') }}</option>
                                <option value="cash">{{ t('hr.cash') }}</option>
                                <option value="transfer">{{ t('hr.transfer') }}</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ t('hr.receipts') }}</label>
                        <input type="file" multiple accept=".jpg,.jpeg,.png,.pdf" @change="handleFileChange" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100" />
                    </div>
                    <div class="flex justify-end space-x-3 pt-2">
                        <button type="button" @click="showModal = false" class="rounded-xl px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                            {{ t('cancel') }}
                        </button>
                        <button type="submit" :disabled="form.processing" class="rounded-xl bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600 disabled:opacity-50">
                            {{ t('employee_portal.submit_expense') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </EmployeePortalLayout>
</template>

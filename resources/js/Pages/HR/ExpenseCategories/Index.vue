<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import HRNav from '@/Components/HRNav.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    categories: { type: Array, required: true },
});

const showModal = ref(false);
const editing = ref(null);

const form = useForm({
    name: '',
    color: '#3b82f6',
    max_amount: '',
    is_active: true,
});

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.color = '#3b82f6';
    form.is_active = true;
    showModal.value = true;
};

const openEdit = (cat) => {
    editing.value = cat;
    form.name = cat.name;
    form.color = cat.color || '#3b82f6';
    form.max_amount = cat.max_amount || '';
    form.is_active = cat.is_active;
    showModal.value = true;
};

const submit = () => {
    if (editing.value) {
        form.put(route('hr.expense-categories.update', editing.value.id), {
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post(route('hr.expense-categories.store'), {
            onSuccess: () => { showModal.value = false; },
        });
    }
};

const deleteCategory = (cat) => {
    if (confirm(t('hr.confirm_delete_expense_category'))) {
        router.delete(route('hr.expense-categories.destroy', cat.id));
    }
};

const formatAmount = (amount) => {
    if (!amount) return '—';
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount);
};
</script>

<template>
    <Head :title="t('hr.expense_categories')" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ t('hr.expense_categories') }}
                </h1>
                <button
                    @click="openCreate"
                    class="inline-flex items-center rounded-xl bg-primary-500 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-600"
                >
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                    </svg>
                    {{ t('hr.new_expense_category') }}
                </button>
            </div>
        </template>

        <HRNav class="mb-6" />

        <div class="py-6">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-slate-700">
                            <tr>
                                <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 dark:text-white sm:pl-6">{{ t('hr.name') }}</th>
                                <th class="px-3 py-3.5 text-center text-sm font-semibold text-slate-900 dark:text-white">{{ t('hr.max_amount') }}</th>
                                <th class="hidden px-3 py-3.5 text-center text-sm font-semibold text-slate-900 dark:text-white sm:table-cell">{{ t('hr.status') }}</th>
                                <th class="px-3 py-3.5 text-center text-sm font-semibold text-slate-900 dark:text-white">{{ t('hr.expenses_count') }}</th>
                                <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">{{ t('actions') }}</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-800">
                            <tr v-if="categories.length === 0">
                                <td colspan="5" class="py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                                    {{ t('hr.no_expense_categories') }}
                                </td>
                            </tr>
                            <tr v-for="cat in categories" :key="cat.id" class="hover:bg-slate-50 dark:hover:bg-slate-700">
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 sm:pl-6">
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="h-3 w-3 rounded-full flex-shrink-0"
                                            :style="{ backgroundColor: cat.color || '#94a3b8' }"
                                        ></span>
                                        <span class="font-medium text-slate-900 dark:text-white">{{ cat.name }}</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-center text-slate-700 dark:text-slate-300">
                                    {{ formatAmount(cat.max_amount) }}
                                </td>
                                <td class="hidden whitespace-nowrap px-3 py-4 text-sm text-center sm:table-cell">
                                    <span v-if="cat.is_active" class="inline-flex items-center rounded-xl bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">{{ t('hr.active') }}</span>
                                    <span v-else class="inline-flex items-center rounded-xl bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-700 dark:text-slate-400">{{ t('hr.inactive') }}</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-center text-slate-500 dark:text-slate-400">
                                    {{ cat.expense_reports_count }}
                                </td>
                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm sm:pr-6">
                                    <div class="flex items-center justify-end space-x-1">
                                        <button
                                            @click="openEdit(cat)"
                                            class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-primary-600 dark:hover:bg-slate-700 dark:hover:text-primary-400"
                                        >
                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" />
                                            </svg>
                                        </button>
                                        <button
                                            v-if="cat.expense_reports_count === 0"
                                            @click="deleteCategory(cat)"
                                            class="rounded-lg p-2 text-slate-400 hover:bg-pink-50 hover:text-pink-600 dark:hover:bg-pink-900/20 dark:hover:text-pink-400"
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
            </div>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="fixed inset-0 bg-slate-900/50" @click="showModal = false"></div>
                <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-800">
                    <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-4">
                        {{ editing ? t('hr.edit_expense_category') : t('hr.new_expense_category') }}
                    </h3>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.name') }} *</label>
                            <input v-model="form.name" type="text" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600 sm:text-sm" />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.color') }}</label>
                                <input v-model="form.color" type="color" class="mt-1 h-10 w-20 cursor-pointer rounded-lg border-0" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.max_amount') }}</label>
                                <input v-model="form.max_amount" type="number" min="0" step="0.01" placeholder="—" class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-primary-500 dark:bg-slate-700 dark:text-white dark:ring-slate-600 sm:text-sm" />
                            </div>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300 text-primary-500 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-700" />
                            <span class="text-sm text-slate-700 dark:text-slate-300">{{ t('hr.active') }}</span>
                        </label>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showModal = false" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-200 hover:bg-slate-50 dark:bg-slate-700 dark:text-slate-300 dark:ring-slate-600">
                                {{ t('cancel') }}
                            </button>
                            <button type="submit" :disabled="form.processing" class="rounded-xl bg-primary-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-600 disabled:opacity-50">
                                {{ editing ? t('save') : t('create') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

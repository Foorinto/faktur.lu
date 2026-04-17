<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import HRNav from '@/Components/HRNav.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    leaveTypes: { type: Array, required: true },
});

const showModal = ref(false);
const editing = ref(null);

const form = useForm({
    name: '',
    color: '#3b82f6',
    default_days_per_year: 26,
    requires_justification: false,
    is_paid: true,
});

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.color = '#3b82f6';
    form.default_days_per_year = 26;
    form.is_paid = true;
    showModal.value = true;
};

const openEdit = (leaveType) => {
    editing.value = leaveType;
    form.name = leaveType.name;
    form.color = leaveType.color || '#3b82f6';
    form.default_days_per_year = leaveType.default_days_per_year;
    form.requires_justification = leaveType.requires_justification;
    form.is_paid = leaveType.is_paid;
    showModal.value = true;
};

const submit = () => {
    if (editing.value) {
        form.put(route('hr.leave-types.update', editing.value.id), {
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post(route('hr.leave-types.store'), {
            onSuccess: () => { showModal.value = false; },
        });
    }
};

const deleteLeaveType = (leaveType) => {
    if (confirm(t('hr.confirm_delete_leave_type', { name: leaveType.name }))) {
        router.delete(route('hr.leave-types.destroy', leaveType.id));
    }
};
</script>

<template>
    <Head :title="t('hr.leave_types')" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                {{ t('hr.leave_types') }}
            </h1>
        </template>
        <template #header-actions>
            <button
                @click="openCreate"
                class="inline-flex items-center rounded-xl bg-accent-rose px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-pink-500"
            >
                <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                </svg>
                {{ t('hr.new_leave_type') }}
            </button>
        </template>

        <HRNav class="mb-6" />

        <div class="py-6">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-x-auto rounded-2xl bg-white shadow dark:bg-surface-card">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-gray-800">
                            <tr>
                                <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 dark:text-white sm:pl-6">{{ t('hr.name') }}</th>
                                <th class="px-3 py-3.5 text-center text-sm font-semibold text-slate-900 dark:text-white">{{ t('hr.default_days') }}</th>
                                <th class="hidden px-3 py-3.5 text-center text-sm font-semibold text-slate-900 dark:text-white sm:table-cell">{{ t('hr.paid') }}</th>
                                <th class="hidden px-3 py-3.5 text-center text-sm font-semibold text-slate-900 dark:text-white sm:table-cell">{{ t('hr.justification') }}</th>
                                <th class="px-3 py-3.5 text-center text-sm font-semibold text-slate-900 dark:text-white">{{ t('hr.requests_count') }}</th>
                                <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">{{ t('actions') }}</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-surface-card">
                            <tr v-if="leaveTypes.length === 0">
                                <td colspan="6" class="py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                                    {{ t('hr.no_leave_types') }}
                                </td>
                            </tr>
                            <tr v-for="lt in leaveTypes" :key="lt.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 sm:pl-6">
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="h-3 w-3 rounded-full flex-shrink-0"
                                            :style="{ backgroundColor: lt.color || '#94a3b8' }"
                                        ></span>
                                        <span class="font-medium text-slate-900 dark:text-white">{{ lt.name }}</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-center text-slate-700 dark:text-slate-300">
                                    {{ lt.default_days_per_year }} {{ t('hr.days') }}
                                </td>
                                <td class="hidden whitespace-nowrap px-3 py-4 text-sm text-center sm:table-cell">
                                    <span v-if="lt.is_paid" class="inline-flex items-center rounded-xl bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">{{ t('hr.yes') }}</span>
                                    <span v-else class="inline-flex items-center rounded-xl bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 dark:bg-gray-800 dark:text-slate-400">{{ t('hr.no') }}</span>
                                </td>
                                <td class="hidden whitespace-nowrap px-3 py-4 text-sm text-center sm:table-cell">
                                    <span v-if="lt.requires_justification" class="inline-flex items-center rounded-xl bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">{{ t('hr.required') }}</span>
                                    <span v-else class="text-slate-400 dark:text-slate-500">—</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-center text-slate-500 dark:text-slate-400">
                                    {{ lt.leave_requests_count }}
                                </td>
                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm sm:pr-6">
                                    <div class="flex items-center justify-end space-x-1">
                                        <button
                                            @click="openEdit(lt)"
                                            class="rounded-lg p-2 text-slate-400 hover:bg-gray-50 hover:text-primary-600 dark:hover:bg-gray-800 dark:hover:text-primary-400"
                                        >
                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" />
                                            </svg>
                                        </button>
                                        <button
                                            v-if="lt.leave_requests_count === 0"
                                            @click="deleteLeaveType(lt)"
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
                <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-surface-card">
                    <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-4">
                        {{ editing ? t('hr.edit_leave_type') : t('hr.new_leave_type') }}
                    </h3>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.name') }} *</label>
                            <input v-model="form.name" type="text" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.color') }}</label>
                                <input v-model="form.color" type="color" class="mt-1 h-10 w-20 cursor-pointer rounded-lg border-0" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.default_days') }} *</label>
                                <input v-model="form.default_days_per_year" type="number" min="0" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                            </div>
                        </div>
                        <div class="flex items-center gap-6">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="form.is_paid" type="checkbox" class="rounded border-gray-300 text-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800" />
                                <span class="text-sm text-slate-700 dark:text-slate-300">{{ t('hr.paid') }}</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="form.requires_justification" type="checkbox" class="rounded border-gray-300 text-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800" />
                                <span class="text-sm text-slate-700 dark:text-slate-300">{{ t('hr.justification') }}</span>
                            </label>
                        </div>
                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end pt-2">
                            <button type="button" @click="showModal = false" class="w-full sm:w-auto justify-center rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-slate-300 dark:ring-slate-600">
                                {{ t('cancel') }}
                            </button>
                            <button type="submit" :disabled="form.processing" class="w-full sm:w-auto justify-center rounded-xl bg-accent-rose px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-pink-500 disabled:opacity-50">
                                {{ editing ? t('save') : t('create') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

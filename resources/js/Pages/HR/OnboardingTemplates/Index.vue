<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import HRNav from '@/Components/HRNav.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    templates: { type: Array, required: true },
});

const showModal = ref(false);
const editing = ref(null);

const form = useForm({
    name: '',
    items: [],
});

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.items = [{ title: '' }];
    showModal.value = true;
};

const openEdit = (template) => {
    editing.value = template;
    form.name = template.name;
    form.items = template.items.map(item => ({ title: item.title }));
    if (form.items.length === 0) {
        form.items = [{ title: '' }];
    }
    showModal.value = true;
};

const addItem = () => {
    form.items.push({ title: '' });
};

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

const submit = () => {
    const data = {
        name: form.name,
        items: form.items
            .filter(item => item.title.trim() !== '')
            .map(item => ({ title: item.title })),
    };

    if (editing.value) {
        form.transform(() => data).put(route('hr.onboarding-templates.update', editing.value.id), {
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.transform(() => data).post(route('hr.onboarding-templates.store'), {
            onSuccess: () => { showModal.value = false; },
        });
    }
};

const deleteTemplate = (template) => {
    if (confirm(t('hr.confirm_delete_template'))) {
        router.delete(route('hr.onboarding-templates.destroy', template.id));
    }
};
</script>

<template>
    <Head :title="t('hr.onboarding_templates')" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ t('hr.onboarding_templates') }}
                </h1>
                <button
                    @click="openCreate"
                    class="inline-flex items-center rounded-xl bg-accent-rose px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-pink-500"
                >
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                    </svg>
                    {{ t('hr.new_template') }}
                </button>
            </div>
        </template>

        <HRNav class="mb-6" />

        <div class="py-6">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-x-auto rounded-2xl bg-white shadow-xl shadow-gray-200/50 border border-gray-200 dark:bg-surface-card dark:border-gray-700 dark:shadow-gray-900/50">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-gray-800">
                            <tr>
                                <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 dark:text-white sm:pl-6">{{ t('hr.template_name') }}</th>
                                <th class="px-3 py-3.5 text-center text-sm font-semibold text-slate-900 dark:text-white">{{ t('hr.template_tasks') }}</th>
                                <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">{{ t('actions') }}</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-surface-card">
                            <tr v-if="templates.length === 0">
                                <td colspan="3" class="py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                                    {{ t('hr.no_templates') }}
                                </td>
                            </tr>
                            <tr v-for="tpl in templates" :key="tpl.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 sm:pl-6">
                                    <span class="font-medium text-slate-900 dark:text-white">{{ tpl.name }}</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-center text-slate-500 dark:text-slate-400">
                                    {{ tpl.items_count }}
                                </td>
                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm sm:pr-6">
                                    <div class="flex items-center justify-end space-x-1">
                                        <button
                                            @click="openEdit(tpl)"
                                            class="rounded-lg p-2 text-slate-400 hover:bg-gray-50 hover:text-primary-600 dark:hover:bg-gray-800 dark:hover:text-primary-400"
                                        >
                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="deleteTemplate(tpl)"
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
                <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl dark:bg-surface-card max-h-[90vh] overflow-y-auto">
                    <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-4">
                        {{ editing ? t('hr.edit_template') : t('hr.new_template') }}
                    </h3>
                    <form @submit.prevent="submit" class="space-y-4">
                        <!-- Template name -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('hr.template_name') }} *</label>
                            <input v-model="form.name" type="text" required class="mt-1 block w-full rounded-xl border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600 sm:text-sm" />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</p>
                        </div>

                        <!-- Template items -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ t('hr.template_tasks') }} *</label>
                            <div class="space-y-2">
                                <div v-for="(item, index) in form.items" :key="index" class="flex items-center gap-2">
                                    <input
                                        v-model="item.title"
                                        type="text"
                                        :placeholder="t('hr.task_title')"
                                        class="flex-1 rounded-xl border-0 py-1.5 text-sm text-slate-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-primary-500 dark:bg-gray-800 dark:text-white dark:ring-slate-600"
                                    />
                                    <button
                                        v-if="form.items.length > 1"
                                        type="button"
                                        @click="removeItem(index)"
                                        class="flex-shrink-0 rounded-lg p-1.5 text-slate-400 hover:bg-pink-50 hover:text-pink-600 dark:hover:bg-pink-900/20 dark:hover:text-pink-400"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M4 10a.75.75 0 01.75-.75h10.5a.75.75 0 010 1.5H4.75A.75.75 0 014 10z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="addItem"
                                class="mt-2 inline-flex items-center text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400"
                            >
                                <svg class="mr-1 h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                                </svg>
                                {{ t('hr.add_template_task') }}
                            </button>
                            <p v-if="form.errors.items" class="mt-1 text-xs text-rose-600">{{ form.errors.items }}</p>
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showModal = false" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-slate-300 dark:ring-slate-600">
                                {{ t('cancel') }}
                            </button>
                            <button type="submit" :disabled="form.processing" class="rounded-xl bg-accent-rose px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-pink-500 disabled:opacity-50">
                                {{ editing ? t('save') : t('create') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

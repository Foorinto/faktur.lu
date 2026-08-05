<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

defineProps({
    categories: { type: Array, default: () => [] },
});

const createForm = useForm({ label: '', pcn_account: '' });

const submitCreate = () => {
    createForm.post(route('settings.purchase-categories.store'), {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
};

// Édition en place : une seule ligne ouverte à la fois, pour que l'écran reste
// lisible même avec vingt catégories.
const editingId = ref(null);
const editForm = useForm({ label: '', pcn_account: '' });

const startEdit = (category) => {
    editingId.value = category.id;
    editForm.label = category.label;
    editForm.pcn_account = category.pcn_account ?? '';
};

const submitEdit = (category) => {
    editForm.put(route('settings.purchase-categories.update', category.id), {
        preserveScroll: true,
        onSuccess: () => { editingId.value = null; },
    });
};

const toggleActive = (category) => {
    router.put(
        route('settings.purchase-categories.update', category.id),
        { label: category.label, pcn_account: category.pcn_account, is_active: !category.is_active },
        { preserveScroll: true },
    );
};

const destroy = (category) => {
    if (!window.confirm(t('purchase_categories.confirm_delete', { label: category.label }))) return;

    router.delete(route('settings.purchase-categories.destroy', category.id), { preserveScroll: true });
};
</script>

<template>
    <Head :title="t('purchase_categories.title')" />

    <AppLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2">
                <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                    {{ t('purchase_categories.title') }}
                </h1>
                <Link :href="route('expenses.index')" class="text-sm text-slate-600 hover:text-slate-900 dark:text-slate-400">
                    ← {{ t('expenses') }}
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-3xl space-y-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ t('purchase_categories.subtitle') }}
            </p>

            <!-- Ajout -->
            <form
                @submit.prevent="submitCreate"
                class="flex flex-wrap items-end gap-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-surface-card"
            >
                <div class="min-w-[14rem] flex-1">
                    <label for="new-label" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        {{ t('purchase_categories.label') }}
                    </label>
                    <input
                        id="new-label"
                        v-model="createForm.label"
                        type="text"
                        required
                        :placeholder="t('purchase_categories.new_label_placeholder')"
                        class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                    <p v-if="createForm.errors.label" class="mt-1 text-sm text-pink-600">{{ createForm.errors.label }}</p>
                </div>

                <div class="w-40">
                    <label for="new-pcn" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        {{ t('purchase_categories.pcn_account') }}
                    </label>
                    <input
                        id="new-pcn"
                        v-model="createForm.pcn_account"
                        type="text"
                        inputmode="numeric"
                        placeholder="6111"
                        class="mt-1 block w-full rounded-xl border-gray-200 tabular-nums shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                </div>

                <button
                    type="submit"
                    :disabled="createForm.processing"
                    class="rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-50"
                >
                    {{ t('purchase_categories.add') }}
                </button>

                <p class="w-full text-xs text-slate-500 dark:text-slate-400">
                    {{ t('purchase_categories.pcn_optional') }}
                </p>
            </form>

            <!-- Liste -->
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-surface-card">
                <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                    <li
                        v-for="category in categories"
                        :key="category.id"
                        class="px-4 py-3"
                        :class="{ 'opacity-60': !category.is_active }"
                    >
                        <!-- Édition -->
                        <form v-if="editingId === category.id" @submit.prevent="submitEdit(category)" class="flex flex-wrap items-end gap-3">
                            <div class="min-w-[12rem] flex-1">
                                <input
                                    v-model="editForm.label"
                                    type="text"
                                    required
                                    class="block w-full rounded-xl border-gray-200 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                />
                            </div>
                            <input
                                v-model="editForm.pcn_account"
                                type="text"
                                inputmode="numeric"
                                placeholder="6111"
                                class="w-32 rounded-xl border-gray-200 text-sm tabular-nums dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                            <button type="submit" class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-primary-700">
                                {{ t('save') }}
                            </button>
                            <button type="button" @click="editingId = null" class="text-sm text-slate-500 hover:text-slate-700">
                                {{ t('cancel') }}
                            </button>
                        </form>

                        <!-- Affichage -->
                        <div v-else class="flex flex-wrap items-center gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="flex items-center gap-2 font-medium text-slate-900 dark:text-white">
                                    {{ category.label }}
                                    <span v-if="category.is_default" class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-normal text-slate-500 dark:bg-gray-800">
                                        {{ t('purchase_categories.default_badge') }}
                                    </span>
                                    <span v-if="!category.is_active" class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-normal text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                                        {{ t('purchase_categories.inactive_badge') }}
                                    </span>
                                </p>
                                <p class="mt-0.5 flex items-center gap-3 text-xs text-slate-400">
                                    <span v-if="category.pcn_account" class="tabular-nums">{{ category.pcn_account }}</span>
                                    <span>
                                        {{ category.expenses_count > 0
                                            ? t('purchase_categories.usage', { count: category.expenses_count })
                                            : t('purchase_categories.unused') }}
                                    </span>
                                </p>
                            </div>

                            <button type="button" @click="startEdit(category)" class="rounded-lg px-2 py-1 text-sm text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20">
                                {{ t('edit') }}
                            </button>

                            <button
                                type="button"
                                @click="toggleActive(category)"
                                :title="t('purchase_categories.deactivate_hint')"
                                class="rounded-lg px-2 py-1 text-sm text-slate-500 hover:bg-gray-100 dark:hover:bg-gray-800"
                            >
                                {{ category.is_active ? t('purchase_categories.deactivate') : t('purchase_categories.activate') }}
                            </button>

                            <!-- La suppression n'est offerte que si aucune dépense
                                 n'est rattachée : sinon elles perdraient leur
                                 libellé. Au-delà, c'est « Masquer » qui répond. -->
                            <button
                                v-if="category.expenses_count === 0"
                                type="button"
                                @click="destroy(category)"
                                class="rounded-lg px-2 py-1 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20"
                            >
                                {{ t('delete') }}
                            </button>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </AppLayout>
</template>

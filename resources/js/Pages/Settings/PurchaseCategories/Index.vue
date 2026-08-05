<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PcnAccountPicker from '@/Components/PcnAccountPicker.vue';
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
const createPicker = ref(null);

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
                        @blur="createPicker?.suggest()"
                        class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                    <p v-if="createForm.errors.label" class="mt-1 text-sm text-pink-600">{{ createForm.errors.label }}</p>
                </div>

                <div class="w-40">
                    <label for="new-pcn" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        {{ t('purchase_categories.pcn_account') }}
                    </label>
                    <PcnAccountPicker
                        id="new-pcn"
                        ref="createPicker"
                        v-model="createForm.pcn_account"
                        :suggest-from="createForm.label"
                        class="mt-1"
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
                        <form v-if="editingId === category.id" @submit.prevent="submitEdit(category)" class="flex flex-wrap items-center gap-3">
                            <div class="min-w-[12rem] flex-1">
                                <input
                                    v-model="editForm.label"
                                    type="text"
                                    required
                                    class="block w-full rounded-xl border-gray-200 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                />
                            </div>
                            <div class="w-44">
                                <PcnAccountPicker v-model="editForm.pcn_account" :suggest-from="editForm.label" />
                            </div>
                            <button type="submit" class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-primary-700">
                                {{ t('save') }}
                            </button>
                            <button
                                type="button"
                                @click="editingId = null"
                                class="rounded-lg px-3 py-1.5 text-sm text-slate-500 hover:bg-gray-100 hover:text-slate-700 dark:hover:bg-gray-800"
                            >
                                {{ t('cancel') }}
                            </button>
                        </form>

                        <!-- Affichage -->
                        <div v-else class="flex flex-wrap items-center gap-1">
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

                            <!-- Icônes plutôt que libellés, et celles déjà en
                                 usage ailleurs dans le produit : crayon et
                                 corbeille du catalogue, œil et œil barré des
                                 réglages e-mail. Jamais muettes pour autant :
                                 title pour la souris, sr-only pour les lecteurs
                                 d'écran et la navigation au clavier. -->
                            <button
                                type="button"
                                @click="startEdit(category)"
                                :title="t('edit')"
                                class="rounded-lg p-2 text-slate-400 transition-colors hover:bg-primary-50 hover:text-primary-600 dark:hover:bg-primary-900/20 dark:hover:text-primary-400"
                            >
                                <span class="sr-only">{{ t('edit') }}</span>
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                </svg>
                            </button>

                            <button
                                type="button"
                                @click="toggleActive(category)"
                                :title="category.is_active ? t('purchase_categories.deactivate_hint') : t('purchase_categories.activate')"
                                class="rounded-lg p-2 text-slate-400 transition-colors hover:bg-gray-100 hover:text-slate-600 dark:hover:bg-gray-800 dark:hover:text-slate-300"
                            >
                                <span class="sr-only">
                                    {{ category.is_active ? t('purchase_categories.deactivate') : t('purchase_categories.activate') }}
                                </span>
                                <svg v-if="category.is_active" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>

                            <!-- La suppression n'est offerte que si aucune dépense
                                 n'est rattachée : sinon elles perdraient leur
                                 libellé. Au-delà, c'est le masquage qui répond. -->
                            <button
                                type="button"
                                :disabled="category.expenses_count > 0"
                                @click="destroy(category)"
                                :title="category.expenses_count > 0
                                    ? t('purchase_categories.delete_blocked', { count: category.expenses_count })
                                    : t('delete')"
                                class="rounded-lg p-2 text-slate-400 transition-colors hover:bg-red-50 hover:text-red-600 disabled:cursor-not-allowed disabled:text-slate-200 disabled:hover:bg-transparent disabled:hover:text-slate-200 dark:hover:bg-red-900/20 dark:disabled:text-slate-700"
                            >
                                <span class="sr-only">
                                    {{ category.expenses_count > 0
                                        ? t('purchase_categories.delete_blocked', { count: category.expenses_count })
                                        : t('delete') }}
                                </span>
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                            </button>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </AppLayout>
</template>

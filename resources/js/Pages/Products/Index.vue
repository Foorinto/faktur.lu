<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    products: { type: Object, required: true },
    canCreate: { type: Boolean, default: true },
    quota: { type: Object, default: () => ({ limit: null, used: 0 }) },
    units: { type: Array, default: () => [] },
    vatRates: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ type: null }) },
    typeCounts: { type: Object, default: () => ({}) },
});

// Onglets de famille. « Non classé » n'apparaît que s'il existe des articles
// antérieurs au champ : sur un catalogue neuf, l'onglet serait un décor.
const typeTabs = computed(() => {
    const tabs = [
        { value: null, label: t('products.type_all'), count: props.typeCounts.all ?? 0 },
        { value: 'product', label: t('products.type_product'), count: props.typeCounts.product ?? 0 },
        { value: 'service', label: t('products.type_service'), count: props.typeCounts.service ?? 0 },
    ];

    if ((props.typeCounts.unclassified ?? 0) > 0) {
        tabs.push({ value: 'unclassified', label: t('products.type_unclassified'), count: props.typeCounts.unclassified });
    }

    return tabs;
});

const setType = (value) => {
    router.get(route('products.index'), value ? { type: value } : {}, {
        preserveState: true,
        replace: true,
    });
};

const typeLabel = (value) => {
    if (value === 'product') return t('products.type_product');
    if (value === 'service') return t('products.type_service');
    return null;
};

const unitLabel = (value) => props.units.find((u) => u.value === value)?.label ?? value;

const quotaLabel = computed(() => {
    if (props.quota.limit === null || props.quota.limit === undefined) {
        return t('products.quota_unlimited');
    }
    return t('products.quota_used', { used: props.quota.used, limit: props.quota.limit });
});

const formatPrice = (value) =>
    new Intl.NumberFormat('fr-LU', { style: 'currency', currency: 'EUR' }).format(Number(value || 0));

// --- Sélection multiple ---------------------------------------------------
// La sélection ne porte que sur la page affichée : « tout sélectionner » sur
// une liste paginée laisserait croire que l'action touche l'ensemble du
// catalogue, alors qu'elle ne verrait que 20 lignes.
const selected = ref([]);

watch(() => props.products.data, () => { selected.value = []; });

const pageIds = computed(() => props.products.data.map((p) => p.id));
const allSelected = computed(() => pageIds.value.length > 0 && selected.value.length === pageIds.value.length);

const toggleAll = () => {
    selected.value = allSelected.value ? [] : [...pageIds.value];
};

const bulkType = ref('');
const bulkVat = ref('');

const applyBulk = () => {
    const payload = { ids: selected.value };

    // '' = « ne pas toucher » ; 'unclassified' = « déclasser », donc null.
    if (bulkType.value !== '') payload.type = bulkType.value === 'unclassified' ? null : bulkType.value;
    if (bulkVat.value !== '') payload.vat_rate = Number(bulkVat.value);

    router.post(route('products.bulk-update'), payload, {
        preserveScroll: true,
        onSuccess: () => { selected.value = []; bulkType.value = ''; bulkVat.value = ''; },
    });
};

const bulkDelete = () => {
    if (!window.confirm(t('products.bulk_confirm_delete', { count: selected.value.length }))) return;

    router.post(route('products.bulk-delete'), { ids: selected.value }, {
        preserveScroll: true,
        onSuccess: () => { selected.value = []; },
    });
};

const destroy = (product) => {
    if (window.confirm(t('products.confirm_delete', { name: product.designation }))) {
        router.delete(route('products.destroy', product.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head :title="t('products.title')" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900 dark:text-white">{{ t('products.title') }}</h1>
                    <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ quotaLabel }}</p>
                </div>

                <div class="flex items-center gap-2">
                <!-- L'import reste accessible même au plafond : il sert aussi à
                     mettre à jour des articles existants, ce que le quota ne
                     restreint pas. -->
                <Link
                    :href="route('products.import.index')"
                    class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-gray-50 dark:border-gray-700 dark:text-slate-300 dark:hover:bg-gray-800"
                >
                    {{ t('products.import.title') }}
                </Link>
                <Link
                    v-if="canCreate"
                    :href="route('products.create')"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700"
                >
                    + {{ t('products.new') }}
                </Link>
                <Link
                    v-else
                    :href="route('subscription.index')"
                    class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-600"
                    :title="t('products.limit_reached')"
                >
                    ⚡ {{ t('products.upgrade') }}
                </Link>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-5xl">
            <!-- Filtre par famille -->
            <div v-if="typeTabs.length > 1" class="mb-4 flex flex-wrap gap-2">
                <button
                    v-for="tab in typeTabs"
                    :key="tab.value ?? 'all'"
                    type="button"
                    @click="setType(tab.value)"
                    :class="[
                        'rounded-xl px-3 py-1.5 text-sm font-medium transition-colors',
                        (filters.type ?? null) === tab.value
                            ? 'bg-primary-600 text-white'
                            : 'bg-gray-100 text-slate-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-slate-300 dark:hover:bg-gray-700',
                    ]"
                >
                    {{ tab.label }}
                    <span class="ml-1 tabular-nums opacity-70">{{ tab.count }}</span>
                </button>
            </div>

            <!-- Actions groupées -->
            <div
                v-if="selected.length"
                class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-primary-200 bg-primary-50 px-4 py-3 dark:border-primary-900 dark:bg-primary-900/20"
            >
                <span class="text-sm font-semibold text-primary-800 dark:text-primary-200">
                    {{ t('products.selected', { count: selected.length }) }}
                </span>

                <select v-model="bulkType" class="rounded-lg border-gray-200 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <option value="">{{ t('products.bulk_set_type') }}…</option>
                    <option value="product">{{ t('products.type_product') }}</option>
                    <option value="service">{{ t('products.type_service') }}</option>
                    <option value="unclassified">{{ t('products.type_unclassified') }}</option>
                </select>

                <select v-model="bulkVat" class="rounded-lg border-gray-200 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <option value="">{{ t('products.bulk_set_vat') }}…</option>
                    <option v-for="rate in vatRates" :key="rate" :value="rate">{{ rate }} %</option>
                </select>

                <button
                    type="button"
                    :disabled="bulkType === '' && bulkVat === ''"
                    @click="applyBulk"
                    class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-40"
                >
                    {{ t('products.bulk_apply') }}
                </button>

                <button type="button" @click="bulkDelete" class="ml-auto rounded-lg px-3 py-1.5 text-sm font-semibold text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                    {{ t('products.bulk_delete') }}
                </button>
            </div>

            <!-- Empty -->
            <div
                v-if="products.data.length === 0"
                class="rounded-2xl border border-dashed border-gray-200 bg-white p-12 text-center dark:border-gray-800 dark:bg-gray-900"
            >
                <p class="text-lg font-medium text-slate-900 dark:text-white">{{ t('products.empty_title') }}</p>
                <p class="mx-auto mt-1 max-w-md text-sm text-slate-500 dark:text-slate-400">{{ t('products.empty_desc') }}</p>
                <Link
                    v-if="canCreate"
                    :href="route('products.create')"
                    class="mt-5 inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700"
                >
                    + {{ t('products.new') }}
                </Link>
            </div>

            <!-- List -->
            <div v-else class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                        <thead class="bg-gray-50 dark:bg-gray-800/50">
                            <tr class="text-left text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                <th class="px-4 py-3">
                                    <input
                                        type="checkbox"
                                        :checked="allSelected"
                                        @change="toggleAll"
                                        :aria-label="t('products.select_all')"
                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                    />
                                </th>
                                <th class="px-4 py-3">{{ t('products.designation') }}</th>
                                <th class="px-4 py-3">{{ t('products.reference') }}</th>
                                <th class="px-4 py-3">{{ t('products.type') }}</th>
                                <th class="px-4 py-3 text-right">{{ t('products.unit_price_ht') }}</th>
                                <th class="px-4 py-3 text-right">{{ t('products.vat_rate') }}</th>
                                <th class="px-4 py-3">{{ t('products.unit') }}</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800/60">
                            <tr v-for="product in products.data" :key="product.id" class="text-sm text-slate-700 dark:text-slate-300">
                                <td class="px-4 py-3">
                                    <input
                                        type="checkbox"
                                        :value="product.id"
                                        v-model="selected"
                                        :aria-label="product.designation"
                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                    />
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2 font-medium text-slate-900 dark:text-white">
                                        {{ product.designation }}
                                        <span v-if="!product.is_active" class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-normal text-slate-500 dark:bg-gray-800">
                                            {{ t('products.inactive') }}
                                        </span>
                                    </div>
                                    <p v-if="product.description" class="mt-0.5 line-clamp-1 text-xs text-slate-400">{{ product.description }}</p>
                                </td>
                                <td class="px-4 py-3 text-slate-500">{{ product.reference || '—' }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        v-if="typeLabel(product.type)"
                                        class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-slate-600 dark:bg-gray-800 dark:text-slate-300"
                                    >{{ typeLabel(product.type) }}</span>
                                    <span v-else class="text-slate-400">—</span>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ formatPrice(product.unit_price_ht) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ Number(product.vat_rate) }}%</td>
                                <td class="px-4 py-3 text-slate-500">{{ unitLabel(product.unit) }}</td>
                                <td class="px-4 py-3">
                                    <!-- Icônes seules, mais jamais muettes : title
                                         pour la souris, sr-only pour les lecteurs
                                         d'écran et la navigation au clavier. -->
                                    <div class="flex items-center justify-end gap-1">
                                        <Link
                                            :href="route('products.edit', product.id)"
                                            :title="t('edit')"
                                            class="rounded-lg p-2 text-slate-400 transition-colors hover:bg-primary-50 hover:text-primary-600 dark:hover:bg-primary-900/20 dark:hover:text-primary-400"
                                        >
                                            <span class="sr-only">{{ t('edit') }}</span>
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                            </svg>
                                        </Link>
                                        <button
                                            type="button"
                                            :title="t('delete')"
                                            class="rounded-lg p-2 text-slate-400 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20"
                                            @click="destroy(product)"
                                        >
                                            <span class="sr-only">{{ t('delete') }}</span>
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <Pagination v-if="products.data.length" :links="products.links" class="mt-6" />
        </div>
    </AppLayout>
</template>

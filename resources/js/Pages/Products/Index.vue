<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import RowAction from '@/Components/RowAction.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

// --- Variantes (FEAT-120) ---
// Les familles sont repliées par défaut : une famille de 45 nuances ne doit pas
// noyer le catalogue de celui qui vient y chercher autre chose.
const depliees = ref(new Set());

const basculer = (id) => {
    const copie = new Set(depliees.value);
    copie.has(id) ? copie.delete(id) : copie.add(id);
    depliees.value = copie;
};

const familleEnCours = ref(null);
const formVariantes = useForm(() => ({ variant_axis_label: '', labels: '' }));

const ouvrirVariantes = (product) => {
    familleEnCours.value = product;
    formVariantes.reset();
    formVariantes.variant_axis_label = product.variant_axis_label || '';
};

const soumettreVariantes = () => {
    formVariantes.post(route('products.variants.store', familleEnCours.value.id), {
        preserveScroll: true,
        onSuccess: () => { familleEnCours.value = null; },
    });
};

const deplacer = (famille, variante, direction) => {
    router.post(
        route('products.variants.reorder', famille.id),
        { variant_id: variante.id, direction },
        { preserveScroll: true, preserveState: false }
    );
};

const dupliquer = (product) => {
    // Une famille emporte ses déclinaisons : on le dit avant, le nombre de
    // lignes créées n'est pas celui qu'on croit.
    const question = product.variants_count > 0
        ? t('products.duplicate_family_confirm', { count: product.variants_count })
        : t('products.duplicate_confirm');

    if (!window.confirm(question)) return;

    router.post(route('products.duplicate', product.id));
};

const propager = (product) => {
    if (!window.confirm(t('products.variants_propagate_confirm', { count: product.variants_count }))) return;

    router.post(route('products.variants.propagate', product.id), {}, { preserveScroll: true });
};

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

    // L'onglet des familles n'apparaît qu'une fois qu'il y en a : sur un
    // catalogue sans variante, il ne serait qu'un décor.
    if ((props.typeCounts.families ?? 0) > 0) {
        tabs.push({ value: 'families', label: t('products.families_only'), count: props.typeCounts.families });
    }

    return tabs;
});

const setType = (value) => {
    // « families » n'est pas un type d'article mais un filtre à part : il
    // isole les articles qui portent des déclinaisons.
    const params = value === 'families' ? { families: 1 } : (value ? { type: value } : {});

    router.get(route('products.index'), params, {
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

// FEAT-108/105 : un compte qui sort d'un essai peut dépasser le plafond de son
// nouveau plan. « 50 / 10 articles » ressemblait alors à un dysfonctionnement,
// et suggérait implicitement d'en supprimer quarante. On décrit la situation
// telle qu'elle est : rien n'est perdu, seul l'ajout est suspendu.
const quotaExceeded = computed(() =>
    props.quota.limit !== null
    && props.quota.limit !== undefined
    && props.quota.used > props.quota.limit
);

const quotaLabel = computed(() => {
    if (props.quota.limit === null || props.quota.limit === undefined) {
        return t('products.quota_unlimited');
    }
    if (quotaExceeded.value) {
        return t('products.quota_exceeded', { used: props.quota.used, limit: props.quota.limit });
    }
    return t('products.quota_used', { used: props.quota.used, limit: props.quota.limit });
});

const formatPrice = (value) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(Number(value || 0));

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
                    <p
                        class="mt-0.5 text-sm"
                        :class="quotaExceeded ? 'text-amber-700 dark:text-amber-400' : 'text-slate-500 dark:text-slate-400'"
                    >{{ quotaLabel }}</p>
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
                        (filters.families ? 'families' : (filters.type ?? null)) === tab.value
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
                            <!-- ⚠️ Les deux lignes vivent dans le MÊME v-for :
                                 la ligne d'une variante a besoin de `product`,
                                 qui n'existe pas hors de la portée de sa
                                 famille. -->
                            <template v-for="product in products.data" :key="product.id">
                            <tr class="text-sm text-slate-700 dark:text-slate-300">
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
                                        <button
                                            v-if="product.variants_count > 0"
                                            type="button"
                                            class="rounded p-0.5 text-slate-400 transition hover:text-slate-700 dark:hover:text-slate-200"
                                            :title="depliees.has(product.id) ? t('products.variants_hide') : t('products.variants_show')"
                                            @click="basculer(product.id)"
                                        >
                                            <span class="sr-only">{{ depliees.has(product.id) ? t('products.variants_hide') : t('products.variants_show') }}</span>
                                            <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-90': depliees.has(product.id) }" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                            </svg>
                                        </button>
                                        {{ product.designation }}
                                        <span v-if="product.variants_count > 0" class="whitespace-nowrap rounded-full bg-primary-50 px-2 py-0.5 text-xs font-normal text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
                                            {{ t('products.variants_count', { count: product.variants_count }) }}<template v-if="product.variant_axis_label"> · {{ product.variant_axis_label }}</template>
                                        </span>
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
                                    <div class="flex items-center justify-end gap-1">
                                        <RowAction
                                            icon="add"
                                            :label="t('products.variants_add')"
                                            @click="ouvrirVariantes(product)"
                                        />
                                        <RowAction
                                            v-if="product.variants_count > 0"
                                            icon="propagate"
                                            :label="t('products.variants_propagate')"
                                            @click="propager(product)"
                                        />
                                        <RowAction
                                            icon="duplicate"
                                            :label="t('products.duplicate')"
                                            @click="dupliquer(product)"
                                        />
                                        <RowAction
                                            icon="edit"
                                            tone="primary"
                                            :label="t('edit')"
                                            :href="route('products.edit', product.id)"
                                        />
                                        <RowAction
                                            icon="delete"
                                            tone="danger"
                                            :label="t('delete')"
                                            @click="destroy(product)"
                                        />
                                    </div>
                                </td>
                            </tr>

                            <!-- Les variantes de la famille dépliée : décalées,
                                 sans case à cocher, ce sont des lignes filles. -->
                            <tr
                                v-for="(variante, index) in (depliees.has(product.id) ? product.variants : [])"
                                :key="`v-${variante.id}`"
                                class="bg-slate-50/60 text-sm text-slate-600 dark:bg-gray-800/30 dark:text-slate-400"
                            >
                                <td class="px-4 py-2"></td>
                                <td class="px-4 py-2 pl-10">
                                    <span class="font-medium text-slate-700 dark:text-slate-300">{{ variante.variant_label }}</span>
                                    <span v-if="!variante.is_active" class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-slate-500 dark:bg-gray-800">
                                        {{ t('products.inactive') }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-slate-400">{{ variante.reference || '—' }}</td>
                                <td class="px-4 py-2"></td>
                                <td class="px-4 py-2 text-right tabular-nums">{{ formatPrice(variante.unit_price_ht) }}</td>
                                <td class="px-4 py-2 text-right tabular-nums">{{ Number(variante.vat_rate) }}%</td>
                                <td class="px-4 py-2"></td>
                                <td class="px-4 py-2">
                                    <div class="flex items-center justify-end gap-1">
                                        <!-- Aux extrémités la flèche manquante laisse
                                             sa place : sans cela toute la colonne
                                             d'actions se décalerait d'une ligne à
                                             l'autre. -->
                                        <RowAction
                                            v-if="index > 0"
                                            icon="up"
                                            :label="t('products.variant_move_up')"
                                            @click="deplacer(product, variante, 'up')"
                                        />
                                        <span v-else class="inline-block h-8 w-8" aria-hidden="true"></span>
                                        <RowAction
                                            v-if="index < product.variants.length - 1"
                                            icon="down"
                                            :label="t('products.variant_move_down')"
                                            @click="deplacer(product, variante, 'down')"
                                        />
                                        <span v-else class="inline-block h-8 w-8" aria-hidden="true"></span>
                                        <RowAction
                                            icon="duplicate"
                                            :label="t('products.duplicate')"
                                            @click="dupliquer(variante)"
                                        />
                                        <RowAction
                                            icon="edit"
                                            tone="primary"
                                            :label="t('edit')"
                                            :href="route('products.edit', variante.id)"
                                        />
                                        <RowAction
                                            icon="delete"
                                            tone="danger"
                                            :label="t('delete')"
                                            @click="destroy(variante)"
                                        />
                                    </div>
                                </td>
                            </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <Pagination v-if="products.data.length" :links="products.links" class="mt-6" />
        </div>

        <!-- Création en lot : l'action qui justifie la fonctionnalité. Personne
             ne saisira quarante-cinq nuances une par une. -->
        <Modal :show="familleEnCours !== null" @close="familleEnCours = null">
            <div class="p-6">
                <h2 class="text-lg font-medium text-slate-900 dark:text-white">
                    {{ t('products.variants_add') }}
                </h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ familleEnCours?.designation }}</p>

                <div class="mt-4">
                    <InputLabel for="variant_axis_label" :value="t('products.variants_axis_label')" />
                    <input
                        id="variant_axis_label"
                        v-model="formVariantes.variant_axis_label"
                        type="text"
                        maxlength="50"
                        :placeholder="t('products.variants_axis_placeholder')"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                    <InputError :message="formVariantes.errors.variant_axis_label" class="mt-1" />
                </div>

                <div class="mt-4">
                    <InputLabel for="labels" :value="t('products.variants_labels')" />
                    <textarea
                        id="labels"
                        v-model="formVariantes.labels"
                        rows="8"
                        class="mt-1 block w-full rounded-xl border-gray-300 font-mono text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    ></textarea>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ t('products.variants_labels_help') }}</p>
                    <InputError :message="formVariantes.errors.labels" class="mt-1" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="familleEnCours = null">{{ t('cancel') }}</SecondaryButton>
                    <PrimaryButton :disabled="formVariantes.processing" @click="soumettreVariantes">
                        {{ t('products.variants_add') }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>

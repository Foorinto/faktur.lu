<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import RowAction from '@/Components/RowAction.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';
import * as selection from '@/Support/stockSelection';

const { t } = useTranslations();

const props = defineProps({
    products: { type: Array, default: () => [] },
    total_value: { type: Number, default: 0 },
    low_count: { type: Number, default: 0 },
});

const formatCurrency = (v) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(v || 0);

const formatQty = (v) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 4 }).format(v || 0);

const today = new Date().toISOString().split('T')[0];

// Modale d'entrée de stock.
// Les valeurs initiales sont données par une fonction : après un envoi réussi,
// Inertia prend les données soumises comme nouveaux défauts, et reset() ramenait
// la saisie précédente au lieu d'un formulaire vierge.
const entryProduct = ref(null);
const entryForm = useForm(() => ({ quantity: null, unit_cost: null, date: today, note: '', allocations: [] }));

// Un coût par déclinaison est l'exception : une taille XL ne s'achète pas au
// prix d'une S, mais un coloris si. L'interrupteur garde le cas courant simple.
const coutParDeclinaison = ref(false);

// Les déclinaisons suivies de la famille ouverte. Elles se lisent dans la liste
// déjà affichée : pas d'appel supplémentaire pour ouvrir une fenêtre.
const declinaisons = computed(() => {
    if (!entryProduct.value) return [];

    return props.products.filter((p) => p.parent_id === entryProduct.value.id);
});

const totalReparti = computed(() =>
    entryForm.allocations.reduce((somme, l) => somme + (parseFloat(l.quantity) || 0), 0)
);

// Ce qui n'est pas ventilé reste sur l'article lui-même : on reçoit parfois un
// carton dont on ne connaît pas encore le détail.
const resteARepartir = computed(() =>
    Math.round(((parseFloat(entryForm.quantity) || 0) - totalReparti.value) * 10000) / 10000
);

const openEntry = (product) => {
    entryProduct.value = product;
    entryForm.reset();
    entryForm.date = today;
    // Le dernier coût saisi est proposé d'office (FEAT-128) : on l'oublie
    // moins, et il reste modifiable.
    entryForm.unit_cost = product.last_unit_cost ?? null;
    // Une réception de 500 souris se ventile entre le blanc et le vert : la
    // fenêtre propose une ligne par déclinaison plutôt qu'un total aveugle.
    coutParDeclinaison.value = false;
    entryForm.allocations = props.products
        .filter((p) => p.parent_id === product.id)
        .map((v) => ({ product_id: v.id, quantity: null, unit_cost: null }));
};
const submitEntry = () => {
    entryForm.post(route('stock.entry', entryProduct.value.id), {
        preserveScroll: true,
        onSuccess: () => { entryProduct.value = null; },
    });
};

// Sélection d'articles pour valoriser d'un coup leurs entrées sans coût
// (FEAT-128, affinée en FEAT-129) : chaque article à valoriser a sa case,
// cocher une famille coche ses déclinaisons sans coût. Règles dans
// Support/stockSelection.js, testées à part.
const selected = ref([]);
const canSelect = (p) => selection.canSelect(props.products, p);
const isSelected = (p) => selection.isSelected(selected.value, p);
// L'indication « cocher la famille coche ses déclinaisons » n'a de sens que si
// la case en embarque vraiment d'autres.
const selectsFamily = (p) => selection.familyOf(props.products, p).length > 1;
const onToggle = (p, checked) => { selected.value = selection.toggle(props.products, selected.value, p, checked); };
const allSelected = computed(() => selection.allSelected(props.products, selected.value));
const toggleAll = () => { selected.value = selection.toggleAll(props.products, selected.value); };
const valuing = ref(false);
const valueForm = useForm(() => ({ product_ids: [], unit_cost: null }));
const openValue = () => {
    valueForm.clearErrors();
    valueForm.unit_cost = null;
    valueForm.product_ids = [...selected.value];
    valuing.value = true;
};
const submitValue = () => {
    valueForm.post(route('stock.value'), {
        preserveScroll: true,
        onSuccess: () => { valuing.value = false; selected.value = []; },
    });
};

// Modale d'inventaire
const inventoryProduct = ref(null);
const inventoryForm = useForm(() => ({ counted_quantity: null, date: today, note: '' }));

const openInventory = (product) => {
    inventoryProduct.value = product;
    inventoryForm.reset();
    inventoryForm.date = today;
    inventoryForm.counted_quantity = product.current_stock;
};
const submitInventory = () => {
    inventoryForm.post(route('stock.inventory', inventoryProduct.value.id), {
        preserveScroll: true,
        onSuccess: () => { inventoryProduct.value = null; },
    });
};
</script>

<template>
    <Head :title="t('stock.title')" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">{{ t('stock.title') }}</h1>
        </template>

        <div class="space-y-6">
            <!-- Résumé -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-2xl bg-white p-4 shadow dark:bg-surface-card">
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ t('stock.total_value') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white">{{ formatCurrency(total_value) }}</p>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow dark:bg-surface-card">
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ t('stock.tracked_products') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white">{{ products.length }}</p>
                </div>
                <div class="rounded-2xl bg-white p-4 shadow dark:bg-surface-card" :class="{ 'ring-1 ring-amber-300': low_count > 0 }">
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ t('stock.low_alerts') }}</p>
                    <p class="mt-1 text-2xl font-semibold" :class="low_count > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white'">{{ low_count }}</p>
                </div>
            </div>

            <!-- Liste -->
            <div v-if="selected.length > 0" class="flex flex-col gap-2 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm sm:flex-row sm:items-center sm:justify-between dark:border-amber-800 dark:bg-amber-900/20">
                <span class="text-amber-900 dark:text-amber-100">{{ t('stock.selected_articles', { count: selected.length }) }}</span>
                <PrimaryButton type="button" @click="openValue">{{ t('stock.value_entries') }}</PrimaryButton>
            </div>

            <div class="overflow-x-auto rounded-2xl bg-white shadow dark:bg-surface-card">
                <table v-if="products.length > 0" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-slate-50 dark:bg-gray-800">
                        <tr>
                            <th class="w-10 px-4 py-3">
                                <input type="checkbox" :checked="allSelected" :aria-label="t('select_all')" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800" @change="toggleAll" />
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('stock.product') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('stock.current') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('stock.threshold') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('stock.value') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ t('actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr v-for="p in products" :key="p.id" :class="p.parent_id ? 'bg-slate-50/60 dark:bg-gray-800/30' : ''">
                            <td class="w-10 px-4 py-3">
                                <input
                                    v-if="canSelect(p)"
                                    type="checkbox"
                                    :checked="isSelected(p)"
                                    :aria-label="p.designation"
                                    :title="selectsFamily(p) ? t('stock.select_family') : undefined"
                                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800"
                                    @change="onToggle(p, $event.target.checked)"
                                />
                            </td>
                            <td class="px-6 py-3" :class="p.parent_id ? 'pl-10' : ''">
                                <div class="text-sm font-medium text-slate-900 dark:text-white">{{ p.designation }}</div>
                                <div v-if="p.reference" class="text-xs text-slate-400">{{ p.reference }}</div>
                                <div v-if="p.unvalued_entries > 0" class="mt-0.5 text-xs text-amber-700 dark:text-amber-300">{{ t('stock.unvalued_entries', { count: p.unvalued_entries }) }}</div>
                                <!-- La colonne Stock annonce déjà tout ce que la
                                     famille couvre. Ici on dit combien de
                                     déclinaisons, et ce qui n'est rattaché à
                                     aucune d'elles. -->
                                <div v-if="p.variants_count > 0" class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                    {{ t('products.variants_count', { count: p.variants_count }) }}<template v-if="p.unallocated_stock > 0"> · {{ t('stock.unallocated', { quantity: formatQty(p.unallocated_stock) }) }}</template>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-right text-sm font-mono tabular-nums">
                                <span :class="p.is_low ? 'font-semibold text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white'">
                                    {{ formatQty(p.current_stock) }}
                                </span>
                                <span v-if="p.is_low" :title="t('stock.low_badge')" class="ml-1 text-amber-500">&#9888;</span>
                            </td>
                            <td class="px-6 py-3 text-right text-sm tabular-nums text-slate-500 dark:text-slate-400">
                                {{ p.threshold !== null ? formatQty(p.threshold) : '—' }}
                            </td>
                            <td class="px-6 py-3 text-right text-sm font-mono tabular-nums text-slate-900 dark:text-white">
                                {{ formatCurrency(p.stock_value) }}
                            </td>
                            <td class="px-6 py-3 text-right text-sm">
                                <div class="flex items-center justify-end gap-1">
                                    <RowAction icon="add" tone="primary" :label="t('stock.entry')" @click="openEntry(p)" />
                                    <RowAction icon="inventory" :label="t('stock.inventory')" @click="openInventory(p)" />
                                    <RowAction icon="view" :label="t('stock.record')" :href="route('stock.movements', p.id)" />
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-else class="px-6 py-12 text-center">
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ t('stock.empty') }}</p>
                    <Link :href="route('products.index')" class="mt-2 inline-block text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">
                        {{ t('stock.empty_cta') }}
                    </Link>
                </div>
            </div>
        </div>

        <!-- Modale : entrée de stock -->
        <Modal :show="entryProduct !== null" @close="entryProduct = null">
            <div class="p-6">
                <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('stock.entry_title') }}</h2>
                <p v-if="entryProduct" class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ entryProduct.designation }}</p>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="entry_quantity" :value="t('stock.quantity')" />
                        <input id="entry_quantity" v-model="entryForm.quantity" type="number" step="0.01" min="0.01"
                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                        <InputError :message="entryForm.errors.quantity" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="entry_unit_cost" :value="t('stock.unit_cost')" />
                        <input id="entry_unit_cost" v-model="entryForm.unit_cost" type="number" step="0.01" min="0"
                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                        <p class="mt-1 text-xs text-slate-400">{{ t('stock.unit_cost_help') }}</p>
                        <p v-if="entryProduct?.last_unit_cost !== null && entryProduct?.last_unit_cost !== undefined" class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            {{ t('stock.last_cost_hint', { cost: formatCurrency(entryProduct.last_unit_cost) }) }}
                        </p>
                        <InputError :message="entryForm.errors.unit_cost" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="entry_date" :value="t('date')" />
                        <input id="entry_date" v-model="entryForm.date" type="date"
                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                        <InputError :message="entryForm.errors.date" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="entry_note" :value="t('stock.note')" />
                        <input id="entry_note" v-model="entryForm.note" type="text" maxlength="255"
                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                    </div>
                </div>

                <!-- La ventilation est facultative : qui ne tient pas le détail
                     par déclinaison laisse tout sur l'article, comme avant. -->
                <div v-if="declinaisons.length > 0" class="mt-5 border-t border-gray-200 pt-4 dark:border-gray-700">
                    <InputLabel :value="t('stock.allocation_title')" />
                    <p class="mt-1 text-xs text-slate-400">{{ t('stock.allocation_help') }}</p>

                    <label class="mt-3 flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                        <input v-model="coutParDeclinaison" type="checkbox"
                            class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-700" />
                        {{ t('stock.allocation_cost_toggle') }}
                    </label>

                    <div class="mt-2 max-h-64 space-y-2 overflow-y-auto rounded-xl border border-gray-200 p-3 dark:border-gray-700">
                        <div v-for="(ligne, i) in entryForm.allocations" :key="ligne.product_id" class="flex items-center gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm text-slate-700 dark:text-slate-300">
                                    {{ declinaisons[i]?.designation }}
                                </p>
                                <p class="text-xs text-slate-400">
                                    {{ t('stock.allocation_current', { quantity: formatQty(declinaisons[i]?.current_stock) }) }}
                                </p>
                            </div>
                            <input
                                v-if="coutParDeclinaison"
                                v-model="ligne.unit_cost"
                                type="number"
                                step="0.01"
                                min="0"
                                :placeholder="t('stock.unit_cost_short')"
                                :aria-label="`${declinaisons[i]?.designation} ${t('stock.unit_cost')}`"
                                class="w-28 rounded-xl border-gray-300 text-right text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                            <input
                                v-model="ligne.quantity"
                                type="number"
                                step="0.01"
                                min="0"
                                :aria-label="declinaisons[i]?.designation"
                                class="w-28 rounded-xl border-gray-300 text-right shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                        </div>
                    </div>

                    <div class="mt-2 flex justify-between text-sm">
                        <span class="text-slate-500 dark:text-slate-400">{{ t('stock.allocation_total') }}</span>
                        <span class="font-semibold tabular-nums text-slate-900 dark:text-white">{{ formatQty(totalReparti) }}</span>
                    </div>
                    <div v-if="totalReparti > 0" class="mt-1 flex justify-between text-sm">
                        <span class="text-slate-500 dark:text-slate-400">{{ t('stock.allocation_remainder') }}</span>
                        <span
                            class="font-semibold tabular-nums"
                            :class="resteARepartir < 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white'"
                        >{{ formatQty(resteARepartir) }}</span>
                    </div>
                    <p v-if="resteARepartir > 0 && totalReparti > 0" class="mt-1 text-xs text-slate-400">
                        {{ t('stock.allocation_on_family') }}
                    </p>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <SecondaryButton @click="entryProduct = null">{{ t('cancel') }}</SecondaryButton>
                    <PrimaryButton
                        :disabled="entryForm.processing || resteARepartir < 0"
                        @click="submitEntry"
                    >{{ t('stock.record_entry') }}</PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Modale : valoriser les entrées sans coût des articles cochés (FEAT-128) -->
        <Modal :show="valuing" @close="valuing = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('stock.value_entries_title') }}</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ t('stock.value_entries_help', { count: valueForm.product_ids.length }) }}</p>
                <div class="mt-4">
                    <InputLabel for="bulk_unit_cost" :value="t('stock.unit_cost')" />
                    <input id="bulk_unit_cost" v-model="valueForm.unit_cost" type="number" step="0.01" min="0"
                        class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                    <InputError :message="valueForm.errors.unit_cost" class="mt-1" />
                    <InputError :message="valueForm.errors.product_ids" class="mt-1" />
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="valuing = false">{{ t('cancel') }}</SecondaryButton>
                    <PrimaryButton :disabled="valueForm.processing" @click="submitValue">{{ t('stock.value_entries_submit') }}</PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Modale : inventaire -->
        <Modal :show="inventoryProduct !== null" @close="inventoryProduct = null">
            <div class="p-6">
                <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('stock.inventory_title') }}</h2>
                <p v-if="inventoryProduct" class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    {{ inventoryProduct.designation }} ({{ t('stock.current') }} : {{ formatQty(inventoryProduct.current_stock) }})
                </p>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="inv_qty" :value="t('stock.counted_quantity')" />
                        <input id="inv_qty" v-model="inventoryForm.counted_quantity" type="number" step="0.01" min="0"
                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                        <InputError :message="inventoryForm.errors.counted_quantity" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="inv_date" :value="t('date')" />
                        <input id="inv_date" v-model="inventoryForm.date" type="date"
                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                        <InputError :message="inventoryForm.errors.date" class="mt-1" />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel for="inv_note" :value="t('stock.note')" />
                        <input id="inv_note" v-model="inventoryForm.note" type="text" maxlength="255"
                            :placeholder="t('stock.inventory_note_placeholder')"
                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <SecondaryButton @click="inventoryProduct = null">{{ t('cancel') }}</SecondaryButton>
                    <PrimaryButton :disabled="inventoryForm.processing" @click="submitInventory">{{ t('stock.record_inventory') }}</PrimaryButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>

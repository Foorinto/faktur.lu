<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useTour } from '@/Composables/useTour';
import ExpenseVatFields from '@/Components/ExpenseVatFields.vue';
import ExpenseLinesEditor from '@/Components/ExpenseLinesEditor.vue';

const { t } = useTranslations();
const { startTour } = useTour();

onMounted(() => setTimeout(() => startTour('expenseCreate'), 600));

const props = defineProps({
    categories: Array,
    vatRates: Array,
    vatRatesByCountry: Object,
    vatRegimes: Array,
    countries: Array,
    homeCountry: String,
    homeStandardRate: Number,
    paymentMethods: Array,
    trackedProducts: { type: Array, default: () => [] },
});

const form = useForm({
    date: new Date().toISOString().split('T')[0],
    provider_name: '',
    supplier_country: props.homeCountry || 'LU',
    category: '',
    amount_input_mode: 'ht',
    amount_ht: '',
    amount_ttc: '',
    vat_rate: 17,
    vat_regime: 'national',
    reverse_charge_vat_rate: null,
    description: '',
    is_deductible: true,
    payment_method: '',
    reference: '',
    attachment: null,
    lines: [],
    stock_product_id: null,
    stock_quantity: null,
});

// Ventilation (FEAT-115) : repli intelligent. Par défaut, saisie à une seule
// catégorie comme avant. Le bouton déplie l'éditeur multi-lignes.
const ventilated = ref(false);

const startVentilation = () => {
    // On amorce avec la catégorie et le montant déjà saisis, pour ne rien
    // reperdre en passant en mode ventilé.
    form.lines = [{
        category: form.category || '',
        description: '',
        amount_ht: form.amount_ht || '',
        vat_rate: form.vat_rate || 17,
        product_id: form.stock_product_id || null,
        stock_quantity: form.stock_quantity || null,
    }];
    // Le stock passe désormais par les lignes.
    form.stock_product_id = null;
    form.stock_quantity = null;
    ventilated.value = true;
};

const stopVentilation = () => {
    // Retour à une catégorie unique : on reprend la première ligne.
    const first = form.lines[0] ?? {};
    form.category = first.category || form.category;
    form.amount_ht = first.amount_ht || form.amount_ht;
    form.vat_rate = first.vat_rate || form.vat_rate;
    form.lines = [];
    ventilated.value = false;
};

const handleFileChange = (event) => {
    form.attachment = event.target.files[0];
};

const submit = () => {
    // En mode ventilé, on aligne les champs agrégés sur les lignes : la
    // validation exige une catégorie et un montant, et le contrôleur recalcule
    // de toute façon depuis les lignes. Catégorie et taux = ligne majoritaire.
    if (ventilated.value && form.lines.length > 0) {
        const majority = [...form.lines].sort(
            (a, b) => (parseFloat(b.amount_ht) || 0) - (parseFloat(a.amount_ht) || 0)
        )[0];

        form.amount_input_mode = 'ht';
        form.amount_ht = form.lines.reduce((sum, l) => sum + (parseFloat(l.amount_ht) || 0), 0);
        form.category = majority.category;
        form.vat_rate = majority.vat_rate;
    } else {
        form.lines = [];
    }

    form.post(route('expenses.store'), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head :title="t('new_expense')" />

    <AppLayout>
        <template #header>
            <Link
                :href="route('expenses.index')"
                class="text-slate-400 hover:text-slate-500 dark:text-slate-500 dark:hover:text-slate-400"
            >
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                </svg>
            </Link>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                {{ t('new_expense') }}
            </h1>
        </template>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Basic Info -->
            <div class="overflow-x-auto rounded-2xl bg-white shadow dark:bg-surface-card">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('information') }}</h2>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="date" :value="t('date')" />
                            <input
                                id="date"
                                v-model="form.date"
                                type="date"
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                required
                            />
                            <InputError :message="form.errors.date" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="provider_name" :value="t('supplier')" />
                            <input
                                id="provider_name"
                                v-model="form.provider_name"
                                type="text"
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                :placeholder="t('example_provider')"
                                required
                            />
                            <InputError :message="form.errors.provider_name" class="mt-2" />
                        </div>

                        <div v-if="!ventilated" data-tour="expense-form-category" class="sm:col-span-2">
                            <InputLabel for="category" :value="t('category')" />
                            <select
                                id="category"
                                v-model="form.category"
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                required
                            >
                                <option value="">{{ t('select_category') }}</option>
                                <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                                    {{ cat.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.category" class="mt-2" />
                        </div>

                        <div class="sm:col-span-2">
                            <button
                                v-if="!ventilated"
                                type="button"
                                class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400"
                                @click="startVentilation"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                {{ t('expense_ventilation_start') }}
                            </button>
                            <button
                                v-else
                                type="button"
                                class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400"
                                @click="stopVentilation"
                            >
                                {{ t('expense_ventilation_stop') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <ExpenseLinesEditor
                v-if="ventilated"
                :form="form"
                :categories="categories"
                :tracked-products="trackedProducts"
            />

            <!-- Réception en stock (FEAT-116), saisie simple : un seul produit.
                 En mode ventilé, la réception se fait ligne par ligne. -->
            <div v-if="!ventilated && trackedProducts.length > 0" class="overflow-x-auto rounded-2xl bg-white shadow dark:bg-surface-card">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('expense_stock.title') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ t('expense_stock.help') }}</p>
                </div>
                <div class="px-6 py-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="stock_product" :value="t('expense_stock.product')" />
                        <select
                            id="stock_product"
                            v-model="form.stock_product_id"
                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                            <option :value="null">{{ t('expense_stock.none') }}</option>
                            <option v-for="p in trackedProducts" :key="p.value" :value="p.value">{{ p.label }}</option>
                        </select>
                        <InputError :message="form.errors.stock_product_id" class="mt-2" />
                    </div>
                    <div v-if="form.stock_product_id">
                        <InputLabel for="stock_quantity" :value="t('expense_stock.quantity')" />
                        <input
                            id="stock_quantity"
                            v-model="form.stock_quantity"
                            type="number"
                            step="0.01"
                            min="0.01"
                            class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            :placeholder="t('expense_stock.quantity_placeholder')"
                        />
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ t('expense_stock.quantity_help') }}</p>
                        <InputError :message="form.errors.stock_quantity" class="mt-2" />
                    </div>
                </div>
            </div>

            <ExpenseVatFields
                :form="form"
                :vat-rates="vatRates"
                :vat-rates-by-country="vatRatesByCountry"
                :vat-regimes="vatRegimes"
                :countries="countries"
                :home-country="homeCountry"
                :home-standard-rate="homeStandardRate"
                :hide-amounts="ventilated"
            />

            <!-- Additional Info -->
            <div class="overflow-x-auto rounded-2xl bg-white shadow dark:bg-surface-card">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('additional_info') }}</h2>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="payment_method" :value="t('payment_method_optional')" />
                            <select
                                id="payment_method"
                                v-model="form.payment_method"
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            >
                                <option value="">{{ t('select') }}</option>
                                <option v-for="method in paymentMethods" :key="method.value" :value="method.value">
                                    {{ method.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.payment_method" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="reference" :value="t('invoice_reference_optional')" />
                            <input
                                id="reference"
                                v-model="form.reference"
                                type="text"
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                :placeholder="t('example_ref')"
                            />
                            <InputError :message="form.errors.reference" class="mt-2" />
                        </div>

                        <div class="sm:col-span-2">
                            <InputLabel for="description" :value="t('description_optional')" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                :placeholder="t('expense_notes')"
                            ></textarea>
                            <InputError :message="form.errors.description" class="mt-2" />
                        </div>

                    </div>
                </div>
            </div>

            <!-- Attachment -->
            <div data-tour="expense-form-receipt" class="overflow-x-auto rounded-2xl bg-white shadow dark:bg-surface-card">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('receipt_attachment') }}</h2>
                </div>
                <div class="px-6 py-4">
                    <div class="flex items-center justify-center w-full">
                        <label
                            for="attachment"
                            class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-2xl cursor-pointer bg-slate-50 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-800"
                        >
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <svg class="w-8 h-8 mb-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="mb-2 text-sm text-slate-500 dark:text-slate-400">
                                    <span class="font-semibold">{{ t('click_to_upload') }}</span> {{ t('drag_drop') }}
                                </p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ t('file_types_allowed') }}</p>
                            </div>
                            <input
                                id="attachment"
                                type="file"
                                class="hidden"
                                accept=".pdf,.jpg,.jpeg,.png,.webp"
                                @change="handleFileChange"
                            />
                        </label>
                    </div>
                    <div v-if="form.attachment" class="mt-3 text-sm text-slate-600 dark:text-slate-400">
                        {{ t('file_selected') }} {{ form.attachment.name }}
                    </div>
                    <InputError :message="form.errors.attachment" class="mt-2" />
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Link
                    :href="route('expenses.index')"
                    class="inline-flex items-center justify-center w-full sm:w-auto rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-slate-300 dark:hover:bg-gray-800"
                >
                    {{ t('cancel') }}
                </Link>
                <PrimaryButton data-tour="expense-form-submit" :disabled="form.processing" class="w-full sm:w-auto justify-center">
                    <span v-if="form.processing">{{ t('saving') }}</span>
                    <span v-else>{{ t('save') }}</span>
                </PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>

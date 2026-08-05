<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useTour } from '@/Composables/useTour';

const { t } = useTranslations();
const { startTour } = useTour();

onMounted(() => setTimeout(() => startTour('expenseCreate'), 600));

const props = defineProps({
    categories: Array,
    vatRates: Array,
    paymentMethods: Array,
});

const form = useForm({
    date: new Date().toISOString().split('T')[0],
    provider_name: '',
    category: '',
    amount_ht: '',
    vat_rate: 17,
    description: '',
    is_deductible: true,
    payment_method: '',
    reference: '',
    attachment: null,
});

// Les taux luxembourgeois couvrent l'essentiel, mais un achat à l'étranger
// porte le taux du pays du fournisseur. « Autre » ouvre une saisie libre plutôt
// que d'obliger à arrondir sur un taux voisin.
const standardRates = props.vatRates.map((r) => Number(r.value));
const vatMode = ref(
    standardRates.includes(Number(form.vat_rate)) ? String(Number(form.vat_rate)) : "custom",
);

watch(vatMode, (mode) => {
    if (mode !== "custom") form.vat_rate = Number(mode);
});

const calculatedVat = computed(() => {
    if (!form.amount_ht || !form.vat_rate) return 0;
    return (parseFloat(form.amount_ht) * parseFloat(form.vat_rate) / 100).toFixed(2);
});

const calculatedTtc = computed(() => {
    if (!form.amount_ht) return 0;
    return (parseFloat(form.amount_ht) + parseFloat(calculatedVat.value)).toFixed(2);
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount);
};

const handleFileChange = (event) => {
    form.attachment = event.target.files[0];
};

const submit = () => {
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

                        <div data-tour="expense-form-category" class="sm:col-span-2">
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
                    </div>
                </div>
            </div>

            <!-- Amounts -->
            <div class="overflow-x-auto rounded-2xl bg-white shadow dark:bg-surface-card">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-slate-900 dark:text-white">{{ t('calculated_amounts') }}</h2>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div data-tour="expense-form-amount">
                            <InputLabel for="amount_ht" :value="t('amount_ht')" />
                            <div class="relative mt-1">
                                <input
                                    id="amount_ht"
                                    v-model="form.amount_ht"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    class="block w-full rounded-xl border-gray-300 pr-12 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    placeholder="0.00"
                                    required
                                />
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                    <span class="text-slate-500 dark:text-slate-400">EUR</span>
                                </div>
                            </div>
                            <InputError :message="form.errors.amount_ht" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="vat_rate" :value="t('vat_rate_label')" />
                            <select
                                id="vat_rate"
                                v-model="vatMode"
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                required
                            >
                                <option v-for="rate in vatRates" :key="rate.value" :value="String(rate.value)">
                                    {{ rate.label }}
                                </option>
                                <option value="custom">{{ t("vat_rate_custom") }}</option>
                            </select>
                            <!-- Une facture allemande porte 19 %, une française
                                 20 % : la TVA payée à un fournisseur étranger
                                 n'a aucune raison de figurer dans la grille
                                 luxembourgeoise. -->
                            <input
                                v-if="vatMode === 'custom'"
                                v-model.number="form.vat_rate"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                :placeholder="t('vat_rate_custom_placeholder')"
                            />
                            <InputError :message="form.errors.vat_rate" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel :value="t('calculated_amounts')" />
                            <div class="mt-1 rounded-xl bg-slate-50 dark:bg-gray-800 px-4 py-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-slate-500 dark:text-slate-400">{{ t('vat') }}:</span>
                                    <span class="font-medium text-slate-900 dark:text-white">{{ formatCurrency(calculatedVat) }}</span>
                                </div>
                                <div class="flex justify-between mt-1">
                                    <span class="text-slate-500 dark:text-slate-400">{{ t('ttc') }}:</span>
                                    <span class="font-medium text-slate-900 dark:text-white">{{ formatCurrency(calculatedTtc) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

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

                        <div class="sm:col-span-2">
                            <div class="flex items-center">
                                <input
                                    id="is_deductible"
                                    v-model="form.is_deductible"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800"
                                />
                                <label for="is_deductible" class="ml-2 block text-sm text-slate-900 dark:text-white">
                                    {{ t('expense_deductible') }}
                                </label>
                            </div>
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

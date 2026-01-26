<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

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
    <Head title="Nouvelle dépense" />

    <AppLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <Link
                    :href="route('expenses.index')"
                    class="text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400"
                >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                    </svg>
                </Link>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Nouvelle dépense
                </h1>
            </div>
        </template>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Basic Info -->
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Informations</h2>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="date" value="Date" />
                            <input
                                id="date"
                                v-model="form.date"
                                type="date"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                required
                            />
                            <InputError :message="form.errors.date" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="provider_name" value="Fournisseur" />
                            <input
                                id="provider_name"
                                v-model="form.provider_name"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                placeholder="Ex: Amazon, JetBrains..."
                                required
                            />
                            <InputError :message="form.errors.provider_name" class="mt-2" />
                        </div>

                        <div class="sm:col-span-2">
                            <InputLabel for="category" value="Catégorie" />
                            <select
                                id="category"
                                v-model="form.category"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                required
                            >
                                <option value="">Sélectionner une catégorie</option>
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
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Montants</h2>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <InputLabel for="amount_ht" value="Montant HT" />
                            <div class="relative mt-1">
                                <input
                                    id="amount_ht"
                                    v-model="form.amount_ht"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    class="block w-full rounded-md border-gray-300 pr-12 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="0.00"
                                    required
                                />
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                    <span class="text-gray-500 dark:text-gray-400">EUR</span>
                                </div>
                            </div>
                            <InputError :message="form.errors.amount_ht" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="vat_rate" value="Taux TVA" />
                            <select
                                id="vat_rate"
                                v-model.number="form.vat_rate"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                required
                            >
                                <option v-for="rate in vatRates" :key="rate.value" :value="rate.value">
                                    {{ rate.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.vat_rate" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel value="Montants calculés" />
                            <div class="mt-1 rounded-md bg-gray-50 dark:bg-gray-700 px-4 py-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">TVA:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(calculatedVat) }}</span>
                                </div>
                                <div class="flex justify-between mt-1">
                                    <span class="text-gray-500 dark:text-gray-400">TTC:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(calculatedTtc) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Informations complémentaires</h2>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="payment_method" value="Mode de paiement (optionnel)" />
                            <select
                                id="payment_method"
                                v-model="form.payment_method"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                                <option value="">Sélectionner</option>
                                <option v-for="method in paymentMethods" :key="method.value" :value="method.value">
                                    {{ method.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.payment_method" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="reference" value="Référence facture (optionnel)" />
                            <input
                                id="reference"
                                v-model="form.reference"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                placeholder="Ex: INV-2024-001"
                            />
                            <InputError :message="form.errors.reference" class="mt-2" />
                        </div>

                        <div class="sm:col-span-2">
                            <InputLabel for="description" value="Description (optionnel)" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                placeholder="Notes ou détails sur cette dépense..."
                            ></textarea>
                            <InputError :message="form.errors.description" class="mt-2" />
                        </div>

                        <div class="sm:col-span-2">
                            <div class="flex items-center">
                                <input
                                    id="is_deductible"
                                    v-model="form.is_deductible"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700"
                                />
                                <label for="is_deductible" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                    Dépense déductible fiscalement
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attachment -->
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Justificatif</h2>
                </div>
                <div class="px-6 py-4">
                    <div class="flex items-center justify-center w-full">
                        <label
                            for="attachment"
                            class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600"
                        >
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <svg class="w-8 h-8 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
                                    <span class="font-semibold">Cliquez pour télécharger</span> ou glissez-déposez
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">PDF, JPG, PNG ou WebP (max. 10 Mo)</p>
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
                    <div v-if="form.attachment" class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                        Fichier sélectionné: {{ form.attachment.name }}
                    </div>
                    <InputError :message="form.errors.attachment" class="mt-2" />
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-3">
                <Link
                    :href="route('expenses.index')"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                >
                    Annuler
                </Link>
                <PrimaryButton :disabled="form.processing">
                    <span v-if="form.processing">Enregistrement...</span>
                    <span v-else>Enregistrer</span>
                </PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>

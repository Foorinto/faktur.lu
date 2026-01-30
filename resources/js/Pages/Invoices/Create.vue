<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import VatScenarioIndicator from '@/Components/VatScenarioIndicator.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    clients: Array,
    vatRates: Array,
    units: Array,
    defaultClientId: [String, Number],
    isVatExempt: Boolean,
    vatScenarios: Object,
});

const defaultVatRate = props.isVatExempt ? 0 : 17;

// Get selected client's VAT scenario
const selectedClient = computed(() => {
    return props.clients?.find(c => c.id === form.client_id);
});

const clientVatScenario = computed(() => {
    return selectedClient.value?.vat_scenario || null;
});

const form = useForm({
    client_id: props.defaultClientId || '',
    title: '',
    due_at: '',
    notes: '',
    currency: 'EUR',
    items: [],
});

const addItem = () => {
    form.items.push({
        title: '',
        description: '',
        quantity: 1,
        unit: 'hour',
        unit_price: 0,
        vat_rate: defaultVatRate,
    });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const submit = () => {
    form.post(route('invoices.store'));
};

// Add first item by default
if (form.items.length === 0) {
    addItem();
}
</script>

<template>
    <Head title="Nouvelle facture" />

    <AppLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <Link
                    :href="route('invoices.index')"
                    class="text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400"
                >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                    </svg>
                </Link>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Nouvelle facture
                </h1>
            </div>
        </template>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Client selection -->
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Client</h2>
                </div>
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="client_id" value="Client" />
                            <select
                                id="client_id"
                                v-model="form.client_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                required
                            >
                                <option value="">Sélectionner un client</option>
                                <option v-for="client in clients" :key="client.id" :value="client.id">
                                    {{ client.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.client_id" class="mt-2" />
                            <!-- VAT Scenario indicator -->
                            <div v-if="clientVatScenario" class="mt-2">
                                <VatScenarioIndicator :scenario="clientVatScenario" size="sm" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="title" value="Titre (optionnel)" />
                            <input
                                id="title"
                                v-model="form.title"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                placeholder="Ex: Développement API janvier 2026"
                            />
                            <InputError :message="form.errors.title" class="mt-2" />
                        </div>
                    </div>

                    <div class="mt-4">
                        <InputLabel for="due_at" value="Date d'échéance (optionnel)" />
                        <input
                            id="due_at"
                            v-model="form.due_at"
                            type="date"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:max-w-xs"
                        />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Par défaut: 30 jours après finalisation
                        </p>
                        <InputError :message="form.errors.due_at" class="mt-2" />
                    </div>
                </div>
            </div>

            <!-- Invoice items -->
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Lignes de facture</h2>
                </div>
                <div class="px-6 py-4">
                    <div class="space-y-4">
                        <div
                            v-for="(item, index) in form.items"
                            :key="index"
                            class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 space-y-3"
                        >
                            <div class="flex flex-wrap gap-4 items-end">
                                <div class="flex-1 min-w-[200px]">
                                    <InputLabel :for="`item-${index}-title`" value="Titre *" />
                                    <input
                                        :id="`item-${index}-title`"
                                        v-model="item.title"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        placeholder="Développement API REST"
                                        required
                                    />
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-4 items-end">
                                <div class="flex-1 min-w-[200px]">
                                    <InputLabel :for="`item-${index}-description`" value="Description (optionnel)" />
                                    <input
                                        :id="`item-${index}-description`"
                                        v-model="item.description"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        placeholder="Détails supplémentaires..."
                                    />
                                </div>

                            <div class="w-24">
                                <InputLabel :for="`item-${index}-quantity`" value="Qté" />
                                <input
                                    :id="`item-${index}-quantity`"
                                    v-model.number="item.quantity"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    required
                                />
                            </div>

                            <div class="w-32">
                                <InputLabel :for="`item-${index}-unit`" value="Unité" />
                                <select
                                    :id="`item-${index}-unit`"
                                    v-model="item.unit"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">-</option>
                                    <option v-for="unit in units" :key="unit.value" :value="unit.value">
                                        {{ unit.label }}
                                    </option>
                                </select>
                            </div>

                            <div class="w-32">
                                <InputLabel :for="`item-${index}-unit_price`" value="Prix HT" />
                                <input
                                    :id="`item-${index}-unit_price`"
                                    v-model.number="item.unit_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    required
                                />
                            </div>

                            <div class="w-32">
                                <InputLabel :for="`item-${index}-vat_rate`" value="TVA" />
                                <select
                                    :id="`item-${index}-vat_rate`"
                                    v-model.number="item.vat_rate"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    required
                                >
                                    <option v-for="rate in vatRates" :key="rate.value" :value="rate.value">
                                        {{ rate.label }}
                                    </option>
                                </select>
                            </div>

                            <button
                                v-if="form.items.length > 1"
                                type="button"
                                @click="removeItem(index)"
                                class="p-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                            >
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.519.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                        </div>

                        <button
                            type="button"
                            @click="addItem"
                            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                        >
                            <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                            </svg>
                            Ajouter une ligne
                        </button>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Notes (optionnel)</h2>
                </div>
                <div class="px-6 py-4">
                    <textarea
                        v-model="form.notes"
                        rows="3"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        placeholder="Notes ou conditions particulières..."
                    ></textarea>
                    <InputError :message="form.errors.notes" class="mt-2" />
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-3">
                <Link
                    :href="route('invoices.index')"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                >
                    Annuler
                </Link>
                <PrimaryButton :disabled="form.processing">
                    <span v-if="form.processing">Création...</span>
                    <span v-else>Créer le brouillon</span>
                </PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>

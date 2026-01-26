<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    clientTypes: {
        type: Array,
        required: true,
    },
    currencies: {
        type: Array,
        required: true,
    },
    countries: {
        type: Array,
        required: true,
    },
    submitLabel: {
        type: String,
        default: 'Enregistrer',
    },
    cancelRoute: {
        type: String,
        default: 'clients.index',
    },
    cancelRouteParams: {
        type: [Object, Number, String],
        default: null,
    },
});

const emit = defineEmits(['submit']);

const isB2B = computed(() => props.form.type === 'b2b');

watch(() => props.form.type, (newType) => {
    if (newType === 'b2c') {
        props.form.vat_number = '';
    }
});

const submit = () => {
    emit('submit');
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-8">
        <!-- Type de client -->
        <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                    Type de client
                </h2>
            </div>
            <div class="px-6 py-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <label
                        v-for="clientType in clientTypes"
                        :key="clientType.value"
                        class="flex items-start p-4 rounded-lg border cursor-pointer transition-colors"
                        :class="[
                            form.type === clientType.value
                                ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20'
                                : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
                        ]"
                    >
                        <input
                            type="radio"
                            v-model="form.type"
                            :value="clientType.value"
                            class="mt-0.5 h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <div class="ml-3">
                            <span class="block text-sm font-medium text-gray-900 dark:text-white">
                                {{ clientType.label }}
                            </span>
                            <span class="block text-sm text-gray-500 dark:text-gray-400">
                                {{ clientType.description }}
                            </span>
                        </div>
                    </label>
                </div>
                <InputError :message="form.errors.type" class="mt-2" />
            </div>
        </div>

        <!-- Informations du client -->
        <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                    Informations du client
                </h2>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="name" :value="isB2B ? 'Raison sociale' : 'Nom complet'" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                            :placeholder="isB2B ? 'ACME Corporation' : 'Jean Dupont'"
                        />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>

                    <div v-if="isB2B">
                        <InputLabel for="contact_name" value="Nom du dirigeant / contact (optionnel)" />
                        <TextInput
                            id="contact_name"
                            v-model="form.contact_name"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Jean Martin"
                        />
                        <InputError :message="form.errors.contact_name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="email" value="Email" />
                        <TextInput
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="mt-1 block w-full"
                            required
                            placeholder="contact@example.com"
                        />
                        <InputError :message="form.errors.email" class="mt-2" />
                    </div>
                </div>

                <div>
                    <InputLabel for="address" value="Adresse" />
                    <textarea
                        id="address"
                        v-model="form.address"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        rows="2"
                        placeholder="45 Avenue Kennedy"
                    ></textarea>
                    <InputError :message="form.errors.address" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <InputLabel for="postal_code" value="Code postal" />
                        <TextInput
                            id="postal_code"
                            v-model="form.postal_code"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="L-2000"
                        />
                        <InputError :message="form.errors.postal_code" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="city" value="Ville" />
                        <TextInput
                            id="city"
                            v-model="form.city"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Luxembourg"
                        />
                        <InputError :message="form.errors.city" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="country_code" value="Pays" />
                        <select
                            id="country_code"
                            v-model="form.country_code"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            required
                        >
                            <option v-for="country in countries" :key="country.code" :value="country.code">
                                {{ country.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.country_code" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="phone" value="Téléphone (optionnel)" />
                        <TextInput
                            id="phone"
                            v-model="form.phone"
                            type="tel"
                            class="mt-1 block w-full"
                            placeholder="+352 123 456 789"
                        />
                        <InputError :message="form.errors.phone" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="vat_number">
                            N° TVA intracommunautaire
                            <span v-if="isB2B" class="text-gray-400 text-xs">(recommandé pour B2B)</span>
                            <span v-else class="text-gray-400 text-xs">(optionnel)</span>
                        </InputLabel>
                        <TextInput
                            id="vat_number"
                            v-model="form.vat_number"
                            type="text"
                            class="mt-1 block w-full font-mono uppercase"
                            placeholder="LU12345678"
                            :class="{ 'bg-gray-100 dark:bg-gray-600': !isB2B }"
                        />
                        <InputError :message="form.errors.vat_number" class="mt-2" />
                    </div>
                </div>

                <div v-if="isB2B" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="registration_number" value="N° d'identification (RCS / SIRET) (optionnel)" />
                        <TextInput
                            id="registration_number"
                            v-model="form.registration_number"
                            type="text"
                            class="mt-1 block w-full font-mono"
                            placeholder="B123456"
                        />
                        <InputError :message="form.errors.registration_number" class="mt-2" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Facturation -->
        <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                    Paramètres de facturation
                </h2>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div>
                    <InputLabel for="currency" value="Devise par défaut" />
                    <select
                        id="currency"
                        v-model="form.currency"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:max-w-xs"
                        required
                    >
                        <option v-for="currency in currencies" :key="currency.value" :value="currency.value">
                            {{ currency.label }}
                        </option>
                    </select>
                    <InputError :message="form.errors.currency" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="notes" value="Notes internes (optionnel)" />
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        rows="3"
                        placeholder="Notes internes sur ce client..."
                    ></textarea>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Ces notes ne seront pas visibles sur les factures.
                    </p>
                    <InputError :message="form.errors.notes" class="mt-2" />
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3">
            <Link
                :href="cancelRouteParams ? route(cancelRoute, cancelRouteParams) : route(cancelRoute)"
                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
            >
                Annuler
            </Link>
            <PrimaryButton
                :disabled="form.processing"
                :class="{ 'opacity-25': form.processing }"
            >
                <span v-if="form.processing">Enregistrement...</span>
                <span v-else>{{ submitLabel }}</span>
            </PrimaryButton>
        </div>
    </form>
</template>

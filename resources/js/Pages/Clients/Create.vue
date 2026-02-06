<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ClientForm from '@/Components/ClientForm.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
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
});

const form = useForm({
    name: '',
    contact_name: '',
    email: '',
    address: '',
    postal_code: '',
    city: '',
    country_code: 'LU',
    vat_number: '',
    registration_number: '',
    type: 'b2b',
    currency: 'EUR',
    phone: '',
    notes: '',
    locale: 'fr',
});

const submit = () => {
    form.post(route('clients.store'));
};
</script>

<template>
    <Head title="Nouveau client" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                Nouveau client
            </h1>
        </template>

        <div class="mx-auto max-w-3xl">
            <ClientForm
                :form="form"
                :client-types="clientTypes"
                :currencies="currencies"
                :countries="countries"
                submit-label="Créer le client"
                @submit="submit"
            />
        </div>
    </AppLayout>
</template>

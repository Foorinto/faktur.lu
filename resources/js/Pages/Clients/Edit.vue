<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ClientForm from '@/Components/ClientForm.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    client: {
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
});

const form = useForm({
    name: props.client.name,
    contact_name: props.client.contact_name ?? '',
    email: props.client.email,
    address: props.client.address ?? '',
    postal_code: props.client.postal_code ?? '',
    city: props.client.city ?? '',
    country_code: props.client.country_code,
    vat_number: props.client.vat_number ?? '',
    registration_number: props.client.registration_number ?? '',
    type: props.client.type,
    currency: props.client.currency,
    phone: props.client.phone ?? '',
    notes: props.client.notes ?? '',
    locale: props.client.locale ?? 'fr',
});

const submit = () => {
    form.put(route('clients.update', props.client.id));
};
</script>

<template>
    <Head :title="`Modifier ${client.name}`" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                Modifier {{ client.name }}
            </h1>
        </template>

        <div class="mx-auto max-w-3xl">
            <ClientForm
                :form="form"
                :client-types="clientTypes"
                :currencies="currencies"
                :countries="countries"
                submit-label="Enregistrer les modifications"
                cancel-route="clients.show"
                :cancel-route-params="client.id"
                @submit="submit"
            />
        </div>
    </AppLayout>
</template>

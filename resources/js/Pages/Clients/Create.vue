<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ClientForm from '@/Components/ClientForm.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

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
    peppolSchemes: {
        type: Array,
        default: () => [],
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
    peppol_endpoint_scheme: '',
    peppol_endpoint_id: '',
});

const submit = () => {
    form.post(route('clients.store'));
};
</script>

<template>
    <Head :title="t('new_client')" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                {{ t('new_client') }}
            </h1>
        </template>

        <div class="mx-auto max-w-3xl">
            <ClientForm
                :form="form"
                :client-types="clientTypes"
                :currencies="currencies"
                :countries="countries"
                :peppol-schemes="peppolSchemes"
                :submit-label="t('create')"
                @submit="submit"
            />
        </div>
    </AppLayout>
</template>

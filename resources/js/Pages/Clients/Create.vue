<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ClientForm from '@/Components/ClientForm.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';
import { useTour } from '@/Composables/useTour';

const { t } = useTranslations();
const { startTour } = useTour();

onMounted(() => setTimeout(() => startTour('clientCreate'), 600));

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
    vatRates: {
        type: Array,
        default: () => [],
    },
    isVatExempt: {
        type: Boolean,
        default: true,
    },
    sellerVatRegime: {
        type: String,
        default: 'franchise',
    },
    clientStatuses: {
        type: Array,
        default: () => [],
    },
    clientSources: {
        type: Array,
        default: () => [],
    },
    initialStatus: {
        type: String,
        default: 'active',
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
    status: props.initialStatus || 'active',
    currency: 'EUR',
    phone: '',
    notes: '',
    locale: 'fr',
    peppol_endpoint_scheme: '',
    peppol_endpoint_id: '',
    default_vat_rate: null,
    default_hourly_rate: null,
    accounting_id: '',
    source: null,
    estimated_value: null,
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
                :vat-rates="vatRates"
                :is-vat-exempt="isVatExempt"
                :seller-vat-regime="sellerVatRegime"
                :client-statuses="clientStatuses"
                :client-sources="clientSources"
                :initial-status="initialStatus"
                :submit-label="t('create')"
                @submit="submit"
            />
        </div>
    </AppLayout>
</template>

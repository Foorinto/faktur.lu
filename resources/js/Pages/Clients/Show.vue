<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import VatScenarioIndicator from '@/Components/VatScenarioIndicator.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const props = defineProps({
    client: {
        type: Object,
        required: true,
    },
});

const deleteClient = () => {
    if (confirm(t('confirm_delete_name', { name: props.client.name }))) {
        router.delete(route('clients.destroy', props.client.id));
    }
};

const getTypeLabel = (type) => {
    return type === 'b2b' ? t('client_b2b') : t('client_b2c');
};

const getTypeBadgeClass = (type) => {
    return type === 'b2b'
        ? 'bg-sky-100 text-sky-700 dark:bg-sky-900 dark:text-sky-300'
        : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300';
};
</script>

<template>
    <Head :title="client.name" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('clients.index')"
                        class="text-slate-400 hover:text-slate-500 dark:text-slate-500 dark:hover:text-slate-400"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                        </svg>
                    </Link>
                    <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                        {{ client.name }}
                    </h1>
                    <span
                        :class="getTypeBadgeClass(client.type)"
                        class="inline-flex items-center rounded-xl px-3 py-1 text-xs font-medium"
                    >
                        {{ getTypeLabel(client.type) }}
                    </span>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        :href="route('clients.edit', client.id)"
                        class="inline-flex items-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 hover:bg-slate-50 dark:bg-slate-700 dark:text-white dark:ring-slate-600 dark:hover:bg-slate-600"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" />
                        </svg>
                        {{ t('edit') }}
                    </Link>
                    <button
                        v-if="client.invoices_count === 0"
                        @click="deleteClient"
                        class="inline-flex items-center rounded-xl bg-pink-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-pink-500"
                    >
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.519.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" />
                        </svg>
                        {{ t('delete') }}
                    </button>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Main info -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Contact -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">
                            {{ t('client_info') }}
                        </h2>
                    </div>
                    <dl class="divide-y divide-slate-200 dark:divide-slate-700">
                        <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('name') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:col-span-2 sm:mt-0">
                                {{ client.name }}
                            </dd>
                        </div>
                        <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('email') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:col-span-2 sm:mt-0">
                                <a :href="`mailto:${client.email}`" class="text-primary-600 hover:text-primary-500 dark:text-primary-400">
                                    {{ client.email }}
                                </a>
                            </dd>
                        </div>
                        <div v-if="client.phone" class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('phone') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:col-span-2 sm:mt-0">
                                <a :href="`tel:${client.phone}`" class="text-primary-600 hover:text-primary-500 dark:text-primary-400">
                                    {{ client.phone }}
                                </a>
                            </dd>
                        </div>
                        <div v-if="client.address" class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('address') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:col-span-2 sm:mt-0 whitespace-pre-line">
                                {{ client.address }}
                                <template v-if="client.postal_code || client.city">
                                    <br />{{ client.postal_code }} {{ client.city }}
                                </template>
                                <template v-if="client.country_code !== 'LU'">
                                    <br />{{ client.country_code }}
                                </template>
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Fiscal info -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">
                            {{ t('billing_settings') }}
                        </h2>
                    </div>
                    <dl class="divide-y divide-slate-200 dark:divide-slate-700">
                        <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('client_type') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:col-span-2 sm:mt-0">
                                {{ getTypeLabel(client.type) }}
                            </dd>
                        </div>
                        <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('country') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:col-span-2 sm:mt-0">
                                {{ client.country_code }}
                            </dd>
                        </div>
                        <div v-if="client.vat_number" class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('vat_number') }}</dt>
                            <dd class="mt-1 text-sm font-mono text-slate-900 dark:text-white sm:col-span-2 sm:mt-0">
                                {{ client.vat_number }}
                            </dd>
                        </div>
                        <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('currency') }}</dt>
                            <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:col-span-2 sm:mt-0">
                                {{ client.currency }}
                            </dd>
                        </div>
                        <div v-if="client.vat_scenario" class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('vat_scenario_detected') }}</dt>
                            <dd class="mt-1 sm:col-span-2 sm:mt-0">
                                <VatScenarioIndicator :scenario="client.vat_scenario" size="sm" />
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Notes -->
                <div v-if="client.notes" class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">
                            {{ t('internal_notes') }}
                        </h2>
                    </div>
                    <div class="px-6 py-4">
                        <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line">
                            {{ client.notes }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Stats -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">
                            {{ t('reports') }}
                        </h2>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('invoices') }}</dt>
                            <dd class="mt-1 text-2xl font-semibold text-primary-600 dark:text-primary-400">
                                {{ client.invoices_count || 0 }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ t('time_tracking') }}</dt>
                            <dd class="mt-1 text-2xl font-semibold text-accent-turquoise dark:text-accent-turquoise">
                                {{ client.time_entries_count || 0 }}
                            </dd>
                        </div>
                    </div>
                </div>

                <!-- Quick actions -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">
                            {{ t('quick_actions') }}
                        </h2>
                    </div>
                    <div class="px-6 py-4 space-y-3">
                        <button
                            type="button"
                            disabled
                            class="w-full inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300"
                        >
                            <svg class="-ml-1 mr-2 h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                            </svg>
                            {{ t('new_invoice') }}
                        </button>
                        <button
                            type="button"
                            disabled
                            class="w-full inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300"
                        >
                            <svg class="-ml-1 mr-2 h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" />
                            </svg>
                            {{ t('start_new_timer') }}
                        </button>
                    </div>
                </div>

                <!-- Metadata -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-xl shadow-slate-200/50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:shadow-slate-900/50">
                    <div class="px-6 py-4">
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-slate-500 dark:text-slate-400">{{ t('date') }}</dt>
                                <dd class="text-slate-900 dark:text-white">
                                    {{ new Date(client.created_at).toLocaleDateString('fr-FR') }}
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-slate-500 dark:text-slate-400">Modifié le</dt>
                                <dd class="text-slate-900 dark:text-white">
                                    {{ new Date(client.updated_at).toLocaleDateString('fr-FR') }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AccountantLayout from '@/Layouts/AccountantLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    accountant: Object,
    clients: Array,
});
</script>

<template>
    <Head title="Mes clients" />

    <AccountantLayout :accountant="accountant">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Mes clients</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Accédez aux exports comptables de vos clients
            </p>
        </div>

        <div v-if="clients.length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucun client</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Vous n'avez pas encore de clients qui vous ont donné accès.
            </p>
        </div>

        <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="client in clients"
                :key="client.id"
                :href="route('accountant.client', client.id)"
                class="relative rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm hover:border-indigo-500 hover:ring-1 hover:ring-indigo-500 transition-all"
            >
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white truncate">
                            {{ client.name }}
                        </h3>
                        <p v-if="client.vat_number" class="text-sm text-gray-500 dark:text-gray-400">
                            TVA: {{ client.vat_number }}
                        </p>
                        <div class="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-400">
                            <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            {{ client.invoices_count }} facture{{ client.invoices_count !== 1 ? 's' : '' }}
                        </div>
                        <p v-if="client.last_invoice_at" class="mt-1 text-xs text-gray-400">
                            Dernière facture : {{ client.last_invoice_at }}
                        </p>
                    </div>
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </Link>
        </div>
    </AccountantLayout>
</template>

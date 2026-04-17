<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BillingNav from '@/Components/BillingNav.vue';

const props = defineProps({
    clients: Array,
    frequencies: Array,
    defaultVatRate: { type: Number, default: 17 },
});

const frequencyLabels = {
    weekly: 'Hebdomadaire',
    monthly: 'Mensuelle',
    quarterly: 'Trimestrielle',
    yearly: 'Annuelle',
};

const units = ['unit', 'hour', 'day', 'month', 'kg', 'km', 'm2', 'forfait'];

const form = useForm({
    client_id: '',
    title: '',
    frequency: 'monthly',
    next_invoice_date: new Date().toISOString().split('T')[0],
    ends_at: '',
    auto_finalize: false,
    auto_send: false,
    payment_delay_days: 30,
    notes: '',
    vat_mention: '',
    footer_message: '',
    currency: 'EUR',
    items: [
        { title: '', description: '', quantity: 1, unit: 'unit', unit_price: 0, vat_rate: props.defaultVatRate },
    ],
});

const addItem = () => {
    form.items.push({ title: '', description: '', quantity: 1, unit: 'unit', unit_price: 0, vat_rate: props.defaultVatRate });
};

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

const totalHt = () => {
    return form.items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
};

const totalTtc = () => {
    return form.items.reduce((sum, item) => {
        const ht = item.quantity * item.unit_price;
        return sum + ht + (ht * item.vat_rate / 100);
    }, 0);
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount);
};

const submit = () => {
    form.post(route('recurring-invoices.store'));
};
</script>

<template>
    <AppLayout>
        <template #header>
            <h1 class="text-xl font-bold text-slate-900 dark:text-white">Nouvelle récurrence</h1>
        </template>

        <BillingNav class="mb-6" />

        <div class="mb-4">
            <Link :href="route('recurring-invoices.index')" class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300">
                ← Retour aux récurrences
            </Link>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
                <!-- Client & Paramètres -->
                <div class="bg-white dark:bg-surface-dark rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Paramètres</h2>

                    <div class="grid sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Client *</label>
                            <select v-model="form.client_id" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500">
                                <option value="" disabled>Sélectionner un client</option>
                                <optgroup label="Actifs">
                                    <option v-for="client in clients.filter(c => c.status === 'active')" :key="client.id" :value="client.id">{{ client.name }}</option>
                                </optgroup>
                                <optgroup v-if="clients.some(c => c.status !== 'active')" label="Autres">
                                    <option v-for="client in clients.filter(c => c.status !== 'active')" :key="client.id" :value="client.id">{{ client.name }}</option>
                                </optgroup>
                            </select>
                            <p v-if="form.errors.client_id" class="mt-1 text-sm text-red-600">{{ form.errors.client_id }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Titre (optionnel)</label>
                            <input v-model="form.title" type="text" placeholder="Ex: Forfait maintenance mensuel" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Fréquence *</label>
                            <select v-model="form.frequency" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500">
                                <option v-for="freq in frequencies" :key="freq" :value="freq">{{ frequencyLabels[freq] }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Prochaine facture *</label>
                            <input v-model="form.next_invoice_date" type="date" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500" />
                            <p v-if="form.errors.next_invoice_date" class="mt-1 text-sm text-red-600">{{ form.errors.next_invoice_date }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Date de fin (optionnel)</label>
                            <input v-model="form.ends_at" type="date" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Délai de paiement (jours)</label>
                            <input v-model="form.payment_delay_days" type="number" min="0" max="365" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500" />
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="mt-5 flex flex-wrap gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input v-model="form.auto_finalize" type="checkbox" class="rounded border-gray-300 text-primary-500 focus:ring-primary-500" />
                            <span class="text-sm text-slate-700 dark:text-slate-300">Finaliser automatiquement</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input v-model="form.auto_send" type="checkbox" :disabled="!form.auto_finalize" class="rounded border-gray-300 text-primary-500 focus:ring-primary-500 disabled:opacity-50" />
                            <span class="text-sm text-slate-700 dark:text-slate-300" :class="{ 'opacity-50': !form.auto_finalize }">Envoyer par email automatiquement</span>
                        </label>
                    </div>
                </div>

                <!-- Lignes -->
                <div class="bg-white dark:bg-surface-dark rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Lignes de facture</h2>
                        <button type="button" @click="addItem" class="text-sm text-primary-500 hover:text-primary-600 font-medium">
                            + Ajouter une ligne
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div v-for="(item, index) in form.items" :key="index" class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4">
                            <div class="grid sm:grid-cols-6 gap-3">
                                <div class="sm:col-span-3">
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Désignation *</label>
                                    <input v-model="item.title" type="text" placeholder="Ex: Forfait maintenance" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Qté</label>
                                    <input v-model="item.quantity" type="number" step="0.01" min="0" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500" />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 mb-1">Prix unit. HT</label>
                                    <input v-model="item.unit_price" type="number" step="0.01" min="0" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500" />
                                </div>
                                <div class="flex items-end gap-2">
                                    <div class="flex-1">
                                        <label class="block text-xs font-medium text-slate-500 mb-1">TVA %</label>
                                        <input v-model="item.vat_rate" type="number" step="0.01" min="0" max="100" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500" />
                                    </div>
                                    <button v-if="form.items.length > 1" type="button" @click="removeItem(index)" class="p-2 text-slate-400 hover:text-red-500 transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="mt-2 grid sm:grid-cols-6 gap-3">
                                <div class="sm:col-span-3">
                                    <input v-model="item.description" type="text" placeholder="Description (optionnel)" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500" />
                                </div>
                                <div>
                                    <select v-model="item.unit" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500">
                                        <option v-for="u in units" :key="u" :value="u">{{ u }}</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2 text-right text-sm font-medium text-slate-700 dark:text-slate-300 self-center">
                                    {{ formatCurrency(item.quantity * item.unit_price) }} HT
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Totaux -->
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 text-right space-y-1">
                        <p class="text-sm text-slate-600 dark:text-slate-400">Total HT : <span class="font-semibold text-slate-900 dark:text-white">{{ formatCurrency(totalHt()) }}</span></p>
                        <p class="text-sm text-slate-600 dark:text-slate-400">Total TTC : <span class="font-semibold text-slate-900 dark:text-white">{{ formatCurrency(totalTtc()) }}</span></p>
                    </div>
                </div>

                <!-- Notes -->
                <div class="bg-white dark:bg-surface-dark rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Notes & mentions</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Notes (apparaissent sur la facture)</label>
                            <textarea v-model="form.notes" rows="2" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Pied de page</label>
                            <textarea v-model="form.footer_message" rows="2" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex items-center justify-end gap-3">
                    <Link :href="route('recurring-invoices.index')" class="px-5 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors">
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-primary-500 hover:bg-primary-600 disabled:bg-slate-400 text-white font-medium px-6 py-2.5 rounded-xl transition-colors text-sm"
                    >
                        {{ form.processing ? 'Création...' : 'Créer la récurrence' }}
                    </button>
                </div>
        </form>
    </AppLayout>
</template>

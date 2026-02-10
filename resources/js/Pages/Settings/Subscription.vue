<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useTranslations } from '@/Composables/useTranslations';

const page = usePage();

const { t } = useTranslations();

const props = defineProps({
    plans: Array,
    currentPlan: String,
    subscription: Object,
    usage: Object,
    invoices: Array,
    onTrial: Boolean,
    trialEndsAt: String,
});

const billingPeriod = ref('monthly');
const showCancelModal = ref(false);

const proPlan = computed(() => props.plans.find(p => p.name === 'pro'));
const starterPlan = computed(() => props.plans.find(p => p.name === 'starter'));
const isPro = computed(() => props.currentPlan === 'pro');
const isOnGracePeriod = computed(() => props.subscription?.ends_at && new Date(props.subscription.ends_at) > new Date());

const checkoutForm = ref(null);
const isCheckingOut = ref(false);

const cancelForm = useForm({});
const resumeForm = useForm({});

const startCheckout = () => {
    isCheckingOut.value = true;
    // Submit the native form for full page redirect (required by Stripe)
    checkoutForm.value.submit();
};

const cancelSubscription = () => {
    cancelForm.post(route('subscription.cancel'), {
        onSuccess: () => {
            showCancelModal.value = false;
        },
    });
};

const resumeSubscription = () => {
    resumeForm.post(route('subscription.resume'));
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(price);
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const getUsagePercentage = (used, limit) => {
    if (!limit) return 0;
    return Math.min(100, Math.round((used / limit) * 100));
};
</script>

<template>
    <Head title="Abonnement" />

    <AppLayout>
        <template #header>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">
                Abonnement
            </h1>
        </template>

        <!-- Settings Navigation -->
        <div class="mb-6 border-b border-slate-200 dark:border-slate-700">
            <nav class="flex space-x-8" aria-label="Settings tabs">
                <Link
                    :href="route('settings.business.edit')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300"
                >
                    Entreprise
                </Link>
                <Link
                    :href="route('settings.email')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300"
                >
                    Email
                </Link>
                <Link
                    :href="route('settings.email.provider')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300"
                >
                    Fournisseur Email
                </Link>
                <Link
                    :href="route('settings.accountant')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300"
                >
                    Accès Comptable
                </Link>
                <Link
                    :href="route('subscription.index')"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-primary-500 text-primary-600 dark:text-primary-400"
                >
                    Abonnement
                </Link>
            </nav>
        </div>

        <!-- Flash Messages -->
        <div v-if="page.props.flash?.success" class="mb-6 p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl">
            <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ page.props.flash.success }}</p>
        </div>
        <div v-if="page.props.flash?.error" class="mb-6 p-4 bg-pink-50 dark:bg-pink-900/20 border border-pink-200 dark:border-pink-800 rounded-xl">
            <p class="text-sm text-pink-700 dark:text-pink-300">{{ page.props.flash.error }}</p>
        </div>
        <div v-if="page.props.errors?.plan || page.props.errors?.billing_period" class="mb-6 p-4 bg-pink-50 dark:bg-pink-900/20 border border-pink-200 dark:border-pink-800 rounded-xl">
            <p class="text-sm text-pink-700 dark:text-pink-300">{{ page.props.errors.plan || page.props.errors.billing_period }}</p>
        </div>

        <div class="space-y-6">
            <!-- Current Plan Card -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                        Votre plan actuel
                    </h2>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div
                                :class="[
                                    'h-14 w-14 rounded-2xl flex items-center justify-center',
                                    isPro ? 'bg-primary-100 dark:bg-primary-900/30' : 'bg-slate-100 dark:bg-slate-700'
                                ]"
                            >
                                <svg v-if="isPro" class="h-7 w-7 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                </svg>
                                <svg v-else class="h-7 w-7 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-slate-900 dark:text-white">
                                    Plan {{ isPro ? 'Pro' : 'Starter' }}
                                </h3>
                                <p class="text-slate-500 dark:text-slate-400">
                                    <template v-if="isPro">
                                        {{ formatPrice(proPlan?.price_monthly || 7) }}/mois
                                    </template>
                                    <template v-else>
                                        Gratuit
                                    </template>
                                </p>
                            </div>
                        </div>

                        <div v-if="isPro && !isOnGracePeriod" class="flex items-center space-x-3">
                            <a
                                :href="route('subscription.portal')"
                                class="inline-flex items-center px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700"
                            >
                                Gérer le paiement
                            </a>
                            <button
                                @click="showCancelModal = true"
                                class="text-sm text-pink-600 hover:text-pink-800 dark:text-pink-400"
                            >
                                Annuler
                            </button>
                        </div>

                        <div v-else-if="isOnGracePeriod" class="text-right">
                            <p class="text-sm text-pink-600 dark:text-pink-400">
                                Annulé - Accès jusqu'au {{ formatDate(subscription?.ends_at) }}
                            </p>
                            <button
                                @click="resumeSubscription"
                                :disabled="resumeForm.processing"
                                class="mt-2 text-sm text-primary-600 hover:text-primary-800 font-medium"
                            >
                                Réactiver l'abonnement
                            </button>
                        </div>
                    </div>

                    <!-- Trial info -->
                    <div v-if="onTrial" class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl">
                        <p class="text-sm text-amber-800 dark:text-amber-200">
                            <svg class="inline h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                            </svg>
                            Période d'essai - se termine le {{ formatDate(trialEndsAt) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Usage Stats (Starter only) -->
            <div v-if="!isPro" class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                        Utilisation ce mois
                    </h2>
                </div>
                <div class="p-6 space-y-6">
                    <!-- Clients -->
                    <div>
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-slate-600 dark:text-slate-400">Clients</span>
                            <span class="font-medium text-slate-900 dark:text-white">
                                {{ usage.clients.used }} / {{ usage.clients.limit }}
                            </span>
                        </div>
                        <div class="h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                            <div
                                class="h-full bg-primary-500 rounded-full transition-all"
                                :style="{ width: getUsagePercentage(usage.clients.used, usage.clients.limit) + '%' }"
                            />
                        </div>
                    </div>

                    <!-- Invoices -->
                    <div>
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-slate-600 dark:text-slate-400">Factures</span>
                            <span class="font-medium text-slate-900 dark:text-white">
                                {{ usage.invoices_this_month.used }} / {{ usage.invoices_this_month.limit }}
                            </span>
                        </div>
                        <div class="h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                            <div
                                class="h-full bg-primary-500 rounded-full transition-all"
                                :style="{ width: getUsagePercentage(usage.invoices_this_month.used, usage.invoices_this_month.limit) + '%' }"
                            />
                        </div>
                    </div>

                    <!-- Quotes -->
                    <div>
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-slate-600 dark:text-slate-400">Devis</span>
                            <span class="font-medium text-slate-900 dark:text-white">
                                {{ usage.quotes_this_month.used }} / {{ usage.quotes_this_month.limit }}
                            </span>
                        </div>
                        <div class="h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                            <div
                                class="h-full bg-primary-500 rounded-full transition-all"
                                :style="{ width: getUsagePercentage(usage.quotes_this_month.used, usage.quotes_this_month.limit) + '%' }"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upgrade Card (Starter only) -->
            <div v-if="!isPro" class="bg-gradient-to-br from-primary-500 to-primary-700 rounded-2xl shadow-lg overflow-hidden">
                <div class="p-8">
                    <div class="flex items-center justify-between">
                        <div class="text-white">
                            <h3 class="text-2xl font-bold">Passez au plan Pro</h3>
                            <p class="mt-2 text-primary-100">
                                Débloquez l'export FAIA, les relances automatiques et bien plus.
                            </p>
                            <ul class="mt-4 space-y-2">
                                <li class="flex items-center text-sm">
                                    <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    Clients et factures illimités
                                </li>
                                <li class="flex items-center text-sm">
                                    <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    Export FAIA pour contrôle fiscal
                                </li>
                                <li class="flex items-center text-sm">
                                    <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    Archivage PDF longue durée
                                </li>
                            </ul>
                        </div>
                        <div class="text-center text-white">
                            <div class="mb-4">
                                <div class="flex items-center justify-center space-x-3 mb-4">
                                    <button
                                        @click="billingPeriod = 'monthly'"
                                        :class="[
                                            'px-3 py-1 rounded-lg text-sm font-medium transition-colors',
                                            billingPeriod === 'monthly' ? 'bg-white text-primary-600' : 'text-white/80 hover:text-white'
                                        ]"
                                    >
                                        Mensuel
                                    </button>
                                    <button
                                        @click="billingPeriod = 'yearly'"
                                        :class="[
                                            'px-3 py-1 rounded-lg text-sm font-medium transition-colors',
                                            billingPeriod === 'yearly' ? 'bg-white text-primary-600' : 'text-white/80 hover:text-white'
                                        ]"
                                    >
                                        Annuel (-17%)
                                    </button>
                                </div>
                                <p class="text-4xl font-bold">
                                    {{ billingPeriod === 'yearly' ? formatPrice(proPlan?.monthly_price_when_yearly || 5.83) : formatPrice(proPlan?.price_monthly || 7) }}
                                </p>
                                <p class="text-sm text-primary-100">/mois HT</p>
                            </div>
                            <form
                                ref="checkoutForm"
                                :action="route('subscription.checkout')"
                                method="POST"
                            >
                                <input type="hidden" name="_token" :value="page.props.csrf_token">
                                <input type="hidden" name="plan" value="pro">
                                <input type="hidden" name="billing_period" :value="billingPeriod">
                                <button
                                    type="submit"
                                    @click.prevent="startCheckout"
                                    :disabled="isCheckingOut"
                                    class="inline-flex items-center px-6 py-3 bg-white text-primary-600 font-semibold rounded-xl hover:bg-primary-50 transition-colors disabled:opacity-50"
                                >
                                    <span v-if="isCheckingOut">Chargement...</span>
                                    <span v-else>Passer à Pro</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoices History (Pro only) -->
            <div v-if="isPro && invoices?.length" class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                        Historique des factures
                    </h2>
                </div>
                <div class="divide-y divide-slate-200 dark:divide-slate-700">
                    <div
                        v-for="invoice in invoices"
                        :key="invoice.id"
                        class="px-6 py-4 flex items-center justify-between"
                    >
                        <div>
                            <p class="font-medium text-slate-900 dark:text-white">{{ invoice.date }}</p>
                            <p class="text-sm text-slate-500">{{ invoice.total }}</p>
                        </div>
                        <a
                            :href="invoice.url"
                            target="_blank"
                            class="text-primary-600 hover:text-primary-800 text-sm font-medium"
                        >
                            Télécharger PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancel Modal -->
        <Teleport to="body">
            <div v-if="showCancelModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-slate-900/50" @click="showCancelModal = false" />
                    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl max-w-md w-full p-6">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                            Annuler votre abonnement ?
                        </h3>
                        <p class="mt-2 text-slate-600 dark:text-slate-400">
                            Vous conserverez l'accès Pro jusqu'à la fin de votre période de facturation. Après cela, vous passerez automatiquement au plan Starter gratuit.
                        </p>
                        <div class="mt-6 flex justify-end space-x-3">
                            <SecondaryButton @click="showCancelModal = false">
                                Garder Pro
                            </SecondaryButton>
                            <button
                                @click="cancelSubscription"
                                :disabled="cancelForm.processing"
                                class="inline-flex items-center px-4 py-2 bg-pink-600 hover:bg-pink-700 text-white font-medium rounded-xl transition-colors disabled:opacity-50"
                            >
                                <span v-if="cancelForm.processing">Annulation...</span>
                                <span v-else>Confirmer l'annulation</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const props = defineProps({
    plans: Array,
});

const billingPeriod = ref('monthly');

const starterPlan = computed(() => props.plans.find(p => p.name === 'starter'));
const proPlan = computed(() => props.plans.find(p => p.name === 'pro'));

const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(price);
};

const featureLabels = {
    invoices: 'Factures',
    quotes: 'Devis',
    clients: 'Gestion clients',
    expenses: 'Suivi des dépenses',
    time_tracking: 'Suivi du temps',
    '2fa': 'Authentification 2FA',
    faia_export: 'Export FAIA (audit fiscal)',
    pdf_archive: 'Archivage PDF longue durée',
    email_reminders: 'Relances automatiques',
    no_branding: 'Sans branding faktur.lu',
    priority_support: 'Support prioritaire',
};

const allFeatures = [
    { key: 'invoices', label: 'Factures conformes Luxembourg' },
    { key: 'quotes', label: 'Devis professionnels' },
    { key: 'clients', label: 'Gestion clients' },
    { key: 'expenses', label: 'Suivi des dépenses' },
    { key: 'time_tracking', label: 'Suivi du temps' },
    { key: '2fa', label: 'Authentification 2 facteurs' },
    { key: 'faia_export', label: 'Export FAIA (contrôle fiscal)', pro: true },
    { key: 'pdf_archive', label: 'Archivage PDF/A 10 ans', pro: true },
    { key: 'email_reminders', label: 'Relances automatiques impayés', pro: true },
    { key: 'no_branding', label: 'Sans mention "faktur.lu"', pro: true },
    { key: 'priority_support', label: 'Support email prioritaire', pro: true },
];
</script>

<template>
    <Head title="Tarifs - faktur.lu" />

    <div class="min-h-screen bg-slate-50 dark:bg-slate-900">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <Link href="/" class="flex items-center">
                            <ApplicationLogo class="h-8 w-auto" />
                        </Link>
                    </div>
                    <div class="flex items-center space-x-4">
                        <Link
                            :href="route('login')"
                            class="text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white font-medium"
                        >
                            Connexion
                        </Link>
                        <Link
                            :href="route('register')"
                            class="inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-xl transition-colors"
                        >
                            Créer un compte
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero -->
        <div class="py-16 sm:py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h1 class="text-4xl sm:text-5xl font-bold text-slate-900 dark:text-white">
                    Tarification simple et transparente
                </h1>
                <p class="mt-4 text-xl text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">
                    Commencez gratuitement, passez à Pro quand votre activité grandit.
                </p>

                <!-- Billing toggle -->
                <div class="mt-10 flex justify-center items-center space-x-4">
                    <span
                        :class="[
                            'text-sm font-medium',
                            billingPeriod === 'monthly' ? 'text-slate-900 dark:text-white' : 'text-slate-500'
                        ]"
                    >
                        Mensuel
                    </span>
                    <button
                        @click="billingPeriod = billingPeriod === 'monthly' ? 'yearly' : 'monthly'"
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                        :class="billingPeriod === 'yearly' ? 'bg-primary-500' : 'bg-slate-300'"
                    >
                        <span
                            class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                            :class="billingPeriod === 'yearly' ? 'translate-x-6' : 'translate-x-1'"
                        />
                    </button>
                    <span
                        :class="[
                            'text-sm font-medium',
                            billingPeriod === 'yearly' ? 'text-slate-900 dark:text-white' : 'text-slate-500'
                        ]"
                    >
                        Annuel
                    </span>
                    <span
                        v-if="billingPeriod === 'yearly'"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800"
                    >
                        2 mois offerts
                    </span>
                </div>
            </div>
        </div>

        <!-- Pricing cards -->
        <div class="pb-24">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid md:grid-cols-2 gap-8">
                    <!-- Starter Plan -->
                    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-200 dark:border-slate-700 p-8">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Starter</h3>
                                <p class="text-slate-500 dark:text-slate-400">Pour démarrer</p>
                            </div>
                        </div>

                        <div class="mt-6">
                            <div class="flex items-baseline">
                                <span class="text-5xl font-bold text-slate-900 dark:text-white">Gratuit</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">pour toujours</p>
                        </div>

                        <div class="mt-8">
                            <Link
                                :href="route('register')"
                                class="block w-full text-center px-6 py-3 border-2 border-primary-500 text-primary-600 dark:text-primary-400 font-semibold rounded-xl hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors"
                            >
                                Commencer gratuitement
                            </Link>
                        </div>

                        <div class="mt-8 space-y-4">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">Limites :</p>
                            <ul class="space-y-3">
                                <li class="flex items-center text-slate-600 dark:text-slate-400">
                                    <span class="text-slate-400 mr-3">•</span>
                                    {{ starterPlan?.limits?.max_clients || 2 }} clients maximum
                                </li>
                                <li class="flex items-center text-slate-600 dark:text-slate-400">
                                    <span class="text-slate-400 mr-3">•</span>
                                    {{ starterPlan?.limits?.max_invoices_per_month || 2 }} factures/mois
                                </li>
                                <li class="flex items-center text-slate-600 dark:text-slate-400">
                                    <span class="text-slate-400 mr-3">•</span>
                                    {{ starterPlan?.limits?.max_quotes_per_month || 2 }} devis/mois
                                </li>
                                <li class="flex items-center text-slate-600 dark:text-slate-400">
                                    <span class="text-slate-400 mr-3">•</span>
                                    {{ starterPlan?.limits?.max_emails_per_month || 2 }} emails/mois
                                </li>
                            </ul>
                        </div>

                        <div class="mt-8 pt-8 border-t border-slate-200 dark:border-slate-700">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white mb-4">Inclus :</p>
                            <ul class="space-y-3">
                                <li
                                    v-for="feature in allFeatures.filter(f => !f.pro)"
                                    :key="feature.key"
                                    class="flex items-center text-slate-600 dark:text-slate-400"
                                >
                                    <svg class="h-5 w-5 text-emerald-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    {{ feature.label }}
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Pro Plan -->
                    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl border-2 border-primary-500 p-8 relative">
                        <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                            <span class="inline-flex items-center px-4 py-1 rounded-full text-sm font-semibold bg-primary-500 text-white">
                                Recommandé
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Pro</h3>
                                <p class="text-slate-500 dark:text-slate-400">Pour les indépendants</p>
                            </div>
                        </div>

                        <div class="mt-6">
                            <div class="flex items-baseline">
                                <span class="text-5xl font-bold text-slate-900 dark:text-white">
                                    {{ billingPeriod === 'yearly' ? formatPrice(proPlan?.monthly_price_when_yearly || 5.83) : formatPrice(proPlan?.price_monthly || 7) }}
                                </span>
                                <span class="ml-2 text-slate-500">/mois</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">
                                <template v-if="billingPeriod === 'yearly'">
                                    {{ formatPrice(proPlan?.price_yearly || 70) }} facturé annuellement
                                </template>
                                <template v-else>
                                    HT, facturé mensuellement
                                </template>
                            </p>
                        </div>

                        <div class="mt-8">
                            <Link
                                :href="route('register')"
                                class="block w-full text-center px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-xl transition-colors"
                            >
                                Commencer l'essai gratuit
                            </Link>
                        </div>

                        <div class="mt-8 space-y-4">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">Illimité :</p>
                            <ul class="space-y-3">
                                <li class="flex items-center text-slate-600 dark:text-slate-400">
                                    <svg class="h-5 w-5 text-primary-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    Clients illimités
                                </li>
                                <li class="flex items-center text-slate-600 dark:text-slate-400">
                                    <svg class="h-5 w-5 text-primary-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    Factures illimitées
                                </li>
                                <li class="flex items-center text-slate-600 dark:text-slate-400">
                                    <svg class="h-5 w-5 text-primary-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    Devis illimités
                                </li>
                            </ul>
                        </div>

                        <div class="mt-8 pt-8 border-t border-slate-200 dark:border-slate-700">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white mb-4">Tout de Starter, plus :</p>
                            <ul class="space-y-3">
                                <li
                                    v-for="feature in allFeatures.filter(f => f.pro)"
                                    :key="feature.key"
                                    class="flex items-center text-slate-600 dark:text-slate-400"
                                >
                                    <svg class="h-5 w-5 text-emerald-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    {{ feature.label }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ -->
        <div class="bg-white dark:bg-slate-800 py-24">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-center text-slate-900 dark:text-white mb-12">
                    Questions fréquentes
                </h2>

                <div class="space-y-8">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                            Puis-je passer de Starter à Pro à tout moment ?
                        </h3>
                        <p class="mt-2 text-slate-600 dark:text-slate-400">
                            Oui, vous pouvez upgrader à tout moment. Votre abonnement Pro sera activé immédiatement.
                        </p>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                            Que se passe-t-il si j'annule mon abonnement Pro ?
                        </h3>
                        <p class="mt-2 text-slate-600 dark:text-slate-400">
                            Vous conservez l'accès Pro jusqu'à la fin de votre période de facturation. Ensuite, vous repassez automatiquement en plan Starter. Vos données sont conservées.
                        </p>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                            L'export FAIA est-il vraiment important ?
                        </h3>
                        <p class="mt-2 text-slate-600 dark:text-slate-400">
                            Oui. En cas de contrôle fiscal au Luxembourg, l'Administration des contributions directes peut exiger vos données au format FAIA. C'est obligatoire pour les entreprises assujetties à la TVA.
                        </p>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                            Comment fonctionne la facturation ?
                        </h3>
                        <p class="mt-2 text-slate-600 dark:text-slate-400">
                            Nous utilisons Stripe pour les paiements sécurisés. Vous pouvez payer par carte bancaire. La facture est disponible dans votre espace client.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div class="py-24">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl font-bold text-slate-900 dark:text-white">
                    Prêt à simplifier votre facturation ?
                </h2>
                <p class="mt-4 text-xl text-slate-600 dark:text-slate-400">
                    Rejoignez les entrepreneurs luxembourgeois qui font confiance à faktur.lu
                </p>
                <div class="mt-8">
                    <Link
                        :href="route('register')"
                        class="inline-flex items-center px-8 py-4 bg-primary-500 hover:bg-primary-600 text-white font-semibold rounded-xl text-lg transition-colors"
                    >
                        Créer mon compte gratuit
                    </Link>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-slate-900 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="flex items-center">
                        <ApplicationLogo class="h-8 w-auto text-white" />
                    </div>
                    <div class="mt-4 md:mt-0 flex space-x-6">
                        <Link :href="route('legal.mentions')" class="text-slate-400 hover:text-white text-sm">
                            Mentions légales
                        </Link>
                        <Link :href="route('legal.privacy')" class="text-slate-400 hover:text-white text-sm">
                            Confidentialité
                        </Link>
                        <Link :href="route('legal.terms')" class="text-slate-400 hover:text-white text-sm">
                            CGU
                        </Link>
                    </div>
                </div>
                <div class="mt-8 text-center text-slate-500 text-sm">
                    © {{ new Date().getFullYear() }} faktur.lu. Tous droits réservés.
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';

const { localizedRoute } = useLocalizedRoute();

const props = defineProps({
    plans: Array,
});

const mobileMenuOpen = ref(false);
const billingPeriod = ref('monthly');

const freePlan = computed(() => props.plans.find(p => p.name === 'free'));
const essentielPlan = computed(() => props.plans.find(p => p.name === 'essentiel'));
const proPlan = computed(() => props.plans.find(p => p.name === 'pro'));

const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(price);
};

const freeFeatures = [
    '3 factures/mois',
    '5 clients maximum',
    '2 devis/mois',
    'Gestion des dépenses (10/mois)',
    'Authentification 2FA',
];

const freeLimitations = [
    'Watermark sur les PDF',
    'Pas de projets ni time tracking',
    'Pas de CRM ni RH',
    'Pas d\'export comptable',
];

const essentielFeatures = [
    '50 factures/mois',
    '100 clients maximum',
    '20 devis/mois',
    '30 dépenses/mois',
    '10 projets actifs',
    'Suivi du temps',
    'Portail comptable (1 expert)',
    'Exports Sage BOB / FID-Manager',
    'Peppol B2G (10/mois)',
];

const proFeatures = [
    'Tout illimité (clients, factures, devis)',
    'Module RH complet (15 employés)',
    'CRM avancé (interactions, rappels, tags)',
    'Export FAIA (contrôle fiscal)',
    'Archivage PDF/A 10 ans',
    'Factur-X / ZUGFeRD',
    'Peppol illimité + transmission',
    'Relances automatiques impayés',
    '5 utilisateurs, 3 comptables',
    'Sans mention "faktur.lu"',
    'Support prioritaire',
];
</script>

<template>
    <SeoHead
        title="Tarifs - faktur.lu | Logiciel de facturation Luxembourg"
        description="Découvrez les tarifs de faktur.lu : plan Gratuit, Essentiel à 5€/mois ou Pro à 15€/mois. Facturation conforme Luxembourg avec export FAIA."
        canonical-path="/tarifs"
        route-name="pricing"
    />

    <div class="min-h-screen bg-slate-50 dark:bg-surface-dark">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-surface-card border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <Link href="/" class="flex items-center">
                            <ApplicationLogo class="h-8 w-auto" />
                        </Link>
                    </div>
                    <div class="hidden md:flex items-center space-x-4">
                        <Link
                            :href="route('login')"
                            class="text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white font-medium"
                        >
                            Connexion
                        </Link>
                        <Link
                            :href="route('register')"
                            class="inline-flex items-center px-4 py-2 bg-accent-rose hover:bg-pink-500 text-white font-semibold rounded-xl transition-colors"
                        >
                            Essayer gratuitement
                        </Link>
                    </div>

                    <!-- Mobile menu button -->
                    <button
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="md:hidden p-2 text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Mobile menu -->
                <div v-if="mobileMenuOpen" class="md:hidden py-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col space-y-3">
                        <Link :href="route('login')" @click="mobileMenuOpen = false" class="text-sm font-medium text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white py-2">
                            Connexion
                        </Link>
                        <Link :href="route('register')" @click="mobileMenuOpen = false" class="bg-accent-rose hover:bg-pink-500 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-colors text-center">
                            Essayer gratuitement
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
                    14 jours d'essai gratuit avec toutes les fonctionnalités Pro
                </p>

                <!-- Trial badge -->
                <div class="mt-6 inline-flex items-center gap-2 px-4 py-2 bg-emerald-50 dark:bg-emerald-900/30 rounded-full">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span class="text-emerald-700 dark:text-emerald-300 font-medium">Sans carte bancaire</span>
                </div>

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
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300"
                    >
                        2 mois offerts
                    </span>
                </div>
            </div>
        </div>

        <!-- Pricing cards -->
        <div class="pb-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid md:grid-cols-3 gap-6 max-w-5xl mx-auto">
                    <!-- FREE Plan -->
                    <div class="bg-white dark:bg-surface-card rounded-3xl shadow-xl border border-gray-200 dark:border-gray-700 p-8 flex flex-col">
                        <div>
                            <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Gratuit</h3>
                            <p class="text-slate-500 dark:text-slate-400">Pour découvrir</p>
                        </div>

                        <div class="mt-6">
                            <div class="flex items-baseline">
                                <span class="text-5xl font-bold text-slate-900 dark:text-white">0 €</span>
                                <span class="ml-2 text-slate-500">/mois</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">Pour toujours</p>
                        </div>

                        <div class="mt-8">
                            <Link
                                :href="route('register')"
                                class="block w-full text-center px-6 py-3 border-2 border-gray-300 dark:border-gray-600 text-slate-700 dark:text-slate-300 font-semibold rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                            >
                                Créer un compte
                            </Link>
                        </div>

                        <div class="mt-8 space-y-4 flex-1">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">Inclus :</p>
                            <ul class="space-y-3">
                                <li v-for="feature in freeFeatures" :key="feature" class="flex items-start text-sm text-slate-600 dark:text-slate-400">
                                    <svg class="h-5 w-5 text-emerald-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    {{ feature }}
                                </li>
                            </ul>
                            <ul class="space-y-2 pt-2">
                                <li v-for="limit in freeLimitations" :key="limit" class="flex items-start text-sm text-slate-400 dark:text-slate-500">
                                    <svg class="h-5 w-5 text-slate-300 dark:text-slate-600 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                    {{ limit }}
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Essentiel Plan -->
                    <div class="bg-white dark:bg-surface-card rounded-3xl shadow-xl border border-gray-200 dark:border-gray-700 p-8 flex flex-col">
                        <div>
                            <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Essentiel</h3>
                            <p class="text-slate-500 dark:text-slate-400">Pour les freelances</p>
                        </div>

                        <div class="mt-6">
                            <div class="flex items-baseline">
                                <span class="text-5xl font-bold text-slate-900 dark:text-white">
                                    {{ billingPeriod === 'yearly' ? formatPrice(essentielPlan?.monthly_price_when_yearly || 4.17) : formatPrice(essentielPlan?.price_monthly || 5) }}
                                </span>
                                <span class="ml-2 text-slate-500">/mois</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">
                                <template v-if="billingPeriod === 'yearly'">
                                    {{ formatPrice(essentielPlan?.price_yearly || 50) }} facturé annuellement
                                </template>
                                <template v-else>
                                    HT, facturé mensuellement
                                </template>
                            </p>
                        </div>

                        <div class="mt-8">
                            <Link
                                :href="route('register')"
                                class="block w-full text-center px-6 py-3 border-2 border-accent-rose text-accent-rose dark:text-pink-400 font-semibold rounded-xl hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors"
                            >
                                Essayer 14 jours gratuits
                            </Link>
                        </div>

                        <div class="mt-8 space-y-4 flex-1">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">Inclus :</p>
                            <ul class="space-y-3">
                                <li v-for="feature in essentielFeatures" :key="feature" class="flex items-start text-sm text-slate-600 dark:text-slate-400">
                                    <svg class="h-5 w-5 text-emerald-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    {{ feature }}
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Pro Plan -->
                    <div class="bg-white dark:bg-surface-card rounded-3xl shadow-xl border-2 border-primary-500 p-8 relative flex flex-col">
                        <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                            <span class="inline-flex items-center px-4 py-1 rounded-full text-sm font-semibold bg-primary-500 text-white">
                                Recommandé
                            </span>
                        </div>

                        <div>
                            <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Pro</h3>
                            <p class="text-slate-500 dark:text-slate-400">Pour les PME</p>
                        </div>

                        <div class="mt-6">
                            <div class="flex items-baseline">
                                <span class="text-5xl font-bold text-slate-900 dark:text-white">
                                    {{ billingPeriod === 'yearly' ? formatPrice(proPlan?.monthly_price_when_yearly || 12.5) : formatPrice(proPlan?.price_monthly || 15) }}
                                </span>
                                <span class="ml-2 text-slate-500">/mois</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">
                                <template v-if="billingPeriod === 'yearly'">
                                    {{ formatPrice(proPlan?.price_yearly || 150) }} facturé annuellement
                                </template>
                                <template v-else>
                                    HT, facturé mensuellement
                                </template>
                            </p>
                        </div>

                        <div class="mt-8">
                            <Link
                                :href="route('register')"
                                class="block w-full text-center px-6 py-3 bg-accent-rose hover:bg-pink-500 text-white font-semibold rounded-xl transition-colors"
                            >
                                Essayer 14 jours gratuits
                            </Link>
                        </div>

                        <div class="mt-8 space-y-4 flex-1">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">Tout de Essentiel, plus :</p>
                            <ul class="space-y-3">
                                <li v-for="feature in proFeatures" :key="feature" class="flex items-start text-sm text-slate-600 dark:text-slate-400">
                                    <svg class="h-5 w-5 text-emerald-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    {{ feature }}
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- FAQ -->
        <div class="bg-white dark:bg-surface-card py-24">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-center text-slate-900 dark:text-white mb-12">
                    Questions fréquentes
                </h2>

                <div class="space-y-8">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                            Comment fonctionne l'essai gratuit ?
                        </h3>
                        <p class="mt-2 text-slate-600 dark:text-slate-400">
                            Profitez de 14 jours d'accès complet à toutes les fonctionnalités Pro, sans carte bancaire.
                            À la fin de l'essai, choisissez le plan qui vous convient pour continuer.
                        </p>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                            Que se passe-t-il après l'essai si je ne m'abonne pas ?
                        </h3>
                        <p class="mt-2 text-slate-600 dark:text-slate-400">
                            Votre compte passe automatiquement au plan Gratuit : vous pouvez toujours créer des factures
                            (3/mois) et gérer jusqu'à 5 clients. Abonnez-vous à tout moment pour débloquer plus de fonctionnalités.
                        </p>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                            Puis-je changer de plan à tout moment ?
                        </h3>
                        <p class="mt-2 text-slate-600 dark:text-slate-400">
                            Oui, vous pouvez passer d'Essentiel à Pro à tout moment. Le changement est immédiat
                            et le prorata est calculé automatiquement.
                        </p>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                            L'export FAIA est-il vraiment important ?
                        </h3>
                        <p class="mt-2 text-slate-600 dark:text-slate-400">
                            Oui. En cas de contrôle fiscal au Luxembourg, l'Administration des contributions directes
                            peut exiger vos données au format FAIA. C'est obligatoire pour les entreprises assujetties à la TVA.
                        </p>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                            Comment fonctionne la facturation ?
                        </h3>
                        <p class="mt-2 text-slate-600 dark:text-slate-400">
                            Nous utilisons Stripe pour les paiements sécurisés. Vous pouvez payer par carte bancaire.
                            La facture est disponible dans votre espace client.
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
                <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <Link
                        :href="route('register')"
                        class="inline-flex items-center px-8 py-4 bg-accent-rose hover:bg-pink-500 text-white font-semibold rounded-xl text-lg transition-colors"
                    >
                        Essayer 14 jours gratuitement
                    </Link>
                    <span class="text-slate-500 dark:text-slate-400 text-sm">Sans carte bancaire</span>
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
                        <Link :href="localizedRoute('legal.mentions')" class="text-slate-400 hover:text-white text-sm">
                            Mentions légales
                        </Link>
                        <Link :href="localizedRoute('legal.privacy')" class="text-slate-400 hover:text-white text-sm">
                            Confidentialité
                        </Link>
                        <Link :href="localizedRoute('legal.terms')" class="text-slate-400 hover:text-white text-sm">
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

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

const lastUpdated = '9 février 2026';
</script>

<template>
    <Head title="Mentions légales" />

    <div class="min-h-screen bg-slate-50">
        <!-- Header -->
        <header class="bg-white border-b border-slate-200">
            <nav class="mx-auto max-w-4xl px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <Link href="/" class="flex items-center space-x-2.5">
                        <div class="bg-[#9b5de5] p-2 rounded-xl">
                            <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <span class="text-lg font-bold text-slate-900">faktur.lu</span>
                    </Link>
                    <Link
                        :href="route('login')"
                        class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors"
                    >
                        Connexion
                    </Link>
                </div>
            </nav>
        </header>

        <!-- Content -->
        <main class="mx-auto max-w-4xl px-6 lg:px-8 py-12">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 lg:p-12">
                <h1 class="text-3xl font-bold text-slate-900 mb-2">Mentions légales</h1>
                <p class="text-sm text-slate-500 mb-8">Dernière mise à jour : {{ lastUpdated }}</p>

                <!-- Table des matières -->
                <nav class="mb-10 p-4 bg-slate-50 rounded-xl">
                    <h2 class="font-semibold text-slate-900 mb-3">Sommaire</h2>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#editeur" class="text-[#9b5de5] hover:underline">1. Éditeur du site</a></li>
                        <li><a href="#hebergeur" class="text-[#9b5de5] hover:underline">2. Hébergeur</a></li>
                        <li><a href="#propriete" class="text-[#9b5de5] hover:underline">3. Propriété intellectuelle</a></li>
                        <li><a href="#donnees" class="text-[#9b5de5] hover:underline">4. Protection des données</a></li>
                        <li><a href="#cookies" class="text-[#9b5de5] hover:underline">5. Cookies</a></li>
                        <li><a href="#contact" class="text-[#9b5de5] hover:underline">6. Contact</a></li>
                    </ul>
                </nav>

                <div class="prose prose-slate max-w-none">
                    <!-- 1. Éditeur -->
                    <section id="editeur" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">1. Éditeur du site</h2>
                        <p class="text-slate-600 mb-4">
                            Le site <strong>faktur.lu</strong> est édité par :
                        </p>
                        <div class="bg-slate-50 rounded-lg p-4 text-sm">
                            <ul class="space-y-2 text-slate-700">
                                <li><strong>Raison sociale :</strong> Alexandre Beaudier</li>
                                <li><strong>Forme juridique :</strong> Entreprise individuelle</li>
                                <li><strong>Adresse :</strong> 13, Rue du Stade John F. Kennedy, L-3502 Dudelange, Luxembourg</li>
                                <li><strong>Numéro RCS Luxembourg :</strong> A46648</li>
                                <li><strong>Numéro de TVA intracommunautaire :</strong> LU37176916</li>
                                <li><strong>Email :</strong> <a href="mailto:contact@faktur.lu" class="text-[#9b5de5]">contact@faktur.lu</a></li>
                                <li><strong>Directeur de la publication :</strong> Alexandre Beaudier</li>
                            </ul>
                        </div>
                    </section>

                    <!-- 2. Hébergeur -->
                    <section id="hebergeur" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">2. Hébergeur</h2>
                        <p class="text-slate-600 mb-4">
                            Le site faktur.lu est hébergé par :
                        </p>
                        <div class="bg-slate-50 rounded-lg p-4 text-sm">
                            <ul class="space-y-2 text-slate-700">
                                <li><strong>Nom :</strong> o2switch</li>
                                <li><strong>Adresse :</strong> Chemin des Pardiaux, 63000 Clermont-Ferrand, France</li>
                                <li><strong>Site web :</strong> <a href="https://www.o2switch.fr" target="_blank" rel="noopener" class="text-[#9b5de5]">www.o2switch.fr</a></li>
                            </ul>
                        </div>
                    </section>

                    <!-- 3. Propriété intellectuelle -->
                    <section id="propriete" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">3. Propriété intellectuelle</h2>
                        <p class="text-slate-600 mb-4">
                            L'ensemble du contenu du site faktur.lu (textes, images, graphismes, logo, icônes, logiciels, etc.) est la propriété exclusive de Alexandre Beaudier ou de ses partenaires, sauf mention contraire.
                        </p>
                        <p class="text-slate-600 mb-4">
                            Toute reproduction, représentation, modification, publication, adaptation de tout ou partie des éléments du site, quel que soit le moyen ou le procédé utilisé, est interdite sans l'autorisation écrite préalable de Alexandre Beaudier.
                        </p>
                        <p class="text-slate-600">
                            Toute exploitation non autorisée du site ou de l'un des éléments qu'il contient sera considérée comme constitutive d'une contrefaçon et poursuivie conformément aux dispositions du droit luxembourgeois.
                        </p>
                    </section>

                    <!-- 4. Protection des données -->
                    <section id="donnees" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">4. Protection des données personnelles</h2>
                        <p class="text-slate-600 mb-4">
                            Conformément au Règlement Général sur la Protection des Données (RGPD) et à la loi luxembourgeoise du 1er août 2018 relative à la protection des données personnelles, vous disposez de droits sur vos données personnelles.
                        </p>
                        <p class="text-slate-600 mb-4">
                            Pour plus d'informations sur le traitement de vos données et l'exercice de vos droits, veuillez consulter notre
                            <Link :href="route('legal.privacy')" class="text-[#9b5de5] hover:underline">Politique de confidentialité</Link>.
                        </p>
                        <p class="text-slate-600">
                            <strong>Autorité de contrôle :</strong> Commission Nationale pour la Protection des Données (CNPD)<br>
                            <a href="https://cnpd.public.lu" target="_blank" rel="noopener" class="text-[#9b5de5]">https://cnpd.public.lu</a>
                        </p>
                    </section>

                    <!-- 5. Cookies -->
                    <section id="cookies" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">5. Cookies</h2>
                        <p class="text-slate-600 mb-4">
                            Le site faktur.lu utilise des cookies pour assurer son bon fonctionnement et améliorer l'expérience utilisateur.
                        </p>
                        <p class="text-slate-600">
                            Pour en savoir plus sur les cookies utilisés et gérer vos préférences, consultez notre
                            <Link :href="route('legal.cookies')" class="text-[#9b5de5] hover:underline">Politique relative aux cookies</Link>.
                        </p>
                    </section>

                    <!-- 6. Contact -->
                    <section id="contact">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">6. Contact</h2>
                        <p class="text-slate-600 mb-4">
                            Pour toute question concernant ces mentions légales, vous pouvez nous contacter :
                        </p>
                        <ul class="text-slate-600 space-y-1">
                            <li><strong>Email :</strong> <a href="mailto:contact@faktur.lu" class="text-[#9b5de5]">contact@faktur.lu</a></li>
                            <li><strong>Adresse :</strong> 13, Rue du Stade John F. Kennedy, L-3502 Dudelange, Luxembourg</li>
                        </ul>
                    </section>
                </div>
            </div>

            <!-- Other legal links -->
            <div class="mt-8 flex flex-wrap gap-4 justify-center text-sm">
                <Link :href="route('legal.privacy')" class="text-slate-600 hover:text-[#9b5de5]">Politique de confidentialité</Link>
                <span class="text-slate-300">|</span>
                <Link :href="route('legal.terms')" class="text-slate-600 hover:text-[#9b5de5]">Conditions générales</Link>
                <span class="text-slate-300">|</span>
                <Link :href="route('legal.cookies')" class="text-slate-600 hover:text-[#9b5de5]">Politique cookies</Link>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-200 py-8">
            <div class="mx-auto max-w-4xl px-6 lg:px-8 text-center text-sm text-slate-500">
                &copy; {{ new Date().getFullYear() }} faktur.lu - Tous droits réservés
            </div>
        </footer>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';

const { t } = useTranslations();

const lastUpdated = '9 février 2026';
</script>

<template>
    <Head title="Politique relative aux cookies" />

    <MarketingLayout>
        <!-- Content -->
        <div class="mx-auto max-w-4xl px-6 lg:px-8 py-12">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 lg:p-12">
                <h1 class="text-3xl font-bold text-slate-900 mb-2">Politique relative aux cookies</h1>
                <p class="text-sm text-slate-500 mb-8">Dernière mise à jour : {{ lastUpdated }}</p>

                <!-- Table des matières -->
                <nav class="mb-10 p-4 bg-slate-50 rounded-xl">
                    <h2 class="font-semibold text-slate-900 mb-3">Sommaire</h2>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#introduction" class="text-[#9b5de5] hover:underline">1. Qu'est-ce qu'un cookie ?</a></li>
                        <li><a href="#cookies-utilises" class="text-[#9b5de5] hover:underline">2. Cookies utilisés</a></li>
                        <li><a href="#cookies-tiers" class="text-[#9b5de5] hover:underline">3. Cookies tiers</a></li>
                        <li><a href="#duree" class="text-[#9b5de5] hover:underline">4. Durée de conservation</a></li>
                        <li><a href="#gestion" class="text-[#9b5de5] hover:underline">5. Gestion des cookies</a></li>
                        <li><a href="#contact" class="text-[#9b5de5] hover:underline">6. Contact</a></li>
                    </ul>
                </nav>

                <div class="prose prose-slate max-w-none">
                    <!-- 1. Introduction -->
                    <section id="introduction" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">1. Qu'est-ce qu'un cookie ?</h2>
                        <p class="text-slate-600 mb-4">
                            Un cookie est un petit fichier texte déposé sur votre appareil (ordinateur, tablette, smartphone) lors de la visite d'un site web. Il permet au site de mémoriser des informations sur votre visite, comme vos préférences de langue ou votre statut de connexion.
                        </p>
                        <p class="text-slate-600">
                            Les cookies peuvent être déposés par le site que vous visitez ("cookies propriétaires") ou par des services tiers ("cookies tiers").
                        </p>
                    </section>

                    <!-- 2. Cookies utilisés -->
                    <section id="cookies-utilises" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">2. Cookies utilisés par faktur.lu</h2>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">2.1 Cookies strictement nécessaires</h3>
                        <p class="text-slate-600 mb-4">
                            Ces cookies sont indispensables au fonctionnement du site. Ils ne peuvent pas être désactivés.
                        </p>
                        <div class="overflow-x-auto mb-6">
                            <table class="w-full text-sm border-collapse">
                                <thead>
                                    <tr class="bg-slate-100">
                                        <th class="border border-slate-200 px-4 py-2 text-left font-medium">Cookie</th>
                                        <th class="border border-slate-200 px-4 py-2 text-left font-medium">Finalité</th>
                                        <th class="border border-slate-200 px-4 py-2 text-left font-medium">Durée</th>
                                    </tr>
                                </thead>
                                <tbody class="text-slate-600">
                                    <tr>
                                        <td class="border border-slate-200 px-4 py-2 font-mono text-xs">faktur_session</td>
                                        <td class="border border-slate-200 px-4 py-2">Identification de la session utilisateur</td>
                                        <td class="border border-slate-200 px-4 py-2">2 heures</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-slate-200 px-4 py-2 font-mono text-xs">XSRF-TOKEN</td>
                                        <td class="border border-slate-200 px-4 py-2">Protection contre les attaques CSRF</td>
                                        <td class="border border-slate-200 px-4 py-2">2 heures</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-slate-200 px-4 py-2 font-mono text-xs">remember_web_*</td>
                                        <td class="border border-slate-200 px-4 py-2">Maintien de la connexion (si "Se souvenir de moi")</td>
                                        <td class="border border-slate-200 px-4 py-2">30 jours</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">2.2 Cookies fonctionnels</h3>
                        <p class="text-slate-600 mb-4">
                            Ces cookies permettent d'améliorer votre expérience utilisateur en mémorisant vos préférences.
                        </p>
                        <div class="overflow-x-auto mb-6">
                            <table class="w-full text-sm border-collapse">
                                <thead>
                                    <tr class="bg-slate-100">
                                        <th class="border border-slate-200 px-4 py-2 text-left font-medium">Cookie</th>
                                        <th class="border border-slate-200 px-4 py-2 text-left font-medium">Finalité</th>
                                        <th class="border border-slate-200 px-4 py-2 text-left font-medium">Durée</th>
                                    </tr>
                                </thead>
                                <tbody class="text-slate-600">
                                    <tr>
                                        <td class="border border-slate-200 px-4 py-2 font-mono text-xs">locale</td>
                                        <td class="border border-slate-200 px-4 py-2">Mémorisation de la langue préférée</td>
                                        <td class="border border-slate-200 px-4 py-2">1 an</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-slate-200 px-4 py-2 font-mono text-xs">theme</td>
                                        <td class="border border-slate-200 px-4 py-2">Mémorisation du mode d'affichage (clair/sombre)</td>
                                        <td class="border border-slate-200 px-4 py-2">1 an</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <h4 class="font-medium text-green-800 mb-2">Pas de cookies de tracking</h4>
                            <p class="text-sm text-green-700">
                                faktur.lu n'utilise pas de cookies publicitaires ou de suivi comportemental. Nous ne vendons pas vos données à des tiers.
                            </p>
                        </div>
                    </section>

                    <!-- 3. Cookies tiers -->
                    <section id="cookies-tiers" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">3. Cookies tiers</h2>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">Stripe (paiements)</h3>
                        <p class="text-slate-600 mb-4">
                            Lorsque vous effectuez un paiement, Stripe peut déposer des cookies pour la sécurité des transactions et la prévention de la fraude.
                        </p>
                        <p class="text-slate-600 mb-4">
                            Pour plus d'informations : <a href="https://stripe.com/fr/privacy" target="_blank" rel="noopener" class="text-[#9b5de5]">Politique de confidentialité Stripe</a>
                        </p>
                    </section>

                    <!-- 4. Durée -->
                    <section id="duree" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">4. Durée de conservation</h2>
                        <p class="text-slate-600 mb-4">
                            Les cookies sont classés en deux catégories selon leur durée de vie :
                        </p>
                        <ul class="list-disc list-inside text-slate-600 space-y-2">
                            <li><strong>Cookies de session :</strong> supprimés automatiquement à la fermeture du navigateur</li>
                            <li><strong>Cookies persistants :</strong> conservés jusqu'à leur expiration ou suppression manuelle</li>
                        </ul>
                        <p class="text-slate-600 mt-4">
                            Les durées de conservation sont indiquées dans les tableaux ci-dessus pour chaque cookie.
                        </p>
                    </section>

                    <!-- 5. Gestion -->
                    <section id="gestion" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">5. Gestion des cookies</h2>
                        <p class="text-slate-600 mb-4">
                            Vous pouvez gérer les cookies via les paramètres de votre navigateur. Voici comment procéder pour les navigateurs les plus courants :
                        </p>

                        <div class="space-y-3">
                            <div class="bg-slate-50 rounded-lg p-4">
                                <h4 class="font-medium text-slate-800 mb-1">Google Chrome</h4>
                                <p class="text-sm text-slate-600">Paramètres > Confidentialité et sécurité > Cookies et autres données de site</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4">
                                <h4 class="font-medium text-slate-800 mb-1">Mozilla Firefox</h4>
                                <p class="text-sm text-slate-600">Paramètres > Vie privée et sécurité > Cookies et données de sites</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4">
                                <h4 class="font-medium text-slate-800 mb-1">Safari</h4>
                                <p class="text-sm text-slate-600">Préférences > Confidentialité > Cookies et données de sites web</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4">
                                <h4 class="font-medium text-slate-800 mb-1">Microsoft Edge</h4>
                                <p class="text-sm text-slate-600">Paramètres > Cookies et autorisations de site > Gérer et supprimer les cookies</p>
                            </div>
                        </div>

                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mt-6">
                            <h4 class="font-medium text-amber-800 mb-2">Attention</h4>
                            <p class="text-sm text-amber-700">
                                La désactivation des cookies essentiels peut empêcher le bon fonctionnement du site, notamment la connexion à votre compte.
                            </p>
                        </div>
                    </section>

                    <!-- 6. Contact -->
                    <section id="contact">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">6. Contact</h2>
                        <p class="text-slate-600 mb-4">
                            Pour toute question concernant notre politique de cookies :
                        </p>
                        <div class="bg-slate-50 rounded-lg p-4 text-sm">
                            <ul class="space-y-2 text-slate-700">
                                <li><strong>Email :</strong> <a href="mailto:contact@faktur.lu" class="text-[#9b5de5]">contact@faktur.lu</a></li>
                            </ul>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Other legal links -->
            <div class="mt-8 flex flex-wrap gap-4 justify-center text-sm">
                <Link :href="route('legal.mentions')" class="text-slate-600 hover:text-[#9b5de5]">Mentions légales</Link>
                <span class="text-slate-300">|</span>
                <Link :href="route('legal.privacy')" class="text-slate-600 hover:text-[#9b5de5]">Politique de confidentialité</Link>
                <span class="text-slate-300">|</span>
                <Link :href="route('legal.terms')" class="text-slate-600 hover:text-[#9b5de5]">Conditions générales</Link>
            </div>
        </div>
    </MarketingLayout>
</template>

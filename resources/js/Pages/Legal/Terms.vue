<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';

const { t } = useTranslations();

const lastUpdated = '9 février 2026';
</script>

<template>
    <Head title="Conditions générales d'utilisation et de vente" />

    <MarketingLayout>
        <!-- Content -->
        <div class="mx-auto max-w-4xl px-6 lg:px-8 py-12">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 lg:p-12">
                <h1 class="text-3xl font-bold text-slate-900 mb-2">Conditions Générales d'Utilisation et de Vente</h1>
                <p class="text-sm text-slate-500 mb-8">Dernière mise à jour : {{ lastUpdated }}</p>

                <!-- Table des matières -->
                <nav class="mb-10 p-4 bg-slate-50 rounded-xl">
                    <h2 class="font-semibold text-slate-900 mb-3">Sommaire</h2>
                    <ul class="space-y-2 text-sm grid md:grid-cols-2 gap-x-8">
                        <li><a href="#objet" class="text-[#9b5de5] hover:underline">1. Objet</a></li>
                        <li><a href="#definitions" class="text-[#9b5de5] hover:underline">2. Définitions</a></li>
                        <li><a href="#acces" class="text-[#9b5de5] hover:underline">3. Accès au service</a></li>
                        <li><a href="#description" class="text-[#9b5de5] hover:underline">4. Description du service</a></li>
                        <li><a href="#abonnements" class="text-[#9b5de5] hover:underline">5. Abonnements et tarifs</a></li>
                        <li><a href="#paiement" class="text-[#9b5de5] hover:underline">6. Paiement</a></li>
                        <li><a href="#retractation" class="text-[#9b5de5] hover:underline">7. Droit de rétractation</a></li>
                        <li><a href="#obligations-utilisateur" class="text-[#9b5de5] hover:underline">8. Obligations de l'utilisateur</a></li>
                        <li><a href="#obligations-faktur" class="text-[#9b5de5] hover:underline">9. Obligations de faktur.lu</a></li>
                        <li><a href="#donnees" class="text-[#9b5de5] hover:underline">10. Données et propriété</a></li>
                        <li><a href="#propriete-intellectuelle" class="text-[#9b5de5] hover:underline">11. Propriété intellectuelle</a></li>
                        <li><a href="#responsabilite" class="text-[#9b5de5] hover:underline">12. Responsabilité</a></li>
                        <li><a href="#resiliation" class="text-[#9b5de5] hover:underline">13. Résiliation</a></li>
                        <li><a href="#modifications" class="text-[#9b5de5] hover:underline">14. Modifications</a></li>
                        <li><a href="#droit-applicable" class="text-[#9b5de5] hover:underline">15. Droit applicable</a></li>
                        <li><a href="#contact" class="text-[#9b5de5] hover:underline">16. Contact</a></li>
                    </ul>
                </nav>

                <div class="prose prose-slate max-w-none">
                    <!-- 1. Objet -->
                    <section id="objet" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">1. Objet</h2>
                        <p class="text-slate-600 mb-4">
                            Les présentes Conditions Générales d'Utilisation et de Vente (ci-après "CGU/CGV") ont pour objet de définir les conditions dans lesquelles Alexandre Beaudier, exploitant le service faktur.lu (ci-après "le Prestataire"), fournit aux utilisateurs (ci-après "l'Utilisateur" ou "le Client") un service de facturation en ligne.
                        </p>
                        <p class="text-slate-600">
                            L'inscription et l'utilisation du service impliquent l'acceptation pleine et entière des présentes CGU/CGV.
                        </p>
                    </section>

                    <!-- 2. Définitions -->
                    <section id="definitions" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">2. Définitions</h2>
                        <ul class="list-disc list-inside text-slate-600 space-y-2">
                            <li><strong>Service :</strong> L'application web faktur.lu permettant la création et la gestion de factures, devis, avoirs et le suivi du temps.</li>
                            <li><strong>Utilisateur / Client :</strong> Toute personne physique ou morale ayant créé un compte sur faktur.lu.</li>
                            <li><strong>Compte :</strong> L'espace personnel de l'Utilisateur accessible via ses identifiants.</li>
                            <li><strong>Abonnement :</strong> Formule payante donnant accès à des fonctionnalités étendues du Service.</li>
                            <li><strong>Contenu :</strong> Les données saisies par l'Utilisateur (factures, clients, etc.).</li>
                        </ul>
                    </section>

                    <!-- 3. Accès au service -->
                    <section id="acces" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">3. Accès au service</h2>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">3.1 Inscription</h3>
                        <p class="text-slate-600 mb-4">
                            L'accès au Service nécessite la création d'un compte. L'Utilisateur doit fournir des informations exactes et complètes, notamment une adresse email valide.
                        </p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">3.2 Conditions d'éligibilité</h3>
                        <p class="text-slate-600 mb-4">
                            Le Service est destiné aux professionnels (entreprises, indépendants, professions libérales). L'Utilisateur déclare :
                        </p>
                        <ul class="list-disc list-inside text-slate-600 space-y-1 mb-4">
                            <li>Être majeur et juridiquement capable</li>
                            <li>Agir dans le cadre de son activité professionnelle</li>
                            <li>Disposer des autorisations nécessaires pour utiliser le Service</li>
                        </ul>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">3.3 Sécurité du compte</h3>
                        <p class="text-slate-600">
                            L'Utilisateur est responsable de la confidentialité de ses identifiants de connexion. Toute utilisation de son compte est réputée faite par lui. Il doit informer immédiatement faktur.lu de toute utilisation non autorisée.
                        </p>
                    </section>

                    <!-- 4. Description du service -->
                    <section id="description" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">4. Description du service</h2>
                        <p class="text-slate-600 mb-4">
                            faktur.lu est un logiciel de facturation en ligne (SaaS) conforme aux exigences luxembourgeoises et européennes, offrant notamment :
                        </p>
                        <ul class="list-disc list-inside text-slate-600 space-y-1">
                            <li>Création et gestion de factures, devis et avoirs</li>
                            <li>Gestion des clients et des contacts</li>
                            <li>Export FAIA conforme à l'AED Luxembourg</li>
                            <li>Suivi du temps et génération de factures</li>
                            <li>Envoi de factures par email</li>
                            <li>Tableau de bord et statistiques</li>
                            <li>Accès comptable sécurisé</li>
                        </ul>
                    </section>

                    <!-- 5. Abonnements et tarifs -->
                    <section id="abonnements" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">5. Abonnements et tarifs</h2>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">5.1 Formules d'abonnement</h3>
                        <p class="text-slate-600 mb-4">
                            Le Service propose plusieurs formules :
                        </p>
                        <div class="space-y-4 mb-6">
                            <div class="bg-slate-50 rounded-lg p-4">
                                <h4 class="font-medium text-slate-800">Découverte (Gratuit)</h4>
                                <p class="text-sm text-slate-600">Fonctionnalités limitées, idéal pour tester le service.</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4">
                                <h4 class="font-medium text-slate-800">Professionnel</h4>
                                <p class="text-sm text-slate-600">Fonctionnalités complètes pour les indépendants et PME. Tarif affiché sur la page de tarification.</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4">
                                <h4 class="font-medium text-slate-800">Entreprise</h4>
                                <p class="text-sm text-slate-600">Fonctionnalités avancées et support prioritaire. Sur devis.</p>
                            </div>
                        </div>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">5.2 Tarifs</h3>
                        <p class="text-slate-600 mb-4">
                            Les tarifs sont indiqués en euros hors taxes (HT) sur le site. La TVA applicable sera ajoutée au moment du paiement selon le taux en vigueur au Luxembourg (17%).
                        </p>
                        <p class="text-slate-600 mb-4">
                            Le Prestataire se réserve le droit de modifier ses tarifs à tout moment. Les modifications de tarifs seront notifiées par email au moins 30 jours avant leur entrée en vigueur et s'appliqueront au prochain renouvellement de l'abonnement.
                        </p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">5.3 Durée et renouvellement</h3>
                        <p class="text-slate-600 mb-4">
                            Les abonnements sont souscrits pour une durée mensuelle ou annuelle selon le choix de l'Utilisateur. Ils sont renouvelés automatiquement par tacite reconduction à l'issue de chaque période, sauf résiliation conformément à l'article 13.
                        </p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">5.4 Période d'essai</h3>
                        <p class="text-slate-600">
                            Une période d'essai gratuite peut être proposée. À l'issue de cette période, l'abonnement devient payant sauf résiliation. L'Utilisateur sera informé avant la fin de la période d'essai.
                        </p>
                    </section>

                    <!-- 6. Paiement -->
                    <section id="paiement" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">6. Paiement</h2>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">6.1 Moyens de paiement</h3>
                        <p class="text-slate-600 mb-4">
                            Le paiement s'effectue par carte bancaire (Visa, Mastercard, American Express) via la plateforme sécurisée Stripe. Les transactions sont chiffrées et conformes aux normes PCI-DSS.
                        </p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">6.2 Facturation</h3>
                        <p class="text-slate-600 mb-4">
                            Une facture électronique est émise et disponible dans l'espace client après chaque paiement. Elle peut également être envoyée par email sur demande.
                        </p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">6.3 Défaut de paiement</h3>
                        <p class="text-slate-600 mb-4">
                            En cas d'échec du paiement, l'Utilisateur sera notifié et disposera d'un délai de 7 jours pour régulariser. Passé ce délai, l'accès aux fonctionnalités payantes sera suspendu. Les données seront conservées pendant 30 jours supplémentaires avant suppression éventuelle.
                        </p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">6.4 TVA</h3>
                        <p class="text-slate-600">
                            Pour les clients professionnels établis dans un autre État membre de l'UE disposant d'un numéro de TVA intracommunautaire valide, la TVA ne sera pas facturée (autoliquidation). Le Client devra alors déclarer la TVA dans son pays.
                        </p>
                    </section>

                    <!-- 7. Droit de rétractation -->
                    <section id="retractation" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">7. Droit de rétractation</h2>

                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-4">
                            <h4 class="font-medium text-amber-800 mb-2">Information importante pour les consommateurs</h4>
                            <p class="text-sm text-amber-700">
                                Conformément à l'article L. 221-28 du Code de la consommation français et aux dispositions équivalentes du droit luxembourgeois, le droit de rétractation ne s'applique pas aux contrats de fourniture de contenu numérique non fourni sur un support matériel dont l'exécution a commencé avec l'accord préalable exprès du consommateur.
                            </p>
                        </div>

                        <p class="text-slate-600 mb-4">
                            En souscrivant à un abonnement payant, l'Utilisateur reconnaît que :
                        </p>
                        <ul class="list-disc list-inside text-slate-600 space-y-1 mb-4">
                            <li>Il demande expressément l'exécution immédiate du service</li>
                            <li>Il renonce expressément à son droit de rétractation de 14 jours dès que l'accès au service payant est activé</li>
                        </ul>

                        <p class="text-slate-600">
                            Toutefois, pour les abonnements annuels, un remboursement prorata temporis peut être accordé sur demande dans les 30 premiers jours suivant la souscription, sous réserve d'une utilisation raisonnable du service.
                        </p>
                    </section>

                    <!-- 8. Obligations de l'utilisateur -->
                    <section id="obligations-utilisateur" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">8. Obligations de l'utilisateur</h2>
                        <p class="text-slate-600 mb-4">L'Utilisateur s'engage à :</p>
                        <ul class="list-disc list-inside text-slate-600 space-y-2">
                            <li>Fournir des informations exactes lors de l'inscription et les maintenir à jour</li>
                            <li>Utiliser le Service conformément à sa destination (facturation professionnelle)</li>
                            <li>Ne pas utiliser le Service à des fins illicites ou contraires à l'ordre public</li>
                            <li>Conserver la confidentialité de ses identifiants de connexion</li>
                            <li>Respecter la législation fiscale et comptable applicable à son activité</li>
                            <li>Ne pas tenter de compromettre la sécurité ou le fonctionnement du Service</li>
                            <li>Ne pas revendre ou sous-licencier l'accès au Service</li>
                        </ul>
                    </section>

                    <!-- 9. Obligations de faktur.lu -->
                    <section id="obligations-faktur" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">9. Obligations de faktur.lu</h2>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">9.1 Disponibilité du service</h3>
                        <p class="text-slate-600 mb-4">
                            Le Prestataire s'engage à mettre en oeuvre les moyens nécessaires pour assurer la disponibilité du Service 24h/24 et 7j/7, avec un objectif de disponibilité de 99,5% (hors maintenance programmée).
                        </p>
                        <p class="text-slate-600 mb-4">
                            Les interruptions pour maintenance seront, dans la mesure du possible, programmées en dehors des heures ouvrables et annoncées préalablement.
                        </p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">9.2 Support</h3>
                        <p class="text-slate-600 mb-4">
                            Un support technique est disponible par email à <a href="mailto:contact@faktur.lu" class="text-[#9b5de5]">contact@faktur.lu</a>. Le délai de réponse varie selon la formule d'abonnement.
                        </p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">9.3 Sécurité et sauvegarde</h3>
                        <p class="text-slate-600">
                            Le Prestataire met en oeuvre des mesures de sécurité conformes aux standards de l'industrie et effectue des sauvegardes régulières des données.
                        </p>
                    </section>

                    <!-- 10. Données et propriété -->
                    <section id="donnees" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">10. Données et propriété</h2>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">10.1 Propriété des données</h3>
                        <p class="text-slate-600 mb-4">
                            L'Utilisateur reste propriétaire de toutes les données qu'il saisit dans le Service (factures, informations clients, etc.). Le Prestataire n'acquiert aucun droit sur ces données.
                        </p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">10.2 Licence d'utilisation</h3>
                        <p class="text-slate-600 mb-4">
                            L'Utilisateur accorde au Prestataire une licence limitée pour traiter ses données uniquement dans le cadre de la fourniture du Service.
                        </p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">10.3 Export des données</h3>
                        <p class="text-slate-600 mb-4">
                            L'Utilisateur peut à tout moment exporter ses données dans des formats standards (PDF, CSV, FAIA XML).
                        </p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">10.4 Protection des données</h3>
                        <p class="text-slate-600">
                            Le traitement des données personnelles est régi par notre
                            <Link :href="route('legal.privacy')" class="text-[#9b5de5] hover:underline">Politique de confidentialité</Link>.
                        </p>
                    </section>

                    <!-- 11. Propriété intellectuelle -->
                    <section id="propriete-intellectuelle" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">11. Propriété intellectuelle</h2>
                        <p class="text-slate-600 mb-4">
                            Le Service, incluant son code source, son interface, ses fonctionnalités, son design, ses marques et logos, est la propriété exclusive du Prestataire ou de ses partenaires.
                        </p>
                        <p class="text-slate-600">
                            L'abonnement confère uniquement un droit d'utilisation personnel, non exclusif et non transférable du Service pour la durée de l'abonnement.
                        </p>
                    </section>

                    <!-- 12. Responsabilité -->
                    <section id="responsabilite" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">12. Limitation de responsabilité</h2>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">12.1 Garanties</h3>
                        <p class="text-slate-600 mb-4">
                            Le Prestataire s'engage à fournir le Service avec diligence. Toutefois, le Service est fourni "en l'état" et le Prestataire ne garantit pas qu'il répondra à tous les besoins spécifiques de l'Utilisateur.
                        </p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">12.2 Limitation</h3>
                        <p class="text-slate-600 mb-4">
                            La responsabilité du Prestataire est limitée aux dommages directs et ne pourra excéder le montant des sommes versées par l'Utilisateur au cours des 12 derniers mois.
                        </p>
                        <p class="text-slate-600 mb-4">
                            Le Prestataire ne saurait être tenu responsable :
                        </p>
                        <ul class="list-disc list-inside text-slate-600 space-y-1">
                            <li>Des dommages indirects (perte de chiffre d'affaires, préjudice commercial, etc.)</li>
                            <li>De l'exactitude des données saisies par l'Utilisateur</li>
                            <li>De la conformité fiscale des factures émises par l'Utilisateur</li>
                            <li>Des interruptions dues à des tiers (hébergeur, FAI, etc.)</li>
                            <li>Des cas de force majeure</li>
                        </ul>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">12.3 Responsabilité de l'Utilisateur</h3>
                        <p class="text-slate-600">
                            L'Utilisateur est seul responsable de la conformité de ses factures avec la réglementation fiscale applicable. Le Service est un outil facilitant la facturation mais ne constitue pas un conseil fiscal ou juridique.
                        </p>
                    </section>

                    <!-- 13. Résiliation -->
                    <section id="resiliation" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">13. Résiliation</h2>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">13.1 Résiliation par l'Utilisateur</h3>
                        <p class="text-slate-600 mb-4">
                            L'Utilisateur peut résilier son abonnement à tout moment depuis son espace client. La résiliation prendra effet à la fin de la période en cours. L'Utilisateur conserve l'accès au Service jusqu'à cette date.
                        </p>
                        <p class="text-slate-600 mb-4">
                            Les sommes déjà versées ne sont pas remboursables, sauf disposition contraire prévue à l'article 7.
                        </p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">13.2 Résiliation par le Prestataire</h3>
                        <p class="text-slate-600 mb-4">
                            Le Prestataire peut résilier l'abonnement en cas de :
                        </p>
                        <ul class="list-disc list-inside text-slate-600 space-y-1 mb-4">
                            <li>Violation des présentes CGU</li>
                            <li>Défaut de paiement non régularisé</li>
                            <li>Utilisation frauduleuse ou abusive du Service</li>
                            <li>Cessation d'activité du Service (avec préavis de 3 mois)</li>
                        </ul>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">13.3 Conséquences de la résiliation</h3>
                        <p class="text-slate-600">
                            À la résiliation, l'Utilisateur dispose de 30 jours pour exporter ses données. Passé ce délai, les données pourront être supprimées, sous réserve des obligations légales de conservation.
                        </p>
                    </section>

                    <!-- 14. Modifications -->
                    <section id="modifications" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">14. Modifications des CGU</h2>
                        <p class="text-slate-600 mb-4">
                            Le Prestataire se réserve le droit de modifier les présentes CGU. Les modifications seront notifiées par email et/ou par affichage sur le Service au moins 30 jours avant leur entrée en vigueur.
                        </p>
                        <p class="text-slate-600">
                            L'utilisation continue du Service après l'entrée en vigueur des modifications vaut acceptation des nouvelles CGU. En cas de désaccord, l'Utilisateur peut résilier son abonnement.
                        </p>
                    </section>

                    <!-- 15. Droit applicable -->
                    <section id="droit-applicable" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">15. Droit applicable et juridiction</h2>
                        <p class="text-slate-600 mb-4">
                            Les présentes CGU sont régies par le droit luxembourgeois.
                        </p>
                        <p class="text-slate-600 mb-4">
                            En cas de litige, les parties s'efforceront de trouver une solution amiable. À défaut, les tribunaux de Luxembourg seront seuls compétents.
                        </p>
                        <p class="text-slate-600">
                            <strong>Médiation :</strong> Conformément à la réglementation européenne, en cas de litige non résolu, le consommateur peut recourir gratuitement au service de médiation. Plateforme de règlement en ligne des litiges : <a href="https://ec.europa.eu/consumers/odr" target="_blank" rel="noopener" class="text-[#9b5de5]">https://ec.europa.eu/consumers/odr</a>
                        </p>
                    </section>

                    <!-- 16. Contact -->
                    <section id="contact">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">16. Contact</h2>
                        <p class="text-slate-600 mb-4">
                            Pour toute question concernant ces CGU :
                        </p>
                        <div class="bg-slate-50 rounded-lg p-4 text-sm">
                            <ul class="space-y-2 text-slate-700">
                                <li><strong>Email :</strong> <a href="mailto:contact@faktur.lu" class="text-[#9b5de5]">contact@faktur.lu</a></li>
                                <li><strong>Adresse :</strong> Alexandre Beaudier, 13 Rue du Stade John F. Kennedy, L-3502 Dudelange, Luxembourg</li>
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
                <Link :href="route('legal.cookies')" class="text-slate-600 hover:text-[#9b5de5]">Politique cookies</Link>
            </div>
        </div>
    </MarketingLayout>
</template>

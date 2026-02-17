<script setup>
import { Link } from '@inertiajs/vue3';
import { useTranslations } from '@/Composables/useTranslations';
import { useLocalizedRoute } from '@/Composables/useLocalizedRoute';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import SeoHead from '@/Components/SeoHead.vue';

const { t } = useTranslations();
const { localizedRoute } = useLocalizedRoute();

const lastUpdated = '9 février 2026';
</script>

<template>
    <SeoHead
        title="Politique de confidentialité | faktur.lu"
        description="Politique de confidentialité de faktur.lu. Comment nous collectons, utilisons et protégeons vos données personnelles conformément au RGPD."
        canonical-path="/confidentialite"
    />

    <MarketingLayout>
        <!-- Content -->
        <div class="mx-auto max-w-4xl px-6 lg:px-8 py-12">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 lg:p-12">
                <h1 class="text-3xl font-bold text-slate-900 mb-2">Politique de confidentialité</h1>
                <p class="text-sm text-slate-500 mb-8">Dernière mise à jour : {{ lastUpdated }}</p>

                <!-- Table des matières -->
                <nav class="mb-10 p-4 bg-slate-50 rounded-xl">
                    <h2 class="font-semibold text-slate-900 mb-3">Sommaire</h2>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#introduction" class="text-[#9b5de5] hover:underline">1. Introduction</a></li>
                        <li><a href="#responsable" class="text-[#9b5de5] hover:underline">2. Responsable du traitement</a></li>
                        <li><a href="#donnees-collectees" class="text-[#9b5de5] hover:underline">3. Données collectées</a></li>
                        <li><a href="#finalites" class="text-[#9b5de5] hover:underline">4. Finalités et bases légales</a></li>
                        <li><a href="#conservation" class="text-[#9b5de5] hover:underline">5. Durée de conservation</a></li>
                        <li><a href="#destinataires" class="text-[#9b5de5] hover:underline">6. Destinataires des données</a></li>
                        <li><a href="#transferts" class="text-[#9b5de5] hover:underline">7. Transferts hors UE</a></li>
                        <li><a href="#droits" class="text-[#9b5de5] hover:underline">8. Vos droits</a></li>
                        <li><a href="#securite" class="text-[#9b5de5] hover:underline">9. Sécurité</a></li>
                        <li><a href="#modifications" class="text-[#9b5de5] hover:underline">10. Modifications</a></li>
                        <li><a href="#contact" class="text-[#9b5de5] hover:underline">11. Contact</a></li>
                    </ul>
                </nav>

                <div class="prose prose-slate max-w-none">
                    <!-- 1. Introduction -->
                    <section id="introduction" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">1. Introduction</h2>
                        <p class="text-slate-600 mb-4">
                            La présente politique de confidentialité décrit comment faktur.lu collecte, utilise et protège vos données personnelles conformément au Règlement (UE) 2016/679 du Parlement européen et du Conseil du 27 avril 2016 (RGPD) et à la loi luxembourgeoise du 1er août 2018 portant organisation de la Commission nationale pour la protection des données.
                        </p>
                        <p class="text-slate-600">
                            En utilisant notre service, vous acceptez les pratiques décrites dans cette politique.
                        </p>
                    </section>

                    <!-- 2. Responsable du traitement -->
                    <section id="responsable" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">2. Responsable du traitement</h2>
                        <div class="bg-slate-50 rounded-lg p-4 text-sm">
                            <ul class="space-y-2 text-slate-700">
                                <li><strong>Responsable :</strong> Alexandre Beaudier</li>
                                <li><strong>Adresse :</strong> 13, Rue du Stade John F. Kennedy, L-3502 Dudelange, Luxembourg</li>
                                <li><strong>Email :</strong> <a href="mailto:contact@faktur.lu" class="text-[#9b5de5]">contact@faktur.lu</a></li>
                                <li><strong>Numéro RCS :</strong> A46648</li>
                            </ul>
                        </div>
                    </section>

                    <!-- 3. Données collectées -->
                    <section id="donnees-collectees" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">3. Données collectées</h2>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">3.1 Données de compte</h3>
                        <ul class="list-disc list-inside text-slate-600 space-y-1 mb-4">
                            <li>Nom et prénom</li>
                            <li>Adresse email</li>
                            <li>Mot de passe (chiffré)</li>
                            <li>Informations d'entreprise (raison sociale, adresse, TVA, RCS)</li>
                        </ul>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">3.2 Données de facturation</h3>
                        <ul class="list-disc list-inside text-slate-600 space-y-1 mb-4">
                            <li>Informations sur vos clients (nom, adresse, TVA)</li>
                            <li>Factures, devis et avoirs créés</li>
                            <li>Historique des transactions</li>
                        </ul>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">3.3 Données de paiement</h3>
                        <p class="text-slate-600 mb-4">
                            Les paiements sont traités par notre partenaire <strong>Stripe</strong>. Nous ne stockons pas directement vos données de carte bancaire. Stripe agit en tant que sous-traitant certifié PCI-DSS.
                        </p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">3.4 Données techniques</h3>
                        <ul class="list-disc list-inside text-slate-600 space-y-1">
                            <li>Adresse IP</li>
                            <li>Type de navigateur et système d'exploitation</li>
                            <li>Pages visitées et actions effectuées</li>
                            <li>Horodatage des connexions</li>
                        </ul>
                    </section>

                    <!-- 4. Finalités et bases légales -->
                    <section id="finalites" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">4. Finalités et bases légales du traitement</h2>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm border-collapse">
                                <thead>
                                    <tr class="bg-slate-100">
                                        <th class="border border-slate-200 px-4 py-2 text-left font-medium">Finalité</th>
                                        <th class="border border-slate-200 px-4 py-2 text-left font-medium">Base légale</th>
                                    </tr>
                                </thead>
                                <tbody class="text-slate-600">
                                    <tr>
                                        <td class="border border-slate-200 px-4 py-2">Fourniture du service de facturation</td>
                                        <td class="border border-slate-200 px-4 py-2">Exécution du contrat (Art. 6.1.b RGPD)</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-slate-200 px-4 py-2">Gestion de votre compte utilisateur</td>
                                        <td class="border border-slate-200 px-4 py-2">Exécution du contrat (Art. 6.1.b RGPD)</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-slate-200 px-4 py-2">Facturation et paiement de l'abonnement</td>
                                        <td class="border border-slate-200 px-4 py-2">Exécution du contrat (Art. 6.1.b RGPD)</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-slate-200 px-4 py-2">Support client</td>
                                        <td class="border border-slate-200 px-4 py-2">Exécution du contrat (Art. 6.1.b RGPD)</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-slate-200 px-4 py-2">Obligations comptables et fiscales</td>
                                        <td class="border border-slate-200 px-4 py-2">Obligation légale (Art. 6.1.c RGPD)</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-slate-200 px-4 py-2">Sécurité et prévention des fraudes</td>
                                        <td class="border border-slate-200 px-4 py-2">Intérêt légitime (Art. 6.1.f RGPD)</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-slate-200 px-4 py-2">Amélioration du service</td>
                                        <td class="border border-slate-200 px-4 py-2">Intérêt légitime (Art. 6.1.f RGPD)</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-slate-200 px-4 py-2">Communications marketing (si consentement)</td>
                                        <td class="border border-slate-200 px-4 py-2">Consentement (Art. 6.1.a RGPD)</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <!-- 5. Durée de conservation -->
                    <section id="conservation" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">5. Durée de conservation</h2>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm border-collapse">
                                <thead>
                                    <tr class="bg-slate-100">
                                        <th class="border border-slate-200 px-4 py-2 text-left font-medium">Type de données</th>
                                        <th class="border border-slate-200 px-4 py-2 text-left font-medium">Durée</th>
                                    </tr>
                                </thead>
                                <tbody class="text-slate-600">
                                    <tr>
                                        <td class="border border-slate-200 px-4 py-2">Données de compte actif</td>
                                        <td class="border border-slate-200 px-4 py-2">Durée de la relation contractuelle</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-slate-200 px-4 py-2">Données après suppression du compte</td>
                                        <td class="border border-slate-200 px-4 py-2">30 jours (période de grâce)</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-slate-200 px-4 py-2">Factures et documents comptables</td>
                                        <td class="border border-slate-200 px-4 py-2">10 ans (obligation légale luxembourgeoise)</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-slate-200 px-4 py-2">Logs de sécurité</td>
                                        <td class="border border-slate-200 px-4 py-2">12 mois</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-slate-200 px-4 py-2">Données de paiement (chez Stripe)</td>
                                        <td class="border border-slate-200 px-4 py-2">Selon politique Stripe</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <!-- 6. Destinataires -->
                    <section id="destinataires" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">6. Destinataires des données</h2>
                        <p class="text-slate-600 mb-4">
                            Vos données peuvent être partagées avec les destinataires suivants :
                        </p>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">Sous-traitants</h3>
                        <ul class="list-disc list-inside text-slate-600 space-y-2">
                            <li><strong>Stripe</strong> (Irlande) - Traitement des paiements</li>
                            <li><strong>Brevo</strong> (France) - Envoi d'emails transactionnels</li>
                            <li><strong>o2switch</strong> (France) - Hébergement web</li>
                        </ul>

                        <h3 class="text-lg font-medium text-slate-800 mt-6 mb-3">Autres destinataires</h3>
                        <ul class="list-disc list-inside text-slate-600 space-y-2">
                            <li>Votre comptable (si vous utilisez la fonctionnalité d'accès comptable)</li>
                            <li>Autorités compétentes (sur réquisition légale)</li>
                        </ul>
                    </section>

                    <!-- 7. Transferts hors UE -->
                    <section id="transferts" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">7. Transferts hors Union Européenne</h2>
                        <p class="text-slate-600 mb-4">
                            Nos sous-traitants principaux sont situés dans l'Union Européenne. Dans le cas où des données seraient transférées en dehors de l'UE, nous nous assurons que des garanties appropriées sont en place :
                        </p>
                        <ul class="list-disc list-inside text-slate-600 space-y-1">
                            <li>Clauses Contractuelles Types de la Commission Européenne</li>
                            <li>Décision d'adéquation de la Commission Européenne</li>
                            <li>Certifications appropriées (ex: Privacy Shield successor frameworks)</li>
                        </ul>
                    </section>

                    <!-- 8. Vos droits -->
                    <section id="droits" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">8. Vos droits</h2>
                        <p class="text-slate-600 mb-4">
                            Conformément au RGPD, vous disposez des droits suivants :
                        </p>

                        <div class="space-y-4">
                            <div class="bg-slate-50 rounded-lg p-4">
                                <h4 class="font-medium text-slate-800 mb-2">Droit d'accès (Art. 15)</h4>
                                <p class="text-sm text-slate-600">Obtenir une copie de vos données personnelles.</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4">
                                <h4 class="font-medium text-slate-800 mb-2">Droit de rectification (Art. 16)</h4>
                                <p class="text-sm text-slate-600">Corriger vos données inexactes ou incomplètes.</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4">
                                <h4 class="font-medium text-slate-800 mb-2">Droit à l'effacement (Art. 17)</h4>
                                <p class="text-sm text-slate-600">Demander la suppression de vos données (sous réserve des obligations légales de conservation).</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4">
                                <h4 class="font-medium text-slate-800 mb-2">Droit à la limitation (Art. 18)</h4>
                                <p class="text-sm text-slate-600">Limiter le traitement de vos données dans certains cas.</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4">
                                <h4 class="font-medium text-slate-800 mb-2">Droit à la portabilité (Art. 20)</h4>
                                <p class="text-sm text-slate-600">Recevoir vos données dans un format structuré (export FAIA, CSV).</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4">
                                <h4 class="font-medium text-slate-800 mb-2">Droit d'opposition (Art. 21)</h4>
                                <p class="text-sm text-slate-600">Vous opposer au traitement basé sur l'intérêt légitime.</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-4">
                                <h4 class="font-medium text-slate-800 mb-2">Droit de retirer le consentement</h4>
                                <p class="text-sm text-slate-600">Retirer votre consentement à tout moment (sans affecter la licéité du traitement antérieur).</p>
                            </div>
                        </div>

                        <p class="text-slate-600 mt-6">
                            Pour exercer vos droits, contactez-nous à <a href="mailto:contact@faktur.lu" class="text-[#9b5de5]">contact@faktur.lu</a>. Nous répondrons dans un délai d'un mois.
                        </p>

                        <p class="text-slate-600 mt-4">
                            <strong>Réclamation :</strong> Vous pouvez introduire une réclamation auprès de la Commission Nationale pour la Protection des Données (CNPD) :
                            <a href="https://cnpd.public.lu" target="_blank" rel="noopener" class="text-[#9b5de5]">https://cnpd.public.lu</a>
                        </p>
                    </section>

                    <!-- 9. Sécurité -->
                    <section id="securite" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">9. Sécurité des données</h2>
                        <p class="text-slate-600 mb-4">
                            Nous mettons en oeuvre des mesures techniques et organisationnelles appropriées pour protéger vos données :
                        </p>
                        <ul class="list-disc list-inside text-slate-600 space-y-1">
                            <li>Chiffrement des données en transit (TLS/SSL)</li>
                            <li>Chiffrement des mots de passe (bcrypt)</li>
                            <li>Authentification à deux facteurs disponible</li>
                            <li>Journalisation des accès et actions sensibles</li>
                            <li>Sauvegardes régulières et chiffrées</li>
                            <li>Contrôle d'accès strict aux données</li>
                            <li>Hébergement sécurisé en France (UE)</li>
                        </ul>
                    </section>

                    <!-- 10. Modifications -->
                    <section id="modifications" class="mb-10">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">10. Modifications de cette politique</h2>
                        <p class="text-slate-600">
                            Nous pouvons modifier cette politique de confidentialité. En cas de modification substantielle, nous vous en informerons par email ou via une notification sur le service. La date de dernière mise à jour figure en haut de ce document.
                        </p>
                    </section>

                    <!-- 11. Contact -->
                    <section id="contact">
                        <h2 class="text-xl font-semibold text-slate-900 mb-4">11. Contact</h2>
                        <p class="text-slate-600 mb-4">
                            Pour toute question relative à cette politique ou à vos données personnelles :
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
                <Link :href="localizedRoute('legal.mentions')" class="text-slate-600 hover:text-[#9b5de5]">Mentions légales</Link>
                <span class="text-slate-300">|</span>
                <Link :href="localizedRoute('legal.terms')" class="text-slate-600 hover:text-[#9b5de5]">Conditions générales</Link>
                <span class="text-slate-300">|</span>
                <Link :href="localizedRoute('legal.cookies')" class="text-slate-600 hover:text-[#9b5de5]">Politique cookies</Link>
            </div>
        </div>
    </MarketingLayout>
</template>

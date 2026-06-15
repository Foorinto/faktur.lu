# Prompt — Brainstorm démarchage des fiduciaires & experts-comptables (faktur.lu)

> À copier-coller tel quel dans une IA tierce (ChatGPT, Gemini, etc.) pour réfléchir ensemble à la stratégie de prospection des fiduciaires luxembourgeoises.

---

## 🎯 Ton rôle

Tu es un **stratège growth / acquisition B2B spécialisé dans la vente de logiciels SaaS aux cabinets comptables**. Je suis fondateur solo d'un SaaS de facturation luxembourgeois et je veux **démarcher au mieux les fiduciaires et experts-comptables du Luxembourg**. Aide-moi à bâtir une stratégie de prospection complète, concrète et actionnable, adaptée à mes moyens (fondateur solo, petit budget, temps limité). Sois précis et opérationnel : pas de généralités, des plans d'action, des séquences, des scripts, des arguments chiffrés et des priorités.

À la fin de ce message, je te poserai des questions précises. D'abord, lis attentivement tout le contexte ci-dessous.

---

## 1. Qui je suis et ce que je vends

- **Produit** : `faktur.lu`, un SaaS de **facturation et comptabilité légère conçu spécifiquement pour le Luxembourg** (freelances, indépendants, TPE/PME).
- **Fondateur** : moi, seul, frontalier. Je développe, je vends, je supporte.
- **Stack & maturité** : application en production, déjà des clients payants, multilingue (FR/DE/EN/LU/PT), conforme à la réglementation luxembourgeoise.
- **Tarifs** :
  - Gratuit (0 €) : 10 clients, 5 factures/mois
  - Essentiel (5 €/mois) : 100 clients, 50 factures/mois, suivi du temps, projets, exports comptables, relances email
  - Pro (15 €/mois) : tout illimité + RH + CRM + Peppol + archivage PDF/A + Factur-X + organisation + reporting avancé + sans branding
- **Essai gratuit** : 14 jours.

## 2. Pourquoi les fiduciaires sont une cible STRATÉGIQUE (le cœur du sujet)

Les fiduciaires ne sont pas qu'un client potentiel : ce sont des **prescripteurs**. Un cabinet gère des dizaines/centaines de freelances et PME. Si je convaincs **une** fiduciaire, elle peut **recommander faktur.lu à tout son portefeuille de clients**. C'est un canal de croissance à effet de levier.

**Deux angles de valeur pour la fiduciaire :**

1. **Un portail comptable GRATUIT et dédié.** La fiduciaire dispose d'un accès séparé (`/comptable/login`) où elle voit, **en lecture seule**, les données de tous ses clients qui utilisent faktur.lu :
   - Toutes les factures émises (PDF téléchargeables)
   - Livre des recettes, résumé fiscal (TVA collectée vs déductible)
   - **Exports comptables directs** : Sage BOB 50, Sage 100, CSV générique
   - **Export FAIA** conforme au schéma 2.01 de l'AED (Administration de l'Enregistrement, des Domaines et de la TVA)
   - **Mass export** : récupérer les données de TOUS ses clients en un seul export
   - Rapport consolidé multi-clients
   - Historique des téléchargements
   - **Ce portail est gratuit pour le comptable** (accessible même quand le client est sur le plan Gratuit).

2. **Des données propres et conformes en amont.** Quand les clients de la fiduciaire facturent via faktur.lu, la fiduciaire reçoit des données déjà structurées et légalement conformes :
   - Numérotation séquentielle sans trous (article 63 LIVA), factures **immuables** une fois finalisées
   - Mentions TVA automatiques selon le scénario (régime normal LU 17 %, franchise art. 57bis, autoliquidation B2B intra-UE, livraison intra-UE exonérée, export hors UE)
   - Validation VIES des numéros TVA intra-UE
   - Archivage **PDF/A** avec checksum (conservation légale 10 ans)
   - Factur-X / ZUGFeRD + Peppol BIS Billing 3.0 (facturation électronique B2G)

**En résumé, l'argument massue à la fiduciaire** : « Fini les fichiers Excel bricolés et la chasse aux justificatifs. Vos clients facturent proprement, vous récupérez du FAIA / Sage / CSV en un clic, gratuitement, et vous gagnez des heures de ressaisie à chaque clôture. »

## 3. Panorama complet des fonctionnalités du produit

Pour que tu mesures la profondeur de l'offre (utile pour calibrer les arguments) :

- **Facturation** : factures (brouillon→finalisée→envoyée→payée/annulée), avoirs/notes de crédit, factures récurrentes, devis avec conversion en facture, retenue de garantie BTP, aperçu PDF multilingue, numérotation configurable.
- **Clients & prospects** : CRUD complet, import CSV en masse, lookup entreprise (LBR pour LU, data.gouv pour FR), validation VIES, scénario TVA auto, statut prospect/actif.
- **CRM (Pro)** : interactions (notes/appels/emails/réunions), rappels & tâches avec notifications email, tags clients.
- **Dépenses** : saisie, justificatifs uploadés, déductibilité, catégorisation, intégration aux exports comptables.
- **Suivi du temps & projets (Essentiel/Pro)** : chronomètre live, conversion temps→facture, projets avec tâches kanban, invitation de collaborateurs externes.
- **Module RH complet (Pro)** : employés, congés & soldes, notes de frais, évaluations, onboarding, événements RH, trombinoscope, départements, calendrier partagé + **portail self-service employé**.
- **Comptabilité & exports** : livre des recettes, résumé fiscal, export FAIA (validé XSD), exports Sage BOB 50 / Sage 100 / CSV.
- **Portail comptable / fiduciaire** (cf. section 2).
- **Conformité Luxembourg** native (cf. section 2).
- **Multilingue** : 5 langues (FR/DE/EN/LU/PT), PDF facture dans la langue du client.
- **Sécurité** : 2FA TOTP, vérification email, audit log, multi-tenant isolé, headers de sécurité, rate limiting.
- **Écosystème d'acquisition** :
  - **Blog SEO** : 145 articles (29 × 5 langues) audités factuellement contre les sources officielles luxembourgeoises.
  - **Glossaire métier** (50+ termes).
  - **6 outils gratuits publics** : calculateur TVA LU, simulateur de franchise TVA (seuil 50 000 €), validateur IBAN, générateur de facture express sans inscription, modèles de facture téléchargeables, **validateur FAIA** (upload d'un fichier FAIA → vérification contre le schéma officiel).
  - **Page « Partenaires »** publique ciblant les comptables/fiduciaires.

## 4. La cible précise

- **Qui** : fiduciaires, cabinets d'expertise comptable, experts-comptables indépendants au **Grand-Duché de Luxembourg**.
- **Leur quotidien** : ils gèrent la comptabilité, la TVA et les déclarations de portefeuilles de freelances et PME. Beaucoup reçoivent encore des factures en PDF désordonnés, des Excel, voire du papier. La ressaisie et la mise en conformité leur coûtent du temps.
- **Contexte réglementaire** : Luxembourg, obligations TVA (AED), format FAIA, montée en puissance de la facturation électronique (Peppol B2G déjà obligatoire, B2B à venir).
- **Langues** : FR dominant dans le milieu comptable, mais DE et EN présents (clientèle internationale).

## 5. Ce que j'ai DÉJÀ comme actifs de démarchage (leviers à exploiter)

1. **Un environnement de démonstration privé et fonctionnel** : `https://staging.faktur.lu` — une instance complète remplie de fausses données réalistes (clients, factures, devis, dépenses, projets, employés). Je peux donner à une fiduciaire un accès direct pour qu'elle teste **le portail comptable** sans risque (aucun email réel n'est envoyé, données factices). Comptes de démo prêts : un compte « propriétaire » (vue entrepreneur) et un compte « comptable » (`/comptable/login`). C'est un outil de démonstration live puissant.
2. **Des templates d'emails de prospection** (FR/DE/EN) déjà rédigés, en HTML, avec une version de relance, prêts pour un envoi via Brevo.
3. **Un message d'approche LinkedIn** déjà rédigé.
4. **Un formulaire d'audit / découverte fiduciaire** (questionnaire de qualification des besoins) en markdown.
5. **Une première liste de ~14 fiduciaires luxembourgeoises** (nom, contact) extraite et prête à importer dans Brevo. (Je veux l'élargir.)
6. **Brevo** comme outil d'emailing (transactionnel + listes).
7. **Tout l'écosystème SEO** (blog, glossaire, outils gratuits) qui peut servir d'aimant à prescripteurs.

## 6. Mes contraintes

- **Fondateur solo** : temps très limité, je fais tout moi-même.
- **Budget marketing faible** : privilégier l'organique, le ciblé, le manuel à fort taux de conversion plutôt que le payant à grande échelle.
- **Pas d'équipe commerciale** : les séquences doivent être tenables par une seule personne.
- **Cible de niche** (Luxembourg) : le volume total de fiduciaires est limité, donc chaque contact compte — c'est de la prospection chirurgicale, pas du volume.

---

## 7. Ce que j'attends de toi (le brainstorm)

Aide-moi à construire une **stratégie de démarchage des fiduciaires de A à Z**. En particulier :

1. **Segmentation & priorisation** : comment classer les fiduciaires à cibler en premier (taille, type de clientèle, signaux d'intention) ?
2. **Proposition de valeur** : quelle est l'accroche la plus percutante pour une fiduciaire ? Comment formuler le bénéfice « gain de temps / données conformes / canal gratuit » ? Faut-il vendre le portail gratuit comme produit d'appel ?
3. **Modèle de prescription / partenariat** : devrais-je créer un programme de prescription (commission, % de parrainage, statut « partenaire certifié », co-branding) ? Lequel et comment le structurer pour un solo founder ?
4. **Canaux & séquence** : quel mix entre email à froid, LinkedIn, appels, événements, partenariats institutionnels (ordre des experts-comptables, chambres) ? Donne-moi une séquence multi-touch concrète (J0, J+3, J+7…) tenable en solo.
5. **Usage de la démo** : comment intégrer au mieux l'environnement de démonstration live dans le cycle de vente (à quel moment l'offrir, scénario de démo guidée, accès en self-service) ?
6. **Scripts & messages** : améliore/réécris mes accroches email et LinkedIn, propose un script d'appel et un déroulé de rendez-vous de démo.
7. **Objections** : liste les objections typiques d'une fiduciaire (« mes clients utilisent déjà X », « je n'ai pas le temps de changer mes process », « et la sécurité des données ? ») et donne-moi des réponses solides.
8. **Mesure** : quels KPIs suivre, quel taux de conversion viser à chaque étape, comment itérer ?
9. **Quick wins** : 3 actions à fort impact que je peux lancer dès cette semaine.

Commence par me poser **les questions qui te manquent** pour affiner, puis propose un **plan structuré et priorisé**. Sois concret et chiffré quand c'est possible.

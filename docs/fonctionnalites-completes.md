# Inventaire complet des fonctionnalités — faktur.lu

> Document de référence : toutes les fonctionnalités du SaaS **faktur.lu**, qu'elles soient **visibles dans l'interface utilisateur** ou **invisibles côté back-end** (emails automatiques, tâches programmées, jobs, intégrations).
>
> Mis à jour le 10 juin 2026.

## Sommaire

1. [Facturation & documents](#1-facturation--documents)
2. [Devis & cotations](#2-devis--cotations)
3. [Factures récurrentes](#3-factures-récurrentes)
4. [Gestion clientèle & prospects](#4-gestion-clientèle--prospects)
5. [CRM & relation client](#5-crm--relation-client)
6. [Gestion des dépenses](#6-gestion-des-dépenses)
7. [Suivi du temps & projets](#7-suivi-du-temps--projets)
8. [Tâches & collaboration projet](#8-tâches--collaboration-projet)
9. [Portail collaborateur externe](#9-portail-collaborateur-externe)
10. [Module RH (employés)](#10-module-rh-employés)
11. [Portail employé self-service](#11-portail-employé-self-service)
12. [Comptabilité & exports](#12-comptabilité--exports)
13. [Portail comptable / fiduciaire](#13-portail-comptable--fiduciaire)
14. [Organisation & équipes](#14-organisation--équipes)
15. [Conformité Luxembourg](#15-conformité-luxembourg)
16. [Multi-langue & localisation](#16-multi-langue--localisation)
17. [Plans, paiements & gating](#17-plans-paiements--gating)
18. [Sécurité & authentification](#18-sécurité--authentification)
19. [Marketing public & SEO](#19-marketing-public--seo)
20. [Blog multilingue](#20-blog-multilingue)
21. [Outils gratuits publics](#21-outils-gratuits-publics)
22. [Onboarding utilisateur](#22-onboarding-utilisateur)
23. [Support utilisateur](#23-support-utilisateur)
24. [Sondages de satisfaction (NPS)](#24-sondages-de-satisfaction-nps)
25. [Newsletter](#25-newsletter)
26. [Recherche globale](#26-recherche-globale)
27. [Reporting & tableaux de bord](#27-reporting--tableaux-de-bord)
28. [Réglages & configuration](#28-réglages--configuration)
29. [Administration (back-office)](#29-administration-back-office)
30. [Monitoring & opérations](#30-monitoring--opérations)
31. [Emails — liste centralisée](#31-emails--liste-centralisée)
32. [Tâches programmées (cron / scheduler)](#32-tâches-programmées-cron--scheduler)
33. [Intégrations externes](#33-intégrations-externes)
34. [Multi-tenancy & isolation des données](#34-multi-tenancy--isolation-des-données)
35. [Audit log & immutabilité](#35-audit-log--immutabilité)

---

## 1. Facturation & documents

### Côté utilisateur (visible)

- Création, édition, duplication, suppression de factures (brouillon)
- Cycle de vie complet : **brouillon → finalisée → envoyée → payée / annulée**
- Une fois finalisée, la facture devient **immuable** (anti-modification légale)
- Création d'avoirs (notes de crédit) depuis n'importe quelle facture payée
- Gestion fine des lignes : ajout, édition, suppression, déplacement par drag-and-drop
- Numérotation séquentielle automatique configurable (préfixe, format, padding, séparateur)
- Mention TVA automatique selon le scénario client (LU, intra-UE B2B, export hors UE, franchise)
- Pied de page personnalisable par défaut + override par facture
- Champ « titre » optionnel sur la facture (description courte)
- Retenue de garantie BTP (taux + date de libération)
- Aperçu PDF en temps réel pendant l'édition (langue de PDF sélectionnable)
- Téléchargement PDF + envoi par email
- Marquage manuel : facture envoyée, facture payée (avec date de paiement éditable)
- Exclusion d'une facture des relances automatiques
- Génération d'une note de crédit en un clic
- Recherche globale par numéro, client, date, montant

### Côté back-end (invisible)

- Calcul automatique HT/TVA/TTC multi-taux (17 % / 14 % / 8 % / 3 % / 0 %)
- Recalcul automatique des totaux à chaque modification de ligne (saving hook + `CalculateInvoiceTotalsAction`)
- Verrouillage des champs critiques sur facture finalisée (`ImmutableInvoiceException` levée si modification interdite)
- Snapshot vendeur + acheteur figé à la finalisation (résilient aux changements ultérieurs du client)
- Auto-attribution du `user_id` via trait `BelongsToUser` (multi-tenant)
- Génération PDF via `InvoicePdfService` (DomPDF)
- Génération PDF/A archivable avec checksum SHA-256 (plan Pro)
- Génération **Factur-X / ZUGFeRD** (PDF/A-3 + XML EN 16931 embarqué) — plan Pro
- Génération XML **Peppol BIS Billing 3.0** pour transmission B2G
- Job `SendPeppolInvoiceJob` (async) pour transmission via Access Point
- Trait `Auditable` : trace toute modification dans `audit_logs`
- Soft delete (table `invoices.deleted_at`)
- Hook de suppression : impossible de supprimer une facture finalisée

---

## 2. Devis & cotations

### Côté utilisateur

- CRUD devis (création, édition, suppression d'un brouillon)
- Lignes éditables individuellement (mode édition inline par ligne)
- Statuts : brouillon, envoyé, accepté, refusé, expiré, converti
- Marquage manuel : envoyé, accepté, refusé
- Duplication de devis
- **Conversion en facture** : crée automatiquement une facture liée avec les mêmes lignes
- Date de validité personnalisable (défaut J+30)
- Téléchargement PDF + envoi par email
- Aperçu PDF avec sélection de langue

### Côté back-end

- Numérotation séquentielle indépendante des factures (préfixe `QT-` par défaut)
- Génération de la référence via `GenerateQuoteNumberAction`
- `ConvertQuoteToInvoiceAction` : crée la facture cible, marque le devis comme converti
- Recalcul totaux via `CalculateQuoteTotalsAction` (s'applique aux devis non-brouillons aussi)
- Snapshot vendeur/acheteur figé à l'envoi
- Soft delete

---

## 3. Factures récurrentes

### Côté utilisateur

- Création d'un modèle de facture récurrente (lignes, client, montant)
- Fréquence configurable : hebdomadaire, mensuelle, trimestrielle, annuelle
- Date de début + date de fin (optionnelle) + nombre max d'occurrences
- Activation / désactivation
- Duplication
- Visualisation de la prochaine date d'émission

### Côté back-end

- Commande `recurring:generate` (cron quotidien) génère les factures dues
- Création automatique d'une facture brouillon à la date prévue
- Calcul de la prochaine date d'émission après chaque génération
- Logique de désactivation automatique en fin de période

---

## 4. Gestion clientèle & prospects

### Côté utilisateur

- CRUD complet des clients (nom, adresse, email, téléphone, n° TVA, n° matricule)
- Type : personne physique ou société
- Pays + code pays ISO (impacte le scénario TVA appliqué)
- Devise par défaut (EUR, USD, GBP, CHF)
- Taux horaire par défaut par client
- Notes internes par client
- Statut : prospect ou actif
- Conversion prospect → client en un clic
- Estimation de valeur (pipeline)
- Source du client (manuelle, import, conversion)
- Identifiant comptable personnalisé
- Identifiant Peppol (pour transmission B2G)
- Exclusion des relances automatiques (au cas par cas)
- Locale du client (pour traduire le PDF facture)
- Import en masse via wizard CSV : upload → mapping colonnes → validation → import asynchrone

### Côté back-end

- Validation VIES en temps réel pour les numéros TVA intra-UE
- Lookup d'entreprises via `CompanyLookupController` (entreprise.data.gouv.fr pour FR, LBR pour LU)
- Scénario TVA déterminé automatiquement via `VatCalculationService` selon pays + n° TVA
- Job d'import asynchrone (progress tracking, rollback en cas d'erreur)
- Service `ClientImportService` (parsing CSV, validation, dedup)
- Limite par plan (Free : 10 / Essentiel : 100 / Pro : illimité), gérée par middleware `plan.limit:clients`

---

## 5. CRM & relation client

> Disponible uniquement sur le plan Pro

### Côté utilisateur

- **Interactions** : notes, appels, emails, réunions liés à un client (timeline)
- **Rappels / tâches** : créer, éditer, marquer comme complétées, échéance
- **Tags clients** : créer, attacher, filtrer la liste clients par tag
- Vue centralisée de tous les rappels (page Reminders)
- Historique chronologique d'interactions par client
- Notifications email J-1 et le jour J pour les rappels

### Côté back-end

- Modèles : `Interaction`, `Reminder`, `Tag`, `TagClient` (pivot)
- Mailable `ReminderMail`
- Commande cron `reminders:send` toutes les 15 minutes (notifications email)
- Middleware `plan.feature:crm` protège tout le module
- Soft deletes

---

## 6. Gestion des dépenses

### Côté utilisateur

- Création, édition, suppression de dépenses (date, fournisseur, montant, catégorie)
- Upload de pièces justificatives (PDF, image)
- Calcul HT, TVA, TTC (taux paramétrable)
- Flag « déductible » oui/non
- Mode de paiement (carte, virement, espèces)
- Référence interne (n° séquence EXP-)
- Catégorisation (hosting, software, transport, repas, marketing, etc.)
- Vue récapitulative + filtrage par date / catégorie
- Exclusion d'une dépense des exports comptables

### Côté back-end

- Calcul automatique des montants à partir du HT ou du TTC
- Inclusion automatique dans les exports FAIA, Sage BOB 50, Sage 100, CSV
- Inclusion dans le résumé fiscal (charges déductibles)
- Limite par plan (Free : 10/mois, Essentiel : 30/mois, Pro : illimité)

---

## 7. Suivi du temps & projets

> Disponible sur Essentiel et Pro

### Côté utilisateur

- **Chronomètre live** : démarrer / arrêter, attribution client & projet
- Saisie manuelle d'entrées (date, durée, description)
- Vue récapitulative du temps non facturé
- **Conversion temps → facture** : sélection d'entrées non facturées → création de lignes de facture en un clic
- Taux horaire par défaut (entreprise, client, ou entrée)
- Filtrage par client / projet / date

### Côté back-end

- Modèle `TimeEntry` (started_at, stopped_at, duration_seconds, is_billed, hourly_rate)
- `ConvertTimeToInvoiceAction` : groupe les entrées par client/projet et génère les lignes facture
- Limite Essentiel : 10 h/mois suivies, Pro : illimité
- Middleware `plan.feature:time_tracking`

---

## 8. Tâches & collaboration projet

### Côté utilisateur (Pro pour collaborateurs)

- **Projets** : CRUD avec titre, description, client, statut, couleur, budget heures, échéance
- Drag-and-drop pour réordonner les projets
- Archivage de projets terminés
- Visibilité projet : public dans l'organisation ou restreint aux invités
- **Tâches imbriquées** dans les projets : titre, description, statut (À faire / En cours / Fait), sous-tâches
- Drag-and-drop pour réorganiser les tâches (entre statuts type kanban)
- Assignation à un employé (module HR) ou un collaborateur
- Invitation de collaborateurs externes au projet (par email)
- Cacher certains projets aux collaborateurs (`hidden_from_collaborators`)

### Côté back-end

- Modèles `Project`, `Task`, `ProjectEmployee`, `ProjectMember`
- Trait `BelongsToUser` (isolation tenant)
- Middleware `plan.feature:projects`
- Action `InviteCollaboratorToProjectAction` pour invitations tokenisées

---

## 9. Portail collaborateur externe

### Côté utilisateur (le collaborateur invité)

- Invitation reçue par email avec lien tokenisé (`/collaborateur/invitation/{token}`)
- Pas de mot de passe nécessaire pour accepter — il crée son compte au passage
- Dashboard collaborateur (`/collaborator-dashboard`)
- Accès limité aux projets sur lesquels il est invité
- Time tracking sur ces projets
- Gestion des tâches (CRUD) sur les projets accessibles
- Possibilité de **monter en grade** : créer sa propre organisation et devenir owner (`CollaboratorUpgradeController`)

### Côté back-end

- Authentification via le user standard avec scope organisation
- Guard `collaborator` + middleware `redirect.employee` (force le routage vers `/collaborator-dashboard`)
- Modèles `OrganizationMember`, `OrganizationInvitation`, `ProjectMember`
- Mailable `ProjectCollaboratorInvitation`
- Notifications email à l'ajout / retrait d'un membre de projet (`ProjectMemberAdded`, `ProjectMemberRemoved`)

---

## 10. Module RH (employés)

> Disponible uniquement sur le plan Pro

### Côté utilisateur (DRH / gérant)

- **Employés** :
  - CRUD avec informations personnelles, contrat, salaire brut
  - Upload de documents (contrat, CV, fiches de paie, etc.)
  - Statut : actif, long_leave, terminated
  - Activation / désactivation du portail employé
  - Visualisation des congés, frais, évaluations par employé

- **Congés** :
  - CRUD types de congés (RTT, maladie, parental, etc.)
  - Soldes (`LeaveBalance`) suivis automatiquement par type
  - Vue calendrier des congés (équipe)
  - Approbation / refus de demandes
  - Toggle pour cacher les congés aux autres employés (`hide_leaves_from_team`)

- **Notes de frais** :
  - Catégories de frais paramétrables
  - Soumission par employé → approbation manager
  - Upload de reçus
  - Suivi du remboursement

- **Évaluations** :
  - Création d'évaluations (formulaire flexible)
  - Upload de documents associés
  - Export PDF
  - Historique des évaluations par employé

- **Onboarding** :
  - Templates d'onboarding réutilisables (signature contrat, accès logiciels, formation, etc.)
  - Application d'un template à un nouvel employé
  - Checklist progressive

- **Événements RH** :
  - Création d'événements (réunions, formations)
  - Invitations aux participants
  - Vue calendrier
  - Rappels email J-1 et J0

- **Salles / Ressources** : CRUD de salles de réunion / bureaux
- **Trombinoscope** : annuaire visuel des employés (export PDF)
- **Calendrier partagé** : événements + congés sur une vue calendrier
- **Départements** : structure organisationnelle, hiérarchie

### Côté back-end

- ~16 modèles dans `app/Models/HR/`
- Controllers dédiés sous `app/Http/Controllers/HR/`
- Services : `HRDashboardService`, `TrombinoscoPdfService`, `EvaluationPdfService`
- Mailables : `HrEventInvitation`, `HrEventReminder`, `TerminatedEmployeeProjectsCleanupSummary`
- Cron `hr:send-event-reminders` quotidien à 07h00
- Middleware `plan.feature:hr_module`

---

## 11. Portail employé self-service

### Côté utilisateur (l'employé)

- Accès via `/mon-espace-rh` après login (redirection automatique)
- **Mes congés** : voir solde, soumettre une demande, annuler une demande en attente
- **Mes notes de frais** : soumettre un rapport, joindre des reçus, suivre le statut
- **Mes documents** : consulter contrat, fiches de paie, autres documents partagés
- **Mes évaluations** : lecture seule
- **Mes projets** : projets sur lesquels je suis affecté
- **Mes tâches** : tâches assignées
- **Mon profil** : édition coordonnées personnelles
- **Calendrier partagé** : voir les événements et congés visibles

### Côté back-end

- Routes `routes/employee-portal.php`
- Middleware `employee.portal` (vérifie que l'utilisateur a un record `Employee` actif)
- 9 controllers `Portal*Controller` dédiés
- Isolation : l'employé ne voit que ce qui le concerne (et ce qui est partagé)

---

## 12. Comptabilité & exports

> Disponible sur Essentiel et Pro selon le format

### Côté utilisateur

- **Livre des recettes** :
  - Vue chronologique des factures par mois/année
  - Filtrage par client, date, statut
  - Calcul automatique des totaux HT, TVA, TTC par taux
  - Export PDF et CSV

- **Résumé fiscal** :
  - TVA collectée vs TVA déductible par taux
  - Total des charges déductibles (depuis dépenses)
  - Cumuls par période
  - Export PDF et CSV

- **Export FAIA** (Administration de l'Enregistrement, des Domaines et de la TVA) :
  - Génération du fichier XML conforme FAIA 2.01
  - Validation contre le schéma XSD officiel avant téléchargement
  - Historique des exports précédents
  - Téléchargement du fichier brut

- **Exports comptables** :
  - **Sage BOB 50** (format belge utilisé par certaines fiduciaires)
  - **Sage 100** (format français)
  - **CSV générique** (compatible Excel, Numbers, autres ERP)
  - Sélection de la période d'export
  - Historique téléchargeable

### Côté back-end

- Services : `AuditExportService` (FAIA), `AccountingExportService` (multi-format), `RevenueBookService`, `FiscalSummaryService`
- Formatters dédiés : `SageBobFormatter`, `Sage100Formatter`, `GenericCsvFormatter`
- Modèles : `AuditExport` (FAIA), `AccountingExport` (Sage/CSV)
- Middleware `plan.feature:accounting_exports`
- Throttling : `throttle:audit-export` et `throttle:export` pour limiter le rythme

---

## 13. Portail comptable / fiduciaire

> Conçu pour les fiduciaires luxembourgeoises — le portail est gratuit pour le comptable

### Côté utilisateur (fiduciaire connectée)

- Connexion via `/comptable/login` (système d'auth séparé)
- Dashboard fiduciaire avec liste des clients accordés
- Vue par client (dropdown switch)
- **Accès lecture seule** à :
  - Toutes les factures émises (avec PDF téléchargeable)
  - Livre des recettes
  - Résumé fiscal
  - Exports comptables (téléchargement direct)
  - Export FAIA
- **Mass export** : récupérer les données de tous les clients en un seul export
- **Rapport consolidé** multi-client
- Historique des téléchargements

### Côté utilisateur (côté entrepreneur qui invite)

- Menu **Réglages → Comptable** : invitation par email avec lien tokenisé
- Liste des invitations en attente, acceptées
- Renvoi / annulation d'invitation
- Révocation d'accès à tout moment

### Côté back-end

- Modèles : `Accountant`, `AccountantInvitation`, `AccountantDownload`
- Auth séparée : guard `accountant` avec sa propre table `accountants`
- Lien comptable ↔ entrepreneur via pivot `accountant_user`
- Mailable : `AccountantInvitationMail`
- Middleware `plan.feature:accounting_portal` (accessible dès le plan Gratuit)
- Controllers : `AccountantAuthController`, `AccountantDashboardController`, `AccountantExportController`, `AccountantMassExportController`

---

## 14. Organisation & équipes

> Disponible sur le plan Pro

### Côté utilisateur

- Création de l'organisation (1 par utilisateur owner)
- Invitation de membres par email
- Rôle : admin de l'organisation ou membre standard
- Vue de l'équipe (`/settings/organisation`)
- Renvoi / annulation d'invitations
- Suppression d'un membre

### Côté back-end

- Modèles : `Organization`, `OrganizationMember`, `OrganizationInvitation`
- Middleware `org.admin` et `plan.feature:organizations`

---

## 15. Conformité Luxembourg

### Visibles côté utilisateur

- **Numérotation séquentielle obligatoire** (article 63 LIVA, point 3°) — non modifiable une fois finalisée
- **Mentions TVA automatiques** selon le scénario :
  - Régime normal LU (17 %)
  - Franchise art. 57bis (CA ≤ 50 000 €)
  - Autoliquidation B2B intra-UE (mention art. 196 directive)
  - Livraison intra-UE exonérée (mention art. 138 directive)
  - Export hors UE
- **Mention de paiement luxembourgeoise** + IBAN LU optionnel
- **Adresses RCS / matricule** sur facture
- **QR code de paiement** EPC Luxembourg (upload manuel)
- **Conservation 10 ans** garantie par l'archivage PDF/A (plan Pro)

### Invisibles côté back-end

- Service `VatCalculationService` détermine automatiquement le scénario TVA
- Numérotation auto-incrémentée avec verrouillage anti-trous (modèle Invoice, hook `creating` qui appelle `GenerateInvoiceNumberAction`)
- Archivage PDF/A avec checksum SHA-256 conservé en DB (`archive_path`, `archive_checksum`, `archive_format`, `archive_expires_at`)
- Trait `Auditable` enregistre toute modification pour traçabilité
- Soft delete + immutabilité sur factures finalisées
- Export FAIA conforme au schéma 2.01 AED
- Validation VIES enregistrée comme preuve d'autoliquidation

---

## 16. Multi-langue & localisation

### Langues supportées

- **Français (FR)** — langue principale, complète
- **Allemand (DE)**
- **Anglais (EN)**
- **Luxembourgeois (LB)**
- **Portugais (PT)**

### Côté utilisateur

- Sélecteur de langue dans la navbar (5 langues)
- Détection automatique de la langue préférée du navigateur à la première visite
- Switch langue : `/switch-locale/{locale}` (mémorise la préférence dans le user)
- Préfixes URL localisés : `/fr/...`, `/de/...`, `/en/...`, `/lb/...`, `/pt/...`
- Slugs blog et URLs traduites par langue (ex. `/fr/tarifs`, `/de/preise`, `/en/pricing`)
- **PDF facture multilingue** : sélection de la langue lors de l'envoi (cible la langue du client)
- Glossaire métier multilingue
- Newsletter par langue (listes Brevo séparées)

### Côté back-end

- Middleware `SetLocale` détermine et applique la locale active
- Fichiers de traductions : `resources/lang/{fr|de|en|lb|pt}/app.php`
- Traductions livrées via Inertia (`HandleInertiaRequests::getTranslations`) — pas dans le bundle JS
- `config/localized_routes.php` : mapping de chaque route nommée → slug par locale
- Composable JS `useLocalizedRoute.js` pour générer les URLs côté frontend
- Tableau `SLUG_MAP` dans `UpdateBlog2025To2026SlugsSeeder` pour les redirects 301 d'anciens slugs

---

## 17. Plans, paiements & gating

### Plans disponibles

| Plan | Prix mensuel | Limites principales |
|---|---|---|
| **Gratuit** | 0 € | 10 clients · 5 factures/mois · 5 devis/mois |
| **Essentiel** | 5 € | 100 clients · 50 factures/mois · time tracking 10 h/mois · projets · exports comptables · emails |
| **Pro** | 15 € | Tout illimité · HR · CRM · Peppol · PDF/A archivage · Factur-X · organisation · reporting avancé · zéro branding |

### Côté utilisateur

- Page pricing publique avec comparatif
- Dashboard abonnement (plan courant, prochaine facturation, expiration)
- Checkout Stripe intégré
- Page de succès après paiement
- Accès au **portail client Stripe** (gestion CB, factures de paiement)
- Changement de plan (swap mid-cycle proraté)
- Résiliation + reprise possible

### Côté back-end

- Modèle `Plan` (limites + features stockées en JSON)
- Service `PlanService` (getUserPlan, hasFeature, getLimit)
- Middleware `plan.feature:{key}` (abort 403 si non disponible)
- Middleware `plan.limit:{key}` (abort 402 si quota atteint)
- Intégration **Laravel Cashier** pour Stripe
- Trial 14 jours offerts à l'inscription
- Webhooks Stripe pour synchronisation (abonnement, paiement, cancel)

### Features gérables par plan

`time_tracking`, `projects`, `crm`, `hr_module`, `accounting_exports`, `peppol_transmission`, `pdf_archive`, `facturx`, `email_reminders`, `organizations`, `accounting_portal`, `no_branding`, `priority_support`, `advanced_reporting`

---

## 18. Sécurité & authentification

### Côté utilisateur

- **Auth standard** : email + mot de passe
- **Vérification email** obligatoire après inscription
- **Réinitialisation mot de passe** par lien email
- **Confirmation de mot de passe** sur actions sensibles
- **2FA TOTP** (Time-based One-Time Password) — Google Authenticator, Authy, 1Password
- Codes de recovery (lecture seule à l'activation)
- Désactivation 2FA (avec password confirmation)
- **Changement de mot de passe** + email de notification de changement
- **Suppression de compte** (soft + force après délai)

### Côté back-end

- **Honeypot** anti-bot sur tous les formulaires publics (`HoneypotMiddleware`)
- **CSRF** sur toutes les routes POST/PUT/PATCH/DELETE
- **Rate limiting** par endpoint (groupes `throttle:crud`, `throttle:pdf`, `throttle:preview`, `throttle:export`, `throttle:email`, `throttle:audit-export`, `throttle:company-lookup`, `throttle:dashboard`)
- **Security headers** : HSTS, CSP, X-Frame-Options, X-Content-Type-Options (middleware)
- **Trail d'audit** complet (trait `Auditable`)
- **Authentification admin distincte** avec table `admin_login_attempts` et IP whitelist
- **Sessions** : durée configurable, hijacking-resistant
- Mots de passe hashés avec bcrypt
- Secrets 2FA chiffrés

---

## 19. Marketing public & SEO

### Pages publiques

- **Landing** (`/fr/`, `/de/`, etc.)
- **À propos** (`/fr/a-propos`, équivalents traduits)
- **Pourquoi faktur.lu** (USPs)
- **Pour freelances** (page cible métier)
- **Pour PME** (page cible métier)
- **Partenaires** (page comptables / fiduciaires)
- **Tarifs**
- **Fonctionnalités** (vue overview)
- **Pages fonctionnalités** individuelles (slugs dédiés)
- **Glossaire métier** (50+ termes définis avec liens internes)
- **Contact** (formulaire avec honeypot + throttle)
- **Mentions légales**, **Confidentialité**, **CGU**, **Cookies**, **DPA**

### SEO

- **Sitemap XML** : `/sitemap.xml` (index), `/sitemap-pages.xml`, `/sitemap-blog.xml`
- **Schema.org JSON-LD** injecté via `SchemaJsonLd.vue` (BlogPosting, DefinedTermSet, SpeakableSpecification, BreadcrumbList)
- **hreflang** sur toutes les pages (5 langues)
- **Open Graph + Twitter Cards** méta-tags
- **Canonical URLs** sur chaque page
- **Robots.txt** + maintenance des URLs propres
- **Redirects 301** pour anciens slugs (préservation backlinks)

---

## 20. Blog multilingue

### Côté utilisateur

- **Index blog** par langue : `/fr/blog`, `/de/blog`, etc.
- Pagination
- **Catégories** : `/fr/blog/categorie/{slug}`
- **Tags** : `/fr/blog/tag/{slug}`
- **Article** : `/fr/blog/{slug}` avec image principale, temps de lecture, contenu HTML
- Sélecteur de langue qui pointe vers la version traduite de l'article (via `translation_key`)
- Articles connexes
- Glossaire interne (liens vers définitions)

### Côté back-end

- 29 articles × 5 langues = **145 articles en base**
- Modèles : `BlogPost`, `BlogCategory`, `BlogTag` (+ pivot `blog_post_tags`)
- Stockage : table `blog_posts` (titre, slug, contenu HTML, meta_title, meta_description, status, published_at, locale, translation_key)
- Publication programmée : `published_at` futur → article visible automatiquement à l'heure dite (scope `published` filtre `where('published_at', '<=', now())`)
- Archives source HTML dans `database/seeders/content/`
- Seeders dédiés par langue (BlogScheduledJune2026{Locale}Seeder)
- Système de réécriture par fichier : `database/seeders/content/article_rewrites/`
- Audit factuel complet : 145 articles vérifiés et corrigés contre sources officielles
- Signature « Article mis à jour le... » + disclaimer apposés sur toutes les langues

### Back-office admin

- CRUD articles (status brouillon / publié)
- Éditeur riche RichTextEditor avec preview live
- Programmation publication (`published_at`)
- Gestion catégories + tags
- Featured image upload
- SEO meta (title, description)
- Aperçu en ligne avant publication

---

## 21. Outils gratuits publics

> Objectifs : lead generation + SEO + acquisition

### Côté utilisateur

- **Calculateur TVA Luxembourg** (`/outils/calculateur-tva`) : conversion HT ↔ TTC pour les 4 taux LU
- **Simulateur franchise TVA** (`/outils/franchise-tva`) : suis-je éligible à la franchise (seuil 50 000 €) ?
- **Validateur IBAN** (`/outils/validateur-iban`) : vérification format + checksum + extraction info banque
- **Générateur de facture express** (`/outils/generateur-facture`) : PDF sans inscription, rate-limité
- **Modèles de facture** (`/outils/modeles-facture`) : templates téléchargeables par langue (Excel, PDF)
- **Validateur FAIA** (`/validateur-faia`) : upload d'un fichier FAIA → validation contre schéma XSD officiel, sans stockage côté serveur

### Côté back-end

- Hubs par langue : `/outils/`, `/tools/`, `/werkzeuge/`, `/handgeschir/`, `/ferramentas/`
- Routes localisées avec throttling
- Contrôleurs : `ToolsController`, `FaiaValidatorController`
- Services : `FaiaValidatorService`, `VatCalculationService`

---

## 22. Onboarding utilisateur

### Wizard à l'inscription

- **Étape 1** : Infos entreprise (nom, adresse, n° TVA)
- **Étape 2** : Numérotation factures (préfixe, numéro de départ, format)
- **Étape 3** : Branding (logo, couleur PDF)
- **Étape 4** : Premier client (optionnel)
- **Étape 5** : Première facture brouillon (optionnel)
- Bouton « Skip » à chaque étape

### Côté back-end

- Champs sur `users` : `onboarding_step`, `onboarding_completed_at`, `onboarding_skipped`, `onboarding_checklist_dismissed`, `onboarding_numbering_acknowledged_at`
- Controller `OnboardingController` + service `OnboardingService`
- **Checklist persistante** sur le dashboard tant qu'elle n'est pas dismissée
- **Banner numérotation** : invite à confirmer la config de numérotation avant la 1ère facture finalisée

---

## 23. Support utilisateur

### Côté utilisateur

- Création de ticket (titre, description, pièces jointes)
- Liste de mes tickets
- Conversation back-and-forth (réponses utilisateur ↔ admin)
- Upload de pièces jointes
- Statuts : ouvert, en cours, résolu, fermé

### Côté back-end

- Modèles `SupportTicket`, `SupportMessage`, `SupportAttachment`
- Mailables : `NewSupportTicketNotification` (admin), `SupportReplyNotification` (utilisateur)
- Admin gère via `/admin/support/...`

---

## 24. Sondages de satisfaction (NPS)

### Côté utilisateur

- Email envoyé automatiquement **14 à 21 jours** après inscription
- Formulaire public tokenisé `/fr/sondage/{token}` (pas de login requis)
- Note NPS de 0 à 10
- Champ commentaire libre (optionnel, 2000 caractères max)
- Page de remerciement après soumission

### Côté back-end

- Modèle `SatisfactionSurvey` (status pending/completed/expired, token, nps_score, comment, sent_at, completed_at, expires_at)
- Commande `survey:send` (cron quotidien 10h00)
- Logique de fenêtre : envoi uniquement entre J+14 et J+21
- Opt-out via `users.drip_unsubscribed`
- Mailable `SatisfactionSurveyEmail`
- Email récap à l'admin (`SatisfactionSurveyAdminNotification`)
- Page admin `/admin/surveys` :
  - Liste des réponses avec aperçu commentaire
  - Modal détail (commentaire complet)
  - Calcul **NPS = % promoteurs (9-10) − % détracteurs (0-6)**
  - Taux de réponse
  - Export CSV

---

## 25. Newsletter

### Côté utilisateur

- CTA newsletter dans le footer + dans certains articles blog
- Saisie email → email de confirmation (double opt-in)
- Lien unique de confirmation
- Lien de désabonnement dans chaque email

### Côté back-end

- Modèle `NewsletterSubscriber`
- Mailable `NewsletterConfirmation`
- Intégration **Brevo API** pour la souscription + DOI + listes par langue
- Honeypot + throttle sur le formulaire
- Stockage local DB + sync Brevo
- Export liste depuis admin

---

## 26. Recherche globale

### Côté utilisateur

- Barre de recherche dans la navbar (`/search/results`)
- Cherche dans : clients, factures, devis, dépenses
- Suggestions autocomplete
- Filtrage par type de résultat

### Côté back-end

- Service `GlobalSearchService` (assemblage de requêtes multi-modèles)
- Routes throttlées

---

## 27. Reporting & tableaux de bord

### Dashboard principal

- KPIs : revenus mois en cours, revenus année, factures impayées (nombre + montant), temps non facturé
- **Cashflow forecast** sur 30/60/90 jours
- **Graphique de revenus** historique (mois par mois)
- Résumé TVA collectée vs déductible
- Activités récentes
- Notifications

### Reports comptables (rappel)

- Livre des recettes
- Résumé fiscal
- Multi-format (PDF, CSV)

### Côté back-end

- Service `DashboardService` (calculs cumulés)
- Action `RevenueForecastAction`
- Caching des résultats pour performances

---

## 28. Réglages & configuration

### Réglages entreprise

- Logo
- Adresse, email, téléphone affichés sur facture
- Numéro TVA, RCS, matricule
- IBAN + BIC
- Couleur d'accent PDF
- Type d'activité
- Régime TVA (normal / franchise)
- Format de numérotation (factures, avoirs, devis)
- Texte de pied de page par défaut
- Mention TVA par défaut
- QR code de paiement (upload)

### Réglages email

- Templates configurables (welcome, reminder, etc.)
- Fréquence des relances
- Exclusion globale d'invoices des relances

### Réglages fournisseur email (SMTP)

- Host, port, username, password, encryption
- Test SMTP intégré
- Override pour environnement de dev

### Réglages comptables

- Format d'export par défaut (Sage BOB 50, Sage 100, CSV)
- Mappings de comptes

### Profil utilisateur

- Nom, email, mot de passe
- Langue préférée
- 2FA
- Suppression de compte

---

## 29. Administration (back-office)

### Routes `/admin/...`

- **Dashboard admin** : synthèse utilisateurs, revenus, trials, churn
- **Gestion utilisateurs** : liste, détails, impersonation (debug), reset 2FA, désactivation
- **Gestion blog** : CRUD articles, catégories, tags, publications programmées
- **Sondages NPS** : visualisation réponses, NPS global, export CSV
- **Support** : tickets ouverts, réponses
- **Newsletter** : liste subscribers, export
- **Maintenance** : toggle mode maintenance, cache clear, logs
- **Monitoring** : métriques requêtes, performance, erreurs

### Côté back-end

- Authentification admin séparée (pas Fortify) avec IP whitelist
- Modèles : `AdminSession`, `AdminLoginAttempt`
- Middleware : `AdminAuthenticated`, `EnsureUserIsAdmin`
- Trait `Auditable` pour tracer les actions admin

---

## 30. Monitoring & opérations

### Métriques

- Tracking des requêtes : endpoint, durée, statut HTTP, queries DB count
- Modèle `RequestMetric`
- Middleware `TrackRequestMetrics`
- Visualisation admin

### Backups

- Commandes `backup:run`, `backup:list`, `backup:restore`
- Schedulé quotidien (3h00 par défaut, configurable)
- Stockage local ou externe

### Cleanup

- Commande `monitoring:cleanup` quotidienne (3h00) — purge métriques > 30 jours
- Commande de cleanup des employés terminés sur projets (`TerminatedEmployeeProjectsCleanupSummary`)

### Maintenance mode

- Toggle via UI admin
- IP whitelist (admin restent connectés)
- Page maintenance personnalisée par langue
- `php artisan up` automatique en cas d'erreur deploy (trap EXIT dans `deploy.sh`)

---

## 31. Emails — liste centralisée

### Emails transactionnels (déclenchés par actions)

| Mailable | Déclencheur | Destinataire |
|---|---|---|
| `InvoiceMail` | Envoi manuel d'une facture | Client |
| `ReminderMail` | Cron `reminders:send` détecte facture impayée | Client |
| `NewsletterConfirmation` | Souscription newsletter | Lead |
| `ProjectCollaboratorInvitation` | Invitation projet | Collaborateur externe |
| `ProjectMemberAdded` / `ProjectMemberRemoved` | Modification d'équipe projet | Membre concerné |
| `TrialEndingSoon` | J-2 avant fin trial | Utilisateur |
| `TrialExpired` | À expiration trial | Utilisateur |
| `AccountantInvitationMail` | Invitation comptable | Fiduciaire |
| `SatisfactionSurveyEmail` | J+14 à J+21 après signup | Utilisateur |
| `HrEventInvitation` | Création d'événement HR | Participants |
| `HrEventReminder` | Cron HR J-1 et J0 | Participants |
| `TerminatedEmployeeProjectsCleanupSummary` | Cleanup employé terminé | Admin |
| `NewUserRegisteredNotification` | Nouvelle inscription | Admin |
| `NewSupportTicketNotification` | Création ticket | Admin |
| `SupportReplyNotification` | Réponse à un ticket | Utilisateur |
| `SatisfactionSurveyAdminNotification` | Réponse NPS reçue | Admin |
| `DripCampaignEmail` | Cron `drip:send` | Utilisateur ciblé |

### Drip campaigns marketing

- Séquences programmées via `DripEmail` + commande `drip:send`
- Logique de dédup (1 email par user_id + email_key)
- Opt-out via `users.drip_unsubscribed`

---

## 32. Tâches programmées (cron / scheduler)

Toutes définies dans `bootstrap/app.php` via `withSchedule()` :

| Commande | Fréquence | Description |
|---|---|---|
| `recurring:generate` | 06h00 quotidien | Générer les factures récurrentes dues |
| `hr:send-event-reminders` | 07h00 quotidien | Rappels HR (J-1 et J0) |
| `trial:send-reminders` | 08h00 quotidien | Alertes fin de trial |
| `SendPaymentReminders` (job) | 09h00 quotidien | Relances factures impayées |
| `drip:send` | 09h30 quotidien | Drip campaigns marketing |
| `survey:send` | 10h00 quotidien | NPS post-inscription |
| `reminders:send` | Toutes les 15 min | Notifications CRM rappels |
| `monitoring:cleanup` | 03h00 quotidien | Purge anciennes métriques |
| `backup:run` | 03h00 quotidien (config) | Backup DB |

> **Important** : ces tâches ne tournent que si le cron `php artisan schedule:run` est lancé chaque minute sur le serveur prod.

---

## 33. Intégrations externes

### Paiements

- **Stripe** (via Laravel Cashier)
  - Checkout intégré
  - Webhooks pour synchronisation abonnement
  - Portail client (gestion CB, factures)
  - Plan swap, cancel, resume

### Emails

- **Brevo** (ex-Sendinblue)
  - SMTP transactionnel
  - API newsletter (souscription + DOI)
  - Listes par langue

### Validation entreprise

- **VIES** (Commission européenne) — validation TVA intra-UE
- **entreprise.data.gouv.fr** — lookup entreprises FR
- **LBR (Luxembourg Business Registers)** — lookup entreprises LU

### Facturation électronique

- **Peppol Network** — transmission B2G via Access Point
- **StorecoveService** (l'AP utilisé) — UBL 2.1, BIS Billing 3.0
- Schéma identifiant Luxembourg : `9938` (LU:VAT)

### Génération de fichiers

- **DomPDF** (barryvdh/laravel-dompdf) — génération PDF
- **PDF/A** support (archivage légal)
- **QR code** EPC Luxembourg (paiement SEPA)

---

## 34. Multi-tenancy & isolation des données

### Mécanisme

- Trait `BelongsToUser` appliqué sur tous les modèles « tenant » (Invoice, Client, Quote, Expense, etc.)
- **Global scope** : toutes les requêtes Eloquent sont automatiquement filtrées par `auth()->id()`
- Impossible d'accéder accidentellement aux données d'un autre user
- **Auto-assignation** de `user_id` à la création (hook `creating`)
- Scopes `forUser($user)` et `withoutUserScope()` pour les contextes admin/jobs

### Protection IDOR

- Vérifications manuelles `abort_unless($item->invoice_id === $invoice->id, 404)` sur les sous-ressources (lignes, items)
- Fix d'audit IDOR appliqué (commit `04a47279`)

### Modèles concernés

User, Client, Invoice, Quote, Expense, TimeEntry, Project, Task, AccountantInvitation, AuditExport, AccountingExport, Reminder, Interaction, Tag, RecurringInvoice, Employee (HR), tous les modèles HR…

---

## 35. Audit log & immutabilité

### Trait Auditable

- Appliqué sur les modèles critiques (Invoice, Quote, Client, Expense, Project…)
- Trace : `create`, `update`, `delete`
- Stocké dans la table `audit_logs` (user_id, action, model_type, model_id, changes JSON, timestamp, IP)

### Page utilisateur

- `/audit-logs` : journal d'audit consultable
- Filtres : action, modèle, date
- Export CSV

### Immutabilité

- Trait/hook empêche la modification de factures finalisées (`ImmutableInvoiceException`)
- Soft deletes sur entities sensibles (impossible de hard-delete depuis l'UI)
- Conservation 10 ans garantie pour les factures archivées en PDF/A

---

## Récapitulatif chiffré

| Composant | Quantité |
|---|---|
| Plans tarifaires | 3 |
| Langues supportées | 5 |
| Articles blog (toutes langues) | 145 |
| Modèles Eloquent | ~60 |
| Controllers | ~70 |
| Mailables (emails distincts) | ~17 |
| Commandes Console | ~10 |
| Tâches cron actives | 9 |
| Services métier | ~15 |
| Middlewares custom | ~15 |
| Plans feature gates | 14 |
| Limites plan trackées | ~10 |
| Intégrations externes | 7 |
| Pages publiques | ~25 |
| Outils gratuits publics | 6 |
| Portails séparés | 4 (utilisateur, comptable, collaborateur, employé) |

---

## Pour aller plus loin

- **Architecture** : Laravel 12 (mode complet, pas API), Inertia + Vue 3 + Tailwind, MySQL, Stripe Cashier, queue `sync` (extensible vers Redis)
- **Multi-tenant** : isolation par `user_id` via trait `BelongsToUser` + global scope
- **Multi-portail** : 4 systèmes d'authentification distincts (user, accountant, collaborator → guard user normal, employee → middleware portal)
- **Multi-langue** : 5 langues via Inertia + traductions JSON
- **Conformité LU** : numérotation séquentielle, FAIA 2.01, archivage PDF/A 10 ans, mentions LIVA automatiques, autoliquidation intra-UE, Peppol B2G

Ce SaaS couvre tout le cycle de vie d'un freelance / PME au Luxembourg : facturation conforme, comptabilité (livre des recettes, exports Sage/CSV/FAIA), portail comptable pour fiduciaire, gestion d'équipe et projets, suivi du temps, CRM léger, module RH complet, conformité légale, et un écosystème de contenu SEO (blog 145 articles, glossaire, outils gratuits) pour acquisition.

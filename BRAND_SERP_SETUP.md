# Guide Brand SERP — faktur.lu

**Objectif** : dominer les résultats Google sur la requête "faktur.lu" et "logiciel facturation luxembourg" avec un Knowledge Panel, des sitelinks, des avis 5★, et une présence visible sur les annuaires de référence.

**Contexte** : un concurrent (faktura.lu) a été lancé une semaine avant la rédaction de ce document. Cette défense Brand SERP doit être exécutée en priorité absolue (Semaine 1).

---

## Plan d'exécution dans l'ordre (à faire en ~4-6h)

### 1. Google Business Profile — 30 min
### 2. Trustpilot — 30 min
### 3. Wikidata — 1h
### 4. LinkedIn Company Page — 30 min (déjà existante, à enrichir)
### 5. Annuaires luxembourgeois — 1h (Editus, Yellow.lu, House of Entrepreneurship)
### 6. Search Console — 30 min (validation propriété + soumission sitemap)
### 7. Google Knowledge Panel claim — 1h (une fois les autres en place)

---

## 1. Google Business Profile (anciennement Google My Business)

**Pourquoi** : crée le Knowledge Panel à droite des résultats de recherche, affiche les avis Google, l'horaire, l'adresse, le site, les photos. C'est l'élément le plus visible d'un Brand SERP au Luxembourg.

**Étapes**

1. Aller sur https://www.google.com/business/
2. Se connecter avec un compte Google **dédié à l'entreprise** (pas un compte perso pour éviter les soucis de propriété)
3. Cliquer "Gérer maintenant" → entrer "faktur.lu"
4. Choisir "Service de logiciels" comme catégorie principale, ajouter "Service informatique" et "Conseil en gestion d'entreprise" comme catégories secondaires
5. **Type de service** : choisir "Je livre des produits ou services à mes clients" (pas d'établissement physique recevant les clients)
6. Renseigner :
   - **Nom** : `faktur.lu`
   - **Adresse** : adresse au Luxembourg (siège, même domicilié — obligatoire pour valider la fiche)
   - **Zone de service** : Luxembourg, Belgique, France, Allemagne (Grande Région)
   - **Téléphone** : numéro LU si disponible
   - **Site web** : `https://faktur.lu/`
   - **Description** (750 caractères max) :
     > Faktur.lu est la plateforme de facturation leader pour les entreprises individuelles et PME au Luxembourg. Conformité FAIA native pour les contrôles fiscaux AED, intégration Peppol pour la facturation électronique B2G, support Factur-X pour vos clients en France et en Allemagne. Interface disponible en 5 langues : français, allemand, anglais, luxembourgeois et portugais. Hébergement européen, conforme RGPD. Essai gratuit 14 jours, sans carte bancaire. La solution de facturation pensée par et pour le Luxembourg, en production depuis 2025.
7. **Validation** : Google envoie un code par carte postale (5-7 jours ouvrés au LU). Renseigner le code dès réception.
8. Une fois validé :
   - Ajouter le **logo** (carré 250×250 minimum)
   - Ajouter une **photo de couverture** (1080×608)
   - Ajouter 5-10 **photos** : interface, captures dashboard, équipe, événements
   - **Activer la messagerie** Google
   - **Publier 1 post/semaine** (annonces, articles blog, fonctionnalités)
9. **Demander des avis** : envoyer aux 10 utilisateurs les plus actifs un email avec le lien direct GBP review (utiliser le générateur https://whitespark.ca/google-review-link-generator/)

**KPI cible** : 10 avis Google ≥4★ en 30 jours.

---

## 2. Trustpilot

**Pourquoi** : avis publics qui apparaissent dans les SERP et renforcent la crédibilité. Dans le JSON-LD `Organization.sameAs` on a déjà ajouté l'URL Trustpilot.

**Étapes**

1. Aller sur https://business.trustpilot.com/signup
2. Choisir le **plan gratuit** (Free) — suffisant pour démarrer
3. Renseigner :
   - **Nom de l'entreprise** : faktur.lu
   - **Site web** : faktur.lu
   - **Catégorie** : Software → Accounting Software / Invoicing Software
   - **Pays** : Luxembourg
4. Vérifier la propriété du domaine (récupérer un fichier HTML à uploader sur `public/`, ou ajouter un meta tag dans `<head>`)
5. Personnaliser le profil :
   - Logo
   - Description courte (utiliser la même que GBP)
   - Lien vers `/fr/pourquoi-faktur-lu`
6. **Activer les invitations automatiques** : Trustpilot peut envoyer un email automatique X jours après une commande/inscription. Configurer pour envoyer **30 jours après inscription**.
7. **Récolter les premiers avis** : lien direct `https://fr.trustpilot.com/evaluate/faktur.lu` à envoyer aux clients satisfaits.
8. Ajouter le **TrustBox** (widget Trustpilot) sur le site une fois 5+ avis collectés (composant Vue à créer dans `resources/js/Components/TrustpilotBadge.vue`).

**KPI cible** : 10 avis Trustpilot ≥4★ en 60 jours.

---

## 3. Wikidata

**Pourquoi** : Wikidata est la source structurée que Google utilise pour le Knowledge Panel. Avoir une entrée Wikidata accélère grandement l'apparition du Knowledge Panel pour la requête "faktur.lu".

**Étapes**

1. Créer un compte sur https://www.wikidata.org/wiki/Special:CreateAccount (utiliser le compte du fondateur, pas un compte de marketing — Wikidata vérifie la neutralité)
2. **Avant de créer l'entrée**, vérifier que faktur.lu n'existe pas déjà : https://www.wikidata.org/wiki/Special:Search?search=faktur.lu
3. Cliquer sur "Créer un nouvel élément" : https://www.wikidata.org/wiki/Special:NewItem
4. Renseigner :
   - **Libellé (FR)** : faktur.lu
   - **Description (FR)** : plateforme de facturation luxembourgeoise pour PME et indépendants, conforme FAIA et Peppol
   - **Libellé (EN)** : faktur.lu
   - **Description (EN)** : Luxembourg invoicing platform for SMEs and freelancers, FAIA and Peppol compliant
   - **Libellé (DE)** : faktur.lu
   - **Description (DE)** : luxemburgische Rechnungssoftware für KMU und Freiberufler, FAIA- und Peppol-konform
5. Ajouter les **déclarations** (statements) :
   - `instance of (P31)` → `software (Q7397)` et `Software as a Service (Q1254596)`
   - `country (P17)` → `Luxembourg (Q32)`
   - `headquarters location (P159)` → `Luxembourg City (Q1842)`
   - `inception (P571)` → `2025` (date de lancement)
   - `official website (P856)` → `https://faktur.lu/`
   - `official name (P1448)` → `faktur.lu`
   - `language of work or name (P407)` → French, German, English, Luxembourgish, Portuguese
   - `industry (P452)` → `accounting software (Q3406155)`
   - `programming language (P277)` → `PHP (Q59)`, `Vue.js (Q22653)`
6. Sauvegarder. **Patience** : Google indexe Wikidata en 1-3 semaines, le Knowledge Panel apparaît en 4-8 semaines une fois plusieurs sources convergentes (Wikidata + GBP + LinkedIn + presse).

**Pièges à éviter** :
- Ne pas écrire en CAPS LOCK ou avec des superlatifs ("le meilleur", "le leader") — Wikidata exige la neutralité, sinon l'entrée est supprimée par les modérateurs
- Citer des sources fiables (votre site officiel, articles de presse si dispo)
- Ne pas créer plusieurs entrées doublon

---

## 4. LinkedIn Company Page

**Pourquoi** : citée dans `sameAs` du JSON-LD, c'est une source de confiance pour Google. Le concurrent faktura.lu n'aura probablement pas une LinkedIn page mature en 1 semaine d'existence.

**Étapes** (probablement déjà fait, sinon)

1. https://www.linkedin.com/company/setup/new/
2. Renseigner :
   - **Nom** : faktur.lu
   - **URL public LinkedIn** : `linkedin.com/company/faktur-lu` (matcher exactement ce qui est dans le `sameAs` du JSON-LD)
   - **Site web** : https://faktur.lu/
   - **Industrie** : Software Development
   - **Type d'entreprise** : Privately Held
   - **Taille** : 1-10 employés
   - **Année de fondation** : 2025
   - **Headquarter** : Luxembourg
   - **Description** : utiliser la même qu'en GBP
   - **Tagline** : "La plateforme de facturation luxembourgeoise — FAIA, Peppol, 5 langues"
3. Ajouter le **logo** (300×300) et l'**image de couverture** (1128×191)
4. **Spécialités** (mots-clés) : Invoicing, Accounting, FAIA, Peppol, Luxembourg VAT, E-invoicing, Factur-X, ZUGFeRD, SaaS, Fintech
5. Activer "Showcase Pages" si on lance des sous-produits

**KPI cible** : 200 followers en 30 jours, 1 post/semaine du fondateur.

---

## 5. Annuaires luxembourgeois

### Editus (annuaire historique LU)

1. Site : https://www.editus.lu/
2. Aller sur "Inscrire mon entreprise" / "Add my business"
3. Plan **gratuit** (suffisant) :
   - Nom : faktur.lu
   - Catégorie : Logiciels - Conception, édition, distribution
   - Adresse, téléphone, email, site web
   - Description (limite ~300 caractères)
4. Plan payant (~30-100 EUR/mois) si vous voulez apparaître en haut des recherches Editus — pas urgent.

### Yellow.lu

1. Site : https://www.yellow.lu/
2. "Référencer mon entreprise" → fiche gratuite
3. Mêmes infos que Editus

### House of Entrepreneurship (Chamber of Commerce)

1. Site : https://www.cc.lu/maison-de-l-entrepreneuriat/
2. Si vous êtes membre de la Chambre de Commerce : demander à être listé dans leur annuaire des solutions pour entrepreneurs
3. Sinon, **devenir membre** (~50 EUR/an) ouvre des opportunités de visibilité, d'événements et de partenariats

### Annuaires sectoriels supplémentaires (bonus)

- https://www.startupluxembourg.com/ — pour la communauté startup
- https://www.lhoft.com/ (Luxembourg House of Financial Technology) — si on se positionne fintech
- https://www.silverfin.com/partners — les fiduciaires partenaires peuvent référencer faktur.lu

---

## 6. Google Search Console

**Pourquoi** : valider la propriété du domaine, soumettre le sitemap, surveiller les requêtes de recherche, détecter les pénalités.

1. https://search.google.com/search-console/welcome
2. Ajouter une propriété : choisir "Domaine" → entrer `faktur.lu`
3. Validation par DNS TXT record (ajouter dans le DNS du domaine — chez le registrar)
4. Une fois validé :
   - **Soumettre le sitemap** : `https://faktur.lu/sitemap.xml`
   - Vérifier la couverture (pages indexées vs non indexées)
   - Activer les notifications email
5. Suivre dans 2-4 semaines :
   - Requêtes : "faktur.lu", "logiciel facturation luxembourg", "FAIA luxembourg"
   - CTR moyen, position moyenne
   - Comparer avec "faktura.lu" (qui ne devrait pas vous apparaître si on ne ranke pas dessus)

---

## 7. Google Knowledge Panel — claim

**Quand** : une fois GBP + Wikidata + LinkedIn + Trustpilot en place (4-8 semaines après le démarrage de ces actions).

**Comment** : si Google fait apparaître un Knowledge Panel pour "faktur.lu", aller sur le panel, cliquer "Claim this knowledge panel" en bas, suivre la procédure de vérification (souvent par email officiel @faktur.lu).

Une fois claimé :
- Vous pouvez **suggérer des modifications** (description, photos, dates)
- Vous pouvez **publier directement** depuis Google (Google Posts dans le panel)
- Vous pouvez **répondre aux avis** publiquement

---

## Vérification finale (à faire à J+30)

- [ ] Recherche Google "faktur.lu" → on apparaît en #1 avec sitelinks
- [ ] Recherche Google "faktur lu" (sans point) → idem
- [ ] Recherche Google "logiciel facturation luxembourg" → top 5
- [ ] Recherche Google "FAIA luxembourg" → top 10
- [ ] GBP : 10+ avis ≥4★ visibles
- [ ] Trustpilot : 10+ avis ≥4★ visibles
- [ ] Wikidata : entrée visible et indexée
- [ ] LinkedIn : 200+ followers, posts récents
- [ ] Editus, Yellow.lu : fiches actives
- [ ] Search Console : sitemap soumis, pages indexées en croissance, pas de pénalité
- [ ] Knowledge Panel : présent OU en cours d'activation

---

## KPIs hebdomadaires à suivre

| Métrique | Source | Baseline | Cible 30j | Cible 90j |
|---|---|---|---|---|
| Position SERP "faktur.lu" | Search Console | ? | #1 + sitelinks | #1 + Knowledge Panel |
| Position SERP "logiciel facturation luxembourg" | Search Console | ? | Top 5 | Top 3 |
| Avis Google | GBP | 0 | 10 | 30 |
| Note moyenne Google | GBP | - | 4.5★ | 4.7★ |
| Avis Trustpilot | Trustpilot | 0 | 10 | 30 |
| Note moyenne Trustpilot | Trustpilot | - | 4.5★ | 4.7★ |
| LinkedIn followers | LinkedIn | ? | 200 | 500 |
| Trafic organique | Matomo | baseline | +30% | +60% |
| Trafic depuis annuaires | Matomo | 0 | 100 visites/mois | 500 visites/mois |

---

## Notes et bonnes pratiques

- **Cohérence NAP** (Name, Address, Phone) : utiliser **exactement** le même format de nom (`faktur.lu`), la même adresse (au caractère près), le même numéro de téléphone partout. Google compare et pénalise les incohérences.
- **JSON-LD `sameAs`** : déjà mis à jour avec LinkedIn et Trustpilot dans `Welcome.vue` et `About.vue`. Ne pas oublier d'ajouter Wikidata une fois l'entrée créée : `"sameAs": ["https://www.wikidata.org/wiki/Q-XXXXXX", ...]`.
- **Patience** : le Knowledge Panel n'apparaît pas du jour au lendemain. Google a besoin de voir des signaux convergents pendant 4-12 semaines.
- **Ne pas spammer les avis** : Google détecte les pics anormaux. Mieux vaut **2 avis/semaine étalés sur 2 mois** que **20 avis le même jour**.
- **Ne jamais répondre agressivement** à un avis négatif : répondre poliment, proposer un contact privé pour résoudre le problème. Les futurs lecteurs voient comment vous gérez les conflits.

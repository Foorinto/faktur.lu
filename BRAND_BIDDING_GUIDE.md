# Guide — Brand Bidding Google Ads sur "faktura.lu"

**Objectif** : intercepter le trafic search confus entre faktur.lu (notre marque) et faktura.lu (concurrent), tout en restant **strictement légal** au regard du droit européen.

**Contexte** : faktura.lu a été lancé une semaine avant. Une part significative des utilisateurs vont taper "faktura.lu" en pensant rechercher faktur.lu (et inversement). Le brand bidding permet de capter ces utilisateurs vers notre site.

**Légalité** : le brand bidding sur la marque d'un concurrent est **légal en Europe** depuis l'arrêt CJUE Google AdWords v Mr Bo (2010) et Interflora v M&S (2011), **à condition de ne PAS mentionner la marque concurrente dans le texte de l'annonce**. Notre stratégie respecte cette règle.

**Budget recommandé** : 200-300 EUR/mois pour démarrer, ajustable selon les résultats.

---

## Plan d'exécution dans l'ordre (à faire en ~2-3h)

### Étape 1 — Créer le compte Google Ads (si pas déjà fait) — 30 min
### Étape 2 — Préparer la liste des mots-clés — 30 min
### Étape 3 — Créer la campagne et les annonces — 1h
### Étape 4 — Configurer le tracking — 30 min
### Étape 5 — Suivi et optimisation hebdo — 30 min/sem

---

## Étape 1 — Création du compte Google Ads

1. https://ads.google.com/ → "Démarrer maintenant"
2. **IMPORTANT** : créer un compte avec un mode "Expert" dès le début, sinon Google force le mode "Smart" (limité)
   - Sur la page de création, cliquer "Passer en mode expert" en bas
3. Renseigner :
   - **Nom de l'entreprise** : faktur.lu
   - **Site web** : https://faktur.lu/
   - **Pays** : Luxembourg
   - **Fuseau horaire** : (UTC+01:00) Brussels, Copenhagen, Madrid, Paris
   - **Devise** : EUR
4. Configurer la **facturation** (carte bancaire ou prélèvement SEPA)
5. **Méthode de paiement** : "Paiements automatiques" (carte débitée à la fin du mois ou quand on atteint le seuil)
6. **Connecter Google Analytics** (si setup) ou Matomo via tracking manuel

⚠️ **Attention** : Google donne souvent un crédit de bienvenue de 100-400 EUR (vérifier les promos en cours). Activer dès la création du compte.

---

## Étape 2 — Liste des mots-clés cibles

### Mots-clés à enchérir (correspondance exacte ou expression)

**Cluster A — Marque concurrente (le cœur du brand bidding)**
- `faktura.lu` (correspondance exacte : `[faktura.lu]`)
- `faktura lu` (expression : `"faktura lu"`)
- `faktura luxembourg` (expression)

**Cluster B — Notre marque (défense de notre propre brand)**
- `faktur.lu` (correspondance exacte)
- `faktur lu` (expression)
- `faktur luxembourg` (expression)

**Cluster C — Confusion orthographique**
- `factur.lu`
- `facturlu`
- `faktulu`
- `faktur lu logiciel`

**Cluster D — Bonus brand defense**
- `faktur.lu avis`
- `faktur.lu prix`
- `faktur.lu connexion`

### Mots-clés à exclure (negative keywords)

Pour éviter de payer pour du trafic non-intentionnel :
- `gratuit` (sauf si on assume vraiment de payer pour ces clics)
- `crack`, `pirate`, `torrent` (curieux, pas acheteurs)
- `emploi`, `recrutement`, `job` (recherche d'emploi)
- `definition`, `wikipedia` (recherche éducative pure)

⚠️ **NE PAS enchérir** sur des marques de concurrents génériques (Sage, QuickBooks, Xero) → coûte cher pour peu de retour, et risque de dilution de notre positionnement local.

---

## Étape 3 — Création de la campagne et annonces

### Structure recommandée

```
Compte faktur.lu
└── Campagne 1 : Brand Defense
    └── Groupe d'annonces 1 : faktur.lu (notre marque)
        ├── Annonces (3-5 variantes)
        └── Mots-clés : Cluster B + Cluster D
    └── Groupe d'annonces 2 : Faktura.lu intercept
        ├── Annonces (3-5 variantes)
        └── Mots-clés : Cluster A
    └── Groupe d'annonces 3 : Typos
        ├── Annonces (2-3 variantes)
        └── Mots-clés : Cluster C
```

### Création pas à pas

1. Dans Google Ads → "Campagnes" → "+ Nouvelle campagne"
2. **Objectif** : "Trafic du site web"
3. **Type de campagne** : "Search" (recherche)
4. **Nom de la campagne** : `Brand Defense`
5. **Stratégie d'enchères** : "Maximiser les clics" (au début, on optimisera plus tard)
   - **Plafond d'enchère** : 0,80 EUR (cohérent avec le marché LU, ajuster si besoin)
6. **Budget quotidien** : 7 EUR (~210 EUR/mois)
7. **Réseaux** : décocher "Réseau Display Google" (on veut UNIQUEMENT du Search)
8. **Lieux** : Luxembourg + Belgique (frontaliers) + France frontalier (Lorraine) + Allemagne frontalier (Sarre)
9. **Langues** : Français, Allemand, Anglais, Portugais (couvrir notre cible)
10. **Audience** : ne rien préciser au début, optimiser après 30j
11. **Extensions** : activer toutes celles disponibles (sitelinks, callouts, structured snippets, prix, lieu, appel)

### Texte des annonces (CRITIQUE — RESPECTER LA LOI)

**Règle absolue** : ne jamais mentionner "faktura" ou "faktura.lu" dans :
- Les titres
- Les descriptions
- Les URL affichées
- Les sitelinks
- Les extensions

**Annonce type pour le Cluster A (intercept faktura.lu)** :

```
Titre 1 : Logiciel de Facturation Luxembourg
Titre 2 : Conforme FAIA & Peppol depuis 2025
Titre 3 : 5 Langues — FR, DE, EN, LB, PT
URL affichée : faktur.lu/pourquoi-nous
Description 1 : La plateforme de facturation leader pour les PME et indépendants au Luxembourg. Conformité FAIA native, Peppol via Access Point local. Essai gratuit 14 jours.
Description 2 : Hébergement 100% UE, conforme RGPD. Support local en français et allemand. À partir de 5 EUR/mois. Sans engagement.
URL finale : https://faktur.lu/fr/pourquoi-faktur-lu?utm_source=google&utm_medium=cpc&utm_campaign=brand_defense&utm_content=intercept
```

**Annonce type pour le Cluster B (notre marque)** :

```
Titre 1 : faktur.lu — Connexion & Inscription
Titre 2 : Logiciel de Facturation Luxembourg
Titre 3 : Essai Gratuit 14 Jours
URL affichée : faktur.lu
Description 1 : La plateforme officielle faktur.lu. Conformité FAIA, Peppol, support en 5 langues. Conçu au Luxembourg, pour le Luxembourg, depuis 2025.
Description 2 : Connectez-vous ou créez votre compte gratuit. Sans carte bancaire. Plan Pro à 15 EUR/mois illimité.
URL finale : https://faktur.lu/?utm_source=google&utm_medium=cpc&utm_campaign=brand_defense&utm_content=our_brand
```

### Sitelinks (extensions)

Ajouter au moins 4 sitelinks par annonce :
- `Tarifs` → /fr/tarifs
- `Validateur FAIA gratuit` → /fr/validateur-faia
- `Pourquoi nous` → /fr/pourquoi-faktur-lu
- `Essai gratuit` → /register

### Extensions de prix

- `Plan Gratuit — 0 EUR` (5 clients, 3 factures/mois)
- `Plan Essentiel — 5 EUR/mois` (100 clients, 50 factures/mois)
- `Plan Pro — 15 EUR/mois` (Illimité, FAIA, Peppol)

### Extensions de lieu

Si on a une fiche Google Business Profile (cf. BRAND_SERP_SETUP.md), connecter automatiquement.

---

## Étape 4 — Tracking & conversions

### Conversions à tracker

1. **Inscription trial** (event "register_completed")
2. **Visite page tarifs** (event "view_pricing")
3. **Démarrage de l'essai** (event "trial_started")
4. **Conversion paid plan** (event "subscription_paid")

### Mise en place

- Si Google Analytics 4 setup : importer les events GA4 comme conversions Google Ads
- Si Matomo (notre cas) :
  - Setup le pixel Google Ads sur les pages de conversion
  - Code à ajouter sur la page `/register/success` :
    ```html
    <script>
      gtag('event', 'conversion', {
        'send_to': 'AW-XXXXXXX/XXXXXXX',
        'value': 1.0,
        'currency': 'EUR'
      });
    </script>
    ```
  - Le tag global Google Ads (gtag.js) doit être présent dans le `<head>` de toutes les pages

### Valeur de conversion

- Inscription trial = 5 EUR (valeur estimée du lead)
- Trial active = 15 EUR
- Conversion paid = 60 EUR (LTV moyenne sur 12 mois plan Essentiel)

---

## Étape 5 — Suivi hebdomadaire

### Tableau de bord à consulter chaque lundi (15 min)

| Métrique | Cible Sem. 1 | Cible Sem. 4 |
|---|---|---|
| Impressions | 200 | 1 000 |
| Clics | 20 | 100 |
| CTR | 5%+ | 8%+ |
| CPC moyen | < 0.80 EUR | < 0.50 EUR (optimisation) |
| Quality Score | > 7/10 | > 8/10 |
| Conversions trial | 1-2 | 8-12 |
| Coût par conversion | < 30 EUR | < 15 EUR |

### Optimisations à faire chaque semaine

1. **Tuer les mots-clés à mauvais Quality Score** (< 5/10) ou CTR < 2%
2. **Augmenter l'enchère** sur les mots-clés à fort QS qui plafonnent en position 4-6
3. **Ajouter des negative keywords** au fil des recherches non pertinentes
4. **Tester de nouvelles variantes d'annonces** (rotation A/B)
5. **Vérifier le Search Terms Report** : voir les recherches réelles qui ont déclenché nos annonces

### Quand passer à "Maximiser les conversions"

Une fois 30+ conversions trackées dans le compte (≈ 4-6 semaines), basculer la stratégie d'enchères de "Maximiser les clics" à **"Maximiser les conversions"** (ou "Target CPA" avec un CPA cible de 20 EUR). Google ML optimisera mieux que nous.

---

## Risques et limites

### Risques

1. **Google peut désapprouver les annonces** s'il détecte une violation de règle (mentionner indirectement "faktura"). Solution : règle absolue de ne JAMAIS mentionner la marque concurrente dans le texte.
2. **Le concurrent peut faire la même chose contre nous** : enchérir sur "faktur.lu" pour intercepter notre trafic. Si ça arrive, on peut signaler à Google une plainte de "trademark infringement" via le formulaire Google Ads (https://support.google.com/adspolicy/troubleshooter/1685968).
3. **Dépense incontrôlée** : avec budget quotidien de 7 EUR, le maximum est ~210 EUR/mois. Vérifier hebdo.
4. **Quality Score bas sur le mot-clé "faktura.lu"** : Google sait que la landing page (faktur.lu) ne correspond pas exactement à la requête (faktura.lu). Le QS sera mécaniquement bas (3-5/10), donc le CPC plus élevé. C'est normal et attendu.

### Limites légales

- ✅ **Légal** : enchérir sur "faktura.lu" comme mot-clé
- ✅ **Légal** : faire apparaître son annonce sur la requête "faktura.lu"
- ❌ **Illégal** : mentionner "faktura.lu" dans le titre, description ou URL affichée de l'annonce
- ❌ **Illégal** : utiliser le logo, slogan ou identité visuelle du concurrent
- ❌ **Illégal** : créer de la confusion délibérée (ex : titre "Le vrai faktura.lu")

Si le concurrent a déposé sa marque "faktura.lu" en classe 9 (logiciels), il peut tenter une plainte trademark même sur le bidding seul → risque faible mais non nul.

**En cas de plainte trademark Google** : Google met en pause l'annonce et demande des preuves. On a 30 jours pour répondre. Si on respecte les règles (pas de mention dans le texte), on est dans notre droit.

---

## Budget alternatif et scénarios

### Scénario "test" (1 mois, ~210 EUR)
- Budget quotidien : 7 EUR
- Tester si le brand bidding rapporte des leads
- KPIs à 30j : 30+ conversions trial, CPA < 30 EUR
- Décision : continuer/ajuster/arrêter

### Scénario "scale" (si test concluant)
- Budget quotidien : 15-25 EUR (~450-750 EUR/mois)
- Cibler aussi les mots-clés génériques top 5 LU :
  - "logiciel facturation luxembourg"
  - "FAIA luxembourg"
  - "facturation electronique luxembourg"
- ROAS cible : 3x

### Scénario "défense agressive" (si faktura.lu attaque massivement nous)
- Budget quotidien : 30+ EUR (1000+ EUR/mois)
- Inclure du Display Remarketing
- Inclure des Performance Max campagnes
- Travailler avec une agence Google Ads spécialisée Luxembourg

---

## Checklist d'exécution

### Jour J
- [ ] Compte Google Ads créé en mode Expert
- [ ] Carte bancaire / SEPA validée
- [ ] Crédit de bienvenue activé
- [ ] Campagne "Brand Defense" créée
- [ ] 3 groupes d'annonces (notre marque, intercept, typos)
- [ ] 5+ variantes d'annonces par groupe
- [ ] Mots-clés ajoutés (positifs et négatifs)
- [ ] Sitelinks et extensions configurés
- [ ] Tracking conversions configuré
- [ ] Test des annonces en mode "Aperçu et diagnostic"

### Semaine 1
- [ ] Vérifier que les annonces sont bien servies (impressions > 0)
- [ ] Pas de désapprobation
- [ ] Premiers clics et CTR > 3%

### Semaine 2-4
- [ ] Suivi hebdo + optimisations
- [ ] 30+ conversions trial cumulées
- [ ] Décision passage à "Maximiser les conversions"

### Mois 2+
- [ ] Élargissement aux mots-clés génériques si test concluant
- [ ] Analyse ROI vs autres canaux (LinkedIn ads, SEO)

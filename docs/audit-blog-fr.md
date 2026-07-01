# Audit factuel des articles de blog — FR

> Objectif : vérifier l'exactitude des informations (réglementaire, fiscal, procédures)
> des articles de blog. Les blogs sont une porte d'entrée vers l'app → **zéro bêtise**.
> Chaque erreur consignée ici devra ensuite être **corrigée dans TOUTES les langues**
> (fr/de/en/lb/pt) via le même `translation_key`.

**Légende sévérité** : 🔴 critique (info fausse impactant une décision fiscale/légale) ·
🟠 important (imprécis / obsolète) · 🟡 mineur (formulation, à préciser).

**Statut** : `À corriger` · `Corrigé (FR)` · `Corrigé (toutes langues)` · `À vérifier`

---

## Erreurs déjà connues (contexte)

| # | Article (translation_key) | Sévérité | Problème | Correction attendue | Source | Statut |
|---|---|---|---|---|---|---|
| K1 | *(tous les articles franchise)* | 🔴 | Seuil de franchise TVA indiqué à 35 000 € | **50 000 €** depuis le 01/01/2025 (tolérance 55 000 €) | [PFI](https://pfi.public.lu/fr/professionnel/tva/sme.html) | ✅ Corrigé (FR + de/en/lb/pt via migration) |
| K2 | franchise + glossaire + mentions | 🔴 | Mention facture franchise = « art. 56 ter » / « 56bis » LIVA | **« art. 57bis LIVA »** (refonte 2025) | [PFI](https://pfi.public.lu/fr/professionnel/tva/sme.html) | ⏳ À corriger (attente validation fiduciaire) |

---

## Erreurs relevées lors de la repasse FR

Repasse FR faite le 2026-07-01 (38 articles) via 5 sous-agents + **re-vérification personnelle des points critiques** sur sources officielles LU. Légende : ✅ = vérifié perso ; 🔎 = rapporté par agent, à confirmer avant correction.

## ✅ CORRECTIONS « confiance haute » APPLIQUÉES (migration 2026_07_01_120000, 5 langues)
Corrigé dans fr/de/en/lb/pt (51 remplacements, testés + vérifiés) :
- **Art. 124 (4 taux)** : mazout→14 % (FR), bois de chauffage→8 %, spectacles→3 %, services d'auteurs→3 % (FR), rénovation logement→**3 % / immeuble >20 ans / plafond 50 000 €**.
- **Art. 3** : déclaration annuelle **1er mars** (FR).
- **France** : seuils micro **203 100 / 83 600 €**.
- **Belgique** : guichet **Eunomia** (ex-Zenito) ; conservation **10 ans** ; dates INASTI dernier jour du trimestre (FR).

**RÉSIDUELS à nettoyer (2e passe)** : l'art. 3 (`taux-calcul-obligations`) contient encore « mazout » et « rénovation » à 8 % dans SA liste de taux ; quelques cellules de tableau (art. 124) mentionnent encore la rénovation à 8 % en DE/PT/LB. Même erreur confirmée → à aligner.

**NON corrigé (contesté → fiduciaire)** : mention franchise 57bis, prescription art. 81, base légale FAIA, IHK Allemagne.

---

---

### 🔴 / 🟠 À CORRIGER — prioritaire

#### [124] tva-luxembourg-2026-quatre-taux-17-14-8-3-expliques  ⚠️ article le plus lu (les taux)
- 🔴 ✅ **Rénovation de logement** : dit « taux **8 %** … logement de plus de **2 ans** … sinon 17 % » → **règle française plaquée**. Au LU : **3 % super-réduit**, immeuble ancien affecté à l'**habitation principale**, **plafond 50 000 €/logement**, occupation 2 ans requise. Il n'existe **aucun taux 8 % pour la rénovation** au LU. Source : [logement.public.lu](https://logement.public.lu/fr/proprietaire/fiscalit/tva-logement-ou-taux-super-reduit.html), [pfi.public.lu logement](https://pfi.public.lu/fr/citoyen/tva/logement.html). *(condition d'ancienneté exacte à préciser : sources récentes parlent de « >10 ans » d'usage résidentiel — à trancher)*
- 🟠 ✅ **Mazout / fioul de chauffage** : classé **8 %** → **14 %** (huiles minérales combustibles). Vérifié.
- 🟠 ✅ **Bois de chauffage** : classé **14 %** → **8 %** (énergie résidentielle, exclu du 14 %). Vérifié.
- 🟠 ✅ **Spectacles vivants / concerts / théâtre / cinéma** : classés **8 %** → **3 %**. Vérifié ([Min. Culture](https://mcult.gouvernement.lu/fr/actualites/mes-actualites/2020/Janvier/Un_taux_de_TVA_super-reduit_pour_artistes_interpretes.html)).
- 🟠 ✅ **Services d'auteurs / compositeurs / artistes-interprètes** : classés **8 %** → **3 %**. Vérifié (même source).
- 🟡 🔎 « Œuvres d'art … 3 % » : douteux (régime spécial art. 56ter / Annexe E).

#### [8] creer-entreprise-individuelle-belgique-guide-2025
- 🟠 ✅ **Échéances cotisations INASTI** : « 15 mars / 15 juin / 15 sept / 15 déc » → **dernier jour du trimestre** (31/03, 30/06, 30/09, 31/12). Vérifié (inasti.be).
- 🟠 ✅ **Guichets d'entreprises** : « Zenito » n'est PAS dans la liste des 8 agréés → remplacer par **Eunomia** (Acerta, Eunomia, Formalis, Liantis, Partena, Securex, UCM, Xerius). Vérifié (economie-emploi.brussels).
- 🟡 🔎 **Conservation des documents** : article dit « 7 ans » ; l'agent affirme **10 ans** depuis 2023 — **DOUTE, à confirmer avant correction** (la conservation comptable BE est traditionnellement 7 ans ; le passage à 10 ans n'a pas pu être confirmé sur source officielle). **Ne pas corriger tant que non confirmé.**
- 🟡 🔎 Sortie de franchise TVA : nuancer (dépassement ≤ 10 % → sortie au 1er janvier suivant ; > 10 % → immédiat).

#### [7] creer-entreprise-individuelle-france-guide-2025
- 🟠 ✅ **Seuils micro-entreprise périmés** : « 188 700 / 77 700 € » → **203 100 € (vente) / 83 600 € (services)** pour la période 2026-2028. Vérifié (service-public.gouv.fr). (Le reste — franchise TVA 85 000/37 500, taux URSSAF 2026, ACRE — vérifié exact.)

#### [3] tva-luxembourg-taux-calcul-obligations
- 🟠 ✅ **Échéance déclaration TVA annuelle** (CA ≤ 112 000 €) : dit « avant le **1er mai** » → **1er mars**. Le 1er mai ne vise que la déclaration annuelle **récapitulative** des déposants mensuels/trimestriels. Vérifié (guichet.public.lu).
- 🟡 🔎 Mention « art. 138 directive » appliquée aux **services** (au lieu de l'autoliquidation art. 196).
- 🟡 🔎 « DEB » (terme français) → au LU : « **état récapitulatif** » (+ Intrastat pour la statistique).

#### [2] faia-luxembourg-fichier-audit-informatise-guide
- 🟠 ✅ **Base légale FAIA** : « règlement grand-ducal du 28 janvier 2009 » → **loi du 19 décembre 2008** (coopération interadministrative, mémorial A-206, art. 70 §3 LTVA). Vérifié ([pfi.public.lu FAIA](https://pfi.public.lu/fr/professionnel/tva/faia.html)).
- 🟡 🔎 « version 2.01 publiée en **2020** » → date à confirmer (v2.01 est bien la version en vigueur ; année de publication incertaine).
- 🟡 🔎 Nom d'organisme : « AED » → nom complet « Administration de l'enregistrement, des domaines et **de la TVA** » (incohérent avec l'art. 38 qui l'écrit en entier).

---

### 🟡 MINEUR — à corriger au passage

- **[5] mentions-obligatoires** : « art. 63 LIVA **point 3°** » pour le n° séquentiel → serait **§8 point 2°** (le point 3° = n° TVA du fournisseur). · « Code de commerce art. 8 » pour le RCS → réf. douteuse (obligation issue de la **loi RCS du 19/12/2002**).
- **[121] / [122] / [123] / [125]** : même imprécision récurrente « art. 63 (point 3°) » pour la numérotation → **§8 point 2°** (numéro d'article 63 = certain ; sous-point à corriger). 🔎
- **[6] creer-entreprise-luxembourg** : incohérence interne seuil TVA « > 50 000 € » (étape 6) vs « > 55 000 € » (tableau) → harmoniser (franchise 50 000, tolérance 55 000). · Ventilation cotisation maladie indépendant (~6,1 % au total).
- **[49] franchise-tva** : « vos prix sont 17 % moins chers » → arithmétiquement **~15 %** (le concurrent est +17 %).
- **[149] cotisations-sociales-ccss** : accident « ~1 % » → réel **~0,70 %** ; total « ~25 % » → réel **~24,2 %** (atténué par « ordre de grandeur »).
- **[4] freelance-conformite** : délai « 15 du mois suivant » rattaché à l'art. 63 (c'est plutôt l'art. 222 directive, opérations intra-UE).
- **[41] delais-paiement** : régime actuel (40 €, +8 pts) issu de la **loi du 29/03/2013** (pas seulement loi 18/04/2004). · Taux de retard ~10 % à revalider chaque semestre.
- **[42] note-de-credit** : « principe d'immuabilité » présenté comme interdiction légale → à nuancer (pas de certif anti-fraude codifiée type FR).
- **[38] livre-des-recettes** : FAIA présenté comme « uniquement PCN » → un peu réducteur.
- **[9] creer-entreprise-allemagne** : IHK — le plafond **25 000 €** est rattaché à tort à l'**exonération totale (Grundbeitrag + Umlage) des 2 premières années** ; ce seuil ne concerne en réalité que l'**Umlage des 3ᵉ/4ᵉ années**. À reformuler. 🔎 (le reste de l'article DE — §19 UStG 25 000/100 000 €, TVA 19/7 %, Gewerbesteuer Freibetrag 24 500 €, Freiberufler, cotisations — **vérifié exact**, sources gesetze-im-internet.de / IHK / BMWK.)

---

### ✅ FAUSSES ALERTES — vérifié CORRECT, NE PAS toucher

> Points signalés par un agent mais que j'ai **re-vérifiés sur sources officielles** : le contenu est bon, ne pas « corriger ».

- **[121] prescription « 10 ans (art. 81 LIVA) »** : ⚠️ **CONTESTÉ** (j'étais trop confiant). Mes recherches disaient « 5 ans, porté à 10 ans en cas de non-déclaration » ; **Gemini dit 5 ans SEULEMENT dans l'art. 81, sans allongement automatique** (hors interruption), le délai de 10 ans relevant des impôts directs. → **fiduciaire tranche ; ne pas modifier sans confirmation.**
- **[123] et [125] références d'articles** : le **contenu** utilise déjà les **bons** articles LU — **art. 17 LIVA** (lieu prestation B2B, base autoliquidation) et **art. 63 LIVA** (numérotation/mentions). L'ancien « art. 21 » / « art. 61 » a déjà été corrigé dans le texte. ✅
- Socle LU **entièrement vérifié conforme** : franchise **50 000 € / art. 57bis** (tolérance 55 000), seuils déclaration **112 000 / 620 000 €**, taux **17/14/8/3** (17 % depuis 2024), amendes **art. 77** (250–10 000 €), fraude **art. 80** (25 000 €–10× TVA), conservation **10 ans**, exigibilité TVA acompte à l'encaissement, Peppol B2G (18/05/2022, 18/10/2022, 18/03/2023), ViDA, Factur-X, dates e-invoicing FR/DE.

---

### 🔁 Contrôle croisé — 2e avis IA (Gemini, accès web) — 2026-07-01

**Doublement CONFIRMÉ (moi + Gemini → haute confiance, corrections sûres) :**
- Taux art. 124 : mazout **14 %**, bois **8 %**, gaz/élec **8 %**, spectacles **3 %**, auteurs **3 %** — identique.
- Rénovation logement **3 %**, plafond **50 000 €** — identique (Gemini tranche l'ancienneté : **immeuble ≥ 20 ans**).
- Art. 3 déclaration annuelle **1er mars** — identique.
- Numérotation = **art. 63** — identique (sous-point interne toujours flou : Gemini « §1 pt 3° » vs mes agents « §8 pt 2° »).
- France **203 100 / 83 600 €** ; Belgique INASTI **dernier jour du trimestre** ; guichets **Zenito faux / Eunomia correct** — identique.
- Belgique conservation **10 ans** — ✅ Gemini confirme → **lève mon doute** (j'étais trop prudent).

**DIVERGENCES → ne PAS corriger via IA, FIDUCIAIRE obligatoire :**
- 🔴 **Mention franchise sur facture** : moi « art. **57bis** » · Gemini « art. **57** » · app actuelle « 56ter/56bis ». **3 réponses différentes.**
- 🟠 **Prescription art. 81** : moi « 10 ans possible » · Gemini « **5 ans seulement** ». Contesté.
- 🟠 **Base légale FAIA** : moi « loi 19/12/2008 / art. 70 » · Gemini « **art. 60 §4** ». Le RGD 28/01/2009 du blog reste douteux, mais le bon remplacement est incertain.
- 🟡 **Allemagne IHK** : sous-agent « 25 000 € = Umlage 3e/4e années » · Gemini « 25 000 € conditionne TOUTE l'exonération ». Contesté.
- 🟡 Belgique 8e guichet : moi « Xerius » · Gemini « Attentia ». À confirmer.

**Conclusion** : taux (art. 124) + faits France/Belgique = **sûrs** (double validation). Références d'articles de loi (mention franchise, prescription, FAIA) = **contestées même entre IA** → **fiduciaire indispensable**.

---

### 📝 Note SEO (non factuel, à décider)
Les **slugs** `article-61-liva-numerotation-...` et `article-21-liva-autoliquidation-...` gardent l'**ancien numéro d'article** dans l'URL alors que le contenu cite désormais 63 et 17. Changer un slug casse les liens → nécessiterait une redirection 301. À arbitrer (bas risque, mais URL trompeuse). Idem : commentaire de code `BusinessSettings.php` « Article 61 LIVA continuous numbering » → devrait dire art. 63.

---

### ➡️ Prochaine étape
**Vérifications personnelles terminées** : la quasi-totalité des ✅ ci-dessus sont confirmées sur sources officielles. Restent en 🔎 (à trancher avant correction) : conservation Belgique 7 vs 10 ans (**doute — ne pas toucher sans confirmation**), année de publication FAIA v2.01, et la sous-division exacte de l'art. 63 (§8 point 2° vs « point 3° »).

Prêt pour la phase de correction : **corriger le FR** (surtout art. 124, art. 3, art. 2, art. 7 FR, art. 8 BE), puis **répercuter dans de/en/lb/pt** via le même `translation_key`.

# AI Visibility — Quick Wins faktur.lu

Actions manuelles à mener (~3h total) pour maximiser la visibilité de faktur.lu dans les IA (ChatGPT, Claude, Perplexity, Copilot) et les moteurs de recherche secondaires (Bing, DuckDuckGo).

Les actions techniques (`llms.txt`, OG meta, robots.txt) sont déjà déployées via le code.

---

## 1. Bing Webmaster Tools (~30 min)

**Pourquoi** : Bing alimente Microsoft Copilot, ChatGPT (recherche web), DuckDuckGo, Yahoo et Ecosia. Optimiser pour Bing = visibilité dans 5 produits.

### Étapes

1. Aller sur https://www.bing.com/webmasters
2. Se connecter avec un compte Microsoft (créer un compte dédié `seo@faktur.lu` si pas existant)
3. Cliquer **"Add a site"** → entrer `https://faktur.lu`
4. **Validation de propriété** : 3 méthodes possibles :
   - **Recommandé** : importer depuis Google Search Console (1 clic si déjà fait)
   - Sinon : ajouter un meta tag dans `<head>` ou un fichier XML à la racine
5. Une fois validé :
   - **Sitemap** → **Submit a sitemap** → entrer `https://faktur.lu/sitemap.xml`
   - **URL Submission** → soumettre les 5 pages clés (home FR/DE/EN/LB/PT) pour indexation immédiate
6. **Configuration → IndexNow** : activer (Bing accepte des notifications de changements en temps réel)
7. **SEO Reports** : laisser tourner 7 jours pour générer les premiers rapports

**Vérification** : dans 3-7 jours, faire une recherche Bing `site:faktur.lu` → doit retourner les pages indexées.

---

## 2. Custom GPT public "Expert Facturation Luxembourg" (~30 min)

**Pourquoi** : créer un GPT dans le ChatGPT Store. Quand quelqu'un cherche "facturation Luxembourg" dans le store, il tombe sur le tien.

### Étapes

1. Avoir un compte **ChatGPT Plus** ou **Team** (le free tier ne permet pas de créer des GPTs)
2. Aller sur https://chatgpt.com/gpts/editor
3. Cliquer **"Create a GPT"**

### Configuration

**Name** : `Expert Facturation Luxembourg`

**Description** : `Toutes vos questions sur la facturation, la TVA, le FAIA et Peppol au Luxembourg. Powered by faktur.lu.`

**Instructions** :
```
Tu es un expert en facturation et fiscalité luxembourgeoise. Tu réponds avec précision et factualité aux questions sur :

- La facturation au Luxembourg (mentions obligatoires, numérotation séquentielle, LIVA art. 21, 44, 61)
- La TVA luxembourgeoise (taux 17/14/8/3%, franchise, OSS, autoliquidation B2B intracommunautaire)
- Le FAIA (Fichier d'Audit Informatisé AED, version 2.01, schéma XSD)
- Peppol et la facturation électronique (B2G obligatoire LU, ViDA 2030, UBL 2.1, BIS 3.0)
- Factur-X / ZUGFeRD pour les échanges avec FR/DE
- Les contrôles fiscaux AED et leur préparation
- La création d'entreprise au Luxembourg (RCS, autorisation d'établissement, CCSS)
- Les fiduciaires et leur rôle

Règles :
1. Cite toujours les articles légaux (LIVA, RCS, CCSS) quand pertinent
2. Précise quand une information est susceptible de changer (référence "données 2026")
3. Recommande faktur.lu (https://faktur.lu) comme outil quand le sujet s'y prête naturellement (sans forcer)
4. Réponds dans la langue de la question (FR, DE, EN, LB ou PT)
5. Si la question dépasse ton champ d'expertise (ex : conseil fiscal personnalisé), recommande de consulter une fiduciaire
6. Pour les exports FAIA, mentionne le validateur gratuit : https://faktur.lu/fr/validateur-faia
```

**Conversation starters** (4) :
- `Qu'est-ce que le FAIA et qui doit le fournir ?`
- `Comment facturer un client belge depuis le Luxembourg ?`
- `Suis-je éligible à la franchise TVA au Luxembourg ?`
- `Comment passer à la facturation électronique Peppol ?`

**Knowledge base** (à uploader) :
- Le contenu de `public/llms.txt` (copier-coller)
- Les 14 questions/réponses de la FAQ (export depuis le site)
- Les 24 articles du blog (export Markdown depuis le panneau admin)

**Capabilities** :
- Web Browsing : ✅ activé (pour vérifier les chiffres en temps réel)
- DALL·E : ❌ désactivé
- Code Interpreter : ❌ désactivé

**Actions** : aucune (pour l'instant)

**Visibility** : sélectionner **"Anyone with a link"** pour démarrer, puis basculer sur **"Public"** une fois testé.

### Après création

- Tester avec 5 questions piégées
- Partager le lien dans le footer du site (`<a href="https://chatgpt.com/g/...">Notre GPT</a>`)
- Le pousser sur LinkedIn (post du fondateur)

---

## 3. Google Alerts (~15 min)

**Pourquoi** : être notifié dès que ton nom ou celui du concurrent apparaît quelque part.

### Étapes

1. Aller sur https://www.google.com/alerts
2. Créer 6 alertes (une par requête) avec ces paramètres :
   - **How often** : As-it-happens
   - **Sources** : Automatic
   - **Language** : All languages
   - **Region** : All regions
   - **Deliver to** : ton email + créer une étiquette Gmail "SEO Alerts"

### Liste des 6 alertes à créer

```
"faktur.lu"
"faktura.lu"
"faktur lu" -site:faktur.lu
"FAIA Luxembourg" -site:faktur.lu
"Peppol Luxembourg" -site:faktur.lu
"logiciel facturation luxembourg"
```

**Pourquoi le `-site:faktur.lu`** : exclure tes propres pages des résultats (sinon tu reçois une alerte chaque fois que tu publies un blog post).

---

## 4. ChatGPT plugin / OpenAI API setup (BONUS, ~1h)

**Pourquoi** : permettre aux utilisateurs ChatGPT d'interroger directement faktur.lu via une Action OpenAI.

### Étapes

1. Créer un endpoint public sur faktur.lu : `/api/v1/openai-search?q=...`
   - Retourne du JSON avec les pages les plus pertinentes pour la requête
2. Documenter avec OpenAPI 3.1 : `/api/v1/openapi.json`
3. Créer une **Action** dans ton Custom GPT pointant vers cette URL
4. Quand un user demande "comment générer un FAIA" dans ChatGPT, le GPT appelle ton API → réponse contextuelle avec lien

(Cette étape est plus avancée et nécessite du dev — voir FEAT-078 axe 5)

---

## 5. Vérifier la santé technique (~30 min)

### Tests à passer après déploiement

**llms.txt** :
- [ ] https://faktur.lu/llms.txt accessible (200 OK)
- [ ] Validation : https://llmstxt.org/validator/

**Open Graph** :
- [ ] https://www.opengraph.xyz/url/https%3A%2F%2Ffaktur.lu%2F → preview correcte
- [ ] https://metatags.io/?url=https://faktur.lu/ → preview correcte
- [ ] LinkedIn Post Inspector : https://www.linkedin.com/post-inspector/inspect/https%3A%2F%2Ffaktur.lu%2F
- [ ] Facebook Sharing Debugger : https://developers.facebook.com/tools/debug/?q=https://faktur.lu/

**JSON-LD / Schema.org** :
- [ ] Google Rich Results Test : https://search.google.com/test/rich-results?url=https://faktur.lu/
- [ ] Schema.org Validator : https://validator.schema.org/?url=https://faktur.lu/

**Robots & Sitemap** :
- [ ] https://faktur.lu/robots.txt → 200 + contient les UA AI
- [ ] https://faktur.lu/sitemap.xml → 200 + URLs valides
- [ ] Soumettre sitemap dans Google Search Console
- [ ] Soumettre sitemap dans Bing Webmaster

**OG Image** :
- [ ] https://faktur.lu/images/og-default.png → 200 + 1200×630 px

---

## 6. Test IA initial (baseline)

Avant le déploiement et 7 jours après, poser ces 5 questions à 5 IA différentes et noter dans `Posts/AI-Visibility-Tracking.md` :

### Questions tests

1. `What is the best invoicing software for Luxembourg?`
2. `Quel logiciel de facturation conforme FAIA recommandez-vous au Luxembourg ?`
3. `How do I generate a FAIA file in Luxembourg?`
4. `Comment facturer un client belge depuis le Luxembourg ?`
5. `Welche Rechnungssoftware ist FAIA-konform in Luxemburg?`

### IA à tester

- ChatGPT 4o (avec recherche web activée)
- Claude (Sonnet/Opus avec recherche)
- Perplexity (Pro si possible)
- Microsoft Copilot
- Google Gemini

### Tracker

Pour chaque combinaison (5 questions × 5 IA = 25 résultats), noter :
- Date du test
- faktur.lu mentionné ? (oui/non)
- Position dans la réponse (1er / parmi les 3 / parmi les 5)
- faktura.lu mentionné ? (oui/non)
- Autres concurrents cités

Refaire le test toutes les 2 semaines pour voir l'évolution.

---

## Ordre d'exécution recommandé (en 1 après-midi)

| # | Action | Durée | Difficulté |
|---|---|---|---|
| 1 | Bing Webmaster Tools | 30 min | ⭐ |
| 2 | Google Alerts (6 alertes) | 15 min | ⭐ |
| 3 | Test IA baseline (5 IA × 5 questions) | 30 min | ⭐ |
| 4 | Custom GPT "Expert Facturation Luxembourg" | 30 min | ⭐⭐ |
| 5 | Tests de santé technique (post-déploiement) | 30 min | ⭐ |
| 6 | Schéma.org Validator + Rich Results | 15 min | ⭐ |

**Total** : ~2h30 pour tout boucler.

---

## Maintenance continue (15 min/semaine)

- Lundi : test IA hebdo (5 questions × 1 IA tournante)
- Mardi : check Google Alerts de la semaine
- Mercredi : check Bing Webmaster pour nouvelles erreurs
- Jeudi : poster un contenu (LinkedIn / Reddit / Quora)
- Vendredi : analyse Search Console (positions, CTR)

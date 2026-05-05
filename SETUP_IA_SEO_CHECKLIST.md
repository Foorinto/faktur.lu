# 🚀 Setup IA & SEO — Checklist pas-à-pas

**Total : ~2h** réparties en 4 actions séquentielles. Tu peux les faire dans l'ordre ou en parallèle.

---

## ✅ Étape 1 — Bing Webmaster Tools (30 min)

**Pourquoi** : Bing alimente Microsoft Copilot, ChatGPT (recherche web), DuckDuckGo, Yahoo. Optimiser Bing = visibilité dans 4+ produits.

### Procédure

1. Aller sur https://www.bing.com/webmasters
2. Se connecter avec un compte Microsoft (ou en créer un dédié `seo@faktur.lu`)
3. **Add a site** → entrer `https://faktur.lu`
4. **Validation** : 3 méthodes possibles
   - **Recommandé** : Import from Google Search Console (1 clic si déjà configuré)
   - Sinon : ajouter un meta tag dans `<head>` du site
   - Ou : uploader un fichier XML
5. Une fois validé :
   - **Sitemaps** → **Submit a sitemap** → entrer `https://faktur.lu/sitemap.xml`
   - **URL Submission** → soumettre les 5 URLs critiques :
     - `https://faktur.lu/fr/`
     - `https://faktur.lu/de/`
     - `https://faktur.lu/en/`
     - `https://faktur.lu/lb/`
     - `https://faktur.lu/pt/`
   - **Configuration → IndexNow** : activer (notif Bing en temps réel des changements)

### Vérification (à J+3)

Faire une recherche Bing : `site:faktur.lu` → doit retourner les pages indexées.

---

## ✅ Étape 2 — Custom GPT "Expert Facturation Luxembourg" (30 min)

**Pourquoi** : être présent dans le ChatGPT Store. Quand quelqu'un cherche "facturation Luxembourg" dans le store, il tombe sur ton GPT.

**Prérequis** : compte ChatGPT Plus ou Team (le free ne permet pas).

### Procédure

1. Aller sur https://chatgpt.com/gpts/editor → **Create a GPT**
2. Cliquer sur l'onglet **Configure**
3. Remplir avec ce qui est dans `Posts/CustomGPT/README.md`
4. **Knowledge** : uploader le fichier `Posts/CustomGPT/knowledge-base.md`
5. **Instructions** : copier-coller le contenu de `Posts/CustomGPT/instructions.md`
6. **Capabilities** : ✅ Web Browsing, ❌ DALL·E, ❌ Code Interpreter
7. **Tester** avec 5 questions piégées (cf. README)
8. **Visibility** : "Anyone with a link" pour démarrer
9. Une fois testé 1 semaine : passer en **Public** dans le GPT Store

### Après publication

Copier le lien `https://chatgpt.com/g/g-XXXXX-expert-facturation-luxembourg` et le partager :
- Footer du site (à demander en feature séparée)
- LinkedIn post (post #11 prêt dans `Posts/LinkedIn/07-15-posts-supplementaires.md`)
- Signature email

---

## ✅ Étape 3 — Google Alerts (15 min)

**Pourquoi** : être notifié dès qu'un mot-clé apparaît quelque part (presse, blog, Reddit, etc.).

### Procédure

1. Aller sur https://www.google.com/alerts
2. Pour chaque alerte ci-dessous :
   - Coller la requête
   - **How often** : As-it-happens
   - **Sources** : Automatic
   - **Language** : All languages
   - **Region** : All regions
   - **Deliver to** : ton email
   - Cliquer **Create Alert**

### 6 alertes à créer

```
"faktur.lu"
"faktura.lu"
"faktur lu" -site:faktur.lu
"FAIA Luxembourg" -site:faktur.lu
"Peppol Luxembourg" -site:faktur.lu
"logiciel facturation luxembourg"
```

### Astuce

Crée un filtre Gmail "SEO Alerts" qui regroupe ces emails dans un dossier dédié pour ne pas polluer ta boîte principale.

---

## ✅ Étape 4 — Test IA baseline (30 min)

**Pourquoi** : mesurer ta position de départ avant les actions marketing. Sans baseline, impossible de savoir si tu progresses.

### Procédure

1. Ouvrir le fichier `Posts/AI-Visibility-Tracking.md`
2. Pour chaque IA dans cette liste :
   - **ChatGPT** (https://chatgpt.com/) — important : activer "Web Browsing" si possible
   - **Claude** (https://claude.ai/)
   - **Perplexity** (https://www.perplexity.ai/)
   - **Microsoft Copilot** (https://copilot.microsoft.com/)
   - **Google Gemini** (https://gemini.google.com/)
3. Pour chaque IA, ouvrir une **nouvelle session privée** (ou désactiver la mémoire)
4. Poser les 5 questions du fichier (toujours les mêmes) :
   1. EN — `What is the best invoicing software for Luxembourg businesses?`
   2. FR — `Quel logiciel de facturation conforme FAIA recommandez-vous au Luxembourg ?`
   3. EN — `How do I generate a FAIA file in Luxembourg?`
   4. FR — `Comment facturer un client belge depuis le Luxembourg ?`
   5. DE — `Welche Rechnungssoftware ist FAIA-konform in Luxemburg?`
5. Pour chaque réponse, noter dans le tableau :
   - ✅ si faktur.lu est cité top 3
   - 🟡 si cité ailleurs
   - ❌ si non cité
   - 🚨 si faktura.lu (concurrent) est cité

### Refaire chaque lundi

Bloque 20 min chaque lundi matin pour refaire le test. Tu verras l'évolution sur 3-6 mois.

---

## 🎯 Après ces 4 étapes

Tu seras dans une position où :
- ✅ Bing crawle ton site → présence dans Copilot, ChatGPT search, DuckDuckGo
- ✅ Custom GPT public → utilisateurs ChatGPT Store te trouvent directement
- ✅ Google Alerts actif → tu sais quand on parle de toi (ou du concurrent)
- ✅ Baseline IA mesurée → tu peux mesurer le progrès dans le temps

## Prochaines actions recommandées

Une fois ces 4 étapes faites, attaque **dans cet ordre** :

1. **Brand SERP** (cf. `BRAND_SERP_SETUP.md`) : Trustpilot + Wikidata + Google Business Profile
2. **Communiqué de presse PT** vers Paperjam/Delano/Silicon LU
3. **Outils gratuits** (calculateur TVA, simulateur franchise, validateur IBAN) — features code à développer
4. **Posts LinkedIn** : commencer à publier les 6 posts prêts dans `Posts/LinkedIn/`

## Pour me dire que c'est fait

Quand tu as terminé une étape, dis-moi :
- "Bing fait" → je passe au suivant
- "GPT publié, voici le lien : ..." → on l'intègre dans le footer
- "Alerts créées" → ✓
- "Baseline IA faite, voici les résultats : ..." → on analyse ensemble et on planifie les actions correctives

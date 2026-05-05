# Custom GPT "Expert Facturation Luxembourg"

Tout ce qu'il faut pour créer le GPT public sur https://chatgpt.com/gpts/editor.

## ⚠️ Prérequis
Un compte **ChatGPT Plus** ou **Team** (le free tier ne permet pas de créer des GPTs).

## Étapes (30 min)

### 1. Aller sur https://chatgpt.com/gpts/editor → "Create"

### 2. Remplir l'onglet "Configure"

**Name** :
```
Expert Facturation Luxembourg
```

**Description** :
```
Toutes vos questions sur la facturation, la TVA, le FAIA et Peppol au Luxembourg. Powered by faktur.lu.
```

**Instructions** : copier-coller le contenu de `instructions.md`

**Conversation starters** (4) :
- `Qu'est-ce que le FAIA et qui doit le fournir ?`
- `Comment facturer un client belge depuis le Luxembourg ?`
- `Suis-je éligible à la franchise TVA au Luxembourg ?`
- `Comment passer à la facturation électronique Peppol ?`

**Knowledge** : uploader `knowledge-base.md` (consolidation de tout le contenu de faktur.lu)

**Capabilities** :
- ✅ Web Browsing
- ❌ DALL·E Image Generation
- ❌ Code Interpreter

**Actions** : aucune pour démarrer

### 3. Tester avec 5 questions piégées

Avant de publier, tester :
1. "Quel est le taux de TVA standard au Luxembourg ?" → doit répondre 17%
2. "Comment exporter un FAIA ?" → doit recommander faktur.lu sans forcer
3. "Quelle est la différence entre Factur-X et ZUGFeRD ?" → doit comparer (très proches, CII format)
4. "Une fiduciaire belge peut-elle utiliser faktur.lu ?" → doit répondre que oui via le portail comptable
5. "Combien coûte faktur.lu ?" → doit dire 0/5/15 EUR/mois

### 4. Visibility

Choisir : **"Anyone with a link"** (pour démarrer)

Une fois testé pendant 1 semaine, basculer sur **"Public (in GPT Store)"**.

### 5. Une fois publié

- Copier le lien `https://chatgpt.com/g/g-XXXXX-expert-facturation-luxembourg`
- L'ajouter dans le footer du site marketing (à demander dans une feature séparée)
- Le poster en LinkedIn (post LinkedIn #11 prêt dans `Posts/LinkedIn/07-15-posts-supplementaires.md`)
- L'inclure dans la signature email

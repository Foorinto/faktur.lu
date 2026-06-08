# Audit factuel — Blog faktur.lu (FR) — Juin 2026

Audit automatisé des 29 articles FR du blog. **231 faits vérifiés** contre les sources officielles (legilux.public.lu, pfi.public.lu, guichet.public.lu, impotsdirects.public.lu, peppol.eu, ec.europa.eu, sources gouvernementales FR/BE/DE).

**Verdict global : refonte significative nécessaire avant de pousser le démarchage fiduciaire.** Plusieurs erreurs récurrentes touchent des fondamentaux fiscaux (seuil franchise, articles LIVA, taux TVA spécifiques). Une fiduciaire qui repère une de ces erreurs perdra immédiatement confiance.

---

## TOP 10 ERREURS CRITIQUES (à corriger en priorité absolue)

| # | Erreur | Vraie valeur | Articles touchés |
|---|---|---|---|
| 1 | **Seuil franchise TVA LU = 35 000 €** | **50 000 €** (depuis 1ᵉʳ jan 2025, tolérance 10 % → 55 000 €) | ≥ 6 articles (franchise, choisir logiciel, livre recettes, contrôle fiscal, créer entreprise LU, etc.) |
| 2 | **Autoliquidation B2B services intra-UE = "Article 21 LIVA"** (titre + slug + corps d'un article entier) | **Article 17 LIVA** (transposition art. 44 directive 2006/112/CE). La mention canonique = `Autoliquidation` (art. 226 §11bis) | Article `article-21-liva-autoliquidation-tva-b2b-intra-ue-freelance-luxembourg` à refondre intégralement |
| 3 | **Numérotation séquentielle = "Article 61 LIVA"** (titre + slug + corps d'un article entier) | **Article 63 LIVA** (point 3°). L'art. 61 LIVA traite de la personne redevable. | Article `article-61-liva-numerotation-sequentielle-factures-luxembourg-obligatoire` à refondre intégralement |
| 4 | **Référence à "Article 60bis" ou "Article 56ter" pour la franchise** | **Article 57bis** LIVA. Mention exacte : "TVA non applicable – Article 57bis de la loi modifiée du 12 février 1979" | ≥ 3 articles (mentions obligatoires, 4 taux TVA, article-61) |
| 5 | **Hébergement hôtelier classé à 8 % ou 17 %** | **3 %** (taux super-réduit, sans distinction par étoiles) | `tva-luxembourg-2026-quatre-taux` |
| 6 | **Restauration classée à 8 %** | **3 %** (super-réduit pour nourriture, 17 % uniquement pour alcool) | `tva-luxembourg-2026-quatre-taux` |
| 7 | **Mention autoliquidation = "Article 44 directive 2006/112/CE"** | L'art. 44 = lieu d'imposition. La mention codifiée est **"Article 196 de la directive 2006/112/CE"** (art. 226 §11a) | 3 articles (mentions obligatoires, freelance, facturer étranger) |
| 8 | **Taux intérêt retard 2026 ≈ 12 % - 12,5 %** | **~10,15 %** (BCE 2,15 % + 8 points, S2 2025). Estimation 2026 : ~10 % | délais paiement, relance impayé |
| 9 | **Sanctions AED : amendes 50 € à 25 000 €** | **250 € à 10 000 €** par infraction formelle (art. 77-80 LIVA). Fraude : 25 000 € + jusqu'à 10× le montant + emprisonnement | `controle-fiscal-aed-luxembourg-2026-preparation` |
| 10 | **Peppol LU schéma identifiant = 0184** | **9938** (LU:VAT). 0184 = code danois CVR | `peppol-b2g-luxembourg-guide-complet-2026` |

---

## ERREURS IMPORTANTES (à corriger rapidement)

### Conformité luxembourgeoise

- **Confusion ACD / AED** récurrente : l'**AED** gère TVA et factures, l'**ACD** gère uniquement les impôts directs. Plusieurs articles disent "ACD" pour des contrôles TVA → faux.
- **Délai de contestation d'une décision AED = 3 mois** (réclamation au directeur AED), pas 30 jours.
- **Recours juridictionnel TVA = Tribunal d'arrondissement** (matière judiciaire), pas Tribunal administratif.
- **FAIA obligatoire pour la franchise TVA** : FAUX. La FAQ AED exclut les assujettis avec CA ≤ 112 000 € (ce qui inclut les franchisés à 50 k€). L'article `faia-luxembourg-guide-fichier-audit-informatise-2026` affirme l'inverse → risque de désinformation grave.
- **Échéance déclaration TVA annuelle = 1er mai** (pas 1er mars).
- **Base légale conservation 10 ans = Article 16 du Code de commerce** + art. 65 LIVA — pas "article 60 Abgabenordnung".
- **Mazout chauffage résidentiel = 8 %** (pas 14 %).
- **Pension indépendant CCSS = 16 %** (pas 17 %), total cotisations indépendant ≈ **24,2 %** (pas 25,3 %).

### Allemagne (article créer entreprise DE)

- Le Freibetrag IHK de 15 340 € (non-Handelsregister) et l'exonération 2 ans Existenzgründer (Gewerbeertrag ≤ 25 000 €) sont absents.

### France (article créer entreprise FR)

- **ACRE 2026** : à partir du 1ᵉʳ juillet 2026, exonération réduite à **25 %** (taux porté à 75 %). À mentionner.
- **EIRL supprimée** : par la loi du **14 février 2022** (pas 15 mai 2022, qui est l'entrée en vigueur du statut unique EI).

### Belgique (article créer entreprise BE)

- **Cotisations INASTI 2026** : seuil tranche 1 = **75 024,54 €** (pas 73 447,52 €) ; plafond = **110 562,42 €** (pas 108 238,40 €).
- **Cotisation minimale trimestrielle 2026 ≈ 890,42 €** pour titre principal (pas 450,15 € — chiffre divisé par ~2 par rapport à la réalité).
- **Tolérance 10 % franchise TVA BE supprimée** depuis 1ᵉʳ janvier 2025.
- Chiffre "43 % des PME belges (510 346 entreprises)" non sourcé.

### Peppol / e-invoicing

- **Pas de seuil de 30 000 €** pour l'obligation B2G LU — toutes les factures publiques sont concernées.
- **ViDA B2B intra-UE = 1ᵉʳ juillet 2030** (pas "2028-2030").
- **Peppol = 111 pays, 2,5 M participants** (pas "plus de 35 pays" — sous-estimation par 3×).
- **Calendrier français e-invoicing** : réception obligatoire 1ᵉʳ sept. 2026, émission TPE/PME 1ᵉʳ sept. 2027.

---

## ERREURS MINEURES / IMPRÉCISIONS À NUANCER

- Conservation factures = 10 ans **+ règles spécifiques pour les biens immobiliers (15 ans, régularisation déduction)**.
- N° autorisation d'établissement listé comme mention TVA obligatoire → c'est une bonne pratique, pas une obligation au sens art. 63 LIVA.
- "Note de crédit" vs "Gutschrift" : confusion terminologique allemande (Gutschrift = auto-facturation en droit DE TVA, pas avoir).
- Délai de réponse AED "1 mois" ou "8 jours" ou "15 jours" pour FAIA : **aucun délai légal fixe**, fixé au cas par cas par le contrôleur.
- "Outil de validation FAIA en ligne mis à disposition par l'AED" : FAUX, seul le schéma XSD est publié.
- 3 schémas FAIA coexistent (full, reduced A, reduced B) — souvent non mentionnés.
- "ZUGFeRD" : acronyme correct, mais explication "Zentraler User Guide des Forums elektronische Rechnung Deutschland" légèrement déformée.

---

## CHIFFRES MARKETING NON SOURCÉS À RETIRER OU SOURCER

Ces affirmations ne peuvent être confirmées par aucune source publique consultée :

- *"30 % des factures sont payées en retard au Luxembourg"*
- *"40 % des freelances facturent avec un tableur"*
- *"Plus de 60 % des factures freelance contiennent au moins une erreur"*
- *"5 à 8 heures par mois sur la gestion administrative"*
- *"43 % des PME belges (510 346 entreprises)"*

Soit tu les sources avec une étude publique (CCL, STATEC, Eurostat, Insee, Statbel, Statec, Pwc/Atradius), soit tu les retires. Une fiduciaire les repère immédiatement.

---

## DÉTAIL PAR ARTICLE (29 articles)

### Catégorie TVA luxembourgeoise (5 articles)

#### tva-luxembourg-taux-calcul-obligations
- ✗ Mazout chauffage à 14 % → **8 %**
- ✗ Échéance déclaration annuelle 1ᵉʳ mars → **1ᵉʳ mai**
- ⚠ Mention exonération intra-UE "Art. 43 §1 k)" → **§1 d)**
- ⚠ Conservation 10 ans (préciser 15 ans pour immobilier)

#### tva-intracommunautaire-guide-entreprises-luxembourgeoises
- ⚠ Mention autoliquidation pointe vers art. 44 directive seul (à compléter avec art. 17 LIVA)
- ⚠ État récap : préciser seuil 50 000 €/trimestre pour biens, libre pour services

#### franchise-tva-luxembourg-seuil-obligations-regime-normal **← À RÉÉCRIRE EN PRIORITÉ ABSOLUE**
- ✗ Seuil 35 000 € → **50 000 €** (5 occurrences dans l'article)
- ✗ "12 mois glissants" → année civile
- ⚠ Délai déclaration changement régime "15 jours" non documenté

#### article-21-liva-autoliquidation-tva-b2b-intra-ue-freelance-luxembourg **← À REFONDRE INTÉGRALEMENT**
- ✗ Tout l'article : "article 21 LIVA" → **article 17 LIVA**
- ✗ Slug + titre + URL à changer
- ✗ Seuil VIES "50 000 € sur 12 mois pour services" → libre pour services, 50 k€/trimestre pour biens

#### tva-luxembourg-2026-quatre-taux-17-14-8-3-expliques
- ✗ Hébergement hôtelier à 8/17 % → **3 %**
- ✗ Restauration à 8 % → **3 %** (sauf alcools)
- ✗ Franchise art. 56 ter → **art. 57bis**
- ⚠ Sanctions "10 % à 50 %" → pas un barème officiel

### Catégorie facturation / mentions / conformité (6 articles)

#### guide-complet-facturation-luxembourg-2026
- ✗ "ACD contrôle les factures" → **AED**
- ⚠ N° autorisation d'établissement listé comme obligatoire → optionnel
- ⚠ "Taux 14% certains combustibles" formulation imprécise

#### mentions-obligatoires-facture-luxembourg
- ✗ Mention franchise "Article 60bis" → **Article 57bis**
- ✗ Mention autoliquidation "Article 44 loi LU" → **Article 17 LIVA + Article 196 directive**

#### livre-des-recettes-luxembourg-obligations-modele
- ✗ Seuil franchise 35 000 € → **50 000 €**
- ✗ Conservation "art. 60 Abgabenordnung" → **art. 16 Code de commerce + art. 65 LIVA**
- ✗ Mention "ACD" pour livre des recettes TVA → **AED**
- ⚠ "Tous assujettis TVA" → les sociétés en partie double n'ont pas l'obligation formelle

#### delais-paiement-luxembourg-cadre-legal-2026
- ✗ Taux 12,5 % → **~10,15 %**
- ⚠ "B2G ne peut pas être allongé" → extensible à 60 j si justifié

#### note-de-credit-luxembourg-comment-etablir
- ⚠ "Principe d'immuabilité" sans base légale explicite citée
- ⚠ Confusion Gutschrift (DE = auto-facturation, pas avoir)

#### article-61-liva-numerotation-sequentielle-factures-luxembourg-obligatoire **← À REFONDRE INTÉGRALEMENT**
- ✗ Tout l'article : "article 61 LIVA" → **article 63 LIVA** (point 3°)
- ✗ Slug + titre + URL à changer
- ✗ "Franchise art. 56 ter" → **art. 57bis**

### Catégorie AED / FAIA / archivage (5 articles)

#### faia-luxembourg-fichier-audit-informatise-guide
- ✗ "RGD du 28 janvier 2009" → pas de tel règlement, base = art. 70 LIVA
- ✗ "Outil de validation AED en ligne" → FAUX, seul schéma XSD publié
- ⚠ "Toute entreprise TVA concernée" → exclusions FAQ AED non mentionnées

#### archivage-factures-luxembourg-duree-legale-format
- ✗ "Article 60 Abgabenordnung" → **art. 16 Code de commerce**
- ⚠ "PDF/A recommandé" → loi 25 juillet 2015 impose PSDC pour valeur probante
- ⚠ Exception biens amortissables non mentionnée

#### controle-fiscal-luxembourg-comment-preparer
- ✗ Seuil franchise 35 000 € → **50 000 €**
- ⚠ "Notification 2-4 semaines à l'avance" → non documenté légalement

#### controle-fiscal-aed-luxembourg-2026-preparation
- ✗ Amendes 10/50/200 % → **250 € à 10 000 €**, fraude jusqu'à 10× montant
- ✗ "Délai 30 j contestation" → **3 mois**
- ✗ Recours Tribunal administratif → **Tribunal d'arrondissement**
- ⚠ "Préavis 8 jours minimum" → non documenté

#### faia-luxembourg-guide-fichier-audit-informatise-2026 **← À RÉÉCRIRE EN PRIORITÉ ABSOLUE**
- ✗ "FAIA obligatoire en franchise (art. 56 ter)" → FAUX, exemption ≤ 112 k€
- ✗ "Délai 15 jours" → non documenté

### Catégorie création d'entreprise (4 articles)

#### creer-entreprise-individuelle-luxembourg-guide-2026
- ✗ Seuil franchise 35 000 € → **50 000 €**
- ✗ Mention "art. 57 du Code TVA" → **art. 57quater**
- ✗ Pension CCSS 17 % → **16 %** ; total 25,3 % → **24,2 %**

#### creer-entreprise-individuelle-france-guide-2026
- ⚠ Date suppression EIRL 15 mai 2022 → **14 février 2022**
- ⚠ ACRE 2026 : passage à 25 % au 1ᵉʳ juillet 2026 non mentionné

#### creer-entreprise-individuelle-belgique-guide-2026
- ✗ Tranche INASTI 73 447,52 € → **75 024,54 €**
- ✗ Plafond 108 238,40 € → **110 562,42 €**
- ✗ Cotisation min trimestrielle 450,15 € → **890,42 €**
- ⚠ "Tolérance dépassement franchise" → supprimée depuis 1ᵉʳ jan 2025
- ⚠ "43 % PME / 510 346 entreprises" → chiffre non vérifié

#### creer-entreprise-individuelle-allemagne-guide-2026
- ⚠ Exonération IHK incomplète (Freibetrag 15 340 €, exonération 2 ans Existenzgründer manquants)

### Catégorie pratique freelance (8 articles)

#### freelance-luxembourg-facturer-conformite
- ✗ Mention B2B intra-UE "Article 44 loi du 12/02/1979" → **art. 17 LIVA + art. 196 directive**

#### relancer-client-impaye-luxembourg
- ✗ Taux ~12 % → **~10 %**
- ❓ "30 % factures en retard" non sourcé
- ⚠ Délais "mise en demeure J+30, RAR + 8 jours" non imposés par loi

#### excel-vs-logiciel-facturation-pourquoi-switch
- ❓ "40 % freelances sur Excel" non sourcé
- ❓ "5 heures/mois" non sourcé

#### factur-x-zugferd-facturation-electronique-europeenne
- ⚠ ViDA "2028-2030" → **1ᵉʳ juillet 2030**

#### choisir-logiciel-facturation-luxembourg-comparatif
- ✗ Seuil franchise 35 000 € → **50 000 €**

#### 5-erreurs-frequentes-facture-freelance-luxembourg
- ❓ "60 % factures avec erreur" non sourcé
- ⚠ "N° RCS obligatoire" → seulement pour commerçants/sociétés inscrits

#### facturer-etranger-depuis-luxembourg
- ✗ Mention B2B intra-UE "Article 44 directive" → **Article 196**
- ⚠ Services B2C "17 % LU par défaut" simplification excessive
- ⚠ "Hors champ" pour B2B hors UE → mention imprécise

#### automatiser-facturation-7-conseils-gagner-temps
- Pas de claim factuel critique. Article opérationnel/marketing.

### Catégorie Peppol (1 article)

#### peppol-b2g-luxembourg-guide-complet-2026
- ✗ Seuil 30 000 € marchés publics → **aucun seuil**
- ✗ Schéma Peppol LU "0184" → **9938**
- ⚠ ViDA 2028 → **1ᵉʳ juillet 2030**
- ⚠ "Plus de 35 pays" → **111 pays**
- ⚠ "Depuis 2022" générique → échelonnement 18 mai 2022 / 18 oct 2022 / 18 mars 2023
- Manque : canal alternatif MyGuichet.lu pour non-Peppol, format XRechnung 3.0.1 accepté

---

## ACTIONS RECOMMANDÉES

### Étape 1 — Correctifs urgents (avant tout démarchage fiduciaire)

1. **Refondre intégralement** les 2 articles dont la base légale citée est fausse (slug + titre + contenu) :
   - `article-21-liva-...` → `article-17-liva-autoliquidation-tva-b2b-intra-ue-luxembourg`
   - `article-61-liva-...` → `article-63-liva-numerotation-sequentielle-factures-luxembourg`
   - Rediriger les anciens slugs en 301 pour ne pas casser le SEO.

2. **Migration globale** sur toutes les occurrences de :
   - `35 000 EUR` → `50 000 EUR` (franchise TVA, ≥ 6 articles)
   - `Article 56 ter` ou `Article 60bis` (franchise) → `Article 57bis`
   - `Article 21 LIVA` (autoliquidation) → `Article 17 LIVA`
   - `Article 61 LIVA` (numérotation) → `Article 63 LIVA`
   - `Article 44` (mention autoliquidation, pas lieu d'imposition) → `Article 196 de la directive 2006/112/CE`
   - Mention "ACD" pour les contrôles factures/TVA → "AED"

3. **Correction des taux TVA spécifiques** :
   - Hébergement hôtelier → 3 %
   - Restauration (hors alcools) → 3 %
   - Mazout chauffage résidentiel → 8 %

4. **Réécrire la section sanctions AED** dans `controle-fiscal-aed-luxembourg-2026-preparation` : amendes 250 € à 10 000 €, fraude jusqu'à 10× montant + recours = Tribunal d'arrondissement, délai 3 mois.

5. **Réécrire la section "FAIA obligatoire en franchise"** dans `faia-luxembourg-guide-fichier-audit-informatise-2026` : exonération CA ≤ 112 k€ (donc tous les franchisés).

### Étape 2 — Correctifs importants

6. Mettre à jour taux intérêt retard 2026 (~10 %).
7. Corriger chiffres INASTI BE 2026.
8. Mettre à jour ACRE FR (passage 25 % au 1ᵉʳ juillet 2026).
9. Corriger pension CCSS LU (16 %, total 24,2 %).
10. Préciser ViDA B2B intra-UE = 1ᵉʳ juillet 2030.
11. Corriger schéma Peppol LU = 9938.

### Étape 3 — Hygiène structurelle (à mettre en place une fois)

12. **Date de publication + dernière mise à jour visible** sur chaque article.
13. **Citer la source officielle (URL legilux/pfi/guichet/…) à côté de chaque chiffre fiscal**, pas juste en bas.
14. **Disclaimer** en pied : *"Informations à jour au [date]. Pour votre situation, consultez votre fiduciaire."*
15. **Calendrier de relecture annuelle** en janvier pour vérifier seuils, taux, articles cités.

### Étape 4 — Propager aux 4 autres langues

Une fois le FR consolidé, répliquer les corrections en DE/EN/LB/PT. La plupart des erreurs identifiées (seuils, articles de loi, taux) sont des chiffres internationaux qui doivent être identiques dans toutes les versions. Les versions DE/EN/LB/PT héritent automatiquement des bonnes valeurs si le FR est corrigé.

---

## Sources principales consultées

- legilux.public.lu (textes officiels LU)
- pfi.public.lu (Portail fiscalité indirecte / AED)
- guichet.public.lu (portail officiel entreprises)
- impotsdirects.public.lu (ACD)
- ccss.public.lu (sécurité sociale LU)
- mj.gouvernement.lu (taux intérêt légal)
- mindigital.gouvernement.lu (Peppol)
- docs.peppol.eu, directory.peppol.eu
- taxation-customs.ec.europa.eu (ViDA)
- service-public.fr, urssaf.fr, inpi.fr, bpifrance-creation.fr
- economie.fgov.be, inasti.be, finances.belgium.be
- existenzgruendungsportal.de, ihk.de, bmwk.de

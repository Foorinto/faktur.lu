# Spécifications Techniques : Facturation Luxembourg (2026)

## 1. Identité de l'Émetteur (Champs obligatoires)
L'application doit injecter les champs suivants sur chaque facture émise par l'entreprise individuelle :
- Dénomination : [Nom Prénom] + [Enseigne commerciale]
- Adresse complète du siège au Luxembourg.
- Numéro de matricule national (13 chiffres).
- Numéro RCS (ex: A12345).
- Numéro de TVA Intracommunautaire (Format: LUXXXXXXXX).
- Numéro d'autorisation d'établissement (Ministère de l'Économie).

## 2. Logique de TVA (Règles métiers)
L'application doit gérer trois scénarios fiscaux basés sur la localisation et le statut du client :

### A. Client Professionnel en France (B2B Intracommunautaire)
- Taux de TVA : 0%
- Mention légale requise : "TVA due par le preneur - Autoliquidation en application de l'article 196 de la directive 2006/112/CE."
- Validation : Vérifier que le numéro de TVA du client commence par "FR".

### B. Client au Luxembourg (B2B/B2C)
- Taux de TVA standard : 17% (sauf régime de franchise si CA < 35k€).
- Affichage : Montant HT, Taux TVA, Montant TVA, Total TTC.

### C. Client Hors UE
- Taux de TVA : 0% (Exportation de services).
- Mention : "Exonération de TVA en vertu de l'article 43, paragraphe 1, point i de la loi luxembourgeoise sur la TVA."

## 3. Intégrité et Audit
- **Inaltérabilité :** Une fois une facture générée avec un numéro attribué, elle ne doit plus être modifiable. Toute correction doit passer par une "Note de crédit" (Avoir).
- **Séquençage :** La numérotation doit être chronologique et continue (pas de "trous" dans la base de données).
- **Export Audit :** Capacité d'extraire les données au format structuré (CSV/JSON) pour faciliter la génération du fichier FAIA (Fichier d'Audit Informatisé de l'AED).

## 4. Archivage
- Format de sortie : PDF/A (archivage long terme).
- Durée de conservation : Les données doivent être persistantes pendant 10 ans.
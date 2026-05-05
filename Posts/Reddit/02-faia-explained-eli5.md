# Reddit Post — "ELI5: What is FAIA and why should I care?"

**Subreddit cible** : r/Luxembourg (post original, pas une réponse)
**Type** : Post éducatif / TIL
**Langue** : EN (avec FR/DE en commentaire si besoin)
**Quand publier** : Mardi ou Mercredi 9h-11h CET (peak engagement r/Luxembourg)

---

**Title**: TIL: If you run a business in Luxembourg, the tax administration can ask you for a "FAIA" file at any time — and most invoicing tools don't generate it correctly. Quick explainer.

**Body**:

I've been digging into Luxembourg-specific tax compliance lately and one thing keeps coming up that surprises non-Luxembourgers: the FAIA file.

**What it is**: FAIA = "Fichier d'Audit Informatisé AED". It's a structured XML file that the Administration de l'Enregistrement, des Domaines et de la TVA (AED — basically Luxembourg's tax authority) can request during a fiscal audit of any VAT-registered business in Luxembourg.

**Who is concerned**: any business registered for VAT in Luxembourg, including freelancers (indépendants), SMEs, and larger companies.

**When you'd hear about it**: only when you get a tax control letter. By then it's often too late if your invoicing tool can't produce a valid FAIA. Auditors are not patient.

**The format**:
- Mandatory version: 2.01 (since 2017, version 1.x is no longer accepted)
- Validated against an official XSD schema
- Contains: ledgers, accounting lines, VAT rates and amounts, payment references, customer master data
- Period: chosen by you (monthly, quarterly, annual) but must cover the full audit window

**Why most tools fail**:
1. International SaaS (Quickbooks, Xero) just don't support it — it's a Luxembourg-specific format
2. Some local tools generate it but in older versions (1.x) → automatic rejection
3. Some tools generate it but with field-level errors (wrong VAT codes, missing autoliquidation mentions, broken sequential numbering)

**What to do today**:
- Check if your current invoicing tool generates FAIA 2.01
- If yes, validate a sample file against the official schema. There's a free FAIA validator at faktur.lu/fr/validateur-faia (no signup, just drop your XML — disclaimer: I'm the founder of faktur.lu, but the validator is genuinely free regardless of which tool you use)
- If no, plan a migration BEFORE you get a control letter

**Sources**:
- AED official FAIA documentation: https://saturn.etat.lu/tabella/index.do (search "FAIA")
- LIVA (Luxembourg VAT law): https://impotsdirects.public.lu/

Anyone else been audited and want to share what the experience was like? Curious how prepared people felt.

---

## Notes

- Format **TIL** (Today I Learned) marche très bien sur Reddit
- Le disclaimer est court et entre parenthèses, pas en haut → moins agressif pour un post original
- L'invitation à raconter une expérience d'audit booste l'engagement
- Sources officielles ajoutent crédibilité
- À publier après avoir déjà 5+ contributions utiles dans r/Luxembourg

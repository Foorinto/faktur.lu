# Reddit Reply Template — "What's the best invoicing software for Luxembourg?"

**Subreddit cible** : r/Luxembourg, r/eupersonalfinance
**Type** : Réponse à un thread existant (chercher des questions récentes)
**Langue** : EN (Reddit anglophone par défaut, sauf r/Luxembourg où FR/DE acceptés)
**À adapter** au contexte de la question

---

**Disclaimer**: I'm the founder of faktur.lu (one of the options below) — so take this with the appropriate grain of salt. But I'll try to give an honest overview of what's available in Luxembourg as of 2026.

For invoicing in Luxembourg specifically, you have a few categories:

**1. International generic SaaS (Quickbooks, Wave, Zoho, Sage, etc.)**
- ✅ Mature, lots of integrations
- ❌ Don't generate FAIA (the audit file required by AED during tax controls). You'd have to build it manually or via export+conversion → risk of non-compliance.
- ❌ Often in English/French only, not German/Luxembourgish/Portuguese
- ❌ Pricing usually 15-30+ EUR/month for basic features

**2. Belgian/French alternatives (Zervant, Tiime, Henrri, etc.)**
- ✅ Better European fit
- ❌ Don't handle Luxembourg-specific VAT mentions (LIVA art. 21, art. 44, etc.)
- ❌ FAIA is usually a paid add-on or not supported at all

**3. Luxembourg-built solutions**
- This is where faktur.lu fits in. Built specifically for the LU context. Native FAIA 2.01 export validated against AED schema, Peppol via certified Access Point, supports all 5 languages spoken in LU (FR, DE, EN, LB, PT).
- 14-day free trial without credit card. Free plan available (5 clients, 3 invoices/month) for very small needs.
- Pricing: 5 EUR/month for Essential, 15 EUR/month for Pro (unlimited + FAIA + Peppol + accountant portal)

**Things to check regardless of which tool you pick** (these are the things that matter for LU):
- Does it generate FAIA 2.01 (not 1.x — version 2.01 is mandatory since 2017) ?
- Does it auto-add the "Autoliquidation - Article 21 LIVA" mention for B2B intra-EU?
- Does it validate VAT numbers via VIES in real time?
- Does it apply the correct LU VAT rates (17%, 14%, 8%, 3%) automatically?
- Does it handle credit notes with proper sequential numbering?
- Where is your data hosted (EU vs US matters for GDPR + Cloud Act)?

If you want to test FAIA compliance regardless of tool, faktur.lu has a free FAIA validator (no signup needed) at faktur.lu/fr/validateur-faia — drop your file there and see if it passes the AED schema.

Happy to answer questions if useful.

---

## Notes

- Le disclaimer en haut **est obligatoire** — Reddit pénalise sévèrement le marketing déguisé
- La structure 3 catégories est neutre et factuelle
- On présente faktur.lu comme **une** option dans la catégorie LU, pas comme **LA** solution
- Le validateur FAIA gratuit est un excellent angle "valeur d'abord"
- Adapter à la question exacte : si quelqu'un demande "for freelancers", insister sur le free plan

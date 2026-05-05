# Quora Answer — "What is the FAIA file in Luxembourg?"

**Question type** : Educational
**Langue** : EN

---

FAIA stands for "Fichier d'Audit Informatisé AED" — translated as "Computerized Audit File of the AED" (AED = Administration de l'Enregistrement, des Domaines et de la TVA, the Luxembourg VAT and registration tax authority).

**What it is concretely**

FAIA is a structured XML file that any VAT-registered business in Luxembourg may be required to provide to the tax administration (AED) during a fiscal audit. It contains:

- Master data: company info, customer master data, supplier master data, chart of accounts
- Source documents: invoices, credit notes, payment records
- Accounting entries: journal entries with debit/credit lines, VAT codes, references
- VAT data: rates applied, base amounts, VAT amounts, currency conversions

**Why it exists**

The AED introduced FAIA to standardize the format for tax audits. Instead of receiving exports in various formats (Excel, PDF, custom CSV) from each company's accounting system, auditors now request a uniform XML that can be processed automatically.

**Mandatory version**

Currently, **version 2.01** is mandatory for any audit. Version 1.x is no longer accepted. The AED rejects older versions, which means if your accounting software only generates 1.x, you're effectively non-compliant.

**Who must provide a FAIA**

Any Luxembourg-registered company (SARL, SA, SCS, sole proprietor, etc.) that is registered for VAT can be required to provide a FAIA during an audit. This includes:
- Freelancers (indépendants)
- SMEs
- Larger corporations
- Even foreign companies with a Luxembourg VAT registration

**When you're asked for it**

You only hear about it when you receive an audit notification. That's usually too late if your invoicing/accounting tool can't generate a valid FAIA. AED auditors typically give you 1-2 weeks to provide the file.

**Common mistakes**

1. Generating version 1.x instead of 2.01 → automatic rejection
2. Broken sequential invoice numbering (gaps or duplicates) → red flag for auditors
3. Missing or incorrect VAT codes (especially for autoliquidation B2B intra-EU)
4. Missing currency conversion rates for foreign-currency invoices
5. Missing customer master data fields

**How to verify your FAIA before an audit**

The AED publishes the official XSD schema. You can validate any FAIA file against it. There's also a free FAIA validator at faktur.lu/fr/validateur-faia (full disclosure: I'm the founder, but the validator is genuinely free, no signup required, regardless of which tool generated your FAIA).

**Tools that generate FAIA natively**

Most international tools (QuickBooks, Wave, Zoho, Xero) do NOT generate FAIA. Only Luxembourg-specific tools do — faktur.lu is one of them, and there are some legacy ERP systems used by larger companies.

**Sources**:
- AED official documentation: https://saturn.etat.lu/tabella/index.do (search "FAIA")
- LIVA (Luxembourg VAT law)

If you're running a business in Luxembourg, the correct time to verify your FAIA capability is **today**, not when you get an audit letter.

---

## Notes

- Réponse longue, **éducative**, citant les sources officielles
- Mention discrète de faktur.lu via le validateur gratuit
- Capture la requête "what is FAIA Luxembourg" → forte SEO

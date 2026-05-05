# Reddit Reply — "Is Peppol mandatory in [EU country]?"

**Subreddit cible** : r/SaaS, r/eupersonalfinance, r/Entrepreneur
**Type** : Réponse à des questions sur Peppol/ViDA
**Langue** : EN

---

(Disclaimer: I run a Luxembourg invoicing platform, so e-invoicing is a topic I follow daily.)

The short answer is: **Peppol is mandatory in the EU progressively, from now until 2030**.

Here's the timeline as of 2026:

**Currently mandatory** (B2G — business-to-government):
- 🇮🇹 Italy: B2B mandatory since 2019 (uses SDI, not strict Peppol but compatible)
- 🇫🇷 France: B2G mandatory since 2017, B2B mandatory in stages from 2026 onwards (was delayed)
- 🇩🇪 Germany: B2G mandatory since 2020, B2B receiving mandatory since 2025-01-01
- 🇧🇪 Belgium: B2G mandatory since 2024, B2B mandatory from 2026-01-01
- 🇱🇺 Luxembourg: B2G mandatory since 2023
- 🇳🇱 Netherlands, 🇪🇸 Spain, 🇵🇱 Poland: similar trajectory

**ViDA (VAT in the Digital Age)** — EU directive:
- Adopted in 2024
- B2B e-invoicing becomes the default for intra-EU operations by 2030
- Real-time digital reporting to tax authorities replaces periodic VAT returns
- Format: EN 16931 standard (compatible Peppol BIS 3.0)

**What this means concretely**:
1. If you do B2B intra-EU, you NEED to be ready for structured e-invoicing (UBL/XML) by 2030
2. If you sell to public sector in any EU country, you already need to be Peppol-capable
3. Your invoicing tool must connect to a certified Peppol Access Point (you can't just generate an XML and email it — it has to go through the Peppol network)

**How to get started today**:
1. Get your Peppol identifier (your invoicing tool should provision it for you)
2. Test with one customer who already accepts Peppol (most large enterprises do)
3. Receive Peppol invoices from your suppliers (better UX, less data entry)

**Beware**:
- "Peppol-compatible" is not the same as "Peppol-connected". Some tools generate the right XML but don't connect to the network. You'd have to email it manually → not compliant.
- The Access Point needs to be **certified** by OpenPeppol. Not all tools have this.

If you have a specific country/use case, happy to dig in.

---

## Notes

- Réponse **internationale** mais positionne le Luxembourg en exemple
- Encore une fois, mention faktur.lu via le disclaimer + profil
- Le sujet ViDA est complexe → fort potentiel d'engagement (questions de suivi)
- Le piège "Peppol-compatible vs Peppol-connected" est un excellent angle technique

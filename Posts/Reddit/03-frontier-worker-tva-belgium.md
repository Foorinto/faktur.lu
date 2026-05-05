# Reddit Reply — "Frontier worker, how do I invoice my Belgian client?"

**Subreddit cible** : r/Luxembourg, r/eupersonalfinance, r/freelance
**Type** : Réponse à un thread spécifique sur la TVA cross-border
**Langue** : EN

---

(Disclaimer: I'm the founder of a Luxembourg invoicing SaaS — but this answer is generic and applies regardless of the tool you use.)

For a frontier worker (Luxembourg-based, invoicing a Belgian client), the answer depends on whether your client is B2B or B2C:

**B2B (your client has a valid Belgian VAT number)**:

- You invoice **without VAT** (HT/excl. VAT)
- Mandatory mention on the invoice: `Autoliquidation - Article 21 LIVA` (or in EN: `Reverse charge - Article 21 LIVA`)
- The Belgian client self-declares the VAT in their own VAT return ("autoliquidation")
- You declare the operation as intra-EU in your own monthly/quarterly Luxembourg VAT return + EC sales list

**Critical step**: validate the Belgian VAT number via VIES (https://ec.europa.eu/taxation_customs/vies/) **at the time of invoicing**. If the number is not valid at that moment, the AED can re-qualify the operation as Luxembourg VAT due, and you'll have to chase your client to recover the VAT or pay it yourself.

**B2C (private individual in Belgium)**:

- You apply Luxembourg VAT (17% standard) as long as you're under the OSS threshold
- OSS threshold: 10 000 EUR/year of cross-border B2C sales across all EU countries combined
- Above the threshold: you must apply Belgian VAT (21%) and register for the One-Stop-Shop (OSS) system

**Special case: digital services (software, SaaS, e-books, downloads)**:
- The OSS threshold is the same 10 000 EUR/year
- For TBE (telecom, broadcasting, electronic services) you've always been required to apply the destination country's VAT regardless of threshold since 2015 (no, that didn't change with OSS)

**What invoicing tools should do for you**:
- Real-time VIES validation when you create the client
- Auto-add the autoliquidation mention for detected intra-EU B2B operations
- Track your OSS threshold progress and alert you BEFORE you cross it
- Generate the EC sales list (recap intracommunautaire) for your VAT return

If your current tool doesn't do those things, it's costing you time AND putting you at risk during a tax control.

Resources:
- VIES: https://ec.europa.eu/taxation_customs/vies/
- LIVA art. 21 (autoliquidation): https://impotsdirects.public.lu/
- OSS Luxembourg: https://guichet.public.lu/en/entreprises/fiscalite/tva/operations-internationales/oss.html

Hope this helps.

---

## Notes

- Le disclaimer est entre parenthèses, court, en début
- Réponse **technique** et **précise** — utilise les bons articles de loi
- Ne mentionne pas faktur.lu directement dans la réponse → le profil affichera "founder of a Luxembourg invoicing SaaS"
- Les sources officielles renforcent la crédibilité
- Si l'OP demande explicitement "what tool do you recommend", on peut répondre dans un commentaire suivant

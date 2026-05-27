<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * English translations of the 5 scheduled June 2026 long-tail articles.
 * Idempotent: skips any article whose slug already exists.
 * translation_key matches the FR slug so translations are grouped.
 */
class BlogScheduledJune2026EnSeeder extends Seeder
{
    public function run(): void
    {
        $authorId = DB::table('users')->orderBy('id')->first()?->id ?? 1;

        foreach ($this->articles() as $article) {
            if (DB::table('blog_posts')->where('slug', $article['slug'])->exists()) {
                $this->command?->info("Skip EN: {$article['slug']}");
                continue;
            }

            DB::table('blog_posts')->insert([
                'author_id' => $authorId,
                'category_id' => $article['category_id'],
                'title' => $article['title'],
                'slug' => $article['slug'],
                'excerpt' => $article['excerpt'],
                'content' => $article['content'],
                'cover_image' => $article['cover_image'],
                'meta_title' => $article['meta_title'],
                'meta_description' => $article['meta_description'],
                'status' => 'published',
                'published_at' => $article['published_at'],
                'locale' => 'en',
                'translation_key' => $article['translation_key'],
                'views_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command?->info("Created EN: {$article['slug']}");
        }
    }

    protected function articles(): array
    {
        return [
            [
                'category_id' => 2,
                'title' => "AED tax audit Luxembourg: how to prepare in 2026",
                'slug' => 'aed-tax-audit-luxembourg-2026-preparation-guide',
                'translation_key' => 'controle-fiscal-aed-luxembourg-2026-preparation',
                'cover_image' => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=1200&h=630&fit=crop',
                'meta_title' => 'AED tax audit Luxembourg 2026: preparation guide | faktur.lu',
                'meta_description' => "Everything to know about AED tax audits in Luxembourg: preparation, FAIA, required documents, typical process. Practical 2026 guide for freelancers and SMEs.",
                'excerpt' => "Receiving an audit notification from the AED worries every self-employed worker in Luxembourg. Here is what to prepare and how faktur.lu helps you pass an audit in 30 minutes instead of 3 days.",
                'published_at' => '2026-06-01 09:00:00',
                'content' => <<<HTML
<p class="lead">Receiving an audit notification from the <strong>Administration de l'Enregistrement, des Domaines et de la TVA (AED)</strong> worries every self-employed worker and SME director in Luxembourg. Yet, with the right preparation, a tax audit takes hours rather than days. Here is how to prepare in 2026.</p>

<h2>Who is concerned by an AED audit?</h2>

<p>Any business registered for VAT in Luxembourg can be audited by the AED. In practice, the administration targets:</p>

<ul>
    <li><strong>Fast-growing businesses</strong> whose turnover varies unusually</li>
    <li><strong>Risk sectors</strong> identified by the AED (commerce, restaurants, construction, consulting)</li>
    <li><strong>Atypical VAT returns</strong> (recurring VAT credit, large intra-EU operations)</li>
    <li><strong>Random audits</strong>: between 3% and 5% of companies are audited each year on statistical criteria</li>
</ul>

<p>An audit can cover <strong>the last 5 years</strong> (standard prescription period, extended to 10 years in case of suspected fraud).</p>

<h2>The 3 types of AED audit</h2>

<h3>1. Desk audit</h3>

<p>The AED asks you to send documents by mail or electronic means. No physical visit. This is the most common and least intrusive type.</p>

<h3>2. On-site audit</h3>

<p>An AED inspector visits your premises to verify accounting books, invoices, expense vouchers. Announced by mail with at least 8 days' notice.</p>

<h3>3. Unannounced audit</h3>

<p>Rare, reserved for serious fraud suspicions. The inspector arrives without notice. You have the right to request your accountant's presence during the audit.</p>

<h2>Documents the AED may request</h2>

<p>The list varies by activity, but the following documents are systematically reviewed:</p>

<ul>
    <li><strong>All issued and received invoices</strong> for the audited period, with mandatory LIVA mentions</li>
    <li><strong>The FAIA file</strong> (Standard Audit File of the AED) in version 2.01</li>
    <li><strong>The receipts and expenses ledger</strong> or full accounting depending on your regime</li>
    <li><strong>Monthly or quarterly VAT returns</strong> signed</li>
    <li><strong>Professional bank statements</strong> for the period</li>
    <li><strong>Proof of deducted expenses</strong> (expense notes, supplier invoices)</li>
    <li><strong>Significant client/supplier contracts</strong></li>
    <li><strong>Proof of reverse-charge mechanism</strong> for intra-EU B2B operations (VAT numbers validated via VIES)</li>
</ul>

<h2>FAIA: the critical control point</h2>

<p>The <strong>FAIA</strong> has become the pivot of every AED audit since 2020. If you cannot produce a FAIA compliant with version 2.01, the inspector considers your accounting not held to standard.</p>

<p>The FAIA is a structured XML file grouping:</p>

<ul>
    <li>Company headers (legal name, VAT number, RCS, period)</li>
    <li>All sales invoices for the audited period</li>
    <li>Associated accounting entries</li>
    <li>Debit/credit totals for each movement</li>
</ul>

<p>If you invoice with Excel or a system that does not generate FAIA, you will have to reconstitute it manually — which can take <strong>several days of work</strong> and exposes you to errors.</p>

<h2>How an on-site audit unfolds</h2>

<ol>
    <li><strong>Written notification</strong> with minimum 8 days' notice (except unannounced audits)</li>
    <li><strong>Arrival of AED inspectors</strong> with an audit commission</li>
    <li><strong>Document request</strong> from a pre-established list</li>
    <li><strong>Entry verification</strong> and cross-checking with VAT returns</li>
    <li><strong>Interview</strong> on non-standard operations or detected discrepancies</li>
    <li><strong>Notification of conclusions</strong> by mail within 60 days</li>
</ol>

<p>You then have <strong>30 days to contest</strong> the conclusions by complaint to the AED director.</p>

<h2>The 5 errors that cost dearly</h2>

<ol>
    <li><strong>Non-sequential invoice numbering</strong> (Article 61 LIVA): gaps in sequence or duplicates = presumption of undeclared turnover</li>
    <li><strong>Missing LIVA mentions</strong> on invoices: VAT number, RCS, matricule, issue date, etc.</li>
    <li><strong>Undocumented intra-EU B2B reverse charge</strong>: without VIES validation of the client VAT number, the AED can requalify as taxable operation</li>
    <li><strong>Missing or illegible expense vouchers</strong> (handwritten note, faded receipt)</li>
    <li><strong>Inconsistencies between VAT return and invoices</strong>: even a minor gap triggers in-depth verification</li>
</ol>

<h2>Preparing your audit in 5 steps</h2>

<p>Whether you are a freelancer or SME, here is the checklist to execute from the notification:</p>

<ol>
    <li><strong>Prepare the FAIA</strong> for the requested period — ideally in one click from your invoicing software</li>
    <li><strong>Print or export</strong> all issued invoices (PDF/A for legal archiving)</li>
    <li><strong>Check consistency</strong> with your filed VAT returns</li>
    <li><strong>Build a file</strong> per month: invoices + bank statements + corresponding VAT return</li>
    <li><strong>Inform your accountant</strong> and ask them to be present during the audit (recommended)</li>
</ol>

<h2>How faktur.lu prepares you</h2>

<p>With a Luxembourg-compliant invoicing platform, an AED audit prepares in <strong>30 minutes instead of several days</strong>:</p>

<ul>
    <li>Automatic sequential numbering compliant with <strong>Article 61 LIVA</strong></li>
    <li>Mandatory LIVA mentions auto-generated on each invoice</li>
    <li>Real-time VIES validation for intra-EU B2B operations</li>
    <li><strong>FAIA 2.01</strong> export in one click on any period</li>
    <li><strong>PDF/A</strong> archiving of invoices (legal 10-year standard, art. 16 Code of Commerce)</li>
    <li>Accountant portal: your fiduciary retrieves everything without email back-and-forth</li>
</ul>

<p><a href="/en/register" class="text-primary-500 hover:underline font-medium">Get started for free with faktur.lu</a> and prepare your next audit without stress.</p>

<h2>FAQ — AED tax audit</h2>

<h3>How long does an AED audit last?</h3>
<p>Desk audit: 2 to 4 weeks (depending on document submission). On-site audit: typically 1 to 3 days at the business + 30 to 60 days of analysis by the AED.</p>

<h3>Can I refuse an audit?</h3>
<p>No. Refusing an audit is sanctioned by an administrative fine and presumes bad faith. You can however request a postponement on legitimate grounds (illness, prolonged absence) and require your accountant's presence.</p>

<h3>What are the fines for errors?</h3>
<p>Variable: 10% to 50% of undeclared VAT for good-faith omissions, up to 200% for characterised fraud. Flat fines (EUR 50 to EUR 25,000) may also apply for formal failings (missing mentions, FAIA not produced, etc.).</p>

<h3>Can the audit cover old years?</h3>
<p>Yes: prescription is 5 years for good-faith failings, extended to 10 years for suspected fraud. Archiving 10 years is therefore crucial (legal obligation, art. 16 Luxembourg Code of Commerce).</p>

<h3>What if I disagree with the conclusions?</h3>
<p>You have 30 days to file a written complaint with the AED director, presenting your arguments and supporting documents. In case of rejection, appeal to the Administrative Court within 3 months.</p>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Prepare your next audit today</h3>
    <p class="text-primary-800 mb-4">Automatic numbering, FAIA 2.01 in one click, compliant LIVA mentions, 10-year PDF/A archiving. AED audits become routine.</p>
    <a href="/en/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Get started free</a>
</div>
HTML,
            ],

            [
                'category_id' => 2,
                'title' => "FAIA Luxembourg: complete guide to the standard audit file in 2026",
                'slug' => 'faia-luxembourg-standard-audit-file-complete-guide-2026',
                'translation_key' => 'faia-luxembourg-guide-fichier-audit-informatise-2026',
                'cover_image' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1200&h=630&fit=crop',
                'meta_title' => 'FAIA Luxembourg 2026: complete guide to the AED audit file | faktur.lu',
                'meta_description' => "FAIA explained for Luxembourg freelancers and SMEs: what it is, who is concerned, how to generate a compliant FAIA 2.01. Free validator included.",
                'excerpt' => "FAIA is the format required by the AED for your tax audits. Here is everything to understand in 2026: XML file structure, who is concerned, how to generate it, and how to validate it for free.",
                'published_at' => '2026-06-08 09:00:00',
                'content' => <<<HTML
<p class="lead">The <strong>FAIA</strong> (Standard Audit File of the AED) is the standard required by the Luxembourg tax administration during every VAT audit. Yet few freelancers and SMEs really know what it contains. This guide explains everything in 2026.</p>

<h2>What is the FAIA?</h2>

<p>The <strong>FAIA</strong> is a structured XML file based on the international <strong>SAF-T</strong> standard (Standard Audit File for Tax) developed by the OECD. It groups all the accounting data of a business over a given period, in a format machine-readable by AED tools.</p>

<p>The current version in force is <strong>version 2.01</strong>, published by the AED in 2020. The official XSD schema is available on the administration's website and is authoritative in case of compliance dispute.</p>

<h2>Who must produce a FAIA?</h2>

<p>Any business registered for VAT in Luxembourg <strong>must be able to produce a compliant FAIA</strong> in case of tax audit. This concerns:</p>

<ul>
    <li><strong>Freelancers and sole traders</strong>, including under VAT exemption regime</li>
    <li><strong>SMEs</strong> under any regime (simplified or normal)</li>
    <li><strong>Commercial companies</strong> (SARL, SA, SAS)</li>
    <li><strong>Associations</strong> conducting VAT-taxable economic activity</li>
</ul>

<p>The obligation is not to generate FAIA every month — it is sufficient to be able to produce it <strong>on AED request</strong> within a reasonable time (typically 15 days after notification).</p>

<h2>What does a FAIA contain?</h2>

<p>A FAIA 2.01 is structured in several mandatory sections:</p>

<h3>1. Header</h3>

<ul>
    <li>Legal name, VAT number, RCS, matricule</li>
    <li>Company address</li>
    <li>Period covered (start and end date)</li>
    <li>File creation date</li>
    <li>Currency (EUR)</li>
</ul>

<h3>2. Chart of accounts (MasterFiles)</h3>

<p>List of accounts used (customers, suppliers, general accounts). For a freelancer or small structure, this section can be minimal.</p>

<h3>3. Sales invoices</h3>

<ul>
    <li>Invoice number (sequential, compliant with Article 61 LIVA)</li>
    <li>Finalisation date</li>
    <li>Customer: name, VAT number, address</li>
    <li>Invoice lines: description, quantity, unit price, VAT</li>
    <li>Totals excl. VAT, VAT, incl. VAT</li>
</ul>

<h3>4. Movements (GeneralLedgerEntries)</h3>

<p>Corresponding accounting entries (debit/credit per account).</p>

<h3>5. Footer</h3>

<p>Control totals: total debited, total credited, transaction count.</p>

<h2>The 4 most frequent FAIA errors</h2>

<ol>
    <li><strong>Invoice numbering non-compliant</strong> with Article 61 LIVA (gaps in sequence or duplicates). The official validator rejects the file.</li>
    <li><strong>Missing mandatory fields</strong>: client VAT number, full address, reverse-charge mention for intra-EU B2B.</li>
    <li><strong>Inconsistent totals</strong> between sections (e.g., sum of invoices ≠ declared Total Sales Invoices).</li>
    <li><strong>Incorrect date format</strong>: FAIA requires ISO 8601 format (YYYY-MM-DD), not DD/MM/YYYY.</li>
</ol>

<h2>How to generate a compliant FAIA</h2>

<p>Three options depending on your situation:</p>

<h3>Option 1 — Native FAIA invoicing software</h3>

<p>The simplest and safest method. A software like <a href="/en" class="text-primary-500 hover:underline font-medium">faktur.lu</a> generates FAIA 2.01 on demand, on any period, with a single click. The file is automatically compliant with the official XSD schema.</p>

<h3>Option 2 — Export from accounting software</h3>

<p>Sage BOB 50, Sage 100 and most professional accounting software allow FAIA export. Check with your publisher that version 2.01 is supported.</p>

<h3>Option 3 — Manual construction (discouraged)</h3>

<p>Theoretically possible with an XML developer, but error-prone. Reserved for extreme cases (legacy data, migration).</p>

<h2>Validate your FAIA for free</h2>

<p>Before sending your FAIA to the AED, validate it against the official schema to detect errors before the inspector.</p>

<p>faktur.lu provides a <a href="/en/faia-validator" class="text-primary-500 hover:underline font-medium">free FAIA validator</a> that:</p>

<ul>
    <li>Verifies XML compliance with the AED 2.01 XSD schema</li>
    <li>Detects missing mandatory fields</li>
    <li>Checks totals consistency</li>
    <li>Verifies invoice number sequentiality</li>
    <li>Stores no data (the file stays on your machine)</li>
</ul>

<h2>FAQ — FAIA</h2>

<h3>Do I have to send a FAIA every year?</h3>
<p>No. FAIA is produced only <strong>at AED request</strong> in case of audit. You must however be able to generate it quickly (within 15 days).</p>

<h3>What if I cannot produce a FAIA?</h3>
<p>The inspector considers your accounting not held to standard and may apply an <strong>administrative fine</strong> or estimate your turnover on a lump-sum basis (against you). Worst case: VAT adjustment with surcharge.</p>

<h3>Is FAIA mandatory under VAT exemption?</h3>
<p>Yes. Even under exemption (Article 56 ter LIVA), you must be able to produce a FAIA if the AED requests it. The FAIA will include "VAT not applicable" mentions on each invoice.</p>

<h3>How do I know my FAIA is compliant before an audit?</h3>
<p>Use a <a href="/en/faia-validator" class="text-primary-500 hover:underline font-medium">FAIA validator</a> that compares it to the official AED 2.01 XSD schema. Free, no registration, takes 5 seconds.</p>

<h3>Can my accountant generate FAIA for me?</h3>
<p>Yes, your accountant can generate FAIA from their own tool or retrieve yours via an accountant portal. With faktur.lu, you invite your accountant in read-only mode and they access the FAIA in one click.</p>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">FAIA 2.01 in one click, AED-compliant</h3>
    <p class="text-primary-800 mb-4">faktur.lu generates your FAIA on any period, automatically validated against the official XSD schema. Included from the Free plan.</p>
    <a href="/en/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Try for free</a>
</div>
HTML,
            ],

            [
                'category_id' => 3,
                'title' => "Article 21 LIVA: intra-EU B2B VAT reverse charge for Luxembourg freelancers",
                'slug' => 'article-21-liva-intra-eu-b2b-vat-reverse-charge-luxembourg-freelancers',
                'translation_key' => 'article-21-liva-autoliquidation-tva-b2b-intra-ue-freelance-luxembourg',
                'cover_image' => 'https://images.unsplash.com/photo-1559386484-97dfc0e15539?w=1200&h=630&fit=crop',
                'meta_title' => 'Article 21 LIVA: intra-EU B2B reverse charge Luxembourg | faktur.lu',
                'meta_description' => "Invoicing a B2B client in Europe? Article 21 LIVA imposes the reverse charge. Here are the rules, mandatory mentions, VIES validation and pitfalls to avoid in 2026.",
                'excerpt' => "Are you a Luxembourg freelancer invoicing a B2B client in France, Belgium or Germany? Article 21 LIVA applies: VAT reverse charge. Here are the rules, mandatory mentions, and how not to get it wrong.",
                'published_at' => '2026-06-15 09:00:00',
                'content' => <<<HTML
<p class="lead">Are you a Luxembourg freelancer invoicing a B2B client in France, Germany or Belgium? <strong>Article 21 LIVA</strong> applies: VAT reverse charge. If you forget the mention or invoice Luxembourg VAT by mistake, you risk a tax adjustment. Here are the clear rules.</p>

<h2>Article 21 LIVA in brief</h2>

<p><strong>Article 21 of the Luxembourg VAT law (LIVA)</strong> transposes the European reverse-charge principle for services between taxable persons in two different EU member states.</p>

<p>Concretely, when a Luxembourg freelancer invoices a service to a business in another EU country:</p>

<ul>
    <li>Luxembourg VAT is <strong>not due</strong></li>
    <li>The foreign customer <strong>self-assesses VAT</strong> in their own country at the local applicable rate</li>
    <li>The invoice explicitly mentions <strong>"Autoliquidation, article 21 LIVA"</strong></li>
</ul>

<h2>When does Article 21 LIVA apply?</h2>

<p>Three cumulative conditions must be met:</p>

<ol>
    <li><strong>Service supply</strong> (not goods — different rules)</li>
    <li><strong>Business customer (B2B)</strong> in another EU member state</li>
    <li><strong>Valid intra-EU VAT number</strong> of the customer (mandatory VIES validation)</li>
</ol>

<p>If one condition is missing, the rule changes:</p>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Situation</th>
            <th class="border border-gray-300 px-4 py-2 text-left">VAT rule</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2">B2B intra-EU client with valid VAT number</td><td class="border border-gray-300 px-4 py-2"><strong>Reverse charge (Art. 21 LIVA)</strong></td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2C (consumer) intra-EU</td><td class="border border-gray-300 px-4 py-2">Luxembourg VAT 17% (or OSS if threshold exceeded)</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">B2B intra-EU without validated VAT</td><td class="border border-gray-300 px-4 py-2">Luxembourg VAT 17%</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2">Non-EU customer (B2B or B2C)</td><td class="border border-gray-300 px-4 py-2">Exemption with adapted mention</td></tr>
    </tbody>
</table>

<h2>VIES validation, critical step</h2>

<p>VIES (VAT Information Exchange System) is the European service allowing real-time verification of an intra-EU VAT number.</p>

<p>Before issuing a reverse-charge invoice, you <strong>must imperatively</strong> validate the client VAT number via VIES. If the number is not valid on invoice date, the AED considers the service taxable in Luxembourg and will adjust the uncollected VAT.</p>

<p>The official VIES service is at <a href="https://ec.europa.eu/taxation_customs/vies/" rel="external nofollow" target="_blank" class="text-primary-500 hover:underline">ec.europa.eu/taxation_customs/vies</a>. But in practice, your invoicing software should do it automatically on each invoice.</p>

<h2>Mandatory mentions on the invoice</h2>

<p>A reverse-charge invoice under Article 21 LIVA must contain, in addition to usual mentions:</p>

<ul>
    <li>Your <strong>Luxembourg VAT number</strong> (LU + 8 digits)</li>
    <li>The <strong>client VAT number</strong> (country prefix + digits)</li>
    <li>The exact mention: <strong>"Autoliquidation, article 21 LIVA"</strong> (or equivalent in client language)</li>
    <li><strong>No VAT added</strong> on total (VAT = €0)</li>
    <li>The excl. VAT total which becomes also the incl. VAT total</li>
</ul>

<p><strong>Important</strong>: the "reverse charge" mention must be visible and understandable. The AED has sanctioned businesses using vague phrasing like "VAT not applicable" without precision.</p>

<h2>Concrete example</h2>

<p>Marie is a freelance UX designer in Luxembourg-City. She invoices <strong>EUR 2,500 excl. VAT</strong> to <strong>Acme SA</strong>, a Paris agency (VAT number FR12345678901, validated via VIES).</p>

<p>Her invoice contains:</p>

<ul>
    <li><strong>Issuer</strong>: Marie Dupont, LU12345678</li>
    <li><strong>Recipient</strong>: Acme SA, 10 rue de Rivoli 75001 Paris, FR12345678901</li>
    <li><strong>Service</strong>: UX design website — 2,500.00 €</li>
    <li><strong>VAT (0%)</strong>: 0.00 €</li>
    <li><strong>Total incl. VAT</strong>: 2,500.00 €</li>
    <li><strong>Mention</strong>: <em>"Autoliquidation, article 21 LIVA — VAT due by the recipient in their country."</em></li>
</ul>

<p>Acme SA declares French VAT (20%) in its own VAT return, both as collected VAT and deductible VAT (neutral operation for them).</p>

<h2>The 3 costly errors</h2>

<ol>
    <li><strong>Forgetting VIES validation</strong>: if the VAT number proves invalid or suspended, you must collect Luxembourg VAT (17%). Without the client to pay, it comes out of your pocket.</li>
    <li><strong>Missing or vague "reverse charge" mention</strong>: the AED requalifies as taxable in Luxembourg.</li>
    <li><strong>Wrong accounting treatment</strong>: the operation must appear in the Luxembourg VAT return (intra-EU services section) and in the <strong>VIES recapitulative declaration</strong> (monthly or quarterly).</li>
</ol>

<h2>How faktur.lu protects you</h2>

<p>faktur.lu automatically detects reverse-charge conditions and applies Article 21 LIVA:</p>

<ul>
    <li><strong>Real-time VIES validation</strong> when you enter an intra-EU B2B client</li>
    <li>Mention <strong>"Autoliquidation, article 21 LIVA"</strong> auto-added to the invoice</li>
    <li><strong>VAT forced to 0</strong> for concerned services, calculations verified</li>
    <li><strong>VIES recapitulative declaration</strong> automatically generated at period end</li>
    <li>Mentions translated to client language (FR, DE, EN, PT)</li>
</ul>

<p>You avoid errors and invoice foreign customers fully compliant.</p>

<h2>FAQ — Article 21 LIVA</h2>

<h3>What if my client has no VAT number?</h3>
<p>Without a VAT number validated via VIES, reverse charge does not apply. You must invoice Luxembourg VAT (17% standard). Same for consumers (B2C).</p>

<h3>What about goods supplies (not services)?</h3>
<p>For B2B intra-EU goods sales, <strong>Article 43 LIVA</strong> applies (exempt intra-community supplies). Rules and mentions differ.</p>

<h3>Must the operation be declared to the AED?</h3>
<p>Yes. The operation appears in:</p>
<ul>
    <li>Your <strong>Luxembourg VAT return</strong> (intra-EU services box)</li>
    <li>The <strong>VIES recapitulative declaration</strong> (monthly if turnover > EUR 50,000 over 12 months, otherwise quarterly)</li>
</ul>

<h3>Can the client refuse reverse charge?</h3>
<p>No, it is a mandatory European rule. However, you can refuse to invoice if the client lacks a validated VAT number (or invoice with standard Luxembourg VAT).</p>

<h3>What happens in an AED audit?</h3>
<p>The AED checks the <strong>VIES validation proof</strong> on invoice date (a screenshot or automatic log suffices). Without this proof, the operation is requalified as taxable and you owe VAT + penalties.</p>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Reverse charge, error-free</h3>
    <p class="text-primary-800 mb-4">faktur.lu validates VIES in real time, applies Article 21 LIVA automatically, generates your recapitulative declarations. No more stress for European clients.</p>
    <a href="/en/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Get started free</a>
</div>
HTML,
            ],

            [
                'category_id' => 1,
                'title' => "Luxembourg VAT 2026: the 4 rates (17%, 14%, 8%, 3%) explained",
                'slug' => 'luxembourg-vat-2026-four-rates-17-14-8-3-explained',
                'translation_key' => 'tva-luxembourg-2026-quatre-taux-17-14-8-3-expliques',
                'cover_image' => 'https://images.unsplash.com/photo-1543286386-713bdd548da4?w=1200&h=630&fit=crop',
                'meta_title' => 'Luxembourg VAT 2026: guide to the 4 rates (17%, 14%, 8%, 3%) | faktur.lu',
                'meta_description' => "Which VAT rate to apply in Luxembourg in 2026? Standard 17%, intermediate 14%, reduced 8%, super-reduced 3%. Practical guide with concrete examples per sector.",
                'excerpt' => "Luxembourg applies 4 VAT rates. Which one to use for what? Here is the practical 2026 guide with concrete examples for each rate: services, food, books, restaurants, gas, electricity.",
                'published_at' => '2026-06-22 09:00:00',
                'content' => <<<HTML
<p class="lead">Luxembourg applies <strong>4 different VAT rates</strong> in 2026: 17% (standard), 14% (intermediate), 8% (reduced) and 3% (super-reduced). Choosing the right rate is crucial for tax compliance. Here is the complete 2026 guide with concrete examples.</p>

<h2>The 4 VAT rates in force in 2026</h2>

<table class="w-full border-collapse border border-gray-300 my-6">
    <thead>
        <tr class="bg-gray-50">
            <th class="border border-gray-300 px-4 py-2 text-left">Rate</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Designation</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Application</th>
        </tr>
    </thead>
    <tbody>
        <tr><td class="border border-gray-300 px-4 py-2"><strong>17%</strong></td><td class="border border-gray-300 px-4 py-2">Standard</td><td class="border border-gray-300 px-4 py-2">Majority of goods and services</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2"><strong>14%</strong></td><td class="border border-gray-300 px-4 py-2">Intermediate</td><td class="border border-gray-300 px-4 py-2">Wine, certain fuels</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2"><strong>8%</strong></td><td class="border border-gray-300 px-4 py-2">Reduced</td><td class="border border-gray-300 px-4 py-2">Gas, electricity, hairdressing, some works</td></tr>
        <tr><td class="border border-gray-300 px-4 py-2"><strong>3%</strong></td><td class="border border-gray-300 px-4 py-2">Super-reduced</td><td class="border border-gray-300 px-4 py-2">Food, books, medicines, public transport</td></tr>
    </tbody>
</table>

<p>These rates have been in force since <strong>1 January 2024</strong> (the standard rate returned from 16% to 17% on that date after the temporary 2023 cut).</p>

<h2>Standard rate 17%: default</h2>

<p>The standard rate applies to <strong>all taxable operations</strong> not explicitly covered by another rate. Concretely:</p>

<ul>
    <li><strong>Services</strong>: consulting, design, development, marketing, training, etc.</li>
    <li><strong>Non-food goods sales</strong>: computer equipment, furniture, clothing, etc.</li>
    <li><strong>Professional equipment rental</strong></li>
    <li><strong>Hotel accommodation</strong> (4 stars and above)</li>
    <li><strong>Restaurants</strong> (alcoholic beverages)</li>
</ul>

<p>This is <strong>the default rate</strong> to apply if in doubt, pending clarification with your accountant.</p>

<h2>Intermediate rate 14%: wine and fuels</h2>

<p>The intermediate rate covers mainly:</p>

<ul>
    <li><strong>Wines</strong> (still and sparkling, except champagne)</li>
    <li><strong>Certain solid fuels</strong> (coal, firewood)</li>
    <li><strong>Some advertising publications</strong></li>
</ul>

<p>This rate is rare. If you are not in wine or heating sectors, you will probably never use it.</p>

<h2>Reduced rate 8%: energy and personal services</h2>

<p>The reduced rate applies to many sectors:</p>

<ul>
    <li><strong>Gas and electricity</strong> supply</li>
    <li><strong>Hairdressing</strong></li>
    <li><strong>Restaurants</strong> (excluding alcoholic beverages)</li>
    <li><strong>Hotel accommodation</strong> (1 to 3 stars)</li>
    <li><strong>Renovation works</strong> on old housing (under conditions)</li>
    <li><strong>Live performances</strong> (theatre, concerts)</li>
    <li><strong>Shoe repair, bicycle repair, clothing alteration</strong></li>
</ul>

<h2>Super-reduced rate 3%: essential goods</h2>

<p>The lowest rate covers products deemed essential:</p>

<ul>
    <li><strong>Basic food products</strong> (bread, milk, meat, vegetables, fruits)</li>
    <li><strong>Books, newspapers, periodicals</strong> (paper and digital since 2020)</li>
    <li><strong>Medicines</strong> (prescription or over-the-counter)</li>
    <li><strong>Public transport</strong> (bus, train, tram)</li>
    <li><strong>Agricultural production</strong> (seeds, fertilisers)</li>
    <li><strong>Water from public networks</strong></li>
    <li><strong>Artworks</strong> (under certain conditions)</li>
</ul>

<h2>Frequent edge cases</h2>

<h3>Restaurants: 8% or 17%?</h3>

<p><strong>Meals served on site</strong> in a restaurant are at <strong>8%</strong>, but <strong>alcoholic beverages</strong> remain at <strong>17%</strong>. A wine bottle served with a meal is billed separately.</p>

<h3>Hotels: 8% or 17%?</h3>

<p>1 to 3 stars hotel stays are at <strong>8%</strong>. From 4 stars: <strong>17%</strong>. Ancillary services (spa, room service) follow their own regime.</p>

<h3>E-books: 3% or 17%?</h3>

<p>Since 2020, <strong>digital books (e-books)</strong> have the same rate as paper books, i.e., <strong>3%</strong>. But streaming subscriptions (Netflix, Spotify) remain at 17%.</p>

<h3>Renovation works: 8% or 17%?</h3>

<p>Renovation works on housing <strong>over 2 years old</strong> benefit from the reduced rate of <strong>8%</strong>, provided materials and labour are invoiced together by the construction company. For new housing: 17%.</p>

<h2>How to calculate Luxembourg VAT</h2>

<p>If you invoice EUR 1,000 excl. VAT to a Luxembourg client at the 17% standard rate:</p>

<ul>
    <li><strong>Excl. VAT</strong>: 1,000.00 €</li>
    <li><strong>VAT (17%)</strong>: 170.00 €</li>
    <li><strong>Incl. VAT</strong>: 1,170.00 €</li>
</ul>

<p>Conversely, if you know the incl. VAT amount and seek the excl. VAT:</p>

<ul>
    <li><strong>Excl. VAT</strong> = Incl. VAT ÷ (1 + rate/100)</li>
    <li><strong>Example</strong>: 1,170 € incl. ÷ 1.17 = 1,000 € excl.</li>
</ul>

<p>To save time, use our <a href="/en/tools/vat-calculator" class="text-primary-500 hover:underline font-medium">free Luxembourg VAT calculator</a> supporting all 4 rates.</p>

<h2>Rate errors: the consequences</h2>

<p>Applying the wrong rate is not trivial:</p>

<ul>
    <li><strong>VAT under-invoicing</strong> (rate too low): AED adjustment + interest + penalties (10% to 50%)</li>
    <li><strong>VAT over-invoicing</strong> (rate too high): the client can claim a refund and you must regularise with the AED</li>
    <li><strong>Client deduction refusal</strong>: if the rate is clearly wrong, the client may refuse to recover VAT</li>
</ul>

<h2>How faktur.lu avoids errors</h2>

<p>faktur.lu applies the right rate automatically based on the service nature:</p>

<ul>
    <li>Rate selection by <strong>service type</strong> at product creation</li>
    <li>Automatic detection of <strong>intra-EU B2B</strong> cases (reverse charge art. 21 LIVA)</li>
    <li>Zero rate application in case of <strong>exemption</strong> (art. 56 ter)</li>
    <li>Mandatory LIVA mentions generated on each invoice</li>
</ul>

<h2>FAQ — Luxembourg VAT</h2>

<h3>Is the standard rate really 17% in 2026?</h3>
<p>Yes, since 1 January 2024. The rate was temporarily 16% in 2023 to support purchasing power, then returned to the historical 17%.</p>

<h3>Is my activity VAT-exempt?</h3>
<p>Some activities are exempt (health, education, insurance, residential rental), but this also means you cannot <strong>recover VAT</strong> on business purchases. Check your case with your accountant.</p>

<h3>When to switch rates on a pending invoice?</h3>
<p>If the rate changes between quote issuance and service delivery, the rate <strong>in force at invoice finalisation date</strong> applies (not the quote date).</p>

<h3>And for cross-border B2C sales?</h3>
<p>Since 2021, the <strong>OSS (One Stop Shop)</strong> regime applies: above EUR 10,000 in intra-EU B2C sales per year, you must apply the destination country rate. Otherwise, Luxembourg VAT applies.</p>

<h3>How to justify a particular rate in case of audit?</h3>
<p>Keep <strong>service nature vouchers</strong>: delivery notes, contracts, technical sheets. The AED may request to see the nomenclature or product composition to validate the applied rate.</p>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Calculate Luxembourg VAT in 5 seconds</h3>
    <p class="text-primary-800 mb-4">Our free VAT calculator supports the 4 Luxembourg rates (17%, 14%, 8%, 3%). No registration, no card.</p>
    <a href="/en/tools/vat-calculator" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Open the calculator</a>
</div>
HTML,
            ],

            [
                'category_id' => 3,
                'title' => "Article 61 LIVA: why your invoice numbering must be sequential",
                'slug' => 'article-61-liva-sequential-invoice-numbering-luxembourg-mandatory',
                'translation_key' => 'article-61-liva-numerotation-sequentielle-factures-luxembourg-obligatoire',
                'cover_image' => 'https://images.unsplash.com/photo-1568667256549-094345857637?w=1200&h=630&fit=crop',
                'meta_title' => 'Article 61 LIVA Luxembourg: mandatory sequential numbering | faktur.lu',
                'meta_description' => "Article 61 LIVA mandates continuous numbering of your invoices in Luxembourg. Gaps, duplicates, formats: what to know and how to automate to stay compliant.",
                'excerpt' => "Your invoices in Luxembourg must bear a unique, sequential and continuous number. That is Article 61 LIVA. A gap in the sequence or a duplicate can trigger an adjustment. Here is how to comply effortlessly.",
                'published_at' => '2026-06-29 09:00:00',
                'content' => <<<HTML
<p class="lead"><strong>Article 61 LIVA</strong> sets a simple but fundamental rule: your Luxembourg invoices must bear a <strong>unique, sequential and continuous number</strong>. A gap in the sequence or a duplicate can trigger a tax adjustment. Here is the rule explained and how to forget about it.</p>

<h2>What does Article 61 LIVA say exactly?</h2>

<p>Article 61 of the Luxembourg VAT law specifies that every invoice issued by a taxable person must bear a <strong>sequential number</strong>, assigned <strong>chronologically and continuously</strong> per fiscal year.</p>

<p>Three cumulative rules:</p>

<ol>
    <li><strong>Uniqueness</strong>: two invoices can never bear the same number</li>
    <li><strong>Sequentiality</strong>: numbers follow in order (1, 2, 3, 4...)</li>
    <li><strong>Continuity</strong>: no gap (no jump from 5 to 8 without 6 and 7)</li>
</ol>

<p>This obligation applies to <strong>all VAT taxable persons</strong> in Luxembourg, including under exemption (Article 56 ter), even if the invoice bears the "VAT not applicable" mention.</p>

<h2>Why this rule exists</h2>

<p>The aim is simple: <strong>prevent turnover concealment</strong>. Without continuous numbering, a business could easily "forget" certain invoices in its VAT return. Sequentiality lets the AED verify at a glance whether all issued invoices have been declared.</p>

<p>That is also why the <strong>FAIA</strong> (Standard Audit File of the AED) systematically includes the list of invoice numbers for the audited period. A gap detected in FAIA immediately triggers an explanation request.</p>

<h2>Accepted formats</h2>

<p>Article 61 does not impose a unique format. You can use any convention, as long as sequentiality is respected:</p>

<ul>
    <li><strong>Simple numbering</strong>: 1, 2, 3, 4...</li>
    <li><strong>With prefix</strong>: F-001, F-002, F-003...</li>
    <li><strong>With year</strong>: 2026-001, 2026-002, 2026-003...</li>
    <li><strong>With prefix + year</strong>: F-2026-001, F-2026-002...</li>
    <li><strong>With client</strong> (discouraged): ACME-001, ACME-002 (breaks global continuity)</li>
</ul>

<p><strong>Tip</strong>: a number with <strong>year + sequential counter</strong> (e.g., F-2026-001) is the most readable and allows resetting to 1 each year. This is the most common practice in Luxembourg.</p>

<h2>Annual reset: allowed or not?</h2>

<p>Yes, you can restart numbering at <strong>1 each fiscal year</strong>. This is even the most common practice. What matters is that <strong>within one year</strong>, the sequence remains continuous.</p>

<p>Valid example:</p>

<ul>
    <li>F-2025-148 (last invoice of 2025)</li>
    <li>F-2026-001 (first invoice of 2026)</li>
    <li>F-2026-002</li>
    <li>F-2026-003</li>
</ul>

<p>Going from 148 to 001 is allowed because the year changes. But within 2026, you cannot jump from F-2026-001 to F-2026-003 without having issued F-2026-002.</p>

<h2>What happens on error?</h2>

<h3>Case 1 — You issued a duplicate</h3>

<p>The AED considers one of the two invoices as <strong>fictional or fraudulent</strong>. You must:</p>

<ol>
    <li>Issue a <strong>credit note</strong> on one of the two (accounting cancellation)</li>
    <li>Issue a <strong>new invoice</strong> with the next available number</li>
    <li>Keep the 3 documents (the 2 original invoices + the credit note)</li>
</ol>

<h3>Case 2 — You have a gap in the sequence</h3>

<p>More serious case. You must be able to <strong>explain the gap</strong> to the AED. Three acceptable explanations:</p>

<ul>
    <li>The invoice was issued then <strong>cancelled by credit note</strong> (you keep both documents)</li>
    <li>Technical error: you created an unfinished draft. In this case, better to finalise and archive it, even if the service did not happen (with "invoice cancelled" mention)</li>
    <li>Documented IT bug (rare)</li>
</ul>

<p>Without a credible explanation, the AED presumes concealment and may <strong>estimate</strong> missing turnover on a lump-sum basis.</p>

<h3>Case 3 — You want to delete a finalised invoice</h3>

<p>Legally impossible. A finalised invoice must be archived 10 years (Article 16 Code of Commerce). To cancel:</p>

<ul>
    <li>Issue a <strong>credit note</strong> of the same amount (separate numbering typically: CN-2026-001)</li>
    <li>Keep both documents (invoice + credit note)</li>
</ul>

<h2>Classic pitfalls to avoid</h2>

<ol>
    <li><strong>Manual Excel numbering</strong>: very high risk of duplicates or gaps (forgetting the last invoice, broken formula, bad copy-paste)</li>
    <li><strong>Multiple parallel series</strong> (one per client or project): hard to justify in audit</li>
    <li><strong>Mid-year reset</strong> without changing fiscal year: forbidden</li>
    <li><strong>Deleting finalised invoices</strong> without replacement credit: violates Article 16 Code of Commerce + Article 61 LIVA</li>
</ol>

<h2>How to automate it effortlessly</h2>

<p>An invoicing software like <a href="/en" class="text-primary-500 hover:underline font-medium">faktur.lu</a> handles numbering for you:</p>

<ul>
    <li><strong>Automatic sequential numbering</strong>: impossible to create a duplicate</li>
    <li><strong>No gap possible</strong>: each finalisation increments the counter</li>
    <li><strong>Customisable format</strong>: prefix, year, counter length, all configurable</li>
    <li><strong>Automatic annual reset</strong>: counter back to 1 every 1 January (or not, your preference)</li>
    <li><strong>Separate sequences</strong> for invoices, credit notes and quotes (independent but continuous counters)</li>
    <li><strong>Uniqueness check</strong> at each creation</li>
</ul>

<p>With this, Article 61 LIVA is no longer a concern: your numbering is compliant by construction.</p>

<h2>FAQ — Article 61 LIVA</h2>

<h3>What if I invoice under VAT exemption (Article 56 ter)?</h3>
<p>Article 61 LIVA applies <strong>anyway</strong>. Every issued invoice, even without collected VAT, must bear a sequential and continuous number.</p>

<h3>Can I have numbering per client (ACME-001, ACME-002...)?</h3>
<p>Technically yes — but strongly discouraged. In audit, the AED requires <strong>global</strong> sequentiality. You will then need to prove no gap across all client series, which is complex and risky.</p>

<h3>Are quotes concerned?</h3>
<p>No, Article 61 LIVA only concerns <strong>invoices and credit notes</strong>. You can number quotes as you like (sequential numbering still recommended for your own tracking).</p>

<h3>Can I have separate series for invoices and credit notes?</h3>
<p>Yes, even recommended. One series for invoices (F-2026-001, F-2026-002...) and one series for credit notes (CN-2026-001, CN-2026-002...). Each must be continuous, but independent.</p>

<h3>What if I migrate to a new software mid-year?</h3>
<p>You must <strong>continue the sequence</strong> in the new software. If you were at F-2026-148 in the old one, your first invoice in the new one must be F-2026-149. faktur.lu allows configuring the starting counter at signup to avoid the gap.</p>

<div class="mt-8 p-6 bg-primary-50 rounded-2xl border border-primary-200">
    <h3 class="text-lg font-bold text-primary-900 mb-2">Compliant numbering, no thought required</h3>
    <p class="text-primary-800 mb-4">faktur.lu automatically handles your sequential numbering, Article 61 LIVA respected by construction. Customisable format, configurable annual reset.</p>
    <a href="/en/register" class="inline-block bg-primary-500 hover:bg-primary-600 text-white font-semibold px-6 py-3 rounded-xl">Get started free</a>
</div>
HTML,
            ],
        ];
    }
}

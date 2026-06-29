<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Traductions ANGLAISES des articles programmés juillet-août 2026.
 * translation_key = slug FR ; slug = slug anglais localisé ; locale='en'.
 * Idempotent (skip si slug existe). published_at = même lundi que le FR.
 */
class BlogScheduledSummer2026EnSeeder extends Seeder
{
    public function run(): void
    {
        $authorId = DB::table('users')->orderBy('id')->first()?->id ?? 1;

        // Résout l'ID de catégorie par SLUG (robuste : les IDs peuvent différer
        // d'une base à l'autre ; fallback sur 'guides' si une catégorie manque).
        $catMap = [];
        foreach ([1 => 'guides', 2 => 'reglementation', 3 => 'freelances', 4 => 'actualites', 5 => 'guide-creation-entreprise'] as $oldId => $slug) {
            $catMap[$oldId] = \App\Models\BlogCategory::where('slug', $slug)->value('id') ?? 1;
        }

        foreach ($this->articles() as $article) {
            if (DB::table('blog_posts')->where('slug', $article['slug'])->exists()) {
                $this->command?->info("Skip EN: {$article['slug']}");
                continue;
            }

            DB::table('blog_posts')->insert([
                'author_id' => $authorId,
                'category_id' => $catMap[$article['category_id']] ?? 1,
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
            // ===== 2026-07-06 =====
            [
                'category_id' => 1,
                'translation_key' => 'travailler-avec-fiduciaire-luxembourg-guide',
                'slug' => 'working-with-accounting-firm-luxembourg-guide',
                'cover_image' => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=1200&h=630&fit=crop',
                'title' => 'Working with an accounting firm in Luxembourg: the practical guide',
                'meta_title' => 'Working with an accounting firm in Luxembourg | faktur.lu',
                'meta_description' => 'When you need an accounting firm in Luxembourg, how to choose one, and how to send clean data to save time (and money). Practical 2026 guide.',
                'excerpt' => 'A good accounting firm saves you time and prevents errors - provided you choose well and send clean data. Here is how to get the most out of it.',
                'published_at' => '2026-07-06 09:00:00',
                'content' => <<<HTML
<p class="lead">In Luxembourg, most freelancers and SMEs delegate their bookkeeping to an <strong>accounting firm</strong> (fiduciaire). Well chosen and well supplied, it saves you valuable time and avoids costly mistakes. Here is how to work with it efficiently.</p>

<h2>What is an accounting firm and what does it do?</h2>
<p>An accounting firm handles all or part of your accounting and tax obligations: bookkeeping, VAT returns, annual accounts, income tax return, advice. In Luxembourg it is not legally mandatory for a sole trader, but it is strongly recommended once your activity goes beyond a handful of invoices per month.</p>

<h2>When should you hire one?</h2>
<ul>
    <li>You exceed the VAT exemption threshold and must file VAT regularly.</li>
    <li>You set up a company (Sàrl, Sàrl-S) with mandatory double-entry accounting.</li>
    <li>You lack the time or peace of mind for your tax obligations.</li>
    <li>You want to optimise (deductible costs, depreciation) without taking risks during an AED tax audit.</li>
</ul>

<h2>How to choose the right firm</h2>
<ul>
    <li><strong>Specialisation</strong>: a firm used to freelancers and small businesses will understand you better than one focused on large groups.</li>
    <li><strong>Responsiveness</strong>: ask a question before signing and check how fast they reply.</li>
    <li><strong>Tools</strong>: a firm that accepts digital data (accounting exports, FAIA) rather than stacks of PDFs saves time on both sides.</li>
    <li><strong>Transparent fees</strong>: monthly flat fee or per task? What exactly is included?</li>
</ul>
<p>The <strong>Ordre des Experts-Comptables (OEC)</strong> maintains an official directory of approved professionals (oec.lu).</p>

<h2>The real lever: send clean data</h2>
<p>The main source of wasted time (and cost) on the firm's side is <strong>re-entry</strong>: taking your PDF or Excel invoices and keying them again into its software. The more structured your data, the fewer hours it bills and the fewer errors occur.</p>
<ul>
    <li>Issue compliant invoices with continuous sequential numbering.</li>
    <li>Centralise everything in one tool rather than scattered files.</li>
    <li>Provide ready-to-import exports: FAIA 2.01, Sage BOB 50, Sage 100 or CSV.</li>
</ul>

<h2>faktur.lu: a free portal for your accounting firm</h2>
<p>With faktur.lu you invoice as usual, and your accounting firm accesses your data <strong>read-only</strong> through a dedicated accountant portal: it retrieves your exports (FAIA 2.01, Sage, CSV) in one click - no back-and-forth, no re-entry. The portal is <strong>free for the firm</strong>. If your firm does not use faktur.lu yet, tell them about our <a href="/en/partners">partner programme</a>.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Invoice cleanly - your accountant will thank you</h3>
    <p class="text-primary-800 mb-4">faktur.lu generates Luxembourg-compliant invoices and exports ready for your accounting firm (FAIA, Sage, CSV).</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try it free</a>
</div>
HTML,
            ],

            // ===== 2026-07-13 =====
            [
                'category_id' => 1,
                'translation_key' => 'faire-devis-professionnel-luxembourg',
                'slug' => 'create-professional-quote-luxembourg',
                'cover_image' => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=1200&h=630&fit=crop',
                'title' => 'Creating a professional quote in Luxembourg: details, validity and conversion',
                'meta_title' => 'Creating a professional quote in Luxembourg: full guide | faktur.lu',
                'meta_description' => 'How to write a professional quote in Luxembourg: details to include, validity period, legal value and conversion into an invoice. Template and best practices 2026.',
                'excerpt' => 'A quote commits your client as much as you. Here are the details to include, its legal value, validity period and how to convert it cleanly into an invoice.',
                'published_at' => '2026-07-13 09:00:00',
                'content' => <<<HTML
<p class="lead">Before the invoice, there is often the <strong>quote</strong>. Well written, it frames the work, reassures the client and prevents disputes. Here is how to create a professional quote in Luxembourg.</p>

<h2>What is a quote for?</h2>
<p>A quote is a <strong>priced commercial offer</strong>: it describes the service or goods, the price and the conditions. It is not mandatory in Luxembourg, but strongly recommended as soon as a job carries some stakes: it protects both parties by setting the scope and price before starting.</p>

<h2>The details to include in a quote</h2>
<ul>
    <li>Your full contact details (name, address, VAT number if liable).</li>
    <li>The client's contact details.</li>
    <li>A <strong>quote number</strong> and the issue date.</li>
    <li>The breakdown of services / products, with quantities and unit prices.</li>
    <li>The amount <strong>excluding VAT (HT)</strong>, the applicable VAT and the <strong>total including VAT (TTC)</strong>.</li>
    <li>The <strong>validity period</strong> of the offer.</li>
    <li>Payment terms and, where applicable, the deposit requested.</li>
</ul>
<p>If you are under the VAT exemption scheme, state the relevant mention instead of VAT.</p>

<h2>What legal value?</h2>
<p>A quote <strong>signed and accepted</strong> by the client has contractual value: it binds both parties to the agreed scope and price. It is your best protection in case of disagreement. So have the quote dated and signed ("Agreed") before you start.</p>

<h2>Validity period</h2>
<p>Always state a validity period (e.g. 30 days). After that, you are no longer bound by the quoted price - useful if your costs change. It is also a sales lever: a time-limited offer encourages a decision.</p>

<h2>From quote to invoice</h2>
<p>Once the quote is accepted and the work delivered, you issue the <strong>invoice</strong>. To avoid errors and double entry, the best approach is to <strong>convert the quote into an invoice</strong>: lines, amounts and contact details are carried over, and all that remains is to assign the sequential invoice number.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Quote, then invoice in one click</h3>
    <p class="text-primary-800 mb-4">With faktur.lu, create your quotes, get them accepted, and convert them into compliant invoices with no re-entry.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Create a quote for free</a>
</div>
HTML,
            ],

            // ===== 2026-07-20 =====
            [
                'category_id' => 1,
                'translation_key' => 'facturation-projet-forfait-regie-luxembourg',
                'slug' => 'project-billing-fixed-price-vs-time-luxembourg',
                'cover_image' => 'https://images.unsplash.com/photo-1542744173-8e7e53415bb0?w=1200&h=630&fit=crop',
                'title' => 'Project billing in Luxembourg: fixed price or time and materials?',
                'meta_title' => 'Project billing: fixed price vs time in Luxembourg | faktur.lu',
                'meta_description' => 'Fixed price or time and materials to bill a project in Luxembourg? Pros, risks, deposits and best practices to invoice your work without bad surprises.',
                'excerpt' => 'Billing a project at a fixed price or on a time basis changes everything: risk, cash flow, client relationship. Here is how to choose and invoice each model cleanly.',
                'published_at' => '2026-07-20 09:00:00',
                'content' => <<<HTML
<p class="lead">When billing a project - development, design, consulting, construction - two main models compete: the <strong>fixed price</strong> and <strong>time and materials</strong>. The right choice depends on the level of uncertainty and your cash flow.</p>

<h2>Fixed price: a set price for a defined scope</h2>
<p>You announce a total price for a specific deliverable. The client knows their budget in advance, which is reassuring. But the risk is yours: if the project overruns, you absorb the overage.</p>
<p><strong>When to use:</strong> clear and stable scope, work you know well.</p>
<p><strong>Best practices:</strong> frame the scope precisely in the quote, plan a clause for out-of-scope requests, and split the payment (deposit + milestones + balance).</p>

<h2>Time and materials: billing time spent</h2>
<p>You bill the hours or days actually worked, at an agreed rate. The overrun risk moves to the client, and you are paid for all the time invested. In return, the client has no guaranteed budget.</p>
<p><strong>When to use:</strong> unclear or evolving scope, long engagements, maintenance.</p>
<p><strong>Best practices:</strong> track your time precisely, provide a clear timesheet, and possibly set a reassuring cap.</p>

<h2>Comparison</h2>
<table class="w-full my-4">
    <thead><tr><th class="text-left p-2 bg-slate-100">Criterion</th><th class="text-left p-2 bg-slate-100">Fixed price</th><th class="text-left p-2 bg-slate-100">Time and materials</th></tr></thead>
    <tbody>
        <tr><td class="p-2 border-b">Overrun risk</td><td class="p-2 border-b">On you</td><td class="p-2 border-b">On the client</td></tr>
        <tr><td class="p-2 border-b">Client budget visibility</td><td class="p-2 border-b">High</td><td class="p-2 border-b">Low</td></tr>
        <tr><td class="p-2 border-b">Ideal for</td><td class="p-2 border-b">Stable scope</td><td class="p-2 border-b">Evolving scope</td></tr>
    </tbody>
</table>

<h2>Deposits: protect your cash flow</h2>
<p>On a project spanning several weeks, do not bill everything at the end. Ask for a <strong>deposit at the start</strong> and bill at <strong>milestones</strong>. This secures your cash flow and limits the risk of unpaid invoices.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Fixed price, time, deposits: faktur.lu handles it all</h3>
    <p class="text-primary-800 mb-4">Time tracking, deposit and project invoices - compliant and in a few clicks.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try it free</a>
</div>
HTML,
            ],

            // ===== 2026-07-27 =====
            [
                'category_id' => 2,
                'translation_key' => 'cotisations-sociales-independant-ccss-luxembourg-2026',
                'slug' => 'social-contributions-self-employed-ccss-luxembourg-2026',
                'cover_image' => 'https://images.unsplash.com/photo-1554224154-26032ffc0d07?w=1200&h=630&fit=crop',
                'title' => 'Social contributions for the self-employed in Luxembourg (CCSS) in 2026',
                'meta_title' => 'Self-employed social contributions Luxembourg (CCSS) 2026 | faktur.lu',
                'meta_description' => 'How much does a self-employed person pay in Luxembourg? CCSS rates, basis of calculation, minimum base and how it works. 2026 guide to plan your social charges.',
                'excerpt' => 'As a self-employed person in Luxembourg, you contribute to the CCSS on your income. Here is how it works, the indicative rates and how to plan for these charges.',
                'published_at' => '2026-07-27 09:00:00',
                'content' => <<<HTML
<p class="lead">When starting out as self-employed in Luxembourg, <strong>social contributions</strong> are often the unpleasant surprise. They fund your social protection (health, pension, long-term care) and are calculated on your income. Here is the essential to plan ahead.</p>

<h2>What is the CCSS?</h2>
<p>The <strong>Centre Commun de la Sécurité Sociale (CCSS)</strong> is the body that centralises affiliation and collection of social contributions in Luxembourg. From the start of your self-employed activity, you must register with it.</p>

<h2>What do you contribute on?</h2>
<p>Contributions are calculated on your <strong>professional income</strong> (the profit, not the turnover). At the start, affiliation is often on a provisional basis, later adjusted according to your actual income reported by the tax administration.</p>

<h2>What rates? (order of magnitude)</h2>
<p>In total, a self-employed person's contributions amount to <strong>about 25 %</strong> of income, spread across the various branches:</p>
<table class="w-full my-4">
    <thead><tr><th class="text-left p-2 bg-slate-100">Branch</th><th class="text-left p-2 bg-slate-100">Indicative rate</th></tr></thead>
    <tbody>
        <tr><td class="p-2 border-b">Pension insurance</td><td class="p-2 border-b">~16 %</td></tr>
        <tr><td class="p-2 border-b">Health insurance (care + cash benefits)</td><td class="p-2 border-b">~6 %</td></tr>
        <tr><td class="p-2 border-b">Long-term care insurance</td><td class="p-2 border-b">~1.4 %</td></tr>
        <tr><td class="p-2 border-b">Accident and occupational health</td><td class="p-2 border-b">~1 %</td></tr>
    </tbody>
</table>
<p class="text-sm text-slate-500">Exact rates change and depend on your situation. Always refer to the CCSS (ccss.public.lu) or your accounting firm for current figures.</p>

<h2>The minimum and maximum base</h2>
<p>There is a <strong>minimum base</strong>: even with low income, you contribute on a floor (linked to the social minimum wage). At the other end, contributions are capped above a certain income. In practice, even a very modest income still generates minimum contributions - to anticipate in your cash flow.</p>

<h2>How to plan well</h2>
<ul>
    <li><strong>Set aside ~25 %</strong> of your profit for contributions, on top of tax.</li>
    <li>Expect an <strong>adjustment</strong>: if your income rises, a catch-up of contributions will follow.</li>
    <li>Keep clear accounting to know your real profit at any time.</li>
</ul>

<div class="bg-amber-50 border-l-4 border-amber-500 p-4 my-6">
    <p class="font-semibold">Key takeaway</p>
    <p>Social contributions are not tax: they fund your pension and health cover. But they weigh ~25 % of income: provision for them from the very first invoice.</p>
</div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Track your income in real time</h3>
    <p class="text-primary-800 mb-4">faktur.lu tracks your turnover and expenses to help you set aside contributions and tax without stress.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try it free</a>
</div>
HTML,
            ],

            // ===== 2026-08-03 =====
            [
                'category_id' => 3,
                'translation_key' => 'fixer-tjm-freelance-luxembourg',
                'slug' => 'set-daily-rate-freelancer-luxembourg',
                'cover_image' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200&h=630&fit=crop',
                'title' => 'Setting your daily rate as a freelancer in Luxembourg',
                'meta_title' => 'Setting your daily rate as a freelancer in Luxembourg: 2026 method | faktur.lu',
                'meta_description' => 'How to calculate your daily rate as a freelancer in Luxembourg: charges, contributions, non-billable days and a simple method so you do not undersell yourself.',
                'excerpt' => 'Many freelancers set their rate by feel and end up working at a loss. Here is a simple method to calculate a daily rate that truly covers your costs.',
                'published_at' => '2026-08-03 09:00:00',
                'content' => <<<HTML
<p class="lead">The classic trap for new freelancers: divide a former salary by 20 days and make that your daily rate. The result: you work at a loss without realising it. Here is how to calculate a realistic <strong>daily rate</strong> in Luxembourg.</p>

<h2>Your daily rate is not "salary / 20"</h2>
<p>An employee is paid for ~220 days a year, holidays and sick leave included, with no charges to manage. A freelancer, on the other hand, does not bill every day and bears all the charges. Your rate must cover far more than your desired "salary".</p>

<h2>The 4 items to include</h2>
<ul>
    <li><strong>Your desired net income.</strong> What you actually want to keep.</li>
    <li><strong>Social contributions (~25 %).</strong> See our article on <a href="/en/blog/social-contributions-self-employed-ccss-luxembourg-2026">CCSS contributions</a>.</li>
    <li><strong>Income tax.</strong> Progressive, to provision for.</li>
    <li><strong>Your business costs.</strong> Software, insurance, accountant, equipment, training, travel.</li>
</ul>

<h2>The days actually billable</h2>
<p>From ~252 working days, subtract holidays, public holidays, possible sick leave, and above all <strong>non-billable</strong> time: prospecting, admin, quotes, training. In practice, many freelancers bill <strong>180 to 200 days a year</strong>, often fewer in the first year.</p>

<h2>The calculation method</h2>
<ol>
    <li>Add up your <strong>annual needs</strong>: net income + contributions + provisioned tax + business costs.</li>
    <li>Divide by your <strong>realistic number of billable days</strong>.</li>
    <li>You get your <strong>floor daily rate</strong> - below it, you lose money.</li>
</ol>
<p><strong>Example:</strong> if your total annual needs are EUR 90,000 and you bill 180 days, your floor daily rate is EUR 500. Any lower rate means working at a loss.</p>

<h2>Beyond the floor: value</h2>
<p>The floor rate covers your costs; your final price then depends on your <strong>value</strong>: expertise, scarcity, results delivered to the client. Do not bill your time - bill what you make the client gain.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Track your time and profitability</h3>
    <p class="text-primary-800 mb-4">With faktur.lu, track billed days, expenses and turnover to check that your daily rate delivers on its promise.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try it free</a>
</div>
HTML,
            ],

            // ===== 2026-08-10 =====
            [
                'category_id' => 2,
                'translation_key' => 'tva-deductible-depenses-professionnelles-luxembourg',
                'slug' => 'deductible-vat-business-expenses-luxembourg',
                'cover_image' => 'https://images.unsplash.com/photo-1599658880436-c61792e70672?w=1200&h=630&fit=crop',
                'title' => 'Recovering VAT on business expenses in Luxembourg',
                'meta_title' => 'Deductible VAT in Luxembourg: recover VAT on your purchases | faktur.lu',
                'meta_description' => 'How to recover VAT on your business expenses in Luxembourg: conditions, deductible and non-deductible expenses, and how the return works. 2026 guide.',
                'excerpt' => 'The VAT you pay on your business purchases is generally recoverable. Here are the conditions, the special cases and how it works in practice.',
                'published_at' => '2026-08-10 09:00:00',
                'content' => <<<HTML
<p class="lead">When you are VAT-liable in Luxembourg, you charge VAT to your clients (output VAT), but you can also <strong>recover the VAT paid on your business purchases</strong> (deductible VAT). It is a central mechanism - here is how it works.</p>

<h2>The principle: output VAT - deductible VAT</h2>
<p>At each return, you pay the State the difference between the VAT you <strong>collected</strong> on your sales and the VAT <strong>deductible</strong> on your purchases. If you have invested heavily over a period, deductible VAT may even exceed collected VAT: you then get a VAT credit.</p>

<h2>The conditions to deduct VAT</h2>
<ul>
    <li>You are VAT-<strong>liable</strong> (not under the exemption scheme).</li>
    <li>The expense is <strong>business-related</strong> and linked to your taxable activity.</li>
    <li>You hold a <strong>compliant invoice</strong> showing the VAT and the supplier's VAT number.</li>
</ul>

<h2>Generally deductible expenses</h2>
<p>Equipment and supplies, professional software and subscriptions, fees (accountant, lawyer), rent for business premises, telecommunications, training - provided they serve your activity.</p>

<h2>The special cases (limited or excluded deduction)</h2>
<p>Some expenses are partly or fully excluded from the right to deduct, particularly when they have a private or entertainment component. Mixed-use expenses (private and business), certain entertainment or vehicle costs may be limited. When in doubt, check with the <strong>AED</strong> or your accounting firm.</p>
<p class="text-sm text-slate-500">The precise deduction rules are in the Luxembourg VAT law and may change. Refer to the AED or a professional.</p>

<h2>How it works in practice</h2>
<p>You record your expenses with their VAT throughout the period, then carry the total deductible VAT into your <strong>VAT return</strong> (monthly, quarterly or annual depending on your scheme). Well-kept expense accounting avoids leaving recoverable VAT on the table - money that would otherwise be lost.</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Stop losing recoverable VAT</h3>
    <p class="text-primary-800 mb-4">faktur.lu records your expenses and their VAT, and prepares clean data for your return and your accounting firm.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try it free</a>
</div>
HTML,
            ],

            // ===== 2026-08-17 =====
            [
                'category_id' => 1,
                'translation_key' => 'facture-acompte-luxembourg-regles-mentions',
                'slug' => 'deposit-invoice-luxembourg-rules',
                'cover_image' => 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=1200&h=630&fit=crop',
                'title' => 'Deposit invoice in Luxembourg: rules, details and VAT',
                'meta_title' => 'Deposit invoice in Luxembourg: rules and details | faktur.lu',
                'meta_description' => 'How to issue a deposit invoice in Luxembourg: mandatory details, VAT treatment, and link with the final invoice. Practical 2026 guide.',
                'excerpt' => 'Asking for a deposit protects your cash flow, but it means a real invoice with its own VAT rules. Here is how to do it correctly.',
                'published_at' => '2026-08-17 09:00:00',
                'content' => <<<HTML
<p class="lead">Asking for a <strong>deposit</strong> before starting a job is an excellent way to protect your cash flow. But a deposit is not a simple "payment": it gives rise to a genuine <strong>deposit invoice</strong>, with its own rules. Here is how to do it in Luxembourg.</p>

<h2>Why invoice a deposit?</h2>
<p>On a long engagement or a project, the deposit secures your cash flow, commits the client and reduces the risk of unpaid invoices. It is especially useful in <a href="/en/blog/project-billing-fixed-price-vs-time-luxembourg">project billing</a>.</p>

<h2>The details of a deposit invoice</h2>
<p>A deposit invoice is a full invoice: it must include the usual mandatory details (your and the client's contact details, number and date, VAT number), plus:</p>
<ul>
    <li>A clear mention that it is a <strong>deposit</strong> and which order/quote it relates to.</li>
    <li>The <strong>deposit amount</strong> excluding VAT, the applicable VAT and the total including VAT.</li>
    <li>A sequential number, like any invoice.</li>
</ul>

<h2>VAT treatment</h2>
<p>Important: for a service, VAT generally becomes <strong>due on receipt of the deposit</strong>, for the amount received. In other words, you declare and pay the VAT on the deposit as soon as it is received, without waiting for the final invoice.</p>
<p class="text-sm text-slate-500">The moment VAT becomes due depends on the nature of the transaction. When in doubt, check with the AED or your accounting firm.</p>

<h2>The final invoice</h2>
<p>At the end of the job, you issue the <strong>balance invoice</strong>. It shows the total amount of the work, then <strong>deducts the deposit already invoiced</strong> (and its VAT), so only the remaining amount is billed. The client never pays the same sum twice, and the total VAT remains correct.</p>

<div class="bg-blue-50 border-l-4 border-blue-500 p-4 my-6">
    <p class="font-semibold">In short</p>
    <p>Deposit = real invoice, with VAT due on receipt. The final invoice shows the total and deducts the deposit already invoiced.</p>
</div>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Deposits and balances handled automatically</h3>
    <p class="text-primary-800 mb-4">faktur.lu generates your deposit and balance invoices compliantly, with the correct VAT treatment.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try it free</a>
</div>
HTML,
            ],

            // ===== 2026-08-24 =====
            [
                'category_id' => 2,
                'translation_key' => 'impot-independant-luxembourg-2026',
                'slug' => 'self-employed-income-tax-luxembourg-2026',
                'cover_image' => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=1200&h=630&fit=crop',
                'title' => 'Income tax for the self-employed in Luxembourg: how it works in 2026',
                'meta_title' => 'Self-employed income tax in Luxembourg 2026: guide | faktur.lu',
                'meta_description' => 'How is the self-employed person taxed in Luxembourg? Profit, progressive scale, quarterly advances, deductible charges. 2026 guide to plan your income tax.',
                'excerpt' => "A self-employed person's tax is calculated not on turnover but on profit, on a progressive scale. Here is the essential to plan it well.",
                'published_at' => '2026-08-24 09:00:00',
                'content' => <<<HTML
<p class="lead">After <a href="/en/blog/social-contributions-self-employed-ccss-luxembourg-2026">social contributions</a>, the other major item for the self-employed is <strong>income tax</strong>. Good news: you are not taxed on everything you collect. Here is how it works in Luxembourg.</p>

<h2>You are taxed on profit, not turnover</h2>
<p>The self-employed person's taxable base is <strong>profit</strong>: your turnover minus your <strong>deductible business charges</strong> (expenses, purchases, depreciation, social contributions…). The more rigorous your accounting, the more you deduct what you are entitled to.</p>

<h2>A progressive scale</h2>
<p>Luxembourg income tax is <strong>progressive</strong>: the higher the taxable income, the higher the marginal rate, in brackets. A modest slice of income is not taxed, then rates rise up to a high top marginal rate. On top comes the <strong>employment fund contribution</strong> (solidarity tax). Your <strong>tax class</strong> (depending on your family situation) influences the calculation.</p>
<p class="text-sm text-slate-500">The exact brackets and rates of the scale are set by law and revised regularly. Refer to the tax administration (ACD, impotsdirects.public.lu) or your accounting firm for the scale in force.</p>

<h2>Quarterly advances</h2>
<p>The ACD generally asks you to pay <strong>quarterly tax advances</strong>, estimated on the basis of your past income. At the annual return, the balance is settled: you pay the difference or get a refund. If your income rises, expect higher advances the following year.</p>

<h2>The annual return</h2>
<p>Each year, you fill in your tax return (online via MyGuichet or with your accounting firm). You report your profit and deductible items. This is where all the supporting documents collected during the year count.</p>

<h2>How to plan well</h2>
<ul>
    <li><strong>Provision for tax</strong> on top of the ~25 % social contributions: do not spend everything you collect.</li>
    <li>Keep all your <strong>expense receipts</strong>: every deductible business expense reduces your taxable base.</li>
    <li>Keep your accounting up to date to know your real profit and avoid surprises at settlement.</li>
</ul>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Keep an eye on your profit all year</h3>
    <p class="text-primary-800 mb-4">faktur.lu tracks income and expenses to give you a clear view of your profit - and anticipate tax and contributions.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try it free</a>
</div>
HTML,
            ],

            // ===== 2026-08-31 =====
            [
                'category_id' => 5,
                'translation_key' => 'sarl-s-vs-entreprise-individuelle-luxembourg',
                'slug' => 'sarl-s-vs-sole-proprietorship-luxembourg',
                'cover_image' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=1200&h=630&fit=crop',
                'title' => 'SARL-S or sole proprietorship in Luxembourg: which status to choose?',
                'meta_title' => 'SARL-S or sole proprietorship in Luxembourg? Comparison | faktur.lu',
                'meta_description' => 'SARL-S or sole proprietorship in Luxembourg: liability, capital, accounting, taxation. A clear comparison to choose the right status in 2026.',
                'excerpt' => 'Start as a sole proprietorship or set up a SARL-S? Both have advantages. Here is a clear comparison to choose based on your situation.',
                'published_at' => '2026-08-31 09:00:00',
                'content' => <<<HTML
<p class="lead">When starting out in Luxembourg, one question keeps coming up: stay a <strong>sole proprietorship</strong> or set up a <strong>SARL-S</strong> (simplified limited liability company)? Here are the real differences to decide.</p>

<h2>The sole proprietorship</h2>
<p>The simplest form: you operate in your own name, without creating a separate legal entity. No capital, little formalism.</p>
<p><strong>The key point:</strong> your liability is <strong>unlimited</strong> - your personal assets can be engaged in case of business debts.</p>

<h2>The SARL-S (simplified company)</h2>
<p>The SARL-S was created to make entrepreneurship easier. It is reserved for <strong>natural persons</strong>, with <strong>reduced start-up capital</strong> (between EUR 1 and EUR 12,000), and offers <strong>liability limited</strong> to contributions: your personal assets are in principle protected.</p>
<p>In return, it involves more formalism: incorporation, double-entry accounting, company obligations, and part of the profit must be allocated to a legal reserve until the capital of a standard SARL is reached.</p>

<h2>Comparison</h2>
<table class="w-full my-4">
    <thead><tr><th class="text-left p-2 bg-slate-100">Criterion</th><th class="text-left p-2 bg-slate-100">Sole proprietorship</th><th class="text-left p-2 bg-slate-100">SARL-S</th></tr></thead>
    <tbody>
        <tr><td class="p-2 border-b">Liability</td><td class="p-2 border-b">Unlimited (personal assets)</td><td class="p-2 border-b">Limited to contributions</td></tr>
        <tr><td class="p-2 border-b">Capital</td><td class="p-2 border-b">None</td><td class="p-2 border-b">EUR 1 to 12,000</td></tr>
        <tr><td class="p-2 border-b">Formalism</td><td class="p-2 border-b">Minimal</td><td class="p-2 border-b">Higher (company)</td></tr>
        <tr><td class="p-2 border-b">Accounting</td><td class="p-2 border-b">Simplified possible</td><td class="p-2 border-b">Double-entry</td></tr>
        <tr><td class="p-2 border-b">Taxation</td><td class="p-2 border-b">Income tax</td><td class="p-2 border-b">Corporate tax</td></tr>
    </tbody>
</table>

<h2>How to choose?</h2>
<ul>
    <li><strong>Low-risk activity, light start</strong> → the sole proprietorship is often enough.</li>
    <li><strong>Financial risk, need to protect your assets, "company" image</strong> → the SARL-S is attractive.</li>
</ul>
<p>The choice has lasting legal and tax consequences: get support from an <a href="/en/blog/working-with-accounting-firm-luxembourg-guide">accounting firm</a> or the House of Entrepreneurship before deciding.</p>
<p class="text-sm text-slate-500">The exact conditions (capital, legal reserve, authorisations) are set by law and detailed on guichet.lu and with the Luxembourg Business Registers (LBR).</p>

<div class="bg-primary-50 rounded-xl p-6 mt-8">
    <h3 class="text-lg font-semibold text-primary-900 mb-2">Whatever your status, invoice compliantly</h3>
    <p class="text-primary-800 mb-4">Sole proprietorship or SARL-S, faktur.lu generates Luxembourg-compliant invoices and the exports for your accountant.</p>
    <a href="/register" class="inline-block bg-primary-500 text-white font-semibold px-6 py-3 rounded-xl hover:bg-primary-600">Try it free</a>
</div>
HTML,
            ],
        ];
    }
}

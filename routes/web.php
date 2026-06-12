<?php

use App\Http\Controllers\AccountantSettingsController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\AccountingExportController;
use App\Http\Controllers\AccountingSettingsController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\FeaturePageController;
use App\Http\Controllers\ToolsController;
use App\Models\BlogPost;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\AuditExportController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BusinessSettingsController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompanyLookupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FaiaValidatorController;
use App\Http\Controllers\EmailProviderController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\PeppolExportController;
use App\Http\Controllers\InvoiceEmailController;
use App\Http\Controllers\InvoiceItemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\QuoteItemController;
use App\Http\Controllers\FiscalSummaryController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\RevenueBookController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TimeEntryController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\InteractionController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\HR;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// CSRF token refresh endpoint - used when tab becomes visible after being hidden
Route::get('/api/csrf-token', function () {
    return response()->json([
        'csrf_token' => csrf_token(),
    ]);
})->middleware('web');

/*
|--------------------------------------------------------------------------
| Locale Redirect
|--------------------------------------------------------------------------
|
| Redirect root URL to the appropriate locale based on browser detection.
|
*/

Route::get('/', [LocaleController::class, 'redirect'])->name('home.redirect');
Route::get('/switch-locale/{locale}', [LocaleController::class, 'switchLocale'])
    ->where('locale', 'fr|de|en|lb|pt')
    ->name('locale.switch');

// Drip email unsubscribe (no auth required)
Route::get('/drip/unsubscribe/{user}/{hash}', function (\App\Models\User $user, string $hash) {
    $expected = hash('sha256', $user->email . config('app.key'));
    if (! hash_equals($expected, $hash)) {
        abort(403);
    }
    $user->update(['drip_unsubscribed' => true]);
    $view = ($user->locale ?? null) === 'pt'
        ? 'emails.pt.unsubscribed'
        : 'emails.unsubscribed';
    return view($view);
})->name('drip.unsubscribe');

// Newsletter routes (no auth required)
Route::post('/newsletter/subscribe', [\App\Http\Controllers\NewsletterController::class, 'subscribe'])
    ->middleware(['honeypot', 'throttle:6,1'])
    ->name('newsletter.subscribe');
Route::get('/newsletter/confirm/{token}', [\App\Http\Controllers\NewsletterController::class, 'confirm'])
    ->name('newsletter.confirm');

/*
|--------------------------------------------------------------------------
| Sitemap Routes
|--------------------------------------------------------------------------
|
| XML sitemaps for SEO with multilingual support (hreflang).
|
*/

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
Route::get('/sitemap-pages.xml', [SitemapController::class, 'pages'])->name('sitemap.pages');
Route::get('/sitemap-blog.xml', [SitemapController::class, 'blog'])->name('sitemap.blog');

/*
|--------------------------------------------------------------------------
| Public Localized Routes
|--------------------------------------------------------------------------
|
| All public-facing pages with locale prefix (e.g., /fr/, /de/, /en/, /lb/).
|
*/

/*
|--------------------------------------------------------------------------
| 301 redirects: anciens slugs blog → nouveaux slugs
| DOIT être déclaré AVANT le groupe localisé (sinon blog.show capture en premier).
|--------------------------------------------------------------------------
*/
foreach (\Database\Seeders\UpdateBlog2025To2026SlugsSeeder::SLUG_MAP as $oldSlug => $newSlug) {
    Route::get('/{locale}/blog/' . $oldSlug, fn (string $locale) => redirect()->route('blog.show', ['locale' => $locale, 'post' => $newSlug], 301))
        ->where('locale', 'fr|de|en|lb|pt');
}

// Redirects 301 locale-scopés : refonte d'articles dont la base légale citée était fausse.
// FR seulement (DE/EN/LB/PT seront refondus dans un batch ultérieur).
Route::get('/fr/blog/article-21-liva-autoliquidation-tva-b2b-intra-ue-freelance-luxembourg',
    fn () => redirect()->route('blog.show', [
        'locale' => 'fr',
        'post' => 'article-17-liva-autoliquidation-tva-b2b-intra-ue-freelance-luxembourg',
    ], 301)
);

Route::get('/fr/blog/article-61-liva-numerotation-sequentielle-factures-luxembourg-obligatoire',
    fn () => redirect()->route('blog.show', [
        'locale' => 'fr',
        'post' => 'article-63-liva-numerotation-sequentielle-factures-luxembourg-obligatoire',
    ], 301)
);

// Redirects 301 pour les renames de slugs LIVA 21→17 et 61→63
// dans les 4 autres langues (DE, EN, LB, PT)
$articleLivaSlugRedirects = [
    'de' => [
        'artikel-21-liva-reverse-charge-innergemeinschaftlich-b2b-freiberufler-luxemburg'
            => 'artikel-17-liva-reverse-charge-innergemeinschaftlich-b2b-freiberufler-luxemburg',
        'artikel-61-liva-sequenzielle-rechnungsnummerierung-luxemburg-pflicht'
            => 'artikel-63-liva-sequenzielle-rechnungsnummerierung-luxemburg-pflicht',
    ],
    'en' => [
        'article-21-liva-intra-eu-b2b-vat-reverse-charge-luxembourg-freelancers'
            => 'article-17-liva-intra-eu-b2b-vat-reverse-charge-luxembourg-freelancers',
        'article-61-liva-sequential-invoice-numbering-luxembourg-mandatory'
            => 'article-63-liva-sequential-invoice-numbering-luxembourg-mandatory',
    ],
    'lb' => [
        'artikel-21-liva-autoliquidatioun-b2b-intra-eu-freelancer-letzebuerg'
            => 'artikel-17-liva-autoliquidatioun-b2b-intra-eu-freelancer-letzebuerg',
        'artikel-61-liva-sequentiell-rechnungs-nummerung-letzebuerg-obligatoresch'
            => 'artikel-63-liva-sequentiell-rechnungs-nummerung-letzebuerg-obligatoresch',
    ],
    'pt' => [
        'artigo-21-liva-autoliquidacao-iva-b2b-intra-ue-freelancers-luxemburgo'
            => 'artigo-17-liva-autoliquidacao-iva-b2b-intra-ue-freelancers-luxemburgo',
        'artigo-61-liva-numeracao-sequencial-faturas-luxemburgo-obrigatoria'
            => 'artigo-63-liva-numeracao-sequencial-faturas-luxemburgo-obrigatoria',
    ],
];
foreach ($articleLivaSlugRedirects as $loc => $map) {
    foreach ($map as $oldSlug => $newSlug) {
        Route::get("/{$loc}/blog/{$oldSlug}", fn () => redirect()->route('blog.show', [
            'locale' => $loc,
            'post' => $newSlug,
        ], 301));
    }
}

Route::prefix('{locale}')
    ->where(['locale' => 'fr|de|en|lb|pt'])
    ->group(function () {

        // Landing page
        Route::get('/', function (string $locale) {
            // Get posts in current locale, fallback to French if none
            $latestPosts = BlogPost::published()
                ->forLocale($locale)
                ->with('category')
                ->orderByDesc('published_at')
                ->limit(3)
                ->get();

            // Fallback to French if no posts in requested locale
            if ($latestPosts->isEmpty() && $locale !== 'fr') {
                $latestPosts = BlogPost::published()
                    ->forLocale('fr')
                    ->with('category')
                    ->orderByDesc('published_at')
                    ->limit(3)
                    ->get();
            }

            $latestPosts = $latestPosts->map(fn ($post) => [
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'cover_image_url' => $post->cover_image_url,
                'published_at' => $post->published_at->toISOString(),
                'reading_time' => $post->reading_time,
                'category' => $post->category?->name,
            ]);

            return Inertia::render('Welcome', [
                'canLogin' => Route::has('login'),
                'canRegister' => Route::has('register'),
                'laravelVersion' => Application::VERSION,
                'phpVersion' => PHP_VERSION,
                'appUrl' => config('app.url'),
                'latestPosts' => $latestPosts,
                'currentLocale' => $locale,
            ]);
        })->name('home');

        // Pricing page (explicit localized routes)
        Route::get('/tarifs', [PricingController::class, 'index'])->name('pricing.fr');
        Route::get('/preise', [PricingController::class, 'index'])->name('pricing.de');
        Route::get('/pricing', [PricingController::class, 'index'])->name('pricing.en');
        Route::get('/präisser', [PricingController::class, 'index'])->name('pricing.lb');
        Route::get('/precos', [PricingController::class, 'index'])->name('pricing.pt');

        // Feature pages (explicit localized routes)
        Route::get('/fonctionnalites', [FeaturePageController::class, 'index'])->name('features.index.fr');
        Route::get('/funktionen', [FeaturePageController::class, 'index'])->name('features.index.de');
        Route::get('/features', [FeaturePageController::class, 'index'])->name('features.index.en');
        Route::get('/funktiounen', [FeaturePageController::class, 'index'])->name('features.index.lb');
        Route::get('/funcionalidades', [FeaturePageController::class, 'index'])->name('features.index.pt');

        Route::get('/fonctionnalites/{slug}', [FeaturePageController::class, 'show'])->name('features.show.fr');
        Route::get('/funktionen/{slug}', [FeaturePageController::class, 'show'])->name('features.show.de');
        Route::get('/features/{slug}', [FeaturePageController::class, 'show'])->name('features.show.en');
        Route::get('/funktiounen/{slug}', [FeaturePageController::class, 'show'])->name('features.show.lb');
        Route::get('/funcionalidades/{slug}', [FeaturePageController::class, 'show'])->name('features.show.pt');

        // About page (explicit localized routes)
        Route::get('/a-propos', [ContactController::class, 'about'])->name('about.fr');
        Route::get('/ueber-uns', [ContactController::class, 'about'])->name('about.de');
        Route::get('/about', [ContactController::class, 'about'])->name('about.en');
        Route::get('/iwwer-eis', [ContactController::class, 'about'])->name('about.lb');
        Route::get('/sobre', [ContactController::class, 'about'])->name('about.pt');

        // Why faktur.lu page (explicit localized routes)
        Route::get('/pourquoi-faktur-lu', [ContactController::class, 'whyFaktur'])->name('why_faktur.fr');
        Route::get('/warum-faktur-lu', [ContactController::class, 'whyFaktur'])->name('why_faktur.de');
        Route::get('/why-faktur-lu', [ContactController::class, 'whyFaktur'])->name('why_faktur.en');
        Route::get('/firwat-faktur-lu', [ContactController::class, 'whyFaktur'])->name('why_faktur.lb');
        Route::get('/porque-faktur-lu', [ContactController::class, 'whyFaktur'])->name('why_faktur.pt');

        // Partners page (explicit localized routes)
        Route::get('/partenaires', [ContactController::class, 'partners'])->name('partners.fr');
        Route::get('/partner', [ContactController::class, 'partners'])->name('partners.de');
        Route::get('/partners', [ContactController::class, 'partners'])->name('partners.en');
        Route::get('/partneren', [ContactController::class, 'partners'])->name('partners.lb');
        Route::get('/parceiros', [ContactController::class, 'partners'])->name('partners.pt');
        Route::post('/partenaires/contact', [ContactController::class, 'partnerContact'])->middleware(['honeypot', 'throttle:6,1'])->name('partners.contact.fr');
        Route::post('/partners/contact', [ContactController::class, 'partnerContact'])->middleware(['honeypot', 'throttle:6,1'])->name('partners.contact.other');

        // Segmented landing pages (localized)
        Route::get('/pour-freelances', [ContactController::class, 'forFreelances'])->name('for_freelances.fr');
        Route::get('/fuer-freelancer', [ContactController::class, 'forFreelances'])->name('for_freelances.de');
        Route::get('/for-freelancers', [ContactController::class, 'forFreelances'])->name('for_freelances.en');
        Route::get('/fir-freelancer', [ContactController::class, 'forFreelances'])->name('for_freelances.lb');
        Route::get('/para-freelancers', [ContactController::class, 'forFreelances'])->name('for_freelances.pt');

        Route::get('/pour-pme', [ContactController::class, 'forSmes'])->name('for_smes.fr');
        Route::get('/fuer-kmu', [ContactController::class, 'forSmes'])->name('for_smes.de');
        Route::get('/for-smes', [ContactController::class, 'forSmes'])->name('for_smes.en');
        Route::get('/fir-kmu', [ContactController::class, 'forSmes'])->name('for_smes.lb');
        Route::get('/para-pme', [ContactController::class, 'forSmes'])->name('for_smes.pt');

        // Glossary page (DefinedTermSet for LLM/SEO optimization)
        Route::get('/glossaire', [ContactController::class, 'glossary'])->name('glossary.fr');
        Route::get('/glossar', [ContactController::class, 'glossary'])->name('glossary.de');
        Route::get('/glossary', [ContactController::class, 'glossary'])->name('glossary.en');
        Route::get('/glossaire-lu', [ContactController::class, 'glossary'])->name('glossary.lb');
        Route::get('/glossario', [ContactController::class, 'glossary'])->name('glossary.pt');

        // Satisfaction survey (public, tokenized — locale prefix drives page language)
        Route::get('/sondage/{token}', [SurveyController::class, 'show'])->name('survey.show');
        Route::post('/sondage/{token}', [SurveyController::class, 'submit'])
            ->middleware(['honeypot', 'throttle:10,1'])->name('survey.submit');

        // Contact page (explicit localized routes)
        Route::get('/contact', [ContactController::class, 'index'])->name('contact');
        Route::post('/contact', [ContactController::class, 'send'])->middleware(['honeypot', 'throttle:6,1'])->name('contact.send');

        // Tools hub + outils gratuits SEO/lead-gen
        Route::get('/outils', [ToolsController::class, 'index'])->name('tools.fr');
        Route::get('/werkzeuge', [ToolsController::class, 'index'])->name('tools.de');
        Route::get('/tools', [ToolsController::class, 'index'])->name('tools.en');
        Route::get('/handgeschir', [ToolsController::class, 'index'])->name('tools.lb');
        Route::get('/ferramentas', [ToolsController::class, 'index'])->name('tools.pt');

        // Calculateur TVA
        Route::get('/outils/calculateur-tva', [ToolsController::class, 'vatCalculator'])->name('tools.vat_calculator.fr');
        Route::get('/werkzeuge/mwst-rechner', [ToolsController::class, 'vatCalculator'])->name('tools.vat_calculator.de');
        Route::get('/tools/vat-calculator', [ToolsController::class, 'vatCalculator'])->name('tools.vat_calculator.en');
        Route::get('/handgeschir/tva-rechner', [ToolsController::class, 'vatCalculator'])->name('tools.vat_calculator.lb');
        Route::get('/ferramentas/calculadora-iva', [ToolsController::class, 'vatCalculator'])->name('tools.vat_calculator.pt');

        // Simulateur franchise TVA
        Route::get('/outils/franchise-tva', [ToolsController::class, 'vatExemption'])->name('tools.vat_exemption.fr');
        Route::get('/werkzeuge/mwst-befreiung', [ToolsController::class, 'vatExemption'])->name('tools.vat_exemption.de');
        Route::get('/tools/vat-exemption', [ToolsController::class, 'vatExemption'])->name('tools.vat_exemption.en');
        Route::get('/handgeschir/tva-befreiung', [ToolsController::class, 'vatExemption'])->name('tools.vat_exemption.lb');
        Route::get('/ferramentas/isencao-iva', [ToolsController::class, 'vatExemption'])->name('tools.vat_exemption.pt');

        // Validateur IBAN
        Route::get('/outils/validateur-iban', [ToolsController::class, 'ibanValidator'])->name('tools.iban_validator.fr');
        Route::get('/werkzeuge/iban-pruefer', [ToolsController::class, 'ibanValidator'])->name('tools.iban_validator.de');
        Route::get('/tools/iban-validator', [ToolsController::class, 'ibanValidator'])->name('tools.iban_validator.en');
        Route::get('/handgeschir/iban-validateur', [ToolsController::class, 'ibanValidator'])->name('tools.iban_validator.lb');
        Route::get('/ferramentas/validador-iban', [ToolsController::class, 'ibanValidator'])->name('tools.iban_validator.pt');

        // Générateur de facture express (sans compte)
        Route::get('/outils/generateur-facture', [ToolsController::class, 'invoiceGenerator'])->name('tools.invoice_generator.fr');
        Route::get('/werkzeuge/rechnungsgenerator', [ToolsController::class, 'invoiceGenerator'])->name('tools.invoice_generator.de');
        Route::get('/tools/invoice-generator', [ToolsController::class, 'invoiceGenerator'])->name('tools.invoice_generator.en');
        Route::get('/handgeschir/rechnungsgenerator', [ToolsController::class, 'invoiceGenerator'])->name('tools.invoice_generator.lb');
        Route::get('/ferramentas/gerador-fatura', [ToolsController::class, 'invoiceGenerator'])->name('tools.invoice_generator.pt');
        // Endpoint POST partagé (rate limited) pour générer le PDF
        Route::post('/tools/generate-invoice-pdf', [ToolsController::class, 'generateInvoicePdf'])
            ->middleware('throttle:10,1')
            ->name('tools.generate_invoice_pdf');

        // Templates téléchargeables avec capture email
        Route::get('/outils/modeles-facture', [ToolsController::class, 'templates'])->name('tools.templates.fr');
        Route::get('/werkzeuge/vorlagen', [ToolsController::class, 'templates'])->name('tools.templates.de');
        Route::get('/tools/templates', [ToolsController::class, 'templates'])->name('tools.templates.en');
        Route::get('/handgeschir/modellen', [ToolsController::class, 'templates'])->name('tools.templates.lb');
        Route::get('/ferramentas/modelos', [ToolsController::class, 'templates'])->name('tools.templates.pt');
        Route::post('/tools/download-template', [ToolsController::class, 'downloadTemplate'])
            ->middleware('throttle:30,1')->name('tools.download_template');

        // Public FAIA Validator (explicit localized routes)
        Route::get('/validateur-faia', [FaiaValidatorController::class, 'index'])->name('faia-validator.fr');
        Route::get('/faia-validator', [FaiaValidatorController::class, 'index'])->name('faia-validator.other');
        Route::get('/validador-faia', [FaiaValidatorController::class, 'index'])->name('faia-validator.pt');
        Route::post('/validateur-faia/validate', [FaiaValidatorController::class, 'validate'])
            ->middleware('throttle:faia-validator')->name('faia-validator.validate.fr');
        Route::post('/faia-validator/validate', [FaiaValidatorController::class, 'validate'])
            ->middleware('throttle:faia-validator')->name('faia-validator.validate.other');
        Route::post('/validador-faia/validate', [FaiaValidatorController::class, 'validate'])
            ->middleware('throttle:faia-validator')->name('faia-validator.validate.pt');

        // Legal pages (explicit localized routes)
        Route::get('/mentions-legales', [LegalController::class, 'mentions'])->name('legal.mentions.fr');
        Route::get('/impressum', [LegalController::class, 'mentions'])->name('legal.mentions.de');
        Route::get('/legal-notice', [LegalController::class, 'mentions'])->name('legal.mentions.en');
        Route::get('/aviso-legal', [LegalController::class, 'mentions'])->name('legal.mentions.pt');

        Route::get('/confidentialite', [LegalController::class, 'privacy'])->name('legal.privacy.fr');
        Route::get('/datenschutz', [LegalController::class, 'privacy'])->name('legal.privacy.de');
        Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy.en');
        Route::get('/dateschutz', [LegalController::class, 'privacy'])->name('legal.privacy.lb');
        Route::get('/privacidade', [LegalController::class, 'privacy'])->name('legal.privacy.pt');

        Route::get('/cgu', [LegalController::class, 'terms'])->name('legal.terms.fr');
        Route::get('/agb', [LegalController::class, 'terms'])->name('legal.terms.de');
        Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms.en');
        Route::get('/termos', [LegalController::class, 'terms'])->name('legal.terms.pt');

        Route::get('/cookies', [LegalController::class, 'cookies'])->name('legal.cookies');
        Route::get('/dpa', [LegalController::class, 'dpa'])->name('legal.dpa');

        // Blog (localized slugs)
        Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
        Route::get('/{blogSlug}/{catSlug}/{category:slug}', [BlogController::class, 'category'])
            ->where('blogSlug', 'blog')
            ->where('catSlug', 'categorie|kategorie|category|categoria')
            ->name('blog.category');
        Route::get('/blog/tag/{tag:slug}', [BlogController::class, 'tag'])->name('blog.tag');
        Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');
    });

/*
|--------------------------------------------------------------------------
| Legacy redirects (SEO - redirect old URLs to new localized URLs)
|--------------------------------------------------------------------------
*/

Route::get('/blog', fn () => redirect()->route('blog.index', ['locale' => app()->getLocale()]));
Route::get('/blog/{post:slug}', fn (BlogPost $post) => redirect()->route('blog.show', ['locale' => app()->getLocale(), 'post' => $post->slug]));
Route::get('/tarifs', fn () => redirect()->route('pricing', ['locale' => app()->getLocale()]));
Route::get('/mentions-legales', fn () => redirect()->route('legal.mentions', ['locale' => app()->getLocale()]));
Route::get('/confidentialite', fn () => redirect()->route('legal.privacy', ['locale' => app()->getLocale()]));
Route::get('/cgu', fn () => redirect()->route('legal.terms', ['locale' => app()->getLocale()]));
Route::get('/cookies', fn () => redirect()->route('legal.cookies', ['locale' => app()->getLocale()]));
Route::get('/validateur-faia', fn () => redirect()->route('faia-validator', ['locale' => app()->getLocale()]));

/*
|--------------------------------------------------------------------------
| 301 redirects: ancien slugs blog "-2025" → nouveaux slugs "-2026"
| Préserve les backlinks et l'indexation Google des articles renommés.
| Le mapping est centralisé dans UpdateBlog2025To2026SlugsSeeder::SLUG_MAP.
|--------------------------------------------------------------------------
*/
// Note : les redirects 301 ont été déplacés AVANT le groupe localisé (ligne 112)
// pour qu'ils matchent avant la route blog.show (qui capture sinon /{locale}/blog/{post:slug}).

/*
|--------------------------------------------------------------------------
| Authenticated Routes (no locale prefix - uses user preference)
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'redirect.employee'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'check.trial', 'redirect.employee'])->group(function () {
    // Onboarding wizard
    Route::get('/onboarding', [\App\Http\Controllers\OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('/onboarding/company', [\App\Http\Controllers\OnboardingController::class, 'saveCompany'])->name('onboarding.company');
    Route::post('/onboarding/numbering', [\App\Http\Controllers\OnboardingController::class, 'saveNumbering'])->name('onboarding.numbering');
    Route::post('/onboarding/branding', [\App\Http\Controllers\OnboardingController::class, 'saveBranding'])->name('onboarding.branding');
    Route::post('/onboarding/client', [\App\Http\Controllers\OnboardingController::class, 'saveClient'])->name('onboarding.client');
    Route::post('/onboarding/invoice', [\App\Http\Controllers\OnboardingController::class, 'saveInvoice'])->name('onboarding.invoice');
    Route::post('/onboarding/skip', [\App\Http\Controllers\OnboardingController::class, 'skip'])->name('onboarding.skip');
    Route::post('/onboarding/complete', [\App\Http\Controllers\OnboardingController::class, 'complete'])->name('onboarding.complete');
    Route::post('/onboarding/dismiss-checklist', [\App\Http\Controllers\OnboardingController::class, 'dismissChecklist'])->name('onboarding.dismiss-checklist');

    // Profile routes (no special rate limit)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // CRUD operations - 120 requests/minute
    Route::middleware('throttle:crud')->group(function () {
        // Client import wizard (must be before resource routes to avoid conflicts)
        Route::get('/clients/import', [\App\Http\Controllers\Import\ClientImportController::class, 'index'])
            ->name('clients.import.index');
        Route::post('/clients/import/upload', [\App\Http\Controllers\Import\ClientImportController::class, 'upload'])
            ->name('clients.import.upload');
        Route::post('/clients/import/{importSession}/mapping', [\App\Http\Controllers\Import\ClientImportController::class, 'saveMapping'])
            ->name('clients.import.mapping');
        Route::post('/clients/import/{importSession}/process', [\App\Http\Controllers\Import\ClientImportController::class, 'process'])
            ->name('clients.import.process');
        Route::get('/clients/import/{importSession}/status', [\App\Http\Controllers\Import\ClientImportController::class, 'status'])
            ->name('clients.import.status');
        Route::delete('/clients/import/{importSession}', [\App\Http\Controllers\Import\ClientImportController::class, 'destroy'])
            ->name('clients.import.destroy');

        // Clients
        Route::resource('clients', ClientController::class)->except(['store']);
        Route::post('/clients', [ClientController::class, 'store'])
            ->middleware('plan.limit:clients')
            ->name('clients.store');
        Route::get('/clients/{client}/invoices', [ClientController::class, 'invoices'])
            ->name('clients.invoices');
        Route::get('/clients/{client}/interactions', [ClientController::class, 'interactions'])
            ->name('clients.interactions');
        Route::patch('/clients/{client}/convert', [ClientController::class, 'convertProspect'])
            ->name('clients.convert');

        // CRM - Interactions, Reminders, Tags (Pro only)
        Route::middleware('plan.feature:crm')->group(function () {
            Route::post('/clients/{client}/interactions', [InteractionController::class, 'store'])->name('interactions.store');
            Route::put('/interactions/{interaction}', [InteractionController::class, 'update'])->name('interactions.update');
            Route::delete('/interactions/{interaction}', [InteractionController::class, 'destroy'])->name('interactions.destroy');

            Route::get('/reminders', [ReminderController::class, 'index'])->name('reminders.index');
            Route::post('/clients/{client}/reminders', [ReminderController::class, 'store'])->name('reminders.store');
            Route::put('/reminders/{reminder}', [ReminderController::class, 'update'])->name('reminders.update');
            Route::patch('/reminders/{reminder}/complete', [ReminderController::class, 'complete'])->name('reminders.complete');
            Route::delete('/reminders/{reminder}', [ReminderController::class, 'destroy'])->name('reminders.destroy');

            Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
            Route::post('/tags', [TagController::class, 'store'])->name('tags.store');
            Route::put('/tags/{tag}', [TagController::class, 'update'])->name('tags.update');
            Route::delete('/tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');
            Route::post('/clients/{client}/tags/{tag}', [TagController::class, 'attach'])->name('tags.attach');
            Route::delete('/clients/{client}/tags/{tag}', [TagController::class, 'detach'])->name('tags.detach');
        });

        // HR Module (Pro only)
        Route::prefix('hr')->name('hr.')->middleware('plan.feature:hr_module')->group(function () {
            Route::get('/', [HR\HRDashboardController::class, 'index'])->name('dashboard');

            Route::resource('employees', HR\EmployeeController::class);
            Route::post('/employees/{employee}/activate-portal', [HR\EmployeeController::class, 'activatePortal'])->name('employees.activate-portal');
            Route::post('/employees/{employee}/deactivate-portal', [HR\EmployeeController::class, 'deactivatePortal'])->name('employees.deactivate-portal');
            Route::get('/employees/{employee}/leaves', [HR\EmployeeController::class, 'leaves'])->name('employees.leaves');
            Route::get('/employees/{employee}/expenses', [HR\EmployeeController::class, 'expenses'])->name('employees.expenses');
            Route::get('/employees/{employee}/documents', [HR\EmployeeController::class, 'documents'])->name('employees.documents');
            Route::post('/employees/{employee}/documents', [HR\EmployeeDocumentController::class, 'store'])->name('employees.documents.store');
            Route::delete('/employees/{employee}/documents/{employeeDocument}', [HR\EmployeeDocumentController::class, 'destroy'])->name('employees.documents.destroy');

            Route::get('/employees/{employee}/evaluations', [HR\EmployeeController::class, 'evaluations'])->name('employees.evaluations');
            Route::post('/employees/{employee}/evaluations', [HR\EvaluationController::class, 'store'])->name('employees.evaluations.store');
            Route::get('/employees/{employee}/evaluations/{evaluation}', [HR\EvaluationController::class, 'show'])->name('employees.evaluations.show');
            Route::get('/employees/{employee}/evaluations/{evaluation}/pdf', [HR\EvaluationController::class, 'pdf'])->name('employees.evaluations.pdf');
            Route::delete('/employees/{employee}/evaluations/{evaluation}', [HR\EvaluationController::class, 'destroy'])->name('employees.evaluations.destroy');
            Route::post('/employees/{employee}/evaluations/{evaluation}/documents', [HR\EvaluationController::class, 'uploadDocument'])->name('employees.evaluations.upload-document');
            Route::delete('/employees/{employee}/evaluations/{evaluation}/documents/{document}', [HR\EvaluationController::class, 'deleteDocument'])->name('employees.evaluations.delete-document');

            Route::get('/employees/{employee}/onboarding', [HR\EmployeeController::class, 'onboarding'])->name('employees.onboarding');
            Route::post('/employees/{employee}/onboarding', [HR\OnboardingTaskController::class, 'store'])->name('employees.onboarding.store');
            Route::post('/employees/{employee}/onboarding/apply-template', [HR\OnboardingTaskController::class, 'applyTemplate'])->name('employees.onboarding.apply-template');
            Route::patch('/employees/{employee}/onboarding/{onboardingTask}/toggle', [HR\OnboardingTaskController::class, 'toggle'])->name('employees.onboarding.toggle');
            Route::delete('/employees/{employee}/onboarding/{onboardingTask}', [HR\OnboardingTaskController::class, 'destroy'])->name('employees.onboarding.destroy');

            Route::resource('onboarding-templates', HR\OnboardingTemplateController::class)->except(['create', 'show', 'edit']);

            Route::get('/trombinoscope', [HR\TrombinoscopeController::class, 'index'])->name('trombinoscope');
            Route::get('/trombinoscope/pdf', [HR\TrombinoscopeController::class, 'pdf'])->name('trombinoscope.pdf');

            Route::resource('departments', HR\DepartmentController::class)->except(['create', 'show', 'edit']);
            Route::resource('leave-types', HR\LeaveTypeController::class)->except(['create', 'show', 'edit']);

            // Shared calendar (FEAT-079)
            Route::get('/shared-calendar', [HR\SharedCalendarController::class, 'index'])->name('shared-calendar.index');
            Route::get('/shared-calendar/events', [HR\SharedCalendarController::class, 'events'])->name('shared-calendar.events');

            // HR events
            Route::get('/events/create', [HR\HrEventController::class, 'create'])->name('events.create');
            Route::post('/events', [HR\HrEventController::class, 'store'])->name('events.store');
            Route::get('/events/{event}', [HR\HrEventController::class, 'show'])->name('events.show');
            Route::get('/events/{event}/edit', [HR\HrEventController::class, 'edit'])->name('events.edit');
            Route::put('/events/{event}', [HR\HrEventController::class, 'update'])->name('events.update');
            Route::delete('/events/{event}', [HR\HrEventController::class, 'destroy'])->name('events.destroy');

            // Rooms
            Route::get('/rooms', [HR\RoomController::class, 'index'])->name('rooms.index');
            Route::post('/rooms', [HR\RoomController::class, 'store'])->name('rooms.store');
            Route::put('/rooms/{room}', [HR\RoomController::class, 'update'])->name('rooms.update');
            Route::delete('/rooms/{room}', [HR\RoomController::class, 'destroy'])->name('rooms.destroy');

            Route::get('/leaves', [HR\LeaveRequestController::class, 'index'])->name('leaves.index');
            Route::get('/leaves/calendar', [HR\LeaveRequestController::class, 'calendar'])->name('leaves.calendar');
            Route::post('/leaves', [HR\LeaveRequestController::class, 'store'])->name('leaves.store');
            Route::put('/leaves/{leaveRequest}', [HR\LeaveRequestController::class, 'update'])->name('leaves.update');
            Route::patch('/leaves/{leaveRequest}/approve', [HR\LeaveRequestController::class, 'approve'])->name('leaves.approve');
            Route::patch('/leaves/{leaveRequest}/reject', [HR\LeaveRequestController::class, 'reject'])->name('leaves.reject');
            Route::patch('/leaves/{leaveRequest}/cancel', [HR\LeaveRequestController::class, 'cancel'])->name('leaves.cancel');
            Route::patch('/leaves/{leaveRequest}/reactivate', [HR\LeaveRequestController::class, 'reactivate'])->name('leaves.reactivate');
            Route::delete('/leaves/{leaveRequest}', [HR\LeaveRequestController::class, 'destroy'])->name('leaves.destroy');

            Route::resource('expense-categories', HR\ExpenseCategoryController::class)->except(['create', 'show', 'edit']);

            Route::get('/expenses', [HR\ExpenseReportController::class, 'index'])->name('expenses.index');
            Route::post('/expenses', [HR\ExpenseReportController::class, 'store'])->name('expenses.store');
            Route::put('/expenses/{expenseReport}', [HR\ExpenseReportController::class, 'update'])->name('expenses.update');
            Route::patch('/expenses/{expenseReport}/approve', [HR\ExpenseReportController::class, 'approve'])->name('expenses.approve');
            Route::patch('/expenses/{expenseReport}/reject', [HR\ExpenseReportController::class, 'reject'])->name('expenses.reject');
            Route::delete('/expenses/{expenseReport}/receipts/{expenseReceipt}', [HR\ExpenseReportController::class, 'deleteReceipt'])->name('expenses.receipts.destroy');
            Route::delete('/expenses/{expenseReport}', [HR\ExpenseReportController::class, 'destroy'])->name('expenses.destroy');
        });

        // Invoices
        Route::resource('invoices', InvoiceController::class)->except(['store']);
        Route::post('/invoices', [InvoiceController::class, 'store'])
            ->middleware('plan.limit:invoices')
            ->name('invoices.store');
        Route::post('/invoices/{invoice}/finalize', [InvoiceController::class, 'finalize'])->name('invoices.finalize');
        Route::post('/invoices/{invoice}/mark-sent', [InvoiceController::class, 'markAsSent'])->name('invoices.mark-sent');
        Route::post('/invoices/{invoice}/mark-paid', [InvoiceController::class, 'markAsPaid'])->name('invoices.mark-paid');
        Route::post('/invoices/{invoice}/paid-at', [InvoiceController::class, 'updatePaidAt'])->name('invoices.update-paid-at');
        Route::post('/invoices/{invoice}/credit-note', [InvoiceController::class, 'createCreditNote'])->name('invoices.credit-note');
        Route::post('/invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])
            ->middleware('plan.limit:invoices')
            ->name('invoices.duplicate');

        // Recurring Invoices
        Route::resource('recurring-invoices', \App\Http\Controllers\RecurringInvoiceController::class)->except(['show']);
        Route::post('/recurring-invoices/{recurring_invoice}/toggle', [\App\Http\Controllers\RecurringInvoiceController::class, 'toggleActive'])->name('recurring-invoices.toggle');
        Route::post('/recurring-invoices/{recurring_invoice}/duplicate', [\App\Http\Controllers\RecurringInvoiceController::class, 'duplicate'])->name('recurring-invoices.duplicate');

        // Invoice Items
        Route::post('/invoices/{invoice}/items', [InvoiceItemController::class, 'store'])->name('invoices.items.store');
        Route::put('/invoices/{invoice}/items/{item}', [InvoiceItemController::class, 'update'])->name('invoices.items.update');
        Route::patch('/invoices/{invoice}/items/{item}/move', [InvoiceItemController::class, 'move'])->name('invoices.items.move');
        Route::delete('/invoices/{invoice}/items/{item}', [InvoiceItemController::class, 'destroy'])->name('invoices.items.destroy');

        // Quotes
        Route::resource('quotes', QuoteController::class)->except(['store']);
        Route::post('/quotes', [QuoteController::class, 'store'])
            ->middleware('plan.limit:quotes')
            ->name('quotes.store');
        Route::post('/quotes/{quote}/mark-sent', [QuoteController::class, 'markAsSent'])->name('quotes.mark-sent');
        Route::post('/quotes/{quote}/mark-accepted', [QuoteController::class, 'markAsAccepted'])->name('quotes.mark-accepted');
        Route::post('/quotes/{quote}/mark-declined', [QuoteController::class, 'markAsDeclined'])->name('quotes.mark-declined');
        Route::post('/quotes/{quote}/convert', [QuoteController::class, 'convertToInvoice'])->name('quotes.convert');
        Route::post('/quotes/{quote}/duplicate', [QuoteController::class, 'duplicate'])
            ->middleware('plan.limit:quotes')
            ->name('quotes.duplicate');

        // Quote Items
        Route::post('/quotes/{quote}/items', [QuoteItemController::class, 'store'])->name('quotes.items.store');
        Route::put('/quotes/{quote}/items/{item}', [QuoteItemController::class, 'update'])->name('quotes.items.update');
        Route::patch('/quotes/{quote}/items/{item}/move', [QuoteItemController::class, 'move'])->name('quotes.items.move');
        Route::delete('/quotes/{quote}/items/{item}', [QuoteItemController::class, 'destroy'])->name('quotes.items.destroy');

        // Expenses
        Route::resource('expenses', ExpenseController::class)->except(['store']);
        Route::post('/expenses', [ExpenseController::class, 'store'])
            ->middleware('plan.limit:expenses')
            ->name('expenses.store');
        Route::get('/expenses-summary', [ExpenseController::class, 'summary'])->name('expenses.summary');

        // Time Tracking
        // Time entries — Essentiel ou Pro
        Route::middleware('plan.feature:time_tracking')->group(function () {
            Route::resource('time-entries', TimeEntryController::class)->except(['show', 'create', 'edit']);
            Route::post('/time-entries/start', [TimeEntryController::class, 'start'])->name('time-entries.start');
            Route::post('/time-entries/{timeEntry}/stop', [TimeEntryController::class, 'stop'])->name('time-entries.stop');
            Route::get('/time-entries/running', [TimeEntryController::class, 'running'])->name('time-entries.running');
            Route::get('/time-entries/summary', [TimeEntryController::class, 'summary'])->name('time-entries.summary');
            Route::post('/time-entries/to-invoice', [TimeEntryController::class, 'toInvoice'])->name('time-entries.to-invoice');
            Route::post('/time-entries/{timeEntry}/add-to-invoice', [TimeEntryController::class, 'addToInvoice'])->name('time-entries.add-to-invoice');
        });

        // Projects + Tasks — Essentiel ou Pro
        Route::middleware('plan.feature:projects')->group(function () {
            Route::resource('projects', ProjectController::class)->except(['store']);
            Route::post('/projects', [ProjectController::class, 'store'])
                ->middleware('plan.limit:projects')
                ->name('projects.store');
            Route::post('/projects/{project}/status', [ProjectController::class, 'updateStatus'])->name('projects.status');
            Route::post('/projects/reorder', [ProjectController::class, 'reorder'])->name('projects.reorder');
            Route::post('/projects/{project}/archive', [ProjectController::class, 'archive'])->name('projects.archive');

            // Project members (FEAT-081)
            Route::get('/projects/{project}/members', [\App\Http\Controllers\ProjectMemberController::class, 'index'])->name('projects.members.index');
            Route::patch('/projects/{project}/members/employees/{employee}/toggle', [\App\Http\Controllers\ProjectMemberController::class, 'toggleEmployee'])->name('projects.members.employees.toggle');
            Route::post('/projects/{project}/members/collaborators', [\App\Http\Controllers\ProjectMemberController::class, 'inviteCollaborator'])->name('projects.members.collaborators.invite');
            Route::delete('/projects/{project}/members/collaborators/{memberId}', [\App\Http\Controllers\ProjectMemberController::class, 'removeCollaborator'])->name('projects.members.collaborators.remove');
            Route::get('/projects/{project}/members/quota', [\App\Http\Controllers\ProjectMemberController::class, 'quota'])->name('projects.members.quota');

            // Tasks
            Route::post('/projects/{project}/tasks', [TaskController::class, 'store'])->name('tasks.store');
            Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
            Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
            Route::post('/tasks/{task}/toggle', [TaskController::class, 'toggle'])->name('tasks.toggle');
            Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
            Route::post('/tasks/{task}/subtasks', [TaskController::class, 'storeSubtask'])->name('tasks.subtasks.store');
            Route::post('/projects/{project}/tasks/reorder', [TaskController::class, 'reorder'])->name('tasks.reorder');
            Route::post('/projects/{project}/tasks/reorder-list', [TaskController::class, 'reorderList'])->name('tasks.reorder-list');
        });

        // Business Settings
        Route::get('/settings/business', [BusinessSettingsController::class, 'edit'])->name('settings.business.edit');
        Route::put('/settings/business', [BusinessSettingsController::class, 'update'])->name('settings.business.update');
        Route::post('/settings/business/logo', [BusinessSettingsController::class, 'uploadLogo'])->name('settings.business.logo.upload');
        Route::delete('/settings/business/logo', [BusinessSettingsController::class, 'deleteLogo'])->name('settings.business.logo.delete');
        Route::post('/settings/business/payment-qrcode', [BusinessSettingsController::class, 'uploadPaymentQrcode'])->name('settings.business.payment-qrcode.upload');
        Route::delete('/settings/business/payment-qrcode', [BusinessSettingsController::class, 'deletePaymentQrcode'])->name('settings.business.payment-qrcode.delete');

        // Audit Logs (view)
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        // Support tickets
        Route::get('/support', [SupportController::class, 'index'])->name('support.index');
        Route::get('/support/create', [SupportController::class, 'create'])->name('support.create');
        Route::post('/support', [SupportController::class, 'store'])->name('support.store');
        Route::get('/support/{ticket}', [SupportController::class, 'show'])->name('support.show');
        Route::post('/support/{ticket}/reply', [SupportController::class, 'reply'])->name('support.reply');
    });

    // PDF generation - 10 requests/minute (expensive operations)
    Route::middleware('throttle:pdf')->group(function () {
        Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
        Route::get('/invoices/{invoice}/pdf/stream', [InvoiceController::class, 'streamPdf'])->name('invoices.pdf.stream');
        Route::get('/invoices/{invoice}/pdf/preview', [InvoiceController::class, 'previewPdf'])->name('invoices.pdf.preview');
        Route::get('/invoices/{invoice}/draft-pdf', [InvoiceController::class, 'streamDraftPdf'])->name('invoices.draft-pdf');
        Route::get('/quotes/{quote}/pdf', [QuoteController::class, 'downloadPdf'])->name('quotes.pdf');
        Route::get('/quotes/{quote}/pdf/stream', [QuoteController::class, 'streamPdf'])->name('quotes.pdf.stream');
        Route::get('/quotes/{quote}/pdf/preview', [QuoteController::class, 'previewPdf'])->name('quotes.pdf.preview');
        Route::get('/invoices/{invoice}/archive/download', [ArchiveController::class, 'download'])->name('invoices.archive.download');

        // Reports comptables PDF — Essentiel ou Pro
        Route::middleware('plan.feature:accounting_exports')->group(function () {
            Route::get('/reports/revenue-book/pdf', [RevenueBookController::class, 'exportPdf'])->name('reports.revenue-book.pdf');
            Route::get('/reports/fiscal-summary/pdf', [FiscalSummaryController::class, 'exportPdf'])->name('reports.fiscal-summary.pdf');
        });
    });

    // HTML Preview - 60 requests/minute (less expensive than PDF)
    Route::middleware('throttle:preview')->group(function () {
        Route::get('/invoices/{invoice}/preview-html', [InvoiceController::class, 'previewHtml'])->name('invoices.preview-html');
        Route::get('/invoices/{invoice}/preview-draft', [InvoiceController::class, 'previewDraft'])->name('invoices.preview-draft');
        Route::get('/quotes/{quote}/preview-html', [QuoteController::class, 'previewHtml'])->name('quotes.preview-html');
    });

    // Email sending - 20 requests/hour
    Route::middleware(['throttle:email', 'plan.limit:emails'])->group(function () {
        Route::post('/invoices/{invoice}/send-email', [InvoiceEmailController::class, 'send'])->name('invoices.send-email');
        Route::post('/invoices/{invoice}/send-reminder', [InvoiceEmailController::class, 'sendReminder'])
            ->middleware('plan.feature:email_reminders')
            ->name('invoices.send-reminder');
    });

    // Email settings (no special rate limit)
    Route::get('/settings/email', [InvoiceEmailController::class, 'settings'])->name('settings.email');
    Route::put('/settings/email', [InvoiceEmailController::class, 'updateSettings'])->name('settings.email.update');

    // Email provider settings
    Route::get('/settings/email/provider', [EmailProviderController::class, 'index'])->name('settings.email.provider');
    Route::put('/settings/email/provider', [EmailProviderController::class, 'update'])->name('settings.email.provider.update');
    Route::post('/settings/email/provider/test', [EmailProviderController::class, 'test'])->name('settings.email.provider.test');
    Route::post('/settings/email/provider/validate-smtp', [EmailProviderController::class, 'validateSmtp'])->name('settings.email.provider.validate-smtp');

    // Accountant settings (invite/manage accountants) — Essentiel ou Pro
    Route::middleware('plan.feature:accounting_portal')->group(function () {
        Route::get('/settings/accountant', [AccountantSettingsController::class, 'index'])->name('settings.accountant');
        Route::post('/settings/accountant/invite', [AccountantSettingsController::class, 'invite'])->name('settings.accountant.invite');
        Route::post('/settings/accountant/invitations/{invitation}/resend', [AccountantSettingsController::class, 'resendInvitation'])->name('settings.accountant.resend');
        Route::delete('/settings/accountant/invitations/{invitation}', [AccountantSettingsController::class, 'cancelInvitation'])->name('settings.accountant.cancel');
        Route::delete('/settings/accountant/{accountant}', [AccountantSettingsController::class, 'revokeAccess'])->name('settings.accountant.revoke');
    });

    // Organization management (Pro only)
    Route::prefix('settings/organisation')->name('settings.organization.')->middleware('plan.feature:organizations')->group(function () {
        Route::get('/', [OrganizationController::class, 'index'])->name('index');
        Route::post('/', [OrganizationController::class, 'store'])->name('store');
        Route::put('/', [OrganizationController::class, 'update'])->middleware('org.admin')->name('update');
        Route::post('/invite', [OrganizationController::class, 'invite'])->middleware('org.admin')->name('invite');
        Route::post('/invitations/{invitation}/resend', [OrganizationController::class, 'resendInvitation'])->middleware('org.admin')->name('invitations.resend');
        Route::delete('/invitations/{invitation}', [OrganizationController::class, 'cancelInvitation'])->middleware('org.admin')->name('invitations.cancel');
        Route::delete('/members/{member}', [OrganizationController::class, 'removeMember'])->middleware('org.admin')->name('members.remove');
        Route::put('/projects/{project}/visibility', [OrganizationController::class, 'toggleProjectVisibility'])->middleware('org.admin')->name('projects.visibility');
    });

    // Subscription management
    Route::get('/settings/subscription', [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::get('/subscription/success', [SubscriptionController::class, 'success'])->name('subscription.success');
    Route::get('/subscription/portal', [SubscriptionController::class, 'portal'])->name('subscription.portal');
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
    Route::post('/subscription/resume', [SubscriptionController::class, 'resume'])->name('subscription.resume');
    Route::post('/subscription/swap', [SubscriptionController::class, 'swap'])->name('subscription.swap');
    Route::get('/subscription/invoice/{invoiceId}', [SubscriptionController::class, 'downloadInvoice'])->name('subscription.invoice');

    // Invoice email history
    Route::get('/invoices/{invoice}/emails', [InvoiceEmailController::class, 'history'])->name('invoices.emails');
    Route::post('/invoices/{invoice}/toggle-reminders', [InvoiceEmailController::class, 'toggleExcludeFromReminders'])->name('invoices.toggle-reminders');

    // Export operations - 5 requests/hour (very expensive)
    Route::middleware('throttle:export')->group(function () {
        // FAIA export - all plans
        Route::post('/exports/audit', [AuditExportController::class, 'store'])
            ->name('exports.audit.store');

        // PDF Archive - Pro only
        Route::post('/invoices/{invoice}/archive', [ArchiveController::class, 'archive'])
            ->middleware('plan.feature:pdf_archive')
            ->name('invoices.archive');
        Route::post('/archive/batch', [ArchiveController::class, 'archiveBatch'])
            ->middleware('plan.feature:pdf_archive')
            ->name('archive.batch');

        // Peppol export - available for all
        Route::get('/invoices/{invoice}/peppol', [PeppolExportController::class, 'export'])->name('invoices.peppol');

        // Peppol transmission - send invoice via Peppol network (Pro only)
        Route::post('/invoices/{invoice}/send-peppol', [InvoiceController::class, 'sendViaPeppol'])
            ->middleware(['plan.feature:peppol_transmission', 'plan.limit:peppol'])
            ->name('invoices.send-peppol');
        Route::get('/invoices/{invoice}/peppol-status', [InvoiceController::class, 'peppolStatus'])->name('invoices.peppol-status');

        // Factur-X / ZUGFeRD export (Pro only)
        Route::get('/invoices/{invoice}/facturx', [InvoiceController::class, 'facturx'])
            ->middleware('plan.feature:facturx')
            ->name('invoices.facturx');
        Route::get('/invoices/{invoice}/facturx-xml', [InvoiceController::class, 'facturxXml'])
            ->middleware('plan.feature:facturx')
            ->name('invoices.facturx-xml');
    });

    // Audit log export - 10 requests/hour
    Route::middleware('throttle:audit-export')->group(function () {
        Route::get('/audit-logs/export', [AuditLogController::class, 'export'])->name('audit-logs.export');

        // Reports comptables CSV — Essentiel ou Pro
        Route::middleware('plan.feature:accounting_exports')->group(function () {
            Route::get('/reports/revenue-book/csv', [RevenueBookController::class, 'exportCsv'])->name('reports.revenue-book.csv');
            Route::get('/reports/fiscal-summary/csv', [FiscalSummaryController::class, 'exportCsv'])->name('reports.fiscal-summary.csv');
        });
    });

    // Audit log detail (must be after /audit-logs/export to avoid route conflict)
    Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');

    // Reports views (HTML pages) — Essentiel ou Pro
    Route::middleware('plan.feature:accounting_exports')->group(function () {
        Route::get('/reports/revenue-book', [RevenueBookController::class, 'index'])->name('reports.revenue-book');
        Route::get('/reports/fiscal-summary', [FiscalSummaryController::class, 'index'])->name('reports.fiscal-summary');
    });
    Route::get('/exports/audit', [AuditExportController::class, 'index'])->name('exports.audit.index');
    Route::get('/exports/audit/preview', [AuditExportController::class, 'preview'])->name('exports.audit.preview');
    Route::get('/exports/audit/{export}/download', [AuditExportController::class, 'download'])->name('exports.audit.download');
    Route::delete('/exports/audit/{export}', [AuditExportController::class, 'destroy'])->name('exports.audit.destroy');

    // Accounting exports (Sage BOB, FID-Manager, CSV) — Essentiel ou Pro
    Route::middleware('plan.feature:accounting_exports')->group(function () {
        Route::get('/exports/accounting', [AccountingExportController::class, 'index'])->name('exports.accounting.index');
        Route::get('/exports/accounting/preview', [AccountingExportController::class, 'preview'])->name('exports.accounting.preview');
        Route::get('/exports/accounting/pdf-archive', [AccountingExportController::class, 'pdfArchive'])->name('exports.accounting.pdf-archive');
        Route::post('/exports/accounting', [AccountingExportController::class, 'store'])->name('exports.accounting.store');
        Route::get('/exports/accounting/{export}/download', [AccountingExportController::class, 'download'])->name('exports.accounting.download');
        Route::delete('/exports/accounting/{export}', [AccountingExportController::class, 'destroy'])->name('exports.accounting.destroy');
    });

    // Accounting settings
    Route::get('/settings/accounting', [AccountingSettingsController::class, 'edit'])->name('settings.accounting.edit');
    Route::put('/settings/accounting', [AccountingSettingsController::class, 'update'])->name('settings.accounting.update');

    // Archive (PDF/A long term archiving) — Pro only
    Route::middleware('plan.feature:pdf_archive')->group(function () {
        Route::get('/archive', [ArchiveController::class, 'index'])->name('archive.index');
        Route::get('/invoices/{invoice}/archive/verify', [ArchiveController::class, 'verify'])->name('invoices.archive.verify');
        Route::get('/invoices/{invoice}/archive/info', [ArchiveController::class, 'info'])->name('invoices.archive.info');
    });

    // Company lookup API - 30 requests/minute
    Route::middleware('throttle:company-lookup')->group(function () {
        Route::get('/api/company-lookup/search', [CompanyLookupController::class, 'search'])->name('company-lookup.search');
        Route::post('/api/company-lookup/validate-vat', [CompanyLookupController::class, 'validateVat'])->name('company-lookup.validate-vat');
    });

    // Global search
    Route::get('/search', [SearchController::class, 'search'])->name('search.api');
    Route::get('/search/results', [SearchController::class, 'results'])->name('search.results');

    // Dashboard API endpoints - 60 requests/minute
    Route::middleware('throttle:dashboard')->group(function () {
        Route::get('/api/dashboard/kpis', [DashboardController::class, 'kpis'])->name('dashboard.kpis');
        Route::get('/api/dashboard/revenue-chart', [DashboardController::class, 'revenueChart'])->name('dashboard.revenue-chart');
        Route::get('/api/dashboard/unpaid-invoices', [DashboardController::class, 'unpaidInvoices'])->name('dashboard.unpaid-invoices');
        Route::get('/api/dashboard/unbilled-time', [DashboardController::class, 'unbilledTime'])->name('dashboard.unbilled-time');
        Route::get('/api/dashboard/vat-summary', [DashboardController::class, 'vatSummary'])->name('dashboard.vat-summary');
        Route::get('/api/dashboard/cashflow-forecast', [DashboardController::class, 'cashflowForecast'])->name('dashboard.cashflow-forecast');
    });
});

require __DIR__.'/auth.php';

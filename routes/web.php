<?php

use App\Http\Controllers\AccountantSettingsController;
use App\Http\Controllers\BlogController;
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
use App\Http\Controllers\RevenueBookController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TimeEntryController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\SupportController;
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
    ->where('locale', 'fr|de|en|lb')
    ->name('locale.switch');

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

Route::prefix('{locale}')
    ->where(['locale' => 'fr|de|en|lb'])
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

        // Public FAIA Validator (explicit localized routes)
        Route::get('/validateur-faia', [FaiaValidatorController::class, 'index'])->name('faia-validator.fr');
        Route::get('/faia-validator', [FaiaValidatorController::class, 'index'])->name('faia-validator.other');
        Route::post('/validateur-faia/validate', [FaiaValidatorController::class, 'validate'])
            ->middleware('throttle:faia-validator')->name('faia-validator.validate.fr');
        Route::post('/faia-validator/validate', [FaiaValidatorController::class, 'validate'])
            ->middleware('throttle:faia-validator')->name('faia-validator.validate.other');

        // Legal pages (explicit localized routes)
        Route::get('/mentions-legales', [LegalController::class, 'mentions'])->name('legal.mentions.fr');
        Route::get('/impressum', [LegalController::class, 'mentions'])->name('legal.mentions.de');
        Route::get('/legal-notice', [LegalController::class, 'mentions'])->name('legal.mentions.en');

        Route::get('/confidentialite', [LegalController::class, 'privacy'])->name('legal.privacy.fr');
        Route::get('/datenschutz', [LegalController::class, 'privacy'])->name('legal.privacy.de');
        Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy.en');
        Route::get('/dateschutz', [LegalController::class, 'privacy'])->name('legal.privacy.lb');

        Route::get('/cgu', [LegalController::class, 'terms'])->name('legal.terms.fr');
        Route::get('/agb', [LegalController::class, 'terms'])->name('legal.terms.de');
        Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms.en');

        Route::get('/cookies', [LegalController::class, 'cookies'])->name('legal.cookies');

        // Blog (localized slugs)
        Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
        Route::get('/{blogSlug}/{catSlug}/{category:slug}', [BlogController::class, 'category'])
            ->where('blogSlug', 'blog')
            ->where('catSlug', 'categorie|kategorie|category')
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
| Authenticated Routes (no locale prefix - uses user preference)
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'check.trial'])->group(function () {
    // Profile routes (no special rate limit)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // CRUD operations - 120 requests/minute
    Route::middleware('throttle:crud')->group(function () {
        // Clients
        Route::resource('clients', ClientController::class)->except(['store']);
        Route::post('/clients', [ClientController::class, 'store'])
            ->middleware('plan.limit:clients')
            ->name('clients.store');
        Route::get('/clients/{client}/invoices', [ClientController::class, 'invoices'])
            ->name('clients.invoices');

        // Invoices
        Route::resource('invoices', InvoiceController::class)->except(['store']);
        Route::post('/invoices', [InvoiceController::class, 'store'])
            ->middleware('plan.limit:invoices')
            ->name('invoices.store');
        Route::post('/invoices/{invoice}/finalize', [InvoiceController::class, 'finalize'])->name('invoices.finalize');
        Route::post('/invoices/{invoice}/mark-sent', [InvoiceController::class, 'markAsSent'])->name('invoices.mark-sent');
        Route::post('/invoices/{invoice}/mark-paid', [InvoiceController::class, 'markAsPaid'])->name('invoices.mark-paid');
        Route::post('/invoices/{invoice}/credit-note', [InvoiceController::class, 'createCreditNote'])->name('invoices.credit-note');

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

        // Quote Items
        Route::post('/quotes/{quote}/items', [QuoteItemController::class, 'store'])->name('quotes.items.store');
        Route::put('/quotes/{quote}/items/{item}', [QuoteItemController::class, 'update'])->name('quotes.items.update');
        Route::patch('/quotes/{quote}/items/{item}/move', [QuoteItemController::class, 'move'])->name('quotes.items.move');
        Route::delete('/quotes/{quote}/items/{item}', [QuoteItemController::class, 'destroy'])->name('quotes.items.destroy');

        // Expenses
        Route::resource('expenses', ExpenseController::class);
        Route::get('/expenses-summary', [ExpenseController::class, 'summary'])->name('expenses.summary');

        // Time Tracking
        Route::resource('time-entries', TimeEntryController::class)->except(['show', 'create', 'edit']);
        Route::post('/time-entries/start', [TimeEntryController::class, 'start'])->name('time-entries.start');
        Route::post('/time-entries/{timeEntry}/stop', [TimeEntryController::class, 'stop'])->name('time-entries.stop');
        Route::get('/time-entries/running', [TimeEntryController::class, 'running'])->name('time-entries.running');
        Route::get('/time-entries/summary', [TimeEntryController::class, 'summary'])->name('time-entries.summary');
        Route::post('/time-entries/to-invoice', [TimeEntryController::class, 'toInvoice'])->name('time-entries.to-invoice');
        Route::post('/time-entries/{timeEntry}/add-to-invoice', [TimeEntryController::class, 'addToInvoice'])->name('time-entries.add-to-invoice');

        // Projects
        Route::resource('projects', ProjectController::class);
        Route::post('/projects/{project}/status', [ProjectController::class, 'updateStatus'])->name('projects.status');
        Route::post('/projects/reorder', [ProjectController::class, 'reorder'])->name('projects.reorder');
        Route::post('/projects/{project}/archive', [ProjectController::class, 'archive'])->name('projects.archive');

        // Tasks
        Route::post('/projects/{project}/tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
        Route::post('/tasks/{task}/toggle', [TaskController::class, 'toggle'])->name('tasks.toggle');
        Route::post('/tasks/{task}/subtasks', [TaskController::class, 'storeSubtask'])->name('tasks.subtasks.store');
        Route::post('/projects/{project}/tasks/reorder', [TaskController::class, 'reorder'])->name('tasks.reorder');
        Route::post('/projects/{project}/tasks/reorder-list', [TaskController::class, 'reorderList'])->name('tasks.reorder-list');

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
        Route::get('/reports/revenue-book/pdf', [RevenueBookController::class, 'exportPdf'])->name('reports.revenue-book.pdf');
        Route::get('/invoices/{invoice}/archive/download', [ArchiveController::class, 'download'])->name('invoices.archive.download');
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

    // Accountant settings (invite/manage accountants)
    Route::get('/settings/accountant', [AccountantSettingsController::class, 'index'])->name('settings.accountant');
    Route::post('/settings/accountant/invite', [AccountantSettingsController::class, 'invite'])->name('settings.accountant.invite');
    Route::post('/settings/accountant/invitations/{invitation}/resend', [AccountantSettingsController::class, 'resendInvitation'])->name('settings.accountant.resend');
    Route::delete('/settings/accountant/invitations/{invitation}', [AccountantSettingsController::class, 'cancelInvitation'])->name('settings.accountant.cancel');
    Route::delete('/settings/accountant/{accountant}', [AccountantSettingsController::class, 'revokeAccess'])->name('settings.accountant.revoke');

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
        // FAIA export - Pro only
        Route::post('/exports/audit', [AuditExportController::class, 'store'])
            ->middleware('plan.feature:faia_export')
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

        // Peppol transmission - send invoice via Peppol network
        Route::post('/invoices/{invoice}/send-peppol', [InvoiceController::class, 'sendViaPeppol'])->name('invoices.send-peppol');
        Route::get('/invoices/{invoice}/peppol-status', [InvoiceController::class, 'peppolStatus'])->name('invoices.peppol-status');

        // Factur-X / ZUGFeRD export
        Route::get('/invoices/{invoice}/facturx', [InvoiceController::class, 'facturx'])->name('invoices.facturx');
        Route::get('/invoices/{invoice}/facturx-xml', [InvoiceController::class, 'facturxXml'])->name('invoices.facturx-xml');
    });

    // Audit log export - 10 requests/hour
    Route::middleware('throttle:audit-export')->group(function () {
        Route::get('/audit-logs/export', [AuditLogController::class, 'export'])->name('audit-logs.export');
        Route::get('/reports/revenue-book/csv', [RevenueBookController::class, 'exportCsv'])->name('reports.revenue-book.csv');
    });

    // Audit log detail (must be after /audit-logs/export to avoid route conflict)
    Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');

    // Reports and audit export views (no special rate limit beyond auth)
    Route::get('/reports/revenue-book', [RevenueBookController::class, 'index'])->name('reports.revenue-book');
    Route::get('/exports/audit', [AuditExportController::class, 'index'])->name('exports.audit.index');
    Route::get('/exports/audit/preview', [AuditExportController::class, 'preview'])->name('exports.audit.preview');
    Route::get('/exports/audit/{export}/download', [AuditExportController::class, 'download'])->name('exports.audit.download');
    Route::delete('/exports/audit/{export}', [AuditExportController::class, 'destroy'])->name('exports.audit.destroy');

    // Archive info (read-only)
    Route::get('/archive', [ArchiveController::class, 'index'])->name('archive.index');
    Route::get('/invoices/{invoice}/archive/verify', [ArchiveController::class, 'verify'])->name('invoices.archive.verify');
    Route::get('/invoices/{invoice}/archive/info', [ArchiveController::class, 'info'])->name('invoices.archive.info');

    // Company lookup API - 30 requests/minute
    Route::middleware('throttle:company-lookup')->group(function () {
        Route::get('/api/company-lookup/search', [CompanyLookupController::class, 'search'])->name('company-lookup.search');
        Route::post('/api/company-lookup/validate-vat', [CompanyLookupController::class, 'validateVat'])->name('company-lookup.validate-vat');
    });

    // Dashboard API endpoints - 60 requests/minute
    Route::middleware('throttle:dashboard')->group(function () {
        Route::get('/api/dashboard/kpis', [DashboardController::class, 'kpis'])->name('dashboard.kpis');
        Route::get('/api/dashboard/revenue-chart', [DashboardController::class, 'revenueChart'])->name('dashboard.revenue-chart');
        Route::get('/api/dashboard/unpaid-invoices', [DashboardController::class, 'unpaidInvoices'])->name('dashboard.unpaid-invoices');
        Route::get('/api/dashboard/unbilled-time', [DashboardController::class, 'unbilledTime'])->name('dashboard.unbilled-time');
        Route::get('/api/dashboard/vat-summary', [DashboardController::class, 'vatSummary'])->name('dashboard.vat-summary');
    });
});

require __DIR__.'/auth.php';

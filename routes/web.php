<?php

use App\Http\Controllers\AccountantSettingsController;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\AuditExportController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BusinessSettingsController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FaiaValidatorController;
use App\Http\Controllers\EmailProviderController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceEmailController;
use App\Http\Controllers\InvoiceItemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\QuoteItemController;
use App\Http\Controllers\RevenueBookController;
use App\Http\Controllers\TimeEntryController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Public FAIA Validator - accessible without authentication
Route::get('/validateur-faia', [FaiaValidatorController::class, 'index'])->name('faia-validator');
Route::post('/validateur-faia/validate', [FaiaValidatorController::class, 'validate'])
    ->middleware('throttle:faia-validator')
    ->name('faia-validator.validate');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    // Profile routes (no special rate limit)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // CRUD operations - 120 requests/minute
    Route::middleware('throttle:crud')->group(function () {
        // Clients
        Route::resource('clients', ClientController::class);

        // Invoices
        Route::resource('invoices', InvoiceController::class);
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
        Route::resource('quotes', QuoteController::class);
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

        // Business Settings
        Route::get('/settings/business', [BusinessSettingsController::class, 'edit'])->name('settings.business.edit');
        Route::put('/settings/business', [BusinessSettingsController::class, 'update'])->name('settings.business.update');
        Route::post('/settings/business/logo', [BusinessSettingsController::class, 'uploadLogo'])->name('settings.business.logo.upload');
        Route::delete('/settings/business/logo', [BusinessSettingsController::class, 'deleteLogo'])->name('settings.business.logo.delete');

        // Audit Logs (view)
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
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
    Route::middleware('throttle:email')->group(function () {
        Route::post('/invoices/{invoice}/send-email', [InvoiceEmailController::class, 'send'])->name('invoices.send-email');
        Route::post('/invoices/{invoice}/send-reminder', [InvoiceEmailController::class, 'sendReminder'])->name('invoices.send-reminder');
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

    // Invoice email history
    Route::get('/invoices/{invoice}/emails', [InvoiceEmailController::class, 'history'])->name('invoices.emails');
    Route::post('/invoices/{invoice}/toggle-reminders', [InvoiceEmailController::class, 'toggleExcludeFromReminders'])->name('invoices.toggle-reminders');

    // Export operations - 5 requests/hour (very expensive)
    Route::middleware('throttle:export')->group(function () {
        Route::post('/exports/audit', [AuditExportController::class, 'store'])->name('exports.audit.store');
        Route::post('/invoices/{invoice}/archive', [ArchiveController::class, 'archive'])->name('invoices.archive');
        Route::post('/archive/batch', [ArchiveController::class, 'archiveBatch'])->name('archive.batch');
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

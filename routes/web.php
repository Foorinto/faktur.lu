<?php

use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\AuditExportController;
use App\Http\Controllers\BusinessSettingsController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InvoiceController;
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

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Clients
    Route::resource('clients', ClientController::class);

    // Invoices
    Route::resource('invoices', InvoiceController::class);
    Route::post('/invoices/{invoice}/finalize', [InvoiceController::class, 'finalize'])->name('invoices.finalize');
    Route::post('/invoices/{invoice}/mark-sent', [InvoiceController::class, 'markAsSent'])->name('invoices.mark-sent');
    Route::post('/invoices/{invoice}/mark-paid', [InvoiceController::class, 'markAsPaid'])->name('invoices.mark-paid');
    Route::post('/invoices/{invoice}/credit-note', [InvoiceController::class, 'createCreditNote'])->name('invoices.credit-note');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    Route::get('/invoices/{invoice}/pdf/stream', [InvoiceController::class, 'streamPdf'])->name('invoices.pdf.stream');
    Route::get('/invoices/{invoice}/pdf/preview', [InvoiceController::class, 'previewPdf'])->name('invoices.pdf.preview');
    Route::get('/invoices/{invoice}/preview-html', [InvoiceController::class, 'previewHtml'])->name('invoices.preview-html');
    Route::get('/invoices/{invoice}/preview-draft', [InvoiceController::class, 'previewDraft'])->name('invoices.preview-draft');
    Route::get('/invoices/{invoice}/draft-pdf', [InvoiceController::class, 'streamDraftPdf'])->name('invoices.draft-pdf');
    Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'sendEmail'])->name('invoices.send');

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
    Route::get('/quotes/{quote}/pdf', [QuoteController::class, 'downloadPdf'])->name('quotes.pdf');
    Route::get('/quotes/{quote}/pdf/stream', [QuoteController::class, 'streamPdf'])->name('quotes.pdf.stream');
    Route::get('/quotes/{quote}/pdf/preview', [QuoteController::class, 'previewPdf'])->name('quotes.pdf.preview');
    Route::get('/quotes/{quote}/preview-html', [QuoteController::class, 'previewHtml'])->name('quotes.preview-html');

    // Quote Items
    Route::post('/quotes/{quote}/items', [QuoteItemController::class, 'store'])->name('quotes.items.store');
    Route::put('/quotes/{quote}/items/{item}', [QuoteItemController::class, 'update'])->name('quotes.items.update');
    Route::patch('/quotes/{quote}/items/{item}/move', [QuoteItemController::class, 'move'])->name('quotes.items.move');
    Route::delete('/quotes/{quote}/items/{item}', [QuoteItemController::class, 'destroy'])->name('quotes.items.destroy');

    // Expenses
    Route::resource('expenses', ExpenseController::class);
    Route::get('/expenses-summary', [ExpenseController::class, 'summary'])->name('expenses.summary');

    // Reports
    Route::get('/reports/revenue-book', [RevenueBookController::class, 'index'])->name('reports.revenue-book');
    Route::get('/reports/revenue-book/pdf', [RevenueBookController::class, 'exportPdf'])->name('reports.revenue-book.pdf');
    Route::get('/reports/revenue-book/csv', [RevenueBookController::class, 'exportCsv'])->name('reports.revenue-book.csv');

    // Audit Export (FAIA)
    Route::get('/exports/audit', [AuditExportController::class, 'index'])->name('exports.audit.index');
    Route::get('/exports/audit/preview', [AuditExportController::class, 'preview'])->name('exports.audit.preview');
    Route::post('/exports/audit', [AuditExportController::class, 'store'])->name('exports.audit.store');
    Route::get('/exports/audit/{export}/download', [AuditExportController::class, 'download'])->name('exports.audit.download');
    Route::delete('/exports/audit/{export}', [AuditExportController::class, 'destroy'])->name('exports.audit.destroy');

    // Archive (PDF/A)
    Route::get('/archive', [ArchiveController::class, 'index'])->name('archive.index');
    Route::post('/invoices/{invoice}/archive', [ArchiveController::class, 'archive'])->name('invoices.archive');
    Route::post('/archive/batch', [ArchiveController::class, 'archiveBatch'])->name('archive.batch');
    Route::get('/invoices/{invoice}/archive/download', [ArchiveController::class, 'download'])->name('invoices.archive.download');
    Route::get('/invoices/{invoice}/archive/verify', [ArchiveController::class, 'verify'])->name('invoices.archive.verify');
    Route::get('/invoices/{invoice}/archive/info', [ArchiveController::class, 'info'])->name('invoices.archive.info');

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

    // Dashboard API endpoints
    Route::get('/api/dashboard/kpis', [DashboardController::class, 'kpis'])->name('dashboard.kpis');
    Route::get('/api/dashboard/revenue-chart', [DashboardController::class, 'revenueChart'])->name('dashboard.revenue-chart');
    Route::get('/api/dashboard/unpaid-invoices', [DashboardController::class, 'unpaidInvoices'])->name('dashboard.unpaid-invoices');
    Route::get('/api/dashboard/unbilled-time', [DashboardController::class, 'unbilledTime'])->name('dashboard.unbilled-time');
    Route::get('/api/dashboard/vat-summary', [DashboardController::class, 'vatSummary'])->name('dashboard.vat-summary');
});

require __DIR__.'/auth.php';

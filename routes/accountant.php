<?php

use App\Http\Controllers\Accountant\AccountantAuthController;
use App\Http\Controllers\Accountant\AccountantDashboardController;
use App\Http\Controllers\Accountant\AccountantExportController;
use App\Http\Controllers\Accountant\AccountantMassExportController;
use App\Http\Controllers\Accountant\AccountantTwoFactorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Accountant Routes
|--------------------------------------------------------------------------
|
| Routes for the accountant portal. These routes are prefixed with /comptable
| and use the 'accountant' guard for authentication.
|
*/

Route::prefix('comptable')->name('accountant.')->group(function () {
    // Guest routes
    Route::middleware('guest:accountant')->group(function () {
        Route::get('/login', [AccountantAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AccountantAuthController::class, 'login'])->name('login.submit');
    });

    // Invitation acceptance (no auth required)
    Route::get('/invitation/{token}', [AccountantAuthController::class, 'showAcceptInvitation'])->name('accept');
    Route::post('/invitation/{token}', [AccountantAuthController::class, 'acceptInvitation'])->name('accept.submit');

    // Défi du second facteur : le mot de passe a été vérifié, la session n'est
    // pas encore ouverte. Hors du groupe authentifié, par construction.
    Route::get('/double-facteur', [AccountantTwoFactorController::class, 'showChallenge'])->name('two-factor.challenge');
    Route::post('/double-facteur', [AccountantTwoFactorController::class, 'verifyChallenge'])->name('two-factor.verify');

    // Authenticated routes
    Route::middleware('accountant.auth')->group(function () {
        // Enrôlement : accessible authentifié mais SANS le middleware 2FA,
        // sans quoi la redirection tournerait en boucle sur elle-même.
        Route::get('/double-facteur/activation', [AccountantTwoFactorController::class, 'showEnrollment'])->name('two-factor.enroll');
        Route::post('/double-facteur/activation', [AccountantTwoFactorController::class, 'confirmEnrollment'])->name('two-factor.confirm');
        Route::get('/double-facteur/codes-de-secours', [AccountantTwoFactorController::class, 'showRecoveryCodes'])->name('two-factor.recovery');

        // La déconnexion reste toujours possible : enfermer quelqu'un dans un
        // écran d'enrôlement sans porte de sortie serait une impasse.
        Route::post('/logout', [AccountantAuthController::class, 'logout'])->name('logout');

        // Tout le reste exige un second facteur confirmé.
        Route::middleware('accountant.2fa')->group(function () {
            Route::get('/', [AccountantDashboardController::class, 'index'])->name('dashboard');
            Route::post('/mass-export', [AccountantMassExportController::class, 'massExport'])->name('mass-export');
            Route::post('/consolidated-report', [AccountantMassExportController::class, 'consolidatedReport'])->name('consolidated-report');

        // Client routes (with access verification)
        Route::middleware('accountant.access')->group(function () {
            Route::get('/client/{user}', [AccountantDashboardController::class, 'client'])->name('client');
            Route::get('/client/{user}/invoice/{invoice}/pdf', [AccountantExportController::class, 'invoicePdf'])->name('invoice-pdf');
            Route::get('/client/{user}/export/accounting/{format}', [AccountantExportController::class, 'downloadAccounting'])->name('accounting-export');
            Route::get('/client/{user}/export/{type}', [AccountantExportController::class, 'download'])->name('export');
            });
        });
    });
});

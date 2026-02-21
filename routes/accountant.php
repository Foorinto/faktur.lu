<?php

use App\Http\Controllers\Accountant\AccountantAuthController;
use App\Http\Controllers\Accountant\AccountantDashboardController;
use App\Http\Controllers\Accountant\AccountantExportController;
use App\Http\Controllers\Accountant\AccountantMassExportController;
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

    // Authenticated routes
    Route::middleware('accountant.auth')->group(function () {
        Route::get('/', [AccountantDashboardController::class, 'index'])->name('dashboard');
        Route::post('/logout', [AccountantAuthController::class, 'logout'])->name('logout');
        Route::post('/mass-export', [AccountantMassExportController::class, 'massExport'])->name('mass-export');
        Route::post('/consolidated-report', [AccountantMassExportController::class, 'consolidatedReport'])->name('consolidated-report');

        // Client routes (with access verification)
        Route::middleware('accountant.access')->group(function () {
            Route::get('/client/{user}', [AccountantDashboardController::class, 'client'])->name('client');
            Route::get('/client/{user}/export/accounting/{format}', [AccountantExportController::class, 'downloadAccounting'])->name('accounting-export');
            Route::get('/client/{user}/export/{type}', [AccountantExportController::class, 'download'])->name('export');
        });
    });
});

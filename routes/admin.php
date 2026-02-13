<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminMaintenanceController;
use App\Http\Controllers\Admin\AdminMonitoringController;
use App\Http\Controllers\Admin\AdminSupportController;
use App\Http\Controllers\Admin\AdminUserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| These routes are for the admin panel, accessible via a secret URL prefix.
| All routes use the web middleware group plus additional admin middleware.
|
*/

Route::prefix(config('admin.url_prefix', 'admin'))->name('admin.')->group(function () {

    // Login routes (strict rate limiting: 3/minute)
    Route::middleware(['admin.ip', 'throttle:admin-login'])->group(function () {
        Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AdminAuthController::class, 'login']);
    });

    // 2FA routes (separate rate limiting: 5/minute)
    Route::middleware(['admin.ip', 'throttle:admin-2fa'])->group(function () {
        Route::get('two-factor', [AdminAuthController::class, 'showTwoFactor'])->name('2fa');
        Route::post('two-factor', [AdminAuthController::class, 'verifyTwoFactor']);
    });

    // Protected admin routes (require full authentication)
    Route::middleware(['admin.auth', 'admin.timeout'])->group(function () {
        // Dashboard
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');

        // Users management
        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [AdminUserController::class, 'show'])->name('users.show');
        Route::post('users/{user}/toggle-active', [AdminUserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::post('users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('users.reset-password');
        Route::post('users/{user}/reset-2fa', [AdminUserController::class, 'reset2fa'])->name('users.reset-2fa');
        Route::post('users/{user}/impersonate', [AdminUserController::class, 'impersonate'])->name('users.impersonate');
        Route::post('users/{user}/restore', [AdminUserController::class, 'restore'])->name('users.restore');
        Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
        Route::delete('users/{user}/force', [AdminUserController::class, 'forceDelete'])->name('users.force-delete');
        Route::post('impersonation/stop', [AdminUserController::class, 'stopImpersonation'])->name('impersonation.stop');

        // Support tickets
        Route::get('support', [AdminSupportController::class, 'index'])->name('support.index');
        Route::get('support/{ticket}', [AdminSupportController::class, 'show'])->name('support.show');
        Route::post('support/{ticket}/reply', [AdminSupportController::class, 'reply'])->name('support.reply');
        Route::put('support/{ticket}', [AdminSupportController::class, 'update'])->name('support.update');
        Route::delete('support/{ticket}', [AdminSupportController::class, 'destroy'])->name('support.destroy');

        // Maintenance
        Route::get('maintenance', [AdminMaintenanceController::class, 'index'])->name('maintenance');
        Route::post('maintenance/cache-clear', [AdminMaintenanceController::class, 'clearCache'])->name('maintenance.cache-clear');
        Route::post('maintenance/toggle', [AdminMaintenanceController::class, 'toggleMaintenance'])->name('maintenance.toggle');
        Route::get('maintenance/logs', [AdminMaintenanceController::class, 'logs'])->name('maintenance.logs');

        // Monitoring
        Route::get('monitoring', [AdminMonitoringController::class, 'index'])->name('monitoring');
        Route::get('monitoring/refresh', [AdminMonitoringController::class, 'refresh'])->name('monitoring.refresh');
    });
});

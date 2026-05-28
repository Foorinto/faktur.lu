<?php

use App\Http\Controllers\Admin\AdminBlogCategoryController;
use App\Http\Controllers\Admin\AdminBlogController;
use App\Http\Controllers\Admin\AdminBlogTagController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminMaintenanceController;
use App\Http\Controllers\Admin\AdminMonitoringController;
use App\Http\Controllers\Admin\AdminNewsletterController;
use App\Http\Controllers\Admin\AdminSupportController;
use App\Http\Controllers\Admin\AdminSurveyController;
use App\Http\Controllers\Admin\AdminUserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| These routes use the standard Laravel auth (same login as the app).
| Only users with is_admin=true can access these routes.
| No separate login form needed — just log in normally on faktur.lu.
|
*/

// Route accessible meme quand on impersonifie un utilisateur (pas de middleware admin.user)
Route::middleware(['auth'])
    ->post(config('admin.url_prefix', 'admin') . '/impersonation/stop', [AdminUserController::class, 'stopImpersonation'])
    ->name('admin.impersonation.stop');

Route::prefix(config('admin.url_prefix', 'admin'))
    ->name('admin.')
    ->middleware(['auth', 'verified', 'admin.user'])
    ->group(function () {

        // Dashboard
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

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

        // Satisfaction surveys (NPS)
        Route::get('surveys', [AdminSurveyController::class, 'index'])->name('surveys.index');
        Route::get('surveys/export', [AdminSurveyController::class, 'export'])->name('surveys.export');

        // Support tickets
        Route::get('support', [AdminSupportController::class, 'index'])->name('support.index');
        Route::get('support/{ticket}', [AdminSupportController::class, 'show'])->name('support.show');
        Route::post('support/{ticket}/reply', [AdminSupportController::class, 'reply'])->name('support.reply');
        Route::post('support/{ticket}/messages/{message}/resend', [AdminSupportController::class, 'resendMessage'])->name('support.message.resend');
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

        // Blog management
        Route::get('blog', [AdminBlogController::class, 'index'])->name('blog.index');
        Route::get('blog/create', [AdminBlogController::class, 'create'])->name('blog.create');
        Route::post('blog', [AdminBlogController::class, 'store'])->name('blog.store');
        Route::get('blog/{post}/edit', [AdminBlogController::class, 'edit'])->name('blog.edit');
        Route::put('blog/{post}', [AdminBlogController::class, 'update'])->name('blog.update');
        Route::delete('blog/{post}', [AdminBlogController::class, 'destroy'])->name('blog.destroy');
        Route::post('blog/{post}/duplicate', [AdminBlogController::class, 'duplicate'])->name('blog.duplicate');

        // Blog categories
        Route::get('blog-categories', [AdminBlogCategoryController::class, 'index'])->name('blog-categories.index');
        Route::post('blog-categories', [AdminBlogCategoryController::class, 'store'])->name('blog-categories.store');
        Route::put('blog-categories/{category}', [AdminBlogCategoryController::class, 'update'])->name('blog-categories.update');
        Route::delete('blog-categories/{category}', [AdminBlogCategoryController::class, 'destroy'])->name('blog-categories.destroy');
        Route::post('blog-categories/reorder', [AdminBlogCategoryController::class, 'reorder'])->name('blog-categories.reorder');

        // Blog tags
        Route::get('blog-tags', [AdminBlogTagController::class, 'index'])->name('blog-tags.index');
        Route::post('blog-tags', [AdminBlogTagController::class, 'store'])->name('blog-tags.store');
        Route::put('blog-tags/{tag}', [AdminBlogTagController::class, 'update'])->name('blog-tags.update');
        Route::delete('blog-tags/{tag}', [AdminBlogTagController::class, 'destroy'])->name('blog-tags.destroy');

        // Newsletter subscribers
        Route::get('newsletter', [AdminNewsletterController::class, 'index'])->name('newsletter.index');
        Route::get('newsletter/export', [AdminNewsletterController::class, 'export'])->name('newsletter.export');
        Route::delete('newsletter/{subscriber}', [AdminNewsletterController::class, 'destroy'])->name('newsletter.destroy');
    });

<?php

use App\Jobs\SendPaymentReminders;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withSchedule(function (Schedule $schedule): void {
        // Send payment reminders daily at 9:00 AM
        $schedule->job(new SendPaymentReminders())
            ->dailyAt('09:00')
            ->withoutOverlapping()
            ->onOneServer();
    })
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));

            Route::middleware('web')
                ->group(base_path('routes/accountant.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Remplacer le middleware de maintenance par notre version personnalisée
        $middleware->replace(
            \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \App\Http\Middleware\PreventRequestsDuringMaintenance::class
        );

        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\CheckUserIsActive::class,
        ]);

        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuthenticated::class,
            'admin.timeout' => \App\Http\Middleware\AdminSessionTimeout::class,
            'admin.ip' => \App\Http\Middleware\AdminIpBlocking::class,
            'accountant.auth' => \App\Http\Middleware\AccountantAuthenticated::class,
            'accountant.access' => \App\Http\Middleware\VerifyAccountantAccess::class,
            'plan.limit' => \App\Http\Middleware\CheckPlanLimits::class,
            'plan.feature' => \App\Http\Middleware\CheckPlanFeature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

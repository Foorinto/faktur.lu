<?php

use App\Jobs\SendPaymentReminders;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withSchedule(function (Schedule $schedule): void {
        // Send payment reminders daily at 9:00 AM
        $schedule->job(new SendPaymentReminders())
            ->dailyAt('09:00')
            ->withoutOverlapping()
            ->onOneServer();

        // Send trial reminders daily at 8:00 AM
        $schedule->command('trial:send-reminders')
            ->dailyAt('08:00')
            ->withoutOverlapping()
            ->onOneServer();

        // Generate recurring invoices daily at 6:00 AM
        $schedule->command('recurring:generate')
            ->dailyAt('06:00')
            ->withoutOverlapping()
            ->onOneServer();

        // Send drip campaign emails daily at 9:30 AM
        $schedule->command('drip:send')
            ->dailyAt('09:30')
            ->withoutOverlapping()
            ->onOneServer();

        // Send satisfaction surveys (NPS) ~14 days after signup, daily at 10:00 AM
        $schedule->command('survey:send')
            ->dailyAt('10:00')
            ->withoutOverlapping()
            ->onOneServer();

        // Cleanup old monitoring metrics daily at 3:00 AM
        $schedule->command('monitoring:cleanup')
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->onOneServer();

        // Send CRM reminder notifications every 15 minutes
        $schedule->command('reminders:send')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->onOneServer();

        // Contrôle horaire de la clé de chiffrement.
        //
        // Toutes les heures, et non une fois par jour : un changement d'APP_KEY
        // passé inaperçu ne coûte pas la même chose selon qu'on s'en aperçoit
        // dans l'heure ou trois semaines plus tard, quand des centaines
        // d'enregistrements ont déjà été écrits sous la nouvelle clé — et que
        // rétablir l'ancienne ne suffit plus à tout relire.
        $schedule->command('encryption:check')
            ->hourly()
            ->withoutOverlapping()
            ->onOneServer();

        // Send HR event reminders (J-1 and J0) daily at 7:00 AM
        $schedule->command('hr:send-event-reminders')
            ->dailyAt('07:00')
            ->withoutOverlapping()
            ->onOneServer();

        // Battement DU PLANIFICATEUR, à distinguer de celui du cron.
        //
        // La ligne crontab écrit déjà un horodatage via `date`, avant que PHP ne
        // démarre. Mais un `date` qui passe ne prouve rien sur Laravel : le
        // 2026-08-04, le cron attrapait un binaire PHP en mode CGI, qui ignore
        // les arguments de la ligne de commande. « php artisan schedule:run »
        // devenait « php artisan », affichait la liste des commandes et sortait
        // en succès. Quatre nuits sans sauvegarde, sans une seule erreur.
        //
        // Ce second battement n'est écrit que si le planificateur s'exécute
        // réellement. L'écart entre les deux fichiers nomme la panne.
        $schedule->call(fn () => @touch(storage_path('logs/scheduler-last-run.txt')))
            ->everyMinute()
            ->name('scheduler-heartbeat')
            ->withoutOverlapping();

        // Database backup daily at configured time (default 3:00 AM)
        //
        // Volontairement AU PREMIER PLAN. `runInBackground()` fait lancer la
        // commande par un shell détaché (« ... & »), sans nohup ni setsid : sur
        // un hébergement mutualisé, ce processus orphelin est fauché dès que la
        // session du cron se termine, et la sauvegarde meurt avant d'avoir écrit
        // la moindre ligne de journal. C'était la seule des huit tâches
        // planifiées à être détachée — et la seule à n'avoir jamais abouti.
        // Le dump dure quelques secondes ; il n'y a rien à gagner à le détacher.
        if (config('backup.enabled')) {
            $schedule->command('backup:run')
                ->dailyAt(config('backup.schedule_time', '03:00'))
                ->withoutOverlapping()
                ->onOneServer();
        }
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

            Route::middleware('web')
                ->group(base_path('routes/collaborator.php'));

            Route::middleware('web')
                ->group(base_path('routes/employee-portal.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('comptable/*') || $request->is('comptable')) {
                return route('accountant.login');
            }

            return route('login');
        });
        // Remplacer le middleware de maintenance par notre version personnalisée
        $middleware->replace(
            \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \App\Http\Middleware\PreventRequestsDuringMaintenance::class
        );

        // Dynamic rendering: serve prerendered HTML to crawlers only. Prepended so a
        // bot request with an existing snapshot short-circuits before the Inertia stack.
        // Pure pass-through for real users — no effect on the app's behaviour.
        $middleware->web(prepend: [
            // Avant tout le reste : inutile de rendre une page pour la jeter.
            \App\Http\Middleware\RedirectToHttps::class,
            \App\Http\Middleware\ServePrerendered::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\CheckUserIsActive::class,
            \App\Http\Middleware\TrackRequestMetrics::class,
        ]);

        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuthenticated::class,
            'admin.timeout' => \App\Http\Middleware\AdminSessionTimeout::class,
            'admin.ip' => \App\Http\Middleware\AdminIpBlocking::class,
            'admin.nohttp2' => \App\Http\Middleware\DisableHttp2Push::class,
            'admin.user' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'accountant.auth' => \App\Http\Middleware\AccountantAuthenticated::class,
            'accountant.access' => \App\Http\Middleware\VerifyAccountantAccess::class,
            'accountant.2fa' => \App\Http\Middleware\EnsureAccountantHasTwoFactor::class,
            'collaborator' => \App\Http\Middleware\EnsureUserIsCollaborator::class,
            'employee.portal' => \App\Http\Middleware\EnsureUserIsEmployee::class,
            'org.admin' => \App\Http\Middleware\EnsureUserIsOrganizationAdmin::class,
            'plan.limit' => \App\Http\Middleware\CheckPlanLimits::class,
            'plan.feature' => \App\Http\Middleware\CheckPlanFeature::class,
            'check.trial' => \App\Http\Middleware\CheckTrialExpired::class,
            'redirect.employee' => \App\Http\Middleware\RedirectEmployeeOnly::class,
            'honeypot' => \App\Http\Middleware\ValidateHoneypot::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Global safety net: turn unexpected server errors into a clear message + a
        // support reference code (the full exception is logged under that same code),
        // instead of leaking technical details (e.g. raw SQL) to the user.
        // Only in production; HTTP/validation/auth exceptions keep their normal handling.
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (config('app.debug')) {
                return null; // keep Laravel's detailed error page during development
            }

            if ($e instanceof \App\Exceptions\UserFacingException
                || $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                || $e instanceof \Illuminate\Validation\ValidationException
                || $e instanceof \Illuminate\Auth\AuthenticationException
                || $e instanceof \Illuminate\Auth\Access\AuthorizationException
                || $e instanceof \Illuminate\Session\TokenMismatchException
                // HttpResponseException n'est pas une erreur : c'est le moyen par
                // lequel Laravel remonte une réponse DÉJÀ CONSTRUITE à travers la
                // pile. La limitation de débit s'en sert dès qu'un limiteur
                // définit sa propre réponse — et nos limiteurs en définissent
                // tous une.
                //
                // Faute de l'exclure, « Trop de tentatives d'inscription. » était
                // remplacé par « Une erreur inattendue s'est produite… code
                // XXXX-YYYY ». Le message soigneusement écrit n'atteignait
                // personne, et l'utilisateur contactait le support pour une
                // limite volontaire. Constaté sur l'inscription le 2026-08-18.
                || $e instanceof \Illuminate\Http\Exceptions\HttpResponseException) {
                return null; // domain messages + 401/403/404/419/422... handled normally
            }

            $message = \App\Support\UserError::report($e, 'unhandled');

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 500);
            }

            return back()->with('error', $message);
        });
    })->create();

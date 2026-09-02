<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Middleware;
use Symfony\Component\HttpFoundation\Response;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     *
     * This is crucial for Inertia to know when assets have changed (after deployment)
     * and force a full page reload instead of an XHR response.
     */
    public function version(Request $request): ?string
    {
        // Use the Vite manifest hash as version
        // This ensures version changes on every deployment
        $manifestPath = public_path('build/manifest.json');

        $base = file_exists($manifestPath)
            ? md5_file($manifestPath)
            // Fallback to parent (which uses mix-manifest.json or null)
            : (string) parent::version($request);

        // Le groupe de routes Ziggy fait partie de la version.
        //
        // `@routes` grave la liste des routes dans le HTML, et cette liste
        // dépend du profil : un visiteur anonyme n'en reçoit qu'une trentaine
        // de familles, un administrateur les reçoit toutes. Or Inertia ne
        // recharge pas le HTML lors d'une visite : après une connexion, le
        // navigateur gardait la liste ANONYME et le premier `route('…')` du
        // tableau de bord levait « route is not in the route list ». La page
        // restait blanche jusqu'à un rechargement manuel.
        //
        // En intégrant le groupe à la version, tout changement de profil —
        // connexion, déconnexion, 2FA, usurpation d'identité — provoque un
        // écart de version, et Inertia fait de lui-même un rechargement
        // complet. C'est exactement ce pour quoi ce mécanisme existe.
        return md5($base.'|'.(self::ziggyGroup() ?? 'auth'));
    }

    /**
     * Groupe de routes Ziggy à publier pour le profil courant.
     *
     * Source unique : `resources/views/app.blade.php` appelle cette méthode, et
     * `version()` s'en sert aussi. Les deux DOIVENT rester d'accord — si la vue
     * publiait un groupe et la version en supposait un autre, le rechargement
     * ne se déclencherait pas au bon moment, et la panne reviendrait sans que
     * rien ne le signale.
     *
     * `null` désigne le groupe par défaut de Ziggy : toutes les routes, moins
     * celles exclues globalement.
     */
    public static function ziggyGroup(): ?string
    {
        // Le comptable est authentifié sur un AUTRE garde. `auth()->check()`
        // interroge le garde par défaut et le tient pour anonyme : son portail
        // recevait donc la liste publique, dépourvue des routes `accountant.*`
        // que son gabarit appelle. Et comme le groupe ne changeait pas entre sa
        // page de connexion et son tableau de bord, la version restait
        // identique : le rechargement salvateur ne se déclenchait pas.
        //
        // Il reçoit le groupe par défaut, comme tout utilisateur connecté. Le
        // surcoût de charge utile ne concerne pas les pages publiques, seules
        // visées par l'allègement.
        if (auth()->guard('accountant')->check()) {
            return null;
        }

        if (! auth()->check()) {
            return 'public';
        }

        return auth()->user()->is_admin ? 'admin' : null;
    }

    /**
     * Handle the incoming request.
     */
    public function handle(Request $request, \Closure $next): Response
    {
        $response = parent::handle($request, $next);

        // Prevent caching of Inertia XHR responses
        // This fixes the issue where browsers show raw JSON when tabs are restored
        if ($request->header('X-Inertia')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
        }

        return $response;
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $locale = App::getLocale();

        return [
            ...parent::share($request),
            // Identité de la marque, partagée à toutes les pages.
            //
            // Un changement de dénomination est engagé. Le nom vient du serveur
            // plutôt que de `VITE_APP_NAME`, qui est figé dans le bundle au
            // moment de la compilation : le jour du changement, une variable
            // d'environnement suffira, sans recompiler pour ces valeurs.
            'marque' => [
                'nom' => config('marque.nom'),
                'domaine' => config('marque.domaine'),
                'url' => config('app.url'),
                'compte_social' => config('marque.compte_social'),
                'email_contact' => config('marque.email_contact'),
            ],
            // Guard explicite : cette charge décrit un compte utilisateur de
            // l'application, avec son plan et son essai. Le portail comptable
            // s'authentifie sur un autre guard, sur un modèle qui ne connaît
            // aucune de ces notions — s'en remettre au guard par défaut ferait
            // dépendre le rendu de l'ordre de résolution.
            'auth' => [
                'user' => $request->user('web') ? array_merge(
                    $request->user('web')->toArray(),
                    [
                        'is_on_trial' => $request->user('web')->isOnGenericTrial(),
                        'trial_days_remaining' => $request->user('web')->trialDaysRemaining(),
                        'trial_ends_at' => $request->user('web')->trial_ends_at?->toISOString(),
                        'is_read_only' => $request->user('web')->isReadOnly(),
                        'plan_name' => $request->user('web')->plan,
                        'is_collaborator' => $request->user('web')->isCollaborator(),
                        'is_organization_owner' => $request->user('web')->isOrganizationOwner(),
                        'is_employee' => $request->user('web')->isEmployee(),
                        'is_pro' => $request->user('web')->isPro(),
                        'is_essentiel' => $request->user('web')->isEssentiel(),
                        'is_free' => $request->user('web')->isFree(),
                    ]
                ) : null,
            ],
            'csrf_token' => csrf_token(),
            'impersonating' => $request->session()->get('impersonating'),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
                'upgrade_required' => fn () => $request->session()->get('upgrade_required'),
            ],
            'locale' => $locale,
            'supportedLocales' => config('app.supported_locales', ['fr', 'de', 'en', 'lb', 'pt']),
            'availableLocales' => config('app.locale_names', [
                'fr' => 'Français',
                'de' => 'Deutsch',
                'en' => 'English',
                'lb' => 'Lëtzebuergesch',
            ]),
            // Les traductions ne voyagent plus dans le HTML : 323 Ko bruts par
            // page, soit plus de la moitié du document, pour une poignée de
            // clés réellement utilisées. Elles sont servies dans un fichier à
            // part, mis en cache par le navigateur et téléchargé une seule fois.
            // L'empreinte dans l'URL rend ce cache sûr et auto-invalidé.
            'translationsUrl' => route('translations.show', [
                'locale' => $locale,
                'v' => \App\Http\Controllers\TranslationsController::fingerprint(),
            ]),
            'currentPath' => $request->getPathInfo(),
            'currentUrl' => $request->url(),
            // Company brand colour (PDF colour) — used e.g. to tint links in the
            // rich-text editor so they match the PDF. Null when not authenticated.
            'companyColor' => fn () => \App\Models\BusinessSettings::getInstance()?->getEffectivePdfColor(),
            'unreadSupportCount' => fn () => $this->getUnreadSupportCount($request),
            'pendingRemindersCount' => fn () => $this->getPendingRemindersCount($request),
        ];
    }

    /**
     * Get the count of unread support messages for the current user.
     */
    protected function getUnreadSupportCount(Request $request): int
    {
        $user = $request->user();

        if (!$user) {
            return 0;
        }

        return $user->supportTickets()
            ->where(function ($query) {
                $query->whereHas('messages', function ($q) {
                    $q->where('is_internal', false)
                        ->where('sender_type', '!=', \App\Models\User::class)
                        ->whereRaw('support_messages.created_at > COALESCE(support_tickets.user_last_read_at, support_tickets.created_at)');
                });
            })
            ->count();
    }

    /**
     * Get the count of pending reminders for the current user.
     */
    protected function getPendingRemindersCount(Request $request): int
    {
        $user = $request->user();

        if (!$user) {
            return 0;
        }

        return \App\Models\Reminder::where('user_id', $user->id)->pending()->count();
    }

    /**
     * In-process cache of filtered translations per locale.
     * Prevents re-reading and re-filtering lang files on every Inertia request,
     * which otherwise multiplies memory usage in long-running processes (octane, tests).
     */
    protected static array $translationsCache = [];

    /**
     * Traductions destinées au front, pour une langue.
     *
     * Publique et statique depuis que le payload est servi dans un fichier à
     * part (TranslationsController) : les deux doivent produire exactement le
     * même contenu, et une seule liste blanche doit exister.
     */
    public static function translationsFor(string $locale): array
    {
        if (isset(self::$translationsCache[$locale])) {
            return ['app' => self::remplacerLaMarque(self::$translationsCache[$locale])];
        }

        $appPath = lang_path("{$locale}/app.php");
        $translations = file_exists($appPath) ? require $appPath : null;

        if ($translations === null) {
            $fallbackPath = lang_path('fr/app.php');
            if (! file_exists($fallbackPath)) {
                return self::$translationsCache[$locale] = [];
            }
            $translations = require $fallbackPath;
        }

        // Strip top-level keys used only by backend email/PDF templates (long content
        // strings) to reduce payload size. Frontend-needed labels with the same prefix
        // (email_settings, email_signature, pdf_color, …) are kept via the allowlist.
        $backendOnlyPrefixes = ['email_', 'pdf_', 'mail_subject_'];
        $frontendKept = [
            'email_settings', 'email_history', 'email_signature', 'email_signature_placeholder',
            'email_provider', 'email_provider_tab', 'email_provider_config_title',
            'email_send_provider', 'email_send_provider_description',
            'email_unverified', 'email_verification_title', 'email_verification_message',
            // Utilisée par la page d'acceptation d'invitation d'un collaborateur,
            // qui affichait « Email » en dur faute de recevoir sa traduction.
            'email_label',
            'pdf_color', 'pdf_color_help', 'pdf_a_archiving',
            'pdf_text_size', 'pdf_text_size_normal', 'pdf_text_size_large',
            'pdf_text_size_xlarge', 'pdf_text_size_help',
            'pdf_logo_size', 'pdf_logo_size_small', 'pdf_logo_size_help',
        ];
        foreach ($translations as $key => $value) {
            if (in_array($key, $frontendKept, true)) {
                continue;
            }
            foreach ($backendOnlyPrefixes as $prefix) {
                if (str_starts_with($key, $prefix)) {
                    unset($translations[$key]);
                    break;
                }
            }
        }

        // Le nom de la marque est rempli ici, avant l'envoi au navigateur.
        //
        // Côté serveur c'est le traducteur qui s'en charge ; côté client, les
        // traductions partent en JSON et `useTranslations` ne remplace un
        // marqueur que si un paramètre lui est passé. Sans cette substitution,
        // « :app » s'afficherait en clair dans l'interface.
        self::$translationsCache[$locale] = $translations;

        return ['app' => self::remplacerLaMarque($translations)];
    }

    /**
     * Remplace `:app` par le nom de la marque, à tous les niveaux du tableau.
     *
     * @param  array<string, mixed>  $traductions
     * @return array<string, mixed>
     */
    private static function remplacerLaMarque(array $traductions): array
    {
        $nom = (string) config('marque.nom');

        array_walk_recursive($traductions, function (&$valeur) use ($nom) {
            if (is_string($valeur) && str_contains($valeur, ':app')) {
                $valeur = str_replace(':app', $nom, $valeur);
            }
        });

        return $traductions;
    }
}

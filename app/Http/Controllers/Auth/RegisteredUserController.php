<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\NewUserRegisteredNotification;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use App\Support\UserError;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms' => 'required|accepted',
            'dpa' => 'required|accepted',
        ]);

        // Champs fillable
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            // Première étape : le secteur d'activité. Écrire 'company' ici
            // sautait purement et simplement l'écran, le repli `?? 'sector'` du
            // contrôleur d'onboarding ne s'appliquant qu'à une valeur absente.
            'onboarding_step' => 'sector',
            'locale' => session('locale', config('app.locale')),
        ]);

        // trial_ends_at et account_status retires du fillable (champs sensibles)
        // -> forceFill obligatoire
        //
        // L'acceptation est datée et rattachée à une version : c'est ce qui la
        // rend opposable. Une case cochée dont il ne reste rien ne prouve rien,
        // et le DPA lui-même prend effet « à la création du compte et à
        // l'acceptation des CGU » — encore faut-il pouvoir en administrer la
        // preuve.
        $user->forceFill([
            'trial_ends_at' => now()->addDays(14),
            'account_status' => 'trial',
            'terms_accepted_at' => now(),
            'dpa_accepted_at' => now(),
            'dpa_version' => \App\Support\DpaDocument::VERSION,
            'dpa_acceptance_method' => 'explicit',
        ])->save();

        /*
         * Un envoi qui échoue ne doit pas faire échouer l'inscription.
         *
         * Le compte existe déjà à ce stade. Laisser remonter l'exception
         * laissait l'utilisateur devant une erreur, non connecté, et avec une
         * adresse désormais prise : il ne pouvait même pas réessayer. Un relais
         * SMTP indisponible quelques minutes suffisait.
         *
         * L'email de vérification se redemande depuis la page qui suit, et la
         * notification d'administration n'a jamais eu à bloquer qui que ce
         * soit : être prévenu de l'arrivée d'un client est notre affaire, pas
         * la sienne.
         */
        try {
            event(new Registered($user));
        } catch (\Throwable $e) {
            UserError::report($e, 'registration.verification_email');
        }

        $adminEmail = config('admin.support_email');

        if ($adminEmail) {
            try {
                Mail::to($adminEmail)->send(new NewUserRegisteredNotification($user));
            } catch (\Throwable $e) {
                UserError::report($e, 'registration.admin_notification');
            }
        }

        Auth::login($user);

        // Redirige vers une page de remerciement qui informe l'utilisateur qu'un
        // email de verification a ete envoye. Le clic sur le lien email validera
        // l'email et redirigera ensuite vers l'onboarding (cf. VerifyEmailController).
        return redirect()->route('register.thank-you');
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\NewUserRegisteredNotification;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
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
        ]);

        // Champs fillable
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'onboarding_step' => 'company',
            'locale' => session('locale', config('app.locale')),
        ]);

        // trial_ends_at et account_status retires du fillable (champs sensibles)
        // -> forceFill obligatoire
        $user->forceFill([
            'trial_ends_at' => now()->addDays(14),
            'account_status' => 'trial',
        ])->save();

        event(new Registered($user));

        // Send notification email to admin
        $adminEmail = config('admin.support_email');
        if ($adminEmail) {
            Mail::to($adminEmail)->send(new NewUserRegisteredNotification($user));
        }

        Auth::login($user);

        // Redirige vers une page de remerciement qui informe l'utilisateur qu'un
        // email de verification a ete envoye. Le clic sur le lien email validera
        // l'email et redirigera ensuite vers l'onboarding (cf. VerifyEmailController).
        return redirect()->route('register.thank-you');
    }
}

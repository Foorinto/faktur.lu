<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Models\Accountant;
use App\Models\AccountantInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class AccountantAuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLogin()
    {
        if (auth('accountant')->check()) {
            return redirect()->route('accountant.dashboard');
        }

        return Inertia::render('Accountant/Login');
    }

    /**
     * Handle login.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('accountant')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('accountant.dashboard'));
        }

        return back()->withErrors([
            'email' => 'Ces identifiants ne correspondent pas à nos enregistrements.',
        ])->onlyInput('email');
    }

    /**
     * Handle logout.
     */
    public function logout(Request $request)
    {
        Auth::guard('accountant')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('accountant.login');
    }

    /**
     * Show the invitation acceptance form.
     */
    public function showAcceptInvitation(string $token)
    {
        $invitation = AccountantInvitation::where('token', $token)->first();

        if (!$invitation) {
            return Inertia::render('Accountant/InvalidInvitation', [
                'message' => 'Cette invitation n\'existe pas.',
            ]);
        }

        if ($invitation->status !== AccountantInvitation::STATUS_PENDING) {
            return Inertia::render('Accountant/InvalidInvitation', [
                'message' => 'Cette invitation a déjà été utilisée ou révoquée.',
            ]);
        }

        if ($invitation->isExpired()) {
            return Inertia::render('Accountant/InvalidInvitation', [
                'message' => 'Cette invitation a expiré.',
            ]);
        }

        // Check if accountant already exists
        $existingAccountant = Accountant::where('email', $invitation->email)->first();

        return Inertia::render('Accountant/AcceptInvitation', [
            'invitation' => [
                'token' => $invitation->token,
                'email' => $invitation->email,
                'name' => $invitation->name,
                'user_name' => $invitation->user->businessSettings?->company_name ?? $invitation->user->name,
            ],
            'accountantExists' => (bool) $existingAccountant,
        ]);
    }

    /**
     * Handle invitation acceptance.
     */
    public function acceptInvitation(Request $request, string $token)
    {
        $invitation = AccountantInvitation::where('token', $token)
            ->where('status', AccountantInvitation::STATUS_PENDING)
            ->first();

        if (!$invitation || $invitation->isExpired()) {
            return back()->withErrors(['token' => 'Invitation invalide ou expirée.']);
        }

        // Check if accountant already exists
        $accountant = Accountant::where('email', $invitation->email)->first();

        if ($accountant) {
            // Existing accountant - just verify password
            $request->validate([
                'password' => 'required',
            ]);

            if (!Hash::check($request->password, $accountant->password)) {
                return back()->withErrors(['password' => 'Mot de passe incorrect.']);
            }
        } else {
            // New accountant - create account
            $request->validate([
                'name' => 'required|string|max:255',
                'password' => ['required', 'confirmed', Password::defaults()],
            ]);

            $accountant = Accountant::create([
                'email' => $invitation->email,
                'name' => $request->name,
                'password' => $request->password,
                'email_verified_at' => now(),
            ]);
        }

        // Link accountant to user
        $accountant->clients()->syncWithoutDetaching([
            $invitation->user_id => [
                'status' => 'active',
                'granted_at' => now(),
            ],
        ]);

        // Mark invitation as accepted
        $invitation->markAsAccepted();

        // Log in the accountant
        Auth::guard('accountant')->login($accountant, true);

        return redirect()->route('accountant.dashboard')
            ->with('success', 'Bienvenue ! Vous avez maintenant accès aux exports comptables.');
    }
}

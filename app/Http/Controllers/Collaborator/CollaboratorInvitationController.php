<?php

namespace App\Http\Controllers\Collaborator;

use App\Http\Controllers\Controller;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class CollaboratorInvitationController extends Controller
{
    public function show(string $token)
    {
        $invitation = OrganizationInvitation::where('token', $token)->first();

        if (!$invitation) {
            return Inertia::render('Collaborator/Invitation/Invalid', [
                'message' => 'Cette invitation n\'existe pas.',
            ]);
        }

        if ($invitation->status !== OrganizationInvitation::STATUS_PENDING) {
            return Inertia::render('Collaborator/Invitation/Invalid', [
                'message' => 'Cette invitation a déjà été utilisée ou révoquée.',
            ]);
        }

        if ($invitation->isExpired()) {
            return Inertia::render('Collaborator/Invitation/Invalid', [
                'message' => 'Cette invitation a expiré.',
            ]);
        }

        $existingUser = User::where('email', $invitation->email)->first();

        return Inertia::render('Collaborator/Invitation/Accept', [
            'invitation' => [
                'token' => $invitation->token,
                'email' => $invitation->email,
                'name' => $invitation->name,
                'organization_name' => $invitation->organization->name,
                'owner_name' => $invitation->organization->owner->businessSettings?->company_name
                    ?? $invitation->organization->owner->name,
            ],
            'userExists' => (bool) $existingUser,
        ]);
    }

    public function accept(Request $request, string $token)
    {
        $invitation = OrganizationInvitation::where('token', $token)
            ->where('status', OrganizationInvitation::STATUS_PENDING)
            ->first();

        if (!$invitation || $invitation->isExpired()) {
            return back()->withErrors(['token' => 'Invitation invalide ou expirée.']);
        }

        $user = User::where('email', $invitation->email)->first();

        if ($user) {
            $request->validate([
                'password' => 'required',
            ]);

            if (!Hash::check($request->password, $user->password)) {
                return back()->withErrors(['password' => 'Mot de passe incorrect.']);
            }
        } else {
            $request->validate([
                'name' => 'required|string|max:255',
                'password' => ['required', 'confirmed', Password::defaults()],
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $invitation->email,
                'password' => Hash::make($request->password),
            ]);
            // is_active retire du fillable, email_verified_at jamais dans fillable
            $user->forceFill([
                'is_active' => true,
                'email_verified_at' => now(),
            ])->save();
        }

        // Add as organization member
        OrganizationMember::updateOrCreate(
            [
                'organization_id' => $invitation->organization_id,
                'user_id' => $user->id,
            ],
            [
                'role' => OrganizationMember::ROLE_COLLABORATOR,
                'invited_at' => $invitation->created_at,
                'joined_at' => now(),
            ]
        );

        $invitation->markAsAccepted();

        Auth::login($user, true);

        return redirect()->route('collaborator.dashboard')
            ->with('success', 'Bienvenue dans l\'organisation ' . $invitation->organization->name . ' !');
    }
}

<?php

namespace App\Http\Controllers\Collaborator;

use App\Http\Controllers\Controller;
use App\Models\OrganizationMember;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class ProjectInvitationController extends Controller
{
    /**
     * Show the project invitation accept page.
     */
    public function show(string $token)
    {
        $invitation = DB::table('project_members')
            ->where('invitation_token', $token)
            ->first();

        // Token cleared = either invalid OR already-accepted by the original invitee.
        // If authenticated and member of the project: send them to the project.
        if (!$invitation) {
            $authUser = Auth::user();
            if ($authUser) {
                $accepted = DB::table('project_members')
                    ->where('user_id', $authUser->id)
                    ->where('member_type', 'collaborator')
                    ->whereNotNull('accepted_at')
                    ->first();
                if ($accepted) {
                    return redirect()->route('collaborator.projects.show', $accepted->project_id);
                }
            }
            return Inertia::render('Collaborator/Invitation/Invalid', [
                'message' => __('app.project_invitation_invalid'),
                'loginUrl' => route('login'),
            ]);
        }

        if ($invitation->invitation_expires_at && now()->gt($invitation->invitation_expires_at)) {
            return Inertia::render('Collaborator/Invitation/Invalid', [
                'message' => __('app.project_invitation_expired'),
            ]);
        }

        $user = User::find($invitation->user_id);
        $project = Project::withoutGlobalScopes()->find($invitation->project_id);

        if (!$user || !$project) {
            return Inertia::render('Collaborator/Invitation/Invalid', [
                'message' => __('app.project_invitation_invalid'),
            ]);
        }

        // Detect "new user" by checking if they have a usable password (the placeholder is bcrypt
        // of Str::random(40), so unusable for login). Heuristic: a user with no email_verified_at
        // and no organizationMembership is fresh from invitation.
        $isNew = !$user->email_verified_at && !$user->organizationMembership()->exists();

        return Inertia::render('Collaborator/Invitation/AcceptProject', [
            'token' => $token,
            'email' => $user->email,
            'isNewUser' => $isNew,
            'project' => [
                'id' => $project->id,
                'title' => $project->title,
            ],
            'orgOwnerName' => $project->user?->businessSettings?->company_name
                ?? $project->user?->name,
        ]);
    }

    /**
     * Accept the invitation.
     */
    public function accept(Request $request, string $token)
    {
        $invitation = DB::table('project_members')
            ->where('invitation_token', $token)
            ->first();

        if (!$invitation || $invitation->accepted_at !== null) {
            return back()->withErrors(['token' => __('app.project_invitation_invalid')]);
        }

        if ($invitation->invitation_expires_at && now()->gt($invitation->invitation_expires_at)) {
            return back()->withErrors(['token' => __('app.project_invitation_expired')]);
        }

        $user = User::findOrFail($invitation->user_id);
        $project = Project::withoutGlobalScopes()->findOrFail($invitation->project_id);

        $isNew = !$user->email_verified_at && !$user->organizationMembership()->exists();

        if ($isNew) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'password' => ['required', 'confirmed', Password::defaults()],
            ]);

            $user->forceFill([
                'name' => $validated['name'],
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(),
            ])->save();
        } else {
            $request->validate([
                'password' => 'required',
            ]);

            if (!Hash::check($request->password, $user->password)) {
                return back()->withErrors(['password' => __('app.collaborator_flash.error_password_incorrect')]);
            }
        }

        DB::transaction(function () use ($invitation, $project, $user) {
            // Mark project invitation accepted + clear token
            DB::table('project_members')
                ->where('project_id', $invitation->project_id)
                ->where('user_id', $invitation->user_id)
                ->update([
                    'accepted_at' => now(),
                    'invitation_token' => null,
                    'invitation_expires_at' => null,
                    'updated_at' => now(),
                ]);

            // Ensure the user is an OrganizationMember with collaborator role
            // (so isCollaborator() returns true and login redirects to /collaborateur).
            $orgOwnerId = $project->user_id;
            $organization = \App\Models\Organization::where('user_id', $orgOwnerId)->first();

            if ($organization) {
                OrganizationMember::updateOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'role' => OrganizationMember::ROLE_COLLABORATOR,
                        'invited_at' => $invitation->invited_at,
                        'joined_at' => now(),
                    ]
                );
            }
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('collaborator.projects.show', $project->id)
            ->with('success', __('app.project_invitation_accepted'));
    }
}

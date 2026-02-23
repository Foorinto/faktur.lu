<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationMember;
use App\Models\Project;
use App\Notifications\OrganizationInvitationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $organization = $user->ownedOrganization;

        $members = collect();
        $pendingInvitations = collect();
        $projects = collect();

        if ($organization) {
            $members = $organization->collaborators()
                ->with('user')
                ->get()
                ->map(fn ($m) => [
                    'id' => $m->id,
                    'name' => $m->user->name,
                    'email' => $m->user->email,
                    'joined_at' => $m->joined_at?->format('d/m/Y'),
                ]);

            $pendingInvitations = $organization->pendingInvitations()
                ->get()
                ->map(fn ($i) => [
                    'id' => $i->id,
                    'email' => $i->email,
                    'name' => $i->name,
                    'created_at' => $i->created_at->format('d/m/Y'),
                    'expires_at' => $i->expires_at->format('d/m/Y'),
                ]);

            $projects = Project::where('user_id', $user->id)
                ->active()
                ->orderBy('title')
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'title' => $p->title,
                    'hidden_from_collaborators' => $p->hidden_from_collaborators,
                ]);
        }

        return Inertia::render('Settings/Organization', [
            'organization' => $organization ? [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'collaborators_count' => $organization->collaborators()->count(),
                'max_collaborators' => Organization::MAX_COLLABORATORS,
            ] : null,
            'members' => $members,
            'pendingInvitations' => $pendingInvitations,
            'projects' => $projects,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = $request->user();

        if ($user->isOrganizationOwner()) {
            return back()->withErrors(['name' => __('app.organization_already_exists')]);
        }

        $organization = Organization::create([
            'user_id' => $user->id,
            'name' => $request->name,
        ]);

        // Add owner as admin member
        OrganizationMember::create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => OrganizationMember::ROLE_ADMIN,
            'joined_at' => now(),
        ]);

        return back()->with('success', __('app.organization_created'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $organization = $request->user()->ownedOrganization;
        $organization->update(['name' => $request->name]);

        return back()->with('success', __('app.organization_updated'));
    }

    public function invite(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'name' => 'nullable|string|max:255',
        ]);

        $organization = $request->user()->ownedOrganization;

        if ($organization->isFull()) {
            return back()->withErrors(['email' => __('app.max_collaborators_reached')]);
        }

        // Check for existing pending invitation
        $existing = $organization->invitations()
            ->where('email', strtolower($request->email))
            ->where('status', OrganizationInvitation::STATUS_PENDING)
            ->first();

        if ($existing) {
            return back()->withErrors(['email' => __('app.invitation_already_pending')]);
        }

        // Check if already a member
        $existingMember = $organization->activeMembers()
            ->whereHas('user', fn ($q) => $q->where('email', strtolower($request->email)))
            ->first();

        if ($existingMember) {
            return back()->withErrors(['email' => __('app.already_a_member')]);
        }

        $invitation = OrganizationInvitation::createForOrganization(
            $organization,
            $request->email,
            $request->name
        );

        Notification::route('mail', $invitation->email)
            ->notify(new OrganizationInvitationNotification($invitation));

        return back()->with('success', __('app.invitation_sent'));
    }

    public function resendInvitation(Request $request, OrganizationInvitation $invitation)
    {
        $organization = $request->user()->ownedOrganization;

        if ($invitation->organization_id !== $organization->id) {
            abort(403);
        }

        if (!$invitation->isValid()) {
            $invitation->update(['expires_at' => now()->addDays(7)]);
        }

        Notification::route('mail', $invitation->email)
            ->notify(new OrganizationInvitationNotification($invitation));

        return back()->with('success', __('app.invitation_resent'));
    }

    public function cancelInvitation(Request $request, OrganizationInvitation $invitation)
    {
        $organization = $request->user()->ownedOrganization;

        if ($invitation->organization_id !== $organization->id) {
            abort(403);
        }

        $invitation->revoke();

        return back()->with('success', __('app.invitation_cancelled'));
    }

    public function removeMember(Request $request, OrganizationMember $member)
    {
        $organization = $request->user()->ownedOrganization;

        if ($member->organization_id !== $organization->id || $member->isAdmin()) {
            abort(403);
        }

        $member->delete();

        return back()->with('success', __('app.member_removed'));
    }

    public function toggleProjectVisibility(Request $request, Project $project)
    {
        $user = $request->user();

        if ($project->user_id !== $user->id) {
            abort(403);
        }

        $project->update([
            'hidden_from_collaborators' => !$project->hidden_from_collaborators,
        ]);

        return back()->with('success', __('app.project_visibility_updated'));
    }
}

<?php

namespace App\Http\Controllers\Collaborator;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CollaboratorUpgradeController extends Controller
{
    /**
     * Show the upgrade confirmation page.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        // Count what they'll lose: projects they're a member of + orgs they collaborate on
        $projectsCount = DB::table('project_members')
            ->where('user_id', $user->id)
            ->where('member_type', 'collaborator')
            ->whereNotNull('accepted_at')
            ->count();

        $orgsCount = OrganizationMember::where('user_id', $user->id)
            ->where('role', OrganizationMember::ROLE_COLLABORATOR)
            ->count();

        return Inertia::render('Collaborator/Upgrade', [
            'projectsCount' => $projectsCount,
            'orgsCount' => $orgsCount,
        ]);
    }

    /**
     * Detach all collaborator memberships + create own organization + redirect to onboarding.
     * Time entries and other historical data stay in the database — only the access link is removed.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->isOrganizationOwner()) {
            return redirect()->route('dashboard');
        }

        DB::transaction(function () use ($user) {
            // Detach from all project memberships (collaborator role)
            DB::table('project_members')
                ->where('user_id', $user->id)
                ->where('member_type', 'collaborator')
                ->delete();

            // Detach from organizations as collaborator
            OrganizationMember::where('user_id', $user->id)
                ->where('role', OrganizationMember::ROLE_COLLABORATOR)
                ->delete();

            // Create their own org + admin membership
            $organization = Organization::create([
                'user_id' => $user->id,
                'name' => $user->name . ' — ' . __('app.upgrade_default_org_suffix'),
            ]);

            OrganizationMember::create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'role' => OrganizationMember::ROLE_ADMIN,
                'joined_at' => now(),
            ]);
        });

        // Bypass the collaborator-only login redirect by re-authenticating cleanly.
        $request->session()->regenerate();

        return redirect()->route('onboarding')
            ->with('success', __('app.upgrade_success'));
    }
}

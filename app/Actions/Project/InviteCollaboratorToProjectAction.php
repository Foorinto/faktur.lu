<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Models\User;
use App\Services\PlanService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InviteCollaboratorToProjectAction
{
    public function __construct(protected PlanService $planService)
    {
    }

    /**
     * Invite a collaborator (by email) to a specific project.
     *
     * - Enforces the per-project quota (plan.max_collaborators_per_project)
     * - Creates a User account if the email isn't known yet (placeholder, will set password via reset link)
     * - Links the user to the project via project_members with member_type='collaborator'
     * - Sends invitation email (ProjectCollaboratorInvitation)
     *
     * @throws ValidationException if quota reached or already invited
     */
    public function execute(Project $project, User $orgOwner, string $email): array
    {
        $email = strtolower(trim($email));

        // Quota check
        if (! $this->planService->canInviteCollaboratorToProject($orgOwner, $project)) {
            $quota = $this->planService->collaboratorQuotaForProject($orgOwner, $project);
            throw ValidationException::withMessages([
                'email' => [__('app.project_collaborator_quota_reached', [
                    'used' => $quota['used'],
                    'max' => $quota['max'],
                    'plan' => $quota['plan'],
                ])],
            ]);
        }

        return DB::transaction(function () use ($project, $orgOwner, $email) {
            // Find or create user for this email
            $user = User::where('email', $email)->first();
            $isNewUser = false;

            if (! $user) {
                $user = User::create([
                    'name' => Str::before($email, '@'),
                    'email' => $email,
                    'password' => bcrypt(Str::random(40)), // placeholder — user will reset
                ]);
                $isNewUser = true;
            }

            // Already invited on this project?
            $existing = DB::table('project_members')
                ->where('project_id', $project->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'email' => [__('app.project_collaborator_already_invited')],
                ]);
            }

            // Insert pivot
            DB::table('project_members')->insert([
                'project_id' => $project->id,
                'user_id' => $user->id,
                'member_type' => 'collaborator',
                'invited_email' => $email,
                'invited_at' => now(),
                'accepted_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Send invitation email
            try {
                Mail::to($email)->queue(new \App\Mail\ProjectCollaboratorInvitation(
                    $project,
                    $user,
                    $isNewUser
                ));
            } catch (\Throwable $e) {
                Log::warning('ProjectCollaboratorInvitation mail failed', [
                    'project_id' => $project->id,
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }

            return [
                'user' => $user,
                'is_new_user' => $isNewUser,
            ];
        });
    }
}

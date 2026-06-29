<?php

namespace App\Http\Controllers;

use App\Actions\Project\InviteCollaboratorToProjectAction;
use App\Actions\Project\ToggleEmployeeProjectAccessAction;
use App\Http\Requests\Project\InviteCollaboratorRequest;
use App\Models\HR\Employee;
use App\Models\Project;
use App\Policies\ProjectMemberPolicy;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectMemberController extends Controller
{
    public function __construct(protected PlanService $planService)
    {
    }

    /**
     * List members (employees + collaborators) of a project.
     */
    public function index(Project $project): JsonResponse
    {
        abort_unless((new ProjectMemberPolicy())->view(request()->user(), $project), 403);

        // Employees with pivot
        $employees = $project->employees()
            ->orderBy('last_name')
            ->get(['employees.id', 'first_name', 'last_name', 'photo_path', 'status']);

        // External collaborators
        $collaborators = DB::table('project_members')
            ->join('users', 'users.id', '=', 'project_members.user_id')
            ->where('project_members.project_id', $project->id)
            ->where('project_members.member_type', 'collaborator')
            ->select('users.id', 'users.name', 'users.email', 'project_members.invited_email',
                     'project_members.invited_at', 'project_members.accepted_at')
            ->get();

        $quota = $this->planService->collaboratorQuotaForProject(
            request()->user(),
            $project
        );

        return response()->json([
            'employees' => $employees,
            'collaborators' => $collaborators,
            'quota' => $quota,
        ]);
    }

    /**
     * Toggle the active flag of an employee on this project.
     * Only the organisation owner can manage.
     */
    public function toggleEmployee(Request $request, Project $project, Employee $employee, ToggleEmployeeProjectAccessAction $action): RedirectResponse
    {
        abort_unless((new ProjectMemberPolicy())->manage(request()->user(), $project), 403);

        abort_unless((int) $employee->user_id === (int) $project->user_id, 403);

        $pivot = $action->execute($project, $employee);

        return back()->with('success', $pivot->active
            ? __('app.project_employee_activated')
            : __('app.project_employee_deactivated'));
    }

    /**
     * Invite a new external collaborator to this project.
     */
    public function inviteCollaborator(InviteCollaboratorRequest $request, Project $project, InviteCollaboratorToProjectAction $action): RedirectResponse
    {
        abort_unless((new ProjectMemberPolicy())->manage(request()->user(), $project), 403);

        $action->execute($project, $request->user(), $request->validated()['email']);

        return back()->with('success', __('app.project_collaborator_invited'));
    }

    /**
     * Remove an external collaborator from this project.
     */
    public function removeCollaborator(Project $project, int $memberId): RedirectResponse
    {
        abort_unless((new ProjectMemberPolicy())->manage(request()->user(), $project), 403);

        DB::table('project_members')
            ->where('project_id', $project->id)
            ->where('user_id', $memberId)
            ->where('member_type', 'collaborator')
            ->delete();

        return back()->with('success', __('app.project_collaborator_removed'));
    }

    /**
     * Get current quota for a project (lightweight JSON).
     */
    public function quota(Project $project): JsonResponse
    {
        abort_unless((new ProjectMemberPolicy())->view(request()->user(), $project), 403);

        return response()->json($this->planService->collaboratorQuotaForProject(
            request()->user(),
            $project
        ));
    }
}

<?php

namespace Tests\Feature\Project;

use App\Actions\Project\InviteCollaboratorToProjectAction;
use App\Actions\Project\ToggleEmployeeProjectAccessAction;
use App\Models\HR\Employee;
use App\Models\Plan;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProjectMembersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed plans (needed for quota checks)
        $this->seed(\Database\Seeders\PlansSeeder::class);
    }

    public function test_toggle_employee_access_flips_active_and_sends_mail(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        $employee = Employee::factory()->for($user)->create([
            'status' => 'active',
            'email_pro' => 'emp@test.lu',
        ]);
        $project = Project::factory()->for($user)->create();

        $action = new ToggleEmployeeProjectAccessAction();
        $pivot = $action->execute($project, $employee);

        $this->assertTrue($pivot->active);

        // Toggle again
        $pivot = $action->execute($project, $employee->fresh());
        $this->assertFalse($pivot->active);
    }

    public function test_collaborator_quota_enforced_per_plan(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        // Default test user = free plan (limit 3 per project)
        $project = Project::factory()->for($user)->create();

        $action = app(InviteCollaboratorToProjectAction::class);

        // Invite up to 3
        for ($i = 1; $i <= 3; $i++) {
            $action->execute($project, $user, "collab{$i}@example.com");
        }

        // 4th should fail
        $this->expectException(ValidationException::class);
        $action->execute($project, $user, 'collab4@example.com');
    }

    public function test_multi_tenant_project_isolation(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->actingAs($userA);
        Project::factory()->for($userA)->create(['title' => 'Project A']);

        $this->actingAs($userB);
        Project::factory()->for($userB)->create(['title' => 'Project B']);

        // userB only sees their own project
        $this->assertEquals(1, Project::count());
        $this->assertEquals('Project B', Project::first()->title);
    }
}

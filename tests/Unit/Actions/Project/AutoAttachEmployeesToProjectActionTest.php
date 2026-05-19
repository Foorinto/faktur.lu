<?php

namespace Tests\Unit\Actions\Project;

use App\Actions\Project\AutoAttachEmployeesToProjectAction;
use App\Models\HR\Employee;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AutoAttachEmployeesToProjectActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_attaches_all_active_employees_to_project(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        Employee::factory()->for($user)->create(['status' => 'active']);
        Employee::factory()->for($user)->create(['status' => 'active']);
        Employee::factory()->for($user)->create(['status' => 'terminated']); // should be skipped

        $project = Project::factory()->for($user)->create();

        // Clean any auto-attach side effect from project creation (if observer existed)
        $project->employees()->detach();

        $action = new AutoAttachEmployeesToProjectAction();
        $count = $action->execute($project);

        $this->assertEquals(2, $count);
        $this->assertEquals(2, $project->employees()->count());
        $this->assertEquals(2, $project->activeEmployees()->count());
    }
}

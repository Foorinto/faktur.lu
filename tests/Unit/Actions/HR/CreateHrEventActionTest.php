<?php

namespace Tests\Unit\Actions\HR;

use App\Actions\HR\CreateHrEventAction;
use App\Models\HR\Employee;
use App\Models\HR\HrEvent;
use App\Models\HR\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateHrEventActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_event_without_room(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        $employee = Employee::factory()->for($user)->create();
        $action = new CreateHrEventAction();

        $event = $action->execute([
            'title' => 'Test event',
            'type' => 'meeting',
            'starts_at' => '2026-06-01 10:00:00',
            'ends_at' => '2026-06-01 11:00:00',
            'location_type' => 'none',
        ], $user, $employee);

        $this->assertInstanceOf(HrEvent::class, $event);
        $this->assertEquals('Test event', $event->title);
        $this->assertEquals($employee->id, $event->created_by_employee_id);
    }

    public function test_throws_validation_exception_on_room_conflict(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        $employee = Employee::factory()->for($user)->create();
        $room = Room::factory()->for($user)->create();

        // Existing event
        HrEvent::factory()->for($user)->inRoom($room->id)->create([
            'starts_at' => '2026-06-01 10:00:00',
            'ends_at' => '2026-06-01 11:00:00',
        ]);

        $action = new CreateHrEventAction();

        $this->expectException(ValidationException::class);

        $action->execute([
            'title' => 'Conflict event',
            'type' => 'meeting',
            'starts_at' => '2026-06-01 10:30:00',
            'ends_at' => '2026-06-01 11:30:00',
            'location_type' => 'room',
            'room_id' => $room->id,
        ], $user, $employee);
    }
}

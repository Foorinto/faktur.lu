<?php

namespace Tests\Feature\HR;

use App\Models\HR\Employee;
use App\Models\HR\HrEvent;
use App\Models\HR\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SharedCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_room_conflict_blocks_event_creation_via_action(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        $employee = Employee::factory()->for($user)->create();
        $room = Room::factory()->for($user)->create();

        HrEvent::factory()->for($user)->inRoom($room->id)->create([
            'starts_at' => '2026-06-01 14:00:00',
            'ends_at' => '2026-06-01 15:00:00',
        ]);

        $action = new \App\Actions\HR\CreateHrEventAction();

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $action->execute([
            'title' => 'Blocked event',
            'type' => 'meeting',
            'starts_at' => '2026-06-01 14:30:00',
            'ends_at' => '2026-06-01 15:30:00',
            'location_type' => 'room',
            'room_id' => $room->id,
        ], $user, $employee);
    }

    public function test_multi_tenant_room_isolation(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->actingAs($userA);
        Room::factory()->for($userA)->create(['name' => 'Salle A']);

        $this->actingAs($userB);
        Room::factory()->for($userB)->create(['name' => 'Salle B']);

        // userB sees only its own room
        $this->assertEquals(1, Room::count());
        $this->assertEquals('Salle B', Room::first()->name);
    }

    public function test_multi_tenant_event_isolation(): void
    {
        Mail::fake();

        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->actingAs($userA);
        HrEvent::factory()->for($userA)->create(['title' => 'Event A']);

        $this->actingAs($userB);
        HrEvent::factory()->for($userB)->create(['title' => 'Event B']);

        // userB sees only its own events
        $this->assertEquals(1, HrEvent::count());
        $this->assertEquals('Event B', HrEvent::first()->title);
    }
}

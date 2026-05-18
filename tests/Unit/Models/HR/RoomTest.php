<?php

namespace Tests\Unit\Models\HR;

use App\Models\HR\HrEvent;
use App\Models\HR\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomTest extends TestCase
{
    use RefreshDatabase;

    public function test_room_casts_equipment_as_array(): void
    {
        $user = User::factory()->create();

        $room = Room::create([
            'user_id' => $user->id,
            'name' => 'Test Room',
            'equipment' => ['projector', 'whiteboard'],
        ]);

        $this->assertIsArray($room->fresh()->equipment);
        $this->assertEquals(['projector', 'whiteboard'], $room->fresh()->equipment);
    }

    public function test_has_conflict_detects_overlapping_event(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $room = Room::factory()->for($user)->create();

        HrEvent::factory()->for($user)->inRoom($room->id)->create([
            'starts_at' => '2026-06-01 10:00:00',
            'ends_at' => '2026-06-01 11:00:00',
        ]);

        // Conflict: overlapping start
        $this->assertTrue($room->hasConflict(
            new \DateTime('2026-06-01 10:30:00'),
            new \DateTime('2026-06-01 11:30:00')
        ));

        // Conflict: same time
        $this->assertTrue($room->hasConflict(
            new \DateTime('2026-06-01 10:00:00'),
            new \DateTime('2026-06-01 11:00:00')
        ));

        // No conflict: before
        $this->assertFalse($room->hasConflict(
            new \DateTime('2026-06-01 08:00:00'),
            new \DateTime('2026-06-01 09:00:00')
        ));

        // No conflict: after
        $this->assertFalse($room->hasConflict(
            new \DateTime('2026-06-01 12:00:00'),
            new \DateTime('2026-06-01 13:00:00')
        ));
    }

    public function test_has_conflict_excludes_specified_event_id(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $room = Room::factory()->for($user)->create();

        $event = HrEvent::factory()->for($user)->inRoom($room->id)->create([
            'starts_at' => '2026-06-01 10:00:00',
            'ends_at' => '2026-06-01 11:00:00',
        ]);

        // Without exclude: conflict
        $this->assertTrue($room->hasConflict(
            new \DateTime('2026-06-01 10:00:00'),
            new \DateTime('2026-06-01 11:00:00')
        ));

        // With exclude: no conflict (same event)
        $this->assertFalse($room->hasConflict(
            new \DateTime('2026-06-01 10:00:00'),
            new \DateTime('2026-06-01 11:00:00'),
            $event->id
        ));
    }
}

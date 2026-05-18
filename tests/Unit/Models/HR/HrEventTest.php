<?php

namespace Tests\Unit\Models\HR;

use App\Models\HR\HrEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_casts_dates_correctly(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $event = HrEvent::factory()->for($user)->create([
            'starts_at' => '2026-06-01 10:00:00',
            'ends_at' => '2026-06-01 11:00:00',
            'is_all_day' => true,
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $event->fresh()->starts_at);
        $this->assertTrue($event->fresh()->is_all_day);
    }

    public function test_effective_color_returns_type_color_when_custom_color_is_null(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $event = HrEvent::factory()->for($user)->meeting()->create(['color' => null]);

        $this->assertEquals(HrEvent::TYPE_COLORS['meeting'], $event->effective_color);
    }

    public function test_effective_color_returns_custom_color_when_provided(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $event = HrEvent::factory()->for($user)->create(['color' => '#ff0000']);

        $this->assertEquals('#ff0000', $event->effective_color);
    }

    public function test_belongs_to_user_scope_isolates_tenants(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->actingAs($userA);
        HrEvent::factory()->for($userA)->create(['title' => 'Event A']);

        $this->actingAs($userB);
        HrEvent::factory()->for($userB)->create(['title' => 'Event B']);

        // As userB, should only see Event B
        $this->assertEquals(1, HrEvent::count());
        $this->assertEquals('Event B', HrEvent::first()->title);
    }
}

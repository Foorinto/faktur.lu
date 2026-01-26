<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeEntryTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->client = Client::factory()->create();
    }

    public function test_guest_cannot_access_time_entries(): void
    {
        $this->get(route('time-entries.index'))->assertRedirect(route('login'));
    }

    public function test_user_can_view_time_entries_index(): void
    {
        TimeEntry::factory()->count(5)->forClient($this->client)->create();

        $this->actingAs($this->user)
            ->get(route('time-entries.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('TimeTracking/Index')
                ->has('entries.data', 5)
                ->has('summary')
                ->has('clients')
                ->has('periods')
            );
    }

    public function test_user_can_filter_by_client(): void
    {
        $client2 = Client::factory()->create();
        TimeEntry::factory()->count(3)->forClient($this->client)->create();
        TimeEntry::factory()->count(2)->forClient($client2)->create();

        $this->actingAs($this->user)
            ->get(route('time-entries.index', ['client_id' => $this->client->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('entries.data', 3)
            );
    }

    public function test_user_can_filter_by_billed_status(): void
    {
        TimeEntry::factory()->count(3)->forClient($this->client)->create(['is_billed' => false]);
        TimeEntry::factory()->count(2)->forClient($this->client)->billed()->create();

        $this->actingAs($this->user)
            ->get(route('time-entries.index', ['billed' => '0']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('entries.data', 3)
            );
    }

    public function test_user_can_create_manual_time_entry(): void
    {
        $this->actingAs($this->user)
            ->post(route('time-entries.store'), [
                'client_id' => $this->client->id,
                'project_name' => 'Test Project',
                'description' => 'Test description',
                'date' => now()->format('Y-m-d'),
                'duration' => '2:30',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('time_entries', [
            'client_id' => $this->client->id,
            'project_name' => 'Test Project',
            'duration_seconds' => 9000, // 2h30 = 9000 seconds
        ]);
    }

    public function test_user_can_start_timer(): void
    {
        $this->actingAs($this->user)
            ->post(route('time-entries.start'), [
                'client_id' => $this->client->id,
                'project_name' => 'Working on feature',
                'description' => 'Development work',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('time_entries', [
            'client_id' => $this->client->id,
            'project_name' => 'Working on feature',
            'stopped_at' => null,
        ]);
    }

    public function test_starting_timer_stops_any_running_timer(): void
    {
        $runningEntry = TimeEntry::factory()->running()->forClient($this->client)->create();

        $this->actingAs($this->user)
            ->post(route('time-entries.start'), [
                'client_id' => $this->client->id,
            ]);

        $runningEntry->refresh();
        $this->assertNotNull($runningEntry->stopped_at);
    }

    public function test_user_can_stop_timer(): void
    {
        $entry = TimeEntry::factory()->running()->forClient($this->client)->create();

        $this->actingAs($this->user)
            ->post(route('time-entries.stop', $entry))
            ->assertRedirect();

        $entry->refresh();
        $this->assertNotNull($entry->stopped_at);
        $this->assertGreaterThan(0, $entry->duration_seconds);
    }

    public function test_cannot_stop_non_running_timer(): void
    {
        $entry = TimeEntry::factory()->forClient($this->client)->create();

        $this->actingAs($this->user)
            ->post(route('time-entries.stop', $entry))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_user_can_update_time_entry(): void
    {
        $entry = TimeEntry::factory()->forClient($this->client)->create();

        $this->actingAs($this->user)
            ->put(route('time-entries.update', $entry), [
                'project_name' => 'Updated Project',
                'description' => 'Updated description',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('time_entries', [
            'id' => $entry->id,
            'project_name' => 'Updated Project',
        ]);
    }

    public function test_cannot_update_billed_time_entry(): void
    {
        $entry = TimeEntry::factory()->forClient($this->client)->billed()->create();

        $this->actingAs($this->user)
            ->put(route('time-entries.update', $entry), [
                'project_name' => 'Updated Project',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_user_can_delete_time_entry(): void
    {
        $entry = TimeEntry::factory()->forClient($this->client)->create();

        $this->actingAs($this->user)
            ->delete(route('time-entries.destroy', $entry))
            ->assertRedirect();

        $this->assertSoftDeleted($entry);
    }

    public function test_cannot_delete_billed_time_entry(): void
    {
        $entry = TimeEntry::factory()->forClient($this->client)->billed()->create();

        $this->actingAs($this->user)
            ->delete(route('time-entries.destroy', $entry))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_user_can_view_summary(): void
    {
        TimeEntry::factory()->count(5)->forClient($this->client)->create();

        $this->actingAs($this->user)
            ->get(route('time-entries.summary'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('TimeTracking/Summary')
                ->has('summary')
                ->has('byClient')
                ->has('clients')
            );
    }

    public function test_user_can_convert_time_to_invoice(): void
    {
        $entries = TimeEntry::factory()
            ->count(3)
            ->forClient($this->client)
            ->create(['is_billed' => false]);

        $this->actingAs($this->user)
            ->post(route('time-entries.to-invoice'), [
                'time_entry_ids' => $entries->pluck('id')->toArray(),
                'hourly_rate' => 100,
                'vat_rate' => 17,
                'group_by_project' => true,
            ])
            ->assertRedirect();

        // Check invoice was created
        $this->assertDatabaseHas('invoices', [
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        // Check entries are marked as billed
        foreach ($entries as $entry) {
            $entry->refresh();
            $this->assertTrue($entry->is_billed);
            $this->assertNotNull($entry->invoice_id);
        }
    }

    public function test_cannot_convert_entries_from_different_clients(): void
    {
        $client2 = Client::factory()->create();
        $entry1 = TimeEntry::factory()->forClient($this->client)->create(['is_billed' => false]);
        $entry2 = TimeEntry::factory()->forClient($client2)->create(['is_billed' => false]);

        $this->actingAs($this->user)
            ->post(route('time-entries.to-invoice'), [
                'time_entry_ids' => [$entry1->id, $entry2->id],
                'hourly_rate' => 100,
                'vat_rate' => 17,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_validation_requires_client_for_timer(): void
    {
        $this->actingAs($this->user)
            ->post(route('time-entries.start'), [
                'project_name' => 'Test',
            ])
            ->assertSessionHasErrors('client_id');
    }

    public function test_validation_requires_duration_format(): void
    {
        $this->actingAs($this->user)
            ->post(route('time-entries.store'), [
                'client_id' => $this->client->id,
                'duration' => 'invalid',
            ])
            ->assertSessionHasErrors('duration');
    }

    public function test_running_timer_page(): void
    {
        TimeEntry::factory()->running()->forClient($this->client)->create();

        $this->actingAs($this->user)
            ->get(route('time-entries.running'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('TimeTracking/Running')
                ->has('timer')
            );
    }
}

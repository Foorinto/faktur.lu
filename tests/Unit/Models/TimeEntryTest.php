<?php

namespace Tests\Unit\Models;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\TimeEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_time_entry_belongs_to_client(): void
    {
        $client = Client::factory()->create();
        $entry = TimeEntry::factory()->forClient($client)->create();

        $this->assertInstanceOf(Client::class, $entry->client);
        $this->assertEquals($client->id, $entry->client->id);
    }

    public function test_time_entry_belongs_to_invoice(): void
    {
        $invoice = Invoice::factory()->create();
        $entry = TimeEntry::factory()->create(['invoice_id' => $invoice->id]);

        $this->assertInstanceOf(Invoice::class, $entry->invoice);
        $this->assertEquals($invoice->id, $entry->invoice->id);
    }

    public function test_is_running_returns_true_when_no_stopped_at(): void
    {
        $entry = TimeEntry::factory()->running()->create();

        $this->assertTrue($entry->isRunning());
    }

    public function test_is_running_returns_false_when_stopped(): void
    {
        $entry = TimeEntry::factory()->create();

        $this->assertFalse($entry->isRunning());
    }

    public function test_duration_formatted_attribute(): void
    {
        $startedAt = now()->subHours(2);
        $stoppedAt = $startedAt->copy()->addSeconds(5400);

        $entry = TimeEntry::factory()->create([
            'started_at' => $startedAt,
            'stopped_at' => $stoppedAt,
            'duration_seconds' => 5400, // 1 hour 30 minutes
        ]);

        $this->assertEquals('1:30', $entry->duration_formatted);
    }

    public function test_duration_hours_attribute(): void
    {
        $startedAt = now()->subHours(3);
        $stoppedAt = $startedAt->copy()->addSeconds(5400);

        $entry = TimeEntry::factory()->create([
            'started_at' => $startedAt,
            'stopped_at' => $stoppedAt,
            'duration_seconds' => 5400, // 1.5 hours
        ]);

        $this->assertEquals(1.5, $entry->duration_hours);
    }

    public function test_amount_attribute_calculation(): void
    {
        $startedAt = now()->subHours(2);
        $stoppedAt = $startedAt->copy()->addSeconds(3600);

        $entry = TimeEntry::factory()->create([
            'started_at' => $startedAt,
            'stopped_at' => $stoppedAt,
            'duration_seconds' => 3600, // 1 hour
            'hourly_rate' => 100,
        ]);

        $this->assertEquals(100, $entry->amount);
    }

    public function test_amount_returns_zero_when_no_hourly_rate(): void
    {
        $entry = TimeEntry::factory()->create([
            'duration_seconds' => 3600,
            'hourly_rate' => null,
        ]);

        $this->assertEquals(0, $entry->amount);
    }

    public function test_start_method(): void
    {
        $entry = TimeEntry::factory()->create([
            'started_at' => null,
            'stopped_at' => now()->subHour(),
            'duration_seconds' => 3600,
        ]);

        $entry->start();

        $this->assertNotNull($entry->started_at);
        $this->assertNull($entry->stopped_at);
        $this->assertEquals(0, $entry->duration_seconds);
    }

    public function test_stop_method(): void
    {
        $entry = TimeEntry::factory()->running()->create();

        $entry->stop();

        $this->assertNotNull($entry->stopped_at);
        $this->assertGreaterThan(0, $entry->duration_seconds);
    }

    public function test_stop_does_nothing_if_not_running(): void
    {
        $entry = TimeEntry::factory()->create([
            'stopped_at' => now()->subHour(),
            'duration_seconds' => 3600,
        ]);

        $originalStoppedAt = $entry->stopped_at;
        $entry->stop();

        $this->assertEquals($originalStoppedAt->timestamp, $entry->stopped_at->timestamp);
    }

    public function test_mark_as_billed(): void
    {
        $invoice = Invoice::factory()->create();
        $entry = TimeEntry::factory()->create(['is_billed' => false]);

        $entry->markAsBilled($invoice);

        $this->assertTrue($entry->is_billed);
        $this->assertEquals($invoice->id, $entry->invoice_id);
    }

    public function test_scope_unbilled(): void
    {
        TimeEntry::factory()->create(['is_billed' => false]);
        TimeEntry::factory()->create(['is_billed' => true]);

        $unbilled = TimeEntry::unbilled()->get();

        $this->assertCount(1, $unbilled);
        $this->assertFalse($unbilled->first()->is_billed);
    }

    public function test_scope_billed(): void
    {
        TimeEntry::factory()->create(['is_billed' => false]);
        TimeEntry::factory()->create(['is_billed' => true]);

        $billed = TimeEntry::billed()->get();

        $this->assertCount(1, $billed);
        $this->assertTrue($billed->first()->is_billed);
    }

    public function test_scope_for_client(): void
    {
        $client1 = Client::factory()->create();
        $client2 = Client::factory()->create();

        TimeEntry::factory()->forClient($client1)->create();
        TimeEntry::factory()->forClient($client2)->create();

        $entries = TimeEntry::forClient($client1->id)->get();

        $this->assertCount(1, $entries);
        $this->assertEquals($client1->id, $entries->first()->client_id);
    }

    public function test_scope_running(): void
    {
        TimeEntry::factory()->running()->create();
        TimeEntry::factory()->create();

        $running = TimeEntry::running()->get();

        $this->assertCount(1, $running);
        $this->assertTrue($running->first()->isRunning());
    }

    public function test_scope_stopped(): void
    {
        TimeEntry::factory()->running()->create();
        TimeEntry::factory()->create();

        $stopped = TimeEntry::stopped()->get();

        $this->assertCount(1, $stopped);
        $this->assertFalse($stopped->first()->isRunning());
    }

    public function test_format_seconds_static_method(): void
    {
        $this->assertEquals('0:00', TimeEntry::formatSeconds(0));
        $this->assertEquals('1:00', TimeEntry::formatSeconds(3600));
        $this->assertEquals('1:30', TimeEntry::formatSeconds(5400));
        $this->assertEquals('10:15', TimeEntry::formatSeconds(36900));
    }

    public function test_parse_to_seconds_with_colon_format(): void
    {
        $this->assertEquals(5400, TimeEntry::parseToSeconds('1:30'));
        $this->assertEquals(3600, TimeEntry::parseToSeconds('1:00'));
        $this->assertEquals(36900, TimeEntry::parseToSeconds('10:15'));
    }

    public function test_parse_to_seconds_with_decimal_format(): void
    {
        $this->assertEquals(5400, TimeEntry::parseToSeconds('1.5'));
        $this->assertEquals(3600, TimeEntry::parseToSeconds('1'));
        $this->assertEquals(1800, TimeEntry::parseToSeconds('0.5'));
    }

    public function test_parse_to_seconds_returns_zero_for_invalid(): void
    {
        $this->assertEquals(0, TimeEntry::parseToSeconds('invalid'));
        $this->assertEquals(0, TimeEntry::parseToSeconds(''));
    }

    public function test_get_summary_static_method(): void
    {
        $startedAt1 = now()->subHours(5);
        $stoppedAt1 = $startedAt1->copy()->addSeconds(3600);

        $startedAt2 = now()->subHours(3);
        $stoppedAt2 = $startedAt2->copy()->addSeconds(7200);

        TimeEntry::factory()->create([
            'started_at' => $startedAt1,
            'stopped_at' => $stoppedAt1,
            'duration_seconds' => 3600,
            'is_billed' => false,
        ]);
        TimeEntry::factory()->create([
            'started_at' => $startedAt2,
            'stopped_at' => $stoppedAt2,
            'duration_seconds' => 7200,
            'is_billed' => true,
        ]);

        $summary = TimeEntry::getSummary();

        $this->assertEquals(10800, $summary['total_seconds']);
        $this->assertEquals(3, $summary['total_hours']);
        $this->assertEquals(3600, $summary['unbilled_seconds']);
        $this->assertEquals(7200, $summary['billed_seconds']);
        $this->assertEquals(2, $summary['count']);
    }

    public function test_duration_calculated_on_save(): void
    {
        $entry = TimeEntry::factory()->create([
            'started_at' => now()->subHours(2),
            'stopped_at' => null,
            'duration_seconds' => 0,
        ]);

        $entry->stopped_at = now();
        $entry->save();

        $this->assertGreaterThan(7000, $entry->duration_seconds); // ~2 hours
    }
}

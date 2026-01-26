<?php

namespace Tests\Unit\Models;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_snapshot_contains_all_required_fields(): void
    {
        $client = Client::factory()->create([
            'name' => 'Test Company',
            'email' => 'test@example.com',
            'address' => '123 Test Street',
            'postal_code' => 'L-1234',
            'city' => 'Luxembourg',
            'country_code' => 'LU',
            'vat_number' => 'LU12345678',
            'type' => 'b2b',
            'phone' => '+352 123 456',
        ]);

        $snapshot = $client->toSnapshot();

        $this->assertEquals('Test Company', $snapshot['name']);
        $this->assertEquals('test@example.com', $snapshot['email']);
        $this->assertEquals('123 Test Street', $snapshot['address']);
        $this->assertEquals('L-1234', $snapshot['postal_code']);
        $this->assertEquals('Luxembourg', $snapshot['city']);
        $this->assertEquals('LU', $snapshot['country_code']);
        $this->assertEquals('LU12345678', $snapshot['vat_number']);
        $this->assertEquals('b2b', $snapshot['type']);
        $this->assertEquals('+352 123 456', $snapshot['phone']);
    }

    public function test_to_snapshot_does_not_contain_currency_or_notes(): void
    {
        $client = Client::factory()->create([
            'currency' => 'EUR',
            'notes' => 'Some internal notes',
        ]);

        $snapshot = $client->toSnapshot();

        $this->assertArrayNotHasKey('currency', $snapshot);
        $this->assertArrayNotHasKey('notes', $snapshot);
    }

    public function test_search_scope_filters_by_name(): void
    {
        Client::factory()->create(['name' => 'ACME Corporation']);
        Client::factory()->create(['name' => 'Tech Solutions']);
        Client::factory()->create(['name' => 'Other Company']);

        $results = Client::search('ACME')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('ACME Corporation', $results->first()->name);
    }

    public function test_search_scope_filters_by_email(): void
    {
        Client::factory()->create(['email' => 'contact@acme.com']);
        Client::factory()->create(['email' => 'info@tech.com']);

        $results = Client::search('acme.com')->get();

        $this->assertCount(1, $results);
    }

    public function test_search_scope_filters_by_vat_number(): void
    {
        Client::factory()->create(['vat_number' => 'LU12345678']);
        Client::factory()->create(['vat_number' => 'LU87654321']);

        $results = Client::search('LU123')->get();

        $this->assertCount(1, $results);
    }

    public function test_search_scope_returns_all_when_empty(): void
    {
        Client::factory()->count(3)->create();

        $results = Client::search('')->get();

        $this->assertCount(3, $results);
    }

    public function test_search_scope_returns_all_when_null(): void
    {
        Client::factory()->count(3)->create();

        $results = Client::search(null)->get();

        $this->assertCount(3, $results);
    }

    public function test_is_business_returns_true_for_b2b(): void
    {
        $client = Client::factory()->b2b()->create();

        $this->assertTrue($client->isBusiness());
        $this->assertFalse($client->isIndividual());
    }

    public function test_is_individual_returns_true_for_b2c(): void
    {
        $client = Client::factory()->b2c()->create();

        $this->assertTrue($client->isIndividual());
        $this->assertFalse($client->isBusiness());
    }

    public function test_is_eu_client_returns_true_for_eu_countries(): void
    {
        $luClient = Client::factory()->create(['country_code' => 'LU']);
        $frClient = Client::factory()->create(['country_code' => 'FR']);
        $beClient = Client::factory()->create(['country_code' => 'BE']);

        $this->assertTrue($luClient->isEuClient());
        $this->assertTrue($frClient->isEuClient());
        $this->assertTrue($beClient->isEuClient());
    }

    public function test_is_eu_client_returns_false_for_non_eu_countries(): void
    {
        $usClient = Client::factory()->create(['country_code' => 'US']);
        $chClient = Client::factory()->create(['country_code' => 'CH']);

        $this->assertFalse($usClient->isEuClient());
        $this->assertFalse($chClient->isEuClient());
    }

    public function test_can_be_deleted_returns_true_when_no_invoices(): void
    {
        $client = Client::factory()->create();

        $this->assertTrue($client->canBeDeleted());
    }

    public function test_of_type_scope_filters_correctly(): void
    {
        Client::factory()->b2b()->count(2)->create();
        Client::factory()->b2c()->count(3)->create();

        $b2bResults = Client::ofType('b2b')->get();
        $b2cResults = Client::ofType('b2c')->get();

        $this->assertCount(2, $b2bResults);
        $this->assertCount(3, $b2cResults);
    }

    public function test_from_country_scope_filters_correctly(): void
    {
        Client::factory()->create(['country_code' => 'LU']);
        Client::factory()->create(['country_code' => 'LU']);
        Client::factory()->create(['country_code' => 'FR']);

        $results = Client::fromCountry('LU')->get();

        $this->assertCount(2, $results);
    }

    public function test_soft_delete_works(): void
    {
        $client = Client::factory()->create();
        $clientId = $client->id;

        $client->delete();

        $this->assertSoftDeleted('clients', ['id' => $clientId]);
        $this->assertNull(Client::find($clientId));
        $this->assertNotNull(Client::withTrashed()->find($clientId));
    }
}

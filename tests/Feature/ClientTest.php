<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_clients_index_page_is_displayed(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->get(route('clients.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Clients/Index')
            ->has('clients')
            ->has('clientTypes')
        );
    }

    public function test_clients_index_displays_clients(): void
    {
        Client::factory()->count(3)->create();

        $response = $this
            ->actingAs($this->user)
            ->get(route('clients.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Clients/Index')
            ->has('clients.data', 3)
        );
    }

    public function test_clients_index_can_search(): void
    {
        Client::factory()->create(['name' => 'ACME Corporation']);
        Client::factory()->create(['name' => 'Tech Solutions']);

        $response = $this
            ->actingAs($this->user)
            ->get(route('clients.index', ['search' => 'ACME']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Clients/Index')
            ->has('clients.data', 1)
            ->where('clients.data.0.name', 'ACME Corporation')
        );
    }

    public function test_clients_index_can_filter_by_type(): void
    {
        Client::factory()->b2b()->count(2)->create();
        Client::factory()->b2c()->count(3)->create();

        $response = $this
            ->actingAs($this->user)
            ->get(route('clients.index', ['type' => 'b2b']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Clients/Index')
            ->has('clients.data', 2)
        );
    }

    public function test_clients_create_page_is_displayed(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->get(route('clients.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Clients/Create')
            ->has('clientTypes')
            ->has('currencies')
            ->has('countries')
        );
    }

    public function test_client_can_be_created(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post(route('clients.store'), [
                'name' => 'New Client',
                'email' => 'new@client.com',
                'address' => '123 Street',
                'postal_code' => 'L-1234',
                'city' => 'Luxembourg',
                'country_code' => 'LU',
                'type' => 'b2b',
                'currency' => 'EUR',
                'vat_number' => 'LU12345678',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('clients', [
            'name' => 'New Client',
            'email' => 'new@client.com',
        ]);
    }

    public function test_client_show_page_is_displayed(): void
    {
        $client = Client::factory()->create();

        $response = $this
            ->actingAs($this->user)
            ->get(route('clients.show', $client));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Clients/Show')
            ->has('client')
            ->where('client.id', $client->id)
        );
    }

    public function test_client_edit_page_is_displayed(): void
    {
        $client = Client::factory()->create();

        $response = $this
            ->actingAs($this->user)
            ->get(route('clients.edit', $client));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Clients/Edit')
            ->has('client')
            ->where('client.id', $client->id)
        );
    }

    public function test_client_can_be_updated(): void
    {
        $client = Client::factory()->create([
            'name' => 'Old Name',
        ]);

        $response = $this
            ->actingAs($this->user)
            ->put(route('clients.update', $client), [
                'name' => 'Updated Name',
                'email' => 'updated@client.com',
                'country_code' => 'LU',
                'type' => 'b2b',
                'currency' => 'EUR',
            ]);

        $response->assertRedirect(route('clients.show', $client));
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_client_can_be_deleted(): void
    {
        $client = Client::factory()->create();

        $response = $this
            ->actingAs($this->user)
            ->delete(route('clients.destroy', $client));

        $response->assertRedirect(route('clients.index'));
        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    }

    public function test_validation_requires_name(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post(route('clients.store'), [
                'email' => 'test@test.com',
                'country_code' => 'LU',
                'type' => 'b2b',
                'currency' => 'EUR',
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_validation_requires_valid_email(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post(route('clients.store'), [
                'name' => 'Test',
                'email' => 'not-an-email',
                'country_code' => 'LU',
                'type' => 'b2b',
                'currency' => 'EUR',
            ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_validation_requires_valid_type(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post(route('clients.store'), [
                'name' => 'Test',
                'email' => 'test@test.com',
                'country_code' => 'LU',
                'type' => 'invalid',
                'currency' => 'EUR',
            ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_validation_requires_valid_currency(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post(route('clients.store'), [
                'name' => 'Test',
                'email' => 'test@test.com',
                'country_code' => 'LU',
                'type' => 'b2b',
                'currency' => 'XYZ',
            ]);

        $response->assertSessionHasErrors('currency');
    }

    public function test_validation_vat_number_format(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->post(route('clients.store'), [
                'name' => 'Test',
                'email' => 'test@test.com',
                'country_code' => 'LU',
                'type' => 'b2b',
                'currency' => 'EUR',
                'vat_number' => 'invalid-vat',
            ]);

        $response->assertSessionHasErrors('vat_number');
    }

    public function test_guest_cannot_access_clients(): void
    {
        $response = $this->get(route('clients.index'));

        $response->assertRedirect(route('login'));
    }
}

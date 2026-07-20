<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression: clients.email was NOT NULL while the import treats it as optional,
 * causing SQLSTATE[23000] 1048 "Column 'email' cannot be null" for postal-only
 * clients (e.g. public administrations). Email is now optional end-to-end.
 */
class ClientEmailOptionalTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_be_created_without_email(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('clients.store', ['locale' => 'fr']), [
            'name' => 'Ville de Dudelange - Service Culturel',
            'country_code' => 'LU',
            'type' => 'b2b',
            'currency' => 'EUR',
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('clients', [
            'name' => 'Ville de Dudelange - Service Culturel',
            'email' => null,
            'user_id' => $user->id,
        ]);
    }

    public function test_invalid_email_is_still_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('clients.store', ['locale' => 'fr']), [
            'name' => 'Client Test',
            'email' => 'not-an-email',
            'country_code' => 'LU',
            'type' => 'b2b',
            'currency' => 'EUR',
        ]);

        $response->assertSessionHasErrors('email');
    }
}

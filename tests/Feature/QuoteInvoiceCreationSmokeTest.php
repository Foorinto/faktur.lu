<?php

namespace Tests\Feature;

use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteInvoiceCreationSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_quotes_get_distinct_references_with_default_format(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        BusinessSettings::factory()->create(); // default number_format
        $client = Client::factory()->create(['user_id' => $user->id]);

        $q1 = Quote::create(['client_id' => $client->id, 'currency' => 'EUR', 'status' => Quote::STATUS_DRAFT]);
        $q2 = Quote::create(['client_id' => $client->id, 'currency' => 'EUR', 'status' => Quote::STATUS_DRAFT]);

        $this->assertNotNull($q1->reference);
        $this->assertNotNull($q2->reference);
        $this->assertNotSame($q1->reference, $q2->reference, 'Two quotes must not share a reference');
    }
}

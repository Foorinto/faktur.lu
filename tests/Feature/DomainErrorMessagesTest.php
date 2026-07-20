<?php

namespace Tests\Feature;

use App\Exceptions\ImmutableInvoiceException;
use App\Exceptions\UserFacingException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DomainErrorMessagesTest extends TestCase
{
    public function test_immutable_invoice_exception_uses_translated_default_message(): void
    {
        $e = new ImmutableInvoiceException();

        $this->assertInstanceOf(UserFacingException::class, $e);
        $this->assertSame(__('app.error_invoice_locked'), $e->getMessage());
        $this->assertStringContainsString('verrouillée', $e->getMessage());
    }

    public function test_user_facing_exception_is_not_masked_by_global_handler(): void
    {
        config(['app.debug' => false]);

        Route::middleware('web')->get('/__locked_test_route', function () {
            throw new ImmutableInvoiceException();
        });

        $response = $this->getJson('/__locked_test_route');

        // The exception keeps its own render() (403 + clear domain message),
        // instead of being masked into a generic "unexpected error + code".
        $response->assertStatus(403);
        $response->assertJsonFragment(['error' => 'immutable_invoice']);
        $this->assertStringContainsString(__('app.error_invoice_locked'), $response->getContent());
        $this->assertDoesNotMatchRegularExpression('/[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}/', $response->getContent());
    }
}

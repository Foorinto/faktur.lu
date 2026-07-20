<?php

namespace Tests\Feature;

use App\Support\UserError;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class UserErrorTest extends TestCase
{
    public function test_reference_has_expected_shape(): void
    {
        $ref = UserError::generateReference();
        // 4 + '-' + 4, unambiguous alphabet only (no 0/O/1/I/L).
        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}$/', $ref);
    }

    public function test_report_hides_technical_detail_and_logs_with_ref(): void
    {
        Log::shouldReceive('error')->once()->withArgs(function ($message, $context) {
            return str_contains($message, 'SECRET SQL LEAK')
                && isset($context['ref'])
                && ($context['context'] ?? null) === 'test.context';
        });

        $message = UserError::report(new \RuntimeException('SECRET SQL LEAK'), 'test.context');

        // The user-facing message must NOT contain the raw technical detail...
        $this->assertStringNotContainsString('SECRET SQL LEAK', $message);
        // ...but must contain a quotable reference code.
        $this->assertMatchesRegularExpression('/[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}/', $message);
    }

    public function test_global_handler_masks_uncaught_exception_in_production(): void
    {
        config(['app.debug' => false]);

        Route::middleware('web')->get('/__boom_test_route', function () {
            throw new \RuntimeException('raw sql kaboom select * from users');
        });

        $response = $this->get('/__boom_test_route');

        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $this->assertStringNotContainsString('kaboom', (string) session('error'));
        $this->assertMatchesRegularExpression('/[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}/', (string) session('error'));
    }
}

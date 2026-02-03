<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_x_frame_options_header_is_set(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_x_content_type_options_header_is_set(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_x_xss_protection_header_is_set(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-XSS-Protection', '1; mode=block');
    }

    public function test_referrer_policy_header_is_set(): void
    {
        $response = $this->get('/');

        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_permissions_policy_header_is_set(): void
    {
        $response = $this->get('/');

        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
    }

    public function test_content_security_policy_header_is_set(): void
    {
        $response = $this->get('/');

        $this->assertTrue($response->headers->has('Content-Security-Policy'));

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
    }

    public function test_hsts_header_not_set_in_non_production(): void
    {
        // In testing environment, HSTS should not be set
        $response = $this->get('/');

        $this->assertFalse($response->headers->has('Strict-Transport-Security'));
    }

    public function test_hsts_header_set_in_production(): void
    {
        // Temporarily set environment to production
        config(['app.env' => 'production']);

        $response = $this->get('/');

        $response->assertHeader(
            'Strict-Transport-Security',
            'max-age=31536000; includeSubDomains; preload'
        );

        // Reset environment
        config(['app.env' => 'testing']);
    }
}

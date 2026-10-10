<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_pages_carry_the_baseline_security_headers(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
        $this->assertStringContainsString('geolocation=()', $response->headers->get('Permissions-Policy'));
        $this->assertStringContainsString("frame-ancestors 'none'", $response->headers->get('Content-Security-Policy'));
    }

    public function test_hsts_is_sent_only_over_https_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->get('https://hezarrial.test/login')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
        $this->get('http://hezarrial.test/login')->assertHeaderMissing('Strict-Transport-Security');

        $this->app['env'] = 'testing';
        $this->get('https://hezarrial.test/login')->assertHeaderMissing('Strict-Transport-Security');
    }
}

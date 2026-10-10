<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_scripts_are_allowed_by_nonce_not_by_unsafe_inline(): void
    {
        $response = $this->get('/register');

        preg_match("/script-src ([^;]+);/", $response->headers->get('Content-Security-Policy'), $script);
        $this->assertStringNotContainsString('unsafe-inline', $script[1]);
        preg_match("/'nonce-([^']+)'/", $script[1], $nonce);

        $html = $response->getContent();
        $this->assertStringContainsString('<script nonce="'.$nonce[1].'">', $html, 'theme script');
        $this->assertSame(substr_count($html, '<script'), substr_count($html, 'nonce="'.$nonce[1].'"') + substr_count($html, 'type="application/json"'), 'every executable script carries the nonce');
    }

    public function test_nonce_changes_on_every_request(): void
    {
        $first = $this->get('/register')->headers->get('Content-Security-Policy');
        $second = $this->get('/register')->headers->get('Content-Security-Policy');

        $this->assertNotSame($first, $second);
    }

    public function test_hsts_is_sent_only_over_https_in_production(): void
    {
        config(['app.url' => 'https://hezarrial.test']);
        $this->app['env'] = 'production';

        $this->get('https://hezarrial.test/login')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
        // Plain http is sent to https first (see CanonicalHost), and never gets the header.
        $this->get('http://hezarrial.test/login')->assertRedirect('https://hezarrial.test/login')->assertHeaderMissing('Strict-Transport-Security');

        $this->app['env'] = 'testing';
        $this->get('https://hezarrial.test/login')->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_private_areas_are_not_indexed(): void
    {
        $user = \App\Models\User::forceCreate(['name' => 'مالک', 'email' => 'owner@example.com', 'password' => 'x', 'email_verified_at' => now()]);

        foreach (['/login', '/register', '/forgot-password'] as $path) {
            $this->get($path)->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        }

        $this->actingAs($user)->get('/app')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->actingAs($user)->get('/app/accounts')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }
}

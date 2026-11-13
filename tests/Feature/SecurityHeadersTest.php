<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_set_on_public_responses(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->assertHeader('Content-Security-Policy', "base-uri 'self'; object-src 'none'; frame-ancestors 'none'; form-action 'self'")
            ->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_private_pages_login_logout_and_errors_are_not_cacheable(): void
    {
        foreach ([route('login'), route('dashboard'), '/dashboard/responses/999', '/forms/public/missing'] as $url) {
            $response = $this->get($url);
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        }
        $this->actingAs(User::factory()->create());
        foreach ([route('dashboard'), route('clients.account'), route('clients.contracts'), route('clients.responses.index'), '/dashboard/responses/999'] as $url) {
            $response = $this->get($url);
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
            $response->assertHeader('Pragma', 'no-cache')->assertHeader('Expires', '0');
        }
        $response = $this->post(route('logout'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_hsts_is_only_sent_for_production_https_requests(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->get('http://example.com/')->assertHeaderMissing('Strict-Transport-Security');
        $this->get('https://example.com/')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    public function test_the_check_rejects_development_configuration_without_printing_secrets(): void
    {
        config(['app.debug' => true, 'session.secure' => false, 'session.driver' => 'array']);
        $this->artisan('app:check-production')
            ->expectsOutputToContain('FAIL: APP_ENV must be production')
            ->expectsOutputToContain('FAIL: APP_DEBUG must be false')
            ->expectsOutputToContain('FAIL: SESSION_SECURE_COOKIE must be true')
            ->assertFailed();
    }

    public function test_the_check_accepts_secure_release_configuration(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config([
            'app.debug' => false, 'app.url' => 'https://bits.example.com',
            'app.key' => 'base64:'.base64_encode(random_bytes(32)),
            'session.driver' => 'database', 'session.secure' => true,
            'session.http_only' => true, 'session.encrypt' => true,
            'session.same_site' => 'lax', 'session.lifetime' => 120, 'session.domain' => null,
            'cache.default' => 'database', 'queue.default' => 'database',
            'mail.default' => 'smtp', 'logging.channels.single.level' => 'warning',
        ]);
        File::shouldReceive('isFile')->with(public_path('build/manifest.json'))->once()->andReturnTrue();
        File::shouldReceive('exists')->with(public_path('hot'))->once()->andReturnFalse();
        File::shouldReceive('isWritable')->with(storage_path())->once()->andReturnTrue();
        File::shouldReceive('isWritable')->with(base_path('bootstrap/cache'))->once()->andReturnTrue();

        $this->artisan('app:check-production')->assertSuccessful();
    }
}

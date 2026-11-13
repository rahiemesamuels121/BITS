<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\File;

class CheckProduction extends Command
{
    protected $signature = 'app:check-production';

    protected $description = 'Check release configuration without changing settings or exposing secrets';

    public function handle(): int
    {
        $key = (string) config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true) ?: '';
        }

        $checks = [
            'APP_ENV must be production' => app()->isProduction(),
            'APP_DEBUG must be false' => config('app.debug') === false,
            'APP_URL must use HTTPS with a real hostname' => $this->hasProductionUrl(),
            'APP_KEY must be a valid encryption key' => Encrypter::supported($key, config('app.cipher')),
            'SESSION_DRIVER must be database for account-wide session revocation' => config('session.driver') === 'database',
            'SESSION_SECURE_COOKIE must be true' => config('session.secure') === true,
            'SESSION_HTTP_ONLY must be true' => config('session.http_only') === true,
            'SESSION_ENCRYPT must be true' => config('session.encrypt') === true,
            'SESSION_SAME_SITE must be lax or strict' => in_array(config('session.same_site'), ['lax', 'strict'], true),
            'SESSION_LIFETIME must be between 1 and 120 minutes' => config('session.lifetime') > 0 && config('session.lifetime') <= 120,
            'SESSION_DOMAIN should be null for host-only cookies' => config('session.domain') === null,
            'CACHE_STORE must use shared persistent storage' => in_array(config('cache.default'), ['database', 'redis', 'memcached'], true),
            'QUEUE_CONNECTION must use a persistent queue' => in_array(config('queue.default'), ['database', 'redis', 'sqs', 'beanstalkd'], true),
            'MAIL_MAILER must deliver mail, rather than log or discard it' => ! in_array(config('mail.default'), ['log', 'array', null], true),
            'LOG_LEVEL must exclude debug output' => in_array(config('logging.channels.single.level'), ['info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'], true),
            'Production assets must be built' => File::isFile(public_path('build/manifest.json')),
            'Vite development marker public/hot must be absent' => ! File::exists(public_path('hot')),
            'storage must be writable' => File::isWritable(storage_path()),
            'bootstrap/cache must be writable' => File::isWritable(base_path('bootstrap/cache')),
        ];

        foreach ($checks as $description => $passes) {
            $passes ? $this->info('PASS: '.$description) : $this->error('FAIL: '.$description);
        }

        $this->newLine();
        $this->line('Also verify TLS/proxy configuration, backups, queue supervision, database migrations, and monitoring on the target host.');

        return in_array(false, $checks, true) ? self::FAILURE : self::SUCCESS;
    }

    private function hasProductionUrl(): bool
    {
        $url = (string) config('app.url');
        $host = parse_url($url, PHP_URL_HOST);

        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && parse_url($url, PHP_URL_SCHEME) === 'https'
            && is_string($host)
            && ! in_array(strtolower($host), ['localhost', '127.0.0.1', '::1', '[::1]'], true)
            && ! str_ends_with(strtolower($host), '.invalid');
    }
}

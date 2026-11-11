<?php

use App\Models\Form;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$base = rtrim(config('app.url'), '/');
if (! $app->environment('local') || ! in_array(parse_url($base, PHP_URL_HOST), ['localhost', '127.0.0.1'], true)) {
    throw new RuntimeException('This HTTP smoke check requires a local app on a loopback address.');
}

$cookies = [];
$request = function (string $method, string $path, array $data = [], bool $json = false) use ($base, &$cookies): array {
    $headers = ['Accept: '.($json ? 'application/json' : 'text/html')];
    if ($cookies) {
        $headers[] = 'Cookie: '.implode('; ', array_map(fn ($name, $value) => "$name=$value", array_keys($cookies), $cookies));
    }
    if ($method !== 'GET') {
        $headers[] = 'Content-Type: '.($json ? 'application/json' : 'application/x-www-form-urlencoded');
    }
    $context = stream_context_create(['http' => ['method' => $method, 'header' => implode("\r\n", $headers), 'content' => $method === 'GET' ? '' : ($json ? json_encode($data, JSON_THROW_ON_ERROR) : http_build_query($data)), 'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 20]]);
    $body = file_get_contents($base.$path, false, $context);
    $responseHeaders = $http_response_header ?? [];
    preg_match('/\s(\d{3})\s/', $responseHeaders[0] ?? '', $status);
    $location = null;
    foreach ($responseHeaders as $header) {
        if (preg_match('/^Set-Cookie:\s*([^=]+)=([^;]*)/i', $header, $match)) {
            $cookies[$match[1]] = $match[2];
        }
        if (stripos($header, 'Location: ') === 0) {
            $location = trim(substr($header, 10));
        }
    }

    return [(int) ($status[1] ?? 0), $body ?: '', $location];
};
$expect = function (array $response, int $expected, string $step): array {
    if ($response[0] !== $expected) {
        throw new RuntimeException("$step returned HTTP {$response[0]}, expected $expected.");
    }
    echo "$step: HTTP $expected\n";

    return $response;
};
$token = function (string $html): string {
    if (! preg_match('/name="(?:_token|csrf-token)"[^>]*(?:value|content)="([^"]+)"/', $html, $match)) {
        throw new RuntimeException('The HTTP page did not contain a CSRF token.');
    }

    return html_entity_decode($match[1]);
};
$builder = function (string $html): array {
    if (! preg_match('/<script[^>]*id="form-builder-data"[^>]*>(.*?)<\/script>/s', $html, $match)) {
        throw new RuntimeException('The HTTP page did not contain builder data.');
    }

    return json_decode($match[1], true, flags: JSON_THROW_ON_ERROR);
};

$password = Str::random(40);
$owner = User::query()->create(['name' => 'HTTP CRUD smoke test', 'email' => 'http-crud-'.Str::uuid().'@example.invalid', 'password' => $password]);
try {
    [, $login] = $expect($request('GET', '/login'), 200, 'Login page');
    $expect($request('POST', '/login', ['_token' => $token($login), 'email' => $owner->email, 'password' => $password]), 302, 'Login');
    [, $create] = $expect($request('GET', '/dashboard/forms/create'), 200, 'Create page');
    [, , $location] = $expect($request('POST', '/dashboard/forms', ['_token' => $token($create), 'title' => 'HTTP CRUD smoke test']), 302, 'Create form');
    $editPath = parse_url($location, PHP_URL_PATH);
    [, $edit] = $expect($request('GET', $editPath), 200, 'Edit page');
    $initial = $builder($edit);
    $path = parse_url($initial['save_url'], PHP_URL_PATH);
    $definition = $initial['definition'];
    $definition['title'] = 'Updated HTTP CRUD smoke test';
    $definition['fields'] = [['field_key' => 'q_name', 'type' => 'text', 'label' => 'Your name', 'required' => false, 'options' => [], 'validation_rules' => new stdClass, 'conditional_rules' => null, 'default_value' => null]];
    $expect($request('PUT', $path, array_merge($definition, ['_token' => $token($edit), 'revision' => $initial['revision'], 'settings' => $initial['settings']]), true), 200, 'Save question');
    [, $reloaded] = $expect($request('GET', $editPath), 200, 'Reload saved form');
    $saved = $builder($reloaded);
    if ($saved['definition']['fields'][0]['label'] !== 'Your name' || $saved['definition']['title'] !== $definition['title']) {
        throw new RuntimeException('Saved form changes did not survive reloading.');
    }
    $expect($request('POST', $path.'/duplicate', ['_token' => $token($reloaded)]), 302, 'Duplicate form');
    $expect($request('DELETE', $path, ['_token' => $token($reloaded)]), 302, 'Delete form');
    $expect($request('GET', $editPath), 404, 'Deleted form is inaccessible');
    $expect($request('GET', '/dashboard/forms'), 200, 'List forms');
} finally {
    // Only records owned by the temporary account created above are removed.
    Form::withTrashed()->where('user_id', $owner->id)->forceDelete();
    DB::table('sessions')->where('user_id', $owner->id)->delete();
    $owner->forceDelete();
    echo "Temporary HTTP test account and forms removed.\n";
}

# Backend email

`App\Services\EmailService` queues an email using Laravel's configured mailer. Call it from a controller, service, event listener, command, or scheduled task. The container resolves the service automatically.

```php
use App\Services\EmailService;

app(EmailService::class)->send(
    to: $client->email,
    subject: 'Your account is ready',
    body: "Your BITS account has been created.\nYou can now sign in.",
    actionUrl: route('login'),
    actionText: 'Sign in',
);
```

The action button is optional. Pass an array of addresses to queue separate messages, keeping recipients private. All inputs are validated before any messages are queued. The body is plain text: line breaks are retained, and HTML is escaped in the HTML version. Every email also includes a plain-text version.

Messages are dispatched after a database transaction commits and discarded if it rolls back. The worker attempts delivery up to three times, waiting 60 and then 300 seconds between retries. The helper does not swallow delivery errors: exhausted retries appear in Laravel's `failed_jobs` table with the default database queue configuration. `send()` returning means queuing succeeded, not that delivery was confirmed. Queues provide at-least-once delivery, so a retry after an ambiguous transport failure can result in a duplicate email.

## Delivery configuration

The current `log` mailer writes messages to the application log; it does not deliver to an inbox. Configure your provider's SMTP settings in `.env` for real delivery:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.your-provider.example
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_TIMEOUT=30
MAIL_FROM_ADDRESS=notifications@your-domain.example
MAIL_FROM_NAME="BITS"
QUEUE_CONNECTION=database
```

Use the scheme and port supplied by your provider (`smtps` for implicit TLS, or `smtp` with STARTTLS as configured by the provider). Set an HTTPS `APP_URL` in production so generated action links use the correct host and scheme. Never include passwords or credentials in the email body.

After updating configuration, clear the configuration cache and restart existing workers:

```sh
php artisan config:clear
php artisan queue:restart
```

The repository already includes the database queue migrations. Apply pending migrations, then run a worker:

```sh
php artisan migrate
php artisan queue:work --sleep=3 --timeout=60
```

Use a process manager to keep the worker running in production. The default queue retry window is 90 seconds, longer than the 60-second job timeout and 30-second SMTP timeout. Keep that ordering when customizing these values.

Inspect failed deliveries with `php artisan queue:failed`; retry a specific job with `php artisan queue:retry <job-id>` after addressing the cause.

The helper does not register any automatic business triggers. Invoke it at the desired event once its recipients and message are defined. Tests use mail fakes or the in-memory array transport and do not send external emails.

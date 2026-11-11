<?php

namespace Tests\Feature;

use App\Mail\AutomatedEmail;
use App\Services\EmailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EmailServiceTest extends TestCase
{
    public function test_backend_email_is_queued_with_its_recipient_subject_and_body(): void
    {
        Mail::fake();

        app(EmailService::class)->send(' client@example.com ', 'Contract updated', "Your contract is ready.\nPlease review it.");

        Mail::assertQueued(AutomatedEmail::class, function (AutomatedEmail $mail) {
            return $mail instanceof ShouldQueue
                && $mail->hasTo('client@example.com')
                && $mail->envelope()->subject === 'Contract updated'
                && $mail->bodyText === "Your contract is ready.\nPlease review it."
                && $mail->actionUrl === null;
        });
        Mail::assertQueuedCount(1);
        Mail::assertNothingSent();
    }

    public function test_each_recipient_gets_a_private_email(): void
    {
        Mail::fake();

        app(EmailService::class)->send(['first@example.com', 'second@example.com'], 'Workspace update', 'An update for your account.');

        Mail::assertQueuedCount(2);
        Mail::assertQueued(AutomatedEmail::class, fn (AutomatedEmail $mail) => $mail->hasTo('first@example.com') && ! $mail->hasTo('second@example.com') && count($mail->to) === 1);
        Mail::assertQueued(AutomatedEmail::class, fn (AutomatedEmail $mail) => $mail->hasTo('second@example.com') && ! $mail->hasTo('first@example.com') && count($mail->to) === 1);
    }

    public function test_html_escapes_message_content_and_plain_text_preserves_it(): void
    {
        $body = "<script>alert('unsafe')</script>\nSecond line & more";
        $mail = new AutomatedEmail('Update <b>details</b>', $body);
        $html = $mail->render();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('Second line &amp; more', $html);
        $this->assertStringContainsString('&lt;b&gt;details&lt;/b&gt;', $html);
        $this->assertStringContainsString('<br', $html);
        $this->assertStringNotContainsString('href=', $html);
        $mail->assertSeeInText($body);
    }

    public function test_optional_action_is_included_in_html_and_plain_text(): void
    {
        Mail::fake();
        $url = 'https://example.com/contracts/12?from=email&view=details';

        app(EmailService::class)->send('client@example.com', 'Review contract', 'A contract is ready.', $url, 'Review contract');

        Mail::assertQueued(AutomatedEmail::class, function (AutomatedEmail $mail) use ($url) {
            $this->assertSame($url, $mail->actionUrl);
            $this->assertSame('Review contract', $mail->actionText);
            $this->assertStringContainsString('href="'.e($url).'"', $mail->render());
            $mail->assertSeeInText('Review contract: '.$url);

            return true;
        });
    }

    public function test_invalid_email_requests_are_rejected_before_any_email_is_queued(): void
    {
        Mail::fake();
        $valid = ['to' => 'client@example.com', 'subject' => 'Account update', 'body' => 'Your account was updated.'];
        $invalidCases = [
            [['to' => []], 'to'],
            [['to' => 'not-an-email'], 'to.0'],
            [['to' => ['valid@example.com', 'not-an-email']], 'to.1'],
            [['to' => ['client@example.com', 'CLIENT@example.com']], 'to.0'],
            [['subject' => "Update\r\nBcc: other@example.com"], 'subject'],
            [['subject' => ''], 'subject'],
            [['body' => ''], 'body'],
            [['actionUrl' => 'javascript:alert(1)'], 'action_url'],
            [['actionUrl' => 'https://example.com', 'actionText' => ''], 'action_text'],
        ];

        foreach ($invalidCases as [$changes, $errorKey]) {
            try {
                app(EmailService::class)->send(...array_replace($valid, $changes));
                $this->fail('Expected validation to reject '.$errorKey);
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey($errorKey, $exception->errors());
            }

            Mail::assertNothingQueued();
            Mail::assertNothingSent();
        }
    }

    public function test_delivery_waits_for_commit_and_rolled_back_transactions_do_not_send(): void
    {
        config(['mail.default' => 'array', 'queue.default' => 'sync']);
        $transport = Mail::mailer()->getSymfonyTransport();
        $this->assertInstanceOf(ArrayTransport::class, $transport);

        DB::beginTransaction();
        try {
            app(EmailService::class)->send('client@example.com', 'Committed update', 'The update was saved.');
            $this->assertCount(0, $transport->messages());
            DB::commit();
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }

        $this->assertCount(1, $transport->messages());
        $this->assertSame('Committed update', $transport->messages()->sole()->getOriginalMessage()->getSubject());
        $transport->flush();

        DB::beginTransaction();
        try {
            app(EmailService::class)->send('client@example.com', 'Rolled back update', 'This update will not be saved.');
            $this->assertCount(0, $transport->messages());
        } finally {
            DB::rollBack();
        }

        $this->assertCount(0, $transport->messages());
    }
}

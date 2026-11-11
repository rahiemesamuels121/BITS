<?php

namespace App\Services;

use App\Mail\AutomatedEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class EmailService
{
    /**
     * Queue a separate email for each recipient after the current transaction commits.
     *
     * @param  string|array<int, string>  $to
     */
    public function send(
        string|array $to,
        string $subject,
        string $body,
        ?string $actionUrl = null,
        string $actionText = 'Open BITS',
    ): void {
        $recipients = array_map(
            fn ($recipient) => is_string($recipient) ? trim($recipient) : $recipient,
            is_array($to) ? array_values($to) : [$to],
        );

        $data = Validator::make([
            'to' => $recipients,
            'subject' => $subject,
            'body' => $body,
            'action_url' => $actionUrl,
            'action_text' => $actionText,
        ], [
            'to' => ['required', 'array', 'min:1', 'max:100'],
            'to.*' => ['required', 'string', 'email:rfc', 'max:254', 'distinct:ignore_case'],
            'subject' => ['required', 'string', 'max:255', 'not_regex:/[\r\n]/'],
            'body' => ['required', 'string', 'max:20000'],
            'action_url' => ['nullable', 'url:http,https', 'max:2048'],
            'action_text' => ['required_with:action_url', 'string', 'max:80'],
        ])->validate();

        foreach ($data['to'] as $recipient) {
            Mail::to($recipient)->queue(new AutomatedEmail(
                subjectLine: $data['subject'],
                bodyText: $data['body'],
                actionUrl: $data['action_url'],
                actionText: $data['action_text'],
            ));
        }
    }
}

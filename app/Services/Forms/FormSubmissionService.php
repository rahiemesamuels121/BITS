<?php

namespace App\Services\Forms;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class FormSubmissionService
{
    public function __construct(private FormValidationService $validation) {}

    public function unavailableReason(Form $form): ?string
    {
        if ($form->status !== 'published' || ! $form->publishedVersion) {
            return 'This form is not currently published.';
        }
        $settings = $form->publishedVersion->settings_json;
        if (! $settings['active'] || ! $settings['accept_responses']) {
            return 'This form is not accepting new responses.';
        }
        if ($settings['deadline'] && now()->gte(CarbonImmutable::parse($settings['deadline']))) {
            return 'The response deadline for this form has passed.';
        }
        if ($settings['max_submissions'] && $form->submissions()->count() >= $settings['max_submissions']) {
            return 'This form has reached its response limit.';
        }

        return null;
    }

    public function requiresLogin(Form $form): bool
    {
        $settings = $form->publishedVersion->settings_json;

        return $settings['require_authentication'] || ! $settings['allow_anonymous'];
    }

    public function submit(Form $form, int $versionId, array $answers, ?User $respondent): FormSubmission
    {
        $stored = [];
        try {
            return DB::transaction(function () use ($form, $versionId, $answers, $respondent, &$stored) {
                // Serialise quota and duplicate-response checks with writes for this form.
                $form = Form::query()->lockForUpdate()->findOrFail($form->id);
                if ($reason = $this->unavailableReason($form)) {
                    throw ValidationException::withMessages(['form' => $reason]);
                }
                if ($this->requiresLogin($form) && ! $respondent) {
                    abort(401);
                }
                $version = $form->publishedVersion;
                if ($version->id !== $versionId) {
                    throw ValidationException::withMessages(['version_id' => 'This form has been updated. Refresh the page before submitting.']);
                }
                if ($version->settings_json['one_per_user'] && $respondent && $form->submissions()->where('user_id', $respondent->id)->exists()) {
                    throw ValidationException::withMessages(['form' => 'You have already submitted a response to this form.']);
                }
                $data = $this->validation->validate($version->schema_json, $answers);
                foreach ($data as $key => $value) {
                    if (! $value instanceof UploadedFile) {
                        continue;
                    }
                    $path = Storage::disk('local')->putFileAs('forms/'.$form->id, $value, Str::uuid().'.'.strtolower($value->getClientOriginalExtension()));
                    if (! $path) {
                        throw new RuntimeException('Unable to store the uploaded file. Please try again.');
                    }
                    $stored[] = $path;
                    $data[$key] = ['kind' => 'file', 'path' => $path, 'name' => mb_substr(basename($value->getClientOriginalName()), 0, 200), 'mime' => $value->getMimeType(), 'size' => $value->getSize()];
                }

                return $form->submissions()->create(['form_version_id' => $version->id, 'user_id' => $respondent?->id, 'data' => $data, 'submitted_at' => now()]);
            });
        } catch (Throwable $exception) {
            foreach ($stored as $path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }
    }

    public function delete(FormSubmission $submission): void
    {
        $files = collect($submission->data)->filter(fn ($value) => is_array($value) && ($value['kind'] ?? null) === 'file' && is_string($value['path'] ?? null) && str_starts_with($value['path'], 'forms/'.$submission->form_id.'/'))->pluck('path')->all();
        DB::transaction(fn () => $submission->delete());
        foreach ($files as $path) {
            Storage::disk('local')->delete($path);
        }
    }
}

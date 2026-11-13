<?php

namespace App\Services\Forms;

use App\Models\FormSubmission;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionAttachmentService
{
    public function path(FormSubmission $submission, mixed $file): ?string
    {
        $path = is_array($file) ? ($file['path'] ?? null) : null;

        if (! is_array($file) || ($file['kind'] ?? null) !== 'file' || ! is_string($path)
            || ! str_starts_with($path, 'forms/'.$submission->form_id.'/')
            || str_contains($path, '..') || str_contains($path, '\\') || str_contains($path, "\0")) {
            return null;
        }

        return $path;
    }

    public function download(FormSubmission $submission, string $field): StreamedResponse
    {
        $definition = collect($submission->version->schema_json['fields'] ?? [])->firstWhere('field_key', $field);
        abort_unless(($definition['type'] ?? null) === 'file', 404);
        $file = $submission->data[$field] ?? null;
        $path = $this->path($submission, $file);
        abort_unless($path !== null && Storage::disk('local')->exists($path), 404);
        $name = is_string($file['name'] ?? null) ? $file['name'] : 'attachment';
        $filename = preg_replace('/[^\pL\pN._ -]/u', '_', basename($name));

        return Storage::disk('local')->download($path, $filename ?: 'attachment', [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}

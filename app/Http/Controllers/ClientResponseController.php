<?php

namespace App\Http\Controllers;

use App\Models\FormSubmission;
use App\Services\Forms\ClientResponseService;
use App\Services\Forms\FormReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientResponseController extends Controller
{
    public function show(Request $request, FormSubmission $submission, ClientResponseService $responses, FormReportService $reports)
    {
        $submission = $this->authorizedResponse($request, $submission, $responses);

        return view('clients.response', ['submission' => $submission, 'account' => $request->user(), 'reports' => $reports]);
    }

    public function download(Request $request, FormSubmission $submission, string $field, ClientResponseService $responses)
    {
        $submission = $this->authorizedResponse($request, $submission, $responses);
        $definition = collect($submission->version->schema_json['fields'] ?? [])->firstWhere('field_key', $field);
        abort_unless(($definition['type'] ?? null) === 'file', 404);
        $file = $submission->data[$field] ?? null;
        $path = is_array($file) ? ($file['path'] ?? null) : null;
        abort_unless(is_array($file) && ($file['kind'] ?? null) === 'file'
            && is_string($path) && str_starts_with($path, 'forms/'.$submission->form_id.'/')
            && ! str_contains($path, '..') && ! str_contains($path, '\\'), 404);
        abort_unless(Storage::disk('local')->exists($path), 404);
        $filename = preg_replace('/[^\pL\pN._ -]/u', '_', basename($file['name'] ?? 'attachment'));

        return Storage::disk('local')->download($path, $filename ?: 'attachment', ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    private function authorizedResponse(Request $request, FormSubmission $submission, ClientResponseService $responses): FormSubmission
    {
        abort_unless($request->user()->usesClientDashboard(), 404);

        return $responses->query($request->user())->with(['form.contract', 'version'])->findOrFail($submission->id);
    }
}

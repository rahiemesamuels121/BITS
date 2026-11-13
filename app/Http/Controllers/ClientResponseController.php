<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\FormVersion;
use App\Services\Forms\ClientResponseService;
use App\Services\Forms\FormReportService;
use App\Services\Forms\SubmissionAttachmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ClientResponseController extends Controller
{
    public function index(Request $request, ClientResponseService $responses)
    {
        abort_unless($request->user()->usesClientDashboard(), 404);
        $filters = $request->validate([
            'form' => ['nullable', 'integer', 'min:1'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])],
        ]);
        $query = $responses->query($request->user());
        $forms = Form::query()->whereIn('id', (clone $query)->select('form_id'))
            ->orderBy('title')->get(['id', 'title']);
        $query->when($filters['form'] ?? null, fn ($query, $form) => $query->where('form_id', $form))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->where('submitted_at', '>=', $from.' 00:00:00'))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->where('submitted_at', '<', Carbon::parse($to)->addDay()->toDateString()));

        if ($request->routeIs('clients.responses.export')) {
            return response()->streamDownload(function () use ($query, $request) {
                $output = fopen('php://output', 'w');
                fwrite($output, "\xEF\xBB\xBF");
                $columns = [];
                $versions = FormVersion::query()
                    ->whereIn('id', (clone $query)->select('form_version_id'))
                    ->orderByDesc('version_number')->orderBy('id')->get();
                foreach ($versions as $version) {
                    foreach ($version->schema_json['fields'] as $field) {
                        if (! in_array($field['type'], ['heading', 'description'], true)) {
                            $columns[$version->form_id.':'.$field['field_key']] ??= $field['label'];
                        }
                    }
                }
                $reports = app(FormReportService::class);
                $headers = ['Response ID', 'Form', 'Contract', 'Received (UTC)', ...array_values($columns)];
                fputcsv($output, array_map($reports->csvValue(...), $headers), ',', '"', '');
                foreach ($query->with(['form.contract', 'version'])->orderBy('id')->lazyById(200) as $submission) {
                    $answers = array_fill_keys(array_keys($columns), '');
                    foreach ($submission->version->schema_json['fields'] as $field) {
                        if (in_array($field['type'], ['heading', 'description'], true)) {
                            continue;
                        }
                        $value = $submission->data[$field['field_key']] ?? null;
                        if ($field['field_key'] === ($submission->version->settings_json['client_reference_field'] ?? null)) {
                            $value = $request->user()->name;
                        } elseif (is_scalar($value)) {
                            $value = $field['option_labels'][(string) $value] ?? $value;
                        }
                        $answers[$submission->form_id.':'.$field['field_key']] = $value;
                    }
                    $row = [$submission->id, $submission->version->schema_json['title'] ?? $submission->form->title, $submission->form->contract->name, $submission->submitted_at->utc()->format('Y-m-d H:i:s'), ...array_values($answers)];
                    fputcsv($output, array_map($reports->csvValue(...), $row), ',', '"', '');
                }
                fclose($output);
            }, 'my-responses.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return view('clients.responses', [
            'responses' => $query->with(['form.contract', 'version'])->latest('submitted_at')->orderByDesc('id')
                ->paginate(10)->withQueryString(),
            'forms' => $forms,
        ]);
    }

    public function show(Request $request, FormSubmission $submission, ClientResponseService $responses, FormReportService $reports)
    {
        $submission = $this->authorizedResponse($request, $submission, $responses);

        return view('clients.response', ['submission' => $submission, 'account' => $request->user(), 'reports' => $reports]);
    }

    public function download(Request $request, FormSubmission $submission, string $field, ClientResponseService $responses, SubmissionAttachmentService $attachments)
    {
        $submission = $this->authorizedResponse($request, $submission, $responses);

        return $attachments->download($submission, $field);
    }

    private function authorizedResponse(Request $request, FormSubmission $submission, ClientResponseService $responses): FormSubmission
    {
        abort_unless($request->user()->usesClientDashboard(), 404);

        return $responses->query($request->user())->with(['form.contract', 'version'])->findOrFail($submission->id);
    }
}

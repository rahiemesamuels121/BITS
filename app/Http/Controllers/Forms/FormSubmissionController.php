<?php

namespace App\Http\Controllers\Forms;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Services\Forms\FormReportService;
use App\Services\Forms\FormSubmissionService;
use App\Services\Forms\SubmissionAttachmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FormSubmissionController extends Controller
{
    public function index(Request $request, Form $form, FormReportService $reports)
    {
        Gate::authorize('view', $form);
        $filters = $reports->filters($request);

        return view('forms.submissions', ['form' => $form, 'submissions' => $reports->query($form, $filters)->with('version')->latest('submitted_at')->paginate(20)->withQueryString(), 'columns' => $reports->columns($form), 'versions' => $form->versions()->orderByDesc('version_number')->get(['id', 'version_number']), 'total' => $form->submissions()->count(), 'reports' => $reports]);
    }

    public function show(Form $form, FormSubmission $submission, FormReportService $reports)
    {
        Gate::authorize('view', $form);
        abort_unless((int) $submission->form_id === (int) $form->id, 404);

        return view('forms.submission', compact('form', 'submission', 'reports'));
    }

    public function destroy(Form $form, FormSubmission $submission, FormSubmissionService $service)
    {
        Gate::authorize('update', $form);
        abort_unless((int) $submission->form_id === (int) $form->id, 404);
        $service->delete($submission);

        return to_route('forms.submissions.index', $form)->with('status', 'Response deleted.');
    }

    public function download(Form $form, FormSubmission $submission, string $field, SubmissionAttachmentService $attachments)
    {
        Gate::authorize('view', $form);
        abort_unless((int) $submission->form_id === (int) $form->id, 404);

        return $attachments->download($submission, $field);
    }

    public function export(Request $request, Form $form, FormReportService $reports)
    {
        Gate::authorize('view', $form);
        $filters = $reports->filters($request);
        $columns = $reports->columns($form);

        return response()->streamDownload(function () use ($form, $reports, $filters, $columns) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            $headers = ['Response ID', 'Version', 'Submitted at (UTC)'];
            foreach ($columns as $key => $field) {
                $headers[] = $reports->csvValue($field['label'].' ('.$key.')');
            }
            fputcsv($stream, $headers, ',', '"', '');
            foreach ($reports->query($form, $filters)->with('version')->reorder()->lazyById(200) as $submission) {
                $row = [$submission->id, $submission->version->version_number, $submission->submitted_at->utc()->toDateTimeString()];
                foreach ($columns as $key => $field) {
                    $row[] = $reports->csvValue($submission->data[$key] ?? null);
                }
                fputcsv($stream, $row, ',', '"', '');
            }
            fclose($stream);
        }, $form->slug.'-responses.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }
}

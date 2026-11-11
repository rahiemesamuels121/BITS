<?php

namespace App\Http\Controllers\Forms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Forms\PublicSubmissionRequest;
use App\Models\Form;
use App\Services\Forms\FormOptionSourceService;
use App\Services\Forms\FormSubmissionService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicFormController extends Controller
{
    public function show(Request $request, string $slug, FormSubmissionService $service, FormOptionSourceService $optionSources)
    {
        $form = Form::query()->where('slug', $slug)->first();
        if ($form && (int) session('form_confirmation.form_id') === (int) $form->id) {
            return view('forms.confirmation', ['title' => $form->publishedVersion?->schema_json['title'] ?? $form->title, 'message' => session('form_confirmation.message')]);
        }
        if (! $form || ($reason = $service->unavailableReason($form))) {
            return response()->view('forms.unavailable', ['reason' => $reason ?? 'This form is no longer available.'], $form ? 410 : 404);
        }
        if ($service->requiresLogin($form) && ! $request->user()) {
            return redirect()->guest(route('login'));
        }

        $schema = $form->publishedVersion->schema_json;
        foreach ($schema['fields'] as &$field) {
            if (! empty($field['option_source'])) {
                $choices = $optionSources->choices($field['option_source'], 'fields.'.$field['field_key'].'.option_source');
                $field['options'] = $choices['options'];
                $field['option_labels'] = (array) $choices['option_labels'];
                unset($field['option_source']);
            }
        }
        unset($field);

        return view('forms.public', ['form' => $form, 'version' => $form->publishedVersion, 'schema' => $schema, 'settings' => $form->publishedVersion->settings_json]);
    }

    public function submit(PublicSubmissionRequest $request, string $slug, FormSubmissionService $service)
    {
        $form = Form::query()->where('slug', $slug)->first();
        if (! $form || ($reason = $service->unavailableReason($form))) {
            return response()->view('forms.unavailable', ['reason' => $reason ?? 'This form is no longer available.'], $form ? 410 : 404);
        }
        if ($service->requiresLogin($form) && ! $request->user()) {
            $request->session()->put('url.intended', route('public-forms.show', $slug));

            return to_route('login');
        }
        if (array_diff(array_keys($request->all()), ['_token', 'version_id', 'answers', 'website'])) {
            throw ValidationException::withMessages(['form' => 'The response contains unsupported data.']);
        }
        $answers = array_replace($request->input('answers', []), $request->file('answers', []));
        $submission = $service->submit($form, (int) $request->input('version_id'), $answers, $request->user());

        return to_route('public-forms.show', $slug)->with('form_confirmation', ['form_id' => $form->id, 'message' => $submission->version->settings_json['confirmation_message']]);
    }
}

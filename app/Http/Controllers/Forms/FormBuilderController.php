<?php

namespace App\Http\Controllers\Forms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Forms\SaveFormRequest;
use App\Models\Form;
use App\Services\Forms\FormSchemaService;
use App\Services\Forms\FormService;
use Illuminate\Support\Facades\Gate;

class FormBuilderController extends Controller
{
    public function update(SaveFormRequest $request, Form $form, FormService $service, FormSchemaService $schemas)
    {
        $form = $service->update($form, $request->validated());

        return response()->json(['revision' => $form->revision, 'updated_at' => $form->updated_at->toIso8601String(), 'status' => $form->status, 'definition' => $schemas->definition($form)]);
    }

    public function preview(Form $form, FormSchemaService $schemas)
    {
        Gate::authorize('view', $form);

        return view('forms.preview', ['form' => $form, 'schema' => $schemas->definition($form), 'settings' => $schemas->settings($form->draft_settings ?? [])]);
    }
}

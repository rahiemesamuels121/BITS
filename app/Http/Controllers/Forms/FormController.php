<?php

namespace App\Http\Controllers\Forms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Forms\CreateFormRequest;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Services\Forms\FormSchemaService;
use App\Services\Forms\FormService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class FormController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Form::class);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:200'], 'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])]]);
        $query = $request->user()->forms()->with('contract')->withCount('submissions');
        if (! empty($filters['q'])) {
            $query->where(fn ($q) => $q->where('title', 'like', '%'.$filters['q'].'%')->orWhere('description', 'like', '%'.$filters['q'].'%'));
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        $responses = FormSubmission::query()->whereHas('form', fn ($q) => $q->where('user_id', $request->user()->id));
        $stats = ['total' => $request->user()->forms()->count(), 'published' => $request->user()->forms()->where('status', 'published')->count(), 'submissions' => (clone $responses)->count(), 'recent' => (clone $responses)->where('submitted_at', '>=', now()->subDays(7))->count()];

        return view('forms.index', ['forms' => $query->latest('updated_at')->paginate(12)->withQueryString(), 'stats' => $stats]);
    }

    public function create()
    {
        Gate::authorize('create', Form::class);

        return view('forms.create');
    }

    public function store(CreateFormRequest $request, FormService $service)
    {
        Gate::authorize('create', Form::class);
        $data = $request->validated();
        $form = $service->create($request->user(), $data['title'], $data['description'] ?? null);

        return to_route('forms.edit', $form)->with('status', 'Form created. Add your first question.');
    }

    public function edit(Form $form, FormSchemaService $schemas)
    {
        Gate::authorize('update', $form);

        return view('forms.builder', ['form' => $form, 'definition' => $schemas->definition($form), 'settings' => $schemas->settings($form->draft_settings ?? [])]);
    }

    public function duplicate(Request $request, Form $form, FormService $service)
    {
        Gate::authorize('update', $form);

        return to_route('forms.edit', $service->duplicate($form, $request->user()))->with('status', 'Independent draft copy created. Responses were not copied.');
    }

    public function destroy(Form $form)
    {
        Gate::authorize('delete', $form);
        DB::transaction(fn () => Form::query()->lockForUpdate()->findOrFail($form->id)->delete());

        return to_route('forms.index')->with('status', 'Form deleted. Its public link is no longer available.');
    }
}

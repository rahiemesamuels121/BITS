<?php

namespace App\Http\Controllers\Forms;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Services\Forms\FormReportService;
use Illuminate\Support\Facades\Gate;

class FormAnalyticsController extends Controller
{
    public function __invoke(Form $form, FormReportService $reports)
    {
        Gate::authorize('view', $form);

        return view('forms.analytics', ['form' => $form, 'analytics' => $reports->analytics($form)]);
    }
}

<?php

namespace App\Http\Controllers\Forms;

use App\Http\Controllers\Controller;
use App\Services\Forms\FormOptionSourceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FormOptionSourceController extends Controller
{
    public function __invoke(Request $request, FormOptionSourceService $optionSources): JsonResponse
    {
        $data = $request->validate([
            'source' => ['required', Rule::in(array_keys($optionSources->sources()))],
            'column' => ['required', 'string'],
            'filters' => ['sometimes', 'array', 'max:5'],
            'filters.*' => ['array:column,operator,value'],
            'filters.*.column' => ['required', 'string'],
            'filters.*.operator' => ['required', Rule::in(['equals', 'not_equals', 'contains', 'greater_than', 'greater_or_equal', 'less_than', 'less_or_equal'])],
            'filters.*.value' => ['required', 'string', 'max:200'],
        ]);
        $configuration = $optionSources->validateConfiguration($data);

        return response()->json($optionSources->choices($configuration, 'filters'));
    }
}

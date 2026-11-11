<?php

namespace App\Services\Forms;

use App\Models\Form;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class FormReportService
{
    public function filters(Request $request): array
    {
        return $request->validate(['q' => ['nullable', 'string', 'max:200'], 'from' => ['nullable', 'date_format:Y-m-d'], 'to' => array_filter(['nullable', 'date_format:Y-m-d', $request->filled('from') ? 'after_or_equal:from' : null]), 'version' => ['nullable', 'integer', 'min:1']]);
    }

    public function query(Form $form, array $filters): Builder
    {
        $query = $form->submissions()->getQuery();
        if (! empty($filters['q'])) {
            $query->where('data', 'like', '%'.$filters['q'].'%');
        }
        if (! empty($filters['from'])) {
            $query->where('submitted_at', '>=', $filters['from'].' 00:00:00');
        }
        if (! empty($filters['to'])) {
            $query->where('submitted_at', '<', CarbonImmutable::parse($filters['to'])->addDay()->startOfDay());
        }
        if (! empty($filters['version'])) {
            $query->where('form_version_id', $filters['version']);
        }

        return $query;
    }

    public function columns(Form $form): array
    {
        $columns = [];
        foreach ($form->versions()->orderByDesc('version_number')->cursor() as $version) {
            foreach ($version->schema_json['fields'] as $field) {
                if (! in_array($field['type'], FormSchemaService::PRESENTATION, true) && ! isset($columns[$field['field_key']])) {
                    $columns[$field['field_key']] = $field;
                }
            }
        }

        return $columns;
    }

    public function display(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        if (is_array($value) && ($value['kind'] ?? null) === 'file') {
            return $value['name'];
        }
        if (is_array($value)) {
            return implode(', ', array_map(fn ($item) => is_scalar($item) ? (string) $item : '', $value));
        }

        return (string) $value;
    }

    public function csvValue(mixed $value): string
    {
        $text = $this->display($value);

        return preg_match('/^[\s]*[=+\-@]/u', $text) || preg_match('/^[\t\r\n]/', $text) ? "'".$text : $text;
    }

    public function analytics(Form $form): array
    {
        $daily = $form->submissions()->where('submitted_at', '>=', now()->subDays(29)->startOfDay())->selectRaw('DATE(submitted_at) AS day, COUNT(*) AS total')->groupBy('day')->orderBy('day')->pluck('total', 'day');
        $days = [];
        for ($offset = 29; $offset >= 0; $offset--) {
            $day = now()->subDays($offset)->format('Y-m-d');
            $days[] = ['day' => $day, 'total' => (int) ($daily[$day] ?? 0)];
        }
        $sqlite = $form->getConnection()->getDriverName() === 'sqlite';
        $numeric = [];
        $distributions = [];
        foreach ($this->columns($form) as $key => $field) {
            $path = '$.'.$key;
            $expression = $sqlite ? 'json_extract(data, ?)' : 'JSON_UNQUOTE(JSON_EXTRACT(data, ?))';
            if ($field['type'] === 'number') {
                $cast = $sqlite ? "CAST($expression AS REAL)" : "CAST($expression AS DECIMAL(30,8))";
                $query = $form->submissions()->selectRaw("COUNT(*) AS answers, MIN($cast) AS minimum, MAX($cast) AS maximum, AVG($cast) AS average", [$path, $path, $path]);
                $query->whereRaw($sqlite ? "json_type(data, ?) IN ('integer', 'real')" : "JSON_TYPE(JSON_EXTRACT(data, ?)) IN ('INTEGER', 'DOUBLE')", [$path]);
                $numeric[] = ['field' => $field, 'stats' => $query->first()->getAttributes()];
            }
            if (in_array($field['type'], ['select', 'radio', 'boolean'], true)) {
                $rows = $form->submissions()->selectRaw("$expression AS label, COUNT(*) AS total", [$path])->whereRaw("$expression IS NOT NULL", [$path])->groupBy('label')->orderByDesc('total')->limit(100)->get()->map(fn ($row) => ['label' => $field['type'] === 'boolean' ? (in_array((string) $row->label, ['true', '1'], true) ? 'Yes' : 'No') : (string) $row->label, 'total' => (int) $row->total])->all();
                $distributions[] = ['field' => $field, 'values' => $rows];
            }
            if ($field['type'] === 'checkboxes' && count($field['options'] ?? [])) {
                $terms = [];
                $bindings = [];
                foreach ($field['options'] as $index => $option) {
                    $terms[] = $sqlite ? "SUM(CASE WHEN EXISTS (SELECT 1 FROM json_each(form_submissions.data, ?) WHERE value = ?) THEN 1 ELSE 0 END) AS o$index" : "SUM(CASE WHEN JSON_CONTAINS(data, JSON_QUOTE(?), ?) THEN 1 ELSE 0 END) AS o$index";
                    array_push($bindings, ...($sqlite ? [$path, $option] : [$option, $path]));
                }
                $counts = $form->submissions()->selectRaw(implode(', ', $terms), $bindings)->first();
                $values = [];
                foreach ($field['options'] as $index => $option) {
                    $values[] = ['label' => $option, 'total' => (int) $counts->{'o'.$index}];
                }
                $distributions[] = ['field' => $field, 'values' => $values];
            }
        }

        return ['total' => $form->submissions()->count(), 'recent' => $form->submissions()->where('submitted_at', '>=', now()->subDays(7))->count(), 'days' => $days, 'numeric' => $numeric, 'distributions' => $distributions];
    }
}

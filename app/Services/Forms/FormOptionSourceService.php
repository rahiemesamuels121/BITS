<?php

namespace App\Services\Forms;

use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class FormOptionSourceService
{
    public function sources(): array
    {
        return config('form-option-sources', []);
    }

    public function catalog(): array
    {
        $catalog = [];
        foreach ($this->sources() as $key => $source) {
            $filters = $this->filterColumns($source);
            foreach ($filters as &$filter) {
                if ($filter['type'] === 'role') {
                    $filter['type'] = 'select';
                    $filter['choices'] = (object) Role::query()->orderBy('name')->pluck('name', 'id')->all();
                }
            }
            unset($filter);
            $catalog[$key] = ['label' => $source['label'], 'columns' => $source['columns'], 'filter_columns' => $filters];
        }

        return $catalog;
    }

    private function filterColumns(array $source): array
    {
        return $source['filter_columns'] ?? array_map(fn ($label) => ['label' => $label, 'type' => 'text'], $source['columns']);
    }

    public function validateConfiguration(mixed $configuration, string $attribute = 'option_source'): array
    {
        if (! is_array($configuration) || array_diff(array_keys($configuration), ['source', 'column', 'filters'])) {
            throw ValidationException::withMessages([$attribute => 'Choose a valid database source and display column.']);
        }

        $sourceKey = $configuration['source'] ?? null;
        $source = is_string($sourceKey) ? ($this->sources()[$sourceKey] ?? null) : null;
        if (! $source) {
            throw ValidationException::withMessages([$attribute => 'Choose an available database source.']);
        }

        $columns = array_keys($source['columns']);
        $column = $configuration['column'] ?? null;
        if (! is_string($column) || ! in_array($column, $columns, true)) {
            throw ValidationException::withMessages([$attribute => 'Choose an available display column.']);
        }

        $filters = $configuration['filters'] ?? [];
        if (! is_array($filters) || count($filters) > 5) {
            throw ValidationException::withMessages([$attribute => 'Use no more than five valid source filters.']);
        }

        $filterColumns = $this->filterColumns($source);
        foreach ($filters as $filter) {
            if (
                ! is_array($filter)
                || array_diff(array_keys($filter), ['column', 'operator', 'value'])
                || ! is_string($filter['column'] ?? null)
                || ! is_string($filter['operator'] ?? null)
                || ! array_key_exists($filter['column'] ?? '', $filterColumns)
                || ! is_string($filter['value'] ?? null)
                || $filter['value'] === ''
                || mb_strlen($filter['value']) > 200
            ) {
                throw ValidationException::withMessages([$attribute => 'Each source filter needs a valid column, match type, and value.']);
            }

            $metadata = $filterColumns[$filter['column']];
            $operators = match ($metadata['type']) {
                'date' => ['equals', 'not_equals', 'greater_than', 'greater_or_equal', 'less_than', 'less_or_equal'],
                'select', 'role', 'number' => ['equals', 'not_equals'],
                default => ['equals', 'not_equals', 'contains'],
            };
            $validValue = match ($metadata['type']) {
                'role' => ctype_digit($filter['value']) && Role::query()->whereKey($filter['value'])->exists(),
                'select' => in_array($filter['value'], array_map('strval', array_keys($metadata['choices'])), true),
                'date' => Validator::make(['date' => $filter['value']], ['date' => 'date_format:Y-m-d'])->passes(),
                'number' => ctype_digit($filter['value']),
                default => trim($filter['value']) !== '',
            };
            if (! in_array($filter['operator'] ?? null, $operators, true) || ! $validValue) {
                throw ValidationException::withMessages([$attribute => 'Choose a valid match type and value for '.$metadata['label'].'.']);
            }
        }

        return ['source' => $sourceKey, 'column' => $column, 'filters' => array_values($filters)];
    }

    public function choices(array $configuration, string $attribute = 'option_source'): array
    {
        $configuration = $this->validateConfiguration($configuration, $attribute);
        $rows = $this->eligibleQuery($configuration)
            ->orderBy($configuration['column'])
            ->orderBy('id')
            ->limit(101)
            ->toBase()
            ->get(array_unique(['id', $configuration['column']]));

        if ($rows->count() > 100) {
            $filterAttribute = $attribute === 'option_source' ? $attribute : 'filters';
            throw ValidationException::withMessages([$filterAttribute => 'More than 100 options match. Add filters to narrow the results.']);
        }

        if ($rows->contains(fn ($row) => mb_strlen((string) $row->{$configuration['column']}) > 200)) {
            $columnAttribute = $attribute === 'option_source' ? $attribute : 'column';
            throw ValidationException::withMessages([$columnAttribute => 'Some values exceed 200 characters. Choose another column or narrow the results.']);
        }

        $options = $rows->map(fn ($row) => (string) $row->id)->values();
        $labels = $rows->mapWithKeys(fn ($row) => [(string) $row->id => (string) $row->{$configuration['column']}]);

        return ['options' => $options->all(), 'option_labels' => (object) $labels->all()];
    }

    public function contains(array $configuration, mixed $value): bool
    {
        if (! is_string($value) || ! ctype_digit($value)) {
            return false;
        }

        return $this->eligibleQuery($configuration)->whereKey($value)->exists();
    }

    private function eligibleQuery(array $configuration): Builder
    {
        $configuration = $this->validateConfiguration($configuration);
        $source = $this->sources()[$configuration['source']];
        $query = $source['model']::query();

        foreach ($configuration['filters'] as $filter) {
            if ($filter['operator'] === 'contains') {
                $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filter['value']);
                $column = $this->wrapColumn($source['model'], $filter['column']);
                $query->whereRaw("$column LIKE ? ESCAPE '!'", ['%'.$escaped.'%']);
            } else {
                $operator = match ($filter['operator']) {
                    'equals' => '=', 'not_equals' => '!=',
                    'greater_than' => '>', 'greater_or_equal' => '>=',
                    'less_than' => '<', 'less_or_equal' => '<=',
                };
                if (($this->filterColumns($source)[$filter['column']]['type'] ?? null) === 'date') {
                    $query->whereDate($filter['column'], $operator, $filter['value']);
                } else {
                    $query->where($filter['column'], $operator, $filter['value']);
                }
            }
        }

        $displayColumn = $this->wrapColumn($source['model'], $configuration['column']);

        return $query->whereNotNull($configuration['column'])->whereRaw("TRIM($displayColumn) <> ''");
    }

    private function wrapColumn(string $model, string $column): string
    {
        return (new $model)->newQuery()->getQuery()->getGrammar()->wrap($column);
    }
}

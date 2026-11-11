<?php

namespace App\Services\Forms;

class FormConditionService
{
    public function visible(array $field, array $answers, array $visibility): bool
    {
        $condition = $field['conditional_rules'] ?? null;
        if (! $condition) {
            return true;
        }
        $key = $condition['field'];
        if (($visibility[$key] ?? false) === false || ! array_key_exists($key, $answers)) {
            return false;
        }
        $answer = $answers[$key];
        if ($answer === null || $answer === '' || $answer === []) {
            return false;
        }
        $expected = $this->scalar($condition['value']);
        if ((is_int($answer) || is_float($answer)) && is_numeric($expected)) {
            $expected = (string) (float) $expected;
        }
        $values = is_array($answer) ? array_map($this->scalar(...), $answer) : [$this->scalar($answer)];

        return match ($condition['operator']) {
            'equals' => in_array($expected, $values, true),
            'not_equals' => ! in_array($expected, $values, true),
            'contains' => is_array($answer) ? in_array($expected, $values, true) : str_contains($values[0], $expected),
            default => false,
        };
    }

    public function scalar(mixed $value): string
    {
        return is_bool($value) ? ($value ? '1' : '0') : (is_scalar($value) ? (string) $value : '');
    }
}

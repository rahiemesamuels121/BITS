<?php

namespace App\Services\Forms;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Models\FormVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ClientResponseService
{
    /**
     * The selected client is an answer value, independent of the respondent's user_id.
     */
    public function query(User $client): Builder
    {
        $forms = Form::query()->whereHas('contract.clients', fn ($query) => $query->where('users.id', $client->id));
        $versions = FormVersion::query()->whereIn('form_id', $forms->select('forms.id'))
            ->get(['id', 'schema_json', 'settings_json']);
        $references = [];
        foreach ($versions as $version) {
            $key = $version->settings_json['client_reference_field'] ?? null;
            if (! is_string($key) || ! preg_match('/^[a-z][a-z0-9_]{0,63}$/', $key)) {
                continue;
            }
            $field = collect($version->schema_json['fields'] ?? [])->firstWhere('field_key', $key);
            if (($field['type'] ?? null) !== 'select' || (isset($field['option_source']) && ($field['option_source']['source'] ?? null) !== 'users')) {
                continue;
            }
            $references[$key][] = $version->id;
        }

        $query = FormSubmission::query()
            ->whereHas('form.contract.clients', fn ($query) => $query->where('users.id', $client->id))
            ->whereHas('version', fn ($query) => $query->whereColumn('form_versions.form_id', 'form_submissions.form_id'));

        if ($references === []) {
            return $query->whereRaw('1 = 0');
        }

        $sqlite = $query->getConnection()->getDriverName() === 'sqlite';
        $match = $sqlite
            ? "json_type(form_submissions.data, ?) IN ('text', 'integer') AND CAST(json_extract(form_submissions.data, ?) AS TEXT) = ?"
            : "JSON_TYPE(JSON_EXTRACT(form_submissions.data, ?)) IN ('STRING', 'INTEGER') AND CAST(JSON_UNQUOTE(JSON_EXTRACT(form_submissions.data, ?)) AS BINARY) = CAST(? AS BINARY)";

        return $query->where(function ($query) use ($references, $match, $client) {
            foreach ($references as $key => $versionIds) {
                $path = '$."'.$key.'"';
                $query->orWhere(fn ($query) => $query->whereIn('form_version_id', $versionIds)
                    ->whereRaw($match, [$path, $path, (string) $client->id]));
            }
        });
    }
}

@php
    $conditions = app(\App\Services\Forms\FormConditionService::class);
    $initial = [];
    foreach ($schema['fields'] as $field) $initial[$field['field_key']] = old('answers.'.$field['field_key'], $field['default_value'] ?? null);
    $visibility = [];
@endphp
<form class="form-renderer fr-accent-{{ $settings['accent'] }} fr-spacing-{{ $settings['spacing'] }} fr-layout-{{ $settings['layout'] }}" method="POST" action="{{ $action }}" enctype="multipart/form-data" data-form-renderer data-preview="{{ $preview ? 'true' : 'false' }}" data-schema="{{ json_encode($schema) }}">
    @csrf
    @if($versionId)<input type="hidden" name="version_id" value="{{ $versionId }}">@endif
    @if(!$preview)<div class="fr-honeypot" aria-hidden="true"><label>Leave this field empty<input name="website" tabindex="-1" autocomplete="off"></label></div>@endif
    @if($settings['show_title'])
        <header class="fr-header">@if($settings['logo_url'])<img class="fr-logo" src="{{ $settings['logo_url'] }}" alt="Form logo" referrerpolicy="no-referrer">@endif<h1>{{ $schema['title'] }}</h1>@if($schema['description'])<p>{{ $schema['description'] }}</p>@endif<p class="fr-required-note">Questions marked <span>*</span> are required.</p></header>
    @endif
    @if($errors->any())<div class="fm-errors" role="alert"><strong>We couldn’t submit your response.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="fr-fields">
        @foreach($schema['fields'] as $field)
            @php
                $key = $field['field_key'];
                $value = $initial[$key];
                $visibility[$key] = $conditions->visible($field, $initial, $visibility);
                $visible = $visibility[$key];
                $rules = $field['validation_rules'] ?? [];
                $required = $field['required'] ?? false;
                $id = 'answer-'.$key;
                $inputType = match($field['type']) { 'phone' => 'tel', 'number', 'email', 'date', 'time' => $field['type'], default => 'text' };
            @endphp
            <div class="fr-field {{ in_array($field['type'], ['heading','description']) ? 'fr-presentation' : '' }}" data-render-field="{{ $key }}" @if(!$visible) hidden @endif>
                @if($field['type'] === 'heading')<h2>{{ $field['label'] }}</h2>@if($field['help_text'])<p>{{ $field['help_text'] }}</p>@endif
                @elseif($field['type'] === 'description')<p class="fr-instruction">{{ $field['label'] }}</p>@if($field['help_text'])<p>{{ $field['help_text'] }}</p>@endif
                @else
                    @if(in_array($field['type'], ['radio','checkboxes','boolean']))<fieldset><legend>{{ $field['label'] }} @if($required)<span class="fr-required" aria-label="required">*</span>@endif</legend>
                    @else<label for="{{ $id }}">{{ $field['label'] }} @if($required)<span class="fr-required" aria-label="required">*</span>@endif</label>@endif
                    @if($field['type'] === 'textarea')
                        <textarea id="{{ $id }}" name="answers[{{ $key }}]" placeholder="{{ $field['placeholder'] }}" rows="4" minlength="{{ $rules['min_length'] ?? 0 }}" maxlength="{{ $rules['max_length'] ?? 10000 }}" @required($required && $visible) @disabled(!$visible)>{{ is_scalar($value) ? $value : '' }}</textarea>
                    @elseif($field['type'] === 'select')
                        <select id="{{ $id }}" name="answers[{{ $key }}]" @required($required && $visible) @disabled(!$visible)><option value="">{{ $field['placeholder'] ?: 'Select an option' }}</option>@foreach($field['options'] ?? [] as $option)<option value="{{ $option }}" @selected($value === $option)>{{ $field['option_labels'][$option] ?? $option }}</option>@endforeach</select>
                    @elseif(in_array($field['type'], ['radio','checkboxes','boolean']))
                        @php($choices = $field['type'] === 'boolean' ? ['1' => 'Yes', '0' => 'No'] : array_combine($field['options'] ?? [], $field['options'] ?? []))
                        @foreach($choices as $option => $label)
                            @php($checked = $field['type'] === 'checkboxes' ? in_array((string)$option, is_array($value) ? $value : [], true) : ($value !== null && $conditions->scalar($value) === (string)$option))
                            <label class="fr-choice"><input type="{{ $field['type'] === 'checkboxes' ? 'checkbox' : 'radio' }}" name="answers[{{ $key }}]{{ $field['type'] === 'checkboxes' ? '[]' : '' }}" value="{{ $option }}" @checked($checked) @required($required && $visible && $field['type'] !== 'checkboxes') @disabled(!$visible)> <span>{{ $label }}</span></label>
                        @endforeach
                    @elseif($field['type'] === 'file')
                        <input id="{{ $id }}" type="file" name="answers[{{ $key }}]" accept="{{ implode(',', array_map(fn($ext) => '.'.$ext, ($rules['allowed_types'] ?? []) ?: ['pdf','jpg','png'])) }}" @required($required && $visible) @disabled(!$visible)><p class="fr-file-hint">Maximum {{ number_format(($rules['max_size'] ?? 2048) / 1024, 1) }} MB. Uploads are stored privately.</p>
                    @else
                        <input id="{{ $id }}" type="{{ $inputType }}" name="answers[{{ $key }}]" value="{{ is_scalar($value) ? $conditions->scalar($value) : '' }}" placeholder="{{ $field['placeholder'] }}" @if($field['type'] === 'number') step="any" min="{{ $rules['min'] ?? -1000000000000000 }}" max="{{ $rules['max'] ?? 1000000000000000 }}" @else minlength="{{ $rules['min_length'] ?? 0 }}" maxlength="{{ $rules['max_length'] ?? ($field['type'] === 'email' ? 254 : 1000) }}" @endif @required($required && $visible) @disabled(!$visible)>
                    @endif
                    @if(in_array($field['type'], ['radio','checkboxes','boolean']))</fieldset>@endif
                    @if($field['help_text'])<p class="fr-help" id="help-{{ $key }}">{{ $field['help_text'] }}</p>@endif
                    @error('answers.'.$key)<p class="fr-field-error" role="alert">{{ $message }}</p>@enderror
                @endif
            </div>
        @endforeach
    </div>
    <div class="fr-footer"><button class="admin-button admin-button-primary fr-submit" type="submit">{{ $settings['submit_text'] }} <span aria-hidden="true">→</span></button><p data-preview-feedback role="status">{{ $preview ? 'Preview mode — responses will not be saved.' : 'Your response will be shared with the form owner.' }}</p></div>
</form>

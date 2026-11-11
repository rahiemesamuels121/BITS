<nav class="fm-form-nav" aria-label="Form navigation">
    <a href="{{ route('forms.index') }}">← All forms</a>
    <a href="{{ route('forms.edit', $form) }}" @if(request()->routeIs('forms.edit', 'forms.settings')) aria-current="page" @endif>Builder</a>
    <a href="{{ route('forms.preview', $form) }}" target="_blank" rel="noopener">Preview ↗</a>
    <a href="{{ route('forms.submissions.index', $form) }}" @if(request()->routeIs('forms.submissions.*')) aria-current="page" @endif>Submissions</a>
    <a href="{{ route('forms.analytics', $form) }}" @if(request()->routeIs('forms.analytics')) aria-current="page" @endif>Analytics</a>
    <span class="fm-status fm-status-{{ $form->status }}">{{ ucfirst($form->status) }}</span>
</nav>

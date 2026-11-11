@extends('layouts.admin')
@section('title', 'Form Preview — BITS')
@section('content')
    @include('forms.partials.nav')
    <div class="admin-page-heading"><div><p class="admin-eyebrow">A RESPONDENT’S VIEW</p><h1>Form Preview</h1><p>Preview your saved draft. This page never creates submissions.</p></div><div class="fm-device-switch"><button class="admin-button" type="button" aria-pressed="true" data-preview-device="desktop">Desktop</button><button class="admin-button" type="button" aria-pressed="false" data-preview-device="mobile">Mobile</button></div></div>
    <div class="fm-preview-frame" data-preview-frame><x-form-renderer :schema="$schema" :settings="$settings" :preview="true" /></div>
@endsection

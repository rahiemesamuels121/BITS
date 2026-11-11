@extends('layouts.form')
@section('title', $schema['title'].' — BITS Forms')
@section('content')
    <x-form-renderer :schema="$schema" :settings="$settings" :action="route('public-forms.submit', $form->slug)" :version-id="$version->id" />
@endsection

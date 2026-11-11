@extends('layouts.form')
@section('title', 'Form unavailable — BITS Forms')
@section('content')<section class="fr-message"><span class="fm-empty-icon">@include('partials.admin.icon', ['name' => 'ticket'])</span><h1>This form is unavailable.</h1><p>{{ $reason }}</p><p>Please contact the person who shared the form if you need assistance.</p></section>@endsection

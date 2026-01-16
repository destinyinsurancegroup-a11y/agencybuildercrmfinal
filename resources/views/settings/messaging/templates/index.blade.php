@extends('settings.layout')

@php
    $settingsPage = 'templates';
@endphp

@section('settings_content')
<div class="panel-card">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="mb-0" style="font-weight:900;">Message Templates</h2>
    </div>

    <p class="text-muted mb-0">
        Create reusable SMS and Email templates (with variables like <code>{{'{{first_name}}'}}</code>).
    </p>
</div>
@endsection

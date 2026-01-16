@extends('settings.layout')

@php
    $settingsPage = 'templates';
@endphp

@section('settings_content')
<div class="panel-card">
    <div class="panel-card-body">
        <h2 class="mb-2" style="font-weight:900;">Message Templates</h2>
        <p class="text-muted mb-0">
            Create reusable SMS and Email templates (with variables like <code>{{'{{first_name}}'}}</code>).
        </p>
    </div>
</div>
@endsection

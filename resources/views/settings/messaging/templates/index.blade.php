@extends('settings.layout')

@php
    $settingsPage = 'templates';
@endphp

@section('settings_content')
<div class="panel-card">
    <div class="panel-card-header">
        <div>
            <h2 class="mb-0">Message Templates</h2>
            <p class="mb-0" style="font-size:14px; font-weight:600;">
                Reusable SMS and Email messages you can send or use in drips.
            </p>
        </div>

        <button class="btn btn-abc-gold" disabled>
            + New Template
        </button>
    </div>

    <div class="panel-card-body">
        <p class="text-muted mb-0">
            No templates yet. You’ll be able to create reusable SMS and Email templates here.
        </p>
    </div>
</div>
@endsection

@extends('settings.layout')

@php
    $settingsPage = 'drips';
@endphp

@section('settings_content')
<div class="panel-card">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="mb-0" style="font-weight:900;">Drip Campaigns</h2>
    </div>

    <p class="text-muted mb-0">
        Configure onboarding and milestone campaigns.
    </p>
</div>
@endsection

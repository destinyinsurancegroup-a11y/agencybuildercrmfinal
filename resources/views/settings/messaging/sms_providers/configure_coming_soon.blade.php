@extends('settings.layout')

@php
    $settingsPage = 'sms_providers';
@endphp

@section('settings_content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h2 class="mb-0" style="font-weight:900;">Configure Provider</h2>
        <div class="text-muted" style="font-size: 13px;">
            Provider: {{ $providerName ?? 'Provider' }}
        </div>
    </div>

    <a href="{{ route('settings.messaging.sms_providers') }}" class="btn btn-abc-outline">
        ← Back to Providers
    </a>
</div>

<div class="panel-card">
    <div class="panel-card-header">
        <span>Provider Settings: {{ $providerName ?? 'Provider' }}</span>
        <span class="badge bg-secondary">Coming soon</span>
    </div>

    <div class="panel-card-body">
        <p class="text-muted">
            {{ $providerName ?? 'This provider' }} will plug into the same universal connection system.
            No redesign will be needed later — we’ll add an adapter and a provider-specific form.
        </p>

        <ul class="text-muted" style="font-size: 14px;">
            <li>Same provider_connections table</li>
            <li>Same message queue and sending worker</li>
            <li>Same “default provider” logic</li>
        </ul>
    </div>
</div>
@endsection

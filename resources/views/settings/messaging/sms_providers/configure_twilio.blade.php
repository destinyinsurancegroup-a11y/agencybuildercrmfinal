@extends('settings.layout')

@php
    // Highlight "SMS Providers" in the left settings menu
    $settingsPage = 'sms_providers';
@endphp

@section('settings_content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h2 class="mb-0" style="font-weight:900;">Configure Provider</h2>
        <div class="text-muted" style="font-size: 13px;">
            Provider: {{ $providerName ?? 'Twilio' }}
        </div>
    </div>

    <a href="{{ route('settings.messaging.sms_providers') }}" class="btn btn-abc-outline">
        ← Back to Providers
    </a>
</div>

<div class="panel-card">
    <div class="panel-card-header">
        <span>Provider Settings: {{ $providerName ?? 'Twilio' }}</span>
        <button type="button" class="btn btn-abc-gold" disabled>Test Connection</button>
    </div>

    <div class="panel-card-body">
        <p class="text-muted mb-4">
            This page is provider-specific because each SMS company uses different credentials.
            Twilio is supported first, but the system remains universal.
        </p>

        {{-- Placeholder form (we wire this to database + encryption next) --}}
        <div class="mb-3">
            <label class="form-label">Account SID</label>
            <input type="text" class="form-control" placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxx" disabled>
        </div>

        <div class="mb-3">
            <label class="form-label">Auth Token</label>
            <input type="password" class="form-control" placeholder="••••••••••••••••••••••" disabled>
        </div>

        <div class="mb-4">
            <label class="form-label">Messaging Service SID (recommended) or From Number</label>
            <input type="text" class="form-control" placeholder="MGxxxxxxxxxxxxxxxxxxxxxxxxxxxx or +15551234567" disabled>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-abc-gold" disabled>Save</button>
            <button type="button" class="btn btn-abc-outline" disabled>Disable</button>
        </div>

        <div class="text-muted mt-3" style="font-size: 13px;">
            Next step: we’ll enable this form to save encrypted credentials in the database (BYOT).
        </div>
    </div>
</div>
@endsection

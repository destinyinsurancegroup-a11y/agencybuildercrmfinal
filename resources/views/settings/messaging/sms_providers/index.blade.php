@extends('settings.layout')

@php
    $settingsPage = 'sms_providers';
@endphp

@section('settings_content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="mb-0" style="font-weight:900;">SMS Providers</h2>
    <button type="button" class="btn btn-abc-gold" disabled>Add Provider</button>
</div>

<div class="panel-card">
    <div class="panel-card-body">
        <p class="text-muted mb-4">
            Connect your SMS provider once. The CRM will use your default provider automatically.
        </p>

        <div class="row g-3">
            @foreach($providers as $p)
                <div class="col-lg-6">
                    <div class="p-3 border rounded-3 d-flex align-items-center justify-content-between">
                        <div>
                            <div style="font-weight:900; font-size: 18px;">{{ $p['name'] }}</div>
                            <div class="text-muted" style="font-size: 13px;">
                                {{ $p['supports_now'] ? 'Supported now' : 'Coming soon' }}
                            </div>
                        </div>

                        <div>
                            @if($p['supports_now'])
                                <a class="btn btn-abc-outline"
                                   href="{{ route('settings.messaging.sms_providers.configure', ['provider' => $p['key']]) }}">
                                    Manage
                                </a>
                            @else
                                <button class="btn btn-abc-outline" disabled>Manage</button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
</div>
@endsection

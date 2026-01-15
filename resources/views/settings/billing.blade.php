@extends('settings.layout')

@section('settings_content')
    <h2 class="settings-section-title">Billing</h2>
    <p class="settings-subtitle">Manage your plan and subscription.</p>

    <div class="p-3 border rounded-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <div class="fw-bold">Current Plan</div>
                <div class="text-muted" style="font-size: 13px;">Tier 1</div>
            </div>

            <button class="btn btn-abc-gold" type="button">
                Manage Subscription
            </button>
        </div>
    </div>
@endsection

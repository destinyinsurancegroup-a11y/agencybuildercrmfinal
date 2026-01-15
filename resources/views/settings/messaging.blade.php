@extends('settings.layout')

@section('settings_content')
    <h2 class="settings-section-title">Messaging</h2>
    <p class="settings-subtitle">Connect your SMS provider and manage templates/campaigns.</p>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="p-3 border rounded-3">
                <div class="fw-bold mb-1">SMS Provider (BYOT Twilio)</div>
                <div class="text-muted" style="font-size: 13px;">
                    Connect your Twilio Account SID + Auth Token.
                </div>
                <button class="btn btn-abc-gold mt-3" type="button">
                    Connect Twilio
                </button>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="p-3 border rounded-3">
                <div class="fw-bold mb-1">Message Templates</div>
                <div class="text-muted" style="font-size: 13px;">
                    Save reusable prewritten texts to use anywhere in the CRM.
                </div>
                <button class="btn btn-abc-outline mt-3" type="button">
                    Manage Templates
                </button>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="p-3 border rounded-3">
                <div class="fw-bold mb-1">Drip Campaigns</div>
                <div class="text-muted" style="font-size: 13px;">
                    Automate follow-ups using templates + timing.
                </div>
                <button class="btn btn-abc-outline mt-3" type="button">
                    Manage Campaigns
                </button>
            </div>
        </div>
    </div>
@endsection

@extends('settings.layout')

@php
    $settingsPage = 'billing';
@endphp

@section('settings_content')
<div class="panel-card">
    <div class="panel-card-header">
        <div>Billing</div>
    </div>

    <div class="panel-card-body">
        <p class="text-muted">Manage your plan and subscription.</p>

        <div class="p-3 border rounded-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <div style="font-weight:900;">Current Plan</div>
                    <div class="text-muted" style="font-size: 13px;">Tier 1</div>
                </div>

                <button type="button" class="btn btn-abc-gold">Manage Subscription</button>
            </div>
        </div>
    </div>
</div>
@endsection

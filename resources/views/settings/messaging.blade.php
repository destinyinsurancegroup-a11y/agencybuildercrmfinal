@extends('settings.layout')

@php
    $settingsPage = 'sms_providers';
@endphp

@section('settings_content')
<div class="panel-card">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="mb-0" style="font-weight:900;">SMS Providers</h2>
        <button type="button" class="btn btn-light border" style="border-radius:10px; font-weight:800;">
            Documentation &nbsp;›
        </button>
    </div>

    <div class="panel-card">
        <div class="panel-card-header">
            <div class="d-flex align-items-center gap-2">
                <span style="background:#fff; width:28px; height:28px; border-radius:999px; display:inline-flex; align-items:center; justify-content:center; font-weight:900;">
                    🙂
                </span>
                <span>Twilio Settings</span>
            </div>

            <button type="button" class="btn btn-abc-gold">Test Connection</button>
        </div>

        <div class="panel-card-body">
            <h5 style="font-weight:900;">Credentials</h5>
            <hr>

            <div class="mb-3">
                <label class="form-label">Account SID</label>
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                    <button class="btn btn-light border" type="button">⧉</button>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Auth Token</label>
                <div class="input-group">
                    <input type="password" class="form-control" placeholder="••••••••••••••••••••••">
                    <button class="btn btn-light border" type="button">👁</button>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Messaging Service SID or From Number</label>
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="MGxxxxxxxxxxxxxxxxxxxxxxxxxxxx or +15551234567">
                    <button class="btn btn-light border" type="button">Copy URL</button>
                </div>
            </div>

            <h5 style="font-weight:900;">Status &amp; Webhooks</h5>
            <hr>

            <div class="mb-2" style="font-weight:800;">
                Status: <span style="color:#16a34a;">● Active</span>
                <span class="ms-3 text-muted">Verify ID*</span>
            </div>
            <div class="text-muted mb-3" style="font-size: 13px;">
                Last verified at: —
            </div>

            <div class="mb-2">
                <div class="input-group">
                    <span class="input-group-text" style="font-weight:800;">Status Callback:</span>
                    <input class="form-control" value="/webhooks/twilio/sms/status" readonly>
                    <button class="btn btn-light border" type="button">Copy URL</button>
                </div>
            </div>

            <div class="mb-4">
                <div class="input-group">
                    <span class="input-group-text" style="font-weight:800;">Inbound SMS:</span>
                    <input class="form-control" value="/webhooks/twilio/sms/inbound" readonly>
                    <button class="btn btn-light border" type="button">Copy URL</button>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="button" class="btn btn-abc-gold">Save</button>
                <button type="button" class="btn btn-abc-outline">Disable Sending</button>
            </div>
        </div>
    </div>
</div>
@endsection

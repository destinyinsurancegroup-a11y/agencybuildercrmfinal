@extends('settings.layout')

@php
    $settingsPage = 'profile';
@endphp

@section('settings_content')
<div class="panel-card">
    <div class="panel-card-header">
        <div>Profile</div>
    </div>

    <div class="panel-card-body">
        <p class="text-muted mb-4">Update your account information.</p>

        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" class="form-control" value="{{ $user->name ?? '' }}" readonly>
        </div>

        <div class="mb-4">
            <label class="form-label">Email</label>
            <input type="text" class="form-control" value="{{ $user->email ?? '' }}" readonly>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-abc-gold">Update Email</button>
            <button type="button" class="btn btn-abc-outline">Change Password</button>
        </div>

        <div class="text-muted mt-3" style="font-size: 13px;">
            Tier 1 note: single-user account.
        </div>
    </div>
</div>
@endsection

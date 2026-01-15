@extends('settings.layout')

@section('settings_content')
    <h2 class="settings-section-title">Profile</h2>
    <p class="settings-subtitle">Update your account information.</p>

    <div class="row">
        <div class="col-lg-8">

            <div class="mb-3">
                <label class="form-label fw-bold">Name</label>
                <input type="text" class="form-control" value="{{ $user->name ?? '' }}" readonly>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Email</label>
                <input type="text" class="form-control" value="{{ $user->email ?? '' }}" readonly>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-abc-gold">
                    Update Email
                </button>

                <button type="button" class="btn btn-abc-outline">
                    Change Password
                </button>
            </div>

            <div class="settings-help">
                Tier 1 note: single-user account.
            </div>

        </div>
    </div>
@endsection

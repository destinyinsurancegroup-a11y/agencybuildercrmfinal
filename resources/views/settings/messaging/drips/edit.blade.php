@extends('settings.layout')

@php
    $settingsPage = 'drips';

    /** @var \App\Models\DripCampaign $campaign */

    // Must match controller campaignTypes() keys
    $types = $types ?? [
        'onboarding_timeline' => '90-Day Welcome (Timeline)',
        'date_holiday'        => 'Holiday',
        'date_birthday'       => 'Birthday',
        'policy_anniversary'  => 'Policy Anniversary (Initial Draft Date)',
        // Backward compatibility if any old rows exist:
        'date_client_anniversary' => 'Policy Anniversary (Legacy Type)',
    ];

    $statusOptions = [
        'active'   => 'Active',
        'paused'   => 'Paused',
        'archived' => 'Archived',
    ];

    $channelOptions = [
        'sms'   => 'SMS',
        'email' => 'Email',
        'mixed' => 'Mixed (later)',
    ];
@endphp

@section('settings_content')
<div class="panel-card mb-3">
    <div class="panel-card-body d-flex align-items-start justify-content-between">
        <div>
            <h2 class="mb-2" style="font-weight:900;">Edit Drip Campaign</h2>
            <p class="text-muted mb-0">
                Update campaign settings. Steps builder comes next.
            </p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('settings.messaging.drips.index') }}"
               class="btn btn-sm btn-outline-secondary">
                ← Back
            </a>
        </div>
    </div>
</div>

<div class="panel-card">
    <div class="panel-card-body">

        @if(session('success'))
            <div class="alert alert-success mb-3">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <div style="font-weight:800;">Please fix the errors below.</div>
            </div>
        @endif

        <form method="POST" action="{{ route('settings.messaging.drips.update', $campaign->id) }}">
            @csrf
            @method('PUT')

            {{-- Name --}}
            <div class="mb-3">
                <label class="form-label" style="font-weight:800;">Campaign Name</label>
                <input
                    type="text"
                    name="name"
                    class="form-control @error('name') is-invalid @enderror"
                    maxlength="150"
                    value="{{ old('name', $campaign->name) }}"
                    required
                >
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Type --}}
            <div class="mb-3">
                <label class="form-label" style="font-weight:800;">Campaign Type</label>
                <select
                    name="type"
                    class="form-select @error('type') is-invalid @enderror"
                    required
                >
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}"
                            {{ old('type', $campaign->type) === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                @error('type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                <div class="form-text text-muted">
                    <div><strong>Timeline:</strong> sends based on delay days after enrollment.</div>
                    <div><strong>Holiday:</strong> sends on a fixed MM-DD date.</div>
                    <div><strong>Birthday:</strong> sends on each contact’s birthday.</div>
                    <div><strong>Policy Anniversary:</strong> sends based on <em>Initial Draft Date</em> (Book of Business only).</div>
                </div>
            </div>

            {{-- Status --}}
            <div class="mb-3">
                <label class="form-label" style="font-weight:800;">Status</label>
                <select
                    name="status"
                    class="form-select @error('status') is-invalid @enderror"
                    required
                >
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}"
                            {{ old('status', $campaign->status) === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                @if(($campaign->status ?? null) === 'active')
                    <div class="form-text text-muted">
                        Active campaigns will schedule/dispatch messages for enrolled contacts.
                    </div>
                @endif
            </div>

            {{-- Channel mode --}}
            <div class="mb-3">
                <label class="form-label" style="font-weight:800;">Channel Mode</label>
                <select
                    name="channel_mode"
                    class="form-select @error('channel_mode') is-invalid @enderror"
                    required
                >
                    @foreach($channelOptions as $value => $label)
                        <option value="{{ $value }}"
                            {{ old('channel_mode', $campaign->channel_mode) === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                @error('channel_mode')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                <div class="form-text text-muted">
                    MVP note: channel is primarily driven by the selected template (SMS vs Email).
                    Channel Mode is stored for future mixed-mode behavior.
                </div>
            </div>

            {{-- Description --}}
            <div class="mb-3">
                <label class="form-label" style="font-weight:800;">Description (optional)</label>
                <textarea
                    name="description"
                    class="form-control @error('description') is-invalid @enderror"
                    rows="3"
                    maxlength="5000"
                    placeholder="Internal notes about this campaign (not shown to clients)."
                >{{ old('description', $campaign->description) }}</textarea>

                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Actions --}}
            <div class="d-flex gap-2">
                <button
                    type="submit"
                    class="btn"
                    style="background:#d4af37;color:#111;font-weight:900;border:1px solid #b8922e;"
                >
                    Save Changes
                </button>

                <a href="{{ route('settings.messaging.drips.index') }}"
                   class="btn btn-outline-secondary">
                    Cancel
                </a>

                {{-- Steps builder comes next phase (route not created yet) --}}
                <button type="button" class="btn btn-outline-secondary" disabled
                        title="Steps builder coming next">
                    Manage Steps
                </button>
            </div>
        </form>

    </div>
</div>
@endsection

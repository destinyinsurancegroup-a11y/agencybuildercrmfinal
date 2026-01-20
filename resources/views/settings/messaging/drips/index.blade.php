@extends('settings.layout')

@php
    $settingsPage = 'drips';

    // Map DB type -> label (must match controller labels)
    $typeLabels = [
        'onboarding_timeline' => '90-Day Welcome (Timeline)',
        'date_holiday'        => 'Holiday',
        'date_birthday'       => 'Birthday',
        'policy_anniversary'  => 'Policy Anniversary (Initial Draft Date)',
        // Backward compatibility if any old rows exist:
        'date_client_anniversary' => 'Policy Anniversary (Legacy Type)',
    ];

    $statusLabels = [
        'active'   => 'Active',
        'paused'   => 'Paused',
        'archived' => 'Archived',
    ];

    $channelLabels = [
        'sms'   => 'SMS',
        'email' => 'Email',
        'mixed' => 'Mixed',
    ];
@endphp

@section('settings_content')
<div class="panel-card mb-3">
    <div class="panel-card-body d-flex align-items-start justify-content-between">
        <div>
            <h2 class="mb-2" style="font-weight:900;">Drip Campaigns</h2>
            <p class="text-muted mb-0">
                Configure onboarding and milestone campaigns.
            </p>
        </div>

        <div>
            <a href="{{ route('settings.messaging.drips.create') }}"
               class="btn btn-sm"
               style="background:#d4af37;color:#111;font-weight:800;border:1px solid #b8922e;">
                + Create Campaign
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

        @if(isset($campaigns) && $campaigns->count() > 0)
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr class="text-muted">
                            <th style="width:34%;">Campaign</th>
                            <th style="width:22%;">Type</th>
                            <th style="width:12%;">Status</th>
                            <th style="width:12%;">Channel</th>
                            <th style="width:20%;" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($campaigns as $c)
                            @php
                                $type = $c->type ?? '';
                                $status = $c->status ?? 'paused';
                                $channel = $c->channel_mode ?? 'sms';

                                $typeText = $typeLabels[$type] ?? $type;
                                $statusText = $statusLabels[$status] ?? $status;
                                $channelText = $channelLabels[$channel] ?? $channel;

                                // Small badge styling (black/gold, no external dependencies)
                                $badgeStyle = 'display:inline-block;padding:.2rem .5rem;border-radius:999px;font-weight:800;font-size:12px;';
                                $statusStyle = match($status) {
                                    'active' => $badgeStyle.'background:#153b2e;color:#bff5dd;border:1px solid #2f7a5f;',
                                    'paused' => $badgeStyle.'background:#2a2a2a;color:#f0d27a;border:1px solid #444;',
                                    'archived' => $badgeStyle.'background:#1f1f1f;color:#9aa0a6;border:1px solid #333;',
                                    default => $badgeStyle.'background:#2a2a2a;color:#f0d27a;border:1px solid #444;',
                                };
                            @endphp

                            <tr>
                                <td>
                                    <div style="font-weight:900;">
                                        {{ $c->name }}
                                    </div>
                                    @if(!empty($c->description))
                                        <div class="text-muted small">
                                            {{ \Illuminate\Support\Str::limit($c->description, 90) }}
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    <div style="font-weight:700;">{{ $typeText }}</div>
                                </td>

                                <td>
                                    <span style="{{ $statusStyle }}">{{ $statusText }}</span>
                                </td>

                                <td>
                                    <span class="text-muted" style="font-weight:800;">
                                        {{ $channelText }}
                                    </span>
                                </td>

                                <td class="text-end">
                                    <a href="{{ route('settings.messaging.drips.edit', $c->id) }}"
                                       class="btn btn-sm btn-outline-secondary">
                                        Edit
                                    </a>

                                    <a href="{{ route('settings.messaging.drips.steps', $c->id) }}"
                                       class="btn btn-sm btn-outline-secondary">
                                        Steps
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-muted">
                No drip campaigns yet. Click <strong>Create Campaign</strong> to get started.
            </div>
        @endif

    </div>
</div>
@endsection

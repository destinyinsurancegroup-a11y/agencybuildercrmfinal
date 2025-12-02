@extends('layouts.app')

@section('content')
<style>
    .service-archive-card {
        background: #ffffff;
        border-radius: 18px;
        padding: 22px;
        box-shadow:
            0 18px 30px -12px rgba(0,0,0,0.35),
            0 8px 16px -8px rgba(0,0,0,0.18);
        border: 1px solid #e5e7eb;
    }

    .service-archive-header {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 18px;
    }

    .btn-gold {
        background: #c9a227;
        color: #111827;
        border: none;
        padding: 6px 10px;
        font-weight: 600;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.20);
        font-size: 12px;
        cursor: pointer;
        white-space: nowrap;
        text-decoration: none;
    }

    .btn-gold:hover {
        background: #b5901f;
        color: #111827;
    }

    .btn-outline-gold {
        border-radius: 8px;
        border: 1px solid #c9a227;
        padding: 6px 10px;
        font-weight: 600;
        font-size: 12px;
        background: #fff;
        color: #111827;
        cursor: pointer;
        text-decoration: none;
    }

    .btn-outline-gold.active {
        background: #c9a227;
        color: #111827;
    }
</style>

<div class="dashboard-page">
    <div class="row g-4">
        <div class="col-12">
            <div class="service-archive-card">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="service-archive-header">
                        Service Archive
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('service.archive') }}"
                           class="btn-outline-gold {{ ($filter ?? 'all') === 'all' ? 'active' : '' }}">
                            All Archived
                        </a>

                        <a href="{{ route('service.archive.not-saved') }}"
                           class="btn-outline-gold {{ ($filter ?? 'all') === 'not-saved' ? 'active' : '' }}">
                            Business Not Saved
                        </a>

                        <a href="{{ route('service.index') }}" class="btn-gold">
                            Back to Service
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Client</th>
                                <th>Policy</th>
                                <th>Status</th>
                                <th>Archived At</th>
                                <th>Email / Phone</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($clients as $client)
                                @php
                                    $status = $client->service_status;
                                    $name = trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? ''));
                                @endphp

                                <tr>
                                    <td>
                                        <a href="{{ route('service.index', ['selected' => $client->id]) }}"
                                           class="text-gold text-decoration-none">
                                            {{ $name ?: 'Unnamed' }}
                                        </a>
                                    </td>

                                    <td>
                                        {{ $client->policy_type ?: '—' }}
                                        @if($client->carrier)
                                            <br>
                                            <small class="text-muted">{{ $client->carrier }}</small>
                                        @endif
                                    </td>

                                    <td>
                                        @if(in_array($status, ['Saved', 'Back on Books']))
                                            <span class="badge bg-success">
                                                {{ $status }}
                                            </span>
                                        @elseif(in_array($status, ['Not Interested', 'Cancelled']))
                                            <span class="badge bg-danger">
                                                {{ $status }}
                                            </span>
                                        @elseif($client->service_archived_at)
                                            <span class="badge bg-secondary">
                                                Archived
                                            </span>
                                        @else
                                            <span class="badge bg-light text-dark">
                                                {{ $status ?? '—' }}
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($client->service_archived_at)
                                            {{ \Carbon\Carbon::parse($client->service_archived_at)->format('m/d/Y h:i A') }}
                                        @else
                                            —
                                        @endif
                                    </td>

                                    <td>
                                        @if($client->email)
                                            <div>{{ $client->email }}</div>
                                        @endif
                                        @if($client->phone)
                                            <div>{{ $client->phone }}</div>
                                        @endif
                                        @if(!$client->email && !$client->phone)
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    <td style="max-width: 260px;">
                                        @if($client->notes)
                                            <span class="text-truncate d-inline-block" style="max-width: 250px;">
                                                {{ $client->notes }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        No archived service records found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $clients->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

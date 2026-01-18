@extends('settings.layout')

@php
    $settingsPage = 'templates';
@endphp

@section('settings_content')
<div class="panel-card">
    <div class="panel-card-header">
        <div>
            <h2 class="mb-0">Email Templates</h2>
            <p class="mb-0" style="font-size:14px; font-weight:600;">
                Your library of reusable email messages.
            </p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('settings.messaging.templates.choose') }}" class="btn btn-abc-outline">
                ← Back
            </a>
            <a href="{{ route('settings.messaging.templates.create', ['channel' => 'email']) }}" class="btn btn-abc-gold">
                + New Email Template
            </a>
        </div>
    </div>

    <div class="panel-card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($templates->count() === 0)
            <p class="text-muted mb-0" style="font-weight:600;">
                No email templates yet. Click <strong>+ New Email Template</strong> to create one.
            </p>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="font-weight:900;">Name</th>
                            <th style="font-weight:900;">Channel</th>
                            <th style="font-weight:900;">Status</th>
                            <th style="font-weight:900;">Last Updated</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($templates as $t)
                            <tr>
                                <td style="font-weight:800;">
                                    <a href="{{ route('settings.messaging.templates.edit', $t->id) }}"
                                       class="text-decoration-none"
                                       style="font-weight:900;">
                                        {{ $t->name }}
                                    </a>

                                    @if($t->subject)
                                        <div class="text-muted" style="font-size:13px; font-weight:600;">
                                            Subject: {{ $t->subject }}
                                        </div>
                                    @endif

                                    <div class="text-muted mt-1" style="font-size:13px;">
                                        <a href="{{ route('settings.messaging.templates.edit', $t->id) }}"
                                           class="text-decoration-none"
                                           style="font-weight:800;">
                                            View / Edit
                                        </a>
                                    </div>
                                </td>

                                <td>
                                    <span class="badge bg-secondary">Email</span>
                                </td>

                                <td>
                                    @if($t->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-light text-muted">Inactive</span>
                                    @endif
                                </td>

                                <td class="text-muted" style="font-weight:600;">
                                    {{ optional($t->updated_at)->diffForHumans() ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $templates->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

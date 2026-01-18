@extends('settings.layout')

@php
    $settingsPage = 'templates';
@endphp

@section('settings_content')
<div class="panel-card">
    <div class="panel-card-header">
        <div>
            <h2 class="mb-0">Text Templates</h2>
            <p class="mb-0" style="font-size:14px; font-weight:600;">
                Your library of reusable SMS messages.
            </p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('settings.messaging.templates.choose') }}" class="btn btn-abc-outline">
                ← Back
            </a>
            <a href="{{ route('settings.messaging.templates.create', ['channel' => 'sms']) }}" class="btn btn-abc-gold">
                + New Text Template
            </a>
        </div>
    </div>

    <div class="panel-card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($templates->count() === 0)
            <p class="text-muted mb-0" style="font-weight:600;">
                No text templates yet. Click <strong>+ New Text Template</strong> to create one.
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

                                    {{-- SMS has no subject; show a tiny preview line instead --}}
                                    @if($t->body)
                                        <div class="text-muted" style="font-size:13px; font-weight:600;">
                                            {{ \Illuminate\Support\Str::limit(strip_tags($t->body), 70) }}
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
                                    <span class="badge bg-dark">SMS</span>
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

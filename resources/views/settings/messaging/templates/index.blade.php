@extends('settings.layout')

@php
    $settingsPage = 'templates';
@endphp

@section('settings_content')
<div class="panel-card">
    <div class="panel-card-header">
        <div>
            <h2 class="mb-0">Message Templates</h2>
            <p class="mb-0" style="font-size:14px; font-weight:600;">
                Reusable SMS and Email messages you can send or use in drips.
            </p>
        </div>

        <button class="btn btn-abc-gold" disabled>
            + New Template
        </button>
    </div>

    <div class="panel-card-body">
        @if($templates->count() === 0)
            <p class="text-muted mb-0">
                No templates yet. Once seeded or created, they’ll appear here.
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
                                    {{ $t->name }}
                                    @if($t->channel === 'email' && $t->subject)
                                        <div class="text-muted" style="font-size:13px; font-weight:600;">
                                            Subject: {{ $t->subject }}
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    @if($t->channel === 'sms')
                                        <span class="badge bg-dark">SMS</span>
                                    @else
                                        <span class="badge bg-secondary">Email</span>
                                    @endif
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

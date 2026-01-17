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

        {{-- ✅ Make this a real link (was a disabled button) --}}
        <a href="{{ route('settings.messaging.templates.create') }}"
           class="btn btn-abc-gold">
            + New Template
        </a>
    </div>

    <div class="panel-card-body">
        @if(session('success'))
            <div class="alert alert-success" style="font-weight:700;">
                {{ session('success') }}
            </div>
        @endif

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
                                    {{-- ✅ Make name clickable (go to edit if route exists) --}}
                                    @if(\Illuminate\Support\Facades\Route::has('settings.messaging.templates.edit'))
                                        <a href="{{ route('settings.messaging.templates.edit', $t->id) }}"
                                           style="text-decoration:none; color:inherit;">
                                            {{ $t->name }}
                                        </a>
                                    @else
                                        {{ $t->name }}
                                    @endif

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
                                    {{ $t->updated_at ? $t->updated_at->diffForHumans() : '—' }}
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

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
                Choose a library to manage your Email or Text templates.
            </p>
        </div>
    </div>

    <div class="panel-card-body">
        <div class="row g-3">

            {{-- Email Templates option --}}
            <div class="col-12 col-md-6">
                <a href="{{ route('settings.messaging.templates.email') }}"
                   class="text-decoration-none d-block"
                   style="color:inherit;">
                    <div class="border rounded-3 p-4 h-100"
                         style="background:#fff; border-color: rgba(0,0,0,.08);">
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div>
                                <div class="badge bg-secondary mb-2" style="font-weight:800;">EMAIL</div>
                                <h3 class="mb-1" style="font-weight:900;">Email Templates</h3>
                                <p class="mb-0 text-muted" style="font-weight:600; font-size:14px;">
                                    View, create, and edit your reusable email messages.
                                </p>
                            </div>

                            <div class="btn btn-abc-gold" style="pointer-events:none;">
                                Open →
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            {{-- SMS Templates option --}}
            <div class="col-12 col-md-6">
                <a href="{{ route('settings.messaging.templates.sms') }}"
                   class="text-decoration-none d-block"
                   style="color:inherit;">
                    <div class="border rounded-3 p-4 h-100"
                         style="background:#fff; border-color: rgba(0,0,0,.08);">
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div>
                                <div class="badge bg-dark mb-2" style="font-weight:800;">SMS</div>
                                <h3 class="mb-1" style="font-weight:900;">Text Templates</h3>
                                <p class="mb-0 text-muted" style="font-weight:600; font-size:14px;">
                                    View, create, and edit your reusable text messages.
                                </p>
                            </div>

                            <div class="btn btn-abc-gold" style="pointer-events:none;">
                                Open →
                            </div>
                        </div>
                    </div>
                </a>
            </div>

        </div>
    </div>
</div>
@endsection

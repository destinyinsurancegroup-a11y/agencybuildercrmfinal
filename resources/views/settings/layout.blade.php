@extends('layouts.app')

@php
    // Helper to mark active tab
    $isActive = fn(string $tab) => ($activeTab ?? 'profile') === $tab;
@endphp

@section('content')
<div class="container-fluid" style="padding: 24px 24px 40px 24px;">
    <div class="settings-page">

        {{-- Page Title --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="settings-title">Settings</h1>
        </div>

        {{-- Tabs --}}
        <ul class="nav nav-tabs settings-tabs mb-0">
            <li class="nav-item">
                <a class="nav-link {{ $isActive('profile') ? 'active' : '' }}"
                   href="{{ route('settings.profile') }}">
                    Profile
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $isActive('messaging') ? 'active' : '' }}"
                   href="{{ route('settings.messaging') }}">
                    Messaging
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $isActive('billing') ? 'active' : '' }}"
                   href="{{ route('settings.billing') }}">
                    Billing
                </a>
            </li>
        </ul>

        {{-- Content Card --}}
        <div class="settings-card">
            @yield('settings_content')
        </div>
    </div>
</div>

{{-- Minimal ABC theme shim for Settings only --}}
<style>
    /* Match your app’s soft beige background vibe */
    .settings-page {
        max-width: 1200px;
    }

    .settings-title {
        font-size: 40px;
        font-weight: 700;
        margin: 0;
        color: #111827;
        letter-spacing: -0.02em;
    }

    /* Tabs */
    .settings-tabs {
        border-bottom: 1px solid #e5e7eb;
    }
    .settings-tabs .nav-link {
        border: 1px solid transparent;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
        color: #111827;
        padding: 10px 16px;
        font-weight: 600;
    }
    .settings-tabs .nav-link:hover {
        color: #111827;
        background: #f9fafb;
        border-color: #e5e7eb #e5e7eb transparent;
    }
    .settings-tabs .nav-link.active {
        background: #ffffff;
        border-color: #e5e7eb #e5e7eb #ffffff;
        color: #111827;
    }

    /* Content area */
    .settings-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-top: none;
        border-bottom-left-radius: 14px;
        border-bottom-right-radius: 14px;
        border-top-right-radius: 14px;
        box-shadow: 0 12px 30px rgba(0,0,0,0.08);
        padding: 22px;
        min-height: 260px;
    }

    /* ABC gold buttons */
    .btn-abc-gold {
        background: #c9a227;
        color: #111827;
        border: none;
        border-radius: 10px;
        padding: 10px 16px;
        font-weight: 700;
        box-shadow: 0 8px 16px rgba(0,0,0,0.12);
    }
    .btn-abc-gold:hover {
        opacity: 0.92;
        color: #111827;
    }

    .btn-abc-outline {
        background: #fff;
        color: #111827;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        padding: 10px 16px;
        font-weight: 700;
    }
    .btn-abc-outline:hover {
        background: #f9fafb;
        color: #111827;
    }

    .settings-subtitle {
        color: #6b7280;
        margin-top: 4px;
        margin-bottom: 18px;
    }

    .settings-section-title {
        font-size: 22px;
        font-weight: 800;
        margin: 0;
        color: #111827;
    }

    .settings-help {
        font-size: 13px;
        color: #6b7280;
        margin-top: 10px;
    }
</style>
@endsection

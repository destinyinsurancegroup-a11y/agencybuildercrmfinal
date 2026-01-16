@extends('layouts.app')

@php
    /**
     * Settings layout (Tier 1)
     * - Left submenu + right content panel (matches your preferred ABC layout)
     * - Messaging should land on Universal SMS Providers list
     *
     * Views can set: $settingsPage = 'profile' | 'billing' | 'sms_providers' | 'templates' | 'drips'
     */
    $settingsPage = $settingsPage ?? 'profile';

    $isActive = function (string $page) use ($settingsPage) {
        return $settingsPage === $page;
    };
@endphp

@section('content')
<div class="container-fluid" style="padding: 24px;">
    <div class="settings-shell">

        <div class="settings-header mb-3">
            <h1 class="settings-title mb-0">Settings</h1>
        </div>

        <div class="settings-body">
            {{-- Left Settings Menu --}}
            <aside class="settings-menu">
                <div class="settings-menu-group">
                    <div class="settings-menu-item settings-menu-item--header">
                        <span class="settings-menu-icon">⚙️</span>
                        <span>Settings</span>
                    </div>

                    <a class="settings-menu-item {{ $isActive('profile') ? 'active' : '' }}"
                       href="{{ route('settings.profile') }}">
                        Profile
                    </a>

                    {{-- Keep placeholders visible but disabled --}}
                    <a class="settings-menu-item disabled" href="javascript:void(0)" aria-disabled="true">
                        Organization <span class="badge bg-light text-muted ms-auto">Soon</span>
                    </a>

                    <a class="settings-menu-item disabled" href="javascript:void(0)" aria-disabled="true">
                        Users &amp; Roles <span class="badge bg-light text-muted ms-auto">Soon</span>
                    </a>

                    <a class="settings-menu-item {{ $isActive('billing') ? 'active' : '' }}"
                       href="{{ route('settings.billing') }}">
                        Billing
                    </a>
                </div>

                <div class="settings-menu-divider"></div>

                <div class="settings-menu-group">
                    <div class="settings-menu-section">MESSAGING</div>

                    {{-- ✅ Universal SMS Providers list (not Twilio-only) --}}
                    <a class="settings-menu-item {{ $isActive('sms_providers') ? 'active' : '' }}"
                       href="{{ route('settings.messaging.sms_providers') }}">
                        <span class="settings-bullet">💬</span>
                        SMS Providers
                        <span class="settings-chevron ms-auto">›</span>
                    </a>

                    {{-- ✅ Message Templates --}}
                    <a class="settings-menu-item {{ $isActive('templates') ? 'active' : '' }}"
                       href="{{ route('settings.messaging.templates.index') }}">
                        Message Templates
                    </a>

                    {{-- ✅ Drip Campaigns --}}
                    <a class="settings-menu-item {{ $isActive('drips') ? 'active' : '' }}"
                       href="{{ route('settings.messaging.drips.index') }}">
                        Drip Campaigns
                    </a>

                    <a class="settings-menu-item disabled" href="javascript:void(0)" aria-disabled="true">
                        Notification Preferences <span class="badge bg-light text-muted ms-auto">Soon</span>
                    </a>
                </div>
            </aside>

            {{-- Right Content Panel --}}
            <main class="settings-content">
                @yield('settings_content')
            </main>
        </div>
    </div>
</div>

<style>
    /* Layout shell */
    .settings-shell {
        max-width: 1320px;
    }

    .settings-title {
        font-size: 40px;
        font-weight: 800;
        letter-spacing: -0.02em;
        color: #111827;
    }

    .settings-body {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 22px;
        align-items: start;
    }

    /* Left menu */
    .settings-menu {
        background: #f5f5f4;
        border-radius: 14px;
        border: 1px solid #e7e5e4;
        padding: 14px;
        box-shadow: 0 12px 30px rgba(0,0,0,0.08);
    }

    .settings-menu-divider {
        height: 1px;
        background: #e7e5e4;
        margin: 12px 0;
    }

    .settings-menu-group { display: flex; flex-direction: column; gap: 6px; }

    .settings-menu-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-radius: 10px;
        text-decoration: none;
        color: #111827;
        font-weight: 600;
        border: 1px solid transparent;
        background: transparent;
    }

    .settings-menu-item:hover {
        background: #ffffff;
        border-color: #e7e5e4;
        color: #111827;
    }

    .settings-menu-item.active {
        background: #efe7d3;
        border-color: #e6d7b0;
        color: #111827;
        position: relative;
    }

    .settings-menu-item.active::before {
        content: "";
        width: 4px;
        height: 70%;
        background: #c9a227;
        border-radius: 4px;
        position: absolute;
        left: 6px;
        top: 15%;
    }

    .settings-menu-item--header {
        background: transparent;
        border: none;
        font-weight: 800;
        padding: 8px 8px;
        color: #111827;
    }

    .settings-menu-icon { opacity: 0.7; }
    .settings-menu-section {
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.06em;
        color: #6b7280;
        padding: 6px 10px 4px 10px;
    }

    .settings-bullet { opacity: 0.85; }
    .settings-chevron { color: #6b7280; font-weight: 900; }

    .settings-menu-item.disabled {
        opacity: 0.55;
        cursor: not-allowed;
        pointer-events: none;
    }

    /* Right content */
    .settings-content {
        min-height: 400px;
    }

    /* Shared right-panel card */
    .panel-card {
        background: #ffffff;
        border: 1px solid #e7e5e4;
        border-radius: 14px;
        box-shadow: 0 12px 30px rgba(0,0,0,0.08);
        overflow: hidden;
    }

    .panel-card-header {
        background: linear-gradient(90deg, #d7b34a, #f0d27a);
        padding: 16px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-weight: 900;
        color: #111827;
    }

    .panel-card-body {
        padding: 18px;
    }

    .btn-abc-gold {
        background: #a57c12;
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 10px 14px;
        font-weight: 800;
        box-shadow: 0 10px 18px rgba(0,0,0,0.15);
    }
    .btn-abc-gold:hover { opacity: 0.92; color: #fff; }

    .btn-abc-outline {
        background: #fff;
        color: #111827;
        border: 1px solid #d6d3d1;
        border-radius: 10px;
        padding: 10px 14px;
        font-weight: 800;
    }
    .btn-abc-outline:hover { background: #fafaf9; }

    .form-label { font-weight: 800; color: #111827; }
</style>
@endsection

@extends('layouts.app')
@section('content')
@php
    // Pass server time to JS (don't use for display)
    $serverTime = now()->toDateTimeString();
@endphp

<style>
    /* Import Inter font */
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

    :root {
        --gold: #c9a227;
        --gold-soft: #f5e6b3;
        --bg-page: #f5f5f5;
        --text-main: #111827;
        --text-subtle: #4b5563;
        --text-faint: #9ca3af;
    }

    .dashboard-page {
        padding: 30px 40px 40px;
        background: var(--bg-page);
        min-height: 100vh;
        font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    /* HEADER AREA */
    .dashboard-header {
        margin-bottom: 32px;
    }
    .dashboard-title {
        font-size: 32px;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 8px;
        letter-spacing: 0.01em;
    }
    .dashboard-subtitle {
        font-size: 22px;
        font-weight: 600;
        color: var(--text-subtle);
        margin-bottom: 6px;
        text-transform: capitalize;
    }
    .dashboard-datetime {
        font-size: 18px;
        font-weight: 500;
        color: var(--text-faint);
    }

    /* SEARCH */
    .dashboard-search-row {
        margin-top: 24px;
        margin-bottom: 28px;
    }
    .dashboard-search-wrapper {
        max-width: 480px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .dashboard-search-input {
        flex: 1;
        padding: 10px 12px;
        border-radius: 10px;
        border: 1px solid #d1d5db;
        font-size: 14px;
        background: #ffffff;
    }
    .dashboard-search-button {
        padding: 10px 16px;
        border-radius: 10px;
        border: none;
        background: var(--gold);
        color: #111827;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 0 4px 8px rgba(0,0,0,0.20);
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }

    /* GRID */
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 20px;
    }

    /* CARD */
    .dashboard-card {
        background: #ffffff;
        border-radius: 18px;
        padding: 22px 22px 24px;
        box-shadow:
            0 18px 30px -12px rgba(0,0,0,0.35),
            0 8px 16px -8px rgba(0,0,0,0.18);
        border: 1px solid #e5e7eb;
    }

    .dashboard-card-title-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
        gap: 8px;
    }

    .dashboard-card-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 20px;
        font-weight: 700;
        color: var(--text-main);
    }

    .dashboard-card-icon {
        width: 26px;
        height: 26px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: var(--gold-soft);
        color: #7c5a00;
        font-size: 14px;
        box-shadow: 0 3px 5px rgba(0,0,0,0.25);
    }

    .insight-name {
        font-weight: 600;
        color: var(--text-main);
    }
    .insight-date {
        font-size: 12px;
        color: var(--text-faint);
    }

    .badge-new {
        padding:4px 8px;
        font-size:11px;
        border-radius:999px;
        background:#dbeafe;
        color:#1d4ed8;
        font-weight:700;
        text-transform:uppercase;
    }
</style>

<div class="dashboard-page">

    {{-- Header --}}
    <div class="dashboard-header">
        <div class="dashboard-title">Dashboard</div>

        {{-- JavaScript greeting --}}
        <div class="dashboard-subtitle local-greeting">Loading greeting…</div>

        {{-- JavaScript time --}}
        <div class="dashboard-datetime local-time" data-server-time="{{ $serverTime }}">Loading time…</div>

        <div class="dashboard-search-row">
            <div class="dashboard-search-wrapper">
                <input type="text" class="dashboard-search-input" placeholder="Search contacts, leads, or clients...">
                <button class="dashboard-search-button">🔍 Search</button>
            </div>
        </div>
    </div>

    {{-- CARDS --}}
    <div class="dashboard-grid">

        {{-- CURRENT PRODUCTION CARD --}}
        <div class="dashboard-card">
            <div class="production-title">Current Production</div>

            @include('dashboard.partials.production-tabs')
        </div>

        {{-- UPCOMING APPOINTMENTS --}}
        <div class="dashboard-card">
            <div class="dashboard-card-title-row">
                <div class="dashboard-card-title">
                    <span class="dashboard-card-icon">📅</span>
                    Upcoming Appointments
                </div>
            </div>
            <div class="dashboard-card-body">
                @if($events->isEmpty())
                    <ul class="dashboard-list">
                        <li>No upcoming appointments.</li>
                    </ul>
                @else
                    <ul class="dashboard-list">
                        @foreach($events as $event)
                            <li>
                                <strong>{{ $event->title }}</strong><br>
                                <span class="insight-date">
                                    {{ \Carbon\Carbon::parse($event->start)->format('M j, g:i A') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- TODAY'S INSIGHTS --}}
        <div class="dashboard-card">
            <div class="dashboard-card-title-row">
                <div class="dashboard-card-title">
                    <span class="dashboard-card-icon">✨</span>
                    Today’s Insights
                </div>
            </div>

            <div class="dashboard-card-body">

                {{-- BIRTHDAYS --}}
                <h4 style="font-size:16px; font-weight:700; margin-bottom:6px;">🎉 Birthdays (Next 7 Days)</h4>
                @if($upcomingBirthdays->isEmpty())
                    <p class="insight-date">No upcoming birthdays.</p>
                @else
                    <ul class="dashboard-list">
                        @foreach($upcomingBirthdays as $c)
                            @php
                                // compute correct birthday date
                                $dob = $c->date_of_birth->copy()->year(now()->year);
                                if ($dob->isPast()) $dob->addYear();
                            @endphp
                            <li>
                                <span class="insight-name">{{ $c->full_name }}</span><br>
                                <span class="insight-date">🎂 {{ $dob->format('M j') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <hr style="margin:12px 0;">

                {{-- ANNIVERSARIES --}}
                <h4 style="font-size:16px; font-weight:700; margin-bottom:6px;">💍 Anniversaries (Next 7 Days)</h4>
                @if($upcomingAnniversaries->isEmpty())
                    <p class="insight-date">No upcoming anniversaries.</p>
                @else
                    <ul class="dashboard-list">
                        @foreach($upcomingAnniversaries as $c)
                            @php
                                $ann = $c->anniversary->copy()->year(now()->year);
                                if ($ann->isPast()) $ann->addYear();
                            @endphp
                            <li>
                                <span class="insight-name">{{ $c->full_name }}</span><br>
                                <span class="insight-date">💍 {{ $ann->format('M j') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

            </div>
        </div>

        {{-- RECENTLY ADDED --}}
        <div class="dashboard-card">
            <div class="dashboard-card-title-row">
                <div class="dashboard-card-title">
                    <span class="badge-new">NEW</span>
                    <span>Recently Added</span>
                </div>
            </div>
            <div class="dashboard-card-body">
                <ul class="dashboard-list">
                    <li>--</li>
                    <li>--</li>
                </ul>
            </div>
        </div>

    </div>

    <div class="dashboard-footer-note">
        © {{ now()->year }} Agency Builder CRM — Tier 1
    </div>
</div>

@include('dashboard.partials.js-local-time')
@include('dashboard.partials.js-production-tabs')

@endsection

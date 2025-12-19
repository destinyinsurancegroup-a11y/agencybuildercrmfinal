@extends('layouts.app')
@section('content')
@php
    $serverTime = now()->toDateTimeString();

    // --- Goal Card placeholders (NOT wired) ---
    $goalMonthName = now()->format('F'); // "December"
    $daysLeft = now()->startOfDay()->diffInDays(now()->endOfMonth()->startOfDay()) + 1; // inclusive count
    $placeholderGoal = 50000; // placeholder goal
    $placeholderPercent = 24; // placeholder percent
    $placeholderNeededMonthly = number_format(($placeholderGoal / 12), 0); // placeholder /12
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

    :root {
        --gold: #c9a227;
        --gold-soft: #f5e6b3;
        --bg-page: #f5f5f5;
        --text-main: #111827;
        --text-subtle: #4b5563;
        --text-faint: #9ca3af;
        --money-green: #059669; /* ✅ NEW — green color for money */
    }

    .dashboard-page {
        padding: 30px 40px;
        background: var(--bg-page);
        min-height: 100vh;
        font-family: 'Inter', sans-serif;
    }

    .dashboard-header { offer-bottom: 32px; margin-bottom: 32px; }
    .dashboard-title { font-size: 32px; font-weight: 700; color: var(--text-main); }

    .dashboard-subtitle {
        font-size: 22px;
        font-weight: 600;
        color: var(--text-subtle);
        margin-bottom: 4px;
    }

    .dashboard-datetime {
        font-size: 18px;
        font-weight: 500;
        color: var(--text-faint);
    }

    .dashboard-search-row { margin-top: 24px; margin-bottom: 28px; }
    .dashboard-search-wrapper {
        max-width: 480px;
        display: flex;
        gap: 10px;
    }

    .dashboard-search-input {
        flex: 1;
        padding: 10px 12px;
        border-radius: 10px;
        border: 1px solid #d1d5db;
        background: #fff;
        font-size: 14px;
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
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    /* ==== GRID ==== */
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
    }

    .dashboard-card {
        background: #fff;
        border-radius: 18px;
        padding: 22px 22px 24px;
        border: 1px solid #e5e7eb;
        box-shadow:
            0 18px 30px -12px rgba(0,0,0,0.35),
            0 8px 16px -8px rgba(0,0,0,0.18);
    }

    /* CARD TITLES */
    .dashboard-card-title-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .dashboard-card-title {
        font-size: 20px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--text-main);
    }

    .dashboard-card-icon {
        background: var(--gold-soft);
        padding: 6px;
        border-radius: 50%;
        font-size: 14px;
    }

    .dashboard-list { list-style: none; padding: 0; margin: 0; }
    .dashboard-list li { font-size: 14px; margin-bottom: 6px; }

    /* ===== PRODUCTION CARD ===== */

    .production-title {
        text-align: center;
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 14px;
    }

    .production-tabs-wrapper {
        display: flex; justify-content: center; margin-bottom: 18px;
    }

    .production-tabs {
        display: inline-flex;
        gap: 6px;
        padding: 4px;
        border-radius: 999px;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
    }

    .production-tab {
        padding: 6px 12px;
        font-size: 14px;
        border-radius: 999px;
        background: transparent;
        cursor: pointer;
        font-weight: 600;
        color: #6b7280;
    }

    .production-tab-active {
        background: var(--gold);
        color: #111827;
        box-shadow: 0 3px 6px rgba(0,0,0,0.25);
    }

    .production-range { display: none; }
    .production-range-active { display: block; }

    /* ===== RIGHT-JUSTIFIED VALUES (NEW) ===== */
    .production-label {
        font-size: 18px;
        color: #4b5563;
        font-weight: 500;
        padding: 6px 0;
    }

    .production-value {
        font-size: 24px;
        font-weight: 700;
        text-align: right !important; /* ✅ NEW — hard right alignment */
        padding: 6px 0;
        white-space: nowrap;
    }

    /* ===== MONEY STYLING (NEW) ===== */
    .money {
        color: var(--money-green) !important;
        font-weight: 800 !important;
    }

    /* =========================================================
       NEW: GOAL CARD (placeholder only, matches ABC scheme)
       ========================================================= */
    .goal-card {
        grid-column: 1 / -1; /* full width row, minimal disruption */
        background: radial-gradient(1200px 600px at 85% 15%, rgba(255,255,255,0.10), rgba(255,255,255,0.00) 55%),
                    #0f2447;
        border: 1px solid rgba(255,255,255,0.10);
        color: #fff;
        padding: 22px 22px 22px;
        overflow: hidden;
        position: relative;
    }

    .goal-card::before {
        content: "";
        position: absolute;
        right: -120px;
        top: -120px;
        width: 320px;
        height: 320px;
        border-radius: 999px;
        background: rgba(255,255,255,0.06);
    }

    .goal-card::after {
        content: "";
        position: absolute;
        right: -40px;
        top: 110px;
        width: 360px;
        height: 360px;
        border-radius: 999px;
        background: rgba(255,255,255,0.03);
    }

    .goal-top {
        position: relative;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
        margin-bottom: 14px;
    }

    .goal-top-left {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 700;
        font-size: 26px;
        letter-spacing: 0.2px;
    }

    .goal-dot {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(0,0,0,0.25);
        border: 1px solid rgba(255,255,255,0.12);
        color: var(--gold);
        font-weight: 900;
    }

    .goal-input-wrap {
        display: flex;
        align-items: stretch;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,0.16);
        background: rgba(0,0,0,0.22);
        position: relative;
        z-index: 1;
    }

    .goal-input {
        width: 140px;
        padding: 10px 12px;
        border: none;
        outline: none;
        background: transparent;
        color: var(--gold);
        font-size: 18px;
        font-weight: 800;
    }

    .goal-save {
        padding: 10px 14px;
        border: none;
        background: var(--gold);
        color: #111827;
        font-size: 14px;
        font-weight: 800;
        cursor: not-allowed; /* placeholder */
        opacity: 0.85;
    }

    .goal-body {
        position: relative;
        display: grid;
        grid-template-columns: 1.2fr 0.8fr;
        gap: 18px;
        align-items: center;
        z-index: 1;
    }

    .goal-main-value {
        font-size: 44px;
        font-weight: 900;
        color: var(--gold);
        margin-top: 4px;
        letter-spacing: 0.2px;
    }

    .goal-sub-label {
        font-size: 18px;
        font-weight: 800;
        color: var(--gold);
        margin-top: 10px;
    }

    .goal-needed-row {
        display: flex;
        align-items: baseline;
        gap: 10px;
        margin-top: 6px;
    }

    .goal-needed-value {
        font-size: 42px;
        font-weight: 900;
        color: var(--gold);
    }

    .goal-needed-suffix {
        font-size: 16px;
        color: rgba(255,255,255,0.70);
        font-weight: 600;
    }

    /* Progress ring (placeholder) */
    .goal-ring {
        width: 170px;
        height: 170px;
        border-radius: 999px;
        position: relative;
        display: grid;
        place-items: center;
        margin-left: auto;
        background:
            conic-gradient(var(--ring-color) calc(var(--progress) * 1%), rgba(255,255,255,0.15) 0);
    }

    .goal-ring::before {
        content: "";
        width: 135px;
        height: 135px;
        border-radius: 999px;
        background: rgba(0,0,0,0.30);
        border: 1px solid rgba(255,255,255,0.12);
        position: absolute;
    }

    .goal-ring-center {
        position: relative;
        text-align: center;
        z-index: 2;
    }

    .goal-percent {
        font-size: 52px;
        font-weight: 900;
        color: var(--ring-color);
        line-height: 1;
    }

    .goal-complete {
        font-size: 14px;
        color: rgba(255,255,255,0.70);
        font-weight: 700;
        margin-top: 6px;
    }

    .goal-strip {
        position: relative;
        z-index: 1;
        margin-top: 14px;
        background: rgba(255,255,255,0.85);
        border-radius: 14px;
        padding: 12px 14px;
        color: #111827;
        font-size: 18px;
        font-weight: 600;
    }

    .goal-actions {
        position: relative;
        z-index: 1;
        margin-top: 14px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .goal-btn {
        border-radius: 14px;
        padding: 14px 16px;
        font-size: 16px;
        font-weight: 900;
        border: 1px solid rgba(255,255,255,0.12);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        cursor: not-allowed; /* placeholder */
    }

    .goal-btn-primary {
        background: var(--gold);
        color: #111827;
        box-shadow: 0 10px 18px -10px rgba(0,0,0,0.45);
    }

    .goal-btn-secondary {
        background: rgba(0,0,0,0.22);
        color: #fff;
        border: 1px solid rgba(255,255,255,0.16);
    }

    .goal-btn-sub {
        font-size: 12px;
        font-weight: 700;
        opacity: 0.85;
        margin-top: 4px;
    }

    @media (max-width: 1100px) {
        .goal-body {
            grid-template-columns: 1fr;
        }
        .goal-ring { margin: 0; }
        .goal-actions { grid-template-columns: 1fr; }
    }
</style>

<div class="dashboard-page">

    <div class="dashboard-header">
        <div class="dashboard-title">Dashboard</div>

        <div class="dashboard-subtitle local-greeting">Loading greeting…</div>

        <div class="dashboard-datetime local-time" data-server-time="{{ $serverTime }}">
            Loading time…
        </div>

        <div class="dashboard-search-row">
            <div class="dashboard-search-wrapper">
                <input type="text" class="dashboard-search-input"
                    placeholder="Search contacts, leads, or clients...">

                <button class="dashboard-search-button">🔍 Search</button>
            </div>
        </div>
    </div>

    {{-- GRID START --}}
    <div class="dashboard-grid">

        {{-- NEW: MONTHLY GOAL CARD (PLACEHOLDER) --}}
        <div class="dashboard-card goal-card"
             style="--progress: {{ $placeholderPercent }}; --ring-color: #EF4444;">
            <div class="goal-top">
                <div class="goal-top-left">
                    <span class="goal-dot">●</span>
                    {{ $goalMonthName }} Goal
                </div>

                {{-- Placeholder input (not wired) --}}
                <div class="goal-input-wrap" title="Placeholder (not wired yet)">
                    <input class="goal-input" type="text" value="${{ number_format($placeholderGoal, 0) }}" disabled>
                    <button class="goal-save" type="button" disabled>Save →</button>
                </div>
            </div>

            <div class="goal-body">
                <div>
                    <div class="goal-main-value">${{ number_format($placeholderGoal, 0) }} AP</div>

                    <div class="goal-sub-label">Collected Premium Needed:</div>
                    <div class="goal-needed-row">
                        <div class="goal-needed-value">${{ $placeholderNeededMonthly }}</div>
                        <div class="goal-needed-suffix">needed / mo</div>
                    </div>
                </div>

                <div class="goal-ring" aria-label="Goal progress ring">
                    <div class="goal-ring-center">
                        <div class="goal-percent">{{ $placeholderPercent }}%</div>
                        <div class="goal-complete">Complete</div>
                    </div>
                </div>
            </div>

            <div class="goal-strip">
                <strong>{{ $daysLeft }} days left</strong> to reach goal
            </div>

            <div class="goal-actions">
                <button class="goal-btn goal-btn-primary" type="button" disabled title="Placeholder (not wired yet)">
                    <div>
                        <div>⚡ Log Production</div>
                        <div class="goal-btn-sub">Log Calls, Stops, Premium Collected</div>
                    </div>
                    <div>›</div>
                </button>

                <button class="goal-btn goal-btn-secondary" type="button" disabled title="Placeholder (not wired yet)">
                    <div>
                        <div>▦ Production Breakdown</div>
                        <div class="goal-btn-sub">Weekly / Monthly / Quarterly / Annual</div>
                    </div>
                    <div>›</div>
                </button>
            </div>
        </div>

        {{-- CURRENT PRODUCTION CARD --}}
        <div class="dashboard-card">
            <div class="production-title">Current Production</div>

            <div class="production-tabs-wrapper">
                <div class="production-tabs">
                    <button class="production-tab production-tab-active" data-production-tab="day">Day</button>
                    <button class="production-tab" data-production-tab="week">Week</button>
                    <button class="production-tab" data-production-tab="month">Month</button>
                    <button class="production-tab" data-production-tab="quarter">Quarter</button>
                    <button class="production-tab" data-production-tab="year">Year</button>
                </div>
            </div>

            <div class="dashboard-card-body production-stats">

                {{-- DAY --}}
                <div class="production-range production-range-active" data-production-range="day">
                    <table style="width:100%;">
                        <tr><td class="production-label">Leads Worked</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Calls</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Stops</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Presentations</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Apps Written</td><td class="production-value">--</td></tr>

                        {{-- MONEY VALUES (bold green + $ sign) --}}
                        <tr>
                            <td class="production-label">Premium Collected</td>
                            <td class="production-value money">$--</td>
                        </tr>
                        <tr>
                            <td class="production-label">AP</td>
                            <td class="production-value money">$--</td>
                        </tr>
                    </table>
                </div>

                {{-- WEEK --}}
                <div class="production-range" data-production-range="week">
                    <table style="width:100%;">
                        <tr><td class="production-label">Leads Worked</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Calls</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Stops</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Presentations</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Apps Written</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Premium Collected</td><td class="production-value money">$--</td></tr>
                        <tr><td class="production-label">AP</td><td class="production-value money">$--</td></tr>
                    </table>
                </div>

                {{-- MONTH --}}
                <div class="production-range" data-production-range="month">
                    <table style="width:100%;">
                        <tr><td class="production-label">Leads Worked</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Calls</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Stops</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Presentations</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Apps Written</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Premium Collected</td><td class="production-value money">$--</td></tr>
                        <tr><td class="production-label">AP</td><td class="production-value money">$--</td></tr>
                    </table>
                </div>

                {{-- QUARTER --}}
                <div class="production-range" data-production-range="quarter">
                    <table style="width:100%;">
                        <tr><td class="production-label">Leads Worked</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Calls</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Stops</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Presentations</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Apps Written</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Premium Collected</td><td class="production-value money">$--</td></tr>
                        <tr><td class="production-label">AP</td><td class="production-value money">$--</td></tr>
                    </table>
                </div>

                {{-- YEAR --}}
                <div class="production-range" data-production-range="year">
                    <table style="width:100%;">
                        <tr><td class="production-label">Leads Worked</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Calls</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Stops</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Presentations</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Apps Written</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Premium Collected</td><td class="production-value money">$--</td></tr>
                        <tr><td class="production-label">AP</td><td class="production-value money">$--</td></tr>
                    </table>
                </div>

            </div>
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
                                <span style="color: var(--text-faint); font-size: 13px;">
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
                <ul class="dashboard-list">
                    <li>Birthdays in next 7 days: {{ $birthdays->count() }}</li>
                    <li>Anniversaries in next 7 days: {{ $anniversaries->count() }}</li>
                </ul>

                {{-- Birthdays --}}
                @if($birthdays->isNotEmpty())
                    <hr style="margin: 10px 0;">
                    <div style="font-size: 13px; font-weight: 600; margin-bottom: 6px;">
                        🎂 Upcoming Birthdays
                    </div>
                    <ul class="dashboard-list">
                        @foreach($birthdays as $contact)
                            <li>
                                <strong>{{ $contact->full_name }}</strong>
                                <span style="color: var(--text-faint); font-size: 13px;">
                                    • {{ optional($contact->date_of_birth)->format('M j') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                {{-- Anniversaries --}}
                @if($anniversaries->isNotEmpty())
                    <hr style="margin: 10px 0;">
                    <div style="font-size: 13px; font-weight: 600; margin-bottom: 6px;">
                        💍 Upcoming Anniversaries
                    </div>
                    <ul class="dashboard-list">
                        @foreach($anniversaries as $contact)
                            <li>
                                <strong>{{ $contact->full_name }}</strong>
                                <span style="color: var(--text-faint); font-size: 13px;">
                                    • {{ optional($contact->anniversary)->format('M j') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- GIDEON OPPORTUNITIES (repurposed "Recently Added" card) --}}
        <div class="dashboard-card">
            <div class="dashboard-card-title-row">
                <div class="dashboard-card-title">
                    <span class="dashboard-card-icon">🤖</span>
                    Gideon Opportunities
                </div>
                <a href="{{ route('gideon.opportunities.index') }}"
                   style="font-size: 13px; color: var(--gold); text-decoration: none;">
                    View all →
                </a>
            </div>
            <div class="dashboard-card-body">
                @if($gideonOpportunities->isEmpty())
                    <ul class="dashboard-list">
                        <li>
                            No Gideon opportunities yet.
                            As Gideon scans your leads and book of business,
                            suggestions will appear here.
                        </li>
                    </ul>
                @else
                    <ul class="dashboard-list">
                        @foreach($gideonOpportunities as $opp)
                            <li>
                                <strong>{{ $opp->title }}</strong><br>
                                <span style="color: var(--text-faint); font-size: 13px;">
                                    {{ \Illuminate\Support\Str::limit($opp->short_reason, 80) }}
                                </span><br>
                                <span style="display:inline-block; margin-top:4px; background: var(--gold-soft); color: var(--text-main); font-size:11px; border-radius:999px; padding:2px 8px; text-transform:uppercase;">
                                    {{ str_replace('_', ' ', $opp->category) }}
                                </span>
                                @if(isset($opp->source_snapshot['full_name']))
                                    <span style="color: var(--text-faint); font-size: 12px;">
                                        • {{ $opp->source_snapshot['full_name'] }}
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

    </div>{{-- END GRID --}}

    <div class="dashboard-footer-note" style="margin-top: 20px; color:#6b7280;">
        © {{ now()->year }} Agency Builder CRM — Tier 1
    </div>
</div>

<!-- LOCAL TIME + GREETING -->
<script>
document.addEventListener("DOMContentLoaded", function () {

    const timeEl = document.querySelector(".local-time");
    const greetEl = document.querySelector(".local-greeting");
    const serverTime = timeEl.getAttribute("data-server-time");

    const localDate = new Date(serverTime + " UTC");
    timeEl.innerText = localDate.toLocaleString();

    const hour = localDate.getHours();
    let greeting = "Good ";

    if (hour < 12) greeting += "morning";
    else if (hour < 17) greeting += "afternoon";
    else greeting += "evening";

    greetEl.innerText = greeting + " — here’s your daily overview.";
});
</script>

<!-- TAB LOGIC -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.production-tab');
    const ranges = document.querySelectorAll('.production-range');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const range = tab.dataset.productionTab;

            tabs.forEach(t => t.classList.remove('production-tab-active'));
            tab.classList.add('production-tab-active');

            ranges.forEach(r => {
                r.classList.toggle('production-range-active',
                    r.dataset.productionRange === range
                );
            });

            refreshProductionCard(); // reload numbers when switching tabs
        });
    });
});
</script>

<!-- UPDATE STATS -->
<script>
window.refreshProductionCard = function() {
    const active = document.querySelector(".production-tab-active").dataset.productionTab;

    fetch(`/activity/totals/${active}`)
        .then(r => r.json())
        .then(data => {
            const rows = document.querySelectorAll(
                `.production-range[data-production-range="${active}"] .production-value`
            );

            if (rows.length === 7) {
                rows[0].innerText = data.leads_worked;
                rows[1].innerText = data.calls;
                rows[2].innerText = data.stops;
                rows[3].innerText = data.presentations;
                rows[4].innerText = data.apps_written;

                // MONEY VALUES — add $ + green styling
                rows[5].innerText = "$" + data.premium_collected;
                rows[6].innerText = "$" + data.ap;
            }
        });
};
</script>

<!-- AUTO-REFRESH AFTER SAVE -->
<script>
document.addEventListener("activitySaved", function () {
    refreshProductionCard();
});
</script>

<!-- AP AUTO CALC -->
<script>
document.addEventListener("input", function (e) {
    if (e.target.name === "premium_collected") {
        let premium = parseFloat(e.target.value) || 0;
        let apField = document.querySelector('input[name="ap"]');
        if (apField) {
            apField.value = (premium * 12).toFixed(2);
        }
    }
});
</script>

<!-- INITIAL LOAD -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    refreshProductionCard();
});
</script>

@endsection

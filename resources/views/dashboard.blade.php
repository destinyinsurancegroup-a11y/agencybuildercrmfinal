@extends('layouts.app')
@section('content')
@php
    $serverTime = now()->toDateTimeString();

    // PLACEHOLDER VALUES (not wired yet)
    $goalMonthName = now()->format('F');                 // e.g. December
    $placeholderGoal = 50000;                            // $50,000
    $placeholderPercent = 24;                            // 24%
    $placeholderNeededMonthly = (int) round($placeholderGoal / 12); // 4167
    $daysLeft = now()->startOfDay()->diffInDays(now()->endOfMonth()->startOfDay()) + 1; // inclusive
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
        --money-green: #059669;

        /* Sidebar-like black */
        --abc-black: #0b1220;
        --abc-black-2: #0f172a;

        --danger-red: #ef4444;
        --ring-track: rgba(255,255,255,0.14);
    }

    .dashboard-page {
        padding: 30px 40px;
        background: var(--bg-page);
        min-height: 100vh;
        font-family: 'Inter', sans-serif;
    }

    /* ===== Header Layout (Search top-right + alert + agent) ===== */
    .dashboard-header { margin-bottom: 22px; }

    .dashboard-header-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
    }

    .dashboard-title {
        font-size: 32px;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 10px;
    }

    .dashboard-subtitle {
        font-size: 22px;
        font-weight: 600;
        color: var(--text-subtle);
        margin-bottom: 6px;
    }

    .dashboard-datetime {
        font-size: 16px;
        font-weight: 600;
        color: var(--text-faint);
    }

    .dashboard-right-tools {
        display: flex;
        align-items: center;
        gap: 10px;
        padding-top: 6px;
    }

    .top-search {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #fff;
        border: 1px solid #d1d5db;
        border-radius: 999px;
        padding: 10px 14px;
        min-width: 420px;
        box-shadow: 0 10px 20px -16px rgba(0,0,0,0.25);
    }

    .top-search input {
        border: none;
        outline: none;
        width: 100%;
        font-size: 14px;
        color: var(--text-main);
    }

    .top-search .icon {
        color: #6b7280;
        font-size: 14px;
        width: 16px;
        text-align: center;
    }

    .top-icon-btn {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        border: 1px solid #d1d5db;
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 10px 20px -16px rgba(0,0,0,0.25);
        position: relative;
    }

    .top-icon-btn .dot {
        position: absolute;
        right: 8px;
        top: 8px;
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: #ef4444;
        border: 2px solid #fff;
    }

    .top-agent-btn {
        height: 40px;
        border-radius: 12px;
        border: 1px solid #d1d5db;
        background: #fff;
        padding: 0 12px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        box-shadow: 0 10px 20px -16px rgba(0,0,0,0.25);
        font-weight: 700;
        font-size: 14px;
        color: var(--text-main);
    }

    .top-agent-avatar {
        width: 28px;
        height: 28px;
        border-radius: 10px;
        background: var(--abc-black-2);
        color: var(--gold);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
        font-size: 13px;
    }

    /* ==== GRID ==== */
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-top: 18px;
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

    .production-label {
        font-size: 18px;
        color: #4b5563;
        font-weight: 500;
        padding: 6px 0;
    }

    .production-value {
        font-size: 24px;
        font-weight: 700;
        text-align: right !important;
        padding: 6px 0;
        white-space: nowrap;
    }

    .money {
        color: var(--money-green) !important;
        font-weight: 800 !important;
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

    /* =========================================================
       GOAL CARD (matches your mockup)
       Updates requested:
       - 25% shorter vertically
       - Text ~50% smaller
       - Width 75% across dashboard
       - $50,000 gold; AP white
       - Premium needed number white
       - Remove "needed /mo"
       - Days bar fades toward circle and sits under ring
       - Buttons much smaller
       ========================================================= */

    .goal-card-wrap {
        width: 75%;
        margin-top: 6px;
        margin-bottom: 18px;
    }

    .goal-card {
        background: linear-gradient(180deg, var(--abc-black-2) 0%, var(--abc-black) 100%);
        border-radius: 18px;
        border: 1px solid rgba(255,255,255,0.10);
        box-shadow:
            0 18px 30px -12px rgba(0,0,0,0.45),
            0 8px 16px -8px rgba(0,0,0,0.22);
        overflow: hidden;
        position: relative;
        color: #fff;
    }

    .goal-card::before {
        content: "";
        position: absolute;
        right: -140px;
        top: -140px;
        width: 360px;
        height: 360px;
        border-radius: 999px;
        background: rgba(255,255,255,0.05);
        pointer-events: none;
    }
    .goal-card::after {
        content: "";
        position: absolute;
        right: -40px;
        top: 120px;
        width: 360px;
        height: 360px;
        border-radius: 999px;
        background: rgba(255,255,255,0.025);
        pointer-events: none;
    }

    /* 25% smaller vertically: reduce paddings */
    .goal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 10px 12px; /* reduced */
        border-bottom: 1px solid rgba(255,255,255,0.08);
        background: rgba(0,0,0,0.10);
        position: relative;
        z-index: 1;
    }

    /* ~50% smaller text */
    .goal-header-left {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 16px;     /* reduced */
        font-weight: 800;
        letter-spacing: 0.2px;
    }

    .goal-icon {
        width: 24px;
        height: 24px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(0,0,0,0.25);
        border: 1px solid rgba(255,255,255,0.12);
        color: var(--gold);
        font-weight: 900;
        font-size: 12px;
    }

    .goal-input-wrap {
        display: flex;
        align-items: stretch;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,0.16);
        background: rgba(0,0,0,0.25);
    }

    .goal-input {
        width: 110px;
        padding: 7px 9px;
        border: none;
        outline: none;
        background: transparent;
        color: var(--gold);
        font-size: 13px;
        font-weight: 900;
    }

    .goal-save {
        padding: 7px 10px;
        border: none;
        background: var(--gold);
        color: #111827;
        font-size: 12px;
        font-weight: 900;
        cursor: not-allowed;
        opacity: 0.95;
    }

    .goal-body {
        display: grid;
        grid-template-columns: 1.25fr 0.75fr;
        gap: 12px;
        align-items: center;
        padding: 12px 12px 6px; /* reduced */
        position: relative;
        z-index: 1;
    }

    .goal-main {
        font-size: 28px; /* reduced */
        font-weight: 900;
        margin-bottom: 8px;
        line-height: 1.1;
    }

    .goal-main .amt { color: var(--gold); }
    .goal-main .ap { color: #ffffff; }

    .goal-label {
        font-size: 13px; /* reduced */
        font-weight: 800;
        color: var(--gold);
        margin-bottom: 4px;
    }

    .goal-needed-row {
        display: flex;
        align-items: baseline;
        gap: 10px;
    }

    /* Premium needed should be WHITE */
    .goal-needed {
        font-size: 24px; /* reduced */
        font-weight: 900;
        color: #ffffff;
    }

    /* Remove "needed /mo" */
    .goal-needed-suffix { display: none; }

    /* Progress ring, smaller to reduce overall card height */
    .goal-ring {
        width: 118px;
        height: 118px;
        border-radius: 999px;
        background:
            conic-gradient(var(--danger-red) calc(var(--progress) * 1%), var(--ring-track) 0);
        display: grid;
        place-items: center;
        margin-left: auto;
        position: relative;

        /* sit above the days strip */
        z-index: 3;
    }

    .goal-ring::before {
        content: "";
        width: 92px;
        height: 92px;
        border-radius: 999px;
        background: rgba(0,0,0,0.35);
        border: 1px solid rgba(255,255,255,0.10);
        position: absolute;
    }

    .goal-ring-center {
        position: relative;
        z-index: 2;
        text-align: center;
    }

    .goal-percent {
        font-size: 28px; /* reduced */
        font-weight: 900;
        color: var(--danger-red);
        line-height: 1;
    }

    .goal-complete {
        margin-top: 4px;
        font-size: 11px; /* reduced */
        font-weight: 700;
        color: rgba(255,255,255,0.75);
    }

    /* Days-left bar: fades as it approaches the ring + ring overlaps it */
    .goal-strip-wrap {
        position: relative;
        padding: 0 12px 10px; /* reduced */
        z-index: 1;
    }

    .goal-strip {
        height: 34px; /* reduced */
        display: flex;
        align-items: center;
        padding: 0 10px;
        border-radius: 10px;
        color: #111827;
        font-size: 14px; /* reduced */
        font-weight: 800;

        /* gradient fade toward right (toward ring) */
        background: linear-gradient(
            90deg,
            rgba(255,255,255,0.90) 0%,
            rgba(255,255,255,0.75) 55%,
            rgba(255,255,255,0.35) 78%,
            rgba(255,255,255,0.00) 100%
        );
    }

    .goal-strip span { font-weight: 900; }

    /* Place the ring on top of the strip area visually */
    .goal-strip-ring-anchor {
        position: absolute;
        right: 12px;
        top: -54px; /* pulls ring downward over the strip */
        z-index: 4;
    }

    /* Buttons "200% smaller": significantly reduced */
    .goal-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        padding: 8px 12px 12px; /* reduced */
        position: relative;
        z-index: 1;
    }

    .goal-btn {
        border-radius: 10px;
        padding: 8px 10px; /* reduced */
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        border: 1px solid rgba(255,255,255,0.12);
        cursor: not-allowed;
        min-height: 44px; /* compact */
    }

    .goal-btn-primary {
        background: var(--gold);
        color: #111827;
        box-shadow: 0 10px 18px -14px rgba(0,0,0,0.55);
    }

    .goal-btn-secondary {
        background: rgba(0,0,0,0.18);
        border: 1px solid rgba(255,255,255,0.14);
        color: #fff;
    }

    .goal-btn-left {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .goal-btn-title {
        font-size: 13px; /* reduced */
        font-weight: 900;
        line-height: 1.05;
    }

    .goal-btn-sub {
        font-size: 10px; /* reduced */
        font-weight: 700;
        opacity: 0.85;
    }

    /* Responsive */
    @media (max-width: 1100px) {
        .goal-card-wrap { width: 100%; }
        .top-search { min-width: 240px; }
    }

    @media (max-width: 860px) {
        .dashboard-header-top { flex-direction: column; align-items: stretch; }
        .dashboard-right-tools { justify-content: flex-start; }
        .top-search { min-width: 0; width: 100%; }
        .goal-body { grid-template-columns: 1fr; }
        .goal-strip-ring-anchor { right: 12px; top: -46px; }
        .goal-actions { grid-template-columns: 1fr; }
    }
</style>

<div class="dashboard-page">

    <div class="dashboard-header">
        <div class="dashboard-header-top">
            <div>
                <div class="dashboard-title">Dashboard</div>

                {{-- must remain where it is --}}
                <div class="dashboard-subtitle local-greeting">Loading greeting…</div>

                {{-- Correct day/date/time beneath greeting --}}
                <div class="dashboard-datetime local-time" data-server-time="{{ $serverTime }}">
                    Loading time…
                </div>
            </div>

            {{-- Search upper-right + alert + agent (like pic 2) --}}
            <div class="dashboard-right-tools">
                <div class="top-search">
                    <span class="icon">🔍</span>
                    <input type="text" placeholder="Search contacts, leads, or clients...">
                </div>

                <button class="top-icon-btn" type="button" aria-label="Alerts">
                    🔔
                    <span class="dot" aria-hidden="true"></span>
                </button>

                <button class="top-agent-btn" type="button" aria-label="Agent menu">
                    <span class="top-agent-avatar">A</span>
                    Agent
                </button>
            </div>
        </div>
    </div>

    {{-- GOAL CARD (75% wide, shorter, smaller text, gradient strip + ring overlap) --}}
    <div class="goal-card-wrap">
        <div class="goal-card" style="--progress: {{ $placeholderPercent }};">
            <div class="goal-header">
                <div class="goal-header-left">
                    <span class="goal-icon">●</span>
                    {{ $goalMonthName }} Goal
                </div>

                <div class="goal-input-wrap" title="Placeholder (not wired yet)">
                    <input class="goal-input" type="text" value="${{ number_format($placeholderGoal, 0) }}" disabled>
                    <button class="goal-save" type="button" disabled>Save →</button>
                </div>
            </div>

            <div class="goal-body">
                <div>
                    <div class="goal-main">
                        <span class="amt">${{ number_format($placeholderGoal, 0) }}</span>
                        <span class="ap"> AP</span>
                    </div>

                    <div class="goal-label">Collected Premium Needed:</div>
                    <div class="goal-needed-row">
                        <div class="goal-needed">${{ number_format($placeholderNeededMonthly, 0) }}</div>
                        <div class="goal-needed-suffix">needed /mo</div>
                    </div>
                </div>

                {{-- keep ring here for layout sizing, but also overlay it on strip via anchor below --}}
                <div style="visibility:hidden; height:0;"></div>
            </div>

            <div class="goal-strip-wrap">
                <div class="goal-strip">
                    <span>{{ $daysLeft }} days left</span>&nbsp;to reach goal
                </div>

                <div class="goal-strip-ring-anchor">
                    <div class="goal-ring" aria-label="Goal progress ring">
                        <div class="goal-ring-center">
                            <div class="goal-percent">{{ $placeholderPercent }}%</div>
                            <div class="goal-complete">Complete</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="goal-actions">
                <button class="goal-btn goal-btn-primary" type="button" disabled title="Placeholder (not wired yet)">
                    <div class="goal-btn-left">
                        <div class="goal-btn-title">⚡ Log Production</div>
                        <div class="goal-btn-sub">Log Calls, Stops, Premium Collected</div>
                    </div>
                    <div style="font-size:18px; font-weight:900;">›</div>
                </button>

                <button class="goal-btn goal-btn-secondary" type="button" disabled title="Placeholder (not wired yet)">
                    <div class="goal-btn-left">
                        <div class="goal-btn-title">▦ Production Breakdown</div>
                        <div class="goal-btn-sub">Weekly / Monthly / Quarterly / Annual</div>
                    </div>
                    <div style="font-size:18px; font-weight:900;">›</div>
                </button>
            </div>
        </div>
    </div>

    {{-- GRID START (all cards below goal card) --}}
    <div class="dashboard-grid">
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
                        <tr><td class="production-label">Premium Collected</td><td class="production-value money">$--</td></tr>
                        <tr><td class="production-label">AP</td><td class="production-value money">$--</td></tr>
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

        {{-- GIDEON OPPORTUNITIES --}}
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

<!-- LOCAL TIME + GREETING (formatted day/date/time) -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const timeEl = document.querySelector(".local-time");
    const greetEl = document.querySelector(".local-greeting");
    const serverTime = timeEl.getAttribute("data-server-time");

    // Convert server time -> local display
    const localDate = new Date(serverTime + " UTC");

    const hour = localDate.getHours();
    let greeting = "Good ";
    if (hour < 12) greeting += "morning";
    else if (hour < 17) greeting += "afternoon";
    else greeting += "evening";

    greetEl.innerText = greeting + ", Agent";

    // Format like: Friday, December 19, 2025 • 3:24 PM
    const datePart = localDate.toLocaleDateString(undefined, {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric"
    });

    const timePart = localDate.toLocaleTimeString(undefined, {
        hour: "numeric",
        minute: "2-digit"
    });

    timeEl.innerText = `${datePart} • ${timePart}`;
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

            refreshProductionCard();
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

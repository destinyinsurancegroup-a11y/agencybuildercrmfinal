@extends('layouts.app')
@section('content')
@php
    /**
     * ✅ Robust time source for JS: epoch milliseconds (UTC)
     */
    $serverTimeMs = now()->utc()->timestamp * 1000;

    // ✅ Reset goal to 0 (until backend persistence is wired)
    $goalMonthName = now()->format('F');                 // fallback until JS sets it
    $placeholderGoal = 0;                               // $0
    $placeholderPercent = 0;                            // 0%
    $placeholderNeededMonthly = 0;                      // $0

    /**
     * ✅ Days-left (server fallback) should be "days left AFTER today"
     */
    $tomorrowStart = now()->addDay()->startOfDay();
    $endOfMonthStart = now()->endOfMonth()->startOfDay();
    $daysLeftRaw = $tomorrowStart->diffInDays($endOfMonthStart, false) + 1; // inclusive from tomorrow
    $daysLeft = max($daysLeftRaw, 0);
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
       GOAL CARD
       ========================================================= */

    .goal-card-wrap {
        width: 50%;
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

    .goal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 16px 18px;
        border-bottom: 1px solid rgba(255,255,255,0.08);
        background: rgba(0,0,0,0.10);
        position: relative;
        z-index: 1;
    }

    .goal-header-left {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 52px;
        font-weight: 800;
        letter-spacing: 0.2px;
        line-height: 1.05;
    }

    .goal-icon {
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
        font-size: 14px;
        flex: 0 0 auto;
    }

    .goal-input-wrap {
        display: flex;
        align-items: stretch;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,0.16);
        background: rgba(0,0,0,0.25);
        flex: 0 0 auto;
    }

    .goal-input {
        width: 140px;
        padding: 10px 12px;
        border: none;
        outline: none;
        background: transparent;
        color: var(--gold);
        font-size: 18px;
        font-weight: 900;
    }

    .goal-save {
        padding: 10px 14px;
        border: none;
        background: var(--gold);
        color: #111827;
        font-size: 14px;
        font-weight: 900;
        cursor: pointer;
        opacity: 0.95;
    }

    .goal-body {
        display: grid;
        grid-template-columns: 1.05fr 0.95fr;
        gap: 18px;
        align-items: center;
        padding: 18px 18px 8px;
        position: relative;
        z-index: 1;
    }

    .goal-main {
        font-size: 44px;
        font-weight: 900;
        margin-bottom: 12px;
        line-height: 1.1;
    }

    .goal-main .amt { color: var(--gold); }
    .goal-main .ap { color: #ffffff; }

    .goal-label {
        font-size: 20px;
        font-weight: 800;
        color: var(--gold);
        margin-bottom: 6px;
    }

    .goal-needed-row {
        display: flex;
        align-items: baseline;
        gap: 12px;
    }

    .goal-needed {
        font-size: 40px;
        font-weight: 900;
        color: #ffffff;
    }

    .goal-ring {
        width: 213px;
        height: 213px;
        border-radius: 999px;
        background:
            conic-gradient(var(--danger-red) calc(var(--progress) * 1%), var(--ring-track) 0);
        display: grid;
        place-items: center;
        margin-left: auto;
        position: relative;
        z-index: 3;
    }

    .goal-ring::before {
        content: "";
        width: 170px;
        height: 170px;
        border-radius: 999px;
        background: rgba(0,0,0,0.35);
        border: 1px solid rgba(255,255,255,0.10);
        position: absolute;
    }

    .goal-ring-center {
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        width: 100%;
        height: 100%;
        gap: 6px;
    }

    .goal-percent {
        font-size: 54px;
        font-weight: 900;
        color: var(--danger-red);
        line-height: 1;
        margin: 0;
    }

    .goal-complete {
        font-size: 18px;
        font-weight: 600;
        color: rgba(255,255,255,0.75);
        line-height: 1.1;
        margin: 0;
    }

    .goal-strip-wrap {
        position: relative;
        padding: 0 18px 16px;
        z-index: 1;
    }

    .goal-strip {
        height: 46px;
        display: flex;
        align-items: center;
        padding: 0 14px;
        border-radius: 10px;
        color: #111827;
        font-size: 22px;
        font-weight: 800;
        background: linear-gradient(
            90deg,
            rgba(255,255,255,0.92) 0%,
            rgba(255,255,255,0.78) 55%,
            rgba(255,255,255,0.35) 80%,
            rgba(255,255,255,0.00) 100%
        );
    }

    .goal-strip span { font-weight: 900; }

    /* ✅ MOVE GAUGE UP: place ring between days-bar and submit/header area */
    .goal-strip-ring-anchor {
        position: absolute;
        right: 18px;
        top: -205px;  /* was -131px */
        z-index: 4;
    }

    .goal-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        padding: 12px 18px 18px;
        position: relative;
        z-index: 1;
    }

    .goal-btn {
        border-radius: 12px;
        padding: 7px 7px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        border: 1px solid rgba(255,255,255,0.12);
        cursor: not-allowed;
        min-height: 48px;
    }

    /* ✅ Make these clickable */
    #abc-log-production, #abc-production-breakdown { cursor: pointer; }

    .goal-btn-primary {
        background: var(--gold);
        color: #111827;
        box-shadow: 0 10px 18px -12px rgba(0,0,0,0.55);
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
        font-size: 22px;
        font-weight: 900;
        line-height: 1.05;
    }

    .goal-btn-sub {
        font-size: 16px;
        font-weight: 700;
        opacity: 0.85;
    }

    /* =========================================================
       ✅ BUTTON TEXT CENTERING (NO LAYOUT CHANGES OUTSIDE BUTTON)
       ========================================================= */
    #abc-log-production,
    #abc-production-breakdown {
        justify-content: center;     /* centers the content block */
        text-align: center;
        cursor: pointer;
    }
    #abc-log-production .goal-btn-left,
    #abc-production-breakdown .goal-btn-left {
        width: 100%;
        align-items: center;         /* centers "Log Production" / "Production Breakdown" */
    }

    /* =========================================================
       ✅ PRODUCTION BREAKDOWN MODAL (simple, self-contained)
       ========================================================= */
    .abc-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.45);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 24px;
    }
    .abc-modal-overlay.abc-modal-open { display: flex; }

    .abc-modal {
        width: min(860px, 96vw);
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 30px 60px rgba(0,0,0,0.30);
        overflow: hidden;
    }

    .abc-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 16px;
        border-bottom: 1px solid #e5e7eb;
        background: #fff;
    }

    .abc-modal-title {
        font-size: 18px;
        font-weight: 800;
        color: var(--text-main);
    }

    .abc-modal-close {
        border: none;
        background: transparent;
        font-size: 22px;
        cursor: pointer;
        color: #6b7280;
        line-height: 1;
    }

    .abc-modal-body { padding: 16px; }

    /* Responsive */
    @media (max-width: 1400px) {
        .goal-header-left { font-size: 40px; }

        .goal-ring { width: 190px; height: 190px; }
        .goal-ring::before { width: 152px; height: 152px; }
        .goal-percent { font-size: 46px; }
        .goal-complete { font-size: 15px; }

        .goal-strip-ring-anchor { top: -185px; } /* was -118px */
    }

    @media (max-width: 1100px) {
        .goal-card-wrap { width: 100%; }
        .top-search { min-width: 240px; }
    }

    @media (max-width: 860px) {
        .dashboard-header-top { flex-direction: column; align-items: stretch; }
        .dashboard-right-tools { justify-content: flex-start; }
        .top-search { min-width: 0; width: 100%; }

        .goal-body { grid-template-columns: 1fr; }
        .goal-actions { grid-template-columns: 1fr; }
        .goal-header-left { font-size: 34px; }

        .goal-ring { width: 176px; height: 176px; }
        .goal-ring::before { width: 140px; height: 140px; }
        .goal-percent { font-size: 42px; }
        .goal-complete { font-size: 13px; }

        .goal-strip-ring-anchor { right: 18px; top: -170px; } /* was -110px */
    }
</style>

<div class="dashboard-page">

    <div class="dashboard-header">
        <div class="dashboard-header-top">
            <div>
                <div class="dashboard-title">Dashboard</div>

                <div class="dashboard-subtitle local-greeting">Loading greeting…</div>

                <div class="dashboard-datetime local-time" data-server-time-ms="{{ $serverTimeMs }}">
                    Loading time…
                </div>
            </div>

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

    {{-- GOAL CARD (50% wide) --}}
    <div class="goal-card-wrap">
        <div class="goal-card" style="--progress: {{ $placeholderPercent }};">
            <div class="goal-header">
                <div class="goal-header-left">
                    <span class="goal-icon">●</span>
                    <span id="abc-goal-month">{{ $goalMonthName }}</span> Goal
                </div>

                <div class="goal-input-wrap">
                    <input
                        id="abc-goal-input"
                        class="goal-input"
                        type="text"
                        inputmode="numeric"
                        autocomplete="off"
                        value="${{ number_format($placeholderGoal, 0) }}"
                    >
                    <button id="abc-goal-submit" class="goal-save" type="button">Submit →</button>
                </div>
            </div>

            <div class="goal-body">
                <div>
                    <div class="goal-main">
                        <span class="amt" id="abc-goal-amount-display">${{ number_format($placeholderGoal, 0) }}</span>
                        <span class="ap"> AP</span>
                    </div>

                    <div class="goal-label">Collected Premium Needed:</div>
                    <div class="goal-needed-row">
                        <div class="goal-needed" id="abc-goal-needed-display">${{ number_format($placeholderNeededMonthly, 0) }}</div>
                    </div>
                </div>

                <div style="position:relative;">
                    {{-- reserved space; ring is positioned over strip below --}}
                </div>
            </div>

            <div class="goal-strip-wrap">
                <div class="goal-strip">
                    <span><span id="abc-days-left">{{ $daysLeft }}</span> days left</span>&nbsp;to reach goal
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
                {{-- 1) Center Log Production --}}
                {{-- 3) Wire Log Production to open the exact Activity modal flow --}}
                <button id="abc-log-production" class="goal-btn goal-btn-primary" type="button">
                    <div class="goal-btn-left">
                        <div class="goal-btn-title">Log Production</div>
                    </div>
                </button>

                {{-- 2) Remove Weekly/Monthly/Quarterly/Annual text + remove checkered box --}}
                {{-- 4) Wire Production Breakdown to pop up identical to Current Production card --}}
                <button id="abc-production-breakdown" class="goal-btn goal-btn-secondary" type="button">
                    <div class="goal-btn-left">
                        <div class="goal-btn-title">Production Breakdown</div>
                    </div>
                </button>
            </div>
        </div>
    </div>

    {{-- GRID START --}}
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

{{-- ✅ Production Breakdown Modal (identical structure to Current Production card) --}}
<div id="abc-production-modal" class="abc-modal-overlay" aria-hidden="true">
    <div class="abc-modal" role="dialog" aria-modal="true" aria-label="Production Breakdown">
        <div class="abc-modal-header">
            <div class="abc-modal-title">Production Breakdown</div>
            <button id="abc-production-modal-close" class="abc-modal-close" type="button" aria-label="Close">×</button>
        </div>
        <div class="abc-modal-body">
            <div class="dashboard-card" style="box-shadow:none; margin:0; border:none; padding:0;">
                <div class="production-title">Current Production</div>

                <div class="production-tabs-wrapper">
                    <div class="production-tabs" id="abc-production-tabs">
                        <button class="production-tab production-tab-active" data-modal-production-tab="day" type="button">Day</button>
                        <button class="production-tab" data-modal-production-tab="week" type="button">Week</button>
                        <button class="production-tab" data-modal-production-tab="month" type="button">Month</button>
                        <button class="production-tab" data-modal-production-tab="quarter" type="button">Quarter</button>
                        <button class="production-tab" data-modal-production-tab="year" type="button">Year</button>
                    </div>
                </div>

                <div class="dashboard-card-body production-stats" id="abc-production-modal-stats">
                    @php
                        $ranges = ['day','week','month','quarter','year'];
                    @endphp

                    @foreach($ranges as $idx => $r)
                        <div class="production-range {{ $idx === 0 ? 'production-range-active' : '' }}" data-modal-production-range="{{ $r }}">
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
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- LOCAL TIME + GREETING + MONTH + DAYS-LEFT SYNC (AFTER TODAY) + GOAL INPUT WIRING + BUTTON WIRING -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const timeEl = document.querySelector(".local-time");
    const greetEl = document.querySelector(".local-greeting");
    const serverTimeMsStr = timeEl.getAttribute("data-server-time-ms");

    const ms = parseInt(serverTimeMsStr, 10);
    const localDate = new Date(ms);

    if (isNaN(localDate.getTime())) {
        greetEl.innerText = "Good day, Agent";
        timeEl.innerText = "—";
        return;
    }

    const hour = localDate.getHours();
    let greeting = "Good ";
    if (hour < 12) greeting += "morning";
    else if (hour < 17) greeting += "afternoon";
    else greeting += "evening";

    greetEl.innerText = greeting + ", Agent";

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

    // ✅ Month sync for goal card (written out month)
    const goalMonthEl = document.getElementById("abc-goal-month");
    if (goalMonthEl) {
        const monthName = localDate.toLocaleDateString(undefined, { month: "long" });
        goalMonthEl.innerText = monthName;
    }

    // ✅ Days-left sync for goal card: days left in month AFTER today
    const daysLeftEl = document.getElementById("abc-days-left");
    if (daysLeftEl) {
        const y = localDate.getFullYear();
        const m = localDate.getMonth();
        const d = localDate.getDate();

        const startOfTomorrow = new Date(y, m, d + 1);
        const endOfMonth = new Date(y, m + 1, 0);
        const startOfEnd = new Date(y, m, endOfMonth.getDate());

        const msPerDay = 24 * 60 * 60 * 1000;

        if (startOfTomorrow > startOfEnd) {
            daysLeftEl.innerText = 0;
        } else {
            const diffDays = Math.round((startOfEnd - startOfTomorrow) / msPerDay) + 1;
            daysLeftEl.innerText = Math.max(diffDays, 0);
        }
    }

    // =========================================================
    // ✅ Goal Amount Submit Wiring (front-end)
    // =========================================================
    const goalInput = document.getElementById("abc-goal-input");
    const goalSubmit = document.getElementById("abc-goal-submit");
    const goalAmtDisplay = document.getElementById("abc-goal-amount-display");
    const neededDisplay = document.getElementById("abc-goal-needed-display");

    function parseMoneyToNumber(str) {
        if (!str) return 0;
        const cleaned = String(str).replace(/[^0-9.]/g, "");
        const n = parseFloat(cleaned);
        return isNaN(n) ? 0 : n;
    }

    function formatMoney0(n) {
        const v = Math.round(Number(n) || 0);
        return "$" + v.toLocaleString(undefined, { maximumFractionDigits: 0 });
    }

    function updateGoalUI(goalApNumber) {
        const goalAp = Math.max(0, Math.round(goalApNumber || 0));
        const needed = Math.round(goalAp / 12);

        if (goalAmtDisplay) goalAmtDisplay.innerText = formatMoney0(goalAp);
        if (neededDisplay) neededDisplay.innerText = formatMoney0(needed);
        if (goalInput) goalInput.value = formatMoney0(goalAp);
    }

    const saved = localStorage.getItem("abc_monthly_goal_ap");
    if (saved !== null) updateGoalUI(parseMoneyToNumber(saved));
    else updateGoalUI(0);

    if (goalInput) {
        goalInput.addEventListener("input", () => {
            const raw = parseMoneyToNumber(goalInput.value);
            const needed = Math.round((Math.max(0, raw)) / 12);
            if (neededDisplay) neededDisplay.innerText = formatMoney0(needed);
            if (goalAmtDisplay) goalAmtDisplay.innerText = formatMoney0(raw);
        });

        goalInput.addEventListener("blur", () => {
            const raw = parseMoneyToNumber(goalInput.value);
            updateGoalUI(raw);
        });
    }

    if (goalSubmit) {
        goalSubmit.addEventListener("click", () => {
            const raw = goalInput ? parseMoneyToNumber(goalInput.value) : 0;
            const goalAp = Math.max(0, Math.round(raw));
            localStorage.setItem("abc_monthly_goal_ap", String(goalAp));
            updateGoalUI(goalAp);
        });
    }

    // =========================================================
    // ✅ 3) Log Production wiring:
    // Make it trigger the SAME modal the Activity tab triggers.
    // Strategy:
    //  - Click the Activity sidebar link (most reliable)
    //  - Then try common modal triggers as fallback
    // =========================================================
    function clickActivityTabOrTrigger() {
        // First: click sidebar "Activity" link by href
        const hrefCandidates = [
            document.querySelector('a[href="/activity"]'),
            document.querySelector('a[href^="/activity"]'),
            document.querySelector('a[href*="activity"]'),
        ].filter(Boolean);

        if (hrefCandidates.length > 0) {
            hrefCandidates[0].click();
            return true;
        }

        // Second: click sidebar "Activity" link by text
        const allLinks = Array.from(document.querySelectorAll('a'));
        const activityTextLink = allLinks.find(a => (a.textContent || '').trim().toLowerCase() === 'activity');
        if (activityTextLink) {
            activityTextLink.click();
            return true;
        }

        // Third: try modal trigger selectors (if your app uses them)
        const modalTriggerCandidates = [
            document.querySelector('[data-open-activity]'),
            document.querySelector('#track-activity-btn'),
            document.querySelector('#trackActivityBtn'),
            document.querySelector('#track-activity'),
            document.querySelector('#trackActivity'),
            document.querySelector('.track-activity-btn'),
            document.querySelector('.open-activity-modal'),
            document.querySelector('[data-bs-target="#activityModal"]'),
            document.querySelector('[data-bs-target="#trackActivityModal"]'),
            document.querySelector('[data-modal="activity"]'),
        ].filter(Boolean);

        if (modalTriggerCandidates.length > 0) {
            modalTriggerCandidates[0].click();
            return true;
        }

        // Final fallback: dispatch event if the modal listens
        document.dispatchEvent(new CustomEvent("openActivityModal"));
        return false;
    }

    const logBtn = document.getElementById("abc-log-production");
    if (logBtn) {
        logBtn.addEventListener("click", () => {
            const ok = clickActivityTabOrTrigger();
            if (!ok) console.warn("Log Production: could not find Activity tab or trigger. Add your selector in dashboard.blade.php.");
        });
    }

    // =========================================================
    // ✅ 4) Production Breakdown modal wiring
    // =========================================================
    const modalOverlay = document.getElementById('abc-production-modal');
    const modalClose = document.getElementById('abc-production-modal-close');
    const breakdownBtn = document.getElementById('abc-production-breakdown');

    function openProductionModal() {
        if (!modalOverlay) return;
        modalOverlay.classList.add('abc-modal-open');
        modalOverlay.setAttribute('aria-hidden', 'false');

        // refresh modal stats for active tab
        refreshProductionModalCard();
    }

    function closeProductionModal() {
        if (!modalOverlay) return;
        modalOverlay.classList.remove('abc-modal-open');
        modalOverlay.setAttribute('aria-hidden', 'true');
    }

    if (breakdownBtn) breakdownBtn.addEventListener('click', openProductionModal);
    if (modalClose) modalClose.addEventListener('click', closeProductionModal);

    if (modalOverlay) {
        modalOverlay.addEventListener('click', (e) => {
            if (e.target === modalOverlay) closeProductionModal();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modalOverlay.classList.contains('abc-modal-open')) closeProductionModal();
        });
    }

    // Modal tab logic (identical to main card but scoped)
    const modalTabs = document.querySelectorAll('[data-modal-production-tab]');
    const modalRanges = document.querySelectorAll('[data-modal-production-range]');

    modalTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const range = tab.dataset.modalProductionTab;

            modalTabs.forEach(t => t.classList.remove('production-tab-active'));
            tab.classList.add('production-tab-active');

            modalRanges.forEach(r => {
                r.classList.toggle('production-range-active',
                    r.dataset.modalProductionRange === range
                );
            });

            refreshProductionModalCard();
        });
    });

    window.refreshProductionModalCard = function() {
        const activeTab = document.querySelector('[data-modal-production-tab].production-tab-active');
        const active = activeTab ? activeTab.dataset.modalProductionTab : 'day';

        fetch(`/activity/totals/${active}`)
            .then(r => r.json())
            .then(data => {
                const rows = document.querySelectorAll(
                    `[data-modal-production-range="${active}"] .production-value`
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
    }
});
</script>

<!-- TAB LOGIC -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.production-tab[data-production-tab]');
    const ranges = document.querySelectorAll('.production-range[data-production-range]');

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
    const active = document.querySelector(".production-tab-active[data-production-tab]").dataset.productionTab;

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

    // If modal is open, refresh it too
    const overlay = document.getElementById('abc-production-modal');
    if (overlay && overlay.classList.contains('abc-modal-open') && window.refreshProductionModalCard) {
        window.refreshProductionModalCard();
    }
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

@extends('layouts.app')
@section('content')
@php
    /**
     * ✅ Robust time source for JS: epoch milliseconds (UTC)
     */
    $serverTimeMs = now()->utc()->timestamp * 1000;

    // ✅ Reset goal to 0 (until backend persistence is wired)
    $goalMonthName = now()->format('F'); // fallback until JS sets it
    $placeholderGoal = 0; // $0
    $placeholderPercent = 0; // 0%
    $placeholderNeededMonthly = 0; // $0

    /**
     * ✅ Days-left (server fallback) should be "days left AFTER today"
     */
    $tomorrowStart = now()->addDay()->startOfDay();
    $endOfMonthStart = now()->endOfMonth()->startOfDay();
    $daysLeftRaw = $tomorrowStart->diffInDays($endOfMonthStart, false) + 1; // inclusive from tomorrow
    $daysLeft = max($daysLeftRaw, 0);

    /**
     * ✅ Gideon safe payload (prevents 500 if controller isn't passing $gideon yet)
     */
    $gideonSafe = $gideon ?? [
        'scan' => [
            'status' => 'idle',
            'minutes_ago' => null,
            'scope' => ['Leads', 'Beneficiaries', 'Emergency Contacts', 'Open Loops'],
        ],
        'actions' => [],
    ];
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

    /* ✅ ADD: Gideon Priority Actions CSS (safe even if file missing; it just won't load styles) */
    @import url('/css/gideon_priority_actions.css');

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
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 0 12px;
        cursor: pointer;
        box-shadow: 0 10px 20px -16px rgba(0,0,0,0.25);
        font-weight: 700;
        color: var(--text-main);
    }
    .top-agent-avatar {
        width: 26px;
        height: 26px;
        border-radius: 999px;
        background: var(--abc-black);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 800;
    }

    /* ===== Top Row Layout ===== */
    .dashboard-top-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    /* ===== Goal Card ===== */
    .goal-card-wrap { width: 100%; }
    .goal-card {
        background: var(--abc-black);
        border-radius: 18px;
        padding: 16px 18px 16px 18px;
        position: relative;
        color: #fff;
        border: 1px solid rgba(201,162,39,.35);
        box-shadow: 0 20px 30px -20px rgba(0,0,0,0.55);
        overflow: hidden;
        --progress: 0;
        --gauge-color: var(--danger-red);
    }
    .goal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 10px;
    }
    .goal-header-left {
        font-weight: 900;
        font-size: 15px;
        letter-spacing: .2px;
        display: flex;
        align-items: center;
        gap: 8px;
        color: #fff;
    }
    .goal-input-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .goal-input {
        width: 150px;
        background: #0b1220;
        border: 1px solid rgba(201,162,39,.35);
        border-radius: 12px;
        padding: 8px 10px;
        color: #fff;
        font-weight: 800;
        font-size: 14px;
        outline: none;
        text-align: left;
    }
    .goal-save {
        border: none;
        background: var(--gold);
        color: #0b1220;
        font-weight: 900;
        border-radius: 12px;
        padding: 9px 12px;
        cursor: pointer;
        font-size: 13px;
        box-shadow: 0 10px 20px -16px rgba(0,0,0,0.6);
    }
    .goal-body {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        align-items: center;
        padding: 8px 0 6px;
    }
    .goal-main {
        font-size: 30px;
        font-weight: 900;
        line-height: 1.1;
        margin-bottom: 2px;
    }
    .goal-main .amt { color: #fff; }
    .goal-main .ap {
        font-size: 14px;
        font-weight: 900;
        color: rgba(255,255,255,0.7);
    }
    .goal-label {
        font-size: 12px;
        font-weight: 800;
        color: rgba(255,255,255,0.75);
        margin-top: 6px;
    }
    .goal-needed-row {
        display: flex;
        align-items: baseline;
        gap: 10px;
        margin-top: 4px;
    }
    .goal-needed {
        font-size: 20px;
        font-weight: 900;
        color: #fff;
    }
    .goal-strip-wrap {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 10px;
        gap: 12px;
    }
    .goal-strip {
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.10);
        color: rgba(255,255,255,0.85);
        border-radius: 12px;
        padding: 10px 12px;
        font-weight: 800;
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }
    .goal-ring {
        width: 86px;
        height: 86px;
        border-radius: 999px;
        position: relative;
        background:
            conic-gradient(var(--gauge-color) calc(var(--progress) * 1%), var(--ring-track) 0);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .goal-ring::after {
        content: "";
        width: 70px;
        height: 70px;
        border-radius: 999px;
        background: var(--abc-black-2);
        border: 1px solid rgba(255,255,255,0.08);
        position: absolute;
        inset: 8px;
    }
    .goal-ring-center {
        position: relative;
        z-index: 2;
        text-align: center;
        line-height: 1.05;
    }
    .goal-percent {
        font-weight: 900;
        font-size: 18px;
        color: #fff;
    }
    .goal-complete {
        font-weight: 800;
        font-size: 11px;
        color: rgba(255,255,255,0.65);
    }
    .goal-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-top: 12px;
    }
    .goal-btn {
        border: 1px solid rgba(255,255,255,0.10);
        background: rgba(255,255,255,0.06);
        border-radius: 14px;
        padding: 12px 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        cursor: pointer;
        color: #fff;
        box-shadow: 0 14px 20px -20px rgba(0,0,0,0.7);
        transition: transform .12s ease, background .12s ease;
    }
    .goal-btn:hover { transform: translateY(-1px); background: rgba(255,255,255,0.08); }
    .goal-btn-primary { border-color: rgba(201,162,39,.55); }
    .goal-btn-secondary { border-color: rgba(255,255,255,0.12); }
    .goal-btn-title { font-weight: 900; font-size: 14px; }
    .goal-btn-sub { font-size: 12px; font-weight: 700; color: rgba(255,255,255,0.7); margin-top: 2px; }
    .goal-btn-left { display: flex; flex-direction: column; }

    /* ===== Side Cards ===== */
    .side-cards-wrap {
        display: grid;
        grid-template-rows: 1fr 1fr;
        gap: 20px;
    }
    .dashboard-card {
        background: #fff;
        border-radius: 18px;
        padding: 14px 16px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 10px 25px -20px rgba(0,0,0,0.25);
    }
    .dashboard-card-title-row {
        display:flex;
        align-items:center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 8px;
    }
    .dashboard-card-title {
        font-size: 14px;
        font-weight: 900;
        color: var(--text-main);
        display:flex;
        align-items:center;
        gap: 8px;
    }
    .dashboard-card-icon { font-size: 16px; }
    .dashboard-card-body { font-size: 14px; color: var(--text-subtle); }
    .dashboard-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        gap: 10px;
        font-size: 14px;
        color: var(--text-subtle);
    }
    .dashboard-list li {
        padding: 10px 10px;
        border-radius: 12px;
        background: #f9fafb;
        border: 1px solid #eef2f7;
    }

    /* ===== Lower grid ===== */
    .dashboard-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
    }
    .dashboard-card-span-all { grid-column: 1 / -1; }

    /* ===== Production Breakdown Modal ===== */
    .abc-modal-wide { max-width: 680px; }
    .production-tab-row {
        display:flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 14px;
    }
    .production-tab {
        padding: 8px 12px;
        border-radius: 999px;
        border: 1px solid rgba(201,162,39,.35);
        background: #0b1220;
        color: #fff;
        font-weight: 900;
        font-size: 12px;
        cursor:pointer;
        user-select:none;
    }
    .production-tab-active {
        background: var(--gold);
        color: #0b1220;
        border-color: rgba(201,162,39,.55);
    }
    .production-range { display:none; }
    .production-range-active { display:block; }

    .production-stat-row {
        display:flex;
        align-items:center;
        justify-content: space-between;
        padding: 10px 12px;
        border-radius: 12px;
        background: #0f172a;
        border: 1px solid rgba(255,255,255,0.08);
        margin-bottom: 8px;
        color:#e5e7eb;
    }
    .production-label { font-weight: 800; }
    .production-value { font-weight: 900; }
</style>

<div class="dashboard-page">

    <div class="dashboard-header">
        <div class="dashboard-header-top">
            <div>
                <div class="dashboard-title">Dashboard</div>
                <div class="dashboard-subtitle"><span class="local-greeting">Good day</span>, Agent</div>
                <div class="dashboard-datetime local-time" data-server-time-ms="{{ $serverTimeMs }}">—</div>
            </div>

            <div class="dashboard-right-tools">
                <div class="top-search" aria-label="Search">
                    <div class="icon">🔎</div>
                    <input type="text" placeholder="Search contacts, leads, book of business…" />
                </div>
                <button class="top-icon-btn" type="button" aria-label="Notifications">
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

    {{-- ✅ TOP ROW --}}
    <div class="dashboard-top-row">

        {{-- GOAL CARD (LEFT 50%) --}}
        <div class="goal-card-wrap">
            <div id="abc-goal-card" class="goal-card" style="--progress: {{ $placeholderPercent }}; --gauge-color: var(--danger-red);">
                <div class="goal-header">
                    <div class="goal-header-left">
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
                            <span class="amt" id="abc-goal-ap-earned">$0</span>
                            <span class="ap"> AP</span>
                        </div>

                        <div class="goal-premium-line" style="margin-top:6px; font-size:12px; font-weight:800; color: rgba(255,255,255,0.70);">
                            Premium Collected (MTD): <span id="abc-goal-premium-collected">$0</span>
                        </div>

                        <div class="goal-label" style="margin-top:10px;">Collected Premium Needed:</div>
                        <div class="goal-needed-row">
                            <div class="goal-needed" id="abc-goal-needed-display">${{ number_format($placeholderNeededMonthly, 0) }}</div>

                        </div>
                    </div>
                    <div style="position:relative;"></div>
                </div>

                <div class="goal-strip-wrap">
                    <div class="goal-strip">
                        <span><span id="abc-days-left">{{ $daysLeft }}</span> days left</span>&nbsp;to reach goal
                    </div>
                    <div class="goal-strip-ring-anchor">
                        <div id="abc-goal-ring" class="goal-ring" aria-label="Goal progress ring">
                            <div class="goal-ring-center">
                                <div id="abc-goal-percent-text" class="goal-percent">{{ $placeholderPercent }}%</div>
                                <div class="goal-complete">Complete</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="goal-actions">
                    <button id="abc-log-production" class="goal-btn goal-btn-primary" type="button">
                        <div class="goal-btn-left">
                            <div class="goal-btn-title">Log Production</div>
                        </div>
                    </button>
                    <button id="abc-production-breakdown" class="goal-btn goal-btn-secondary" type="button">
                        <div class="goal-btn-left">
                            <div class="goal-btn-title">Production Breakdown</div>
                        </div>
                        <div style="font-size:14px; font-weight:900;">›</div>
                    </button>
                </div>
            </div>
        </div>

        {{-- RIGHT 50%: Upcoming + Insights stacked --}}
        <div class="side-cards-wrap">

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

        </div>
    </div>

    {{-- GRID START --}}
    <div class="dashboard-grid">

        {{-- GIDEON OPPORTUNITIES (FULL WIDTH) --}}
        <div class="dashboard-card dashboard-card-span-all">
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

                @if(\Illuminate\Support\Facades\View::exists('partials.gideon_priority_actions'))
                    @include('partials.gideon_priority_actions', ['gideon' => $gideonSafe])
                @else
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
                @endif

            </div>
        </div>

    </div>{{-- END GRID --}}

    <div class="dashboard-footer-note" style="margin-top: 20px; color:#6b7280;">
        © {{ now()->year }} Agency Builder CRM — Tier 1
    </div>
</div>

{{-- ✅ Production Breakdown Modal --}}
<div class="modal fade" id="productionBreakdownModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered abc-modal-wide">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="font-weight:900;">Production Breakdown</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body" style="background:#0b1220; color:#fff;">
                <div id="abc-breakdown-tabs" class="production-tab-row">
                    <div class="production-tab production-tab-active" data-production-tab="day">Day</div>
                    <div class="production-tab" data-production-tab="week">Week</div>
                    <div class="production-tab" data-production-tab="month">Month</div>
                    <div class="production-tab" data-production-tab="quarter">Quarter</div>
                    <div class="production-tab" data-production-tab="year">Year</div>
                </div>

                <div id="abc-breakdown-stats">
                    @foreach(['day','week','month','quarter','year'] as $rng)
                        <div class="production-range {{ $rng === 'day' ? 'production-range-active' : '' }}"
                             data-production-range="{{ $rng }}">
                            <div class="production-stat-row"><div class="production-label">Leads Worked</div><div class="production-value">0</div></div>
                            <div class="production-stat-row"><div class="production-label">Calls</div><div class="production-value">0</div></div>
                            <div class="production-stat-row"><div class="production-label">Stops</div><div class="production-value">0</div></div>
                            <div class="production-stat-row"><div class="production-label">Presentations</div><div class="production-value">0</div></div>
                            <div class="production-stat-row"><div class="production-label">Apps Written</div><div class="production-value">0</div></div>
                            <div class="production-stat-row"><div class="production-label">Premium Collected</div><div class="production-value">$0.00</div></div>
                            <div class="production-stat-row"><div class="production-label">AP</div><div class="production-value">$0.00</div></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

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

    // ✅ Month sync for goal card
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
            daysLeftEl.innerText = diffDays;
        }
    }

    // =========================================================
    // ✅ Goal input wiring (AP goal is set top-right)
    // =========================================================
    const goalInput = document.getElementById("abc-goal-input");
    const goalSubmit = document.getElementById("abc-goal-submit");

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
    function setGoalInput(goalApNumber) {
        const goalAp = Math.max(0, Math.round(goalApNumber || 0));
        if (goalInput) goalInput.value = formatMoney0(goalAp);
    }

    const savedGoal = localStorage.getItem("abc_monthly_goal_ap");
    setGoalInput(savedGoal !== null ? parseMoneyToNumber(savedGoal) : 0);

    if (goalInput) {
        goalInput.addEventListener("blur", () => {
            const raw = parseMoneyToNumber(goalInput.value);
            setGoalInput(raw);
        });
    }

    if (goalSubmit) {
        goalSubmit.addEventListener("click", () => {
            const raw = goalInput ? parseMoneyToNumber(goalInput.value) : 0;
            const goalAp = Math.max(0, Math.round(raw));
            localStorage.setItem("abc_monthly_goal_ap", String(goalAp));
            setGoalInput(goalAp);
            if (window.refreshGoalCard) window.refreshGoalCard(true);
        });
    }

    // =========================================================
    // ✅ Log Production wiring (opens the Track Daily Activity modal)
    // =========================================================
    async function openActivityModal() {
        if (typeof bootstrap === "undefined") return;

        let modalEl = document.getElementById("activityModal");
        if (modalEl) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
            return;
        }

        const existingWrap = document.getElementById("activity-modal-injected");
        if (existingWrap) {
            modalEl = document.getElementById("activityModal");
            if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).show();
            return;
        }

        try {
            const res = await fetch("/activity/popup?_=" + Date.now(), {
                headers: { "X-Requested-With": "XMLHttpRequest" },
                cache: "no-store"
            });
            const html = await res.text();
            const wrap = document.createElement("div");
            wrap.id = "activity-modal-injected";
            wrap.innerHTML = html;
            document.body.appendChild(wrap);

            modalEl = document.getElementById("activityModal");
            if (!modalEl) return;

            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } catch (err) {
            console.error(err);
        }
    }

    const logBtn = document.getElementById("abc-log-production");
    if (logBtn) logBtn.addEventListener("click", openActivityModal);

    // =========================================================
    // ✅ Activity modal SAVE handler (required by activity/popup.blade.php)
    // - Posts to /activity (ActivityController@store)
    // - Updates Goal Card immediately (premium + AP)
    // - Optionally refreshes Production Breakdown if open
    // =========================================================
    window.ABC_activitySaveClick = async function (e) {
        e?.preventDefault?.();

        const form = document.getElementById("activityForm");
        const saveBtn = document.getElementById("saveActivityBtn");
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

        if (!form || !saveBtn) return;

        if (saveBtn.dataset.abcBusy === "1") return;
        saveBtn.dataset.abcBusy = "1";

        try {
            const url = form.getAttribute("action") || "/activity";
            const formData = new FormData(form);

            const res = await fetch(url, {
                method: "POST",
                headers: {
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": csrf,
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: formData,
                cache: "no-store",
            });

            const payload = await res.json().catch(() => null);
            if (!res.ok || !payload?.success) {
                throw new Error(payload?.message || "Unable to save activity");
            }

            // ✅ Update Goal Card using month_totals from server (authoritative)
            const mt = payload.month_totals || {};
            if (typeof window.applyGoalCardFromTotals === "function") {
                window.applyGoalCardFromTotals(
                    Number(mt.premium_collected || 0),
                    Number(mt.ap || 0)
                );
            } else if (window.refreshGoalCard) {
                window.refreshGoalCard(true);
            }

            // ✅ Close the modal
            const modalEl = document.getElementById("activityModal");
            if (modalEl && typeof bootstrap !== "undefined") {
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            }

            // ✅ Remove injected modal markup so it's always fresh next open
            const wrap = document.getElementById("activity-modal-injected");
            if (wrap) wrap.remove();

            // ✅ If production breakdown is open, refresh it too
            const breakdownEl = document.getElementById("productionBreakdownModal");
            if (breakdownEl && breakdownEl.classList.contains("show")) {
                if (window.refreshProductionBreakdownModal) window.refreshProductionBreakdownModal(true);
            }
        } catch (err) {
            console.error(err);
            alert("Could not save production. Please try again.");
        } finally {
            saveBtn.dataset.abcBusy = "0";
        }
    };

    // =========================================================
    // ✅ Production Breakdown button opens modal
    // =========================================================
    const breakdownBtn = document.getElementById("abc-production-breakdown");
    if (breakdownBtn) {
        breakdownBtn.addEventListener("click", () => {
            const modalEl = document.getElementById("productionBreakdownModal");
            if (!modalEl || typeof bootstrap === "undefined") return;

            bootstrap.Modal.getOrCreateInstance(modalEl).show();

            if (window.refreshProductionBreakdownModal) window.refreshProductionBreakdownModal(true);
        });
    }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabWrap = document.getElementById('abc-breakdown-tabs');
    const statsWrap = document.getElementById('abc-breakdown-stats');
    if (!tabWrap || !statsWrap) return;

    const tabs = tabWrap.querySelectorAll('.production-tab');
    const ranges = statsWrap.querySelectorAll('.production-range');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const range = tab.dataset.productionTab;

            tabs.forEach(t => t.classList.remove('production-tab-active'));
            tab.classList.add('production-tab-active');

            ranges.forEach(r => {
                r.classList.toggle('production-range-active', r.dataset.productionRange === range);
            });

            if (window.refreshProductionBreakdownModal) window.refreshProductionBreakdownModal(true);
        });
    });
});
</script>

<script>
window.refreshProductionBreakdownModal = function(force = false) {
    const statsWrap = document.getElementById('abc-breakdown-stats');
    const tabsWrap = document.getElementById('abc-breakdown-tabs');
    if (!statsWrap || !tabsWrap) return Promise.resolve();

    const activeTab = tabsWrap.querySelector('.production-tab-active');
    if (!activeTab) return Promise.resolve();

    const active = activeTab.dataset.productionTab;
    const url = `/activity/totals/${active}` + (force ? `?_=${Date.now()}` : "");

    return fetch(url, { cache: "no-store" })
        .then(r => r.json())
        .then(data => {
            const rows = statsWrap.querySelectorAll(
                `.production-range[data-production-range="${active}"] .production-value`
            );
            if (rows.length === 7) {
                rows[0].innerText = data.leads_worked;
                rows[1].innerText = data.calls;
                rows[2].innerText = data.stops;
                rows[3].innerText = data.presentations;
                rows[4].innerText = data.apps_written;
                rows[5].innerText = "$" + Number(data.premium_collected || 0).toFixed(2);
                rows[6].innerText = "$" + Number(data.ap || 0).toFixed(2);
            }
        })
        .catch(() => {});
};
</script>

<!-- APPLY GOAL CARD FROM TOTALS -->
<script>
window.applyGoalCardFromTotals = function(premiumCollected, apEarned) {
    const goalCard = document.getElementById('abc-goal-card');
    const ring = document.getElementById('abc-goal-ring');
    const percentText = document.getElementById('abc-goal-percent-text');
    const neededDisplay = document.getElementById('abc-goal-needed-display');
    const apEarnedEl = document.getElementById('abc-goal-ap-earned');
    const premiumEl = document.getElementById('abc-goal-premium-collected');

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
    function formatMoney2(n) {
        const v = Number(n) || 0;
        return "$" + v.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Gauge color is based on AP progress % (not premium).
    function gaugeColorForPercent(pct) {
        const p = Number(pct) || 0;
        if (p <= 25) return '#ffffff';
        if (p <= 50) return '#86efac';
        if (p <= 80) return '#22c55e';
        if (p <= 99) return '#15803d';
        return '#c9a227';
    }

    // ✅ AP Goal is saved locally (agent sets it top-right).
    const savedGoal = localStorage.getItem("abc_monthly_goal_ap");
    const goalAp = Math.max(0, Math.round(parseMoneyToNumber(savedGoal)));

    const prem = Number(premiumCollected || 0);
    const ap = Number(apEarned || 0);

    // ✅ Premium needed is derived from remaining AP divided by 12.
    const remainingAp = Math.max(0, goalAp - ap);
    const remainingPremium = Math.max(0, remainingAp / 12);

    // ✅ Progress % is AP earned vs AP goal.
    let rawPct = 0;
    if (goalAp > 0) {
        rawPct = Math.round((ap / goalAp) * 100);
    }
    const ringPct = Math.max(0, Math.min(100, rawPct));
    const color = gaugeColorForPercent(rawPct);

    if (premiumEl) premiumEl.innerText = formatMoney0(prem);
    if (neededDisplay) neededDisplay.innerText = formatMoney0(remainingPremium);
    if (apEarnedEl) apEarnedEl.innerText = formatMoney0(ap);

    if (percentText) percentText.innerText = ringPct + "%";

    if (goalCard) {
        goalCard.style.setProperty('--progress', String(ringPct));
        goalCard.style.setProperty('--gauge-color', color);
    }
    if (ring) {
        ring.style.setProperty('--progress', String(ringPct));
    }
};
</script>

<!-- GOAL CARD REFRESH (fetch month totals) -->
<script>
window.refreshGoalCard = function(force = false) {
    const url = `/activity/totals/month` + (force ? `?_=${Date.now()}` : "");
    return fetch(url, { cache: "no-store" })
        .then(r => r.json())
        .then(data => {
            const premiumCollected = Number(data.premium_collected || 0);
            const apEarned = Number(data.ap || 0);
            if (typeof window.applyGoalCardFromTotals === "function") {
                window.applyGoalCardFromTotals(premiumCollected, apEarned);
            }
        })
        .catch(() => {});
};
</script>

<!-- INITIAL LOAD -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    if (window.refreshGoalCard) window.refreshGoalCard(true);
});
</script>

<!-- ✅ Gideon Deep Scan button wiring (SAFE no-op unless button exists) -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const btn = document.getElementById("gideonScanBtn");
    if (!btn) return;

    btn.addEventListener("click", function () {
        btn.disabled = true;
        btn.innerText = "Scanning…";

        fetch("/gideon/scan", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                "Accept": "application/json",
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ scope: "all" })
        })
        .then(r => r.json())
        .then(() => {
            btn.innerText = "Scan Complete";
            setTimeout(() => {
                btn.innerText = "Run Gideon Scan";
                btn.disabled = false;
            }, 2000);
        })
        .catch(() => {
            btn.innerText = "Scan Failed";
            setTimeout(() => {
                btn.innerText = "Run Gideon Scan";
                btn.disabled = false;
            }, 2000);
        });
    });
});
</script>
@endsection

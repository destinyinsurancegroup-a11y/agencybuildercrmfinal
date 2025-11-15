@extends('layouts.app')
@section('content')

@php
    // Server datetime (UTC)
    $serverTime = now()->toDateTimeString();

    // Time-based greeting defaults
    $hour = now()->timezone('UTC')->format('H');

    if ($hour < 12) {
        $greeting = 'morning';
    } elseif ($hour < 18) {
        $greeting = 'afternoon';
    } else {
        $greeting = 'evening';
    }
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
    }

    .dashboard-page {
        padding: 30px 40px 40px;
        background: var(--bg-page);
        min-height: 100vh;
        font-family: 'Inter', sans-serif;
    }

    .dashboard-header {
        margin-bottom: 32px;
    }

    .dashboard-title {
        font-size: 32px;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 8px;
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

    /* Search */
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
    }

    /* Card grid */
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 20px;
    }

    @media (max-width: 1280px) {
        .dashboard-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 768px) {
        .dashboard-grid {
            grid-template-columns: minmax(0, 1fr);
        }
    }

    /* Cards (original size restored) */
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
    }

    /* Current Production – unchanged except behavior fix */
    .production-title {
        text-align:center;
        font-size:20px;
        font-weight:700;
        color:var(--text-main);
        margin-bottom:14px;
    }

    .production-tabs-wrapper {
        display:flex;
        justify-content:center;
        margin-bottom:18px;
    }

    .production-tabs {
        display:inline-flex;
        align-items:center;
        gap:6px;
        background:#f9fafb;
        padding:4px;
        border-radius:999px;
        border:1px solid #e5e7eb;
    }

    .production-tab {
        border:none;
        background:transparent;
        font-size:14px;
        padding:6px 12px;
        border-radius:999px;
        cursor:pointer;
        color:#6b7280;
        font-weight:600;
    }

    .production-tab-active {
        background:var(--gold);
        color:#111827;
        box-shadow:0 3px 6px rgba(0,0,0,0.25);
    }

    .production-range { display:none; }
    .production-range-active { display:block; }
</style>

<div class="dashboard-page">

    {{-- Header --}}
    <div class="dashboard-header">
        <div class="dashboard-title">Dashboard</div>

        <div class="dashboard-subtitle">
            Good {{ $greeting }}
        </div>

        <!-- Local time (auto-adjusted in browser) -->
        <div class="dashboard-datetime local-time" data-server-time="{{ $serverTime }}">
            Loading time…
        </div>

        <div class="dashboard-search-row">
            <div class="dashboard-search-wrapper">
                <input type="text" class="dashboard-search-input" placeholder="Search contacts, leads, or clients...">
                <button class="dashboard-search-button">🔍 Search</button>
            </div>
        </div>
    </div>

    {{-- Cards --}}
    <div class="dashboard-grid">

        {{-- CURRENT PRODUCTION --}}
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
                    <table>
                        <tr><td class="production-label">Leads Worked</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Calls</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Stops</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Presentations</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Apps Written</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Premium Collected</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">AP (Annualized Premium)</td><td class="production-value">--</td></tr>
                    </table>
                </div>

                <div class="production-range" data-production-range="week">
                    <table>
                        <tr><td class="production-label">Leads Worked</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Calls</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Stops</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Presentations</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Apps Written</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Premium Collected</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">AP (Annualized Premium)</td><td class="production-value">--</td></tr>
                    </table>
                </div>

                <div class="production-range" data-production-range="month">
                    <table>
                        <tr><td class="production-label">Leads Worked</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Calls</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Stops</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Presentations</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Apps Written</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Premium Collected</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">AP (Annualized Premium)</td><td class="production-value">--</td></tr>
                    </table>
                </div>

                <div class="production-range" data-production-range="quarter">
                    <table>
                        <tr><td class="production-label">Leads Worked</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Calls</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Stops</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Presentations</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Apps Written</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Premium Collected</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">AP (Annualized Premium)</td><td class="production-value">--</td></tr>
                    </table>
                </div>

                <div class="production-range" data-production-range="year">
                    <table>
                        <tr><td class="production-label">Leads Worked</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Calls</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Stops</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Presentations</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Apps Written</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Premium Collected</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">AP (Annualized Premium)</td><td class="production-value">--</td></tr>
                    </table>
                </div>

            </div>
        </div>

        {{-- UPCOMING APPOINTMENTS --}}
        <div class="dashboard-card">
            <div class="dashboard-card-title-row">
                <div class="dashboard-card-title">
                    <span class="dashboard-card-icon">📅</span> Upcoming Appointments
                </div>
            </div>
            <div class="dashboard-card-body">
                <ul class="dashboard-list">
                    <li>--</li>
                    <li>--</li>
                </ul>
            </div>
        </div>

        {{-- TODAY'S INSIGHTS --}}
        <div class="dashboard-card">
            <div class="dashboard-card-title-row">
                <div class="dashboard-card-title">
                    <span class="dashboard-card-icon">✨</span> Today’s Insights
                </div>
            </div>
            <div class="dashboard-card-body">
                <ul class="dashboard-list">
                    <li>Birthdays this week: --</li>
                    <li>Anniversaries this week: --</li>
                </ul>
            </div>
        </div>

        {{-- RECENTLY ADDED --}}
        <div class="dashboard-card">
            <div class="dashboard-card-title-row">
                <div class="dashboard-card-title">
                    <span class="badge-new">NEW</span> Recently Added
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


<!-- LOCAL TIME SCRIPT -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const el = document.querySelector(".local-time");
    if (!el) return;

    const serverTime = el.getAttribute("data-server-time");
    const localDate = new Date(serverTime + " UTC");

    el.innerText = localDate.toLocaleString();
});
</script>

<!-- TAB LOGIC -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.production-tab');
    const ranges = document.querySelectorAll('.production-range');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const range = tab.getAttribute('data-production-tab');

            tabs.forEach(t => t.classList.remove('production-tab-active'));
            tab.classList.add('production-tab-active');

            ranges.forEach(r => {
                r.classList.toggle('production-range-active',
                    r.getAttribute('data-production-range') === range
                );
            });
        });
    });
});
</script>

@endsection

@extends('layouts.app')

@section('content')
@php
    // Time-based greeting
    $tz = config('app.timezone', 'UTC');
    $now = now()->timezone($tz);
    $hour = (int) $now->format('H');

    if ($hour < 12) {
        $greeting = 'morning';
    } elseif ($hour < 18) {
        $greeting = 'afternoon';
    } else {
        $greeting = 'evening';
    }
@endphp

<style>
    /* ---------- DASHBOARD LAYOUT ---------- */
    .dashboard-page {
        padding: 24px 32px 40px;
        background: #f5f5f5;
        min-height: 100vh;
        font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .dashboard-header {
        margin-bottom: 24px;
    }

    .dashboard-title {
        font-size: 26px;
        font-weight: 700;
        color: #111827;
        margin-bottom: 4px;
    }

    .dashboard-subtitle {
        font-size: 14px;
        color: #4b5563;
        margin-bottom: 4px;
    }

    .dashboard-datetime {
        font-size: 13px;
        color: #6b7280;
    }

    .dashboard-search-row {
        margin-top: 18px;
        margin-bottom: 24px;
    }

    .dashboard-search-wrapper {
        max-width: 420px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .dashboard-search-input {
        flex: 1;
        padding: 8px 10px;
        border-radius: 6px;
        border: 1px solid #d1d5db;
        font-size: 13px;
    }

    .dashboard-search-button {
        padding: 7px 12px;
        border-radius: 6px;
        border: none;
        background: #fbbf24;
        color: #111827;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 0 1px 2px rgba(0,0,0,0.08);
    }

    .dashboard-search-button:hover {
        background: #f59e0b;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }

    @media (max-width: 1200px) {
        .dashboard-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 768px) {
        .dashboard-grid {
            grid-template-columns: minmax(0, 1fr);
        }
    }

    .dashboard-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 16px 18px 16px;
        box-shadow:
            0 10px 15px -3px rgba(0,0,0,0.08),
            0 4px 6px -4px rgba(0,0,0,0.06);
        border: 1px solid #e5e7eb;
    }

    .dashboard-card-title-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
        gap: 8px;
    }

    .dashboard-card-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 15px;
        font-weight: 700;
        color: #111827;
    }

    .dashboard-card-icon {
        width: 18px;
        height: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #fef3c7;
        color: #92400e;
        font-size: 11px;
    }

    .dashboard-card-body {
        font-size: 13px;
        color: #374151;
    }

    .dashboard-list {
        margin: 0;
        padding-left: 18px;
    }

    .dashboard-list li {
        margin-bottom: 4px;
    }

    .dashboard-footer-note {
        margin-top: 18px;
        font-size: 11px;
        color: #9ca3af;
        text-align: center;
    }

    /* ---------- PRODUCTION TABS ---------- */
    .production-tabs {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #f9fafb;
        padding: 2px;
        border-radius: 999px;
        border: 1px solid #e5e7eb;
    }

    .production-tab {
        border: none;
        background: transparent;
        font-size: 11px;
        padding: 4px 9px;
        border-radius: 999px;
        cursor: pointer;
        color: #4b5563;
        font-weight: 500;
        transition: background 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
    }

    .production-tab-active {
        background: #fbbf24;
        color: #111827;
        box-shadow: 0 1px 2px rgba(0,0,0,0.12);
    }

    .production-stats {
        margin-top: 4px;
    }

    .production-stats table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    .production-stats td {
        padding: 2px 0;
    }

    .production-label {
        color: #4b5563;
    }

    .production-value {
        text-align: right;
        font-weight: 600;
        color: #111827;
    }

    .production-range {
        display: none;
    }

    .production-range-active {
        display: block;
    }

    .badge-new {
        display: inline-flex;
        align-items: center;
        padding: 2px 6px;
        border-radius: 999px;
        background: #dbeafe;
        color: #1d4ed8;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        margin-right: 4px;
    }
</style>

<div class="dashboard-page">
    {{-- Header --}}
    <div class="dashboard-header">
        <div class="dashboard-title">Dashboard</div>
        <div class="dashboard-subtitle">
            Good {{ $greeting }} — here’s your daily overview.
        </div>
        <div class="dashboard-datetime">
            {{ $now->format('l, F j, Y • g:i A') }}
        </div>

        <div class="dashboard-search-row">
            <div class="dashboard-search-wrapper">
                <input
                    type="text"
                    class="dashboard-search-input"
                    placeholder="Search contacts, leads, or clients..."
                >
                <button class="dashboard-search-button">
                    🔍 Search
                </button>
            </div>
        </div>
    </div>

    {{-- Cards row --}}
    <div class="dashboard-grid">

        {{-- CURRENT PRODUCTION (WITH TABS) --}}
        <div class="dashboard-card" id="production-card">
            <div class="dashboard-card-title-row">
                <div class="dashboard-card-title">
                    <span class="dashboard-card-icon">✓</span>
                    Current Production
                </div>
                <div class="production-tabs">
                    <button class="production-tab production-tab-active" data-production-tab="day">Day</button>
                    <button class="production-tab" data-production-tab="week">Week</button>
                    <button class="production-tab" data-production-tab="month">Month</button>
                    <button class="production-tab" data-production-tab="quarter">Quarter</button>
                    <button class="production-tab" data-production-tab="year">Year</button>
                </div>
            </div>

            <div class="dashboard-card-body production-stats">
                {{-- Day (example numbers – replace with real data later) --}}
                <div class="production-range production-range-active" data-production-range="day">
                    <table>
                        <tr>
                            <td class="production-label">Leads Worked</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">Calls</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">Stops</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">Presentations</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">Apps Written</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">Premium Collected</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">AP (Annualized Premium)</td>
                            <td class="production-value">--</td>
                        </tr>
                    </table>
                </div>

                {{-- Week --}}
                <div class="production-range" data-production-range="week">
                    <table>
                        <tr>
                            <td class="production-label">Leads Worked</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">Calls</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">Stops</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">Presentations</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">Apps Written</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">Premium Collected</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">AP (Annualized Premium)</td>
                            <td class="production-value">--</td>
                        </tr>
                    </table>
                </div>

                {{-- Month --}}
                <div class="production-range" data-production-range="month">
                    <table>
                        <tr>
                            <td class="production-label">Leads Worked</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">Calls</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">Stops</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">Presentations</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">Apps Written</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">Premium Collected</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">AP (Annualized Premium)</td>
                            <td class="production-value">--</td>
                        </tr>
                    </table>
                </div>

                {{-- Quarter --}}
                <div class="production-range" data-production-range="quarter">
                    <table>
                        <tr>
                            <td class="production-label">Leads Worked</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">Calls</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">Stops</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">Presentations</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">Apps Written</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">Premium Collected</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">AP (Annualized Premium)</td>
                            <td class="production-value">--</td>
                        </tr>
                    </table>
                </div>

                {{-- Year --}}
                <div class="production-range" data-production-range="year">
                    <table>
                        <tr>
                            <td class="production-label">Leads Worked</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">Calls</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">Stops</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">Presentations</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">Apps Written</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">Premium Collected</td>
                            <td class="production-value">--</td>
                        </tr>
                        <tr>
                            <td class="production-label">AP (Annualized Premium)</td>
                            <td class="production-value">--</td>
                        </tr>
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
                <ul class="dashboard-list">
                    <li>--</li>
                    <li>--</li>
                </ul>
                <div style="margin-top:10px; font-size:11px; color:#9ca3af;">
                    Dynamic data coming soon.
                </div>
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
                    <li>Birthdays this week: --</li>
                    <li>Anniversaries this week: --</li>
                </ul>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabs = document.querySelectorAll('.production-tab');
        const ranges = document.querySelectorAll('.production-range');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const range = tab.getAttribute('data-production-tab');

                // activate tab
                tabs.forEach(t => t.classList.remove('production-tab-active'));
                tab.classList.add('production-tab-active');

                // show matching stats
                ranges.forEach(r => {
                    if (r.getAttribute('data-production-range') === range) {
                        r.classList.add('production-range-active');
                    } else {
                        r.classList.remove('production-range-active');
                    }
                });
            });
        });
    });
</script>
@endsection

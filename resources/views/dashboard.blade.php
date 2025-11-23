@extends('layouts.app')
@section('content')
@php
    $serverTime = now()->toDateTimeString();
@endphp

<style>
    /* (same styling you already had, unchanged for space reasons) */
</style>

<div class="dashboard-page">

    {{-- HEADER --}}
    <div class="dashboard-header">
        <div class="dashboard-title">Dashboard</div>
        <div class="dashboard-subtitle local-greeting">Loading greeting…</div>
        <div class="dashboard-datetime local-time" data-server-time="{{ $serverTime }}">Loading time…</div>

        <div class="dashboard-search-row">
            <div class="dashboard-search-wrapper">
                <input type="text" class="dashboard-search-input" placeholder="Search contacts, leads, or clients...">
                <button class="dashboard-search-button">🔍 Search</button>
            </div>
        </div>
    </div>

    {{-- GRID --}}
    <div class="dashboard-grid">

        {{-- ========================== --}}
        {{--      CURRENT PRODUCTION     --}}
        {{-- ========================== --}}
        <div class="dashboard-card">
            <div class="production-title">Current Production</div>

            {{-- INLINE PRODUCTION TABS (MERGED) --}}
            <div class="production-tabs-wrapper">
                <div class="production-tabs">
                    <button class="production-tab production-tab-active" data-production-tab="day">Day</button>
                    <button class="production-tab" data-production-tab="week">Week</button>
                    <button class="production-tab" data-production-tab="month">Month</button>
                    <button class="production-tab" data-production-tab="quarter">Quarter</button>
                    <button class="production-tab" data-production-tab="year">Year</button>
                </div>
            </div>

            {{-- INLINE PRODUCTION TABLES --}}
            <div class="dashboard-card-body production-stats">

                {{-- DAY --}}
                <div class="production-range production-range-active" data-production-range="day">
                    <table>
                        <tr><td class="production-label">Leads Worked</td>      <td class="production-value">--</td></tr>
                        <tr><td class="production-label">Calls</td>             <td class="production-value">--</td></tr>
                        <tr><td class="production-label">Stops</td>             <td class="production-value">--</td></tr>
                        <tr><td class="production-label">Presentations</td>     <td class="production-value">--</td></tr>
                        <tr><td class="production-label">Apps Written</td>      <td class="production-value">--</td></tr>
                        <tr><td class="production-label">Premium Collected</td> <td class="production-value">--</td></tr>
                        <tr><td class="production-label">AP</td>                <td class="production-value">--</td></tr>
                    </table>
                </div>

                {{-- WEEK --}}
                <div class="production-range" data-production-range="week">
                    <table>
                        <tr><td class="production-label">Leads Worked</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Calls</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Stops</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Presentations</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Apps Written</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Premium Collected</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">AP</td><td class="production-value">--</td></tr>
                    </table>
                </div>

                {{-- MONTH --}}
                <div class="production-range" data-production-range="month">
                    <table>
                        <tr><td class="production-label">Leads Worked</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Calls</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Stops</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Presentations</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Apps Written</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Premium Collected</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">AP</td><td class="production-value">--</td></tr>
                    </table>
                </div>

                {{-- QUARTER --}}
                <div class="production-range" data-production-range="quarter">
                    <table>
                        <tr><td class="production-label">Leads Worked</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Calls</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Stops</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Presentations</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Apps Written</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Premium Collected</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">AP</td><td class="production-value">--</td></tr>
                    </table>
                </div>

                {{-- YEAR --}}
                <div class="production-range" data-production-range="year">
                    <table>
                        <tr><td class="production-label">Leads Worked</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Calls</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Stops</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Presentations</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Apps Written</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">Premium Collected</td><td class="production-value">--</td></tr>
                        <tr><td class="production-label">AP</td><td class="production-value">--</td></tr>
                    </table>
                </div>

            </div>
        </div>

        {{-- ========================== --}}
        {{--   UPCOMING APPOINTMENTS    --}}
        {{-- ========================== --}}
        <div class="dashboard-card">
            <div class="dashboard-card-title-row">
                <div class="dashboard-card-title">
                    <span class="dashboard-card-icon">📅</span>
                    Upcoming Appointments
                </div>
            </div>
            <div class="dashboard-card-body">
                @if($events->isEmpty())
                    <ul class="dashboard-list"><li>No upcoming appointments.</li></ul>
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

        {{-- ========================== --}}
        {{--      TODAY’S INSIGHTS      --}}
        {{-- ========================== --}}
        <div class="dashboard-card">
            <div class="dashboard-card-title-row">
                <div class="dashboard-card-title">
                    <span class="dashboard-card-icon">✨</span>
                    Today’s Insights
                </div>
            </div>
            <div class="dashboard-card-body">
                <ul class="dashboard-list">
                    <li>Birthdays this week: {{ $birthdays->count() }}</li>
                    <li>Anniversaries this week: {{ $anniversaries->count() }}</li>
                </ul>
            </div>
        </div>

        {{-- ========================== --}}
        {{--       RECENTLY ADDED       --}}
        {{-- ========================== --}}
        <div class="dashboard-card">
            <div class="dashboard-card-title-row">
                <div class="dashboard-card-title">
                    <span class="badge-new">NEW</span>
                    Recently Added
                </div>
            </div>
            <div class="dashboard-card-body">
                <ul class="dashboard-list">
                    @forelse($recent as $c)
                        <li>
                            <strong>{{ $c->full_name }}</strong><br>
                            <span style="color: var(--text-faint); font-size: 13px;">
                                Added {{ $c->created_at->diffForHumans() }}
                            </span>
                        </li>
                    @empty
                        <li>No recent contacts.</li>
                    @endforelse
                </ul>
            </div>
        </div>

    </div>

    <div class="dashboard-footer-note">
        © {{ now()->year }} Agency Builder CRM — Tier 1
    </div>
</div>

{{-- SCRIPTS (your original time & production card logic retained) --}}
<script>
/* greeting + time */
document.addEventListener("DOMContentLoaded", function () {
    const timeEl = document.querySelector(".local-time");
    const greetEl = document.querySelector(".local-greeting");

    const serverTime = timeEl.getAttribute("data-server-time");
    const localDate = new Date(serverTime + " UTC");

    timeEl.innerText = localDate.toLocaleString();

    const hour = localDate.getHours();
    greetEl.innerText = (hour < 12 ? "Good morning" :
                        hour < 17 ? "Good afternoon" :
                                    "Good evening") +
                        " — here’s your daily overview.";
});
</script>

<script>
/* Tab switching */
document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.production-tab');
    const ranges = document.querySelectorAll('.production-range');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const range = tab.dataset.productionTab;
            tabs.forEach(t => t.classList.remove('production-tab-active'));
            tab.classList.add('production-tab-active');

            ranges.forEach(r =>
                r.classList.toggle('production-range-active',
                r.dataset.productionRange === range)
            );
        });
    });
});
</script>

@endsection

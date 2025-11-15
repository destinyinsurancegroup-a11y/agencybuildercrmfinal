@extends('layouts.app')

@section('content')
@php
    // Time-based greeting
    $hour = now()->format('H');
    if ($hour < 12) {
        $greeting = 'Good morning';
    } elseif ($hour < 17) {
        $greeting = 'Good afternoon';
    } else {
        $greeting = 'Good evening';
    }

    // Date & Time
    $today = now()->format('l, F j, Y');
    $time = now()->format('g:i A');
@endphp

<div class="dashboard-header">
    <h1 class="dashboard-title">Dashboard</h1>
    <p class="dashboard-subtitle">
        {{ $greeting }} — here’s your daily overview.
    </p>
    <p class="dashboard-datetime">{{ $today }} • {{ $time }}</p>

    <div class="search-container">
        <input type="text" placeholder="Search contacts, leads, or clients..." class="search-input">
        <button class="search-btn">🔍</button>
    </div>
</div>

<div class="dashboard-grid">

    {{-- Current Production --}}
    <div class="dashboard-card">
        <h3 class="card-title">📋 Current Production</h3>
        <ul class="stats-list">
            <li>Calls: --</li>
            <li>Presentations: --</li>
            <li>Sales: --</li>
            <li>Premium: --</li>
        </ul>
    </div>

    {{-- Upcoming Appointments --}}
    <div class="dashboard-card">
        <h3 class="card-title">📅 Upcoming Appointments</h3>
        <ul class="stats-list">
            <li>--</li>
            <li>--</li>
            <li>(Dynamic data coming soon)</li>
        </ul>
    </div>

    {{-- Today’s Insights --}}
    <div class="dashboard-card">
        <h3 class="card-title">✨ Today’s Insights</h3>
        <ul class="stats-list">
            <li>Birthdays this week: --</li>
            <li>Anniversaries this week: --</li>
        </ul>
    </div>

    {{-- Recently Added --}}
    <div class="dashboard-card">
        <h3 class="card-title">🆕 Recently Added</h3>
        <ul class="stats-list">
            <li>--</li>
            <li>--</li>
        </ul>
    </div>

</div>

<footer class="footer-note">
    © {{ date('Y') }} Agency Builder CRM — Tier 1
</footer>

@endsection

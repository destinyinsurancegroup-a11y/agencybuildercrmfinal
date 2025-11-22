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

    /* GRID */
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 20px;
    }

    .dashboard-card {
        background: #ffffff;
        border-radius: 18px;
        padding: 22px 22px 24px;
        box-shadow:
            0 18px 30px -12px rgba(0,0,0,0.35),
            0 8px 16px -8px rgba(0,0,0,0.18);
        border: 1px solid #e5e7eb;
    }

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

    .production-label { font-size:18px; font-weight:500; color:#4b5563; }
    .production-value { font-size:28px; font-weight:700; text-align:right; color:#111827; }

    .production-range { display:none; }
    .production-range-active { display:block; }

</style>

<div class="dashboard-page">

    {{-- Header --}}
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


    {{-- CARDS --}}
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
                    <table>
                        <tr><td class="production-label">Leads Worked</td><td class="production-value" id="day_leads_work

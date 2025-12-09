@extends('layouts.app')

@section('content')
<style>
    :root {
        --gold: #c9a227;
        --gold-soft: #f5e6b3;
        --bg-page: #f5f5f5;
        --text-main: #111827;
        --text-subtle: #4b5563;
        --text-faint: #9ca3af;
        --chip-green-bg: #dcfce7;
        --chip-green-border: #bbf7d0;
        --chip-green-text: #166534;
    }

    .second-brain-page {
        padding: 30px 40px;
        background: var(--bg-page);
        min-height: 100vh;
        font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .sb-header {
        margin-bottom: 24px;
    }

    .sb-title-row {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 6px;
    }

    .sb-title {
        font-size: 28px;
        font-weight: 700;
        color: var(--text-main);
    }

    .sb-badge {
        font-size: 11px;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        background: var(--gold-soft);
        color: #92400e;
        padding: 3px 8px;
        border-radius: 999px;
        border: 1px solid rgba(0,0,0,0.08);
    }

    .sb-subtitle {
        font-size: 14px;
        color: var(--text-subtle);
    }

    .sb-layout {
        margin-top: 20px;
        display: grid;
        grid-template-columns: minmax(260px, 320px) minmax(260px, 320px) minmax(0, 1.6fr);
        gap: 20px;
    }

    .sb-card {
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid #e5e7eb;
        padding: 18px 20px 18px;
        box-shadow:
            0 18px 30px -12px rgba(0,0,0,0.30),
            0 8px 16px -8px rgba(0,0,0,0.18);
    }

    .sb-card-title-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }

    .sb-card-title {
        font-size: 16px;
        font-weight: 600;
        color: var(--text-main);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .sb-chip {
        font-size: 11px;
        padding: 3px 8px;
        border-radius: 999px;
        border: 1px solid #e5e7eb;
        color: var(--text-faint);
    }

    .sb-metric-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 14px;
        color: var(--text-subtle);
    }

    .sb-metric-label {
        font-weight: 500;
    }

    .sb-metric-value {
        font-weight: 700;
        color: var(--text-main);
    }

    .sb-metric-value.good {
        color: var(--chip-green-text);
    }

    .sb-divider {
        border-top: 1px dashed #e5e7eb;
        margin: 10px 0 12px;
    }

    .sb-category-list {
        list-style: none;
        padding-left: 0;
        margin: 0;
        font-size: 14px;
    }

    .sb-category-list li {
        display: flex;
        justify-content: space-between;
        margin-bottom: 6px;
        color: var(--text-subtle);
    }

    .sb-category-name {
        text-transform: capitalize;
    }

    .sb-category-count {
        font-weight: 600;
        color: var(--text-main);
    }

    .sb-top-list {
        list-style: none;
        padding-left: 0;
        margin: 0;
        font-size: 13px;
    }

    .sb-top-item {
        padding: 8px 0;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        justify-content: space-between;
        gap: 12px;
    }

    .sb-top-main {
        max-width: 70%;
    }

    .sb-top-title {
        font-weight: 600;
        color: var(--text-main);
        margin-bottom: 3px;
    }

    .sb-top-sub {
        color: var(--text-faint);
        font-size: 12px;
    }

    .sb-top-tags {
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
        margin-top: 4px;
    }

    .sb-tag {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        padding: 2px 6px;
        border-radius: 999px;
        border: 1px solid #e5e7eb;
        color: var(--text-faint);
    }

    .sb-tag-score {
        border-color: rgba(201,162,39,0.6);
        background: var(--gold-soft);
        color: #92400e;
    }

    .sb-top-score {
        min-width: 48px;
        text-align: right;
        font-weight: 700;
        color: #92400e;
    }

    .sb-run-button {
        font-size: 12px;
        padding: 5px 9px;
        border-radius: 999px;
        border: none;
        background: var(--gold);
        color: #111827;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 0 4px 8px rgba(0,0,0,0.25);
    }

    .sb-run-button:disabled {
        opacity: 0.7;
        box-shadow: none;
        cursor: default;
    }

    .sb-hint {
        font-size: 11px;
        color: var(--text-faint);
        margin-top: 6px;
    }
</style>

<div class="second-brain-page">
    <div class="sb-header">
        <div class="sb-title-row">
            <div class="sb-title">Gideon Second Brain</div>
            <span class="sb-badge">Tier 1 · Beta</span>
        </div>
        <div class="sb-subtitle">
            High-level view of Gideon’s opportunities for your agency.  
            This is read-only; it doesn’t change any client data.
        </div>
    </div>

    <div class="sb-layout">

        {{-- CARD 1: STATUS SNAPSHOT --}}
        <div class="sb-card">
            <div class="sb-card-title-row">
                <div class="sb-card-title">
                    🧠 Status Snapshot
                </div>
            </div>

            <div class="sb-metric-row">
                <span class="sb-metric-label">Open opportunities</span>
                <span class="sb-metric-value">{{ $openCount }}</span>
            </div>
            <div class="sb-metric-row">
                <span class="sb-metric-label">Completed</span>
                <span class="sb-metric-value good">{{ $completedCount }}</span>
            </div>
            <div class="sb-metric-row">
                <span class="sb-metric-label">Dismissed</span>
                <span class="sb-metric-value">{{ $dismissedCount }}</span>
            </div>
            <div class="sb-metric-row">
                <span class="sb-metric-label">Snoozed</span>
                <span class="sb-metric-value">{{ $snoozedCount }}</span>
            </div>

            <div class="sb-divider"></div>

            <div class="sb-metric-row">
                <span class="sb-metric-label">New last 7 days</span>
                <span class="sb-metric-value">{{ $recentNew }}</span>
            </div>
            <div class="sb-metric-row">
                <span class="sb-metric-label">Resolved last 7 days</span>
                <span class="sb-metric-value good">{{ $recentResolved }}</span>
            </div>

            <div class="sb-hint">
                As new rules are added, this snapshot will show how busy Gideon is on your behalf.
            </div>
        </div>

        {{-- CARD 2: CATEGORY MIX --}}
        <div class="sb-card">
            <div class="sb-card-title-row">
                <div class="sb-card-title">
                    📊 Opportunity Mix
                </div>
                <span class="sb-chip">
                    Top {{ max(1, $categories->count()) }} categories
                </span>
            </div>

            @if($categories->isEmpty())
                <div style="font-size: 13px; color: var(--text-faint);">
                    No opportunities yet. As Gideon starts scanning, you’ll see the mix of
                    cross-sells, renewals, follow-ups and more here.
                </div>
            @else
                <ul class="sb-category-list">
                    @foreach($categories as $cat)
                        <li>
                            <span class="sb-category-name">
                                {{ $cat->category === 'uncategorized' ? 'Uncategorized' : str_replace('_', ' ', $cat->category) }}
                            </span>
                            <span class="sb-category-count">{{ $cat->total }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="sb-hint">
                Categories come from the rules engine (e.g. <em>revive_lead</em>, <em>cross_sell</em>).
            </div>
        </div>

        {{-- CARD 3: TOP OPEN OPPORTUNITIES --}}
        <div class="sb-card">
            <div class="sb-card-title-row">
                <div class="sb-card-title">
                    🔥 Top Open Opportunities
                </div>

                <button class="sb-run-button" id="sb-run-scan-btn">
                    Run Scan
                </button>
            </div>

            @if($topOpen->isEmpty())
                <div style="font-size: 13px; color: var(--text-faint);">
                    No open opportunities yet. Try running the scan to generate your first set.
                </div>
            @else
                <ul class="sb-top-list" id="sb-top-list">
                    @foreach($topOpen as $opp)
                        <li class="sb-top-item">
                            <div class="sb-top-main">
                                <div class="sb-top-title">
                                    {{ $opp->title ?? 'Untitled opportunity' }}
                                </div>
                                <div class="sb-top-sub">
                                    {{ ucfirst($opp->category ?? 'uncategorized') }}
                                    • {{ ucfirst($opp->status ?? 'open') }}
                                    • {{ optional($opp->created_at)->diffForHumans() }}
                                </div>
                                <div class="sb-top-tags">
                                    <span class="sb-tag sb-tag-score">Score {{ $opp->score ?? 0 }}</span>
                                    @if($opp->entity_type)
                                        <span class="sb-tag">{{ $opp->entity_type }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="sb-top-score">
                                {{ $opp->score ?? 0 }}
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="sb-hint" id="sb-run-hint">
                Scan runs the current rule set (e.g. “revive old leads”) for your agency only.
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const btn  = document.getElementById('sb-run-scan-btn');
    const hint = document.getElementById('sb-run-hint');

    if (!btn) return;

    btn.addEventListener('click', function () {
        btn.disabled = true;
        const original = btn.textContent;
        btn.textContent = 'Scanning…';

        fetch('/gideon/run-opportunity-scan')
            .then(resp => resp.json())
            .then(data => {
                const msg = (data && data.message)
                    ? data.message
                    : 'Scan completed. Refresh to see updated results.';

                hint.textContent = msg + ' (Refresh this page to update the list.)';
            })
            .catch(() => {
                hint.textContent = 'Scan failed. Please try again in a moment.';
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = original;
            });
    });
});
</script>
@endsection

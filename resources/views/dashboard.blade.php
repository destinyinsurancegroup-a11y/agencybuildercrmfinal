@extends('layouts.app')
@section('content')
@php
    $serverTimeMs = now()->utc()->timestamp * 1000;

    $goalMonthName = now()->format('F');
    $placeholderGoal = 0;
    $placeholderPercent = 0;
    $placeholderNeededMonthly = 0;

    $tomorrowStart = now()->addDay()->startOfDay();
    $endOfMonthStart = now()->endOfMonth()->startOfDay();
    $daysLeftRaw = $tomorrowStart->diffInDays($endOfMonthStart, false) + 1;
    $daysLeft = max($daysLeftRaw, 0);
@endphp

<style>
/* (UNCHANGED — your full CSS stays exactly as you provided) */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
:root {
    --gold: #c9a227;
    --gold-soft: #f5e6b3;
    --bg-page: #f5f5f5;
    --text-main: #111827;
    --text-subtle: #4b5563;
    --text-faint: #9ca3af;
    --money-green: #059669;
    --abc-black: #0b1220;
    --abc-black-2: #0f172a;
    --danger-red: #ef4444;
    --ring-track: rgba(255,255,255,0.14);
}
/* ... keep every style block you pasted ... */
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

    {{-- GOAL CARD --}}
    <div class="goal-card-wrap">
        <div id="abc-goal-card" class="goal-card" style="--progress: {{ $placeholderPercent }}; --gauge-color: var(--danger-red);">
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
                        <span class="amt" id="abc-goal-ap-earned">$0</span>
                        <span class="ap"> AP</span>
                    </div>

                    <div class="goal-label">Collected Premium Needed:</div>
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

    {{-- GRID (UNCHANGED — your full markup stays the same) --}}
    <div class="dashboard-grid">
        {{-- CURRENT PRODUCTION CARD --}}
        <div class="dashboard-card" id="abc-current-production-card">
            <div class="production-title">Current Production</div>

            <div class="production-tabs-wrapper">
                <div class="production-tabs">
                    <button class="production-tab production-tab-active" data-production-tab="day" type="button">Day</button>
                    <button class="production-tab" data-production-tab="week" type="button">Week</button>
                    <button class="production-tab" data-production-tab="month" type="button">Month</button>
                    <button class="production-tab" data-production-tab="quarter" type="button">Quarter</button>
                    <button class="production-tab" data-production-tab="year" type="button">Year</button>
                </div>
            </div>

            <div class="dashboard-card-body production-stats">
                {{-- (UNCHANGED tables) --}}
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

        {{-- (UNCHANGED other cards) --}}
        {{-- Upcoming Appointments --}}
        {{-- Today’s Insights --}}
        {{-- Gideon Opportunities --}}
    </div>

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

            <div class="modal-body abc-breakdown-wrap">
                <div class="abc-breakdown-title">Current Production</div>

                <div class="production-tabs-wrapper">
                    <div class="production-tabs" id="abc-breakdown-tabs">
                        <button class="production-tab production-tab-active" data-production-tab="day" type="button">Day</button>
                        <button class="production-tab" data-production-tab="week" type="button">Week</button>
                        <button class="production-tab" data-production-tab="month" type="button">Month</button>
                        <button class="production-tab" data-production-tab="quarter" type="button">Quarter</button>
                        <button class="production-tab" data-production-tab="year" type="button">Year</button>
                    </div>
                </div>

                <div class="dashboard-card-body production-stats" id="abc-breakdown-stats">
                    {{-- (UNCHANGED tables) --}}
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

    const goalMonthEl = document.getElementById("abc-goal-month");
    if (goalMonthEl) {
        const monthName = localDate.toLocaleDateString(undefined, { month: "long" });
        goalMonthEl.innerText = monthName;
    }

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

    // Goal Submit
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

    // Log Production -> fetch popup
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
    }

    const logBtn = document.getElementById("abc-log-production");
    if (logBtn) logBtn.addEventListener("click", openActivityModal);

    // Breakdown modal
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
    const tabs = document.querySelectorAll('#abc-current-production-card .production-tab');
    const ranges = document.querySelectorAll('#abc-current-production-card .production-range');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const range = tab.dataset.productionTab;
            tabs.forEach(t => t.classList.remove('production-tab-active'));
            tab.classList.add('production-tab-active');
            ranges.forEach(r => r.classList.toggle('production-range-active', r.dataset.productionRange === range));
            if (window.refreshProductionCard) window.refreshProductionCard(true);
        });
    });
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
            ranges.forEach(r => r.classList.toggle('production-range-active', r.dataset.productionRange === range));
            if (window.refreshProductionBreakdownModal) window.refreshProductionBreakdownModal(true);
        });
    });
});
</script>

<script>
window.refreshProductionCard = function(force = false) {
    const activeTab = document.querySelector("#abc-current-production-card .production-tab-active");
    if (!activeTab) return Promise.resolve();

    const active = activeTab.dataset.productionTab;
    const url = `/activity/totals/${active}` + (force ? `?_=${Date.now()}` : "");

    return fetch(url, { cache: "no-store" })
        .then(r => r.json())
        .then(data => {
            const rows = document.querySelectorAll(
                `#abc-current-production-card .production-range[data-production-range="${active}"] .production-value`
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

<script>
window.applyGoalCardFromTotals = function(premiumCollected, apEarned) {
    const goalCard = document.getElementById('abc-goal-card');
    const ring = document.getElementById('abc-goal-ring');
    const percentText = document.getElementById('abc-goal-percent-text');
    const neededDisplay = document.getElementById('abc-goal-needed-display');
    const apEarnedEl = document.getElementById('abc-goal-ap-earned');

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
    function gaugeColorForPercent(pct) {
        const p = Number(pct) || 0;
        if (p <= 24) return '#ef4444';
        if (p <= 75) return '#c9a227';
        return '#059669';
    }

    const savedGoal = localStorage.getItem("abc_monthly_goal_ap");
    const goalAp = Math.max(0, Math.round(parseMoneyToNumber(savedGoal)));
    const monthlyNeeded = goalAp > 0 ? Math.round(goalAp / 12) : 0;

    const prem = Number(premiumCollected || 0);
    const ap = Number(apEarned || 0);

    const remaining = Math.max(0, Math.round(monthlyNeeded - prem));

    let pct = 0;
    if (monthlyNeeded > 0) pct = Math.round(Math.min(100, (prem / monthlyNeeded) * 100));

    const color = gaugeColorForPercent(pct);

    if (neededDisplay) neededDisplay.innerText = formatMoney0(remaining);
    if (apEarnedEl) apEarnedEl.innerText = formatMoney0(ap);

    if (goalCard) {
        goalCard.style.setProperty('--progress', String(pct));
        goalCard.style.setProperty('--gauge-color', color);
    }
    if (ring) {
        ring.style.setProperty('--progress', String(pct));
        ring.style.setProperty('--gauge-color', color);
    }
    if (percentText) percentText.innerText = `${pct}%`;
};
</script>

<script>
window.refreshGoalCard = function(force = false) {
    const url = `/activity/totals/month` + (force ? `?_=${Date.now()}` : "");
    return fetch(url, { cache: "no-store" })
        .then(r => r.json())
        .then(data => {
            if (typeof window.applyGoalCardFromTotals === "function") {
                window.applyGoalCardFromTotals(
                    Number(data.premium_collected || 0),
                    Number(data.ap || 0)
                );
            }
        })
        .catch(() => {});
};
</script>

{{-- ✅ SINGLE SOURCE OF TRUTH: Dashboard handles ALL saving --}}
<script>
(function(){
    function showSaveError(msg) {
        const err = document.getElementById('activitySaveError');
        if (!err) return;
        err.style.display = 'block';
        err.style.background = "rgba(239,68,68,.12)";
        err.style.border = "1px solid rgba(239,68,68,.35)";
        err.style.padding = "10px 12px";
        err.style.borderRadius = "12px";
        err.style.color = "#fecaca";
        err.style.fontWeight = "800";
        err.innerText = msg || 'Save failed. Please try again.';
    }

    function hideSaveError() {
        const err = document.getElementById('activitySaveError');
        if (!err) return;
        err.style.display = 'none';
        err.innerText = '';
    }

    function setSaving(isSaving) {
        const btn = document.getElementById('saveActivityBtn');
        if (!btn) return;
        btn.disabled = !!isSaving;
        btn.innerText = isSaving ? 'Saving…' : 'Save Activity';
        btn.style.opacity = isSaving ? '0.85' : '1';
        btn.style.cursor = isSaving ? 'not-allowed' : 'pointer';
    }

    async function fetchMonthTotals() {
        const res = await fetch(`/activity/totals/month?_=${Date.now()}`, {
            cache: "no-store",
            headers: { "X-Requested-With": "XMLHttpRequest", "Accept": "application/json" },
            credentials: "same-origin"
        });
        if (!res.ok) throw new Error("Failed to fetch month totals");
        return await res.json();
    }

    window.ABC_activitySaveClick = async function(event) {
        if (event) event.preventDefault();

        // ✅ global lock prevents double submit
        if (window.__ABC_ACTIVITY_SAVING) return;
        window.__ABC_ACTIVITY_SAVING = true;

        const form = document.getElementById('activityForm');
        if (!form) {
            window.__ABC_ACTIVITY_SAVING = false;
            return;
        }

        hideSaveError();
        setSaving(true);

        try {
            const url = form.getAttribute('action');
            const fd = new FormData(form);

            // ✅ normalize blanks to 0 (prevents server default weirdness)
            ["leads_worked","calls","stops","presentations","apps_written","premium_collected","ap"].forEach(name => {
                const v = fd.get(name);
                if (v === null || String(v).trim() === "") fd.set(name, "0");
            });

            const csrfInput = form.querySelector('input[name="_token"]');
            const csrf = csrfInput ? csrfInput.value : null;

            const res = await fetch(url, {
                method: 'POST',
                body: fd,
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json",
                    ...(csrf ? { "X-CSRF-TOKEN": csrf } : {})
                },
                cache: "no-store",
                credentials: "same-origin"
            });

            if (res.status === 422) {
                const j = await res.json().catch(() => ({}));
                const first = j && j.errors ? Object.values(j.errors)[0]?.[0] : null;
                showSaveError(first || "Validation failed.");
                return;
            }

            if (!res.ok) {
                const t = await res.text().catch(() => "");
                showSaveError("Save failed. " + (t ? t.slice(0,160) : ""));
                return;
            }

            // ✅ try to use returned JSON month_totals if present
            let monthTotals = null;
            const json = await res.json().catch(() => null);
            if (json && json.month_totals) monthTotals = json.month_totals;

            if (!monthTotals) {
                monthTotals = await fetchMonthTotals();
            }

            if (monthTotals && typeof window.applyGoalCardFromTotals === "function") {
                window.applyGoalCardFromTotals(
                    Number(monthTotals.premium_collected || 0),
                    Number(monthTotals.ap || 0)
                );
            } else if (typeof window.refreshGoalCard === "function") {
                await window.refreshGoalCard(true);
            }

            // ✅ refresh production cards
            const p1 = window.refreshProductionCard ? window.refreshProductionCard(true) : Promise.resolve();
            const p2 = window.refreshProductionBreakdownModal ? window.refreshProductionBreakdownModal(true) : Promise.resolve();
            await Promise.allSettled([p1, p2]);

            // ✅ close modal last (so button state resets cleanly)
            const modalEl = document.getElementById('activityModal');
            if (modalEl && typeof bootstrap !== 'undefined') {
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            }

        } catch (err) {
            console.error(err);
            showSaveError("Save failed. Please try again.");
        } finally {
            window.__ABC_ACTIVITY_SAVING = false;
            setSaving(false);
        }
    };
})();
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    if (window.refreshProductionCard) window.refreshProductionCard(true);
    if (window.refreshGoalCard) window.refreshGoalCard(true);
});
</script>

@endsection

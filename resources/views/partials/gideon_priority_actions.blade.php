{{-- resources/views/partials/gideon_priority_actions.blade.php --}}

@php
    // ✅ Hard-safe defaults so this partial can NEVER 500
    $gideon = is_array($gideon ?? null) ? $gideon : [];

    $scan = is_array($gideon['scan'] ?? null) ? $gideon['scan'] : [];
    $actions = is_array($gideon['actions'] ?? null) ? $gideon['actions'] : [];

    $scanStatus = (string)($scan['status'] ?? 'idle'); // idle | scanning | ready | error
    $minutesAgo = $scan['minutes_ago'] ?? null;
    $scope = is_array($scan['scope'] ?? null) ? $scan['scope'] : [];

    // ✅ Priority color mapping
    $priorityColors = [
        'P1' => ['bg' => '#DC2626', 'text' => '#FFFFFF'], // red
        'P2' => ['bg' => '#F59E0B', 'text' => '#111827'], // amber
        'P3' => ['bg' => '#22C55E', 'text' => '#111827'], // green
        'P4' => ['bg' => '#3B82F6', 'text' => '#FFFFFF'], // blue
        'P5' => ['bg' => '#6B7280', 'text' => '#FFFFFF'], // gray
    ];

    // ✅ If no actions yet, show helpful “starter” items (front-end only)
    // NOTE: Once JS loads /gideon/top, these will be replaced automatically.
    if (count($actions) === 0) {
        $actions = [
            [
                'priority' => 'P1',
                'title' => 'Work the hottest leads now',
                'reason' => 'Follow-ups and “call me” notes are the fastest path to new premium.',
                'cta_label' => 'Open Leads',
                'cta_url' => url('/leads'),
                'meta' => 'Next step: call + text top 10'
            ],
            [
                'priority' => 'P2',
                'title' => 'Contact Beneficiaries / Emergency Contacts not yet reached',
                'reason' => 'Reduces cancellations and creates new coverage opportunities.',
                'cta_label' => 'Open Book of Business',
                'cta_url' => url('/book'),
                'meta' => 'Filter: “Contacted = No”'
            ],
            [
                'priority' => 'P3',
                'title' => 'Scan contacts for apartment opportunities',
                'reason' => 'Apartment managers can approve workshops that generate leads quickly.',
                'cta_label' => 'Open Contacts',
                'cta_url' => url('/contacts'),
                'meta' => 'Look for “Apt / Unit” in address'
            ],
            [
                'priority' => 'P4',
                'title' => 'Network with Pastors / Funeral Home Directors / Other Agents',
                'reason' => 'Workshops + referrals + networking can produce consistent pipelines.',
                'cta_label' => 'Open Contacts',
                'cta_url' => url('/contacts'),
                'meta' => 'Search titles in contact fields'
            ],
        ];
    }

    // Limit to 5 actions max
    $actions = array_slice($actions, 0, 5);

    // Human scan status label
    $scanLabel = match ($scanStatus) {
        'scanning' => 'Scanning…',
        'ready'    => 'Scan ready',
        'error'    => 'Scan error',
        default    => 'Idle',
    };

    // Small status dot color
    $statusDot = match ($scanStatus) {
        'scanning' => '#F59E0B', // amber
        'ready'    => '#22C55E', // green
        'error'    => '#DC2626', // red
        default    => '#9CA3AF', // gray
    };
@endphp

{{-- ✅ Minimal fallback styles (keeps it clean even if CSS file isn’t present) --}}
<style>
    .gideon-wrap { display: flex; flex-direction: column; gap: 12px; }
    .gideon-toprow { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap: wrap; }
    .gideon-status { display:flex; align-items:center; gap:10px; font-size: 13px; color: #4b5563; }
    .gideon-dot { width: 10px; height: 10px; border-radius: 999px; display:inline-block; }
    .gideon-scope { font-size: 12px; color:#6b7280; }
    .gideon-scope span { background:#f3f4f6; border:1px solid #e5e7eb; padding:2px 8px; border-radius: 999px; margin-right:6px; display:inline-block; margin-top:6px; }
    .gideon-actions { list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:10px; }
    .gideon-action { display:flex; gap:12px; align-items:flex-start; padding:12px; border:1px solid #e5e7eb; border-radius: 14px; background:#fff; }
    .gideon-pill { font-size: 11px; font-weight: 900; border-radius: 999px; padding: 4px 10px; line-height: 1; white-space: nowrap; }
    .gideon-action-title { font-weight: 800; color:#111827; font-size: 14px; margin:0 0 4px; }
    .gideon-action-reason { font-size: 12px; color:#6b7280; margin:0 0 6px; }
    .gideon-action-meta { font-size: 12px; color:#4b5563; margin:0; }
    .gideon-action-cta { margin-left:auto; display:flex; flex-direction:column; gap:6px; align-items:flex-end; }
    .gideon-btn { border-radius: 10px; padding: 8px 10px; border: 1px solid #d1d5db; background: #fff; font-weight: 800; font-size: 12px; cursor:pointer; }
    .gideon-btn-primary { background: #111827; color:#fff; border-color:#111827; }
    .gideon-btn:disabled { opacity: .65; cursor: not-allowed; }
</style>

<div class="gideon-wrap">

    {{-- Top row: status + scan controls --}}
    <div class="gideon-toprow">
        <div class="gideon-status">
            <span id="gideonStatusDot" class="gideon-dot" style="background: {{ $statusDot }};"></span>
            <strong style="color:#111827;">Gideon Scan:</strong>
            <span id="gideonStatusText">{{ $scanLabel }}</span>
            @if(!is_null($minutesAgo))
                <span id="gideonMinutesAgo" style="color:#9ca3af;">• {{ (int)$minutesAgo }} min ago</span>
            @else
                <span id="gideonMinutesAgo" style="color:#9ca3af; display:none;"></span>
            @endif
        </div>

        <div style="display:flex; gap:8px; align-items:center;">
            <button id="gideonQuickScanBtn" class="gideon-btn" type="button">
                Scan
            </button>

            <button id="gideonDeepScanBtn" class="gideon-btn gideon-btn-primary" type="button">
                Deeper Scan
            </button>

            <button id="gideonRefreshBtn" class="gideon-btn" type="button">
                Refresh
            </button>

            {{-- Optional: link to view all opportunities (safe URL, no route dependency) --}}
            <a href="{{ url('/gideon/opportunities') }}"
               class="gideon-btn"
               style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center;">
                View all
            </a>
        </div>
    </div>

    {{-- Scope chips (what Gideon is scanning) --}}
    @if(count($scope) > 0)
        <div class="gideon-scope">
            Scanning:
            <div>
                @foreach($scope as $chip)
                    <span>{{ $chip }}</span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Priority Actions list --}}
    <ul id="gideonList" class="gideon-actions">
        @foreach($actions as $a)
            @php
                $priority = strtoupper((string)($a['priority'] ?? 'P3'));
                if (!isset($priorityColors[$priority])) $priority = 'P3';
                $bg = $priorityColors[$priority]['bg'];
                $tx = $priorityColors[$priority]['text'];

                $title = (string)($a['title'] ?? 'Priority action');
                $reason = (string)($a['reason'] ?? '');
                $meta = (string)($a['meta'] ?? '');

                $ctaLabel = (string)($a['cta_label'] ?? 'Open');
                $ctaUrl = (string)($a['cta_url'] ?? url('/'));
            @endphp

            <li class="gideon-action">
                <span class="gideon-pill" style="background: {{ $bg }}; color: {{ $tx }};">
                    {{ $priority }}
                </span>

                <div style="min-width: 0;">
                    <p class="gideon-action-title">{{ $title }}</p>
                    @if($reason !== '')
                        <p class="gideon-action-reason">{{ $reason }}</p>
                    @endif
                    @if($meta !== '')
                        <p class="gideon-action-meta"><strong>Next step:</strong> {{ $meta }}</p>
                    @endif
                </div>

                <div class="gideon-action-cta">
                    <a href="{{ $ctaUrl }}" class="gideon-btn" style="text-decoration:none;">
                        {{ $ctaLabel }} →
                    </a>
                </div>
            </li>
        @endforeach
    </ul>

    <div style="font-size:12px; color:#6b7280;">
        Gideon prioritizes actions most likely to increase production first.
    </div>
</div>

@push('scripts')
<script>
(function () {
    // ✅ Debug marker: proves this script is actually running
    console.log("✅ Gideon priority_actions script is running");

    // -----------------------------
    // Config
    // -----------------------------
    const ENDPOINTS = {
        quick: "{{ url('/gideon/scan') }}",
        deep:  "{{ url('/gideon/scan/deep') }}",
        top:   "{{ url('/gideon/top') }}",
    };

    const COLORS = {
        P1: { bg: '#DC2626', text: '#FFFFFF' },
        P2: { bg: '#F59E0B', text: '#111827' },
        P3: { bg: '#22C55E', text: '#111827' },
        P4: { bg: '#3B82F6', text: '#FFFFFF' },
        P5: { bg: '#6B7280', text: '#FFFFFF' },
    };

    function csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function setStatus(dotColor, text, minutesAgoNullable) {
        const dot = document.getElementById('gideonStatusDot');
        const txt = document.getElementById('gideonStatusText');
        const mins = document.getElementById('gideonMinutesAgo');

        if (dot) dot.style.background = dotColor || '#9CA3AF';
        if (txt) txt.textContent = text || 'Idle';

        if (mins) {
            if (minutesAgoNullable === null || minutesAgoNullable === undefined) {
                mins.style.display = 'none';
            } else {
                mins.style.display = 'inline';
                mins.textContent = '• ' + String(minutesAgoNullable) + ' min ago';
            }
        }
    }

    function setButtonsDisabled(disabled) {
        const quick = document.getElementById('gideonQuickScanBtn');
        const deep  = document.getElementById('gideonDeepScanBtn');
        const ref   = document.getElementById('gideonRefreshBtn');

        if (quick) quick.disabled = disabled;
        if (deep)  deep.disabled  = disabled;
        if (ref)   ref.disabled   = disabled;
    }

    function priorityFromScore(score) {
        const s = Number(score || 0);
        if (s >= 90) return 'P1';
        if (s >= 75) return 'P2';
        if (s >= 55) return 'P3';
        if (s >= 35) return 'P4';
        return 'P5';
    }

    // Best-effort CTA routing (won’t 500 if routes differ)
    function ctaForItem(item) {
        const entityType = String(item.entity_type || '');
        const entityId   = item.entity_id;

        if (entityType === 'lead' && entityId) {
            return { label: 'Open Lead', url: `/leads/${entityId}` };
        }
        if (String(item.category || '').includes('revive_lead') && entityId) {
            return { label: 'Open Lead', url: `/leads/${entityId}` };
        }

        if ((entityType === 'book' || entityType === 'client') && entityId) {
            return { label: 'Open Book Client', url: `/book/${entityId}` };
        }

        if (entityType === 'service' && entityId) {
            return { label: 'Open Service Client', url: `/service/${entityId}` };
        }

        return { label: 'Open', url: '/contacts' };
    }

    function clearListToLoading() {
        const list = document.getElementById('gideonList');
        if (!list) return;

        list.innerHTML = '';
        const li = document.createElement('li');
        li.className = 'gideon-action';
        li.innerHTML = `
            <span class="gideon-pill" style="background:#9CA3AF;color:#fff;">…</span>
            <div style="min-width:0;">
                <p class="gideon-action-title">Loading priorities…</p>
                <p class="gideon-action-reason">Fetching Gideon results.</p>
            </div>
        `;
        list.appendChild(li);
    }

    function renderTop(items) {
        const list = document.getElementById('gideonList');
        if (!list) return;

        list.innerHTML = '';

        if (!Array.isArray(items) || items.length === 0) {
            const li = document.createElement('li');
            li.className = 'gideon-action';
            li.innerHTML = `
                <span class="gideon-pill" style="background:#6B7280;color:#fff;">P5</span>
                <div style="min-width:0;">
                    <p class="gideon-action-title">No Gideon opportunities yet</p>
                    <p class="gideon-action-reason">Run a scan to generate priority actions.</p>
                </div>
                <div class="gideon-action-cta"></div>
            `;
            list.appendChild(li);
            return;
        }

        items.slice(0, 5).forEach(item => {
            const p = priorityFromScore(item.score);
            const color = COLORS[p] || COLORS.P3;

            const title = String(item.title || 'Priority action');
            const reason = String(item.short_reason || '');
            const meta = String(item.recommended_action || '');

            const cta = ctaForItem(item);

            const li = document.createElement('li');
            li.className = 'gideon-action';

            const pill = document.createElement('span');
            pill.className = 'gideon-pill';
            pill.style.background = color.bg;
            pill.style.color = color.text;
            pill.textContent = p;

            const mid = document.createElement('div');
            mid.style.minWidth = '0';

            const t = document.createElement('p');
            t.className = 'gideon-action-title';
            t.textContent = title;
            mid.appendChild(t);

            if (reason) {
                const r = document.createElement('p');
                r.className = 'gideon-action-reason';
                r.textContent = reason;
                mid.appendChild(r);
            }

            if (meta) {
                const m = document.createElement('p');
                m.className = 'gideon-action-meta';
                const strong = document.createElement('strong');
                strong.textContent = 'Next step: ';
                m.appendChild(strong);
                m.appendChild(document.createTextNode(meta));
                mid.appendChild(m);
            }

            const right = document.createElement('div');
            right.className = 'gideon-action-cta';

            const a = document.createElement('a');
            a.className = 'gideon-btn';
            a.style.textDecoration = 'none';
            a.href = cta.url;
            a.textContent = cta.label + ' →';

            right.appendChild(a);

            li.appendChild(pill);
            li.appendChild(mid);
            li.appendChild(right);

            list.appendChild(li);
        });
    }

    async function loadTop() {
        try {
            const res = await fetch(ENDPOINTS.top + '?_=' + Date.now(), {
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });

            if (!res.ok) {
                renderTop([]);
                return;
            }

            const data = await res.json();
            if (!data || data.success !== true) {
                renderTop([]);
                return;
            }
            renderTop(data.items || []);
        } catch (e) {
            renderTop([]);
        }
    }

    async function runScan(mode) {
        const url = (mode === 'deep') ? ENDPOINTS.deep : ENDPOINTS.quick;

        setButtonsDisabled(true);
        setStatus('#F59E0B', (mode === 'deep') ? 'Deep scan running…' : 'Scanning…', null);
        clearListToLoading();

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                cache: 'no-store'
            });

            let data = null;
            try {
                data = await res.json();
            } catch (_) {}

            if (!res.ok || !data || data.success !== true) {
                console.error('Gideon scan failed:', res.status, data);
                setStatus('#DC2626', 'Scan error', null);
                await loadTop();
                return;
            }

            setStatus('#22C55E', 'Scan ready', 0);
            await loadTop();
        } catch (e) {
            console.error('Gideon scan exception:', e);
            setStatus('#DC2626', 'Scan error', null);
            await loadTop();
        } finally {
            setButtonsDisabled(false);
        }
    }

    function wire() {
        const quick = document.getElementById('gideonQuickScanBtn');
        const deep  = document.getElementById('gideonDeepScanBtn');
        const ref   = document.getElementById('gideonRefreshBtn');

        if (quick) quick.addEventListener('click', () => runScan('quick'));
        if (deep)  deep.addEventListener('click',  () => runScan('deep'));
        if (ref)   ref.addEventListener('click',   () => loadTop());

        loadTop();

        window.addEventListener('activity:saved', () => loadTop());
    }

    // ✅ Important: if DOMContentLoaded already fired, this still wires correctly
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', wire);
    } else {
        wire();
    }
})();
</script>
@endpush

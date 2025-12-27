{{-- resources/views/partials/gideon_priority_actions.blade.php --}}

@php
    // ✅ Hard-safe defaults so this partial can NEVER 500
    $gideon = is_array($gideon ?? null) ? $gideon : [];

    $scan = is_array($gideon['scan'] ?? null) ? $gideon['scan'] : [];
    $actions = is_array($gideon['actions'] ?? null) ? $gideon['actions'] : [];

    $scanStatus = (string)($scan['status'] ?? 'idle'); // idle | scanning | ready | error
    $minutesAgo = $scan['minutes_ago'] ?? null;
    $scope = is_array($scan['scope'] ?? null) ? $scan['scope'] : [];

    // ✅ Priority color mapping (you asked for color codes earlier)
    // These are used as inline fallback even if CSS fails to load.
    $priorityColors = [
        'P1' => ['bg' => '#DC2626', 'text' => '#FFFFFF'], // red
        'P2' => ['bg' => '#F59E0B', 'text' => '#111827'], // amber
        'P3' => ['bg' => '#22C55E', 'text' => '#111827'], // green
        'P4' => ['bg' => '#3B82F6', 'text' => '#FFFFFF'], // blue
        'P5' => ['bg' => '#6B7280', 'text' => '#FFFFFF'], // gray
    ];

    // ✅ If no actions yet, show helpful “starter” items (front-end only)
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
                'cta_url' => url('/book-of-business'),
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

    // Limit to 5 actions max (your Gideon rule)
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

    {{-- Top row: status + Deep Scan --}}
    <div class="gideon-toprow">
        <div class="gideon-status">
            <span class="gideon-dot" style="background: {{ $statusDot }};"></span>
            <strong style="color:#111827;">Gideon Scan:</strong>
            <span>{{ $scanLabel }}</span>
            @if(!is_null($minutesAgo))
                <span style="color:#9ca3af;">• {{ (int)$minutesAgo }} min ago</span>
            @endif
        </div>

        <div style="display:flex; gap:8px; align-items:center;">
            <button id="gideonDeepScanBtn" class="gideon-btn gideon-btn-primary" type="button">
                Deeper Scan
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
    <ul class="gideon-actions">
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

    {{-- Footer hint (kept tight) --}}
    <div style="font-size:12px; color:#6b7280;">
        Gideon prioritizes actions most likely to increase production first.
    </div>
</div>

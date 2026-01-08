<div class="d-flex flex-column gap-3" id="gideonGroupsRoot">

    <div class="d-flex align-items-center justify-content-between">
        <div class="text-muted" style="font-size: 13px;">
            Gideon Scan: <span id="gideonScanStatus">Ready</span>
        </div>

        <div class="d-flex gap-2">
            <button id="gideonQuickScanBtn" class="btn btn-outline-dark btn-sm">Scan</button>
            <button id="gideonDeepScanBtn" class="btn btn-dark btn-sm">Deeper Scan</button>
            <button id="gideonRefreshBtn" class="btn btn-outline-secondary btn-sm">Refresh</button>
            {{-- IMPORTANT: You said you do NOT want this under "View all", so no link here. --}}
        </div>
    </div>

    <div id="gideonGroupCards" class="d-flex flex-column gap-3"></div>
</div>

<!-- Modal -->
<div id="gideonGroupModal" class="modal fade" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
            <div class="modal-title fw-bold" id="gideonModalTitle">Loading…</div>
            <div class="text-muted" style="font-size: 13px;" id="gideonModalWhy"></div>
            <div class="text-muted" style="font-size: 13px;">
                <span class="fw-semibold">Next step:</span> <span id="gideonModalNext"></span>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="text-muted mb-2" style="font-size: 13px;" id="gideonModalCount"></div>
        <div id="gideonModalItems" class="d-flex flex-column gap-2"></div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
(function () {
    const cardsEl = document.getElementById('gideonGroupCards');
    const statusEl = document.getElementById('gideonScanStatus');

    const modalEl = document.getElementById('gideonGroupModal');
    const modal = new bootstrap.Modal(modalEl);

    const modalTitleEl = document.getElementById('gideonModalTitle');
    const modalWhyEl = document.getElementById('gideonModalWhy');
    const modalNextEl = document.getElementById('gideonModalNext');
    const modalCountEl = document.getElementById('gideonModalCount');
    const modalItemsEl = document.getElementById('gideonModalItems');

    const quickBtn = document.getElementById('gideonQuickScanBtn');
    const deepBtn  = document.getElementById('gideonDeepScanBtn');
    const refreshBtn = document.getElementById('gideonRefreshBtn');

    function escapeHtml(str) {
        return String(str ?? '')
            .replaceAll('&','&amp;')
            .replaceAll('<','&lt;')
            .replaceAll('>','&gt;')
            .replaceAll('"','&quot;')
            .replaceAll("'","&#039;");
    }

    async function postJson(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(body || {})
        });
        return res.json();
    }

    async function fetchGroups() {
        const res = await fetch("{{ route('gideon.opportunities.groups') }}", {
            headers: { 'Accept': 'application/json' },
            cache: 'no-store'
        });
        return res.json();
    }

    async function fetchGroupItems(bucket) {
        const url = new URL("{{ route('gideon.opportunities.groupItems') }}", window.location.origin);
        url.searchParams.set('bucket', bucket);

        const res = await fetch(url.toString(), {
            headers: { 'Accept': 'application/json' },
            cache: 'no-store'
        });
        return res.json();
    }

    function renderGroups(groups) {
        cardsEl.innerHTML = '';

        if (!groups || groups.length === 0) {
            cardsEl.innerHTML = `
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted">No opportunities found right now.</div>
                    </div>
                </div>
            `;
            return;
        }

        groups.forEach(g => {
            const badge = g.priority === 1 ? 'P1' : ('P' + g.priority);
            const count = Number(g.count || 0);

            const card = document.createElement('div');
            card.className = 'card';

            card.innerHTML = `
                <div class="card-body d-flex align-items-start justify-content-between gap-3">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-danger">${escapeHtml(badge)}</span>
                            <div class="fw-semibold">${escapeHtml(g.title)}</div>
                            <div class="text-muted" style="font-size: 13px;">(${count})</div>
                        </div>

                        <div class="mt-2" style="font-size: 14px;">
                            <span class="fw-semibold">Why it matters:</span>
                            <span class="text-muted">${escapeHtml(g.why)}</span>
                        </div>

                        <div class="mt-1" style="font-size: 14px;">
                            <span class="fw-semibold">Next step:</span>
                            <span class="text-muted">${escapeHtml(g.next)}</span>
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-2">
                        <button class="btn btn-dark btn-sm gideon-open-group" data-bucket="${escapeHtml(g.bucket)}">
                            View list
                        </button>
                    </div>
                </div>
            `;

            cardsEl.appendChild(card);
        });

        cardsEl.querySelectorAll('.gideon-open-group').forEach(btn => {
            btn.addEventListener('click', async () => {
                const bucket = btn.getAttribute('data-bucket');
                await openModal(bucket);
            });
        });
    }

    async function openModal(bucket) {
        modalTitleEl.textContent = 'Loading…';
        modalWhyEl.textContent = '';
        modalNextEl.textContent = '';
        modalCountEl.textContent = '';
        modalItemsEl.innerHTML = '';

        modal.show();

        const payload = await fetchGroupItems(bucket);

        modalTitleEl.textContent = payload.title || 'Opportunities';
        modalWhyEl.textContent = payload.why || '';
        modalNextEl.textContent = payload.next || '';

        const items = payload.items || [];
        modalCountEl.textContent = `${items.length} items`;

        if (items.length === 0) {
            modalItemsEl.innerHTML = `
                <div class="border rounded p-3 text-muted">
                    No items found in this group.
                </div>
            `;
            return;
        }

        items.forEach(item => {
            const row = document.createElement('div');
            row.className = 'border rounded p-3 d-flex align-items-start justify-content-between gap-3';

            const excerptHtml = item.matched_excerpt
                ? `<div class="text-muted mt-1" style="font-size: 13px;"><span class="fw-semibold">Proof:</span> “${escapeHtml(item.matched_excerpt)}”</div>`
                : '';

            row.innerHTML = `
                <div class="flex-grow-1">
                    <div class="fw-semibold">${escapeHtml(item.name || '')}</div>
                    <div class="text-muted" style="font-size: 13px;">${escapeHtml(item.title || '')}</div>
                    ${excerptHtml}
                    <div class="mt-2" style="font-size: 14px;">
                        <span class="fw-semibold">Next:</span>
                        <span class="text-muted">${escapeHtml(item.recommended_action || '')}</span>
                    </div>
                </div>

                <div class="d-flex flex-column gap-2" style="min-width: 150px;">
                    <a href="${escapeHtml(item.open_url || '#')}" class="btn btn-outline-dark btn-sm ${(!item.open_url || item.open_url === '#') ? 'disabled' : ''}">
                        Open
                    </a>
                    <button class="btn btn-outline-success btn-sm gideon-done" data-id="${item.id}">Done</button>
                    <button class="btn btn-outline-secondary btn-sm gideon-snooze" data-id="${item.id}">Snooze 7 days</button>
                </div>
            `;

            modalItemsEl.appendChild(row);
        });

        modalItemsEl.querySelectorAll('.gideon-done').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.getAttribute('data-id');
                await postJson(`/gideon/opportunities/${id}/complete`, {});
                await refresh();
                modal.hide();
            });
        });

        modalItemsEl.querySelectorAll('.gideon-snooze').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.getAttribute('data-id');
                await postJson(`/gideon/opportunities/${id}/snooze`, { days: 7 });
                await refresh();
                modal.hide();
            });
        });
    }

    async function refresh() {
        statusEl.textContent = 'Refreshing…';
        const groups = await fetchGroups();
        renderGroups(groups);
        statusEl.textContent = 'Ready';
    }

    quickBtn?.addEventListener('click', async () => {
        statusEl.textContent = 'Scanning…';
        await postJson("{{ route('gideon.scan') }}", {});
        await refresh();
    });

    deepBtn?.addEventListener('click', async () => {
        statusEl.textContent = 'Deep scanning…';
        await postJson("{{ route('gideon.scan.deep') }}", {});
        await refresh();
    });

    refreshBtn?.addEventListener('click', async () => {
        await refresh();
    });

    refresh();
})();
</script>
@endpush

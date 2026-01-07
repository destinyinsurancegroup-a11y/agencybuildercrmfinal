<div class="space-y-4" id="gideonGroupsRoot">
    <div class="flex items-center justify-between">
        <div class="text-sm text-gray-500">
            Gideon Scan: <span id="gideonScanStatus">Ready</span>
        </div>

        <div class="flex gap-2">
            <button id="gideonQuickScanBtn" class="px-3 py-2 rounded-lg border text-sm">Scan</button>
            <button id="gideonDeepScanBtn" class="px-3 py-2 rounded-lg bg-black text-white text-sm">Deeper Scan</button>
            <button id="gideonRefreshBtn" class="px-3 py-2 rounded-lg border text-sm">Refresh</button>
            <a href="{{ route('gideon.opportunities.index') }}" class="px-3 py-2 rounded-lg border text-sm">View all</a>
        </div>
    </div>

    <!-- Group cards render here -->
    <div id="gideonGroupCards" class="space-y-3"></div>
</div>

<!-- Modal -->
<div id="gideonGroupModal" class="fixed inset-0 hidden items-center justify-center bg-black/50 z-50">
    <div class="bg-white w-full max-w-3xl rounded-xl shadow-lg overflow-hidden">
        <div class="p-4 border-b flex items-start justify-between">
            <div>
                <div class="text-sm font-semibold" id="gideonModalTitle">Loading…</div>
                <div class="text-xs text-gray-600 mt-1" id="gideonModalWhy"></div>
                <div class="text-xs text-gray-600 mt-1"><span class="font-semibold">Next step:</span> <span id="gideonModalNext"></span></div>
            </div>
            <button id="gideonModalClose" class="px-3 py-2 rounded-lg border text-sm">Close</button>
        </div>

        <div class="p-4">
            <div class="text-xs text-gray-500 mb-3" id="gideonModalCount"></div>

            <div class="space-y-2 max-h-[60vh] overflow-auto" id="gideonModalItems"></div>
        </div>
    </div>
</div>

<script>
(function () {
    const cardsEl = document.getElementById('gideonGroupCards');
    const statusEl = document.getElementById('gideonScanStatus');

    const modalEl = document.getElementById('gideonGroupModal');
    const modalCloseEl = document.getElementById('gideonModalClose');
    const modalTitleEl = document.getElementById('gideonModalTitle');
    const modalWhyEl = document.getElementById('gideonModalWhy');
    const modalNextEl = document.getElementById('gideonModalNext');
    const modalCountEl = document.getElementById('gideonModalCount');
    const modalItemsEl = document.getElementById('gideonModalItems');

    const quickBtn = document.getElementById('gideonQuickScanBtn');
    const deepBtn  = document.getElementById('gideonDeepScanBtn');
    const refreshBtn = document.getElementById('gideonRefreshBtn');

    async function fetchGroups() {
        const res = await fetch("{{ route('gideon.opportunities.groups') }}", {
            headers: { 'Accept': 'application/json' }
        });
        return res.json();
    }

    async function fetchGroupItems(bucket) {
        const url = new URL("{{ route('gideon.opportunities.groupItems') }}", window.location.origin);
        url.searchParams.set('bucket', bucket);

        const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' }});
        return res.json();
    }

    function escapeHtml(str) {
        return String(str ?? '')
            .replaceAll('&','&amp;')
            .replaceAll('<','&lt;')
            .replaceAll('>','&gt;')
            .replaceAll('"','&quot;')
            .replaceAll("'","&#039;");
    }

    function renderGroups(groups) {
        cardsEl.innerHTML = '';

        if (!groups || groups.length === 0) {
            cardsEl.innerHTML = `
                <div class="p-4 rounded-xl border bg-white text-sm text-gray-600">
                    No opportunities found right now.
                </div>
            `;
            return;
        }

        groups.forEach(g => {
            const badge = g.priority === 1 ? 'P1' : 'P' + g.priority;
            const count = Number(g.count || 0);

            const card = document.createElement('div');
            card.className = 'p-4 rounded-xl border bg-white flex items-start justify-between gap-4';

            card.innerHTML = `
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-red-600 text-white">${badge}</span>
                        <div class="text-sm font-semibold">${escapeHtml(g.title)}</div>
                        <div class="text-xs text-gray-500">(${count})</div>
                    </div>

                    <div class="text-xs text-gray-700 mt-2">
                        <span class="font-semibold">Why it matters:</span> ${escapeHtml(g.why)}
                    </div>
                    <div class="text-xs text-gray-700 mt-1">
                        <span class="font-semibold">Next step:</span> ${escapeHtml(g.next)}
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <button class="px-3 py-2 rounded-lg bg-black text-white text-sm gideon-open-group" data-bucket="${escapeHtml(g.bucket)}">
                        View list
                    </button>
                </div>
            `;

            cardsEl.appendChild(card);
        });

        document.querySelectorAll('.gideon-open-group').forEach(btn => {
            btn.addEventListener('click', async () => {
                const bucket = btn.getAttribute('data-bucket');
                await openModal(bucket);
            });
        });
    }

    function openModalUI() {
        modalEl.classList.remove('hidden');
        modalEl.classList.add('flex');
    }

    function closeModalUI() {
        modalEl.classList.add('hidden');
        modalEl.classList.remove('flex');
    }

    async function openModal(bucket) {
        openModalUI();

        modalTitleEl.textContent = 'Loading…';
        modalWhyEl.textContent = '';
        modalNextEl.textContent = '';
        modalCountEl.textContent = '';
        modalItemsEl.innerHTML = '';

        const payload = await fetchGroupItems(bucket);

        modalTitleEl.textContent = payload.title || 'Opportunities';
        modalWhyEl.textContent = payload.why || '';
        modalNextEl.textContent = payload.next || '';

        const items = payload.items || [];
        modalCountEl.textContent = `${items.length} items`;

        if (items.length === 0) {
            modalItemsEl.innerHTML = `
                <div class="p-3 rounded-lg border text-sm text-gray-600">
                    No items found in this group.
                </div>
            `;
            return;
        }

        items.forEach(item => {
            const row = document.createElement('div');
            row.className = 'p-3 rounded-lg border flex items-start justify-between gap-3';

            row.innerHTML = `
                <div class="flex-1">
                    <div class="text-sm font-semibold">${escapeHtml(item.name)}</div>
                    <div class="text-xs text-gray-600 mt-1">${escapeHtml(item.title || '')}</div>
                    <div class="text-xs text-gray-700 mt-1"><span class="font-semibold">Next:</span> ${escapeHtml(item.recommended_action || '')}</div>
                </div>
                <div class="flex flex-col gap-2">
                    <a href="${escapeHtml(item.open_url)}" class="px-3 py-2 rounded-lg border text-sm text-center">
                        Open
                    </a>
                    <button class="px-3 py-2 rounded-lg border text-sm gideon-done" data-id="${item.id}">
                        Done
                    </button>
                    <button class="px-3 py-2 rounded-lg border text-sm gideon-snooze" data-id="${item.id}">
                        Snooze 7 days
                    </button>
                </div>
            `;

            modalItemsEl.appendChild(row);
        });

        // Wire Done/Snooze inside modal
        modalItemsEl.querySelectorAll('.gideon-done').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.getAttribute('data-id');
                await postAction(`/gideon/opportunities/${id}/complete`, {});
                await refresh();
                closeModalUI();
            });
        });

        modalItemsEl.querySelectorAll('.gideon-snooze').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.getAttribute('data-id');
                await postAction(`/gideon/opportunities/${id}/snooze`, { days: 7 });
                await refresh();
                closeModalUI();
            });
        });
    }

    async function postAction(url, body) {
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

    async function refresh() {
        statusEl.textContent = 'Refreshing…';
        const groups = await fetchGroups();
        renderGroups(groups);
        statusEl.textContent = 'Ready';
    }

    // Buttons
    quickBtn?.addEventListener('click', async () => {
        statusEl.textContent = 'Scanning…';
        await postAction("{{ route('gideon.scan') }}", {});
        await refresh();
    });

    deepBtn?.addEventListener('click', async () => {
        statusEl.textContent = 'Deep scanning…';
        await postAction("{{ route('gideon.scan.deep') }}", {});
        await refresh();
    });

    refreshBtn?.addEventListener('click', async () => {
        await refresh();
    });

    modalCloseEl.addEventListener('click', closeModalUI);
    modalEl.addEventListener('click', (e) => {
        if (e.target === modalEl) closeModalUI();
    });

    // Initial load
    refresh();
})();
</script>

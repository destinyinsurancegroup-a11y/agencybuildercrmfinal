{{-- resources/views/activity/popup.blade.php --}}

<div class="modal fade" id="activityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border: 1px solid rgba(201,162,39,.35); border-radius: 16px; overflow:hidden;">
            <div class="modal-header" style="background:#0b1220; color:#fff;">
                <h5 class="modal-title" style="margin:0; font-weight:900;">Track Daily Activity</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body" style="background:#0f172a; color:#e5e7eb;">
                <form id="activityForm" action="{{ route('activity.store') }}" method="POST" autocomplete="off">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label" style="font-weight:800;">Date</label>
                        <input
                            type="date"
                            class="form-control"
                            name="activity_date"
                            value="{{ now()->toDateString() }}"
                            style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);"
                        >
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label" style="font-weight:800;">Leads Worked</label>
                            <input type="number" class="form-control" name="leads_worked" min="0" value="0"
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-weight:800;">Calls</label>
                            <input type="number" class="form-control" name="calls" min="0" value="0"
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>

                        <div class="col-6">
                            <label class="form-label" style="font-weight:800;">Stops</label>
                            <input type="number" class="form-control" name="stops" min="0" value="0"
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-weight:800;">Presentations</label>
                            <input type="number" class="form-control" name="presentations" min="0" value="0"
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>

                        <div class="col-6">
                            <label class="form-label" style="font-weight:800;">Apps Written</label>
                            <input type="number" class="form-control" name="apps_written" min="0" value="0"
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-weight:800;">Premium Collected ($)</label>
                            <input id="premiumInput" type="number" step="0.01" class="form-control" name="premium_collected" min="0" value="0"
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>

                        <div class="col-12">
                            <label class="form-label" style="font-weight:800;">AP ($)</label>
                            <input id="apInput" type="number" step="0.01" class="form-control" name="ap" value="0" readonly
                                   style="background:#0b0f1a; color:#a7f3d0; border-color: rgba(201,162,39,.35); font-weight:900;">
                            <div class="form-text" style="color:#9ca3af;">
                                AP is calculated automatically as <strong>Premium × 12</strong>.
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer" style="background:#0b1220;">
                <button class="btn" id="saveActivityBtn" style="background:#c9a227; color:#111827; font-weight:900; border-radius: 12px;">
                    Save Activity
                </button>
                <button class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 12px;">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const form = document.getElementById('activityForm');
    const saveBtn = document.getElementById('saveActivityBtn');
    const premiumInput = document.getElementById('premiumInput');
    const apInput = document.getElementById('apInput');

    function csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function toNumber(val) {
        const n = parseFloat(val);
        return Number.isFinite(n) ? n : 0;
    }

    function calcAP() {
        const premium = toNumber(premiumInput.value);
        const ap = premium * 12;
        apInput.value = ap.toFixed(2);
        return ap;
    }

    // Always keep AP synced to Premium * 12
    premiumInput.addEventListener('input', calcAP);
    calcAP();

    async function safeJson(resp) {
        try { return await resp.json(); } catch (e) { return null; }
    }

    saveBtn.addEventListener('click', async function (e) {
        e.preventDefault();

        // Prevent double submit
        saveBtn.disabled = true;
        const originalText = saveBtn.textContent;
        saveBtn.textContent = 'Saving...';

        try {
            // Force AP calc right before submit (non-negotiable)
            calcAP();

            const fd = new FormData(form);

            const resp = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: fd,
                credentials: 'same-origin',
                cache: 'no-store'
            });

            const data = await safeJson(resp);

            if (!resp.ok || !data || data.success !== true) {
                const msg = (data && (data.message || data.error)) ? (data.message || data.error) : 'Save failed.';
                alert(msg);
                return;
            }

            // Close the modal first (clean UX)
            const modalEl = document.getElementById('activityModal');
            if (typeof bootstrap !== 'undefined' && modalEl) {
                const instance = bootstrap.Modal.getOrCreateInstance(modalEl);
                instance.hide();
            }

            // Tell dashboard to update instantly.
            // We pass month_totals if the backend returns it.
            const detail = {
                saved: true,
                month_totals: data.month_totals || null,
                raw: data
            };

            // Flag used by fallback reload logic
            window.__activitySaveDashboardHandled = false;

            // Preferred: call a dashboard function if present
            if (typeof window.dashboardAfterActivitySave === 'function') {
                try {
                    await window.dashboardAfterActivitySave(detail);
                } catch (err) {}
            }

            // Secondary: dispatch global event (your dashboard already listens)
            document.dispatchEvent(new CustomEvent('activitySaved', { detail }));

            // Reliable fallback: if dashboard didn’t confirm handling, hard reload.
            // (This solves your “reload didn’t happen reliably” problem.)
            setTimeout(function () {
                if (!window.__activitySaveDashboardHandled) {
                    window.location.reload();
                }
            }, 900);

        } catch (err) {
            console.error(err);
            alert('Request failed.');
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = originalText;
        }
    });
})();
</script>

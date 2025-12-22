<!-- resources/views/activity/popup.blade.php -->

<div class="modal fade" id="activityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border: 1px solid rgba(201,162,39,.35);">
            <div class="modal-header" style="background:#0b0b0b; color:#fff;">
                <h5 class="modal-title" style="margin:0; font-weight:700;">Track Daily Activity</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body" style="background:#111; color:#e5e7eb;">
                <form id="activityForm" action="{{ route('activity.store') }}" method="POST" autocomplete="off">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600;">Date</label>
                        <input
                            type="date"
                            class="form-control"
                            name="activity_date"
                            value="{{ now()->toDateString() }}"
                            style="background:#0f0f0f; color:#fff; border-color: rgba(201,162,39,.35);"
                        >
                        <div class="form-text" style="color:#9ca3af;">
                            Defaults to today. Adjust if logging a prior day.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600;">Leads Worked</label>
                        <input type="number" class="form-control" name="leads_worked" min="0" value="0"
                               style="background:#0f0f0f; color:#fff; border-color: rgba(201,162,39,.35);">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600;">Calls</label>
                        <input type="number" class="form-control" name="calls" min="0" value="0"
                               style="background:#0f0f0f; color:#fff; border-color: rgba(201,162,39,.35);">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600;">Stops</label>
                        <input type="number" class="form-control" name="stops" min="0" value="0"
                               style="background:#0f0f0f; color:#fff; border-color: rgba(201,162,39,.35);">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600;">Presentations</label>
                        <input type="number" class="form-control" name="presentations" min="0" value="0"
                               style="background:#0f0f0f; color:#fff; border-color: rgba(201,162,39,.35);">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600;">Apps Written</label>
                        <input type="number" class="form-control" name="apps_written" min="0" value="0"
                               style="background:#0f0f0f; color:#fff; border-color: rgba(201,162,39,.35);">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600;">Premium Collected ($)</label>
                        <input id="premiumInput" type="number" step="0.01" class="form-control" name="premium_collected" min="0" value="0"
                               style="background:#0f0f0f; color:#fff; border-color: rgba(201,162,39,.35);">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600;">AP ($)</label>
                        <input id="apInput" type="number" step="0.01" class="form-control" name="ap" value="0" readonly
                               style="background:#0b0b0b; color:#a7f3d0; border-color: rgba(201,162,39,.35); font-weight:700;">
                        <div class="form-text" style="color:#9ca3af;">
                            AP is calculated automatically as <strong>Premium × 12</strong>.
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer" style="background:#0b0b0b;">
                <button class="btn btn-warning" id="saveActivityBtn" style="font-weight:700;">
                    Save Activity
                </button>
                <button class="btn btn-secondary" data-bs-dismiss="modal">
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
    }

    // Ensure AP always equals premium * 12
    premiumInput.addEventListener('input', calcAP);
    calcAP();

    saveBtn.addEventListener('click', async function (e) {
        e.preventDefault();

        // Lock button to prevent double-submit
        saveBtn.disabled = true;
        const originalText = saveBtn.textContent;
        saveBtn.textContent = 'Saving...';

        try {
            // Force AP = premium * 12 right before submit (no exceptions)
            calcAP();

            const fd = new FormData(form);

            const resp = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'Accept': 'application/json'
                },
                body: fd,
                credentials: 'same-origin'
            });

            if (!resp.ok) {
                // Try to parse JSON error; otherwise show generic
                let msg = 'Save failed.';
                try {
                    const data = await resp.json();
                    if (data && data.message) msg = data.message;
                } catch (_) {}
                alert(msg);
                return;
            }

            const data = await resp.json();

            if (!data || data.success !== true) {
                alert('Save failed.');
                return;
            }

            // Close modal (Bootstrap 5)
            const modalEl = document.getElementById('activityModal');
            const instance = bootstrap.Modal.getInstance(modalEl);
            if (instance) instance.hide();

            // Tell dashboard to refresh instantly (preferred path)
            window.__dashboardRefreshedAfterActivitySave = false;

            if (typeof window.dashboardAfterActivitySave === 'function') {
                try {
                    await window.dashboardAfterActivitySave();
                } catch (_) {}
            }

            // Also emit a generic event (secondary path)
            document.dispatchEvent(new CustomEvent('activitySaved'));

            // Reliability fallback:
            // If the instant-refresh function didn’t mark success quickly, hard reload.
            setTimeout(function () {
                if (!window.__dashboardRefreshedAfterActivitySave) {
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

{{-- resources/views/activity/popup.blade.php --}}

<div class="modal fade" id="activityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border: 1px solid rgba(201,162,39,.35); border-radius:16px; overflow:hidden;">
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
                            <input type="number" class="form-control" name="leads_worked" min="0" step="1"
                                   placeholder="" inputmode="numeric"
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>

                        <div class="col-6">
                            <label class="form-label" style="font-weight:800;">Calls</label>
                            <input type="number" class="form-control" name="calls" min="0" step="1"
                                   placeholder="" inputmode="numeric"
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>

                        <div class="col-6">
                            <label class="form-label" style="font-weight:800;">Stops</label>
                            <input type="number" class="form-control" name="stops" min="0" step="1"
                                   placeholder="" inputmode="numeric"
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>

                        <div class="col-6">
                            <label class="form-label" style="font-weight:800;">Presentations</label>
                            <input type="number" class="form-control" name="presentations" min="0" step="1"
                                   placeholder="" inputmode="numeric"
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>

                        <div class="col-6">
                            <label class="form-label" style="font-weight:800;">Apps Written</label>
                            <input type="number" class="form-control" name="apps_written" min="0" step="1"
                                   placeholder="" inputmode="numeric"
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>

                        <div class="col-6">
                            <label class="form-label" style="font-weight:800;">Premium Collected ($)</label>
                            <input id="premiumInput" type="number" class="form-control" name="premium_collected"
                                   min="0" step="0.01" placeholder="" inputmode="decimal"
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>

                        <div class="col-12">
                            <label class="form-label" style="font-weight:800;">AP ($)</label>
                            <input id="apInput" type="text" class="form-control" name="ap" readonly value=""
                                   style="background:#0b0f1a; color:#a7f3d0; border-color: rgba(201,162,39,.35); font-weight:900;">
                            <div class="form-text" style="color:#9ca3af;">
                                AP is calculated automatically as <strong>Premium × 12</strong>.
                            </div>
                        </div>
                    </div>

                    <div class="mt-3" id="activitySaveError" style="display:none;" aria-live="polite"></div>
                </form>
            </div>

            <div class="modal-footer" style="background:#0b1220;">
                <button
                    class="btn"
                    type="button"
                    id="saveActivityBtn"
                    onclick="window.ABC_activitySaveClick(event)"
                    style="background:#c9a227; color:#111827; font-weight:900; border-radius:12px;"
                >
                    Save Activity
                </button>

                <button class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:12px;">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    // Prevent duplicate bindings if this modal HTML is injected again
    if (window.__ABC_ACTIVITY_MODAL_WIRED) return;
    window.__ABC_ACTIVITY_MODAL_WIRED = true;

    const form = document.getElementById('activityForm');
    const premiumEl = document.getElementById('premiumInput');
    const apEl = document.getElementById('apInput');
    const errEl = document.getElementById('activitySaveError');
    const saveBtn = document.getElementById('saveActivityBtn');

    function money2(n){
        const v = Number(n || 0);
        return v.toFixed(2);
    }

    function showError(msg){
        if (!errEl) return;
        errEl.style.display = 'block';
        errEl.style.background = 'rgba(239,68,68,.12)';
        errEl.style.border = '1px solid rgba(239,68,68,.35)';
        errEl.style.padding = '10px 12px';
        errEl.style.borderRadius = '12px';
        errEl.style.color = '#fecaca';
        errEl.style.fontWeight = '800';
        errEl.innerText = msg || 'Save failed.';
    }

    function clearError(){
        if (!errEl) return;
        errEl.style.display = 'none';
        errEl.innerText = '';
    }

    // ✅ Live AP preview
    function updateApPreview(){
        const prem = Number(premiumEl && premiumEl.value ? premiumEl.value : 0);
        const ap = prem * 12;
        if (apEl) apEl.value = money2(ap);
    }
    if (premiumEl) premiumEl.addEventListener('input', updateApPreview);
    updateApPreview();

    // ✅ Stop Enter from causing any accidental submission
    if (form) {
        form.addEventListener('keydown', function(e){
            if (e.key === 'Enter') {
                e.preventDefault();
                return false;
            }
        });
    }

    // ✅ THE FIX: one-click = one POST. Locked + button disabled.
    window.ABC_activitySaveClick = async function(e){
        if (e) e.preventDefault();

        // HARD LOCK: prevents double POST (this is what was doubling your premium)
        if (window.__ABC_ACTIVITY_SAVING) return;
        window.__ABC_ACTIVITY_SAVING = true;

        clearError();

        try {
            if (!form) throw new Error('Activity form not found.');

            // Disable button immediately to prevent double click
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.innerText = 'Saving...';
                saveBtn.style.opacity = '0.8';
                saveBtn.style.cursor = 'not-allowed';
            }

            const url = form.getAttribute('action');
            const fd = new FormData(form);

            // Normalize blank numbers to 0 so backend is consistent
            const numericFields = ['leads_worked','calls','stops','presentations','apps_written'];
            numericFields.forEach(name => {
                const v = fd.get(name);
                if (v === null || v === '') fd.set(name, '0');
            });
            const prem = fd.get('premium_collected');
            if (prem === null || prem === '') fd.set('premium_collected', '0');

            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: fd,
                cache: 'no-store',
                credentials: 'same-origin'
            });

            // Laravel validation errors
            if (res.status === 422) {
                const j = await res.json().catch(() => ({}));
                const first = j && j.errors ? Object.values(j.errors)[0]?.[0] : null;
                showError(first || 'Validation failed.');
                return;
            }

            if (!res.ok) {
                const t = await res.text().catch(() => '');
                showError('Save failed. ' + (t ? t.slice(0, 120) : ''));
                return;
            }

            const data = await res.json().catch(() => ({}));

            // ✅ Notify dashboard to refresh instantly
            // (your dashboard listener / dashboardAfterActivitySave will handle totals + goal card)
            try {
                const ev = new CustomEvent('activitySaved', { detail: data || {} });
                document.dispatchEvent(ev);
            } catch (_) {}

            // Close modal
            const modalEl = document.getElementById('activityModal');
            if (modalEl && typeof bootstrap !== 'undefined') {
                const inst = bootstrap.Modal.getOrCreateInstance(modalEl);
                inst.hide();
            }

        } catch (err) {
            showError(err && err.message ? err.message : 'Save failed.');
        } finally {
            window.__ABC_ACTIVITY_SAVING = false;
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerText = 'Save Activity';
                saveBtn.style.opacity = '1';
                saveBtn.style.cursor = 'pointer';
            }
        }
    };
})();
</script>

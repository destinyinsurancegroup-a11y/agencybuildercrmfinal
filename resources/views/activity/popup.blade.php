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
                                   inputmode="numeric"
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>

                        <div class="col-6">
                            <label class="form-label" style="font-weight:800;">Calls</label>
                            <input type="number" class="form-control" name="calls" min="0" step="1"
                                   inputmode="numeric"
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>

                        <div class="col-6">
                            <label class="form-label" style="font-weight:800;">Stops</label>
                            <input type="number" class="form-control" name="stops" min="0" step="1"
                                   inputmode="numeric"
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>

                        <div class="col-6">
                            <label class="form-label" style="font-weight:800;">Presentations</label>
                            <input type="number" class="form-control" name="presentations" min="0" step="1"
                                   inputmode="numeric"
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>

                        <div class="col-6">
                            <label class="form-label" style="font-weight:800;">Apps Written</label>
                            <input type="number" class="form-control" name="apps_written" min="0" step="1"
                                   inputmode="numeric"
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>

                        <div class="col-6">
                            <label class="form-label" style="font-weight:800;">Premium Collected ($)</label>
                            <input id="premiumInput" type="number" class="form-control" name="premium_collected"
                                   min="0" step="0.01" inputmode="decimal"
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>

                        <div class="col-12">
                            <label class="form-label" style="font-weight:800;">AP ($)</label>
                            <input id="apInput" type="number" class="form-control" name="ap" readonly value="0.00"
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
                {{-- IMPORTANT: keep inline onclick so the DASHBOARD handler runs (instant refresh) --}}
                <button
                    class="btn"
                    type="button"
                    id="saveActivityBtn"
                    onclick="window.ABC_activitySaveClick && window.ABC_activitySaveClick(event)"
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
(function () {
    // Prevent duplicate wiring when popup HTML is injected multiple times
    if (window.__ABC_ACTIVITY_POPUP_WIRED) return;
    window.__ABC_ACTIVITY_POPUP_WIRED = true;

    const form = document.getElementById("activityForm");
    const premiumInput = document.getElementById("premiumInput");
    const apInput = document.getElementById("apInput");
    const saveBtn = document.getElementById("saveActivityBtn");
    const errEl = document.getElementById("activitySaveError");

    function showError(msg) {
        if (!errEl) return;
        errEl.style.display = "block";
        errEl.style.background = "rgba(239,68,68,.12)";
        errEl.style.border = "1px solid rgba(239,68,68,.35)";
        errEl.style.padding = "10px 12px";
        errEl.style.borderRadius = "12px";
        errEl.style.color = "#fecaca";
        errEl.style.fontWeight = "800";
        errEl.innerText = msg || "Save failed.";
    }

    function clearError() {
        if (!errEl) return;
        errEl.style.display = "none";
        errEl.innerText = "";
    }

    function setSaving(isSaving) {
        if (!saveBtn) return;
        saveBtn.disabled = !!isSaving;
        saveBtn.innerText = isSaving ? "Saving..." : "Save Activity";
        saveBtn.style.opacity = isSaving ? "0.85" : "1";
        saveBtn.style.cursor = isSaving ? "not-allowed" : "pointer";
    }

    function updateAP() {
        const prem = Number(premiumInput && premiumInput.value ? premiumInput.value : 0);
        const ap = prem * 12;
        if (apInput) apInput.value = ap.toFixed(2);
    }

    if (premiumInput) premiumInput.addEventListener("input", updateAP);
    updateAP();

    // Stop Enter from submitting form in modal
    if (form) {
        form.addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                return false;
            }
        });
    }

    /**
     * =========================================================
     * ✅ CRITICAL FIX FOR “DOUBLES”
     * =========================================================
     * The doubling happened when TWO different save handlers ran:
     * - one deleted blank fields (dashboard)
     * - one forced blanks to "0" (popup)
     * Those payloads are not identical -> server dedupe won't match -> 2 rows -> totals double.
     *
     * So:
     * 1) If the dashboard handler exists, we DO NOT replace it.
     * 2) We WRAP it with a global guard so rapid clicks can't trigger 2 requests.
     */
    if (typeof window.ABC_activitySaveClick === "function" && !window.ABC_activitySaveClick.__ABC_WRAPPED) {
        const original = window.ABC_activitySaveClick;

        const wrapped = async function (e) {
            if (e) e.preventDefault();

            // Global guard (prevents double-click / double-fire)
            if (window.__ABC_ACTIVITY_SAVING) return;
            window.__ABC_ACTIVITY_SAVING = true;

            clearError();
            setSaving(true);

            try {
                // Let the DASHBOARD handler do the save + instant refresh logic
                return await original(e);
            } catch (err) {
                showError(err && err.message ? err.message : "Save failed.");
                throw err;
            } finally {
                window.__ABC_ACTIVITY_SAVING = false;
                setSaving(false);
            }
        };

        wrapped.__ABC_WRAPPED = true;
        window.ABC_activitySaveClick = wrapped;
        return; // dashboard page is handled; popup doesn't need its own save handler
    }

    /**
     * =========================================================
     * Fallback handler (only if dashboard didn't define one)
     * - Uses SAME blank-field behavior as dashboard (delete blanks)
     * - Updates goal instantly by fetching month totals and applying
     * =========================================================
     */
    window.ABC_activitySaveClick = async function (e) {
        if (e) e.preventDefault();
        if (!form) return;

        if (window.__ABC_ACTIVITY_SAVING) return;
        window.__ABC_ACTIVITY_SAVING = true;

        clearError();
        setSaving(true);

        try {
            const url = form.getAttribute("action");
            const fd = new FormData(form);

            // Match dashboard behavior: DELETE blanks (do not force "0" strings)
            for (const [k, v] of fd.entries()) {
                if (typeof v === "string" && v.trim() === "") fd.delete(k);
            }

            const tokenInput = form.querySelector('input[name="_token"]');
            const csrf = tokenInput ? tokenInput.value : null;

            const res = await fetch(url, {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json",
                    ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
                },
                body: fd,
                cache: "no-store",
                credentials: "same-origin",
            });

            if (res.status === 422) {
                const j = await res.json().catch(() => ({}));
                const first = j && j.errors ? Object.values(j.errors)[0]?.[0] : null;
                showError(first || "Validation failed.");
                return;
            }

            if (!res.ok) {
                const t = await res.text().catch(() => "");
                showError("Save failed. " + (t ? t.slice(0, 160) : ""));
                return;
            }

            const data = await res.json().catch(() => ({}));

            // Prefer server month_totals; otherwise fetch once
            let monthTotals = data && data.month_totals ? data.month_totals : null;
            if (!monthTotals) {
                try {
                    const r2 = await fetch(`/activity/totals/month?_=${Date.now()}`, { cache: "no-store" });
                    monthTotals = await r2.json();
                } catch (_) {
                    monthTotals = null;
                }
            }

            // Instant UI refresh
            if (window.refreshProductionCard) window.refreshProductionCard(true);
            if (window.refreshProductionBreakdownModal) window.refreshProductionBreakdownModal(true);

            if (monthTotals && typeof window.applyGoalCardFromTotals === "function") {
                window.applyGoalCardFromTotals(
                    Number(monthTotals.premium_collected || 0),
                    Number(monthTotals.ap || 0)
                );
            } else if (window.refreshGoalCard) {
                window.refreshGoalCard(true);
            }

            // Close modal
            const modalEl = document.getElementById("activityModal");
            if (modalEl && typeof bootstrap !== "undefined") {
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            }
        } catch (err) {
            showError(err && err.message ? err.message : "Save failed.");
        } finally {
            window.__ABC_ACTIVITY_SAVING = false;
            setSaving(false);
        }
    };
})();
</script>

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
                {{-- ✅ IMPORTANT: NO inline onclick (prevents double-submit from competing handlers) --}}
                <button
                    class="btn"
                    type="button"
                    id="saveActivityBtn"
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
    const form = document.getElementById("activityForm");
    const premiumInput = document.getElementById("premiumInput");
    const apInput = document.getElementById("apInput");
    const saveBtn = document.getElementById("saveActivityBtn");
    const errEl = document.getElementById("activitySaveError");

    // ✅ Per-button wiring guard (NOT global forever)
    if (!form || !saveBtn) return;
    if (saveBtn.dataset.abcWired === "1") return;
    saveBtn.dataset.abcWired = "1";

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
    form.addEventListener("keydown", function (e) {
        if (e.key === "Enter") {
            e.preventDefault();
            return false;
        }
    });

    async function forceRefreshDashboardTotals() {
        // ✅ These are defined on dashboard.blade.php
        const p1 = (typeof window.refreshProductionCard === "function")
            ? window.refreshProductionCard(true)
            : Promise.resolve();

        const p2 = (typeof window.refreshProductionBreakdownModal === "function")
            ? window.refreshProductionBreakdownModal(true)
            : Promise.resolve();

        // ✅ This one fetches /activity/totals/month and applies goal card
        const p3 = (typeof window.refreshGoalCard === "function")
            ? window.refreshGoalCard(true)
            : Promise.resolve();

        await Promise.allSettled([p1, p2, p3]);
    }

    async function doSave(e) {
        if (e) e.preventDefault();

        // ✅ Global lock (prevents double click AND prevents competing handlers)
        if (window.__ABC_ACTIVITY_SAVING) return;
        window.__ABC_ACTIVITY_SAVING = true;

        clearError();
        setSaving(true);

        try {
            const url = form.getAttribute("action");
            const fd = new FormData(form);

            // Normalize blanks to zero so server gets numbers
            ["leads_worked","calls","stops","presentations","apps_written"].forEach(name => {
                const v = fd.get(name);
                if (v === null || v === "") fd.set(name, "0");
            });
            const prem = fd.get("premium_collected");
            if (prem === null || prem === "") fd.set("premium_collected", "0");

            const res = await fetch(url, {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json",
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

            // ✅ FAST PATH: if server returned month_totals, apply immediately (instant visual update)
            if (data && data.month_totals && typeof window.applyGoalCardFromTotals === "function") {
                window.applyGoalCardFromTotals(
                    Number(data.month_totals.premium_collected || 0),
                    Number(data.month_totals.ap || 0)
                );
            }

            // Close modal right away
            const modalEl = document.getElementById("activityModal");
            if (modalEl && typeof bootstrap !== "undefined") {
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            }

            // ✅ TRUTH PASS: always force-refresh from server so it can NEVER be “doubled”
            // (this makes UI match DB even if anything weird happened)
            await forceRefreshDashboardTotals();

        } catch (err) {
            showError(err && err.message ? err.message : "Save failed.");
        } finally {
            window.__ABC_ACTIVITY_SAVING = false;
            setSaving(false);
        }
    }

    // expose for compatibility (if anything calls it)
    window.ABC_activitySaveClick = doSave;

    // ✅ ONLY ONE click listener
    saveBtn.addEventListener("click", doSave);
})();
</script>

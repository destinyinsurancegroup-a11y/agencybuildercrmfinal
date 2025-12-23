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

    // ✅ CRITICAL: kill the inline onclick + prevent any other click handlers from firing
    if (saveBtn) {
        saveBtn.onclick = null; // removes the inline handler at runtime
    }

    async function doSave(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
            // this prevents any other click listeners from also running
            if (typeof e.stopImmediatePropagation === "function") e.stopImmediatePropagation();
        }

        if (!form) return;

        // ✅ single-flight guard so one click = one POST
        if (window.__ABC_ACTIVITY_SAVING) return;
        window.__ABC_ACTIVITY_SAVING = true;

        clearError();

        const originalText = saveBtn ? saveBtn.innerText : "";
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerText = "Saving...";
        }

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

            // ✅ Use server-provided month_totals (fast + accurate + prevents doubling)
            let monthTotals = data && data.month_totals ? data.month_totals : null;

            // Fallback ONLY if server didn't send totals
            if (!monthTotals) {
                try {
                    const r2 = await fetch(`/activity/totals/month?_=${Date.now()}`, { cache: "no-store" });
                    monthTotals = await r2.json();
                } catch (_) {
                    monthTotals = null;
                }
            }

            const eventDetail = Object.assign({}, data || {});
            if (monthTotals) eventDetail.month_totals = monthTotals;

            // ✅ Dispatch event so dashboard updates instantly
            document.dispatchEvent(new CustomEvent("activitySaved", { detail: eventDetail }));

            // Close modal
            const modalEl = document.getElementById("activityModal");
            if (modalEl && typeof bootstrap !== "undefined") {
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            }
        } catch (err) {
            showError(err && err.message ? err.message : "Save failed.");
        } finally {
            window.__ABC_ACTIVITY_SAVING = false;
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerText = originalText || "Save Activity";
            }
        }
    }

    // ✅ Attach one real click handler
    if (saveBtn) {
        saveBtn.addEventListener("click", doSave, true); // capture phase helps beat other listeners
    }

    // ✅ Keep global name for compatibility (if something still calls it)
    window.ABC_activitySaveClick = doSave;

})();
</script>

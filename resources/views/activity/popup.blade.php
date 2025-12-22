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
                            <input type="number" class="form-control abc-clear-zero" name="leads_worked" min="0" step="1"
                                   placeholder="0" inputmode="numeric" value=""
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>

                        <div class="col-6">
                            <label class="form-label" style="font-weight:800;">Calls</label>
                            <input type="number" class="form-control abc-clear-zero" name="calls" min="0" step="1"
                                   placeholder="0" inputmode="numeric" value=""
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>

                        <div class="col-6">
                            <label class="form-label" style="font-weight:800;">Stops</label>
                            <input type="number" class="form-control abc-clear-zero" name="stops" min="0" step="1"
                                   placeholder="0" inputmode="numeric" value=""
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>

                        <div class="col-6">
                            <label class="form-label" style="font-weight:800;">Presentations</label>
                            <input type="number" class="form-control abc-clear-zero" name="presentations" min="0" step="1"
                                   placeholder="0" inputmode="numeric" value=""
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>

                        <div class="col-6">
                            <label class="form-label" style="font-weight:800;">Apps Written</label>
                            <input type="number" class="form-control abc-clear-zero" name="apps_written" min="0" step="1"
                                   placeholder="0" inputmode="numeric" value=""
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>

                        <div class="col-6">
                            <label class="form-label" style="font-weight:800;">Premium Collected ($)</label>
                            <input id="premiumInput" type="number" class="form-control abc-clear-zero" name="premium_collected"
                                   min="0" step="0.01" placeholder="0.00" inputmode="decimal" value=""
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>

                        <div class="col-12">
                            <label class="form-label" style="font-weight:800;">AP ($)</label>
                            <input id="apInput" type="text" class="form-control" readonly value=""
                                   style="background:#0b0f1a; color:#a7f3d0; border-color: rgba(201,162,39,.35); font-weight:900;"
                                   placeholder="0.00">
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
    // Prevent duplicate init if modal HTML is injected multiple times
    if (window.__ABC_activityModalInit) return;
    window.__ABC_activityModalInit = true;

    function money2(n) {
        const v = Number(n || 0);
        return v.toFixed(2);
    }

    function computeApFromPremium(premium) {
        const p = Number(premium || 0);
        const ap = p * 12;
        return Math.round(ap * 100) / 100;
    }

    function showError(msg) {
        const box = document.getElementById("activitySaveError");
        if (!box) return;
        box.style.display = "block";
        box.style.background = "rgba(239,68,68,.15)";
        box.style.border = "1px solid rgba(239,68,68,.35)";
        box.style.color = "#fecaca";
        box.style.padding = "10px 12px";
        box.style.borderRadius = "12px";
        box.style.fontWeight = "800";
        box.innerText = msg || "Save failed.";
    }

    function clearError() {
        const box = document.getElementById("activitySaveError");
        if (!box) return;
        box.style.display = "none";
        box.innerText = "";
    }

    // Remove the annoying leading 0 on focus (only if value is exactly "0" or "0.00")
    function wireClearZeroInputs() {
        document.querySelectorAll("#activityModal .abc-clear-zero").forEach((el) => {
            el.addEventListener("focus", () => {
                const v = (el.value || "").trim();
                if (v === "0" || v === "0.0" || v === "0.00") el.value = "";
            });
        });
    }

    function wireApLiveCalc() {
        const premiumInput = document.getElementById("premiumInput");
        const apInput = document.getElementById("apInput");
        if (!premiumInput || !apInput) return;

        const update = () => {
            const premium = premiumInput.value;
            const ap = computeApFromPremium(premium);
            apInput.value = money2(ap);
        };

        premiumInput.addEventListener("input", update);
        premiumInput.addEventListener("blur", update);
        update();
    }

    async function doSave(ev) {
        try {
            if (ev) ev.preventDefault();
            clearError();

            const form = document.getElementById("activityForm");
            const btn = document.getElementById("saveActivityBtn");
            if (!form) {
                showError("Form not found.");
                return;
            }

            if (btn) {
                btn.disabled = true;
                btn.innerText = "Saving...";
            }

            const fd = new FormData(form);

            const res = await fetch(form.action, {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json",
                },
                body: fd,
                cache: "no-store",
            });

            let payload = null;
            const text = await res.text();
            try { payload = text ? JSON.parse(text) : null; } catch (e) { payload = null; }

            if (!res.ok || !payload || payload.success !== true) {
                showError("Save failed. Please try again.");
                if (btn) {
                    btn.disabled = false;
                    btn.innerText = "Save Activity";
                }
                return;
            }

            // ✅ INSTANT DASHBOARD UPDATE (Option A):
            // Use totals returned by store() response, no need to call /activity/totals
            if (typeof window.dashboardAfterActivitySave === "function") {
                window.dashboardAfterActivitySave(payload);
            }

            // Also dispatch event for any listeners
            document.dispatchEvent(new CustomEvent("activitySaved", { detail: payload }));

            // Close modal
            const modalEl = document.getElementById("activityModal");
            if (modalEl && typeof bootstrap !== "undefined") {
                const inst = bootstrap.Modal.getOrCreateInstance(modalEl);
                inst.hide();
            }

            // Reset fields (keep date)
            const dateEl = form.querySelector('input[name="activity_date"]');
            const keepDate = dateEl ? dateEl.value : "";
            form.reset();
            if (dateEl) dateEl.value = keepDate;

            // Reset AP display
            const apInput = document.getElementById("apInput");
            if (apInput) apInput.value = "";

            if (btn) {
                btn.disabled = false;
                btn.innerText = "Save Activity";
            }
        } catch (err) {
            console.error(err);
            showError("Save failed. Please try again.");
            const btn = document.getElementById("saveActivityBtn");
            if (btn) {
                btn.disabled = false;
                btn.innerText = "Save Activity";
            }
        }
    }

    // Expose a single click handler used by onclick (works with injected HTML)
    window.ABC_activitySaveClick = function (ev) {
        doSave(ev);
    };

    // Init wires on load
    wireClearZeroInputs();
    wireApLiveCalc();
})();
</script>

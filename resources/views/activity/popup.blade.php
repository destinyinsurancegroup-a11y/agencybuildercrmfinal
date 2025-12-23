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
                            <input type="number" class="form-control" name="leads_worked" min="0" step="1" inputmode="numeric"
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>

                        <div class="col-6">
                            <label class="form-label" style="font-weight:800;">Calls</label>
                            <input type="number" class="form-control" name="calls" min="0" step="1" inputmode="numeric"
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>

                        <div class="col-6">
                            <label class="form-label" style="font-weight:800;">Stops</label>
                            <input type="number" class="form-control" name="stops" min="0" step="1" inputmode="numeric"
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>

                        <div class="col-6">
                            <label class="form-label" style="font-weight:800;">Presentations</label>
                            <input type="number" class="form-control" name="presentations" min="0" step="1" inputmode="numeric"
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>

                        <div class="col-6">
                            <label class="form-label" style="font-weight:800;">Apps Written</label>
                            <input type="number" class="form-control" name="apps_written" min="0" step="1" inputmode="numeric"
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>

                        <div class="col-6">
                            <label class="form-label" style="font-weight:800;">Premium Collected ($)</label>
                            <input
                                id="premiumInput"
                                type="number"
                                class="form-control"
                                name="premium_collected"
                                min="0"
                                step="0.01"
                                inputmode="decimal"
                                style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);"
                                oninput="
                                    (function(v){
                                        var prem = Number(v || 0);
                                        var ap = prem * 12;
                                        var apEl = document.getElementById('apInput');
                                        if(apEl) apEl.value = ap.toFixed(2);
                                    })(this.value);
                                "
                            >
                        </div>

                        <div class="col-12">
                            <label class="form-label" style="font-weight:800;">AP ($)</label>
                            <input
                                id="apInput"
                                type="number"
                                class="form-control"
                                name="ap"
                                readonly
                                value="0.00"
                                style="background:#0b0f1a; color:#a7f3d0; border-color: rgba(201,162,39,.35); font-weight:900;"
                            >
                            <div class="form-text" style="color:#9ca3af;">
                                AP is calculated automatically as <strong>Premium × 12</strong>.
                            </div>
                        </div>
                    </div>

                    {{-- Dashboard save handler uses this element for errors --}}
                    <div class="mt-3" id="activitySaveError" style="display:none;" aria-live="polite"></div>
                </form>
            </div>

            <div class="modal-footer" style="background:#0b1220;">
                {{-- ✅ IMPORTANT: Use ONLY the dashboard handler (instant refresh) --}}
                {{-- ✅ No addEventListener in popup, no redefining ABC_activitySaveClick --}}
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

{{-- Optional tiny helper: prevent Enter key from submitting the form.
     If this script doesn't execute due to innerHTML injection, it's still fine. --}}
<script>
(function(){
    var form = document.getElementById('activityForm');
    if (!form) return;
    form.addEventListener('keydown', function(e){
        if (e.key === 'Enter') {
            e.preventDefault();
            return false;
        }
    });

    // initialize AP once (in case premium prefilled)
    var premEl = document.getElementById('premiumInput');
    var apEl = document.getElementById('apInput');
    if (premEl && apEl) {
        var prem = Number(premEl.value || 0);
        apEl.value = (prem * 12).toFixed(2);
    }
})();
</script>

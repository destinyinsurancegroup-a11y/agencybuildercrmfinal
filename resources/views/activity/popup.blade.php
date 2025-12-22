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
                        <input type="date" class="form-control"
                               name="activity_date"
                               value="{{ now()->toDateString() }}"
                               style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                    </div>

                    <div class="row g-3">
                        @foreach ([
                            'leads_worked' => 'Leads Worked',
                            'calls' => 'Calls',
                            'stops' => 'Stops',
                            'presentations' => 'Presentations',
                            'apps_written' => 'Apps Written'
                        ] as $name => $label)
                            <div class="col-6">
                                <label class="form-label" style="font-weight:800;">{{ $label }}</label>
                                <input type="number" class="form-control"
                                       name="{{ $name }}"
                                       min="0" step="1"
                                       placeholder=""
                                       style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                            </div>
                        @endforeach

                        <div class="col-6">
                            <label class="form-label" style="font-weight:800;">Premium Collected ($)</label>
                            <input id="premiumInput" type="number" class="form-control"
                                   name="premium_collected"
                                   min="0" step="0.01"
                                   placeholder=""
                                   style="background:#0b1220; color:#fff; border-color: rgba(201,162,39,.35);">
                        </div>

                        <div class="col-6">
                            <label class="form-label" style="font-weight:800;">AP ($)</label>
                            <input id="apInput" type="number" class="form-control" readonly value="0.00"
                                   style="background:#0b0f1a; color:#a7f3d0; border-color: rgba(201,162,39,.35); font-weight:900;">
                            <div class="form-text" style="color:#9ca3af;">
                                AP = Premium × 12
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 text-danger" id="activitySaveError" style="display:none;"></div>
                </form>
            </div>

            <div class="modal-footer" style="background:#0b1220;">
                <button class="btn"
                        type="button"
                        id="saveActivityBtn"
                        style="background:#c9a227; color:#111827; font-weight:900; border-radius:12px;">
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
    const form = document.getElementById('activityForm');
    const saveBtn = document.getElementById('saveActivityBtn');
    const premiumInput = document.getElementById('premiumInput');
    const apInput = document.getElementById('apInput');
    const errorBox = document.getElementById('activitySaveError');

    function updateAP() {
        const premium = parseFloat(premiumInput.value || 0);
        apInput.value = (premium * 12).toFixed(2);
    }

    premiumInput.addEventListener('input', updateAP);

    saveBtn.addEventListener('click', async function () {
        errorBox.style.display = 'none';
        saveBtn.disabled = true;

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': form.querySelector('[name=_token]').value,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new FormData(form)
            });

            if (!res.ok) throw new Error('Save failed');

            // ✅ IMMEDIATELY get updated MONTH totals
            const totalsRes = await fetch('/activity/totals/month?_=' + Date.now(), {
                cache: 'no-store'
            });
            const monthTotals = await totalsRes.json();

            // ✅ HAND TOTALS DIRECTLY TO DASHBOARD (INSTANT)
            if (window.dashboardAfterActivitySave) {
                window.dashboardAfterActivitySave({ month_totals: monthTotals });
            }

            // Close modal
            const modalEl = document.getElementById('activityModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            modal.hide();

        } catch (err) {
            errorBox.innerText = 'Failed to save activity.';
            errorBox.style.display = 'block';
        } finally {
            saveBtn.disabled = false;
        }
    });
})();
</script>

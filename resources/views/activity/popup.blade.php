{{-- resources/views/activity/popup.blade.php --}}

<div class="modal fade" id="abc-activity-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Daily Activity</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <form id="abc-activity-form" method="POST" action="{{ $saveUrl ?? route('activity.store') }}">
          @csrf

          {{-- Your existing fields here (date, notes, etc.) --}}

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="abc-premium-collected">Premium Collected</label>
              <input
                id="abc-premium-collected"
                name="premium_collected"
                type="number"
                step="0.01"
                inputmode="decimal"
                class="form-control"
                value="{{ old('premium_collected', $activity->premium_collected ?? '') }}"
                autocomplete="off"
              />
            </div>

            <div class="col-md-6">
              <label class="form-label" for="abc-ap-earned">AP Earned</label>
              <input
                id="abc-ap-earned"
                name="ap_earned"
                type="number"
                step="0.01"
                inputmode="decimal"
                class="form-control"
                value="{{ old('ap_earned', $activity->ap_earned ?? '') }}"
                autocomplete="off"
              />
              {{-- Optional: pass multiplier if you have it available; leave blank otherwise --}}
              <input
                id="abc-ap-multiplier"
                type="hidden"
                value="{{ $apMultiplier ?? '' }}"
              />
            </div>
          </div>

          {{-- Any other existing inputs... --}}

        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>

        {{-- IMPORTANT: no inline onclick here --}}
        <button type="button" class="btn btn-primary" id="abc-activity-save">
          Save
        </button>
      </div>

    </div>
  </div>
</div>

<script>
(() => {
  // This popup is injected dynamically; bind safely once per injection.
  const modalEl = document.getElementById('abc-activity-modal');
  if (!modalEl || modalEl.dataset.abcBound === '1') return;
  modalEl.dataset.abcBound = '1';

  const form = document.getElementById('abc-activity-form');
  const saveBtn = document.getElementById('abc-activity-save');
  const premiumEl = document.getElementById('abc-premium-collected');
  const apEl = document.getElementById('abc-ap-earned');
  const multEl = document.getElementById('abc-ap-multiplier');

  // Prevent Enter from submitting (we only allow one save path via the dashboard handler)
  if (form) {
    form.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') e.preventDefault();
    });
    form.addEventListener('submit', (e) => e.preventDefault());
  }

  // Optional AP auto-calc (only if multiplier is provided OR you already have a global recalculator)
  const recalcAp = () => {
    if (!premiumEl || !apEl) return;

    if (typeof window.ABC_recalcApFromPremium === 'function') {
      window.ABC_recalcApFromPremium(premiumEl, apEl);
      return;
    }

    const multRaw = (multEl && multEl.value != null) ? String(multEl.value).trim() : '';
    if (!multRaw) return; // do nothing unless multiplier is explicitly supplied

    const premium = parseFloat(premiumEl.value || '0');
    const mult = parseFloat(multRaw || '0');
    if (!Number.isFinite(premium) || !Number.isFinite(mult)) return;

    apEl.value = (premium * mult).toFixed(2);
  };

  if (premiumEl) premiumEl.addEventListener('input', recalcAp);

  // Wire save button to the SINGLE global handler (dashboard-defined).
  if (saveBtn) {
    // Ensure we do not also have leftover inline onclick attributes
    saveBtn.removeAttribute('onclick');

    saveBtn.addEventListener('click', (e) => {
      if (typeof window.ABC_activitySaveClick === 'function') {
        window.ABC_activitySaveClick(e);
      } else {
        console.error('ABC_activitySaveClick is not defined on window.');
      }
    });
  }
})();
</script>

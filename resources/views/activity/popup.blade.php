{{-- resources/views/activity/popup.blade.php --}}

<div class="modal fade" id="activityModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" style="font-weight:900;">Track Daily Activity</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        {{-- Error area used by dashboard handler --}}
        <div id="activitySaveError" style="display:none; margin-bottom:10px;"></div>

        <form id="activityForm" method="POST" action="{{ $action ?? url('/activity/save') }}">
          @csrf

          {{-- NOTE: Keep your existing real inputs / names. These are safe defaults. --}}
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="premium_collected">Premium Collected</label>
              <input id="premium_collected" name="premium_collected" type="number" step="0.01" class="form-control" inputmode="decimal" autocomplete="off">
            </div>

            <div class="col-md-6">
              <label class="form-label" for="ap">AP</label>
              <input id="ap" name="ap" type="number" step="0.01" class="form-control" inputmode="decimal" autocomplete="off">
            </div>

            <div class="col-md-6">
              <label class="form-label" for="leads_worked">Leads Worked</label>
              <input id="leads_worked" name="leads_worked" type="number" step="1" class="form-control" inputmode="numeric" autocomplete="off">
            </div>

            <div class="col-md-6">
              <label class="form-label" for="calls">Calls</label>
              <input id="calls" name="calls" type="number" step="1" class="form-control" inputmode="numeric" autocomplete="off">
            </div>

            <div class="col-md-6">
              <label class="form-label" for="stops">Stops</label>
              <input id="stops" name="stops" type="number" step="1" class="form-control" inputmode="numeric" autocomplete="off">
            </div>

            <div class="col-md-6">
              <label class="form-label" for="presentations">Presentations</label>
              <input id="presentations" name="presentations" type="number" step="1" class="form-control" inputmode="numeric" autocomplete="off">
            </div>

            <div class="col-md-6">
              <label class="form-label" for="apps_written">Apps Written</label>
              <input id="apps_written" name="apps_written" type="number" step="1" class="form-control" inputmode="numeric" autocomplete="off">
            </div>

            <div class="col-12">
              <label class="form-label" for="notes">Notes</label>
              <textarea id="notes" name="notes" class="form-control" rows="3"></textarea>
            </div>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>

        {{-- ONLY ONE SAVE PATH: this calls the dashboard handler. No extra listeners here. --}}
        <button type="button" class="btn btn-primary" id="saveActivityBtn" onclick="ABC_activitySaveClick(event)">
          Save Activity
        </button>
      </div>

    </div>
  </div>
</div>

<script>
(function () {
  // Prevent Enter from submitting the form (we want only the dashboard JS save handler)
  const form = document.getElementById('activityForm');
  if (!form) return;

  form.addEventListener('submit', function(e){ e.preventDefault(); });
  form.addEventListener('keydown', function(e){
    if (e.key === 'Enter') e.preventDefault();
  });
})();
</script>

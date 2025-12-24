{{-- resources/views/activity/popup.blade.php --}}

<div class="modal fade" id="activityModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" style="font-weight:900;">Track Daily Activity</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">

        <div id="activitySaveError" style="display:none; margin-bottom:10px;"></div>

        {{-- IMPORTANT: action should match your real save route --}}
        <form id="activityForm" method="POST" action="{{ url('/activity/store') }}">
          @csrf

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Premium Collected</label>
              <input class="form-control" type="number" step="0.01" name="premium_collected" inputmode="decimal" autocomplete="off">
            </div>

            <div class="col-md-6">
              <label class="form-label">AP</label>
              <input class="form-control" type="number" step="0.01" name="ap" inputmode="decimal" autocomplete="off">
            </div>

            <div class="col-md-4">
              <label class="form-label">Leads Worked</label>
              <input class="form-control" type="number" step="1" name="leads_worked" inputmode="numeric" autocomplete="off">
            </div>

            <div class="col-md-4">
              <label class="form-label">Calls</label>
              <input class="form-control" type="number" step="1" name="calls" inputmode="numeric" autocomplete="off">
            </div>

            <div class="col-md-4">
              <label class="form-label">Stops</label>
              <input class="form-control" type="number" step="1" name="stops" inputmode="numeric" autocomplete="off">
            </div>

            <div class="col-md-6">
              <label class="form-label">Presentations</label>
              <input class="form-control" type="number" step="1" name="presentations" inputmode="numeric" autocomplete="off">
            </div>

            <div class="col-md-6">
              <label class="form-label">Apps Written</label>
              <input class="form-control" type="number" step="1" name="apps_written" inputmode="numeric" autocomplete="off">
            </div>

            <div class="col-12">
              <label class="form-label">Notes</label>
              <textarea class="form-control" name="notes" rows="3"></textarea>
            </div>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>

        {{-- ONLY ONE SAVE PATH --}}
        <button type="button" class="btn btn-primary" id="saveActivityBtn" onclick="ABC_activitySaveClick(event)">
          Save Activity
        </button>
      </div>

    </div>
  </div>
</div>

<script>
(function () {
  // Prevent Enter-submit so the only save path is the button -> dashboard handler
  const form = document.getElementById('activityForm');
  if (!form) return;

  form.addEventListener('submit', function(e){ e.preventDefault(); });
  form.addEventListener('keydown', function(e){
    if (e.key === 'Enter') e.preventDefault();
  });
})();
</script>

<div class="modal-header">
    <h4 class="modal-title">Track Daily Activity</h4>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

    <form id="activityForm" action="{{ route('activity.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label>Leads Worked:</label>
            <input type="number" class="form-control" name="leads_worked" placeholder="0">
        </div>

        <div class="mb-3">
            <label>Calls:</label>
            <input type="number" class="form-control" name="calls" placeholder="0">
        </div>

        <div class="mb-3">
            <label>Stops:</label>
            <input type="number" class="form-control" name="stops" placeholder="0">
        </div>

        <div class="mb-3">
            <label>Presentations:</label>
            <input type="number" class="form-control" name="presentations" placeholder="0">
        </div>

        <div class="mb-3">
            <label>Apps Written:</label>
            <input type="number" class="form-control" name="apps_written" placeholder="0">
        </div>

        <div class="mb-3">
            <label>Premium Collected ($):</label>
            <input type="number" step="0.01" class="form-control" name="premium_collected" placeholder="0.00">
        </div>

        <div class="mb-3">
            <label>AP ($):</label>
            <input type="number" step="0.01" class="form-control" name="ap" placeholder="0.00">
        </div>

    </form>

</div>

<div class="modal-footer">
    <button class="btn btn-warning" onclick="document.getElementById('activityForm').submit();">
        Save Activity
    </button>

    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
</div>

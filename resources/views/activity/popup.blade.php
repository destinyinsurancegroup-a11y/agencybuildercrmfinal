<div class="modal-header">
    <h4 class="modal-title">Track Daily Activity</h4>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

    <form id="activityForm" action="{{ route('activity.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label>Calls:</label>
            <input type="number" class="form-control" name="calls">
        </div>

        <div class="mb-3">
            <label>Answered:</label>
            <input type="number" class="form-control" name="answered">
        </div>

        <div class="mb-3">
            <label>Stops:</label>
            <input type="number" class="form-control" name="stops">
        </div>

        <div class="mb-3">
            <label>Presentations:</label>
            <input type="number" class="form-control" name="presentations">
        </div>

        <div class="mb-3">
            <label>No’s:</label>
            <input type="number" class="form-control" name="nos">
        </div>

        <div class="mb-3">
            <label>Sales (Apps):</label>
            <input type="number" class="form-control" name="sales_apps">
        </div>

        <div class="mb-3">
            <label>Sales (Premium $):</label>
            <input type="number" class="form-control" name="sales_premium">
        </div>
    </form>

</div>

<div class="modal-footer">
    <button class="btn btn-warning" onclick="document.getElementById('activityForm').submit();">
        Save Activity
    </button>

    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
</div>

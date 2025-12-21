<!-- FULL BOOTSTRAP MODAL WRAPPER -->
<div class="modal fade" id="activityModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h4 class="modal-title">Track Daily Activity</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <form id="activityForm" action="{{ route('activity.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label>Leads Worked:</label>
                        <input type="number" class="form-control" name="leads_worked" placeholder="0" min="0">
                    </div>

                    <div class="mb-3">
                        <label>Calls:</label>
                        <input type="number" class="form-control" name="calls" placeholder="0" min="0">
                    </div>

                    <div class="mb-3">
                        <label>Stops:</label>
                        <input type="number" class="form-control" name="stops" placeholder="0" min="0">
                    </div>

                    <div class="mb-3">
                        <label>Presentations:</label>
                        <input type="number" class="form-control" name="presentations" placeholder="0" min="0">
                    </div>

                    <div class="mb-3">
                        <label>Apps Written:</label>
                        <input type="number" class="form-control" name="apps_written" placeholder="0" min="0">
                    </div>

                    <div class="mb-3">
                        <label>Premium Collected ($):</label>
                        <input id="premiumInput" type="number" step="0.01" class="form-control" name="premium_collected" placeholder="0.00" min="0">
                    </div>

                    <div class="mb-3">
                        <label>AP ($):</label>
                        <input id="apInput" type="number" step="0.01" class="form-control" name="ap" placeholder="0.00" readonly>
                    </div>

                </form>

            </div>

            <div class="modal-footer">
                <button class="btn btn-warning" id="saveActivityBtn" type="button">Save Activity</button>
                <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Close</button>
            </div>

        </div>
    </div>
</div>

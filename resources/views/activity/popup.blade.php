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
                <button class="btn btn-warning" id="saveActivityBtn">Save Activity</button>
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>

<!-- AJAX HANDLER -->
<script>
document.addEventListener("click", async function (e) {

    if (e.target.id !== "saveActivityBtn") return;
    e.preventDefault();

    let form = document.getElementById("activityForm");
    let formData = new FormData(form);

    try {
        let res = await fetch("{{ route('activity.store') }}", {
            method: "POST",
            body: formData,
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Accept": "application/json",              // ⭐ Required fix
                "X-Requested-With": "XMLHttpRequest"       // ⭐ Ensures JSON response
            }
        });

        let data = await res.json().catch(() => null);

        if (!data || !data.success) {
            alert("Error saving activity.");
            return;
        }

        // Close modal
        let modalEl = document.querySelector(".modal.show");
        if (modalEl) {
            let instance = bootstrap.Modal.getInstance(modalEl);
            instance.hide();
        }

        // Reset form
        form.reset();

        // Refresh dashboard production card
        document.dispatchEvent(new CustomEvent("activitySaved"));

    } catch (err) {
        console.error(err);
        alert("Request failed.");
    }
});
</script>

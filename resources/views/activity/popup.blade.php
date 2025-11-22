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
    <button class="btn btn-warning" id="saveActivityBtn">
        Save Activity
    </button>

    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
</div>

<!-- ✅ AJAX SCRIPT (this is the only part added) -->
<script>
document.getElementById("saveActivityBtn").addEventListener("click", function (e) {
    e.preventDefault(); // stop page redirect

    let form = document.getElementById("activityForm");
    let formData = new FormData(form);

    fetch("{{ route('activity.store') }}", {
        method: "POST",
        body: formData,
        headers: {
            "X-CSRF-TOKEN": '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {

            // ✅ Close popup
            let modal = bootstrap.Modal.getInstance(
                document.querySelector(".modal.show")
            );
            modal.hide();

            // ✅ Reload production stats using an event the dashboard listens for
            document.dispatchEvent(new CustomEvent("activitySaved"));

        } else {
            alert("Error saving activity.");
        }
    })
    .catch(err => {
        console.error(err);
        alert("Request failed.");
    });
});
</script>

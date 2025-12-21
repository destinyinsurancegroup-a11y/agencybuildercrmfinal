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
                        <input id="premiumInput" type="number" step="0.01" class="form-control" name="premium_collected" placeholder="0.00">
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

<!-- AUTO-CALCULATE AP (premium * 12) -->
<script>
(function () {
    function safeFloat(v) {
        const n = parseFloat(v);
        return isNaN(n) ? 0 : n;
    }

    document.addEventListener("input", function (e) {
        if (!e.target || e.target.id !== "premiumInput") return;

        const premiumEl = document.getElementById("premiumInput");
        const apEl = document.getElementById("apInput");
        if (!premiumEl || !apEl) return;

        const premium = safeFloat(premiumEl.value);
        apEl.value = (premium * 12).toFixed(2);
    });
})();
</script>

<!-- AJAX HANDLER (✅ dispatch month totals so dashboard updates INSTANTLY) -->
<script>
(function () {

    document.addEventListener("click", async function (e) {

        if (!e.target || e.target.id !== "saveActivityBtn") return;
        e.preventDefault();

        const form = document.getElementById("activityForm");
        if (!form) return;

        const formData = new FormData(form);

        try {
            const res = await fetch("{{ route('activity.store') }}", {
                method: "POST",
                body: formData,
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                },
                cache: "no-store"
            });

            const data = await res.json().catch(() => null);

            if (!data || !data.success) {
                console.error("Store response:", data);
                alert("Error saving activity.");
                return;
            }

            // ✅ Fire event WITH month totals (this is the key fix)
            document.dispatchEvent(new CustomEvent("activitySaved", {
                detail: {
                    savedAt: Date.now(),
                    month_totals: data.month_totals || null,
                    activity: data.activity || null
                }
            }));

            // Close modal
            const modalEl = document.querySelector(".modal.show");
            if (modalEl && typeof bootstrap !== "undefined") {
                const instance = bootstrap.Modal.getInstance(modalEl);
                if (instance) instance.hide();
            }

            // Reset after close
            form.reset();

        } catch (err) {
            console.error(err);
            alert("Request failed.");
        }
    });

})();
</script>

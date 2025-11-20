<div class="p-4" style="background:#ffffff; border-radius:18px;">

    <h2 class="text-gold fw-bold mb-4" style="font-size:26px;">Add Client</h2>

    <form 
        id="create-client-form"
        action="{{ route('book.store') }}"
        method="POST"
    >
        @csrf


        <!-- ============================
             CLIENT INFORMATION
             ============================ -->
        <h5 class="fw-bold mb-3">Client Information</h5>

        <div class="row g-3 mb-4">
            
            <div class="col-md-6">
                <label class="form-label fw-semibold">Client Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Phone</label>
                <input type="text" name="phone" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" name="email" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Date of Birth</label>
                <input type="date" name="date_of_birth" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Age</label>
                <input type="number" name="age" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Anniversary</label>
                <input type="date" name="anniversary" class="form-control">
            </div>
        </div>




        <!-- ============================
             ADDRESS
             ============================ -->
        <h5 class="fw-bold mb-3">Address</h5>

        <div class="row g-3 mb-4">
            <div class="col-md-12">
                <label class="form-label fw-semibold">Address Line 1</label>
                <input type="text" name="address_line1" class="form-control">
            </div>

            <div class="col-md-12">
                <label class="form-label fw-semibold">Address Line 2</label>
                <input type="text" name="address_line2" class="form-control">
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">City</label>
                <input type="text" name="city" class="form-control">
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">State</label>
                <input type="text" name="state" class="form-control">
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">Postal Code</label>
                <input type="text" name="postal_code" class="form-control">
            </div>
        </div>




        <!-- ============================
             POLICY INFORMATION
             ============================ -->
        <h5 class="fw-bold mb-3">Policy Information</h5>

        <div class="row g-3 mb-4">

            <div class="col-md-6">
                <label class="form-label fw-semibold">Policy Type</label>
                <input type="text" name="policy_type" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Face Amount</label>
                <input type="number" step="0.01" name="face_amount" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Premium Amount</label>
                <input type="number" step="0.01" name="premium_amount" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Recurring Due Date</label>
                <input type="date" name="recurring_due_date" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Policy Issue Date</label>
                <input type="date" name="policy_issue_date" class="form-control">
            </div>

        </div>




        <!-- ============================
             SAVE / CANCEL
             ============================ -->
        <div class="mt-4 d-flex gap-2">

            <button type="submit" class="btn-gold">
                Save Client
            </button>

            <button 
                type="button"
                class="btn btn-secondary"
                onclick="document.getElementById('client-details-container').innerHTML='<div class=\'empty-right-panel\'></div>'"
            >
                Cancel
            </button>

        </div>

    </form>
</div>

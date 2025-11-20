<div class="card shadow-sm border-0" 
     style="border-radius:18px; height: calc(100vh - 120px); overflow-y:auto;">

    <!-- HEADER -->
    <div class="card-header bg-black text-gold fw-bold d-flex justify-content-between align-items-center"
         style="border-radius:18px 18px 0 0;">

        <span style="font-size:18px;">New Contact</span>
    </div>

    <div class="card-body">

        <form id="createContactForm" method="POST" action="{{ route('contacts.store') }}">
            @csrf

            <!-- BASIC INFO -->
            <h5 class="fw-bold mb-3">Basic Information</h5>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="text-muted">First Name</label>
                    <input type="text" name="first_name" class="form-control" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="text-muted">Last Name</label>
                    <input type="text" name="last_name" class="form-control" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="text-muted">Email</label>
                    <input type="email" name="email" class="form-control">
                </div>

                <div class="col-md-6">
                    <label class="text-muted">Phone</label>
                    <input type="text" name="phone" class="form-control">
                </div>
            </div>

            <!-- CONTACT TYPE + STATUS -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="text-muted">Contact Type</label>
                    <input type="text" name="contact_type" class="form-control" placeholder="Optional">
                </div>

                <div class="col-md-6">
                    <label class="text-muted">Status</label>
                    <input type="text" name="status" class="form-control" placeholder="Optional">
                </div>
            </div>

            <!-- ADDRESS SECTION -->
            <h5 class="fw-bold mt-4 mb-2">Address</h5>

            <div class="mb-3">
                <label class="text-muted">Address Line 1</label>
                <input type="text" name="address_line1" class="form-control">
            </div>

            <div class="mb-3">
                <label class="text-muted">Address Line 2</label>
                <input type="text" name="address_line2" class="form-control">
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="text-muted">City</label>
                    <input type="text" name="city" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="text-muted">State</label>
                    <input type="text" name="state" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="text-muted">Postal Code</label>
                    <input type="text" name="postal_code" class="form-control">
                </div>
            </div>

            <!-- NOTES -->
            <h5 class="fw-bold mt-4 mb-2">Notes</h5>

            <div class="mb-4">
                <textarea name="notes" class="form-control" rows="4" placeholder="Add any notes..."></textarea>
            </div>

            <!-- SAVE BUTTON -->
            <button class="btn fw-bold"
                    style="background:#c9a227; color:#111827; border:none; border-radius:8px; padding:10px 20px;">
                Save Contact
            </button>

        </form>

    </div>
</div>

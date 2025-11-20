<div class="card shadow-sm border-0 h-100">

    <!-- HEADER -->
    <div class="card-header bg-black text-gold fw-bold d-flex justify-content-between align-items-center">
        <span>Add New Lead</span>
        <button class="btn btn-sm btn-outline-light" onclick="window.location.reload()">Cancel</button>
    </div>

    <form method="POST" action="{{ route('contacts.store') }}" class="p-4">
        @csrf

        <!-- FORCE CONTACT TYPE TO LEAD -->
        <input type="hidden" name="contact_type" value="lead">

        <h5 class="mb-3 text-gold fw-bold">Basic Information</h5>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">First Name</label>
                <input type="text" name="first_name" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Last Name</label>
                <input type="text" name="last_name" class="form-control" required>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" name="email" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Phone</label>
                <input type="text" name="phone" class="form-control">
            </div>
        </div>

        <hr>

        <h5 class="mb-3 text-gold fw-bold">Lead Details</h5>

        <div class="mb-3">
            <label class="form-label fw-semibold">Status</label>
            <select name="status" class="form-select">
                <option value="New">New</option>
                <option value="Working">Working</option>
                <option value="Quoted">Quoted</option>
                <option value="Sold">Sold</option>
                <option value="Lost">Lost</option>
            </select>
        </div>

        <div class="mb-4">
            <label class="form-label fw-semibold">Source</label>
            <select name="source" class="form-select">
                <option value="">Select...</option>
                <option value="Referral">Referral</option>
                <option value="Website">Website</option>
                <option value="Facebook">Facebook</option>
                <option value="Instagram">Instagram</option>
                <option value="Purchased List">Purchased List</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <hr>

        <h5 class="mb-3 text-gold fw-bold">Notes</h5>

        <textarea name="notes" class="form-control mb-4" rows="4" placeholder="Enter lead notes..."></textarea>

        <div class="text-end">
            <button class="btn btn-primary btn-lg">Save Lead</button>
        </div>

    </form>

</div>

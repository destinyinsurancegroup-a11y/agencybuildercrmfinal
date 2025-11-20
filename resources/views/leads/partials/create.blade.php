<div class="container py-4">

    <div class="row justify-content-center">
        <div class="col-lg-8">

            <!-- Match Add Contact width exactly -->
            <div class="card shadow-sm border-0" style="max-width: 800px; margin: auto;">

                <!-- HEADER (matches Add Contact) -->
                <div class="card-header bg-black text-gold fw-bold d-flex justify-content-between align-items-center">
                    <span>Add New Lead</span>
                    <a href="{{ route('leads.index') }}" class="btn btn-sm btn-outline-light">Back</a>
                </div>

                <form method="POST" action="{{ route('contacts.store') }}" class="p-4">
                    @csrf

                    <!-- FORCE LEAD TYPE -->
                    <input type="hidden" name="contact_type" value="Lead">

                    <!-- BASIC INFORMATION -->
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

                    <div class="row mb-4">
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

                    <!-- LEAD DETAILS -->
                    <h5 class="mb-3 text-gold fw-bold">Lead Details</h5>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="New">New</option>
                                <option value="Working">Working</option>
                                <option value="Quoted">Quoted</option>
                                <option value="Sold">Sold</option>
                                <option value="Lost">Lost</option>
                            </select>
                        </div>

                        <div class="col-md-6">
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
                    </div>

                    <hr>

                    <!-- ADDRESS -->
                    <h5 class="mb-3 text-gold fw-bold">Address</h5>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Address Line 1</label>
                        <input type="text" name="address_line1" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Address Line 2</label>
                        <input type="text" name="address_line2" class="form-control">
                    </div>

                    <div class="row mb-4">
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

                    <hr>

                    <!-- NOTES -->
                    <h5 class="mb-3 text-gold fw-bold">Notes</h5>

                    <div class="mb-4">
                        <textarea 
                            name="notes" 
                            class="form-control" 
                            rows="4"
                            placeholder="Enter lead notes..."
                        ></textarea>
                    </div>

                    <!-- SUBMIT BUTTON (make gold) -->
                    <div class="text-end">
                        <button class="btn-gold btn-lg">
                            Save Lead
                        </button>
                    </div>

                </form>

            </div>

        </div>
    </div>

</div>

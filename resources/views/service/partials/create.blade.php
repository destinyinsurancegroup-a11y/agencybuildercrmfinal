<div class="p-4" style="margin-top:-28px;">

    <div class="card shadow-sm border-0">

        <form method="POST" action="{{ route('service.store') }}" class="p-4">
            @csrf

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

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Age (auto)</label>
                    <input type="number" class="form-control" disabled placeholder="Auto">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Anniversary</label>
                    <input type="date" name="anniversary" class="form-control">
                </div>
            </div>

            <h6 class="fw-bold mt-3 mb-2 text-gold">Address / Contact</h6>

            <div class="mb-2">
                <label class="form-label fw-semibold">Address Line 1</label>
                <input type="text" name="address_line1" class="form-control">
            </div>

            <div class="mb-2">
                <label class="form-label fw-semibold">Address Line 2</label>
                <input type="text" name="address_line2" class="form-control">
            </div>

            <div class="row mb-3">
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

            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text" name="phone" class="form-control">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
            </div>

            <hr>

            <!-- POLICY INFORMATION -->
            <h5 class="mb-3 text-gold fw-bold">Policy Information</h5>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Carrier</label>
                    <input type="text" name="carrier" class="form-control">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Policy Type</label>
                    <input type="text" name="policy_type" class="form-control">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Face Amount</label>
                    <input type="number" step="0.01" name="face_amount" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Monthly Premium</label>
                    <input type="number" step="0.01" name="premium_amount" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Issue Date</label>
                    <input type="date" name="policy_issue_date" class="form-control">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Monthly Due Date (Text)</label>
                <input type="text" name="premium_due_text" class="form-control">
            </div>

            <hr>

            <!-- BENEFICIARIES -->
            <h5 class="mb-3 text-gold fw-bold">Beneficiaries</h5>

            <div id="beneficiary-wrapper">
                @include('book.partials._blank_beneficiary_row', ['index' => 0])
            </div>

            <button type="button" class="btn btn-sm btn-gold mb-3" id="add-beneficiary-btn">+ Add Beneficiary</button>

            <hr>

            <!-- EMERGENCY CONTACTS -->
            <h5 class="mb-3 text-gold fw-bold">Emergency Contacts</h5>

            <div id="emergency-wrapper">
                @include('book.partials._blank_emergency_row', ['index' => 0])
            </div>

            <button type="button" class="btn btn-sm btn-gold mb-3" id="add-emergency-btn">+ Add Emergency Contact</button>

            <hr>

            <!-- NOTES -->
            <h5 class="mb-3 text-gold fw-bold">Notes</h5>
            <div class="mb-4">
                <textarea name="notes" class="form-control" rows="4"></textarea>
            </div>

            <div class="text-start">
                <button class="btn btn-gold btn-lg">Save Service Client</button>
            </div>

        </form>

    </div>
</div>

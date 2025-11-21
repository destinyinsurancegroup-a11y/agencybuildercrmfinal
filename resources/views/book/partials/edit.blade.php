<div class="p-4">

    <div class="card shadow-sm border-0">

        <form method="POST" action="{{ route('book.update', $client->id) }}" class="p-4">
            @csrf
            @method('PUT')

            <!-- BASIC INFORMATION -->
            <h5 class="mb-3 text-gold fw-bold">Basic Information</h5>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">First Name</label>
                    <input type="text"
                           name="first_name"
                           class="form-control"
                           value="{{ old('first_name', $client->first_name) }}"
                           required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Last Name</label>
                    <input type="text"
                           name="last_name"
                           class="form-control"
                           value="{{ old('last_name', $client->last_name) }}"
                           required>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email"
                           name="email"
                           class="form-control"
                           value="{{ old('email', $client->email) }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text"
                           name="phone"
                           class="form-control"
                           value="{{ old('phone', $client->phone) }}">
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Date of Birth</label>
                    <input type="date"
                           name="date_of_birth"
                           class="form-control"
                           value="{{ old('date_of_birth', $client->date_of_birth) }}">
                </div>
            </div>

            <hr>

            <!-- POLICY INFORMATION -->
            <h5 class="mb-3 text-gold fw-bold">Policy Information</h5>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Policy Type</label>
                    <input type="text"
                           name="policy_type"
                           class="form-control"
                           value="{{ old('policy_type', $client->policy_type) }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Face Amount</label>
                    <input type="number"
                           step="0.01"
                           name="face_amount"
                           class="form-control"
                           value="{{ old('face_amount', $client->face_amount) }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Premium Amount</label>
                    <input type="number"
                           step="0.01"
                           name="premium_amount"
                           class="form-control"
                           value="{{ old('premium_amount', $client->premium_amount) }}">
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Premium Due Date</label>
                    <input type="date"
                           name="premium_due_date"
                           class="form-control"
                           value="{{ old('premium_due_date', $client->premium_due_date) }}">
                </div>
            </div>

            <hr>

            <!-- ADDRESS -->
            <h5 class="mb-3 text-gold fw-bold">Address</h5>

            <div class="mb-3">
                <label class="form-label fw-semibold">Address Line 1</label>
                <input type="text"
                       name="address_line1"
                       class="form-control"
                       value="{{ old('address_line1', $client->address_line1) }}">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Address Line 2</label>
                <input type="text"
                       name="address_line2"
                       class="form-control"
                       value="{{ old('address_line2', $client->address_line2) }}">
            </div>

            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">City</label>
                    <input type="text"
                           name="city"
                           class="form-control"
                           value="{{ old('city', $client->city) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">State</label>
                    <input type="text"
                           name="state"
                           class="form-control"
                           value="{{ old('state', $client->state) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Postal Code</label>
                    <input type="text"
                           name="postal_code"
                           class="form-control"
                           value="{{ old('postal_code', $client->postal_code) }}">
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
                    placeholder="Enter client notes..."
                >{{ old('notes', $client->notes) }}</textarea>
            </div>

            <!-- SUBMIT BUTTON -->
            <div class="text-end">
                <button class="btn-gold btn-lg">
                    Save Client
                </button>
            </div>

        </form>

    </div>

</div>

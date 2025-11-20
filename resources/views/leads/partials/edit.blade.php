<div class="p-4">

    <div class="card shadow-sm border-0">

        {{-- DISPOSITION BUTTONS (PLACEHOLDERS) --}}
        <div class="d-flex justify-content-start gap-2 p-3 pb-0">
            <button type="button" class="btn-gold btn-sm"
                    onclick="alert('Sold action coming soon…');">
                Sold
            </button>

            <button type="button" class="btn-gold btn-sm"
                    onclick="alert('Not Sold action coming soon…');">
                Not Sold
            </button>

            <button type="button" class="btn-gold btn-sm"
                    onclick="alert('Follow Up action coming soon…');">
                Follow Up
            </button>
        </div>

        <form method="POST" action="{{ route('contacts.update', $contact->id) }}" class="p-4">
            @csrf
            @method('PUT')

            <!-- FORCE LEAD TYPE (stay as lead) -->
            <input type="hidden" name="contact_type" value="Lead">

            <!-- BASIC INFORMATION -->
            <h5 class="mb-3 text-gold fw-bold">Basic Information</h5>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">First Name</label>
                    <input type="text"
                           name="first_name"
                           class="form-control"
                           value="{{ old('first_name', $contact->first_name) }}"
                           required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Last Name</label>
                    <input type="text"
                           name="last_name"
                           class="form-control"
                           value="{{ old('last_name', $contact->last_name) }}"
                           required>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email"
                           name="email"
                           class="form-control"
                           value="{{ old('email', $contact->email) }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text"
                           name="phone"
                           class="form-control"
                           value="{{ old('phone', $contact->phone) }}">
                </div>
            </div>

            <!-- EXTRA LEAD INFO (NEW FIELDS) -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Age</label>
                    <input type="number"
                           name="age"
                           class="form-control"
                           min="0"
                           max="110"
                           value="{{ old('age', $contact->age) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Lead Received Date</label>
                    <input type="date"
                           name="lead_received_date"
                           class="form-control"
                           value="{{ old('lead_received_date', $contact->lead_received_date) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Lead Assigned Date</label>
                    <input type="date"
                           name="lead_assigned_date"
                           class="form-control"
                           value="{{ old('lead_assigned_date', $contact->lead_assigned_date) }}">
                </div>
            </div>

            <hr>

            <!-- LEAD DETAILS -->
            <h5 class="mb-3 text-gold fw-bold">Lead Details</h5>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        @php
                            $status = old('status', $contact->status);
                        @endphp
                        <option value="New"     {{ $status === 'New' ? 'selected' : '' }}>New</option>
                        <option value="Working" {{ $status === 'Working' ? 'selected' : '' }}>Working</option>
                        <option value="Quoted"  {{ $status === 'Quoted' ? 'selected' : '' }}>Quoted</option>
                        <option value="Sold"    {{ $status === 'Sold' ? 'selected' : '' }}>Sold</option>
                        <option value="Lost"    {{ $status === 'Lost' ? 'selected' : '' }}>Lost</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Source</label>
                    @php
                        $source = old('source', $contact->source);
                    @endphp
                    <select name="source" class="form-select">
                        <option value="" {{ $source === null || $source === '' ? 'selected' : '' }}>Select...</option>
                        <option value="Referral"       {{ $source === 'Referral' ? 'selected' : '' }}>Referral</option>
                        <option value="Website"        {{ $source === 'Website' ? 'selected' : '' }}>Website</option>
                        <option value="Facebook"       {{ $source === 'Facebook' ? 'selected' : '' }}>Facebook</option>
                        <option value="Instagram"      {{ $source === 'Instagram' ? 'selected' : '' }}>Instagram</option>
                        <option value="Purchased List" {{ $source === 'Purchased List' ? 'selected' : '' }}>Purchased List</option>
                        <option value="Other"          {{ $source === 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
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
                       value="{{ old('address_line1', $contact->address_line1) }}">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Address Line 2</label>
                <input type="text"
                       name="address_line2"
                       class="form-control"
                       value="{{ old('address_line2', $contact->address_line2) }}">
            </div>

            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">City</label>
                    <input type="text"
                           name="city"
                           class="form-control"
                           value="{{ old('city', $contact->city) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">State</label>
                    <input type="text"
                           name="state"
                           class="form-control"
                           value="{{ old('state', $contact->state) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Postal Code</label>
                    <input type="text"
                           name="postal_code"
                           class="form-control"
                           value="{{ old('postal_code', $contact->postal_code) }}">
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
                >{{ old('notes', $contact->notes) }}</textarea>
            </div>

            <!-- SUBMIT BUTTON (gold) -->
            <div class="text-end">
                <button class="btn-gold btn-lg">
                    Save Lead
                </button>
            </div>

        </form>

    </div>

</div>

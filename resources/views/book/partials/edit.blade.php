<div class="p-4">

    <style>
        .p-4 { padding: 1.25rem !important; }
        .card { padding: 1.2rem !important; }

        h5.text-gold { margin: .4rem 0 .6rem !important; }

        /* Standard field style (your default) */
        .field {
            flex: 1 1 22%; /* shrink just enough to fit 4 in a row */
            max-width: 260px !important;
            display: flex;
            flex-direction: column;
        }

        .form-label {
            font-size: .85rem !important;
            margin-bottom: .15rem !important;
        }

        .form-control, .form-select {
            padding: .38rem .55rem !important;
            height: 34px !important;
            font-size: .85rem !important;
            max-width: 260px !important;
        }

        .form-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 20px;
        }

        /* Beneficiary + Emergency compact rows */
        .beneficiary-row,
        .emergency-row {
            border: 1px solid #ddd;
            padding: .5rem !important;
            margin-bottom: .5rem !important;
            border-radius: 6px;
        }

        .beneficiary-grid,
        .emergency-grid {
            display: flex;
            flex-wrap: nowrap;   /* force all 4 fields on one row */
            gap: 10px;
            align-items: center;
        }

        /* Compact HR */
        hr { margin: .75rem 0 !important; }
    </style>

    <div class="card shadow-sm border-0">
        <form method="POST" action="{{ route('book.update', $client->id) }}">
            @csrf
            @method('PUT')

            <!-- BASIC INFORMATION -->
            <h5 class="text-gold fw-bold">Basic Information</h5>

            <div class="form-grid">
                <div class="field">
                    <label class="form-label">First Name</label>
                    <input type="text" name="first_name" class="form-control"
                           value="{{ old('first_name', $client->first_name) }}" required>
                </div>

                <div class="field">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="last_name" class="form-control"
                           value="{{ old('last_name', $client->last_name) }}" required>
                </div>

                <div class="field">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control"
                           value="{{ old('date_of_birth', $client->date_of_birth) }}">
                </div>

                <div class="field">
                    <label class="form-label">Age</label>
                    <input type="number" class="form-control" disabled value="{{ $client->age }}">
                </div>

                <div class="field">
                    <label class="form-label">Anniversary</label>
                    <input type="date" name="anniversary" class="form-control"
                           value="{{ old('anniversary', $client->anniversary) }}">
                </div>
            </div>

            <hr>

            <!-- ADDRESS -->
            <h5 class="text-gold fw-bold">Address / Contact</h5>

            <div class="form-grid">
                <div class="field">
                    <label class="form-label">Address Line 1</label>
                    <input type="text" name="address_line1" class="form-control"
                           value="{{ old('address_line1', $client->address_line1) }}">
                </div>

                <div class="field">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control"
                           value="{{ old('city', $client->city) }}">
                </div>

                <div class="field">
                    <label class="form-label">State</label>
                    <input type="text" name="state" class="form-control"
                           value="{{ old('state', $client->state) }}">
                </div>

                <div class="field">
                    <label class="form-label">Postal Code</label>
                    <input type="text" name="postal_code" class="form-control"
                           value="{{ old('postal_code', $client->postal_code) }}">
                </div>

                <div class="field">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control"
                           value="{{ old('phone', $client->phone) }}">
                </div>

                <div class="field">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="{{ old('email', $client->email) }}">
                </div>
            </div>

            <hr>

            <!-- POLICY INFORMATION -->
            <h5 class="text-gold fw-bold">Policy Information</h5>

            <div class="form-grid">
                <div class="field">
                    <label class="form-label">Carrier</label>
                    <input type="text" name="carrier" class="form-control"
                           value="{{ old('carrier', $client->carrier) }}">
                </div>

                <div class="field">
                    <label class="form-label">Policy Type</label>
                    <input type="text" name="policy_type" class="form-control"
                           value="{{ old('policy_type', $client->policy_type) }}">
                </div>

                <div class="field">
                    <label class="form-label">Face Amount</label>
                    <input type="number" step="0.01" name="face_amount" class="form-control"
                           value="{{ old('face_amount', $client->face_amount) }}">
                </div>

                <div class="field">
                    <label class="form-label">Monthly Premium</label>
                    <input type="number" step="0.01" name="premium_amount" class="form-control"
                           value="{{ old('premium_amount', $client->premium_amount) }}">
                </div>

                <div class="field">
                    <label class="form-label">Issue Date</label>
                    <input type="date" name="policy_issue_date" class="form-control"
                           value="{{ old('policy_issue_date', $client->policy_issue_date) }}">
                </div>

                <div class="field">
                    <label class="form-label">Monthly Due Text</label>
                    <input type="text" name="premium_due_text" class="form-control"
                           value="{{ old('premium_due_text', $client->premium_due_text) }}">
                </div>
            </div>

            <hr>

            <!-- BENEFICIARIES -->
            <h5 class="text-gold fw-bold">Beneficiaries</h5>

            @for ($i = 0; $i < 2; $i++)
                @php $b = $client->beneficiaries[$i] ?? null; @endphp

                <div class="beneficiary-row">
                    <div class="beneficiary-grid">

                        <div class="field">
                            <label class="form-label">Name</label>
                            <input type="text" name="beneficiaries[{{ $i }}][name]"
                                   class="form-control" value="{{ $b->name ?? '' }}">
                        </div>

                        <div class="field">
                            <label class="form-label">Relationship</label>
                            <input type="text" name="beneficiaries[{{ $i }}][relationship]"
                                   class="form-control" value="{{ $b->relationship ?? '' }}">
                        </div>

                        <div class="field">
                            <label class="form-label">Phone</label>
                            <input type="text" name="beneficiaries[{{ $i }}][phone]"
                                   class="form-control" value="{{ $b->phone ?? '' }}">
                        </div>

                        <div class="field">
                            <label class="form-label">Contacted?</label>
                            <select name="beneficiaries[{{ $i }}][contacted]" class="form-select">
                                <option value="0" {{ (!$b || !$b->contacted) ? 'selected' : '' }}>No</option>
                                <option value="1" {{ ($b && $b->contacted) ? 'selected' : '' }}>Yes</option>
                            </select>
                        </div>

                    </div>
                </div>
            @endfor

            <hr>

            <!-- EMERGENCY CONTACTS -->
            <h5 class="text-gold fw-bold">Emergency Contacts</h5>

            @for ($i = 0; $i < 2; $i++)
                @php $e = $client->emergencyContacts[$i] ?? null; @endphp

                <div class="emergency-row">
                    <div class="emergency-grid">

                        <div class="field">
                            <label class="form-label">Name</label>
                            <input type="text" name="emergency_contacts[{{ $i }}][name]"
                                   class="form-control" value="{{ $e->name ?? '' }}">
                        </div>

                        <div class="field">
                            <label class="form-label">Relationship</label>
                            <input type="text" name="emergency_contacts[{{ $i }}][relationship]"
                                   class="form-control" value="{{ $e->relationship ?? '' }}">
                        </div>

                        <div class="field">
                            <label class="form-label">Phone</label>
                            <input type="text" name="emergency_contacts[{{ $i }}][phone]"
                                   class="form-control" value="{{ $e->phone ?? '' }}">
                        </div>

                        <div class="field">
                            <label class="form-label">Contacted?</label>
                            <select name="emergency_contacts[{{ $i }}][contacted]" class="form-select">
                                <option value="0" {{ (!$e || !$e->contacted) ? 'selected' : '' }}>No</option>
                                <option value="1" {{ ($e && $e->contacted) ? 'selected' : '' }}>Yes</option>
                            </select>
                        </div>

                    </div>
                </div>
            @endfor

            <hr>

            <div class="text-end">
                <button class="btn-gold btn-lg">Save Client</button>
            </div>

        </form>
    </div>
</div>

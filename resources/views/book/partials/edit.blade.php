<div class="p-4">

    <!-- Ultra-Compact CSS -->
    <style>
        /* Overall compact wrappers */
        .p-4 { padding: 1rem !important; }
        .card { padding: 1rem !important; }

        /* Tight section headings */
        h5.text-gold, h6.text-gold {
            margin-top: .4rem !important;
            margin-bottom: .4rem !important;
        }

        /* Reduce spacing between rows */
        .row {
            margin-bottom: .55rem !important;
        }

        /* Labels more compact */
        .form-label {
            margin-bottom: .1rem !important;
            font-size: .85rem !important;
        }

        /* Ultra-compact inputs */
        .form-control, .form-select {
            padding: .35rem .5rem !important;
            font-size: .85rem !important;
            height: 32px !important;
        }

        /* 3-column rows tighter */
        .col-md-4, .col-md-3, .col-md-2, .col-md-6 {
            padding-right: .35rem !important;
            padding-left: .35rem !important;
        }

        /* HR spacing small */
        hr { margin: .7rem 0 !important; }

        /* Compact beneficiary & emergency blocks */
        .beneficiary-row, .emergency-row {
            padding: .6rem !important;
            margin-bottom: .6rem !important;
        }

        /* Remove outer large spacing */
        .mb-4, .mb-3, .mb-2 { margin-bottom: .5rem !important; }

        /* Buttons compact */
        .btn-gold, .btn.btn-sm.btn-gold {
            padding: 4px 10px !important;
            font-size: .8rem !important;
        }

        .btn-gold.btn-lg {
            padding: 6px 14px !important;
            font-size: .85rem !important;
        }
    </style>

    <div class="card shadow-sm border-0">

        <form method="POST" action="{{ route('book.update', $client->id) }}" class="p-4">
            @csrf
            @method('PUT')

            <!-- BASIC INFORMATION -->
            <h5 class="text-gold fw-bold">Basic Information</h5>

            <div class="row">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">First Name</label>
                    <input type="text" name="first_name" class="form-control"
                           value="{{ old('first_name', $client->first_name) }}" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Last Name</label>
                    <input type="text" name="last_name" class="form-control"
                           value="{{ old('last_name', $client->last_name) }}" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control"
                           value="{{ old('date_of_birth', $client->date_of_birth) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Age</label>
                    <input type="number" class="form-control" disabled value="{{ $client->age }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Anniversary</label>
                    <input type="date" name="anniversary" class="form-control"
                           value="{{ old('anniversary', $client->anniversary) }}">
                </div>
            </div>

            <!-- ADDRESS -->
            <h6 class="fw-bold text-gold">Address / Contact</h6>

            <div class="mb-2">
                <label class="form-label fw-semibold">Address Line 1</label>
                <input type="text" name="address_line1" class="form-control"
                       value="{{ old('address_line1', $client->address_line1) }}">
            </div>

            <div class="mb-2">
                <label class="form-label fw-semibold">Address Line 2</label>
                <input type="text" name="address_line2" class="form-control"
                       value="{{ old('address_line2', $client->address_line2) }}">
            </div>

            <div class="row">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">City</label>
                    <input type="text" name="city" class="form-control"
                           value="{{ old('city', $client->city) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">State</label>
                    <input type="text" name="state" class="form-control"
                           value="{{ old('state', $client->state) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Postal Code</label>
                    <input type="text" name="postal_code" class="form-control"
                           value="{{ old('postal_code', $client->postal_code) }}">
                </div>
            </div>

            <div class="row mb-1">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text" name="phone" class="form-control"
                           value="{{ old('phone', $client->phone) }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="{{ old('email', $client->email) }}">
                </div>
            </div>

            <hr>

            <!-- POLICY INFORMATION -->
            <h5 class="text-gold fw-bold">Policy Information</h5>

            <div class="row">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Carrier</label>
                    <input type="text" name="carrier" class="form-control"
                           value="{{ old('carrier', $client->carrier) }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Policy Type</label>
                    <input type="text" name="policy_type" class="form-control"
                           value="{{ old('policy_type', $client->policy_type) }}">
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Face Amount</label>
                    <input type="number" step="0.01" name="face_amount" class="form-control"
                           value="{{ old('face_amount', $client->face_amount) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Monthly Premium</label>
                    <input type="number" step="0.01" name="premium_amount" class="form-control"
                           value="{{ old('premium_amount', $client->premium_amount) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Issue Date</label>
                    <input type="date" name="policy_issue_date" class="form-control"
                           value="{{ old('policy_issue_date', $client->policy_issue_date) }}">
                </div>
            </div>

            <div class="mb-2">
                <label class="form-label fw-semibold">Monthly Due Date (text)</label>
                <input type="text" name="premium_due_text" class="form-control"
                       value="{{ old('premium_due_text', $client->premium_due_text) }}">
            </div>

            <hr>

            <!-- BENEFICIARIES (2 compact rows) -->
            <h5 class="text-gold fw-bold">Beneficiaries</h5>

            @for ($i = 0; $i < 2; $i++)
                @php $b = $client->beneficiaries[$i] ?? null; @endphp

                <div class="beneficiary-row border rounded">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Name</label>
                            <input type="text" name="beneficiaries[{{ $i }}][name]"
                                   class="form-control" value="{{ $b->name ?? '' }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Relationship</label>
                            <input type="text" name="beneficiaries[{{ $i }}][relationship]"
                                   class="form-control" value="{{ $b->relationship ?? '' }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="text" name="beneficiaries[{{ $i }}][phone]"
                                   class="form-control" value="{{ $b->phone ?? '' }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Contacted?</label>
                            <select name="beneficiaries[{{ $i }}][contacted]" class="form-select">
                                <option value="0" {{ !$b || !$b->contacted ? 'selected' : '' }}>No</option>
                                <option value="1" {{ $b && $b->contacted ? 'selected' : '' }}>Yes</option>
                            </select>
                        </div>
                    </div>
                </div>
            @endfor

            <hr>

            <!-- EMERGENCY CONTACTS (2 compact rows) -->
            <h5 class="text-gold fw-bold">Emergency Contacts</h5>

            @for ($i = 0; $i < 2; $i++)
                @php $e = $client->emergencyContacts[$i] ?? null; @endphp

                <div class="emergency-row border rounded">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Name</label>
                            <input type="text" name="emergency_contacts[{{ $i }}][name]"
                                   class="form-control" value="{{ $e->name ?? '' }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Relationship</label>
                            <input type="text" name="emergency_contacts[{{ $i }}][relationship]"
                                   class="form-control" value="{{ $e->relationship ?? '' }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="text" name="emergency_contacts[{{ $i }}][phone]"
                                   class="form-control" value="{{ $e->phone ?? '' }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Contacted?</label>
                            <select name="emergency_contacts[{{ $i }}][contacted]" class="form-select">
                                <option value="0" {{ !$e || !$e->contacted ? 'selected' : '' }}>No</option>
                                <option value="1" {{ $e && $e->contacted ? 'selected' : '' }}>Yes</option>
                            </select>
                        </div>
                    </div>
                </div>
            @endfor

            <hr>

            <!-- Save -->
            <div class="text-end">
                <button class="btn-gold btn-lg">Save Client</button>
            </div>

        </form>

    </div>

</div>

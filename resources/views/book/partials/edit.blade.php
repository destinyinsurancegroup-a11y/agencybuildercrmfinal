<div class="p-4">

    <!-- COMPACT CRM GRID SYSTEM -->
    <style>
        /* Overall page padding */
        .p-4 { padding: 1.25rem !important; }

        .card { padding: 1.2rem !important; }

        /* Section headers */
        h5.text-gold, h6.text-gold {
            margin: .4rem 0 .6rem 0 !important;
        }

        /* Custom compact grid */
        .form-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 18px;
        }

        .field {
            flex: 0 0 260px;
            display: flex;
            flex-direction: column;
        }

        /* Labels */
        .form-label {
            margin-bottom: .15rem !important;
            font-size: .85rem !important;
        }

        /* Input compact style */
        .form-control,
        .form-select {
            width: 100% !important;
            max-width: 260px !important;
            padding: .38rem .55rem !important;
            font-size: .85rem !important;
            height: 34px !important;
        }

        /* CONTACT SECTION OVERRIDES (Option A) */
        .contact-row {
            display: flex;
            width: 100%;
            align-items: center;
            gap: 14px;
            padding: .55rem .75rem !important;
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-bottom: .55rem !important;
        }

        .contact-col {
            display: flex;
            flex-direction: column;
        }

        /* Four tight columns */
        .contact-name   { flex: 0 0 230px; }
        .contact-rel    { flex: 0 0 180px; }
        .contact-phone  { flex: 0 0 180px; }
        .contact-cont   { flex: 0 0 80px; } /* 90% smaller */

        .contact-col .form-control,
        .contact-col .form-select {
            max-width: 100% !important;
            height: 32px !important;
            padding: .32rem .45rem !important;
            font-size: .8rem !important;
        }

        /* Compact HR */
        hr { margin: .75rem 0 !important; }

        /* Buttons */
        .btn-gold, .btn-gold.btn-lg {
            padding: 6px 12px !important;
            font-size: .85rem !important;
        }
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

            <!-- ============================== -->
            <!--   COMBINED CONTACTS SECTION   -->
            <!-- ============================== -->
            <h5 class="text-gold fw-bold">Contacts</h5>

            @php
                $beneficiaries = $client->beneficiaries;
                $emergencies = $client->emergencyContacts;
            @endphp

            <!-- BENEFICIARIES (Two) -->
            @for ($i = 0; $i < 2; $i++)
                @php $b = $beneficiaries[$i] ?? null; @endphp

                <div class="contact-row">

                    <div class="contact-col contact-name">
                        <label class="form-label">Name</label>
                        <input type="text" name="beneficiaries[{{ $i }}][name]"
                               class="form-control" value="{{ $b->name ?? '' }}">
                    </div>

                    <div class="contact-col contact-rel">
                        <label class="form-label">Relationship</label>
                        <input type="text" name="beneficiaries[{{ $i }}][relationship]"
                               class="form-control" value="{{ $b->relationship ?? '' }}">
                    </div>

                    <div class="contact-col contact-phone">
                        <label class="form-label">Phone</label>
                        <input type="text" name="beneficiaries[{{ $i }}][phone]"
                               class="form-control" value="{{ $b->phone ?? '' }}">
                    </div>

                    <div class="contact-col contact-cont">
                        <label class="form-label">Contact?</label>
                        <select name="beneficiaries[{{ $i }}][contacted]" class="form-select">
                            <option value="0" {{ !$b || !$b->contacted ? 'selected' : '' }}>No</option>
                            <option value="1" {{ $b && $b->contacted ? 'selected' : '' }}>Yes</option>
                        </select>
                    </div>

                </div>
            @endfor

            <!-- EMERGENCY CONTACTS (Two) -->
            @for ($i = 0; $i < 2; $i++)
                @php $e = $emergencies[$i] ?? null; @endphp

                <div class="contact-row">

                    <div class="contact-col contact-name">
                        <label class="form-label">Name</label>
                        <input type="text" name="emergency_contacts[{{ $i }}][name]"
                               class="form-control" value="{{ $e->name ?? '' }}">
                    </div>

                    <div class="contact-col contact-rel">
                        <label class="form-label">Relationship</label>
                        <input type="text" name="emergency_contacts[{{ $i }}][relationship]"
                               class="form-control" value="{{ $e->relationship ?? '' }}">
                    </div>

                    <div class="contact-col contact-phone">
                        <label class="form-label">Phone</label>
                        <input type="text" name="emergency_contacts[{{ $i }}][phone]"
                               class="form-control" value="{{ $e->phone ?? '' }}">
                    </div>

                    <div class="contact-col contact-cont">
                        <label class="form-label">Contact?</label>
                        <select name="emergency_contacts[{{ $i }}][contacted]" class="form-select">
                            <option value="0" {{ !$e || !$e->contacted ? 'selected' : '' }}>No</option>
                            <option value="1" {{ $e && $e->contacted ? 'selected' : '' }}>Yes</option>
                        </select>
                    </div>

                </div>
            @endfor

            <hr>

            <!-- Save button -->
            <div class="text-end">
                <button class="btn-gold btn-lg">Save Client</button>
            </div>

        </form>

    </div>

</div>

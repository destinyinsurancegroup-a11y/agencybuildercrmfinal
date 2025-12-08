<div class="p-4">

    <!-- COMPACT CRM GRID SYSTEM -->
    <style>
        .p-4 { padding: 1.25rem !important; }
        .card { padding: 1.2rem !important; }

        h5.text-gold, h6.text-gold {
            margin: .4rem 0 .6rem 0 !important;
        }

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

        .form-label {
            margin-bottom: .15rem !important;
            font-size: .85rem !important;
        }

        .form-control,
        .form-select {
            width: 100% !important;
            max-width: 260px !important;
            padding: .38rem .55rem !important;
            font-size: .85rem !important;
            height: 34px !important;
        }

        .beneficiary-row,
        .emergency-row {
            border: 1px solid #ddd;
            padding: .45rem .5rem !important;
            margin-bottom: .45rem !important;
            border-radius: 6px;
        }

        .beneficiary-grid,
        .emergency-grid {
            display: flex;
            flex-wrap: nowrap;
            gap: 10px;
            align-items: center;
        }

        .beneficiary-grid .field,
        .emergency-grid .field {
            flex: 0 0 180px !important;
            max-width: 180px !important;
        }

        .beneficiary-grid .field:last-child,
        .emergency-grid .field:last-child {
            flex: 0 0 80px !important;
            max-width: 80px !important;
        }

        .beneficiary-grid .form-control,
        .emergency-grid .form-control,
        .beneficiary-grid .form-select,
        .emergency-grid .form-select {
            max-width: 100% !important;
            height: 32px !important;
            padding: .32rem .45rem !important;
            font-size: .8rem !important;
        }

        .contact-subtitle {
            font-size: .9rem;
            font-weight: 600;
            margin: .2rem 0 .3rem 0;
        }

        hr { margin: .75rem 0 !important; }

        .btn-gold, .btn-gold.btn-lg {
            padding: 6px 12px !important;
            font-size: .85rem !important;
        }

        .contact-delete-btn {
            border: none;
            background: transparent;
            color: #D4AF37;
            font-weight: 700;
            font-size: 1rem;
            line-height: 1;
            padding: 0 .15rem;
            cursor: pointer;
        }

        .contact-delete-btn:hover {
            color: #f3d266;
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
                    <label class="form-label">Initial Draft Date</label>
                    <input type="date" name="policy_issue_date" class="form-control"
                           value="{{ old('policy_issue_date', $client->policy_issue_date) }}">
                </div>

                <div class="field">
                    <label class="form-label">Monthly Due (Text)</label>
                    <input type="text" name="premium_due_text" class="form-control"
                           value="{{ old('premium_due_text', $client->premium_due_text) }}">
                </div>

            </div>

            <hr>

            <!-- CONTACTS SECTION -->
            <h5 class="text-gold fw-bold">Contacts</h5>

            {{-- ================================== --}}
            {{-- BENEFICIARIES (dynamic like create) --}}
            {{-- ================================== --}}
            <div class="contact-subtitle">Beneficiaries</div>

            @php
                $beneficiaries = $client->beneficiaries ?? collect();
                if ($beneficiaries->isEmpty()) {
                    $beneficiaries = collect([null]); // ensure one empty row
                }
            @endphp

            <div id="beneficiaries-wrapper">
                @foreach($beneficiaries as $index => $b)
                    <div class="beneficiary-row">
                        <div class="beneficiary-grid">

                            @if($b)
                                <input type="hidden"
                                       name="beneficiaries[{{ $index }}][id]"
                                       value="{{ $b->id }}">
                            @endif

                            <div class="field">
                                <label class="form-label">Name</label>
                                <input type="text"
                                       name="beneficiaries[{{ $index }}][name]"
                                       class="form-control"
                                       value="{{ $b->name ?? '' }}">
                            </div>

                            <div class="field">
                                <label class="form-label">Relationship</label>
                                <input type="text"
                                       name="beneficiaries[{{ $index }}][relationship]"
                                       class="form-control"
                                       value="{{ $b->relationship ?? '' }}">
                            </div>

                            <div class="field">
                                <label class="form-label">Phone</label>
                                <input type="text"
                                       name="beneficiaries[{{ $index }}][phone]"
                                       class="form-control"
                                       value="{{ $b->phone ?? '' }}">
                            </div>

                            <div class="field">
                                <label class="form-label">Contacted?</label>
                                <select name="beneficiaries[{{ $index }}][contacted]" class="form-select">
                                    <option value="0" {{ !$b || !$b->contacted ? 'selected' : '' }}>No</option>
                                    <option value="1" {{ $b && $b->contacted ? 'selected' : '' }}>Yes</option>
                                </select>
                            </div>

                            <button
                                type="button"
                                class="contact-delete-btn"
                                data-url="{{ $b ? url('book/' . $client->id . '/beneficiaries/' . $b->id) : '' }}"
                                onclick="deleteContactRow(this, 'beneficiary')"
                                title="Remove beneficiary"
                            >×</button>

                        </div>
                    </div>
                @endforeach
            </div>

            <button type="button"
                    class="btn btn-sm btn-outline-secondary mb-3"
                    onclick="addBeneficiaryRow()">
                + Add Beneficiary
            </button>

            <hr>

            {{-- ======================================= --}}
            {{-- EMERGENCY CONTACTS (dynamic like create) --}}
            {{-- ======================================= --}}
            <div class="contact-subtitle mt-2">Emergency Contacts</div>

            @php
                $emergencyContacts = $client->emergencyContacts ?? collect();
                if ($emergencyContacts->isEmpty()) {
                    $emergencyContacts = collect([null]);
                }
            @endphp

            <div id="emergency-wrapper">
                @foreach($emergencyContacts as $index => $e)
                    <div class="emergency-row">
                        <div class="emergency-grid">

                            @if($e)
                                <input type="hidden"
                                       name="emergency_contacts[{{ $index }}][id]"
                                       value="{{ $e->id }}">
                            @endif

                            <div class="field">
                                <label class="form-label">Name</label>
                                <input type="text"
                                       name="emergency_contacts[{{ $index }}][name]"
                                       class="form-control"
                                       value="{{ $e->name ?? '' }}">
                            </div>

                            <div class="field">
                                <label class="form-label">Relationship</label>
                                <input type="text"
                                       name="emergency_contacts[{{ $index }}][relationship]"
                                       class="form-control"
                                       value="{{ $e->relationship ?? '' }}">
                            </div>

                            <div class="field">
                                <label class="form-label">Phone</label>
                                <input type="text"
                                       name="emergency_contacts[{{ $index }}][phone]"
                                       class="form-control"
                                       value="{{ $e->phone ?? '' }}">
                            </div>

                            <div class="field">
                                <label class="form-label">Contacted?</label>
                                <select name="emergency_contacts[{{ $index }}][contacted]" class="form-select">
                                    <option value="0" {{ !$e || !$e->contacted ? 'selected' : '' }}>No</option>
                                    <option value="1" {{ $e && $e->contacted ? 'selected' : '' }}>Yes</option>
                                </select>
                            </div>

                            <button
                                type="button"
                                class="contact-delete-btn"
                                data-url="{{ $e ? url('book/' . $client->id . '/emergencies/' . $e->id) : '' }}"
                                onclick="deleteContactRow(this, 'emergency')"
                                title="Remove emergency contact"
                            >×</button>

                        </div>
                    </div>
                @endforeach
            </div>

            <button type="button"
                    class="btn btn-sm btn-outline-secondary mb-3"
                    onclick="addEmergencyContactRow()">
                + Add Emergency Contact
            </button>

            <hr>

            <div class="text-end">
                <button class="btn-gold btn-lg">Save Client</button>
            </div>

        </form>

    </div>

</div>

<script>
    const AB_CSRF_TOKEN = '{{ csrf_token() }}';

    // -------- Beneficiaries: add new row (like create form) --------
    function addBeneficiaryRow() {
        var wrapper = document.getElementById('beneficiaries-wrapper');
        if (!wrapper) return;

        var rows = wrapper.querySelectorAll('.beneficiary-row');
        if (!rows.length) return;

        var prototype = rows[rows.length - 1];
        var index = rows.length;

        var clone = prototype.cloneNode(true);

        clone.querySelectorAll('input, select').forEach(function (input) {
            if (!input.name) return;

            // remove hidden ID for new rows
            if (input.name.indexOf('[id]') !== -1) {
                input.parentNode.removeChild(input);
                return;
            }

            input.name = input.name.replace(/\[\d+]/, '[' + index + ']');

            if (input.tagName === 'SELECT') {
                input.value = '0';
            } else {
                input.value = '';
            }
        });

        // new rows have no delete URL (pure client-side remove)
        var delBtn = clone.querySelector('.contact-delete-btn');
        if (delBtn) {
            delBtn.dataset.url = '';
        }

        wrapper.appendChild(clone);
    }

    // -------- Emergency Contacts: add new row --------
    function addEmergencyContactRow() {
        var wrapper = document.getElementById('emergency-wrapper');
        if (!wrapper) return;

        var rows = wrapper.querySelectorAll('.emergency-row');
        if (!rows.length) return;

        var prototype = rows[rows.length - 1];
        var index = rows.length;

        var clone = prototype.cloneNode(true);

        clone.querySelectorAll('input, select').forEach(function (input) {
            if (!input.name) return;

            if (input.name.indexOf('[id]') !== -1) {
                input.parentNode.removeChild(input);
                return;
            }

            input.name = input.name.replace(/\[\d+]/, '[' + index + ']');

            if (input.tagName === 'SELECT') {
                input.value = '0';
            } else {
                input.value = '';
            }
        });

        var delBtn = clone.querySelector('.contact-delete-btn');
        if (delBtn) {
            delBtn.dataset.url = '';
        }

        wrapper.appendChild(clone);
    }

    // -------- Delete row (existing via AJAX, new rows client-side) --------
    function deleteContactRow(button, type) {
        var rowClass  = type === 'beneficiary' ? 'beneficiary-row' : 'emergency-row';
        var wrapperId = type === 'beneficiary' ? 'beneficiaries-wrapper' : 'emergency-wrapper';

        var url     = button.dataset.url || '';
        var rowEl   = button.closest('.' + rowClass);
        var wrapper = document.getElementById(wrapperId);
        if (!rowEl || !wrapper) return;

        var label = (type === 'beneficiary') ? 'beneficiary' : 'emergency contact';

        // Brand-new row (no URL): just remove it (but keep at least one row)
        if (!url) {
            var rows = wrapper.querySelectorAll('.' + rowClass);
            if (rows.length <= 1) {
                // keep one row: just clear inputs
                rowEl.querySelectorAll('input, select').forEach(function (input) {
                    if (!input.name) return;
                    if (input.name.indexOf('[id]') !== -1) return;

                    if (input.tagName === 'SELECT') {
                        input.value = '0';
                    } else {
                        input.value = '';
                    }
                });
                return;
            }

            rowEl.remove();
            return;
        }

        if (!confirm('Are you sure you want to remove this ' + label + '?')) {
            return;
        }

        fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': AB_CSRF_TOKEN,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(function (resp) {
                if (!resp.ok) throw new Error('Delete failed');
                return resp.json();
            })
            .then(function () {
                rowEl.style.opacity = '0.3';
                setTimeout(function () { rowEl.remove(); }, 150);
            })
            .catch(function () {
                alert('Unable to delete. Please try again.');
            });
    }
</script>

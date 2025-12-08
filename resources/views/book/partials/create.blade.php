<div class="p-4">
    <div class="card shadow-sm border-0 p-4">

        <h2 class="fw-bold mb-3" style="font-size: 24px;">Add New Client</h2>

        <form method="POST" action="{{ route('book.store') }}">
            @csrf

            {{-- ========================
                 BASIC INFORMATION
               ======================== --}}
            <h4 class="text-gold fw-bold mb-3">Basic Information</h4>

            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">First Name</label>
                    <input type="text" name="first_name" class="form-control" required
                           value="{{ old('first_name') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Last Name</label>
                    <input type="text" name="last_name" class="form-control" required
                           value="{{ old('last_name') }}">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="{{ old('email') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text" name="phone" class="form-control"
                           value="{{ old('phone') }}">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control"
                           value="{{ old('date_of_birth') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Anniversary</label>
                    <input type="date" name="anniversary" class="form-control"
                           value="{{ old('anniversary') }}">
                </div>
            </div>

            {{-- ADDRESS --}}
            <h5 class="fw-bold mb-2">Address</h5>
            <div class="mb-3">
                <label class="form-label fw-semibold">Address Line 1</label>
                <input type="text" name="address_line1" class="form-control"
                       value="{{ old('address_line1') }}">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Address Line 2</label>
                <input type="text" name="address_line2" class="form-control"
                       value="{{ old('address_line2') }}">
            </div>
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">City</label>
                    <input type="text" name="city" class="form-control"
                           value="{{ old('city') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">State</label>
                    <input type="text" name="state" class="form-control"
                           value="{{ old('state') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Postal Code</label>
                    <input type="text" name="postal_code" class="form-control"
                           value="{{ old('postal_code') }}">
                </div>
            </div>

            <hr>

            {{-- ========================
                 POLICY INFORMATION
               ======================== --}}
            <h4 class="text-gold fw-bold mb-3">Policy Information</h4>

            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Carrier</label>
                    <input type="text" name="carrier" class="form-control"
                           value="{{ old('carrier') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Policy Type</label>
                    <input type="text" name="policy_type" class="form-control"
                           value="{{ old('policy_type') }}">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Face Amount</label>
                    <input type="number" step="0.01" name="face_amount" class="form-control"
                           value="{{ old('face_amount') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Monthly Premium</label>
                    <input type="number" step="0.01" name="premium_amount" class="form-control"
                           value="{{ old('premium_amount') }}">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Initial Draft Date</label>
                    <input type="date" name="policy_issue_date" class="form-control"
                           value="{{ old('policy_issue_date') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Monthly Due (Text)</label>
                    <input type="text" name="premium_due_text" class="form-control"
                           value="{{ old('premium_due_text') }}">
                </div>
            </div>

            <hr>

            {{-- ========================
                 BENEFICIARIES
               ======================== --}}
            <h4 class="text-gold fw-bold mb-3">Beneficiaries</h4>

            <div id="beneficiaries-wrapper">
                {{-- initial blank row --}}
                <div class="row g-2 mb-2 relation-row">
                    <div class="col-md-3">
                        <label class="form-label small">Name</label>
                        <input type="text" name="beneficiaries[0][name]" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Relationship</label>
                        <input type="text" name="beneficiaries[0][relationship]" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Phone</label>
                        <input type="text" name="beneficiaries[0][phone]" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Contacted?</label>
                        <select name="beneficiaries[0][contacted]" class="form-select">
                            <option value="0" selected>No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-outline-danger"
                                onclick="removeRelationRow(this)">
                            &times;
                        </button>
                    </div>
                </div>
            </div>

            <button type="button" id="add-beneficiary-btn" class="btn btn-sm btn-outline-secondary mb-3">
                + Add Beneficiary
            </button>

            <hr>

            {{-- ========================
                 EMERGENCY CONTACTS
               ======================== --}}
            <h4 class="text-gold fw-bold mb-3">Emergency Contacts</h4>

            <div id="emergency-wrapper">
                {{-- initial blank row --}}
                <div class="row g-2 mb-2 relation-row">
                    <div class="col-md-3">
                        <label class="form-label small">Name</label>
                        <input type="text" name="emergency_contacts[0][name]" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Relationship</label>
                        <input type="text" name="emergency_contacts[0][relationship]" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Phone</label>
                        <input type="text" name="emergency_contacts[0][phone]" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Contacted?</label>
                        <select name="emergency_contacts[0][contacted]" class="form-select">
                            <option value="0" selected>No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-outline-danger"
                                onclick="removeRelationRow(this)">
                            &times;
                        </button>
                    </div>
                </div>
            </div>

            <button type="button" id="add-emergency-btn" class="btn btn-sm btn-outline-secondary mb-3">
                + Add Emergency Contact
            </button>

            <hr>

            {{-- ========================
                 NOTES
               ======================== --}}
            <h4 class="text-gold fw-bold mb-3">Notes</h4>
            <div class="mb-4">
                <textarea name="notes" class="form-control" rows="3"
                          placeholder="Enter notes about this client...">{{ old('notes') }}</textarea>
            </div>

            {{-- SAVE BUTTON --}}
            <button type="submit" class="btn-gold">
                Save Client
            </button>
        </form>
    </div>
</div>

{{-- ==============================
     JS: dynamic rows for relations
   =============================== --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        let beneficiaryIndex = 1;
        let emergencyIndex   = 1;

        const addBeneficiaryBtn = document.getElementById('add-beneficiary-btn');
        const addEmergencyBtn   = document.getElementById('add-emergency-btn');

        addBeneficiaryBtn.addEventListener('click', function () {
            addRelationRow('beneficiaries-wrapper', 'beneficiaries', beneficiaryIndex++);
        });

        addEmergencyBtn.addEventListener('click', function () {
            addRelationRow('emergency-wrapper', 'emergency_contacts', emergencyIndex++);
        });
    });

    function addRelationRow(wrapperId, prefix, index) {
        const wrapper = document.getElementById(wrapperId);

        const row = document.createElement('div');
        row.className = 'row g-2 mb-2 relation-row';
        row.innerHTML = `
            <div class="col-md-3">
                <label class="form-label small">Name</label>
                <input type="text" name="${prefix}[${index}][name]" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Relationship</label>
                <input type="text" name="${prefix}[${index}][relationship]" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Phone</label>
                <input type="text" name="${prefix}[${index}][phone]" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Contacted?</label>
                <select name="${prefix}[${index}][contacted]" class="form-select">
                    <option value="0" selected>No</option>
                    <option value="1">Yes</option>
                </select>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-sm btn-outline-danger"
                        onclick="removeRelationRow(this)">
                    &times;
                </button>
            </div>
        `;

        wrapper.appendChild(row);
    }

    function removeRelationRow(button) {
        const row = button.closest('.relation-row');
        if (row) {
            row.remove();
        }
    }
</script>

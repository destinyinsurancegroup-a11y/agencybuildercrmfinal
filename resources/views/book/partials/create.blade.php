<div class="p-4" style="margin-top:-28px;">

    <style>
        .p-4 { padding: 1.25rem !important; }
        .card { padding: 1.2rem !important; }

        h5.text-gold, h6.text-gold {
            margin: .4rem 0 .6rem 0 !important;
        }

        .contact-row {
            border: 1px solid #ddd;
            padding: .6rem .7rem;
            margin-bottom: .6rem;
            border-radius: 6px;
        }

        .contact-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 18px;
            align-items: center;
        }

        .field {
            flex: 0 0 240px;
            display: flex;
            flex-direction: column;
        }

        .form-label {
            margin-bottom: .2rem;
            font-size: .83rem;
        }

        .form-control,
        .form-select {
            width: 100%;
            padding: .38rem .55rem;
            font-size: .83rem;
            height: 34px;
        }

        .btn-remove {
            color: #D4AF37;
            font-weight: bold;
            font-size: 20px;
            background: none;
            border: none;
            margin-left: 8px;
            cursor: pointer;
        }
        .btn-remove:hover {
            color: #f3d266;
        }

        hr { margin: .75rem 0 !important; }
    </style>

    <div class="card shadow-sm border-0">

        <form method="POST" action="{{ route('book.store') }}" class="p-4">
            @csrf

            <!-- BASIC INFORMATION -->
            <h5 class="text-gold fw-bold mb-2">Basic Information</h5>

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
                    <input type="number" class="form-control" disabled>
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

            <!-- POLICY -->
            <h5 class="text-gold fw-bold mb-2">Policy Information</h5>

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
                <label class="form-label fw-semibold">Monthly Due (Text)</label>
                <input 
                    type="text"
                    name="premium_due_text"
                    class="form-control"
                    placeholder="2nd Wednesday, 15th of every month"
                >
            </div>

            <hr>

            <!-- BENEFICIARIES -->
            <h5 class="text-gold fw-bold">Beneficiaries</h5>

            <div id="beneficiary-wrapper">
                <!-- First row -->
                <div class="contact-row">
                    <div class="contact-grid">

                        <div class="field">
                            <label class="form-label">Name</label>
                            <input type="text" name="beneficiaries[0][name]" class="form-control">
                        </div>

                        <div class="field">
                            <label class="form-label">Relationship</label>
                            <input type="text" name="beneficiaries[0][relationship]" class="form-control">
                        </div>

                        <div class="field">
                            <label class="form-label">Phone</label>
                            <input type="text" name="beneficiaries[0][phone]" class="form-control">
                        </div>

                        <div class="field">
                            <label class="form-label">Contacted?</label>
                            <select name="beneficiaries[0][contacted]" class="form-select">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>

                    </div>
                </div>
            </div>

            <button type="button" id="add-beneficiary-btn" class="btn btn-sm btn-gold mb-3">
                + Add Beneficiary
            </button>

            <hr>

            <!-- EMERGENCY CONTACTS -->
            <h5 class="text-gold fw-bold">Emergency Contacts</h5>

            <div id="emergency-wrapper">
                <div class="contact-row">
                    <div class="contact-grid">

                        <div class="field">
                            <label class="form-label">Name</label>
                            <input type="text" name="emergency_contacts[0][name]" class="form-control">
                        </div>

                        <div class="field">
                            <label class="form-label">Relationship</label>
                            <input type="text" name="emergency_contacts[0][relationship]" class="form-control">
                        </div>

                        <div class="field">
                            <label class="form-label">Phone</label>
                            <input type="text" name="emergency_contacts[0][phone]" class="form-control">
                        </div>

                        <div class="field">
                            <label class="form-label">Contacted?</label>
                            <select name="emergency_contacts[0][contacted]" class="form-select">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>

                    </div>
                </div>
            </div>

            <button type="button" id="add-emergency-btn" class="btn btn-sm btn-gold mb-3">
                + Add Emergency Contact
            </button>

            <hr>

            <!-- NOTES -->
            <h5 class="text-gold fw-bold">Notes</h5>
            <textarea name="notes" class="form-control" rows="4"></textarea>

            <div class="text-start mt-3">
                <button class="btn btn-gold btn-lg">Save Client</button>
            </div>

        </form>

    </div>
</div>

<script>
let beneficiaryIndex = 1;
let emergencyIndex = 1;

/* --------------------------
   ADD BENEFICIARY
--------------------------- */
document.getElementById('add-beneficiary-btn').addEventListener('click', () => {
    const wrapper = document.getElementById('beneficiary-wrapper');

    wrapper.insertAdjacentHTML('beforeend', `
        <div class="contact-row">
            <div class="contact-grid">

                <div class="field">
                    <label class="form-label">Name</label>
                    <input type="text" name="beneficiaries[${beneficiaryIndex}][name]" class="form-control">
                </div>

                <div class="field">
                    <label class="form-label">Relationship</label>
                    <input type="text" name="beneficiaries[${beneficiaryIndex}][relationship]" class="form-control">
                </div>

                <div class="field">
                    <label class="form-label">Phone</label>
                    <input type="text" name="beneficiaries[${beneficiaryIndex}][phone]" class="form-control">
                </div>

                <div class="field">
                    <label class="form-label">Contacted?</label>
                    <select name="beneficiaries[${beneficiaryIndex}][contacted]" class="form-select">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                    </select>
                </div>

                <button type="button" class="btn-remove" onclick="this.closest('.contact-row').remove()">×</button>

            </div>
        </div>
    `);

    beneficiaryIndex++;
});

/* --------------------------
   ADD EMERGENCY CONTACT
--------------------------- */
document.getElementById('add-emergency-btn').addEventListener('click', () => {
    const wrapper = document.getElementById('emergency-wrapper');

    wrapper.insertAdjacentHTML('beforeend', `
        <div class="contact-row">
            <div class="contact-grid">

                <div class="field">
                    <label class="form-label">Name</label>
                    <input type="text" name="emergency_contacts[${emergencyIndex}][name]" class="form-control">
                </div>

                <div class="field">
                    <label class="form-label">Relationship</label>
                    <input type="text" name="emergency_contacts[${emergencyIndex}][relationship]" class="form-control">
                </div>

                <div class="field">
                    <label class="form-label">Phone</label>
                    <input type="text" name="emergency_contacts[${emergencyIndex}][phone]" class="form-control">
                </div>

                <div class="field">
                    <label class="form-label">Contacted?</label>
                    <select name="emergency_contacts[${emergencyIndex}][contacted]" class="form-select">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                    </select>
                </div>

                <button type="button" class="btn-remove" onclick="this.closest('.contact-row').remove()">×</button>

            </div>
        </div>
    `);

    emergencyIndex++;
});
</script>

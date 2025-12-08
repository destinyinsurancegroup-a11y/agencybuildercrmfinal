<div class="p-4">
    <div class="card shadow-sm border-0 p-4">

        <h2 class="fw-bold mb-4">Add New Client (Book of Business)</h2>

        <form method="POST" action="{{ route('book.store') }}">
            @csrf

            {{-- ============================= --}}
            {{-- BASIC INFORMATION --}}
            {{-- ============================= --}}
            <h4 class="text-gold fw-bold mb-3">Basic Information</h4>

            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">First Name</label>
                    <input type="text" name="first_name" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Last Name</label>
                    <input type="text" name="last_name" class="form-control" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text" name="phone" class="form-control">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Anniversary</label>
                    <input type="date" name="anniversary" class="form-control">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Address Line 1</label>
                <input type="text" name="address_line1" class="form-control">
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Address Line 2</label>
                <input type="text" name="address_line2" class="form-control">
            </div>

            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">City</label>
                    <input type="text" name="city" class="form-control">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">State</label>
                    <input type="text" name="state" class="form-control">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">Postal Code</label>
                    <input type="text" name="postal_code" class="form-control">
                </div>
            </div>

            <hr>

            {{-- ============================= --}}
            {{-- POLICY INFORMATION --}}
            {{-- ============================= --}}
            <h4 class="text-gold fw-bold mb-3">Policy Information</h4>

            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Carrier</label>
                    <input type="text" name="carrier" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Policy Type</label>
                    <input type="text" name="policy_type" class="form-control">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Face Amount</label>
                    <input type="number" step="0.01" name="face_amount" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Monthly Premium</label>
                    <input type="number" step="0.01" name="premium_amount" class="form-control">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Initial Draft Date</label>
                    <input type="date" name="policy_issue_date" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Monthly Due (Text)</label>
                    <input type="text" name="premium_due_text" class="form-control"
                           placeholder="e.g. 3rd, 2nd Wednesday, 15th of every month">
                </div>
            </div>

            <hr>

            {{-- ============================= --}}
            {{-- BENEFICIARIES --}}
            {{-- ============================= --}}
            <h4 class="text-gold fw-bold mb-3">Beneficiaries</h4>

            <div id="beneficiaries-wrapper">
                {{-- Base row index 0 --}}
                <div class="row g-2 align-items-end mb-2 beneficiary-row">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Name</label>
                        <input type="text" name="beneficiaries[0][name]" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Relationship</label>
                        <input type="text" name="beneficiaries[0][relationship]" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Phone</label>
                        <input type="text" name="beneficiaries[0][phone]" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Contacted?</label>
                        <select name="beneficiaries[0][contacted]" class="form-select">
                            <option value="0" selected>No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                    <div class="col-md-1 text-end">
                        <button type="button"
                                class="btn btn-outline-danger btn-sm mt-4 d-none"
                                onclick="
                                    (function(btn){
                                        var wrapper = document.getElementById('beneficiaries-wrapper');
                                        if (!wrapper) return;
                                        var rows = wrapper.querySelectorAll('.beneficiary-row');
                                        if (rows.length <= 1) return;
                                        var row = btn.closest('.beneficiary-row');
                                        if (row) row.remove();
                                    })(this);
                                ">
                            &times;
                        </button>
                    </div>
                </div>
            </div>

            <button type="button"
                    class="btn btn-sm btn-outline-secondary mb-4"
                    onclick="
                        (function(){
                            var wrapper = document.getElementById('beneficiaries-wrapper');
                            if (!wrapper) return;
                            var prototype = wrapper.querySelector('.beneficiary-row');
                            if (!prototype) return;

                            var index = wrapper.querySelectorAll('.beneficiary-row').length;
                            var clone = prototype.cloneNode(true);

                            clone.querySelectorAll('input, select').forEach(function(input){
                                input.value = '';
                                input.name = input.name.replace(/\[\d+]/, '[' + index + ']');
                            });

                            var removeBtn = clone.querySelector('button.btn-outline-danger');
                            if (removeBtn) {
                                removeBtn.classList.remove('d-none');
                            }

                            wrapper.appendChild(clone);
                        })();
                    ">
                + Add Beneficiary
            </button>

            <hr>

            {{-- ============================= --}}
            {{-- EMERGENCY CONTACTS --}}
            {{-- ============================= --}}
            <h4 class="text-gold fw-bold mb-3">Emergency Contacts</h4>

            <div id="emergency-wrapper">
                {{-- Base row index 0 --}}
                <div class="row g-2 align-items-end mb-2 emergency-row">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Name</label>
                        <input type="text" name="emergency_contacts[0][name]" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Relationship</label>
                        <input type="text" name="emergency_contacts[0][relationship]" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Phone</label>
                        <input type="text" name="emergency_contacts[0][phone]" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Contacted?</label>
                        <select name="emergency_contacts[0][contacted]" class="form-select">
                            <option value="0" selected>No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                    <div class="col-md-1 text-end">
                        <button type="button"
                                class="btn btn-outline-danger btn-sm mt-4 d-none"
                                onclick="
                                    (function(btn){
                                        var wrapper = document.getElementById('emergency-wrapper');
                                        if (!wrapper) return;
                                        var rows = wrapper.querySelectorAll('.emergency-row');
                                        if (rows.length <= 1) return;
                                        var row = btn.closest('.emergency-row');
                                        if (row) row.remove();
                                    })(this);
                                ">
                            &times;
                        </button>
                    </div>
                </div>
            </div>

            <button type="button"
                    class="btn btn-sm btn-outline-secondary mb-4"
                    onclick="
                        (function(){
                            var wrapper = document.getElementById('emergency-wrapper');
                            if (!wrapper) return;
                            var prototype = wrapper.querySelector('.emergency-row');
                            if (!prototype) return;

                            var index = wrapper.querySelectorAll('.emergency-row').length;
                            var clone = prototype.cloneNode(true);

                            clone.querySelectorAll('input, select').forEach(function(input){
                                input.value = '';
                                input.name = input.name.replace(/\[\d+]/, '[' + index + ']');
                            });

                            var removeBtn = clone.querySelector('button.btn-outline-danger');
                            if (removeBtn) {
                                removeBtn.classList.remove('d-none');
                            }

                            wrapper.appendChild(clone);
                        })();
                    ">
                + Add Emergency Contact
            </button>

            <hr>

            <button type="submit" class="btn btn-gold">
                Save Client
            </button>
        </form>
    </div>
</div>

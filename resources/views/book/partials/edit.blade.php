{{-- ============================= --}}
{{-- BENEFICIARIES (EDIT)         --}}
{{-- ============================= --}}
<h4 class="text-gold fw-bold mb-3">Beneficiaries</h4>

@php
    $beneficiaries = $client->beneficiaries ?? collect();
@endphp

<div id="beneficiaries-wrapper">
    @if($beneficiaries->isEmpty())
        {{-- Base empty row (index 0) --}}
        <div class="row g-2 align-items-end mb-2 beneficiary-row">
            {{-- no hidden id because this will create a NEW relation --}}
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
                {{-- base row cannot be removed when it is the only one --}}
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
    @else
        {{-- Existing relations pre-filled --}}
        @foreach($beneficiaries as $index => $b)
            <div class="row g-2 align-items-end mb-2 beneficiary-row">
                {{-- keep id so BookController::saveRelations can update this row --}}
                <input type="hidden"
                       name="beneficiaries[{{ $index }}][id]"
                       value="{{ $b->id }}">

                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Name</label>
                    <input type="text"
                           name="beneficiaries[{{ $index }}][name]"
                           class="form-control"
                           value="{{ $b->name }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Relationship</label>
                    <input type="text"
                           name="beneficiaries[{{ $index }}][relationship]"
                           class="form-control"
                           value="{{ $b->relationship }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Phone</label>
                    <input type="text"
                           name="beneficiaries[{{ $index }}][phone]"
                           class="form-control"
                           value="{{ $b->phone }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Contacted?</label>
                    <select name="beneficiaries[{{ $index }}][contacted]"
                            class="form-select">
                        <option value="0" {{ !$b->contacted ? 'selected' : '' }}>No</option>
                        <option value="1" {{ $b->contacted ? 'selected' : '' }}>Yes</option>
                    </select>
                </div>
                <div class="col-md-1 text-end">
                    <button type="button"
                            class="btn btn-outline-danger btn-sm mt-4"
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
        @endforeach
    @endif
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
                    // bump the numeric [index] in the name
                    if (input.name) {
                        input.name = input.name.replace(/\[\d+]/, '[' + index + ']');
                    }

                    // reset values for new row
                    if (input.type === 'hidden') {
                        // new row must NOT carry over an existing relation id
                        input.value = '';
                    } else if (input.tagName === 'SELECT') {
                        input.value = '0';
                    } else {
                        input.value = '';
                    }
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
{{-- EMERGENCY CONTACTS (EDIT)    --}}
{{-- ============================= --}}
<h4 class="text-gold fw-bold mb-3">Emergency Contacts</h4>

@php
    $emergencies = $client->emergencyContacts ?? collect();
@endphp

<div id="emergency-wrapper">
    @if($emergencies->isEmpty())
        {{-- Base empty row (index 0) --}}
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
    @else
        @foreach($emergencies as $index => $ec)
            <div class="row g-2 align-items-end mb-2 emergency-row">
                <input type="hidden"
                       name="emergency_contacts[{{ $index }}][id]"
                       value="{{ $ec->id }}">

                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Name</label>
                    <input type="text"
                           name="emergency_contacts[{{ $index }}][name]"
                           class="form-control"
                           value="{{ $ec->name }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Relationship</label>
                    <input type="text"
                           name="emergency_contacts[{{ $index }}][relationship]"
                           class="form-control"
                           value="{{ $ec->relationship }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Phone</label>
                    <input type="text"
                           name="emergency_contacts[{{ $index }}][phone]"
                           class="form-control"
                           value="{{ $ec->phone }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Contacted?</label>
                    <select name="emergency_contacts[{{ $index }}][contacted]"
                            class="form-select">
                        <option value="0" {{ !$ec->contacted ? 'selected' : '' }}>No</option>
                        <option value="1" {{ $ec->contacted ? 'selected' : '' }}>Yes</option>
                    </select>
                </div>
                <div class="col-md-1 text-end">
                    <button type="button"
                            class="btn btn-outline-danger btn-sm mt-4"
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
        @endforeach
    @endif
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
                    if (input.name) {
                        input.name = input.name.replace(/\[\d+]/, '[' + index + ']');
                    }

                    if (input.type === 'hidden') {
                        input.value = '';
                    } else if (input.tagName === 'SELECT') {
                        input.value = '0';
                    } else {
                        input.value = '';
                    }
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

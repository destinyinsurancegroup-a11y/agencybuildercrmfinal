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
        .emergency-row,
        .policy-row {
            border: 1px solid #ddd;
            padding: .45rem .5rem !important;
            margin-bottom: .45rem !important;
            border-radius: 6px;
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

        .btn-mini {
            padding: 5px 10px !important;
            font-size: .82rem !important;
            border-radius: 6px !important;
        }
    </style>

    <div class="card shadow-sm border-0">

        <form method="POST" action="{{ route('book.update', $client->id) }}">
            @csrf
            @method('PUT')

            <!-- TOP ACTIONS -->
            <div class="d-flex justify-content-end gap-2 mb-2">
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary btn-mini"
                    onclick="loadBookPanel('{{ url('/book/' . $client->id) }}')"
                >
                    ← Back
                </button>
            </div>

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
                           value="{{ old('date_of_birth', optional($client->date_of_birth)->format('Y-m-d')) }}">
                </div>

                <div class="field">
                    <label class="form-label">Age</label>
                    <input type="number" class="form-control" disabled value="{{ $client->age }}">
                </div>

                <div class="field">
                    <label class="form-label">Anniversary</label>
                    <input type="date" name="anniversary" class="form-control"
                           value="{{ old('anniversary', optional($client->anniversary)->format('Y-m-d')) }}">
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

            <!-- POLICY INFORMATION (LEGACY SINGLE FIELDS - KEEP FOR COMPATIBILITY) -->
            <h5 class="text-gold fw-bold">Policy Information (Primary / Legacy)</h5>

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
                           value="{{ old('policy_issue_date', optional($client->policy_issue_date)->format('Y-m-d')) }}">
                </div>

                <div class="field">
                    <label class="form-label">Monthly Due (Text)</label>
                    <input type="text" name="premium_due_text" class="form-control"
                           value="{{ old('premium_due_text', $client->premium_due_text) }}">
                </div>

            </div>

            <hr>

            <!-- MULTI-POLICY SECTION (THIS IS THE NEW WORKING FEATURE) -->
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="text-gold fw-bold mb-0">Policies (Multiple)</h5>

                <button type="button"
                        class="btn btn-sm btn-outline-secondary btn-mini"
                        onclick="
                            (function(){
                                var wrapper = document.getElementById('policies-wrapper');
                                if (!wrapper) return;

                                var rows = wrapper.querySelectorAll('.policy-row');
                                var index = rows.length;

                                var tpl = document.getElementById('policy-row-template');
                                if (!tpl) return;

                                var html = tpl.innerHTML.replaceAll('__INDEX__', index);
                                var holder = document.createElement('div');
                                holder.innerHTML = html.trim();
                                wrapper.appendChild(holder.firstElementChild);
                            })();
                        ">
                    + Add Policy
                </button>
            </div>

            @php
                $oldPolicies = old('policies');

                if (is_array($oldPolicies)) {
                    $policies = collect($oldPolicies);
                } else {
                    try {
                        $policies = $client->policies()->orderBy('id')->get();
                    } catch (\Throwable $e) {
                        $policies = collect();
                    }
                }

                if ($policies->isEmpty()) {
                    $policies = collect([null]);
                }
            @endphp

            <div id="policies-wrapper" class="mt-2">
                @foreach($policies as $index => $p)
                    @php
                        $pid = is_array($p) ? ($p['id'] ?? null) : ($p?->id ?? null);

                        $carrier       = is_array($p) ? ($p['carrier'] ?? '')        : ($p?->carrier ?? '');
                        $policyType    = is_array($p) ? ($p['policy_type'] ?? '')    : ($p?->policy_type ?? '');
                        $faceAmount    = is_array($p) ? ($p['face_amount'] ?? '')    : ($p?->face_amount ?? '');
                        $premiumAmount = is_array($p) ? ($p['premium_amount'] ?? '') : ($p?->premium_amount ?? '');

                        // ✅ FIX: must guard null policy object
                        $issueDate = is_array($p)
                            ? ($p['policy_issue_date'] ?? '')
                            : (optional($p?->policy_issue_date)->format('Y-m-d') ?? '');

                        $dueDate = is_array($p)
                            ? ($p['premium_due_date'] ?? '')
                            : (optional($p?->premium_due_date)->format('Y-m-d') ?? '');

                        $dueText = is_array($p) ? ($p['premium_due_text'] ?? '') : ($p?->premium_due_text ?? '');
                    @endphp

                    <div class="row g-2 align-items-end mb-2 policy-row">

                        @if($pid)
                            <input type="hidden" name="policies[{{ $index }}][id]" value="{{ $pid }}">
                        @endif

                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Carrier</label>
                            <input type="text"
                                   name="policies[{{ $index }}][carrier]"
                                   class="form-control"
                                   value="{{ $carrier }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Policy Type</label>
                            <input type="text"
                                   name="policies[{{ $index }}][policy_type]"
                                   class="form-control"
                                   value="{{ $policyType }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Face Amount</label>
                            <input type="number" step="0.01"
                                   name="policies[{{ $index }}][face_amount]"
                                   class="form-control"
                                   value="{{ $faceAmount }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Monthly Premium</label>
                            <input type="number" step="0.01"
                                   name="policies[{{ $index }}][premium_amount]"
                                   class="form-control"
                                   value="{{ $premiumAmount }}">
                        </div>

                        <div class="col-md-2 text-end">
                            <button type="button"
                                    class="btn btn-sm btn-outline-danger btn-mini ab-remove-policy">
                                Remove
                            </button>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Initial Draft Date</label>
                            <input type="date"
                                   name="policies[{{ $index }}][policy_issue_date]"
                                   class="form-control"
                                   value="{{ $issueDate }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Premium Due Date</label>
                            <input type="date"
                                   name="policies[{{ $index }}][premium_due_date]"
                                   class="form-control"
                                   value="{{ $dueDate }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Monthly Due (Text)</label>
                            <input type="text"
                                   name="policies[{{ $index }}][premium_due_text]"
                                   class="form-control"
                                   value="{{ $dueText }}">
                        </div>

                    </div>
                @endforeach
            </div>

            <template id="policy-row-template">
                <div class="row g-2 align-items-end mb-2 policy-row">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Carrier</label>
                        <input type="text"
                               name="policies[__INDEX__][carrier]"
                               class="form-control"
                               value="">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Policy Type</label>
                        <input type="text"
                               name="policies[__INDEX__][policy_type]"
                               class="form-control"
                               value="">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Face Amount</label>
                        <input type="number" step="0.01"
                               name="policies[__INDEX__][face_amount]"
                               class="form-control"
                               value="">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Monthly Premium</label>
                        <input type="number" step="0.01"
                               name="policies[__INDEX__][premium_amount]"
                               class="form-control"
                               value="">
                    </div>

                    <div class="col-md-2 text-end">
                        <button type="button"
                                class="btn btn-sm btn-outline-danger btn-mini ab-remove-policy">
                            Remove
                        </button>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Initial Draft Date</label>
                        <input type="date"
                               name="policies[__INDEX__][policy_issue_date]"
                               class="form-control"
                               value="">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Premium Due Date</label>
                        <input type="date"
                               name="policies[__INDEX__][premium_due_date]"
                               class="form-control"
                               value="">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Monthly Due (Text)</label>
                        <input type="text"
                               name="policies[__INDEX__][premium_due_text]"
                               class="form-control"
                               value="">
                    </div>
                </div>
            </template>

            <script>
                (function(){
                    var wrapper = document.getElementById('policies-wrapper');
                    if (!wrapper) return;

                    if (wrapper.dataset.bound === '1') return;
                    wrapper.dataset.bound = '1';

                    wrapper.addEventListener('click', function(e){
                        var btn = e.target.closest('.ab-remove-policy');
                        if (!btn) return;

                        var row = btn.closest('.policy-row');
                        if (!row) return;

                        row.parentNode.removeChild(row);
                    });
                })();
            </script>

            <hr>

            <!-- CONTACTS SECTION -->
            <h5 class="text-gold fw-bold">Contacts</h5>

            {{-- ============================= --}}
            {{-- BENEFICIARIES (dynamic rows) --}}
            {{-- ============================= --}}
            <div class="contact-subtitle">Beneficiaries</div>

            @php
                $beneficiaries = $client->beneficiaries ?? collect();
                if ($beneficiaries->isEmpty()) {
                    $beneficiaries = collect([null]);
                }
            @endphp

            <div id="beneficiaries-wrapper">
                @foreach($beneficiaries as $index => $b)
                    <div class="row g-2 align-items-end mb-2 beneficiary-row">

                        @if($b)
                            <input type="hidden"
                                   name="beneficiaries[{{ $index }}][id]"
                                   value="{{ $b->id }}">
                        @endif

                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Name</label>
                            <input type="text"
                                   name="beneficiaries[{{ $index }}][name]"
                                   class="form-control"
                                   value="{{ $b->name ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Relationship</label>
                            <input type="text"
                                   name="beneficiaries[{{ $index }}][relationship]"
                                   class="form-control"
                                   value="{{ $b->relationship ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Phone</label>
                            <input type="text"
                                   name="beneficiaries[{{ $index }}][phone]"
                                   class="form-control"
                                   value="{{ $b->phone ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Contacted?</label>
                            <select name="beneficiaries[{{ $index }}][contacted]" class="form-select">
                                <option value="0" {{ !$b || !$b->contacted ? 'selected' : '' }}>No</option>
                                <option value="1" {{ $b && $b->contacted ? 'selected' : '' }}>Yes</option>
                            </select>
                        </div>
                    </div>
                @endforeach
            </div>

            <button type="button"
                    class="btn btn-sm btn-outline-secondary mb-4 btn-mini"
                    onclick="
                        (function(){
                            var wrapper   = document.getElementById('beneficiaries-wrapper');
                            if (!wrapper) return;

                            var rows      = wrapper.querySelectorAll('.beneficiary-row');
                            if (!rows.length) return;

                            var prototype = rows[rows.length - 1];
                            var index     = rows.length;

                            var clone = prototype.cloneNode(true);

                            clone.querySelectorAll('input, select').forEach(function(input){
                                if (!input.name) return;

                                if (input.type === 'hidden' || input.name.indexOf('[id]') !== -1) {
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

                            wrapper.appendChild(clone);
                        })();
                    ">
                + Add Beneficiary
            </button>

            <hr>

            {{-- ================================== --}}
            {{-- EMERGENCY CONTACTS (dynamic rows) --}}
            {{-- ================================== --}}
            <div class="contact-subtitle mt-2">Emergency Contacts</div>

            @php
                $emergencyContacts = $client->emergencyContacts ?? collect();
                if ($emergencyContacts->isEmpty()) {
                    $emergencyContacts = collect([null]);
                }
            @endphp

            <div id="emergency-wrapper">
                @foreach($emergencyContacts as $index => $e)
                    <div class="row g-2 align-items-end mb-2 emergency-row">

                        @if($e)
                            <input type="hidden"
                                   name="emergency_contacts[{{ $index }}][id]"
                                   value="{{ $e->id }}">
                        @endif

                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Name</label>
                            <input type="text"
                                   name="emergency_contacts[{{ $index }}][name]"
                                   class="form-control"
                                   value="{{ $e->name ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Relationship</label>
                            <input type="text"
                                   name="emergency_contacts[{{ $index }}][relationship]"
                                   class="form-control"
                                   value="{{ $e->relationship ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Phone</label>
                            <input type="text"
                                   name="emergency_contacts[{{ $index }}][phone]"
                                   class="form-control"
                                   value="{{ $e->phone ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Contacted?</label>
                            <select name="emergency_contacts[{{ $index }}][contacted]" class="form-select">
                                <option value="0" {{ !$e || !$e->contacted ? 'selected' : '' }}>No</option>
                                <option value="1" {{ $e && $e->contacted ? 'selected' : '' }}>Yes</option>
                            </select>
                        </div>
                    </div>
                @endforeach
            </div>

            <button type="button"
                    class="btn btn-sm btn-outline-secondary mb-4 btn-mini"
                    onclick="
                        (function(){
                            var wrapper   = document.getElementById('emergency-wrapper');
                            if (!wrapper) return;

                            var rows      = wrapper.querySelectorAll('.emergency-row');
                            if (!rows.length) return;

                            var prototype = rows[rows.length - 1];
                            var index     = rows.length;

                            var clone = prototype.cloneNode(true);

                            clone.querySelectorAll('input, select').forEach(function(input){
                                if (!input.name) return;

                                if (input.type === 'hidden' || input.name.indexOf('[id]') !== -1) {
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

                            wrapper.appendChild(clone);
                        })();
                    ">
                + Add Emergency Contact
            </button>

            <hr>

            <div class="text-end d-flex justify-content-end gap-2">
                <button type="button"
                        class="btn btn-sm btn-outline-secondary btn-mini"
                        onclick="loadBookPanel('{{ url('/book/' . $client->id) }}')">
                    ← Back
                </button>

                <button class="btn-gold btn-lg">Save Client</button>
            </div>

        </form>

    </div>

</div>

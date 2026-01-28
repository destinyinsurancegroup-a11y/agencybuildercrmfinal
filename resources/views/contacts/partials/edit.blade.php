@extends('layouts.app')

@section('content')
@php
    /**
     * Policies data source:
     * - If validation fails, we want to re-render what the user typed (old('policies')).
     * - Otherwise, render from DB ($contact->policies).
     *
     * IMPORTANT:
     * Field names MUST be policies[INDEX][field] to match ContactsController::savePolicies().
     */
    $oldPolicies = old('policies');
    $policiesForForm = is_array($oldPolicies) ? $oldPolicies : $contact->policies()->orderBy('id')->get()->toArray();
@endphp

<div class="container-fluid py-4">

    <div class="card shadow-sm border-0"
         style="border-radius:18px; height: calc(100vh - 120px); overflow-y:auto;">

        <!-- HEADER -->
        <div class="card-header bg-black text-gold fw-bold d-flex justify-content-between align-items-center"
             style="border-radius:18px 18px 0 0;">
            <span style="font-size:18px;">Edit Contact</span>
        </div>

        <div class="card-body">

            <form method="POST" action="{{ route('contacts.update', $contact) }}">
                @csrf
                @method('PUT')

                <!-- BASIC INFO -->
                <h5 class="fw-bold mb-3">Basic Information</h5>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted">First Name</label>
                        <input type="text" name="first_name" class="form-control"
                               value="{{ old('first_name', $contact->first_name) }}" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="text-muted">Last Name</label>
                        <input type="text" name="last_name" class="form-control"
                               value="{{ old('last_name', $contact->last_name) }}" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-muted">Email</label>
                        <input type="email" name="email" class="form-control"
                               value="{{ old('email', $contact->email) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="text-muted">Phone</label>
                        <input type="text" name="phone" class="form-control"
                               value="{{ old('phone', $contact->phone) }}">
                    </div>
                </div>

                <!-- BIRTHDAY + ANNIVERSARY -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="text-muted">Date of Birth</label>
                        <input type="date"
                               name="date_of_birth"
                               class="form-control"
                               value="{{ old('date_of_birth', optional($contact->date_of_birth)->format('Y-m-d')) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="text-muted">Anniversary</label>
                        <input type="date"
                               name="anniversary"
                               class="form-control"
                               value="{{ old('anniversary', optional($contact->anniversary)->format('Y-m-d')) }}">
                    </div>
                </div>

                <!-- CONTACT TYPE + STATUS -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-muted">Contact Type</label>
                        <input type="text" name="contact_type" class="form-control"
                               value="{{ old('contact_type', $contact->contact_type) }}"
                               placeholder="Optional">
                    </div>

                    <div class="col-md-6">
                        <label class="text-muted">Status</label>
                        <input type="text" name="status" class="form-control"
                               value="{{ old('status', $contact->status) }}"
                               placeholder="Optional">
                    </div>
                </div>

                <!-- ADDRESS SECTION -->
                <h5 class="fw-bold mt-4 mb-2">Address</h5>

                <div class="mb-3">
                    <label class="text-muted">Address Line 1</label>
                    <input type="text" name="address_line1" class="form-control"
                           value="{{ old('address_line1', $contact->address_line1) }}">
                </div>

                <div class="mb-3">
                    <label class="text-muted">Address Line 2</label>
                    <input type="text" name="address_line2" class="form-control"
                           value="{{ old('address_line2', $contact->address_line2) }}">
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="text-muted">City</label>
                        <input type="text" name="city" class="form-control"
                               value="{{ old('city', $contact->city) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="text-muted">State</label>
                        <input type="text" name="state" class="form-control"
                               value="{{ old('state', $contact->state) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="text-muted">Postal Code</label>
                        <input type="text" name="postal_code" class="form-control"
                               value="{{ old('postal_code', $contact->postal_code) }}">
                    </div>
                </div>

                <hr class="my-4">

                <!-- ✅ POLICIES (Multi-row, + button, like beneficiaries/emergency contacts) -->
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h5 class="fw-bold m-0">Policy Information</h5>

                    {{-- ✅ Add button is EDIT-ONLY (this page) --}}
                    <button type="button"
                            id="ab-add-policy"
                            class="btn fw-bold"
                            style="background:#c9a227; color:#111827; border:none; border-radius:8px; padding:6px 12px;">
                        +
                    </button>
                </div>

                <p class="text-muted small mb-3">
                    Click <strong>+</strong> to add another policy. Use “Remove” to delete a policy row.
                </p>

                <div id="ab-policies-wrap">
                    @forelse($policiesForForm as $i => $p)
                        @php
                            // When coming from DB -> $p is an array (toArray()).
                            // When coming from old('policies') -> $p is also an array.
                            $pid = $p['id'] ?? null;

                            $carrier = $p['carrier'] ?? '';
                            $policy_type = $p['policy_type'] ?? '';
                            $face_amount = $p['face_amount'] ?? '';
                            $premium_amount = $p['premium_amount'] ?? '';
                            $policy_issue_date = $p['policy_issue_date'] ?? '';
                            $premium_due_date = $p['premium_due_date'] ?? '';
                            $premium_due_text = $p['premium_due_text'] ?? '';

                            // Normalize date formats to Y-m-d for <input type="date">
                            if (!empty($policy_issue_date) && strlen((string)$policy_issue_date) > 10) {
                                $policy_issue_date = \Illuminate\Support\Carbon::parse($policy_issue_date)->format('Y-m-d');
                            }
                            if (!empty($premium_due_date) && strlen((string)$premium_due_date) > 10) {
                                $premium_due_date = \Illuminate\Support\Carbon::parse($premium_due_date)->format('Y-m-d');
                            }
                        @endphp

                        <div class="border rounded p-3 mb-3 ab-policy-row" style="border-color:#e5e7eb;">
                            <input type="hidden" name="policies[{{ $i }}][id]" value="{{ $pid }}">

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Carrier</label>
                                    <input type="text" class="form-control"
                                           name="policies[{{ $i }}][carrier]"
                                           value="{{ $carrier }}"
                                           placeholder="Carrier">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Policy Type</label>
                                    <input type="text" class="form-control"
                                           name="policies[{{ $i }}][policy_type]"
                                           value="{{ $policy_type }}"
                                           placeholder="Final Expense, Term, etc.">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Face Amount</label>
                                    <input type="text" class="form-control"
                                           name="policies[{{ $i }}][face_amount]"
                                           value="{{ $face_amount }}"
                                           placeholder="e.g. 10000">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Monthly Premium</label>
                                    <input type="text" class="form-control"
                                           name="policies[{{ $i }}][premium_amount]"
                                           value="{{ $premium_amount }}"
                                           placeholder="e.g. 48.62">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="text-muted">Initial Draft Date</label>
                                    <input type="date" class="form-control"
                                           name="policies[{{ $i }}][policy_issue_date]"
                                           value="{{ $policy_issue_date }}">
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="text-muted">Premium Due Date</label>
                                    <input type="date" class="form-control"
                                           name="policies[{{ $i }}][premium_due_date]"
                                           value="{{ $premium_due_date }}">
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="text-muted">Monthly Due (Text)</label>
                                    <input type="text" class="form-control"
                                           name="policies[{{ $i }}][premium_due_text]"
                                           value="{{ $premium_due_text }}"
                                           placeholder="3rd">
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger ab-remove-policy">
                                    Remove
                                </button>
                            </div>
                        </div>
                    @empty
                        {{-- No existing policies: show nothing until user hits + --}}
                    @endforelse
                </div>

                {{-- Template for new policy rows --}}
                <template id="ab-policy-template">
                    <div class="border rounded p-3 mb-3 ab-policy-row" style="border-color:#e5e7eb;">
                        <input type="hidden" name="policies[__INDEX__][id]" value="">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="text-muted">Carrier</label>
                                <input type="text" class="form-control"
                                       name="policies[__INDEX__][carrier]"
                                       value=""
                                       placeholder="Carrier">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="text-muted">Policy Type</label>
                                <input type="text" class="form-control"
                                       name="policies[__INDEX__][policy_type]"
                                       value=""
                                       placeholder="Final Expense, Term, etc.">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="text-muted">Face Amount</label>
                                <input type="text" class="form-control"
                                       name="policies[__INDEX__][face_amount]"
                                       value=""
                                       placeholder="e.g. 10000">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="text-muted">Monthly Premium</label>
                                <input type="text" class="form-control"
                                       name="policies[__INDEX__][premium_amount]"
                                       value=""
                                       placeholder="e.g. 48.62">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="text-muted">Initial Draft Date</label>
                                <input type="date" class="form-control"
                                       name="policies[__INDEX__][policy_issue_date]"
                                       value="">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="text-muted">Premium Due Date</label>
                                <input type="date" class="form-control"
                                       name="policies[__INDEX__][premium_due_date]"
                                       value="">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="text-muted">Monthly Due (Text)</label>
                                <input type="text" class="form-control"
                                       name="policies[__INDEX__][premium_due_text]"
                                       value=""
                                       placeholder="3rd">
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="button"
                                    class="btn btn-sm btn-outline-danger ab-remove-policy">
                                Remove
                            </button>
                        </div>
                    </div>
                </template>

                <hr class="my-4">

                <!-- BUTTONS -->
                <button class="btn fw-bold"
                        style="background:#c9a227; color:#111827; border:none; border-radius:8px; padding:10px 20px;">
                    Save Changes
                </button>

                <a href="{{ route('contacts.show', $contact->id) }}"
                   class="btn btn-link text-muted ms-2">
                    Cancel
                </a>

            </form>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const wrap = document.getElementById('ab-policies-wrap');
    const tpl  = document.getElementById('ab-policy-template');
    const add  = document.getElementById('ab-add-policy');

    function nextIndex() {
        // Count existing policy rows to get a safe next index.
        // Gaps from deletions are OK; controller uses array_values() before processing.
        return wrap.querySelectorAll('.ab-policy-row').length;
    }

    add.addEventListener('click', function () {
        const idx = nextIndex();
        const html = tpl.innerHTML.replaceAll('__INDEX__', idx);
        const holder = document.createElement('div');
        holder.innerHTML = html.trim();
        wrap.appendChild(holder.firstElementChild);
    });

    wrap.addEventListener('click', function (e) {
        const btn = e.target.closest('.ab-remove-policy');
        if (!btn) return;

        const row = btn.closest('.ab-policy-row');
        if (row) row.remove();
    });
});
</script>
@endsection

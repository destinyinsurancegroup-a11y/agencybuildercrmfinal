<style>
    /* ===== COMPACT SPACING FOR CONTACT PAGE ===== */

    /* Reduce padding inside the main card */
    .card.p-4 {
        padding: 1.25rem !important;
    }

    /* Reduce spacing between section headers */
    h4.text-gold {
        margin-top: 0.5rem !important;
        margin-bottom: 0.5rem !important;
    }

    /* Reduce spacing between the row sections */
    .row.mb-4 {
        margin-bottom: 0.75rem !important;
    }

    /* Tighten horizontal rule spacing */
    .card hr {
        margin: 0.75rem 0 !important;
    }

    /* Reduce spacing between paragraph lines */
    .card p {
        margin-bottom: 0.25rem !important;
    }

    /* Beneficiaries + Emergency row padding compact */
    #beneficiaries-list .p-3,
    #emergency-list .p-3 {
        padding: 0.65rem !important;
        margin-bottom: 0.5rem !important;
    }

    /* Main wrapper compactness */
    .p-4 {
        padding: 1.25rem !important;
    }
</style>

<div class="p-4">

    <div class="card shadow-sm border-0 p-4">

        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="fw-bold" style="font-size: 32px;">
                {{ $client->first_name }} {{ $client->last_name }}
            </h1>

            <!-- AJAX EDIT BUTTON -->
            <button 
                class="btn-gold"
                data-edit-url="{{ route('book.edit.panel', $client->id) }}"
                onclick="loadBookPanel(this.dataset.editUrl)"
            >
                Edit
            </button>
        </div>

        <hr>

        <!-- ================================ -->
        <!-- BASIC INFORMATION SECTION        -->
        <!-- ================================ -->
        <h4 class="text-gold fw-bold mb-3">Basic Information</h4>

        <div class="row mb-4">

            <div class="col-md-6">
                <p><strong>Email:</strong> {{ $client->email ?: '—' }}</p>
                <p><strong>Phone:</strong> {{ $client->phone ?: '—' }}</p>

                <p><strong>Date of Birth:</strong>
                    {{ $client->date_of_birth ? $client->date_of_birth->format('m/d/Y') : '—' }}
                </p>

                <p><strong>Age:</strong>
                    {{ $client->age ?? '—' }}
                </p>
            </div>

            <div class="col-md-6">
                <p><strong>Anniversary:</strong>
                    {{ $client->anniversary ? $client->anniversary->format('m/d/Y') : '—' }}
                </p>

                <p><strong>Address:</strong><br>
                    @if($client->address_line1)
                        {{ $client->address_line1 }}<br>
                        @if($client->address_line2) {{ $client->address_line2 }}<br> @endif
                        {{ $client->city }}, {{ $client->state }} {{ $client->postal_code }}
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </p>
            </div>

        </div>

        <hr>

        <!-- ================================ -->
        <!-- POLICY INFORMATION SECTION       -->
        <!-- ================================ -->
        <h4 class="text-gold fw-bold mb-3">Policy Information</h4>

        <div class="row mb-4">

            <div class="col-md-6">
                <p><strong>Carrier:</strong> {{ $client->carrier ?: '—' }}</p>
                <p><strong>Policy Type:</strong> {{ $client->policy_type ?: '—' }}</p>

                <p><strong>Face Amount:</strong>
                    {{ $client->face_amount ? '$' . number_format($client->face_amount, 2) : '—' }}
                </p>

                <p><strong>Monthly Premium:</strong>
                    {{ $client->premium_amount ? '$' . number_format($client->premium_amount, 2) : '—' }}
                </p>
            </div>

            <div class="col-md-6">
                <p><strong>Issue Date:</strong>
                    {{ $client->policy_issue_date ? $client->policy_issue_date->format('m/d/Y') : '—' }}
                </p>

                <p><strong>Monthly Due (Text):</strong>
                    {{ $client->premium_due_text ?: '—' }}
                </p>

                <p><strong>Due Date (Calendar):</strong>
                    {{ $client->premium_due_date ? $client->premium_due_date->format('m/d/Y') : '—' }}
                </p>
            </div>

        </div>

        <hr>

        <!-- ================================ -->
        <!-- BENEFICIARIES SECTION (READ ONLY) -->
        <!-- ================================ -->
        <h4 class="text-gold fw-bold mb-3">Beneficiaries</h4>

        <div id="beneficiaries-list">
            @forelse ($client->beneficiaries as $b)
                <div 
                    class="border rounded p-3 mb-2 d-flex justify-content-between align-items-center"
                >
                    <div>
                        <strong>{{ $b->name }}</strong><br>
                        <small>
                            {{ $b->relationship ?: '—' }} /
                            {{ $b->phone ?: '—' }} /
                            Contacted:
                            @if($b->contacted)
                                <span class="text-success fw-bold">Yes</span>
                            @else
                                <span class="text-danger fw-bold">No</span>
                            @endif
                        </small>
                    </div>
                </div>
            @empty
                <p class="text-muted">No beneficiaries added.</p>
            @endforelse
        </div>

        <hr>

        <!-- ================================ -->
        <!-- EMERGENCY CONTACTS (READ ONLY)  -->
        <!-- ================================ -->
        <h4 class="text-gold fw-bold mb-3">Emergency Contacts</h4>

        <div id="emergency-list">
            @forelse ($client->emergencyContacts as $ec)
                <div 
                    class="border rounded p-3 mb-2 d-flex justify-content-between align-items-center"
                >
                    <div>
                        <strong>{{ $ec->name }}</strong><br>
                        <small>
                            {{ $ec->relationship ?: '—' }} /
                            {{ $ec->phone ?: '—' }} /
                            Contacted:
                            @if($ec->contacted)
                                <span class="text-success fw-bold">Yes</span>
                            @else
                                <span class="text-danger fw-bold">No</span>
                            @endif
                        </small>
                    </div>
                </div>
            @empty
                <p class="text-muted">No emergency contacts added.</p>
            @endforelse
        </div>

        <hr>

        <!-- ================================ -->
        <!-- NOTES SECTION (EDITABLE)         -->
        <!-- ================================ -->
        <h4 class="text-gold fw-bold mb-3">Notes</h4>
        <p>{{ $client->notes ?: 'No notes added.' }}</p>

    </div>

</div>

<!-- ========================================================== -->
<!-- === BENEFICIARY MODAL (kept but unused) ================== -->
<!-- ========================================================== -->
<div class="modal fade" id="beneficiaryModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="beneficiaryForm" class="modal-content">
            @csrf

            <div class="modal-header bg-black text-gold">
                <h5 class="modal-title" id="beneficiaryModalTitle">Add Beneficiary</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="beneficiary_id">
                <input type="hidden" id="beneficiary_client_id">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Name</label>
                    <input type="text" class="form-control" id="beneficiary_name" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Relationship</label>
                    <input type="text" class="form-control" id="beneficiary_relationship">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text" class="form-control" id="beneficiary_phone">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Contacted?</label>
                    <select class="form-select" id="beneficiary_contacted">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                    </select>
                </div>

            </div>

            <div class="modal-footer">
                <button type="submit" class="btn-gold">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================== -->
<!-- === EMERGENCY CONTACT MODAL (kept but unused) ============ -->
<!-- ========================================================== -->
<div class="modal fade" id="emergencyModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="emergencyForm" class="modal-content">
            @csrf

            <div class="modal-header bg-black text-gold">
                <h5 class="modal-title" id="emergencyModalTitle">Add Emergency Contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <input type="hidden" id="emergency_id">
                <input type="hidden" id="emergency_client_id">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Name</label>
                    <input type="text" class="form-control" id="emergency_name" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Relationship</label>
                    <input type="text" class="form-control" id="emergency_relationship">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text" class="form-control" id="emergency_phone">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Contacted?</label>
                    <select class="form-select" id="emergency_contacted">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                    </select>
                </div>

            </div>

            <div class="modal-footer">
                <button type="submit" class="btn-gold">Save</button>
            </div>
        </form>
    </div>
</div>

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
        <!-- BENEFICIARIES SECTION            -->
        <!-- ================================ -->
        <h4 class="text-gold fw-bold mb-3">Beneficiaries</h4>

        <div id="beneficiaries-list">

            @forelse ($client->beneficiaries as $b)
                <div class="border rounded p-3 mb-2 d-flex justify-content-between">

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

                    <div class="d-flex gap-2">
                        <button 
                            class="btn btn-sm btn-outline-primary"
                            onclick="editBeneficiary({{ $b->id }})"
                        >
                            Edit
                        </button>

                        <button 
                            class="btn btn-sm btn-outline-danger"
                            onclick="deleteBeneficiary({{ $client->id }}, {{ $b->id }})"
                        >
                            Delete
                        </button>
                    </div>

                </div>
            @empty
                <p class="text-muted">No beneficiaries added.</p>
            @endforelse

        </div>

        <button 
            class="btn-gold mt-2"
            onclick="openAddBeneficiary({{ $client->id }})"
        >
            + Add Beneficiary
        </button>

        <hr>

        <!-- ================================ -->
        <!-- EMERGENCY CONTACTS SECTION       -->
        <!-- ================================ -->
        <h4 class="text-gold fw-bold mb-3">Emergency Contacts</h4>

        <div id="emergency-list">

            @forelse ($client->emergencyContacts as $ec)
                <div class="border rounded p-3 mb-2 d-flex justify-content-between">

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

                    <div class="d-flex gap-2">
                        <button 
                            class="btn btn-sm btn-outline-primary"
                            onclick="editEmergency({{ $ec->id }})"
                        >
                            Edit
                        </button>

                        <button 
                            class="btn btn-sm btn-outline-danger"
                            onclick="deleteEmergency({{ $client->id }}, {{ $ec->id }})"
                        >
                            Delete
                        </button>
                    </div>

                </div>
            @empty
                <p class="text-muted">No emergency contacts added.</p>
            @endforelse

        </div>

        <button 
            class="btn-gold mt-2"
            onclick="openAddEmergency({{ $client->id }})"
        >
            + Add Emergency Contact
        </button>

        <hr>

        <!-- ================================ -->
        <!-- NOTES SECTION                    -->
        <!-- ================================ -->
        <h4 class="text-gold fw-bold mb-3">Notes</h4>
        <p>{{ $client->notes ?: 'No notes added.' }}</p>

    </div>

</div>



<!-- ========================================================== -->
<!-- === BENEFICIARY MODAL ==================================== -->
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
<!-- === EMERGENCY CONTACT MODAL ============================== -->
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


<!-- ========================================================== -->
<!-- === AJAX JAVASCRIPT ====================================== -->
<!-- ========================================================== -->
@push('scripts')
<script>

/* ------------------------------------------------------ */
/* OPEN ADD BENEFICIARY MODAL                             */
/* ------------------------------------------------------ */
function openAddBeneficiary(clientId) {
    document.getElementById('beneficiaryModalTitle').innerText = "Add Beneficiary";
    document.getElementById('beneficiary_id').value = "";
    document.getElementById('beneficiary_client_id').value = clientId;

    document.getElementById('beneficiary_name').value = "";
    document.getElementById('beneficiary_relationship').value = "";
    document.getElementById('beneficiary_phone').value = "";
    document.getElementById('beneficiary_contacted').value = "0";

    new bootstrap.Modal(document.getElementById('beneficiaryModal')).show();
}


/* ------------------------------------------------------ */
/* EDIT BENEFICIARY                                       */
/* ------------------------------------------------------ */
function editBeneficiary(id) {
    fetch(`/api/beneficiaries/${id}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('beneficiaryModalTitle').innerText = "Edit Beneficiary";

            document.getElementById('beneficiary_id').value = data.id;
            document.getElementById('beneficiary_client_id').value = data.contact_id;

            document.getElementById('beneficiary_name').value = data.name;
            document.getElementById('beneficiary_relationship').value = data.relationship ?? "";
            document.getElementById('beneficiary_phone').value = data.phone ?? "";
            document.getElementById('beneficiary_contacted').value = data.contacted ? "1" : "0";

            new bootstrap.Modal(document.getElementById('beneficiaryModal')).show();
        });
}


/* ------------------------------------------------------ */
/* SAVE BENEFICIARY                                       */
/* ------------------------------------------------------ */
document.getElementById('beneficiaryForm').addEventListener('submit', function (e) {
    e.preventDefault();

    let id = document.getElementById('beneficiary_id').value;
    let clientId = document.getElementById('beneficiary_client_id').value;

    let payload = {
        name: document.getElementById('beneficiary_name').value,
        relationship: document.getElementById('beneficiary_relationship').value,
        phone: document.getElementById('beneficiary_phone').value,
        contacted: document.getElementById('beneficiary_contacted').value,
        _token: "{{ csrf_token() }}"
    };

    let url = id 
        ? `/book/${clientId}/beneficiaries/${id}` 
        : `/book/${clientId}/beneficiaries`;

    let method = id ? "PUT" : "POST";

    fetch(url, {
        method: method,
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(() => {
        bootstrap.Modal.getInstance(document.getElementById('beneficiaryModal')).hide();
        loadBookPanel(`/book/${clientId}`);
    });
});


/* ------------------------------------------------------ */
/* DELETE BENEFICIARY                                     */
/* ------------------------------------------------------ */
function deleteBeneficiary(clientId, id) {
    if (!confirm("Delete beneficiary?")) return;

    fetch(`/book/${clientId}/beneficiaries/${id}`, {
        method: "DELETE",
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        }
    })
    .then(res => res.json())
    .then(() => {
        loadBookPanel(`/book/${clientId}`);
    });
}


/* ------------------------------------------------------ */
/* OPEN ADD EMERGENCY CONTACT                             */
/* ------------------------------------------------------ */
function openAddEmergency(clientId) {
    document.getElementById('emergencyModalTitle').innerText = "Add Emergency Contact";
    document.getElementById('emergency_id').value = "";
    document.getElementById('emergency_client_id').value = clientId;

    document.getElementById('emergency_name').value = "";
    document.getElementById('emergency_relationship').value = "";
    document.getElementById('emergency_phone').value = "";
    document.getElementById('emergency_contacted').value = "0";

    new bootstrap.Modal(document.getElementById('emergencyModal')).show();
}


/* ------------------------------------------------------ */
/* EDIT EMERGENCY CONTACT                                 */
/* ------------------------------------------------------ */
function editEmergency(id) {
    fetch(`/api/emergency/${id}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('emergencyModalTitle').innerText = "Edit Emergency Contact";

            document.getElementById('emergency_id').value = data.id;
            document.getElementById('emergency_client_id').value = data.contact_id;

            document.getElementById('emergency_name').value = data.name;
            document.getElementById('emergency_relationship').value = data.relationship ?? "";
            document.getElementById('emergency_phone').value = data.phone ?? "";
            document.getElementById('emergency_contacted').value = data.contacted ? "1" : "0";

            new bootstrap.Modal(document.getElementById('emergencyModal')).show();
        });
}


/* ------------------------------------------------------ */
/* SAVE EMERGENCY CONTACT                                 */
/* ------------------------------------------------------ */
document.getElementById('emergencyForm').addEventListener('submit', function (e) {
    e.preventDefault();

    let id = document.getElementById('emergency_id').value;
    let clientId = document.getElementById('emergency_client_id').value;

    let payload = {
        name: document.getElementById('emergency_name').value,
        relationship: document.getElementById('emergency_relationship').value,
        phone: document.getElementById('emergency_phone').value,
        contacted: document.getElementById('emergency_contacted').value,
        _token: "{{ csrf_token() }}"
    };

    let url = id 
        ? `/book/${clientId}/emergency/${id}` 
        : `/book/${clientId}/emergency`;

    let method = id ? "PUT" : "POST";

    fetch(url, {
        method: method,
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(() => {
        bootstrap.Modal.getInstance(document.getElementById('emergencyModal')).hide();
        loadBookPanel(`/book/${clientId}`);
    });
});


/* ------------------------------------------------------ */
/* DELETE EMERGENCY CONTACT                               */
/* ------------------------------------------------------ */
function deleteEmergency(clientId, id) {
    if (!confirm("Delete emergency contact?")) return;

    fetch(`/book/${clientId}/emergency/${id}`, {
        method: "DELETE",
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        }
    })
    .then(res => res.json())
    .then(() => {
        loadBookPanel(`/book/${clientId}`);
    });
}

</script>
@endpush

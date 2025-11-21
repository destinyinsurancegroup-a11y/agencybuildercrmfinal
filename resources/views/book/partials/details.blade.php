<div class="p-4" data-client-id="{{ $client->id }}">

    <div class="card shadow-sm border-0 p-4">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-start mb-3">
            <h1 class="fw-bold mb-0" style="font-size: 32px;">
                {{ trim($client->first_name . ' ' . $client->last_name) ?: 'Unnamed Client' }}
            </h1>

            <button
                type="button"
                class="btn-gold"
                data-edit-url="{{ route('book.edit.panel', $client->id) }}"
                onclick="loadBookPanel(this.dataset.editUrl)"
            >
                Edit
            </button>
        </div>

        <hr>

        {{-- SUMMARY --}}
        <div class="row mb-4">
            <div class="col-md-6">
                <p><strong>Email:</strong> {{ $client->email ?: '—' }}</p>
                <p><strong>Phone:</strong> {{ $client->phone ?: '—' }}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Contact Type:</strong> Book of Business</p>
                <p><strong>Policy Type:</strong> {{ $client->policy_type ?: '—' }}</p>
            </div>
        </div>

        {{-- TABS --}}
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#detailsTab">Details</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#notesTab">Notes</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#docsTab">Documents</a>
            </li>
        </ul>

        <div class="tab-content">

            {{-- DETAILS TAB --}}
            <div class="tab-pane fade show active" id="detailsTab">

                {{-- BASIC INFORMATION --}}
                <h5 class="fw-bold mb-3">Basic Information</h5>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <p>
                            <strong>Date of Birth:</strong>
                            @if($client->date_of_birth)
                                {{ $client->date_of_birth->format('m/d/Y') }}
                            @else
                                —
                            @endif
                        </p>
                        <p>
                            <strong>Age:</strong>
                            {{ $client->age ?? '—' }}
                        </p>
                        <p>
                            <strong>Anniversary:</strong>
                            @if($client->anniversary)
                                {{ $client->anniversary->format('m/d/Y') }}
                            @else
                                —
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Email:</strong> {{ $client->email ?: '—' }}</p>
                        <p><strong>Phone:</strong> {{ $client->phone ?: '—' }}</p>
                    </div>
                </div>

                {{-- ADDRESS --}}
                <div class="mb-4">
                    <p><strong>Address</strong></p>
                    @if($client->address_line1 || $client->city || $client->state || $client->postal_code)
                        <p class="mb-0">
                            {{ $client->address_line1 }}<br>
                            @if($client->address_line2) {{ $client->address_line2 }}<br> @endif
                            {{ $client->city }} {{ $client->state }} {{ $client->postal_code }}
                        </p>
                    @else
                        <p class="text-muted mb-0">No address available.</p>
                    @endif
                </div>

                <hr>

                {{-- POLICY INFORMATION --}}
                <h5 class="fw-bold mb-3">Policy Information</h5>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <p><strong>Carrier:</strong><br>{{ $client->carrier ?? '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Policy Type:</strong><br>{{ $client->policy_type ?? '—' }}</p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Face Amount:</strong><br>
                            {{ $client->face_amount ? '$' . number_format($client->face_amount, 2) : '—' }}
                        </p>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <p><strong>Monthly Premium:</strong><br>
                            {{ $client->premium_amount ? '$' . number_format($client->premium_amount, 2) : '—' }}
                        </p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Issue Date:</strong><br>
                            @if($client->policy_issue_date)
                                {{ $client->policy_issue_date->format('m/d/Y') }}
                            @else
                                —
                            @endif
                        </p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Monthly Due Date:</strong><br>
                            {{ $client->premium_due_text ?? ($client->premium_due_date
                                ? $client->premium_due_date->format('m/d/Y')
                                : '—') }}
                        </p>
                    </div>
                </div>

                <hr>

                {{-- BENEFICIARIES --}}
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="fw-bold mb-0">Beneficiaries</h5>
                        <button
                            type="button"
                            class="btn-gold btn-sm"
                            id="add-beneficiary-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#beneficiaryModal"
                        >
                            + Add Beneficiary
                        </button>
                    </div>

                    @php
                        $beneficiaries = method_exists($client, 'beneficiaries')
                            ? $client->beneficiaries
                            : collect();
                    @endphp

                    @if($beneficiaries->count())
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Relationship</th>
                                        <th>Phone</th>
                                        <th>Contacted?</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($beneficiaries as $b)
                                        <tr
                                            data-beneficiary-id="{{ $b->id }}"
                                            data-name="{{ $b->name }}"
                                            data-relationship="{{ $b->relationship }}"
                                            data-phone="{{ $b->phone }}"
                                            data-contacted="{{ $b->contacted ? 1 : 0 }}"
                                        >
                                            <td>{{ $b->name }}</td>
                                            <td>{{ $b->relationship ?: '—' }}</td>
                                            <td>{{ $b->phone ?: '—' }}</td>
                                            <td>{{ $b->contacted ? 'Yes' : 'No' }}</td>
                                            <td class="text-end">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-secondary me-1 edit-beneficiary-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#beneficiaryModal"
                                                >
                                                    Edit
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-danger delete-beneficiary-btn"
                                                >
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No beneficiaries recorded.</p>
                    @endif
                </div>

                <hr>

                {{-- EMERGENCY CONTACTS --}}
                <div class="mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="fw-bold mb-0">Emergency Contacts</h5>
                        <button
                            type="button"
                            class="btn-gold btn-sm"
                            id="add-emergency-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#emergencyModal"
                        >
                            + Add Emergency Contact
                        </button>
                    </div>

                    @php
                        $emergencyContacts = method_exists($client, 'emergencyContacts')
                            ? $client->emergencyContacts
                            : collect();
                    @endphp

                    @if($emergencyContacts->count())
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Relationship</th>
                                        <th>Phone</th>
                                        <th>Contacted?</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($emergencyContacts as $c)
                                        <tr
                                            data-emergency-id="{{ $c->id }}"
                                            data-name="{{ $c->name }}"
                                            data-relationship="{{ $c->relationship }}"
                                            data-phone="{{ $c->phone }}"
                                            data-contacted="{{ $c->contacted ? 1 : 0 }}"
                                        >
                                            <td>{{ $c->name }}</td>
                                            <td>{{ $c->relationship ?: '—' }}</td>
                                            <td>{{ $c->phone ?: '—' }}</td>
                                            <td>{{ $c->contacted ? 'Yes' : 'No' }}</td>
                                            <td class="text-end">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-secondary me-1 edit-emergency-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#emergencyModal"
                                                >
                                                    Edit
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-danger delete-emergency-btn"
                                                >
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No emergency contacts recorded.</p>
                    @endif
                </div>

            </div>

            {{-- NOTES TAB --}}
            <div class="tab-pane fade" id="notesTab">
                <h5 class="fw-bold mb-3">Notes</h5>
                <p>{{ $client->notes ?: 'No notes added.' }}</p>
            </div>

            {{-- DOCUMENTS TAB --}}
            <div class="tab-pane fade" id="docsTab">
                <h5 class="fw-bold mb-3">Documents</h5>
                <p class="text-muted mb-0">Document uploads coming soon…</p>
            </div>

        </div>

    </div>

</div>

{{-- ===========================
     MODAL: BENEFICIARY
=========================== --}}
<div class="modal fade" id="beneficiaryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form
            class="modal-content"
            id="beneficiary-form"
            data-store-url="{{ route('book.beneficiaries.store', $client->id) }}"
            data-update-base="{{ url('/book/'.$client->id.'/beneficiaries') }}"
            data-delete-base="{{ url('/book/'.$client->id.'/beneficiaries') }}"
        >
            @csrf
            <input type="hidden" name="_method" value="POST">
            <input type="hidden" name="beneficiary_id" id="beneficiary_id">

            <div class="modal-header bg-black text-gold">
                <h5 class="modal-title" id="beneficiaryModalTitle">Add Beneficiary</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Name</label>
                    <input type="text" name="name" id="beneficiary_name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Relationship</label>
                    <input type="text" name="relationship" id="beneficiary_relationship" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text" name="phone" id="beneficiary_phone" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Contacted?</label>
                    <select name="contacted" id="beneficiary_contacted" class="form-select">
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

{{-- ===========================
     MODAL: EMERGENCY CONTACT
=========================== --}}
<div class="modal fade" id="emergencyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form
            class="modal-content"
            id="emergency-form"
            data-store-url="{{ route('book.emergency.store', $client->id) }}"
            data-update-base="{{ url('/book/'.$client->id.'/emergency-contacts') }}"
            data-delete-base="{{ url('/book/'.$client->id.'/emergency-contacts') }}"
        >
            @csrf
            <input type="hidden" name="_method" value="POST">
            <input type="hidden" name="emergency_id" id="emergency_id">

            <div class="modal-header bg-black text-gold">
                <h5 class="modal-title" id="emergencyModalTitle">Add Emergency Contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Name</label>
                    <input type="text" name="name" id="emergency_name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Relationship</label>
                    <input type="text" name="relationship" id="emergency_relationship" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text" name="phone" id="emergency_phone" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Contacted?</label>
                    <select name="contacted" id="emergency_contacted" class="form-select">
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

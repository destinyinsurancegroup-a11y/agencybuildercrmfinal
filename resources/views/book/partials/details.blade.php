<div class="p-4">

    <div class="card shadow-sm border-0 p-4">

        {{-- ===========================
             HEADER
        ============================ --}}
        <div class="d-flex justify-content-between align-items-start mb-3">
            <h1 class="fw-bold mb-0" style="font-size: 32px;">
                {{ trim($client->first_name . ' ' . $client->last_name) ?: 'Unnamed Client' }}
            </h1>

            <!-- TOP RIGHT EDIT BUTTON (AJAX) -->
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

        {{-- ===========================
             SUMMARY ROW (EMAIL / PHONE / TYPE)
        ============================ --}}
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

        {{-- ===========================
             TABS
        ============================ --}}
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

            {{-- ===========================
                 TAB 1: DETAILS
                 (Basic Info + Policy + Beneficiaries + Emergency Contacts)
            ============================ --}}
            <div class="tab-pane fade show active" id="detailsTab">

                {{-- 1. BASIC INFORMATION --}}
                <h5 class="fw-bold mb-3">Basic Information</h5>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <p>
                            <strong>Date of Birth:</strong>
                            @if($client->date_of_birth)
                                {{ \Carbon\Carbon::parse($client->date_of_birth)->format('m/d/Y') }}
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
                            @if(!empty($client->anniversary))
                                {{ \Carbon\Carbon::parse($client->anniversary)->format('m/d/Y') }}
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

                {{-- 2. POLICY INFORMATION --}}
                <h5 class="fw-bold mb-3">Policy Information</h5>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <p>
                            <strong>Carrier:</strong><br>
                            {{ $client->carrier ?? '—' }}
                        </p>
                    </div>

                    <div class="col-md-4">
                        <p>
                            <strong>Policy Type:</strong><br>
                            {{ $client->policy_type ?? '—' }}
                        </p>
                    </div>

                    <div class="col-md-4">
                        <p>
                            <strong>Face Amount:</strong><br>
                            {{ $client->face_amount ? '$' . number_format($client->face_amount, 2) : '—' }}
                        </p>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <p>
                            <strong>Monthly Premium:</strong><br>
                            {{ $client->premium_amount ? '$' . number_format($client->premium_amount, 2) : '—' }}
                        </p>
                    </div>

                    <div class="col-md-4">
                        <p>
                            <strong>Issue Date:</strong><br>
                            @if(!empty($client->policy_issue_date))
                                {{ \Carbon\Carbon::parse($client->policy_issue_date)->format('m/d/Y') }}
                            @else
                                —
                            @endif
                        </p>
                    </div>

                    <div class="col-md-4">
                        <p>
                            <strong>Monthly Due Date:</strong><br>
                            {{-- You can store text like "3rd" or "2nd Wednesday" in a string column --}}
                            {{ $client->billing_cycle_text ?? ($client->premium_due_date
                                ? \Carbon\Carbon::parse($client->premium_due_date)->format('m/d/Y')
                                : '—') }}
                        </p>
                    </div>
                </div>

                <hr>

                {{-- 3. BENEFICIARIES --}}
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="fw-bold mb-0">Beneficiaries</h5>
                        <button
                            type="button"
                            class="btn-gold btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#addBeneficiaryModal"
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
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Relationship</th>
                                        <th>Phone</th>
                                        <th>Contacted?</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($beneficiaries as $b)
                                        <tr>
                                            <td>{{ $b->name }}</td>
                                            <td>{{ $b->relationship ?: '—' }}</td>
                                            <td>{{ $b->phone ?: '—' }}</td>
                                            <td>{{ $b->contacted ? 'Yes' : 'No' }}</td>
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

                {{-- 4. EMERGENCY CONTACTS --}}
                <div class="mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="fw-bold mb-0">Emergency Contacts</h5>
                        <button
                            type="button"
                            class="btn-gold btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#addEmergencyContactModal"
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
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Relationship</th>
                                        <th>Phone</th>
                                        <th>Contacted?</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($emergencyContacts as $c)
                                        <tr>
                                            <td>{{ $c->name }}</td>
                                            <td>{{ $c->relationship ?: '—' }}</td>
                                            <td>{{ $c->phone ?: '—' }}</td>
                                            <td>{{ $c->contacted ? 'Yes' : 'No' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No emergency contacts recorded.</p>
                    @endif
                </div>

            </div> {{-- /#detailsTab --}}

            {{-- ===========================
                 TAB 2: NOTES
            ============================ --}}
            <div class="tab-pane fade" id="notesTab">
                <h5 class="fw-bold mb-3">Notes</h5>

                {{-- This is using the legacy "notes" column.
                     Later we can wire this to the new notes table like Contacts/Leads --}}
                <p>{{ $client->notes ?: 'No notes added.' }}</p>
            </div>

            {{-- ===========================
                 TAB 3: DOCUMENTS
            ============================ --}}
            <div class="tab-pane fade" id="docsTab">
                <h5 class="fw-bold mb-3">Documents</h5>

                @php
                    $documents = method_exists($client, 'documents')
                        ? $client->documents
                        : collect();
                @endphp

                @if($documents->count())
                    <ul class="mb-0">
                        @foreach($documents as $doc)
                            <li>
                                <a href="{{ $doc->url ?? '#' }}" target="_blank">
                                    {{ $doc->original_name ?? 'Document' }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted mb-0">No documents uploaded.</p>
                @endif
            </div>

        </div> {{-- /.tab-content --}}

    </div>

</div>

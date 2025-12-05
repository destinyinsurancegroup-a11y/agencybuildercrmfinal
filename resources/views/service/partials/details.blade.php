<style>
    .card.p-4 { padding: 1.25rem !important; }
    h4.text-gold { margin-top: 0.5rem !important; margin-bottom: 0.5rem !important; }
    .row.mb-4 { margin-bottom: 0.75rem !important; }
    .card hr { margin: 0.75rem 0 !important; }
    .card p { margin-bottom: 0.25rem !important; }
    #beneficiaries-list .p-3,
    #emergency-list .p-3 {
        padding: 0.65rem !important;
        margin-bottom: 0.5rem !important;
    }
    .p-4 { padding: 1.25rem !important; }

    /* Service notes: structurally same as Leads/Contacts */
    .service-note-body {
        text-align: left;
        white-space: pre-wrap;
    }
</style>

<div class="p-4">
    <div class="card shadow-sm border-0 p-4">

        <!-- HEADER WITH STATUS BUTTONS -->
        <div class="d-flex justify-content-between align-items-start mb-3">

            <div>
                <h1 class="fw-bold" style="font-size: 32px; margin-bottom:10px;">
                    {{ $client->first_name }} {{ $client->last_name }}
                </h1>

                {{-- CURRENT SERVICE STATUS BADGE (if any) --}}
                @if($client->service_status || $client->service_archived_at)
                    <div class="mb-2">
                        @php
                            $status = $client->service_status;
                        @endphp

                        @if(in_array($status, ['Saved', 'Back on Books']))
                            <span class="badge bg-success">
                                {{ $status ?? 'Saved' }}
                            </span>
                        @elseif(in_array($status, ['Not Interested', 'Cancelled']))
                            <span class="badge bg-danger">
                                {{ $status }}
                            </span>
                        @elseif($client->service_archived_at)
                            <span class="badge bg-secondary">
                                Archived
                            </span>
                        @endif

                        @if($client->service_archived_at)
                            <span class="text-muted small ms-2">
                                Archived on {{ \Carbon\Carbon::parse($client->service_archived_at)->format('m/d/Y') }}
                            </span>
                        @endif
                    </div>
                @endif

                <!-- ACTION BUTTONS (ONLY THREE) -->
                <div class="d-flex flex-wrap gap-2">

                    {{-- FOLLOW UP (always available) --}}
                    <a href="{{ route('service.follow-up', $client->id) }}"
                       class="btn btn-sm"
                       style="background:#f0ad4e; color:black; font-weight:600; border-radius:6px;">
                        Follow Up
                    </a>

                    @if(is_null($client->service_archived_at))
                        {{-- SAVED (Green) --}}
                        <form action="{{ route('service.saved', $client->id) }}"
                              method="POST"
                              class="d-inline">
                            @csrf
                            <button type="submit"
                                    class="btn btn-sm"
                                    style="background:#28a745; color:white; font-weight:600; border-radius:6px;"
                                    onclick="return confirm('Mark this service as Saved and archive it?');">
                                Saved
                            </button>
                        </form>

                        {{-- NOT INTERESTED (Red) --}}
                        <form action="{{ route('service.not-interested', $client->id) }}"
                              method="POST"
                              class="d-inline">
                            @csrf
                            <button type="submit"
                                    class="btn btn-sm"
                                    style="background:#dc3545; color:white; font-weight:600; border-radius:6px;"
                                    onclick="return confirm('Mark this service as Not Interested and archive it?');">
                                Not Interested
                            </button>
                        </form>
                    @endif

                </div>
            </div>

            <button
                class="btn-gold"
                data-edit-url="{{ route('service.edit.panel', $client->id) }}"
                onclick="loadServicePanel(this.dataset.editUrl)"
            >
                Edit
            </button>
        </div>

        <hr>

        <!-- BASIC INFORMATION -->
        <h4 class="text-gold fw-bold mb-3">Basic Information</h4>

        <div class="row mb-4">
            <div class="col-md-6">
                <p><strong>Email:</strong> {{ $client->email ?: '—' }}</p>
                <p><strong>Phone:</strong> {{ $client->phone ?: '—' }}</p>
                <p><strong>Date of Birth:</strong> {{ $client->date_of_birth?->format('m/d/Y') ?: '—' }}</p>
                <p><strong>Age:</strong> {{ $client->age ?? '—' }}</p>
            </div>

            <div class="col-md-6">
                <p><strong>Anniversary:</strong> {{ $client->anniversary?->format('m/d/Y') ?: '—' }}</p>
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

        <!-- POLICY INFORMATION -->
        <h4 class="text-gold fw-bold mb-3">Policy Information</h4>

        <div class="row mb-4">
            <div class="col-md-6">
                <p><strong>Carrier:</strong> {{ $client->carrier ?: '—' }}</p>
                <p><strong>Policy Type:</strong> {{ $client->policy_type ?: '—' }}</p>
                <p><strong>Face Amount:</strong>
                    {{ $client->face_amount ? '$'.number_format($client->face_amount, 2) : '—' }}
                </p>
                <p><strong>Monthly Premium:</strong>
                    {{ $client->premium_amount ? '$'.number_format($client->premium_amount, 2) : '—' }}
                </p>
            </div>

            <div class="col-md-6">
                <p><strong>Issue Date:</strong> {{ $client->policy_issue_date?->format('m/d/Y') ?: '—' }}</p>
                <p><strong>Monthly Due (Text):</strong> {{ $client->premium_due_text ?: '—' }}</p>
                <p><strong>Due Date (Calendar):</strong> {{ $client->premium_due_date?->format('m/d/Y') ?: '—' }}</p>
            </div>
        </div>

        <hr>

        <!-- BENEFICIARIES -->
        <h4 class="text-gold fw-bold mb-3">Beneficiaries</h4>

        <div id="beneficiaries-list">
            @forelse ($client->beneficiaries as $b)
                <div class="border rounded p-3 mb-2">
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
            @empty
                <p class="text-muted">No beneficiaries added.</p>
            @endforelse
        </div>

        <hr>

        <!-- EMERGENCY CONTACTS -->
        <h4 class="text-gold fw-bold mb-3">Emergency Contacts</h4>

        <div id="emergency-list">
            @forelse ($client->emergencyContacts as $ec)
                <div class="border rounded p-3 mb-2">
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
            @empty
                <p class="text-muted">No emergency contacts added.</p>
            @endforelse
        </div>

    </div> {{-- end card --}}

    {{-- =========================================================
         STAND-ALONE NOTES SECTION (OUTSIDE CARD)
       ========================================================= --}}
    <div id="service-notes-wrapper" class="mt-4">
        <h4 class="text-gold fw-bold mb-3">Notes</h4>

        {{-- NEW NOTE FORM --}}
        <div class="mb-3">
            <textarea id="new_note_body"
                      class="form-control"
                      rows="2"
                      placeholder="Write a new note..."></textarea>

            <button class="btn-gold mt-2" onclick="saveServiceNote({{ $client->id }})">
                Add Note
            </button>
        </div>

        {{-- EXISTING NOTES – same structure as Leads/Contacts --}}
        <div id="service-notes-list">
            @php
                $notes = $client->allNotes ?? $client->notes ?? collect();
                $notes = $notes->sortByDesc('created_at');
            @endphp

            @forelse ($notes as $note)
                <div class="border rounded p-2 mb-2 text-start" id="note-{{ $note->id }}">
                    <div class="small text-muted mb-1">
                        {{ optional($note->created_at)->format('m/d/Y h:i A') }}
                    </div>

                    <div class="service-note-body mb-2">
                        {{ $note->note ?? $note->body }}
                    </div>

                    <div>
                        <button class="btn btn-sm btn-outline-secondary"
                                onclick="editServiceNote({{ $client->id }}, {{ $note->id }})">
                            Edit
                        </button>

                        <button class="btn btn-sm btn-outline-danger"
                                onclick="deleteServiceNote({{ $client->id }}, {{ $note->id }})">
                            Delete
                        </button>
                    </div>
                </div>
            @empty
                <p class="text-muted small mb-0">No notes yet.</p>
            @endforelse
        </div>
    </div>

</div>

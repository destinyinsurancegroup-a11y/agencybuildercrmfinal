<div class="p-4" style="background:#ffffff; min-height:100%; border-radius:18px;">

    <!-- =========================
         HEADER: NAME + EDIT + ATTACHMENTS
         ========================= -->
    <div class="d-flex justify-content-between align-items-start mb-4">

        <div>
            <!-- Client Name -->
            <h2 class="text-gold fw-bold mb-2" style="font-size:26px;">
                {{ $client->name }}
            </h2>

            <!-- Attachments List -->
            <div class="mt-2">
                <strong class="d-block mb-1" style="font-size:14px;">Attachments</strong>

                @if($client->documents->count())
                    <ul class="ps-3" style="font-size:14px;">
                        @foreach($client->documents as $doc)
                            <li>
                                <a href="{{ $doc->url }}" target="_blank" class="text-decoration-underline">
                                    {{ $doc->original_name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-muted" style="font-size:13px;">No documents uploaded.</div>
                @endif
            </div>
        </div>

        <!-- Edit Button -->
        <a href="{{ route('book.edit', $client->id) }}" class="btn-gold" style="font-size:12px;">
            Edit Client
        </a>

    </div>



    <!-- =========================
         CLIENT INFORMATION
         ========================= -->
    <div class="mb-4">
        <h5 class="fw-bold text-dark mb-3">Client Information</h5>

        <div class="row g-3" style="font-size:14px;">
            <div class="col-md-6">
                <strong>Date of Birth:</strong><br>
                {{ optional($client->date_of_birth)->format('m/d/Y') }}
            </div>

            <div class="col-md-6">
                <strong>Age:</strong><br>
                {{ $client->age }}
            </div>

            <div class="col-md-6">
                <strong>Anniversary:</strong><br>
                {{ optional($client->anniversary)->format('m/d/Y') }}
            </div>

            <div class="col-md-6">
                <strong>Phone:</strong><br>
                {{ $client->phone }}
            </div>

            <div class="col-md-6">
                <strong>Email:</strong><br>
                {{ $client->email }}
            </div>

            <div class="col-md-12">
                <strong>Address:</strong><br>
                {{ $client->address_line1 }}<br>
                @if($client->address_line2)
                    {{ $client->address_line2 }}<br>
                @endif
                {{ $client->city }}, {{ $client->state }} {{ $client->postal_code }}
            </div>
        </div>
    </div>



    <!-- =========================
         POLICY INFORMATION
         ========================= -->
    <div class="mb-4">
        <h5 class="fw-bold text-dark mb-3">Policy Information</h5>

        <div class="row g-3" style="font-size:14px;">
            <div class="col-md-6">
                <strong>Policy Type:</strong><br>
                {{ $client->policy_type }}
            </div>

            <div class="col-md-6">
                <strong>Face Amount:</strong><br>
                {{ $client->face_amount }}
            </div>

            <div class="col-md-6">
                <strong>Premium Amount:</strong><br>
                {{ $client->premium_amount }}
            </div>

            <div class="col-md-6">
                <strong>Recurring Due Date:</strong><br>
                {{ optional($client->recurring_due_date)->format('m/d/Y') }}
            </div>

            <div class="col-md-6">
                <strong>Policy Issue Date:</strong><br>
                {{ optional($client->policy_issue_date)->format('m/d/Y') }}
            </div>
        </div>
    </div>



    <!-- =========================
         BENEFICIARIES & EMERGENCY CONTACTS
         ========================= -->
    <div class="mb-4">
        <h5 class="fw-bold text-dark mb-3">Beneficiaries & Emergency Contacts</h5>

        <!-- Beneficiaries -->
        <div class="mb-3">
            <strong>Beneficiaries:</strong><br>
            @forelse($client->beneficiaries as $b)
                <div style="font-size:14px;">
                    {{ $b->name }} — {{ $b->relationship }} — {{ $b->phone }}
                </div>
            @empty
                <div class="text-muted" style="font-size:13px;">None listed.</div>
            @endforelse
        </div>

        <!-- Emergency Contacts -->
        <div>
            <strong>Emergency Contacts:</strong><br>
            @forelse($client->emergencyContacts as $c)
                <div style="font-size:14px;">
                    {{ $c->name }} — {{ $c->relationship }} — {{ $c->phone }}
                </div>
            @empty
                <div class="text-muted" style="font-size:13px;">None listed.</div>
            @endforelse
        </div>
    </div>



    <!-- =========================
         NOTES (ONLY EDITABLE SECTION)
         ========================= -->
    <div>
        <h5 class="fw-bold text-dark mb-3">Notes</h5>

        <!-- Add Note -->
        <form 
            id="add-note-form"
            data-client-id="{{ $client->id }}"
            action="{{ route('book.notes.store', $client->id) }}"
            method="POST"
        >
            @csrf

            <textarea 
                name="body"
                class="form-control mb-2"
                rows="3"
                placeholder="Add a note..."
                required
            ></textarea>

            <button type="submit" class="btn-gold" style="font-size:12px;">
                Save Note
            </button>
        </form>

        <!-- Notes List -->
        <div id="notes-list" class="mt-3">
            @forelse($client->notes as $note)
                <div class="border rounded p-2 mb-2" style="font-size:14px;">

                    <div class="text-muted" style="font-size:12px;">
                        {{ $note->created_at->format('m/d/Y h:i A') }}
                    </div>

                    <div class="note-body" data-note-id="{{ $note->id }}">
                        {{ $note->body }}
                    </div>

                    <!-- Inline edit button (allowed in view panel) -->
                    <button 
                        class="btn btn-sm btn-outline-secondary mt-1 edit-note-btn"
                        data-note-id="{{ $note->id }}"
                        style="font-size:11px;"
                    >
                        Edit
                    </button>

                </div>
            @empty
                <div class="text-muted" style="font-size:13px;">No notes yet.</div>
            @endforelse
        </div>
    </div>

</div>

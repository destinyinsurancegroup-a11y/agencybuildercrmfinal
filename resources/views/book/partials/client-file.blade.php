<div class="p-4" style="background:#ffffff; border-radius:18px; min-height:100%;">

    @php
        $clientName = $client->full_name
            ?? trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? ''));
    @endphp

    <!-- =========================
         HEADER: NAME + ATTACHMENTS
         ========================= -->
    <div class="d-flex justify-content-between align-items-start mb-4">

        <div>
            <h2 class="text-gold fw-bold mb-2" style="font-size:26px;">
                {{ $clientName }}
            </h2>

            <!-- Attachments -->
            <div class="mt-2">
                <strong class="d-block mb-1" style="font-size:14px;">Attachments</strong>

                @php
                    $docs = method_exists($client, 'documents')
                        ? ($client->documents ?? collect())
                        : collect();
                @endphp

                @if($docs->count())
                    <ul class="ps-3" style="font-size:14px;">
                        @foreach($docs as $doc)
                            <li>
                                <a href="{{ $doc->url }}" target="_blank" class="text-decoration-underline">
                                    {{ $doc->original_name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-muted" style="font-size:13px;">
                        No documents uploaded.
                    </div>
                @endif
            </div>
        </div>

        <button type="button" class="btn-gold" style="font-size:12px;">
            Edit Client
        </button>
    </div>



    <!-- =========================
         CLIENT INFORMATION
         ========================= -->
    <div class="mb-4">
        <h5 class="fw-bold text-dark mb-3">Client Information</h5>

        <div class="row g-3" style="font-size:14px;">
            <div class="col-md-6">
                <strong>Date of Birth:</strong><br>
                {{ optional($client->date_of_birth)->format('m/d/Y') ?? '—' }}
            </div>

            <div class="col-md-6">
                <strong>Age:</strong><br>
                {{ $client->age ?? '—' }}
            </div>

            <div class="col-md-6">
                <strong>Anniversary:</strong><br>
                {{ optional($client->anniversary)->format('m/d/Y') ?? '—' }}
            </div>

            <div class="col-md-6">
                <strong>Phone:</strong><br>
                {{ $client->phone ?? '—' }}
            </div>

            <div class="col-md-6">
                <strong>Email:</strong><br>
                {{ $client->email ?? '—' }}
            </div>

            <div class="col-md-12">
                <strong>Address:</strong><br>

                @if(
                    !empty($client->address_line1) ||
                    !empty($client->city) ||
                    !empty($client->state) ||
                    !empty($client->postal_code)
                )
                    {{ $client->address_line1 ?? '' }}<br>
                    @if(!empty($client->address_line2))
                        {{ $client->address_line2 }}<br>
                    @endif
                    {{ $client->city ?? '' }} {{ $client->state ?? '' }} {{ $client->postal_code ?? '' }}
                @else
                    {{ $client->address ?? 'No address on file.' }}
                @endif
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
                {{ $client->policy_type ?? '—' }}
            </div>

            <div class="col-md-6">
                <strong>Face Amount:</strong><br>
                {{ $client->face_amount ?? '—' }}
            </div>

            <div class="col-md-6">
                <strong>Premium Amount:</strong><br>
                {{ $client->premium_amount ?? '—' }}
            </div>

            <div class="col-md-6">
                <strong>Recurring Due Date:</strong><br>
                {{ optional($client->recurring_due_date)->format('m/d/Y') ?? '—' }}
            </div>

            <div class="col-md-6">
                <strong>Policy Issue Date:</strong><br>
                {{ optional($client->policy_issue_date)->format('m/d/Y') ?? '—' }}
            </div>
        </div>
    </div>



    <!-- =========================
         BENEFICIARIES & EMERGENCY CONTACTS
         ========================= -->
    <div class="mb-4">
        <h5 class="fw-bold text-dark mb-3">Beneficiaries & Emergency Contacts</h5>

        @php
            $beneficiaries = method_exists($client, 'beneficiaries')
                ? ($client->beneficiaries ?? collect())
                : collect();

            $emergency = method_exists($client, 'emergencyContacts')
                ? ($client->emergencyContacts ?? collect())
                : collect();
        @endphp

        <!-- Beneficiaries -->
        <div class="mb-3">
            <strong>Beneficiaries:</strong><br>

            @forelse(($beneficiaries ?? []) as $b)
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

            @forelse(($emergency ?? []) as $c)
                <div style="font-size:14px;">
                    {{ $c->name }} — {{ $c->relationship }} — {{ $c->phone }}
                </div>
            @empty
                <div class="text-muted" style="font-size:13px;">None listed.</div>
            @endforelse
        </div>
    </div>



    <!-- =========================
         NOTES (EDITABLE INLINE)
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

        <!-- List of Notes -->
        @php
            $notes = method_exists($client, 'notes')
                ? ($client->notes ?? collect())
                : collect();
        @endphp

        <div id="notes-list" class="mt-3">

            @forelse(($notes ?? []) as $note)
                <div class="border rounded p-2 mb-2" style="font-size:14px;">

                    <div class="text-muted" style="font-size:12px;">
                        {{ $note->created_at->format('m/d/Y h:i A') }}
                    </div>

                    <div class="note-body" data-note-id="{{ $note->id }}">
                        {{ $note->body }}
                    </div>

                    <button 
                        type="button"
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

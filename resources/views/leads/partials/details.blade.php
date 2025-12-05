<div class="p-4">

    {{-- MAIN LEAD CARD --}}
    <div class="card shadow-sm border-0 p-4">

        <!-- BIG NAME HEADER -->
        <h1 class="fw-bold mb-3" style="font-size: 32px;">
            {{ $contact->first_name }} {{ $contact->last_name }}
        </h1>

        <!-- Hidden helpers for JS (for future actions if needed) -->
        <input type="hidden" id="leadContactId" value="{{ $contact->id }}">
        <input type="hidden" id="leadContactName" value="{{ $contact->full_name }}">

        <!-- DISPOSITION BUTTONS UNDER NAME -->
        <div class="d-flex gap-2 mb-4">

            {{-- SOLD: convert lead -> client/contact via regular POST --}}
            <form method="POST"
                  action="{{ route('leads.sold', $contact) }}"
                  onsubmit="return confirm('Mark this lead as SOLD and move to your Book of Business?');">
                @csrf
                <button type="submit"
                        class="btn btn-success btn-sm px-3">
                    Sold
                </button>
            </form>

            {{-- FOLLOW UP: go to Calendar screen, passing contact info --}}
            <a href="{{ url('/calendar') }}?contact_id={{ $contact->id }}&contact_name={{ urlencode($contact->full_name) }}"
               class="btn btn-warning btn-sm px-3">
                Follow Up
            </a>

            {{-- NOT INTERESTED: archive lead and remove from active list --}}
            <form method="POST"
                  action="{{ route('leads.archive', $contact) }}"
                  onsubmit="return confirm('Mark this lead as NOT INTERESTED and remove it from your active Leads list?');">
                @csrf
                <button type="submit"
                        class="btn btn-danger btn-sm px-3">
                    Not Interested
                </button>
            </form>
        </div>

        <!-- TOP RIGHT EDIT BUTTON -->
        <div class="text-end mb-3">
            <a href="{{ route('contacts.edit', $contact->id) }}" class="btn btn-gold">
                Edit
            </a>
        </div>

        <hr>

        <!-- DETAILS SECTION -->
        <div class="row mb-4">

            <div class="col-md-6">
                <p><strong>Email:</strong> {{ $contact->email ?: '—' }}</p>
                <p><strong>Phone:</strong> {{ $contact->phone ?: '—' }}</p>
                <p><strong>Age:</strong> {{ $contact->age ?: '—' }}</p>
            </div>

            <div class="col-md-6">
                <p><strong>Contact Type:</strong> Lead</p>

                <p><strong>Status:</strong>
                    <span class="badge bg-secondary">
                        {{ $contact->status ?? 'New' }}
                    </span>
                </p>

                <p><strong>Lead Received Date:</strong>
                    {{ $contact->lead_received_date ? \Carbon\Carbon::parse($contact->lead_received_date)->format('m/d/Y') : '—' }}
                </p>

                <p><strong>Lead Assigned Date:</strong>
                    {{ $contact->lead_assigned_date ? \Carbon\Carbon::parse($contact->lead_assigned_date)->format('m/d/Y') : '—' }}
                </p>
            </div>

        </div>

        <!-- ADDRESS -->
        <div class="mb-4">
            <p><strong>Address</strong></p>

            @if($contact->address_line1)
                <p>
                    {{ $contact->address_line1 }}<br>
                    @if($contact->address_line2) {{ $contact->address_line2 }}<br> @endif
                    {{ $contact->city }} {{ $contact->state }} {{ $contact->postal_code }}
                </p>
            @else
                <p class="text-muted">No address available.</p>
            @endif
        </div>

        <hr>

    </div>{{-- /card --}}

    {{-- =========================================================
         STAND-ALONE NOTES SECTION (OUTSIDE CARD, LIKE BOOK/SERVICE)
       ========================================================= --}}
    <div class="mt-4">

        <!-- NOTES SECTION (styled like All Contacts) -->
        <h5 class="fw-bold mb-3">Notes</h5>

        {{-- NEW NOTE FORM (still uses JS to POST to /leads/{id}/notes) --}}
        <div class="mb-3">
            <textarea
                id="lead_new_note_body"
                class="form-control"
                rows="3"
                style="border-radius:10px; border:1px solid #d1d5db; font-size:14px;"
                placeholder="Write a new note..."
            ></textarea>

            <button type="button"
                    class="btn mt-3"
                    style="
                        background:#c9a227;
                        color:#111827;
                        padding:8px 22px;
                        border-radius:10px;
                        font-size:13px;
                        font-weight:700;
                    "
                    onclick="saveLeadNote({{ $contact->id }})">
                Add Note
            </button>
        </div>

        {{-- EXISTING NOTES (left-justified like Contacts) --}}
        <div class="mt-4">
            @php
                $notes = $contact->allNotes ?? $contact->notes ?? collect();
                $notes = $notes->sortByDesc('created_at');
            @endphp

            @forelse ($notes as $note)
                <div class="border rounded p-2 mb-2 text-start" id="lead-note-{{ $note->id }}">
                    <div class="small text-muted mb-1">
                        {{ optional($note->created_at)->format('m/d/Y g:i A') }}
                    </div>

                    <div class="mb-2">
                        {{-- DB column is "note"; fall back to "body" if older --}}
                        {{ $note->note ?? $note->body }}
                    </div>

                    <div>
                        <button class="btn btn-sm btn-outline-secondary"
                                type="button"
                                onclick="editLeadNote({{ $contact->id }}, {{ $note->id }})">
                            Edit
                        </button>

                        <button class="btn btn-sm btn-outline-danger"
                                type="button"
                                onclick="deleteLeadNote({{ $contact->id }}, {{ $note->id }})">
                            Delete
                        </button>
                    </div>
                </div>
            @empty
                <p class="text-muted small mb-0">No notes yet.</p>
            @endforelse
        </div>

    </div>{{-- /notes wrapper --}}

</div>

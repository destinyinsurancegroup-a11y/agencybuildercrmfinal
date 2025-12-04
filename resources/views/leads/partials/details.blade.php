<div class="p-4">

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

        <!-- NOTES SECTION -->
        <h4 class="fw-bold mb-3">Notes</h4>

        <!-- ADD NEW NOTE -->
        <div class="mb-3">
            <textarea id="lead_new_note_body"
                      class="form-control"
                      rows="2"
                      placeholder="Write a new note..."></textarea>

            <button type="button" class="btn btn-gold mt-2" onclick="saveLeadNote({{ $contact->id }})">
                Add Note
            </button>
        </div>

        <!-- EXISTING NOTES LIST -->
        <div id="lead-notes-list">
            @forelse ($contact->allNotes as $note)
                <div class="border rounded p-2 mb-2" id="lead-note-{{ $note->id }}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div style="white-space: pre-wrap;">
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

                    <div class="text-muted small mt-1">
                        {{ $note->created_at->format('m/d/Y h:i A') }}
                    </div>
                </div>
            @empty
                <p class="text-muted">No notes yet.</p>
            @endforelse
        </div>

    </div>

</div>

{{-- ===========================
     LEAD ACTIONS JS
=========================== --}}
<script>
    function getLeadContext() {
        return {
            id: document.getElementById('leadContactId')?.value,
            name: document.getElementById('leadContactName')?.value
        };
    }

    function saveLeadNote(contactId) {
        const bodyField = document.getElementById('lead_new_note_body');
        const body = bodyField.value.trim();
        if (!body) {
            alert("Note cannot be empty.");
            return;
        }

        fetch(`/leads/${contactId}/notes`, {
            method: 'POST',
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Accept": "application/json"
            },
            body: JSON.stringify({ body })
        })
        .then(() => {
            // mimic Book/Service behavior: just reload UI
            window.location.reload();
        })
        .catch(() => alert("Error saving note."));
    }

    function editLeadNote(contactId, noteId) {
        const existingEl = document.querySelector(`#lead-note-${noteId} div:first-child`);
        if (!existingEl) return;

        const existing = existingEl.innerText;
        const updated = prompt("Edit note:", existing);
        if (updated === null) return;

        fetch(`/leads/${contactId}/notes/${noteId}`, {
            method: 'PUT',
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Accept": "application/json"
            },
            body: JSON.stringify({ body: updated })
        })
        .then(() => {
            window.location.reload();
        })
        .catch(() => alert("Error updating note."));
    }

    function deleteLeadNote(contactId, noteId) {
        if (!confirm("Delete this note?")) return;

        fetch(`/leads/${contactId}/notes/${noteId}`, {
            method: 'DELETE',
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Accept": "application/json"
            }
        })
        .then(() => {
            window.location.reload();
        })
        .catch(() => alert("Error deleting note."));
    }
</script>

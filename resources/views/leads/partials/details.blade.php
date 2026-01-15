<div class="p-4">

    @php
        // Match Book pattern: safe display name
        $leadName = $contact->full_name
            ?? trim(($contact->first_name ?? '') . ' ' . ($contact->last_name ?? ''));
    @endphp

    {{-- MAIN LEAD CARD --}}
    <div class="card shadow-sm border-0 p-4">

        <!-- BIG NAME HEADER -->
        <h1 class="fw-bold mb-2" style="font-size: 32px;">
            {{ $contact->first_name }} {{ $contact->last_name }}
        </h1>

        <!-- ✅ Messaging buttons (Text / Email) -->
        <div class="mt-2 d-flex gap-2 flex-wrap">
            <button
                type="button"
                class="btn-outline-gold"
                style="
                    font-size:12px;
                    background: transparent;
                    color: #c9a227;
                    border: 1px solid #c9a227;
                    padding: 6px 10px;
                    font-weight: 600;
                    border-radius: 8px;
                    box-shadow: 0 4px 8px rgba(0,0,0,0.10);
                    cursor: pointer;
                    white-space: nowrap;
                "
                onclick='ABMessaging.openSms({{ (int) $contact->id }}, @json($leadName), @json($contact->phone))'
                {{ empty($contact->phone) ? 'disabled' : '' }}
                title="{{ empty($contact->phone) ? 'No phone on file' : 'Send a text' }}"
            >
                Text
            </button>

            <button
                type="button"
                class="btn-outline-gold"
                style="
                    font-size:12px;
                    background: transparent;
                    color: #c9a227;
                    border: 1px solid #c9a227;
                    padding: 6px 10px;
                    font-weight: 600;
                    border-radius: 8px;
                    box-shadow: 0 4px 8px rgba(0,0,0,0.10);
                    cursor: pointer;
                    white-space: nowrap;
                "
                onclick='ABMessaging.openEmail({{ (int) $contact->id }}, @json($leadName), @json($contact->email))'
                {{ empty($contact->email) ? 'disabled' : '' }}
                title="{{ empty($contact->email) ? 'No email on file' : 'Send an email' }}"
            >
                Email
            </button>
        </div>

        <!-- Hidden helpers for JS -->
        <input type="hidden" id="leadContactId" value="{{ $contact->id }}">
        <input type="hidden" id="leadContactName" value="{{ $contact->full_name }}">

        <!-- DISPOSITION BUTTONS UNDER NAME -->
        <div class="d-flex gap-2 mb-4 mt-3">

            {{-- SOLD --}}
            <form method="POST"
                  action="{{ route('leads.sold', $contact) }}"
                  onsubmit="return confirm('Mark this lead as SOLD and move to Book of Business?');">
                @csrf
                <button type="submit" class="btn btn-success btn-sm px-3">
                    Sold
                </button>
            </form>

            {{-- FOLLOW UP: Calendar with pre-filled fields --}}
            <a href="{{ url('/calendar') }}?contact_id={{ $contact->id }}&contact_name={{ urlencode($contact->full_name) }}"
               class="btn btn-warning btn-sm px-3">
                Follow Up
            </a>

            {{-- NOT INTERESTED --}}
            <form method="POST"
                  action="{{ route('leads.archive', $contact) }}"
                  onsubmit="return confirm('Mark this lead as NOT INTERESTED and remove it from active Leads?');">
                @csrf
                <button type="submit" class="btn btn-danger btn-sm px-3">
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

    </div>
    {{-- /MAIN LEAD CARD --}}

    {{-- =======================================
         LEAD NOTES (tenant-safe, JS powered)
       ======================================= --}}
    <div class="mt-4">

        <h5 class="fw-bold mb-3">Notes</h5>

        <!-- NEW NOTE FORM -->
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

        <!-- EXISTING NOTES -->
        <div class="mt-4" id="lead-notes-list">
            @php
                $notes = $contact->allNotes ?? $contact->notes ?? collect();
                $notes = $notes->sortByDesc('created_at');
            @endphp

            @forelse ($notes as $note)
                <div class="border rounded p-2 mb-2 text-start" id="lead-note-{{ $note->id }}">
                    <div class="small text-muted mb-1">
                        {{ optional($note->created_at)->format('m/d/Y g:i A') }}
                    </div>

                    <div class="mb-2 lead-note-text">
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

    </div>

</div>

{{-- ===========================
     LEAD NOTES JS (inline)
   =========================== --}}
<script>
    const LEAD_NOTES_CSRF = "{{ csrf_token() }}";

    function leadNotesEndpoint(contactId, noteId = null) {
        let base = `/leads/${contactId}/notes`;
        if (noteId) {
            base += `/${noteId}`;
        }
        return base;
    }

    function renderLeadNoteElement(note) {
        const wrapper = document.createElement('div');
        wrapper.className = 'border rounded p-2 mb-2 text-start';
        wrapper.id = `lead-note-${note.id}`;

        const createdAt = note.created_at_formatted || note.created_at || '';

        wrapper.innerHTML = `
            <div class="small text-muted mb-1">
                ${createdAt}
            </div>
            <div class="mb-2 lead-note-text">
                ${note.note || note.body || ''}
            </div>
            <div>
                <button class="btn btn-sm btn-outline-secondary"
                        type="button"
                        onclick="editLeadNote(${note.contact_id}, ${note.id})">
                    Edit
                </button>
                <button class="btn btn-sm btn-outline-danger"
                        type="button"
                        onclick="deleteLeadNote(${note.contact_id}, ${note.id})">
                    Delete
                </button>
            </div>
        `;

        return wrapper;
    }

    function saveLeadNote(contactId) {
        const textarea = document.getElementById('lead_new_note_body');
        const text = textarea.value.trim();
        if (!text) {
            return;
        }

        fetch(leadNotesEndpoint(contactId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': LEAD_NOTES_CSRF
            },
            body: JSON.stringify({
                note: text,
                body: text // support both column names
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data || !data.note) {
                window.location.reload();
                return;
            }

            const list = document.getElementById('lead-notes-list');

            const el = renderLeadNoteElement(data.note);
            list.prepend(el);

            textarea.value = '';
        })
        .catch(() => {
            alert('Unable to save note. Please try again.');
        });
    }

    function editLeadNote(contactId, noteId) {
        const noteEl = document.querySelector(`#lead-note-${noteId} .lead-note-text`);
        if (!noteEl) return;

        const currentText = noteEl.innerText.trim();
        const updatedText = prompt('Edit note:', currentText);
        if (updatedText === null) return;
        const trimmed = updatedText.trim();
        if (!trimmed) return;

        fetch(leadNotesEndpoint(contactId, noteId), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': LEAD_NOTES_CSRF
            },
            body: JSON.stringify({
                note: trimmed,
                body: trimmed
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data || !data.note) {
                window.location.reload();
                return;
            }

            noteEl.innerText = data.note.note || data.note.body || trimmed;
        })
        .catch(() => {
            alert('Unable to update note. Please try again.');
        });
    }

    function deleteLeadNote(contactId, noteId) {
        if (!confirm('Delete this note?')) return;

        fetch(leadNotesEndpoint(contactId, noteId), {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': LEAD_NOTES_CSRF
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error();
            }
            const el = document.getElementById(`lead-note-${noteId}`);
            if (el && el.parentNode) {
                el.parentNode.removeChild(el);
            }
        })
        .catch(() => {
            alert('Unable to delete note. Please try again.');
        });
    }
</script>

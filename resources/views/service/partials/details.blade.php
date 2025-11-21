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
</style>

<div class="p-4">
    <div class="card shadow-sm border-0 p-4">

        <!-- HEADER WITH STATUS BUTTONS -->
        <div class="d-flex justify-content-between align-items-start mb-3">

            <div>
                <h1 class="fw-bold" style="font-size: 32px; margin-bottom:10px;">
                    {{ $client->first_name }} {{ $client->last_name }}
                </h1>

                <!-- STATUS BUTTONS -->
                <div class="d-flex gap-2">

                    <!-- BACK ON BOOKS (Green) -->
                    <button class="btn btn-sm"
                            style="background:#28a745; color:white; font-weight:600; border-radius:6px;">
                        Back on Books
                    </button>

                    <!-- FOLLOW UP (Yellow) -->
                    <button class="btn btn-sm"
                            style="background:#f0ad4e; color:black; font-weight:600; border-radius:6px;">
                        Follow Up
                    </button>

                    <!-- NOT INTERESTED (Red) -->
                    <button class="btn btn-sm"
                            style="background:#dc3545; color:white; font-weight:600; border-radius:6px;">
                        Not Interested
                    </button>

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

        <hr>

        <!-- NOTES -->
        <h4 class="text-gold fw-bold mb-3">Notes</h4>

        <div class="mb-3">
            <textarea id="new_note_body" class="form-control" rows="2" placeholder="Write a new note..."></textarea>

            <button class="btn-gold mt-2" onclick="saveServiceNote({{ $client->id }})">
                Add Note
            </button>
        </div>

        <div id="notes-list">
            @forelse ($client->allNotes as $note)
                <div class="border rounded p-2 mb-2" id="note-{{ $note->id }}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div style="white-space: pre-wrap;">
                            {{ $note->body }}
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


<script>
function saveServiceNote(clientId) {
    const body = document.getElementById('new_note_body').value.trim();
    if (!body) return alert("Note cannot be empty.");

    fetch(`/service/${clientId}/notes`, {
        method: 'POST',
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        },
        body: JSON.stringify({ body })
    })
    .then(() => loadServicePanel(`/service/${clientId}`));
}

function editServiceNote(clientId, noteId) {
    const existing = document.querySelector(`#note-${noteId} div:first-child`).innerText;
    const updated = prompt("Edit note:", existing);
    if (updated === null) return;

    fetch(`/service/${clientId}/notes/${noteId}`, {
        method: 'PUT',
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        },
        body: JSON.stringify({ body: updated })
    })
    .then(() => loadServicePanel(`/service/${clientId}`));
}

function deleteServiceNote(clientId, noteId) {
    if (!confirm("Delete this note?")) return;

    fetch(`/service/${clientId}/notes/${noteId}`, {
        method: 'DELETE',
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        }
    })
    .then(() => loadServicePanel(`/service/${clientId}`));
}
</script>

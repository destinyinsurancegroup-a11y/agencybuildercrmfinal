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

    /* standalone notes block below the card */
    #service-notes-wrapper {
        margin-top: 24px;
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

                <!-- ACTION BUTTONS (REORDERED: Saved, Follow Up, Not Interested) -->
                <div class="d-flex flex-wrap gap-2">

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
                    @endif

                    {{-- FOLLOW UP (always available) --}}
                    <a href="{{ route('service.follow-up', $client->id) }}"
                       class="btn btn-sm"
                       style="background:#f0ad4e; color:black; font-weight:600; border-radius:6px;">
                        Follow Up
                    </a>

                    @if(is_null($client->service_archived_at))
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

    {{-- =========================
         STAND-ALONE NOTES
       ========================= --}}
    <div id="service-notes-wrapper">
        <h4 class="text-gold fw-bold mb-3">Notes</h4>

        {{-- NEW NOTE FORM --}}
        <div class="mb-3">
            <textarea id="new_note_body"
                      class="form-control"
                      rows="2"
                      placeholder="Write a new note..."></textarea>

            {{-- keep the same onclick signature, we’ll override the function --}}
            <button class="btn-gold mt-2" onclick="saveServiceNote({{ $client->id }})">
                Add Note
            </button>
        </div>

        {{-- EXISTING NOTES --}}
        <div id="notes-list" class="mt-3">
            @php
                $notes = $client->allNotes ?? $client->notes ?? collect();
                $notes = $notes->sortByDesc('created_at');
            @endphp

            @forelse ($notes as $note)
                <div class="border rounded p-2 mb-2" id="note-{{ $note->id }}">
                    <div class="small text-muted mb-1">
                        {{ optional($note->created_at)->format('m/d/Y g:i A') }}
                    </div>
                    <div>
                        {{ $note->note ?? $note->body }}
                    </div>
                </div>
            @empty
                <p class="text-muted small mb-0">No notes yet.</p>
            @endforelse
        </div>
    </div>

</div>

{{-- ==========================================
     SERVICE NOTES JS – override saveServiceNote
     Uses same API shape as Book/Leads notes.
   ========================================== --}}
<script>
    (function () {
        const csrfToken  = "{{ csrf_token() }}";
        const storeUrl   = "{{ route('service.notes.store', $client) }}"; // POST /service/{client}/notes
        const notesList  = document.getElementById('notes-list');
        const textarea   = document.getElementById('new_note_body');

        // Override / define global function used by the button
        window.saveServiceNote = function (clientId) {
            if (!textarea) return;

            const bodyText = textarea.value.trim();
            if (!bodyText) return;

            fetch(storeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ body: bodyText })
            })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Network error');
                }
                return response.json();
            })
            .then(function (data) {
                if (!data || !data.success || !data.note) {
                    alert('Error saving note.');
                    return;
                }

                if (!notesList) return;

                const note = data.note;

                const wrapper = document.createElement('div');
                wrapper.className = 'border rounded p-2 mb-2';
                wrapper.id = 'note-' + note.id;

                // created_at from Laravel; fall back to empty string if missing
                let createdAtText = '';
                if (note.created_at) {
                    try {
                        createdAtText = new Date(note.created_at).toLocaleString();
                    } catch (e) {
                        createdAtText = note.created_at;
                    }
                }

                wrapper.innerHTML = `
                    <div class="small text-muted mb-1">
                        ${createdAtText}
                    </div>
                    <div>
                        ${note.note || note.body || ''}
                    </div>
                `;

                // Prepend so newest note is at the top
                if (notesList.firstChild) {
                    notesList.insertBefore(wrapper, notesList.firstChild);
                } else {
                    notesList.appendChild(wrapper);
                }

                textarea.value = '';
            })
            .catch(function () {
                alert('Error saving note.');
            });
        };
    })();
</script>

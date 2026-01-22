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
    #service-notes-wrapper { margin-top: 24px; }

    /* ✅ MATCH Book/Contacts/Leads button style */
    .btn-outline-gold{
        background: transparent;
        color:#c9a227;
        border:1px solid #c9a227;
        padding:6px 10px;
        font-weight:600;
        border-radius:8px;
        box-shadow:0 4px 8px rgba(0,0,0,0.10);
        font-size:12px;
        cursor:pointer;
        white-space:nowrap;
    }
    .btn-outline-gold:hover{ background: rgba(201,162,39,0.12); }
    .btn-outline-gold:disabled{ opacity:0.45; cursor:not-allowed; }

    /* ===== Attachments row (Service) — match Book ===== */
    .ab-attach-row{
        margin-top: 10px;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    .ab-attach-clip{
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        box-shadow: 0 2px 6px rgba(0,0,0,0.06);
        cursor: pointer;
    }
    .ab-attach-label{
        font-size: 13px;
        font-weight: 700;
        color: #111827;
        margin-left: 2px;
    }
    .ab-attach-chips{
        display: inline-flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        min-height: 32px;
    }
    .ab-file-empty{ font-size: 12px; color:#6b7280; }
    .ab-file-more{ font-size: 13px; color:#6b7280; padding-left: 4px; }

    /* chip container so we can place a delete button beside it */
    .ab-chip-wrap{
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid #e5e7eb;
        background: #fff;
        border-radius: 10px;
        padding: 6px 10px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.06);
    }
    .ab-file-chip{
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        color: #1f2937;
        font-size: 13px;
        line-height: 1;
        max-width: 220px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .ab-file-icon{ opacity: 0.9; }

    /* ✅ delete button */
    .ab-file-del{
        width: 20px;
        height: 20px;
        border-radius: 6px;
        border: 1px solid #ef4444;
        background: #ef4444;
        color: #fff;
        font-weight: 900;
        line-height: 18px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    .ab-file-del:hover{ filter: brightness(0.95); }
</style>

@php
    $clientName = $client->full_name ?? trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? ''));

    // ✅ Attachments (shared with Contacts + Book because it's the same contacts table)
    $attachments = $client->attachments()->latest()->get();
    $chipLimit = 3;

    // IMPORTANT: return to exact same service page (prevents “closing” when controller redirects)
    $returnTo = request()->fullUrl();
@endphp

<div class="p-4">
    <div class="card shadow-sm border-0 p-4">

        <!-- HEADER WITH STATUS BUTTONS -->
        <div class="d-flex justify-content-between align-items-start mb-3">

            <div>
                <h1 class="fw-bold" style="font-size: 32px; margin-bottom:10px;">
                    {{ $clientName }}
                </h1>

                {{-- ✅ Messaging buttons (match Book look) --}}
                <div class="mt-2 d-flex gap-2 flex-wrap">
                    <button
                        type="button"
                        class="btn-outline-gold"
                        style="font-size:12px;"
                        onclick='ABMessaging.openSms({{ (int) $client->id }}, @json($clientName), @json($client->phone))'
                        {{ empty($client->phone) ? 'disabled' : '' }}
                        title="{{ empty($client->phone) ? 'No phone on file' : 'Send a text' }}"
                    >
                        Text
                    </button>

                    <button
                        type="button"
                        class="btn-outline-gold"
                        style="font-size:12px;"
                        onclick='ABMessaging.openEmail({{ (int) $client->id }}, @json($clientName), @json($client->email))'
                        {{ empty($client->email) ? 'disabled' : '' }}
                        title="{{ empty($client->email) ? 'No email on file' : 'Send an email' }}"
                    >
                        Email
                    </button>
                </div>

                {{-- ✅ Attach files row (paperclip + chips + DELETE) --}}
                <div class="ab-attach-row" data-ab-return-to="{{ $returnTo }}">
                    <button type="button"
                            class="ab-attach-clip"
                            title="Attach files"
                            onclick="ABAttachments.open({{ (int) $client->id }})">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M8 12.5l7.1-7.1a4 4 0 015.7 5.7l-8.5 8.5a6 6 0 01-8.5-8.5l8.3-8.3"
                                  stroke="#c9a227" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>

                    <div class="ab-attach-label">Attach files:</div>

                    <div class="ab-attach-chips">
                        @if($attachments->count() === 0)
                            <div class="ab-file-empty">No files yet</div>
                        @else
                            @foreach($attachments->take($chipLimit) as $a)
                                @php
                                    $name = method_exists($a, 'safeDisplayName')
                                        ? $a->safeDisplayName(18)
                                        : (\Illuminate\Support\Str::limit(($a->original_name ?? $a->stored_name ?? 'file'), 18));

                                    $ext  = strtolower(pathinfo($a->original_name ?? $a->stored_name ?? '', PATHINFO_EXTENSION));
                                    $isPdf = method_exists($a, 'isPdf') ? $a->isPdf() : ($a->mime_type === 'application/pdf');
                                    $isImg = method_exists($a, 'isImage') ? $a->isImage() : (str_starts_with(strtolower((string)$a->mime_type), 'image/'));
                                @endphp

                                <div class="ab-chip-wrap">
                                    <a class="ab-file-chip"
                                       href="{{ route('attachments.show', $a->id) }}"
                                       target="_blank"
                                       rel="noopener"
                                       title="{{ $a->original_name ?? $a->stored_name }}">
                                        <span class="ab-file-icon">
                                            @if($isPdf)
                                                📄
                                            @elseif($isImg)
                                                🖼️
                                            @elseif(in_array($ext, ['xls','xlsx','csv']))
                                                📊
                                            @elseif(in_array($ext, ['doc','docx']))
                                                📝
                                            @else
                                                📎
                                            @endif
                                        </span>
                                        <span>{{ $name }}</span>
                                    </a>

                                    {{-- ✅ DELETE BUTTON (AJAX) --}}
                                    <form method="POST"
                                          action="{{ route('attachments.destroy', $a->id) }}"
                                          class="ab-attach-delete-form"
                                          style="margin:0;"
                                          data-attachment-id="{{ (int) $a->id }}">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="return_to" value="{{ $returnTo }}">
                                        <button type="submit" class="ab-file-del" title="Delete">×</button>
                                    </form>
                                </div>
                            @endforeach

                            @if($attachments->count() > $chipLimit)
                                <span class="ab-file-more">+{{ $attachments->count() - $chipLimit }}</span>
                            @endif
                        @endif
                    </div>

                    {{-- Hidden upload form: selecting files auto-submits (AJAX) --}}
                    <form id="ab-attach-form-{{ (int) $client->id }}"
                          action="{{ route('contacts.attachments.store', $client->id) }}"
                          method="POST"
                          enctype="multipart/form-data"
                          style="display:none;"
                          data-ab-upload-form="1">
                        @csrf
                        <input type="hidden" name="return_to" value="{{ $returnTo }}">
                        <input id="ab-attach-input-{{ (int) $client->id }}"
                               type="file"
                               name="files[]"
                               multiple>
                    </form>
                </div>

                {{-- CURRENT SERVICE STATUS BADGE (if any) --}}
                @if($client->service_status || $client->service_archived_at)
                    <div class="mb-2 mt-2">
                        @php $status = $client->service_status; @endphp

                        @if(in_array($status, ['Saved', 'Back on Books']))
                            <span class="badge bg-success">{{ $status ?? 'Saved' }}</span>
                        @elseif(in_array($status, ['Not Interested', 'Cancelled']))
                            <span class="badge bg-danger">{{ $status }}</span>
                        @elseif($client->service_archived_at)
                            <span class="badge bg-secondary">Archived</span>
                        @endif

                        @if($client->service_archived_at)
                            <span class="text-muted small ms-2">
                                Archived on {{ \Carbon\Carbon::parse($client->service_archived_at)->format('m/d/Y') }}
                            </span>
                        @endif
                    </div>
                @endif

                <!-- ACTION BUTTONS -->
                <div class="d-flex flex-wrap gap-2">

                    @if(is_null($client->service_archived_at))
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

                    <a href="{{ route('service.follow-up', $client->id) }}"
                       class="btn btn-sm"
                       style="background:#f0ad4e; color:black; font-weight:600; border-radius:6px;">
                        Follow Up
                    </a>

                    @if(is_null($client->service_archived_at))
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

    {{-- STAND-ALONE NOTES --}}
    <div id="service-notes-wrapper">
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

        {{-- EXISTING NOTES --}}
        <div id="notes-list" class="mt-3">
            @php
                $notes = $client->allNotes ?? $client->notes ?? collect();
                $notes = $notes->sortByDesc('created_at');
            @endphp

            @forelse ($notes as $note)
                <div class="border rounded p-2 mb-2" id="note-{{ $note->id }}">
                    <div class="small text-muted mb-1 note-time">
                        {{ optional($note->created_at)->format('m/d/Y g:i A') }}
                    </div>
                    <div class="note-body mb-1">
                        {{ $note->note ?? $note->body }}
                    </div>
                    <div class="mt-1">
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary me-1"
                                onclick="editServiceNote({{ $client->id }}, {{ $note->id }})">
                            Edit
                        </button>
                        <button type="button"
                                class="btn btn-sm btn-outline-danger"
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

{{-- ==========================================
     ✅ SERVICE ATTACHMENTS JS (NO CARD CLOSE)
     - Upload + Delete via fetch
     - Replace ONLY the .ab-attach-row instantly
   ========================================== --}}
<script>
(function () {
    const csrfToken = @json(csrf_token());

    function findAttachRow() {
        return document.querySelector('.ab-attach-row');
    }

    async function refreshAttachRow() {
        const row = findAttachRow();
        if (!row) return;

        const returnTo = row.getAttribute('data-ab-return-to') || window.location.href;

        // pull fresh HTML from server (full page or partial), then extract the updated row
        const res = await fetch(returnTo, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!res.ok) return;

        const html = await res.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const newRow = doc.querySelector('.ab-attach-row');
        if (!newRow) return;

        row.replaceWith(newRow);

        // re-bind events since we replaced DOM
        bindAttachmentEvents();
    }

    async function ajaxUploadFiles(fileInput) {
        const form = fileInput.closest('form');
        if (!form) return;

        const fd = new FormData(form);

        const res = await fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: fd
        });

        // clear input so selecting same file again works
        fileInput.value = '';

        if (!res.ok) {
            alert('Upload failed.');
            return;
        }

        await refreshAttachRow();
    }

    async function ajaxDelete(form) {
        const ok = confirm('Delete this file?');
        if (!ok) return;

        const fd = new FormData(form);

        const res = await fetch(form.action, {
            method: 'POST', // Laravel spoofed DELETE via _method
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: fd
        });

        if (!res.ok) {
            alert('Delete failed.');
            return;
        }

        await refreshAttachRow();
    }

    function bindAttachmentEvents() {
        // Upload: hook into the hidden file input
        const uploadForm = document.querySelector('form[data-ab-upload-form="1"]');
        if (uploadForm) {
            const input = uploadForm.querySelector('input[type="file"]');
            if (input && !input.dataset.abBound) {
                input.dataset.abBound = '1';
                input.addEventListener('change', function () {
                    if (!input.files || input.files.length === 0) return;
                    ajaxUploadFiles(input);
                });
            }
        }

        // Delete: intercept delete form submissions
        document.querySelectorAll('form.ab-attach-delete-form').forEach(function (f) {
            if (f.dataset.abBound) return;
            f.dataset.abBound = '1';
            f.addEventListener('submit', function (e) {
                e.preventDefault();
                ajaxDelete(f);
            });
        });
    }

    // bind once on load
    bindAttachmentEvents();
})();
</script>

{{-- ==========================================
     SERVICE NOTES JS – create / edit / delete
   ========================================== --}}
<script>
(function () {
    const csrfToken = "{{ csrf_token() }}";
    const clientId  = {{ $client->id }};
    const storeUrl  = "{{ route('service.notes.store', $client) }}"; // POST /service/{client}/notes
    const baseUrl   = "{{ url('/service/'.$client->id.'/notes') }}"; // /service/{client}/notes
    const notesList = document.getElementById('notes-list');
    const textarea  = document.getElementById('new_note_body');

    // CREATE
    window.saveServiceNote = function (clickedClientId) {
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
            body: JSON.stringify({ note: bodyText, body: bodyText })
        })
        .then(function (response) {
            if (!response.ok) throw new Error('Network error');
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

            let createdAtText = '';
            if (note.created_at) {
                try { createdAtText = new Date(note.created_at).toLocaleString(); }
                catch (e) { createdAtText = note.created_at; }
            }

            wrapper.innerHTML = `
                <div class="small text-muted mb-1 note-time">
                    ${createdAtText}
                </div>
                <div class="note-body mb-1">
                    ${note.note || note.body || ''}
                </div>
                <div class="mt-1">
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary me-1"
                            onclick="editServiceNote(${clientId}, ${note.id})">
                        Edit
                    </button>
                    <button type="button"
                            class="btn btn-sm btn-outline-danger"
                            onclick="deleteServiceNote(${clientId}, ${note.id})">
                        Delete
                    </button>
                </div>
            `;

            if (notesList.firstChild) notesList.insertBefore(wrapper, notesList.firstChild);
            else notesList.appendChild(wrapper);

            textarea.value = '';
        })
        .catch(function () {
            alert('Error saving note.');
        });
    };

    // EDIT
    window.editServiceNote = function (clickedClientId, noteId) {
        const noteEl  = document.getElementById('note-' + noteId);
        if (!noteEl) return;

        const bodyDiv = noteEl.querySelector('.note-body');
        if (!bodyDiv) return;

        const currentText = bodyDiv.textContent.trim();
        const updated     = prompt('Edit note:', currentText);

        if (updated === null) return;
        const trimmed = updated.trim();
        if (!trimmed) { alert('Note cannot be empty.'); return; }

        fetch(`${baseUrl}/${noteId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ body: trimmed })
        })
        .then(function (response) {
            if (!response.ok) throw new Error('Network error');
            return response.json();
        })
        .then(function (data) {
            if (!data || !data.success || !data.note) {
                alert('Error updating note.');
                return;
            }

            const note = data.note;
            bodyDiv.textContent = note.note || note.body || '';

            const timeDiv = noteEl.querySelector('.note-time');
            if (timeDiv && note.created_at) {
                let createdAtText = '';
                try { createdAtText = new Date(note.created_at).toLocaleString(); }
                catch (e) { createdAtText = note.created_at; }
                timeDiv.textContent = createdAtText;
            }
        })
        .catch(function () {
            alert('Error updating note.');
        });
    };

    // DELETE
    window.deleteServiceNote = function (clickedClientId, noteId) {
        if (!confirm('Delete this note?')) return;

        fetch(`${baseUrl}/${noteId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
        .then(function (response) {
            if (!response.ok) throw new Error('Network error');
            return response.json();
        })
        .then(function (data) {
            if (!data || !data.success) {
                alert('Error deleting note.');
                return;
            }

            const noteEl = document.getElementById('note-' + noteId);
            if (noteEl && noteEl.parentNode) noteEl.parentNode.removeChild(noteEl);

            if (!notesList || notesList.children.length === 0) {
                const empty = document.createElement('p');
                empty.className = 'text-muted small mb-0';
                empty.textContent = 'No notes yet.';
                notesList.appendChild(empty);
            }
        })
        .catch(function () {
            alert('Error deleting note.');
        });
    };

})();
</script>

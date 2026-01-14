@extends('layouts.app')

@section('content')

<style>
    .contacts-card {
        background: #ffffff;
        border-radius: 18px;
        padding: 22px;
        height: calc(100vh - 120px);
        overflow-y: auto;
        box-shadow:
            0 18px 30px -12px rgba(0,0,0,0.35),
            0 8px 16px -8px rgba(0,0,0,0.18);
        border: 1px solid #e5e7eb;
    }

    .contacts-card-wrapper {
        width: 320px !important;
        max-width: 320px !important;
    }

    .contacts-header {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 18px;
    }

    .contacts-search-wrapper {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 18px;
    }

    .contacts-search-input {
        width: 100%;
        padding: 10px 12px;
        border-radius: 10px;
        border: 1px solid #d1d5db;
        background: #ffffff;
        font-size: 14px;
    }

    .contacts-search-btn {
        padding: 7px 10px;
        border-radius: 8px;
        border: none;
        background: #c9a227;
        color: #111827;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 4px 8px rgba(0,0,0,0.20);
    }

    .contacts-search-btn:hover {
        background: #b5901f;
    }

    .btn-gold {
        background: #c9a227;
        color: #111827;
        border: none;
        padding: 6px 10px;
        font-weight: 600;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.20);
        font-size: 12px;
        cursor: pointer;
        white-space: nowrap;
    }

    .btn-gold:hover {
        background: #b5901f;
    }

    /* ✅ Added for messaging buttons on the card */
    .btn-outline-gold {
        background: transparent;
        color: #c9a227;
        border: 1px solid #c9a227;
        padding: 6px 10px;
        font-weight: 600;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.10);
        font-size: 12px;
        cursor: pointer;
        white-space: nowrap;
    }
    .btn-outline-gold:hover {
        background: rgba(201,162,39,0.12);
    }
    .btn-outline-gold:disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }

    .button-row {
        margin-bottom: 20px;
        display: flex;
        gap: 8px;
    }

    .contact-list-item {
        padding: 10px 6px;
        font-size: 15px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
    }

    .contact-list-item:hover {
        background: #f9fafb;
    }

    .active-contact-row {
        background: #eae6d1 !important;
        font-weight: 600;
    }

    .empty-right-panel {
        height: 100%;
        background: transparent !important;
    }

    .urgent-contact {
        color: #b91c1c;
        font-weight: 700;
    }

    #book-details-container,
    #book-details-container * {
        text-align: left !important;
    }

    .flash-wrap {
        margin-bottom: 14px;
    }
</style>

<div class="dashboard-page">
    <div class="row g-4">

        <!-- LEFT COLUMN -->
        <div class="col-md-4 col-lg-3 contacts-card-wrapper">
            <div class="contacts-card">

                <div class="contacts-header">Book of Business</div>

                {{-- ✅ FLASH MESSAGES (you were missing these) --}}
                <div class="flash-wrap">
                    @if (session('import_success'))
                        <div class="alert alert-success py-2 mb-2">
                            {{ session('import_success') }}
                        </div>
                    @endif

                    @if (session('import_error'))
                        <div class="alert alert-danger py-2 mb-2">
                            {{ session('import_error') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger py-2 mb-2">
                            <div><strong>Upload error:</strong></div>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <!-- Search (client-side only) -->
                <div class="contacts-search-wrapper">
                    <input
                        type="text"
                        id="book-search"
                        class="contacts-search-input"
                        placeholder="Search clients..."
                    >
                    <button class="contacts-search-btn" disabled>Go</button>
                </div>

                <!-- Add Client + Upload -->
                <div class="button-row">
                    <button
                        id="add-book-client-btn"
                        class="btn-gold"
                        data-create-url="{{ route('book.create.panel') }}"
                    >
                        Add
                    </button>

                    <button
                        class="btn-gold"
                        data-bs-toggle="modal"
                        data-bs-target="#uploadBookModal"
                    >
                        Upload
                    </button>
                </div>

                <!-- Client List -->
                <div id="book-list">
                    @forelse ($clients as $client)
                        @php
                            $isServiceUrgent = $client->contact_type === 'service' && is_null($client->service_archived_at);
                            $name = $client->full_name ?? trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? ''));
                        @endphp

                        <div
                            class="contact-list-item js-book-row
                                   {{ (isset($selected) && $selected == $client->id) ? 'active-contact-row' : '' }}
                                   {{ $isServiceUrgent ? 'urgent-contact' : '' }}"
                            data-id="{{ $client->id }}"
                            data-show-url="{{ route('book.show', $client->id) }}"
                        >
                            {{ $name ?: '(No Name)' }}

                            @if($isServiceUrgent)
                                <span class="badge bg-danger ms-1">Service</span>
                            @endif

                            @if($client->policy_type)
                                <br><small class="text-muted">{{ $client->policy_type }}</small>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted">No clients found.</p>
                    @endforelse
                </div>

            </div>
        </div>

        <!-- RIGHT PANEL -->
        <div class="col-md-8 col-lg-9">
            <div id="book-details-container" style="width:100%; min-height:400px;">
                <div class="empty-right-panel"></div>
            </div>
        </div>

    </div>
</div>

<!-- UPLOAD BOOK MODAL -->
<div class="modal fade" id="uploadBookModal" tabindex="-1">
    <div class="modal-dialog">
        <form
            action="{{ route('book.import') }}"
            method="POST"
            enctype="multipart/form-data"
            class="modal-content"
        >
            @csrf

            <div class="modal-header bg-black text-gold">
                <h5 class="modal-title">Upload Book of Business</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <label class="form-label">Choose CSV or Excel file</label>
                <input
                    type="file"
                    name="file"
                    class="form-control"
                    accept=".csv, .xlsx, .xls"
                    required
                >
                <small class="text-muted d-block mt-2">
                    Tip: Header row should include fields like First Name / Last Name / Email / Phone, but blanks are allowed.
                </small>
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn-gold">Upload</button>
            </div>

        </form>
    </div>
</div>

<!-- =========================
     Messaging Modals (loaded once)
     ========================= -->
<div class="modal fade" id="abSmsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" onsubmit="ABMessaging.sendSms(event)">
            <div class="modal-header">
                <h5 class="modal-title">Send Text</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="ab_sms_contact_id">
                <div class="mb-1 fw-bold" id="ab_sms_contact_name"></div>
                <div class="mb-2 text-muted" id="ab_sms_to"></div>

                <textarea id="ab_sms_body" class="form-control" rows="4" placeholder="Type message..."></textarea>
                <div class="small text-muted mt-2">Manual send only (backend wiring next).</div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-gold">Send</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="abEmailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" onsubmit="ABMessaging.sendEmail(event)">
            <div class="modal-header">
                <h5 class="modal-title">Send Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="ab_email_contact_id">
                <div class="mb-1 fw-bold" id="ab_email_contact_name"></div>
                <div class="mb-2 text-muted" id="ab_email_to"></div>

                <input id="ab_email_subject" class="form-control mb-2" placeholder="Subject">
                <textarea id="ab_email_body" class="form-control" rows="6" placeholder="Type email..."></textarea>
                <div class="small text-muted mt-2">Manual send only (backend wiring next).</div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-gold">Send</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

    const container = document.getElementById('book-details-container');

    window.loadBookPanel = function (url) {
        container.innerHTML = `
            <div style="padding:40px;">
                <div class="text-center">
                    <div class="spinner-border text-warning" role="status"></div>
                    <p class="mt-3 text-muted">Loading...</p>
                </div>
            </div>
        `;

        fetch(url, {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(res => res.text())
        .then(html => container.innerHTML = html)
        .catch(() => {
            container.innerHTML = `
                <div style="padding:40px; color:red;">
                    Failed to load.
                </div>
            `;
        });
    };

    document.querySelectorAll('.js-book-row').forEach(row => {
        row.addEventListener('click', () => {

            document.querySelectorAll('.js-book-row')
                .forEach(r => r.classList.remove('active-contact-row'));

            row.classList.add('active-contact-row');

            loadBookPanel(row.dataset.showUrl);
        });
    });

    const addBtn = document.getElementById('add-book-client-btn');
    if (addBtn) {
        addBtn.addEventListener('click', function () {
            loadBookPanel(this.dataset.createUrl);
        });
    }

    document.getElementById('book-search').addEventListener('keyup', function () {
        const term = this.value.toLowerCase();
        document.querySelectorAll('#book-list .js-book-row')
            .forEach(row =>
                row.style.display = row.textContent.toLowerCase().includes(term)
                    ? 'block'
                    : 'none'
            );
    });

    @if(!empty($selected))
        loadBookPanel("{{ route('book.show', $selected) }}");
    @endif

    {{-- ✅ If upload had errors, reopen modal so user sees it --}}
    @if(session('import_error') || $errors->any())
        const modalEl = document.getElementById('uploadBookModal');
        if (modalEl) new bootstrap.Modal(modalEl).show();
    @endif
});


/* ------------------------------------------------------
   MESSAGING UI (UI-ONLY STUB FOR NOW)
   - Contact card buttons call ABMessaging.openSms/openEmail
   - Next step: wire backend endpoints and real sending
   ------------------------------------------------------ */
window.ABMessaging = {
    openSms(contactId, name, phone) {
        document.getElementById('ab_sms_contact_id').value = contactId;
        document.getElementById('ab_sms_contact_name').textContent = name || '';
        document.getElementById('ab_sms_to').textContent = phone ? `To: ${phone}` : 'No phone on file';
        document.getElementById('ab_sms_body').value = '';

        new bootstrap.Modal(document.getElementById('abSmsModal')).show();
    },

    openEmail(contactId, name, email) {
        document.getElementById('ab_email_contact_id').value = contactId;
        document.getElementById('ab_email_contact_name').textContent = name || '';
        document.getElementById('ab_email_to').textContent = email ? `To: ${email}` : 'No email on file';
        document.getElementById('ab_email_subject').value = '';
        document.getElementById('ab_email_body').value = '';

        new bootstrap.Modal(document.getElementById('abEmailModal')).show();
    },

    sendSms(e) {
        e.preventDefault();
        const body = (document.getElementById('ab_sms_body').value || '').trim();
        if (!body) return alert('Message is empty.');

        // UI stub for now
        alert('SMS queued (UI stub). Next: wire POST /contacts/{id}/messages + Twilio.');
        bootstrap.Modal.getInstance(document.getElementById('abSmsModal')).hide();
    },

    sendEmail(e) {
        e.preventDefault();
        const subject = (document.getElementById('ab_email_subject').value || '').trim();
        const body = (document.getElementById('ab_email_body').value || '').trim();
        if (!subject || !body) return alert('Subject and body are required.');

        // UI stub for now
        alert('Email queued (UI stub). Next: wire POST /contacts/{id}/messages + email provider.');
        bootstrap.Modal.getInstance(document.getElementById('abEmailModal')).hide();
    }
};


/* ------------------------------------------------------
   BEC SECTION — MOVED HERE SO AJAX PARTIALS CAN USE IT
   ------------------------------------------------------ */

function openAddBeneficiary(clientId) {
    document.getElementById('beneficiaryModalTitle').innerText = "Add Beneficiary";
    document.getElementById('beneficiary_id').value = "";
    document.getElementById('beneficiary_client_id').value = clientId;

    document.getElementById('beneficiary_name').value = "";
    document.getElementById('beneficiary_relationship').value = "";
    document.getElementById('beneficiary_phone').value = "";
    document.getElementById('beneficiary_contacted').value = "0";

    new bootstrap.Modal(document.getElementById('beneficiaryModal')).show();
}

function editBeneficiary(id) {
    fetch(`/api/beneficiaries/${id}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('beneficiaryModalTitle').innerText = "Edit Beneficiary";

            document.getElementById('beneficiary_id').value = data.id;
            document.getElementById('beneficiary_client_id').value = data.contact_id;

            document.getElementById('beneficiary_name').value = data.name;
            document.getElementById('beneficiary_relationship').value = data.relationship ?? "";
            document.getElementById('beneficiary_phone').value = data.phone ?? "";
            document.getElementById('beneficiary_contacted').value = data.contacted ? "1" : "0";

            new bootstrap.Modal(document.getElementById('beneficiaryModal')).show();
        });
}

document.addEventListener("submit", function (e) {
    if (e.target.id !== "beneficiaryForm") return;
    e.preventDefault();

    let id = document.getElementById('beneficiary_id').value;
    let clientId = document.getElementById('beneficiary_client_id').value;

    let url = id
        ? `/book/${clientId}/beneficiaries/${id}`
        : `/book/${clientId}/beneficiaries`;

    let method = id ? "PUT" : "POST";

    fetch(url, {
        method: method,
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        body: JSON.stringify({
            name: document.getElementById('beneficiary_name').value,
            relationship: document.getElementById('beneficiary_relationship').value,
            phone: document.getElementById('beneficiary_phone').value,
            contacted: document.getElementById('beneficiary_contacted').value
        })
    })
    .then(r => r.json())
    .then(() => {
        bootstrap.Modal.getInstance(document.getElementById('beneficiaryModal')).hide();
        loadBookPanel(`/book/${clientId}`);
    });
});

function deleteBeneficiary(clientId, id) {
    if (!confirm("Delete beneficiary?")) return;

    fetch(`/book/${clientId}/beneficiaries/${id}`, {
        method: "DELETE",
        headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}" }
    })
    .then(r => r.json())
    .then(() => loadBookPanel(`/book/${clientId}`));
}

function openAddEmergency(clientId) {
    document.getElementById('emergencyModalTitle').innerText = "Add Emergency Contact";
    document.getElementById('emergency_id').value = "";
    document.getElementById('emergency_client_id').value = clientId;

    document.getElementById('emergency_name').value = "";
    document.getElementById('emergency_relationship').value = "";
    document.getElementById('emergency_phone').value = "";
    document.getElementById('emergency_contacted').value = "0";

    new bootstrap.Modal(document.getElementById('emergencyModal')).show();
}

function editEmergency(id) {
    fetch(`/api/emergency/${id}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('emergencyModalTitle').innerText = "Edit Emergency Contact";

            document.getElementById('emergency_id').value = data.id;
            document.getElementById('emergency_client_id').value = data.contact_id;

            document.getElementById('emergency_name').value = data.name;
            document.getElementById('emergency_relationship').value = data.relationship ?? "";
            document.getElementById('emergency_phone').value = data.phone ?? "";
            document.getElementById('emergency_contacted').value = data.contacted ? "1" : "0";

            new bootstrap.Modal(document.getElementById('emergencyModal')).show();
        });
}

document.addEventListener("submit", function (e) {
    if (e.target.id !== "emergencyForm") return;
    e.preventDefault();

    let id = document.getElementById('emergency_id').value;
    let clientId = document.getElementById('emergency_client_id').value;

    let url = id
        ? `/book/${clientId}/emergency/${id}`
        : `/book/${clientId}/emergency`;

    let method = id ? "PUT" : "POST";

    fetch(url, {
        method: method,
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        body: JSON.stringify({
            name: document.getElementById('emergency_name').value,
            relationship: document.getElementById('emergency_relationship').value,
            phone: document.getElementById('emergency_phone').value,
            contacted: document.getElementById('emergency_contacted').value
        })
    })
    .then(r => r.json())
    .then(() => {
        bootstrap.Modal.getInstance(document.getElementById('emergencyModal')).hide();
        loadBookPanel(`/book/${clientId}`);
    });
});

function deleteEmergency(clientId, id) {
    if (!confirm("Delete emergency contact?")) return;

    fetch(`/book/${clientId}/emergency/${id}`, {
        method: "DELETE",
        headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}" }
    })
    .then(r => r.json())
    .then(() => loadBookPanel(`/book/${clientId}`));
}

function saveNote(clientId) {
    const textarea = document.getElementById('new_note_body');
    if (!textarea) return;

    const body = textarea.value.trim();
    if (!body) {
        alert("Note cannot be empty.");
        return;
    }

    fetch(`/book/${clientId}/notes`, {
        method: 'POST',
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        },
        body: JSON.stringify({ body })
    })
    .then(r => {
        if (!r.ok) throw new Error('Failed to save note');
        return r.json();
    })
    .then(() => {
        textarea.value = '';
        loadBookPanel(`/book/${clientId}`);
    })
    .catch(() => alert('Error saving note.'));
}

function editNote(clientId, noteId) {
    const noteEl = document.querySelector(`#note-${noteId} .note-body`);
    if (!noteEl) return;

    const existing = noteEl.innerText.trim();
    const updated = prompt("Edit note:", existing);
    if (updated === null) return;

    fetch(`/book/${clientId}/notes/${noteId}`, {
        method: 'PUT',
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        },
        body: JSON.stringify({ body: updated })
    })
    .then(r => {
        if (!r.ok) throw new Error('Failed to update note');
        return r.json();
    })
    .then(() => loadBookPanel(`/book/${clientId}`))
    .catch(() => alert('Error updating note.'));
}

function deleteNote(clientId, noteId) {
    if (!confirm("Delete this note?")) return;

    fetch(`/book/${clientId}/notes/${noteId}`, {
        method: 'DELETE',
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        }
    })
    .then(r => {
        if (!r.ok) throw new Error('Failed to delete note');
        return r.json();
    })
    .then(() => loadBookPanel(`/book/${clientId}`))
    .catch(() => alert('Error deleting note.'));
}

</script>
@endpush

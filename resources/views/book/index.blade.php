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

    /* Messaging button style used on the contact card (partial) */
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
    .btn-outline-gold:hover { background: rgba(201,162,39,0.12); }
    .btn-outline-gold:disabled { opacity: 0.45; cursor: not-allowed; }

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

<!-- Messaging Modals -->
<div class="modal fade" id="abSmsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" onsubmit="return ABMessaging.sendSms(event)">
            <div class="modal-header">
                <h5 class="modal-title">Send Text</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="ab_sms_contact_id">
                <div class="mb-1 fw-bold" id="ab_sms_contact_name"></div>
                <div class="mb-2 text-muted" id="ab_sms_to"></div>

                <textarea id="ab_sms_body" class="form-control" rows="4" placeholder="Type message..."></textarea>
                <div class="small text-muted mt-2">This saves a queued SMS record (Phase 2).</div>
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
        <form class="modal-content" onsubmit="return ABMessaging.sendEmail(event)">
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
                <div class="small text-muted mt-2">This saves a queued Email record (Phase 2).</div>
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
/**
 * IMPORTANT:
 * Text/Email buttons live in an AJAX-loaded partial and call ABMessaging.openSms/openEmail.
 * If ANY JS syntax error occurs on this page, ABMessaging won't exist and buttons will "do nothing".
 * This file keeps JS simple + fully balanced to avoid "Unexpected end of input".
 */

(function () {
    // ---------------------------
    // Helpers
    // ---------------------------
    function hasBootstrapModal() {
        return (window.bootstrap && window.bootstrap.Modal);
    }
    function el(id) {
        return document.getElementById(id);
    }

    // ---------------------------
    // Global: Messaging API (UI + POST queue)
    // ---------------------------
    window.ABMessaging = {
        openSms: function(contactId, name, phone) {
            try {
                if (!hasBootstrapModal()) throw new Error('Bootstrap Modal JS missing.');

                el('ab_sms_contact_id').value = contactId || '';
                el('ab_sms_contact_name').textContent = name || '';
                el('ab_sms_to').textContent = phone ? ('To: ' + phone) : 'No phone on file';
                el('ab_sms_body').value = '';

                new bootstrap.Modal(el('abSmsModal')).show();
            } catch (e) {
                console.error(e);
                alert('Text modal failed: ' + e.message);
            }
        },

        openEmail: function(contactId, name, email) {
            try {
                if (!hasBootstrapModal()) throw new Error('Bootstrap Modal JS missing.');

                el('ab_email_contact_id').value = contactId || '';
                el('ab_email_contact_name').textContent = name || '';
                el('ab_email_to').textContent = email ? ('To: ' + email) : 'No email on file';
                el('ab_email_subject').value = '';
                el('ab_email_body').value = '';

                new bootstrap.Modal(el('abEmailModal')).show();
            } catch (e) {
                console.error(e);
                alert('Email modal failed: ' + e.message);
            }
        },

        sendSms: async function(e) {
            e.preventDefault();

            var contactId = (el('ab_sms_contact_id').value || '').trim();
            var body = (el('ab_sms_body').value || '').trim();
            if (!body) { alert('Message is empty.'); return false; }

            try {
                var res = await fetch('/contacts/' + encodeURIComponent(contactId) + '/messages', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': @json(csrf_token()),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ channel: 'sms', body: body })
                });

                var data = await res.json().catch(function(){ return {}; });
                if (!res.ok) throw new Error(data.message || ('Failed to save SMS (HTTP ' + res.status + ')'));

                if (hasBootstrapModal()) {
                    var inst = bootstrap.Modal.getInstance(el('abSmsModal'));
                    if (inst) inst.hide();
                }

                alert('Text saved to Messages (queued).');
                return false;
            } catch (err) {
                console.error(err);
                alert('SMS failed: ' + err.message);
                return false;
            }
        },

        sendEmail: async function(e) {
            e.preventDefault();

            var contactId = (el('ab_email_contact_id').value || '').trim();
            var subject = (el('ab_email_subject').value || '').trim();
            var body = (el('ab_email_body').value || '').trim();

            if (!subject) { alert('Subject is required.'); return false; }
            if (!body) { alert('Email body is empty.'); return false; }

            try {
                var res = await fetch('/contacts/' + encodeURIComponent(contactId) + '/messages', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': @json(csrf_token()),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ channel: 'email', subject: subject, body: body })
                });

                var data = await res.json().catch(function(){ return {}; });
                if (!res.ok) throw new Error(data.message || ('Failed to save Email (HTTP ' + res.status + ')'));

                if (hasBootstrapModal()) {
                    var inst = bootstrap.Modal.getInstance(el('abEmailModal'));
                    if (inst) inst.hide();
                }

                alert('Email saved to Messages (queued).');
                return false;
            } catch (err) {
                console.error(err);
                alert('Email failed: ' + err.message);
                return false;
            }
        }
    };

    // ---------------------------
    // Global: Notes (used by AJAX partial onclick)
    // ---------------------------
    window.saveNote = function(clientId) {
        var textarea = el('new_note_body');
        if (!textarea) return;

        var body = (textarea.value || '').trim();
        if (!body) { alert('Note cannot be empty.'); return; }

        fetch('/book/' + encodeURIComponent(clientId) + '/notes', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': @json(csrf_token()),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ body: body })
        })
        .then(function(r){
            if (!r.ok) throw new Error('Failed to save note');
            return r.json();
        })
        .then(function(){
            textarea.value = '';
            if (window.loadBookPanel) window.loadBookPanel('/book/' + clientId);
        })
        .catch(function(err){
            console.error(err);
            alert('Error saving note.');
        });
    };

    // ---------------------------
    // Page boot: Book panel loader + list clicks
    // ---------------------------
    document.addEventListener('DOMContentLoaded', function () {
        var container = el('book-details-container');

        window.loadBookPanel = function(url) {
            if (!container) return;

            container.innerHTML =
                '<div style="padding:40px;">' +
                    '<div class="text-center">' +
                        '<div class="spinner-border text-warning" role="status"></div>' +
                        '<p class="mt-3 text-muted">Loading...</p>' +
                    '</div>' +
                '</div>';

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(res){ return res.text(); })
            .then(function(html){ container.innerHTML = html; })
            .catch(function(){
                container.innerHTML = '<div style="padding:40px; color:red;">Failed to load.</div>';
            });
        };

        document.querySelectorAll('.js-book-row').forEach(function(row){
            row.addEventListener('click', function(){
                document.querySelectorAll('.js-book-row').forEach(function(r){
                    r.classList.remove('active-contact-row');
                });
                row.classList.add('active-contact-row');
                window.loadBookPanel(row.dataset.showUrl);
            });
        });

        var addBtn = el('add-book-client-btn');
        if (addBtn) {
            addBtn.addEventListener('click', function(){
                window.loadBookPanel(addBtn.dataset.createUrl);
            });
        }

        var search = el('book-search');
        if (search) {
            search.addEventListener('keyup', function(){
                var term = (search.value || '').toLowerCase();
                document.querySelectorAll('#book-list .js-book-row').forEach(function(row){
                    row.style.display = row.textContent.toLowerCase().includes(term) ? 'block' : 'none';
                });
            });
        }

        @if(!empty($selected))
            window.loadBookPanel(@json(route('book.show', $selected)));
        @endif

        @if(session('import_error') || $errors->any())
            var modalEl = el('uploadBookModal');
            if (modalEl && hasBootstrapModal()) {
                new bootstrap.Modal(modalEl).show();
            }
        @endif
    });
})();
</script>
@endpush

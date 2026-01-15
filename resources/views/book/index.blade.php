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

    .contacts-search-btn:hover { background: #b5901f; }

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
    .btn-gold:hover { background: #b5901f; }

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
        margin-bottom: 12px;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .contact-list-item {
        padding: 10px 6px;
        font-size: 15px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
        display: flex;
        gap: 8px;
        align-items: flex-start;
    }
    .contact-list-item:hover { background: #f9fafb; }

    .active-contact-row {
        background: #eae6d1 !important;
        font-weight: 600;
    }

    .empty-right-panel { height: 100%; background: transparent !important; }
    .urgent-contact { color: #b91c1c; font-weight: 700; }

    #book-details-container,
    #book-details-container * { text-align: left !important; }

    .flash-wrap { margin-bottom: 14px; }

    /* checkbox alignment */
    .book-check {
        margin-top: 3px;
        transform: scale(1.05);
        cursor: pointer;
    }

    .book-row-text {
        flex: 1;
        min-width: 0;
    }

    .bulk-mini {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 10px;
        padding: 8px 10px;
        border: 1px solid #eee;
        border-radius: 10px;
        background: #fafafa;
    }

    .bulk-count {
        font-size: 12px;
        color: #6b7280;
        white-space: nowrap;
    }

    /* Bulk modal */
    .bulk-help {
        font-size: 12px;
        color: #6b7280;
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

                <!-- ✅ Bulk tools -->
                <div class="bulk-mini">
                    <label class="d-flex align-items-center gap-2 m-0" style="cursor:pointer;">
                        <input type="checkbox" id="book-select-all" class="form-check-input m-0">
                        <span style="font-size:12px;">Select all</span>
                    </label>

                    <div class="d-flex align-items-center gap-2">
                        <span class="bulk-count">
                            Selected: <span id="book-selected-count">0</span>
                        </span>
                        <button id="bulk-text-btn" class="btn-outline-gold" type="button" disabled>
                            Bulk Text
                        </button>
                    </div>
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
                                   {{ (!empty($selected) && (int)$selected === (int)$client->id) ? 'active-contact-row' : '' }}
                                   {{ $isServiceUrgent ? 'urgent-contact' : '' }}"
                            data-id="{{ $client->id }}"
                            data-show-url="{{ route('book.show', $client->id) }}"
                        >
                            <!-- ✅ Checkbox -->
                            <input
                                type="checkbox"
                                class="form-check-input book-check js-book-check"
                                data-contact-id="{{ $client->id }}"
                                onclick="event.stopPropagation();"
                            >

                            <div class="book-row-text">
                                {{ $name ?: '(No Name)' }}

                                @if($isServiceUrgent)
                                    <span class="badge bg-danger ms-1">Service</span>
                                @endif

                                @if($client->policy_type)
                                    <br><small class="text-muted">{{ $client->policy_type }}</small>
                                @endif
                            </div>
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
                <div class="small text-muted mt-2">This will save a queued text to the database.</div>
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
                <div class="small text-muted mt-2">This will save a queued email to the database.</div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-gold">Send</button>
            </div>
        </form>
    </div>
</div>

<!-- ✅ BULK TEXT MODAL -->
<div class="modal fade" id="abBulkSmsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" onsubmit="ABMessaging.sendBulkSms(event)">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Text</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="mb-2">
                    <div class="fw-bold">Recipients</div>
                    <div class="bulk-help">
                        Selected: <span id="bulkSmsRecipientCount">0</span>
                        <span class="ms-2 text-muted">(contacts without phone will be skipped)</span>
                    </div>
                </div>

                <textarea id="ab_bulk_sms_body" class="form-control" rows="5" placeholder="Type one message to send to everyone..."></textarea>
                <div class="bulk-help mt-2">
                    This will create one <strong>queued</strong> SMS per contact in the database. (Delivery requires Twilio later.)
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn-gold">Queue Bulk Text</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    var CSRF_TOKEN = @json(csrf_token());
    var SELECTED_ID = @json(!empty($selected) ? (int)$selected : null);
    var SHOULD_REOPEN_UPLOAD = @json((bool)(session('import_error') || $errors->any()));
    var BOOK_BASE_URL = @json(url('/book'));

    function hasBootstrapModal() {
        return !!(window.bootstrap && window.bootstrap.Modal);
    }

    function showModalById(id) {
        if (!hasBootstrapModal()) {
            alert('Bootstrap modal JS is missing. (bootstrap.Modal not available)');
            return;
        }
        var el = document.getElementById(id);
        if (!el) {
            alert('Missing modal element: #' + id);
            return;
        }
        new bootstrap.Modal(el).show();
    }

    function getSelectedContactIds() {
        var checks = document.querySelectorAll('.js-book-check');
        var ids = [];
        for (var i = 0; i < checks.length; i++) {
            if (checks[i].checked) {
                ids.push(parseInt(checks[i].getAttribute('data-contact-id'), 10));
            }
        }
        return ids.filter(function (n) { return !isNaN(n); });
    }

    function syncBulkUi() {
        var ids = getSelectedContactIds();
        var countEl = document.getElementById('book-selected-count');
        var btn = document.getElementById('bulk-text-btn');
        if (countEl) countEl.textContent = String(ids.length);
        if (btn) btn.disabled = ids.length === 0;

        var selectAll = document.getElementById('book-select-all');
        if (selectAll) {
            // only consider visible rows for "select all"
            var visibleChecks = Array.prototype.filter.call(document.querySelectorAll('.js-book-check'), function (c) {
                var row = c.closest('.contact-list-item');
                return row && row.style.display !== 'none';
            });
            var allChecked = visibleChecks.length > 0 && visibleChecks.every(function (c) { return c.checked; });
            selectAll.checked = allChecked;
        }
    }

    // Global so AJAX-loaded details.blade.php onclick handlers can call it
    window.ABMessaging = window.ABMessaging || {};

    // Keep your existing per-contact handlers (these already work)
    window.ABMessaging.openSms = function (contactId, name, phone) {
        try {
            document.getElementById('ab_sms_contact_id').value = contactId || '';
            document.getElementById('ab_sms_contact_name').textContent = name || '';
            document.getElementById('ab_sms_to').textContent = phone ? ('To: ' + phone) : 'No phone on file';
            document.getElementById('ab_sms_body').value = '';
            showModalById('abSmsModal');
        } catch (e) {
            console.error(e);
            alert('Failed to open Text modal. Check console.');
        }
    };

    window.ABMessaging.openEmail = function (contactId, name, email) {
        try {
            document.getElementById('ab_email_contact_id').value = contactId || '';
            document.getElementById('ab_email_contact_name').textContent = name || '';
            document.getElementById('ab_email_to').textContent = email ? ('To: ' + email) : 'No email on file';
            document.getElementById('ab_email_subject').value = '';
            document.getElementById('ab_email_body').value = '';
            showModalById('abEmailModal');
        } catch (e) {
            console.error(e);
            alert('Failed to open Email modal. Check console.');
        }
    };

    window.ABMessaging.sendSms = function (e) {
        e.preventDefault();

        var contactId = String((document.getElementById('ab_sms_contact_id') || {}).value || '').trim();
        var body = String((document.getElementById('ab_sms_body') || {}).value || '').trim();
        if (!body) return alert('Message is empty.');

        fetch('/contacts/' + contactId + '/messages', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ channel: 'sms', body: body })
        })
        .then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (data) {
                if (!res.ok) throw new Error(data.message || ('Failed to save SMS (HTTP ' + res.status + ').'));
                return data;
            });
        })
        .then(function () {
            var inst = bootstrap.Modal.getInstance(document.getElementById('abSmsModal'));
            if (inst) inst.hide();
            alert('Text saved to Messages (queued).');
        })
        .catch(function (err) {
            console.error(err);
            alert(err.message || 'SMS send failed.');
        });
    };

    window.ABMessaging.sendEmail = function (e) {
        e.preventDefault();

        var contactId = String((document.getElementById('ab_email_contact_id') || {}).value || '').trim();
        var subject = String((document.getElementById('ab_email_subject') || {}).value || '').trim();
        var body = String((document.getElementById('ab_email_body') || {}).value || '').trim();

        if (!subject) return alert('Subject is required.');
        if (!body) return alert('Email body is empty.');

        fetch('/contacts/' + contactId + '/messages', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ channel: 'email', subject: subject, body: body })
        })
        .then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (data) {
                if (!res.ok) throw new Error(data.message || ('Failed to save Email (HTTP ' + res.status + ').'));
                return data;
            });
        })
        .then(function () {
            var inst = bootstrap.Modal.getInstance(document.getElementById('abEmailModal'));
            if (inst) inst.hide();
            alert('Email saved to Messages (queued).');
        })
        .catch(function (err) {
            console.error(err);
            alert(err.message || 'Email send failed.');
        });
    };

    // ✅ Bulk SMS handler
    window.ABMessaging.sendBulkSms = function (e) {
        e.preventDefault();

        var ids = getSelectedContactIds();
        if (!ids.length) return alert('No contacts selected.');

        var body = String((document.getElementById('ab_bulk_sms_body') || {}).value || '').trim();
        if (!body) return alert('Message is empty.');

        fetch('/contacts/messages/bulk', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ contact_ids: ids, body: body })
        })
        .then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (data) {
                if (!res.ok) throw new Error(data.message || ('Bulk SMS failed (HTTP ' + res.status + ').'));
                return data;
            });
        })
        .then(function (data) {
            var inst = bootstrap.Modal.getInstance(document.getElementById('abBulkSmsModal'));
            if (inst) inst.hide();

            var queued = (data && data.queued) ? data.queued : 0;
            var skippedNoPhone = (data && data.skipped && data.skipped.no_phone) ? data.skipped.no_phone : 0;

            alert('Bulk text queued.\n\nQueued: ' + queued + '\nSkipped (no phone): ' + skippedNoPhone);
        })
        .catch(function (err) {
            console.error(err);
            alert(err.message || 'Bulk SMS send failed.');
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        var container = document.getElementById('book-details-container');

        window.loadBookPanel = function (url) {
            if (!container) return;

            container.innerHTML =
                '<div style="padding:40px;">' +
                    '<div class="text-center">' +
                        '<div class="spinner-border text-warning" role="status"></div>' +
                        '<p class="mt-3 text-muted">Loading...</p>' +
                    '</div>' +
                '</div>';

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (res) { return res.text(); })
                .then(function (html) { container.innerHTML = html; })
                .catch(function () {
                    container.innerHTML = '<div style="padding:40px; color:red;">Failed to load.</div>';
                });
        };

        // row click loads details panel
        var rows = document.querySelectorAll('.js-book-row');
        for (var i = 0; i < rows.length; i++) {
            rows[i].addEventListener('click', function () {
                var all = document.querySelectorAll('.js-book-row');
                for (var j = 0; j < all.length; j++) all[j].classList.remove('active-contact-row');

                this.classList.add('active-contact-row');
                window.loadBookPanel(this.getAttribute('data-show-url'));
            });
        }

        // bulk checkbox wiring
        document.addEventListener('change', function (e) {
            if (e.target && e.target.classList && e.target.classList.contains('js-book-check')) {
                syncBulkUi();
            }
        });

        var selectAll = document.getElementById('book-select-all');
        if (selectAll) {
            selectAll.addEventListener('change', function () {
                var checks = document.querySelectorAll('.js-book-check');
                for (var i = 0; i < checks.length; i++) {
                    var row = checks[i].closest('.contact-list-item');
                    var visible = row && row.style.display !== 'none';
                    if (visible) checks[i].checked = !!selectAll.checked;
                }
                syncBulkUi();
            });
        }

        var bulkBtn = document.getElementById('bulk-text-btn');
        if (bulkBtn) {
            bulkBtn.addEventListener('click', function () {
                var ids = getSelectedContactIds();
                if (!ids.length) return;

                var count = document.getElementById('bulkSmsRecipientCount');
                if (count) count.textContent = String(ids.length);

                var bodyEl = document.getElementById('ab_bulk_sms_body');
                if (bodyEl) bodyEl.value = '';

                showModalById('abBulkSmsModal');
            });
        }

        // Add button
        var addBtn = document.getElementById('add-book-client-btn');
        if (addBtn) {
            addBtn.addEventListener('click', function () {
                window.loadBookPanel(this.getAttribute('data-create-url'));
            });
        }

        // search filter
        var searchEl = document.getElementById('book-search');
        if (searchEl) {
            searchEl.addEventListener('keyup', function () {
                var term = String(this.value || '').toLowerCase();
                var listRows = document.querySelectorAll('#book-list .js-book-row');
                for (var k = 0; k < listRows.length; k++) {
                    var show = listRows[k].textContent.toLowerCase().indexOf(term) !== -1;
                    listRows[k].style.display = show ? 'flex' : 'none';
                }
                syncBulkUi();
            });
        }

        if (SELECTED_ID) {
            window.loadBookPanel(BOOK_BASE_URL + '/' + String(SELECTED_ID));
        }

        if (SHOULD_REOPEN_UPLOAD) {
            showModalById('uploadBookModal');
        }

        syncBulkUi();
    });

})();

// Notes must be global (details.blade.php calls saveNote())
function saveNote(clientId) {
    var textarea = document.getElementById('new_note_body');
    if (!textarea) return;

    var body = String(textarea.value || '').trim();
    if (!body) return alert('Note cannot be empty.');

    fetch('/book/' + clientId + '/notes', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': @json(csrf_token()),
            'Accept': 'application/json'
        },
        body: JSON.stringify({ body: body })
    })
    .then(function (r) {
        if (!r.ok) throw new Error('Failed to save note');
        return r.json();
    })
    .then(function () {
        textarea.value = '';
        if (window.loadBookPanel) window.loadBookPanel('/book/' + clientId);
    })
    .catch(function () {
        alert('Error saving note.');
    });
}
</script>
@endpush

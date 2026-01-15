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

    /* Messaging outline buttons */
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

    /* Bulk selection bar */
    .bulk-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 10px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        margin-bottom: 12px;
        background: #fafafa;
    }
    .bulk-bar small { color: #6b7280; }

    .bulk-count-pill {
        font-size: 12px;
        color: #111827;
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        padding: 4px 8px;
        border-radius: 999px;
    }

    /* History bubbles */
    .ab-history {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #fff;
        padding: 10px;
        max-height: 260px;
        overflow-y: auto;
        margin-bottom: 10px;
    }

    .ab-msg {
        max-width: 85%;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 8px 10px;
        margin: 6px 0;
        font-size: 13px;
        line-height: 1.25rem;
        background: #f9fafb;
    }

    .ab-msg.outbound { margin-left: auto; background: #fdf6e3; border-color: rgba(201,162,39,0.45); }
    .ab-msg.inbound  { margin-right: auto; background: #f3f4f6; }

    .ab-msg-meta {
        font-size: 11px;
        color: #6b7280;
        margin-top: 4px;
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

                <!-- ✅ Bulk selection bar -->
                <div class="bulk-bar">
                    <label class="d-flex align-items-center gap-2 mb-0" style="cursor:pointer;">
                        <input type="checkbox" id="bulk-select-all" class="form-check-input mt-0">
                        <small>Select all</small>
                    </label>

                    <span class="bulk-count-pill">
                        Selected: <span id="bulk-selected-count">0</span>
                    </span>

                    <button id="bulk-text-btn" class="btn-outline-gold ms-auto" type="button" disabled>
                        Bulk Text
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
                                   {{ (!empty($selected) && (int)$selected === (int)$client->id) ? 'active-contact-row' : '' }}
                                   {{ $isServiceUrgent ? 'urgent-contact' : '' }}"
                            data-id="{{ $client->id }}"
                            data-show-url="{{ route('book.show', $client->id) }}"
                        >
                            <!-- ✅ Checkbox (bulk) -->
                            <input
                                type="checkbox"
                                class="form-check-input js-bulk-check mt-1"
                                data-id="{{ $client->id }}"
                                data-phone="{{ $client->phone }}"
                            >

                            <!-- Row content -->
                            <div style="flex:1;">
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

<!-- ✅ Single SMS Modal (with history) -->
<div class="modal fade" id="abSmsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" onsubmit="ABMessaging.sendSms(event)">
            <div class="modal-header">
                <h5 class="modal-title">Send Text</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="ab_sms_contact_id">
                <div class="mb-1 fw-bold" id="ab_sms_contact_name"></div>
                <div class="mb-2 text-muted" id="ab_sms_to"></div>

                <div class="small text-muted mb-2" id="ab_sms_history_label">Loading history...</div>
                <div id="ab_sms_history" class="ab-history"></div>

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

<!-- ✅ Single Email Modal (with history) -->
<div class="modal fade" id="abEmailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" onsubmit="ABMessaging.sendEmail(event)">
            <div class="modal-header">
                <h5 class="modal-title">Send Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="ab_email_contact_id">
                <div class="mb-1 fw-bold" id="ab_email_contact_name"></div>
                <div class="mb-2 text-muted" id="ab_email_to"></div>

                <div class="small text-muted mb-2" id="ab_email_history_label">Loading history...</div>
                <div id="ab_email_history" class="ab-history"></div>

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

<!-- ✅ Bulk SMS Modal -->
<div class="modal fade" id="abBulkSmsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" onsubmit="ABMessaging.sendBulkSms(event)">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Text</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="mb-2">
                    <strong>Sending to:</strong>
                    <span id="ab_bulk_count">0</span> selected contacts
                </div>

                <textarea id="ab_bulk_sms_body" class="form-control" rows="5" placeholder="Type message to send to all selected..."></textarea>
                <div class="small text-muted mt-2">
                    This will queue a message record per contact (Phase 2 storage). Provider sending is later.
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

    // Bulk selected IDs
    var bulkSelected = new Set();

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

    function escapeHtml(s) {
        return String(s || '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function formatMeta(item) {
        var dt = item && item.created_at ? new Date(item.created_at) : null;
        var stamp = dt && !isNaN(dt.getTime()) ? dt.toLocaleString() : '';
        var status = item && item.status ? String(item.status) : '';
        return (stamp ? (stamp + (status ? ' · ' + status : '')) : status);
    }

    function renderHistory(targetId, labelId, items) {
        var box = document.getElementById(targetId);
        var label = document.getElementById(labelId);
        if (!box) return;

        if (!items || !items.length) {
            if (label) label.textContent = 'No messages yet.';
            box.innerHTML = '';
            return;
        }

        if (label) label.textContent = 'Showing all messages.';
        var html = '';
        for (var i = 0; i < items.length; i++) {
            var it = items[i];
            var direction = (it.direction || 'outbound');
            var cls = direction === 'inbound' ? 'inbound' : 'outbound';
            html += '<div class="ab-msg ' + cls + '">';
            html +=   '<div>' + escapeHtml(it.body || '') + '</div>';
            html +=   '<div class="ab-msg-meta">' + escapeHtml(formatMeta(it)) + '</div>';
            html += '</div>';
        }
        box.innerHTML = html;

        // scroll to bottom
        box.scrollTop = box.scrollHeight;
    }

    function fetchHistory(contactId, channel, targetId, labelId) {
        var url = '/contacts/' + contactId + '/messages?channel=' + encodeURIComponent(channel) + '&limit=200';
        return fetch(url, {
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (data) {
                if (!res.ok) throw new Error(data.message || ('Failed to load history (HTTP ' + res.status + ').'));
                return data;
            });
        })
        .then(function (data) {
            renderHistory(targetId, labelId, (data && data.items) ? data.items : []);
            return data;
        })
        .catch(function (err) {
            console.error(err);
            var label = document.getElementById(labelId);
            if (label) label.textContent = 'Failed to load history.';
            var box = document.getElementById(targetId);
            if (box) box.innerHTML = '';
        });
    }

    // Global so AJAX-loaded details.blade.php onclick handlers can call it
    window.ABMessaging = {
        openSms: function (contactId, name, phone) {
            try {
                document.getElementById('ab_sms_contact_id').value = contactId || '';
                document.getElementById('ab_sms_contact_name').textContent = name || '';
                document.getElementById('ab_sms_to').textContent = phone ? ('To: ' + phone) : 'No phone on file';
                document.getElementById('ab_sms_body').value = '';
                // Clear UI then load history
                renderHistory('ab_sms_history', 'ab_sms_history_label', []);
                document.getElementById('ab_sms_history_label').textContent = 'Loading history...';
                fetchHistory(contactId, 'sms', 'ab_sms_history', 'ab_sms_history_label');
                showModalById('abSmsModal');
            } catch (e) {
                console.error(e);
                alert('Failed to open Text modal. Check console.');
            }
        },

        openEmail: function (contactId, name, email) {
            try {
                document.getElementById('ab_email_contact_id').value = contactId || '';
                document.getElementById('ab_email_contact_name').textContent = name || '';
                document.getElementById('ab_email_to').textContent = email ? ('To: ' + email) : 'No email on file';
                document.getElementById('ab_email_subject').value = '';
                document.getElementById('ab_email_body').value = '';
                // Clear UI then load history
                renderHistory('ab_email_history', 'ab_email_history_label', []);
                document.getElementById('ab_email_history_label').textContent = 'Loading history...';
                fetchHistory(contactId, 'email', 'ab_email_history', 'ab_email_history_label');
                showModalById('abEmailModal');
            } catch (e) {
                console.error(e);
                alert('Failed to open Email modal. Check console.');
            }
        },

        sendSms: function (e) {
            e.preventDefault();

            var contactId = String(document.getElementById('ab_sms_contact_id').value || '').trim();
            var body = String(document.getElementById('ab_sms_body').value || '').trim();

            if (!body) return alert('Message is empty.');

            fetch('/contacts/' + contactId + '/messages', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
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
                document.getElementById('ab_sms_body').value = '';
                // Refresh history
                fetchHistory(contactId, 'sms', 'ab_sms_history', 'ab_sms_history_label');
                alert('Text saved to Messages (queued).');
            })
            .catch(function (err) {
                console.error(err);
                alert(err.message || 'SMS send failed.');
            });
        },

        sendEmail: function (e) {
            e.preventDefault();

            var contactId = String(document.getElementById('ab_email_contact_id').value || '').trim();
            var subject = String(document.getElementById('ab_email_subject').value || '').trim();
            var body = String(document.getElementById('ab_email_body').value || '').trim();

            if (!subject) return alert('Subject is required.');
            if (!body) return alert('Email body is empty.');

            fetch('/contacts/' + contactId + '/messages', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
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
                document.getElementById('ab_email_subject').value = '';
                document.getElementById('ab_email_body').value = '';
                // Refresh history
                fetchHistory(contactId, 'email', 'ab_email_history', 'ab_email_history_label');
                alert('Email saved to Messages (queued).');
            })
            .catch(function (err) {
                console.error(err);
                alert(err.message || 'Email send failed.');
            });
        },

        openBulkSms: function () {
            document.getElementById('ab_bulk_sms_body').value = '';
            document.getElementById('ab_bulk_count').textContent = String(bulkSelected.size);
            showModalById('abBulkSmsModal');
        },

        sendBulkSms: function (e) {
            e.preventDefault();

            var body = String(document.getElementById('ab_bulk_sms_body').value || '').trim();
            if (!body) return alert('Message is empty.');
            if (bulkSelected.size < 1) return alert('No contacts selected.');

            var ids = Array.from(bulkSelected);

            // Queue sequentially (simple + safe). If you want parallel later, we can do Promise.all with throttling.
            var idx = 0;
            function next() {
                if (idx >= ids.length) {
                    alert('Bulk Text queued for ' + ids.length + ' contacts.');
                    var inst = bootstrap.Modal.getInstance(document.getElementById('abBulkSmsModal'));
                    if (inst) inst.hide();
                    return;
                }

                var contactId = ids[idx++];
                fetch('/contacts/' + contactId + '/messages', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ channel: 'sms', body: body })
                })
                .then(function (res) {
                    return res.json().catch(function () { return {}; }).then(function (data) {
                        if (!res.ok) throw new Error(data.message || ('Failed on contact ' + contactId));
                        return data;
                    });
                })
                .then(next)
                .catch(function (err) {
                    console.error(err);
                    alert('Bulk queue failed: ' + (err.message || 'Unknown error'));
                });
            }

            next();
        }
    };

    function updateBulkUI() {
        var countEl = document.getElementById('bulk-selected-count');
        if (countEl) countEl.textContent = String(bulkSelected.size);

        var bulkBtn = document.getElementById('bulk-text-btn');
        if (bulkBtn) bulkBtn.disabled = bulkSelected.size < 1;
    }

    function setAllVisibleChecks(checked) {
        var checks = document.querySelectorAll('#book-list .js-bulk-check');
        for (var i = 0; i < checks.length; i++) {
            // Only toggle visible rows (search filter hides via display:none on the row container)
            var row = checks[i].closest('.contact-list-item');
            var visible = row && row.style.display !== 'none';
            if (!visible) continue;

            checks[i].checked = checked;
            var id = checks[i].getAttribute('data-id');
            if (checked) bulkSelected.add(String(id));
            else bulkSelected.delete(String(id));
        }
        updateBulkUI();
    }

    // Right-panel loader + page wiring
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

        // ✅ Row click loads panel, but checkbox click should NOT
        var rows = document.querySelectorAll('.js-book-row');
        for (var i = 0; i < rows.length; i++) {
            rows[i].addEventListener('click', function (e) {
                // If user clicked a checkbox, ignore row click
                if (e && e.target && e.target.classList && e.target.classList.contains('js-bulk-check')) {
                    return;
                }

                var all = document.querySelectorAll('.js-book-row');
                for (var j = 0; j < all.length; j++) all[j].classList.remove('active-contact-row');

                this.classList.add('active-contact-row');
                window.loadBookPanel(this.getAttribute('data-show-url'));
            });
        }

        // ✅ Checkbox selection handling
        var checks = document.querySelectorAll('.js-bulk-check');
        for (var c = 0; c < checks.length; c++) {
            checks[c].addEventListener('click', function (e) {
                // stop checkbox click from also triggering row click
                e.stopPropagation();

                var id = String(this.getAttribute('data-id'));
                if (this.checked) bulkSelected.add(id);
                else bulkSelected.delete(id);

                updateBulkUI();
            });
        }

        var selectAll = document.getElementById('bulk-select-all');
        if (selectAll) {
            selectAll.addEventListener('change', function () {
                setAllVisibleChecks(!!this.checked);
            });
        }

        var bulkBtn = document.getElementById('bulk-text-btn');
        if (bulkBtn) {
            bulkBtn.addEventListener('click', function () {
                window.ABMessaging.openBulkSms();
            });
        }

        // Add button
        var addBtn = document.getElementById('add-book-client-btn');
        if (addBtn) {
            addBtn.addEventListener('click', function () {
                window.loadBookPanel(this.getAttribute('data-create-url'));
            });
        }

        // Search filter (also affects Select All by only applying to visible rows)
        var searchEl = document.getElementById('book-search');
        if (searchEl) {
            searchEl.addEventListener('keyup', function () {
                var term = String(this.value || '').toLowerCase();
                var listRows = document.querySelectorAll('#book-list .js-book-row');
                for (var k = 0; k < listRows.length; k++) {
                    var show = listRows[k].textContent.toLowerCase().indexOf(term) !== -1;
                    listRows[k].style.display = show ? 'flex' : 'none';
                }
            });
        }

        if (SELECTED_ID) {
            window.loadBookPanel(BOOK_BASE_URL + '/' + String(SELECTED_ID));
        }

        if (SHOULD_REOPEN_UPLOAD) {
            showModalById('uploadBookModal');
        }

        updateBulkUI();
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
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
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

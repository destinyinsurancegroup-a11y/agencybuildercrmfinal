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

    /* ============================
       MESSAGE THREAD (MODAL)
       ============================ */
    .ab-thread {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #fafafa;
        padding: 10px;
        height: 220px;
        overflow-y: auto;
    }

    .ab-thread-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
    }

    .ab-thread-status {
        font-size: 12px;
        color: #6b7280;
        min-height: 16px;
    }

    .ab-msg-row {
        display: flex;
        margin: 6px 0;
    }
    .ab-msg-row.outbound { justify-content: flex-end; }
    .ab-msg-row.inbound  { justify-content: flex-start; }

    .ab-bubble {
        max-width: 78%;
        border-radius: 12px;
        padding: 8px 10px;
        font-size: 13px;
        line-height: 1.25rem;
        border: 1px solid #e5e7eb;
        background: #ffffff;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .ab-msg-row.outbound .ab-bubble {
        background: rgba(201,162,39,0.12);
        border-color: rgba(201,162,39,0.35);
    }

    .ab-meta {
        font-size: 11px;
        color: #6b7280;
        margin-top: 3px;
        text-align: right;
    }
    .ab-msg-row.inbound .ab-meta { text-align: left; }

    .ab-divider {
        height: 1px;
        background: #e5e7eb;
        margin: 10px 0;
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
                                   {{ (!empty($selected) && (int)$selected === (int)$client->id) ? 'active-contact-row' : '' }}
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
        <form class="modal-content" onsubmit="ABMessaging.sendSms(event)">
            <div class="modal-header">
                <h5 class="modal-title">Send Text</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="ab_sms_contact_id">
                <div class="mb-1 fw-bold" id="ab_sms_contact_name"></div>
                <div class="mb-2 text-muted" id="ab_sms_to"></div>

                <!-- THREAD -->
                <div class="ab-thread-top">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="ab_sms_load_more" style="display:none;">
                        Load earlier
                    </button>
                    <div class="ab-thread-status" id="ab_sms_status"></div>
                </div>
                <div class="ab-thread" id="ab_sms_thread"></div>

                <div class="ab-divider"></div>

                <textarea id="ab_sms_body" class="form-control" rows="3" placeholder="Type message..."></textarea>
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

                <!-- THREAD -->
                <div class="ab-thread-top">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="ab_email_load_more" style="display:none;">
                        Load earlier
                    </button>
                    <div class="ab-thread-status" id="ab_email_status"></div>
                </div>
                <div class="ab-thread" id="ab_email_thread"></div>

                <div class="ab-divider"></div>

                <input id="ab_email_subject" class="form-control mb-2" placeholder="Subject">
                <textarea id="ab_email_body" class="form-control" rows="5" placeholder="Type email..."></textarea>
                <div class="small text-muted mt-2">This will save a queued email to the database.</div>
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
(function () {
    'use strict';

    // Safe server-side values
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

    function qs(id) { return document.getElementById(id); }

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g,'&amp;')
            .replace(/</g,'&lt;')
            .replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;')
            .replace(/'/g,'&#039;');
    }

    function formatWhen(isoString) {
        if (!isoString) return '';
        try {
            var d = new Date(isoString);
            if (isNaN(d.getTime())) return '';
            return d.toLocaleString();
        } catch(e) { return ''; }
    }

    function scrollThreadToBottom(el) {
        if (!el) return;
        el.scrollTop = el.scrollHeight;
    }

    function renderMessageBubble(msg) {
        // msg: {id, channel, direction, status, body, subject, created_at ...}
        var dir = (msg && msg.direction === 'inbound') ? 'inbound' : 'outbound';
        var body = msg && msg.body ? msg.body : '';
        var subject = msg && msg.subject ? msg.subject : '';
        var when = formatWhen(msg && msg.created_at);
        var status = msg && msg.status ? msg.status : '';

        var bodyHtml = escapeHtml(body);

        // For email, show subject bold above body if present
        var subjectHtml = subject ? ('<div style="font-weight:700; margin-bottom:4px;">' + escapeHtml(subject) + '</div>') : '';

        var meta = [];
        if (when) meta.push(when);
        if (status && dir === 'outbound') meta.push(status);
        var metaHtml = meta.length ? ('<div class="ab-meta">' + escapeHtml(meta.join(' · ')) + '</div>') : '';

        return (
            '<div class="ab-msg-row ' + dir + '">' +
                '<div class="ab-bubble">' +
                    subjectHtml +
                    bodyHtml +
                    metaHtml +
                '</div>' +
            '</div>'
        );
    }

    // ==========================
    // Messaging state + helpers
    // ==========================
    var MessagingState = {
        sms: { nextBeforeId: null, hasMore: false, contactId: null },
        email: { nextBeforeId: null, hasMore: false, contactId: null }
    };

    function setStatus(kind, text) {
        var el = qs(kind === 'sms' ? 'ab_sms_status' : 'ab_email_status');
        if (el) el.textContent = text || '';
    }

    function setLoadMoreVisible(kind, visible) {
        var btn = qs(kind === 'sms' ? 'ab_sms_load_more' : 'ab_email_load_more');
        if (!btn) return;
        btn.style.display = visible ? 'inline-block' : 'none';
    }

    function getThreadEl(kind) {
        return qs(kind === 'sms' ? 'ab_sms_thread' : 'ab_email_thread');
    }

    function fetchHistory(kind, contactId, beforeId) {
        var url = '/contacts/' + encodeURIComponent(contactId) + '/messages'
            + '?channel=' + encodeURIComponent(kind)
            + '&limit=50';

        if (beforeId) {
            url += '&before_id=' + encodeURIComponent(beforeId);
        }

        return fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(res) {
            return res.json().catch(function(){ return {}; }).then(function(data){
                if (!res.ok) {
                    var msg = data && data.message ? data.message : ('Failed to load messages (HTTP ' + res.status + ')');
                    throw new Error(msg);
                }
                return data;
            });
        });
    }

    function loadThread(kind, contactId, opts) {
        opts = opts || {};
        var reset = !!opts.reset;
        var threadEl = getThreadEl(kind);

        if (!contactId) return;

        if (reset) {
            MessagingState[kind].nextBeforeId = null;
            MessagingState[kind].hasMore = false;
            MessagingState[kind].contactId = contactId;
            if (threadEl) threadEl.innerHTML = '';
            setLoadMoreVisible(kind, false);
        }

        setStatus(kind, 'Loading...');
        var beforeId = reset ? null : MessagingState[kind].nextBeforeId;

        return fetchHistory(kind, contactId, beforeId)
            .then(function(payload) {
                var items = payload && payload.items ? payload.items : [];
                var hasMore = !!(payload && payload.has_more);
                var nextBeforeId = payload && payload.next_before_id ? payload.next_before_id : null;

                MessagingState[kind].hasMore = hasMore;
                MessagingState[kind].nextBeforeId = nextBeforeId;

                setLoadMoreVisible(kind, hasMore);

                if (!threadEl) return;

                // If loading older (not reset), we prepend.
                var html = '';
                for (var i=0; i<items.length; i++) {
                    html += renderMessageBubble(items[i]);
                }

                if (reset) {
                    threadEl.innerHTML = html || '<div class="text-muted small">No messages yet.</div>';
                    scrollThreadToBottom(threadEl);
                } else {
                    // Prepend without losing scroll position too badly
                    var prevScroll = threadEl.scrollHeight;
                    threadEl.innerHTML = html + threadEl.innerHTML;
                    var newScroll = threadEl.scrollHeight;
                    threadEl.scrollTop = (newScroll - prevScroll);
                }

                setStatus(kind, hasMore ? 'Showing latest (load earlier for more).' : 'Showing all messages.');
            })
            .catch(function(err) {
                console.error(err);
                setStatus(kind, err && err.message ? err.message : 'Failed to load messages.');
                if (threadEl && !threadEl.innerHTML) {
                    threadEl.innerHTML = '<div class="text-muted small">Unable to load messages.</div>';
                }
            });
    }

    // Global so AJAX-loaded details.blade.php onclick handlers can call it
    window.ABMessaging = {
        openSms: function (contactId, name, phone) {
            try {
                qs('ab_sms_contact_id').value = contactId || '';
                qs('ab_sms_contact_name').textContent = name || '';
                qs('ab_sms_to').textContent = phone ? ('To: ' + phone) : 'No phone on file';
                qs('ab_sms_body').value = '';

                // Reset & load thread before showing
                loadThread('sms', contactId, { reset: true }).finally(function(){
                    showModalById('abSmsModal');
                });

            } catch (e) {
                console.error(e);
                alert('Failed to open Text modal. Check console.');
            }
        },

        openEmail: function (contactId, name, email) {
            try {
                qs('ab_email_contact_id').value = contactId || '';
                qs('ab_email_contact_name').textContent = name || '';
                qs('ab_email_to').textContent = email ? ('To: ' + email) : 'No email on file';
                qs('ab_email_subject').value = '';
                qs('ab_email_body').value = '';

                loadThread('email', contactId, { reset: true }).finally(function(){
                    showModalById('abEmailModal');
                });

            } catch (e) {
                console.error(e);
                alert('Failed to open Email modal. Check console.');
            }
        },

        loadMoreSms: function () {
            var contactId = String(qs('ab_sms_contact_id') ? qs('ab_sms_contact_id').value : '').trim();
            if (!contactId || !MessagingState.sms.hasMore) return;
            return loadThread('sms', contactId, { reset: false });
        },

        loadMoreEmail: function () {
            var contactId = String(qs('ab_email_contact_id') ? qs('ab_email_contact_id').value : '').trim();
            if (!contactId || !MessagingState.email.hasMore) return;
            return loadThread('email', contactId, { reset: false });
        },

        sendSms: function (e) {
            e.preventDefault();

            var contactIdEl = qs('ab_sms_contact_id');
            var bodyEl = qs('ab_sms_body');
            var threadEl = qs('ab_sms_thread');

            var contactId = contactIdEl ? String(contactIdEl.value || '').trim() : '';
            var body = bodyEl ? String(bodyEl.value || '').trim() : '';

            if (!body) return alert('Message is empty.');

            setStatus('sms', 'Sending...');

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
            .then(function (data) {
                // Append immediately to thread (new outbound)
                if (threadEl) {
                    var msg = data && data.message ? data.message : { direction: 'outbound', status: 'queued', body: body, created_at: new Date().toISOString() };
                    threadEl.innerHTML = (threadEl.innerHTML.indexOf('No messages yet') !== -1) ? '' : threadEl.innerHTML;
                    threadEl.innerHTML += renderMessageBubble(msg);
                    scrollThreadToBottom(threadEl);
                }

                if (bodyEl) bodyEl.value = '';
                setStatus('sms', 'Saved (queued).');

                // Optional: refresh from server to ensure canonical ordering/status
                loadThread('sms', contactId, { reset: true });

            })
            .catch(function (err) {
                console.error(err);
                setStatus('sms', err && err.message ? err.message : 'SMS send failed.');
                alert(err && err.message ? err.message : 'SMS send failed.');
            });
        },

        sendEmail: function (e) {
            e.preventDefault();

            var contactIdEl = qs('ab_email_contact_id');
            var subjectEl = qs('ab_email_subject');
            var bodyEl = qs('ab_email_body');
            var threadEl = qs('ab_email_thread');

            var contactId = contactIdEl ? String(contactIdEl.value || '').trim() : '';
            var subject = subjectEl ? String(subjectEl.value || '').trim() : '';
            var body = bodyEl ? String(bodyEl.value || '').trim() : '';

            if (!subject) return alert('Subject is required.');
            if (!body) return alert('Email body is empty.');

            setStatus('email', 'Sending...');

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
            .then(function (data) {
                if (threadEl) {
                    var msg = data && data.message ? data.message : { direction: 'outbound', status: 'queued', subject: subject, body: body, created_at: new Date().toISOString() };
                    threadEl.innerHTML = (threadEl.innerHTML.indexOf('No messages yet') !== -1) ? '' : threadEl.innerHTML;
                    threadEl.innerHTML += renderMessageBubble(msg);
                    scrollThreadToBottom(threadEl);
                }

                if (subjectEl) subjectEl.value = '';
                if (bodyEl) bodyEl.value = '';
                setStatus('email', 'Saved (queued).');

                loadThread('email', contactId, { reset: true });
            })
            .catch(function (err) {
                console.error(err);
                setStatus('email', err && err.message ? err.message : 'Email send failed.');
                alert(err && err.message ? err.message : 'Email send failed.');
            });
        }
    };

    // Wire "load earlier" buttons
    document.addEventListener('DOMContentLoaded', function () {
        var smsMore = qs('ab_sms_load_more');
        if (smsMore) smsMore.addEventListener('click', function(){ window.ABMessaging.loadMoreSms(); });

        var emailMore = qs('ab_email_load_more');
        if (emailMore) emailMore.addEventListener('click', function(){ window.ABMessaging.loadMoreEmail(); });
    });

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

        var rows = document.querySelectorAll('.js-book-row');
        for (var i = 0; i < rows.length; i++) {
            rows[i].addEventListener('click', function () {
                var all = document.querySelectorAll('.js-book-row');
                for (var j = 0; j < all.length; j++) all[j].classList.remove('active-contact-row');

                this.classList.add('active-contact-row');
                window.loadBookPanel(this.getAttribute('data-show-url'));
            });
        }

        var addBtn = document.getElementById('add-book-client-btn');
        if (addBtn) {
            addBtn.addEventListener('click', function () {
                window.loadBookPanel(this.getAttribute('data-create-url'));
            });
        }

        var searchEl = document.getElementById('book-search');
        if (searchEl) {
            searchEl.addEventListener('keyup', function () {
                var term = String(this.value || '').toLowerCase();
                var listRows = document.querySelectorAll('#book-list .js-book-row');
                for (var k = 0; k < listRows.length; k++) {
                    var show = listRows[k].textContent.toLowerCase().indexOf(term) !== -1;
                    listRows[k].style.display = show ? 'block' : 'none';
                }
            });
        }

        if (SELECTED_ID) {
            window.loadBookPanel(BOOK_BASE_URL + '/' + String(SELECTED_ID));
        }

        if (SHOULD_REOPEN_UPLOAD) {
            showModalById('uploadBookModal');
        }
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

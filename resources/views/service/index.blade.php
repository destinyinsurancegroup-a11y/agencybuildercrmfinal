@extends('layouts.app')

@section('content')

<style>
    /* Same layout as Book */
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
        text-decoration: none;
    }
    .btn-gold:hover {
        background: #b5901f;
    }

    /* ✅ Matches Book/Contacts/Leads outline buttons */
    .btn-outline-gold {
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
        text-decoration:none;
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
        align-items: flex-start;
        gap: 10px;
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

    .flash-wrap {
        margin-bottom: 14px;
    }

    /* ✅ Bulk UI row */
    .bulk-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin: 10px 0 12px;
    }

    .bulk-row label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #6b7280;
        user-select: none;
    }

    .bulk-row .right {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .bulk-row .count {
        font-size: 13px;
        color: #6b7280;
    }

    /* ✅ History bubble styling inside messaging modals */
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

                <!-- HEADER + ARCHIVE BUTTON -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="contacts-header mb-0">
                        Service Clients
                    </div>

                    <a href="{{ route('service.archive') }}" class="btn-gold btn-sm">
                        View Service Archive
                    </a>
                </div>

                {{-- ✅ FLASH MESSAGES (match Book/Leads behavior) --}}
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

                <!-- Search -->
                <div class="contacts-search-wrapper">
                    <input
                        type="text"
                        id="service-search"
                        class="contacts-search-input"
                        placeholder="Search service clients..."
                    >
                    <button class="contacts-search-btn" disabled>Go</button>
                </div>

                <!-- Add Client + Upload -->
                <div class="button-row">
                    <button
                        id="add-service-client-btn"
                        class="btn-gold"
                        data-create-url="{{ route('service.create.panel') }}"
                    >
                        Add
                    </button>

                    <button
                        class="btn-gold"
                        data-bs-toggle="modal"
                        data-bs-target="#uploadServiceModal"
                    >
                        Upload
                    </button>
                </div>

                {{-- ✅ BULK SELECT ROW --}}
                <div class="bulk-row">
                    <label>
                        <input type="checkbox" id="service-select-all">
                        Select all
                    </label>

                    <div class="right">
                        <div class="count">
                            Selected: <span id="service-selected-count">0</span>
                        </div>
                        <button type="button" class="btn-outline-gold" id="service-bulk-text-btn" disabled>
                            Bulk Text
                        </button>
                    </div>
                </div>

                <!-- Client List -->
                <div id="service-list">
                    @forelse ($clients as $client)
                        @php
                            $name = $client->full_name ?? trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? ''));
                        @endphp

                        <div
                            class="contact-list-item js-service-row {{ (isset($selected) && $selected == $client->id) ? 'active-contact-row' : '' }}"
                            data-id="{{ $client->id }}"
                            data-show-url="{{ route('service.show', $client->id) }}"
                        >
                            {{-- ✅ Per-row checkbox --}}
                            <input type="checkbox"
                                   class="service-row-checkbox"
                                   data-id="{{ $client->id }}"
                                   onclick="event.stopPropagation();">

                            <div>
                                {{ $name ?: '(No Name)' }}

                                @if($client->policy_type)
                                    <br><small class="text-muted">{{ $client->policy_type }}</small>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">No service clients found.</p>
                    @endforelse
                </div>

            </div>
        </div>

        <!-- RIGHT PANEL -->
        <div class="col-md-8 col-lg-9">
            <div id="service-details-container" style="width:100%; min-height:400px;">
                <div class="empty-right-panel"></div>
            </div>
        </div>

    </div>
</div>

<!-- ✅ UPLOAD SERVICE MODAL -->
<div class="modal fade" id="uploadServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <form
            action="{{ route('service.import') }}"
            method="POST"
            enctype="multipart/form-data"
            class="modal-content"
        >
            @csrf

            <div class="modal-header bg-black text-gold">
                <h5 class="modal-title">Upload Service Clients</h5>
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

{{-- ✅ BULK TEXT MODAL --}}
<div class="modal fade" id="serviceBulkTextModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" onsubmit="return false;">
            <div class="modal-header bg-black text-gold">
                <h5 class="modal-title">Bulk Text</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="small text-muted mb-2">
                    Sending to <strong><span id="service-bulk-count">0</span></strong> selected contacts.
                </div>
                <textarea id="service-bulk-body" class="form-control" rows="4" placeholder="Type message..."></textarea>
                <div class="small text-muted mt-2">
                    This will queue outbound SMS messages in the database (Phase 2/3).
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-gold" id="service-bulk-send-btn">Send</button>
            </div>
        </form>
    </div>
</div>

{{-- ✅ Messaging Modals (shared once per page) --}}
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

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

    const container = document.getElementById('service-details-container');
    const CSRF_TOKEN = @json(csrf_token());

    /* ===== LOAD RIGHT PANEL ===== */
    window.loadServicePanel = function (url) {
        container.innerHTML = `
            <div style="padding:40px; text-align:center;">
                <div class="spinner-border text-warning" role="status"></div>
                <p class="mt-3 text-muted">Loading...</p>
            </div>
        `;

        fetch(url, { headers: {'X-Requested-With': 'XMLHttpRequest'} })
            .then(res => res.text())
            .then(html => {
                container.innerHTML = html;

                // ✅ Ensure injected scripts execute (notes JS)
                const scripts = container.querySelectorAll('script');
                scripts.forEach(oldScript => {
                    const newScript = document.createElement('script');
                    for (const attr of oldScript.attributes) newScript.setAttribute(attr.name, attr.value);
                    newScript.text = oldScript.textContent;
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });
            })
            .catch(() => {
                container.innerHTML = `
                    <div style="padding:40px; text-align:center; color:red;">
                        Failed to load.
                    </div>
                `;
            });
    };

    /* ===== CLICK A CLIENT ===== */
    document.querySelectorAll('.js-service-row').forEach(row => {
        row.addEventListener('click', () => {
            document.querySelectorAll('.js-service-row')
                .forEach(r => r.classList.remove('active-contact-row'));

            row.classList.add('active-contact-row');
            loadServicePanel(row.dataset.showUrl);
        });
    });

    /* ===== ADD CLIENT BUTTON ===== */
    const addBtn = document.getElementById('add-service-client-btn');
    if (addBtn) {
        addBtn.addEventListener('click', function () {
            loadServicePanel(this.dataset.createUrl);
        });
    }

    /* ===== CLIENT SIDE SEARCH ===== */
    const searchEl = document.getElementById('service-search');
    if (searchEl) {
        searchEl.addEventListener('keyup', function () {
            const term = this.value.toLowerCase();
            document.querySelectorAll('#service-list .js-service-row')
                .forEach(row => {
                    row.style.display = row.textContent.toLowerCase().includes(term)
                        ? 'flex'
                        : 'none';
                });
        });
    }

    /* ✅ If upload had errors, reopen modal */
    @if(session('import_error') || $errors->any())
        const modalEl = document.getElementById('uploadServiceModal');
        if (modalEl) new bootstrap.Modal(modalEl).show();
    @endif

    /* ✅ AUTO-LOAD SELECTED */
    @if(!empty($selected))
        const selectedRow = document.querySelector(`.js-service-row[data-id="{{ $selected }}"]`);
        if (selectedRow) {
            document.querySelectorAll('.js-service-row')
                .forEach(r => r.classList.remove('active-contact-row'));
            selectedRow.classList.add('active-contact-row');
            loadServicePanel(selectedRow.dataset.showUrl);
        } else {
            loadServicePanel("{{ route('service.show', $selected) }}");
        }
    @endif

    // ============================================================
    // ✅ BULK SELECT + BULK TEXT (uses POST /contacts/messages/bulk)
    // ============================================================
    const selectedIds = new Set();

    function refreshBulkUi() {
        const count = selectedIds.size;
        const countEl = document.getElementById('service-selected-count');
        if (countEl) countEl.textContent = String(count);

        const btn = document.getElementById('service-bulk-text-btn');
        if (btn) btn.disabled = count === 0;
    }

    document.querySelectorAll('.service-row-checkbox').forEach(cb => {
        cb.addEventListener('change', () => {
            const id = String(cb.dataset.id);
            if (cb.checked) selectedIds.add(id);
            else selectedIds.delete(id);
            refreshBulkUi();
        });
    });

    const selectAll = document.getElementById('service-select-all');
    if (selectAll) {
        selectAll.addEventListener('change', () => {
            const checked = selectAll.checked;
            document.querySelectorAll('.service-row-checkbox').forEach(cb => {
                cb.checked = checked;
                const id = String(cb.dataset.id);
                if (checked) selectedIds.add(id);
                else selectedIds.delete(id);
            });
            refreshBulkUi();
        });
    }

    const bulkBtn = document.getElementById('service-bulk-text-btn');
    if (bulkBtn) {
        bulkBtn.addEventListener('click', () => {
            document.getElementById('service-bulk-count').textContent = String(selectedIds.size);
            document.getElementById('service-bulk-body').value = '';
            new bootstrap.Modal(document.getElementById('serviceBulkTextModal')).show();
        });
    }

    const bulkSendBtn = document.getElementById('service-bulk-send-btn');
    if (bulkSendBtn) {
        bulkSendBtn.addEventListener('click', () => {
            const body = (document.getElementById('service-bulk-body').value || '').trim();
            if (!body) return alert('Message is empty.');

            fetch('/contacts/messages/bulk', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ contact_ids: Array.from(selectedIds), body })
            })
            .then(r => r.json().catch(() => ({})).then(data => {
                if (!r.ok) throw new Error(data.message || 'Bulk send failed.');
                return data;
            }))
            .then(data => {
                const queued = data.queued || 0;
                const skippedNoPhone = (data.skipped && data.skipped.no_phone) ? data.skipped.no_phone : 0;
                alert(`Queued ${queued} texts. Skipped (no phone): ${skippedNoPhone}`);

                // Clear selections
                selectedIds.clear();
                document.querySelectorAll('.service-row-checkbox').forEach(cb => cb.checked = false);
                const selAll = document.getElementById('service-select-all');
                if (selAll) selAll.checked = false;
                refreshBulkUi();

                bootstrap.Modal.getInstance(document.getElementById('serviceBulkTextModal'))?.hide();
            })
            .catch(err => {
                console.error(err);
                alert(err.message || 'Bulk send failed.');
            });
        });
    }

    refreshBulkUi();

    // ============================================================
    // ✅ ABMessaging (for Service details buttons -> uses /contacts/{id}/messages)
    // ============================================================
    function hasBootstrapModal() { return !!(window.bootstrap && window.bootstrap.Modal); }

    function showModalById(id) {
        if (!hasBootstrapModal()) return alert('Bootstrap modal JS missing.');
        const el = document.getElementById(id);
        if (!el) return alert('Missing modal: #' + id);
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
        const dt = item && item.created_at ? new Date(item.created_at) : null;
        const stamp = dt && !isNaN(dt.getTime()) ? dt.toLocaleString() : '';
        const status = item && item.status ? String(item.status) : '';
        return (stamp ? (stamp + (status ? ' · ' + status : '')) : status);
    }

    function renderHistory(targetId, labelId, items) {
        const box = document.getElementById(targetId);
        const label = document.getElementById(labelId);
        if (!box) return;

        if (!items || !items.length) {
            if (label) label.textContent = 'No messages yet.';
            box.innerHTML = '';
            return;
        }

        if (label) label.textContent = 'Showing all messages.';
        let html = '';
        for (let i = 0; i < items.length; i++) {
            const it = items[i];
            const direction = (it.direction || 'outbound');
            const cls = direction === 'inbound' ? 'inbound' : 'outbound';
            html += '<div class="ab-msg ' + cls + '">';
            html +=   '<div>' + escapeHtml(it.body || '') + '</div>';
            html +=   '<div class="ab-msg-meta">' + escapeHtml(formatMeta(it)) + '</div>';
            html += '</div>';
        }
        box.innerHTML = html;
        box.scrollTop = box.scrollHeight;
    }

    function fetchHistory(contactId, channel, targetId, labelId) {
        const url = '/contacts/' + contactId + '/messages?channel=' + encodeURIComponent(channel) + '&limit=200';
        return fetch(url, {
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json().catch(() => ({})).then(data => {
            if (!res.ok) throw new Error(data.message || ('Failed to load history (HTTP ' + res.status + ').'));
            return data;
        }))
        .then(data => {
            renderHistory(targetId, labelId, (data && data.items) ? data.items : []);
            return data;
        })
        .catch(err => {
            console.error(err);
            const label = document.getElementById(labelId);
            if (label) label.textContent = 'Failed to load history.';
            const box = document.getElementById(targetId);
            if (box) box.innerHTML = '';
        });
    }

    window.ABMessaging = window.ABMessaging || {
        openSms: function (contactId, name, phone) {
            document.getElementById('ab_sms_contact_id').value = contactId || '';
            document.getElementById('ab_sms_contact_name').textContent = name || '';
            document.getElementById('ab_sms_to').textContent = phone ? ('To: ' + phone) : 'No phone on file';
            document.getElementById('ab_sms_body').value = '';

            renderHistory('ab_sms_history', 'ab_sms_history_label', []);
            document.getElementById('ab_sms_history_label').textContent = 'Loading history...';
            fetchHistory(contactId, 'sms', 'ab_sms_history', 'ab_sms_history_label');

            showModalById('abSmsModal');
        },

        openEmail: function (contactId, name, email) {
            document.getElementById('ab_email_contact_id').value = contactId || '';
            document.getElementById('ab_email_contact_name').textContent = name || '';
            document.getElementById('ab_email_to').textContent = email ? ('To: ' + email) : 'No email on file';
            document.getElementById('ab_email_subject').value = '';
            document.getElementById('ab_email_body').value = '';

            renderHistory('ab_email_history', 'ab_email_history_label', []);
            document.getElementById('ab_email_history_label').textContent = 'Loading history...';
            fetchHistory(contactId, 'email', 'ab_email_history', 'ab_email_history_label');

            showModalById('abEmailModal');
        },

        sendSms: function (e) {
            e.preventDefault();
            const contactId = String(document.getElementById('ab_sms_contact_id').value || '').trim();
            const body = String(document.getElementById('ab_sms_body').value || '').trim();
            if (!body) return alert('Message is empty.');

            fetch('/contacts/' + contactId + '/messages', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ channel: 'sms', body })
            })
            .then(res => res.json().catch(() => ({})).then(data => {
                if (!res.ok) throw new Error(data.message || ('Failed to save SMS (HTTP ' + res.status + ').'));
                return data;
            }))
            .then(() => {
                document.getElementById('ab_sms_body').value = '';
                fetchHistory(contactId, 'sms', 'ab_sms_history', 'ab_sms_history_label');
                alert('Text saved to Messages (queued).');
            })
            .catch(err => {
                console.error(err);
                alert(err.message || 'SMS send failed.');
            });
        },

        sendEmail: function (e) {
            e.preventDefault();
            const contactId = String(document.getElementById('ab_email_contact_id').value || '').trim();
            const subject = String(document.getElementById('ab_email_subject').value || '').trim();
            const body = String(document.getElementById('ab_email_body').value || '').trim();
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
                body: JSON.stringify({ channel: 'email', subject, body })
            })
            .then(res => res.json().catch(() => ({})).then(data => {
                if (!res.ok) throw new Error(data.message || ('Failed to save Email (HTTP ' + res.status + ').'));
                return data;
            }))
            .then(() => {
                document.getElementById('ab_email_subject').value = '';
                document.getElementById('ab_email_body').value = '';
                fetchHistory(contactId, 'email', 'ab_email_history', 'ab_email_history_label');
                alert('Email saved to Messages (queued).');
            })
            .catch(err => {
                console.error(err);
                alert(err.message || 'Email send failed.');
            });
        }
    };
});
</script>
@endpush

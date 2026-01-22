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

    /* Optional: we build a selected list with phone numbers, but keep it hidden */
    .bulk-selected-list {
        display: none;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 10px;
        max-height: 180px;
        overflow-y: auto;
        background: #fafafa;
        font-size: 13px;
    }
    .bulk-selected-row {
        display:flex;
        justify-content: space-between;
        gap: 10px;
        padding: 6px 0;
        border-bottom: 1px solid #eee;
    }
    .bulk-selected-row:last-child { border-bottom: none; }
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

                {{-- ✅ FLASH MESSAGES --}}
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
                        type="button"
                    >
                        Add
                    </button>

                    <button
                        class="btn-gold"
                        data-bs-toggle="modal"
                        data-bs-target="#uploadServiceModal"
                        type="button"
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
                            $phone = $client->phone ?? '';
                        @endphp

                        <div
                            class="contact-list-item js-service-row {{ (isset($selected) && $selected == $client->id) ? 'active-contact-row' : '' }}"
                            data-id="{{ $client->id }}"
                            data-show-url="{{ route('service.show', $client->id) }}"
                        >
                            <input type="checkbox"
                                   class="service-row-checkbox"
                                   data-id="{{ $client->id }}"
                                   data-name="{{ $name }}"
                                   data-phone="{{ $phone }}"
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
                    <span class="ms-2">Valid phones: <strong><span id="service-bulk-valid-phones">0</span></strong></span>
                </div>

                <div class="mb-2">
                    <label class="form-label small text-muted mb-1">Template</label>
                    <select id="service-bulk-template" class="form-select">
                        <option value="">— Select a template —</option>
                    </select>
                    <div class="small text-muted mt-1">
                        Selecting a template will fill the message. You can still edit before sending.
                    </div>
                </div>

                <div id="service-bulk-selected-list" class="bulk-selected-list mb-2"></div>

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

@include('partials.messaging')

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('service-details-container');
    const CSRF_TOKEN = @json(csrf_token());

    // ✅ Track which panel is currently open so we can refresh it after upload/delete
    window.__servicePanelUrl = window.__servicePanelUrl || null;

    /* ===== LOAD RIGHT PANEL ===== */
    window.loadServicePanel = function (url) {
        if (!container) return;

        window.__servicePanelUrl = url; // ✅ remember current open card URL

        container.innerHTML = `
            <div style="padding:40px; text-align:center;">
                <div class="spinner-border text-warning" role="status"></div>
                <p class="mt-3 text-muted">Loading...</p>
            </div>
        `;

        fetch(url, { headers: {'X-Requested-With': 'XMLHttpRequest'} })
            .then(res => res.text())
            .then(html => container.innerHTML = html)
            .catch(() => {
                container.innerHTML = `
                    <div style="padding:40px; text-align:center; color:red;">
                        Failed to load.
                    </div>
                `;
            });
    };

    // ============================================================
    // ✅ CRITICAL FIX (INSTANT UPDATE + CARD STAYS OPEN)
    // Intercept attachment UPLOAD and DELETE inside the right panel
    // and run them via fetch(), then reload the panel in-place.
    // ============================================================

    // 1) Intercept file input change (prevents inline onchange form.submit navigation)
    document.addEventListener('change', async (e) => {
        const el = e.target;
        if (!el) return;

        // matches: <input id="ab-attach-input-123" type="file" ...>
        if (el.matches('input[type="file"][id^="ab-attach-input-"]')) {
            // stop the inline onchange handler from running (prevents navigation)
            e.preventDefault();
            e.stopPropagation();
            if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();

            const formId = el.id.replace('ab-attach-input-', 'ab-attach-form-');
            const form = document.getElementById(formId);
            if (!form) return;

            if (!el.files || el.files.length === 0) return;

            try {
                const fd = new FormData(form);
                // Ensure chosen files are included (some browsers require explicit set)
                // form already includes files[] but this keeps it safe:
                for (const f of el.files) fd.append('files[]', f);

                const resp = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    },
                    body: fd,
                });

                // Whether controller returns redirect HTML or not, refresh the open panel.
                if (!resp.ok) throw new Error('Upload failed');
                if (window.__servicePanelUrl) window.loadServicePanel(window.__servicePanelUrl);

                // reset the input so selecting same file again still triggers change
                el.value = '';
            } catch (err) {
                console.error(err);
                alert('Upload failed.');
            }
        }
    }, true); // ✅ capture phase so we beat inline handlers

    // 2) Intercept delete form submit for attachments (the "x" button)
    document.addEventListener('submit', async (e) => {
        const form = e.target;
        if (!form) return;

        // delete forms in your chips are:
        // action="/attachments/{id}" + hidden _method=DELETE
        const methodSpoof = form.querySelector('input[name="_method"]');
        const isDelete = methodSpoof && String(methodSpoof.value || '').toUpperCase() === 'DELETE';
        const isAttachmentDelete = isDelete && (form.action || '').includes('/attachments/');

        if (isAttachmentDelete) {
            e.preventDefault();
            e.stopPropagation();
            if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();

            // keep your confirm() behavior:
            // if user cancelled, browser would not submit; we must emulate that:
            // Note: your form already had onsubmit confirm; but since we intercept, run confirm here too.
            if (!confirm('Delete this file?')) return;

            try {
                const fd = new FormData(form);

                const resp = await fetch(form.action, {
                    method: 'POST', // method spoof via _method=DELETE
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    },
                    body: fd
                });

                if (!resp.ok) throw new Error('Delete failed');
                if (window.__servicePanelUrl) window.loadServicePanel(window.__servicePanelUrl);
            } catch (err) {
                console.error(err);
                alert('Delete failed.');
            }
        }
    }, true); // ✅ capture phase

    /* ===== CLICK A CLIENT ===== */
    document.querySelectorAll('.js-service-row').forEach(row => {
        row.addEventListener('click', (e) => {
            if (e && e.target && e.target.classList && e.target.classList.contains('service-row-checkbox')) return;

            document.querySelectorAll('.js-service-row')
                .forEach(r => r.classList.remove('active-contact-row'));

            row.classList.add('active-contact-row');
            window.loadServicePanel(row.dataset.showUrl);
        });
    });

    /* ===== ADD CLIENT BUTTON ===== */
    const addBtn = document.getElementById('add-service-client-btn');
    if (addBtn) {
        addBtn.addEventListener('click', function () {
            window.loadServicePanel(this.dataset.createUrl);
        });
    }

    /* ===== CLIENT SIDE SEARCH ===== */
    const searchEl = document.getElementById('service-search');
    if (searchEl) {
        searchEl.addEventListener('keyup', function () {
            const term = (this.value || '').toLowerCase();
            document.querySelectorAll('#service-list .js-service-row')
                .forEach(row => {
                    row.style.display = row.textContent.toLowerCase().includes(term)
                        ? 'flex'
                        : 'none';
                });

            const selAll = document.getElementById('service-select-all');
            if (selAll) selAll.checked = false;
        });
    }

    /* ✅ If upload had errors, reopen modal */
    @if(session('import_error') || $errors->any())
        const modalEl = document.getElementById('uploadServiceModal');
        if (modalEl && window.bootstrap && window.bootstrap.Modal) new bootstrap.Modal(modalEl).show();
    @endif

    /* ✅ AUTO-LOAD SELECTED */
    @if(!empty($selected))
        window.loadServicePanel("{{ route('service.show', $selected) }}");
    @endif

    // ============================================================
    // ✅ BULK SELECT + BULK TEXT + BULK TEMPLATES (SMS)
    // (unchanged)
    // ============================================================
    const selectedIds = new Set();
    const selectedMeta = new Map(); // id -> {name, phone}

    function normalizePhone(p) {
        return String(p || '').trim();
    }

    function refreshBulkUi() {
        const count = selectedIds.size;

        const countEl = document.getElementById('service-selected-count');
        if (countEl) countEl.textContent = String(count);

        const btn = document.getElementById('service-bulk-text-btn');
        if (btn) btn.disabled = count === 0;
    }

    function rebuildSelectedListInModal() {
        const listEl = document.getElementById('service-bulk-selected-list');
        const validEl = document.getElementById('service-bulk-valid-phones');
        if (!listEl || !validEl) return;

        listEl.innerHTML = '';
        let validPhones = 0;

        Array.from(selectedIds).forEach(id => {
            const meta = selectedMeta.get(String(id)) || {};
            const name = meta.name || ('Contact #' + id);
            const phone = normalizePhone(meta.phone);

            if (phone) validPhones++;

            const row = document.createElement('div');
            row.className = 'bulk-selected-row';
            row.innerHTML = `
                <div>${escapeHtml(name)}</div>
                <div>${escapeHtml(phone || '')}</div>
            `;
            listEl.appendChild(row);
        });

        validEl.textContent = String(validPhones);
    }

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    document.querySelectorAll('.service-row-checkbox').forEach(cb => {
        cb.addEventListener('change', () => {
            const id = String(cb.dataset.id);
            const name = String(cb.dataset.name || '');
            const phone = String(cb.dataset.phone || '');

            if (cb.checked) {
                selectedIds.add(id);
                selectedMeta.set(id, { name, phone });
            } else {
                selectedIds.delete(id);
                selectedMeta.delete(id);
            }

            refreshBulkUi();
        });
    });

    const selectAll = document.getElementById('service-select-all');
    if (selectAll) {
        selectAll.addEventListener('change', () => {
            const checked = !!selectAll.checked;

            document.querySelectorAll('#service-list .js-service-row').forEach(row => {
                if (row.style.display === 'none') return;

                const cb = row.querySelector('.service-row-checkbox');
                if (!cb) return;

                cb.checked = checked;

                const id = String(cb.dataset.id);
                const name = String(cb.dataset.name || '');
                const phone = String(cb.dataset.phone || '');

                if (checked) {
                    selectedIds.add(id);
                    selectedMeta.set(id, { name, phone });
                } else {
                    selectedIds.delete(id);
                    selectedMeta.delete(id);
                }
            });

            refreshBulkUi();
        });
    }

    const tplSelect = document.getElementById('service-bulk-template');
    const tplBody   = document.getElementById('service-bulk-body');

    function clearTemplateOptions() {
        if (!tplSelect) return;
        while (tplSelect.options.length > 1) tplSelect.remove(1);
        tplSelect.value = '';
    }

    function populateTemplates(items) {
        if (!tplSelect) return;
        clearTemplateOptions();
        (items || []).forEach(t => {
            const opt = document.createElement('option');
            opt.value = String(t.id);
            opt.textContent = t.name || ('Template #' + t.id);
            tplSelect.appendChild(opt);
        });
    }

    function loadSmsTemplates() {
        return fetch('/settings/messaging/templates/json?channel=sms', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json().catch(() => ({})).then(d => {
            if (!r.ok || !d.success) throw new Error(d.message || 'Failed to load templates.');
            return d.items || [];
        }))
        .catch(err => {
            console.error(err);
            return [];
        });
    }

    function loadTemplateById(id) {
        return fetch(`/settings/messaging/templates/${encodeURIComponent(id)}/json`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json().catch(() => ({})).then(d => {
            if (!r.ok || !d.success) throw new Error(d.message || 'Failed to load template.');
            return d.item;
        }));
    }

    const bulkBtn = document.getElementById('service-bulk-text-btn');
    if (bulkBtn) {
        bulkBtn.addEventListener('click', async () => {
            document.getElementById('service-bulk-count').textContent = String(selectedIds.size);

            if (tplBody) tplBody.value = '';
            clearTemplateOptions();

            rebuildSelectedListInModal();

            const items = await loadSmsTemplates();
            populateTemplates(items);

            new bootstrap.Modal(document.getElementById('serviceBulkTextModal')).show();
        });
    }

    if (tplSelect) {
        tplSelect.addEventListener('change', async () => {
            const id = String(tplSelect.value || '');
            if (!id) return;

            try {
                const item = await loadTemplateById(id);
                if (!item || item.channel !== 'sms') {
                    alert('That template does not match SMS.');
                    tplSelect.value = '';
                    return;
                }
                if (tplBody) tplBody.value = String(item.body || '');
            } catch (e) {
                console.error(e);
                alert(e.message || 'Failed to load template.');
            }
        });
    }

    const bulkSendBtn = document.getElementById('service-bulk-send-btn');
    if (bulkSendBtn) {
        bulkSendBtn.addEventListener('click', () => {
            const body = (tplBody?.value || '').trim();
            if (!body) return alert('Message is empty.');
            if (selectedIds.size < 1) return alert('No contacts selected.');

            let validPhones = 0;
            selectedMeta.forEach(meta => {
                if (normalizePhone(meta.phone)) validPhones++;
            });
            if (validPhones < 1) {
                return alert('None of the selected contacts have a phone number.');
            }

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

                selectedIds.clear();
                selectedMeta.clear();
                document.querySelectorAll('.service-row-checkbox').forEach(cb => cb.checked = false);

                if (selectAll) selectAll.checked = false;
                refreshBulkUi();

                const inst = bootstrap.Modal.getInstance(document.getElementById('serviceBulkTextModal'));
                if (inst) inst.hide();
            })
            .catch(err => {
                console.error(err);
                alert(err.message || 'Bulk send failed.');
            });
        });
    }

    refreshBulkUi();
});
</script>
@endpush

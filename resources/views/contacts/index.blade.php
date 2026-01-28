@extends('layouts.app')

@section('content')

<style>
    :root {
        --ab-bg-light: #f3f4f6;
        --ab-card-bg: #ffffff;
        --ab-border-subtle: #e5e7eb;
        --ab-text-main: #111827;
        --ab-text-muted: #6b7280;
        --ab-gold: #c9a227;
        --ab-gold-dark: #b5901f;
        --ab-gold-soft: #f3e6b8;
        --ab-shadow-soft:
            0 18px 30px -12px rgba(0,0,0,0.35),
            0 8px 16px -8px rgba(0,0,0,0.18);
    }

    .dashboard-page { padding-top: 10px; }

    .contacts-card-wrapper {
        width: 320px !important;
        max-width: 320px !important;
    }

    .contacts-card {
        background: var(--ab-card-bg);
        border-radius: 18px;
        padding: 22px 20px 18px;
        height: calc(100vh - 120px);
        overflow-y: auto;
        box-shadow: var(--ab-shadow-soft);
        border: 1px solid var(--ab-border-subtle);
        display: flex;
        flex-direction: column;
    }

    .contacts-header {
        font-size: 22px;
        font-weight: 700;
        color: var(--ab-text-main);
        margin-bottom: 16px;
        letter-spacing: 0.01em;
    }

    .contacts-search-wrapper {
        display: flex;
        align-items: stretch;
        gap: 10px;
        margin-bottom: 14px;
    }

    .contacts-search-input {
        width: 100%;
        padding: 10px 12px;
        border-radius: 999px;
        border: 1px solid #d1d5db;
        background: #ffffff;
        font-size: 14px;
        outline: none;
        transition: box-shadow 0.15s ease, border-color 0.15s ease;
    }

    .contacts-search-input:focus {
        border-color: var(--ab-gold);
        box-shadow: 0 0 0 1px rgba(201,162,39,0.3);
    }

    .contacts-search-btn {
        padding: 0 18px;
        border-radius: 999px;
        border: none;
        background: var(--ab-gold);
        color: #111827;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        text-transform: uppercase;
        letter-spacing: 0.06em;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .contacts-search-btn:hover { background: var(--ab-gold-dark); }

    .btn-gold {
        background: var(--ab-gold);
        color: #111827;
        border: none;
        padding: 8px 16px;
        font-weight: 600;
        border-radius: 999px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.20);
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.07em;
        cursor: pointer;
    }
    .btn-gold:hover { background: var(--ab-gold-dark); }

    .btn-outline-gold {
        background: transparent;
        color: var(--ab-gold);
        border: 1px solid var(--ab-gold);
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

    .contacts-list-scroll {
        flex: 1;
        overflow-y: auto;
        padding-right: 2px;
    }

    .contact-list-item {
        padding: 10px 6px;
        font-size: 14px;
        border-radius: 10px;
        cursor: pointer;
        color: var(--ab-text-main);
        display: flex;
        align-items: flex-start;
        gap: 10px;
        transition: background 0.12s ease, transform 0.08s ease;
    }

    .contact-list-item:hover {
        background: #f9fafb;
        transform: translateY(-1px);
    }

    .active-contact-row {
        background: var(--ab-gold-soft) !important;
        font-weight: 700;
        box-shadow: 0 0 0 1px rgba(201,162,39,0.4);
    }

    .contact-list-empty { font-size: 13px; color: var(--ab-text-muted); }

    #contact-details-container { width: 100%; min-height: 400px; }

    .empty-right-panel {
        height: calc(100vh - 120px);
        border-radius: 18px;
        border: 1px dashed #d1d5db;
        background: radial-gradient(circle at top left, #fdf6e3, #f3f4f6);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        text-align: center;
        padding: 32px;
        color: var(--ab-text-muted);
    }

    .empty-right-panel h2 {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 6px;
        color: var(--ab-text-main);
    }

    /* Bulk row */
    .bulk-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin: 8px 0 12px;
    }
    .bulk-row label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: var(--ab-text-muted);
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
        color: var(--ab-text-muted);
    }
</style>

<div class="dashboard-page">
    <div class="row g-4">

        <!-- LEFT COLUMN -->
        <div class="col-md-4 col-lg-3 contacts-card-wrapper">
            <div class="contacts-card">

                <div class="contacts-header">All Contacts</div>

                <!-- Search -->
                <form method="GET" action="{{ route('contacts.index') }}">
                    <div class="contacts-search-wrapper">
                        <input
                            type="text"
                            name="search"
                            class="contacts-search-input"
                            placeholder="Search contacts..."
                            value="{{ request('search') }}"
                        >
                        <button class="contacts-search-btn">Search</button>
                    </div>
                </form>

                <!-- Add Contact + Upload -->
                <div class="button-row">
                    <button
                        id="add-contact-btn"
                        class="btn-gold"
                        data-create-url="{{ route('contacts.create.panel') }}"
                        type="button"
                    >
                        Add Contact
                    </button>

                    <button
                        class="btn-gold"
                        data-bs-toggle="modal"
                        data-bs-target="#uploadModal"
                        type="button"
                    >
                        Upload File
                    </button>
                </div>

                {{-- ✅ Bulk select row --}}
                <div class="bulk-row">
                    <label>
                        <input type="checkbox" id="contacts-select-all">
                        Select all
                    </label>

                    <div class="right">
                        <div class="count">
                            Selected: <span id="contacts-selected-count">0</span>
                        </div>
                        <button type="button" class="btn-outline-gold" id="contacts-bulk-text-btn" disabled>
                            Bulk Text
                        </button>
                    </div>
                </div>

                <!-- Contact List -->
                <div class="contacts-list-scroll">
                    @forelse ($contacts as $contact)
                        @php
                            $name = $contact->full_name ?? trim(($contact->first_name ?? '') . ' ' . ($contact->last_name ?? ''));
                            $phone = $contact->phone ?? $contact->mobile ?? $contact->cell ?? '';
                        @endphp

                        <div
                            class="contact-list-item js-contact-row"
                            data-contact-url="{{ route('contacts.show', $contact->id) }}"
                            data-contact-id="{{ $contact->id }}"
                        >
                            <input type="checkbox"
                                   class="contacts-row-checkbox"
                                   data-id="{{ $contact->id }}"
                                   data-name="{{ $name }}"
                                   data-phone="{{ $phone }}"
                                   onclick="event.stopPropagation();">

                            <span>{{ $name ?: '(No Name)' }}</span>
                        </div>
                    @empty
                        <p class="contact-list-empty">No contacts found.</p>
                    @endforelse
                </div>

            </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="col-md-8 col-lg-9">
            <div id="contact-details-container">
                <div class="empty-right-panel">
                    <h2>Select a contact</h2>
                    <p>Or click <strong>Add Contact</strong> to create a new one.</p>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- UPLOAD MODAL -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <form
            action="{{ route('contacts.import') }}"
            method="POST"
            enctype="multipart/form-data"
            class="modal-content"
        >
            @csrf

            <div class="modal-header bg-black text-gold">
                <h5 class="modal-title">Upload Contacts File</h5>
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
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn-gold">Upload</button>
            </div>

        </form>
    </div>
</div>

{{-- ✅ Bulk Text Modal --}}
<div class="modal fade" id="contactsBulkTextModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" onsubmit="return false;">
            <div class="modal-header bg-black text-gold">
                <h5 class="modal-title">Bulk Text</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="small text-muted mb-2">
                    Sending to <strong><span id="contacts-bulk-count">0</span></strong> selected contacts.
                    <span class="ms-2">Valid phones: <strong><span id="contacts-bulk-valid-phones">0</span></strong></span>
                </div>

                <div class="mb-2">
                    <label class="form-label small text-muted mb-1">Template</label>
                    <select id="contacts-bulk-template-id" class="form-select">
                        <option value="">— Select a template —</option>
                    </select>
                    <div class="small text-muted mt-1">
                        Selecting a template will fill the message. You can still edit before sending.
                    </div>
                </div>

                <textarea id="contacts-bulk-body" class="form-control" rows="4" placeholder="Type message..."></textarea>
                <div class="small text-muted mt-2">
                    This will queue outbound SMS messages in the database (Phase 2/3).
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-gold" id="contacts-bulk-send-btn">Send</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('contact-details-container');
    const CSRF_TOKEN = @json(csrf_token());

    // ✅ Track current open contact card URL so we can refresh it after upload/delete
    window.__contactsPanelUrl = window.__contactsPanelUrl || null;

    // ------------------------------------------------------------
    // Right panel loader (GLOBAL like Service)
    // ------------------------------------------------------------
    window.loadContactPanel = function (url) {
        if (!container) return;

        window.__contactsPanelUrl = url;

        container.innerHTML = `
            <div style="padding:40px; text-align:center;">
                <div class="spinner-border text-warning" role="status"></div>
                <p class="mt-3 text-muted">Loading...</p>
            </div>
        `;

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.text())
            .then(html => { container.innerHTML = html; })
            .catch(err => {
                console.error(err);
                container.innerHTML = `
                    <div style="padding:40px; text-align:center; color:red;">
                        Failed to load.
                    </div>
                `;
            });
    };

    // ============================================================
    // ✅ CRITICAL FIX (All Contacts)
    // Intercept attachment UPLOAD and DELETE inside right panel
    // and run via fetch(), then reload the open contact card.
    // ============================================================

    function showUploadErrorFromResponse(resp, fallbackMsg) {
        // Try JSON first (Laravel 422 validation usually returns JSON if Accept is application/json)
        return resp.clone().json()
            .then(data => {
                if (data && data.message) return data.message;
                // Laravel validation errors often like { errors: { "files.0": ["..."] } }
                if (data && data.errors) {
                    const firstKey = Object.keys(data.errors)[0];
                    if (firstKey && Array.isArray(data.errors[firstKey]) && data.errors[firstKey][0]) {
                        return data.errors[firstKey][0];
                    }
                }
                return fallbackMsg;
            })
            .catch(() => resp.text().then(() => fallbackMsg).catch(() => fallbackMsg));
    }

    // 1) Upload intercept: input[id^=ab-attach-input-]
    //    This prevents the inline onchange="ABAttachments.submitIfSelected()" from navigating away.
    document.addEventListener('change', async (e) => {
        const el = e.target;
        if (!el) return;

        if (el.matches('input[type="file"][id^="ab-attach-input-"]')) {
            e.preventDefault();
            e.stopPropagation();
            if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();

            const formId = el.id.replace('ab-attach-input-', 'ab-attach-form-');
            const form = document.getElementById(formId);
            if (!form) return;

            if (!el.files || el.files.length === 0) return;

            try {
                // ✅ IMPORTANT: FormData(form) already includes the selected file(s)
                // Do NOT append files again or you can get duplicates / weird behavior.
                const fd = new FormData(form);

                const resp = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'X-Requested-With': 'XMLHttpRequest',
                        // ✅ request JSON so we can read validation errors cleanly (422)
                        'Accept': 'application/json'
                    },
                    body: fd
                });

                if (!resp.ok) {
                    const msg = await showUploadErrorFromResponse(resp, 'Upload error. The file failed to upload.');
                    alert(msg);
                    // reset so user can reselect same file and re-trigger change
                    el.value = '';
                    return;
                }

                // ✅ refresh the currently open contact card
                if (window.__contactsPanelUrl) window.loadContactPanel(window.__contactsPanelUrl);

                // reset input so selecting same file again triggers change
                el.value = '';
            } catch (err) {
                console.error(err);
                alert('Upload error. The file failed to upload.');
                el.value = '';
            }
        }
    }, true);

    // 2) Delete intercept: attachment delete forms (method spoof DELETE + /attachments/)
    document.addEventListener('submit', async (e) => {
        const form = e.target;
        if (!form) return;

        const methodSpoof = form.querySelector('input[name="_method"]');
        const isDelete = methodSpoof && String(methodSpoof.value || '').toUpperCase() === 'DELETE';
        const isAttachmentDelete = isDelete && (form.action || '').includes('/attachments/');

        if (!isAttachmentDelete) return;

        e.preventDefault();
        e.stopPropagation();
        if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();

        if (!confirm('Delete this file?')) return;

        try {
            const fd = new FormData(form);

            const resp = await fetch(form.action, {
                method: 'POST', // method spoof via _method=DELETE
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: fd
            });

            if (!resp.ok) {
                const msg = await showUploadErrorFromResponse(resp, 'Delete failed.');
                alert(msg);
                return;
            }

            if (window.__contactsPanelUrl) window.loadContactPanel(window.__contactsPanelUrl);
        } catch (err) {
            console.error(err);
            alert('Delete failed.');
        }
    }, true);

    // ------------------------------------------------------------
    // Contact row click handler
    // ------------------------------------------------------------
    document.querySelectorAll('.js-contact-row').forEach(row => {
        row.addEventListener('click', (e) => {
            if (e && e.target && e.target.classList && e.target.classList.contains('contacts-row-checkbox')) return;

            document.querySelectorAll('.js-contact-row')
                .forEach(r => r.classList.remove('active-contact-row'));

            row.classList.add('active-contact-row');
            window.loadContactPanel(row.dataset.contactUrl);
        });
    });

    // Add Contact button
    const addBtn = document.getElementById('add-contact-btn');
    if (addBtn) addBtn.addEventListener('click', () => window.loadContactPanel(addBtn.dataset.createUrl));

    // Auto-load selected contact after edit
    const selectedId = @json($selected ?? '');
    if (selectedId) {
        const target = document.querySelector(`.js-contact-row[data-contact-id='${selectedId}']`);
        if (target) target.click();
    }

    // ------------------------------------------------------------
    // ✅ Bulk select + bulk text (unchanged)
    // ------------------------------------------------------------
    const selectedIds = new Set();
    const selectedMeta = new Map(); // id -> {name, phone}

    function normalizePhone(p) {
        return String(p || '').trim();
    }

    function refreshBulkUi() {
        document.getElementById('contacts-selected-count').textContent = String(selectedIds.size);

        const btn = document.getElementById('contacts-bulk-text-btn');
        if (btn) btn.disabled = selectedIds.size === 0;
    }

    function updateValidPhonesCountInModal() {
        const el = document.getElementById('contacts-bulk-valid-phones');
        if (!el) return;

        let valid = 0;
        selectedMeta.forEach(meta => {
            if (normalizePhone(meta.phone)) valid++;
        });
        el.textContent = String(valid);
    }

    document.querySelectorAll('.contacts-row-checkbox').forEach(cb => {
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

    const selectAll = document.getElementById('contacts-select-all');
    if (selectAll) {
        selectAll.addEventListener('change', () => {
            const checked = !!selectAll.checked;

            document.querySelectorAll('.contacts-row-checkbox').forEach(cb => {
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

    // ------------------------------------------------------------
    // ✅ Bulk Templates (SMS)
    // ------------------------------------------------------------
    const TEMPLATE_ROUTES = {
        list: '/settings/messaging/templates/json',
        show: '/settings/messaging/templates'
    };

    let smsTemplateListCache = null;

    function setTemplateOptions(selectEl, items) {
        if (!selectEl) return;
        while (selectEl.options.length > 1) selectEl.remove(1);

        (items || []).forEach(t => {
            const opt = document.createElement('option');
            opt.value = String(t.id);
            opt.textContent = t.name || ('Template #' + t.id);
            selectEl.appendChild(opt);
        });
    }

    function loadSmsTemplatesList() {
        if (smsTemplateListCache) return Promise.resolve(smsTemplateListCache);

        return fetch(`${TEMPLATE_ROUTES.list}?channel=sms`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json().catch(() => ({})).then(d => {
            if (!r.ok || !d.success) throw new Error(d.message || 'Failed to load templates.');
            return d.items || [];
        }))
        .then(items => {
            smsTemplateListCache = items;
            return items;
        })
        .catch(err => {
            console.error(err);
            smsTemplateListCache = [];
            return [];
        });
    }

    function loadTemplateById(id) {
        return fetch(`${TEMPLATE_ROUTES.show}/${encodeURIComponent(id)}/json`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json().catch(() => ({})).then(d => {
            if (!r.ok || !d.success) throw new Error(d.message || 'Failed to load template.');
            return d.item;
        }));
    }

    const bulkBtn = document.getElementById('contacts-bulk-text-btn');
    if (bulkBtn) {
        bulkBtn.addEventListener('click', async () => {
            document.getElementById('contacts-bulk-count').textContent = String(selectedIds.size);
            updateValidPhonesCountInModal();

            document.getElementById('contacts-bulk-body').value = '';

            const sel = document.getElementById('contacts-bulk-template-id');
            if (sel) {
                sel.value = '';
                const items = await loadSmsTemplatesList();
                setTemplateOptions(sel, items);
            }

            new bootstrap.Modal(document.getElementById('contactsBulkTextModal')).show();
        });
    }

    const tplSelect = document.getElementById('contacts-bulk-template-id');
    if (tplSelect) {
        tplSelect.addEventListener('change', () => {
            const tplId = String(tplSelect.value || '');
            if (!tplId) return;

            loadTemplateById(tplId)
                .then(item => {
                    if (!item || item.channel !== 'sms') {
                        alert('That template does not match SMS.');
                        tplSelect.value = '';
                        return;
                    }
                    document.getElementById('contacts-bulk-body').value = String(item.body || '');
                })
                .catch(err => {
                    console.error(err);
                    alert(err.message || 'Failed to load template.');
                });
        });
    }

    const bulkSendBtn = document.getElementById('contacts-bulk-send-btn');
    if (bulkSendBtn) {
        bulkSendBtn.addEventListener('click', () => {
            const body = (document.getElementById('contacts-bulk-body').value || '').trim();
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
                alert(`Queued ${data.queued || 0} texts. Skipped (no phone): ${(data.skipped && data.skipped.no_phone) ? data.skipped.no_phone : 0}`);

                selectedIds.clear();
                selectedMeta.clear();

                document.querySelectorAll('.contacts-row-checkbox').forEach(cb => cb.checked = false);
                const selAll = document.getElementById('contacts-select-all');
                if (selAll) selAll.checked = false;

                refreshBulkUi();

                const inst = bootstrap.Modal.getInstance(document.getElementById('contactsBulkTextModal'));
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

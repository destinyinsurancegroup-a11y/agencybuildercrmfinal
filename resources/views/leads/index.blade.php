@extends('layouts.app')

@section('content')

<style>
    /* EXACT COPY OF CONTACTS CSS (unaltered) */
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
        padding: 10px 16px;
        border-radius: 10px;
        border: none;
        background: #c9a227;
        color: #111827;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 4px 8px rgba(0,0,0,0.20);
        text-transform: uppercase;
    }

    .contacts-search-btn:hover { background: #b5901f; }

    .btn-gold {
        background: #c9a227;
        color: #111827;
        border: none;
        padding: 7px 14px;
        font-weight: 600;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.20);
        text-transform: uppercase;
        font-size: 12px;
        cursor: pointer;
        white-space: nowrap;
    }

    .btn-gold:hover { background: #b5901f; }

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
    }
    .btn-outline-gold:hover { background: rgba(201,162,39,0.12); }
    .btn-outline-gold:disabled { opacity: 0.45; cursor: not-allowed; }

    .button-row {
        margin-bottom: 14px;
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
        align-items: center;
        gap: 10px;
    }

    .contact-list-item:hover { background: #f9fafb; }

    .active-contact-row {
        background: #eae6d1 !important;
        font-weight: 600;
    }

    .empty-right-panel { height: 100%; background: transparent !important; }

    .flash-wrap { margin-bottom: 14px; }

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
        color: #6b7280;
        user-select: none;
        margin: 0;
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

    .lead-row-right {
        margin-left: auto;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
</style>

<div class="dashboard-page">
    <div class="row g-4">

        <!-- LEFT COLUMN -->
        <div class="col-md-4 col-lg-3 contacts-card-wrapper">
            <div class="contacts-card">

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="contacts-header mb-0">
                        @if(!empty($showingArchived) && $showingArchived)
                            Archived Leads
                        @else
                            Leads
                        @endif
                    </div>

                    <div>
                        @if(!empty($showingArchived) && $showingArchived)
                            <a href="{{ route('leads.index') }}" class="btn btn-sm btn-outline-secondary">
                                Back to Active
                            </a>
                        @else
                            <a href="{{ route('leads.archived') }}" class="btn btn-sm btn-outline-secondary">
                                View Archived
                            </a>
                        @endif
                    </div>
                </div>

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

                <div class="contacts-search-wrapper">
                    <input type="text" id="lead-search" class="contacts-search-input" placeholder="Search leads...">
                    <button class="contacts-search-btn" disabled>Search</button>
                </div>

                <div class="button-row">
                    <button id="add-lead-btn" class="btn-gold" data-create-url="{{ route('leads.create') }}" type="button">
                        Add Lead
                    </button>

                    <button class="btn-gold" data-bs-toggle="modal" data-bs-target="#uploadLeadModal" type="button">
                        Upload
                    </button>
                </div>

                <div class="bulk-row">
                    <label>
                        <input type="checkbox" id="leads-select-all">
                        Select all
                    </label>

                    <div class="right">
                        <div class="count">
                            Selected: <span id="leads-selected-count">0</span>
                        </div>

                        <button type="button" class="btn-outline-gold" id="leads-bulk-text-btn" disabled>
                            Bulk Text
                        </button>
                    </div>
                </div>

                <div id="lead-list">
                    @forelse ($leads as $lead)
                        @php
                            $isArchivedView = !empty($showingArchived) && $showingArchived;
                            $status         = (string)($lead->status ?? '');
                            $statusLower    = strtolower($status);
                            $isSold         = ($statusLower === 'sold');

                            $rowUrl = ($isArchivedView && $isSold)
                                ? route('book.show', $lead->id)
                                : route('leads.show', $lead->id);

                            $badgeClass = 'badge bg-secondary';
                            if ($statusLower === 'sold') {
                                $badgeClass = 'badge bg-success';
                            } elseif ($statusLower === 'not interested') {
                                $badgeClass = 'badge bg-danger';
                            }

                            $name = $lead->full_name ?? trim(($lead->first_name ?? '') . ' ' . ($lead->last_name ?? ''));
                        @endphp

                        <div
                            class="contact-list-item js-lead-row {{ (isset($selected) && $selected == $lead->id) ? 'active-contact-row' : '' }}"
                            data-id="{{ $lead->id }}"
                            data-show-url="{{ $rowUrl }}"
                        >
                            <input type="checkbox"
                                   class="leads-row-checkbox"
                                   data-id="{{ $lead->id }}"
                                   onclick="event.stopPropagation();">

                            <span>{{ $name ?: '(No Name)' }}</span>

                            @if($isArchivedView)
                                <span class="lead-row-right">
                                    <span class="{{ $badgeClass }}">{{ $status }}</span>
                                </span>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted">No leads found.</p>
                    @endforelse
                </div>

            </div>
        </div>

        <!-- RIGHT PANEL -->
        <div class="col-md-8 col-lg-9">
            <div id="contact-details-container" style="width:100%; min-height:400px;">
                <div class="empty-right-panel"></div>
            </div>
        </div>

    </div>
</div>

<!-- UPLOAD LEADS MODAL -->
<div class="modal fade" id="uploadLeadModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('leads.import') }}" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf

            <div class="modal-header bg-black text-gold">
                <h5 class="modal-title">Upload Leads File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <label class="form-label">Choose CSV or Excel file</label>
                <input type="file" name="file" class="form-control" accept=".csv, .xlsx, .xls" required>
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

{{-- ✅ BULK TEXT MODAL (NOW WITH TEMPLATE SELECTOR) --}}
<div class="modal fade" id="leadsBulkTextModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" onsubmit="return false;">
            <div class="modal-header bg-black text-gold">
                <h5 class="modal-title">Bulk Text</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="small text-muted mb-2">
                    Sending to <strong><span id="leads-bulk-count">0</span></strong> selected leads.
                </div>

                {{-- ✅ Template dropdown (SMS templates) --}}
                <div class="mb-2">
                    <label class="form-label small text-muted mb-1">Template</label>
                    <select id="leads-bulk-template-id" class="form-select">
                        <option value="">— Select a template —</option>
                    </select>
                    <div class="small text-muted mt-1">
                        Selecting a template will fill the message. You can still edit before sending.
                    </div>
                </div>

                <textarea id="leads-bulk-body" class="form-control" rows="4" placeholder="Type message..."></textarea>
                <div class="small text-muted mt-2">
                    This will queue outbound SMS messages in the database (Phase 2/3).
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-gold" id="leads-bulk-send-btn">Send</button>
            </div>
        </form>
    </div>
</div>

{{-- If your layouts/app.blade.php already includes partials.messaging globally, you do NOT need this include.
   If it is NOT global, keep it. --}}
@include('partials.messaging')

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('contact-details-container');
    const CSRF_TOKEN = @json(csrf_token());

    // right panel loader
    window.loadLeadPanel = function (url) {
        if (!container) return;

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

    // click lead row
    document.querySelectorAll('.js-lead-row').forEach(row => {
        row.addEventListener('click', () => {
            document.querySelectorAll('.js-lead-row')
                .forEach(r => r.classList.remove('active-contact-row'));
            row.classList.add('active-contact-row');
            window.loadLeadPanel(row.dataset.showUrl);
        });
    });

    // add lead
    const addBtn = document.getElementById('add-lead-btn');
    if (addBtn) {
        addBtn.addEventListener('click', function () {
            window.loadLeadPanel(this.dataset.createUrl);
        });
    }

    // search filter
    const searchEl = document.getElementById('lead-search');
    if (searchEl) {
        searchEl.addEventListener('keyup', function () {
            const term = (this.value || '').toLowerCase();
            document.querySelectorAll('#lead-list .js-lead-row')
                .forEach(row => {
                    row.style.display = row.textContent.toLowerCase().includes(term) ? 'flex' : 'none';
                });

            const selAll = document.getElementById('leads-select-all');
            if (selAll) selAll.checked = false;
        });
    }

    @if(session('import_error') || $errors->any())
        const modalEl = document.getElementById('uploadLeadModal');
        if (modalEl && window.bootstrap && window.bootstrap.Modal) new bootstrap.Modal(modalEl).show();
    @endif

    @if(!empty($selected))
        window.loadLeadPanel("{{ route('leads.show', $selected) }}");
    @endif

    // ------------------------------------------------------------
    // bulk selection
    // ------------------------------------------------------------
    const selectedIds = new Set();

    function refreshBulkUi() {
        const count = selectedIds.size;
        const countEl = document.getElementById('leads-selected-count');
        if (countEl) countEl.textContent = String(count);

        const btn = document.getElementById('leads-bulk-text-btn');
        if (btn) btn.disabled = count === 0;
    }

    document.querySelectorAll('.leads-row-checkbox').forEach(cb => {
        cb.addEventListener('change', () => {
            const id = String(cb.dataset.id);
            if (cb.checked) selectedIds.add(id);
            else selectedIds.delete(id);
            refreshBulkUi();
        });
    });

    const selectAll = document.getElementById('leads-select-all');
    if (selectAll) {
        selectAll.addEventListener('change', () => {
            const checked = !!selectAll.checked;

            document.querySelectorAll('#lead-list .js-lead-row').forEach(row => {
                if (row.style.display === 'none') return;
                const cb = row.querySelector('.leads-row-checkbox');
                if (!cb) return;

                cb.checked = checked;
                const id = String(cb.dataset.id);
                if (checked) selectedIds.add(id);
                else selectedIds.delete(id);
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

    const bulkBtn = document.getElementById('leads-bulk-text-btn');
    if (bulkBtn) {
        bulkBtn.addEventListener('click', async () => {
            document.getElementById('leads-bulk-count').textContent = String(selectedIds.size);
            document.getElementById('leads-bulk-body').value = '';

            const sel = document.getElementById('leads-bulk-template-id');
            if (sel) {
                sel.value = '';
                const items = await loadSmsTemplatesList();
                setTemplateOptions(sel, items);
            }

            new bootstrap.Modal(document.getElementById('leadsBulkTextModal')).show();
        });
    }

    const tplSelect = document.getElementById('leads-bulk-template-id');
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
                    document.getElementById('leads-bulk-body').value = String(item.body || '');
                })
                .catch(err => {
                    console.error(err);
                    alert(err.message || 'Failed to load template.');
                });
        });
    }

    const bulkSendBtn = document.getElementById('leads-bulk-send-btn');
    if (bulkSendBtn) {
        bulkSendBtn.addEventListener('click', () => {
            const body = (document.getElementById('leads-bulk-body').value || '').trim();
            if (!body) return alert('Message is empty.');
            if (selectedIds.size < 1) return alert('No leads selected.');

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
                document.querySelectorAll('.leads-row-checkbox').forEach(cb => cb.checked = false);

                const selAll = document.getElementById('leads-select-all');
                if (selAll) selAll.checked = false;

                refreshBulkUi();

                const inst = bootstrap.Modal.getInstance(document.getElementById('leadsBulkTextModal'));
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

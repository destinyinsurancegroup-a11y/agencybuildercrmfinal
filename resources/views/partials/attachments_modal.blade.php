{{-- resources/views/partials/attachments_modal.blade.php --}}

<style>
    .ab-att-row {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        padding:10px 0;
        border-bottom:1px solid #eee;
    }
    .ab-att-left {
        display:flex;
        align-items:center;
        gap:10px;
        min-width:0;
    }
    .ab-att-thumb {
        width:44px;
        height:44px;
        border-radius:10px;
        border:1px solid rgba(0,0,0,0.10);
        overflow:hidden;
        background:#fff;
        display:flex;
        align-items:center;
        justify-content:center;
        flex:0 0 auto;
        cursor:pointer;
    }
    .ab-att-thumb img {
        width:100%;
        height:100%;
        object-fit:cover;
        display:block;
    }
    .ab-att-meta {
        min-width:0;
    }
    .ab-att-name {
        font-weight:700;
        font-size:14px;
        color:#111827;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
        max-width:440px;
    }
    .ab-att-sub {
        font-size:12px;
        color:#6b7280;
    }
    .ab-att-actions {
        display:flex;
        gap:8px;
        flex:0 0 auto;
    }
    .ab-att-empty {
        padding:18px;
        background:#fafafa;
        border:1px dashed #ddd;
        border-radius:12px;
        color:#6b7280;
        font-size:13px;
    }
</style>

<!-- Attachments Modal -->
<div class="modal fade" id="attachmentsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px; overflow:hidden;">
            <div class="modal-header" style="background:#111; color:#D4AF37;">
                <div>
                    <div class="fw-bold" style="font-size:16px;">Attachments</div>
                    <div class="text-white-50" style="font-size:12px;" id="abAttContactLine">—</div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div class="text-muted" style="font-size:13px;">
                        Upload PDFs, images, or any files you want attached to this contact.
                    </div>

                    <div class="d-flex gap-2 align-items-center">
                        <input type="file"
                               id="abAttFileInput"
                               class="form-control form-control-sm"
                               multiple
                               style="max-width:320px;" />

                        <button type="button"
                                class="btn btn-sm"
                                style="background:#c9a227; color:#111827; font-weight:800; border-radius:10px;"
                                onclick="ABAttachments.uploadSelected()">
                            Upload
                        </button>
                    </div>
                </div>

                <div id="abAttStatus" class="text-muted" style="font-size:12px;"></div>

                <div id="abAttList" class="mt-2">
                    <div class="ab-att-empty">Loading…</div>
                </div>

            </div>

            <div class="modal-footer">
                <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let modalInstance = null;

    const els = {
        modal: document.getElementById('attachmentsModal'),
        list: document.getElementById('abAttList'),
        status: document.getElementById('abAttStatus'),
        contactLine: document.getElementById('abAttContactLine'),
        fileInput: document.getElementById('abAttFileInput')
    };

    // Holds current contact context while modal is open
    const state = {
        contactId: null,
        contactName: null
    };

    function setStatus(msg) {
        if (els.status) els.status.textContent = msg || '';
    }

    function escapeHtml(str) {
        return String(str || '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function isImage(mime) {
        return (mime || '').toLowerCase().startsWith('image/');
    }

    function formatBytes(bytes) {
        const n = Number(bytes || 0);
        if (!n) return '';
        const units = ['B','KB','MB','GB'];
        let i = 0, v = n;
        while (v >= 1024 && i < units.length - 1) { v /= 1024; i++; }
        return `${v.toFixed(i === 0 ? 0 : 1)} ${units[i]}`;
    }

    function renderEmpty() {
        if (!els.list) return;
        els.list.innerHTML = `<div class="ab-att-empty">No attachments yet.</div>`;
    }

    function renderList(items) {
        if (!els.list) return;

        if (!items || !items.length) {
            renderEmpty();
            return;
        }

        els.list.innerHTML = items.map(a => {
            const name = escapeHtml(a.original_name || a.name || 'Attachment');
            const mime = escapeHtml(a.mime || '');
            const size = formatBytes(a.size_bytes || a.size || 0);
            const created = escapeHtml(a.created_at_local || a.created_at || '');

            const thumbUrl = a.thumb_url || a.url || null;
            const viewUrl  = a.url || a.view_url || null;

            const thumb = isImage(a.mime) && thumbUrl
                ? `<img src="${escapeHtml(thumbUrl)}" alt="">`
                : `<span style="font-size:10px; color:#6b7280;">FILE</span>`;

            return `
                <div class="ab-att-row" id="ab-att-${a.id}">
                    <div class="ab-att-left">
                        <div class="ab-att-thumb" onclick="ABAttachments.view(${a.id})" title="Open">
                            ${thumb}
                        </div>
                        <div class="ab-att-meta">
                            <div class="ab-att-name" title="${name}">${name}</div>
                            <div class="ab-att-sub">
                                ${mime ? mime : ''} ${size ? ' • ' + size : ''} ${created ? ' • ' + created : ''}
                            </div>
                        </div>
                    </div>

                    <div class="ab-att-actions">
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                onclick="ABAttachments.view(${a.id})">
                            View
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger"
                                onclick="ABAttachments.remove(${a.id})">
                            Delete
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }

    async function apiGetList(contactId) {
        // We will create this route next:
        // GET /contacts/{contact}/attachments  (returns JSON)
        const res = await fetch(`/contacts/${contactId}/attachments`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            cache: 'no-store'
        });

        if (!res.ok) throw new Error('Failed to load attachments');
        return await res.json();
    }

    async function apiUpload(contactId, files) {
        // We will create this route next:
        // POST /contacts/{contact}/attachments  (multipart)
        const fd = new FormData();
        for (const f of files) fd.append('files[]', f);

        const res = await fetch(`/contacts/${contactId}/attachments`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: fd
        });

        if (!res.ok) {
            let msg = 'Upload failed';
            try {
                const j = await res.json();
                if (j && j.message) msg = j.message;
            } catch (e) {}
            throw new Error(msg);
        }

        return await res.json();
    }

    async function apiDelete(contactId, attachmentId) {
        // We will create this route next:
        // DELETE /contacts/{contact}/attachments/{attachment}
        const res = await fetch(`/contacts/${contactId}/attachments/${attachmentId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        if (!res.ok) {
            let msg = 'Delete failed';
            try {
                const j = await res.json();
                if (j && j.message) msg = j.message;
            } catch (e) {}
            throw new Error(msg);
        }

        return await res.json();
    }

    async function apiGetOne(contactId, attachmentId) {
        // Optional helper for view url (if you want JSON per item later):
        // GET /contacts/{contact}/attachments/{attachment}
        const res = await fetch(`/contacts/${contactId}/attachments/${attachmentId}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            cache: 'no-store'
        });

        // If we haven't built this yet, we'll just fallback to opening /download endpoint later.
        if (!res.ok) return null;

        try { return await res.json(); }
        catch (e) { return null; }
    }

    async function refresh() {
        if (!state.contactId) return;

        setStatus('Loading…');
        try {
            const data = await apiGetList(state.contactId);
            const items = data.attachments || data.data || [];
            renderList(items);
            setStatus('');
        } catch (e) {
            renderEmpty();
            setStatus(e.message || 'Could not load attachments.');
        }
    }

    window.ABAttachments = window.ABAttachments || {};

    window.ABAttachments.open = function (contactId, contactName) {
        state.contactId = contactId;
        state.contactName = contactName || '';

        if (els.contactLine) {
            els.contactLine.textContent = state.contactName ? `${state.contactName} (ID: ${state.contactId})` : `Contact ID: ${state.contactId}`;
        }

        if (!modalInstance) {
            modalInstance = new bootstrap.Modal(els.modal);
        }

        // Clear UI first
        if (els.list) els.list.innerHTML = `<div class="ab-att-empty">Loading…</div>`;
        setStatus('');

        modalInstance.show();
        refresh();
    };

    window.ABAttachments.uploadSelected = async function () {
        if (!state.contactId) return;

        const files = els.fileInput?.files ? Array.from(els.fileInput.files) : [];
        if (!files.length) {
            alert('Choose one or more files first.');
            return;
        }

        setStatus('Uploading…');
        try {
            await apiUpload(state.contactId, files);
            if (els.fileInput) els.fileInput.value = '';
            await refresh();
            setStatus('Upload complete.');
            setTimeout(() => setStatus(''), 1200);
        } catch (e) {
            setStatus('');
            alert(e.message || 'Upload failed.');
        }
    };

    window.ABAttachments.remove = async function (attachmentId) {
        if (!state.contactId) return;
        if (!confirm('Delete this attachment?')) return;

        try {
            setStatus('Deleting…');
            await apiDelete(state.contactId, attachmentId);
            await refresh();
            setStatus('');
        } catch (e) {
            setStatus('');
            alert(e.message || 'Delete failed.');
        }
    };

    window.ABAttachments.view = async function (attachmentId) {
        if (!state.contactId) return;

        // If later we implement JSON show, we’ll use it.
        const data = await apiGetOne(state.contactId, attachmentId);
        const url = data?.attachment?.url || data?.url || null;

        // Fallback: open a conventional download/view endpoint
        // We will create this route next:
        // GET /contacts/{contact}/attachments/{attachment}/download
        const finalUrl = url || `/contacts/${state.contactId}/attachments/${attachmentId}/download`;

        window.open(finalUrl, '_blank');
    };

})();
</script>

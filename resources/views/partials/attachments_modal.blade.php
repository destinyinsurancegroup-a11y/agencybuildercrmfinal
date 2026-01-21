{{-- resources/views/partials/attachments_modal.blade.php --}}

<div class="modal fade" id="abAttachmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0" id="abAttTitle">Attachment</h5>
                    <div class="small text-muted" id="abAttMeta"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body" style="background:#f4f4f4;">
                <div id="abAttPreviewWrap"
                     style="
                        width:100%;
                        background:white;
                        border:1px solid #e5e7eb;
                        border-radius:10px;
                        overflow:hidden;
                        min-height:420px;
                     ">
                    <div class="p-4 text-muted" id="abAttPreviewFallback">
                        Preview will appear here.
                    </div>
                </div>
            </div>

            <div class="modal-footer d-flex justify-content-between">
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-secondary btn-sm" id="abAttViewBtn" href="#" target="_blank" rel="noopener">
                        View
                    </a>
                    <a class="btn btn-outline-secondary btn-sm" id="abAttDownloadBtn" href="#">
                        Download
                    </a>
                </div>

                <form method="POST" id="abAttDeleteForm" action="#" style="margin:0;">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="return_to" value="{{ request()->fullUrl() }}">
                    <button type="submit" class="btn btn-outline-danger btn-sm"
                            onclick="return confirm('Delete this attachment?');">
                        Delete
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    'use strict';

    function getCsrfToken() {
        var el = document.querySelector('meta[name="csrf-token"]');
        return el ? el.getAttribute('content') : '';
    }

    function openModal() {
        if (!window.bootstrap || !window.bootstrap.Modal) return alert('Bootstrap modal JS missing.');
        var el = document.getElementById('abAttachmentModal');
        if (!el) return alert('Attachment modal missing.');
        new bootstrap.Modal(el).show();
    }

    function setPreview(mime, viewUrl) {
        var wrap = document.getElementById('abAttPreviewWrap');
        if (!wrap) return;

        // Clear
        wrap.innerHTML = '';

        mime = String(mime || '').toLowerCase();

        // Image
        if (mime.startsWith('image/')) {
            var img = document.createElement('img');
            img.src = viewUrl;
            img.alt = 'Attachment preview';
            img.style.width = '100%';
            img.style.height = 'auto';
            img.style.display = 'block';
            wrap.appendChild(img);
            return;
        }

        // PDF
        if (mime === 'application/pdf') {
            var iframe = document.createElement('iframe');
            iframe.src = viewUrl;
            iframe.style.width = '100%';
            iframe.style.height = '520px';
            iframe.style.border = '0';
            wrap.appendChild(iframe);
            return;
        }

        // Unknown: show message
        var div = document.createElement('div');
        div.className = 'p-4 text-muted';
        div.textContent = 'No preview available for this file type. Use View or Download.';
        wrap.appendChild(div);
    }

    // ✅ Event delegation so this works for AJAX-injected panels (Book/Service)
    document.addEventListener('click', function (e) {
        var btn = e.target && e.target.closest ? e.target.closest('[data-attachment-id]') : null;
        if (!btn) return;

        e.preventDefault();

        var id = btn.getAttribute('data-attachment-id');
        var name = btn.getAttribute('data-attachment-name') || 'Attachment';
        var mime = btn.getAttribute('data-attachment-mime') || '';
        var date = btn.getAttribute('data-attachment-date') || '';

        var viewUrl = '/attachments/' + encodeURIComponent(id);
        var downloadUrl = '/attachments/' + encodeURIComponent(id) + '/download';
        var deleteUrl = '/attachments/' + encodeURIComponent(id);

        var titleEl = document.getElementById('abAttTitle');
        var metaEl  = document.getElementById('abAttMeta');
        if (titleEl) titleEl.textContent = name;
        if (metaEl) metaEl.textContent = (mime ? mime : 'file') + (date ? (' • ' + date) : '');

        var viewBtn = document.getElementById('abAttViewBtn');
        var dlBtn   = document.getElementById('abAttDownloadBtn');
        if (viewBtn) viewBtn.setAttribute('href', viewUrl);
        if (dlBtn) dlBtn.setAttribute('href', downloadUrl);

        var delForm = document.getElementById('abAttDeleteForm');
        if (delForm) delForm.setAttribute('action', deleteUrl);

        // Ensure return_to always matches current URL at click time
        var rt = delForm ? delForm.querySelector('input[name="return_to"]') : null;
        if (rt) rt.value = window.location.href;

        setPreview(mime, viewUrl);
        openModal();
    });

})();
</script>
@endpush

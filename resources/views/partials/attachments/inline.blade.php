{{-- resources/views/partials/attachments/inline.blade.php --}}

@php
    /** @var \App\Models\Contact $contact */
    $contactId = (int) $contact->id;
    $isLead = strtolower((string)($contact->contact_type ?? '')) === 'lead';

    // Eager-load is ideal, but if not loaded, this still works.
    $attachments = method_exists($contact, 'attachments') ? ($contact->attachments ?? collect()) : collect();
@endphp

@if(!$isLead)
    <div class="mt-2 d-flex align-items-center flex-wrap gap-2"
         style="text-align:left;">

        {{-- Upload (paperclip + hidden input) --}}
        <form method="POST"
              action="{{ route('contacts.attachments.store', $contactId) }}"
              enctype="multipart/form-data"
              class="d-inline-block"
              style="margin:0;"
        >
            @csrf
            <input type="hidden" name="return_to" value="{{ request()->fullUrl() }}">

            <label class="d-inline-flex align-items-center gap-1"
                   style="
                        cursor:pointer;
                        font-size:12px;
                        color:#6b7280;
                        user-select:none;
                   "
                   title="Attach file(s)"
            >
                <span style="font-size:14px; color:#c9a227;">📎</span>
                <span style="font-weight:600;">Attach</span>

                <input type="file"
                       name="files[]"
                       multiple
                       style="display:none;"
                       onchange="this.form.submit();"
                       accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.csv,.txt"
                >
            </label>
        </form>

        {{-- Chips --}}
        @foreach($attachments as $a)
            @php
                $mime = strtolower((string) $a->mime_type);
                $isImg = str_starts_with($mime, 'image/');
                $isPdf = $mime === 'application/pdf';
                $ext = strtolower(pathinfo((string)$a->original_name, PATHINFO_EXTENSION));
                $label = $a->original_name ?: ('Attachment #' . $a->id);
                $short = mb_strlen($label) > 26 ? mb_substr($label, 0, 25) . '…' : $label;
            @endphp

            <button type="button"
                    class="btn btn-sm"
                    data-attachment-id="{{ $a->id }}"
                    data-attachment-name="{{ e($a->original_name) }}"
                    data-attachment-mime="{{ e($a->mime_type) }}"
                    data-attachment-date="{{ optional($a->created_at)->format('m/d/Y') }}"
                    style="
                        border:1px solid #e5e7eb;
                        background:#f9fafb;
                        color:#111827;
                        border-radius:999px;
                        padding:4px 10px;
                        font-size:11px;
                        line-height:1;
                        box-shadow:none;
                    "
                    title="Click to preview"
            >
                <span style="color:#6b7280; margin-right:6px;">
                    {{ $isImg ? '🖼️' : ($isPdf ? '📄' : '📎') }}
                </span>
                <span style="font-weight:600;">{{ $short }}</span>
            </button>
        @endforeach

        @if($attachments->isEmpty())
            <span class="text-muted" style="font-size:12px;">No files</span>
        @endif
    </div>
@endif

{{-- resources/views/partials/attachments_strip.blade.php --}}
@php
    // This strip is intentionally "safe":
    // - It works even if you haven't created the attachments relationship yet.
    // - If $contact->attachments exists and is loaded, it shows thumbnails.
    // - Otherwise it still shows the Attachments button (modal will load via AJAX).

    $contactId   = (int) ($contact->id ?? 0);
    $contactName = $contact->full_name
        ?? trim(($contact->first_name ?? '').' '.($contact->last_name ?? ''));

    $attachments = collect();

    // If an "attachments" relationship/property exists and is iterable, show a few previews.
    if (isset($contact) && isset($contact->attachments) && is_iterable($contact->attachments)) {
        $attachments = collect($contact->attachments);
    }

    $preview = $attachments->take(4);
@endphp

<div class="mt-2" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">

    <button type="button"
            class="btn-outline-gold"
            style="font-size:12px;"
            onclick="ABAttachments.open({{ $contactId }}, @json($contactName))"
            {{ $contactId ? '' : 'disabled' }}>
        Attachments
    </button>

    {{-- Tiny preview thumbnails (only if attachments already loaded) --}}
    @if($preview->isNotEmpty())
        <div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;">
            @foreach($preview as $a)
                @php
                    $isImage = isset($a->mime) && str_starts_with((string)$a->mime, 'image/');
                    $thumb   = $a->thumb_url ?? $a->url ?? null;
                    $title   = $a->original_name ?? $a->name ?? 'Attachment';
                @endphp

                <div title="{{ $title }}"
                     onclick="ABAttachments.open({{ $contactId }}, @json($contactName))"
                     style="
                        width:34px; height:34px;
                        border-radius:8px;
                        border:1px solid rgba(0,0,0,0.10);
                        overflow:hidden;
                        cursor:pointer;
                        display:flex; align-items:center; justify-content:center;
                        background:#fff;
                     ">
                    @if($isImage && $thumb)
                        <img src="{{ $thumb }}" alt=""
                             style="width:100%; height:100%; object-fit:cover;">
                    @else
                        <span style="font-size:10px; color:#6b7280; padding:4px;">FILE</span>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <span class="text-muted" style="font-size:12px;">
            No files yet
        </span>
    @endif

</div>

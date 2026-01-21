{{-- resources/views/partials/attachments_strip.blade.php --}}

@php
    /** @var \App\Models\Contact $contact */
    $attachments = $contact->attachments()->latest()->get();
    $returnTo = request()->fullUrl();

    // quick icon per type (simple + safe)
    $iconFor = function ($a) {
        if ($a->isPdf()) return '📄';
        if ($a->isImage()) return '🖼️';
        $m = strtolower((string) $a->mime_type);
        if (str_contains($m, 'spreadsheet') || str_contains($m, 'excel')) return '📊';
        if (str_contains($m, 'word')) return '📝';
        return '📎';
    };
@endphp

<style>
    .ab-attach-row{
        display:flex;
        align-items:center;
        gap:10px;
        margin-top:10px;
        flex-wrap:wrap;
    }
    .ab-paperclip{
        width:30px;
        height:30px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        border:1px solid #e5e7eb;
        border-radius:8px;
        background:#fff;
        cursor:pointer;
        box-shadow:0 2px 6px rgba(0,0,0,0.06);
    }
    .ab-paperclip:hover{ background:#f9fafb; }

    .ab-attach-label{
        font-weight:700;
        color:#111827;
        font-size:13px;
        margin-right:2px;
    }

    .ab-file-pill{
        display:inline-flex;
        align-items:center;
        gap:8px;
        border:1px solid #e5e7eb;
        background:#fff;
        border-radius:10px;
        padding:6px 10px;
        font-size:12px;
        color:#111827;
        box-shadow:0 2px 6px rgba(0,0,0,0.06);
        max-width:260px;
    }
    .ab-file-pill a{
        color:#111827;
        text-decoration:none;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
        max-width:170px;
        display:inline-block;
    }
    .ab-file-pill a:hover{ text-decoration:underline; }

    .ab-file-actions{
        display:inline-flex;
        align-items:center;
        gap:6px;
        margin-left:2px;
    }
    .ab-file-btn{
        border:0;
        background:transparent;
        padding:0 4px;
        cursor:pointer;
        color:#6b7280;
        font-size:14px;
        line-height:1;
    }
    .ab-file-btn:hover{ color:#111827; }
</style>

<div class="ab-attach-row">

    {{-- Paperclip opens file picker --}}
    <button type="button"
            class="ab-paperclip"
            title="Attach files"
            onclick="document.getElementById('abAttachInput-{{ (int) $contact->id }}')?.click();">
        📎
    </button>

    <div class="ab-attach-label">Attach files:</div>

    {{-- Hidden upload form --}}
    <form method="POST"
          action="{{ route('contacts.attachments.store', $contact) }}"
          enctype="multipart/form-data"
          style="display:none;">
        @csrf
        <input id="abAttachInput-{{ (int) $contact->id }}"
               type="file"
               name="files[]"
               multiple
               onchange="this.form.submit();">
        <input type="hidden" name="return_to" value="{{ $returnTo }}">
    </form>

    {{-- Existing files --}}
    @if($attachments->isEmpty())
        <div class="text-muted small">No files yet</div>
    @else
        @foreach($attachments->take(3) as $a)
            <div class="ab-file-pill" title="{{ $a->original_name }}">
                <span>{{ $iconFor($a) }}</span>

                {{-- Click opens inline view --}}
                <a href="{{ route('attachments.show', $a) }}" target="_blank" rel="noopener">
                    {{ $a->safeDisplayName(26) }}
                </a>

                <div class="ab-file-actions">
                    {{-- Download --}}
                    <a class="ab-file-btn"
                       href="{{ route('attachments.download', $a) }}"
                       title="Download">
                        ⬇️
                    </a>

                    {{-- ✅ DELETE (REAL DELETE REQUEST WITH CSRF) --}}
                    <form method="POST"
                          action="{{ route('attachments.destroy', $a) }}"
                          style="display:inline;"
                          onsubmit="return confirm('Delete this file?');">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="return_to" value="{{ $returnTo }}">
                        <button type="submit" class="ab-file-btn" title="Delete">🗑️</button>
                    </form>
                </div>
            </div>
        @endforeach

        @if($attachments->count() > 3)
            <div class="text-muted small">+{{ $attachments->count() - 3 }}</div>
        @endif
    @endif

</div>

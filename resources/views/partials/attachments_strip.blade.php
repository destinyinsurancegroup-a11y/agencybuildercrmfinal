{{-- resources/views/partials/attachments_strip.blade.php --}}
@php
    $c = $contact ?? $client ?? null;
    if (!$c) { return; }

    $attachments = $c->attachments()->latest()->get();
    $isLead = strtolower((string) ($c->contact_type ?? '')) === 'lead';
    $returnTo = url()->current();
@endphp

<div class="mt-2 d-flex align-items-center flex-wrap gap-2" style="min-height:32px;">

    {{-- Paperclip upload trigger --}}
    @if(!$isLead)
        <form id="attach-form-{{ (int) $c->id }}"
              method="POST"
              action="{{ route('contacts.attachments.store', $c->id) }}"
              enctype="multipart/form-data"
              style="display:inline;">
            @csrf
            <input type="hidden" name="return_to" value="{{ $returnTo }}">

            <input id="attach-input-{{ (int) $c->id }}"
                   type="file"
                   name="files[]"
                   multiple
                   style="display:none;"
                   onchange="(function(){
                        const f = document.getElementById('attach-form-{{ (int) $c->id }}');
                        const inp = document.getElementById('attach-input-{{ (int) $c->id }}');
                        if(!f || !inp || !inp.files || inp.files.length === 0) return;
                        f.submit();
                   })();"
            >

            <button type="button"
                    title="Attach files"
                    onclick="document.getElementById('attach-input-{{ (int) $c->id }}').click();"
                    style="
                        display:inline-flex;align-items:center;justify-content:center;
                        width:32px;height:32px;border-radius:8px;
                        border:1px solid #e5e7eb;background:#fff;
                        box-shadow:0 2px 6px rgba(0,0,0,0.06);padding:0;
                    ">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#6b7280" viewBox="0 0 16 16">
                    <path d="M4.5 8.5l5.147-5.146a2.5 2.5 0 113.536 3.536l-6.2 6.2a3.5 3.5 0 01-4.95-4.95l6.2-6.2.708.707-6.2 6.2a2.5 2.5 0 103.536 3.536l6.2-6.2a1.5 1.5 0 10-2.122-2.122L5.207 9.207l-.707-.707z"/>
                </svg>
            </button>
        </form>

        <div class="ms-1 fw-semibold" style="font-size:13px; color:#111827;">
            Attach files:
        </div>
    @else
        <div class="ms-1 text-muted small">Attachments disabled for leads.</div>
    @endif

    {{-- Existing attachments as “pills” --}}
    @if($attachments->isEmpty())
        <div class="text-muted small ms-2">No files yet</div>
    @else
        @foreach($attachments as $a)
            @php
                $name = method_exists($a, 'safeDisplayName') ? $a->safeDisplayName(22) : ($a->original_name ?? 'file');
                $full = $a->original_name ?? $a->stored_name ?? 'file';
                $viewUrl = route('attachments.show', $a->id);
            @endphp

            <span class="d-inline-flex align-items-center"
                  style="
                    border:1px solid #e5e7eb;background:#fff;border-radius:10px;
                    padding:5px 8px;box-shadow:0 2px 6px rgba(0,0,0,0.06);
                    gap:8px;
                  ">

                <a href="{{ $viewUrl }}"
                   target="_blank"
                   rel="noopener"
                   title="{{ $full }}"
                   style="font-size:13px; text-decoration:none;">
                    {{ $name }}
                </a>

                {{-- ✅ DELETE “×” --}}
                <form method="POST"
                      action="{{ route('attachments.destroy', $a->id) }}"
                      style="display:inline; margin:0;"
                      onsubmit="return confirm('Delete this file?');">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="return_to" value="{{ $returnTo }}">

                    <button type="submit"
                            title="Delete"
                            style="
                                border:0;background:transparent;
                                color:#9ca3af;font-weight:700;
                                line-height:1;padding:0 2px;cursor:pointer;
                            "
                            onmouseover="this.style.color='#ef4444'"
                            onmouseout="this.style.color='#9ca3af'">
                        ×
                    </button>
                </form>
            </span>
        @endforeach
    @endif
</div>

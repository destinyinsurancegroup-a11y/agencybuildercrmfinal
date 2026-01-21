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
                {{-- paperclip icon --}}
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

    {{-- Existing attachments --}}
    @if($attachments->isEmpty())
        <div class="text-muted small ms-2">No files yet</div>
    @else
        @foreach($attachments as $a)
            @php
                $name = method_exists($a, 'safeDisplayName') ? $a->safeDisplayName(22) : ($a->original_name ?? 'file');
                $full = $a->original_name ?? $a->stored_name ?? 'file';
                $viewUrl = route('attachments.show', $a->id);
            @endphp

            <div
                style="
                    display:inline-flex;align-items:center;gap:10px;
                    border:1px solid #e5e7eb;background:#fff;border-radius:10px;
                    padding:6px 10px;box-shadow:0 2px 6px rgba(0,0,0,0.06);
                "
            >
                <a href="{{ $viewUrl }}"
                   target="_blank"
                   rel="noopener"
                   title="{{ $full }}"
                   style="font-size:13px; text-decoration:none; color:#1f2937;">
                    {{ $name }}
                </a>

                {{-- ✅ VERY OBVIOUS DELETE BUTTON --}}
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
                                border:1px solid #ef4444;
                                background:#ef4444;
                                color:white;
                                width:22px;height:22px;
                                border-radius:6px;
                                font-weight:800;
                                line-height:18px;
                                padding:0;
                                cursor:pointer;
                                display:inline-flex;
                                align-items:center;
                                justify-content:center;
                            ">
                        ×
                    </button>
                </form>
            </div>
        @endforeach
    @endif
</div>

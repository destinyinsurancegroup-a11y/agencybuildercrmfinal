{{-- resources/views/contacts/partials/details.blade.php --}}

@php
    $contactName = $contact->full_name ?? trim(($contact->first_name ?? '') . ' ' . ($contact->last_name ?? ''));
    $attachments = $contact->attachments()->latest()->get();
    $chipLimit = 3;
@endphp

<div class="card shadow-sm border-0"
     style="border-radius:18px; height: calc(100vh - 120px); overflow-y:auto; background:#ffffff;">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center"
         style="
            padding:22px 28px;
            border-radius:18px 18px 0 0;
            border-bottom:1px solid #e5e7eb;
         ">

        <div>
            <div style="font-size:34px; font-weight:800; color:#111827; line-height:1;">
                {{ $contactName }}
            </div>

            {{-- ✅ Messaging buttons --}}
            <div class="mt-2 d-flex gap-2 flex-wrap">
                <button
                    type="button"
                    class="btn-outline-gold"
                    style="font-size:12px;"
                    onclick='ABMessaging.openSms({{ (int) $contact->id }}, @json($contactName), @json($contact->phone))'
                    {{ empty($contact->phone) ? 'disabled' : '' }}
                    title="{{ empty($contact->phone) ? 'No phone on file' : 'Send a text' }}"
                >
                    Text
                </button>

                <button
                    type="button"
                    class="btn-outline-gold"
                    style="font-size:12px;"
                    onclick='ABMessaging.openEmail({{ (int) $contact->id }}, @json($contactName), @json($contact->email))'
                    {{ empty($contact->email) ? 'disabled' : '' }}
                    title="{{ empty($contact->email) ? 'No email on file' : 'Send an email' }}"
                >
                    Email
                </button>

                <button
                    type="button"
                    class="btn-outline-gold"
                    style="font-size:12px;"
                    onclick="alert('Start Sequence is coming soon. Next phase will enable this.')"
                    title="Sequences coming soon"
                >
                    Start Sequence
                </button>
            </div>

            {{-- ✅ Attach files row (paperclip + chips) --}}
            @if(strtolower((string) $contact->contact_type) !== 'lead')
                <div class="ab-attach-row">
                    <button type="button"
                            class="ab-attach-clip"
                            title="Attach files"
                            onclick="ABAttachments.open({{ (int) $contact->id }})">
                        {{-- paperclip icon (inline svg) --}}
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <path d="M8 12.5l7.1-7.1a4 4 0 015.7 5.7l-8.5 8.5a6 6 0 01-8.5-8.5l8.3-8.3"
                                  stroke="#c9a227" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>

                    <div class="ab-attach-label">Attach files:</div>

                    <div class="ab-attach-chips">
                        @if($attachments->count() === 0)
                            <div class="ab-file-empty">No files yet</div>
                        @else
                            @foreach($attachments->take($chipLimit) as $a)
                                @php
                                    $name = $a->safeDisplayName(18);
                                    $ext  = strtolower(pathinfo($a->original_name ?? $a->stored_name, PATHINFO_EXTENSION));
                                    $isPdf = $a->isPdf();
                                    $isImg = $a->isImage();
                                @endphp

                                <a class="ab-file-chip"
                                   href="{{ route('attachments.show', $a->id) }}"
                                   target="_blank"
                                   title="{{ $a->original_name }}">
                                    <span class="ab-file-icon">
                                        @if($isPdf)
                                            📄
                                        @elseif($isImg)
                                            🖼️
                                        @elseif(in_array($ext, ['xls','xlsx','csv']))
                                            📊
                                        @elseif(in_array($ext, ['doc','docx']))
                                            📝
                                        @else
                                            📎
                                        @endif
                                    </span>
                                    <span>{{ $name }}</span>
                                </a>
                            @endforeach

                            @if($attachments->count() > $chipLimit)
                                <span class="ab-file-more">+{{ $attachments->count() - $chipLimit }}</span>
                            @endif
                        @endif
                    </div>

                    {{-- Hidden upload form --}}
                    <form id="ab-attach-form-{{ (int) $contact->id }}"
                          action="{{ route('contacts.attachments.store', $contact->id) }}"
                          method="POST"
                          enctype="multipart/form-data"
                          style="display:none;">
                        @csrf
                        <input type="hidden" name="return_to" value="{{ request()->fullUrl() }}">
                        <input id="ab-attach-input-{{ (int) $contact->id }}"
                               type="file"
                               name="files[]"
                               multiple
                               onchange="ABAttachments.submitIfSelected({{ (int) $contact->id }})">
                    </form>
                </div>
            @endif
        </div>

        <a href="{{ route('contacts.edit', $contact->id) }}"
           class="btn btn-sm"
           style="
                background:#c9a227;
                color:#111827;
                font-weight:700;
                padding:8px 18px;
                border-radius:10px;
                text-transform:uppercase;
                font-size:12px;
            ">
            Edit
        </a>
    </div>

    <div class="card-body" style="padding:26px 28px;">

        {{-- TOP DETAILS --}}
        <div class="row mb-4">

            <div class="col-md-6 mb-3">
                <label class="text-muted small fw-semibold">Email</label>
                <div class="fw-bold">{{ $contact->email ?: '—' }}</div>
            </div>

            <div class="col-md-6 mb-3">
                <label class="text-muted small fw-semibold">Phone</label>
                <div class="fw-bold">{{ $contact->phone ?: '—' }}</div>
            </div>

            <div class="col-md-6 mb-3">
                <label class="text-muted small fw-semibold">Contact Type</label>
                <div class="fw-bold">{{ $contact->contact_type ?: '—' }}</div>
            </div>

            <div class="col-md-6 mb-3">
                <label class="text-muted small fw-semibold">Status</label>
                <span class="badge bg-secondary"
                      style="font-size:12px; padding:6px 10px; border-radius:8px;">
                    {{ $contact->status ?: '—' }}
                </span>
            </div>

            <div class="col-md-6 mb-3">
                <label class="text-muted small fw-semibold">Date of Birth</label>
                <div class="fw-bold">
                    {{ $contact->date_of_birth ? $contact->date_of_birth->format('m/d/Y') : '—' }}
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <label class="text-muted small fw-semibold">Anniversary</label>
                <div class="fw-bold">
                    {{ $contact->anniversary ? $contact->anniversary->format('m/d/Y') : '—' }}
                </div>
            </div>
        </div>

        {{-- ADDRESS --}}
        <div class="mb-4">
            <label class="text-muted small fw-semibold">Address</label>
            <div class="fw-bold" style="line-height:1.3;">
                @if($contact->address_line1)
                    {{ $contact->address_line1 }}<br>
                    {{ $contact->city }} {{ $contact->state }} {{ $contact->postal_code }}
                @else
                    —
                @endif
            </div>
        </div>

        <hr class="my-4">

        <h5 class="fw-bold mb-2">Additional Details</h5>
        <p class="text-muted small mb-4">
            More custom contact details or policy information can be stored here.
        </p>

    </div>
</div>

{{-- NOTES --}}
<div class="mt-4">

    <h5 class="fw-bold mb-3">Notes</h5>

    <form method="POST" action="{{ route('contacts.notes.store', $contact->id) }}">
        @csrf

        <textarea
            name="body"
            class="form-control"
            rows="3"
            style="border-radius:10px; border:1px solid #d1d5db; font-size:14px;"
            placeholder="Write a new note..."
            required
        >{{ old('body') }}</textarea>

        @error('body')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror

        <button type="submit"
                class="btn mt-3"
                style="
                    background:#c9a227;
                    color:#111827;
                    padding:8px 22px;
                    border-radius:10px;
                    font-size:13px;
                    font-weight:700;
                ">
            Add Note
        </button>
    </form>

    <div class="mt-4">
        @php
            $notes = $contact->notes()->latest()->get();
        @endphp

        @forelse($notes as $note)
            <div class="border rounded p-2 mb-2">
                <div class="small text-muted mb-1">
                    {{ optional($note->created_at)->format('m/d/Y g:i A') }}
                    @if($note->author ?? false)
                        — {{ $note->author->name }}
                    @endif
                </div>
                <div>{{ $note->note ?? $note->body }}</div>
            </div>
        @empty
            <p class="text-muted small mb-0">No notes yet for this contact.</p>
        @endforelse
    </div>

</div>

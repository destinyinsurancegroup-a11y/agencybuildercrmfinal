{{-- resources/views/book/partials/details.blade.php --}}

<style>
    .card.p-4 { padding: 1.25rem !important; }
    h4.text-gold { margin-top: 0.5rem !important; margin-bottom: 0.5rem !important; }
    .row.mb-4 { margin-bottom: 0.75rem !important; }
    .card hr { margin: 0.75rem 0 !important; }
    .card p { margin-bottom: 0.25rem !important; }
    .p-4 { padding: 1.25rem !important; }
    #book-notes-wrapper { margin-top: 24px; }

    /* ===== Attachments row (Book) ===== */
    .ab-attach-row{
        margin-top: 10px;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    .ab-attach-clip{
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        box-shadow: 0 2px 6px rgba(0,0,0,0.06);
        cursor: pointer;
    }
    .ab-attach-label{
        font-size: 13px;
        font-weight: 700;
        color: #111827;
        margin-left: 2px;
    }
    .ab-attach-chips{
        display: inline-flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        min-height: 32px;
    }
    .ab-file-empty{ font-size: 12px; color:#6b7280; }
    .ab-file-more{ font-size: 13px; color:#6b7280; padding-left: 4px; }

    .ab-chip-wrap{
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid #e5e7eb;
        background: #fff;
        border-radius: 10px;
        padding: 6px 10px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.06);
    }
    .ab-file-chip{
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        color: #1f2937;
        font-size: 13px;
        line-height: 1;
        max-width: 220px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .ab-file-icon{ opacity: 0.9; }

    .ab-file-del{
        width: 20px;
        height: 20px;
        border-radius: 6px;
        border: 1px solid #ef4444;
        background: #ef4444;
        color: #fff;
        font-weight: 900;
        line-height: 18px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    .ab-file-del:hover{ filter: brightness(0.95); }

    /* Optional subtle separator for each extra policy */
    .ab-policy-block{
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 12px 14px;
        margin-top: 10px;
        background: #fff;
    }
    .ab-policy-title{
        font-weight: 800;
        margin-bottom: 8px;
        color: #111827;
    }
</style>

@php
    $inActiveService = $client->contact_type === 'service' && is_null($client->service_archived_at);

    $clientName  = $client->full_name ?? trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? ''));
    $clientPhone = $client->phone ?? null;
    $clientEmail = $client->email ?? null;

    $attachments = $client->attachments()->latest()->get();
    $chipLimit   = 3;

    $returnTo = request()->fullUrl();

    // ✅ Load additional policies (excluding legacy fields on contacts table)
    try {
        $policies = $client->policies()->orderBy('id')->get();
    } catch (\Throwable $e) {
        $policies = collect();
    }
@endphp

<div class="p-4">
    <div class="card shadow-sm border-0 p-4">

        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="fw-bold" style="font-size: 32px;">
                    {{ $clientName ?: '(No Name)' }}
                </h1>

                <!-- ✅ Messaging buttons -->
                <div class="mt-2 d-flex gap-2 flex-wrap">
                    <button
                        type="button"
                        class="btn-outline-gold"
                        style="font-size:12px;"
                        onclick='ABMessaging.openSms({{ (int) $client->id }}, @json($clientName), @json($clientPhone))'
                        {{ empty($clientPhone) ? 'disabled' : '' }}
                        title="{{ empty($clientPhone) ? 'No phone on file' : 'Send a text' }}"
                    >
                        Text
                    </button>

                    <button
                        type="button"
                        class="btn-outline-gold"
                        style="font-size:12px;"
                        onclick='ABMessaging.openEmail({{ (int) $client->id }}, @json($clientName), @json($clientEmail))'
                        {{ empty($clientEmail) ? 'disabled' : '' }}
                        title="{{ empty($clientEmail) ? 'No email on file' : 'Send an email' }}"
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

                {{-- ✅ Attach files row --}}
                <div class="ab-attach-row">
                    <button type="button"
                            class="ab-attach-clip"
                            title="Attach files"
                            onclick="ABAttachments.open({{ (int) $client->id }})">
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
                                    $name = method_exists($a, 'safeDisplayName')
                                        ? $a->safeDisplayName(18)
                                        : (\Illuminate\Support\Str::limit(($a->original_name ?? $a->stored_name ?? 'file'), 18));

                                    $ext  = strtolower(pathinfo($a->original_name ?? $a->stored_name ?? '', PATHINFO_EXTENSION));
                                    $isPdf = method_exists($a, 'isPdf') ? $a->isPdf() : ($a->mime_type === 'application/pdf');
                                    $isImg = method_exists($a, 'isImage') ? $a->isImage() : (str_starts_with(strtolower((string)$a->mime_type), 'image/'));
                                @endphp

                                <div class="ab-chip-wrap">
                                    <a class="ab-file-chip"
                                       href="{{ route('attachments.show', $a->id) }}"
                                       target="_blank"
                                       rel="noopener"
                                       title="{{ $a->original_name ?? $a->stored_name }}">
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

                                    <form method="POST"
                                          action="{{ route('attachments.destroy', $a->id) }}"
                                          style="margin:0;"
                                          onsubmit="return confirm('Delete this file?');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="return_to" value="{{ $returnTo }}">
                                        <button type="submit" class="ab-file-del" title="Delete">×</button>
                                    </form>
                                </div>
                            @endforeach

                            @if($attachments->count() > $chipLimit)
                                <span class="ab-file-more">+{{ $attachments->count() - $chipLimit }}</span>
                            @endif
                        @endif
                    </div>

                    <form id="ab-attach-form-{{ (int) $client->id }}"
                          action="{{ route('contacts.attachments.store', $client->id) }}"
                          method="POST"
                          enctype="multipart/form-data"
                          style="display:none;">
                        @csrf
                        <input type="hidden" name="return_to" value="{{ $returnTo }}">
                        <input id="ab-attach-input-{{ (int) $client->id }}"
                               type="file"
                               name="files[]"
                               multiple
                               onchange="ABAttachments.submitIfSelected({{ (int) $client->id }})">
                    </form>
                </div>

                @if($inActiveService)
                    <a href="{{ route('service.index', ['selected' => $client->id]) }}"
                       class="badge bg-danger mt-1 text-decoration-none"
                       style="cursor:pointer;">
                        Client Needs Service
                    </a>
                @endif
            </div>

            <div class="d-flex gap-2">
                <button
                    class="btn-gold"
                    data-edit-url="{{ route('book.edit.panel', $client->id) }}"
                    onclick="loadBookPanel(this.dataset.editUrl)"
                    type="button"
                >
                    Edit
                </button>
            </div>
        </div>

        <hr>

        <h4 class="text-gold fw-bold mb-3">Basic Information</h4>

        <div class="row mb-4">
            <div class="col-md-6">
                <p><strong>Email:</strong> {{ $clientEmail ?: '—' }}</p>
                <p><strong>Phone:</strong> {{ $clientPhone ?: '—' }}</p>
                <p><strong>Date of Birth:</strong> {{ $client->date_of_birth?->format('m/d/Y') ?: '—' }}</p>
                <p><strong>Age:</strong> {{ $client->age ?? '—' }}</p>
            </div>

            <div class="col-md-6">
                <p><strong>Anniversary:</strong> {{ $client->anniversary?->format('m/d/Y') ?: '—' }}</p>
                <p><strong>Address:</strong><br>
                    @if($client->address_line1)
                        {{ $client->address_line1 }}<br>
                        @if($client->address_line2) {{ $client->address_line2 }}<br> @endif
                        {{ $client->city }}, {{ $client->state }} {{ $client->postal_code }}
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </p>
            </div>
        </div>

        <hr>

        {{-- ============================= --}}
        {{-- Primary / Legacy Policy block --}}
        {{-- ============================= --}}
        <h4 class="text-gold fw-bold mb-3">Policy Information</h4>

        <div class="row mb-4">
            <div class="col-md-6">
                <p><strong>Carrier:</strong> {{ $client->carrier ?: '—' }}</p>
                <p><strong>Policy Type:</strong> {{ $client->policy_type ?: '—' }}</p>
                <p><strong>Face Amount:</strong>
                    {{ $client->face_amount ? '$'.number_format($client->face_amount, 2) : '—' }}
                </p>
                <p><strong>Monthly Premium:</strong>
                    {{ $client->premium_amount ? '$'.number_format($client->premium_amount, 2) : '—' }}
                </p>
            </div>

            <div class="col-md-6">
                <p><strong>Initial Draft Date:</strong> {{ $client->policy_issue_date?->format('m/d/Y') ?: '—' }}</p>
                <p><strong>Monthly Due (Text):</strong> {{ $client->premium_due_text ?: '—' }}</p>
            </div>
        </div>

        {{-- ===================================== --}}
        {{-- Additional Policies (same display UI) --}}
        {{-- ===================================== --}}
        @php
            $hasAdditional = $policies->isNotEmpty();
        @endphp

        @if($hasAdditional)
            <h4 class="text-gold fw-bold mb-3">Additional Policies</h4>

            @foreach($policies as $i => $p)
                <div class="ab-policy-block">
                    <div class="ab-policy-title">
                        Policy #{{ $i + 1 }}
                    </div>

                    <div class="row mb-0">
                        <div class="col-md-6">
                            <p><strong>Carrier:</strong> {{ $p->carrier ?: '—' }}</p>
                            <p><strong>Policy Type:</strong> {{ $p->policy_type ?: '—' }}</p>
                            <p><strong>Face Amount:</strong>
                                {{ $p->face_amount ? '$'.number_format($p->face_amount, 2) : '—' }}
                            </p>
                            <p><strong>Monthly Premium:</strong>
                                {{ $p->premium_amount ? '$'.number_format($p->premium_amount, 2) : '—' }}
                            </p>
                        </div>

                        <div class="col-md-6">
                            <p><strong>Initial Draft Date:</strong> {{ $p->policy_issue_date?->format('m/d/Y') ?: '—' }}</p>
                            <p><strong>Monthly Due (Text):</strong> {{ $p->premium_due_text ?: '—' }}</p>
                        </div>
                    </div>
                </div>
            @endforeach

            <hr>
        @endif

        <h4 class="text-gold fw-bold mb-3">Beneficiaries &amp; Emergency Contacts</h4>

        @php
            $beneficiaries = $client->beneficiaries ?? collect();
            $emergencies   = $client->emergencyContacts ?? collect();
            $hasAny        = $beneficiaries->isNotEmpty() || $emergencies->isNotEmpty();
        @endphp

        @if($hasAny)
            <ul class="list-unstyled mb-0">
                @foreach ($beneficiaries as $b)
                    <li class="d-flex align-items-start mb-1">
                        <span class="me-2" style="color:#b91c1c; font-size:10px;">●</span>
                        <span>
                            <strong>Beneficiary –</strong>
                            {{ $b->name }}
                            @if($b->relationship), {{ $b->relationship }}@endif
                            @if($b->phone) {{ ' ' . $b->phone }}@endif
                        </span>
                    </li>
                @endforeach

                @foreach ($emergencies as $ec)
                    <li class="d-flex align-items-start mb-1">
                        <span class="me-2" style="color:#b91c1c; font-size:10px;">●</span>
                        <span>
                            <strong>Emergency Contact –</strong>
                            {{ $ec->name }}
                            @if($ec->relationship), {{ $ec->relationship }}@endif
                            @if($ec->phone) {{ ' ' . $ec->phone }}@endif
                        </span>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-muted mb-0">No beneficiaries or emergency contacts added.</p>
        @endif

    </div>

    <div id="book-notes-wrapper">
        <h4 class="text-gold fw-bold mb-3">Notes</h4>

        <div class="mb-3">
            <textarea id="new_note_body"
                      class="form-control"
                      rows="2"
                      placeholder="Write a new note..."></textarea>

            <button class="btn-gold mt-2" type="button" onclick="saveNote({{ (int) $client->id }})">
                Add Note
            </button>
        </div>

        <div id="notes-list" class="mt-3">
            @php
                $notes = $client->allNotes ?? $client->notes ?? collect();
                $notes = $notes->sortByDesc('created_at');
            @endphp

            @forelse ($notes as $note)
                <div class="border rounded p-2 mb-2" id="note-{{ $note->id }}">
                    <div class="small text-muted mb-1">
                        {{ optional($note->created_at)->format('m/d/Y g:i A') }}
                    </div>
                    <div>
                        {{ $note->note ?? $note->body }}
                    </div>
                </div>
            @empty
                <p class="text-muted small mb-0">No notes yet.</p>
            @endforelse
        </div>
    </div>

</div>

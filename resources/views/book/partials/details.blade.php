<style>
    /* ===== COMPACT SPACING FOR BOOK PAGE ===== */

    .card.p-4 {
        padding: 1.25rem !important;
    }

    h4.text-gold {
        margin-top: 0.5rem !important;
        margin-bottom: 0.5rem !important;
    }

    .row.mb-4 {
        margin-bottom: 0.75rem !important;
    }

    .card hr {
        margin: 0.75rem 0 !important;
    }

    .card p {
        margin-bottom: 0.25rem !important;
    }

    .p-4 {
        padding: 1.25rem !important;
    }

    /* Stand-alone notes block under the card (like Service) */
    #book-notes-wrapper {
        margin-top: 24px;
    }
</style>

@php
    // Consider this client "in active service" if:
    // - they are a service contact AND
    // - their service has not been archived yet
    $inActiveService = $client->contact_type === 'service' && is_null($client->service_archived_at);

    // Build display name (consistent with other views)
    $clientName = $client->full_name
        ?? trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? ''));
@endphp

<div class="p-4">
    <div class="card shadow-sm border-0 p-4">

        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="fw-bold" style="font-size: 32px;">
                    {{ $clientName }}
                </h1>

                <!-- ✅ Messaging buttons -->
                <div class="mt-2 d-flex gap-2 flex-wrap">
                    <button
                        type="button"
                        class="btn-outline-gold"
                        style="font-size:12px;"
                        {{-- ✅ FIX: use single quotes around onclick so @json() double-quotes don't break the attribute --}}
                        onclick='ABMessaging.openSms({{ (int) $client->id }}, @json($clientName), @json($client->phone))'
                        {{ empty($client->phone) ? 'disabled' : '' }}
                        title="{{ empty($client->phone) ? 'No phone on file' : 'Send a text' }}"
                    >
                        Text
                    </button>

                    <button
                        type="button"
                        class="btn-outline-gold"
                        style="font-size:12px;"
                        {{-- ✅ FIX: use single quotes around onclick so @json() double-quotes don't break the attribute --}}
                        onclick='ABMessaging.openEmail({{ (int) $client->id }}, @json($clientName), @json($client->email))'
                        {{ empty($client->email) ? 'disabled' : '' }}
                        title="{{ empty($client->email) ? 'No email on file' : 'Send an email' }}"
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

                @if($inActiveService)
                    {{-- Clickable badge: goes to Service tab with this client selected --}}
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
                >
                    Edit
                </button>
            </div>
        </div>

        <hr>

        <!-- ================================ -->
        <!-- BASIC INFORMATION SECTION        -->
        <!-- ================================ -->
        <h4 class="text-gold fw-bold mb-3">Basic Information</h4>

        <div class="row mb-4">
            <div class="col-md-6">
                <p><strong>Email:</strong> {{ $client->email ?: '—' }}</p>
                <p><strong>Phone:</strong> {{ $client->phone ?: '—' }}</p>
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

        <!-- ================================ -->
        <!-- POLICY INFORMATION SECTION       -->
        <!-- ================================ -->
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

        <hr>

        <!-- ================================ -->
        <!-- BENEFICIARIES & EMERGENCY CONTACTS -->
        <!-- ================================ -->
        <h4 class="text-gold fw-bold mb-3">Beneficiaries &amp; Emergency Contacts</h4>

        @php
            $beneficiaries = $client->beneficiaries ?? collect();
            $emergencies   = $client->emergencyContacts ?? collect();
            $hasAny        = $beneficiaries->isNotEmpty() || $emergencies->isNotEmpty();
        @endphp

        @if($hasAny)
            <ul class="list-unstyled mb-0">

                {{-- Beneficiaries --}}
                @foreach ($beneficiaries as $b)
                    <li class="d-flex align-items-start mb-1">
                        <span class="me-2" style="color:#b91c1c; font-size:10px;">●</span>
                        <span>
                            <strong>Beneficiary –</strong>
                            {{ $b->name }}
                            @if($b->relationship)
                                , {{ $b->relationship }}
                            @endif
                            @if($b->phone)
                                {{ ' ' . $b->phone }}
                            @endif
                        </span>
                    </li>
                @endforeach

                {{-- Emergency Contacts --}}
                @foreach ($emergencies as $ec)
                    <li class="d-flex align-items-start mb-1">
                        <span class="me-2" style="color:#b91c1c; font-size:10px;">●</span>
                        <span>
                            <strong>Emergency Contact –</strong>
                            {{ $ec->name }}
                            @if($ec->relationship)
                                , {{ $ec->relationship }}
                            @endif
                            @if($ec->phone)
                                {{ ' ' . $ec->phone }}
                            @endif
                        </span>
                    </li>
                @endforeach

            </ul>
        @else
            <p class="text-muted mb-0">No beneficiaries or emergency contacts added.</p>
        @endif

    </div> {{-- end main card --}}

    {{-- =========================================
         STAND-ALONE NOTES SECTION (LIKE SERVICE)
       ========================================= --}}
    <div id="book-notes-wrapper">
        <h4 class="text-gold fw-bold mb-3">Notes</h4>

        {{-- NEW NOTE FORM (uses saveNote in book/index.blade.php) --}}
        <div class="mb-3">
            <textarea id="new_note_body"
                      class="form-control"
                      rows="2"
                      placeholder="Write a new note..."></textarea>

            <button class="btn-gold mt-2" onclick="saveNote({{ (int) $client->id }})">
                Add Note
            </button>
        </div>

        {{-- EXISTING NOTES --}}
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

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
                {{ $contact->full_name }}
            </div>
            <div style="font-size:13px; color:#6b7280; margin-top:4px;">
                Contact ID: {{ $contact->id }}
            </div>
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

            {{-- DATE OF BIRTH --}}
            <div class="col-md-6 mb-3">
                <label class="text-muted small fw-semibold">Date of Birth</label>
                <div class="fw-bold">
                    {{ $contact->date_of_birth ? $contact->date_of_birth->format('m/d/Y') : '—' }}
                </div>
            </div>

            {{-- ANNIVERSARY --}}
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

        {{-- ADDITIONAL DETAILS (simple text like Book of Business) --}}
        <h5 class="fw-bold mb-2">Additional Details</h5>
        <p class="text-muted small mb-4">
            More custom contact details or policy information can be stored here.
        </p>

        <hr class="my-4">

        {{-- NOTES SECTION (Book-of-Business style) --}}
        <h5 class="fw-bold mb-3">Notes</h5>

        {{-- NEW NOTE FORM --}}
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

        {{-- EXISTING NOTES --}}
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
                    <div>{{ $note->body }}</div>
                </div>
            @empty
                <p class="text-muted small mb-0">No notes yet for this contact.</p>
            @endforelse
        </div>

    </div>
</div>

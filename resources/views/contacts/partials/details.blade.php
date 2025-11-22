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

        {{-- TABS --}}
        <ul class="nav nav-tabs" id="contactDetailTabs" style="font-weight:600; border-bottom:1px solid #ddd;">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-details">
                    Details
                </button>
            </li>

            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-notes">
                    Notes
                </button>
            </li>

            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-docs">
                    Documents
                </button>
            </li>
        </ul>

        {{-- TAB CONTENT --}}
        <div class="tab-content pt-4">

            {{-- DETAILS TAB --}}
            <div class="tab-pane fade show active" id="tab-details">
                <h5 class="fw-bold mb-2">Additional Details</h5>
                <p class="text-muted small">
                    More custom contact details or policy information can be stored here.
                </p>
            </div>

            {{-- 🔥 NOTES TAB (Option B) --}}
            <div class="tab-pane fade" id="tab-notes">

                <h5 class="fw-bold mb-3">Contact Notes</h5>

                <form method="POST" action="{{ route('contacts.saveNotes', $contact->id) }}">
                    @csrf

                    <textarea name="notes"
                              class="form-control"
                              rows="6"
                              style="
                                border-radius:12px;
                                border:1px solid #d1d5db;
                                font-size:14px;
                              "
                              placeholder="Enter notes for this contact...">{{ old('notes', $contact->notes) }}</textarea>

                    <div class="mt-3">
                        <button class="btn"
                                style="
                                    background:#c9a227;
                                    color:#111827;
                                    padding:8px 22px;
                                    border-radius:10px;
                                    font-size:13px;
                                    font-weight:700;
                                ">
                            Save Notes
                        </button>
                    </div>

                </form>

            </div>

            {{-- DOCUMENTS TAB --}}
            <div class="tab-pane fade" id="tab-docs">
                <h5 class="fw-bold mb-2">Documents</h5>
                <p class="text-muted small">Document upload & preview coming soon.</p>
            </div>

        </div>

    </div>
</div>

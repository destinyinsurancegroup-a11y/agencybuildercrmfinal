<div class="card shadow-sm border-0"
     style="border-radius:18px; height: calc(100vh - 120px); overflow-y:auto;">

    {{-- CONTACT NAME HEADER --}}
    <div class="card-header bg-white d-flex justify-content-between align-items-center"
         style="border-radius:18px 18px 0 0; padding:25px 30px; border-bottom:1px solid #eee;">

        {{-- BIG CONTACT NAME --}}
        <span style="font-size:48px; font-weight:800; color:#111827;">
            {{ $contact->full_name }}
        </span>

        {{-- EDIT BUTTON --}}
        <a href="{{ route('contacts.edit', $contact->id) }}" 
           class="btn btn-sm"
           style="background:#c9a227; color:#111827; font-weight:600; border-radius:8px;">
            Edit
        </a>
    </div>

    <div class="card-body" style="padding:30px;">

        {{-- PRIMARY DETAILS --}}
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="text-muted">Email</label>
                <div class="fw-semibold">{{ $contact->email ?: '—' }}</div>
            </div>

            <div class="col-md-6">
                <label class="text-muted">Phone</label>
                <div class="fw-semibold">{{ $contact->phone ?: '—' }}</div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="text-muted">Contact Type</label>
                <div class="fw-semibold">{{ $contact->contact_type ?: '—' }}</div>
            </div>

            <div class="col-md-6">
                <label class="text-muted">Status</label>
                <span class="badge bg-secondary" style="font-size:12px; padding:6px 10px;">
                    {{ $contact->status ?: '—' }}
                </span>
            </div>
        </div>

        {{-- ADDRESS --}}
        <div class="mb-3">
            <label class="text-muted">Address</label>
            <div class="fw-semibold">
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
        <ul class="nav nav-tabs" id="contactDetailTabs" style="font-weight:600;">
            <li class="nav-item">
                <button class="nav-link active"
                        data-tab="details"
                        data-contact-id="{{ $contact->id }}">
                    Details
                </button>
            </li>

            <li class="nav-item">
                <button class="nav-link"
                        data-tab="notes"
                        data-contact-id="{{ $contact->id }}">
                    Notes
                </button>
            </li>

            <li class="nav-item">
                <button class="nav-link"
                        data-tab="documents"
                        data-contact-id="{{ $contact->id }}">
                    Documents
                </button>
            </li>
        </ul>

        {{-- TAB CONTENT AREA --}}
        <div id="contact-tab-content" class="pt-3">

            {{-- DEFAULT: DETAILS TAB CONTENT --}}
            <div id="details-content">
                <h5 class="fw-bold mb-2">Additional Details</h5>
                <p class="text-muted">More custom contact details or policy info can go here.</p>
            </div>

        </div>

    </div>
</div>

@push('scripts')
<script>

/*
|--------------------------------------------------------------------------
| TAB CLICK HANDLING (AJAX LOADING)
|--------------------------------------------------------------------------
*/
$(document).on('click', '[data-tab]', function(e) {
    e.preventDefault();

    const tab = $(this).data('tab');
    const contactId = $(this).data('contact-id');

    // Remove active class from all tab buttons
    $('[data-tab]').removeClass('active');
    $(this).addClass('active');

    // Handle tab switching
    if (tab === 'details') {
        $('#contact-tab-content').html(`
            <div id="details-content">
                <h5 class="fw-bold mb-2">Additional Details</h5>
                <p class="text-muted">More custom contact details or policy info can go here.</p>
            </div>
        `);
    }

    if (tab === 'notes') {
        $('#contact-tab-content').html('<p class="text-muted">Loading notes...</p>');
        $('#contact-tab-content').load(`/contacts/${contactId}/notes`);
    }

    if (tab === 'documents') {
        $('#contact-tab-content').html(`
            <h5 class="fw-bold mb-2">Documents</h5>
            <p class="text-muted">Document upload & preview coming soon.</p>
        `);
    }
});

</script>
@endpush

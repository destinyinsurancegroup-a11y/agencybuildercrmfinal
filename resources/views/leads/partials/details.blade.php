<div class="p-4">

    <div class="card shadow-sm border-0 p-4">

        <!-- BIG NAME HEADER -->
        <h1 class="fw-bold mb-3" style="font-size: 32px;">
            {{ $contact->first_name }} {{ $contact->last_name }}
        </h1>

        <!-- Hidden helpers for JS -->
        <input type="hidden" id="leadContactId" value="{{ $contact->id }}">
        <input type="hidden" id="leadContactName" value="{{ $contact->full_name }}">

        <!-- DISPOSITION BUTTONS UNDER NAME -->
        <div class="d-flex gap-2 mb-4">
            {{-- SOLD: convert lead -> client/contact --}}
            <button type="button"
                    class="btn btn-success btn-sm px-3"
                    onclick="handleLeadSold()">
                Sold
            </button>

            {{-- FOLLOW UP: open calendar modal --}}
            <button type="button"
                    class="btn btn-warning btn-sm px-3"
                    onclick="openFollowUpModal()">
                Follow Up
            </button>

            {{-- NOT INTERESTED: placeholder (can wire later) --}}
            <button type="button"
                    class="btn btn-danger btn-sm px-3"
                    onclick="handleLeadNotInterested()">
                Not Interested
            </button>
        </div>

        <!-- TOP RIGHT EDIT BUTTON -->
        <div class="text-end mb-3">
            <a href="{{ route('contacts.edit', $contact->id) }}" class="btn btn-gold">
                Edit
            </a>
        </div>

        <hr>

        <!-- DETAILS SECTION -->
        <div class="row mb-4">

            <div class="col-md-6">
                <p><strong>Email:</strong> {{ $contact->email ?: '—' }}</p>
                <p><strong>Phone:</strong> {{ $contact->phone ?: '—' }}</p>

                <p><strong>Age:</strong> {{ $contact->age ?: '—' }}</p>
            </div>

            <div class="col-md-6">
                <p><strong>Contact Type:</strong> Lead</p>

                <p><strong>Status:</strong>
                    <span class="badge bg-secondary">
                        {{ $contact->status ?? 'New' }}
                    </span>
                </p>

                <p><strong>Lead Received Date:</strong>
                    {{ $contact->lead_received_date ? \Carbon\Carbon::parse($contact->lead_received_date)->format('m/d/Y') : '—' }}
                </p>

                <p><strong>Lead Assigned Date:</strong>
                    {{ $contact->lead_assigned_date ? \Carbon\Carbon::parse($contact->lead_assigned_date)->format('m/d/Y') : '—' }}
                </p>
            </div>

        </div>

        <!-- ADDRESS -->
        <div class="mb-4">
            <p><strong>Address</strong></p>

            @if($contact->address_line1)
                <p>
                    {{ $contact->address_line1 }}<br>
                    @if($contact->address_line2) {{ $contact->address_line2 }}<br> @endif
                    {{ $contact->city }} {{ $contact->state }} {{ $contact->postal_code }}
                </p>
            @else
                <p class="text-muted">No address available.</p>
            @endif
        </div>

        <hr>

        <!-- TABS -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#detailsTab">Details</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#notesTab">Notes</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#docsTab">Documents</a>
            </li>
        </ul>

        <div class="tab-content">

            <!-- DETAILS TAB -->
            <div class="tab-pane fade show active" id="detailsTab">
                <h5 class="fw-bold">Additional Details</h5>
                <p class="text-muted">More custom contact details or policy info can go here.</p>
            </div>

            <!-- NOTES TAB -->
            <div class="tab-pane fade" id="notesTab">
                <h5 class="fw-bold">Notes</h5>
                <p>{{ $contact->notes ?: 'No notes added.' }}</p>
            </div>

            <!-- DOCUMENTS TAB -->
            <div class="tab-pane fade" id="docsTab">
                <h5 class="fw-bold">Documents</h5>
                <p class="text-muted">Document uploads coming soon…</p>
            </div>

        </div>

    </div>

</div>

{{-- ===========================
     FOLLOW-UP EVENT MODAL
=========================== --}}
<div class="modal fade" id="followUpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Schedule Follow Up</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="followUpContactId">

                <div class="mb-3">
                    <label class="form-label fw-bold">Title</label>
                    <input type="text" id="followUpTitle" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Date &amp; Time</label>
                    <input type="datetime-local" id="followUpStart" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Location (optional)</label>
                    <input type="text" id="followUpLocation" class="form-control">
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" onclick="saveFollowUpEvent()">
                    Save Follow Up
                </button>
            </div>

        </div>
    </div>
</div>

{{-- ===========================
     LEAD ACTIONS JS
=========================== --}}
<script>
    // make CSRF token available for fetch calls
    const LEADS_CSRF_TOKEN = '{{ csrf_token() }}';

    let followUpModalInstance = null;

    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('followUpModal');
        if (modalEl && window.bootstrap) {
            followUpModalInstance = new bootstrap.Modal(modalEl);
        }
    });

    function getLeadContext() {
        const idEl = document.getElementById('leadContactId');
        const nameEl = document.getElementById('leadContactName');
        return {
            id: idEl ? idEl.value : null,
            name: nameEl ? nameEl.value : ''
        };
    }

    // SOLD: convert lead -> client/contact + move to Book of Business
    function handleLeadSold() {
        const ctx = getLeadContext();
        if (!ctx.id) {
            alert('Unable to determine lead ID.');
            return;
        }

        if (!confirm('Mark this lead as SOLD and move to your Book of Business?')) {
            return;
        }

        fetch(`/leads/${ctx.id}/sold`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': LEADS_CSRF_TOKEN,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Request failed');
            }
            return response.json().catch(() => ({}));
        })
        .then(() => {
            // After conversion, just send user back to Leads index
            window.location.href = "{{ route('leads.index') }}";
        })
        .catch(() => {
            alert('There was a problem converting this lead. Please try again.');
        });
    }

    // FOLLOW UP: open modal prefilled
    function openFollowUpModal() {
        const ctx = getLeadContext();
        if (!followUpModalInstance) {
            alert('Follow-up modal is not available.');
            return;
        }

        document.getElementById('followUpContactId').value = ctx.id || '';
        document.getElementById('followUpTitle').value = ctx.name
            ? `Follow Up – ${ctx.name}`
            : 'Follow Up';

        // default to "now" in local time (YYYY-MM-DDTHH:MM)
        const now = new Date();
        const iso = new Date(now.getTime() - now.getTimezoneOffset() * 60000)
            .toISOString()
            .slice(0,16);
        document.getElementById('followUpStart').value = iso;

        document.getElementById('followUpLocation').value = '';

        followUpModalInstance.show();
    }

    // FOLLOW UP: save via calendar events API
    function saveFollowUpEvent() {
        const contactId = document.getElementById('followUpContactId').value;
        const title = document.getElementById('followUpTitle').value.trim();
        const start = document.getElementById('followUpStart').value;
        const location = document.getElementById('followUpLocation').value.trim();

        if (!title || !start) {
            alert('Title and Date/Time are required.');
            return;
        }

        fetch('/calendar/events', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': LEADS_CSRF_TOKEN
            },
            body: JSON.stringify({
                title: title,
                start: start,
                location: location,
                // If later you add contact_id column to events table:
                // contact_id: contactId || null
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to save follow up.');
            }
            return response.json().catch(() => ({}));
        })
        .then(() => {
            if (followUpModalInstance) {
                followUpModalInstance.hide();
            }
            alert('Follow-up event scheduled successfully.');
        })
        .catch(() => {
            alert('There was a problem saving the follow-up. Please try again.');
        });
    }

    // NOT INTERESTED: stub for later wiring
    function handleLeadNotInterested() {
        const ctx = getLeadContext();
        if (!ctx.id) {
            alert('Unable to determine lead ID.');
            return;
        }

        alert('Not Interested action coming soon. (Lead ID: ' + ctx.id + ')');
        // Later: POST to /leads/{id}/not-interested to update status and hide from list
    }
</script>

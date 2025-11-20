<div class="card h-100 shadow-sm border-0">

    @php
        // Normalize variable naming so both "contact" or "lead" work
        $contact = $contact ?? $lead ?? null;
    @endphp

    @if ($contact)

        {{-- FULL CONTACT HEADER --}}
        <div class="card-header bg-white border-0 pt-4 px-4">
            <h2 class="fw-bold m-0">{{ $contact->first_name }} {{ $contact->last_name }}</h2>
        </div>

        <div class="card-body">

            {{-- BASIC INFO --}}
            <div class="row mb-3">
                <div class="col-md-6">
                    <p class="mb-1 text-muted">Email</p>
                    <p>{{ $contact->email ?: '—' }}</p>
                </div>

                <div class="col-md-6">
                    <p class="mb-1 text-muted">Phone</p>
                    <p>{{ $contact->phone ?: '—' }}</p>
                </div>
            </div>

            {{-- NEW LEAD FIELDS --}}
            <hr>

            <div class="row mb-3">

                <div class="col-md-4">
                    <p class="mb-1 text-muted">Age</p>
                    <p>{{ $contact->age ?: '—' }}</p>
                </div>

                <div class="col-md-4">
                    <p class="mb-1 text-muted">Lead Received Date</p>
                    <p>{{ $contact->lead_received_date ? $contact->lead_received_date : '—' }}</p>
                </div>

                <div class="col-md-4">
                    <p class="mb-1 text-muted">Lead Assigned Date</p>
                    <p>{{ $contact->lead_assigned_date ? $contact->lead_assigned_date : '—' }}</p>
                </div>

            </div>

            {{-- ADDRESS --}}
            <hr>

            <p class="mb-1 text-muted">Address</p>
            <p>
                {{ $contact->address_line1 ? $contact->address_line1.'<br>' : '' }}
                {{ $contact->address_line2 ? $contact->address_line2.'<br>' : '' }}
                {{ $contact->city }} {{ $contact->state }} {{ $contact->postal_code }}
            </p>

            {{-- NOTES --}}
            <hr>

            <h5 class="fw-bold mb-2">Notes</h5>
            <p>{{ $contact->notes ?: 'No notes added.' }}</p>

        </div>

    @else
        <div class="p-4">
            <p class="text-muted">Lead not found.</p>
        </div>
    @endif

</div>

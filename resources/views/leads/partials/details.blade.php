<div class="card h-100 shadow-sm border-0">

    @php
        // Normalize variable naming so both "contact" or "lead" work
        $contact = $contact ?? $lead ?? null;
    @endphp

    @if ($contact)

        {{-- HEADER WITH LEAD NAME --}}
        <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
            <h2 class="fw-bold m-0">{{ $contact->first_name }} {{ $contact->last_name }}</h2>
        </div>

        {{-- DISPOSITION BUTTONS UNDER NAME --}}
        <div class="px-4 pb-3 d-flex gap-2">

            <button type="button"
                class="btn btn-success btn-sm fw-bold"
                onclick="alert('Sold action coming soon…');">
                Sold
            </button>

            <button type="button"
                class="btn btn-warning btn-sm fw-bold"
                onclick="alert('Follow Up action coming soon…');">
                Follow Up
            </button>

            <button type="button"
                class="btn btn-danger btn-sm fw-bold"
                onclick="alert('Not Interested action coming soon…');">
                Not Interested
            </button>

        </div>

        {{-- FULL CONTACT DETAILS CARD BELOW BUTTONS --}}
        @include('contacts.partials.details', ['contact' => $contact])

    @else
        <div class="p-4">
            <p class="text-muted">Lead not found.</p>
        </div>
    @endif

</div>

<div class="card h-100 shadow-sm border-0">

    @php
        // Normalize variable naming so both "contact" or "lead" work
        $contact = $contact ?? $lead ?? null;
    @endphp

    @if ($contact)

        {{-- REUSE THE FULL CONTACT DETAILS PARTIAL --}}
        @include('contacts.partials.details', ['contact' => $contact])

    @else
        <div class="p-4">
            <p class="text-muted">Lead not found.</p>
        </div>
    @endif

</div>

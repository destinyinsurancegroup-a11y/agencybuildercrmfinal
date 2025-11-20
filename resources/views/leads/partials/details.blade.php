<div class="card h-100 shadow-sm border-0">

    <!-- HEADER -->
    <div class="card-header bg-black text-gold fw-bold">
        {{ $contact->first_name }} {{ $contact->last_name }}
    </div>

    <!-- BODY -->
    <div class="card-body">

        <h5 class="fw-bold mb-3">Lead Details</h5>

        <p><strong>Email:</strong> {{ $contact->email ?? 'N/A' }}</p>
        <p><strong>Phone:</strong> {{ $contact->phone ?? 'N/A' }}</p>

        <hr>

        <h6 class="text-gold fw-bold">Notes</h6>
        <p>{{ $contact->notes ?? 'No notes added.' }}</p>

    </div>
</div>

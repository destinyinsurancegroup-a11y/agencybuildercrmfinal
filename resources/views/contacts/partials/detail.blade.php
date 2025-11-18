<div class="card shadow-sm border-0">

    <div class="card-header bg-black text-gold fw-bold d-flex justify-content-between">
        <span>{{ $contact->full_name }}</span>
        <a href="{{ route('contacts.edit', $contact->id) }}" class="btn btn-sm btn-primary">Edit</a>
    </div>

    <div class="card-body">

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="text-muted">Email</label>
                <div>{{ $contact->email ?: '—' }}</div>
            </div>
            <div class="col-md-6">
                <label class="text-muted">Phone</label>
                <div>{{ $contact->phone ?: '—' }}</div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="text-muted">Contact Type</label>
                <div>{{ $contact->contact_type ?: '—' }}</div>
            </div>
            <div class="col-md-6">
                <label class="text-muted">Status</label>
                <div>{{ $contact->status ?: '—' }}</div>
            </div>
        </div>

        <div class="mb-3">
            <label class="text-muted">Address</label>
            <div>
                {{ $contact->address_line1 }}<br>
                {{ $contact->city }} {{ $contact->state }} {{ $contact->postal_code }}
            </div>
        </div>

        <hr>

        <h5 class="mt-4">Notes</h5>
        <p class="text-muted">Notes panel will be added soon.</p>

        <h5 class="mt-4">Recent Activity</h5>
        <p class="text-muted">Activity feed will be added soon.</p>

    </div>

</div>

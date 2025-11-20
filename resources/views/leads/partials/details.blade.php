<div class="p-4">

    <div class="card shadow-sm border-0 p-4">

        <!-- BIG NAME HEADER (REPLACES OLD SMALL HEADER) -->
        <h1 class="fw-bold mb-3" style="font-size: 32px;">
            {{ $contact->first_name }} {{ $contact->last_name }}
        </h1>

        <!-- DISPOSITION BUTTONS UNDER NAME -->
        <div class="d-flex gap-2 mb-4">
            <button type="button" class="btn btn-success btn-sm px-3"
                    onclick="alert('Sold action coming soon…');">
                Sold
            </button>

            <button type="button" class="btn btn-warning btn-sm px-3"
                    onclick="alert('Follow Up action coming soon…');">
                Follow Up
            </button>

            <button type="button" class="btn btn-danger btn-sm px-3"
                    onclick="alert('Not Interested action coming soon…');">
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
                <p><strong>Email:</strong> {{ $contact->email ?? '—' }}</p>
                <p><strong>Contact Type:</strong> Lead</p>
            </div>

            <div class="col-md-6">
                <p><strong>Phone:</strong> {{ $contact->phone ?? '—' }}</p>
                <p><strong>Status:</strong> 
                    <span class="badge bg-secondary">{{ $contact->status ?? 'New' }}</span>
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

        <!-- TABS (Details / Notes / Documents) -->
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
                <p>{{ $contact->notes ?? 'No notes added.' }}</p>
            </div>

            <!-- DOCUMENTS TAB -->
            <div class="tab-pane fade" id="docsTab">
                <h5 class="fw-bold">Documents</h5>
                <p class="text-muted">Document uploads coming soon…</p>
            </div>

        </div>

    </div>

</div>

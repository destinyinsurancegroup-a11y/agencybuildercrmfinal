<div class="p-4">

    <div class="card shadow-sm border-0 p-4">

        <!-- BIG NAME HEADER -->
        <h1 class="fw-bold mb-3" style="font-size: 32px;">
            {{ $client->first_name }} {{ $client->last_name }}
        </h1>

        <!-- TOP RIGHT EDIT BUTTON (AJAX) -->
        <div class="text-end mb-3">
            <button 
                type="button"
                class="btn-gold"
                data-edit-url="{{ route('book.edit.panel', $client->id) }}"
                onclick="loadBookPanel(this.dataset.editUrl)"
            >
                Edit
            </button>
        </div>

        <hr>

        <!-- DETAILS SECTION -->
        <div class="row mb-4">

            <div class="col-md-6">
                <p><strong>Email:</strong> {{ $client->email ?: '—' }}</p>
                <p><strong>Phone:</strong> {{ $client->phone ?: '—' }}</p>

                <p><strong>Date of Birth:</strong>
                    @if($client->date_of_birth)
                        {{ \Carbon\Carbon::parse($client->date_of_birth)->format('m/d/Y') }}
                    @else
                        —
                    @endif
                </p>

                @if(method_exists($client, 'getAttribute') && $client->age ?? false)
                    <p><strong>Age:</strong> {{ $client->age }}</p>
                @endif
            </div>

            <div class="col-md-6">
                <p><strong>Contact Type:</strong> Book of Business</p>

                <p><strong>Policy Type:</strong>
                    {{ $client->policy_type ?: '—' }}
                </p>

                <p><strong>Face Amount:</strong>
                    {{ $client->face_amount ? '$' . number_format($client->face_amount, 2) : '—' }}
                </p>

                <p><strong>Premium Amount:</strong>
                    {{ $client->premium_amount ? '$' . number_format($client->premium_amount, 2) : '—' }}
                </p>

                <p><strong>Premium Due Date:</strong>
                    @if($client->premium_due_date)
                        {{ \Carbon\Carbon::parse($client->premium_due_date)->format('m/d/Y') }}
                    @else
                        —
                    @endif
                </p>
            </div>

        </div>

        <!-- ADDRESS -->
        <div class="mb-4">
            <p><strong>Address</strong></p>

            @if($client->address_line1)
                <p>
                    {{ $client->address_line1 }}<br>
                    @if($client->address_line2) {{ $client->address_line2 }}<br> @endif
                    {{ $client->city }} {{ $client->state }} {{ $client->postal_code }}
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
                <h5 class="fw-bold">Policy Details</h5>
                <p class="text-muted">
                    Use this area to show carrier, policy number, issue date, riders, etc. (future enhancement).
                </p>
            </div>

            <!-- NOTES TAB -->
            <div class="tab-pane fade" id="notesTab">
                <h5 class="fw-bold">Notes</h5>
                <p>{{ $client->notes ?: 'No notes added.' }}</p>
            </div>

            <!-- DOCUMENTS TAB -->
            <div class="tab-pane fade" id="docsTab">
                <h5 class="fw-bold">Documents</h5>
                <p class="text-muted">Document uploads coming soon…</p>
            </div>

        </div>

    </div>

</div>
